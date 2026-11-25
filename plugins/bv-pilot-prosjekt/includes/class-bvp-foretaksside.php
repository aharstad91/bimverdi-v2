<?php
/**
 * Prosjekter på foretaksprofilen.
 *
 * Øverst på foretaksprofilen (f.eks. /deltakere/wk-entreprenor-as/) vises
 * prosjektene foretaket eier, og prosjektene der noen fra foretaket er
 * medredaktør. Tilgangen er den samme som ellers: det leseren ikke har
 * tilgang til, vises uten lenke, og private prosjekter og kladder vises
 * bare for dem som kan lese dem.
 *
 * Temaet har sin egen mal for foretak. Lista skrives derfor ut nederst på
 * siden og flyttes øverst med et lite skript. Kortkoden
 * [bv_foretak_prosjekter] kan i stedet settes rett inn i malen.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Foretaksside {

	const SHORTCODE = 'bv_foretak_prosjekter';

	/** Er lista alt skrevet ut på denne siden? */
	public static $vist = false;

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'footer' ), 5 );
	}

	/** Er vi på en foretaksprofil? */
	public static function er_foretaksside() {
		return is_singular( BVP_Foretak::post_types() );
	}

	public static function assets() {
		if ( self::er_foretaksside() ) {
			wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		}
	}

	/**
	 * Kortkoden. Uten foretak-attributt brukes foretaket siden handler om.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts( array( 'foretak' => 0 ), $atts, self::SHORTCODE );
		$id   = (int) $atts['foretak'] ? (int) $atts['foretak'] : (int) get_queried_object_id();

		if ( ! $id || ! BVP_Foretak::is_foretak( $id ) ) {
			return '';
		}

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );

		self::$vist = true;

		return self::html( $id );
	}

	/**
	 * Når malen ikke har kortkoden: skriv lista nederst og flytt den øverst.
	 */
	public static function footer() {
		if ( self::$vist || ! self::er_foretaksside() ) {
			return;
		}

		$id = (int) get_queried_object_id();

		if ( ! $id ) {
			return;
		}

		self::$vist = true;

		echo '<template id="bvp-foretak-prosjekter">' . self::html( $id ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
		?>
		<script>
		( function () {
			var mal = document.getElementById( 'bvp-foretak-prosjekter' );
			if ( ! mal ) { return; }
			var del = mal.content.firstElementChild;
			if ( ! del ) { return; }

			// Øverst i hovedspalten, foran «Om foretaket».
			var om = Array.prototype.find.call( document.querySelectorAll( 'main h2' ), function ( h ) {
				return h.textContent.trim() === 'Om foretaket';
			} );
			var forste = om ? om.closest( 'section' ) : null;

			if ( forste && forste.parentNode ) {
				forste.parentNode.insertBefore( del, forste );
				forste.classList.add( 'border-t', 'border-[#E7E5E4]', 'pt-10' );
				return;
			}

			var main = document.querySelector( 'main' );
			if ( main ) {
				main.insertBefore( del, main.firstChild );
			}
		} )();
		</script>
		<?php
	}

	/**
	 * Prosjektene foretaket eier, og der foretaket er medredaktør.
	 *
	 * @param int $foretak_id
	 * @return array eier => WP_Post[], medredaktor => WP_Post[]
	 */
	public static function prosjekter( $foretak_id ) {
		$foretak_id = (int) $foretak_id;
		$ut         = array(
			'eier'        => array(),
			'medredaktor' => array(),
		);

		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 300,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( (array) $poster as $post ) {
			if ( (int) BVP_Foretak::for_user( (int) $post->post_author ) === $foretak_id ) {
				$ut['eier'][] = $post;
				continue;
			}

			foreach ( BVP_Access::contacts( $post->ID ) as $kontakt ) {
				if ( (int) BVP_Foretak::for_user( $kontakt ) === $foretak_id ) {
					$ut['medredaktor'][] = $post;
					break;
				}
			}
		}

		return $ut;
	}

	/**
	 * Skal prosjektet vises for leseren, og i så fall med lenke?
	 *
	 * @param WP_Post $post
	 * @return string 'lenke', 'navn' eller '' (skjult).
	 */
	protected static function visning( $post ) {
		if ( BVP_Access::can_view( $post ) ) {
			return 'lenke';
		}

		// Kladder og private prosjekter vises bare for dem som kan lese dem.
		if ( 'publish' !== $post->post_status || 'privat' === BVP_Access::level( $post->ID ) ) {
			return '';
		}

		return 'navn';
	}

	/**
	 * @param int $foretak_id
	 * @return string
	 */
	public static function html( $foretak_id ) {
		$grupper = self::prosjekter( $foretak_id );
		$titler  = array(
			'eier'        => 'Eier',
			'medredaktor' => 'Medredaktør',
		);

		$deler   = '';
		$skjult  = false;
		$antall  = 0;

		foreach ( $titler as $nokkel => $tittel ) {
			$rader = '';
			$n     = 0;

			foreach ( $grupper[ $nokkel ] as $post ) {
				$visning = self::visning( $post );

				if ( '' === $visning ) {
					continue;
				}

				$n++;

				$navn = esc_html( get_the_title( $post ) );

				if ( 'lenke' === $visning ) {
					$navn = sprintf( '<a href="%1$s" class="text-[#FF8B5E] hover:underline">%2$s</a>', esc_url( BVP_Form::single_url( $post ) ), $navn );
				} else {
					$skjult = true;
				}

				$ekstra = '';

				if ( '' !== BVP_Repository::prosjekt_tekst( $post ) ) {
					$ekstra .= ' ' . BVP_Repository::prosjekt_html( $post );
				}

				if ( 'medredaktor' === $nokkel ) {
					$eier = BVP_Repository::foretak_for( $post );
					if ( '' !== $eier['navn'] ) {
						$ekstra .= ' <span class="bvp-desc">&ndash; eies av ' . esc_html( $eier['navn'] ) . '</span>';
					}
				}

				$rader .= sprintf(
					'<li class="bvp-fp__rad"><code class="bvp-id">#%1$d</code> %2$s %3$s%4$s</li>',
					(int) $post->ID,
					$navn,
					'publish' === $post->post_status ? BVP_Repository::synlighet_html( $post ) : '<span class="bvp-synlig bvp-synlig--kladd">Kladd</span>',
					$ekstra
				);
			}

			if ( ! $n ) {
				continue;
			}

			$antall += $n;
			$deler  .= sprintf(
				'<h3 class="bvp-fp__del">%1$s (%2$d)</h3><ul class="bvp-fp__liste">%3$s</ul>',
				esc_html( $tittel ),
				$n,
				$rader
			);
		}

		$lenke_oversikt = home_url( '/prosjekter/' );

		if ( ! $antall ) {
			$deler = sprintf(
				'<p class="text-[#57534E] text-sm">Ingen prosjekter eller caser ennå. <a href="%s" class="text-[#FF8B5E] hover:underline">Se prosjektene i nettverket</a></p>',
				esc_url( $lenke_oversikt )
			);
		} elseif ( $skjult && ! is_user_logged_in() ) {
			$deler .= sprintf(
				'<p class="text-[#57534E] text-sm"><a href="%s" class="text-[#FF8B5E] hover:underline">Logg inn</a> for å lese prosjektene uten lenke.</p>',
				esc_url( wp_login_url( (string) get_permalink( $foretak_id ) ) )
			);
		}

		return sprintf(
			'<section class="bvp-fp"><div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-[#111827]">Prosjekter og caser</h2><a href="%1$s" class="text-sm text-[#FF8B5E] hover:underline">Alle prosjekter</a></div>%2$s</section>',
			esc_url( $lenke_oversikt ),
			$deler
		);
	}
}
