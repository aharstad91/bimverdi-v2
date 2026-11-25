<?php
/**
 * Lagring, sanitering og validering av BV-piloter/prosjekter.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Repository {

	/** Maks filstørrelse i byte (20 MB). */
	const MAX_UPLOAD = 20971520;

	/**
	 * Hent verdi for ett felt.
	 *
	 * @param int    $post_id
	 * @param string $key
	 * @return mixed streng, array (checkbox/files) eller int (file)
	 */
	public static function get_value( $post_id, $key ) {
		$field = BVP_Fields::get( $key );
		$value = get_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), true );

		if ( ! $field ) {
			return $value;
		}

		if ( in_array( $field['type'], array( 'checkbox', 'files', 'lookup' ), true ) ) {
			return is_array( $value ) ? $value : array();
		}

		return is_scalar( $value ) ? $value : '';
	}

	/**
	 * Alle verdier for ett pilot/prosjekt.
	 *
	 * @param int    $post_id
	 * @param string $audience
	 * @return array
	 */
	/**
	 * Feltet som kobler registreringen til et prosjekt
	 * («Hvilket prosjekt hører caset til?» i seksjonen Prosjekt-kobling).
	 *
	 * @return string Feltnøkkel, eller tom streng.
	 */
	public static function prosjekt_key() {
		$typer    = array( 'checkbox', 'radio', 'select' );
		$kandidat = '';

		foreach ( BVP_Fields::sections() as $section ) {
			$i_seksjon = (bool) preg_match( '/prosjekt.?kobling/iu', (string) ( $section['title'] ?? '' ) );

			foreach ( (array) ( $section['fields'] ?? array() ) as $field ) {
				if ( empty( $field['key'] ) || ! in_array( $field['type'] ?? '', $typer, true ) ) {
					continue;
				}

				if ( preg_match( '/hvilke[tn]? prosjekt/iu', (string) ( $field['label'] ?? '' ) ) ) {
					return (string) apply_filters( 'bvp_prosjekt_felt', $field['key'] );
				}

				if ( $i_seksjon && '' === $kandidat ) {
					$kandidat = $field['key'];
				}
			}
		}

		return (string) apply_filters( 'bvp_prosjekt_felt', $kandidat );
	}

	/**
	 * Prosjektene registreringen er koblet til.
	 *
	 * @param int|WP_Post $post
	 * @return string[]
	 */
	public static function prosjekter( $post ) {
		$post = get_post( $post );
		$key  = self::prosjekt_key();

		if ( ! $post || '' === $key ) {
			return array();
		}

		$verdi = self::get_value( (int) $post->ID, $key );

		return array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $verdi ) ), 'strlen' ) );
	}

	/** Prosjektene som tekst, f.eks. «ByggChat, Skann til BIM for transformasjon». */
	public static function prosjekt_tekst( $post ) {
		return implode( ', ', self::prosjekter( $post ) );
	}

	/** Prosjektene som HTML til tabeller. */
	public static function prosjekt_html( $post ) {
		$liste = self::prosjekter( $post );

		if ( ! $liste ) {
			return '<span class="bvp-desc">&ndash;</span>';
		}

		return implode(
			' ',
			array_map(
				static function ( $navn ) {
					return '<span class="bvp-prosjekt">' . esc_html( $navn ) . '</span>';
				},
				$liste
			)
		);
	}

	public static function get_values( $post_id, $audience = 'all' ) {
		$values = array();
		foreach ( BVP_Fields::all( $audience ) as $key => $field ) {
			$values[ $key ] = self::get_value( $post_id, $key );
		}
		return $values;
	}

	/**
	 * Saniter innsendte verdier for én målgruppe.
	 *
	 * @param array  $raw      Rådata ($_POST['bvp']).
	 * @param string $audience 'user' | 'admin' | 'all'
	 * @return array nøkkel => sanitert verdi
	 */
	/** Valg som ikke kunne lagres i siste sanitize(). */
	protected static $avviste = array();

	/**
	 * Valgene som ble forkastet sist, med felt, tittel og grunn.
	 *
	 * @return array
	 */
	public static function avviste() {
		return self::$avviste;
	}

	public static function sanitize( $raw, $audience = 'user' ) {
		self::$avviste = array();

		$clean  = array();
		$fields = BVP_Fields::all( $audience );

		foreach ( $fields as $key => $field ) {
			if ( in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				continue; // Filer håndteres separat.
			}

			$value = isset( $raw[ $key ] ) ? $raw[ $key ] : '';

			switch ( $field['type'] ) {
				case 'lookup':
					$value = BVP_Lookup::sanitize_value( $value, $field );

					foreach ( BVP_Lookup::avviste() as $avvist ) {
						$avvist['felt']    = $field['label'];
						self::$avviste[]   = $avvist;
					}
					break;

				case 'checkbox':
					$options = isset( $field['options'] ) ? $field['options'] : array();
					$value   = is_array( $value ) ? $value : array();
					$value   = array_values(
						array_intersect(
							array_map( 'wp_unslash', array_map( 'strval', $value ) ),
							$options
						)
					);
					break;

				case 'radio':
				case 'select':
					$options = isset( $field['options'] ) ? $field['options'] : array();
					$value   = wp_unslash( (string) $value );
					$value   = in_array( $value, $options, true ) ? $value : '';
					break;

				case 'number':
					$value = trim( wp_unslash( (string) $value ) );
					$value = is_numeric( $value ) ? $value : '';
					break;

				case 'date':
					$value = trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
					$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
					break;

				case 'email':
					$value = sanitize_email( wp_unslash( (string) $value ) );
					break;

				case 'url':
					$value = esc_url_raw( wp_unslash( (string) $value ) );
					break;

				case 'textarea':
					$value = sanitize_textarea_field( wp_unslash( (string) $value ) );
					if ( ! empty( $field['maxlength'] ) ) {
						$value = mb_substr( $value, 0, (int) $field['maxlength'] );
					}
					break;

				default:
					$value = sanitize_text_field( wp_unslash( (string) $value ) );
					if ( ! empty( $field['maxlength'] ) ) {
						$value = mb_substr( $value, 0, (int) $field['maxlength'] );
					}
					break;
			}

			$clean[ $key ] = $value;
		}

		return $clean;
	}

	/**
	 * Valider verdier. Påkrevde felt håndheves bare ved innsending.
	 *
	 * @param array  $values
	 * @param bool   $is_submit true ved innsending, false ved kladd.
	 * @param string $audience
	 * @return array feltnøkkel => feilmelding
	 */
	public static function validate( $values, $is_submit, $audience = 'user' ) {
		$errors = array();
		$fields = BVP_Fields::all( $audience );

		foreach ( $fields as $key => $field ) {
			if ( in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				continue;
			}

			$value = isset( $values[ $key ] ) ? $values[ $key ] : '';
			$empty = is_array( $value ) ? empty( $value ) : ( '' === trim( (string) $value ) );

			// Betingede felt valideres bare når betingelsen er oppfylt.
			if ( ! BVP_Fields::depends_met( $field, $values ) ) {
				continue;
			}

			if ( $is_submit && ! empty( $field['required'] ) && $empty ) {
				$errors[ $key ] = sprintf( '«%s» må fylles ut.', $field['label'] );
				continue;
			}

			if ( ! $empty && 'email' === $field['type'] && ! is_email( $value ) ) {
				$errors[ $key ] = sprintf( '«%s» er ikke en gyldig e-postadresse.', $field['label'] );
			}

			if ( ! $empty && 'url' === $field['type'] && ! wp_http_validate_url( $value ) ) {
				$errors[ $key ] = sprintf( '«%s» er ikke en gyldig nettadresse.', $field['label'] );
			}

			if ( ! $empty && 'number' === $field['type'] && ! is_numeric( $value ) ) {
				$errors[ $key ] = sprintf( '«%s» må være et tall.', $field['label'] );
			}
		}

		return $errors;
	}

	/**
	 * Opprett eller oppdater et pilot/prosjekt.
	 *
	 * @param int    $post_id  0 for ny.
	 * @param array  $values   Saniterte verdier.
	 * @param bool   $is_submit Innsending (publish) eller kladd (draft).
	 * @param int    $user_id  Eier av registreringen.
	 * @param string $audience Hvilke felt som skrives.
	 * @return int|WP_Error post-ID.
	 */
	public static function save( $post_id, $values, $is_submit, $user_id = 0, $audience = 'user' ) {
		$user_id       = $user_id ? (int) $user_id : get_current_user_id();
		$was_new       = ! $post_id;
		$var_publisert = $post_id && 'publish' === get_post_status( $post_id );

		$title = self::make_title( $values, $post_id );

		$postarr = array(
			'post_type'   => BVP_Post_Type::POST_TYPE,
			'post_title'  => $title,
			'post_status' => $is_submit ? 'publish' : 'draft',
			'post_author' => $user_id,
		);

		if ( $post_id ) {
			$postarr['ID'] = $post_id;

			// Eierskapet skal aldri flytte seg fordi en medredaktør eller
			// administrator lagrer. Det endres bare med eiervelgeren.
			unset( $postarr['post_author'] );

			// Nytt navn på en publisert registrering gir ny adresse.
			// WordPress sender den gamle adressen videre til den nye.
			$naverende = get_post( $post_id );
			if ( $naverende && 'publish' === $naverende->post_status && (string) $naverende->post_title !== $title ) {
				$postarr['post_name'] = sanitize_title( $title );
			}

			// Behold publisert status hvis registreringen allerede er sendt inn.
			if ( ! $is_submit && 'publish' === get_post_status( $post_id ) ) {
				$postarr['post_status'] = 'publish';
			}
			$result = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$post_id = (int) $result;

		$for_endring = array();
		$etter       = array();

		foreach ( BVP_Fields::all( $audience ) as $key => $field ) {
			if ( in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				continue;
			}
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			$for_endring[ $key ] = self::get_value( $post_id, $key );
			$etter[ $key ]       = $values[ $key ];

			update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), $values[ $key ] );
		}

		// Endringslogg: hvem endret hva.
		if ( class_exists( 'BVP_Logg' ) ) {
			$endringer = BVP_Logg::diff( $for_endring, $etter );

			if ( $endringer ) {
				BVP_Logg::registrer( $post_id, $endringer, $user_id, $was_new ? 'opprettet' : 'endring', $was_new ? 'Registreringen ble opprettet' : '' );
			} elseif ( $was_new ) {
				BVP_Logg::registrer( $post_id, array(), $user_id, 'opprettet', 'Registreringen ble opprettet' );
			}

			if ( $is_submit && ! $var_publisert ) {
				BVP_Logg::registrer( $post_id, array(), $user_id, 'status', 'Registreringen ble sendt inn' );
			}
		}

		if ( $is_submit && ! get_post_meta( $post_id, '_bvp_innsendt_dato', true ) ) {
			update_post_meta( $post_id, '_bvp_innsendt_dato', current_time( 'mysql' ) );
		}
		update_post_meta( $post_id, '_bvp_sist_endret_av', get_current_user_id() );

		if ( ! empty( $values['foretaksnavn'] ) ) {
			BVP_Access::remember_foretak( $user_id, $values['foretaksnavn'] );
		}

		/**
		 * Etter lagring av en BV-pilot/prosjekt.
		 *
		 * @param int   $post_id
		 * @param array $values
		 * @param bool  $is_submit
		 */
		do_action( 'bvp_after_save', $post_id, $values, $is_submit );

		return $post_id;
	}

	/**
	 * Prosjekteieren: foretaket som står bak registreringen.
	 *
	 * Først foretaksprofilen eieren er koblet til (den gir lenke til
	 * profilsiden), ellers foretaksnavnet som er skrevet i skjemaet.
	 *
	 * @param int|WP_Post $post
	 * @return array navn, lenke
	 */
	public static function foretak_for( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return array(
				'navn'  => '',
				'lenke' => '',
			);
		}

		$foretak_id = (int) BVP_Foretak::for_user( (int) $post->post_author );
		$navn       = $foretak_id ? (string) BVP_Foretak::name( $foretak_id ) : '';
		$lenke      = '';

		if ( $foretak_id && $navn ) {
			$type = get_post_type( $foretak_id );

			if ( $type && ( ! function_exists( 'is_post_type_viewable' ) || is_post_type_viewable( $type ) ) ) {
				$lenke = (string) get_permalink( $foretak_id );
			}
		}

		if ( '' === $navn ) {
			$navn = trim( (string) self::get_value( (int) $post->ID, 'foretaksnavn' ) );
		}

		return array(
			'navn'  => $navn,
			'lenke' => $lenke,
		);
	}

	/**
	 * Prosjekteier som HTML: navn, lenket til foretaksprofilen når den finnes.
	 *
	 * @param int|WP_Post $post
	 * @return string
	 */
	public static function foretak_html( $post ) {
		$foretak = self::foretak_for( $post );

		if ( '' === $foretak['navn'] ) {
			return '<span class="bvp-desc">&ndash;</span>';
		}

		if ( '' === $foretak['lenke'] ) {
			return esc_html( $foretak['navn'] );
		}

		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( $foretak['lenke'] ),
			esc_html( $foretak['navn'] )
		);
	}

	/**
	 * Synlighet som HTML-merke.
	 *
	 * @param int|WP_Post $post
	 * @return string
	 */
	public static function synlighet_html( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$merke = BVP_Access::visibility_badge( (int) $post->ID );

		return sprintf(
			'<span class="bvp-synlig bvp-synlig--%1$s" title="%2$s">%3$s</span>',
			esc_attr( $merke['nokkel'] ),
			esc_attr( $merke['tittel'] ),
			esc_html( $merke['etikett'] )
		);
	}

	/**
	 * Er betingelsen oppfylt for en lagret registrering?
	 *
	 * @param array $field
	 * @param int   $post_id
	 * @return bool
	 */
	public static function depends_met_for_post( $field, $post_id ) {
		if ( empty( $field['depends']['field'] ) ) {
			return true;
		}

		$key = $field['depends']['field'];

		return BVP_Fields::depends_met( $field, array( $key => self::get_value( (int) $post_id, $key ) ) );
	}

	/**
	 * Engangsreparasjon etter feilen der eierskapet flyttet seg til den
	 * som sist lagret (før 0.42.0).
	 *
	 * Ingenting slettes og ingen eier byttes. Er den som står som
	 * registrant (e-posten i skjemaet) en annen enn eieren, og ikke
	 * medredaktør, legges vedkommende til som medredaktør - så hen ser
	 * registreringen igjen. Administrator får en liste og avgjør resten.
	 */
	public static function maybe_reparer_eierskap() {
		if ( get_option( 'bvp_eierskap_reparert' ) ) {
			return;
		}

		update_option( 'bvp_eierskap_reparert', 1, false );

		$rapport = array();

		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 1000,
			)
		);

		foreach ( (array) $poster as $post ) {
			$epost = trim( (string) self::get_value( (int) $post->ID, 'reg_epost' ) );

			if ( '' === $epost || ! is_email( $epost ) ) {
				continue;
			}

			$registrant = get_user_by( 'email', $epost );

			if ( ! $registrant || (int) $registrant->ID === (int) $post->post_author ) {
				continue;
			}

			$kontakter = BVP_Access::contacts( (int) $post->ID );

			if ( ! in_array( (int) $registrant->ID, $kontakter, true ) ) {
				$kontakter[] = (int) $registrant->ID;
				update_post_meta( (int) $post->ID, BVP_Access::META_CONTACTS, array_values( array_unique( $kontakter ) ) );

				if ( class_exists( 'BVP_Logg' ) ) {
					BVP_Logg::registrer(
						(int) $post->ID,
						array(),
						0,
						'reparasjon',
						sprintf( '%s ble lagt til som medredaktør, fordi eierskapet hadde flyttet seg ved en feil.', $registrant->display_name )
					);
				}
			}

			$eier = get_userdata( (int) $post->post_author );

			$rapport[] = array(
				'id'         => (int) $post->ID,
				'tittel'     => (string) get_the_title( $post ),
				'registrant' => $registrant->display_name,
				'eier'       => $eier ? $eier->display_name : '(ukjent)',
			);
		}

		if ( $rapport ) {
			update_option( 'bvp_eierskap_rapport', $rapport, false );
		}
	}

	/** Teksten som vises når ingenting er fylt ut ennå. */
	const UTEN_TITTEL = 'BV-pilot/prosjekt uten tittel';

	/**
	 * Tittelen registreringen skal ha, hentet fra tittelfeltet i skjemaet
	 * (krysses av i skjemaeditoren - ellers brukes «soknadstittel» eller
	 * første felt som ser ut som et navn).
	 *
	 * @param array $values Verdiene som nå lagres.
	 * @param int   $post_id
	 * @return string
	 */
	public static function make_title( $values, $post_id = 0 ) {
		$key = BVP_Fields::title_key();

		if ( $key && isset( $values[ $key ] ) && '' !== trim( (string) $values[ $key ] ) ) {
			return self::kort( $values[ $key ] );
		}

		// Feltet var ikke med i det som ble sendt inn - se på det som alt er lagret.
		if ( $key && $post_id ) {
			$lagret = self::get_value( $post_id, $key );

			if ( is_scalar( $lagret ) && '' !== trim( (string) $lagret ) ) {
				return self::kort( $lagret );
			}
		}

		if ( $post_id ) {
			$gammel = (string) get_the_title( $post_id );

			if ( '' !== trim( $gammel ) ) {
				return $gammel;
			}
		}

		return self::UTEN_TITTEL;
	}

	/**
	 * Kort, ren tittel på én linje.
	 *
	 * @param string $tekst
	 * @return string
	 */
	protected static function kort( $tekst ) {
		$tekst = sanitize_text_field( (string) $tekst );
		$tekst = trim( preg_replace( '/\s+/u', ' ', $tekst ) );

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $tekst ) > 120 ) {
			$tekst = rtrim( mb_substr( $tekst, 0, 117 ) ) . '...';
		}

		return '' !== $tekst ? $tekst : self::UTEN_TITTEL;
	}

	/**
	 * Oppdater titlene på registreringer som mangler tittel.
	 *
	 * Brukes når tittelfeltet byttes i skjemaeditoren, og ved oppgradering,
	 * slik at eldre kladder også får navnet sitt fram i oversiktene.
	 *
	 * @param bool $bare_tomme Bare de som står uten tittel.
	 * @return int Antall oppdaterte.
	 */
	public static function refresh_titles( $bare_tomme = true ) {
		$key = BVP_Fields::title_key();

		if ( ! $key ) {
			return 0;
		}

		$poster = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'draft', 'publish', 'pending' ),
				'posts_per_page' => 500,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$antall = 0;

		foreach ( (array) $poster as $post ) {
			$gammel = (string) $post->post_title;

			if ( $bare_tomme && '' !== trim( $gammel ) && self::UTEN_TITTEL !== $gammel ) {
				continue;
			}

			$verdi = self::get_value( (int) $post->ID, $key );

			if ( ! is_scalar( $verdi ) || '' === trim( (string) $verdi ) ) {
				continue;
			}

			$ny = self::kort( $verdi );

			if ( $ny === $gammel ) {
				continue;
			}

			$endring = array(
				'ID'         => (int) $post->ID,
				'post_title' => $ny,
			);

			// Adressen følger navnet. WordPress sender den gamle adressen videre.
			if ( 'publish' === $post->post_status ) {
				$endring['post_name'] = sanitize_title( $ny );
			}

			wp_update_post( $endring );

			$antall++;
		}

		return $antall;
	}

	/**
	 * Legg en registrering i papirkurven.
	 *
	 * Vi sletter ikke for godt: innholdet kan hentes tilbake fra papirkurven
	 * i wp-admin. Meldinger og vedlegg følger med.
	 *
	 * @param int $post_id
	 * @param int $user_id Den som sletter. 0 = gjeldende bruker.
	 * @return true|WP_Error
	 */
	public static function trash( $post_id, $user_id = 0 ) {
		$post    = get_post( $post_id );
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $post || BVP_Post_Type::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'bvp_ukjent', 'Fant ingen registrering med den ID-en.' );
		}

		if ( ! BVP_Access::can_delete( $post, $user_id ) ) {
			return new WP_Error( 'bvp_ingen_tilgang', 'Du kan ikke slette denne registreringen.' );
		}

		if ( 'trash' === $post->post_status ) {
			return true;
		}

		if ( ! wp_trash_post( (int) $post->ID ) ) {
			return new WP_Error( 'bvp_slett_feilet', 'Klarte ikke å slette registreringen.' );
		}

		do_action( 'bvp_after_trash', (int) $post->ID, $user_id );

		return true;
	}

	/**
	 * Håndter opplastede filer for ett innlegg.
	 *
	 * @param array $files    $_FILES-struktur.
	 * @param int   $post_id
	 * @param string $audience
	 * @return array feilmeldinger
	 */
	public static function handle_uploads( $files, $post_id, $audience = 'user' ) {
		$errors = array();

		if ( empty( $files ) || ! is_array( $files ) ) {
			return $errors;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Deltakere (abonnenter) har normalt ikke upload_files. Gi retten midlertidig
		// mens de laster opp vedlegg til sitt eget pilot/prosjekt.
		$grant_upload = static function ( $allcaps ) {
			$allcaps['upload_files'] = true;
			return $allcaps;
		};

		$needs_grant = ! current_user_can( 'upload_files' ) && self::user_can_edit( $post_id );

		if ( $needs_grant ) {
			add_filter( 'user_has_cap', $grant_upload, 999 );
		}

		foreach ( BVP_Fields::all( $audience ) as $key => $field ) {
			if ( ! in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				continue;
			}

			$input = 'bvp_file_' . $key;
			if ( empty( $files[ $input ] ) ) {
				continue;
			}

			$entry    = $files[ $input ];
			$multiple = ( 'files' === $field['type'] );
			$names    = $multiple ? (array) $entry['name'] : array( $entry['name'] );
			$count    = count( $names );
			$ids      = $multiple ? (array) self::get_value( $post_id, $key ) : array();

			for ( $i = 0; $i < $count; $i++ ) {
				$file = $multiple
					? array(
						'name'     => $entry['name'][ $i ],
						'type'     => $entry['type'][ $i ],
						'tmp_name' => $entry['tmp_name'][ $i ],
						'error'    => $entry['error'][ $i ],
						'size'     => $entry['size'][ $i ],
					)
					: $entry;

				if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] || '' === $file['name'] ) {
					continue;
				}

				if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
					$errors[ $key ] = sprintf( 'Opplasting av «%s» feilet.', $field['label'] );
					continue;
				}

				if ( (int) $file['size'] > self::MAX_UPLOAD ) {
					$errors[ $key ] = sprintf( '«%s»: filen er større enn 20 MB.', $field['label'] );
					continue;
				}

				$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
				$ext   = $check['ext'] ? $check['ext'] : strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

				if ( ! in_array( $ext, self::allowed_ext( $field['type'] ), true ) ) {
					$errors[ $key ] = sprintf( '«%s»: filtypen .%s er ikke tillatt.', $field['label'], $ext );
					continue;
				}

				$_FILES[ $input . '_single' ] = $file;
				$attachment_id                = media_handle_upload( $input . '_single', $post_id );
				unset( $_FILES[ $input . '_single' ] );

				if ( is_wp_error( $attachment_id ) ) {
					$errors[ $key ] = sprintf( '«%s»: %s', $field['label'], $attachment_id->get_error_message() );
					continue;
				}

				if ( $multiple ) {
					$ids[] = (int) $attachment_id;
				} else {
					$old = (int) self::get_value( $post_id, $key );
					if ( $old && $old !== (int) $attachment_id ) {
						wp_delete_attachment( $old, true );
					}
					update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), (int) $attachment_id );
				}
			}

			if ( $multiple ) {
				update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), array_values( array_unique( array_map( 'intval', $ids ) ) ) );
			}
		}

		if ( $needs_grant ) {
			remove_filter( 'user_has_cap', $grant_upload, 999 );
		}

		return $errors;
	}

	/**
	 * Slett ett vedlegg fra et felt.
	 *
	 * @param int    $post_id
	 * @param string $key
	 * @param int    $attachment_id
	 */
	public static function remove_attachment( $post_id, $key, $attachment_id ) {
		$field = BVP_Fields::get( $key );
		if ( ! $field ) {
			return;
		}

		$attachment_id = (int) $attachment_id;
		$attachment    = get_post( $attachment_id );

		if ( ! $attachment || (int) $attachment->post_parent !== (int) $post_id ) {
			return;
		}

		if ( 'files' === $field['type'] ) {
			$ids = array_diff( (array) self::get_value( $post_id, $key ), array( $attachment_id ) );
			update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), array_values( array_map( 'intval', $ids ) ) );
		} else {
			delete_post_meta( $post_id, BVP_Post_Type::meta_key( $key ) );
		}

		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * Tillatte filendelser.
	 *
	 * @param string $type 'file' | 'files'
	 * @return array
	 */
	public static function allowed_ext( $type ) {
		$images = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );
		$docs   = array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'txt' );

		$allowed = ( 'files' === $type ) ? $images : array_merge( $images, $docs );

		/**
		 * Filtrer tillatte filendelser.
		 *
		 * @param array  $allowed
		 * @param string $type
		 */
		return apply_filters( 'bvp_allowed_ext', $allowed, $type );
	}

	/**
	 * Kan brukeren redigere denne registreringen?
	 *
	 * @param int|WP_Post $post
	 * @param int         $user_id
	 * @return bool
	 */
	public static function user_can_edit( $post, $user_id = 0 ) {
		return BVP_Access::can_edit( $post, $user_id );
	}

	/**
	 * Piloter/prosjekter eid av en bruker.
	 *
	 * @param int $user_id
	 * @return WP_Post[]
	 */
	public static function get_user_prosjekter( $user_id ) {
		return get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'author'         => (int) $user_id,
				'post_status'    => array( 'draft', 'publish', 'pending' ),
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
	}
}
