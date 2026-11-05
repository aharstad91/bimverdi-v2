<?php
/**
 * Selve HTTP-sjekken av én lenke.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Sjekker {

	const MAKS_HOPP = 5;

	private BV_Lenkevakt_Innstillinger $innstillinger;

	/** Siste gang vi kontaktet et gitt vertsnavn — brukes til å bremse oss selv. */
	private array $sist_kontakt = array();

	public function __construct( BV_Lenkevakt_Innstillinger $innstillinger ) {
		$this->innstillinger = $innstillinger;
	}

	/**
	 * Sjekk én URL og returner et resultat.
	 *
	 * @return array{status:string,kode:int,endelig_url:string,permanent:bool,melding:string,hopp:array}
	 */
	public function sjekk( string $url ): array {
		$url = trim( $url );

		if ( '' === $url ) {
			return $this->resultat( BV_Lenkevakt::STATUS_MANGLER, 0, '', false, 'Ingen lenke registrert på kilden.' );
		}

		$deler = wp_parse_url( $url );
		if ( empty( $deler['scheme'] ) || ! in_array( strtolower( $deler['scheme'] ), array( 'http', 'https' ), true ) || empty( $deler['host'] ) ) {
			return $this->resultat( BV_Lenkevakt::STATUS_IKKE_SJEKKBAR, 0, $url, false, 'Lenken er ikke en vanlig http(s)-adresse og sjekkes ikke.' );
		}

		$gjeldende       = $url;
		$hopp            = array();
		$alle_permanente = true;

		for ( $runde = 0; $runde <= self::MAKS_HOPP; $runde++ ) {
			$svar = $this->hent( $gjeldende, 'HEAD' );

			if ( is_wp_error( $svar ) ) {
				return $this->fra_wp_error( $svar, $gjeldende, $hopp );
			}

			$kode = (int) wp_remote_retrieve_response_code( $svar );

			// En del tjenere svarer surt på HEAD. Prøv GET før vi konkluderer.
			if ( in_array( $kode, array( 0, 400, 403, 405, 406, 409, 501 ), true ) ) {
				$svar_get = $this->hent( $gjeldende, 'GET' );
				if ( ! is_wp_error( $svar_get ) ) {
					$kode = (int) wp_remote_retrieve_response_code( $svar_get );
					$svar = $svar_get;
				}
			}

			if ( $kode >= 300 && $kode < 400 ) {
				$lokasjon = wp_remote_retrieve_header( $svar, 'location' );
				if ( is_array( $lokasjon ) ) {
					$lokasjon = end( $lokasjon );
				}
				if ( ! $lokasjon ) {
					return $this->resultat(
						BV_Lenkevakt::STATUS_ADVARSEL,
						$kode,
						$gjeldende,
						false,
						sprintf( 'Svarte %d uten Location-felt.', $kode ),
						$hopp
					);
				}

				$neste = $this->gjor_absolutt( (string) $lokasjon, $gjeldende );
				if ( '' === $neste ) {
					return $this->resultat( BV_Lenkevakt::STATUS_ADVARSEL, $kode, $gjeldende, false, 'Omdirigering til en adresse vi ikke klarte å tolke.', $hopp );
				}

				$hopp[] = array(
					'fra'  => $gjeldende,
					'til'  => $neste,
					'kode' => $kode,
				);

				if ( ! in_array( $kode, array( 301, 308 ), true ) ) {
					$alle_permanente = false;
				}

				$gjeldende = $neste;
				continue;
			}

			return $this->konkluder( $kode, $gjeldende, $url, $hopp, $alle_permanente );
		}

		return $this->resultat(
			BV_Lenkevakt::STATUS_ADVARSEL,
			0,
			$gjeldende,
			false,
			sprintf( 'Mer enn %d omdirigeringer — mulig omdirigeringsløkke.', self::MAKS_HOPP ),
			$hopp
		);
	}

	/**
	 * Oversett en endelig HTTP-kode til vår statusmodell.
	 */
	private function konkluder( int $kode, string $endelig, string $original, array $hopp, bool $alle_permanente ): array {
		$flyttet = ! empty( $hopp ) && untrailingslashit( $endelig ) !== untrailingslashit( $original );

		if ( $kode >= 200 && $kode < 300 ) {
			if ( $flyttet && $alle_permanente ) {
				return $this->resultat( BV_Lenkevakt::STATUS_OPPDATERT, $kode, $endelig, true, 'Permanent flyttet (301/308).', $hopp );
			}
			if ( $flyttet ) {
				return $this->resultat( BV_Lenkevakt::STATUS_MIDLERTIDIG, $kode, $endelig, false, 'Midlertidig omdirigert (302/303/307) — lenken står urørt.', $hopp );
			}
			return $this->resultat( BV_Lenkevakt::STATUS_OK, $kode, $endelig, false, '', $hopp );
		}

		if ( in_array( $kode, array( 404, 410 ), true ) ) {
			return $this->resultat(
				BV_Lenkevakt::STATUS_BRUTT,
				$kode,
				$endelig,
				false,
				410 === $kode ? 'Tjeneren melder at siden er permanent fjernet (410).' : 'Siden finnes ikke (404).',
				$hopp
			);
		}

		if ( in_array( $kode, array( 401, 402, 403, 407, 429 ), true ) ) {
			return $this->resultat(
				BV_Lenkevakt::STATUS_ADVARSEL,
				$kode,
				$endelig,
				false,
				'Tilgang nektet eller strupet — typisk betalingsmur eller robotsperre. Settes aldri til kladd automatisk.',
				$hopp
			);
		}

		if ( $kode >= 500 ) {
			return $this->resultat( BV_Lenkevakt::STATUS_ADVARSEL, $kode, $endelig, false, sprintf( 'Tjenerfeil (%d) — antagelig midlertidig.', $kode ), $hopp );
		}

		return $this->resultat( BV_Lenkevakt::STATUS_ADVARSEL, $kode, $endelig, false, sprintf( 'Uventet svar (%d).', $kode ), $hopp );
	}

	/**
	 * Nettverksfeil: skill navneoppslag (borte) fra tidsavbrudd (antagelig midlertidig).
	 */
	private function fra_wp_error( WP_Error $feil, string $url, array $hopp ): array {
		$melding = $feil->get_error_message();
		$lav     = strtolower( $melding );

		$navnefeil = array( 'could not resolve host', 'name or service not known', 'couldn\'t resolve host', 'no address associated', 'nodename nor servname' );
		foreach ( $navnefeil as $bit ) {
			if ( false !== strpos( $lav, $bit ) ) {
				return $this->resultat( BV_Lenkevakt::STATUS_BRUTT, 0, $url, false, 'Domenet svarer ikke på navneoppslag — nettstedet ser ut til å være borte.', $hopp );
			}
		}

		return $this->resultat( BV_Lenkevakt::STATUS_ADVARSEL, 0, $url, false, 'Nettverksfeil: ' . $melding, $hopp );
	}

	/**
	 * Utfør forespørselen, med enkel bremsing per vertsnavn.
	 */
	private function hent( string $url, string $metode ) {
		$vert = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( isset( $this->sist_kontakt[ $vert ] ) ) {
			$gikk = microtime( true ) - $this->sist_kontakt[ $vert ];
			if ( $gikk < 1.0 ) {
				usleep( (int) ( ( 1.0 - $gikk ) * 1000000 ) );
			}
		}
		$this->sist_kontakt[ $vert ] = microtime( true );

		$argumenter = array(
			'method'      => $metode,
			'timeout'     => (int) $this->innstillinger->hent( 'timeout' ),
			'redirection' => 0, // Vi følger selv, for å kunne skille 301 fra 302.
			'sslverify'   => true,
			'user-agent'  => sprintf(
				'BV-lenkevakt/%s (+%s; kontroll av kunnskapskilder)',
				BV_LENKEVAKT_VERSJON,
				home_url( '/' )
			),
			'headers'     => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/pdf;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'nb-NO,nb;q=0.9,no;q=0.8,en;q=0.5',
			),
		);

		/**
		 * Filter: juster forespørselen (f.eks. legge på egne headere for enkelte verter).
		 */
		$argumenter = apply_filters( 'bv_lenkevakt_forespoersel_argumenter', $argumenter, $url, $metode );

		return wp_remote_request( $url, $argumenter );
	}

	/**
	 * Gjør en relativ Location om til absolutt URL.
	 */
	private function gjor_absolutt( string $lokasjon, string $basis ): string {
		$lokasjon = trim( $lokasjon );
		if ( '' === $lokasjon ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $lokasjon ) ) {
			return $lokasjon;
		}
		if ( 0 === strpos( $lokasjon, '//' ) ) {
			$skjema = (string) wp_parse_url( $basis, PHP_URL_SCHEME );
			return $skjema . ':' . $lokasjon;
		}

		$b = wp_parse_url( $basis );
		if ( empty( $b['scheme'] ) || empty( $b['host'] ) ) {
			return '';
		}
		$rot = $b['scheme'] . '://' . $b['host'] . ( isset( $b['port'] ) ? ':' . $b['port'] : '' );

		if ( 0 === strpos( $lokasjon, '/' ) ) {
			return $rot . $lokasjon;
		}

		$sti = isset( $b['path'] ) ? preg_replace( '#/[^/]*$#', '/', $b['path'] ) : '/';
		return $rot . $sti . $lokasjon;
	}

	private function resultat( string $status, int $kode, string $endelig, bool $permanent, string $melding, array $hopp = array() ): array {
		return array(
			'status'      => $status,
			'kode'        => $kode,
			'endelig_url' => $endelig,
			'permanent'   => $permanent,
			'melding'     => $melding,
			'hopp'        => $hopp,
		);
	}
}
