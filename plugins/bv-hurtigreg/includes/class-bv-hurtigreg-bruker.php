<?php
/**
 * Hurtigbrukeren: oppretting, engangslenker, bekreftelse, fullføring,
 * deaktivering og anonymisering.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Bruker {

	/** Formål for engangslenker. */
	const LENKE_VELKOMMEN = 'velkommen'; // bekrefter e-post, logger inn, åpner /chat/fullfor/ (HR-08)
	const LENKE_LOGIN     = 'login';     // logger inn (HR-05)
	const LENKE_BYTT      = 'bytt';      // bekrefter ny jobbadresse ved fullføring (Å-1)
	const LENKE_SLETT     = 'slett';     // åpner slettesiden fra e-posten (HR-13)

	/** Hindrer at fullføringen trigger seg selv når den bytter rolle. */
	private static $fullforer = array();

	public static function start(): void {
		// Fullføring (HR-09): uansett hvilken av de ordinære flytene som kobler
		// brukeren til foretak, merkes hurtigbrukeren som fullført og
		// byggchat_gjest erstattes.
		add_action( 'added_user_meta', array( __CLASS__, 'ved_meta' ), 10, 4 );
		add_action( 'updated_user_meta', array( __CLASS__, 'ved_meta' ), 10, 4 );
		add_action( 'set_user_role', array( __CLASS__, 'ved_set_role' ), 10, 3 );
		add_action( 'add_user_role', array( __CLASS__, 'ved_add_role' ), 10, 2 );

		// Utløpte og anonymiserte hurtigbrukere kan ikke logge inn med passord.
		add_filter( 'wp_authenticate_user', array( __CLASS__, 'stopp_passordinnlogging' ), 20 );
	}

	/* ---------------------------------------------------------------------
	 * Tilstand
	 * ------------------------------------------------------------------ */

	/** Har brukeren noen gang vært hurtigbruker? */
	public static function er_hurtig( int $id ): bool {
		return $id > 0 && '' !== (string) get_user_meta( $id, BV_Hurtigreg::META_OPPRETTET, true );
	}

	/** Hurtigbruker som ennå ikke har fullført, og som ikke er anonymisert. */
	public static function er_aktiv_hurtig( int $id ): bool {
		return self::er_hurtig( $id )
			&& ! get_user_meta( $id, BV_Hurtigreg::META_FULLFORT, true )
			&& ! get_user_meta( $id, BV_Hurtigreg::META_ANONYMISERT, true );
	}

	public static function er_bekreftet( int $id ): bool {
		return BV_Hurtigreg::STATUS_BEKREFTET === get_user_meta( $id, BV_Hurtigreg::META_STATUS, true );
	}

	public static function er_utlopt( int $id ): bool {
		return (bool) get_user_meta( $id, BV_Hurtigreg::META_UTLOPT, true );
	}

	/** Har brukeren bare gjesterollen? Da gjelder sperrene i HR-14. */
	public static function bare_gjest( $bruker ): bool {
		$bruker = $bruker instanceof WP_User ? $bruker : get_userdata( (int) $bruker );
		if ( ! $bruker ) {
			return false;
		}
		$roller = array_values( (array) $bruker->roles );
		return array( BV_Hurtigreg::ROLLE ) === $roller;
	}

	/** Livsfase til visning i wp-admin. */
	public static function fase( int $id ): string {
		if ( get_user_meta( $id, BV_Hurtigreg::META_ANONYMISERT, true ) ) {
			return 'anonymisert';
		}
		if ( get_user_meta( $id, BV_Hurtigreg::META_FULLFORT, true ) ) {
			return 'fullfort';
		}
		if ( self::er_utlopt( $id ) ) {
			return 'utlopt';
		}
		return self::er_bekreftet( $id ) ? BV_Hurtigreg::STATUS_BEKREFTET : BV_Hurtigreg::STATUS_UBEKREFTET;
	}

	/** Spørsmålskvoten ByggChat skal håndheve (HR-10). */
	public static function kvote( int $id ): int {
		if ( self::er_utlopt( $id ) ) {
			return 0;
		}
		return self::er_bekreftet( $id )
			? BV_Hurtigreg_Innstillinger::tall( 'kvote_bekreftet' )
			: BV_Hurtigreg_Innstillinger::tall( 'kvote_ubekreftet' );
	}

	/** Når tilgangen utløper hvis brukeren ikke fullfører (HR-11). */
	public static function utloper( int $id ): int {
		$opprettet = (int) get_user_meta( $id, BV_Hurtigreg::META_OPPRETTET, true );
		return $opprettet + BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' ) * DAY_IN_SECONDS;
	}

	/** Skal brukeren bytte til jobbadresse før foretakskoblingen? (Å-1) */
	public static function trenger_jobbepost( WP_User $u ): bool {
		return function_exists( 'bimverdi_is_general_domain' ) && bimverdi_is_general_domain( $u->user_email );
	}

	/**
	 * Er profilsteget gjort, og er e-posten bekreftet og klar for den ordinære
	 * foretakskoblingen? Uten bekreftet adresse kunne hvem som helst bli
	 * Tilleggskontakt på et foretaks domene.
	 */
	public static function klar_for_foretak( int $id ): bool {
		$u = get_userdata( $id );
		return $u
			&& self::er_bekreftet( $id )
			&& get_user_meta( $id, BV_Hurtigreg::META_PROFIL, true )
			&& ! get_user_meta( $id, BV_Hurtigreg::META_NY_EPOST, true )
			&& ! self::trenger_jobbepost( $u );
	}

	/** Venter brukeren på godkjenning av et foretak de har registrert? Da skal ikke tilgangen utløpe. */
	public static function har_ventende_foretak( int $id ): bool {
		$poster = get_posts( array(
			'post_type'      => 'foretak',
			'post_status'    => array( 'pending', 'draft' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => 'hovedkontaktperson', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => (string) $id,         // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'  => true,
		) );
		return ! empty( $poster );
	}

	/* ---------------------------------------------------------------------
	 * Oppretting (HR-04) og innlogging (HR-06)
	 * ------------------------------------------------------------------ */

	/**
	 * @return int|WP_Error Ny bruker-ID.
	 */
	public static function opprett( string $navn, string $epost, string $kampanje ) {
		$deler = explode( ' ', trim( preg_replace( '/\s+/', ' ', $navn ) ), 2 );

		do {
			$login = 'hurtig-' . strtolower( wp_generate_password( 12, false, false ) );
		} while ( username_exists( $login ) );

		// Invitasjonspluginen kobler nye brukere til foretak på user_register.
		// HR-04: ingen automatisk foretakskobling før fullføring.
		$fjernet = self::pause_invitasjonskobling();

		$id = wp_insert_user( array(
			'user_login'   => $login,
			'user_pass'    => wp_generate_password( 32, true, true ), // Ukjent for alle. Innlogging skjer med lenker.
			'user_email'   => $epost,
			'display_name' => $navn,
			'nickname'     => $deler[0],
			'first_name'   => $deler[0],
			'last_name'    => $deler[1] ?? '',
			'role'         => BV_Hurtigreg::ROLLE,
		) );

		foreach ( $fjernet as $callback ) {
			add_action( 'user_register', $callback, 10, 1 );
		}

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		update_user_meta( $id, BV_Hurtigreg::META_STATUS, BV_Hurtigreg::STATUS_UBEKREFTET );
		update_user_meta( $id, BV_Hurtigreg::META_OPPRETTET, time() );
		update_user_meta( $id, BV_Hurtigreg::META_KAMPANJE, $kampanje );
		update_user_meta( $id, BV_Hurtigreg::META_GA4, 'registrert' );
		// Skal ikke få WordPress' «show admin bar»-standard for nye brukere.
		update_user_meta( $id, 'show_admin_bar_front', 'false' );

		self::logg( sprintf( 'Ny hurtigbruker #%d (kampanje: %s)', $id, $kampanje ?: '–' ) );
		do_action( 'bv_hurtigreg_opprettet', $id, $kampanje );

		return (int) $id;
	}

	/** @return callable[] Callbackene som ble tatt av, så de kan settes på igjen. */
	private static function pause_invitasjonskobling(): array {
		global $wp_filter;
		$fjernet = array();
		if ( empty( $wp_filter['user_register'] ) ) {
			return $fjernet;
		}
		foreach ( $wp_filter['user_register']->callbacks as $prioritet => $callbacks ) {
			foreach ( $callbacks as $cb ) {
				$f = $cb['function'];
				if ( is_array( $f ) && isset( $f[1] ) && 'link_invited_user' === $f[1] ) {
					remove_action( 'user_register', $f, $prioritet );
					$fjernet[] = $f;
				}
			}
		}
		return $fjernet;
	}

	/** Logg inn med «Husk meg»-levetid (HR-06). */
	public static function logg_inn( int $id ): void {
		$u = get_userdata( $id );
		if ( ! $u ) {
			return;
		}
		wp_set_current_user( $id );
		wp_set_auth_cookie( $id, true, is_ssl() );
		do_action( 'wp_login', $u->user_login, $u );
	}

	/** Merk e-posten som bekreftet (HR-08). Idempotent. */
	public static function bekreft( int $id ): void {
		if ( ! self::er_hurtig( $id ) || self::er_bekreftet( $id ) ) {
			return;
		}
		update_user_meta( $id, BV_Hurtigreg::META_STATUS, BV_Hurtigreg::STATUS_BEKREFTET );
		update_user_meta( $id, BV_Hurtigreg::META_BEKREFTET, time() );
		self::logg( sprintf( 'Hurtigbruker #%d bekreftet e-posten', $id ) );
	}

	/** En utløpt hurtigbruker som logger inn med lenke, får gjesterollen tilbake for å kunne fullføre. */
	public static function gjenopprett_rolle( int $id ): void {
		$u = get_userdata( $id );
		if ( $u && self::er_aktiv_hurtig( $id ) && empty( $u->roles ) ) {
			$u->set_role( BV_Hurtigreg::ROLLE );
		}
	}

	/* ---------------------------------------------------------------------
	 * Engangslenker
	 * ------------------------------------------------------------------ */

	private static function hash( string $token ): string {
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	/**
	 * Lag en engangslenke. Bare hashen lagres, så en databaselekkasje gir ingen gyldige lenker.
	 *
	 * @param array $data Ekstra data, f.eks. ['neste' => 'fullfor'] eller ['epost' => '…'].
	 */
	public static function lag_lenke( int $id, string $formaal, int $levetid, array $data = array() ): string {
		$token  = wp_generate_password( 40, false, false );
		$lenker = self::lenker( $id );

		$lenker[ self::hash( $token ) ] = array(
			'f' => $formaal,
			'u' => time() + $levetid,
			'd' => $data,
		);
		// Behold bare de ti nyeste.
		uasort( $lenker, static function ( $a, $b ) {
			return $b['u'] <=> $a['u'];
		} );
		update_user_meta( $id, BV_Hurtigreg::META_LENKER, array_slice( $lenker, 0, 10, true ) );

		$sti = self::LENKE_SLETT === $formaal ? '/chat/slett/' : '/chat/lenke/';
		return add_query_arg( array( 'u' => $id, 't' => $token ), home_url( $sti ) );
	}

	/**
	 * Slå opp en lenke uten å bruke den opp.
	 *
	 * @return array|null ['f' => formål, 'u' => utløp, 'd' => data]
	 */
	public static function sjekk_lenke( int $id, string $token, ?string $formaal = null ): ?array {
		if ( $id <= 0 || strlen( $token ) < 20 ) {
			return null;
		}
		$lenker = self::lenker( $id );
		$hash   = self::hash( $token );
		foreach ( $lenker as $lagret => $lenke ) {
			if ( hash_equals( (string) $lagret, $hash ) ) {
				if ( (int) $lenke['u'] < time() ) {
					return null;
				}
				if ( null !== $formaal && $lenke['f'] !== $formaal ) {
					return null;
				}
				return $lenke;
			}
		}
		return null;
	}

	/** Bruk opp en lenke. */
	public static function bruk_lenke( int $id, string $token ): void {
		$lenker = self::lenker( $id );
		unset( $lenker[ self::hash( $token ) ] );
		update_user_meta( $id, BV_Hurtigreg::META_LENKER, $lenker );
	}

	/** Gyldige lenker, uten de utløpte. */
	private static function lenker( int $id ): array {
		$lenker = get_user_meta( $id, BV_Hurtigreg::META_LENKER, true );
		$lenker = is_array( $lenker ) ? $lenker : array();
		$na     = time();
		return array_filter( $lenker, static function ( $l ) use ( $na ) {
			return is_array( $l ) && isset( $l['u'], $l['f'] ) && (int) $l['u'] >= $na;
		} );
	}

	/** Lenkene i e-postene. */
	public static function velkomstlenke( int $id ): string {
		return self::lag_lenke( $id, self::LENKE_VELKOMMEN, BV_Hurtigreg_Innstillinger::tall( 'lenke_dager' ) * DAY_IN_SECONDS );
	}

	public static function slettelenke( int $id ): string {
		$levetid = ( BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' ) + BV_Hurtigreg_Innstillinger::tall( 'slett_dager' ) + 1 ) * DAY_IN_SECONDS;
		return self::lag_lenke( $id, self::LENKE_SLETT, $levetid );
	}

	/* ---------------------------------------------------------------------
	 * Fullføring (HR-09)
	 * ------------------------------------------------------------------ */

	/** Foretakskobling via brukermeta (gratisforetak, autojoin, invitasjon, godkjent foretak). */
	public static function ved_meta( $meta_id, $user_id, $nokkel, $verdi ): void {
		if ( ! in_array( $nokkel, array( 'bimverdi_company_id', 'bim_verdi_company_id' ), true ) || empty( $verdi ) ) {
			return;
		}
		self::fullfor( (int) $user_id, 'foretak' );
	}

	/** Noen satte en ordinær rolle på brukeren (f.eks. tilleggskontakt eller godkjent deltaker). */
	public static function ved_set_role( $user_id, $rolle, $gamle ): void {
		if ( '' === (string) $rolle || BV_Hurtigreg::ROLLE === $rolle ) {
			return;
		}
		if ( in_array( BV_Hurtigreg::ROLLE, (array) $gamle, true ) ) {
			self::fullfor( (int) $user_id, 'rolle' );
		}
	}

	/** add_role() i stedet for set_role(): gjesterollen må fjernes. */
	public static function ved_add_role( $user_id, $rolle ): void {
		if ( BV_Hurtigreg::ROLLE === $rolle ) {
			return;
		}
		$u = get_userdata( (int) $user_id );
		if ( $u && in_array( BV_Hurtigreg::ROLLE, (array) $u->roles, true ) ) {
			self::fullfor( (int) $user_id, 'rolle' );
		}
	}

	/**
	 * Gjør hurtigbrukeren til ordinær bruker. Rollen byggchat_gjest erstattes,
	 * hurtigmetaen beholdes for statistikk, og bruker-ID-en er den samme, så
	 * ByggChat-historikken følger med (sub i adgangskortet endres ikke).
	 */
	public static function fullfor( int $id, string $grunn ): void {
		if ( isset( self::$fullforer[ $id ] ) || ! self::er_aktiv_hurtig( $id ) ) {
			return;
		}
		self::$fullforer[ $id ] = true;

		$u = new WP_User( $id );
		if ( in_array( BV_Hurtigreg::ROLLE, (array) $u->roles, true ) ) {
			$u->remove_role( BV_Hurtigreg::ROLLE );
		}
		if ( empty( $u->roles ) ) {
			// Samme regel som rollesynken (bimverdi-roles-sync.php). Finner den
			// ingen foretaksdata ennå, blir brukeren «medlem» som i den ordinære
			// flyten, og rollesynken eller foretakskoblingen justerer etterpå.
			$mal = function_exists( 'bimverdi_compute_target_wp_role' ) ? bimverdi_compute_target_wp_role( $id ) : null;
			if ( ! $mal || ! get_role( $mal ) ) {
				$mal = get_role( 'medlem' ) ? 'medlem' : (string) get_option( 'default_role', 'subscriber' );
			}
			$u->set_role( $mal );
		}

		update_user_meta( $id, BV_Hurtigreg::META_FULLFORT, time() );
		update_user_meta( $id, BV_Hurtigreg::META_GA4, 'fullfort' );
		delete_user_meta( $id, BV_Hurtigreg::META_UTLOPT );
		delete_user_meta( $id, BV_Hurtigreg::META_NY_EPOST );

		self::logg( sprintf( 'Hurtigbruker #%d fullførte (%s), ny rolle: %s', $id, $grunn, implode( ',', $u->roles ) ) );
		do_action( 'bv_hurtigreg_fullfort', $id );

		unset( self::$fullforer[ $id ] );
	}

	/* ---------------------------------------------------------------------
	 * Utløp og sletting (HR-11, HR-13, Å-4)
	 * ------------------------------------------------------------------ */

	public static function deaktiver( int $id ): void {
		$u = get_userdata( $id );
		if ( ! $u || ! self::er_aktiv_hurtig( $id ) ) {
			return;
		}
		// Bare rene gjestekontoer. En bruker med ordinær rolle er ikke vår å skru av.
		if ( ! empty( $u->roles ) && ! self::bare_gjest( $u ) ) {
			return;
		}
		$u->set_role( '' );
		update_user_meta( $id, BV_Hurtigreg::META_UTLOPT, time() );
		WP_Session_Tokens::get_instance( $id )->destroy_all();
		do_action( 'bv_hurtigreg_deaktivert', $id );
	}

	/**
	 * Fjerner navn, e-post og alt annet personlig. Kampanjekode og tidspunkter
	 * beholdes, så statistikken per kampanje stemmer (Å-4: anonymiser).
	 */
	public static function anonymiser( int $id, string $grunn ): bool {
		$u = get_userdata( $id );
		if ( ! $u || ! self::er_aktiv_hurtig( $id ) ) {
			return false;
		}
		// Bare kontoer som aldri har vært noe annet enn gjest.
		if ( ! empty( $u->roles ) && ! self::bare_gjest( $u ) ) {
			return false;
		}

		add_filter( 'send_email_change_email', '__return_false', 99 );
		add_filter( 'send_password_change_email', '__return_false', 99 );
		$svar = wp_update_user( array(
			'ID'           => $id,
			'user_email'   => 'anonym-' . $id . '@hurtig.invalid',
			'display_name' => 'Slettet hurtigbruker',
			'nickname'     => 'slettet',
			'first_name'   => '',
			'last_name'    => '',
			'user_url'     => '',
			'description'  => '',
			'user_pass'    => wp_generate_password( 40, true, true ),
		) );
		remove_filter( 'send_email_change_email', '__return_false', 99 );
		remove_filter( 'send_password_change_email', '__return_false', 99 );

		if ( is_wp_error( $svar ) ) {
			self::logg( sprintf( 'Kunne ikke anonymisere #%d: %s', $id, $svar->get_error_message() ) );
			return false;
		}

		( new WP_User( $id ) )->set_role( '' );
		foreach ( array( BV_Hurtigreg::META_LENKER, BV_Hurtigreg::META_NY_EPOST, BV_Hurtigreg::META_GA4, 'phone', 'job_title', 'bimverdi_newsletter_subscribed', 'session_tokens' ) as $nokkel ) {
			delete_user_meta( $id, $nokkel );
		}
		update_user_meta( $id, BV_Hurtigreg::META_ANONYMISERT, time() );
		WP_Session_Tokens::get_instance( $id )->destroy_all();

		self::logg( sprintf( 'Hurtigbruker #%d anonymisert (%s)', $id, $grunn ) );
		do_action( 'bv_hurtigreg_anonymisert', $id, $grunn );
		return true;
	}

	/** @param WP_User|WP_Error $bruker */
	public static function stopp_passordinnlogging( $bruker ) {
		if ( $bruker instanceof WP_User && self::er_hurtig( $bruker->ID ) ) {
			if ( get_user_meta( $bruker->ID, BV_Hurtigreg::META_ANONYMISERT, true ) ) {
				return new WP_Error( 'bv_hurtig_slettet', 'Denne kontoen er slettet.' );
			}
			if ( self::er_aktiv_hurtig( $bruker->ID ) && empty( $bruker->roles ) ) {
				return new WP_Error( 'bv_hurtig_utlopt', 'Tilgangen til ByggChat er utløpt. Skriv inn e-posten din på bimverdi.no/chat for å få en ny innloggingslenke.' );
			}
		}
		return $bruker;
	}

	/* ---------------------------------------------------------------------
	 * Logg
	 * ------------------------------------------------------------------ */

	public static function logg( string $melding ): void {
		error_log( '[bv-hurtigreg] ' . $melding ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
