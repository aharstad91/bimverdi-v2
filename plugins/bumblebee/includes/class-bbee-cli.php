<?php
/**
 * WP-CLI-kommandoer for Bumblebee.
 *
 *   wp bbee hent-kilder [--limit=N] [--ids=1,2] [--dry-run] [--cache=<mappe>] [--fortsett] [--hent-pa-nytt=1,2] [--rapport=<fil>]
 *   wp bbee embed [--maks-tokens=N] [--batch=N]   (synker vektorlageret til slutt)
 *   wp bbee indekser [--type=kunnskapskilde]
 *   wp bbee status
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_CLI
 */
class BBEE_CLI {

	/** @var BBEE_Indexer */
	protected $indexer;

	/**
	 * @param BBEE_Indexer $indexer Indekserer.
	 */
	public function __construct( $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * Registrer kommandoene.
	 *
	 * @param BBEE_Indexer $indexer Indekserer.
	 */
	public static function register( $indexer ) {
		$cli = new self( $indexer );
		WP_CLI::add_command( 'bbee hent-kilder', array( $cli, 'hent_kilder' ) );
		WP_CLI::add_command( 'bbee embed', array( $cli, 'embed' ) );
		WP_CLI::add_command( 'bbee indekser', array( $cli, 'indekser' ) );
		WP_CLI::add_command( 'bbee status', array( $cli, 'status' ) );
	}

	/**
	 * Hent teksten bak kunnskapskildene og lagre den som tekstbiter (uten embeddings).
	 *
	 * Bare kilder med maskinell_bruk = ikke_reservert blir hentet.
	 * Kjør «wp bbee embed» etterpå for å lage embeddings.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Hent bare de første N kildene.
	 *
	 * [--ids=<ider>]
	 * : Hent bare disse kildene (kommaseparerte ID-er).
	 *
	 * [--dry-run]
	 * : Hent og tell, men skriv ingenting til databasen.
	 *
	 * [--cache=<mappe>]
	 * : Med --dry-run: lagre hentet tekst her. Uten --dry-run: bruk teksten herfra i stedet for å hente på nytt.
	 *
	 * [--rapport=<fil>]
	 * : Skriv en JSON-rapport per kilde til denne filen.
	 *
	 * [--fortsett]
	 * : Med --dry-run og --cache: bruk kilder som allerede ligger i cache, og hent bare resten.
	 *
	 * [--hent-pa-nytt=<ider>]
	 * : Med --cache: hent disse kildene på nytt fra nettet (kommaseparerte ID-er), og skriv
	 *   resultatet til cachen. Alle andre kilder leses fra cachen. Slik holder grensene per domene
	 *   og duplikatsjekken, fordi alle kilder går gjennom i samme rekkefølge som før.
	 *
	 * [--del=<del>]
	 * : Formen i/n, f.eks. 0/4. Ta bare del i av n (0-basert). Kildene fordeles etter domene, så grensen per domene holder.
	 *   Brukes for å hente i flere prosesser samtidig.
	 *
	 * @param array $args  Argumenter.
	 * @param array $assoc Flagg.
	 */
	public function hent_kilder( $args, $assoc ) {
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		wp_raise_memory_limit( 'admin' );

		$dry     = ! empty( $assoc['dry-run'] );
		$limit   = isset( $assoc['limit'] ) ? max( 0, (int) $assoc['limit'] ) : 0;
		$ids     = isset( $assoc['ids'] ) ? array_filter( array_map( 'intval', explode( ',', $assoc['ids'] ) ) ) : array();
		$cache   = isset( $assoc['cache'] ) ? untrailingslashit( $assoc['cache'] ) : '';
		$rapport = isset( $assoc['rapport'] ) ? $assoc['rapport'] : '';
		$fortsett = ! empty( $assoc['fortsett'] );
		$pa_nytt  = isset( $assoc['hent-pa-nytt'] ) ? array_filter( array_map( 'intval', explode( ',', $assoc['hent-pa-nytt'] ) ) ) : array();
		if ( ! empty( $pa_nytt ) && '' === $cache ) {
			WP_CLI::error( '--hent-pa-nytt krever --cache.' );
		}
		$del      = null;
		if ( isset( $assoc['del'] ) ) {
			if ( ! preg_match( '#^(\d+)/(\d+)$#', $assoc['del'], $dm ) || (int) $dm[2] < 1 || (int) $dm[1] >= (int) $dm[2] ) {
				WP_CLI::error( '--del må ha formen i/n, for eksempel 0/4.' );
			}
			$del = array( (int) $dm[1], (int) $dm[2] );
		}

		if ( '' !== $cache && ! is_dir( $cache ) && ! wp_mkdir_p( $cache ) ) {
			WP_CLI::error( 'Kan ikke opprette cache-mappen: ' . $cache );
		}

		$henter = new BBEE_Kildehenter(
			$this->indexer,
			function ( $m ) {
				WP_CLI::log( $m );
			}
		);

		$alle      = BBEE_Kildehenter::alle_kilder();
		$aktuelle  = array();
		$hoppet    = array();
		$grunntall = array();
		foreach ( $alle as $k ) {
			if ( 'ikke_reservert' !== $k['status'] ) {
				$g = 'reservert' === $k['status'] ? 'reservert' : 'ukjent eller tom status';
			} elseif ( ! preg_match( '#^https?://#i', $k['url'] ) ) {
				$g = 'mangler lenke';
			} elseif ( $henter->er_sperret( $k['url'] ) ) {
				$g = 'sperret domene (' . BBEE_Kildehenter::vert( $k['url'] ) . ')';
			} else {
				$aktuelle[] = $k;
				continue;
			}
			$hoppet[]  = array( 'id' => $k['id'], 'tittel' => $k['tittel'], 'url' => $k['url'], 'grunn' => $g );
			$gk        = preg_replace( '/ \(.*$/', '', $g );
			$grunntall[ $gk ] = isset( $grunntall[ $gk ] ) ? $grunntall[ $gk ] + 1 : 1;
		}

		WP_CLI::log( sprintf( 'Kunnskapskilder: %d publisert, %d kan hentes, %d hoppes over før henting.', count( $alle ), count( $aktuelle ), count( $hoppet ) ) );
		foreach ( $grunntall as $g => $n ) {
			WP_CLI::log( sprintf( '  - %s: %d', $g, $n ) );
		}
		WP_CLI::log( 'Sperrede verter fra katalogen: ' . implode( ', ', $henter->sperrede_verter() ) );
		WP_CLI::log( 'Standardsperre: ' . implode( ', ', BBEE_Plugin::blocked_domains() ) );

		// Rydd alltid bort hentet tekst for kilder som ikke (lenger) kan hentes.
		if ( ! $dry ) {
			$slettet = $this->slett_utdatert_kildetekst( wp_list_pluck( $aktuelle, 'id' ) );
			if ( $slettet > 0 ) {
				WP_CLI::log( sprintf( 'Slettet %d biter fra kilder som ikke lenger kan hentes.', $slettet ) );
			}
		}

		if ( ! empty( $ids ) ) {
			$aktuelle = array_values(
				array_filter(
					$aktuelle,
					function ( $k ) use ( $ids ) {
						return in_array( $k['id'], $ids, true );
					}
				)
			);
		}
		if ( $limit > 0 ) {
			$aktuelle = array_slice( $aktuelle, 0, $limit );
		}
		if ( null !== $del ) {
			$aktuelle = array_values(
				array_filter(
					$aktuelle,
					function ( $k ) use ( $del ) {
						$v = preg_replace( '/^([^.]+\.)*([^.]+\.[^.]+)$/', '$2', BBEE_Kildehenter::vert( $k['url'] ) );
						return ( crc32( $v ) % $del[1] ) === $del[0];
					}
				)
			);
		}

		$maks     = BBEE_Kildehenter::MAKS_TOKENS_PER_BYGGING;
		$fjernet_alle = array();
		$kuttet_alle  = array(); // PDF-er som ble kuttet til vedleggsbudsjettet.
		$bit_grunner  = array(); // Grunn => antall biter (eller linjer) som ble fjernet ved lagring.
		$bit_eksempel = array(); // Grunn => noen eksempler.
		$pdf_hoppet   = array();
		$gjenbrukt    = 0;
		$tokens   = 0;
		$sider    = 0;
		$biter    = 0;
		$tegn     = 0;
		$status   = array();
		$per      = array();
		$over     = false;
		$nr       = 0;

		foreach ( $aktuelle as $k ) {
			$nr++;
			$fil = '' !== $cache ? $cache . '/kilde-' . $k['id'] . '.json' : '';

			$fra_cache = ( ! $dry || $fortsett || ! empty( $pa_nytt ) ) && '' !== $fil && is_readable( $fil ) && ! in_array( $k['id'], $pa_nytt, true );
			if ( $fra_cache ) {
				$res = json_decode( (string) file_get_contents( $fil ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				if ( ! is_array( $res ) || ! isset( $res['sider'] ) ) {
					WP_CLI::error( 'Ugyldig cache-fil: ' . $fil );
				}
			} else {
				$res = $henter->hent_kilde( $k['id'], $k['url'] );
				if ( '' !== $fil && ( $dry || in_array( $k['id'], $pa_nytt, true ) ) ) {
					// Cachen får alltid råresultatet. Filteret under kjøres hver gang.
					file_put_contents( $fil, wp_json_encode( $res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				}
			}

			// Fjern støy, eldre versjoner og sider på sperrede domener før lagring.
			$filtrert = $henter->filtrer_resultat( $res );
			$res      = $filtrert['resultat'];
			if ( $fra_cache ) {
				$henter->registrer( $k['id'], $res, $k['url'] );
			}
			foreach ( $filtrert['kuttet'] as $f ) {
				$f['kilde']    = $k['id'];
				$kuttet_alle[] = $f;
			}
			foreach ( $filtrert['fjernet'] as $f ) {
				$f['kilde']     = $k['id'];
				$fjernet_alle[] = $f;
			}
			if ( '' === $res['grunn'] && 'ok' === $res['status'] && empty( $res['sider'] ) ) {
				$res['status'] = 'tom';
				$res['grunn']  = 'bare støy';
			}
			foreach ( (array) $res['hoppet'] as $hu => $hg ) {
				if ( 0 === strpos( (string) $hg, 'PDF' ) ) {
					$pdf_hoppet[] = array( 'kilde' => $k['id'], 'url' => $hu, 'grunn' => $hg );
				}
			}

			// Tell biter og tokens slik de blir lagret.
			$k_biter  = 0;
			$k_tegn   = 0;
			$lagre    = array();
			foreach ( $res['sider'] as $s ) {
				$tittel = $this->bit_tittel( $k['tittel'], $s['tittel'] );
				// Samme oppdeling som ved lagring: tegnrot og andre språk er fjernet.
				$droppet = array();
				$deler   = $this->indexer->kilde_biter( $s['tekst'], $droppet );
				foreach ( $droppet as $dr ) {
					$antall_dr               = isset( $dr['linjer'] ) ? count( $dr['linjer'] ) : 1;
					$bit_grunner[ $dr['grunn'] ] = isset( $bit_grunner[ $dr['grunn'] ] ) ? $bit_grunner[ $dr['grunn'] ] + $antall_dr : $antall_dr;
					if ( ! isset( $bit_eksempel[ $dr['grunn'] ] ) || count( $bit_eksempel[ $dr['grunn'] ] ) < 5 ) {
						$utdrag = isset( $dr['linjer'] ) ? implode( ' ⏎ ', array_slice( $dr['linjer'], 0, 4 ) ) : $dr['tekst'];
						$bit_eksempel[ $dr['grunn'] ][] = array(
							'kilde' => $k['id'],
							'url'   => $s['url'],
							'tekst' => mb_substr( preg_replace( '/\s+/u', ' ', $utdrag ), 0, 200 ),
						);
					}
				}
				foreach ( $deler as $d ) {
					$k_tegn += mb_strlen( $tittel ) + 1 + mb_strlen( $d );
				}
				$k_biter += count( $deler );
				$lagre[]  = array( $s['url'], $tittel, $s['tekst'] );
			}
			$k_tokens = BBEE_Indexer::estimate_tokens_from_chars( $k_tegn );

			if ( $tokens + $k_tokens > $maks ) {
				$over = true;
				if ( ! $dry ) {
					WP_CLI::error(
						sprintf(
							'Stopper før kilde %d: byggingen ville bli %s tokens, over grensen på %s. Ingenting er lagret for denne kilden. Senk grensene eller hev MAKS_TOKENS_PER_BYGGING.',
							$k['id'],
							number_format( $tokens + $k_tokens, 0, ',', ' ' ),
							number_format( $maks, 0, ',', ' ' )
						)
					);
				}
			}

			if ( ! $dry ) {
				// Biter med uendret tekst beholder embeddingen sin.
				$lagret     = $this->indexer->erstatt_kilde_sider( $k['id'], $lagre );
				$gjenbrukt += $lagret['gjenbrukt'];
			}

			$tokens += $k_tokens;
			$tegn   += $k_tegn;
			$biter  += $k_biter;
			$sider  += count( $res['sider'] );
			$status[ $res['status'] ] = isset( $status[ $res['status'] ] ) ? $status[ $res['status'] ] + 1 : 1;

			$pdf = 0;
			foreach ( $res['sider'] as $s ) {
				if ( 'pdf' === $s['type'] ) {
					$pdf++;
				}
			}
			WP_CLI::log(
				sprintf(
					'[%d/%d] %d %s: %s, %d sider (%d PDF), %d biter, ca. %s tokens%s',
					$nr,
					count( $aktuelle ),
					$k['id'],
					BBEE_Kildehenter::vert( $k['url'] ),
					$res['status'],
					count( $res['sider'] ),
					$pdf,
					$k_biter,
					number_format( $k_tokens, 0, ',', ' ' ),
					'' !== $res['grunn'] ? ' – ' . $res['grunn'] : ''
				)
			);

			$hoppet_grunner = array();
			foreach ( $res['hoppet'] as $grunn ) {
				$gk = preg_replace( '/^(hentet for kilde|lik tekst som) .*$/', '$1 …', $grunn );
				$hoppet_grunner[ $gk ] = isset( $hoppet_grunner[ $gk ] ) ? $hoppet_grunner[ $gk ] + 1 : 1;
			}
			$per[] = array(
				'id'             => $k['id'],
				'tittel'         => $k['tittel'],
				'url'            => $k['url'],
				'status'         => $res['status'],
				'grunn'          => $res['grunn'],
				'sider'          => count( $res['sider'] ),
				'pdf'            => $pdf,
				'biter'          => $k_biter,
				'tokens'         => $k_tokens,
				'forsok'         => $res['forsok'],
				'sider_liste'    => array_map(
					function ( $s ) {
						return array( 'url' => $s['url'], 'type' => $s['type'], 'dybde' => $s['dybde'], 'tegn' => mb_strlen( $s['tekst'] ), 'merk' => $s['merk'] );
					},
					$res['sider']
				),
				'hoppet_grunner' => $hoppet_grunner,
				'fjernet'        => count( $filtrert['fjernet'] ),
				'vedlegg_kuttet' => count( $filtrert['kuttet'] ),
			);
		}

		// Oppsummering av det som ble fjernet før lagring.
		$fjern_grunner = array();
		foreach ( $fjernet_alle as $f ) {
			$g                   = preg_replace( '/^(støy) \((url|liste|tittel).*$/u', '$1 ($2)', $f['grunn'] );
			$fjern_grunner[ $g ] = isset( $fjern_grunner[ $g ] ) ? $fjern_grunner[ $g ] + 1 : 1;
		}
		if ( ! empty( $fjernet_alle ) ) {
			WP_CLI::log( sprintf( 'Fjernet før lagring: %d sider.', count( $fjernet_alle ) ) );
			foreach ( $fjern_grunner as $g => $n ) {
				WP_CLI::log( sprintf( '  - %s: %d', $g, $n ) );
			}
		}
		if ( ! empty( $kuttet_alle ) ) {
			WP_CLI::log( sprintf( 'Vedlegg (PDF på dybde 2) kuttet til budsjettet på %s tegn per kilde: %d PDF-er.', number_format( BBEE_Kildehenter::MAKS_VEDLEGG_TEGN, 0, ',', ' ' ), count( $kuttet_alle ) ) );
			foreach ( $kuttet_alle as $f ) {
				WP_CLI::log( sprintf( '  - kilde %d: %s → %s tegn – %s', $f['kilde'], number_format( $f['fra'], 0, ',', ' ' ), number_format( $f['til'], 0, ',', ' ' ), $f['url'] ) );
			}
		}
		if ( ! empty( $bit_grunner ) ) {
			arsort( $bit_grunner );
			WP_CLI::log( 'Fjernet ved lagring (tegnrot og andre språk):' );
			foreach ( $bit_grunner as $g => $n ) {
				WP_CLI::log( sprintf( '  - %s: %d %s', $g, $n, 'tegnrot (linjer)' === $g ? 'linjer' : 'biter' ) );
			}
		}
		if ( ! empty( $pdf_hoppet ) ) {
			WP_CLI::log( sprintf( 'PDF-er som ikke ga tekst: %d (se rapporten).', count( $pdf_hoppet ) ) );
			foreach ( $pdf_hoppet as $p ) {
				WP_CLI::log( sprintf( '  - kilde %d: %s – %s', $p['kilde'], $p['grunn'], $p['url'] ) );
			}
		}

		$sammendrag = array(
			'tid'               => current_time( 'mysql' ),
			'dry_run'           => $dry,
			'kilder_publisert'  => count( $alle ),
			'kilder_forsokt'    => count( $aktuelle ),
			'kilder_status'     => $status,
			'hoppet_for_henting' => $grunntall,
			'sider'             => $sider,
			'biter'             => $biter,
			'tegn'              => $tegn,
			'tokens_anslag'     => $tokens,
			'fjernet_sider'     => count( $fjernet_alle ),
			'fjernet_grunner'   => $fjern_grunner,
			'vedlegg_kuttet'    => count( $kuttet_alle ),
			'biter_fjernet'     => $bit_grunner,
			'pdf_uten_tekst'    => count( $pdf_hoppet ),
			'biter_gjenbrukt'   => $gjenbrukt,
			'kostnad_usd'       => round( $tokens / 1000000 * 0.02, 4 ),
			'maks_tokens'       => $maks,
			'grenser'           => array(
				'dybde'           => BBEE_Kildehenter::MAKS_DYBDE,
				'sider_per_kilde' => BBEE_Kildehenter::MAKS_SIDER_PER_KILDE,
				'sider_per_domene' => BBEE_Kildehenter::MAKS_SIDER_PER_DOMENE,
				'pdf_per_kilde'   => BBEE_Kildehenter::MAKS_PDF_PER_KILDE,
				'vedlegg_tegn'    => BBEE_Kildehenter::MAKS_VEDLEGG_TEGN,
				'domene_andel_min' => BBEE_Kildehenter::MIN_DOMENE_ANDEL,
				'pdf_sider'       => BBEE_Kildehenter::MAKS_PDF_SIDER,
				'tegn_per_side'   => BBEE_Kildehenter::MAKS_TEGN_PER_SIDE,
			),
		);
		if ( ! $dry ) {
			update_option( 'bbee_kilde_siste_bygging', $sammendrag, false );
		}

		if ( '' !== $rapport ) {
			file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions
				$rapport,
				wp_json_encode(
					array( 'sammendrag' => $sammendrag, 'hoppet_for_henting' => $hoppet, 'kilder' => $per, 'fjernet' => $fjernet_alle, 'vedlegg_kuttet' => $kuttet_alle, 'biter_fjernet_eksempler' => $bit_eksempel, 'pdf_uten_tekst' => $pdf_hoppet ),
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
				)
			);
			WP_CLI::log( 'Rapport: ' . $rapport );
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '%s: %d kilder, %d sider, %d biter.', $dry ? 'Prøvekjøring (ingenting lagret)' : 'Lagret', count( $aktuelle ), $sider, $biter ) );
		if ( ! $dry ) {
			WP_CLI::log( sprintf( 'Biter som beholdt embeddingen (uendret tekst): %d. Nye biter som trenger embedding: %d.', $gjenbrukt, $biter - $gjenbrukt ) );
		}
		WP_CLI::log( sprintf( 'Anslag: %s tokens til embeddings, ca. %s USD (grense %s).', number_format( $tokens, 0, ',', ' ' ), number_format( $tokens / 1000000 * 0.02, 3, ',', ' ' ), number_format( $maks, 0, ',', ' ' ) ) );

		if ( $over ) {
			WP_CLI::error( 'Anslaget er over grensen på ' . number_format( $maks, 0, ',', ' ' ) . ' tokens. Ikke kjør full henting med disse grensene.' );
		}
		WP_CLI::success( $dry ? 'Prøvekjøring ferdig.' : 'Henting ferdig. Kjør «wp bbee embed» for å lage embeddings.' );
	}

	/**
	 * Lag embeddings for alle biter som mangler. Stopper hvis anslaget er over grensen.
	 *
	 * ## OPTIONS
	 *
	 * [--maks-tokens=<n>]
	 * : Øvre grense for tokens i denne kjøringen. Standard er MAKS_TOKENS_PER_BYGGING.
	 *
	 * [--batch=<n>]
	 * : Biter per API-kall. Standard 64.
	 *
	 * @param array $args  Argumenter.
	 * @param array $assoc Flagg.
	 */
	public function embed( $args, $assoc ) {
		@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$maks  = isset( $assoc['maks-tokens'] ) ? (int) $assoc['maks-tokens'] : BBEE_Kildehenter::MAKS_TOKENS_PER_BYGGING;
		$batch = isset( $assoc['batch'] ) ? max( 1, min( 256, (int) $assoc['batch'] ) ) : 64;

		$embed = new BBEE_Embeddings();
		if ( ! $embed->is_configured() ) {
			WP_CLI::error( 'Embeddings er ikke satt opp (mangler nøkkel).' );
		}
		$model   = $embed->model_id();
		$venter  = $this->indexer->count_pending( $model );
		$anslag  = $this->indexer->estimate_pending_tokens( $model );
		WP_CLI::log( sprintf( 'Venter: %d biter, ca. %s tokens (%s). Grense: %s.', $venter, number_format( $anslag, 0, ',', ' ' ), $model, number_format( $maks, 0, ',', ' ' ) ) );
		if ( 0 === $venter ) {
			$this->synk_vektorlager( $model );
			WP_CLI::success( 'Ingen biter mangler embedding.' );
			return;
		}
		if ( $anslag > $maks ) {
			WP_CLI::error( 'Anslaget er over grensen. Ingen kall er sendt.' );
		}

		// Unngå at cron kjører samme jobb samtidig.
		wp_clear_scheduled_hook( 'bbee_embed_pending' );

		$brukt  = 0;
		$ferdig = 0;
		$kall   = 0;
		$feil   = 0;
		while ( true ) {
			$r = $this->indexer->embed_pending_batch( $batch );
			$kall++;
			if ( ! empty( $r['error'] ) ) {
				$feil++;
				WP_CLI::warning( 'Feil fra API: ' . $r['error'] );
				if ( $feil >= 3 ) {
					$this->logg_embed( $brukt, $ferdig, 'stoppet etter 3 feil' );
					WP_CLI::error( sprintf( 'Stopper etter 3 feil. Brukt %s tokens, %d biter ferdig.', number_format( $brukt, 0, ',', ' ' ), $ferdig ) );
				}
				sleep( 5 * $feil );
				continue;
			}
			$feil    = 0;
			$brukt  += (int) $r['tokens'];
			$ferdig += (int) $r['done'];
			if ( 0 === $kall % 20 || 0 === (int) $r['remaining'] ) {
				WP_CLI::log( sprintf( '  %d biter ferdig, %d igjen, %s tokens brukt', $ferdig, (int) $r['remaining'], number_format( $brukt, 0, ',', ' ' ) ) );
			}
			if ( $brukt > $maks ) {
				$this->logg_embed( $brukt, $ferdig, 'stoppet ved grensen' );
				WP_CLI::error( 'Brukte tokens er over grensen. Stopper.' );
			}
			if ( 0 === (int) $r['remaining'] || 0 === (int) $r['done'] ) {
				break;
			}
		}
		$this->logg_embed( $brukt, $ferdig, 'ferdig' );
		$this->synk_vektorlager( $model );
		WP_CLI::success( sprintf( '%d biter fikk embedding. Tokens brukt (ifølge API-et): %s, ca. %s USD.', $ferdig, number_format( $brukt, 0, ',', ' ' ), number_format( $brukt / 1000000 * 0.02, 4, ',', ' ' ) ) );
	}

	/**
	 * Indekser katalogtekst på nytt for én innholdstype (uten embeddings).
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Innholdstype. Standard: kunnskapskilde.
	 *
	 * @param array $args  Argumenter.
	 * @param array $assoc Flagg.
	 */
	public function indekser( $args, $assoc ) {
		$type = isset( $assoc['type'] ) ? sanitize_key( $assoc['type'] ) : BBEE_Indexer::KILDE_POST_TYPE;
		$ids  = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
		$n    = 0;
		foreach ( $ids as $id ) {
			$n += $this->indexer->index_post( get_post( $id ) );
		}
		wp_clear_scheduled_hook( 'bbee_embed_pending' );
		WP_CLI::success( sprintf( '%d innlegg av typen %s ga %d biter. Kjør «wp bbee embed».', count( $ids ), $type, $n ) );
	}

	/**
	 * Vis antall biter per type og hvor mange som mangler embedding.
	 *
	 * @param array $args  Argumenter.
	 * @param array $assoc Flagg.
	 */
	public function status( $args, $assoc ) {
		global $wpdb;
		$table = BBEE_Indexer::table_name();
		$embed = new BBEE_Embeddings();
		$model = $embed->model_id();

		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rader = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source_type, post_type, COUNT(*) AS biter,
					SUM(embedding IS NULL OR embedding = '' OR embed_model <> %s) AS uten_embedding,
					COUNT(DISTINCT post_id) AS innlegg,
					SUM(CHAR_LENGTH(title) + 1 + CHAR_LENGTH(content)) AS tegn
				FROM {$table} GROUP BY source_type, post_type ORDER BY source_type, post_type",
				$model
			),
			ARRAY_A
		);
		$sum = array( 'biter' => 0, 'uten' => 0, 'tegn' => 0 );
		$ut  = array();
		foreach ( $rader as $r ) {
			$ut[]          = array(
				'source_type'    => $r['source_type'],
				'post_type'      => $r['post_type'],
				'innlegg'        => (int) $r['innlegg'],
				'biter'          => (int) $r['biter'],
				'uten_embedding' => (int) $r['uten_embedding'],
				'tokens_anslag'  => BBEE_Indexer::estimate_tokens_from_chars( (int) $r['tegn'] ),
			);
			$sum['biter'] += (int) $r['biter'];
			$sum['uten']  += (int) $r['uten_embedding'];
			$sum['tegn']  += (int) $r['tegn'];
		}
		WP_CLI\Utils\format_items( 'table', $ut, array( 'source_type', 'post_type', 'innlegg', 'biter', 'uten_embedding', 'tokens_anslag' ) );
		WP_CLI::log( sprintf( 'Totalt: %d biter, %d uten embedding, ca. %s tokens. Modell: %s.', $sum['biter'], $sum['uten'], number_format( BBEE_Indexer::estimate_tokens_from_chars( $sum['tegn'] ), 0, ',', ' ' ), $model ) );

		$siste = get_option( 'bbee_kilde_siste_bygging', array() );
		if ( ! empty( $siste ) ) {
			WP_CLI::log( sprintf( 'Siste henting: %s, %d kilder, %d sider, %d biter, ca. %s tokens.', $siste['tid'], $siste['kilder_forsokt'], $siste['sider'], $siste['biter'], number_format( $siste['tokens_anslag'], 0, ',', ' ' ) ) );
		}
		$logg = get_option( 'bbee_embed_logg', array() );
		if ( ! empty( $logg ) ) {
			$l = end( $logg );
			WP_CLI::log( sprintf( 'Siste embedding: %s, %d biter, %s tokens (%s).', $l['tid'], $l['biter'], number_format( $l['tokens'], 0, ',', ' ' ), $l['status'] ) );
		}
		if ( class_exists( 'BBEE_Vector_Store' ) ) {
			$st = BBEE_Vector_Store::state( $model );
			WP_CLI::log( sprintf( 'Vektorlager: %d vektorer, %d biter med embedding (%s): %s.', $st['vectors'], $st['chunks'], $model, BBEE_Vector_Store::in_sync( $model ) ? 'i takt' : 'IKKE i takt – kjør «wp bbee embed»' ) );
		}
	}

	/* ---- Hjelpere ---- */

	/**
	 * Bring vektorlageret (wp_bbee_vectors) i takt med bit-tabellen. Uten
	 * tidsgrense, så alle nye biter blir søkbare med en gang.
	 *
	 * @param string $model Modell-ID.
	 */
	protected function synk_vektorlager( $model ) {
		if ( ! class_exists( 'BBEE_Vector_Store' ) ) {
			WP_CLI::warning( 'Vektorlageret (BBEE_Vector_Store) finnes ikke. Hopper over synk.' );
			return;
		}
		$r = BBEE_Vector_Store::sync( $model, 0.0 );
		WP_CLI::log( sprintf( 'Vektorlager: %d lagt til, %d fjernet, %s.', (int) $r['added'], (int) $r['removed'], BBEE_Vector_Store::in_sync( $model ) ? 'i takt med bitene' : 'IKKE i takt' ) );
	}

	/**
	 * Slett hentet tekst for kilder som ikke står i listen over kilder som kan hentes.
	 *
	 * @param array $tillatte Kilde-ID-er som kan hentes.
	 * @return int
	 */
	protected function slett_utdatert_kildetekst( $tillatte ) {
		global $wpdb;
		$table    = BBEE_Indexer::table_name();
		$tillatte = array_map( 'intval', (array) $tillatte );
		if ( empty( $tillatte ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return (int) $wpdb->delete( $table, array( 'source_type' => BBEE_Indexer::KILDE_SOURCE_TYPE ), array( '%s' ) );
		}
		$in = implode( ',', $tillatte );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE source_type = %s AND post_id NOT IN ({$in})", BBEE_Indexer::KILDE_SOURCE_TYPE ) );
	}

	/**
	 * Tittel på en bit: kildenavnet, og sidetittelen hvis den er annerledes.
	 *
	 * @param string $kilde Kildenavn.
	 * @param string $side  Sidetittel.
	 * @return string
	 */
	protected function bit_tittel( $kilde, $side ) {
		$side = trim( preg_replace( '/\s+/u', ' ', (string) $side ) );
		$t    = $kilde;
		if ( '' !== $side && false === mb_stripos( $kilde, $side ) && false === mb_stripos( $side, $kilde ) ) {
			$t = $kilde . ' – ' . $side;
		} elseif ( '' !== $side && mb_strlen( $side ) > mb_strlen( $kilde ) ) {
			$t = $side;
		}
		return mb_substr( $t, 0, 250 );
	}

	/**
	 * Lagre tokenbruk for embeddings (siste 50 kjøringer).
	 *
	 * @param int    $tokens Tokens.
	 * @param int    $biter  Biter.
	 * @param string $status Status.
	 */
	protected function logg_embed( $tokens, $biter, $status ) {
		$logg   = get_option( 'bbee_embed_logg', array() );
		$logg   = is_array( $logg ) ? $logg : array();
		$logg[] = array( 'tid' => current_time( 'mysql' ), 'tokens' => (int) $tokens, 'biter' => (int) $biter, 'status' => $status );
		update_option( 'bbee_embed_logg', array_slice( $logg, -50 ), false );
	}
}
