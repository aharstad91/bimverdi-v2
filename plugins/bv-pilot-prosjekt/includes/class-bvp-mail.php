<?php
/**
 * E-postvarsler.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Mail {

	/**
	 * Varsle ved innsending.
	 *
	 * @param int  $post_id
	 * @param bool $was_new
	 */
	public static function notify_submission( $post_id, $was_new = true ) {
		$settings = BVP_Admin::get_settings();

		if ( ! empty( $settings['notify_admin'] ) && ! empty( $settings['notify_email'] ) ) {
			$emails = array_filter( array_map( 'trim', explode( ',', $settings['notify_email'] ) ), 'is_email' );

			if ( $emails ) {
				$subject = sprintf(
					'[BIM Verdi] %s BV-pilot/prosjekt: %s',
					$was_new ? 'Ny' : 'Oppdatert',
					get_the_title( $post_id )
				);

				$body = self::summary( $post_id ) . "\n\n"
					. 'Rediger i wp-admin: ' . get_edit_post_link( $post_id, 'raw' ) . "\n";

				wp_mail( $emails, $subject, $body, self::headers() );
			}
		}

		if ( ! empty( $settings['send_receipt'] ) ) {
			$til = BVP_Repository::get_value( $post_id, 'reg_epost' );

			if ( is_email( $til ) ) {
				$subject = 'Vi har mottatt registreringen din - BIM Verdi';
				$body    = sprintf(
					"Hei %s,\n\nTakk for at du registrerte BV-pilot/prosjektet «%s» hos BIM Verdi.\n\n"
					. "Vi ser gjennom registreringen og tar kontakt. Du kan når som helst logge inn og oppdatere den.\n\n"
					. "Her er en kopi av det du sendte inn:\n\n%s\n\nMed vennlig hilsen\nBIM Verdi\n",
					BVP_Repository::get_value( $post_id, 'reg_fornavn' ),
					get_the_title( $post_id ),
					self::summary( $post_id )
				);

				wp_mail( $til, $subject, $body, self::headers() );
			}
		}
	}

	/**
	 * Varsle BIM Verdi når noen starter en ny registrering (første kladd).
	 *
	 * @param int $post_id
	 */
	public static function notify_ny_kladd( $post_id ) {
		$settings = BVP_Admin::get_settings();

		if ( empty( $settings['notify_admin'] ) || empty( $settings['notify_kladd'] ) || empty( $settings['notify_email'] ) ) {
			return;
		}

		$emails = array_filter( array_map( 'trim', explode( ',', $settings['notify_email'] ) ), 'is_email' );

		if ( ! $emails ) {
			return;
		}

		$eier = get_userdata( (int) get_post_field( 'post_author', $post_id ) );
		$navn = $eier ? $eier->display_name . ' (' . $eier->user_email . ')' : 'en deltaker';

		$body = sprintf(
			"%s har startet en ny registrering: «%s».\n\nDen er lagret som kladd og vises ikke i oversiktene på nettsiden før den sendes inn.\n\nSe den i wp-admin: %s\n",
			$navn,
			get_the_title( $post_id ),
			get_edit_post_link( $post_id, 'raw' )
		);

		wp_mail( $emails, sprintf( '[BIM Verdi] Ny kladd: %s', get_the_title( $post_id ) ), $body, self::headers() );
	}

	/**
	 * Varsle BIM Verdi om at en registrering er slettet.
	 *
	 * @param int     $post_id
	 * @param WP_User $sletter
	 */
	public static function notify_deleted( $post_id, $sletter = null ) {
		$settings = BVP_Admin::get_settings();

		if ( empty( $settings['notify_admin'] ) || empty( $settings['notify_email'] ) ) {
			return;
		}

		$emails = array_filter( array_map( 'trim', explode( ',', $settings['notify_email'] ) ), 'is_email' );

		if ( ! $emails ) {
			return;
		}

		$navn = $sletter && isset( $sletter->display_name ) ? $sletter->display_name : 'ukjent bruker';

		$subject = sprintf( '[BIM Verdi] Slettet BV-pilot/prosjekt: %s', get_the_title( $post_id ) );

		$body = sprintf(
			"«%s» er slettet av %s.\n\nRegistreringen ligger i papirkurven i wp-admin og kan hentes tilbake derfra.\n\n%s\n",
			get_the_title( $post_id ),
			$navn,
			self::summary( $post_id )
		);

		wp_mail( $emails, $subject, $body, self::headers() );
	}

	/**
	 * Varsle om en ny melding på en registrering.
	 *
	 * @param int     $post_id
	 * @param string  $tekst
	 * @param WP_User $avsender
	 */
	public static function notify_melding( $post_id, $tekst, $avsender, $nevnt = array() ) {
		$nevnt = array_values( array_unique( array_map( 'intval', (array) $nevnt ) ) );

		// De som er nevnt får sin egen e-post, og teller ikke med under.
		self::notify_nevnt( $post_id, $tekst, $avsender, $nevnt );

		$mottakere = BVP_Meldinger::mottakere( $post_id, $avsender ? $avsender->ID : 0, $nevnt );

		$settings = BVP_Admin::get_settings();

		if ( ! empty( $settings['notify_admin'] ) && ! empty( $settings['notify_email'] ) ) {
			foreach ( explode( ',', $settings['notify_email'] ) as $adresse ) {
				$adresse = trim( $adresse );

				if ( is_email( $adresse ) ) {
					$mottakere[] = $adresse;
				}
			}
		}

		$mottakere = array_values( array_unique( array_filter( $mottakere, 'is_email' ) ) );

		if ( empty( $mottakere ) ) {
			return;
		}

		$lenke = BVP_Form::single_url( $post_id, BVP_Minside::skjema_url() );

		$body = sprintf(
			"%s har skrevet en melding om «%s»:\n\n%s\n\nSvar her: %s\n",
			$avsender ? $avsender->display_name : 'En deltaker',
			get_the_title( $post_id ),
			$tekst,
			$lenke
		);

		wp_mail(
			$mottakere,
			sprintf( '[BIM Verdi] Ny melding: %s', get_the_title( $post_id ) ),
			$body,
			self::headers()
		);
	}

	/**
	 * Varsle dem som ble nevnt med @ i en melding.
	 *
	 * @param int     $post_id
	 * @param string  $tekst
	 * @param WP_User $avsender
	 * @param array   $nevnt Bruker-ID-er.
	 */
	public static function notify_nevnt( $post_id, $tekst, $avsender, $nevnt ) {
		$nevnt = array_filter( array_map( 'intval', (array) $nevnt ) );

		if ( empty( $nevnt ) ) {
			return;
		}

		$lenke = BVP_Form::single_url( $post_id, BVP_Minside::skjema_url() );
		$fra   = $avsender && isset( $avsender->display_name ) ? $avsender->display_name : 'En deltaker';

		foreach ( $nevnt as $id ) {
			$bruker = get_userdata( $id );

			if ( ! $bruker || ! is_email( $bruker->user_email ) ) {
				continue;
			}

			$body = sprintf(
				"Hei %s,\n\n%s nevnte deg i en melding om «%s»:\n\n%s\n\nSvar her: %s\n",
				$bruker->display_name,
				$fra,
				get_the_title( $post_id ),
				$tekst,
				$lenke
			);

			wp_mail(
				$bruker->user_email,
				sprintf( '[BIM Verdi] %s nevnte deg: %s', $fra, get_the_title( $post_id ) ),
				$body,
				self::headers()
			);
		}
	}

	/**
	 * Tekstoppsummering av brukerfeltene.
	 *
	 * @param int $post_id
	 * @return string
	 */
	public static function summary( $post_id ) {
		$lines = array();

		foreach ( BVP_Fields::sections_for( 'user' ) as $section ) {
			$lines[] = strtoupper( $section['title'] );
			$lines[] = str_repeat( '-', mb_strlen( $section['title'] ) );

			foreach ( $section['fields'] as $field ) {
				if ( ! BVP_Repository::depends_met_for_post( $field, $post_id ) ) {
					continue;
				}

				$value = BVP_Repository::get_value( $post_id, $field['key'] );
				$text  = BVP_Renderer::value_to_text( $field, $value );

				if ( '' === trim( $text ) ) {
					continue;
				}

				$lines[] = $field['label'] . ': ' . $text;
			}

			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * Standard e-posthoder.
	 *
	 * @return array
	 */
	protected static function headers() {
		return array( 'Content-Type: text/plain; charset=UTF-8' );
	}
}
