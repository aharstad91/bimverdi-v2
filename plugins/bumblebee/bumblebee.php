<?php
/**
 * Plugin Name:       BV Bumblebee
 * Plugin URI:        https://bimverdi.no/
 * Description:        Kopi av BV BusyBIM (0.11.4) som videreutvikles og sammenlignes med ByggChat ALFA. Bruker egne tabeller, innstillinger og kortkode [bumblebee], så den ikke påvirker BusyBIM.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BIM Verdi
 * Author URI:        https://bimverdi.no/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bumblebee
 * Domain Path:       /languages
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Ingen direkte tilgang.
}

// ---------------------------------------------------------------------------
// Konstanter
// ---------------------------------------------------------------------------
define( 'BBEE_VERSION', '0.1.0' );
define( 'BBEE_PLUGIN_FILE', __FILE__ );
define( 'BBEE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BBEE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BBEE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Navn på innstillings-nøkkelen i wp_options.
define( 'BBEE_OPTION_KEY', 'bbee_settings' );

// ---------------------------------------------------------------------------
// Enkel autoloader for plugin-klasser (BBEE_*)
// ---------------------------------------------------------------------------
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, 'BBEE_' ) !== 0 ) {
			return;
		}
		// BBEE_Claude_Client -> class-bbee-claude-client.php
		$file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		$path = BBEE_PLUGIN_DIR . 'includes/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

// ---------------------------------------------------------------------------
// Aktivering / deaktivering
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, array( 'BBEE_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BBEE_Plugin', 'deactivate' ) );

// ---------------------------------------------------------------------------
// Oppstart
// ---------------------------------------------------------------------------
add_action(
	'plugins_loaded',
	function () {
		BBEE_Plugin::instance()->init();
		BBEE_App::init(); // Fullskjerm chat-side (sidemal).
	}
);
