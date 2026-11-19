<?php
/**
 * Innstillinger som kan endres i wp-admin uten kodeendring (HR-10, HR-11, HR-12).
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Innstillinger {

	const OPTION = 'bv_hurtigreg_innstillinger';

	/** @var array|null */
	private static $cache = null;

	public static function start(): void {
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'nullstill' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'nullstill' ) );
	}

	public static function nullstill(): void {
		self::$cache = null;
	}

	/** @return array<string,mixed> */
	public static function standard(): array {
		return array(
			'aktiv'                => 1,
			'stengte_kampanjer'    => '',
			'kvote_ubekreftet'     => 10,
			'kvote_bekreftet'      => 50,
			'utlop_dager'          => 30,
			'slett_dager'          => 30,
			'paaminnelse_1_dager'  => 7,
			'paaminnelse_2_for'    => 3,
			'lenke_dager'          => 7,
			'ip_per_time'          => 5,
			'epost_per_dogn'       => 3,
			'turnstile_site'       => '',
			'turnstile_secret'     => '',
			'ga4_id'               => 'G-QJJEWJBXZ7',
			'byggchat_url'         => 'https://byggchat.bimverdi.no',
		);
	}

	/** @return array<string,mixed> */
	public static function alle(): array {
		if ( null === self::$cache ) {
			$lagret      = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $lagret ) ? $lagret : array(), self::standard() );
		}
		return self::$cache;
	}

	/** @return mixed */
	public static function hent( string $nokkel ) {
		$alle = self::alle();
		return $alle[ $nokkel ] ?? null;
	}

	public static function tall( string $nokkel ): int {
		return max( 0, (int) self::hent( $nokkel ) );
	}

	/**
	 * Turnstile-hemmeligheten kan (og bør) ligge i wp-config.php:
	 *   define( 'BV_HURTIGREG_TURNSTILE_SECRET', '…' );
	 */
	public static function turnstile_secret(): string {
		if ( defined( 'BV_HURTIGREG_TURNSTILE_SECRET' ) && BV_HURTIGREG_TURNSTILE_SECRET ) {
			return (string) BV_HURTIGREG_TURNSTILE_SECRET;
		}
		return (string) self::hent( 'turnstile_secret' );
	}

	public static function turnstile_paa(): bool {
		return '' !== (string) self::hent( 'turnstile_site' ) && '' !== self::turnstile_secret();
	}

	/** Er skjemaet åpent for denne kampanjekoden? (HR-12) */
	public static function aapent_for( string $kampanje ): bool {
		if ( ! (int) self::hent( 'aktiv' ) ) {
			return false;
		}
		if ( '' === $kampanje ) {
			return true;
		}
		return ! in_array( $kampanje, self::stengte_kampanjer(), true );
	}

	/** @return string[] */
	public static function stengte_kampanjer(): array {
		$liste = preg_split( '/[\s,]+/', strtolower( (string) self::hent( 'stengte_kampanjer' ) ) );
		return array_values( array_filter( array_map( array( __CLASS__, 'rens_kampanje' ), (array) $liste ) ) );
	}

	/** Kampanjekode: maks 32 tegn, [a-z0-9-] (HR-01). Ugyldig kode blir tom. */
	public static function rens_kampanje( $verdi ): string {
		$verdi = strtolower( trim( (string) $verdi ) );
		return preg_match( '/^[a-z0-9-]{1,32}$/', $verdi ) ? $verdi : '';
	}

	/** Grunnadressen til ByggChat, uten skråstrek til slutt. */
	public static function byggchat_url(): string {
		$url = esc_url_raw( (string) self::hent( 'byggchat_url' ) );
		return untrailingslashit( $url ?: 'https://byggchat.bimverdi.no' );
	}

	/**
	 * Lenken som sender en innlogget bruker til ByggChat. Går via SSO-pluginen
	 * (bimverdi-byggchat-sso.php), som lager adgangskortet i det brukeren klikker.
	 */
	public static function til_byggchat_url(): string {
		return home_url( '/?byggchat_go=1' );
	}

	/** @param mixed $inn */
	public static function rens( $inn ): array {
		$inn = is_array( $inn ) ? $inn : array();
		$std = self::standard();
		$ut  = array();

		$ut['aktiv'] = empty( $inn['aktiv'] ) ? 0 : 1;
		$ut['stengte_kampanjer'] = implode( "\n", array_unique( array_filter( array_map(
			array( __CLASS__, 'rens_kampanje' ),
			(array) preg_split( '/[\s,]+/', (string) ( $inn['stengte_kampanjer'] ?? '' ) )
		) ) ) );

		$tall = array(
			'kvote_ubekreftet'    => array( 0, 10000 ),
			'kvote_bekreftet'     => array( 0, 10000 ),
			'utlop_dager'         => array( 1, 365 ),
			'slett_dager'         => array( 1, 365 ),
			'paaminnelse_1_dager' => array( 1, 365 ),
			'paaminnelse_2_for'   => array( 1, 60 ),
			'lenke_dager'         => array( 1, 30 ),
			'ip_per_time'         => array( 1, 1000 ),
			'epost_per_dogn'      => array( 1, 50 ),
		);
		foreach ( $tall as $nokkel => list( $min, $max ) ) {
			$verdi          = isset( $inn[ $nokkel ] ) && '' !== $inn[ $nokkel ] ? (int) $inn[ $nokkel ] : $std[ $nokkel ];
			$ut[ $nokkel ] = min( $max, max( $min, $verdi ) );
		}

		$ut['turnstile_site'] = sanitize_text_field( (string) ( $inn['turnstile_site'] ?? '' ) );
		// Tomt felt i skjemaet betyr «behold lagret hemmelighet», så den aldri må vises i klartekst.
		$ny_secret              = sanitize_text_field( (string) ( $inn['turnstile_secret'] ?? '' ) );
		$ut['turnstile_secret'] = '' !== $ny_secret ? $ny_secret : (string) ( self::alle()['turnstile_secret'] ?? '' );
		if ( ! empty( $inn['turnstile_secret_slett'] ) ) {
			$ut['turnstile_secret'] = '';
		}

		$ga4         = strtoupper( sanitize_text_field( (string) ( $inn['ga4_id'] ?? '' ) ) );
		$ut['ga4_id'] = preg_match( '/^G-[A-Z0-9]{4,20}$/', $ga4 ) ? $ga4 : '';

		$url               = esc_url_raw( (string) ( $inn['byggchat_url'] ?? '' ), array( 'https' ) );
		$ut['byggchat_url'] = $url ? untrailingslashit( $url ) : $std['byggchat_url'];

		self::$cache = null;
		return $ut;
	}
}
