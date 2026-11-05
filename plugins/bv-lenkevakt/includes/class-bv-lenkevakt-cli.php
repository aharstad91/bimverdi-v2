<?php
/**
 * WP-CLI-kommandoer for BV lenkevakt.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kontroller lenkene på kunnskapskildene.
 */
class BV_Lenkevakt_CLI {

	/**
	 * Kjør en full kontroll nå, synkront (uten cron).
	 *
	 * ## OPTIONS
	 *
	 * [--torr]
	 * : Ikke endre noe — bare rapporter.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bimverdi lenkevakt kjor
	 *     wp bimverdi lenkevakt kjor --torr
	 *
	 * @param array $args      Posisjonsargumenter.
	 * @param array $assoc     Navngitte argumenter.
	 */
	public function kjor( $args, $assoc ) {
		$plugin = bv_lenkevakt();

		$opprinnelig = $plugin->innstillinger->alle();
		if ( ! empty( $assoc['torr'] ) ) {
			$midlertidig                = $opprinnelig;
			$midlertidig['torrkjoring'] = 1;
			$plugin->innstillinger->lagre( $midlertidig );
		}

		$poster = get_posts(
			array(
				'post_type'      => (array) $plugin->innstillinger->hent( 'posttyper' ),
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$kjoring = 'cli-' . gmdate( 'Ymd-His' );
		$framdrift = \WP_CLI\Utils\make_progress_bar( 'Sjekker lenker', count( $poster ) );

		foreach ( $poster as $post_id ) {
			$plugin->planlegger->sjekk_post( (int) $post_id, $kjoring );
			$framdrift->tick();
		}
		$framdrift->finish();

		if ( ! empty( $assoc['torr'] ) ) {
			$plugin->innstillinger->lagre( $opprinnelig );
		}

		$rader   = $plugin->logg->for_kjoring( $kjoring );
		$telling = array();
		foreach ( $rader as $rad ) {
			$telling[ $rad['status'] ] = ( $telling[ $rad['status'] ] ?? 0 ) + 1;
		}

		foreach ( $telling as $status => $antall ) {
			WP_CLI::line( sprintf( '%-24s %d', BV_Lenkevakt::status_etikett( (string) $status ), $antall ) );
		}

		WP_CLI::success( sprintf( '%d kilder kontrollert.', count( $poster ) ) );
	}

	/**
	 * Publiser igjen alt overvåkingen har satt til utkast i innholdstyper som
	 * aldri skulle vært rørt — innlegg, sider, arrangementer og lignende.
	 *
	 * Trygg å kjøre flere ganger. Kunnskapskilder røres ikke.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bimverdi lenkevakt reparer
	 */
	public function reparer() {
		$gjenopprettet = bv_lenkevakt()->planlegger->gjenopprett_feilaktig_kladdede();

		if ( empty( $gjenopprettet ) ) {
			WP_CLI::success( 'Fant ingenting å rette opp.' );
			return;
		}

		$sum = 0;
		foreach ( $gjenopprettet as $posttype => $ider ) {
			$sum += count( $ider );
			WP_CLI::line( sprintf( '%-20s %d publisert igjen', $posttype, count( $ider ) ) );
		}

		WP_CLI::success( sprintf( '%d poster er publisert igjen.', $sum ) );
	}

	/**
	 * Vis når neste ukentlige kjøring er planlagt.
	 */
	public function neste() {
		$ts = BV_Lenkevakt_Planlegger::neste_kjoring();
		if ( ! $ts ) {
			WP_CLI::warning( 'Ingen ukentlig kjøring er planlagt. Deaktiver og aktiver pluginen for å planlegge på nytt.' );
			return;
		}
		WP_CLI::line( wp_date( 'l j. F Y H:i', $ts ) );
	}
}
