<?php
/**
 * Plugin Name:       BV hurtigregistrering for ByggChat
 * Plugin URI:        https://www.bimverdi.no/
 * Description:       Hurtigregistrering til ByggChat fra QR-kode (2X-KRAV v1): skjema på /chat/ med [bv_hurtigreg], rollen byggchat_gjest, engangslenker på e-post, fullføring på /chat/fullfor/, daglig utløp og misbruksvern.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            BIM Verdi / Verdinettverk AS
 * Text Domain:       bv-hurtigreg
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

define( 'BV_HURTIGREG_VERSJON', '1.0.0' );
define( 'BV_HURTIGREG_FIL', __FILE__ );
define( 'BV_HURTIGREG_STI', plugin_dir_path( __FILE__ ) );
define( 'BV_HURTIGREG_URL', plugin_dir_url( __FILE__ ) );

require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-innstillinger.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-bruker.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-epost.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-vern.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-side.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-tilgang.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-skjema.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-ruter.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-cron.php';
require_once BV_HURTIGREG_STI . 'includes/class-bv-hurtigreg-admin.php';

/**
 * Felles konstanter og oppstart.
 */
final class BV_Hurtigreg {

	/** Rollen en hurtigbruker har til fullføring (HR-04, HR-14). */
	const ROLLE = 'byggchat_gjest';

	/** Brukermeta fra kravet (HR-04). */
	const META_STATUS     = 'bv_hurtig_status';     // ubekreftet | bekreftet
	const META_OPPRETTET  = 'bv_hurtig_opprettet';  // Unix-tid
	const META_KAMPANJE   = 'bv_hurtig_kampanje';   // kampanjekode eller tom
	const META_BEKREFTET  = 'bv_hurtig_bekreftet';  // Unix-tid

	/** Brukermeta for livsløpet. Beholdes etter fullføring og anonymisering, så statistikken holder (HR-09, Å-4). */
	const META_FULLFORT    = 'bv_hurtig_fullfort';    // Unix-tid
	const META_UTLOPT      = 'bv_hurtig_utlopt';      // Unix-tid
	const META_ANONYMISERT = 'bv_hurtig_anonymisert'; // Unix-tid
	const META_PAAMINNELSE = 'bv_hurtig_paaminnelse'; // array med sendte påminnelser
	const META_PROFIL      = 'bv_hurtig_profil';      // Unix-tid, profilsteget på /chat/fullfor/ er gjort
	const META_NY_EPOST    = 'bv_hurtig_ny_epost';    // jobbadresse som venter på bekreftelse
	const META_LENKER      = 'bv_hurtig_lenker';      // hashede engangslenker
	const META_GA4         = 'bv_hurtig_ga4';         // GA4-hendelse som venter på neste sidevisning

	const STATUS_UBEKREFTET = 'ubekreftet';
	const STATUS_BEKREFTET  = 'bekreftet';

	/** Daglig cron (HR-11). */
	const CRON_HOOK = 'bv_hurtigreg_daglig';

	public static function start(): void {
		BV_Hurtigreg_Innstillinger::start();
		BV_Hurtigreg_Bruker::start();
		BV_Hurtigreg_Tilgang::start();
		BV_Hurtigreg_Skjema::start();
		BV_Hurtigreg_Ruter::start();
		BV_Hurtigreg_Cron::start();
		if ( is_admin() ) {
			BV_Hurtigreg_Admin::start();
		}

		add_action( 'init', array( __CLASS__, 'sikre_rolle' ), 5 );
		add_action( 'init', array( __CLASS__, 'oppgrader' ), 99 );
	}

	/**
	 * Rollen gir bare «read», som WordPress trenger for å holde en innlogging.
	 * Ingen medlemsrettigheter (read_member_content, edit_posts …), jf. HR-14.
	 */
	public static function sikre_rolle(): void {
		if ( ! get_role( self::ROLLE ) ) {
			add_role( self::ROLLE, 'ByggChat-gjest', array( 'read' => true ) );
		}
	}

	/** Tømmer omskrivingsreglene når en ny versjon er lagt ut uten re-aktivering. */
	public static function oppgrader(): void {
		if ( get_option( 'bv_hurtigreg_versjon' ) !== BV_HURTIGREG_VERSJON ) {
			flush_rewrite_rules( false );
			update_option( 'bv_hurtigreg_versjon', BV_HURTIGREG_VERSJON, false );
		}
	}

	public static function aktiver(): void {
		self::sikre_rolle();
		BV_Hurtigreg_Ruter::registrer_regler();
		flush_rewrite_rules( false );
		BV_Hurtigreg_Cron::planlegg();
		update_option( 'bv_hurtigreg_versjon', BV_HURTIGREG_VERSJON, false );
	}

	public static function deaktiver(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		flush_rewrite_rules( false );
	}
}

register_activation_hook( __FILE__, array( 'BV_Hurtigreg', 'aktiver' ) );
register_deactivation_hook( __FILE__, array( 'BV_Hurtigreg', 'deaktiver' ) );

BV_Hurtigreg::start();
