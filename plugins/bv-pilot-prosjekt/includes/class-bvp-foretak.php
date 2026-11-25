<?php
/**
 * Foretaksprofiler og kobling mellom bruker og foretak.
 *
 * Persontypene følger BIM Verdi-modellen: Hovedkontakt, Tilleggskontakt,
 * Bruker og Gratisbruker. Bare Hovedkontakt og Tilleggskontakt kan velges
 * som tilleggskontakt på en registrering.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Foretak {

	const POST_TYPE     = 'bvp_foretak';
	const USER_FORETAK  = 'bvp_foretak_id';
	const USER_TYPE     = 'bvp_persontype';
	const META_ORGNR    = '_bvp_orgnr';
	const META_DOMENER  = '_bvp_domener';
	const NONCE         = 'bvp_foretak_save';

	/** Oppslag per forespørsel: bruker-ID => foretak-ID. */
	protected static $cache = array();

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'wp_ajax_bvp_foretak_sok', array( __CLASS__, 'ajax_sok' ) );
		add_action( 'wp_ajax_bvp_foretak_kontakter', array( __CLASS__, 'ajax_kontakter' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
			add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
			add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
			add_action( 'show_user_profile', array( __CLASS__, 'user_fields' ) );
			add_action( 'edit_user_profile', array( __CLASS__, 'user_fields' ) );
			add_action( 'personal_options_update', array( __CLASS__, 'save_user_fields' ) );
			add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user_fields' ) );
		}
	}

	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => 'Foretak',
					'singular_name' => 'Foretak',
					'menu_name'     => 'Foretak',
					'add_new_item'  => 'Nytt foretak',
					'edit_item'     => 'Rediger foretak',
					'search_items'  => 'Søk i foretak',
					'not_found'     => 'Ingen foretak funnet',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'create_posts' => 'edit_others_posts',
				),
				'rewrite'         => false,
				'has_archive'     => false,
			)
		);
	}

	/**
	 * Persontypene.
	 *
	 * @return array
	 */
	public static function persontyper() {
		return array(
			'hovedkontakt'   => 'Hovedkontakt',
			'tilleggskontakt' => 'Tilleggskontakt',
			'bruker'         => 'Bruker',
			'gratisbruker'   => 'Gratisbruker',
		);
	}

	/**
	 * Meta-nøklene nettstedet selv bruker for koblingen bruker => foretak.
	 *
	 * Vi skriver til alle, slik at resten av bimverdi.no ser koblingen med
	 * en gang - ikke bare pluginen.
	 *
	 * @return array
	 */
	public static function nettstedets_nokler() {
		return apply_filters(
			'bvp_foretak_meta_keys',
			array( 'bimverdi_company_id', 'bim_verdi_company_id', 'tilknyttet_foretak' )
		);
	}

	/** Nøkkelen for kontotype, og verdien et foretakskoblet medlem skal ha. */
	public static function kontotype_nokkel() {
		return apply_filters( 'bvp_kontotype_meta_key', 'bimverdi_account_type' );
	}

	/** Verdien kontotypen settes til ved kobling. */
	public static function kontotype_verdi() {
		return apply_filters( 'bvp_kontotype_verdi', 'foretak' );
	}

	/**
	 * Persontyper som skal løftes til tilleggskontakt ved kobling.
	 *
	 * «medlem» er nettstedets egen verdi for en vanlig bruker.
	 *
	 * @return array
	 */
	public static function svake_typer() {
		return apply_filters( 'bvp_svake_persontyper', array( '', 'medlem', 'bruker', 'gratisbruker' ) );
	}

	/** Typene som kan velges som tilleggskontakt på en registrering. */
	public static function kontakttyper() {
		return apply_filters( 'bvp_kontakttyper', array( 'hovedkontakt', 'tilleggskontakt' ) );
	}

	/**
	 * Innholdstyper som regnes som foretak - pluginens egen, pluss dem som
	 * allerede finnes på nettstedet (bimverdi.no har innholdstypen "foretak").
	 *
	 * @return array
	 */
	public static function post_types() {
		$typer = array( self::POST_TYPE, 'foretak' );

		/**
		 * Filtrer hvilke innholdstyper som regnes som foretaksprofiler.
		 *
		 * @param array $typer
		 */
		$typer = (array) apply_filters( 'bvp_foretak_post_types', $typer );

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', $typer ) ) ) );
	}

	/** Er posten en foretaksprofil? */
	public static function is_foretak( $post_id ) {
		return in_array( (string) get_post_type( (int) $post_id ), self::post_types(), true );
	}

	/**
	 * Finn hvilken brukermeta som kobler bruker til foretak på dette nettstedet.
	 *
	 * Nøkkelen huskes i wp_options første gang den finnes, så vi slipper å lete igjen.
	 *
	 * @param int $user_id
	 * @return int foretak-ID, eller 0.
	 */
	public static function detect_link( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return 0;
		}

		// 1. Kjent nøkkel fra tidligere.
		$kjent = (string) get_option( 'bvp_link_meta_key', '' );

		if ( '' !== $kjent && $kjent !== self::USER_FORETAK ) {
			$id = self::first_foretak_id( get_user_meta( $user_id, $kjent, true ) );

			if ( $id ) {
				return $id;
			}
		}

		// 2. Let gjennom brukerens meta - først nøkler som ser ut som foretaksfelt.
		$alle    = get_user_meta( $user_id );
		$hopp    = array( 'wp_user_level', 'user_level', 'session_tokens', 'primary_blog', 'source_domain' );
		$runder  = array( '/foretak|bedrift|firma|company|organisasjon/i', '//' );

		foreach ( $runder as $monster ) {
			foreach ( (array) $alle as $key => $verdier ) {
				if ( in_array( $key, $hopp, true ) || 0 === strpos( $key, 'bvp_' ) ) {
					continue;
				}

				if ( '//' !== $monster && ! preg_match( $monster, $key ) ) {
					continue;
				}

				foreach ( (array) $verdier as $verdi ) {
					$id = self::first_foretak_id( maybe_unserialize( $verdi ) );

					if ( $id ) {
						update_option( 'bvp_link_meta_key', $key, false );
						return $id;
					}
				}
			}
		}

		return 0;
	}

	/**
	 * Plukk ut en foretaks-ID av en metaverdi (tall, streng eller liste).
	 *
	 * @param mixed $verdi
	 * @return int
	 */
	protected static function first_foretak_id( $verdi ) {
		if ( is_array( $verdi ) ) {
			foreach ( $verdi as $del ) {
				$id = self::first_foretak_id( $del );

				if ( $id ) {
					return $id;
				}
			}

			return 0;
		}

		if ( is_object( $verdi ) && isset( $verdi->ID ) ) {
			$verdi = $verdi->ID;
		}

		if ( ! is_scalar( $verdi ) ) {
			return 0;
		}

		$verdi = trim( (string) $verdi );

		if ( '' === $verdi || ! ctype_digit( $verdi ) ) {
			return 0;
		}

		$id = (int) $verdi;

		return ( $id && self::is_foretak( $id ) ) ? $id : 0;
	}

	/**
	 * Finn persontypen slik den er registrert andre steder på nettstedet.
	 *
	 * @param int $user_id
	 * @return string tom streng hvis ingenting er funnet.
	 */
	public static function detect_persontype( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return '';
		}

		$typer = self::persontyper();
		$alle  = get_user_meta( $user_id );

		foreach ( (array) $alle as $key => $verdier ) {
			if ( 0 === strpos( $key, 'bvp_' ) ) {
				continue;
			}

			foreach ( (array) $verdier as $verdi ) {
				if ( ! is_scalar( $verdi ) ) {
					continue;
				}

				$vasket = sanitize_key( str_replace( array( ' ', '-' ), '', (string) $verdi ) );

				if ( isset( $typer[ $vasket ] ) ) {
					return $vasket;
				}
			}
		}

		return '';
	}

	/**
	 * Foretaket brukeren er koblet til.
	 *
	 * @param int $user_id
	 * @return int post-ID, eller 0.
	 */
	public static function for_user( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return 0;
		}

		if ( isset( self::$cache[ $user_id ] ) ) {
			return self::$cache[ $user_id ];
		}

		$id = (int) get_user_meta( $user_id, self::USER_FORETAK, true );

		if ( ! $id || ! self::is_foretak( $id ) ) {
			// Ingen egen kobling - se om nettstedet allerede har koblet brukeren til et foretak.
			$id = self::detect_link( $user_id );
		}

		self::$cache[ $user_id ] = $id;

		return $id;
	}

	/**
	 * Persontypen til brukeren.
	 *
	 * @param int $user_id
	 * @return string
	 */
	public static function persontype( $user_id ) {
		$user_id = (int) $user_id;
		$type    = (string) get_user_meta( $user_id, self::USER_TYPE, true );

		if ( isset( self::persontyper()[ $type ] ) ) {
			return $type;
		}

		$funnet = self::detect_persontype( $user_id );

		if ( '' !== $funnet ) {
			return $funnet;
		}

		/**
		 * Persontype for brukere som er koblet til et foretak andre steder på
		 * nettstedet, men uten at persontypen er kjent.
		 *
		 * @param string $standard
		 * @param int    $user_id
		 */
		return apply_filters( 'bvp_ukjent_persontype', 'tilleggskontakt', $user_id );
	}

	/**
	 * Koble en bruker til et foretak.
	 *
	 * @param int    $user_id
	 * @param int    $foretak_id 0 = fjern koblingen.
	 * @param string $persontype Tom = behold/gjett ut fra e-postdomene.
	 */
	public static function link_user( $user_id, $foretak_id, $persontype = '' ) {
		$user_id    = (int) $user_id;
		$foretak_id = (int) $foretak_id;

		if ( ! $user_id ) {
			return;
		}

		unset( self::$cache[ $user_id ] );

		if ( ! $foretak_id ) {
			delete_user_meta( $user_id, self::USER_FORETAK );
			delete_user_meta( $user_id, self::USER_TYPE );

			foreach ( self::nettstedets_nokler() as $nokkel ) {
				delete_user_meta( $user_id, $nokkel );
			}

			return;
		}

		if ( ! self::is_foretak( $foretak_id ) ) {
			return;
		}

		update_user_meta( $user_id, self::USER_FORETAK, $foretak_id );

		// Nettstedets egne nøkler, pluss den som alt er i bruk her.
		$nokler = self::nettstedets_nokler();
		$kjent  = (string) get_option( 'bvp_link_meta_key', '' );

		if ( '' !== $kjent && self::USER_FORETAK !== $kjent && ! in_array( $kjent, $nokler, true ) ) {
			$nokler[] = $kjent;
		}

		foreach ( $nokler as $nokkel ) {
			update_user_meta( $user_id, $nokkel, $foretak_id );
		}

		// Kontoen regnes som foretakskonto.
		$kontotype = self::kontotype_nokkel();

		if ( $kontotype ) {
			update_user_meta( $user_id, $kontotype, self::kontotype_verdi() );
		}

		if ( '' === $persontype ) {
			$eksisterende = (string) get_user_meta( $user_id, self::USER_TYPE, true );

			if ( '' === $eksisterende ) {
				$eksisterende = (string) self::detect_persontype( $user_id );
			}

			// Hovedkontakt og tilleggskontakt står. «medlem» og tomme verdier
			// løftes til tilleggskontakt når brukeren kobles til et foretak.
			if ( ! in_array( $eksisterende, self::svake_typer(), true ) ) {
				return;
			}

			$persontype = 'tilleggskontakt';
		}

		if ( isset( self::persontyper()[ $persontype ] ) ) {
			update_user_meta( $user_id, self::USER_TYPE, $persontype );

			/**
			 * Brukeren er koblet til et foretak.
			 *
			 * @param int    $user_id
			 * @param int    $foretak_id
			 * @param string $persontype
			 */
			do_action( 'bvp_bruker_koblet', $user_id, $foretak_id, $persontype );
		}
	}

	/**
	 * Har brukeren en e-postadresse på et av foretakets domener?
	 *
	 * @param int $user_id
	 * @param int $foretak_id
	 * @return bool
	 */
	public static function domain_matches( $user_id, $foretak_id ) {
		$user = get_userdata( (int) $user_id );

		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		$domene = strtolower( substr( strrchr( $user->user_email, '@' ), 1 ) );

		foreach ( self::domener( $foretak_id ) as $kandidat ) {
			if ( $domene === $kandidat ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Foretakets e-postdomener.
	 *
	 * @param int $foretak_id
	 * @return array
	 */
	public static function orgnr( $foretak_id ) {
		$foretak_id = (int) $foretak_id;
		$orgnr      = (string) get_post_meta( $foretak_id, self::META_ORGNR, true );

		if ( '' !== $orgnr ) {
			return $orgnr;
		}

		foreach ( array( 'foretak_organisasjonsnummer', 'organisasjonsnummer', 'orgnr', 'org_nr' ) as $kandidat ) {
			$verdi = get_post_meta( $foretak_id, $kandidat, true );

			if ( is_scalar( $verdi ) && '' !== trim( (string) $verdi ) ) {
				return (string) $verdi;
			}
		}

		return '';
	}

	public static function domener( $foretak_id ) {
		$raw = (string) get_post_meta( (int) $foretak_id, self::META_DOMENER, true );

		if ( '' === trim( $raw ) ) {
			foreach ( array( 'foretak_hoveddomene', 'hoveddomene', 'domene' ) as $kandidat ) {
				$verdi = get_post_meta( (int) $foretak_id, $kandidat, true );

				if ( is_scalar( $verdi ) && '' !== trim( (string) $verdi ) ) {
					$raw = (string) $verdi;
					break;
				}
			}
		}

		return array_values(
			array_filter(
				array_map(
					static function ( $d ) {
						return strtolower( trim( ltrim( trim( $d ), '@' ) ) );
					},
					preg_split( '/[,\s]+/', $raw )
				)
			)
		);
	}

	/**
	 * Alle brukere koblet til foretaket.
	 *
	 * @param int   $foretak_id
	 * @param array $typer Begrens til disse persontypene.
	 * @return WP_User[]
	 */
	public static function members( $foretak_id, $typer = array() ) {
		$foretak_id = (int) $foretak_id;

		if ( ! $foretak_id ) {
			return array();
		}

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => self::USER_FORETAK,
				'value' => $foretak_id,
			),
		);

		// Nettstedets egen kobling, hvis vi har funnet hvilken nøkkel den bruker.
		$ekstern = (string) get_option( 'bvp_link_meta_key', '' );

		if ( '' !== $ekstern && self::USER_FORETAK !== $ekstern ) {
			$meta_query[] = array(
				'key'   => $ekstern,
				'value' => $foretak_id,
			);
			$meta_query[] = array(
				'key'     => $ekstern,
				'value'   => '"' . $foretak_id . '"',
				'compare' => 'LIKE',
			);
		}

		$users = get_users(
			array(
				'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'number'     => 500,
			)
		);

		if ( empty( $typer ) ) {
			return $users;
		}

		return array_values(
			array_filter(
				$users,
				static function ( $user ) use ( $typer ) {
					return in_array( self::persontype( $user->ID ), $typer, true );
				}
			)
		);
	}

	/**
	 * Foretakets hovedkontakt og tilleggskontakter.
	 *
	 * @param int $foretak_id
	 * @return WP_User[]
	 */
	public static function contacts( $foretak_id ) {
		return self::members( $foretak_id, self::kontakttyper() );
	}

	/**
	 * Alle foretak med profil.
	 *
	 * @return WP_Post[]
	 */
	public static function all() {
		return get_posts(
			array(
				'post_type'      => self::post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}

	/**
	 * Navnet på et foretak.
	 *
	 * @param int $foretak_id
	 * @return string
	 */
	public static function name( $foretak_id ) {
		$foretak_id = (int) $foretak_id;

		return ( $foretak_id && self::is_foretak( $foretak_id ) ) ? (string) get_the_title( $foretak_id ) : '';
	}

	/**
	 * Søk etter foretak på navn eller organisasjonsnummer.
	 *
	 * @param string $q
	 * @param int    $limit
	 * @return array [ id, navn, orgnr ]
	 */
	public static function search( $q, $limit = 15 ) {
		$q = trim( (string) $q );

		if ( '' === $q ) {
			return array();
		}

		$treff = array();
		$sett  = array();

		$legg_til = static function ( $post_id ) use ( &$treff, &$sett, $limit ) {
			$post_id = (int) $post_id;

			if ( isset( $sett[ $post_id ] ) || count( $treff ) >= $limit ) {
				return;
			}

			$sett[ $post_id ] = true;
			$treff[]          = array(
				'id'    => $post_id,
				'navn'  => (string) get_the_title( $post_id ),
				'orgnr' => self::orgnr( $post_id ),
			);
		};

		// 1. Søk i databasen - tåler mange foretak.
		if ( class_exists( 'WP_Query' ) ) {
			$query = new WP_Query(
				array(
					'post_type'           => self::post_types(),
					'post_status'         => 'publish',
					'posts_per_page'      => 50,
					's'                   => $q,
					'orderby'             => 'title',
					'order'               => 'ASC',
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'suppress_filters'    => false,
				)
			);

			foreach ( $query->posts as $post ) {
				if ( self::matcher( $post->post_title, $q ) ) {
					$legg_til( $post->ID );
				}
			}

			// Organisasjonsnummer.
			if ( count( $treff ) < $limit && ctype_digit( str_replace( ' ', '', $q ) ) ) {
				$meta_query = array( 'relation' => 'OR' );

				foreach ( array( self::META_ORGNR, 'foretak_organisasjonsnummer', 'organisasjonsnummer', 'orgnr', 'org_nr' ) as $nokkel ) {
					$meta_query[] = array(
						'key'     => $nokkel,
						'value'   => str_replace( ' ', '', $q ),
						'compare' => 'LIKE',
					);
				}

				$org = new WP_Query(
					array(
						'post_type'      => self::post_types(),
						'post_status'    => 'publish',
						'posts_per_page' => 20,
						'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery
						'no_found_rows'  => true,
					)
				);

				foreach ( $org->posts as $post ) {
					$legg_til( $post->ID );
				}
			}
		}

		// 2. Reserve: gå gjennom lista (små nettsteder, og som sikkerhetsnett).
		if ( empty( $treff ) ) {
			foreach ( self::all() as $foretak ) {
				if ( self::matcher( (string) $foretak->post_title, $q )
					|| ( '' !== self::orgnr( $foretak->ID ) && 0 === strpos( str_replace( ' ', '', self::orgnr( $foretak->ID ) ), str_replace( ' ', '', $q ) ) ) ) {
					$legg_til( $foretak->ID );
				}
			}
		}

		return $treff;
	}

	/**
	 * Treffer søket navnet? Begynnelsen av navnet eller av et ord i det.
	 *
	 * @param string $navn
	 * @param string $q
	 * @return bool
	 */
	protected static function matcher( $navn, $q ) {
		$navn = trim( (string) $navn );

		return 0 === stripos( $navn, $q ) || false !== stripos( ' ' . $navn, ' ' . $q );
	}

	/**
	 * Kontaktene i et foretak, klart for JSON.
	 *
	 * @param int $foretak_id
	 * @return array
	 */
	public static function contacts_payload( $foretak_id ) {
		$typer = self::persontyper();
		$ut    = array();

		foreach ( self::contacts( $foretak_id ) as $user ) {
			$type = self::persontype( $user->ID );

			$ut[] = array(
				'id'    => (int) $user->ID,
				'navn'  => $user->display_name,
				'type'  => isset( $typer[ $type ] ) ? $typer[ $type ] : $type,
			);
		}

		return $ut;
	}

	/**
	 * Alle registrerte brukere i et foretak, klart for JSON.
	 *
	 * @param int $foretak_id
	 * @return array
	 */
	public static function members_payload( $foretak_id ) {
		$typer = self::persontyper();
		$ut    = array();

		foreach ( self::members( $foretak_id ) as $user ) {
			$type = self::persontype( $user->ID );

			$ut[] = array(
				'id'   => (int) $user->ID,
				'navn' => $user->display_name,
				'type' => isset( $typer[ $type ] ) ? $typer[ $type ] : $type,
			);
		}

		return $ut;
	}

	/** Felles sjekk for AJAX-kallene. */
	protected static function ajax_guard() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'melding' => 'Du må være innlogget.' ), 403 );
		}

		check_ajax_referer( self::NONCE, 'nonce' );
	}

	public static function ajax_sok() {
		self::ajax_guard();

		$q = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		wp_send_json_success( array( 'treff' => self::search( $q ) ) );
	}

	public static function ajax_kontakter() {
		self::ajax_guard();

		$id = isset( $_POST['foretak'] ) ? absint( $_POST['foretak'] ) : 0;

		if ( ! $id || ! self::is_foretak( $id ) ) {
			wp_send_json_error( array( 'melding' => 'Ukjent foretak.' ), 404 );
		}

		wp_send_json_success(
			array(
				'id'        => $id,
				'navn'      => self::name( $id ),
				'orgnr'     => self::orgnr( $id ),
				'kontakter' => self::contacts_payload( $id ),
				'brukere'   => self::members_payload( $id ),
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* wp-admin                                                            */
	/* ------------------------------------------------------------------ */

	public static function meta_boxes() {
		add_meta_box( 'bvp-foretak-info', 'Foretaksprofil', array( __CLASS__, 'render_info_box' ), self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'bvp-foretak-medlemmer', 'Personer i foretaket', array( __CLASS__, 'render_members_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_info_box( $post ) {
		wp_nonce_field( self::NONCE, 'bvp_foretak_nonce' );
		$orgnr   = get_post_meta( $post->ID, self::META_ORGNR, true );
		$domener = get_post_meta( $post->ID, self::META_DOMENER, true );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bvp-orgnr">Organisasjonsnummer</label></th>
				<td><input type="text" class="regular-text" id="bvp-orgnr" name="bvp_orgnr" value="<?php echo esc_attr( $orgnr ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="bvp-domener">E-postdomener</label></th>
				<td>
					<input type="text" class="regular-text" id="bvp-domener" name="bvp_domener" value="<?php echo esc_attr( $domener ); ?>" placeholder="bimverdi.no, verdinettverk.no">
					<p class="description">Skill med komma. Nye brukere med disse domenene blir automatisk tilleggskontakt når de kobles til foretaket.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function render_members_box( $post ) {
		$medlemmer = self::members( $post->ID );
		$typer     = self::persontyper();
		?>
		<table class="widefat striped">
			<thead><tr><th>Person</th><th>E-post</th><th>Persontype</th><th>Fjern</th></tr></thead>
			<tbody>
			<?php if ( empty( $medlemmer ) ) : ?>
				<tr><td colspan="4">Ingen personer er koblet til dette foretaket ennå.</td></tr>
			<?php endif; ?>
			<?php foreach ( $medlemmer as $user ) : ?>
				<tr>
					<td><?php echo esc_html( $user->display_name ); ?></td>
					<td><?php echo esc_html( $user->user_email ); ?></td>
					<td>
						<select name="bvp_medlem[<?php echo (int) $user->ID; ?>]">
							<?php foreach ( $typer as $value => $etikett ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( self::persontype( $user->ID ), $value ); ?>><?php echo esc_html( $etikett ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td><label><input type="checkbox" name="bvp_fjern[]" value="<?php echo (int) $user->ID; ?>"> Fjern</label></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<label for="bvp-legg-til"><strong>Legg til personer</strong></label><br>
			<select id="bvp-legg-til" name="bvp_legg_til[]" multiple size="6" style="min-width:320px">
				<?php foreach ( self::unlinked_users( $post->ID ) as $user ) : ?>
					<option value="<?php echo (int) $user->ID; ?>"><?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description">Brukere som allerede er koblet til et annet foretak, vises ikke.</span>
		</p>
		<?php
	}

	/**
	 * Brukere uten foretak (eller koblet til dette).
	 *
	 * @param int $foretak_id
	 * @return WP_User[]
	 */
	protected static function unlinked_users( $foretak_id ) {
		$users = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'number'  => 500,
			)
		);

		return array_values(
			array_filter(
				$users,
				static function ( $user ) use ( $foretak_id ) {
					$koblet = self::for_user( $user->ID );
					return ! $koblet || (int) $koblet === (int) $foretak_id;
				}
			)
		);
	}

	public static function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['bvp_foretak_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_foretak_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::META_ORGNR, isset( $_POST['bvp_orgnr'] ) ? sanitize_text_field( wp_unslash( $_POST['bvp_orgnr'] ) ) : '' );
		update_post_meta( $post_id, self::META_DOMENER, isset( $_POST['bvp_domener'] ) ? sanitize_text_field( wp_unslash( $_POST['bvp_domener'] ) ) : '' );

		$fjern = isset( $_POST['bvp_fjern'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['bvp_fjern'] ) ) : array();

		if ( isset( $_POST['bvp_medlem'] ) && is_array( $_POST['bvp_medlem'] ) ) {
			foreach ( wp_unslash( $_POST['bvp_medlem'] ) as $user_id => $type ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$user_id = absint( $user_id );

				if ( in_array( $user_id, $fjern, true ) ) {
					continue;
				}

				self::link_user( $user_id, $post_id, sanitize_key( $type ) );
			}
		}

		foreach ( $fjern as $user_id ) {
			self::link_user( $user_id, 0 );
		}

		if ( isset( $_POST['bvp_legg_til'] ) ) {
			foreach ( array_map( 'absint', (array) wp_unslash( $_POST['bvp_legg_til'] ) ) as $user_id ) {
				self::link_user( $user_id, $post_id );
			}
		}
	}

	public static function columns( $columns ) {
		return array(
			'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'          => 'Foretak',
			'bvp_orgnr'      => 'Org.nr',
			'bvp_domener'    => 'E-postdomener',
			'bvp_kontakter'  => 'Kontakter',
			'bvp_medlemmer'  => 'Personer',
		);
	}

	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'bvp_orgnr':
				echo esc_html( self::orgnr( $post_id ) );
				break;

			case 'bvp_domener':
				echo esc_html( implode( ', ', self::domener( $post_id ) ) );
				break;

			case 'bvp_kontakter':
				$navn = array_map(
					static function ( $user ) {
						return $user->display_name;
					},
					self::contacts( $post_id )
				);
				echo $navn ? esc_html( implode( ', ', $navn ) ) : '&ndash;';
				break;

			case 'bvp_medlemmer':
				echo (int) count( self::members( $post_id ) );
				break;
		}
	}

	/**
	 * Felt på brukerprofilen i wp-admin.
	 *
	 * @param WP_User $user
	 */
	public static function user_fields( $user ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		?>
		<h2>BIM Verdi - foretak</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="bvp-user-foretak">Foretak</label></th>
				<td>
					<?php
					wp_nonce_field( self::NONCE, 'bvp_user_nonce' );
					$egen    = (int) get_user_meta( $user->ID, self::USER_FORETAK, true );
					$gjeldende = self::for_user( $user->ID );
					?>
					<select id="bvp-user-foretak" name="bvp_user_foretak">
						<option value="0">- ikke koblet -</option>
						<?php foreach ( self::all() as $foretak ) : ?>
							<option value="<?php echo (int) $foretak->ID; ?>" <?php selected( $gjeldende, $foretak->ID ); ?>><?php echo esc_html( $foretak->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( $gjeldende && ! $egen ) : ?>
						<p class="description">
							Koblingen er hentet fra nettstedets egen foretaksregistrering
							(<?php echo esc_html( get_post_type( $gjeldende ) ); ?> #<?php echo (int) $gjeldende; ?>).
							Lagrer du her, overstyrer du den for denne brukeren.
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="bvp-user-type">Persontype</label></th>
				<td>
					<select id="bvp-user-type" name="bvp_user_type">
						<?php foreach ( self::persontyper() as $value => $etikett ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( self::persontype( $user->ID ), $value ); ?>><?php echo esc_html( $etikett ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">Bare Hovedkontakt og Tilleggskontakt kan velges som tilleggskontakt på en registrering.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * @param int $user_id
	 */
	public static function save_user_fields( $user_id ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		if ( ! isset( $_POST['bvp_user_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_user_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		$foretak = isset( $_POST['bvp_user_foretak'] ) ? absint( $_POST['bvp_user_foretak'] ) : 0;
		$type    = isset( $_POST['bvp_user_type'] ) ? sanitize_key( wp_unslash( $_POST['bvp_user_type'] ) ) : '';

		self::link_user( $user_id, $foretak, $type );
	}
}
