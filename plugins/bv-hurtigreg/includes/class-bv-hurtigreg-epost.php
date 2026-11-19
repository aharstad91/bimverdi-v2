<?php
/**
 * E-postene. Bygges i husets skjelett (bimverdi_epost_dokument), aldri som
 * nakne HTML-fragmenter. Avsenderen settes av bimverdi-resend-mail.php.
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Epost {

	/** Levetid for innloggingslenker (HR-05). Kortere enn velkomstlenken, siden den kan sendes til alle kontotyper. */
	const LOGIN_LEVETID = DAY_IN_SECONDS;

	/** Velkommen (HR-08). */
	public static function velkommen( int $id ): bool {
		$u = get_userdata( $id );
		if ( ! $u ) {
			return false;
		}
		$dager = BV_Hurtigreg_Innstillinger::tall( 'lenke_dager' );

		$innhold = self::avsnitt( sprintf( 'Hei %s!', esc_html( $u->first_name ?: $u->display_name ) ), array( 'storrelse' => '20px', 'farge' => '#1A1A1A' ) )
			. self::avsnitt( 'Du er i gang med ByggChat. Bekreft e-postadressen din, så får du flere spørsmål med en gang.' )
			. self::avsnitt( 'Når du har tid, kobler du deg til foretaket ditt. Det tar ett minutt, og da får du full tilgang til ByggChat og BIM Verdi.' );

		$etter = self::avsnitt(
			sprintf(
				'Lenken virker i %d dager. <a href="%s" style="color:#FF8B5E;">Gå rett til ByggChat</a>',
				$dager,
				esc_url( BV_Hurtigreg_Innstillinger::til_byggchat_url() )
			),
			array( 'storrelse' => '14px', 'farge' => '#6B6B6B' )
		);

		return self::send(
			$u->user_email,
			'Velkommen til ByggChat – fullfør registreringen',
			array(
				'tittel'    => 'Velkommen til ByggChat',
				'innhold'   => $innhold,
				'cta_url'   => BV_Hurtigreg_Bruker::velkomstlenke( $id ),
				'cta_tekst' => 'Bekreft og fullfør registreringen',
				'etter_cta' => $etter,
				'footer'    => self::personvern_footer( $id ),
			)
		);
	}

	/** Innloggingslenke når e-posten finnes fra før (HR-05). */
	public static function innlogging( int $id, string $neste ): bool {
		$u = get_userdata( $id );
		if ( ! $u ) {
			return false;
		}

		// Konto med redaksjonelle eller administrative rettigheter får ingen
		// innloggingslenke. En kompromittert e-postkonto skal ikke gi admin-tilgang.
		if ( user_can( $u, 'edit_others_posts' ) || user_can( $u, 'list_users' ) ) {
			return self::send(
				$u->user_email,
				'Logg inn på BIM Verdi',
				array(
					'tittel'    => 'Logg inn på BIM Verdi',
					'innhold'   => self::avsnitt( 'Noen ba om en innloggingslenke til ByggChat for denne adressen. Kontoen din har utvidede rettigheter, så du må logge inn med passord.' )
						. self::avsnitt( 'Var det ikke deg, kan du se bort fra denne e-posten.', array( 'farge' => '#6B6B6B', 'storrelse' => '14px' ) ),
					'cta_url'   => function_exists( 'bimverdi_auth_url' ) ? bimverdi_auth_url( 'logg-inn' ) : wp_login_url(),
					'cta_tekst' => 'Logg inn',
				)
			);
		}

		$hurtig = BV_Hurtigreg_Bruker::er_aktiv_hurtig( $id );
		$lenke  = BV_Hurtigreg_Bruker::lag_lenke( $id, BV_Hurtigreg_Bruker::LENKE_LOGIN, self::LOGIN_LEVETID, array( 'neste' => $neste ) );

		$innhold = self::avsnitt( sprintf( 'Hei %s!', esc_html( $u->first_name ?: $u->display_name ) ), array( 'storrelse' => '20px', 'farge' => '#1A1A1A' ) )
			. self::avsnitt( 'Trykk på knappen for å logge inn på ByggChat. Lenken virker i 24 timer og kan brukes én gang.' );
		if ( $hurtig && BV_Hurtigreg_Bruker::er_utlopt( $id ) ) {
			$innhold .= self::avsnitt( 'Gratisperioden din er over. Når du logger inn, kan du fullføre registreringen og fortsette å bruke ByggChat.' );
		}
		$innhold .= self::avsnitt( 'Var det ikke deg som ba om lenken, kan du se bort fra denne e-posten.', array( 'farge' => '#6B6B6B', 'storrelse' => '14px' ) );

		return self::send(
			$u->user_email,
			'Logg inn på ByggChat',
			array(
				'tittel'    => 'Logg inn på ByggChat',
				'innhold'   => $innhold,
				'cta_url'   => $lenke,
				'cta_tekst' => 'Logg inn på ByggChat',
				'footer'    => $hurtig ? self::personvern_footer( $id ) : '',
			)
		);
	}

	/** Bekreft ny jobbadresse ved fullføring (Å-1). Sendes til den nye adressen. */
	public static function bytt_epost( int $id, string $ny ): bool {
		$u = get_userdata( $id );
		if ( ! $u ) {
			return false;
		}
		$lenke = BV_Hurtigreg_Bruker::lag_lenke( $id, BV_Hurtigreg_Bruker::LENKE_BYTT, self::LOGIN_LEVETID, array( 'epost' => $ny ) );

		return self::send(
			$ny,
			'Bekreft e-postadressen din hos BIM Verdi',
			array(
				'tittel'    => 'Bekreft e-postadressen',
				'innhold'   => self::avsnitt( sprintf( 'Hei %s!', esc_html( $u->first_name ?: $u->display_name ) ), array( 'storrelse' => '20px', 'farge' => '#1A1A1A' ) )
					. self::avsnitt( sprintf( 'Bekreft at du vil bruke <strong>%s</strong> i BIM Verdi. Deretter kobler du deg til foretaket ditt.', esc_html( $ny ) ) )
					. self::avsnitt( 'Lenken virker i 24 timer.', array( 'farge' => '#6B6B6B', 'storrelse' => '14px' ) ),
				'cta_url'   => $lenke,
				'cta_tekst' => 'Bekreft e-postadressen',
			)
		);
	}

	/** Påminnelser (HR-11). $nr = 1 etter 7 dager, 2 tre dager før utløp. */
	public static function paaminnelse( int $id, int $nr ): bool {
		$u = get_userdata( $id );
		if ( ! $u ) {
			return false;
		}
		$utloper = wp_date( 'j. F', BV_Hurtigreg_Bruker::utloper( $id ) );

		if ( 1 === $nr ) {
			$emne  = 'Fullfør registreringen din i BIM Verdi';
			$tekst = self::avsnitt( 'Takk for at du prøver ByggChat! Du har ikke fullført registreringen ennå.' )
				. self::avsnitt( sprintf( 'Koble deg til foretaket ditt før %s, så beholder du tilgangen og får full bruk av ByggChat. Det tar ett minutt.', esc_html( $utloper ) ) );
		} else {
			$emne  = 'Tilgangen din til ByggChat utløper snart';
			$tekst = self::avsnitt( sprintf( 'Gratistilgangen din til ByggChat utløper %s.', esc_html( $utloper ) ) )
				. self::avsnitt( 'Fullfør registreringen nå, så beholder du tilgangen og historikken din i ByggChat.' );
		}

		return self::send(
			$u->user_email,
			$emne,
			array(
				'tittel'    => $emne,
				'innhold'   => self::avsnitt( sprintf( 'Hei %s!', esc_html( $u->first_name ?: $u->display_name ) ), array( 'storrelse' => '20px', 'farge' => '#1A1A1A' ) ) . $tekst,
				'cta_url'   => BV_Hurtigreg_Bruker::velkomstlenke( $id ),
				'cta_tekst' => 'Fullfør registreringen',
				'footer'    => self::personvern_footer( $id ),
			)
		);
	}

	/** Lagringstid og slettelenke i hver e-post til en hurtigbruker (HR-13). */
	private static function personvern_footer( int $id ): string {
		return sprintf(
			'Du får denne e-posten fordi adressen ble registrert for ByggChat på bimverdi.no. Fullfører du ikke registreringen, stenges tilgangen etter %1$d dager, og navn og e-post slettes %2$d dager etter det. <a href="%3$s" style="color:#9B9B9B;">Slett kontoen nå</a> · <a href="%4$s" style="color:#9B9B9B;">Personvern</a>',
			BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' ),
			BV_Hurtigreg_Innstillinger::tall( 'slett_dager' ),
			esc_url( BV_Hurtigreg_Bruker::slettelenke( $id ) ),
			esc_url( home_url( '/personvern/' ) )
		);
	}

	private static function avsnitt( string $html, array $stiler = array() ): string {
		if ( function_exists( 'bimverdi_epost_avsnitt' ) ) {
			return bimverdi_epost_avsnitt( $html, $stiler );
		}
		return '<p style="margin:0 0 16px 0;font-size:' . esc_attr( $stiler['storrelse'] ?? '16px' ) . ';line-height:1.6;color:' . esc_attr( $stiler['farge'] ?? '#4A4A4A' ) . ';">' . $html . '</p>';
	}

	private static function send( string $til, string $emne, array $args ): bool {
		if ( function_exists( 'bimverdi_epost_dokument' ) ) {
			$html = bimverdi_epost_dokument( $args );
		} else {
			$html = '<!DOCTYPE html><html lang="no"><body>' . $args['innhold']
				. ( ! empty( $args['cta_url'] ) ? '<p><a href="' . esc_url( $args['cta_url'] ) . '">' . esc_html( $args['cta_tekst'] ) . '</a></p>' : '' )
				. ( $args['etter_cta'] ?? '' ) . '<p style="font-size:11px;color:#9B9B9B;">' . ( $args['footer'] ?? '' ) . '</p></body></html>';
		}
		$ok = wp_mail( $til, $emne, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
		if ( ! $ok ) {
			BV_Hurtigreg_Bruker::logg( 'E-post feilet: ' . $emne );
		}
		return $ok;
	}
}
