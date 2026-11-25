<?php
/**
 * Plugin Name:       BV-pilot/prosjekt
 * Plugin URI:        https://bimverdi.no/
 * Description:       Lar innloggede deltakere i BIM Verdi registrere, lagre som kladd og oppdatere et BV-pilot/prosjekt (søknadsskisse for rask avklaring). Erstatter Formidable-skjema 39. Frontend-skjema via kortkoden [bv_pilot_prosjekt], samt skjemaeditor, oversikt, administratorfelt, e-postvarsel og CSV-eksport i wp-admin.
 * Version:           0.51.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Verdinettverk AS
 * Author URI:        https://bimverdi.no/
 * Text Domain:       bv-pilot-prosjekt
 * License:           GPL-2.0-or-later
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

define( 'BVP_VERSION', '0.51.0' );
define( 'BVP_FILE', __FILE__ );
define( 'BVP_PATH', plugin_dir_path( __FILE__ ) );
define( 'BVP_URL', plugin_dir_url( __FILE__ ) );

require_once BVP_PATH . 'includes/class-bvp-lookup.php';
require_once BVP_PATH . 'includes/class-bvp-schema.php';
require_once BVP_PATH . 'includes/class-bvp-fields.php';
require_once BVP_PATH . 'includes/class-bvp-post-type.php';
require_once BVP_PATH . 'includes/class-bvp-foretak.php';
require_once BVP_PATH . 'includes/class-bvp-access.php';
require_once BVP_PATH . 'includes/class-bvp-repository.php';
require_once BVP_PATH . 'includes/class-bvp-renderer.php';
require_once BVP_PATH . 'includes/class-bvp-meldinger.php';
require_once BVP_PATH . 'includes/class-bvp-logg.php';
require_once BVP_PATH . 'includes/class-bvp-mail.php';
require_once BVP_PATH . 'includes/class-bvp-form.php';
require_once BVP_PATH . 'includes/class-bvp-minside.php';
require_once BVP_PATH . 'includes/class-bvp-oversikt.php';
require_once BVP_PATH . 'includes/class-bvp-enkeltside.php';
require_once BVP_PATH . 'includes/class-bvp-foretaksside.php';
require_once BVP_PATH . 'includes/class-bvp-admin.php';
require_once BVP_PATH . 'includes/class-bvp-editor.php';

/**
 * Start pluginen.
 */
function bvp_bootstrap() {
	BVP_Schema::maybe_upgrade();
	BVP_Post_Type::init();
	BVP_Foretak::init();
	BVP_Lookup::init();
	BVP_Form::init();
	BVP_Minside::init();
	BVP_Oversikt::init();
	BVP_Enkeltside::init();
	BVP_Foretaksside::init();
	BVP_Meldinger::init();
	BVP_Logg::init();
	add_action( 'init', array( 'BVP_Repository', 'maybe_reparer_eierskap' ), 99 );

	if ( is_admin() ) {
		BVP_Admin::init();
		BVP_Editor::init();
	}
}
add_action( 'plugins_loaded', 'bvp_bootstrap' );

/**
 * Aktivering: registrer innholdstypen og opprett skjemasiden hvis den mangler.
 */
function bvp_activate() {
	BVP_Post_Type::register();
	BVP_Foretak::register();

	// Hver registrering har sin egen adresse - lenkereglene må skrives om.
	flush_rewrite_rules( false );

	if ( ! get_option( 'bvp_page_id' ) ) {
		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 50,
				's'              => BVP_Form::SHORTCODE,
				'fields'         => 'ids',
			)
		);

		if ( empty( $existing ) ) {
			$page_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'draft',
					'post_title'   => 'Registrer BV-pilot/prosjekt',
					'post_name'    => 'registrer-bv-pilot-prosjekt',
					'post_content' => '<!-- wp:shortcode -->[' . BVP_Form::SHORTCODE . ']<!-- /wp:shortcode -->',
				)
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( 'bvp_page_id', (int) $page_id );
			}
		} else {
			update_option( 'bvp_page_id', (int) $existing[0] );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bvp_activate' );

/**
 * Deaktivering.
 */
function bvp_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bvp_deactivate' );
