<?php
/**
 * Inngangssiden og skjemaet (HR-01, HR-02, HR-05).
 *
 * Siden /chat/ er en vanlig WordPress-side med [bv_hurtigreg]. Pluginen
 * bytter ut temaets mal med en lett mal uten meny og tunge skript.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Skjema {

	const SHORTCODE = 'bv_hurtigreg';

	/** @var array{feil: array<string,string>, verdier: array<string,string>, generell: string} */
	private static $tilstand = array( 'feil' => array(), 'verdier' => array(), 'generell' => '' );

	public static function start(): void {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'template_redirect', array( __CLASS__, 'ved_visning' ), 5 );
		add_filter( 'template_include', array( __CLASS__, 'lett_mal' ), 99 );
	}

	/** Er dette siden med skjemaet? */
	public static function er_skjemaside(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_queried_object();
		return $post instanceof WP_Post && has_shortcode( (string) $post->post_content, self::SHORTCODE );
	}

	public static function kampanje(): string {
		return BV_Hurtigreg_Innstillinger::rens_kampanje( wp_unslash( $_REQUEST['k'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Før siden vises: innlogget bruker sendes videre (HR-01), og en innsending behandles.
	 */
	public static function ved_visning(): void {
		if ( ! self::er_skjemaside() ) {
			return;
		}
		// Siden skal aldri mellomlagres. Den har per-bruker-oppførsel og skjema.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();

		$fullfor = ! empty( $_GET['fullfor'] ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( is_user_logged_in() ) {
			wp_redirect( $fullfor ? home_url( '/chat/fullfor/' ) : BV_Hurtigreg_Innstillinger::til_byggchat_url() );
			exit;
		}

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bv_hurtigreg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::behandle();
		}
	}

	/**
	 * Behandle innsendingen.
	 *
	 * Skjemaet har ingen nonce: siden kan bli mellomlagret av Servebolt, og en
	 * nonce for utloggede brukere er lik for alle og beskytter ikke. I stedet
	 * sjekkes opprinnelsen, og Turnstile, honeypot og ratebegrensning stopper
	 * roboter (HR-12).
	 */
	private static function behandle(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$kampanje = self::kampanje();
		$navn     = trim( sanitize_text_field( wp_unslash( $_POST['navn'] ?? '' ) ) );
		$epost    = strtolower( trim( sanitize_email( wp_unslash( $_POST['epost'] ?? '' ) ) ) );
		$vilkar   = ! empty( $_POST['vilkar'] );
		$neste    = ! empty( $_POST['neste'] ) && 'fullfor' === $_POST['neste'] ? 'fullfor' : 'chat';
		$honning  = (string) wp_unslash( $_POST[ BV_Hurtigreg_Vern::HONEYPOT ] ?? '' );
		$captcha  = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) );
		// phpcs:enable

		self::$tilstand['verdier'] = array( 'navn' => $navn, 'epost' => $epost );

		if ( ! self::riktig_opprinnelse() ) {
			self::$tilstand['generell'] = 'Skjemaet kunne ikke sendes. Last siden på nytt og prøv igjen.';
			return;
		}

		// Honeypot: lat som alt gikk bra, uten å gjøre noe.
		if ( '' !== $honning ) {
			self::til_sjekk_epost( $kampanje );
		}

		if ( ! BV_Hurtigreg_Innstillinger::aapent_for( $kampanje ) ) {
			return; // Skjemaet vises ikke. Shortcoden forklarer.
		}

		if ( BV_Hurtigreg_Vern::ip_sperret() ) {
			self::$tilstand['generell'] = 'Det har kommet mange registreringer fra denne nettverkstilkoblingen. Vent litt og prøv igjen, eller logg inn som vanlig.';
			return;
		}

		$feil = array();
		if ( '' === $navn ) {
			$feil['navn'] = 'Skriv inn navnet ditt.';
		} elseif ( mb_strlen( $navn ) > 100 ) {
			$feil['navn'] = 'Navnet er for langt.';
		}
		$epostfeil = BV_Hurtigreg_Vern::sjekk_epost( $epost );
		if ( '' !== $epostfeil ) {
			$feil['epost'] = $epostfeil;
		}
		if ( ! $vilkar ) {
			$feil['vilkar'] = 'Du må godta vilkårene og personvernerklæringen.';
		}
		if ( $feil ) {
			self::$tilstand['feil'] = $feil;
			return;
		}

		if ( ! BV_Hurtigreg_Vern::sjekk_turnstile( $captcha ) ) {
			self::$tilstand['generell'] = 'Vi klarte ikke å bekrefte at du er et menneske. Prøv igjen.';
			return;
		}

		BV_Hurtigreg_Vern::tell_ip();

		// HR-05: e-posten finnes fra før. Ingen ny bruker, men en innloggingslenke,
		// og samme melding på skjermen uansett.
		$eksisterende = email_exists( $epost );
		if ( $eksisterende ) {
			if ( ! BV_Hurtigreg_Vern::epost_sperret( $epost ) ) {
				BV_Hurtigreg_Vern::tell_epost( $epost );
				BV_Hurtigreg_Epost::innlogging( (int) $eksisterende, $neste );
			}
			self::til_sjekk_epost( $kampanje );
		}

		$id = BV_Hurtigreg_Bruker::opprett( $navn, $epost, $kampanje );
		if ( is_wp_error( $id ) ) {
			BV_Hurtigreg_Bruker::logg( 'Oppretting feilet: ' . $id->get_error_message() );
			// Kan være et samtidig dobbelttrykk med samme adresse. Behandle som eksisterende.
			if ( email_exists( $epost ) ) {
				self::til_sjekk_epost( $kampanje );
			}
			self::$tilstand['generell'] = 'Noe gikk galt. Prøv igjen om litt.';
			return;
		}

		BV_Hurtigreg_Bruker::logg_inn( $id );
		BV_Hurtigreg_Vern::tell_epost( $epost );
		BV_Hurtigreg_Epost::velkommen( $id );

		wp_safe_redirect( home_url( '/chat/inne/' ) );
		exit;
	}

	/** Felles svar for eksisterende e-post og honeypot. */
	private static function til_sjekk_epost( string $kampanje ): void {
		$args = array( 'sjekk' => '1' );
		if ( '' !== $kampanje ) {
			$args['k'] = $kampanje;
		}
		wp_safe_redirect( add_query_arg( $args, get_permalink() ) );
		exit;
	}

	/** Origin eller Referer må være vårt eget domene, når nettleseren sender dem. */
	private static function riktig_opprinnelse(): bool {
		$kilde = $_SERVER['HTTP_ORIGIN'] ?? ( $_SERVER['HTTP_REFERER'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( '' === $kilde || 'null' === $kilde ) {
			return true;
		}
		$vert = wp_parse_url( (string) $kilde, PHP_URL_HOST );
		return $vert && strtolower( $vert ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/** Den lette malen for skjemasiden. */
	public static function lett_mal( $mal ) {
		if ( self::er_skjemaside() && apply_filters( 'bv_hurtigreg_lett_mal', true ) ) {
			return BV_HURTIGREG_STI . 'templates/chat.php';
		}
		return $mal;
	}

	/* ---------------------------------------------------------------------
	 * Shortcode
	 * ------------------------------------------------------------------ */

	/** @param array|string $atts */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array(
			'tekst' => 'Spør ByggChat om BIM, standarder og digitalisering i byggenæringen. Skriv inn navn og e-post – så er du i gang.',
		), $atts, self::SHORTCODE );

		$kampanje = self::kampanje();
		$fullfor  = ! empty( $_GET['fullfor'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$logg_inn = function_exists( 'bimverdi_auth_url' ) ? bimverdi_auth_url( 'logg-inn' ) : wp_login_url();

		ob_start();
		echo '<div class="bvh">';

		if ( ! empty( $_GET['sjekk'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			?>
			<div class="bvh-boks" role="status">
				<h2 class="bvh-h">Sjekk e-posten din</h2>
				<p>Vi har sendt deg en lenke for å logge inn. Den kan ta et minutt å komme fram. Sjekk også søppelposten.</p>
			</div>
			<p class="bvh-liten"><a href="<?php echo esc_url( remove_query_arg( 'sjekk' ) ); ?>">Tilbake</a></p>
			<?php
			echo '</div>';
			return (string) ob_get_clean();
		}

		if ( ! BV_Hurtigreg_Innstillinger::aapent_for( $kampanje ) ) {
			?>
			<div class="bvh-boks" role="status">
				<p>Hurtigregistrering er ikke åpen akkurat nå.</p>
				<p><a href="<?php echo esc_url( $logg_inn ); ?>">Logg inn</a> eller <a href="<?php echo esc_url( home_url( '/registrer/' ) ); ?>">registrer deg</a> for å bruke ByggChat.</p>
			</div>
			<?php
			echo '</div>';
			return (string) ob_get_clean();
		}

		$t   = self::$tilstand;
		$v   = $t['verdier'];
		$f   = $t['feil'];
		$url = add_query_arg( array_filter( array( 'k' => $kampanje ) ), get_permalink() );

		if ( $fullfor ) {
			echo '<p class="bvh-intro">Skriv inn navnet og e-postadressen din, så sender vi deg en lenke for å fortsette registreringen.</p>';
		} else {
			echo '<p class="bvh-intro">' . esc_html( $atts['tekst'] ) . '</p>';
		}
		if ( '' !== $t['generell'] ) {
			echo '<div class="bvh-feilboks" role="alert">' . esc_html( $t['generell'] ) . '</div>';
		}
		?>
		<form class="bvh-skjema" method="post" action="<?php echo esc_url( $url ); ?>" novalidate>
			<input type="hidden" name="bv_hurtigreg" value="1">
			<?php if ( $fullfor ) : ?>
				<input type="hidden" name="neste" value="fullfor">
			<?php endif; ?>

			<div class="bvh-felt<?php echo isset( $f['navn'] ) ? ' har-feil' : ''; ?>">
				<label for="bvh-navn">Navn</label>
				<input id="bvh-navn" name="navn" type="text" autocomplete="name" autocapitalize="words" required maxlength="100"
					value="<?php echo esc_attr( $v['navn'] ?? '' ); ?>"
					aria-describedby="bvh-navn-feil"<?php echo isset( $f['navn'] ) ? ' aria-invalid="true"' : ''; ?>>
				<p class="bvh-feil" id="bvh-navn-feil" aria-live="polite"><?php echo esc_html( $f['navn'] ?? '' ); ?></p>
			</div>

			<div class="bvh-felt<?php echo isset( $f['epost'] ) ? ' har-feil' : ''; ?>">
				<label for="bvh-epost">E-post</label>
				<input id="bvh-epost" name="epost" type="email" inputmode="email" autocomplete="email" autocapitalize="off" spellcheck="false" required maxlength="190"
					value="<?php echo esc_attr( $v['epost'] ?? '' ); ?>"
					aria-describedby="bvh-epost-feil"<?php echo isset( $f['epost'] ) ? ' aria-invalid="true"' : ''; ?>>
				<p class="bvh-feil" id="bvh-epost-feil" aria-live="polite"><?php echo esc_html( $f['epost'] ?? '' ); ?></p>
			</div>

			<div class="bvh-hp" aria-hidden="true">
				<label for="bvh-hp">Nettside</label>
				<input id="bvh-hp" name="<?php echo esc_attr( BV_Hurtigreg_Vern::HONEYPOT ); ?>" type="text" tabindex="-1" autocomplete="off">
			</div>

			<div class="bvh-felt bvh-avkryss<?php echo isset( $f['vilkar'] ) ? ' har-feil' : ''; ?>">
				<label>
					<input name="vilkar" type="checkbox" value="1" required aria-describedby="bvh-vilkar-feil"<?php checked( ! empty( $_POST['vilkar'] ) ); // phpcs:ignore WordPress.Security.NonceVerification ?>>
					<span>Jeg godtar <a href="<?php echo esc_url( defined( 'BV_TERMS_URL' ) ? BV_TERMS_URL : home_url( '/betingelser/' ) ); ?>" target="_blank" rel="noopener">vilkårene</a> og <a href="<?php echo esc_url( home_url( '/personvern/' ) ); ?>" target="_blank" rel="noopener">personvernerklæringen</a>.</span>
				</label>
				<p class="bvh-feil" id="bvh-vilkar-feil" aria-live="polite"><?php echo esc_html( $f['vilkar'] ?? '' ); ?></p>
			</div>

			<?php if ( BV_Hurtigreg_Innstillinger::turnstile_paa() ) : ?>
				<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( (string) BV_Hurtigreg_Innstillinger::hent( 'turnstile_site' ) ); ?>" data-appearance="interaction-only" data-language="nb" data-action="hurtigreg" data-callback="bvhTurnstileOk"></div>
			<?php endif; ?>

			<button type="submit" class="bvh-knapp"><?php echo $fullfor ? 'Send meg lenke' : 'Start ByggChat'; ?></button>
		</form>

		<p class="bvh-liten">Har du konto? <a href="<?php echo esc_url( $logg_inn ); ?>">Logg inn</a></p>
		<?php
		self::klientvalidering();
		echo '</div>';
		return (string) ob_get_clean();
	}

	/** Norske feilmeldinger ved feltet, før skjemaet sendes (HR-02). Serveren sjekker uansett. */
	private static function klientvalidering(): void {
		?>
		<script>
		(function () {
			var f = document.querySelector('.bvh-skjema');
			if (!f) return;
			var meldinger = {
				navn: 'Skriv inn navnet ditt.',
				epost: 'Skriv inn e-postadressen din.',
				epostUgyldig: 'E-postadressen ser ikke riktig ut.',
				vilkar: 'Du må godta vilkårene og personvernerklæringen.'
			};
			function vis(felt, tekst) {
				var boks = felt.closest('.bvh-felt');
				var p = boks.querySelector('.bvh-feil');
				p.textContent = tekst;
				boks.classList.toggle('har-feil', !!tekst);
				if (felt.type !== 'checkbox') felt.setAttribute('aria-invalid', tekst ? 'true' : 'false');
			}
			function sjekk(felt) {
				if (felt.name === 'navn') return felt.value.trim() ? '' : meldinger.navn;
				if (felt.name === 'epost') {
					var v = felt.value.trim();
					if (!v) return meldinger.epost;
					return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? '' : meldinger.epostUgyldig;
				}
				if (felt.name === 'vilkar') return felt.checked ? '' : meldinger.vilkar;
				return '';
			}
			var felter = f.querySelectorAll('input[name=navn],input[name=epost],input[name=vilkar]');
			felter.forEach(function (felt) {
				felt.addEventListener(felt.type === 'checkbox' ? 'change' : 'blur', function () {
					if (felt.type === 'checkbox' || felt.value) vis(felt, sjekk(felt));
				});
			});
			f.addEventListener('submit', function (e) {
				var forste = null;
				felter.forEach(function (felt) {
					var feil = sjekk(felt);
					vis(felt, feil);
					if (feil && !forste) forste = felt;
				});
				if (forste) { e.preventDefault(); forste.focus(); return; }
				var knapp = f.querySelector('.bvh-knapp');
				knapp.disabled = true;
				knapp.textContent = 'Et øyeblikk …';
				// Med autofyll kan brukeren trykke før Turnstile er ferdig. Vent på svaret og send da.
				var ts = f.querySelector('.cf-turnstile');
				var svar = f.querySelector('[name="cf-turnstile-response"]');
				if (ts && (!svar || !svar.value)) {
					e.preventDefault();
					window.bvhVenter = true;
					setTimeout(function () { if (window.bvhVenter) { window.bvhVenter = false; f.submit(); } }, 8000);
				}
			});
			window.bvhTurnstileOk = function () {
				if (window.bvhVenter) { window.bvhVenter = false; f.submit(); }
			};
		})();
		</script>
		<?php
	}
}
