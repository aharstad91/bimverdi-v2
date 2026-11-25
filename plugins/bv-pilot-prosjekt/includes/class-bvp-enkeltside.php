<?php
/**
 * Én side per registrering.
 *
 * Hver innsendte registrering får sin egen adresse, f.eks.
 * https://bimverdi.no/prosjekt/systematisk-innhenting-av-fdv/
 * Tilgangen er den samme som ellers: kladd er privat, og delingsnivået
 * bestemmer hvem som får lese.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Enkeltside {

	/** Er innholdet alt skrevet ut på denne sidevisningen? */
	protected static $skrevet = false;

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'vakt' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'innhold' ) );

		// Blokktemaer uten egen mal for innholdstypen viser utdraget i stedet
		// for innholdet. Da bytter vi ut utdragsblokka med hele registreringen.
		add_filter( 'render_block', array( __CLASS__, 'blokk' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( __CLASS__, 'utdrag' ), 20, 2 );
	}

	/** Er vi på siden til én registrering? */
	public static function er_enkeltside() {
		return is_singular( BVP_Post_Type::POST_TYPE );
	}

	/**
	 * Utloggede som ikke har lesetilgang sendes til innlogging,
	 * så de kommer rett tilbake etterpå.
	 */
	public static function vakt() {
		if ( ! self::er_enkeltside() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( BVP_Access::can_view( $post ) || is_user_logged_in() ) {
			return;
		}

		wp_safe_redirect( wp_login_url( get_permalink( $post ) ) );
		exit;
	}

	/** Stilark og skript, som på skjemasiden. */
	public static function assets() {
		if ( ! self::er_enkeltside() ) {
			return;
		}

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		wp_enqueue_script( 'bvp-form', BVP_URL . 'assets/js/bvp-form.js', array(), BVP_VERSION, true );
	}

	/**
	 * Selve innholdet på siden.
	 *
	 * @param string $innhold
	 * @return string
	 */
	public static function innhold( $innhold ) {
		if ( ! self::er_enkeltside() || ! in_the_loop() || ! is_main_query() ) {
			return $innhold;
		}

		return self::registrering( true );
	}

	/**
	 * Bytt ut utdraget med hele registreringen.
	 *
	 * @param string  $utdrag
	 * @param WP_Post $post
	 * @return string
	 */
	public static function utdrag( $utdrag, $post = null ) {
		if ( ! self::er_enkeltside() ) {
			return $utdrag;
		}

		$vist = get_queried_object();

		if ( ! $vist instanceof WP_Post || ( $post instanceof WP_Post && (int) $post->ID !== (int) $vist->ID ) ) {
			return $utdrag;
		}

		return self::registrering();
	}

	/**
	 * Utdragsblokka i blokktemaer: vis registreringen, og dropp «Les mer».
	 *
	 * @param string $html
	 * @param array  $blokk
	 * @return string
	 */
	public static function blokk( $html, $blokk ) {
		if ( ! self::er_enkeltside() || empty( $blokk['blockName'] ) ) {
			return $html;
		}

		if ( 'core/read-more' === $blokk['blockName'] ) {
			return '';
		}

		if ( 'core/post-excerpt' !== $blokk['blockName'] ) {
			return $html;
		}

		return self::registrering( true );
	}

	/**
	 * Registreringen som HTML.
	 *
	 * @param bool $teller Skal denne utskriften telle som «vist»?
	 *                     Da kommer den bare én gang, selv om temaet
	 *                     har både innholds- og utdragsblokk.
	 * @return string
	 */
	protected static function registrering( $teller = false ) {
		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		if ( $teller ) {
			if ( self::$skrevet ) {
				return '';
			}

			self::$skrevet = true;
		}

		ob_start();
		echo '<div class="bvp bvp-enkeltside" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
		BVP_Form::render_single( $post );
		echo '</div>';

		return (string) ob_get_clean();
	}
}
