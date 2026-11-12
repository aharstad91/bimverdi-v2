<?php
/**
 * Fullskjerm chat-side for Bumblebee.
 *
 * Registrerer sidemalen «Bumblebee – fullskjerm chat». Malen bruker ikke
 * temaets header, footer eller page.php. Den viser bare chatten.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_App
 */
class BBEE_App {

	const TEMPLATE = 'bumblebee-app';

	/**
	 * Koble opp hooks.
	 */
	public static function init() {
		add_filter( 'theme_page_templates', array( __CLASS__, 'add_template' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
	}

	/**
	 * Gjør malen valgbar under «Sideattributter».
	 *
	 * @param array $templates Maler.
	 * @return array
	 */
	public static function add_template( $templates ) {
		$templates[ self::TEMPLATE ] = __( 'Bumblebee – fullskjerm chat', 'bumblebee' );
		return $templates;
	}

	/**
	 * Er gjeldende side en Bumblebee-side?
	 *
	 * @return bool
	 */
	public static function is_app_page() {
		return is_page() && self::TEMPLATE === get_page_template_slug( get_queried_object_id() );
	}

	/**
	 * Bruk pluginens mal i stedet for temaets page.php.
	 *
	 * @param string $template Mal.
	 * @return string
	 */
	public static function template_include( $template ) {
		if ( self::is_app_page() ) {
			return BBEE_PLUGIN_DIR . 'templates/app.php';
		}
		return $template;
	}

	/**
	 * Last inn CSS og JS for chat-siden.
	 */
	public static function enqueue() {
		if ( ! self::is_app_page() ) {
			return;
		}

		$css = BBEE_PLUGIN_DIR . 'assets/css/bumblebee-app.css';
		$js  = BBEE_PLUGIN_DIR . 'assets/js/bumblebee-app.js';

		wp_enqueue_style( 'bbee-app', BBEE_PLUGIN_URL . 'assets/css/bumblebee-app.css', array(), (string) filemtime( $css ) );
		wp_enqueue_script( 'bbee-app', BBEE_PLUGIN_URL . 'assets/js/bumblebee-app.js', array(), (string) filemtime( $js ), true );

		$user = wp_get_current_user();

		wp_localize_script(
			'bbee-app',
			'BBEE_APP',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'bbee/v1/chat' ) ),
				'feedbackUrl' => esc_url_raw( rest_url( 'bbee/v1/feedback' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'loggedIn'    => is_user_logged_in(),
				'firstName'   => $user && $user->exists() ? (string) $user->first_name : '',
				'homeHost'    => (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ),
				'suggestions' => array(
					'Kan jeg bruke gamle betongdragere på nytt, og hva krever DiBK?',
					'Hvilke krav stiller FutureBuilt til plast i bygg?',
					'Hvordan regner jeg klimagassutslipp for et bygg i en offentlig anskaffelse?',
					'Hva er forskjellen på IFC og BCF?',
				),
				'i18n'        => array(
					'thinking'     => __( 'Leter i kunnskapskildene', 'bumblebee' ),
					'error'        => __( 'Beklager, noe gikk galt. Prøv igjen om litt.', 'bumblebee' ),
					'sources'      => __( 'Kilder', 'bumblebee' ),
					'copy'         => __( 'Kopier svaret', 'bumblebee' ),
					'copied'       => __( 'Kopiert', 'bumblebee' ),
					'useful'       => __( 'Nyttig svar', 'bumblebee' ),
					'notUseful'    => __( 'Ikke nyttig svar', 'bumblebee' ),
					'thanks'       => __( 'Takk for tilbakemeldingen', 'bumblebee' ),
					'internalHost' => 'bimverdi.no',
				),
			)
		);
	}
}
