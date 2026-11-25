<?php
/**
 * Frontend: shortcode, visning og innsending.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Form {

	const NONCE   = 'bvp_save_prosjekt';
	const ACTION  = 'bvp_save';
	const GJENOPPRETT  = 'bvp_gjenopprett';
	const SLETT_NONCE  = 'bvp_slett_prosjekt';
	const SLETT_ACTION = 'bvp_slett';
	const SHORTCODE = 'bv_pilot_prosjekt';

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle' ) ); // handle() tar vare på det som er skrevet.
		add_action( 'admin_post_' . self::SLETT_ACTION, array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_nopriv_' . self::SLETT_ACTION, array( __CLASS__, 'handle_nopriv' ) );

		// Skjemaene sendes til nettsiden, ikke til wp-admin. Nettstedet sender
		// vanlige medlemmer bort fra wp-admin (til Min side), og da forsvant
		// innsendingen før den ble lagret.
		add_action( 'wp_loaded', array( __CLASS__, 'front_post' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		global $post;

		if ( ! $post instanceof WP_Post || ! has_shortcode( (string) $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		wp_enqueue_script( 'bvp-form', BVP_URL . 'assets/js/bvp-form.js', array(), BVP_VERSION, true );
	}

	/** Handlingene som kan sendes til nettsiden. */
	public static function front_actions() {
		return array(
			self::ACTION,
			self::SLETT_ACTION,
			BVP_Meldinger::ACTION,
			BVP_Logg::ACTION,
		);
	}

	/** Adressen skjemaene sendes til. */
	public static function post_url() {
		return home_url( '/' );
	}

	/** Skjult felt som merker innsendingen som vår. */
	public static function post_felt() {
		return '<input type="hidden" name="bvp_front" value="1">';
	}

	/**
	 * Ta imot skjemaene på nettsiden - samme behandling som admin-post.php.
	 */
	public static function front_post() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		if ( empty( $_POST['bvp_front'] ) || empty( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $action, self::front_actions(), true ) ) {
			return;
		}

		nocache_headers();

		// Hver behandler sjekker nonce og tilgang selv, og avslutter med en videresending.
		if ( is_user_logged_in() ) {
			do_action( 'admin_post_' . $action );
		} else {
			do_action( 'admin_post_nopriv_' . $action );
		}

		exit;
	}

	/**
	 * Shortcode-utdata.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'tittel' => 'BV-pilot/prosjekt - rask avklaring',
			),
			$atts,
			self::SHORTCODE
		);

		// Sørg for at stil/skript lastes også når shortcoden ligger i en widget eller mal.
		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		wp_enqueue_script( 'bvp-form', BVP_URL . 'assets/js/bvp-form.js', array(), BVP_VERSION, true );

		$view    = isset( $_GET['bvp_view'] ) ? sanitize_key( wp_unslash( $_GET['bvp_view'] ) ) : 'liste';
		$post_id = isset( $_GET['bvp_id'] ) ? absint( $_GET['bvp_id'] ) : 0;

		// Lesevisning er også åpen for utloggede når registreringen er publisert for alle.
		if ( 'vis' === $view && $post_id ) {
			ob_start();
			echo '<div class="bvp" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
			self::render_view( $post_id );
			echo '</div>';

			return ob_get_clean();
		}

		if ( ! is_user_logged_in() ) {
			ob_start();

			// Øverst: det som er registrert, og det som er publisert for alle.
			echo BVP_Oversikt::shortcode( array( 'tittel' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput

			echo '<div class="bvp" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
			self::render_krav( false );
			echo '</div>';

			return ob_get_clean();
		}

		ob_start();
		echo '<div class="bvp" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
		self::notices();

		if ( 'skjema' === $view ) {
			self::render_form( $post_id, $atts['tittel'] );
		} else {
			self::render_list( $atts['tittel'] );
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Kort påminnelse til innloggede som ennå ikke er koblet til et foretak.
	 */
	protected static function render_foretak_varsel() {
		if ( BVP_Foretak::for_user( get_current_user_id() ) ) {
			return;
		}

		$settings = BVP_Admin::get_settings();
		$kontakt  = ! empty( $settings['kontakt_url'] ) ? $settings['kontakt_url'] : '';
		$har_prof = (bool) BVP_Foretak::all();

		echo '<div class="bvp-notice bvp-notice--warning">';

		if ( $har_prof ) {
			printf(
				'Brukerprofilen din er ikke koblet til et foretak ennå. Velg foretaket ditt under <a href="#bvp-access">Deling og tilgang</a> nederst i skjemaet - koblingen lagres sammen med registreringen.%s',
				$kontakt
					? sprintf( ' Finner du ikke foretaket, <a href="%s">si fra her</a>.', esc_url( $kontakt ) )
					: ''
			);
		} else {
			printf(
				'Brukerprofilen din er ikke koblet til et foretak, og ingen foretaksprofiler er opprettet ennå. Du kan fylle ut og lagre som kladd nå.%s',
				$kontakt
					? sprintf( ' <a href="%s">Si fra til BIM Verdi</a>, så oppretter vi foretaksprofilen og kobler deg til.', esc_url( $kontakt ) )
					: ''
			);
		}

		echo '</div>';
	}

	/**
	 * Forklaring av hva som kreves for å registrere - og hva man gjør for å komme i gang.
	 *
	 * @param bool $innlogget Er brukeren innlogget (men uten foretakskobling)?
	 */
	public static function render_krav( $innlogget ) {
		$settings    = BVP_Admin::get_settings();
		$kontakt_url = ! empty( $settings['kontakt_url'] ) ? $settings['kontakt_url'] : '';
		$kontakt_ep  = ! empty( $settings['kontakt_epost'] ) ? $settings['kontakt_epost'] : '';
		$hjelp       = ! empty( $settings['hjelp_url'] ) ? $settings['hjelp_url'] : '';
		$har_prof    = (bool) BVP_Foretak::all();
		?>
		<div class="bvp-krav">
			<h3 class="bvp-krav__tittel">Du må ha en brukerprofil koblet til et foretak</h3>

			<p class="bvp-krav__ingress">
				Du må ha en brukerprofil hos BIM Verdi med kobling til et foretak i Foretaksregisteret/BRREG
				for å registrere eller oppdatere et BV-pilot/prosjekt.
			</p>

			<p class="bvp-krav__slik">Slik kommer du i gang:</p>

			<ol class="bvp-krav__steg">
				<?php if ( ! $innlogget ) : ?>
					<li>
						<strong>Har du allerede en brukerprofil?</strong>
						Logg inn, så kommer du rett til skjemaet.
						<br><a class="bvp-button" href="<?php echo esc_url( wp_login_url( self::form_url( 0 ) ) ); ?>">Logg inn</a>
					</li>
					<li>
						<strong>Har du ikke brukerprofil ennå?</strong>
						Registrer deg som deltaker i BIM Verdi. Foretaket ditt må være registrert i
						Foretaksregisteret/BRREG, og du trenger en e-postadresse hos foretaket.
						<?php if ( $hjelp ) : ?>
							<br><a class="bvp-button bvp-button--secondary" href="<?php echo esc_url( $hjelp ); ?>">Slik blir du deltaker</a>
						<?php endif; ?>
					</li>
				<?php else : ?>
					<li>
						<strong>Brukerprofilen din er ikke koblet til et foretak.</strong>
						<?php if ( $har_prof ) : ?>
							Velg foretaket ditt i skjemaet under, i seksjonen
							<a href="#bvp-access">Deling og tilgang</a>. Koblingen lagres når du lagrer registreringen.
						<?php else : ?>
							Ingen foretaksprofiler er opprettet ennå - ta kontakt med BIM Verdi.
						<?php endif; ?>
					</li>
					<li>
						<strong>Finner du ikke foretaket ditt?</strong>
						Da mangler det en foretaksprofil hos BIM Verdi. Oppgi foretaksnavn og organisasjonsnummer
						til oss, så oppretter vi profilen og kobler deg til.
					</li>
				<?php endif; ?>

				<li>
					<strong>Trenger du hjelp?</strong>
					<?php if ( $kontakt_url ) : ?>
						Ta kontakt med BIM Verdi her:
						<a href="<?php echo esc_url( $kontakt_url ); ?>"><?php echo esc_html( $kontakt_url ); ?></a>,
					<?php elseif ( $kontakt_ep ) : ?>
						Ta kontakt med BIM Verdi på
						<a href="mailto:<?php echo esc_attr( $kontakt_ep ); ?>"><?php echo esc_html( $kontakt_ep ); ?></a>,
					<?php else : ?>
						Ta kontakt med BIM Verdi,
					<?php endif; ?>
					så hjelper vi deg med brukerprofil og foretakskobling.
				</li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Meldinger etter lagring.
	 */
	protected static function notices() {
		$msg = isset( $_GET['bvp_msg'] ) ? sanitize_key( wp_unslash( $_GET['bvp_msg'] ) ) : '';

		$map = array(
			'kladd'     => array( 'success', 'Registreringen er lagret som kladd. Du kan fortsette senere.' ),
			'sendt'     => array( 'success', 'Takk! BV-pilot/prosjektn er sendt inn. Du kan fortsatt oppdatere den.' ),
			'oppdatert' => array( 'success', 'Endringene er lagret.' ),
			'mangler'   => array( 'error', 'Registreringen er IKKE sendt inn - den er lagret som kladd, fordi noen påkrevde felt mangler. Fyll ut de merkede feltene under og trykk «Send inn» igjen.' ),
			'ingen'     => array( 'error', 'Fant ingen registrering med den ID-en, eller du har ikke tilgang til den.' ),
			'melding_sendt' => array( 'success', 'Meldingen er sendt.' ),
			'tom_melding'   => array( 'warning', 'Du må skrive noe før du sender meldingen.' ),
			'feil'      => array( 'error', 'Noe gikk galt under lagringen. Prøv igjen.' ),
			'mistet_tilgang' => array( 'warning', 'Du har ikke lenger tilgang til registreringen du redigerte - den kan være slettet eller flyttet til en annen eier. Det du skrev står her, i et nytt skjema. Lagre det som kladd, og ta kontakt med BIM Verdi.' ),
			'utlopt'    => array( 'warning', 'Innloggingen hadde gått ut, så ingenting ble lagret. Alt du hadde fylt ut står her fortsatt - trykk «Lagre som kladd» nå, så er du trygg.' ),
			'slettet'       => array( 'success', 'Registreringen er slettet. Den ligger i papirkurven i wp-admin til den tømmes - ta kontakt med BIM Verdi hvis du angrer.' ),
			'ikke_bekreftet' => array( 'warning', 'Registreringen ble ikke slettet. Du må krysse av for at du er sikker.' ),
			'kan_ikke_slette' => array( 'error', 'Du kan ikke slette denne registreringen. Bare den som eier den, eller BIM Verdi, kan gjøre det.' ),
			'angret'        => array( 'success', 'Endringen er tilbakestilt. Tilbakestillingen står i endringsloggen.' ),
			'angre_feil'    => array( 'error', 'Klarte ikke å tilbakestille endringen.' ),
		);

		if ( isset( $map[ $msg ] ) ) {
			printf(
				'<div class="bvp-notice bvp-notice--%1$s" role="status">%2$s</div>',
				esc_attr( $map[ $msg ][0] ),
				esc_html( $map[ $msg ][1] )
			);
		}
	}

	/**
	 * Oversikt over brukerens egne piloter/prosjekter.
	 *
	 * @param string $tittel
	 */
	protected static function render_list( $tittel ) {
		$prosjekter = BVP_Repository::get_user_prosjekter( get_current_user_id() );

		echo '<h2 class="bvp-heading">' . esc_html( $tittel ) . '</h2>';

		if ( empty( $prosjekter ) ) {
			echo '<p>Du har ingen registrerte piloter eller prosjekter ennå.</p>';
		} else {
			echo '<table class="bvp-table"><thead><tr><th>ID</th><th>Tittel</th><th>Prosjekt</th><th>Prosjekteier</th><th>Synlighet</th><th>Status</th><th>Sist endret</th><th></th></tr></thead><tbody>';
			foreach ( $prosjekter as $prosjekt ) {
				printf(
					'<tr><td><code class="bvp-id">#%8$d</code></td><td>%1$s</td><td>%9$s</td><td>%2$s</td><td>%3$s</td><td><span class="bvp-status bvp-status--%4$s">%5$s</span></td><td>%6$s</td>' .
					'<td><a class="bvp-button bvp-button--link" href="%7$s" target="_blank" rel="noopener">Åpne</a></td></tr>',
					esc_html( get_the_title( $prosjekt ) ),
					BVP_Repository::foretak_html( $prosjekt ), // phpcs:ignore WordPress.Security.EscapeOutput
					BVP_Repository::synlighet_html( $prosjekt ), // phpcs:ignore WordPress.Security.EscapeOutput
					esc_attr( 'publish' === $prosjekt->post_status ? 'innsendt' : 'kladd' ),
					esc_html( BVP_Post_Type::status_label( $prosjekt ) ),
					esc_html( get_the_modified_date( 'j. F Y H:i', $prosjekt ) ),
					esc_url( self::single_url( $prosjekt ) ),
					(int) $prosjekt->ID,
					BVP_Repository::prosjekt_html( $prosjekt ) // phpcs:ignore WordPress.Security.EscapeOutput
				);
			}
			echo '</tbody></table>';
		}

		printf(
			'<p><a class="bvp-button" href="%s">Registrer nytt pilot/prosjekt</a></p>',
			esc_url( self::form_url( 0 ) )
		);

		self::render_shared_list();
	}

	/**
	 * Registreringer andre har delt med brukeren.
	 */
	protected static function render_shared_list() {
		$delt = BVP_Access::shared_with( get_current_user_id() );

		if ( empty( $delt ) ) {
			return;
		}

		echo '<h3 class="bvp-heading">Delt med meg</h3>';
		echo '<table class="bvp-table"><thead><tr><th>ID</th><th>Tittel</th><th>Prosjekt</th><th>Prosjekteier</th><th>Synlighet</th><th>Status</th><th>Sist endret</th><th></th></tr></thead><tbody>';

		foreach ( $delt as $post ) {
			$kan = BVP_Access::can_edit( $post );

			printf(
				'<tr><td><code class="bvp-id">#%6$d</code></td><td>%1$s</td><td>%10$s</td><td>%2$s</td><td>%3$s</td>' .
				'<td><span class="bvp-status bvp-status--%7$s">%8$s</span></td><td>%4$s</td>' .
				'<td><a class="bvp-button bvp-button--link" href="%5$s" target="_blank" rel="noopener">%9$s</a></td></tr>',
				esc_html( get_the_title( $post ) ),
				BVP_Repository::foretak_html( $post ), // phpcs:ignore WordPress.Security.EscapeOutput
				BVP_Repository::synlighet_html( $post ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( get_the_modified_date( 'j. F Y H:i', $post ) ),
				esc_url( self::single_url( $post ) ),
				(int) $post->ID,
				esc_attr( 'publish' === $post->post_status ? 'innsendt' : 'kladd' ),
				esc_html( BVP_Post_Type::status_label( $post ) ),
				$kan ? 'Åpne' : 'Les',
				BVP_Repository::prosjekt_html( $post ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Lesevisningen på registreringens egen side.
	 *
	 * @param int|WP_Post $post
	 */
	public static function render_single( $post ) {
		$post = get_post( $post );

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type ) {
			return;
		}

		self::render_view( $post->ID, true );
	}

	/**
	 * Språket dikteringen skal lytte etter.
	 *
	 * Nettstedet kjører med lang="en-US", så vi kan ikke gå etter den -
	 * skjemaet er norsk. Endres med filteret bvp_diktering_sprak.
	 *
	 * @return string
	 */
	public static function talesprak() {
		$sprak = (string) apply_filters( 'bvp_diktering_sprak', 'nb-NO' );

		return '' !== trim( $sprak ) ? trim( $sprak ) : 'nb-NO';
	}

	/**
	 * Adressen til registreringens egen side.
	 *
	 * Innsendte registreringer har sin egen permalenke. Kladd er ikke
	 * offentlig tilgjengelig, så de peker til skjemaet som før.
	 *
	 * @param int|WP_Post $post
	 * @param string      $base Side å falle tilbake til for kladd.
	 * @return string
	 */
	public static function single_url( $post, $base = '' ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return self::list_url( $base );
		}

		if ( 'publish' === $post->post_status ) {
			$lenke = get_permalink( $post );

			if ( $lenke ) {
				return $lenke;
			}
		}

		return self::form_url( $post->ID, $base );
	}

	/**
	 * Medredaktørene som liste: «Navn – Foretak», foretaket lenket til profilen.
	 *
	 * @param WP_Post $post
	 * @return string Tom streng når prosjektet ikke har medredaktører.
	 */
	protected static function medredaktorer_html( $post ) {
		$linjer = array();

		foreach ( BVP_Access::contacts( $post->ID ) as $id ) {
			if ( (int) $id === (int) $post->post_author ) {
				continue;
			}

			$bruker = get_userdata( $id );

			if ( ! $bruker ) {
				continue;
			}

			$navn       = '' !== trim( (string) $bruker->display_name ) ? $bruker->display_name : $bruker->user_login;
			$linje      = esc_html( $navn );
			$foretak_id = (int) BVP_Foretak::for_user( $id );
			$foretak    = $foretak_id ? (string) BVP_Foretak::name( $foretak_id ) : '';

			if ( '' !== $foretak ) {
				$type  = get_post_type( $foretak_id );
				$lenke = ( $type && ( ! function_exists( 'is_post_type_viewable' ) || is_post_type_viewable( $type ) ) ) ? (string) get_permalink( $foretak_id ) : '';

				$linje .= ' &ndash; ' . ( '' !== $lenke
					? sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $lenke ), esc_html( $foretak ) )
					: esc_html( $foretak ) );
			}

			$linjer[] = '<li>' . $linje . '</li>';
		}

		return $linjer ? '<ul class="bvp-liste">' . implode( '', $linjer ) . '</ul>' : '';
	}

	/**
	 * Lesevisning av én registrering.
	 *
	 * @param int  $post_id
	 * @param bool $egen_side Vises den på registreringens egen side?
	 */
	protected static function render_view( $post_id, $egen_side = false ) {
		$post = get_post( $post_id );

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type || ! BVP_Access::can_view( $post ) ) {
			echo '<div class="bvp-notice bvp-notice--error"><p>Denne registreringen finnes ikke, eller den er ikke delt med deg.</p>';

			if ( ! is_user_logged_in() ) {
				printf(
					'<p><a class="bvp-button" href="%s">Logg inn</a></p>',
					esc_url( wp_login_url( self::current_url() ) )
				);
			}

			echo '</div>';
			return;
		}

		if ( ! $egen_side ) {
			echo '<h2 class="bvp-heading">' . esc_html( get_the_title( $post ) ) . '</h2>';
		}

		if ( isset( $_GET['bvp_msg'] ) && 'melding_sendt' === sanitize_key( wp_unslash( $_GET['bvp_msg'] ) ) ) {
			echo '<div class="bvp-notice bvp-notice--success">Meldingen er sendt.</div>';
		}

		printf(
			'<p class="bvp-meta"><code class="bvp-id">#%4$d</code> Registrert av %1$s. Sist endret %2$s. Deling: %3$s.</p>',
			esc_html( trim( BVP_Repository::get_value( $post->ID, 'reg_fornavn' ) . ' ' . BVP_Repository::get_value( $post->ID, 'reg_etternavn' ) ) ),
			esc_html( get_the_modified_date( 'j. F Y H:i', $post ) ),
			esc_html( BVP_Access::summary( $post->ID ) ),
			(int) $post->ID
		);

		if ( BVP_Access::can_edit( $post ) ) {
			printf(
				'<p><a class="bvp-button" href="%s">Rediger registreringen</a></p>',
				esc_url( self::form_url( $post->ID, $egen_side ? BVP_Minside::skjema_url() : '' ) )
			);
		}

		// Foretaket som eier registreringen, lenket til foretaksprofilen.
		$eier_foretak = BVP_Repository::foretak_for( $post );
		$eierrad      = array();

		if ( '' !== $eier_foretak['navn'] ) {
			$eierrad[] = array(
				'label' => 'Foretak som eier prosjektet',
				'html'  => BVP_Repository::foretak_html( $post ),
			);
		}

		$medred = self::medredaktorer_html( $post );

		if ( '' !== $medred ) {
			$eierrad[] = array(
				'label' => 'Medredaktører',
				'html'  => $medred,
			);
		}

		$forste = true;

		foreach ( BVP_Fields::sections_for( 'user' ) as $section ) {
			BVP_Renderer::section_readonly( $section, $post->ID, $forste ? $eierrad : array() );
			$forste = false;
		}

		$retur_url = $egen_side ? get_permalink( $post ) : self::view_url( $post->ID );

		BVP_Meldinger::render( $post->ID, $retur_url );
		BVP_Logg::render( $post->ID, $retur_url );

		if ( is_user_logged_in() ) {
			printf(
				'<p><a class="bvp-back" href="%s">&larr; Til oversikten</a></p>',
				esc_url( $egen_side ? self::list_url( BVP_Minside::skjema_url() ) : self::list_url() )
			);
		}
	}

	/**
	 * Selve skjemaet.
	 *
	 * @param int    $post_id
	 * @param string $tittel
	 */
	protected static function render_form( $post_id, $tittel ) {
		$errors = array();
		$values = array();

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type || ! BVP_Repository::user_can_edit( $post ) ) {
				printf(
					'<div class="bvp-notice bvp-notice--error">Fant ingen registrering du har tilgang til. <a href="%s">Tilbake til oversikten</a>.</div>',
					esc_url( self::list_url() )
				);
				return;
			}
		}

		$avviste = array();
		$stashed = get_transient( self::stash_key() );

		if ( is_array( $stashed ) && (int) $stashed['post_id'] === (int) $post_id ) {
			$errors  = isset( $stashed['errors'] ) ? $stashed['errors'] : array();
			$values  = isset( $stashed['values'] ) ? $stashed['values'] : array();
			$avviste = isset( $stashed['avviste'] ) ? $stashed['avviste'] : array();
			delete_transient( self::stash_key() );
		}

		// Skjema som ble reddet unna da innloggingen gikk ut.
		$reddet = isset( $_GET[ self::GJENOPPRETT ] ) ? self::hent_reddet( wp_unslash( $_GET[ self::GJENOPPRETT ] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $reddet ) {
			$values = BVP_Repository::sanitize( $reddet['values'], 'user' );
		}

		$user = wp_get_current_user();

		// Forhåndsutfyll navn og e-post for nye registreringer.
		if ( ! $post_id && empty( $values ) ) {
			$values = array(
				'reg_fornavn'   => $user->first_name,
				'reg_etternavn' => $user->last_name,
				'reg_epost'     => $user->user_email,
			);
		}

		// Foretaksnavnet hentes fra foretaksprofilen brukeren er koblet til,
		// så lenge feltet ikke allerede er fylt ut.
		$eier       = $post_id ? (int) get_post_field( 'post_author', $post_id ) : $user->ID;
		$foretak_id = BVP_Foretak::for_user( $eier ? $eier : $user->ID );

		if ( $foretak_id ) {
			$lagret = $post_id ? BVP_Repository::get_value( $post_id, 'foretaksnavn' ) : '';
			$skrevet = isset( $values['foretaksnavn'] ) ? trim( (string) $values['foretaksnavn'] ) : '';

			if ( '' === $skrevet && '' === trim( (string) $lagret ) ) {
				$values['foretaksnavn'] = BVP_Foretak::name( $foretak_id );
			}
		}

		echo '<h2 class="bvp-heading">' . esc_html( $tittel ) . '</h2>';
		printf( '<p><a class="bvp-back" href="%s">&larr; Alle mine piloter/prosjekter</a></p>', esc_url( self::list_url() ) );

		self::render_foretak_varsel();
		self::render_avviste( $avviste );

		if ( $post_id ) {
			printf(
				'<p class="bvp-meta"><code class="bvp-id">#%3$d</code> Status: <strong>%1$s</strong>. Sist endret %2$s.</p>',
				esc_html( BVP_Post_Type::status_label( $post_id ) ),
				esc_html( get_the_modified_date( 'j. F Y H:i', $post_id ) ),
				(int) $post_id
			);
		}

		if ( $errors ) {
			echo '<div class="bvp-notice bvp-notice--error"><p>Følgende må rettes før registreringen kan sendes inn:</p><ul>';
			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul></div>';
		}

		printf(
			'<form class="bvp-form" method="post" enctype="multipart/form-data" action="%s">',
			esc_url( self::post_url() )
		);

		echo self::post_felt(); // phpcs:ignore WordPress.Security.EscapeOutput

		wp_nonce_field( self::NONCE, 'bvp_nonce' );
		printf( '<input type="hidden" name="action" value="%s">', esc_attr( self::ACTION ) );
		printf( '<input type="hidden" name="bvp_id" value="%d">', (int) $post_id );
		printf( '<input type="hidden" name="bvp_redirect" value="%s">', esc_attr( self::current_url() ) );
		echo '<input type="hidden" name="bvp_anker" id="bvp-anker" value="">';

		foreach ( BVP_Fields::sections_for( 'user' ) as $section ) {
			BVP_Renderer::section( $section, $post_id, $values, $errors );
		}

		BVP_Access::render_box( $post_id );

		echo '<p class="bvp-actions">';
		echo '<button type="submit" name="bvp_submit" value="kladd" class="bvp-button bvp-button--secondary">Lagre som kladd</button> ';
		echo '<button type="submit" name="bvp_submit" value="send" class="bvp-button">Send inn</button>';
		echo '</p>';
		echo '<p class="bvp-hint">Kladd lagrer alt du har fylt ut uten å kreve at alle felt er utfylt. Du kan oppdatere registreringen også etter innsending.</p>';

		echo '</form>';

		if ( $post_id ) {
			self::render_delete( $post_id );
			BVP_Meldinger::render( $post_id, self::form_url( $post_id ) );
			BVP_Logg::render( $post_id, self::form_url( $post_id ) );
		}
	}

	/**
	 * Valg som ikke kunne lagres, med grunnen.
	 *
	 * @param array $avviste
	 */
	protected static function render_avviste( $avviste ) {
		if ( empty( $avviste ) ) {
			return;
		}

		echo '<div class="bvp-notice bvp-notice--warning" role="status">';
		echo '<p><strong>Dette valget kunne ikke lagres:</strong></p><ul class="bvp-avviste">';

		foreach ( $avviste as $avvist ) {
			printf(
				'<li>«%1$s» i feltet %2$s - %3$s.</li>',
				esc_html( isset( $avvist['tittel'] ) ? $avvist['tittel'] : '' ),
				esc_html( isset( $avvist['felt'] ) ? $avvist['felt'] : '' ),
				esc_html( isset( $avvist['grunn'] ) ? $avvist['grunn'] : '' )
			);
		}

		echo '</ul><p class="bvp-desc">Alt annet er lagret. Prøv å velge på nytt, eller ta kontakt med BIM Verdi.</p></div>';
	}

	/**
	 * Slett registreringen. Eget skjema, med avkryssing som må hukes av,
	 * slik at ingenting forsvinner på ett feilklikk.
	 *
	 * @param int $post_id
	 */
	protected static function render_delete( $post_id ) {
		if ( ! BVP_Access::can_delete( $post_id ) ) {
			return;
		}

		$tittel = get_the_title( $post_id );
		?>
		<details class="bvp-slett">
			<summary>Slett registreringen</summary>

			<form method="post" action="<?php echo esc_url( self::post_url() ); ?>" class="bvp-slett__form">
				<?php echo self::post_felt(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php wp_nonce_field( self::SLETT_NONCE, 'bvp_slett_nonce' ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SLETT_ACTION ); ?>">
				<input type="hidden" name="bvp_id" value="<?php echo (int) $post_id; ?>">
				<input type="hidden" name="bvp_redirect" value="<?php echo esc_attr( self::current_url() ); ?>">

				<p class="bvp-desc">
					«<?php echo esc_html( $tittel ); ?>» blir borte fra oversiktene, sammen med meldingene og vedleggene.
					Den legges i papirkurven i wp-admin, så BIM Verdi kan hente den tilbake en stund etterpå.
				</p>

				<label class="bvp-option">
					<input type="checkbox" name="bvp_slett_bekreft" value="1">
					<span>Ja, jeg vil slette denne registreringen.</span>
				</label>

				<p class="bvp-actions">
					<button type="submit" class="bvp-button bvp-button--fare">Slett registreringen</button>
				</p>
			</form>
		</details>
		<?php
	}

	/**
	 * Håndter sletting fra frontend.
	 */
	public static function handle_delete() {
		if ( ! isset( $_POST['bvp_slett_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_slett_nonce'] ) ), self::SLETT_NONCE ) ) {
			wp_die( 'Sikkerhetssjekken feilet. Gå tilbake og prøv igjen.', 'Ugyldig forespørsel', array( 'response' => 403 ) );
		}

		if ( ! is_user_logged_in() ) {
			self::handle_nopriv();
		}

		$redirect = isset( $_POST['bvp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['bvp_redirect'] ) ) : home_url();
		$redirect = wp_validate_redirect( $redirect, home_url() );
		$post_id  = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;

		if ( ! $post_id || ! BVP_Access::can_delete( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'bvp_msg', 'kan_ikke_slette', self::list_url( $redirect ) ) );
			exit;
		}

		if ( empty( $_POST['bvp_slett_bekreft'] ) ) {
			wp_safe_redirect( add_query_arg( 'bvp_msg', 'ikke_bekreftet', self::form_url( $post_id, $redirect ) ) );
			exit;
		}

		// Varsle BIM Verdi før innholdet forsvinner fra oversiktene.
		BVP_Mail::notify_deleted( $post_id, wp_get_current_user() );

		$slettet = BVP_Repository::trash( $post_id, get_current_user_id() );

		if ( is_wp_error( $slettet ) ) {
			wp_safe_redirect( add_query_arg( 'bvp_msg', 'kan_ikke_slette', self::form_url( $post_id, $redirect ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'bvp_msg', 'slettet', self::list_url( $redirect ) ) );
		exit;
	}

	/**
	 * Håndter innsending fra frontend.
	 */
	public static function handle() {
		$nonce_ok = isset( $_POST['bvp_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_nonce'] ) ), self::NONCE );

		// Gikk innloggingen ut mens skjemaet ble fylt ut, eller er nøkkelen
		// for gammel, tar vi vare på det som er skrevet i stedet for å kaste det.
		if ( ! $nonce_ok || ! is_user_logged_in() ) {
			self::redd_innsending();
		}

		$redirect = isset( $_POST['bvp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['bvp_redirect'] ) ) : home_url();
		$redirect = wp_validate_redirect( $redirect, home_url() );
		$post_id  = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;
		$is_submit = ( isset( $_POST['bvp_submit'] ) && 'send' === $_POST['bvp_submit'] );

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type || ! BVP_Repository::user_can_edit( $post ) ) {
				// Tilgangen er borte (slettet, eller eierskapet er flyttet).
				// Det som er skrevet skal ikke gå tapt: vi åpner et nytt skjema
				// med innholdet fylt inn, så det kan lagres som egen kladd.
				$raw_tapt = isset( $_POST['bvp'] ) && is_array( $_POST['bvp'] ) ? wp_unslash( $_POST['bvp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$url      = add_query_arg( 'bvp_msg', 'mistet_tilgang', self::form_url( 0, $redirect ) );

				if ( $raw_tapt ) {
					$token = wp_generate_password( 24, false );

					set_transient(
						'bvp_innsending_' . $token,
						array(
							'post_id' => 0,
							'values'  => $raw_tapt,
							'tid'     => time(),
						),
						2 * HOUR_IN_SECONDS
					);

					$url = add_query_arg( self::GJENOPPRETT, $token, $url );
				}

				wp_safe_redirect( $url );
				exit;
			}
		}

		$raw    = isset( $_POST['bvp'] ) && is_array( $_POST['bvp'] ) ? wp_unslash( $_POST['bvp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$values = BVP_Repository::sanitize( $raw, 'user' );
		$errors = BVP_Repository::validate( $values, $is_submit, 'user' );

		$was_new    = ! $post_id;
		$do_publish = $is_submit && empty( $errors );

		$saved = BVP_Repository::save( $post_id, $values, $do_publish, get_current_user_id(), 'user' );

		if ( is_wp_error( $saved ) ) {
			wp_safe_redirect( add_query_arg( 'bvp_msg', 'feil', self::form_url( $post_id, $redirect ) ) );
			exit;
		}

		$post_id = (int) $saved;

		// Fjern vedlegg som er merket for sletting.
		if ( isset( $_POST['bvp_remove'] ) && is_array( $_POST['bvp_remove'] ) ) {
			foreach ( wp_unslash( $_POST['bvp_remove'] ) as $key => $ids ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				foreach ( (array) $ids as $attachment_id ) {
					BVP_Repository::remove_attachment( $post_id, sanitize_key( $key ), absint( $attachment_id ) );
				}
			}
		}

		if ( isset( $_POST['bvp_access'] ) && is_array( $_POST['bvp_access'] ) ) {
			BVP_Access::save( $post_id, wp_unslash( $_POST['bvp_access'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		$upload_errors = BVP_Repository::handle_uploads( $_FILES, $post_id, 'user' );
		$errors        = array_merge( $errors, $upload_errors );
		$avviste       = BVP_Repository::avviste();

		// Valg som ikke kunne lagres skal sies fra om, men ikke stoppe innsendingen.
		if ( $avviste && ! $errors ) {
			set_transient(
				self::stash_key(),
				array(
					'post_id' => $post_id,
					'errors'  => array(),
					'values'  => array(),
					'avviste' => $avviste,
				),
				HOUR_IN_SECONDS
			);
		}

		if ( $errors ) {
			set_transient(
				self::stash_key(),
				array(
					'post_id' => $post_id,
					'errors'  => $errors,
					'values'  => $values,
					'avviste' => $avviste,
				),
				HOUR_IN_SECONDS
			);
			if ( $was_new ) {
				BVP_Mail::notify_ny_kladd( $post_id );
			}

			wp_safe_redirect( add_query_arg( 'bvp_msg', 'mangler', self::form_url( $post_id, $redirect ) ) );
			exit;
		}

		if ( $do_publish ) {
			BVP_Mail::notify_submission( $post_id, $was_new );
			$msg = 'sendt';
		} else {
			$msg = $was_new ? 'kladd' : 'oppdatert';

			if ( $was_new ) {
				BVP_Mail::notify_ny_kladd( $post_id );
			}
		}

		$url = add_query_arg( 'bvp_msg', $msg, self::form_url( $post_id, $redirect ) );

		// Lagret man fra en seksjon, kommer man tilbake til den samme seksjonen.
		$anker = isset( $_POST['bvp_anker'] ) ? sanitize_html_class( wp_unslash( $_POST['bvp_anker'] ) ) : '';

		if ( '' !== $anker && ! $do_publish ) {
			$url .= '#' . $anker;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Ta vare på et skjema som ikke kunne lagres, og send brukeren videre
	 * til innlogging (eller tilbake til skjemaet) med innholdet i behold.
	 *
	 * Ingenting lagres på registreringen her - det krever fortsatt en
	 * gyldig sikkerhetsnøkkel. Dette er bare en mellomlagring av det
	 * brukerens egen nettleser sendte inn.
	 */
	protected static function redd_innsending() {
		$redirect = isset( $_POST['bvp_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['bvp_redirect'] ) ) : home_url();
		$redirect = wp_validate_redirect( $redirect, home_url() );
		$post_id  = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;

		$data = array(
			'post_id' => $post_id,
			'values'  => isset( $_POST['bvp'] ) && is_array( $_POST['bvp'] ) ? wp_unslash( $_POST['bvp'] ) : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'tid'     => time(),
		);

		$url = self::form_url( $post_id, $redirect );
		$url = add_query_arg( 'bvp_msg', 'utlopt', $url );

		if ( $data['values'] ) {
			$token = wp_generate_password( 24, false );

			set_transient( 'bvp_innsending_' . $token, $data, 2 * HOUR_IN_SECONDS );

			$url = add_query_arg( self::GJENOPPRETT, $token, $url );
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( $url ) );
			exit;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Hent fram igjen et skjema som ble reddet unna.
	 *
	 * @param string $token
	 * @return array|null post_id og values
	 */
	protected static function hent_reddet( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );

		if ( '' === $token ) {
			return null;
		}

		$data = get_transient( 'bvp_innsending_' . $token );

		delete_transient( 'bvp_innsending_' . $token );

		return is_array( $data ) && ! empty( $data['values'] ) ? $data : null;
	}

	/**
	 * Ikke innlogget.
	 */
	public static function handle_nopriv() {
		wp_safe_redirect( wp_login_url( home_url() ) );
		exit;
	}

	/**
	 * Nøkkel for mellomlagring av feil/verdier per bruker.
	 *
	 * @return string
	 */
	protected static function stash_key() {
		return 'bvp_stash_' . get_current_user_id();
	}

	/**
	 * URL til skjemavisning.
	 *
	 * @param int    $post_id
	 * @param string $base
	 * @return string
	 */
	public static function form_url( $post_id = 0, $base = '' ) {
		$base = $base ? $base : self::current_url();
		$base = remove_query_arg( array( 'bvp_view', 'bvp_id', 'bvp_msg' ), $base );
		$args = array( 'bvp_view' => 'skjema' );

		if ( $post_id ) {
			$args['bvp_id'] = (int) $post_id;
		}

		return add_query_arg( $args, $base );
	}

	/**
	 * URL til lesevisning.
	 *
	 * @param int    $post_id
	 * @param string $base
	 * @return string
	 */
	public static function view_url( $post_id, $base = '' ) {
		$base = $base ? $base : self::current_url();
		$base = remove_query_arg( array( 'bvp_view', 'bvp_id', 'bvp_msg' ), $base );

		return add_query_arg(
			array(
				'bvp_view' => 'vis',
				'bvp_id'   => (int) $post_id,
			),
			$base
		);
	}

	/**
	 * URL til oversikten.
	 *
	 * @param string $base
	 * @return string
	 */
	public static function list_url( $base = '' ) {
		$base = $base ? $base : self::current_url();
		return remove_query_arg( array( 'bvp_view', 'bvp_id', 'bvp_msg' ), $base );
	}

	/**
	 * Nåværende URL.
	 *
	 * @return string
	 */
	public static function current_url() {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return home_url( $path );
	}
}
