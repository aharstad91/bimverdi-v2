<?php
/**
 * Feltdefinisjoner for BV-pilot/prosjektet.
 *
 * Én kilde til sannhet: brukes av frontend-skjema, admin-metaboks,
 * validering, e-post og CSV-eksport.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Fields {

	/**
	 * Datastrukturen (se BVP_Schema for standardoppsett og lagring).
	 *
	 * Seksjon: key, title, intro, audience ('user' | 'admin'), fields[]
	 * Felt:    key, label, type, required, options[], maxlength, desc,
	 *          depends ['field' => nøkkel, 'value' => verdi]
	 */

	/**
	 * Gjeldende seksjoner med felt (fra skjemaeditoren, ellers standardoppsettet).
	 *
	 * @return array
	 */
	public static function sections() {
		/**
		 * Filtrer feltdefinisjonene (legg til, fjern eller endre felt).
		 *
		 * @param array $sections
		 */
		return apply_filters( 'bvp_sections', BVP_Schema::get() );
	}

	/**
	 * Flat liste over alle felt, nøkkel => felt.
	 *
	 * @param string $audience 'all' | 'user' | 'admin'
	 * @return array
	 */
	public static function all( $audience = 'all', $include_hidden = false ) {
		$fields = array();

		foreach ( self::sections() as $section ) {
			if ( 'all' !== $audience && $section['audience'] !== $audience ) {
				continue;
			}
			if ( ! $include_hidden && ! empty( $section['hidden'] ) ) {
				continue;
			}
			foreach ( $section['fields'] as $field ) {
				if ( ! $include_hidden && ! empty( $field['hidden'] ) ) {
					continue;
				}
				$fields[ $field['key'] ] = $field;
			}
		}

		return $fields;
	}

	/** Felttypene som kan styre «Vis feltet bare hvis». */
	public static function depends_types() {
		return apply_filters( 'bvp_depends_types', array( 'radio', 'select', 'checkbox' ) );
	}

	/**
	 * Er betingelsen for feltet oppfylt?
	 *
	 * Radioknapp og nedtrekksliste har én verdi; avkryssing har flere, og
	 * da holder det at den forventede verdien er én av dem.
	 *
	 * @param array $field
	 * @param array $values Nøkkel => verdi for registreringen.
	 * @return bool
	 */
	public static function depends_met( $field, $values ) {
		if ( empty( $field['depends']['field'] ) ) {
			return true;
		}

		$key      = $field['depends']['field'];
		$forventet = (string) ( $field['depends']['value'] ?? '' );
		$verdi    = array_key_exists( $key, (array) $values ) ? $values[ $key ] : '';

		if ( is_array( $verdi ) ) {
			foreach ( $verdi as $enkelt ) {
				if ( is_scalar( $enkelt ) && (string) $enkelt === $forventet ) {
					return true;
				}
			}

			return false;
		}

		return (string) $verdi === $forventet;
	}

	/**
	 * Feltet som gir registreringen tittel i oversiktene.
	 *
	 * Rekkefølgen: feltet som er krysset av i skjemaeditoren, ellers det
	 * gamle «soknadstittel», ellers første tekstfelt som ser ut som et navn
	 * eller en tittel.
	 *
	 * @return string Feltnøkkel, eller tom streng.
	 */
	public static function title_key() {
		$alle    = self::all( 'all', true );
		$kandidat = '';

		foreach ( $alle as $key => $field ) {
			if ( ! empty( $field['tittel'] ) ) {
				return $key;
			}
		}

		if ( isset( $alle['soknadstittel'] ) ) {
			return 'soknadstittel';
		}

		foreach ( $alle as $key => $field ) {
			if ( ! in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
				continue;
			}

			if ( preg_match( '/(tittel|kort navn|navn på)/iu', (string) $field['label'] ) ) {
				$kandidat = $key;
				break;
			}
		}

		return $kandidat;
	}

	/**
	 * Skjulte felt (til lesevisning i wp-admin).
	 *
	 * @return array
	 */
	public static function hidden() {
		$hidden = array();

		foreach ( self::sections() as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( ! empty( $field['hidden'] ) || ! empty( $section['hidden'] ) ) {
					$hidden[ $field['key'] ] = $field;
				}
			}
		}

		return $hidden;
	}

	/**
	 * Hent ett felt.
	 *
	 * @param string $key
	 * @return array|null
	 */
	public static function get( $key ) {
		$all = self::all( 'all', true );
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Seksjoner en gitt målgruppe skal se.
	 *
	 * @param string $audience
	 * @return array
	 */
	public static function sections_for( $audience, $include_hidden = false ) {
		return array_values(
			array_filter(
				self::sections(),
				static function ( $section ) use ( $audience, $include_hidden ) {
					if ( $section['audience'] !== $audience ) {
						return false;
					}

					return $include_hidden || empty( $section['hidden'] );
				}
			)
		);
	}
}
