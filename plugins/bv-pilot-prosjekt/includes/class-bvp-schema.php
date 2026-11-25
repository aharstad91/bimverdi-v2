<?php
/**
 * Skjemadefinisjonen: standardoppsett, lagring, validering og import/eksport.
 *
 * Oppsettet redigeres i BVP_Editor og lagres i wp_options under bvp_form_schema.
 * Ligger ingenting der, brukes defaults() under.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Schema {

	const OPTION    = 'bvp_form_schema';

	/** Ting som ble endret under vasking, og som brukeren bør vite om. */
	protected static $advarsler = array();
	const ID_OPTION = 'bvp_neste_id';

	/** Felttyper editoren tilbyr: nøkkel => etikett. */
	public static function types() {
		return array(
			'text'     => 'Tekst (én linje)',
			'textarea' => 'Tekst (flere linjer)',
			'email'    => 'E-post',
			'url'      => 'Nettadresse',
			'number'   => 'Tall',
			'date'     => 'Dato',
			'select'   => 'Nedtrekksliste',
			'radio'    => 'Radioknapper (ett valg)',
			'checkbox' => 'Avkryssing (flere valg)',
			'file'     => 'Filopplasting (én fil)',
			'files'    => 'Filopplasting (flere bilder)',
			'lookup'   => 'Oppslag (kunnskapskilder, verktøy m.m.)',
		);
	}

	/** Typer som har svaralternativer. */
	public static function types_with_options() {
		return array( 'select', 'radio', 'checkbox' );
	}

	/** Typer som er filfelt. */
	public static function file_types() {
		return array( 'file', 'files' );
	}

	/**
	 * Gjeldende oppsett - lagret skjema hvis det finnes, ellers standard.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, null );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return self::defaults();
		}

		return self::normalize( $stored );
	}

	/** Er skjemaet tilpasset (lagret), eller kjører vi på standard? */
	public static function is_customized() {
		$stored = get_option( self::OPTION, null );
		return is_array( $stored ) && ! empty( $stored );
	}

	/**
	 * Lagre et oppsett.
	 *
	 * @param array $sections
	 * @return true|WP_Error
	 */
	public static function save( $sections ) {
		$normalized = self::normalize( $sections );
		$error      = self::validate( $normalized );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		update_option( self::OPTION, $normalized, false );
		update_option( 'bvp_schema_saved', (string) microtime( true ), false );

		return true;
	}

	/** Sett skjemaet tilbake til standardoppsettet. */
	public static function reset() {
		delete_option( self::OPTION );
		update_option( 'bvp_schema_saved', (string) microtime( true ), false );
	}

	/**
	 * Engangsoppdateringer av et lagret oppsett når pluginen oppgraderes.
	 *
	 * Endrer bare ledetekster som fortsatt står med den gamle ordlyden -
	 * har du skrevet din egen tekst, blir den stående.
	 */
	public static function maybe_upgrade() {
		self::maybe_assign_ids();
		self::maybe_refresh_titles();
		self::maybe_utvid_maks();

		$kjort = (string) get_option( 'bvp_schema_version', '' );

		if ( BVP_VERSION === $kjort ) {
			return;
		}

		update_option( 'bvp_schema_version', BVP_VERSION, false );

		if ( ! self::is_customized() ) {
			return;
		}

		$omdopte = array(
			'foretaksnavn' => array(
				'fra' => 'Foretaksnavn som (foreløpig) står som søker',
				'til' => 'Foretaksnavn som registrerer',
			),
		);

		$sections = self::get();
		$endret   = false;

		foreach ( $sections as $i => $section ) {
			foreach ( $section['fields'] as $j => $field ) {
				if ( isset( $omdopte[ $field['key'] ] ) && $omdopte[ $field['key'] ]['fra'] === $field['label'] ) {
					$sections[ $i ]['fields'][ $j ]['label'] = $omdopte[ $field['key'] ]['til'];
					$endret = true;
				}
			}
		}

		if ( $endret ) {
			self::save( $sections );
		}
	}

	/**
	 * Utvid tekstfelt med 700 tegns grense til 1500.
	 *
	 * Kjøres én gang. Har du satt en annen grense selv, står den.
	 */
	public static function maybe_utvid_maks() {
		if ( get_option( 'bvp_maks_1500' ) ) {
			return;
		}

		update_option( 'bvp_maks_1500', 1, false );

		if ( ! self::is_customized() ) {
			return;
		}

		$sections = self::get();
		$endret   = false;

		foreach ( $sections as $i => $section ) {
			foreach ( (array) $section['fields'] as $j => $field ) {
				if ( 700 === (int) ( $field['maxlength'] ?? 0 ) ) {
					$sections[ $i ]['fields'][ $j ]['maxlength'] = 1500;
					$endret = true;
				}
			}
		}

		if ( $endret ) {
			self::save( $sections );
		}
	}

	/**
	 * Etterfyll titler på registreringer som står uten.
	 *
	 * Kjøres én gang per pluginversjon, slik at eldre kladder også viser
	 * navnet fra tittelfeltet i oversiktene.
	 */
	public static function maybe_refresh_titles() {
		if ( (string) get_option( 'bvp_titler_oppdatert', '' ) === BVP_VERSION ) {
			return;
		}

		update_option( 'bvp_titler_oppdatert', BVP_VERSION, false );

		if ( class_exists( 'BVP_Repository' ) ) {
			BVP_Repository::refresh_titles();
		}
	}

	/**
	 * Gi alle felt og seksjoner et ID-nummer første gang, og lagre det.
	 *
	 * Kjøres én gang. Etterpå ligger ID-ene i oppsettet og blir med videre.
	 */
	public static function maybe_assign_ids() {
		if ( get_option( 'bvp_ids_tildelt' ) ) {
			return;
		}

		$sections = self::get();

		if ( self::missing_ids( $sections ) ) {
			self::save( $sections );
		}

		update_option( 'bvp_ids_tildelt', 1, false );
	}

	/**
	 * Advarsler fra siste normalize()/save().
	 *
	 * @return array
	 */
	public static function advarsler() {
		return self::$advarsler;
	}

	/**
	 * HTML-taggene hjelpeteksten kan bruke.
	 *
	 * @return array
	 */
	public static function desc_tags() {
		return apply_filters(
			'bvp_desc_tags',
			array(
				'a'      => array(
					'href'   => true,
					'title'  => true,
					'target' => true,
					'rel'    => true,
				),
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'br'     => array(),
				'code'   => array(),
				'small'  => array(),
			)
		);
	}

	/**
	 * Vask hjelpeteksten: enkel HTML er lov, slik at lenker blir klikkbare.
	 *
	 * @param string $tekst
	 * @return string
	 */
	public static function rens_hjelpetekst( $tekst ) {
		$tekst = wp_kses( (string) $tekst, self::desc_tags() );

		return trim( preg_replace( '/[\r\n]+/', ' ', $tekst ) );
	}

	/**
	 * Neste ledige ID-nummer. ID-en er en fast, unik referanse til feltet
	 * eller seksjonen, og følger med selv om nøkkel, ledetekst eller
	 * plassering endres.
	 *
	 * @param array $brukt ID-er som alt er i bruk i denne runden.
	 * @return int
	 */
	protected static function next_id( $brukt = array() ) {
		$neste = (int) get_option( self::ID_OPTION, 0 );
		$hoyest = $brukt ? max( array_map( 'intval', $brukt ) ) : 0;

		if ( $hoyest >= $neste ) {
			$neste = $hoyest;
		}

		$neste++;

		update_option( self::ID_OPTION, $neste, false );

		return $neste;
	}

	/**
	 * Mangler noe i oppsettet ID?
	 *
	 * @param array $sections
	 * @return bool
	 */
	public static function missing_ids( $sections ) {
		foreach ( (array) $sections as $section ) {
			if ( empty( $section['id'] ) ) {
				return true;
			}

			foreach ( (array) ( $section['fields'] ?? array() ) as $field ) {
				if ( empty( $field['id'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Vask et oppsett slik at alle nøkler og typer er gyldige.
	 *
	 * @param array $sections
	 * @return array
	 */
	public static function normalize( $sections ) {
		self::$advarsler = array();

		$types  = self::types();
		$clean  = array();
		$s_keys = array();
		$f_keys = array();
		$finnes  = array(); // ID-er som alt ligger i oppsettet.
		$tildelt = array(); // ID-er som er brukt opp i denne runden.

		foreach ( (array) $sections as $s_les ) {
			if ( ! empty( $s_les['id'] ) ) {
				$finnes[] = (int) $s_les['id'];
			}

			foreach ( (array) ( $s_les['fields'] ?? array() ) as $f_les ) {
				if ( ! empty( $f_les['id'] ) ) {
					$finnes[] = (int) $f_les['id'];
				}
			}
		}

		foreach ( (array) $sections as $section ) {
			if ( empty( $section['title'] ) && empty( $section['fields'] ) ) {
				continue;
			}

			$title = sanitize_text_field( (string) ( $section['title'] ?? '' ) );
			$key   = sanitize_key( (string) ( $section['key'] ?? '' ) );

			if ( '' === $key ) {
				$key = self::slug( $title, 'seksjon' );
			}

			$key = self::unique( $key, $s_keys );
			$s_keys[] = $key;

			$s_id = (int) ( $section['id'] ?? 0 );

			if ( $s_id <= 0 || in_array( $s_id, $tildelt, true ) ) {
				$s_id = self::next_id( array_merge( $finnes, $tildelt ) );
			}

			$tildelt[] = $s_id;

			$fields = array();

			foreach ( (array) ( $section['fields'] ?? array() ) as $field ) {
				$label = sanitize_text_field( (string) ( $field['label'] ?? '' ) );
				$type  = (string) ( $field['type'] ?? 'text' );

				if ( '' === $label || ! isset( $types[ $type ] ) ) {
					continue;
				}

				$f_key = sanitize_key( (string) ( $field['key'] ?? '' ) );

				if ( '' === $f_key ) {
					$f_key = self::slug( $label, 'felt' );
				}

				$f_key    = self::unique( $f_key, $f_keys );
				$f_keys[] = $f_key;

				$f_id = (int) ( $field['id'] ?? 0 );

				if ( $f_id <= 0 || in_array( $f_id, $tildelt, true ) ) {
					$f_id = self::next_id( array_merge( $finnes, $tildelt ) );
				}

				$tildelt[] = $f_id;

				$clean_field = array(
					'id'    => $f_id,
					'key'   => $f_key,
					'label' => $label,
					'type'  => $type,
				);

				if ( ! empty( $field['required'] ) && ! in_array( $type, self::file_types(), true ) ) {
					$clean_field['required'] = true;
				}

				if ( ! empty( $field['hidden'] ) ) {
					$clean_field['hidden'] = true;
					unset( $clean_field['required'] ); // Et skjult felt kan ikke være påkrevd.
				}

				// Feltet som gir registreringen tittel i oversiktene.
				if ( ! empty( $field['tittel'] ) && in_array( $type, array( 'text', 'textarea' ), true ) ) {
					$clean_field['tittel'] = true;
				}

				$desc = self::rens_hjelpetekst( (string) ( $field['desc'] ?? '' ) );
				if ( '' !== $desc ) {
					$clean_field['desc'] = $desc;
				}

				$maxlength = (int) ( $field['maxlength'] ?? 0 );
				if ( $maxlength > 0 && in_array( $type, array( 'text', 'textarea' ), true ) ) {
					$clean_field['maxlength'] = $maxlength;
				}

				if ( 'lookup' === $type ) {
					$kilde  = sanitize_key( (string) ( $field['kilde'] ?? '' ) );
					$kilder = BVP_Lookup::kilder();

					if ( $kilde && isset( $kilder[ $kilde ] ) ) {
						$clean_field['kilde'] = $kilde;
					}

					$meta_nokkel = sanitize_key( (string) ( $field['meta_nokkel'] ?? '' ) );
					$meta_verdi  = sanitize_text_field( (string) ( $field['meta_verdi'] ?? '' ) );

					if ( '' !== $meta_nokkel && '' !== $meta_verdi ) {
						$clean_field['meta_nokkel'] = $meta_nokkel;
						$clean_field['meta_verdi']  = $meta_verdi;
					}

					$kildetyper = isset( $field['kildetyper'] ) ? (array) $field['kildetyper'] : array();
					$kildetyper = array_values(
						array_intersect(
							array_map( 'sanitize_key', $kildetyper ),
							array_keys( BVP_Lookup::kildetyper() )
						)
					);

					if ( $kildetyper ) {
						$clean_field['kildetyper'] = $kildetyper;
					}

					$temagrupper = isset( $field['temagrupper'] ) ? array_map( 'absint', (array) $field['temagrupper'] ) : array();
					$temagrupper = array_values( array_filter( $temagrupper ) );

					if ( $temagrupper ) {
						$clean_field['temagrupper'] = $temagrupper;
					}

					$maks = (int) ( $field['maks'] ?? 0 );

					if ( $maks > 0 ) {
						$clean_field['maks'] = $maks;
					}

					$merker = BVP_Lookup::merker( $field );

					if ( $merker ) {
						$clean_field['merker'] = $merker;
					}
				}

				if ( in_array( $type, self::types_with_options(), true ) ) {
					$options = $field['options'] ?? array();

					if ( is_string( $options ) ) {
						$options = preg_split( '/\r\n|\r|\n/', $options );
					}

					$options = array_values(
						array_filter(
							array_map(
								static function ( $option ) {
									return sanitize_text_field( (string) $option );
								},
								(array) $options
							),
							static function ( $option ) {
								return '' !== $option;
							}
						)
					);

					if ( empty( $options ) ) {
						// Et valgfelt uten alternativer kan ikke vises. Vi beholder
						// feltet som tekstfelt i stedet for å slette det, og sier fra.
						$clean_field['type'] = 'text';

						self::$advarsler[] = sprintf(
							'«%s» mangler svaralternativer og ble lagret som tekstfelt. Legg inn ett alternativ per linje og lagre på nytt.',
							$label
						);
					} else {
						$clean_field['options'] = $options;
					}
				}

				$dep_field = sanitize_key( (string) ( $field['depends']['field'] ?? '' ) );
				$dep_value = sanitize_text_field( (string) ( $field['depends']['value'] ?? '' ) );

				if ( '' !== $dep_field && '' !== $dep_value ) {
					$clean_field['depends'] = array(
						'field' => $dep_field,
						'value' => $dep_value,
					);
				}

				$fields[] = $clean_field;
			}

			$clean_section = array(
				'id'       => $s_id,
				'key'      => $key,
				'title'    => '' !== $title ? $title : 'Uten tittel',
				'intro'    => sanitize_text_field( (string) ( $section['intro'] ?? '' ) ),
				'audience' => ( 'admin' === ( $section['audience'] ?? 'user' ) ) ? 'admin' : 'user',
				'fields'   => $fields,
			);

			if ( ! empty( $section['hidden'] ) ) {
				$clean_section['hidden'] = true;
			}

			$clean[] = $clean_section;
		}

		return $clean;
	}

	/**
	 * Sjekk at oppsettet henger sammen.
	 *
	 * @param array $sections
	 * @return true|WP_Error
	 */
	public static function validate( $sections ) {
		if ( empty( $sections ) ) {
			return new WP_Error( 'bvp_tomt', 'Skjemaet må ha minst én seksjon med minst ett felt.' );
		}

		$all_keys = array();
		$has_user = false;

		foreach ( $sections as $section ) {
			$synlige = array_filter(
				$section['fields'],
				static function ( $field ) {
					return empty( $field['hidden'] );
				}
			);

			if ( 'user' === $section['audience'] && empty( $section['hidden'] ) && ! empty( $synlige ) ) {
				$has_user = true;
			}
			foreach ( $section['fields'] as $field ) {
				$all_keys[] = $field['key'];
			}
		}

		if ( ! $has_user ) {
			return new WP_Error( 'bvp_ingen_brukerfelt', 'Minst én seksjon må være synlig for brukeren, ikke skjult, og ha minst ett synlig felt.' );
		}

		if ( empty( $all_keys ) ) {
			return new WP_Error( 'bvp_ingen_felt', 'Skjemaet må ha minst ett felt.' );
		}

		// Betingelser må peke på felt som finnes.
		foreach ( $sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( ! empty( $field['depends'] ) && ! in_array( $field['depends']['field'], $all_keys, true ) ) {
					return new WP_Error(
						'bvp_ugyldig_betingelse',
						sprintf( 'Feltet «%s» har en betingelse som peker på et felt som ikke finnes.', $field['label'] )
					);
				}
			}
		}

		return true;
	}

	/**
	 * Oppsettet som JSON.
	 *
	 * @return string
	 */
	public static function to_json() {
		return wp_json_encode( self::get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Les inn oppsett fra JSON.
	 *
	 * @param string $json
	 * @return true|WP_Error
	 */
	public static function from_json( $json ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'bvp_ugyldig_json', 'Filen er ikke gyldig JSON.' );
		}

		return self::save( $data );
	}

	/**
	 * Lag en nøkkel av en ledetekst.
	 *
	 * @param string $text
	 * @param string $fallback
	 * @return string
	 */
	protected static function slug( $text, $fallback ) {
		$slug = strtolower( remove_accents( (string) $text ) );
		$slug = preg_replace( '/[^a-z0-9]+/', '_', $slug );
		$slug = trim( (string) $slug, '_' );
		$slug = sanitize_key( substr( $slug, 0, 40 ) );
		$slug = trim( $slug, '_' );

		return '' !== $slug ? $slug : $fallback . '_' . substr( md5( $text . wp_rand() ), 0, 6 );
	}

	/**
	 * Gjør en nøkkel unik.
	 *
	 * @param string $key
	 * @param array  $taken
	 * @return string
	 */
	protected static function unique( $key, $taken ) {
		if ( ! in_array( $key, $taken, true ) ) {
			return $key;
		}

		$i = 2;
		while ( in_array( $key . '_' . $i, $taken, true ) ) {
			$i++;
		}

		return $key . '_' . $i;
	}

	public static function defaults() {
		$bae_typer = array(
			'Bestiller/byggherre',
			'Arkitekt/rådgiver',
			'Entreprenør/byggmester',
			'Byggevareprodusent',
			'Byggevarehandel',
			'Eiendom/drift',
			'Organisasjon, nettverk m.m.',
		);

		$sections = array(

			array(
				'key'      => 'registrant',
				'title'    => 'Registrert av',
				'audience' => 'user',
				'fields'   => array(
					array(
						'key'       => 'reg_fornavn',
						'label'     => 'Fornavn',
						'type'      => 'text',
						'required'  => true,
						'maxlength' => 100,
					),
					array(
						'key'       => 'reg_mellomnavn',
						'label'     => 'Mellomnavn',
						'type'      => 'text',
						'maxlength' => 100,
					),
					array(
						'key'       => 'reg_etternavn',
						'label'     => 'Etternavn',
						'type'      => 'text',
						'required'  => true,
						'maxlength' => 100,
					),
					array(
						'key'      => 'reg_epost',
						'label'    => 'E-post til den som registrerer',
						'type'     => 'email',
						'required' => true,
					),
					array(
						'key'     => 'reg_rolle',
						'label'   => 'Min rolle er innenfor',
						'type'    => 'checkbox',
						'desc'    => 'Velg gjerne flere.',
						'options' => array(
							'Administrasjon og ledelse',
							'Marked',
							'Utvikling',
							'Logistikk',
							'Digitalisering',
							'Forretningsutvikling',
							'Annet',
						),
					),
					array(
						'key'       => 'foretaksnavn',
						'label'     => 'Foretaksnavn som registrerer',
						'type'      => 'text',
						'required'  => true,
						'desc'      => 'Juridisk navn hvis mulig.',
						'maxlength' => 200,
					),
					array(
						'key'     => 'foretakstype',
						'label'   => 'Foretaket er en',
						'type'    => 'checkbox',
						'desc'    => 'Velg gjerne flere.',
						'options' => array(
							'Bestiller/byggherre',
							'Arkitekt/rådgiver',
							'Entreprenør/byggmester',
							'Byggevareprodusent',
							'Byggevarehandel',
							'Eiendom/drift',
							'Leverandør av digitale verktøy, innhold og løsninger',
							'Organisasjon, nettverk m.m.',
							'Utdanningsinstitusjon',
						),
					),
				),
			),

			array(
				'key'      => 'prosjekt',
				'title'    => 'Pilot/prosjekt',
				'audience' => 'user',
				'fields'   => array(
					array(
						'key'       => 'soknadstittel',
						'label'     => 'Tittel på pilot/prosjekt',
						'type'      => 'text',
						'required'  => true,
						'maxlength' => 200,
						'desc'      => 'Brukes som navn på registreringen i oversikten.',
					),
				),
			),

			array(
				'key'      => 'kunder',
				'title'    => 'Kundene du løser problemet for',
				'audience' => 'user',
				'fields'   => array(
					array(
						'key'      => 'kunde_sektor',
						'label'    => 'Hvem skal dere løse problemet for?',
						'type'     => 'checkbox',
						'required' => true,
						'desc'     => 'Velg alle som er aktuelle.',
						'options'  => array(
							'Privatpersoner',
							'Bedrifter',
							'Offentlige aktører',
							'Egen virksomhet',
							'Andre',
						),
					),
					array(
						'key'      => 'antall_kunder',
						'label'    => 'Hvor mange kunder opplever problemet?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Vet ikke', 'Noen få', 'Hundrevis', 'Tusenvis', 'Millioner' ),
					),
					array(
						'key'      => 'kontaktede_kunder',
						'label'    => 'Hvor mange potensielle kunder har dere vært i kontakt med?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Ingen', '1-9', '10-49', '50-499', '500+' ),
					),
					array(
						'key'      => 'kunde_bae_type',
						'label'    => 'Hvilke typer kunder skal dere løse problemet for?',
						'type'     => 'checkbox',
						'required' => true,
						'desc'     => 'Velg alle som er aktuelle.',
						'options'  => $bae_typer,
					),
					array(
						'key'      => 'betalingsvilje',
						'label'    => 'Har dere dokumentasjon på at kundene er villige til å betale for produktet eller tjenesten?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array(
							'Nei',
							'Kundene har sagt at de er villige til å betale.',
							'Kundene har forhåndsbestilt produktet.',
							'Kundene har forhåndsbetalt for produktet, helt eller delvis.',
							'Produktet er allerede i salg.',
						),
					),
					array(
						'key'       => 'problem',
						'label'     => 'Hvilket problem skal dere løse?',
						'type'      => 'textarea',
						'required'  => true,
						'maxlength' => 1000,
						'desc'      => 'Ikke beskriv hvordan dere skal løse det. Maks 1000 tegn.',
					),
				),
			),

			array(
				'key'      => 'marked',
				'title'    => 'Marked, konkurrenter og utfordringer',
				'audience' => 'user',
				'fields'   => array(
					array(
						'key'      => 'marked_geografi',
						'label'    => 'Hvor er markedet?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Innenfor vårt fylke', 'Norge', 'Skandinavia', 'Europa', 'Hele verden' ),
					),
					array(
						'key'      => 'antall_konkurrenter',
						'label'    => 'Hvor mange konkurrenter finnes?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Ingen', '1 - 9', '10 - 49', '50 +' ),
					),
					array(
						'key'      => 'miljoeffekt',
						'label'    => 'Hvilken effekt vil bedriften/løsningen ha på miljøet?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Positiv', 'Nøytral', 'Negativ' ),
					),
					array(
						'key'      => 'nyhetsgrad',
						'label'    => 'Hva er det ved deres løsning som skiller seg fra det som finnes i markedet i dag?',
						'type'     => 'checkbox',
						'required' => true,
						'desc'     => 'Velg alle som er aktuelle.',
						'options'  => array(
							'Utvikling av helt ny teknologi',
							'Ny/forbedret produksjonsprosess',
							'Ny/forbedret tjeneste',
							'Nytt/forbedret produkt',
							'Forretningsmodellen',
						),
					),
					array(
						'key'      => 'egne_midler',
						'label'    => 'Har bedriften egne midler den vil satse på utviklingen av løsningen?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array(
							'10-100 tusen kroner',
							'100-500 tusen kroner',
							'500-1000 tusen kroner',
							'Mer enn 1 mnok',
						),
					),
					array(
						'key'      => 'utfordringer',
						'label'    => 'Hva er utfordringen med å realisere idéen?',
						'type'     => 'checkbox',
						'required' => true,
						'desc'     => 'Velg alle som er aktuelle.',
						'options'  => array(
							'Trenger kapital utover det vi har tilgang til',
							'Trenger nye ferdigheter',
							'Krever ekstern kompetanse/erfaring',
							'Vanskelig å rekruttere de rette personene',
							'Teknisk krevende',
							'Produksjonsmessig krevende',
							'Tilgang til markedet',
							'Annet',
						),
					),
					array(
						'key'       => 'stikkord',
						'label'     => 'Stikkord eller tags for teknologi, målgrupper, tema etc.',
						'type'      => 'text',
						'maxlength' => 255,
						'desc'      => 'Skill med komma.',
					),
					array(
						'key'       => 'losning',
						'label'     => 'Hvordan skal dere løse problemet?',
						'type'      => 'textarea',
						'required'  => true,
						'maxlength' => 1000,
						'desc'      => 'Maks 1000 tegn.',
					),
					array(
						'key'       => 'nyhetsverdi',
						'label'     => 'Hva gjør din løsning til noe vesentlig nytt i markedet?',
						'type'      => 'textarea',
						'required'  => true,
						'maxlength' => 1000,
						'desc'      => 'Maks 1000 tegn.',
					),
					array(
						'key'       => 'forretningsmodell',
						'label'     => 'Hvordan vil bedriftens forretningsmodell se ut når ambisjonen er realisert?',
						'type'      => 'textarea',
						'required'  => true,
						'maxlength' => 800,
						'desc'      => 'Maks 800 tegn.',
					),
					array(
						'key'   => 'vedlegg_skisse',
						'label' => 'Skisse til løsning, forretningsmodell eller annet',
						'type'  => 'file',
						'desc'  => 'PDF, bilde eller kontordokument. Maks 20 MB.',
					),
				),
			),

			array(
				'key'      => 'teamet',
				'title'    => 'Teamet',
				'audience' => 'user',
				'fields'   => array(
					array(
						'key'      => 'eksterne_partnere',
						'label'    => 'Er dere avhengige av eksterne samarbeidspartnere for å realisere idéen?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( 'Nei', 'Ja' ),
					),
					array(
						'key'     => 'partner_bidrag',
						'label'   => 'Hvis ja, hva skal eksterne samarbeidspartnere bidra med?',
						'type'    => 'checkbox',
						'desc'    => 'Velg alle som er aktuelle.',
						'depends' => array(
							'field' => 'eksterne_partnere',
							'value' => 'Ja',
						),
						'options' => array(
							'Forskning og utvikling',
							'Produkt- og tjenesteutvikling',
							'Marked og skalering',
							'Kapital og kapitalinnhenting',
						),
					),
					array(
						'key'      => 'innsats',
						'label'    => 'Hvor mye innsats mener dere må til for å realisere idéen?',
						'type'     => 'radio',
						'required' => true,
						'options'  => array( '0 - 3 måneder', '4 - 6 måneder', '6 - 12 måneder', '1 - 2 år', '2 år eller lengre' ),
					),
					array(
						'key'       => 'team',
						'label'     => 'Hvem er med i teamet og hvilken kompetanse har dere?',
						'type'      => 'textarea',
						'required'  => true,
						'maxlength' => 600,
						'desc'      => 'Maks 600 tegn. Du kan lagre registreringen som kladd og legge inn dette senere, men det må være på plass før vi kan vurdere utkastet.',
					),
				),
			),

			array(
				'key'      => 'admin_tillegg',
				'title'    => 'Tillegg for administrator',
				'intro'    => 'Fylles ut av rådgiver i BIM Verdi.',
				'audience' => 'admin',
				'fields'   => array(
					array(
						'key'     => 'hovedmalgrupper',
						'label'   => 'Hovedmålgrupper for brukerhistorien',
						'type'    => 'checkbox',
						'desc'    => 'Velg alle som er aktuelle.',
						'options' => array(
							'Bestillere og byggherrer',
							'Prosjekterende (arkitekter, rådgivere)',
							'Utførende (entreprenører)',
							'Byggevareprodusenter og varehandel',
							'Eiendomsforvaltere',
							'Andre',
						),
					),
					array(
						'key'       => 'digital_losning',
						'label'     => 'Navn på digital løsning (produkt, tjeneste) som er sentral i brukerhistorien',
						'type'      => 'text',
						'maxlength' => 200,
					),
					array(
						'key'       => 'produsent',
						'label'     => 'Navn på produsent eller leverandør av den tekniske, digitale løsningen',
						'type'      => 'text',
						'maxlength' => 200,
					),
					array(
						'key'     => 'presentasjon_avklart',
						'label'   => 'Er det avklart med eier eller bruker at en representant derfra kan delta i en mulig presentasjon?',
						'type'    => 'radio',
						'options' => array(
							'Nei - ikke enda',
							'Ja - men det er ikke avklart hvilken person som skal delta i presentasjonen enda.',
							'Ja - og det er avklart hvem som kan presentere historien fra brukeren.',
						),
					),
					array(
						'key'   => 'link_produsent',
						'label' => 'Link til produsent, leverandør eller teknisk løsning',
						'type'  => 'url',
					),
					array(
						'key'   => 'link_info',
						'label' => 'Link til informasjon/presse om brukerhistorien etc.',
						'type'  => 'url',
					),
					array(
						'key'   => 'bilder',
						'label' => 'Bilder som kan illustrere prosjektet',
						'type'  => 'files',
						'desc'  => 'Kun bildefiler. Maks 20 MB per fil.',
					),
				),
			),

			array(
				'key'      => 'effekt',
				'title'    => 'Effektberegninger',
				'intro'    => 'Fylles ut av rådgiver i BIM Verdi.',
				'audience' => 'admin',
				'fields'   => array(
					array(
						'key'     => 'effekttyper',
						'label'   => 'Hvilke tallfestede økonomiske eller miljømessige gevinster kan brukeren fremskaffe?',
						'type'    => 'checkbox',
						'desc'    => 'Velg alle som er aktuelle.',
						'options' => array(
							'Salgsøkning',
							'Kostnadsreduksjon',
							'Økt produktivitet',
							'HMS',
							'CO2-effekt',
							'Annen, kvantifiserbar effekt',
						),
					),
					array(
						'key'   => 'effekt_samlet',
						'label' => 'Overordnet, samlet effektberegning - gjerne tallfestet',
						'type'  => 'textarea',
						'desc'  => 'Beskriv et konkret og reelt talleksempel på anvendelse av løsningen.',
					),
					array(
						'key'   => 'effekt_salg',
						'label' => 'Effekter på salg',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'effekt_kostnader',
						'label' => 'Effekter på kostnader',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'effekt_produktivitet',
						'label' => 'Effekter på produktivitet',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'effekt_hms',
						'label' => 'Effekter på HMS',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'effekt_miljo',
						'label' => 'Miljøeffekter, f.eks. CO2-utslipp',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'kommentar_radgiver',
						'label' => 'Kommentarer/meldinger til rådgiver',
						'type'  => 'textarea',
					),
					array(
						'key'     => 'send_til_in',
						'label'   => 'Kan prosjektskissen sendes til IN slik den er beskrevet?',
						'type'    => 'radio',
						'options' => array( 'Ja', 'Ikke enda' ),
					),
				),
			),
		);

		return $sections;
	}

}
