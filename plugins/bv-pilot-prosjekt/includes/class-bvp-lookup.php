<?php
/**
 * Felttypen "Oppslag (kunnskapskilder)".
 *
 * Henter kunnskapskilder direkte fra nettstedet, filtrert på kildetype
 * (og eventuelt temagruppe), og lagrer ID-ene til de valgte kildene.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Lookup {

	const TYPE       = 'lookup';
	const NONCE      = 'bvp_lookup';
	const META_TYPE  = 'kildetype';

	/** Valg som ble forkastet i siste sanitize_value(). */
	protected static $avviste = array();

	public static function init() {
		add_action( 'wp_ajax_bvp_kilde_sok', array( __CLASS__, 'ajax_sok' ) );
	}

	/** Standardkilden når feltet ikke har valgt noe annet. */
	public static function post_type() {
		return apply_filters( 'bvp_lookup_post_type', 'kunnskapskilde' );
	}

	/** Taksonomien som brukes til tema-filter på kunnskapskilder. */
	public static function taxonomy() {
		return apply_filters( 'bvp_lookup_taxonomy', 'temagruppe' );
	}

	/**
	 * Kildene feltet kan søke i: innholdstype => etikett.
	 *
	 * Alle offentlige innholdstyper på nettstedet kan velges, slik at
	 * samme felttype kan brukes mot kunnskapskilder, verktøy og
	 * digitale tjenester, arrangementer og så videre.
	 *
	 * @return array
	 */
	public static function kilder() {
		$kjent = array(
			'kunnskapskilde' => 'Kunnskapskilder',
			'verktoy'        => 'Verktøy og digitale tjenester',
		);

		$ut = array();

		foreach ( $kjent as $type => $etikett ) {
			if ( post_type_exists( $type ) ) {
				$ut[ $type ] = $etikett;
			}
		}

		$utelat = array( 'post', 'page', 'attachment', 'bv_pilot_prosjekt', 'bvp_foretak', 'arkivside', 'demo' );

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type => $objekt ) {
			if ( isset( $ut[ $type ] ) || in_array( $type, $utelat, true ) ) {
				continue;
			}

			$ut[ $type ] = isset( $objekt->labels->name ) ? $objekt->labels->name : $type;
		}

		if ( empty( $ut ) ) {
			$ut = $kjent;
		}

		return apply_filters( 'bvp_lookup_kilder', $ut );
	}

	/**
	 * Kilden feltet skal søke i.
	 *
	 * @param array $field
	 * @return string
	 */
	public static function kilde( $field = array() ) {
		$valgt = isset( $field['kilde'] ) ? sanitize_key( (string) $field['kilde'] ) : '';
		$kilder = self::kilder();

		if ( $valgt && isset( $kilder[ $valgt ] ) ) {
			return $valgt;
		}

		return self::post_type();
	}

	/**
	 * Etiketten for kilden feltet søker i.
	 *
	 * @param array $field
	 * @return string
	 */
	public static function kilde_etikett( $field = array() ) {
		$type   = self::kilde( $field );
		$kilder = self::kilder();

		return isset( $kilder[ $type ] ) ? $kilder[ $type ] : $type;
	}

	/**
	 * Taksonomier som hører til en kilde.
	 *
	 * @param string $post_type
	 * @return array
	 */
	public static function taksonomier( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return array();
		}

		$ut = array();

		foreach ( get_object_taxonomies( $post_type, 'objects' ) as $slug => $tax ) {
			if ( empty( $tax->public ) ) {
				continue;
			}

			$ut[ $slug ] = isset( $tax->labels->name ) ? $tax->labels->name : $slug;
		}

		return $ut;
	}

	/**
	 * Kategoriene (termene) man kan filtrere en kilde på.
	 *
	 * @param string $post_type
	 * @return array Etikett (taksonomi) => array( term_id => navn )
	 */
	public static function kategorier( $post_type ) {
		$ut = array();

		foreach ( self::taksonomier( $post_type ) as $slug => $etikett ) {
			$termer = get_terms(
				array(
					'taxonomy'   => $slug,
					'hide_empty' => false,
					'number'     => 200,
				)
			);

			if ( is_wp_error( $termer ) || empty( $termer ) ) {
				continue;
			}

			$liste = array();

			foreach ( $termer as $term ) {
				$liste[ (int) $term->term_id ] = $term->name;
			}

			$ut[ $etikett ] = $liste;
		}

		return $ut;
	}

	/**
	 * Egendefinerte felt (meta-nøkler) som finnes på en kilde.
	 *
	 * Leses ut av innholdet selv, så listen passer nettstedet uten at
	 * noe må kodes inn. Resultatet mellomlagres i 12 timer.
	 *
	 * @param string $post_type
	 * @return array
	 */
	public static function meta_nokler( $post_type ) {
		$post_type = sanitize_key( $post_type );
		$cache     = 'bvp_meta_nokler_' . $post_type;
		$lagret    = get_transient( $cache );

		if ( is_array( $lagret ) ) {
			return $lagret;
		}

		$nokler = array();

		$poster = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'posts_per_page'   => 20,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		foreach ( (array) $poster as $post ) {
			$id = is_object( $post ) ? (int) $post->ID : (int) $post;

			foreach ( (array) get_post_meta( $id ) as $nokkel => $verdi ) {
				if ( '' === (string) $nokkel || '_' === substr( (string) $nokkel, 0, 1 ) ) {
					continue;
				}

				$nokler[ $nokkel ] = $nokkel;
			}
		}

		ksort( $nokler );
		set_transient( $cache, $nokler, 12 * HOUR_IN_SECONDS );

		return $nokler;
	}

	/**
	 * Kildetypene, nøkkel => etikett.
	 *
	 * Leses fra ACF-feltet hvis det finnes, ellers brukes lista fra bimverdi.no.
	 *
	 * @return array
	 */
	public static function kildetyper() {
		$valg = array();

		if ( function_exists( 'acf_get_field' ) ) {
			$felt = acf_get_field( self::META_TYPE );

			if ( ! empty( $felt['choices'] ) && is_array( $felt['choices'] ) ) {
				$valg = $felt['choices'];
			}
		}

		if ( empty( $valg ) ) {
			$valg = array(
				'standard'          => 'Standard (ISO, NS, etc.)',
				'veiledning'        => 'Veiledning/metodikk',
				'forskrift_norsk'   => 'Forskrift (norsk lov)',
				'forordning_eu'     => 'Forordning (EU/EØS)',
				'mal'               => 'Mal/Template',
				'forskningsrapport' => 'Forskningsrapport',
				'casestudie'        => 'Casestudie',
				'opplaering'        => 'Opplæringsmateriell',
				'dokumentasjon'     => 'Verktøydokumentasjon',
				'nettressurs'       => 'Nettressurs/Database',
				'annet'             => 'Annet (tjeneste, webside etc.)',
			);
		}

		return apply_filters( 'bvp_lookup_kildetyper', $valg );
	}

	/**
	 * Temagruppene, term-ID => navn.
	 *
	 * @return array
	 */
	public static function temagrupper() {
		$termer = get_terms(
			array(
				'taxonomy'   => self::taxonomy(),
				'hide_empty' => false,
				'number'     => 100,
			)
		);

		if ( is_wp_error( $termer ) ) {
			return array();
		}

		$ut = array();

		foreach ( $termer as $term ) {
			$ut[ (int) $term->term_id ] = $term->name;
		}

		return $ut;
	}

	/**
	 * Søk etter kunnskapskilder.
	 *
	 * @param string $q
	 * @param array  $field Feltdefinisjonen (kildetyper, temagrupper).
	 * @param int    $limit
	 * @return array
	 */
	public static function search( $q, $field = array(), $limit = 20 ) {
		$svar = self::sok( $q, $field, $limit );

		return $svar['treff'];
	}

	/**
	 * Stegvis søk: treffene, hvor mange det er i alt, og grensen som ble brukt.
	 * Brukeren skriver videre for å snevre inn.
	 *
	 * @param string $q
	 * @param array  $field
	 * @param int    $limit
	 * @return array array( 'treff' => array, 'totalt' => int, 'grense' => int )
	 */
	public static function sok( $q, $field = array(), $limit = 20 ) {
		$post_type = self::kilde( $field );
		$limit     = max( 1, (int) $limit );

		$args = array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'orderby'             => 'title',
			'order'               => 'ASC',
			'no_found_rows'       => false, // Vi teller treffene for å kunne si «viser 20 av 143».
			'ignore_sticky_posts' => true,
		);

		$q = trim( (string) $q );

		if ( '' !== $q ) {
			$args['s'] = $q;
		}

		$meta_query = array();

		// Kildetyper gjelder kunnskapskilder (ACF-feltet kildetype).
		if ( self::post_type() === $post_type ) {
			$typer = isset( $field['kildetyper'] ) ? (array) $field['kildetyper'] : array();
			$typer = array_values( array_intersect( $typer, array_keys( self::kildetyper() ) ) );

			if ( $typer ) {
				$eller = array( 'relation' => 'OR' );

				foreach ( $typer as $type ) {
					// ACF lagrer avkryssing som serialisert liste.
					$eller[] = array(
						'key'     => self::META_TYPE,
						'value'   => '"' . $type . '"',
						'compare' => 'LIKE',
					);
				}

				$meta_query[] = $eller;
			}
		}

		// Fritt meta-filter: nøkkel + verdi, valgt i skjemaeditoren.
		$meta_nokkel = isset( $field['meta_nokkel'] ) ? sanitize_key( (string) $field['meta_nokkel'] ) : '';
		$meta_verdi  = isset( $field['meta_verdi'] ) ? trim( (string) $field['meta_verdi'] ) : '';

		if ( '' !== $meta_nokkel && '' !== $meta_verdi ) {
			$meta_query[] = array(
				'key'     => $meta_nokkel,
				'value'   => $meta_verdi,
				'compare' => 'LIKE',
			);
		}

		if ( $meta_query ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}

			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery
		}

		// Kategorifilter: termene kan høre til flere taksonomier.
		$termer = isset( $field['temagrupper'] ) ? array_map( 'absint', (array) $field['temagrupper'] ) : array();
		$termer = array_values( array_filter( $termer ) );

		if ( $termer ) {
			$grupper = array();

			foreach ( $termer as $term_id ) {
				$term = get_term( $term_id );

				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}

				$grupper[ $term->taxonomy ][] = (int) $term->term_id;
			}

			$tax_query = array();

			foreach ( $grupper as $taxonomy => $ids ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $ids,
				);
			}

			if ( $tax_query ) {
				if ( count( $tax_query ) > 1 ) {
					$tax_query['relation'] = 'AND';
				}

				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery
			}
		}

		$query = new WP_Query( $args );
		$treff = array_map( array( __CLASS__, 'payload' ), $query->posts );
		$total = isset( $query->found_posts ) ? (int) $query->found_posts : count( $treff );

		return array(
			'treff'  => $treff,
			'totalt' => max( $total, count( $treff ) ),
			'grense' => $limit,
		);
	}

	/**
	 * Én kilde som enkel liste med data.
	 *
	 * @param WP_Post|int $post
	 * @return array
	 */
	public static function payload( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array();
		}

		$ekstern = self::forste_meta( $post->ID, array( 'ekstern_lenke', 'nettside', 'nettadresse', 'url', 'lenke' ) );
		$side    = (string) get_permalink( $post );

		/*
		 * Lenken skal peke til siden på bimverdi.no, f.eks.
		 * https://bimverdi.no/kunnskapskilder/ns-34512022-bygningsdelstabell/
		 * Den opprinnelige kilden ligger i 'ekstern' for den som trenger den.
		 */
		return array(
			'id'      => (int) $post->ID,
			'tittel'  => (string) get_the_title( $post ),
			'utgiver' => self::forste_meta( $post->ID, array( 'utgiver', 'leverandor', 'leverandør', 'kilde' ) ),
			'type'    => self::type_tekst( $post ),
			'lenke'   => $side ? $side : $ekstern,
			'ekstern' => $ekstern,
		);
	}

	/**
	 * Første meta-nøkkel som har en verdi.
	 *
	 * @param int   $post_id
	 * @param array $nokler
	 * @return string
	 */
	protected static function forste_meta( $post_id, $nokler ) {
		foreach ( (array) $nokler as $nokkel ) {
			$verdi = get_post_meta( $post_id, $nokkel, true );

			if ( is_array( $verdi ) ) {
				$verdi = implode( ', ', array_filter( $verdi, 'is_scalar' ) );
			}

			$verdi = is_scalar( $verdi ) ? trim( (string) $verdi ) : '';

			if ( '' !== $verdi ) {
				return $verdi;
			}
		}

		return '';
	}

	/**
	 * Kort typebeskrivelse: kildetype for kunnskapskilder, ellers
	 * kategoriene eller "type"-feltet innholdet har.
	 *
	 * @param WP_Post $post
	 * @return string
	 */
	protected static function type_tekst( $post ) {
		if ( self::post_type() === $post->post_type ) {
			$typer   = (array) get_post_meta( $post->ID, self::META_TYPE, true );
			$etikett = self::kildetyper();
			$navn    = array();

			foreach ( $typer as $type ) {
				if ( isset( $etikett[ $type ] ) ) {
					$navn[] = $etikett[ $type ];
				}
			}

			if ( $navn ) {
				return implode( ', ', $navn );
			}
		}

		$meta = self::forste_meta( $post->ID, array( 'type', 'verktoytype', 'kategori' ) );

		if ( '' !== $meta ) {
			return $meta;
		}

		$navn = array();

		foreach ( array_keys( self::taksonomier( $post->post_type ) ) as $taxonomy ) {
			$termer = get_the_terms( $post->ID, $taxonomy );

			if ( is_wp_error( $termer ) || empty( $termer ) ) {
				continue;
			}

			foreach ( $termer as $term ) {
				$navn[] = $term->name;
			}

			break; // Første taksonomi holder.
		}

		return implode( ', ', $navn );
	}

	/**
	 * Data for flere ID-er, i oppgitt rekkefølge.
	 *
	 * @param array $ids
	 * @param array $field Feltdefinisjonen, hvis den er kjent.
	 * @return array
	 */
	public static function items( $ids, $field = array() ) {
		$ut = array();

		foreach ( (array) $ids as $entry ) {
			$valg = self::valg( $entry );

			if ( self::is_valid( $valg['id'], $field ) ) {
				$kilde           = self::payload( $valg['id'] );
				$kilde['merker'] = $valg['merker'];
				$ut[]            = $kilde;
			}
		}

		return $ut;
	}

	/**
	 * Merkegruppene for valgte treff. Én gruppe per linje i skjemaeditoren:
	 * «Status: Pålagt, Anbefalt». Tom liste betyr ingen merking.
	 *
	 * @param array $field
	 * @return array Liste med array( 'navn' => string, 'valg' => array )
	 */
	public static function merker( $field = array() ) {
		$merker = isset( $field['merker'] ) ? $field['merker'] : array();

		if ( is_string( $merker ) ) {
			$merker = preg_split( '/\r\n|\r|\n/', $merker );
		}

		$ut    = array();
		$navne = array();

		foreach ( (array) $merker as $gruppe ) {
			if ( is_array( $gruppe ) ) {
				$navn = isset( $gruppe['navn'] ) && is_scalar( $gruppe['navn'] ) ? (string) $gruppe['navn'] : '';
				$valg = isset( $gruppe['valg'] ) ? (array) $gruppe['valg'] : array();
			} elseif ( is_scalar( $gruppe ) ) {
				$deler = explode( ':', (string) $gruppe, 2 );
				$navn  = 2 === count( $deler ) ? $deler[0] : 'Merke';
				$valg  = explode( ',', 2 === count( $deler ) ? $deler[1] : $deler[0] );
			} else {
				continue;
			}

			$navn = sanitize_text_field( $navn );
			$rent = array();

			foreach ( $valg as $v ) {
				$v = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';

				if ( '' !== $v && ! in_array( $v, $rent, true ) ) {
					$rent[] = $v;
				}
			}

			if ( '' === $navn || ! $rent || in_array( $navn, $navne, true ) ) {
				continue;
			}

			$navne[] = $navn;
			$ut[]    = array(
				'navn' => $navn,
				'valg' => $rent,
			);
		}

		return $ut;
	}

	/**
	 * Merkegruppene som tekst for skjemaeditoren, én gruppe per linje.
	 *
	 * @param array $field
	 * @return string
	 */
	public static function merker_tekst( $field = array() ) {
		$linjer = array();

		foreach ( self::merker( $field ) as $gruppe ) {
			$linjer[] = $gruppe['navn'] . ': ' . implode( ', ', $gruppe['valg'] );
		}

		return implode( "\n", $linjer );
	}

	/**
	 * Ett lagret valg som ID og merker (gruppenavn => verdi).
	 * Eldre svar er bare ID-en.
	 *
	 * @param mixed $entry
	 * @return array
	 */
	public static function valg( $entry ) {
		if ( is_array( $entry ) ) {
			$merker = array();

			foreach ( (array) ( isset( $entry['merker'] ) ? $entry['merker'] : array() ) as $navn => $verdi ) {
				if ( is_scalar( $verdi ) && '' !== trim( (string) $verdi ) ) {
					$merker[ (string) $navn ] = trim( (string) $verdi );
				}
			}

			return array(
				'id'     => absint( isset( $entry['id'] ) ? $entry['id'] : 0 ),
				'merker' => $merker,
			);
		}

		return array(
			'id'     => absint( $entry ),
			'merker' => array(),
		);
	}

	/**
	 * Merkene på ett valg i gruppenes rekkefølge, bare gyldige verdier.
	 *
	 * @param array $kilde  Fra items().
	 * @param array $field
	 * @return array gruppenavn => verdi
	 */
	public static function merker_for( $kilde, $field ) {
		$ut    = array();
		$satte = isset( $kilde['merker'] ) ? (array) $kilde['merker'] : array();

		foreach ( self::merker( $field ) as $gruppe ) {
			$verdi = isset( $satte[ $gruppe['navn'] ] ) ? (string) $satte[ $gruppe['navn'] ] : '';

			if ( in_array( $verdi, $gruppe['valg'], true ) ) {
				$ut[ $gruppe['navn'] ] = $verdi;
			}
		}

		return $ut;
	}

	/**
	 * Saniter innsendte valg. Skjemaet sender merkene med gruppenummer
	 * (merker[0], merker[1]); de lagres med gruppenavn. Valg uten merker
	 * lagres som ID, slik som før.
	 *
	 * @param mixed $value
	 * @param array $field
	 * @return array
	 */
	public static function sanitize_value( $value, $field ) {
		$grupper = self::merker( $field );
		$ut      = array();
		$sett    = array();

		self::$avviste = array();

		foreach ( is_array( $value ) ? $value : array() as $entry ) {
			$id = absint( is_array( $entry ) ? ( isset( $entry['id'] ) ? $entry['id'] : 0 ) : $entry );

			if ( ! $id || isset( $sett[ $id ] ) ) {
				continue;
			}

			if ( ! self::is_valid( $id, $field ) ) {
				self::avvis( $id, $field );
				continue;
			}

			$sett[ $id ] = true;
			$innsendt    = is_array( $entry ) && isset( $entry['merker'] ) ? (array) $entry['merker'] : array();
			$merker      = array();

			foreach ( $grupper as $i => $gruppe ) {
				$verdi = isset( $innsendt[ $i ] ) ? $innsendt[ $i ] : ( isset( $innsendt[ $gruppe['navn'] ] ) ? $innsendt[ $gruppe['navn'] ] : '' );
				$verdi = is_scalar( $verdi ) ? sanitize_text_field( (string) $verdi ) : '';

				if ( in_array( $verdi, $gruppe['valg'], true ) ) {
					$merker[ $gruppe['navn'] ] = $verdi;
				}
			}

			$ut[] = $merker
				? array(
					'id'     => $id,
					'merker' => $merker,
				)
				: $id;
		}

		if ( ! empty( $field['maks'] ) && count( $ut ) > (int) $field['maks'] ) {
			$maks   = (int) $field['maks'];
			$kuttet = array_slice( $ut, $maks );
			$ut     = array_slice( $ut, 0, $maks );

			foreach ( $kuttet as $entry ) {
				$id = is_array( $entry ) ? (int) $entry['id'] : (int) $entry;

				self::$avviste[] = array(
					'id'     => $id,
					'tittel' => (string) get_the_title( $id ),
					'grunn'  => sprintf( 'feltet tar maks %d valg', $maks ),
				);
			}
		}

		return $ut;
	}

	/**
	 * Noter et valg som ikke kunne lagres, med grunnen.
	 *
	 * @param int   $id
	 * @param array $field
	 */
	protected static function avvis( $id, $field ) {
		$tittel = (string) get_the_title( $id );
		$type   = get_post_type( $id );

		if ( ! $type ) {
			$grunn = 'oppføringen finnes ikke lenger';
		} elseif ( 'publish' !== get_post_status( $id ) ) {
			$grunn = 'oppføringen er ikke publisert';
		} elseif ( $type !== self::kilde( $field ) ) {
			$grunn = sprintf( 'den hører ikke til %s, som dette feltet søker i', self::kilde_etikett( $field ) );
		} else {
			$grunn = 'valget ble avvist';
		}

		self::$avviste[] = array(
			'id'     => (int) $id,
			'tittel' => '' !== $tittel ? $tittel : 'Valg nr. ' . (int) $id,
			'grunn'  => $grunn,
		);
	}

	/**
	 * Valgene som ble forkastet i siste sanitize_value().
	 *
	 * @return array
	 */
	public static function avviste() {
		return self::$avviste;
	}

	/**
	 * Er ID-en en gyldig kunnskapskilde?
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function is_valid( $id, $field = array() ) {
		$id = (int) $id;

		if ( ! $id || 'publish' !== get_post_status( $id ) ) {
			return false;
		}

		$type = get_post_type( $id );

		// Er feltet kjent, må innholdet komme fra kilden feltet søker i.
		if ( ! empty( $field ) ) {
			return $type === self::kilde( $field );
		}

		return isset( self::kilder()[ $type ] ) || self::post_type() === $type;
	}

	/**
	 * AJAX: søk, med filtrene som er satt på feltet i skjemaeditoren.
	 */
	public static function ajax_sok() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'melding' => 'Du må være innlogget.' ), 403 );
		}

		check_ajax_referer( self::NONCE, 'nonce' );

		$nokkel = isset( $_POST['felt'] ) ? sanitize_key( wp_unslash( $_POST['felt'] ) ) : '';
		$field  = BVP_Fields::get( $nokkel );

		if ( ! $field || self::TYPE !== $field['type'] ) {
			wp_send_json_error( array( 'melding' => 'Ukjent felt.' ), 400 );
		}

		$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		wp_send_json_success( self::sok( $q, $field ) );
	}
}
