<?php
/**
 * Tilgang for byggchat_gjest (HR-07, HR-14) og adgangskortet til ByggChat.
 *
 * En gjest er innlogget bare der det trengs: hurtigsidene under /chat/,
 * overgangen til ByggChat (?byggchat_go=1) og, etter profilsteget, de
 * ordinære sidene for foretakskobling. Alle andre steder ser WordPress
 * gjesten som utlogget. Da kan ingen mal, REST-rute eller medlemssjekk gi
 * en gjest mer enn en anonym besøkende får.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Tilgang {

	/** Sider for den ordinære foretakskoblingen (HR-09). Åpne for gjesten etter profilsteget. */
	const FORETAKSSIDER = array( 'min-side', 'min-side/foretak' );
	const FORETAKSPREFIKS = array( 'min-side/foretak/registrer', 'min-side/registrer-foretak' );

	/** AJAX-handlinger i foretakskoblingen (bimverdi-brreg-api.php). */
	const AJAX = array( 'bimverdi_auto_join_foretak', 'bimverdi_save_bruker_foretak' );

	/** REST-ruter i foretakskoblingen. */
	const REST_PREFIKS = '/bimverdi/v1/brreg/';

	public static function start(): void {
		add_filter( 'determine_current_user', array( __CLASS__, 'avgrens_gjest' ), 100 );
		add_filter( 'show_admin_bar', array( __CLASS__, 'skjul_adminlinje' ) );
		add_filter( 'bimverdi_byggchat_claims', array( __CLASS__, 'adgangskort' ), 10, 2 );
		add_action( 'wp_footer', array( __CLASS__, 'ga4_ventende' ), 100 );
	}

	/**
	 * @param int|false $bruker_id
	 * @return int|false
	 */
	public static function avgrens_gjest( $bruker_id ) {
		if ( ! $bruker_id ) {
			return $bruker_id;
		}
		$bruker = get_userdata( (int) $bruker_id );
		if ( ! $bruker || ! BV_Hurtigreg_Bruker::bare_gjest( $bruker ) ) {
			return $bruker_id;
		}
		return self::tillatt_for_gjest( (int) $bruker_id ) ? $bruker_id : 0;
	}

	private static function tillatt_for_gjest( int $id ): bool {
		$sti = self::sti();

		// Hurtigsidene og overgangen til ByggChat.
		if ( 'chat' === $sti || 0 === strpos( $sti, 'chat/' ) ) {
			return true;
		}
		if ( '' === $sti && isset( $_GET['byggchat_go'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return true;
		}
		// Utlogging må virke.
		if ( 'logg-ut' === $sti || 'wp-login.php' === $sti ) {
			return true;
		}

		if ( ! BV_Hurtigreg_Bruker::klar_for_foretak( $id ) ) {
			return false;
		}

		if ( in_array( $sti, self::FORETAKSSIDER, true ) ) {
			return true;
		}
		foreach ( self::FORETAKSPREFIKS as $prefiks ) {
			if ( $sti === $prefiks || 0 === strpos( $sti, $prefiks . '/' ) ) {
				return true;
			}
		}
		if ( 'wp-admin/admin-ajax.php' === $sti ) {
			$handling = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			return in_array( $handling, self::AJAX, true );
		}
		$rute = self::rest_rute( $sti );
		return '' !== $rute && 0 === strpos( $rute, self::REST_PREFIKS );
	}

	/** Stien relativt til hjemmeadressen, uten skråstreker i endene. */
	public static function sti(): string {
		$sti  = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( '' !== $base && 0 === strpos( $sti, $base ) ) {
			$sti = trim( substr( $sti, strlen( $base ) ), '/' );
		}
		return $sti;
	}

	private static function rest_rute( string $sti ): string {
		if ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return '/' . ltrim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/' ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$prefiks = rest_get_url_prefix();
		if ( 0 === strpos( $sti, $prefiks . '/' ) ) {
			return '/' . substr( $sti, strlen( $prefiks ) + 1 );
		}
		return '';
	}

	public static function skjul_adminlinje( $vis ) {
		$u = wp_get_current_user();
		return ( $u->exists() && BV_Hurtigreg_Bruker::bare_gjest( $u ) ) ? false : $vis;
	}

	/**
	 * Adgangskortet. Samme RS256-JWT, samme nøkkel og samme felter som for
	 * alle andre (bimverdi-byggchat-sso.php). For en hurtigbruker settes
	 * nivaa til «Hurtigbruker», og feltet «hurtig» forteller ByggChat
	 * status og kvote (HR-07, HR-10).
	 *
	 * @param array   $claims
	 * @param WP_User $u
	 */
	public static function adgangskort( $claims, $u ) {
		if ( ! $u instanceof WP_User || ! BV_Hurtigreg_Bruker::er_aktiv_hurtig( $u->ID ) ) {
			return $claims;
		}
		$id       = $u->ID;
		$kampanje = (string) get_user_meta( $id, BV_Hurtigreg::META_KAMPANJE, true );

		$claims['nivaa']   = 'Hurtigbruker';
		$claims['foretak'] = null;
		$claims['hurtig']  = array(
			'status'      => BV_Hurtigreg_Bruker::er_utlopt( $id ) ? 'utlopt' : ( BV_Hurtigreg_Bruker::er_bekreftet( $id ) ? BV_Hurtigreg::STATUS_BEKREFTET : BV_Hurtigreg::STATUS_UBEKREFTET ),
			'kvote'       => BV_Hurtigreg_Bruker::kvote( $id ),
			'kampanje'    => '' !== $kampanje ? $kampanje : null,
			'opprettet'   => (int) get_user_meta( $id, BV_Hurtigreg::META_OPPRETTET, true ),
			'utloper'     => BV_Hurtigreg_Bruker::utloper( $id ),
			'fullfor_url' => home_url( '/chat/fullfor/' ),
			'slett_url'   => home_url( '/chat/slett/' ),
		);
		return $claims;
	}

	/**
	 * GA4-hendelse ved fullføring (HR-15). Sendes på første sidevisning etter
	 * fullføringen, og bare når brukeren har godtatt analysecookies.
	 */
	public static function ga4_ventende(): void {
		$id = get_current_user_id();
		if ( ! $id || 'fullfort' !== get_user_meta( $id, BV_Hurtigreg::META_GA4, true ) ) {
			return;
		}
		delete_user_meta( $id, BV_Hurtigreg::META_GA4 );
		echo self::ga4_skript( 'bv_hurtigreg_fullfort', (string) get_user_meta( $id, BV_Hurtigreg::META_KAMPANJE, true ), '' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Et lite skript som sender én GA4-hendelse hvis brukeren har samtykket.
	 * Lastes gtag ikke allerede av temaet, lastes den her.
	 *
	 * @param string $videre URL å gå til etterpå, eller tom.
	 */
	public static function ga4_skript( string $hendelse, string $kampanje, string $videre ): string {
		$ga4 = (string) BV_Hurtigreg_Innstillinger::hent( 'ga4_id' );
		$cfg = wp_json_encode( array(
			'id'       => $ga4,
			'hendelse' => $hendelse,
			'kampanje' => '' !== $kampanje ? $kampanje : '(ingen)',
			'videre'   => $videre,
		) );
		return <<<HTML
<script>
(function (c) {
	var ferdig = false;
	function videre() { if (!ferdig && c.videre) { ferdig = true; window.location.replace(c.videre); } }
	var samtykke = null;
	try { samtykke = localStorage.getItem('bv_cookie_consent'); } catch (e) {}
	if (samtykke !== 'accepted' || !c.id) { videre(); return; }
	if (!window.gtag) {
		var s = document.createElement('script');
		s.async = true;
		s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(c.id);
		document.head.appendChild(s);
		window.dataLayer = window.dataLayer || [];
		window.gtag = function () { dataLayer.push(arguments); };
		gtag('js', new Date());
		gtag('config', c.id);
	}
	gtag('event', c.hendelse, { kampanje: c.kampanje, event_callback: videre, event_timeout: 1500 });
	setTimeout(videre, 1600);
})({$cfg});
</script>
HTML;
	}
}
