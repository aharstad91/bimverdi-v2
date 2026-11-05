<?php
/**
 * Rydder opp når pluginen slettes.
 *
 * Loggen og metadataene fjernes. Kilder som er satt til kladd blir stående
 * som kladd — de skal vurderes av et menneske, ikke publiseres blindt.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$metanokler = array(
	'_lenkevakt_status',
	'_lenkevakt_http_kode',
	'_lenkevakt_sist_sjekket',
	'_lenkevakt_feil_paa_rad',
	'_lenkevakt_melding',
	'_lenkevakt_sjekket_url',
	'_lenkevakt_foreslatt_url',
	'_lenkevakt_forrige_url',
	'_lenkevakt_draftet',
);

foreach ( $metanokler as $nokkel ) {
	delete_post_meta_by_key( $nokkel );
}

delete_option( 'bimverdi_lenkevakt_innstillinger' );
delete_option( 'bimverdi_lenkevakt_ko' );
delete_option( 'bimverdi_lenkevakt_kjoring' );
delete_option( 'bimverdi_lenkevakt_siste_kjoring' );
delete_option( 'bimverdi_lenkevakt_db_versjon' );

$tabell = $wpdb->prefix . 'bimverdi_lenkevakt_logg';
$wpdb->query( "DROP TABLE IF EXISTS {$tabell}" ); // phpcs:ignore
