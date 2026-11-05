<?php
/**
 * Plugin Name:       BV lenkevakt
 * Plugin URI:        https://www.bimverdi.no/
 * Description:       Ukentlig overvåking av eksterne lenker på kunnskapskilder. Oppdaterer permanent flyttede lenker automatisk, og setter kilden til kladd når lenken er borte.
 * Version:           1.0.2
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            BIM Verdi / Verdinettverk AS
 * Text Domain:       bv-lenkevakt
 * Domain Path:       /sprak
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

define( 'BV_LENKEVAKT_VERSJON', '1.0.2' );
define( 'BV_LENKEVAKT_FIL', __FILE__ );
define( 'BV_LENKEVAKT_STI', plugin_dir_path( __FILE__ ) );
define( 'BV_LENKEVAKT_URL', plugin_dir_url( __FILE__ ) );

require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-innstillinger.php';
require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-logg.php';
require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-sjekker.php';
require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-planlegger.php';
require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-rapport.php';
require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-admin.php';

/**
 * Hovedklasse — kobler sammen delene og holder på delte konstanter.
 */
final class BV_Lenkevakt {

	/** Metanøkler som holder tilstanden per kunnskapskilde. */
	const META_STATUS       = '_lenkevakt_status';
	const META_KODE         = '_lenkevakt_http_kode';
	const META_SIST_SJEKKET = '_lenkevakt_sist_sjekket';
	const META_FEIL_PAA_RAD = '_lenkevakt_feil_paa_rad';
	const META_MELDING      = '_lenkevakt_melding';
	const META_SJEKKET_URL  = '_lenkevakt_sjekket_url';
	const META_FORESLATT    = '_lenkevakt_foreslatt_url';
	const META_FORRIGE_URL  = '_lenkevakt_forrige_url';
	const META_DRAFTET      = '_lenkevakt_draftet';

	/** Statusverdier. */
	const STATUS_OK             = 'ok';
	const STATUS_OPPDATERT      = 'oppdatert';
	const STATUS_MIDLERTIDIG    = 'midlertidig';
	const STATUS_ADVARSEL       = 'advarsel';
	const STATUS_BRUTT          = 'brutt';
	const STATUS_MANGLER        = 'mangler_lenke';
	const STATUS_IKKE_SJEKKBAR  = 'ikke_sjekkbar';

	/** @var BV_Lenkevakt|null */
	private static $instans = null;

	/** @var BV_Lenkevakt_Innstillinger */
	public $innstillinger;

	/** @var BV_Lenkevakt_Logg */
	public $logg;

	/** @var BV_Lenkevakt_Sjekker */
	public $sjekker;

	/** @var BV_Lenkevakt_Planlegger */
	public $planlegger;

	/** @var BV_Lenkevakt_Rapport */
	public $rapport;

	/** @var BV_Lenkevakt_Admin */
	public $admin;

	public static function instans(): BV_Lenkevakt {
		if ( null === self::$instans ) {
			self::$instans = new self();
		}
		return self::$instans;
	}

	private function __construct() {
		$this->innstillinger = new BV_Lenkevakt_Innstillinger();
		$this->logg          = new BV_Lenkevakt_Logg();
		$this->sjekker       = new BV_Lenkevakt_Sjekker( $this->innstillinger );
		$this->rapport       = new BV_Lenkevakt_Rapport( $this->innstillinger, $this->logg );
		$this->planlegger    = new BV_Lenkevakt_Planlegger( $this->innstillinger, $this->sjekker, $this->logg, $this->rapport );
		$this->admin         = new BV_Lenkevakt_Admin( $this->innstillinger, $this->planlegger, $this->logg );

		$this->planlegger->koble();
		$this->admin->koble();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-cli.php';
			WP_CLI::add_command( 'bimverdi lenkevakt', 'BV_Lenkevakt_CLI' );
		}
	}

	/**
	 * Menneskelig etikett for en statusverdi.
	 */
	public static function status_etikett( string $status ): string {
		$etiketter = array(
			self::STATUS_OK            => 'OK',
			self::STATUS_OPPDATERT     => 'Lenke oppdatert',
			self::STATUS_MIDLERTIDIG   => 'Midlertidig omdirigert',
			self::STATUS_ADVARSEL      => 'Trenger blikk',
			self::STATUS_BRUTT         => 'Brutt',
			self::STATUS_MANGLER       => 'Mangler lenke',
			self::STATUS_IKKE_SJEKKBAR => 'Ikke sjekkbar',
		);
		return $etiketter[ $status ] ?? 'Ikke sjekket';
	}

	/**
	 * Fargekode for statusprikken i admin.
	 */
	public static function status_farge( string $status ): string {
		switch ( $status ) {
			case self::STATUS_OK:
			case self::STATUS_OPPDATERT:
				return '#1a7f37';
			case self::STATUS_MIDLERTIDIG:
			case self::STATUS_ADVARSEL:
				return '#bf8700';
			case self::STATUS_BRUTT:
			case self::STATUS_MANGLER:
				return '#b32d2e';
			default:
				return '#8c8f94';
		}
	}
}

/**
 * Kort tilgang til plugin-instansen.
 */
function bv_lenkevakt(): BV_Lenkevakt {
	return BV_Lenkevakt::instans();
}

add_action( 'plugins_loaded', 'bv_lenkevakt' );

/**
 * Aktivering: lag loggtabell og planlegg den ukentlige kjøringen.
 */
register_activation_hook(
	__FILE__,
	function () {
		require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-logg.php';
		require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-innstillinger.php';
		require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-planlegger.php';

		( new BV_Lenkevakt_Logg() )->lag_tabell();
		( new BV_Lenkevakt_Innstillinger() )->sikre_standardverdier();
		BV_Lenkevakt_Planlegger::planlegg_ukentlig();
	}
);

/**
 * Deaktivering: rydd cron-hendelser. Data og logg beholdes.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		require_once BV_LENKEVAKT_STI . 'includes/class-bv-lenkevakt-planlegger.php';
		BV_Lenkevakt_Planlegger::avplanlegg_alt();
	}
);
