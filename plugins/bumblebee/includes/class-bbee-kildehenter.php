<?php
/**
 * Henter teksten bak kunnskapskildene (CPT «kunnskapskilde»).
 *
 * Bygger på BBEE_Crawler (lenkefilter, robots.txt, URL-håndtering) og
 * BBEE_Pdf. Klassen skriver ikke til databasen. Den gir tilbake sidene,
 * og WP-CLI-kommandoen avgjør om de skal lagres.
 *
 * Regler:
 * - Bare kilder med maskinell_bruk = «ikke_reservert» hentes.
 * - Domener med en reservert eller ukjent kilde er sperret for alle lenker.
 * - Standardlisten i BBEE_Plugin::blocked_domains() gjelder også.
 * - robots.txt blir alltid respektert.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Kildehenter
 */
class BBEE_Kildehenter extends BBEE_Crawler {

	/* ---- Grenser (fra kildemålingen 12.11.2026) ---- */

	const MAKS_DYBDE              = 2;
	const MAKS_SIDER_PER_KILDE    = 40;        // Inkludert startsiden.
	const MAKS_FORSOK_PER_KILDE   = 100;       // Henteforsøk, også mislykkede.
	const MAKS_SIDER_PER_DOMENE   = 150;       // For hele byggingen (sider utenfor kildenes egne mapper).
	const MIN_DOMENE_ANDEL        = 10;        // Hver kilde får minst så mange sider på et delt domene.
	const MAKS_PDF_PER_KILDE      = 15;        // Mengden styres av MAKS_VEDLEGG_TEGN.
	const MAKS_VEDLEGG_TEGN       = 400000;    // Sum for PDF-er på dybde 2 per kilde (ca. 380 biter).
	const MAKS_PDF_SIDER          = 150;       // Lengre PDF-er kuttes etter side 150.
	const MAKS_PDF_BYTES          = 20971520;  // 20 MB.
	const MAKS_HTML_BYTES         = 5242880;   // 5 MB.
	const MAKS_TEGN_PER_SIDE      = 60000;
	const MAKS_TOKENS_PER_BYGGING = 15000000;  // Hard stopp, ca. 0,30 USD.
	const MIN_TEGN_PER_SIDE       = 200;
	const JS_SKALL_TEGN           = 500;
	const PAUSE_MS                = 500;       // Pause mellom kall til samme vert.

	/** @var array Sperrede verter (uten www.). */
	protected $sperret = array();
	/** @var array vert => antall lagrede sider i denne byggingen (utenfor kildenes egne mapper). */
	protected $domene_sider = array();
	/** @var array vert => antall kilder som starter på denne verten. */
	protected $kilder_per_vert = array();
	/** @var array vert => antall kilder på verten som ikke er behandlet ennå. */
	protected $vert_igjen = array();
	/** @var array kilde-ID => true når kilden er behandlet (hentet eller lest fra cache). */
	protected $kilde_startet = array();
	/** @var array kilde-ID => (vert => antall sider utenfor egen mappe). */
	protected $kilde_domene = array();
	/** @var array URL => kilde-ID som allerede har hentet den. */
	protected $sett_url = array();
	/** @var array md5 av tekst => URL. */
	protected $sett_hash = array();
	/** @var array vert => grunn til at vi stoppet (f.eks. 429). */
	protected $vert_stoppet = array();
	/** @var array vert => tidspunkt for siste kall. */
	protected $siste_kall = array();
	/** @var callable|null Logg-funksjon. */
	protected $logg;

	/**
	 * @param BBEE_Indexer  $indexer Indekserer.
	 * @param callable|null $logg    Logg-funksjon (tar én streng).
	 */
	public function __construct( $indexer, $logg = null ) {
		parent::__construct( $indexer );
		$this->logg    = $logg;
		$this->sperret = self::sperreliste();
		// Tell kildene per vert. Da kan domenekvoten deles rettferdig.
		foreach ( self::alle_kilder() as $k ) {
			if ( 'ikke_reservert' !== $k['status'] || ! preg_match( '#^https?://#i', $k['url'] ) || $this->er_sperret( $k['url'] ) ) {
				continue;
			}
			$v                           = self::vert( $k['url'] );
			$this->kilder_per_vert[ $v ] = isset( $this->kilder_per_vert[ $v ] ) ? $this->kilder_per_vert[ $v ] + 1 : 1;
		}
		$this->vert_igjen = $this->kilder_per_vert;
	}

	/* ---- Rettferdig domenekvote ---- */

	/**
	 * Andelen av domenekvoten som hver kilde på verten har krav på.
	 *
	 * @param string $vert Vert.
	 * @return int
	 */
	protected function domene_andel( $vert ) {
		$n = isset( $this->kilder_per_vert[ $vert ] ) ? max( 1, $this->kilder_per_vert[ $vert ] ) : 1;
		return max( self::MIN_DOMENE_ANDEL, (int) ceil( self::MAKS_SIDER_PER_DOMENE / $n ) );
	}

	/**
	 * Marker at en kilde er behandlet. Da frigjøres reserven den hadde på verten.
	 *
	 * @param int    $kilde_id Kilde-ID.
	 * @param string $start    Start-URL.
	 */
	protected function start_kilde( $kilde_id, $start ) {
		if ( isset( $this->kilde_startet[ $kilde_id ] ) ) {
			return;
		}
		$this->kilde_startet[ $kilde_id ] = true;
		$v                                = self::vert( $start );
		if ( isset( $this->vert_igjen[ $v ] ) && $this->vert_igjen[ $v ] > 0 ) {
			$this->vert_igjen[ $v ]--;
		}
	}

	/**
	 * Er det plass til én side til fra denne verten for denne kilden?
	 *
	 * Regel: Hver kilde har alltid rett på sin andel (MAKS_SIDER_PER_DOMENE delt
	 * på antall kilder på verten, minst MIN_DOMENE_ANDEL). Utover andelen kan
	 * kilden bruke ledig kvote, men aldri den andelen som er holdt av til kilder
	 * som ikke er behandlet ennå. Slik avgjør ikke rekkefølgen hvem som får plass.
	 *
	 * @param string $vert     Vert.
	 * @param int    $kilde_id Kilde-ID.
	 * @return bool
	 */
	protected function domene_plass( $vert, $kilde_id ) {
		$andel = $this->domene_andel( $vert );
		$brukt = isset( $this->kilde_domene[ $kilde_id ][ $vert ] ) ? $this->kilde_domene[ $kilde_id ][ $vert ] : 0;
		if ( $brukt < $andel ) {
			return true;
		}
		$totalt    = isset( $this->domene_sider[ $vert ] ) ? $this->domene_sider[ $vert ] : 0;
		$reservert = $andel * ( isset( $this->vert_igjen[ $vert ] ) ? $this->vert_igjen[ $vert ] : 0 );
		return $totalt + $reservert < self::MAKS_SIDER_PER_DOMENE;
	}

	/**
	 * Tell én lagret side utenfor kildens egen mappe.
	 *
	 * @param string $vert     Vert.
	 * @param int    $kilde_id Kilde-ID.
	 */
	protected function tell_domene( $vert, $kilde_id ) {
		$this->domene_sider[ $vert ]             = isset( $this->domene_sider[ $vert ] ) ? $this->domene_sider[ $vert ] + 1 : 1;
		$this->kilde_domene[ $kilde_id ][ $vert ] = isset( $this->kilde_domene[ $kilde_id ][ $vert ] ) ? $this->kilde_domene[ $kilde_id ][ $vert ] + 1 : 1;
	}

	/**
	 * Årstall i en URL (f.eks. /uploads/2025/03/), eller 0. Brukes for å hente
	 * nyere dokumenter først når lenkene ellers har lik prioritet.
	 *
	 * @param string $url URL.
	 * @return int
	 */
	protected static function aar_i_url( $url ) {
		$sti = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( ! preg_match_all( '/(?<!\d)(19[5-9]\d|20\d\d)(?!\d)/', $sti, $m ) ) {
			return 0;
		}
		$aar = (int) max( array_map( 'intval', $m[1] ) );
		return $aar <= (int) gmdate( 'Y' ) + 1 ? $aar : 0;
	}

	/* ---- Kilder og sperreliste ---- */

	/**
	 * Alle publiserte kunnskapskilder med status og lenke.
	 *
	 * @return array
	 */
	public static function alle_kilder() {
		$ids = get_posts(
			array(
				'post_type'      => BBEE_Indexer::KILDE_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$out[] = array(
				'id'     => (int) $id,
				'tittel' => html_entity_decode( wp_strip_all_tags( get_the_title( $id ) ), ENT_QUOTES, 'UTF-8' ),
				'url'    => trim( (string) get_post_meta( $id, 'ekstern_lenke', true ) ),
				'status' => (string) get_post_meta( $id, 'maskinell_bruk', true ),
			);
		}
		return $out;
	}

	/**
	 * Verter som aldri skal hentes: alle verter der katalogen har en kilde
	 * merket «reservert». Verter med en «ukjent» kilde sperres også (føre var).
	 *
	 * @return array
	 */
	public static function sperreliste() {
		$verter = array();
		foreach ( self::alle_kilder() as $k ) {
			if ( 'ikke_reservert' === $k['status'] ) {
				continue;
			}
			$h = self::vert( $k['url'] );
			if ( '' !== $h ) {
				$verter[ $h ] = true;
			}
		}
		$verter = array_keys( $verter );
		sort( $verter );
		return apply_filters( 'bbee_kilde_sperreliste', $verter );
	}

	/**
	 * @return array
	 */
	public function sperrede_verter() {
		return $this->sperret;
	}

	/**
	 * Vert uten «www.», små bokstaver.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function vert( $url ) {
		$h = wp_parse_url( (string) $url, PHP_URL_HOST );
		return $h ? preg_replace( '/^www\./', '', strtolower( $h ) ) : '';
	}

	/**
	 * Er URL-en sperret (standardlisten eller sperrelisten fra katalogen)?
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public function er_sperret( $url ) {
		if ( BBEE_Plugin::is_blocked_url( $url ) ) {
			return true;
		}
		$h = self::vert( $url );
		if ( '' === $h ) {
			return true;
		}
		foreach ( $this->sperret as $d ) {
			if ( $h === $d || substr( $h, -( strlen( $d ) + 1 ) ) === '.' . $d ) {
				return true;
			}
		}
		return false;
	}

	/* ---- Henting av én kilde ---- */

	/**
	 * Hent startsiden og lenker til og med dybde 2.
	 *
	 * @param int    $kilde_id Kunnskapskilde-ID.
	 * @param string $start    Ekstern lenke.
	 * @return array {status, grunn, sider[], hoppet[], forsok}
	 */
	public function hent_kilde( $kilde_id, $start ) {
		$resultat = array(
			'status' => 'ok',
			'grunn'  => '',
			'sider'  => array(),
			'hoppet' => array(),
			'forsok' => 0,
		);

		// Sikkerhetsnett: aldri hent en kilde som ikke er «ikke_reservert».
		if ( ! BBEE_Indexer::kilde_kan_hentes( $kilde_id ) ) {
			$resultat['status'] = 'hoppet';
			$resultat['grunn']  = 'ikke «ikke_reservert»';
			return $resultat;
		}

		$start = $this->normalize_url( $start );
		if ( ! preg_match( '#^https?://#i', $start ) ) {
			$resultat['status'] = 'hoppet';
			$resultat['grunn']  = 'ugyldig lenke';
			return $resultat;
		}
		if ( $this->er_sperret( $start ) ) {
			$resultat['status'] = 'hoppet';
			$resultat['grunn']  = 'sperret domene';
			return $resultat;
		}

		$this->start_kilde( $kilde_id, $start );
		$kilde = array(
			'host'        => self::vert( $start ),
			'same_domain' => true,
			'path_prefix' => '',
			'start_dir'   => $this->katalog( $start ),
		);
		// Sider under startsiden hører til kilden selv. De teller ikke mot
		// domenegrensen (ellers kan en stor kilde på samme domene, f.eks. et
		// helt nettsted, ta hele kvoten før en egen rapport-kilde slipper til).
		$egen_rot = $this->egen_rot( $start );

		$sett    = array( $this->nokkel( $start ) => true );
		$nivaa   = array( array( 'url' => $start, 'prio' => 0 ) );
		$antall  = 0;
		$pdfer   = 0;
		$bare_lenker = false; // Startsiden er bare en inngang: følg lenker, lagre ikke tekst.

		for ( $dybde = 0; $dybde <= self::MAKS_DYBDE && ! empty( $nivaa ); $dybde++ ) {
			// Høyest prioritet først. Ved lik prioritet: nyeste årstall i URL-en først
			// (f.eks. /uploads/2026/05/ før /uploads/2021/10/), ellers rekkefølgen på siden.
			foreach ( $nivaa as $i => $item ) {
				$nivaa[ $i ]['nr']  = $i;
				$nivaa[ $i ]['aar'] = self::aar_i_url( $item['url'] );
			}
			usort(
				$nivaa,
				function ( $a, $b ) {
					if ( $a['prio'] !== $b['prio'] ) {
						return $b['prio'] - $a['prio'];
					}
					if ( $a['aar'] !== $b['aar'] ) {
						return $b['aar'] - $a['aar'];
					}
					return $a['nr'] - $b['nr'];
				}
			);
			$neste = array();

			foreach ( $nivaa as $item ) {
				if ( $antall >= self::MAKS_SIDER_PER_KILDE || $resultat['forsok'] >= self::MAKS_FORSOK_PER_KILDE ) {
					break 2;
				}
				$url  = $item['url'];
				$vert = self::vert( $url );

				// Startsiden hentes alltid, også om en annen kilde har lagret den.
				// Da følger vi lenkene herfra. Teksten lagres ikke på nytt
				// (duplikatsjekken på teksten under stopper det).
				if ( $dybde > 0 && isset( $this->sett_url[ $this->nokkel( $url ) ] ) ) {
					$resultat['hoppet'][ $url ] = 'hentet for kilde ' . $this->sett_url[ $this->nokkel( $url ) ];
					continue;
				}
				$egen = 0 === $dybde || ( '' !== $egen_rot && 0 === strpos( $this->nokkel( $url ) . '/', $egen_rot ) );
				if ( ! $egen && ! $this->domene_plass( $vert, $kilde_id ) ) {
					$resultat['hoppet'][ $url ] = 'domenegrense';
					continue;
				}
				$er_pdf = $this->is_pdf_url( $url );
				if ( $er_pdf && $pdfer >= self::MAKS_PDF_PER_KILDE ) {
					$resultat['hoppet'][ $url ] = 'pdf-grense';
					continue;
				}

				$resultat['forsok']++;
				$svar = $this->hent_url( $url, $er_pdf );
				if ( is_wp_error( $svar ) ) {
					$resultat['hoppet'][ $url ] = $svar->get_error_message();
					if ( 0 === $dybde ) {
						$resultat['status'] = 'feil';
						$resultat['grunn']  = $svar->get_error_message();
					}
					continue;
				}

				$endelig = $svar['url'];
				if ( $dybde > 0 && $endelig !== $url && isset( $this->sett_url[ $this->nokkel( $endelig ) ] ) ) {
					$resultat['hoppet'][ $url ] = 'hentet for kilde ' . $this->sett_url[ $this->nokkel( $endelig ) ];
					continue;
				}

				if ( $svar['pdf'] ) {
					if ( $pdfer >= self::MAKS_PDF_PER_KILDE ) {
						$resultat['hoppet'][ $url ] = 'pdf-grense';
						continue;
					}
					$side = $this->les_pdf( $svar['body'], $endelig );
					if ( is_wp_error( $side ) ) {
						$this->logg( sprintf( '  PDF hoppet over (kilde %d): %s – %s', $kilde_id, $side->get_error_message(), $endelig ) );
						$resultat['hoppet'][ $url ] = $side->get_error_message();
						if ( 0 === $dybde ) {
							$resultat['status'] = 'feil';
							$resultat['grunn']  = $side->get_error_message();
						}
						continue;
					}
					$pdfer++;
					$lenker = array();
				} else {
					$side = $this->trekk_ut( $svar['body'], $endelig );
					// En startside med lite egen tekst er ofte en inngangsside med lenker
					// til selve innholdet. Da følger vi lenkene, men lagrer ikke teksten.
					if ( 0 === $dybde && ! $side['js_skall'] && ! $side['bot_vern'] && ! empty( $side['links'] )
						&& $this->mb_len( $side['text'] ) < self::MIN_TEGN_PER_SIDE ) {
						$bare_lenker = true;
						$resultat['hoppet'][ $url ] = 'lite tekst (bare lenker fulgt)';
					} elseif ( $side['js_skall'] || $this->mb_len( $side['text'] ) < self::MIN_TEGN_PER_SIDE ) {
						$grunn = $side['js_skall'] ? 'js-skall' : 'lite tekst';
						if ( $side['bot_vern'] ) {
							$grunn = 'bot-vern';
						}
						$resultat['hoppet'][ $url ] = $grunn;
						if ( 0 === $dybde ) {
							$resultat['status'] = 'hoppet';
							$resultat['grunn']  = $grunn;
						}
						continue;
					}
					// Sider uten fagverdi (kontakt, karriere osv.), sjekket på tittelen.
					if ( $dybde > 0 ) {
						$stoy = self::stoy_grunn( $endelig, $side['title'] );
						if ( '' !== $stoy ) {
							$resultat['hoppet'][ $url ] = $stoy;
							continue;
						}
					}
					// Sider på andre språk enn norsk, svensk, dansk og engelsk lagres ikke.
					// Er det startsiden, følger vi lenkene likevel (som for en inngangsside).
					if ( ! $bare_lenker ) {
						$sprak = self::side_sprak_grunn( $side['text'] );
						if ( '' !== $sprak ) {
							$this->logg( sprintf( '  Side droppet (kilde %d): %s – %s', $kilde_id, $sprak, $endelig ) );
							$resultat['hoppet'][ $url ] = $sprak;
							if ( $dybde > 0 ) {
								continue;
							}
							$bare_lenker = true;
						}
					}
					$lenker = $side['links'];
				}

				$this->sett_url[ $this->nokkel( $url ) ]     = $kilde_id;
				$this->sett_url[ $this->nokkel( $endelig ) ] = $kilde_id;

				$hash = md5( preg_replace( '/\s+/u', ' ', $side['text'] ) );
				if ( $bare_lenker ) {
					$bare_lenker = false;
				} elseif ( isset( $this->sett_hash[ $hash ] ) ) {
					$resultat['hoppet'][ $url ] = 'lik tekst som ' . $this->sett_hash[ $hash ];
				} else {
					$this->sett_hash[ $hash ] = $endelig;
					// Bare sider utenfor kildens egen mappe teller mot domenekvoten.
					if ( ! $egen ) {
						$this->tell_domene( self::vert( $endelig ), $kilde_id );
					}
					$antall++;
					$resultat['sider'][] = array(
						'url'    => $endelig,
						'tittel' => $side['title'],
						'tekst'  => $side['text'],
						'type'   => $svar['pdf'] ? 'pdf' : 'html',
						'dybde'  => $dybde,
						'merk'   => isset( $side['merk'] ) ? $side['merk'] : '',
					);
				}

				// Startsiden kan ha blitt omdirigert til en annen vert.
				if ( 0 === $dybde && ! $svar['pdf'] ) {
					$kilde['host']      = self::vert( $endelig );
					$kilde['start_dir'] = $this->katalog( $endelig );
					if ( $endelig !== $start ) {
						$egen_rot = $this->egen_rot( $endelig );
					}
				}

				if ( $dybde < self::MAKS_DYBDE ) {
					foreach ( $lenker as $lenke ) {
						$abs = $this->filtrer_lenke( $lenke['href'], $endelig, $kilde );
						if ( ! $abs || isset( $sett[ $this->nokkel( $abs ) ] ) ) {
							continue;
						}
						$sett[ $this->nokkel( $abs ) ] = true;
						$prio         = $lenke['i_hoved'] ? 2 : 0;
						if ( '' !== $kilde['start_dir'] && 0 === strpos( $abs, $kilde['start_dir'] ) ) {
							$prio++;
						}
						if ( $this->is_pdf_url( $abs ) ) {
							$prio++;
						}
						$neste[] = array( 'url' => $abs, 'prio' => $prio );
					}
				}
			}
			$nivaa = $neste;
		}

		if ( 'ok' === $resultat['status'] && empty( $resultat['sider'] ) ) {
			$resultat['status'] = 'tom';
			$resultat['grunn']  = 'ingen ny tekst';
		}
		return $resultat;
	}

	/**
	 * Registrer et resultat som er hentet tidligere (fra cache), slik at
	 * domenegrenser og duplikatsjekk også teller disse sidene.
	 *
	 * @param int    $kilde_id Kilde-ID.
	 * @param array  $resultat Resultat fra hent_kilde().
	 * @param string $start    Kildens start-URL (for å skille egne sider fra andre).
	 */
	public function registrer( $kilde_id, $resultat, $start = '' ) {
		$this->start_kilde( $kilde_id, $start );
		$egen_rot = '' !== $start ? $this->egen_rot( $this->normalize_url( $start ) ) : '';
		foreach ( (array) $resultat['sider'] as $s ) {
			$this->sett_url[ $this->nokkel( $s['url'] ) ] = $kilde_id;
			$this->sett_hash[ md5( preg_replace( '/\s+/u', ' ', $s['tekst'] ) ) ] = $s['url'];
			$dybde = isset( $s['dybde'] ) ? (int) $s['dybde'] : 0;
			$egen  = 0 === $dybde || ( '' !== $egen_rot && 0 === strpos( $this->nokkel( $s['url'] ) . '/', $egen_rot ) );
			if ( ! $egen ) {
				$this->tell_domene( self::vert( $s['url'] ), $kilde_id );
			}
		}
	}

	/* ---- HTTP ---- */

	/**
	 * Hent én URL. Følger omdirigeringer selv, slik at hver ny adresse
	 * blir sjekket mot sperrelisten og robots.txt før vi kontakter den.
	 *
	 * @param string $url    URL.
	 * @param bool   $er_pdf Forventer PDF.
	 * @return array|WP_Error {url, body, pdf}
	 */
	protected function hent_url( $url, $er_pdf = false ) {
		$maks    = $er_pdf ? self::MAKS_PDF_BYTES : self::MAKS_HTML_BYTES;
		$nytt    = 0; // Antall nye forsøk etter tidsavbrudd eller serverfeil.

		for ( $i = 0; $i <= 5; $i++ ) {
			if ( $this->er_sperret( $url ) ) {
				return new WP_Error( 'bbee_sperret', 'sperret domene' );
			}
			$vert = self::vert( $url );
			if ( isset( $this->vert_stoppet[ $vert ] ) ) {
				return new WP_Error( 'bbee_stoppet', 'verten er stoppet (' . $this->vert_stoppet[ $vert ] . ')' );
			}
			if ( ! $this->robots_allowed( $url ) ) {
				return new WP_Error( 'bbee_robots', 'sperret av robots.txt' );
			}
			$this->pause( $vert );

			$svar = wp_remote_get(
				$url,
				array(
					'timeout'             => $er_pdf ? 120 : 20,
					'redirection'         => 0,
					'user-agent'          => $this->user_agent(),
					'headers'             => array( 'Accept' => $er_pdf ? 'application/pdf,*/*;q=0.8' : 'text/html,application/xhtml+xml,application/pdf;q=0.9,*/*;q=0.5' ),
					'limit_response_size' => $maks + 1,
				)
			);
			$kode = is_wp_error( $svar ) ? 0 : (int) wp_remote_retrieve_response_code( $svar );
			// Tidsavbrudd, brutt forbindelse og 502/503/504 er ofte forbigående.
			// Prøv én gang til etter en kort pause.
			if ( ( is_wp_error( $svar ) || in_array( $kode, array( 502, 503, 504 ), true ) ) && $nytt < 1 ) {
				$nytt++;
				$i--;
				sleep( 3 );
				continue;
			}
			if ( is_wp_error( $svar ) ) {
				return new WP_Error( 'bbee_http', 'nettverksfeil: ' . $svar->get_error_message() );
			}

			if ( in_array( $kode, array( 301, 302, 303, 307, 308 ), true ) ) {
				$til = wp_remote_retrieve_header( $svar, 'location' );
				if ( is_array( $til ) ) {
					$til = end( $til );
				}
				$til = $this->absolutize( $url, trim( (string) $til ) );
				if ( ! $til || ! preg_match( '#^https?://#i', $til ) ) {
					return new WP_Error( 'bbee_redirect', 'ugyldig omdirigering' );
				}
				$url = $this->normalize_url( $til );
				if ( preg_match( '#/(verify|captcha|challenge)(/|\?|$)#i', $url ) ) {
					return new WP_Error( 'bbee_botvern', 'bot-vern' );
				}
				continue;
			}
			if ( 429 === $kode ) {
				$this->vert_stoppet[ $vert ] = 'HTTP 429';
				return new WP_Error( 'bbee_429', 'HTTP 429 (for mange kall)' );
			}
			$body = (string) wp_remote_retrieve_body( $svar );
			// 202 på en vanlig GET betyr i praksis bot-vern (f.eks. eur-lex).
			if ( 202 === $kode ) {
				return new WP_Error( 'bbee_botvern', 'bot-vern (HTTP 202)' );
			}
			if ( $kode < 200 || $kode >= 300 ) {
				return new WP_Error( 'bbee_http', 'HTTP ' . $kode );
			}
			if ( strlen( $body ) > $maks ) {
				return new WP_Error( 'bbee_stor', $er_pdf ? 'PDF over 20 MB' : 'side over 5 MB' );
			}
			if ( '' === trim( $body ) ) {
				return new WP_Error( 'bbee_tom', 'tomt svar' );
			}

			$ctype = strtolower( (string) wp_remote_retrieve_header( $svar, 'content-type' ) );
			$pdf   = ( 0 === strpos( $body, '%PDF' ) ) || false !== strpos( $ctype, 'application/pdf' );
			if ( $pdf && 0 !== strpos( ltrim( $body ), '%PDF' ) ) {
				return new WP_Error( 'bbee_pdf', 'ugyldig PDF' );
			}
			if ( $er_pdf && ! $pdf ) {
				return new WP_Error( 'bbee_pdf', 'PDF-lenken gir ikke en PDF (død lenke?)' );
			}
			if ( ! $pdf && '' !== $ctype && false === strpos( $ctype, 'html' ) ) {
				return new WP_Error( 'bbee_ctype', 'ikke HTML (' . $ctype . ')' );
			}
			return array( 'url' => $url, 'body' => $body, 'pdf' => $pdf );
		}
		return new WP_Error( 'bbee_redirect', 'for mange omdirigeringer' );
	}

	/**
	 * Vent til det har gått PAUSE_MS siden forrige kall til samme vert.
	 *
	 * @param string $vert Vert.
	 */
	protected function pause( $vert ) {
		if ( isset( $this->siste_kall[ $vert ] ) ) {
			$gaatt = ( microtime( true ) - $this->siste_kall[ $vert ] ) * 1000;
			if ( $gaatt < self::PAUSE_MS ) {
				usleep( (int) ( ( self::PAUSE_MS - $gaatt ) * 1000 ) );
			}
		}
		$this->siste_kall[ $vert ] = microtime( true );
	}

	/* ---- PDF ---- */

	/**
	 * Les tekst fra en PDF. Bare de første MAKS_PDF_SIDER sidene blir med.
	 *
	 * @param string $data Binærdata.
	 * @param string $url  URL.
	 * @return array|WP_Error {title, text, merk}
	 */
	protected function les_pdf( $data, $url ) {
		if ( ! BBEE_Pdf::available() ) {
			return new WP_Error( 'bbee_pdf', 'PDF-leser mangler' );
		}
		try {
			$config = new \Smalot\PdfParser\Config();
			$config->setRetainImageContent( false );
			$parser = new \Smalot\PdfParser\Parser( array(), $config );
			$pdf    = $parser->parseContent( $data );
			$sider  = $pdf->getPages();
			$totalt = count( $sider );
			$deler  = array();
			$uleselig = 0;
			$annet_sprak = array(); // Grunn => antall sider.
			foreach ( array_slice( $sider, 0, self::MAKS_PDF_SIDER ) as $side ) {
				try {
					// PDF-leseren kan gi ugyldig UTF-8. Uten retting blir hele teksten tom.
					// Noen fonter gir NUL-tegn mellom bokstavene («E\0t\0t\0e\0r»). Fjern dem,
					// ellers blir de til mellomrom og ordene blir uleselige.
					$t = self::gyldig_utf8( str_replace( "\0", '', (string) $side->getText() ) );
				} catch ( \Throwable $e ) {
					unset( $e );
					continue;
				}
				// Sperret tekst («E t t e r s p ø r s e l») slås sammen til ord.
				// Sider som fortsatt bare har løse tegn (fonter uten tegnkart), droppes.
				if ( $this->er_uleselig( $t ) ) {
					$t = $this->slaa_sammen_sperret( $t );
					if ( $this->er_uleselig( $t ) ) {
						$uleselig++;
						continue;
					}
				}
				// Linjer med tegnrot fjernes. Sider med tegnrot eller på et annet språk droppes.
				$t = self::rens_tegnrot( $t );
				if ( '' !== self::tegnrot_grunn( $t ) ) {
					$uleselig++;
					continue;
				}
				$sprak = self::sprak_grunn( $t );
				if ( '' !== $sprak ) {
					$annet_sprak[ $sprak ] = isset( $annet_sprak[ $sprak ] ) ? $annet_sprak[ $sprak ] + 1 : 1;
					continue;
				}
				$deler[] = $t;
			}
			$tittel = '';
			$detaljer = $pdf->getDetails();
			if ( ! empty( $detaljer['Title'] ) && is_string( $detaljer['Title'] ) ) {
				$tittel = trim( preg_replace( '/^Microsoft (Word|PowerPoint) - /i', '', $detaljer['Title'] ) );
			}
		} catch ( \Throwable $e ) {
			return new WP_Error( 'bbee_pdf', 'PDF-feil: ' . $e->getMessage() );
		}

		$tekst = $this->rens_pdf_tekst( implode( "\n\n", $deler ) );
		$n_annet = array_sum( $annet_sprak );
		if ( $n_annet > 0 ) {
			arsort( $annet_sprak );
			$this->logg( sprintf( '  PDF-sider på annet språk droppet: %d (%s) – %s', $n_annet, key( $annet_sprak ), $url ) );
		}
		if ( $this->mb_len( $tekst ) < self::MIN_TEGN_PER_SIDE ) {
			if ( $n_annet > 0 && $n_annet >= $uleselig ) {
				return new WP_Error( 'bbee_pdf', sprintf( 'PDF på annet språk: %s (%d av %d sider)', key( $annet_sprak ), $n_annet, $totalt ) );
			}
			if ( $uleselig > 0 ) {
				return new WP_Error( 'bbee_pdf', sprintf( 'PDF med uleselig tekst (%d av %d sider)', $uleselig, $totalt ) );
			}
			return new WP_Error( 'bbee_pdf', 'PDF uten tekstlag (skannet?)' );
		}
		if ( '' === $tittel || $this->mb_len( $tittel ) < 4 ) {
			$sti    = wp_parse_url( $url, PHP_URL_PATH );
			$tittel = $sti ? rawurldecode( basename( $sti ) ) : '';
		}
		// Filnavn som bare er en hash eller en kode, gir ingen mening som tittel.
		if ( preg_match( '/^[0-9a-f_-]{12,}(\.pdf)?$|^[A-Z]?\d{5,}(\.pdf)?$/i', $tittel ) ) {
			$tittel = '';
		}
		$merk = $totalt > self::MAKS_PDF_SIDER ? sprintf( 'kuttet: %d av %d sider', self::MAKS_PDF_SIDER, $totalt ) : $totalt . ' sider';
		if ( $uleselig > 0 ) {
			$merk .= sprintf( ', %d uleselige sider droppet', $uleselig );
		}
		if ( $n_annet > 0 ) {
			$merk .= sprintf( ', %d sider på annet språk droppet', $n_annet );
		}
		return array( 'title' => $tittel, 'text' => $tekst, 'merk' => $merk );
	}

	/**
	 * @param string $text Tekst.
	 * @return string
	 */
	protected function rens_pdf_tekst( $text ) {
		$text = self::gyldig_utf8( (string) $text );
		$text = (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', ' ', $text );
		$text = (string) preg_replace( "/[ \t]+/u", ' ', $text );
		$text = (string) preg_replace( "/\s*\n\s*/u", "\n", $text );
		return trim( $text );
	}

	/**
	 * Slå sammen sperret tekst: bokstaver med ett mellomrom mellom seg blir
	 * ett ord. Ord var skilt med flere mellomrom, og de blir stående.
	 * Brukes bare på sider som ellers regnes som uleselige.
	 *
	 * @param string $t Tekst.
	 * @return string
	 */
	protected function slaa_sammen_sperret( $t ) {
		$ut = preg_replace_callback(
			'/(?:\p{L}\p{M}* ){2,}\p{L}\p{M}*/u',
			function ( $m ) {
				return str_replace( ' ', '', $m[0] );
			},
			(string) $t
		);
		return null === $ut ? (string) $t : $ut;
	}

	/**
	 * Er teksten på en PDF-side uleselig (løse tegn uten ord)? Dette skjer når
	 * fonten mangler tegnkart. Tall og tabeller regnes ikke som uleselige:
	 * vi ser bare på tegn som ikke er tall eller mellomrom.
	 *
	 * @param string $t Tekst fra én side.
	 * @return bool
	 */
	protected function er_uleselig( $t ) {
		$rest = (string) preg_replace( '/[\s\d]+/u', '', (string) $t );
		$n    = $this->mb_len( $rest );
		if ( $n < 40 ) {
			return false;
		}
		preg_match_all( '/\p{L}{3,}/u', (string) $t, $m );
		$i_ord = 0;
		foreach ( $m[0] as $ord ) {
			$i_ord += $this->mb_len( $ord );
		}
		return $i_ord / $n < 0.3;
	}

	/* ---- HTML-uttrekk ---- */

	/**
	 * Trekk ut hovedinnholdet. Menyer, topptekst og bunntekst fjernes.
	 * Innhold i <details> og sammenslåtte avsnitt blir med (det ligger i HTML-en).
	 *
	 * @param string $html HTML.
	 * @param string $base URL.
	 * @return array {title, text, links[], js_skall, bot_vern}
	 */
	protected function trekk_ut( $html, $base ) {
		$ut = array( 'title' => '', 'text' => '', 'links' => array(), 'js_skall' => false, 'bot_vern' => false );
		if ( '' === trim( $html ) || ! class_exists( 'DOMDocument' ) ) {
			return $ut;
		}

		$prev = libxml_use_internal_errors( true );
		$dom  = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_COMPACT );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xp = new DOMXPath( $dom );

		$titler = $dom->getElementsByTagName( 'title' );
		if ( $titler->length > 0 ) {
			$ut['title'] = trim( preg_replace( '/\s+/u', ' ', $titler->item( 0 )->textContent ) );
		}
		$h1 = $dom->getElementsByTagName( 'h1' );
		if ( '' === $ut['title'] && $h1->length > 0 ) {
			$ut['title'] = trim( preg_replace( '/\s+/u', ' ', $h1->item( 0 )->textContent ) );
		}
		if ( preg_match( '/just a moment|attention required|verify you are human|access denied/i', $ut['title'] ) ) {
			$ut['bot_vern'] = true;
			$ut['js_skall'] = true;
			return $ut;
		}

		// Velg hovedinnhold: <main> / role=main, så én <article>, ellers <body>.
		$node = null;
		foreach ( array( '//main', "//*[@role='main']", "//*[@id='main-content' or @id='maincontent' or @id='content' or @id='main']" ) as $q ) {
			$liste = $xp->query( $q );
			if ( $liste && $liste->length > 0 && $this->mb_len( trim( $liste->item( 0 )->textContent ) ) > self::MIN_TEGN_PER_SIDE ) {
				$node = $liste->item( 0 );
				break;
			}
		}
		if ( null === $node ) {
			$art = $dom->getElementsByTagName( 'article' );
			if ( 1 === $art->length ) {
				$node = $art->item( 0 );
			}
		}
		if ( null === $node ) {
			$body = $dom->getElementsByTagName( 'body' );
			$node = $body->length > 0 ? $body->item( 0 ) : null;
		}
		if ( null === $node ) {
			return $ut;
		}

		// Lenker: først fra hovedinnholdet (høyere prioritet), så resten av siden.
		$sett = array();
		foreach ( $xp->query( './/a[@href]', $node ) as $a ) {
			$href = trim( $a->getAttribute( 'href' ) );
			if ( '' !== $href && ! isset( $sett[ $href ] ) ) {
				$sett[ $href ]  = true;
				$ut['links'][] = array( 'href' => $href, 'i_hoved' => true );
			}
		}
		foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
			$href = trim( $a->getAttribute( 'href' ) );
			if ( '' !== $href && ! isset( $sett[ $href ] ) ) {
				$sett[ $href ]  = true;
				$ut['links'][] = array( 'href' => $href, 'i_hoved' => false );
			}
		}

		// Fjern støy inne i den valgte noden.
		$fjern = array();
		foreach ( array( 'script', 'style', 'noscript', 'template', 'svg', 'iframe', 'form', 'button', 'nav', 'aside', 'select' ) as $tag ) {
			foreach ( $xp->query( './/' . $tag, $node ) as $n ) {
				$fjern[] = $n;
			}
		}
		// Topptekst og bunntekst: bare de som ikke hører til en artikkel.
		foreach ( $xp->query( './/header[not(ancestor::article)] | .//footer[not(ancestor::article)]', $node ) as $n ) {
			$fjern[] = $n;
		}
		foreach ( $xp->query( ".//*[@role='navigation' or @role='banner' or @role='contentinfo' or @role='search' or @role='dialog' or @aria-modal='true']", $node ) as $n ) {
			$fjern[] = $n;
		}
		// Klasser/ID-er som nesten alltid er støy: sidemenyer, innholdsfortegnelser,
		// deling, cookie-bannere og alt som er merket «ikke til utskrift».
		// Ord som «share» må stå alene (skilt med mellomrom, - eller _),
		// så f.eks. «sharepoint» ikke treffer.
		$stoy = '/cookie|consent|print:hidden|(^|[\s_-])(breadcrumbs?|brodsmuler?|skip-?link|share|sharing|social|some|newsletter|nyhetsbrev|related|relatert|pagination|sidebar|toc|table-of-contents|innholdsfortegnelse|no-print|d-print-none|hidden-print|sr-only|visually-hidden|screen-reader-text)([\s_-]|$)/';
		foreach ( $xp->query( './/*[@class or @id]', $node ) as $n ) {
			$attr = strtolower( $n->getAttribute( 'class' ) . ' ' . $n->getAttribute( 'id' ) );
			if ( preg_match( $stoy, $attr ) ) {
				$fjern[] = $n;
			}
		}
		// Skjulte overlegg (f.eks. «skriver ut»). Lengre skjult tekst er ofte
		// sammenslåtte avsnitt, og den beholder vi.
		foreach ( $xp->query( './/*[@hidden]', $node ) as $n ) {
			if ( $this->mb_len( trim( $n->textContent ) ) < 200 ) {
				$fjern[] = $n;
			}
		}

		// Vern: fjern aldri noe som har mer enn 60 % av teksten i hovedinnholdet.
		// Kode fjernes først, så teller vi teksten som er igjen.
		$kode = array( 'script', 'style', 'noscript', 'template', 'svg' );
		foreach ( $fjern as $n ) {
			if ( $n->parentNode && in_array( strtolower( $n->nodeName ), $kode, true ) ) {
				$n->parentNode->removeChild( $n );
			}
		}
		// Vern: fjern aldri en blokk som holder selve innholdet (har <h1> eller
		// <article>), eller som har over 90 % av teksten. En lang innholdsfortegnelse
		// i en sidemeny kan ha mye tekst, men har ikke <h1>.
		$total = max( 1, $this->mb_len( $node->textContent ) );
		foreach ( $fjern as $n ) {
			if ( ! $n->parentNode ) {
				continue;
			}
			$holder_innhold = 'article' === strtolower( $n->nodeName ) || $xp->query( './/h1 | .//article', $n )->length > 0;
			if ( $holder_innhold || $this->mb_len( $n->textContent ) > 0.9 * $total ) {
				continue;
			}
			$n->parentNode->removeChild( $n );
		}

		$text = $this->html_til_tekst( $dom->saveHTML( $node ) );
		if ( $this->mb_len( $text ) > self::MAKS_TEGN_PER_SIDE ) {
			$text = mb_substr( $text, 0, self::MAKS_TEGN_PER_SIDE );
		}
		$ut['text'] = $text;

		if ( $this->mb_len( $text ) < self::JS_SKALL_TEGN
			&& preg_match( '/id=["\'](root|app|__next|__nuxt)["\']|ng-version|data-reactroot|<noscript>[^<]*javascript/i', $html ) ) {
			$ut['js_skall'] = true;
		}
		if ( '' === $ut['title'] ) {
			$ut['title'] = $base;
		}
		return $ut;
	}

	/**
	 * HTML til ren tekst. Blokker gir linjeskift, tabellceller skilles med «|».
	 *
	 * @param string $html Fragment.
	 * @return string
	 */
	protected function html_til_tekst( $html ) {
		$html = preg_replace( '#<br[^>]*>#i', "\n", $html );
		$html = preg_replace( '#</t[dh]>#i', ' | ', $html );
		$html = preg_replace( '#</?(p|div|li|ul|ol|dl|dt|dd|section|article|main|details|summary|h[1-6]|tr|table|thead|tbody|blockquote|figure|figcaption|header|footer|pre)[^>]*>#i', "\n", $html );
		$text = wp_strip_all_tags( $html, false );
		return $this->clean_text( $text );
	}

	/* ---- Lenker ---- */

	/**
	 * Godta en lenke? HTML-sider: bare samme domene. PDF-er: alle domener
	 * som ikke er sperret.
	 *
	 * @param string $href  Lenke.
	 * @param string $base  Side-URL.
	 * @param array  $kilde Kildeinfo (host, same_domain, path_prefix).
	 * @return string|null
	 */
	protected function filtrer_lenke( $href, $base, $kilde ) {
		$href = trim( (string) $href );
		if ( '' === $href || preg_match( '~^\s*(mailto:|tel:|javascript:|data:|#)~i', $href ) ) {
			return null;
		}
		$abs = $this->absolutize( $base, $href );
		if ( ! $abs || ! preg_match( '#^https?://#i', $abs ) ) {
			return null;
		}
		$abs = $this->normalize_url( html_entity_decode( $abs, ENT_QUOTES, 'UTF-8' ) );
		if ( $this->er_sperret( $abs ) ) {
			return null;
		}

		$sti   = (string) wp_parse_url( $abs, PHP_URL_PATH );
		$query = (string) wp_parse_url( $abs, PHP_URL_QUERY );
		if ( preg_match( '#/(login|logg-inn|logginn|wp-login|wp-admin|signin|sok|search|handlekurv|cart|checkout|feed|rss)(/|$)#i', $sti ) ) {
			return null;
		}
		if ( '' !== $query && preg_match( '/(^|&)(q|query|search|sok|s|filter|sort|page|side|print|share|utm_[a-z]+|replytocom)=/i', $query ) ) {
			return null;
		}

		if ( self::er_gammel_versjon( $abs ) ) {
			return null;
		}
		if ( $this->is_pdf_url( $abs ) ) {
			return BBEE_Pdf::available() ? $abs : null;
		}
		return $this->filter_link( $abs, $base, $kilde );
	}

	/**
	 * Som i BBEE_Crawler, men uten standardport (:443 / :80).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	protected function normalize_url( $url ) {
		// Ikke fjern skråstrek på slutten (som BBEE_Crawler gjør). Mange servere
		// sender oss da tilbake med en omdirigering, og vi går i ring.
		// Duplikatsjekken bruker nokkel() i stedet.
		$url  = trim( (string) $url );
		$hash = strpos( $url, '#' );
		if ( false !== $hash ) {
			$url = substr( $url, 0, $hash );
		}
		$url = preg_replace( '#^(https://[^/:]+):443(?=/|$|\?)#i', '$1', $url );
		$url = preg_replace( '#^(http://[^/:]+):80(?=/|$|\?)#i', '$1', $url );
		return $url;
	}

	/**
	 * Nøkkel for duplikatsjekk: uten «www.» og uten skråstrek på slutten.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	protected function nokkel( $url ) {
		$url = preg_replace( '#^(https?://)www\.#i', '$1', (string) $url );
		return rtrim( $url, '/' );
	}

	/**
	 * Roten for «kilden sine egne sider»: start-URL-en som nøkkel med «/» til
	 * slutt. Tom hvis startsiden er forsiden til et nettsted (da gjelder
	 * domenegrensen som vanlig) eller en fil.
	 *
	 * @param string $start Start-URL.
	 * @return string
	 */
	protected function egen_rot( $start ) {
		$sti = trim( (string) wp_parse_url( $start, PHP_URL_PATH ), '/' );
		if ( '' === $sti || $this->is_pdf_url( $start ) || preg_match( '#\.[a-z0-9]{2,5}$#i', $sti ) ) {
			return '';
		}
		return $this->nokkel( strtok( $start, '?' ) ) . '/';
	}

	/**
	 * Fjern sider som ikke skal lagres fra et resultat (nytt eller fra cache):
	 * - sider på sperrede domener (sikkerhetsnett),
	 * - sider uten fagverdi (ikke startsiden, ikke PDF-er),
	 * - eldre versjoner fra endringshistorikk, når kilden også har andre sider,
	 * - sider og PDF-er der hovedspråket ikke er norsk, svensk, dansk eller engelsk.
	 * Til slutt kuttes PDF-er på dybde 2 til vedleggsbudsjettet (se kutt_vedlegg()).
	 *
	 * @param array $resultat Resultat fra hent_kilde() eller cache.
	 * @return array {resultat, fjernet[], kuttet[]} der fjernet har url, tittel, grunn
	 *               og kuttet har url, fra, til (tegn).
	 */
	public function filtrer_resultat( $resultat ) {
		$fjernet = array();
		$beholdt = array();
		$gamle   = array();
		foreach ( (array) $resultat['sider'] as $s ) {
			$dybde = isset( $s['dybde'] ) ? (int) $s['dybde'] : 0;
			$grunn = '';
			if ( $this->er_sperret( $s['url'] ) ) {
				$grunn = 'sperret domene';
			} elseif ( $dybde > 0 && 'pdf' !== $s['type'] ) {
				$grunn = self::stoy_grunn( $s['url'], $s['tittel'] );
			}
			if ( '' === $grunn ) {
				// Hovedspråket for hele siden. Blandede sider (f.eks. et EU-dokument med
				// alle språk) blir stående, og bitene sjekkes én for én ved lagring.
				$grunn = self::side_sprak_grunn( $s['tekst'] );
			}
			if ( '' === $grunn && $dybde > 0 && self::er_gammel_versjon( $s['url'] ) ) {
				$gamle[] = $s;
				continue;
			}
			if ( '' !== $grunn ) {
				$fjernet[] = array( 'url' => $s['url'], 'tittel' => $s['tittel'], 'grunn' => $grunn );
				continue;
			}
			$beholdt[] = $s;
		}
		// Eldre versjoner fjernes bare når kilden har en gjeldende side igjen.
		foreach ( $gamle as $s ) {
			if ( ! empty( $beholdt ) ) {
				$fjernet[] = array( 'url' => $s['url'], 'tittel' => $s['tittel'], 'grunn' => 'eldre versjon (endringshistorikk)' );
			} else {
				$beholdt[] = $s;
			}
		}
		$kuttet            = array();
		$resultat['sider'] = $this->kutt_vedlegg( $beholdt, $kuttet );
		return array( 'resultat' => $resultat, 'fjernet' => $fjernet, 'kuttet' => $kuttet );
	}

	/**
	 * Vedleggsbudsjett: PDF-er på dybde 2 (lenket fra en underside, ikke fra
	 * kildens startside) deler MAKS_VEDLEGG_TEGN tegn per kilde.
	 *
	 * Hvorfor: Slike PDF-er er vedlegg til kilden, ikke selve kilden. Uten tak
	 * kan én kilde fylle indeksen med dokumenter utenfor sitt eget tema (f.eks.
	 * bankenes årsrapporter under PCAF: 10 PDF-er, 3,7 mill. tegn). Startsiden og
	 * PDF-er lenket direkte fra den (dybde 0 og 1) får ikke noe slikt tak.
	 *
	 * Fordeling: Små dokumenter beholdes helt. Det som er igjen av budsjettet,
	 * deles likt mellom de store, og de kuttes ved et linjeskift. Starten av et
	 * dokument (sammendrag, innledning) blir alltid med.
	 *
	 * @param array $sider  Sider.
	 * @param array $kuttet Fylles med {url, fra, til}.
	 * @return array
	 */
	protected function kutt_vedlegg( $sider, &$kuttet = null ) {
		$kuttet = array();
		$lengde = array();
		foreach ( $sider as $i => $s ) {
			if ( 'pdf' === $s['type'] && isset( $s['dybde'] ) && (int) $s['dybde'] >= 2 ) {
				$lengde[ $i ] = $this->mb_len( $s['tekst'] );
			}
		}
		if ( array_sum( $lengde ) <= self::MAKS_VEDLEGG_TEGN ) {
			return $sider;
		}
		// Finn taket: minste først, hver får sin del av det som er igjen.
		asort( $lengde );
		$igjen = self::MAKS_VEDLEGG_TEGN;
		$antall = count( $lengde );
		$tak   = 0;
		$nr    = 0;
		foreach ( $lengde as $l ) {
			$del = (int) floor( $igjen / ( $antall - $nr ) );
			if ( $l > $del ) {
				$tak = $del;
				break;
			}
			$igjen -= $l;
			$nr++;
		}
		foreach ( $lengde as $i => $l ) {
			if ( $l <= $tak ) {
				continue;
			}
			$tekst = mb_substr( $sider[ $i ]['tekst'], 0, $tak );
			$slutt = mb_strrpos( $tekst, "\n" );
			if ( false !== $slutt && $slutt > $tak * 0.8 ) {
				$tekst = mb_substr( $tekst, 0, $slutt );
			}
			$sider[ $i ]['tekst'] = $tekst;
			$sider[ $i ]['merk']  = trim( ( isset( $sider[ $i ]['merk'] ) ? $sider[ $i ]['merk'] : '' ) . sprintf( ', vedlegg kuttet til %d av %d tegn', $this->mb_len( $tekst ), $l ), ', ' );
			$kuttet[]             = array( 'url' => $sider[ $i ]['url'], 'fra' => $l, 'til' => $this->mb_len( $tekst ) );
		}
		return $sider;
	}

	/**
	 * Katalogen til en URL (for å prioritere lenker under startsiden).
	 *
	 * @param string $url URL.
	 * @return string
	 */
	protected function katalog( $url ) {
		$sti = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $sti || '/' === $sti ) {
			return '';
		}
		$pos = strrpos( $url, '/' );
		return false !== $pos ? substr( $url, 0, $pos + 1 ) : '';
	}

	/**
	 * @param string $s Streng.
	 * @return int
	 */
	protected function mb_len( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $s ) : strlen( (string) $s );
	}

	/**
	 * @param string $melding Melding.
	 */
	protected function logg( $melding ) {
		if ( is_callable( $this->logg ) ) {
			call_user_func( $this->logg, $melding );
		}
	}
}
