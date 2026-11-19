<?php
/**
 * Validering og misbruksvern: e-post (HR-03), Turnstile, honeypot og
 * ratebegrensning (HR-12).
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Vern {

	/** Navnet på honeypot-feltet. Et vanlig navn, så roboter fyller det ut. */
	const HONEYPOT = 'nettside';

	/**
	 * Kontroller e-postadressen.
	 *
	 * Gmail, Outlook o.l. tillates for hurtigbrukere (Å-1). Domenekravet slår
	 * inn ved fullføring.
	 *
	 * @return string Feilmelding, eller tom streng når adressen er godkjent.
	 */
	public static function sjekk_epost( string $epost ): string {
		if ( '' === $epost ) {
			return 'Skriv inn e-postadressen din.';
		}
		if ( ! is_email( $epost ) ) {
			return 'E-postadressen ser ikke riktig ut.';
		}
		if ( function_exists( 'bimverdi_is_disposable_domain' ) && bimverdi_is_disposable_domain( $epost ) ) {
			return 'Vi godtar ikke engangsadresser. Bruk e-postadressen du bruker til vanlig.';
		}
		if ( ! self::har_mx( substr( strrchr( $epost, '@' ), 1 ) ) ) {
			return 'Vi finner ikke e-postdomenet. Sjekk at adressen er riktig.';
		}
		return '';
	}

	/** MX-oppslag, mellomlagret et døgn per domene så skjemaet holder seg raskt. */
	public static function har_mx( string $domene ): bool {
		$domene = strtolower( trim( $domene ) );
		if ( '' === $domene ) {
			return false;
		}
		// Kjente generelle domener har MX. Spar oppslaget.
		if ( function_exists( 'bimverdi_is_general_domain' ) && bimverdi_is_general_domain( 'x@' . $domene ) ) {
			return true;
		}
		$nokkel = 'bv_hurtig_mx_' . md5( $domene );
		$lagret = get_transient( $nokkel );
		if ( false !== $lagret ) {
			return '1' === $lagret;
		}
		$ok = function_exists( 'checkdnsrr' ) ? checkdnsrr( $domene . '.', 'MX' ) : true;
		set_transient( $nokkel, $ok ? '1' : '0', $ok ? DAY_IN_SECONDS : HOUR_IN_SECONDS );
		return $ok;
	}

	/* ---------------------------------------------------------------------
	 * Cloudflare Turnstile
	 * ------------------------------------------------------------------ */

	public static function sjekk_turnstile( string $svar ): bool {
		if ( ! BV_Hurtigreg_Innstillinger::turnstile_paa() ) {
			return true; // Ikke satt opp. Honeypot og ratebegrensning gjelder fortsatt.
		}
		if ( '' === $svar ) {
			return false;
		}
		$res = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
			'timeout' => 5,
			'body'    => array(
				'secret'   => BV_Hurtigreg_Innstillinger::turnstile_secret(),
				'response' => $svar,
				'remoteip' => self::ip(),
			),
		) );
		if ( is_wp_error( $res ) ) {
			// Cloudflare svarer ikke. Slipp gjennom heller enn å stenge standen;
			// honeypot og ratebegrensning stopper fortsatt masseregistrering.
			BV_Hurtigreg_Bruker::logg( 'Turnstile utilgjengelig: ' . $res->get_error_message() );
			return true;
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return ! empty( $data['success'] );
	}

	/* ---------------------------------------------------------------------
	 * Ratebegrensning
	 * ------------------------------------------------------------------ */

	/** Klientens IP, samme rekkefølge som resten av siden (bimverdi-email-verification.php). */
	public static function ip(): string {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $nokkel ) {
			if ( empty( $_SERVER[ $nokkel ] ) ) {
				continue;
			}
			$ip = trim( explode( ',', wp_unslash( (string) $_SERVER[ $nokkel ] ) )[0] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return (string) apply_filters( 'bv_hurtigreg_ip', $ip );
			}
		}
		return '0.0.0.0';
	}

	/** Maks registreringer per IP per time. Teller innsendinger, også de som går til eksisterende konto. */
	public static function ip_sperret(): bool {
		return self::teller( 'ip', self::ip() ) >= BV_Hurtigreg_Innstillinger::tall( 'ip_per_time' );
	}

	public static function tell_ip(): void {
		self::tell( 'ip', self::ip(), HOUR_IN_SECONDS );
	}

	/** Maks e-poster per adresse per døgn. */
	public static function epost_sperret( string $epost ): bool {
		return self::teller( 'ep', strtolower( $epost ) ) >= BV_Hurtigreg_Innstillinger::tall( 'epost_per_dogn' );
	}

	public static function tell_epost( string $epost ): void {
		self::tell( 'ep', strtolower( $epost ), DAY_IN_SECONDS );
	}

	/** Nøkkelen er en hash, så verken IP eller e-post lagres i klartekst (HR-13). */
	private static function nokkel( string $type, string $verdi ): string {
		return 'bv_hurtig_' . $type . '_' . substr( hash_hmac( 'sha256', $verdi, wp_salt( 'nonce' ) ), 0, 32 );
	}

	private static function teller( string $type, string $verdi ): int {
		$data = get_transient( self::nokkel( $type, $verdi ) );
		return is_array( $data ) ? (int) $data['n'] : 0;
	}

	/** Fast vindu: utløpet settes ved første forsøk og flyttes ikke. */
	private static function tell( string $type, string $verdi, int $vindu ): void {
		$nokkel = self::nokkel( $type, $verdi );
		$data   = get_transient( $nokkel );
		if ( ! is_array( $data ) || (int) $data['slutt'] <= time() ) {
			$data = array( 'n' => 0, 'slutt' => time() + $vindu );
		}
		++$data['n'];
		set_transient( $nokkel, $data, max( 1, (int) $data['slutt'] - time() ) );
	}
}
