<?php
/**
 * Avinstallering: rydd opp i tabeller, innstillinger og cron.
 *
 * @package Bumblebee
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'bbee_settings' );
delete_option( 'bbee_last_indexed' );
delete_option( 'bbee_db_version' );
delete_option( 'bbee_url_sources' );
delete_option( 'bbee_theme_vectors' );
delete_option( 'bbee_themes_last_run' );
delete_option( 'bbee_busybim_applied' );
delete_option( 'bbee_busybim_v2' );
delete_option( 'bbee_log' );
delete_option( 'bbee_vectors_db' );
delete_option( 'bbee_kilde_siste_bygging' );
delete_option( 'bbee_embed_logg' );

// Slett tabeller.
$table = $wpdb->prefix . 'bbee_chunks';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
// Vektorlageret (BBEE_Vector_Store).
$vectors = $wpdb->prefix . 'bbee_vectors';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
$wpdb->query( "DROP TABLE IF EXISTS {$vectors}" );
$rg = $wpdb->prefix . 'bbee_resource_groups';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
$wpdb->query( "DROP TABLE IF EXISTS {$rg}" );

// Fjern crawl-tilstander.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'bbee_crawl_state_%'" );

// Fjern planlagte cron-jobber.
wp_clear_scheduled_hook( 'bbee_reindex_all' );
wp_clear_scheduled_hook( 'bbee_embed_pending' );
wp_clear_scheduled_hook( 'bbee_nightly_reindex' );
wp_clear_scheduled_hook( 'bbee_nightly_classify' );
if ( function_exists( 'wp_unschedule_hook' ) ) {
	wp_unschedule_hook( 'bbee_crawl_batch' );
}
