<?php
/**
 * Registrerer innholdstypen for BV-piloter/prosjekter.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Post_Type {

	const POST_TYPE = 'bv_pilot_prosjekt';

	/** Meta-prefiks for alle feltverdier. */
	const META_PREFIX = '_bvp_';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush' ), 20 );
	}

	/**
	 * Skriv om lenkereglene én gang etter at innholdstypen fikk permalenker.
	 */
	public static function maybe_flush() {
		if ( (string) get_option( 'bvp_permalenker', '' ) === BVP_VERSION ) {
			return;
		}

		update_option( 'bvp_permalenker', BVP_VERSION, false );
		flush_rewrite_rules( false );
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => 'BV-pilot/prosjekt',
					'singular_name'      => 'BV-pilot/prosjekt',
					'menu_name'          => 'BV-pilot/prosjekt',
					'add_new'            => 'Legg til ny',
					'add_new_item'       => 'Nytt BV-pilot/prosjekt',
					'edit_item'          => 'Rediger BV-pilot/prosjekt',
					'view_item'          => 'Vis BV-pilot/prosjekt',
					'search_items'       => 'Søk i piloter/prosjekter',
					'not_found'          => 'Ingen piloter/prosjekter funnet',
					'not_found_in_trash' => 'Ingen piloter/prosjekter i papirkurven',
				),
				'public'              => false,
				'publicly_queryable'  => true, // Hver registrering får sin egen side.
				'query_var'           => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-clipboard',
				'menu_position'       => 26,
				'supports'            => array( 'title', 'author' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'capabilities'        => array(
					'create_posts' => 'edit_others_posts',
				),
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => array(
					'slug'       => apply_filters( 'bvp_permalink_slug', 'prosjekt' ),
					'with_front' => false,
				),
				'hierarchical'        => false,
			)
		);
	}

	/**
	 * Full meta-nøkkel for et felt.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function meta_key( $key ) {
		return self::META_PREFIX . $key;
	}

	/**
	 * Er registreringen innsendt (publisert) eller kladd?
	 *
	 * @param int|WP_Post $post
	 * @return string 'kladd' | 'innsendt'
	 */
	public static function status_label( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		return ( 'publish' === $post->post_status ) ? 'Innsendt' : 'Kladd';
	}
}
