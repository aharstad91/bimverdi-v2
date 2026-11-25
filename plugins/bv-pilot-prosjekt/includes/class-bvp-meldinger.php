<?php
/**
 * Meldinger og dialog på hver registrering.
 *
 * Meldingene lagres som WordPress-kommentarer av typen bvp_melding,
 * knyttet til registreringen. Alle som har lesetilgang kan lese og skrive.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Meldinger {

	const TYPE   = 'bvp_melding';
	const ACTION = 'bvp_ny_melding';
	const NONCE  = 'bvp_melding';

	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_nopriv' ) );
		add_filter( 'comments_clauses', array( __CLASS__, 'skjul_i_vanlige_lister' ), 10, 2 );
		add_action( 'wp_ajax_bvp_melding_personer', array( __CLASS__, 'ajax_personer' ) );
	}

	/**
	 * Hold meldingene utenfor vanlige kommentarlister (tema og kommentartelling),
	 * men la dem være synlige i wp-admin og når vi henter dem selv.
	 *
	 * @param array      $clauses
	 * @param WP_Comment_Query $query
	 * @return array
	 */
	public static function skjul_i_vanlige_lister( $clauses, $query ) {
		$type = isset( $query->query_vars['type'] ) ? $query->query_vars['type'] : '';

		if ( self::TYPE === $type || is_admin() ) {
			return $clauses;
		}

		global $wpdb;

		$clauses['where'] .= $wpdb->prepare( " AND {$wpdb->comments}.comment_type != %s", self::TYPE );

		return $clauses;
	}

	/**
	 * Meldingene på en registrering, eldste først.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public static function les( $post_id ) {
		$meldinger = get_comments(
			array(
				'post_id' => (int) $post_id,
				'type'    => self::TYPE,
				'status'  => 'approve',
				'orderby' => 'comment_date_gmt',
				'order'   => 'ASC',
				'number'  => 200,
			)
		);

		return is_array( $meldinger ) ? $meldinger : array();
	}

	/**
	 * Antall meldinger.
	 *
	 * @param int $post_id
	 * @return int
	 */
	public static function antall( $post_id ) {
		return count( self::les( $post_id ) );
	}

	/**
	 * Hvem som har tilgang til registreringen, og kan nevnes med @.
	 *
	 * @param int $post_id
	 * @return array [ id, navn, foretak ]
	 */
	public static function personer( $post_id ) {
		$post_id = (int) $post_id;
		$ider    = array( (int) get_post_field( 'post_author', $post_id ) );
		$ider    = array_merge( $ider, BVP_Access::contacts( $post_id ), BVP_Access::allowed_users( $post_id ) );

		// Foretak som er gitt tilgang - kontaktpersonene der kan nevnes.
		foreach ( BVP_Access::allowed_foretak( $post_id ) as $foretak_id ) {
			if ( ! is_numeric( $foretak_id ) ) {
				continue;
			}

			foreach ( BVP_Foretak::contacts( (int) $foretak_id ) as $bruker ) {
				$ider[] = (int) $bruker->ID;
			}
		}

		$ider = array_values( array_unique( array_filter( array_map( 'intval', $ider ) ) ) );
		$ut   = array();

		foreach ( $ider as $id ) {
			$bruker = get_userdata( $id );

			if ( ! $bruker ) {
				continue;
			}

			$ut[] = array(
				'id'      => $id,
				'navn'    => $bruker->display_name,
				'foretak' => BVP_Foretak::name( BVP_Foretak::for_user( $id ) ),
			);
		}

		usort(
			$ut,
			static function ( $a, $b ) {
				return strcasecmp( $a['navn'], $b['navn'] );
			}
		);

		return apply_filters( 'bvp_melding_personer', $ut, $post_id );
	}

	/** AJAX: hvem kan nevnes på denne registreringen. */
	public static function ajax_personer() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'melding' => 'Du må være innlogget.' ), 403 );
		}

		check_ajax_referer( self::NONCE, 'nonce' );

		$post_id = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;

		if ( ! $post_id || ! BVP_Access::can_view( $post_id ) ) {
			wp_send_json_error( array( 'melding' => 'Ingen tilgang.' ), 403 );
		}

		wp_send_json_success( array( 'personer' => self::personer( $post_id ) ) );
	}

	/**
	 * Meldingsteksten med @navn uthevet.
	 *
	 * @param string $tekst
	 * @param array  $navn Navnene som kan nevnes.
	 * @return string
	 */
	public static function uthev( $tekst, $navn = array() ) {
		$html = nl2br( esc_html( $tekst ) );

		foreach ( $navn as $ett ) {
			$ett = trim( (string) $ett );

			if ( '' === $ett ) {
				continue;
			}

			$html = str_replace(
				'@' . esc_html( $ett ),
				'<span class="bvp-nevnt">@' . esc_html( $ett ) . '</span>',
				$html
			);
		}

		return $html;
	}

	/**
	 * Meldingsfeltet med dialogen.
	 *
	 * @param int    $post_id
	 * @param string $retur URL å komme tilbake til etter sending.
	 */
	public static function render( $post_id, $retur = '' ) {
		$post_id = (int) $post_id;

		if ( ! $post_id || ! BVP_Access::can_view( $post_id ) ) {
			return;
		}

		$meldinger = self::les( $post_id );
		$retur     = $retur ? $retur : BVP_Form::current_url();
		$personer  = self::personer( $post_id );
		$navn      = wp_list_pluck( $personer, 'navn' );
		?>
		<section class="bvp-section bvp-meldinger" id="bvp-meldinger">
			<h3 class="bvp-section__title">Meldinger<?php echo $meldinger ? ' (' . count( $meldinger ) . ')' : ''; ?></h3>
			<p class="bvp-section__intro">
				Dialog om denne registreringen. Alle som har tilgang til den, kan lese og skrive her.
			</p>

			<?php if ( empty( $meldinger ) ) : ?>
				<p class="bvp-desc">Ingen meldinger ennå.</p>
			<?php else : ?>
				<ol class="bvp-meldinger__liste">
					<?php foreach ( $meldinger as $melding ) : ?>
						<li class="bvp-melding">
							<p class="bvp-melding__hode">
								<strong><?php echo esc_html( $melding->comment_author ); ?></strong>
								<span class="bvp-desc"><?php echo esc_html( mysql2date( 'j. F Y H:i', $melding->comment_date ) ); ?></span>
							</p>
							<div class="bvp-melding__tekst"><?php echo self::uthev( $melding->comment_content, $navn ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( is_user_logged_in() ) : ?>
				<form class="bvp-meldinger__skjema" method="post" action="<?php echo esc_url( BVP_Form::post_url() ); ?>">
					<?php echo BVP_Form::post_felt(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php wp_nonce_field( self::NONCE, 'bvp_melding_nonce' ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<input type="hidden" name="bvp_id" value="<?php echo (int) $post_id; ?>">
					<input type="hidden" name="bvp_retur" value="<?php echo esc_attr( $retur ); ?>">

					<label class="bvp-label" for="bvp-melding-tekst">Ny melding</label>
					<p class="bvp-desc" id="bvp-melding-hjelp">Skriv <code>@</code> for å nevne noen som har tilgang. De får e-post om meldingen.</p>

					<div class="bvp-nevn" data-bvp-id="<?php echo (int) $post_id; ?>"
						data-bvp-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
						data-bvp-nonce="<?php echo esc_attr( wp_create_nonce( self::NONCE ) ); ?>">
						<textarea id="bvp-melding-tekst" name="bvp_melding" rows="4" maxlength="4000" required
							aria-describedby="bvp-melding-hjelp" autocomplete="off"
							placeholder="Skriv en melding til de andre som har tilgang ..."></textarea>
						<ul class="bvp-combo__list bvp-nevn__liste" role="listbox" hidden></ul>
						<p class="bvp-combo__status bvp-nevn__status" role="status" aria-live="polite"></p>
						<div class="bvp-nevn__valgte"></div>
					</div>

					<?php if ( $personer ) : ?>
						<p class="bvp-desc">
							Har tilgang:
							<?php echo esc_html( implode( ', ', array_slice( $navn, 0, 12 ) ) ); ?><?php echo count( $navn ) > 12 ? ' m.fl.' : ''; ?>
						</p>
					<?php endif; ?>

					<p class="bvp-actions">
						<button type="submit" class="bvp-button">Send melding</button>
					</p>
				</form>
			<?php else : ?>
				<p class="bvp-desc">
					<a href="<?php echo esc_url( wp_login_url( $retur ) ); ?>">Logg inn</a> for å skrive en melding.
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Ta imot en ny melding.
	 */
	public static function handle() {
		if ( ! isset( $_POST['bvp_melding_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_melding_nonce'] ) ), self::NONCE ) ) {
			wp_die( 'Sikkerhetssjekken feilet. Gå tilbake og prøv igjen.', 'Ugyldig forespørsel', array( 'response' => 403 ) );
		}

		if ( ! is_user_logged_in() ) {
			self::handle_nopriv();
		}

		$post_id = isset( $_POST['bvp_id'] ) ? absint( $_POST['bvp_id'] ) : 0;
		$retur   = isset( $_POST['bvp_retur'] ) ? esc_url_raw( wp_unslash( $_POST['bvp_retur'] ) ) : home_url();
		$retur   = wp_validate_redirect( $retur, home_url() );
		$tekst   = isset( $_POST['bvp_melding'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bvp_melding'] ) ) : '';
		$tekst   = trim( mb_substr( $tekst, 0, 4000 ) );

		if ( ! $post_id || ! BVP_Access::can_view( $post_id ) ) {
			wp_die( 'Du har ikke tilgang til denne registreringen.', 'Ingen tilgang', array( 'response' => 403 ) );
		}

		if ( '' === $tekst ) {
			wp_safe_redirect( add_query_arg( 'bvp_msg', 'tom_melding', $retur ) . '#bvp-meldinger' );
			exit;
		}

		$bruker = wp_get_current_user();

		$melding_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $bruker->display_name,
				'comment_author_email' => $bruker->user_email,
				'comment_content'      => $tekst,
				'comment_type'         => self::TYPE,
				'comment_approved'     => 1,
				'user_id'              => $bruker->ID,
			)
		);

		if ( $melding_id ) {
			// Hvem ble nevnt? Bare folk som faktisk har tilgang.
			$nevnt   = isset( $_POST['bvp_nevnt'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['bvp_nevnt'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$lovlige = wp_list_pluck( self::personer( $post_id ), 'id' );
			$nevnt   = array_values( array_unique( array_intersect( $nevnt, array_map( 'intval', $lovlige ) ) ) );
			$nevnt   = array_diff( $nevnt, array( (int) $bruker->ID ) );

			if ( $nevnt ) {
				update_comment_meta( $melding_id, 'bvp_nevnt', $nevnt );
			}

			BVP_Mail::notify_melding( $post_id, $tekst, $bruker, $nevnt );
		}

		wp_safe_redirect( add_query_arg( 'bvp_msg', 'melding_sendt', $retur ) . '#bvp-meldinger' );
		exit;
	}

	public static function handle_nopriv() {
		wp_safe_redirect( wp_login_url( home_url() ) );
		exit;
	}

	/**
	 * Hvem skal varsles om en ny melding: eier og tilleggskontakter.
	 *
	 * @param int $post_id
	 * @param int $unntatt Bruker-ID som ikke skal varsles (avsenderen).
	 * @return array e-postadresser
	 */
	public static function mottakere( $post_id, $unntatt = 0, $uten = array() ) {
		$ider = array_merge(
			array( (int) get_post_field( 'post_author', $post_id ) ),
			BVP_Access::contacts( $post_id )
		);

		$ider = array_diff( array_unique( array_filter( $ider ) ), array( (int) $unntatt ), array_map( 'intval', (array) $uten ) );

		$epost = array();

		foreach ( $ider as $id ) {
			$bruker = get_userdata( $id );

			if ( $bruker && is_email( $bruker->user_email ) ) {
				$epost[] = $bruker->user_email;
			}
		}

		return $epost;
	}
}
