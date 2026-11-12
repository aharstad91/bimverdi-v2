<?php
/**
 * Frontend: chat-widget via shortcode [bumblebee] + valgfri flytende popup-boble.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Shortcode
 */
class BBEE_Shortcode {

	/** @var bool */
	protected $enqueued = false;

	/**
	 * Koble opp hooks.
	 */
	public function register_hooks() {
		add_shortcode( 'bumblebee', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_popup' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_popup' ) );
	}

	/**
	 * Registrer assets.
	 */
	public function register_assets() {
		wp_register_style( 'bbee-frontend', BBEE_PLUGIN_URL . 'assets/css/bumblebee.css', array(), BBEE_VERSION );
		wp_register_script( 'bbee-frontend', BBEE_PLUGIN_URL . 'assets/js/bumblebee.js', array(), BBEE_VERSION, true );
	}

	/**
	 * Last inn og lokaliser kjøretidskonfig (én gang per sidelast).
	 */
	protected function ensure_runtime() {
		wp_enqueue_style( 'bbee-frontend' );
		wp_enqueue_script( 'bbee-frontend' );

		if ( $this->enqueued ) {
			return;
		}
		$settings = BBEE_Plugin::get_settings();
		wp_localize_script(
			'bbee-frontend',
			'BBEE_CONFIG',
			array(
				'restUrl'       => esc_url_raw( rest_url( 'bbee/v1/chat' ) ),
				'feedbackUrl'   => esc_url_raw( rest_url( 'bbee/v1/feedback' ) ),
				'feedbackLabel' => __( 'Var svaret nyttig?', 'bumblebee' ),
				'feedbackUp'    => __( 'Nyttig', 'bumblebee' ),
				'feedbackDown'  => __( 'Ikke nyttig', 'bumblebee' ),
				'feedbackThanks' => __( 'Takk for tilbakemeldingen!', 'bumblebee' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'welcome'       => $settings['welcome_message'],
				'assistantName' => $settings['assistant_name'],
				'sourcesLabel'  => __( 'Kilder', 'bumblebee' ),
				'errorMessage'  => __( 'Beklager, noe gikk galt. Prøv igjen om litt.', 'bumblebee' ),
				'thinkingLabel' => __( 'Leter i kunnskapskildene …', 'bumblebee' ),
				'sendLabel'     => __( 'Send', 'bumblebee' ),
			)
		);
		$this->enqueued = true;
	}

	/**
	 * Skal popup-boblen vises for denne besøkende?
	 *
	 * @return bool
	 */
	protected function should_show_popup() {
		$settings = BBEE_Plugin::get_settings();
		if ( empty( $settings['popup_enabled'] ) ) {
			return false;
		}
		// Chatten krever innlogging; vis boble til innloggede, eller til alle hvis valgt
		// (utloggede får da en «logg inn»-boks når de åpner den).
		if ( is_user_logged_in() || ! empty( $settings['popup_show_logged_out'] ) ) {
			/**
			 * Filtrer om popup-boblen skal vises på gjeldende side.
			 *
			 * @param bool $show
			 */
			return (bool) apply_filters( 'bbee_show_popup', true );
		}
		return false;
	}

	/**
	 * Last inn assets for popup når den skal vises.
	 */
	public function maybe_enqueue_popup() {
		if ( $this->should_show_popup() ) {
			$this->ensure_runtime();
		}
	}

	/**
	 * Bygg selve chat-widgeten (delt mellom shortcode og popup).
	 *
	 * @param array $atts     Attributter.
	 * @param bool  $in_popup Om den ligger i popup (viser lukkeknapp).
	 * @return string
	 */
	protected function widget_html( $atts, $in_popup = false ) {
		if ( ! is_user_logged_in() ) {
			return $this->login_notice_html( $atts, $in_popup );
		}

		$height = (int) $atts['height'];
		$uid    = 'bbee-' . wp_generate_password( 6, false, false );

		ob_start();
		?>
		<div class="bbee-widget" id="<?php echo esc_attr( $uid ); ?>"
			data-placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
			style="--bbee-height: <?php echo esc_attr( $height ); ?>px;">
			<div class="bbee-header">
				<img class="bbee-logo" src="<?php echo esc_url( BBEE_PLUGIN_URL . 'assets/images/busybim-logo.png' ); ?>" alt="" aria-hidden="true" />
				<span class="bbee-title"><?php echo esc_html( $atts['title'] ); ?></span>
				<span class="bbee-version" title="<?php esc_attr_e( 'Pilotversjon', 'bumblebee' ); ?>">v<?php echo esc_html( BBEE_VERSION ); ?></span>
				<?php if ( $in_popup ) : ?>
					<button type="button" class="bbee-close" aria-label="<?php esc_attr_e( 'Lukk chat', 'bumblebee' ); ?>">×</button>
				<?php endif; ?>
			</div>
			<div class="bbee-log" role="log" aria-live="polite"></div>
			<form class="bbee-form" autocomplete="off">
				<textarea class="bbee-input" rows="1" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" aria-label="<?php echo esc_attr( $atts['placeholder'] ); ?>"></textarea>
				<button type="submit" class="bbee-send"><?php esc_html_e( 'Send', 'bumblebee' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * «Logg inn»-boks.
	 *
	 * @param array $atts     Attributter.
	 * @param bool  $in_popup I popup.
	 * @return string
	 */
	protected function login_notice_html( $atts, $in_popup = false ) {
		$login_url = wp_login_url( get_permalink() );
		ob_start();
		?>
		<div class="bbee-widget bbee-widget-locked" style="--bbee-height: <?php echo esc_attr( (int) $atts['height'] ); ?>px;">
			<div class="bbee-header">
				<img class="bbee-logo" src="<?php echo esc_url( BBEE_PLUGIN_URL . 'assets/images/busybim-logo.png' ); ?>" alt="" aria-hidden="true" />
				<span class="bbee-title"><?php echo esc_html( $atts['title'] ); ?></span>
				<span class="bbee-version" title="<?php esc_attr_e( 'Pilotversjon', 'bumblebee' ); ?>">v<?php echo esc_html( BBEE_VERSION ); ?></span>
				<?php if ( $in_popup ) : ?>
					<button type="button" class="bbee-close" aria-label="<?php esc_attr_e( 'Lukk chat', 'bumblebee' ); ?>">×</button>
				<?php endif; ?>
			</div>
			<div class="bbee-locked-body">
				<div class="bbee-lock-icon" aria-hidden="true">🔒</div>
				<p class="bbee-locked-text"><?php esc_html_e( 'Bumblebee er kun tilgjengelig for registrerte, innloggede brukere.', 'bumblebee' ); ?></p>
				<a class="bbee-login-btn" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Logg inn', 'bumblebee' ); ?></a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode [bumblebee] (innebygd widget).
	 *
	 * @param array $atts Attributter.
	 * @return string
	 */
	public function render( $atts ) {
		$settings = BBEE_Plugin::get_settings();
		$atts     = shortcode_atts(
			array( 'height' => '560', 'placeholder' => __( 'Skriv spørsmålet ditt her …', 'bumblebee' ), 'title' => $settings['assistant_name'] ),
			$atts,
			'bumblebee'
		);
		wp_enqueue_style( 'bbee-frontend' );
		if ( is_user_logged_in() ) {
			$this->ensure_runtime();
		}
		return $this->widget_html( $atts, false );
	}

	/**
	 * Flytende popup-boble i wp_footer.
	 */
	public function render_popup() {
		if ( ! $this->should_show_popup() ) {
			return;
		}
		$settings = BBEE_Plugin::get_settings();
		$valid    = array( 'top-right', 'top-left', 'bottom-right', 'bottom-left' );
		$position = in_array( ( $settings['popup_position'] ?? 'top-right' ), $valid, true ) ? $settings['popup_position'] : 'top-right';
		$atts     = array(
			'height'      => '560',
			'placeholder' => __( 'Skriv spørsmålet ditt her …', 'bumblebee' ),
			'title'       => $settings['assistant_name'],
		);
		?>
		<div class="bbee-popup" data-position="<?php echo esc_attr( $position ); ?>">
			<button type="button" class="bbee-launcher" aria-expanded="false" aria-label="<?php echo esc_attr( sprintf( __( 'Åpne %s – spør meg', 'bumblebee' ), $settings['assistant_name'] ) ); ?>">
				<svg class="bbee-launcher-ring" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
					<defs>
						<path id="bbee-ring-path" d="M 50,50 m -39,0 a 39,39 0 1,1 78,0 a 39,39 0 1,1 -78,0" />
					</defs>
					<text text-anchor="middle"><textPath href="#bbee-ring-path" xlink:href="#bbee-ring-path" startOffset="25%"><?php esc_html_e( 'Spør Bumblebee', 'bumblebee' ); ?></textPath></text>
				</svg>
				<img class="bbee-launcher-icon" src="<?php echo esc_url( BBEE_PLUGIN_URL . 'assets/images/busybim-logo.png' ); ?>" alt="" aria-hidden="true" />
			</button>
			<div class="bbee-popup-panel" hidden>
				<?php echo $this->widget_html( $atts, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	}
}
