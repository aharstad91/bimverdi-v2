<?php
/**
 * Endringslogg for hver registrering.
 *
 * Hver lagring sammenlignes med det som sto fra før, og endringene
 * skrives til en logg: hvem, når og hva. Den som kan redigere, kan
 * tilbakestille en enkelt endring.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Logg {

	const META   = '_bvp_logg';
	const ACTION = 'bvp_angre';
	const NONCE  = 'bvp_angre_endring';

	/** Hvor mange hendelser vi tar vare på per registrering. */
	const MAKS = 300;

	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
	}

	/**
	 * Hele loggen, eldste først.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public static function les( $post_id ) {
		$logg = get_post_meta( (int) $post_id, self::META, true );

		return is_array( $logg ) ? array_values( $logg ) : array();
	}

	/**
	 * Skriv en hendelse til loggen.
	 *
	 * @param int    $post_id
	 * @param array  $endringer nøkkel => array( 'fra' => ..., 'til' => ... )
	 * @param int    $user_id
	 * @param string $type      endring | status | eier | angre
	 * @param string $notat
	 * @return int Indeksen hendelsen fikk.
	 */
	public static function registrer( $post_id, $endringer, $user_id = 0, $type = 'endring', $notat = '' ) {
		$post_id = (int) $post_id;

		if ( ! $post_id || ( empty( $endringer ) && '' === $notat ) ) {
			return -1;
		}

		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		$bruker  = get_userdata( $user_id );
		$logg    = self::les( $post_id );

		$logg[] = array(
			'tid'       => current_time( 'mysql' ),
			'bruker'    => $user_id,
			'navn'      => $bruker ? $bruker->display_name : 'Ukjent',
			'type'      => sanitize_key( $type ),
			'notat'     => sanitize_text_field( $notat ),
			'endringer' => (array) $endringer,
		);

		if ( count( $logg ) > self::MAKS ) {
			$logg = array_slice( $logg, count( $logg ) - self::MAKS );
		}

		update_post_meta( $post_id, self::META, $logg );

		/**
		 * En hendelse er skrevet til endringsloggen.
		 *
		 * @param int   $post_id
		 * @param array $hendelse
		 */
		do_action( 'bvp_logg_hendelse', $post_id, end( $logg ) );

		return count( $logg ) - 1;
	}

	/**
	 * Sammenlign gamle og nye verdier og noter forskjellene.
	 *
	 * @param array $for_   nøkkel => verdi før
	 * @param array $etter  nøkkel => verdi etter
	 * @return array
	 */
	public static function diff( $for_, $etter ) {
		$endringer = array();

		foreach ( $etter as $key => $ny ) {
			$gammel = array_key_exists( $key, $for_ ) ? $for_[ $key ] : '';

			if ( self::lik( $gammel, $ny ) ) {
				continue;
			}

			$endringer[ $key ] = array(
				'fra' => $gammel,
				'til' => $ny,
			);
		}

		return $endringer;
	}

	/** Er to verdier like nok til at det ikke er en endring? */
	protected static function lik( $a, $b ) {
		if ( is_array( $a ) || is_array( $b ) ) {
			$a = array_map( 'strval', (array) $a );
			$b = array_map( 'strval', (array) $b );

			sort( $a );
			sort( $b );

			return $a === $b;
		}

		return trim( (string) $a ) === trim( (string) $b );
	}

	/**
	 * Tilbakestill én hendelse: feltene settes tilbake til verdiene de hadde.
	 *
	 * @param int $post_id
	 * @param int $indeks
	 * @param int $user_id
	 * @return true|WP_Error
	 */
	public static function angre( $post_id, $indeks, $user_id = 0 ) {
		$post_id = (int) $post_id;
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! BVP_Access::can_edit( $post_id, $user_id ) ) {
			return new WP_Error( 'bvp_ingen_tilgang', 'Du kan ikke endre denne registreringen.' );
		}

		$logg = self::les( $post_id );

		if ( ! isset( $logg[ $indeks ] ) || empty( $logg[ $indeks ]['endringer'] ) ) {
			return new WP_Error( 'bvp_ukjent_hendelse', 'Fant ikke endringen.' );
		}

		$felt      = BVP_Fields::all( 'all', true );
		$tilbake   = array();
		$etikett   = array();

		foreach ( $logg[ $indeks ]['endringer'] as $key => $endring ) {
			if ( ! isset( $felt[ $key ] ) ) {
				continue;
			}

			$tilbake[ $key ] = isset( $endring['fra'] ) ? $endring['fra'] : '';
			$etikett[]       = $felt[ $key ]['label'];
		}

		if ( empty( $tilbake ) ) {
			return new WP_Error( 'bvp_ingenting', 'Ingen felt å tilbakestille.' );
		}

		$na = array();

		foreach ( $tilbake as $key => $verdi ) {
			$na[ $key ] = BVP_Repository::get_value( $post_id, $key );
			update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), $verdi );
		}

		self::registrer(
			$post_id,
			self::diff( $na, $tilbake ),
			$user_id,
			'angre',
			sprintf( 'Tilbakestilte endringen fra %s', mysql2date( 'j. F Y H:i', $logg[ $indeks ]['tid'] ) )
		);

		// Tittelen følger tittelfeltet.
		BVP_Repository::refresh_titles( false );

		return true;
	}

	/**
	 * Vis loggen.
	 *
	 * @param int    $post_id
	 * @param string $retur
	 */
	public static function render( $post_id, $retur = '' ) {
		$post_id = (int) $post_id;

		if ( ! $post_id || ! BVP_Access::can_view( $post_id ) ) {
			return;
		}

		$logg = array_reverse( self::les( $post_id ), true );
		$felt = BVP_Fields::all( 'all', true );
		$kan  = BVP_Access::can_edit( $post_id );
		$retur = $retur ? $retur : BVP_Form::current_url();
		?>
		<section class="bvp-section bvp-logg" id="bvp-logg">
			<h3 class="bvp-section__title">Endringslogg<?php echo $logg ? ' (' . count( $logg ) . ')' : ''; ?></h3>

			<?php if ( empty( $logg ) ) : ?>
				<p class="bvp-desc">Ingen endringer er loggført ennå.</p>
			<?php else : ?>
				<ol class="bvp-logg__liste">
					<?php foreach ( $logg as $indeks => $hendelse ) : ?>
						<li class="bvp-logg__rad">
							<p class="bvp-logg__hode">
								<strong><?php echo esc_html( $hendelse['navn'] ); ?></strong>
								<span class="bvp-desc"><?php echo esc_html( mysql2date( 'j. F Y H:i', $hendelse['tid'] ) ); ?></span>
								<?php if ( 'angre' === $hendelse['type'] ) : ?>
									<span class="bvp-logg__merke">tilbakestilling</span>
								<?php endif; ?>
							</p>

							<?php if ( ! empty( $hendelse['notat'] ) ) : ?>
								<p class="bvp-desc"><?php echo esc_html( $hendelse['notat'] ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $hendelse['endringer'] ) ) : ?>
								<details class="bvp-logg__detaljer">
									<summary><?php echo esc_html( self::sammendrag( $hendelse['endringer'], $felt ) ); ?></summary>
									<dl class="bvp-readonly__list">
										<?php foreach ( $hendelse['endringer'] as $key => $endring ) : ?>
											<dt><?php echo esc_html( isset( $felt[ $key ] ) ? $felt[ $key ]['label'] : $key ); ?></dt>
											<dd>
												<span class="bvp-logg__for"><?php echo esc_html( self::tekst( $endring['fra'] ) ); ?></span>
												<span aria-hidden="true">&rarr;</span>
												<span class="bvp-logg__etter"><?php echo esc_html( self::tekst( $endring['til'] ) ); ?></span>
											</dd>
										<?php endforeach; ?>
									</dl>

									<?php if ( $kan ) : ?>
										<form method="post" action="<?php echo esc_url( BVP_Form::post_url() ); ?>" class="bvp-logg__skjema">
											<?php echo BVP_Form::post_felt(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php wp_nonce_field( self::NONCE, 'bvp_angre_nonce' ); ?>
											<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
											<input type="hidden" name="bvp_id" value="<?php echo (int) $post_id; ?>">
											<input type="hidden" name="bvp_indeks" value="<?php echo (int) $indeks; ?>">
											<input type="hidden" name="bvp_retur" value="<?php echo esc_attr( $retur ); ?>">
											<button type="submit" class="bvp-button bvp-button--secondary">Tilbakestill denne endringen</button>
										</form>
									<?php endif; ?>
								</details>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Kort sammendrag av hva som ble endret.
	 *
	 * @param array $endringer
	 * @param array $felt
	 * @return string
	 */
	public static function sammendrag( $endringer, $felt = array() ) {
		$felt   = $felt ? $felt : BVP_Fields::all( 'all', true );
		$navn   = array();

		foreach ( array_keys( $endringer ) as $key ) {
			$navn[] = isset( $felt[ $key ] ) ? $felt[ $key ]['label'] : $key;
		}

		$antall = count( $navn );

		if ( $antall > 3 ) {
			$navn = array_slice( $navn, 0, 3 );

			return sprintf( 'Endret %s og %d felt til', implode( ', ', $navn ), $antall - 3 );
		}

		return sprintf( 'Endret %s', implode( ', ', $navn ) );
	}

	/**
	 * Verdi som kort tekst.
	 *
	 * @param mixed $verdi
	 * @return string
	 */
	public static function tekst( $verdi ) {
		if ( is_array( $verdi ) ) {
			$verdi = implode( ', ', array_filter( array_map( 'strval', array_filter( $verdi, 'is_scalar' ) ) ) );
		}

		$verdi = trim( (string) $verdi );

		if ( '' === $verdi ) {
			return '(tomt)';
		}

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $verdi ) > 160 ) {
			$verdi = rtrim( mb_substr( $verdi, 0, 157 ) ) . '...';
		}

		return $verdi;
	}

	/** Ta imot en tilbakestilling. */
	public static function handle() {
		if ( ! isset( $_POST['bvp_angre_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_angre_nonce'] ) ), self::NONCE ) ) {
			wp_die( 'Sikkerhetssjekken feilet. Gå tilbake og prøv igjen.', 'Ugyldig forespørsel', array( 'response' => 403 ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url() ) );
			exit;
		}

		$post_id = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;
		$indeks  = isset( $_POST['bvp_indeks'] ) ? absint( $_POST['bvp_indeks'] ) : 0;
		$retur   = isset( $_POST['bvp_retur'] ) ? esc_url_raw( wp_unslash( $_POST['bvp_retur'] ) ) : home_url();
		$retur   = wp_validate_redirect( $retur, home_url() );

		$svar = self::angre( $post_id, $indeks, get_current_user_id() );

		$msg = is_wp_error( $svar ) ? 'angre_feil' : 'angret';

		wp_safe_redirect( add_query_arg( 'bvp_msg', $msg, $retur ) . '#bvp-logg' );
		exit;
	}
}
