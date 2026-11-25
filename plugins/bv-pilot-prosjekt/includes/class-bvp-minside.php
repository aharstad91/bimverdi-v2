<?php
/**
 * "Mine prosjekter" - ferdig modul til min-side.
 *
 * Kan brukes på tre måter:
 *  - kortkoden [bv_mine_prosjekter]
 *  - funksjonen bvp_mine_prosjekter() i en mal
 *  - BVP_Minside::menu_item() for menypunktet
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Minside {

	const SHORTCODE = 'bv_mine_prosjekter';

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Menypunktet min-side skal vise.
	 *
	 * @return array label, url, slug, antall
	 */
	public static function menu_item() {
		return array(
			'label'  => 'Mine prosjekter',
			'slug'   => 'prosjekter',
			'url'    => home_url( '/min-side/prosjekter/' ),
			'antall' => self::antall(),
		);
	}

	/**
	 * Antall registreringer brukeren selv har.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public static function antall( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		return count( BVP_Repository::get_user_prosjekter( $user_id ) );
	}

	/**
	 * Siden skjemaet ligger på.
	 *
	 * @return string
	 */
	public static function skjema_url() {
		$settings = BVP_Admin::get_settings();

		if ( ! empty( $settings['skjema_url'] ) ) {
			return $settings['skjema_url'];
		}

		$side = (int) get_option( 'bvp_page_id' );

		if ( $side && 'publish' === get_post_status( $side ) ) {
			return (string) get_permalink( $side );
		}

		$treff = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				's'              => BVP_Form::SHORTCODE,
				'fields'         => 'ids',
			)
		);

		foreach ( $treff as $id ) {
			if ( has_shortcode( (string) get_post_field( 'post_content', $id ), BVP_Form::SHORTCODE ) ) {
				return (string) get_permalink( $id );
			}
		}

		return home_url( '/tjenester-og-verktoy/prosjekt/' );
	}

	/**
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'tittel'   => 'Mine prosjekter',
				'foretak'  => 'ja',
				'delt'     => 'ja',
			),
			$atts,
			self::SHORTCODE
		);

		return self::render(
			array(
				'tittel'  => $atts['tittel'],
				'foretak' => 'ja' === strtolower( $atts['foretak'] ),
				'delt'    => 'ja' === strtolower( $atts['delt'] ),
			)
		);
	}

	/**
	 * Selve visningen.
	 *
	 * @param array $args tittel, foretak (bool), delt (bool), knapp (bool)
	 * @return string HTML
	 */
	public static function render( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'tittel'  => 'Mine prosjekter',
				'foretak' => true,
				'delt'    => true,
				'knapp'   => true,
			)
		);

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="bvp bvp-minside"><p>Logg inn for å se prosjektene dine.</p><p><a class="bvp-button" href="%s">Logg inn</a></p></div>',
				esc_url( wp_login_url( self::skjema_url() ) )
			);
		}

		$user_id = get_current_user_id();
		$egne    = BVP_Repository::get_user_prosjekter( $user_id );
		$delt    = $args['delt'] ? BVP_Access::shared_with( $user_id ) : array();
		$kolleger = $args['foretak'] ? self::foretakets( $user_id ) : array();

		// Kolleger hører hjemme under foretaket, ikke under "delt med meg".
		$kollega_ider = wp_list_pluck( $kolleger, 'ID' );
		$delt         = array_values(
			array_filter(
				$delt,
				static function ( $post ) use ( $kollega_ider ) {
					return ! in_array( $post->ID, $kollega_ider, true );
				}
			)
		);

		ob_start();
		echo '<div class="bvp bvp-minside">';

		if ( $args['tittel'] ) {
			echo '<h2 class="bvp-heading">' . esc_html( $args['tittel'] ) . '</h2>';
		}

		if ( $args['knapp'] ) {
			printf(
				'<p><a class="bvp-button" href="%s">Registrer nytt pilot/prosjekt</a></p>',
				esc_url( add_query_arg( 'bvp_view', 'skjema', self::skjema_url() ) )
			);
		}

		self::tabell(
			'Mine registreringer',
			$egne,
			'Du har ingen registrerte piloter eller prosjekter ennå.',
			true
		);

		if ( $args['delt'] ) {
			self::tabell(
				'Delt med meg',
				$delt,
				'',
				false
			);
		}

		if ( $args['foretak'] ) {
			$foretak_navn = BVP_Foretak::name( BVP_Foretak::for_user( $user_id ) );

			self::tabell(
				$foretak_navn ? 'Registrert av kolleger i ' . $foretak_navn : 'Registrert av kolleger',
				$kolleger,
				'',
				false,
				'Her vises registreringer fra foretaket ditt som er delt med deg.'
			);
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Registreringer fra kolleger i samme foretak som brukeren har lov å se.
	 *
	 * @param int $user_id
	 * @return WP_Post[]
	 */
	public static function foretakets( $user_id ) {
		$foretak_id = BVP_Foretak::for_user( $user_id );

		if ( ! $foretak_id ) {
			return array();
		}

		$kolleger = array_map(
			static function ( $user ) {
				return (int) $user->ID;
			},
			BVP_Foretak::members( $foretak_id )
		);

		$kolleger = array_values( array_diff( $kolleger, array( (int) $user_id ) ) );

		if ( empty( $kolleger ) ) {
			return array();
		}

		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'author__in'     => $kolleger,
			)
		);

		return array_values(
			array_filter(
				$poster,
				static function ( $post ) use ( $user_id ) {
					return BVP_Access::can_view( $post, $user_id );
				}
			)
		);
	}

	/**
	 * En tabell med registreringer.
	 *
	 * @param string    $tittel
	 * @param WP_Post[] $poster
	 * @param string    $tomtekst Vises når lista er tom. Tom streng = hopp over hele seksjonen.
	 * @param bool      $egne     Egne registreringer kan redigeres.
	 * @param string    $hjelp
	 */
	protected static function tabell( $tittel, $poster, $tomtekst = '', $egne = false, $hjelp = '' ) {
		if ( empty( $poster ) && '' === $tomtekst ) {
			return;
		}

		echo '<h3 class="bvp-heading bvp-minside__tittel">' . esc_html( $tittel ) . '</h3>';

		if ( $hjelp ) {
			echo '<p class="bvp-desc">' . esc_html( $hjelp ) . '</p>';
		}

		if ( empty( $poster ) ) {
			echo '<p>' . esc_html( $tomtekst ) . '</p>';
			return;
		}

		echo '<table class="bvp-table"><thead><tr>';
		echo '<th>ID</th><th>Tittel</th><th>Prosjekt</th>';

		if ( ! $egne ) {
			echo '<th>Registrert av</th>';
		}

		echo '<th>Prosjekteier</th><th>Synlighet</th><th>Status</th><th>Meldinger</th><th>Sist endret</th><th></th></tr></thead><tbody>';

		foreach ( $poster as $post ) {
			$url = BVP_Form::single_url( $post, self::skjema_url() );

			echo '<tr>';
			echo '<td><code class="bvp-id">#' . (int) $post->ID . '</code></td>';
			echo '<td>' . esc_html( get_the_title( $post ) ) . '</td>';
			echo '<td>' . BVP_Repository::prosjekt_html( $post ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput

			if ( ! $egne ) {
				$navn = trim(
					BVP_Repository::get_value( $post->ID, 'reg_fornavn' ) . ' ' .
					BVP_Repository::get_value( $post->ID, 'reg_etternavn' )
				);
				echo '<td>' . esc_html( $navn ) . '</td>';
			}

			echo '<td>' . BVP_Repository::foretak_html( $post ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td>' . BVP_Repository::synlighet_html( $post ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput

			printf(
				'<td><span class="bvp-status bvp-status--%1$s">%2$s</span></td>',
				esc_attr( 'publish' === $post->post_status ? 'innsendt' : 'kladd' ),
				esc_html( BVP_Post_Type::status_label( $post ) )
			);

			$meldinger = BVP_Meldinger::antall( $post->ID );

			echo '<td>' . ( $meldinger ? (int) $meldinger : '&ndash;' ) . '</td>';
			echo '<td>' . esc_html( get_the_modified_date( 'j. F Y', $post ) ) . '</td>';

			printf(
				'<td><a class="bvp-button bvp-button--link" href="%1$s" target="_blank" rel="noopener">%2$s</a></td>',
				esc_url( $url ),
				$egne ? 'Åpne' : 'Les'
			);

			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}

/**
 * "Mine prosjekter" som HTML - til bruk i maler.
 *
 * @param array $args tittel, foretak, delt, knapp
 * @return string
 */
function bvp_mine_prosjekter( $args = array() ) {
	return BVP_Minside::render( $args );
}

/**
 * Antall registreringer brukeren har - til teller i menyen.
 *
 * @param int $user_id
 * @return int
 */
function bvp_mine_prosjekter_antall( $user_id = 0 ) {
	return BVP_Minside::antall( $user_id );
}

/**
 * Menypunktet til min-side.
 *
 * @return array
 */
function bvp_mine_prosjekter_menypunkt() {
	return BVP_Minside::menu_item();
}
