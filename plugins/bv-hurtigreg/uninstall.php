<?php
/**
 * Rydder opp når pluginen slettes.
 *
 * Innstillinger, logg og den planlagte jobben fjernes. Hurtigbrukerne blir
 * stående: noen kan være midt i en fullføring, og brukerdata skal ikke
 * forsvinne fordi en plugin slettes. Rollen byggchat_gjest blir også stående
 * så lenge noen har den.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bv_hurtigreg_innstillinger' );
delete_option( 'bv_hurtigreg_logg' );
delete_option( 'bv_hurtigreg_versjon' );
wp_clear_scheduled_hook( 'bv_hurtigreg_daglig' );

$bv_gjester = get_users( array( 'role' => 'byggchat_gjest', 'number' => 1, 'fields' => 'ID' ) );
if ( empty( $bv_gjester ) ) {
	remove_role( 'byggchat_gjest' );
}
