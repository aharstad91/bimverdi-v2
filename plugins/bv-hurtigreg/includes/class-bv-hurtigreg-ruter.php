<?php
/**
 * Sidene under /chat/: fullføring, engangslenker, sletting og «Du er inne!».
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Ruter {

	const QUERY_VAR = 'bv_hurtig';
	const SIDER     = array( 'fullfor', 'lenke', 'slett', 'inne' );

	public static function start(): void {
		add_action( 'init', array( __CLASS__, 'registrer_regler' ) );
		add_filter( 'query_vars', static function ( $vars ) {
			$vars[] = self::QUERY_VAR;
			return $vars;
		} );
		add_action( 'template_redirect', array( __CLASS__, 'ruter' ), 1 );
	}

	public static function registrer_regler(): void {
		add_rewrite_rule( '^chat/(' . implode( '|', self::SIDER ) . ')/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/** Adressen til skjemasiden. */
	public static function chat_url( array $args = array() ): string {
		return add_query_arg( $args, home_url( '/chat/' ) );
	}

	public static function ruter(): void {
		$side = (string) get_query_var( self::QUERY_VAR );
		if ( ! in_array( $side, self::SIDER, true ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
		header( 'Referrer-Policy: no-referrer' ); // Tokenet i adressen skal ikke lekke videre.

		switch ( $side ) {
			case 'fullfor':
				self::fullfor();
				break;
			case 'lenke':
				self::lenke();
				break;
			case 'slett':
				self::slett();
				break;
			case 'inne':
				self::inne();
				break;
		}
		exit;
	}

	/* ---------------------------------------------------------------------
	 * /chat/inne/ – «Du er inne!» og GA4-hendelsen ved registrering
	 * ------------------------------------------------------------------ */

	private static function inne(): void {
		$id = get_current_user_id();
		if ( ! $id || ! BV_Hurtigreg_Bruker::er_aktiv_hurtig( $id ) ) {
			wp_safe_redirect( self::chat_url() );
			exit;
		}
		$byggchat = BV_Hurtigreg_Innstillinger::til_byggchat_url();
		$ga4      = 'registrert' === get_user_meta( $id, BV_Hurtigreg::META_GA4, true );
		if ( $ga4 ) {
			delete_user_meta( $id, BV_Hurtigreg::META_GA4 );
		}

		BV_Hurtigreg_Side::topp( 'Du er inne!' );
		?>
		<h1>Du er inne!</h1>
		<p>Vi har sendt deg en e-post, så du kan fullføre registreringen senere.</p>
		<p><a class="bvh-knapp" href="<?php echo esc_url( $byggchat ); ?>">Åpne ByggChat</a></p>
		<noscript><meta http-equiv="refresh" content="1;url=<?php echo esc_url( $byggchat ); ?>"></noscript>
		<?php
		if ( $ga4 ) {
			echo BV_Hurtigreg_Tilgang::ga4_skript( 'bv_hurtigreg_registrert', (string) get_user_meta( $id, BV_Hurtigreg::META_KAMPANJE, true ), $byggchat ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<script>window.location.replace(' . wp_json_encode( $byggchat ) . ');</script>';
		}
		BV_Hurtigreg_Side::bunn();
	}

	/* ---------------------------------------------------------------------
	 * /chat/lenke/ – engangslenker fra e-post
	 * ------------------------------------------------------------------ */

	/**
	 * GET viser bare en «Fortsett»-side som sender seg selv med POST. Da
	 * bruker ikke e-postfiltre som åpner lenker på forhånd (Outlook Safe
	 * Links o.l.) opp engangslenken.
	 */
	private static function lenke(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- tokenet i lenken er beviset.
		$id    = absint( $_REQUEST['u'] ?? 0 );
		$token = sanitize_text_field( wp_unslash( $_REQUEST['t'] ?? '' ) );
		// phpcs:enable
		$lenke = BV_Hurtigreg_Bruker::sjekk_lenke( $id, $token );

		if ( ! $lenke || BV_Hurtigreg_Bruker::LENKE_SLETT === $lenke['f'] || get_user_meta( $id, BV_Hurtigreg::META_ANONYMISERT, true ) ) {
			BV_Hurtigreg_Side::melding(
				'Lenken virker ikke lenger',
				'<p>Lenken er brukt eller utløpt.</p><p>Skriv inn navn og e-post på nytt, så sender vi deg en ny lenke.</p>'
				. '<p><a class="bvh-knapp" href="' . esc_url( self::chat_url( array( 'fullfor' => 1 ) ) ) . '">Få ny lenke</a></p>',
				410
			);
		}

		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			BV_Hurtigreg_Side::topp( 'Et øyeblikk' );
			?>
			<h1>Et øyeblikk …</h1>
			<form method="post" action="<?php echo esc_url( home_url( '/chat/lenke/' ) ); ?>" id="bvh-lenke">
				<input type="hidden" name="u" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="t" value="<?php echo esc_attr( $token ); ?>">
				<button type="submit" class="bvh-knapp">Fortsett</button>
			</form>
			<script>document.getElementById('bvh-lenke').submit();</script>
			<?php
			BV_Hurtigreg_Side::bunn();
			return;
		}

		BV_Hurtigreg_Bruker::bruk_lenke( $id, $token );
		$u = get_userdata( $id );
		if ( ! $u ) {
			wp_safe_redirect( self::chat_url() );
			exit;
		}
		// Sikkerhetsnett: ingen lenkeinnlogging for kontoer med utvidede rettigheter.
		if ( user_can( $u, 'edit_others_posts' ) || user_can( $u, 'list_users' ) ) {
			wp_safe_redirect( function_exists( 'bimverdi_auth_url' ) ? bimverdi_auth_url( 'logg-inn' ) : wp_login_url() );
			exit;
		}

		$hurtig = BV_Hurtigreg_Bruker::er_aktiv_hurtig( $id );

		switch ( $lenke['f'] ) {
			case BV_Hurtigreg_Bruker::LENKE_BYTT:
				$ny = sanitize_email( (string) ( $lenke['d']['epost'] ?? '' ) );
				$eier = $ny ? email_exists( $ny ) : false;
				if ( ! $hurtig || ! is_email( $ny ) || ( $eier && (int) $eier !== $id ) ) {
					BV_Hurtigreg_Side::melding(
						'Adressen kan ikke brukes',
						'<p>E-postadressen er allerede registrert på en annen konto.</p><p><a href="' . esc_url( home_url( '/chat/fullfor/' ) ) . '">Prøv en annen adresse</a></p>',
						409
					);
				}
				$svar = wp_update_user( array( 'ID' => $id, 'user_email' => $ny ) );
				if ( is_wp_error( $svar ) ) {
					BV_Hurtigreg_Side::melding( 'Noe gikk galt', '<p>Vi fikk ikke lagret den nye adressen. Prøv igjen om litt.</p>', 500 );
				}
				delete_user_meta( $id, BV_Hurtigreg::META_NY_EPOST );
				BV_Hurtigreg_Bruker::bekreft( $id );
				BV_Hurtigreg_Bruker::logg_inn( $id );
				$mal = home_url( '/chat/fullfor/' );
				break;

			case BV_Hurtigreg_Bruker::LENKE_VELKOMMEN:
				BV_Hurtigreg_Bruker::bekreft( $id );
				BV_Hurtigreg_Bruker::gjenopprett_rolle( $id );
				BV_Hurtigreg_Bruker::logg_inn( $id );
				$mal = $hurtig ? home_url( '/chat/fullfor/' ) : home_url( '/min-side/' );
				break;

			case BV_Hurtigreg_Bruker::LENKE_LOGIN:
			default:
				if ( $hurtig ) {
					// Lenken kom fram, altså eier brukeren adressen.
					BV_Hurtigreg_Bruker::bekreft( $id );
					BV_Hurtigreg_Bruker::gjenopprett_rolle( $id );
				}
				BV_Hurtigreg_Bruker::logg_inn( $id );
				$fullfor = 'fullfor' === ( $lenke['d']['neste'] ?? '' ) || ( $hurtig && BV_Hurtigreg_Bruker::er_utlopt( $id ) );
				$mal     = $fullfor ? home_url( $hurtig ? '/chat/fullfor/' : '/min-side/' ) : BV_Hurtigreg_Innstillinger::til_byggchat_url();
				break;
		}

		wp_safe_redirect( $mal );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * /chat/slett/ – brukeren sletter kontoen selv (HR-13)
	 * ------------------------------------------------------------------ */

	private static function slett(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- token eller nonce sjekkes under.
		$id    = absint( $_REQUEST['u'] ?? 0 );
		$token = sanitize_text_field( wp_unslash( $_REQUEST['t'] ?? '' ) );
		// phpcs:enable
		$meg = get_current_user_id();

		$via_lenke = $id && null !== BV_Hurtigreg_Bruker::sjekk_lenke( $id, $token, BV_Hurtigreg_Bruker::LENKE_SLETT );
		if ( ! $via_lenke ) {
			$id = $meg;
		}

		if ( ! $id || ! BV_Hurtigreg_Bruker::er_aktiv_hurtig( $id ) ) {
			BV_Hurtigreg_Side::melding(
				'Kontoen kan ikke slettes her',
				'<p>Lenken er utløpt, eller kontoen er allerede slettet eller fullført.</p>'
				. '<p>Har du en vanlig BIM Verdi-konto, kan du be om sletting på <a href="mailto:post@bimverdi.no">post@bimverdi.no</a>.</p>',
				404
			);
		}

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$gyldig = $via_lenke || ( $id === $meg && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'bv_hurtig_slett' ) );
			if ( ! $gyldig ) {
				BV_Hurtigreg_Side::melding( 'Noe gikk galt', '<p>Last siden på nytt og prøv igjen.</p>', 400 );
			}
			BV_Hurtigreg_Bruker::anonymiser( $id, 'slettet av brukeren' );
			if ( $id === $meg ) {
				wp_destroy_current_session();
				wp_clear_auth_cookie();
				wp_set_current_user( 0 );
			}
			BV_Hurtigreg_Side::melding(
				'Kontoen er slettet',
				'<p>Navnet og e-postadressen din er slettet fra BIM Verdi. Du kan registrere deg på nytt når som helst.</p>'
			);
		}

		BV_Hurtigreg_Side::topp( 'Slett kontoen' );
		?>
		<h1>Slett kontoen</h1>
		<p>Navnet og e-postadressen din slettes fra BIM Verdi, og du blir logget ut av ByggChat.</p>
		<form method="post" action="<?php echo esc_url( home_url( '/chat/slett/' ) ); ?>">
			<?php if ( $via_lenke ) : ?>
				<input type="hidden" name="u" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="t" value="<?php echo esc_attr( $token ); ?>">
			<?php else : ?>
				<?php wp_nonce_field( 'bv_hurtig_slett' ); ?>
			<?php endif; ?>
			<button type="submit" class="bvh-knapp sekundaer">Slett kontoen min</button>
		</form>
		<p class="bvh-liten"><a href="<?php echo esc_url( BV_Hurtigreg_Innstillinger::til_byggchat_url() ); ?>">Nei, tilbake til ByggChat</a></p>
		<?php
		BV_Hurtigreg_Side::bunn();
	}

	/* ---------------------------------------------------------------------
	 * /chat/fullfor/ – fullføring (HR-09)
	 * ------------------------------------------------------------------ */

	private static function fullfor(): void {
		$id = get_current_user_id();
		if ( ! $id ) {
			wp_safe_redirect( self::chat_url( array( 'fullfor' => 1 ) ) );
			exit;
		}
		if ( ! BV_Hurtigreg_Bruker::er_aktiv_hurtig( $id ) ) {
			wp_safe_redirect( home_url( '/min-side/' ) );
			exit;
		}
		$u = wp_get_current_user();

		// Profilsteget er gjort og adressen er klar: videre til den ordinære
		// foretakskoblingen på Min side, akkurat som etter /aktiver-konto/.
		if ( BV_Hurtigreg_Bruker::klar_for_foretak( $id ) && BV_Hurtigreg_Bruker::er_bekreftet( $id ) ) {
			wp_safe_redirect( add_query_arg( 'welcome', '1', home_url( '/min-side/' ) ) );
			exit;
		}

		$feil     = array();
		$generell = '';
		$melding  = '';
		$handling = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? sanitize_key( wp_unslash( $_POST['handling'] ?? '' ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( '' !== $handling && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'bv_hurtig_fullfor' ) ) {
			$generell = 'Siden var utløpt. Prøv igjen.';
			$handling = '';
		}

		// Ny bekreftelseslenke (rate-begrenset per adresse).
		if ( 'send_bekreftelse' === $handling ) {
			if ( ! BV_Hurtigreg_Vern::epost_sperret( $u->user_email ) ) {
				BV_Hurtigreg_Vern::tell_epost( $u->user_email );
				BV_Hurtigreg_Epost::velkommen( $id );
			}
			$melding = 'Vi har sendt en ny lenke til ' . $u->user_email . '.';
		}

		// E-posten må være bekreftet før brukeren kan kobles til foretak.
		// Ellers kunne hvem som helst registrere seg med en adresse på et
		// foretaks domene og bli Tilleggskontakt der.
		if ( ! BV_Hurtigreg_Bruker::er_bekreftet( $id ) ) {
			self::fullfor_topp();
			?>
			<h1>Bekreft e-postadressen først</h1>
			<?php if ( $melding ) : ?><div class="bvh-boks" role="status"><p><?php echo esc_html( $melding ); ?></p></div><?php endif; ?>
			<p>Vi sendte en lenke til <strong><?php echo esc_html( $u->user_email ); ?></strong> da du registrerte deg. Trykk på lenken i e-posten for å bekrefte adressen og fortsette.</p>
			<form method="post">
				<?php wp_nonce_field( 'bv_hurtig_fullfor' ); ?>
				<input type="hidden" name="handling" value="send_bekreftelse">
				<button type="submit" class="bvh-knapp">Send lenken på nytt</button>
			</form>
			<p class="bvh-liten"><a href="<?php echo esc_url( BV_Hurtigreg_Innstillinger::til_byggchat_url() ); ?>">Tilbake til ByggChat</a></p>
			<?php
			BV_Hurtigreg_Side::bunn();
			return;
		}

		$ventende = (string) get_user_meta( $id, BV_Hurtigreg::META_NY_EPOST, true );

		if ( 'ny_lenke_jobb' === $handling && $ventende ) {
			if ( ! BV_Hurtigreg_Vern::epost_sperret( $ventende ) ) {
				BV_Hurtigreg_Vern::tell_epost( $ventende );
				BV_Hurtigreg_Epost::bytt_epost( $id, $ventende );
			}
			$melding = 'Vi har sendt en ny lenke til ' . $ventende . '.';
		}
		if ( 'annen_adresse' === $handling ) {
			delete_user_meta( $id, BV_Hurtigreg::META_NY_EPOST );
			$ventende = '';
		}

		if ( 'lagre' === $handling ) {
			list( $feil, $generell ) = self::lagre_profil( $u );
			if ( ! $feil && '' === $generell ) {
				$ventende = (string) get_user_meta( $id, BV_Hurtigreg::META_NY_EPOST, true );
				if ( '' === $ventende ) {
					wp_safe_redirect( add_query_arg( 'welcome', '1', home_url( '/min-side/' ) ) );
					exit;
				}
			}
		}

		// Venter på bekreftelse av jobbadressen.
		if ( '' !== $ventende ) {
			self::fullfor_topp();
			?>
			<h1>Sjekk e-posten din</h1>
			<?php if ( $melding ) : ?><div class="bvh-boks" role="status"><p><?php echo esc_html( $melding ); ?></p></div><?php endif; ?>
			<p>Vi har sendt en lenke til <strong><?php echo esc_html( $ventende ); ?></strong>. Trykk på lenken for å bekrefte adressen. Deretter kobler du deg til foretaket ditt.</p>
			<form method="post" style="margin:0 0 12px">
				<?php wp_nonce_field( 'bv_hurtig_fullfor' ); ?>
				<input type="hidden" name="handling" value="ny_lenke_jobb">
				<button type="submit" class="bvh-knapp">Send lenken på nytt</button>
			</form>
			<form method="post">
				<?php wp_nonce_field( 'bv_hurtig_fullfor' ); ?>
				<input type="hidden" name="handling" value="annen_adresse">
				<button type="submit" class="bvh-knapp sekundaer">Bruk en annen adresse</button>
			</form>
			<?php
			BV_Hurtigreg_Side::bunn();
			return;
		}

		self::fullfor_skjema( $u, $feil, $generell );
	}

	private static function fullfor_topp(): void {
		BV_Hurtigreg_Side::topp( 'Fullfør registreringen' );
	}

	/**
	 * Lagre profilsteget. Samme felter og krav som /aktiver-konto/ i den
	 * ordinære flyten (bimverdi-email-verification.php).
	 *
	 * @return array{0: array<string,string>, 1: string}
	 */
	private static function lagre_profil( WP_User $u ): array {
		// phpcs:disable WordPress.Security.NonceVerification -- sjekket i fullfor().
		$navn      = trim( sanitize_text_field( wp_unslash( $_POST['navn'] ?? '' ) ) );
		$mobil     = trim( sanitize_text_field( wp_unslash( $_POST['mobil'] ?? '' ) ) );
		$stilling  = trim( sanitize_text_field( wp_unslash( $_POST['stilling'] ?? '' ) ) );
		$passord   = (string) wp_unslash( $_POST['passord'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$jobbepost = strtolower( trim( sanitize_email( wp_unslash( $_POST['jobbepost'] ?? '' ) ) ) );
		$nyhetsbrev = ! empty( $_POST['nyhetsbrev'] ) ? '1' : '0';
		// phpcs:enable

		$feil = array();
		if ( '' === $navn ) {
			$feil['navn'] = 'Skriv inn navnet ditt.';
		}
		if ( '' === $mobil ) {
			$feil['mobil'] = 'Skriv inn mobilnummeret ditt.';
		}
		if ( '' === $stilling ) {
			$feil['stilling'] = 'Skriv inn stillingen din.';
		}
		if ( strlen( $passord ) < 8 ) {
			$feil['passord'] = 'Passordet må ha minst 8 tegn.';
		}

		$trenger_jobb = BV_Hurtigreg_Bruker::trenger_jobbepost( $u );
		if ( $trenger_jobb ) {
			$epostfeil = BV_Hurtigreg_Vern::sjekk_epost( $jobbepost );
			if ( '' !== $epostfeil ) {
				$feil['jobbepost'] = $epostfeil;
			} elseif ( function_exists( 'bimverdi_is_general_domain' ) && bimverdi_is_general_domain( $jobbepost ) ) {
				$feil['jobbepost'] = 'Bruk e-postadressen du har på jobben, ikke en privat adresse.';
			} elseif ( email_exists( $jobbepost ) ) {
				$feil['jobbepost'] = 'Adressen er allerede registrert. Logg inn med den kontoen i stedet.';
			}
		}

		if ( $feil ) {
			return array( $feil, '' );
		}

		$deler = explode( ' ', preg_replace( '/\s+/', ' ', $navn ), 2 );
		add_filter( 'send_password_change_email', '__return_false', 99 );
		$svar = wp_update_user( array(
			'ID'           => $u->ID,
			'display_name' => $navn,
			'first_name'   => $deler[0],
			'last_name'    => $deler[1] ?? '',
			'user_pass'    => $passord,
		) );
		remove_filter( 'send_password_change_email', '__return_false', 99 );
		if ( is_wp_error( $svar ) ) {
			return array( array(), 'Vi fikk ikke lagret opplysningene. Prøv igjen.' );
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( 'phone', $mobil, 'user_' . $u->ID );
			update_field( 'job_title', $stilling, 'user_' . $u->ID );
		} else {
			update_user_meta( $u->ID, 'phone', $mobil );
			update_user_meta( $u->ID, 'job_title', $stilling );
		}
		update_user_meta( $u->ID, 'bimverdi_account_type', 'profil' );
		update_user_meta( $u->ID, 'bimverdi_newsletter_subscribed', $nyhetsbrev );
		if ( ! get_user_meta( $u->ID, 'bimverdi_registered_at', true ) ) {
			update_user_meta( $u->ID, 'bimverdi_registered_at', current_time( 'mysql' ) );
		}
		update_user_meta( $u->ID, BV_Hurtigreg::META_PROFIL, time() );

		if ( $trenger_jobb ) {
			update_user_meta( $u->ID, BV_Hurtigreg::META_NY_EPOST, $jobbepost );
			if ( ! BV_Hurtigreg_Vern::epost_sperret( $jobbepost ) ) {
				BV_Hurtigreg_Vern::tell_epost( $jobbepost );
				BV_Hurtigreg_Epost::bytt_epost( $u->ID, $jobbepost );
			}
		}
		return array( array(), '' );
	}

	/**
	 * @param array<string,string> $feil
	 */
	private static function fullfor_skjema( WP_User $u, array $feil, string $generell ): void {
		$trenger_jobb = BV_Hurtigreg_Bruker::trenger_jobbepost( $u );
		// phpcs:disable WordPress.Security.NonceVerification
		$verdi = static function ( string $felt, string $standard = '' ) {
			return isset( $_POST[ $felt ] ) ? sanitize_text_field( wp_unslash( $_POST[ $felt ] ) ) : $standard;
		};
		// phpcs:enable
		$mobil    = (string) get_user_meta( $u->ID, 'phone', true );
		$stilling = (string) get_user_meta( $u->ID, 'job_title', true );

		$felt = static function ( string $navn, string $etikett, string $type, string $verdi, string $autocomplete, array $feil, string $hjelp = '', string $ekstra = '' ) {
			?>
			<div class="bvh-felt<?php echo isset( $feil[ $navn ] ) ? ' har-feil' : ''; ?>">
				<label for="bvh-<?php echo esc_attr( $navn ); ?>"><?php echo esc_html( $etikett ); ?></label>
				<input id="bvh-<?php echo esc_attr( $navn ); ?>" name="<?php echo esc_attr( $navn ); ?>" type="<?php echo esc_attr( $type ); ?>"
					value="<?php echo esc_attr( $verdi ); ?>" autocomplete="<?php echo esc_attr( $autocomplete ); ?>" required
					aria-describedby="bvh-<?php echo esc_attr( $navn ); ?>-feil"<?php echo isset( $feil[ $navn ] ) ? ' aria-invalid="true"' : ''; ?><?php echo $ekstra; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<?php if ( $hjelp ) : ?><p class="bvh-hjelp"><?php echo esc_html( $hjelp ); ?></p><?php endif; ?>
				<p class="bvh-feil" id="bvh-<?php echo esc_attr( $navn ); ?>-feil"><?php echo esc_html( $feil[ $navn ] ?? '' ); ?></p>
			</div>
			<?php
		};

		self::fullfor_topp();
		?>
		<h1>Fullfør registreringen</h1>
		<p>Fyll ut resten, og koble deg deretter til foretaket ditt. Da får du full tilgang til ByggChat og BIM Verdi. ByggChat-historikken din blir med.</p>
		<?php if ( $generell ) : ?><div class="bvh-feilboks" role="alert"><?php echo esc_html( $generell ); ?></div><?php endif; ?>

		<form method="post" action="<?php echo esc_url( home_url( '/chat/fullfor/' ) ); ?>">
			<?php wp_nonce_field( 'bv_hurtig_fullfor' ); ?>
			<input type="hidden" name="handling" value="lagre">

			<?php
			$felt( 'navn', 'Navn', 'text', $verdi( 'navn', $u->display_name ), 'name', $feil );
			?>
			<div class="bvh-felt">
				<label for="bvh-epost">E-post</label>
				<input id="bvh-epost" type="email" value="<?php echo esc_attr( $u->user_email ); ?>" readonly>
			</div>
			<?php
			if ( $trenger_jobb ) {
				$felt( 'jobbepost', 'E-post på jobben', 'email', $verdi( 'jobbepost' ), 'work email', $feil, 'BIM Verdi kobler deg til foretaket via e-postadressen. Privatadresser som Gmail og Outlook kan ikke brukes til det.', ' inputmode="email" autocapitalize="off" spellcheck="false"' );
			}
			$felt( 'mobil', 'Mobil', 'tel', $verdi( 'mobil', $mobil ), 'tel', $feil, '', ' inputmode="tel"' );
			$felt( 'stilling', 'Stilling', 'text', $verdi( 'stilling', $stilling ), 'organization-title', $feil );
			$felt( 'passord', 'Velg passord', 'password', '', 'new-password', $feil, 'Minst 8 tegn.', ' minlength="8"' );
			?>

			<div class="bvh-felt bvh-avkryss">
				<label>
					<input type="checkbox" name="nyhetsbrev" value="1"<?php checked( ! empty( $_POST['nyhetsbrev'] ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>>
					<span>Send meg nyhetsbrevet fra BIM Verdi.</span>
				</label>
			</div>

			<button type="submit" class="bvh-knapp">Fortsett til foretakskobling</button>
		</form>
		<p class="bvh-liten"><a href="<?php echo esc_url( BV_Hurtigreg_Innstillinger::til_byggchat_url() ); ?>">Ikke nå, tilbake til ByggChat</a></p>
		<?php
		BV_Hurtigreg_Side::bunn();
	}
}
