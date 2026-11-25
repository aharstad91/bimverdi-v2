<?php
/**
 * Offentlig oversikt: alle registreringer leseren har tilgang til.
 *
 * Kortkode: [bv_alle_prosjekter]
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Oversikt {

	const SHORTCODE = 'bv_alle_prosjekter';

	/** Hvor mange registreringer hentes før filtrering på tilgang. */
	const HENT = 300;

	/** Er oversikten alt vist på denne siden? */
	public static $vist = false;

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		// Vises bare én gang per side, selv om både skjemaet og oversikten ligger der.
		if ( self::$vist ) {
			return '';
		}

		self::$vist = true;

		$atts = shortcode_atts(
			array(
				'tittel'  => 'Registrerte piloter og prosjekter',
				'antall'  => 25,
				'sok'     => 'ja',
				'foretak' => 'ja',
			),
			$atts,
			self::SHORTCODE
		);

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );

		$per_side = max( 5, (int) $atts['antall'] );
		$side     = isset( $_GET['bvp_side'] ) ? max( 1, absint( $_GET['bvp_side'] ) ) : 1;
		$sok      = isset( $_GET['bvp_q'] ) ? sanitize_text_field( wp_unslash( $_GET['bvp_q'] ) ) : '';
		$foretak  = isset( $_GET['bvp_f'] ) ? sanitize_text_field( wp_unslash( $_GET['bvp_f'] ) ) : '';
		$prosjekt = isset( $_GET['bvp_p'] ) ? sanitize_text_field( wp_unslash( $_GET['bvp_p'] ) ) : '';

		$synlige = self::synlige();
		$alle_foretak = self::foretaksliste( $synlige );
		$treff   = self::filtrer( $synlige, $sok, $foretak, $prosjekt );

		$antall_treff = count( $treff );
		$sider        = max( 1, (int) ceil( $antall_treff / $per_side ) );
		$side         = min( $side, $sider );
		$utvalg       = array_slice( $treff, ( $side - 1 ) * $per_side, $per_side );

		ob_start();
		echo '<div class="bvp bvp-oversikt">';

		if ( $atts['tittel'] ) {
			echo '<h2 class="bvp-heading">' . esc_html( $atts['tittel'] ) . '</h2>';
		}

		$utlogget = ! is_user_logged_in();

		// Uten innlogging: hvor mange som er registrert, og hva som finnes bak innloggingen.
		if ( $utlogget ) {
			$niva = self::per_niva();
			self::status( $niva );
			echo '<h3 class="bvp-oversikt__del">Publisert for alle</h3>';
		}

		if ( 'ja' === strtolower( $atts['sok'] ) || 'ja' === strtolower( $atts['foretak'] ) ) {
			self::filterskjema( $atts, $sok, $foretak, $alle_foretak, $prosjekt, self::prosjektliste( $synlige ) );
		}

		if ( empty( $synlige ) && $utlogget ) {
			echo '<p>Ingen registreringer er publisert for alle ennå.</p>';
		} elseif ( empty( $synlige ) ) {
			echo '<p>' . esc_html(
				is_user_logged_in()
					? 'Ingen registreringer er delt med deg ennå.'
					: 'Ingen registreringer er publisert for alle ennå. Logg inn for å se det som er delt med deg.'
			) . '</p>';
		} elseif ( empty( $treff ) ) {
			echo '<p>Ingen registreringer matcher søket.</p>';
		} else {
			printf(
				'<p class="bvp-desc">%d av %d registreringer.</p>',
				count( $utvalg ),
				$antall_treff
			);

			self::tabell( $utvalg );
			self::paginering( $side, $sider );
		}

		if ( $utlogget ) {
			self::navneliste(
				'Kan leses av alle registrerte brukere',
				$niva['deltakere'],
				'Logg inn for å lese disse.'
			);
			self::navneliste(
				'Vises bare for utvalgte deltakere i BIM Verdi',
				$niva['utvalgte'],
				'Logg inn for å se om de er delt med deg eller foretaket ditt.'
			);
		}

		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Alle innsendte registreringer leseren har tilgang til.
	 *
	 * @return WP_Post[]
	 */
	public static function synlige() {
		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => self::HENT,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		return array_values(
			array_filter(
				$poster,
				static function ( $post ) {
					return BVP_Access::can_view( $post );
				}
			)
		);
	}

	/**
	 * Alle innsendte registreringer, sortert på synlighet.
	 *
	 * @return array nivå => WP_Post[]
	 */
	public static function per_niva() {
		$niva = array_fill_keys( array_keys( BVP_Access::levels() ), array() );

		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => self::HENT,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( (array) $poster as $post ) {
			$niva[ BVP_Access::level( $post->ID ) ][] = $post;
		}

		// Kladder telles for seg, uansett valgt synlighet.
		$niva['kladd'] = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'draft', 'pending' ),
				'posts_per_page' => self::HENT,
				'fields'         => 'ids',
			)
		);

		return $niva;
	}

	/**
	 * Antall registrerte, fordelt på synlighet.
	 *
	 * @param array $niva
	 */
	protected static function status( $niva ) {
		$totalt = array_sum( array_map( 'count', $niva ) );

		if ( ! $totalt ) {
			return;
		}

		$deler = array();
		$tekst = array(
			'offentlig' => array( '%d publisert for alle', '%d publisert for alle' ),
			'deltakere' => array( '%d for alle registrerte brukere', '%d for alle registrerte brukere' ),
			'utvalgte'  => array( '%d bare for utvalgte deltakere', '%d bare for utvalgte deltakere' ),
			'privat'    => array( '%d privat', '%d private' ),
			'kladd'     => array( '%d kladd', '%d kladder' ),
		);

		// Alle kategoriene tas med, også de som står på null.
		foreach ( $tekst as $nokkel => $mal ) {
			$antall  = isset( $niva[ $nokkel ] ) ? count( $niva[ $nokkel ] ) : 0;
			$deler[] = sprintf( 1 === $antall ? $mal[0] : $mal[1], $antall );
		}

		printf(
			'<p class="bvp-oversikt__status"><strong>%1$s registrert:</strong> %2$s.</p>',
			esc_html( 1 === $totalt ? '1 prosjekt/case er' : $totalt . ' prosjekter og caser er' ),
			esc_html( ucfirst( self::oppramsing( $deler ) ) )
		);
	}

	/** «a, b og c» */
	protected static function oppramsing( $deler ) {
		if ( count( $deler ) < 2 ) {
			return implode( '', $deler );
		}

		$siste = array_pop( $deler );

		return implode( ', ', $deler ) . ' og ' . $siste;
	}

	/**
	 * Navnene på registreringer som krever innlogging. Bare navnet vises.
	 *
	 * @param string    $tittel
	 * @param WP_Post[] $poster
	 * @param string    $lenketekst
	 */
	protected static function navneliste( $tittel, $poster, $lenketekst ) {
		printf( '<h3 class="bvp-oversikt__del">%s (%d)</h3>', esc_html( $tittel ), count( $poster ) );

		if ( empty( $poster ) ) {
			echo '<p class="bvp-desc">Ingen ennå.</p>';
			return;
		}

		echo '<ul class="bvp-liste bvp-oversikt__navn">';

		foreach ( $poster as $post ) {
			printf(
				'<li><code class="bvp-id">#%1$d</code> %2$s%3$s</li>',
				(int) $post->ID,
				esc_html( get_the_title( $post ) ),
				'' !== BVP_Repository::prosjekt_tekst( $post ) ? ' <span class="bvp-desc">&ndash; ' . esc_html( BVP_Repository::prosjekt_tekst( $post ) ) . '</span>' : ''
			);
		}

		echo '</ul>';

		printf(
			'<p><a class="bvp-button bvp-button--secondary" href="%1$s">%2$s</a></p>',
			esc_url( wp_login_url( (string) get_permalink() ) ),
			esc_html( $lenketekst )
		);
	}

	/**
	 * Foretakene som finnes blant de synlige registreringene.
	 *
	 * @param WP_Post[] $poster
	 * @return array
	 */
	protected static function foretaksliste( $poster ) {
		$navn = array();

		foreach ( $poster as $post ) {
			$f = trim( (string) BVP_Repository::get_value( $post->ID, 'foretaksnavn' ) );

			if ( '' !== $f ) {
				$navn[ $f ] = $f;
			}
		}

		sort( $navn );

		return $navn;
	}

	/**
	 * Filtrer på fritekst og foretak.
	 *
	 * @param WP_Post[] $poster
	 * @param string    $sok
	 * @param string    $foretak
	 * @return WP_Post[]
	 */
	protected static function filtrer( $poster, $sok, $foretak, $prosjekt = '' ) {
		if ( '' === $sok && '' === $foretak && '' === $prosjekt ) {
			return $poster;
		}

		return array_values(
			array_filter(
				$poster,
				static function ( $post ) use ( $sok, $foretak, $prosjekt ) {
					$f = (string) BVP_Repository::get_value( $post->ID, 'foretaksnavn' );

					if ( '' !== $foretak && 0 !== strcasecmp( trim( $f ), $foretak ) ) {
						return false;
					}

					if ( '' !== $prosjekt && ! in_array( $prosjekt, BVP_Repository::prosjekter( $post ), true ) ) {
						return false;
					}

					if ( '' === $sok ) {
						return true;
					}

					$haystack = get_the_title( $post ) . ' ' . $f . ' ' . BVP_Repository::prosjekt_tekst( $post ) . ' ' .
						BVP_Repository::get_value( $post->ID, 'reg_fornavn' ) . ' ' .
						BVP_Repository::get_value( $post->ID, 'reg_etternavn' ) . ' ' .
						BVP_Repository::get_value( $post->ID, 'problem' );

					return false !== stripos( $haystack, $sok );
				}
			)
		);
	}

	/**
	 * Prosjektene som finnes blant de synlige registreringene.
	 *
	 * @param WP_Post[] $poster
	 * @return string[]
	 */
	protected static function prosjektliste( $poster ) {
		$navn = array();

		foreach ( $poster as $post ) {
			foreach ( BVP_Repository::prosjekter( $post ) as $p ) {
				$navn[ $p ] = $p;
			}
		}

		natcasesort( $navn );

		return array_values( $navn );
	}

	/**
	 * Søke- og filterfelt.
	 *
	 * @param array  $atts
	 * @param string $sok
	 * @param string $foretak
	 * @param array  $alle_foretak
	 */
	protected static function filterskjema( $atts, $sok, $foretak, $alle_foretak, $prosjekt = '', $alle_prosjekter = array() ) {
		?>
		<form class="bvp-oversikt__filter" method="get">
			<?php foreach ( self::bevar_parametre() as $navn => $verdi ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $navn ); ?>" value="<?php echo esc_attr( $verdi ); ?>">
			<?php endforeach; ?>

			<?php if ( 'ja' === strtolower( $atts['sok'] ) ) : ?>
				<label>
					<span class="screen-reader-text">Søk</span>
					<input type="search" name="bvp_q" value="<?php echo esc_attr( $sok ); ?>" placeholder="Søk i tittel, foretak eller problem ...">
				</label>
			<?php endif; ?>

			<?php if ( 'ja' === strtolower( $atts['foretak'] ) && $alle_foretak ) : ?>
				<label>
					<span class="screen-reader-text">Foretak</span>
					<select name="bvp_f">
						<option value="">Alle foretak</option>
						<?php foreach ( $alle_foretak as $navn ) : ?>
							<option value="<?php echo esc_attr( $navn ); ?>" <?php selected( $foretak, $navn ); ?>><?php echo esc_html( $navn ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<?php if ( $alle_prosjekter ) : ?>
				<label>
					<span class="screen-reader-text">Prosjekt</span>
					<select name="bvp_p">
						<option value="">Alle prosjekter</option>
						<?php foreach ( $alle_prosjekter as $navn ) : ?>
							<option value="<?php echo esc_attr( $navn ); ?>" <?php selected( $prosjekt, $navn ); ?>><?php echo esc_html( $navn ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<button type="submit" class="bvp-button">Filtrer</button>

			<?php if ( '' !== $sok || '' !== $foretak || '' !== $prosjekt ) : ?>
				<a class="bvp-back" href="<?php echo esc_url( remove_query_arg( array( 'bvp_q', 'bvp_f', 'bvp_p', 'bvp_side' ) ) ); ?>">Nullstill</a>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * Parametre som skal med videre i filterskjemaet (f.eks. side-ID).
	 *
	 * @return array
	 */
	protected static function bevar_parametre() {
		$ut = array();

		foreach ( array( 'page_id', 'p' ) as $navn ) {
			if ( ! empty( $_GET[ $navn ] ) ) {
				$ut[ $navn ] = sanitize_text_field( wp_unslash( $_GET[ $navn ] ) );
			}
		}

		return $ut;
	}

	/**
	 * Selve lista.
	 *
	 * @param WP_Post[] $poster
	 */
	protected static function tabell( $poster ) {
		$base = BVP_Minside::skjema_url();

		echo '<table class="bvp-table"><thead><tr>';
		echo '<th>ID</th><th>Tittel</th><th>Prosjekt</th><th>Prosjekteier</th><th>Synlighet</th><th>Registrert av</th><th>Meldinger</th><th>Sist endret</th><th></th>';
		echo '</tr></thead><tbody>';

		foreach ( $poster as $post ) {
			$navn = trim(
				BVP_Repository::get_value( $post->ID, 'reg_fornavn' ) . ' ' .
				BVP_Repository::get_value( $post->ID, 'reg_etternavn' )
			);

			$meldinger = BVP_Meldinger::antall( $post->ID );

			printf(
				'<tr><td><code class="bvp-id">#%9$d</code></td><td>%1$s</td><td>%10$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td>' .
				'<td><a class="bvp-button bvp-button--link" href="%7$s" target="_blank" rel="noopener">%8$s</a></td></tr>',
				esc_html( get_the_title( $post ) ),
				BVP_Repository::foretak_html( $post ), // phpcs:ignore WordPress.Security.EscapeOutput
				BVP_Repository::synlighet_html( $post ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $navn ),
				$meldinger ? (int) $meldinger : '&ndash;',
				esc_html( get_the_modified_date( 'j. F Y', $post ) ),
				esc_url( BVP_Form::single_url( $post, $base ) ),
				BVP_Access::can_edit( $post ) ? 'Åpne' : 'Les',
				(int) $post->ID,
				BVP_Repository::prosjekt_html( $post ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Enkel sidenavigasjon.
	 *
	 * @param int $side
	 * @param int $sider
	 */
	protected static function paginering( $side, $sider ) {
		if ( $sider < 2 ) {
			return;
		}

		echo '<p class="bvp-oversikt__sider">';

		if ( $side > 1 ) {
			printf(
				'<a class="bvp-button bvp-button--secondary" href="%s">&larr; Forrige</a> ',
				esc_url( add_query_arg( 'bvp_side', $side - 1 ) )
			);
		}

		printf( '<span class="bvp-desc">Side %d av %d</span> ', (int) $side, (int) $sider );

		if ( $side < $sider ) {
			printf(
				'<a class="bvp-button bvp-button--secondary" href="%s">Neste &rarr;</a>',
				esc_url( add_query_arg( 'bvp_side', $side + 1 ) )
			);
		}

		echo '</p>';
	}
}
