<?php
/**
 * Feltrendering (brukes både på frontend og i wp-admin).
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Renderer {

	/**
	 * Render en hel seksjon.
	 *
	 * @param array $section
	 * @param int   $post_id
	 * @param array $values  Verdier som skal vises (f.eks. innsendte ved valideringsfeil).
	 * @param array $errors
	 */
	public static function section( $section, $post_id, $values = array(), $errors = array() ) {
		$synlige = array_filter(
			$section['fields'],
			static function ( $field ) {
				return empty( $field['hidden'] );
			}
		);

		if ( empty( $synlige ) ) {
			return;
		}

		$anker = 'bvp-section-' . $section['key'];

		echo '<fieldset class="bvp-section" id="' . esc_attr( $anker ) . '">';
		echo '<legend class="bvp-section__title">' . esc_html( $section['title'] ) . '</legend>';

		// Lagre underveis, så ingenting går tapt i en lang seksjon.
		printf(
			'<button type="submit" name="bvp_submit" value="kladd" class="bvp-button bvp-button--secondary bvp-lagre-her" data-bvp-anker="%1$s" title="%2$s">Lagre som kladd</button>',
			esc_attr( $anker ),
			esc_attr( 'Lagrer hele skjemaet som kladd, og du blir stående her.' )
		);

		if ( ! empty( $section['intro'] ) ) {
			echo '<p class="bvp-section__intro">' . esc_html( $section['intro'] ) . '</p>';
		}

		foreach ( $section['fields'] as $field ) {
			if ( ! empty( $field['hidden'] ) ) {
				continue;
			}

			$key   = $field['key'];
			$value = array_key_exists( $key, $values ) ? $values[ $key ] : BVP_Repository::get_value( $post_id, $key );
			$error = isset( $errors[ $key ] ) ? $errors[ $key ] : '';
			self::field( $field, $value, $post_id, $error );
		}

		echo '</fieldset>';
	}

	/**
	 * Render ett felt.
	 *
	 * @param array  $field
	 * @param mixed  $value
	 * @param int    $post_id
	 * @param string $error
	 */
	public static function field( $field, $value, $post_id = 0, $error = '' ) {
		// Et felt kan ha byttet type etter at svaret ble lagret. Da viser vi
		// det lagrede svaret som tekst i stedet for «Array».
		if ( is_array( $value ) && ! in_array( $field['type'], array( 'checkbox', 'file', 'files', 'lookup' ), true ) ) {
			$value = implode( ', ', array_filter( $value, 'is_scalar' ) );
		}

		$key      = $field['key'];
		$id       = 'bvp-' . $key;
		$name     = 'bvp[' . $key . ']';
		$required = ! empty( $field['required'] );
		$classes  = array( 'bvp-field', 'bvp-field--' . $field['type'] );

		if ( $error ) {
			$classes[] = 'bvp-field--error';
		}

		$attrs = '';
		if ( ! empty( $field['depends'] ) ) {
			$attrs .= ' data-bvp-depends-field="' . esc_attr( $field['depends']['field'] ) . '"';
			$attrs .= ' data-bvp-depends-value="' . esc_attr( $field['depends']['value'] ) . '"';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput

		$label_tag = in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ? 'span' : 'label';
		printf(
			'<%1$s class="bvp-label"%2$s>%3$s%4$s</%1$s>',
			esc_attr( $label_tag ),
			'label' === $label_tag ? ' for="' . esc_attr( $id ) . '"' : '',
			esc_html( $field['label'] ),
			$required ? ' <span class="bvp-required" aria-hidden="true">*</span>' : ''
		);

		if ( ! empty( $field['desc'] ) ) {
			echo '<p class="bvp-hjelp" id="' . esc_attr( $id ) . '-desc">' . self::hjelpetekst( $field['desc'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		$describedby = ! empty( $field['desc'] ) ? ' aria-describedby="' . esc_attr( $id ) . '-desc"' : '';

		switch ( $field['type'] ) {

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="5"%3$s%4$s%5$s>%6$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					! empty( $field['maxlength'] ) ? ' maxlength="' . (int) $field['maxlength'] . '"' : '',
					$required ? ' data-bvp-required="1"' : '',
					$describedby, // phpcs:ignore WordPress.Security.EscapeOutput
					esc_textarea( (string) $value )
				);
				if ( ! empty( $field['maxlength'] ) ) {
					echo '<span class="bvp-counter" data-max="' . (int) $field['maxlength'] . '"></span>';
				}
				break;

			case 'lookup':
				self::lookup_field( $field, $value, $id, $name );
				break;

			case 'select':
				printf(
					'<select id="%1$s" name="%2$s" data-bvp-field="%5$s"%3$s%4$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required ? ' data-bvp-required="1"' : '',
					$describedby, // phpcs:ignore WordPress.Security.EscapeOutput
					esc_attr( $key )
				);
				echo '<option value="">- velg -</option>';
				foreach ( $field['options'] as $option ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $option ),
						selected( (string) $value, $option, false ),
						esc_html( $option )
					);
				}
				echo '</select>';
				break;

			case 'number':
			case 'date':
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s%6$s>',
					esc_attr( $field['type'] ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					$required ? ' data-bvp-required="1"' : '',
					$describedby // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;

			case 'radio':
				echo '<div class="bvp-options" role="radiogroup">';
				foreach ( $field['options'] as $i => $option ) {
					printf(
						'<label class="bvp-option"><input type="radio" id="%1$s-%2$d" name="%3$s" value="%4$s" %5$s data-bvp-field="%6$s"> <span>%7$s</span></label>',
						esc_attr( $id ),
						(int) $i,
						esc_attr( $name ),
						esc_attr( $option ),
						checked( (string) $value, $option, false ),
						esc_attr( $key ),
						esc_html( $option )
					);
				}
				echo '</div>';
				break;

			case 'checkbox':
				$selected = is_array( $value ) ? $value : array();
				echo '<div class="bvp-options">';
				foreach ( $field['options'] as $i => $option ) {
					printf(
						'<label class="bvp-option"><input type="checkbox" id="%1$s-%2$d" name="%3$s[]" value="%4$s" %5$s data-bvp-field="%7$s"> <span>%6$s</span></label>',
						esc_attr( $id ),
						(int) $i,
						esc_attr( $name ),
						esc_attr( $option ),
						checked( in_array( $option, $selected, true ), true, false ),
						esc_html( $option ),
						esc_attr( $key )
					);
				}
				echo '</div>';
				break;

			case 'file':
			case 'files':
				self::attachments( $field, $value, $post_id );
				printf(
					'<input type="file" id="%1$s" name="bvp_file_%2$s%3$s"%4$s accept="%5$s"%6$s>',
					esc_attr( $id ),
					esc_attr( $key ),
					'files' === $field['type'] ? '[]' : '',
					'files' === $field['type'] ? ' multiple' : '',
					esc_attr( '.' . implode( ',.', BVP_Repository::allowed_ext( $field['type'] ) ) ),
					$describedby // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;

			case 'email':
			case 'url':
			case 'text':
			default:
				$input_type = in_array( $field['type'], array( 'email', 'url' ), true ) ? $field['type'] : 'text';
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s%6$s%7$s>',
					esc_attr( $input_type ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					! empty( $field['maxlength'] ) ? ' maxlength="' . (int) $field['maxlength'] . '"' : '',
					$required ? ' data-bvp-required="1"' : '',
					$describedby // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;
		}

		if ( $error ) {
			echo '<p class="bvp-error">' . esc_html( $error ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Oppslagsfelt mot kunnskapskilder: søk, og de valgte kildene som liste.
	 *
	 * @param array  $field
	 * @param mixed  $value
	 * @param string $id
	 * @param string $name
	 */
	protected static function lookup_field( $field, $value, $id, $name ) {
		$valgte  = BVP_Lookup::items( is_array( $value ) ? $value : array(), $field );
		$merker  = BVP_Lookup::merker( $field );
		$typer   = BVP_Lookup::kildetyper();
		$etikett = BVP_Lookup::kilde_etikett( $field );
		$filter  = array();

		if ( BVP_Lookup::post_type() === BVP_Lookup::kilde( $field ) ) {
			foreach ( (array) ( $field['kildetyper'] ?? array() ) as $type ) {
				if ( isset( $typer[ $type ] ) ) {
					$filter[] = $typer[ $type ];
				}
			}
		}

		printf(
			'<div class="bvp-lookup" data-bvp-felt="%1$s" data-bvp-maks="%2$d" data-bvp-ajax="%3$s" data-bvp-nonce="%4$s" data-bvp-merker="%5$s">',
			esc_attr( $field['key'] ),
			(int) ( $field['maks'] ?? 0 ),
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( wp_create_nonce( BVP_Lookup::NONCE ) ),
			esc_attr( wp_json_encode( $merker ) )
		);

		echo '<p class="bvp-desc">Søker i: ' . esc_html( $etikett );

		if ( $filter ) {
			echo ' (' . esc_html( implode( ', ', $filter ) ) . ')';
		}

		echo '</p>';

		if ( $merker ) {
			echo '<p class="bvp-desc">Merk hvert valg: ' . esc_html( implode( ' og ', wp_list_pluck( $merker, 'navn' ) ) ) . '.</p>';
		}

		echo '<p class="bvp-desc">Begynn å skrive - lista snevres inn for hver bokstav. Velg med piltast og Enter, eller klikk.</p>';

		echo '<div class="bvp-combo">';
		printf(
			'<input type="text" id="%1$s" class="bvp-lookup-sok" autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" placeholder="%2$s">',
			esc_attr( $id ),
			esc_attr( 'Søk i ' . mb_strtolower( $etikett ) . ' ...' )
		);
		echo '<ul class="bvp-combo__list" role="listbox" hidden></ul>';
		echo '<p class="bvp-combo__status" role="status" aria-live="polite"></p>';
		echo '</div>';

		echo '<ul class="bvp-lookup__valgte">';

		foreach ( $valgte as $kilde ) {
			self::lookup_rad( $kilde, $name, $merker );
		}

		echo '</ul>';

		if ( empty( $valgte ) ) {
			echo '<p class="bvp-desc bvp-lookup__tom">Ingenting er valgt ennå.</p>';
		}

		echo '</div>';
	}

	/**
	 * Én valgt kilde, med én nedtrekksliste per merkegruppe.
	 *
	 * @param array  $kilde
	 * @param string $name
	 * @param array  $merker
	 */
	public static function lookup_rad( $kilde, $name, $merker = array() ) {
		$kilde_id = (int) $kilde['id'];
		$navn     = $name . '[' . $kilde_id . ']';
		$lenke    = isset( $kilde['lenke'] ) ? (string) $kilde['lenke'] : '';
		$tittel   = $lenke
			? sprintf(
				'<a class="bvp-lookup__tittel" href="%1$s" target="_blank" rel="noopener">%2$s</a>',
				esc_url( $lenke ),
				esc_html( $kilde['tittel'] )
			)
			: '<span class="bvp-lookup__tittel">' . esc_html( $kilde['tittel'] ) . '</span>';

		$velger = '';
		$satte  = isset( $kilde['merker'] ) ? (array) $kilde['merker'] : array();

		foreach ( $merker as $i => $gruppe ) {
			$valgt   = isset( $satte[ $gruppe['navn'] ] ) ? (string) $satte[ $gruppe['navn'] ] : '';
			$velger .= sprintf(
				'<select class="bvp-lookup__merke" name="%1$s" aria-label="%2$s"><option value="">- %3$s -</option>',
				esc_attr( $navn . '[merker][' . (int) $i . ']' ),
				esc_attr( $gruppe['navn'] . ' for ' . $kilde['tittel'] ),
				esc_html( mb_strtolower( $gruppe['navn'] ) )
			);

			foreach ( $gruppe['valg'] as $merke ) {
				$velger .= sprintf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $merke ),
					selected( $valgt, $merke, false ),
					esc_html( $merke )
				);
			}

			$velger .= '</select>';
		}

		printf(
			'<li class="bvp-lookup__kilde" data-id="%1$d">
				<input type="hidden" name="%2$s" value="%1$d">
				%3$s
				%4$s
				%5$s
				%6$s
				<button type="button" class="bvp-fjern-kilde" aria-label="Fjern %7$s">Fjern</button>
			</li>',
			$kilde_id, // phpcs:ignore WordPress.Security.EscapeOutput
			esc_attr( $navn . '[id]' ),
			$tittel, // phpcs:ignore WordPress.Security.EscapeOutput
			$kilde['utgiver'] ? '<span class="bvp-desc">' . esc_html( $kilde['utgiver'] ) . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput
			$kilde['type'] ? '<span class="bvp-lookup__type">' . esc_html( $kilde['type'] ) . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput
			$velger, // phpcs:ignore WordPress.Security.EscapeOutput
			esc_attr( $kilde['tittel'] )
		);
	}

	/**
	 * Hjelpeteksten som trygg HTML. Lenker åpnes i ny fane, slik at
	 * ingen mister et halvutfylt skjema ved å klikke på en lenke.
	 *
	 * @param string $tekst
	 * @return string
	 */
	public static function hjelpetekst( $tekst ) {
		$html = wp_kses( (string) $tekst, BVP_Schema::desc_tags() );

		$html = preg_replace_callback(
			'/<a\s([^>]*)>/i',
			static function ( $treff ) {
				$attr = $treff[1];

				if ( false === stripos( $attr, 'target=' ) ) {
					$attr .= ' target="_blank"';
				}

				if ( false === stripos( $attr, 'rel=' ) ) {
					$attr .= ' rel="noopener noreferrer"';
				}

				return '<a ' . trim( $attr ) . '>';
			},
			$html
		);

		return $html;
	}

	/**
	 * Vis eksisterende vedlegg med slettelenke.
	 *
	 * @param array $field
	 * @param mixed $value
	 * @param int   $post_id
	 */
	protected static function attachments( $field, $value, $post_id ) {
		$ids = is_array( $value ) ? $value : array_filter( array( (int) $value ) );

		if ( empty( $ids ) || ! $post_id ) {
			return;
		}

		echo '<ul class="bvp-attachments">';
		foreach ( $ids as $attachment_id ) {
			$attachment_id = (int) $attachment_id;
			$url           = wp_get_attachment_url( $attachment_id );
			if ( ! $url ) {
				continue;
			}
			$thumb = wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'bvp-thumb' ) );
			echo '<li class="bvp-attachment">';
			if ( $thumb ) {
				echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			$path     = get_attached_file( $attachment_id );
			$filename = $path ? basename( $path ) : get_the_title( $attachment_id );
			printf(
				'<a href="%1$s" target="_blank" rel="noopener">%2$s</a> ',
				esc_url( $url ),
				esc_html( $filename )
			);
			printf(
				'<label class="bvp-remove"><input type="checkbox" name="bvp_remove[%1$s][]" value="%2$d"> Fjern</label>',
				esc_attr( $field['key'] ),
				$attachment_id
			);
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Lesevisning av en seksjon - brukes når noen ser en registrering de ikke kan redigere.
	 *
	 * @param array $section
	 * @param int   $post_id
	 */
	public static function section_readonly( $section, $post_id, $ekstra = array() ) {
		$rader = array();

		foreach ( $section['fields'] as $field ) {
			if ( ! empty( $field['hidden'] ) ) {
				continue;
			}

			// Felt som ikke gjelder (betingelsen er ikke oppfylt) vises ikke.
			if ( ! BVP_Repository::depends_met_for_post( $field, $post_id ) ) {
				continue;
			}

			$value = BVP_Repository::get_value( $post_id, $field['key'] );
			$text  = self::value_to_text( $field, $value );

			if ( '' === trim( (string) $text ) ) {
				continue;
			}

			$rader[] = array( $field, $value, $text );
		}

		if ( empty( $rader ) && empty( $ekstra ) ) {
			return;
		}

		echo '<section class="bvp-section bvp-readonly">';
		echo '<h3 class="bvp-section__title">' . esc_html( $section['title'] ) . '</h3>';
		echo '<dl class="bvp-readonly__list">';

		foreach ( $rader as $rad ) {
			list( $field, $value, $text ) = $rad;

			echo '<dt>' . esc_html( $field['label'] ) . '</dt><dd>';

			if ( 'lookup' === $field['type'] ) {
				echo '<ul class="bvp-lookup__lest bvp-liste">';

				foreach ( BVP_Lookup::items( is_array( $value ) ? $value : array(), $field ) as $kilde ) {
					$merker = '';

					foreach ( BVP_Lookup::merker_for( $kilde, $field ) as $merke ) {
						$merker .= ' <span class="bvp-lookup__type">' . esc_html( $merke ) . '</span>';
					}

					printf(
						'<li><a href="%1$s" target="_blank" rel="noopener">%2$s</a>%3$s%4$s</li>',
						esc_url( $kilde['lenke'] ),
						esc_html( $kilde['tittel'] ),
						$kilde['utgiver'] ? ' <span class="bvp-desc">' . esc_html( $kilde['utgiver'] ) . '</span>' : '', // phpcs:ignore WordPress.Security.EscapeOutput
						$merker // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}

				echo '</ul>';
			} elseif ( in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				$ids = is_array( $value ) ? $value : array_filter( array( (int) $value ) );

				echo '<ul class="bvp-attachments">';
				foreach ( $ids as $id ) {
					$url = wp_get_attachment_url( (int) $id );
					if ( ! $url ) {
						continue;
					}
					$path     = get_attached_file( (int) $id );
					$filename = $path ? basename( $path ) : get_the_title( (int) $id );
					printf(
						'<li class="bvp-attachment"><a href="%1$s" target="_blank" rel="noopener">%2$s</a></li>',
						esc_url( $url ),
						esc_html( $filename )
					);
				}
				echo '</ul>';
			} elseif ( is_array( $value ) ) {
				// Flere svar: ett per linje som punktliste.
				$valgte = array_filter( array_map( 'trim', array_map( 'strval', array_filter( $value, 'is_scalar' ) ) ) );

				if ( count( $valgte ) > 1 ) {
					echo '<ul class="bvp-liste">';

					foreach ( $valgte as $valgt ) {
						echo '<li>' . esc_html( $valgt ) . '</li>';
					}

					echo '</ul>';
				} else {
					echo esc_html( implode( '', $valgte ) );
				}
			} elseif ( 'textarea' === $field['type'] ) {
				echo nl2br( esc_html( $text ) );
			} else {
				echo esc_html( $text );
			}

			echo '</dd>';
		}

		// Opplysninger som ikke kommer fra et skjemafelt, f.eks. prosjekteier.
		foreach ( (array) $ekstra as $rad ) {
			if ( empty( $rad['label'] ) || '' === trim( (string) $rad['html'] ) ) {
				continue;
			}

			printf(
				'<dt>%1$s</dt><dd>%2$s</dd>',
				esc_html( $rad['label'] ),
				$rad['html'] // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}

		echo '</dl></section>';
	}

	/**
	 * Verdi som lesbar tekst (e-post, CSV, oppsummering).
	 *
	 * @param array $field
	 * @param mixed $value
	 * @return string
	 */
	public static function value_to_text( $field, $value ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return implode( ', ', (array) $value );

			case 'lookup':
				$navn = array();

				foreach ( BVP_Lookup::items( is_array( $value ) ? $value : array(), $field ) as $kilde ) {
					$merker = BVP_Lookup::merker_for( $kilde, $field );

					$navn[] = $kilde['tittel']
						. ( $kilde['utgiver'] ? ' (' . $kilde['utgiver'] . ')' : '' )
						. ( $merker ? ' [' . implode( ', ', $merker ) . ']' : '' )
						. ( ! empty( $kilde['lenke'] ) ? ' – ' . $kilde['lenke'] : '' );
				}

				return implode( '; ', $navn );

			case 'file':
			case 'files':
				$ids  = is_array( $value ) ? $value : array_filter( array( (int) $value ) );
				$urls = array();
				foreach ( $ids as $id ) {
					$url = wp_get_attachment_url( (int) $id );
					if ( $url ) {
						$urls[] = $url;
					}
				}
				return implode( ' | ', $urls );

			default:
				return (string) $value;
		}
	}
}
