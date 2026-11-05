<?php
/**
 * Ukentlig e-postsammendrag.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Rapport {

	private BV_Lenkevakt_Innstillinger $innstillinger;
	private BV_Lenkevakt_Logg $logg;

	public function __construct( BV_Lenkevakt_Innstillinger $innstillinger, BV_Lenkevakt_Logg $logg ) {
		$this->innstillinger = $innstillinger;
		$this->logg          = $logg;
	}

	/**
	 * Send sammendraget for én kjøring.
	 */
	public function send( string $kjoring_id, array $kjoring ): bool {
		if ( ! $this->innstillinger->hent( 'e_post_aktiv' ) ) {
			return false;
		}

		$mottaker = (string) $this->innstillinger->hent( 'e_post_mottaker' );
		if ( ! is_email( $mottaker ) ) {
			return false;
		}

		$rader = $this->logg->for_kjoring( $kjoring_id );
		if ( empty( $rader ) ) {
			return false;
		}

		$telling = (array) ( $kjoring['telling'] ?? array() );
		$avvik   = array_filter(
			$rader,
			static function ( $rad ) {
				return ! in_array( $rad['status'], array( BV_Lenkevakt::STATUS_OK, BV_Lenkevakt::STATUS_IKKE_SJEKKBAR ), true )
					|| ! in_array( $rad['handling'], array( 'ingen', '' ), true );
			}
		);

		$emne = sprintf(
			'[BV lenkevakt] %d avvik av %d kunnskapskilder',
			count( $avvik ),
			(int) ( $kjoring['totalt'] ?? 0 )
		);

		$kropp = $this->bygg_html( $kjoring, $telling, $avvik );

		add_filter( 'wp_mail_content_type', array( $this, 'html_innholdstype' ) );
		$sendt = wp_mail( $mottaker, $emne, $kropp );
		remove_filter( 'wp_mail_content_type', array( $this, 'html_innholdstype' ) );

		return (bool) $sendt;
	}

	public function html_innholdstype(): string {
		return 'text/html';
	}

	private function bygg_html( array $kjoring, array $telling, array $avvik ): string {
		$rapportside = BV_Lenkevakt_Admin::side_url( 'bv-lenkevakt' );

		$h  = '<div style="font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.55;color:#1d2327;max-width:720px;">';
		$h .= '<h2 style="margin:0 0 4px;font-size:19px;">BV lenkevakt — ukentlig kontroll</h2>';
		$h .= '<p style="margin:0 0 18px;color:#646970;">' . esc_html( $this->norsk_dato( (string) ( $kjoring['start'] ?? '' ) ) ) . ' · ' . absint( $kjoring['totalt'] ?? 0 ) . ' kilder sjekket</p>';

		$h .= '<table style="border-collapse:collapse;margin:0 0 22px;">';
		foreach ( array(
			BV_Lenkevakt::STATUS_OK,
			BV_Lenkevakt::STATUS_OPPDATERT,
			BV_Lenkevakt::STATUS_MIDLERTIDIG,
			BV_Lenkevakt::STATUS_ADVARSEL,
			BV_Lenkevakt::STATUS_MANGLER,
			BV_Lenkevakt::STATUS_BRUTT,
		) as $status ) {
			$antall = (int) ( $telling[ $status ] ?? 0 );
			if ( 0 === $antall ) {
				continue;
			}
			$h .= '<tr>';
			$h .= '<td style="padding:3px 14px 3px 0;color:' . esc_attr( BV_Lenkevakt::status_farge( $status ) ) . ';">' . esc_html( BV_Lenkevakt::status_etikett( $status ) ) . '</td>';
			$h .= '<td style="padding:3px 0;font-variant-numeric:tabular-nums;"><strong>' . $antall . '</strong></td>';
			$h .= '</tr>';
		}
		$h .= '</table>';

		if ( empty( $avvik ) ) {
			$h .= '<p>Ingen avvik denne uka. Alle lenker svarer som de skal.</p>';
		} else {
			$h .= '<h3 style="font-size:16px;margin:0 0 8px;">Hva som skjedde</h3>';
			$h .= '<table style="border-collapse:collapse;width:100%;font-size:14px;">';
			$h .= '<tr style="text-align:left;border-bottom:1px solid #dcdcde;">'
				. '<th style="padding:6px 10px 6px 0;">Kilde</th>'
				. '<th style="padding:6px 10px 6px 0;">Status</th>'
				. '<th style="padding:6px 0;">Handling</th></tr>';

			$vist = 0;
			foreach ( $avvik as $rad ) {
				if ( $vist >= 60 ) {
					$h .= '<tr><td colspan="3" style="padding:8px 0;color:#646970;">… og ' . ( count( $avvik ) - $vist ) . ' til. Se rapportsiden.</td></tr>';
					break;
				}
				$vist++;

				$tittel = get_the_title( (int) $rad['post_id'] );
				$lenke  = get_edit_post_link( (int) $rad['post_id'], '' );

				$h .= '<tr style="border-bottom:1px solid #f0f0f1;">';
				$h .= '<td style="padding:7px 10px 7px 0;"><a href="' . esc_url( (string) $lenke ) . '" style="color:#2271b1;">' . esc_html( $tittel ? $tittel : '#' . $rad['post_id'] ) . '</a>';
				if ( ! empty( $rad['url'] ) ) {
					$h .= '<br><span style="color:#8c8f94;font-size:12px;">' . esc_html( $this->kort( (string) $rad['url'] ) ) . '</span>';
				}
				$h .= '</td>';
				$h .= '<td style="padding:7px 10px 7px 0;color:' . esc_attr( BV_Lenkevakt::status_farge( (string) $rad['status'] ) ) . ';">'
					. esc_html( BV_Lenkevakt::status_etikett( (string) $rad['status'] ) )
					. ( $rad['http_kode'] ? ' (' . absint( $rad['http_kode'] ) . ')' : '' ) . '</td>';
				$h .= '<td style="padding:7px 0;">' . esc_html( $this->handling_tekst( (string) $rad['handling'], (string) $rad['ny_url'] ) ) . '</td>';
				$h .= '</tr>';
			}
			$h .= '</table>';
		}

		$h .= '<p style="margin:24px 0 0;"><a href="' . esc_url( $rapportside ) . '" style="background:#2271b1;color:#fff;padding:9px 16px;border-radius:3px;text-decoration:none;">Åpne rapportsiden</a></p>';
		$h .= '<p style="margin:20px 0 0;color:#8c8f94;font-size:12px;">Sendt av BV lenkevakt ' . esc_html( BV_LENKEVAKT_VERSJON ) . ' på ' . esc_html( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) . '.</p>';
		$h .= '</div>';

		return $h;
	}

	private function handling_tekst( string $handling, string $ny_url ): string {
		switch ( $handling ) {
			case 'oppdatert_url':
				return 'Lenken er oppdatert til ' . $this->kort( $ny_url );
			case 'foreslatt':
				return 'Ny lenke foreslått — må godtas manuelt';
			case 'satt_til_kladd':
				return 'Kilden er satt til kladd';
			case 'draft_foreslatt':
				return 'Bør settes til kladd — automatikk er avslått';
			case 'kan_gjenopprettes':
				return 'Lenken svarer igjen — kan publiseres på nytt';
			case 'gjenopprettet':
				return 'Publisert på nytt';
			default:
				return 'Ingen — bare notert';
		}
	}

	private function kort( string $url, int $lengde = 70 ): string {
		return strlen( $url ) > $lengde ? substr( $url, 0, $lengde - 1 ) . '…' : $url;
	}

	private function norsk_dato( string $mysql ): string {
		if ( '' === $mysql ) {
			return '';
		}
		$ts = strtotime( $mysql );
		return $ts ? wp_date( 'j. F Y \k\l. H:i', $ts ) : $mysql;
	}
}
