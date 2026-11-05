<?php
/**
 * Innstillinger for BV lenkevakt.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Innstillinger {

	const OPTION = 'bimverdi_lenkevakt_innstillinger';

	/**
	 * Standardverdier. Merk: `metanokler` tom betyr «finn lenkefeltene automatisk».
	 */
	public static function standard(): array {
		return array(
			'posttyper'          => array( 'kunnskapskilde' ),
			'metanokler'         => array(),
			'auto_oppdater_301'  => 1,
			'auto_draft_404'     => 1,
			'terskel_brutt'      => 2,
			'timeout'            => 15,
			'batchstorrelse'     => 25,
			'ukedag'             => 1, // 1 = mandag.
			'klokkeslett'        => '06:00',
			'e_post_aktiv'       => 1,
			'e_post_mottaker'    => '',
			'torrkjoring'        => 0,
			'sjekk_kladder'      => 0,
		);
	}

	private ?array $bufret = null;

	/**
	 * Hele innstillingssettet, med standardverdier fylt inn.
	 */
	public function alle(): array {
		if ( null === $this->bufret ) {
			$lagret        = get_option( self::OPTION, array() );
			$lagret        = is_array( $lagret ) ? $lagret : array();
			$this->bufret  = wp_parse_args( $lagret, self::standard() );
			if ( '' === $this->bufret['e_post_mottaker'] ) {
				$this->bufret['e_post_mottaker'] = get_option( 'admin_email' );
			}
		}
		return $this->bufret;
	}

	public function hent( string $nokkel ) {
		$alle = $this->alle();
		return $alle[ $nokkel ] ?? null;
	}

	public function lagre( array $verdier ): void {
		update_option( self::OPTION, $verdier, false );
		$this->bufret = null;
	}

	public function sikre_standardverdier(): void {
		if ( false === get_option( self::OPTION, false ) ) {
			$this->lagre( self::standard() );
		}
	}

	/**
	 * Rens innsendt skjema fra innstillingssiden.
	 */
	public function rens( array $inn ): array {
		$ut = self::standard();

		$ut['posttyper'] = array();
		foreach ( (array) ( $inn['posttyper'] ?? array() ) as $pt ) {
			$pt = sanitize_key( $pt );
			if ( post_type_exists( $pt ) ) {
				$ut['posttyper'][] = $pt;
			}
		}
		if ( empty( $ut['posttyper'] ) ) {
			$ut['posttyper'] = array( 'kunnskapskilde' );
		}

		$ut['metanokler'] = array();
		foreach ( (array) ( $inn['metanokler'] ?? array() ) as $nokkel ) {
			$nokkel = sanitize_text_field( wp_unslash( $nokkel ) );
			if ( '' !== $nokkel ) {
				$ut['metanokler'][] = $nokkel;
			}
		}
		$ut['metanokler'] = array_values( array_unique( $ut['metanokler'] ) );

		$ut['auto_oppdater_301'] = empty( $inn['auto_oppdater_301'] ) ? 0 : 1;
		$ut['auto_draft_404']    = empty( $inn['auto_draft_404'] ) ? 0 : 1;
		$ut['e_post_aktiv']      = empty( $inn['e_post_aktiv'] ) ? 0 : 1;
		$ut['torrkjoring']       = empty( $inn['torrkjoring'] ) ? 0 : 1;
		$ut['sjekk_kladder']     = empty( $inn['sjekk_kladder'] ) ? 0 : 1;

		$ut['terskel_brutt']  = max( 1, min( 5, absint( $inn['terskel_brutt'] ?? 2 ) ) );
		$ut['timeout']        = max( 5, min( 60, absint( $inn['timeout'] ?? 15 ) ) );
		$ut['batchstorrelse'] = max( 5, min( 100, absint( $inn['batchstorrelse'] ?? 25 ) ) );
		$ut['ukedag']         = max( 1, min( 7, absint( $inn['ukedag'] ?? 1 ) ) );

		$klokkeslett = sanitize_text_field( wp_unslash( $inn['klokkeslett'] ?? '06:00' ) );
		$ut['klokkeslett'] = preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $klokkeslett ) ? $klokkeslett : '06:00';

		$mottaker = sanitize_email( wp_unslash( $inn['e_post_mottaker'] ?? '' ) );
		$ut['e_post_mottaker'] = is_email( $mottaker ) ? $mottaker : get_option( 'admin_email' );

		return $ut;
	}

	/**
	 * Metanøkler som faktisk skal sjekkes for en gitt post.
	 * Tom liste i innstillingene = automatisk gjenkjenning.
	 */
	public function metanokler_for_post( int $post_id ): array {
		$valgte = (array) $this->hent( 'metanokler' );
		if ( ! empty( $valgte ) ) {
			return $valgte;
		}

		$funnet = array();
		foreach ( (array) get_post_meta( $post_id ) as $nokkel => $verdier ) {
			if ( '_' === substr( (string) $nokkel, 0, 1 ) ) {
				continue; // Skjulte felt (inkl. våre egne) hoppes over.
			}
			$verdi = is_array( $verdier ) ? reset( $verdier ) : $verdier;
			if ( is_string( $verdi ) && preg_match( '#^https?://#i', trim( $verdi ) ) ) {
				$funnet[] = (string) $nokkel;
			}
		}
		return $funnet;
	}

	/**
	 * Kandidater til lenkefelt, med antall poster per nøkkel.
	 * Brukes til å fylle nedtrekket på innstillingssiden.
	 */
	public function finn_lenkefelt(): array {
		global $wpdb;

		$posttyper = (array) $this->hent( 'posttyper' );
		if ( empty( $posttyper ) ) {
			return array();
		}

		$plassholdere = implode( ', ', array_fill( 0, count( $posttyper ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT pm.meta_key AS nokkel, COUNT(DISTINCT pm.post_id) AS antall
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE p.post_type IN ( {$plassholdere} )
			   AND p.post_status IN ( 'publish', 'draft', 'pending', 'private' )
			   AND pm.meta_value LIKE %s
			 GROUP BY pm.meta_key
			 ORDER BY antall DESC, nokkel ASC
			 LIMIT 80",
			array_merge( $posttyper, array( 'http%' ) )
		);
		// phpcs:enable

		$rader = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! is_array( $rader ) ) {
			return array();
		}

		// Skjulte felt (inkl. våre egne) filtreres bort her, ikke i SQL — LIKE-escaping
		// av understrek er for skjørt til å være verdt det.
		return array_values(
			array_filter(
				$rader,
				static function ( $rad ) {
					return '_' !== substr( (string) $rad['nokkel'], 0, 1 );
				}
			)
		);
	}
}
