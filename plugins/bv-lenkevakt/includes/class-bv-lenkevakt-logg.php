<?php
/**
 * Loggtabell for lenkesjekker og automatiske handlinger.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Logg {

	const DB_VERSJON = '1.0.0';

	public function tabell(): string {
		global $wpdb;
		return $wpdb->prefix . 'bimverdi_lenkevakt_logg';
	}

	/**
	 * Opprett eller oppgrader tabellen.
	 */
	public function lag_tabell(): void {
		global $wpdb;

		$tabell   = $this->tabell();
		$collate  = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tabell} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			meta_nokkel VARCHAR(191) NOT NULL DEFAULT '',
			url TEXT NULL,
			ny_url TEXT NULL,
			http_kode SMALLINT NOT NULL DEFAULT 0,
			status VARCHAR(32) NOT NULL DEFAULT '',
			handling VARCHAR(32) NOT NULL DEFAULT '',
			melding TEXT NULL,
			kjoring VARCHAR(32) NOT NULL DEFAULT '',
			tidspunkt DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY kjoring (kjoring),
			KEY tidspunkt (tidspunkt)
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'bimverdi_lenkevakt_db_versjon', self::DB_VERSJON, false );
	}

	/**
	 * Skriv én linje i loggen.
	 */
	public function skriv( array $rad ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$this->tabell(),
			array(
				'post_id'     => absint( $rad['post_id'] ?? 0 ),
				'meta_nokkel' => (string) ( $rad['meta_nokkel'] ?? '' ),
				'url'         => (string) ( $rad['url'] ?? '' ),
				'ny_url'      => (string) ( $rad['ny_url'] ?? '' ),
				'http_kode'   => absint( $rad['http_kode'] ?? 0 ),
				'status'      => (string) ( $rad['status'] ?? '' ),
				'handling'    => (string) ( $rad['handling'] ?? '' ),
				'melding'     => (string) ( $rad['melding'] ?? '' ),
				'kjoring'     => (string) ( $rad['kjoring'] ?? '' ),
				'tidspunkt'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Hent loggrader for én kjøring.
	 */
	public function for_kjoring( string $kjoring, array $statuser = array() ): array {
		global $wpdb;

		$tabell = $this->tabell();

		if ( ! empty( $statuser ) ) {
			$plassholdere = implode( ', ', array_fill( 0, count( $statuser ), '%s' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare(
				"SELECT * FROM {$tabell} WHERE kjoring = %s AND status IN ( {$plassholdere} ) ORDER BY status ASC, id ASC",
				array_merge( array( $kjoring ), $statuser )
			);
			// phpcs:enable
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$tabell} WHERE kjoring = %s ORDER BY id ASC", $kjoring ); // phpcs:ignore
		}

		$rader = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return is_array( $rader ) ? $rader : array();
	}

	/**
	 * Siste loggrader for én post.
	 */
	public function for_post( int $post_id, int $antall = 10 ): array {
		global $wpdb;
		$tabell = $this->tabell();
		$rader  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$tabell} WHERE post_id = %d ORDER BY id DESC LIMIT %d", $post_id, $antall ), // phpcs:ignore
			ARRAY_A
		);
		return is_array( $rader ) ? $rader : array();
	}

	/**
	 * Slett loggrader eldre enn X dager. Kjøres etter hver ukentlige runde.
	 */
	public function rydd( int $dager = 365 ): void {
		global $wpdb;
		$tabell = $this->tabell();
		$grense = gmdate( 'Y-m-d H:i:s', time() - ( $dager * DAY_IN_SECONDS ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabell} WHERE tidspunkt < %s", $grense ) ); // phpcs:ignore
	}
}
