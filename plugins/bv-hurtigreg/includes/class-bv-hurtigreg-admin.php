<?php
/**
 * wp-admin: statistikk per kampanjekode, innstillinger og logg (HR-10, HR-12,
 * HR-15), filter i brukerlisten og tekst til personvernerklæringen (HR-13).
 *
 * @package BimVerdi\Hurtigreg
 */

defined( 'ABSPATH' ) || exit;

final class BV_Hurtigreg_Admin {

	const SIDE = 'bv-hurtigreg';

	public static function start(): void {
		add_action( 'admin_menu', array( __CLASS__, 'meny' ) );
		add_action( 'admin_init', array( __CLASS__, 'registrer_innstillinger' ) );
		add_action( 'admin_init', array( __CLASS__, 'personvern' ) );
		add_action( 'admin_post_bv_hurtigreg_kjor', array( __CLASS__, 'kjor_na' ) );

		// Brukerlisten (HR-15).
		add_filter( 'views_users', array( __CLASS__, 'brukervisning' ) );
		add_action( 'pre_get_users', array( __CLASS__, 'filtrer_brukere' ) );
		add_filter( 'manage_users_columns', array( __CLASS__, 'kolonne' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'kolonneverdi' ), 10, 3 );
	}

	public static function meny(): void {
		add_users_page( 'Hurtigregistrering', 'Hurtigregistrering', 'list_users', self::SIDE, array( __CLASS__, 'side' ) );
	}

	public static function registrer_innstillinger(): void {
		register_setting( 'bv_hurtigreg', BV_Hurtigreg_Innstillinger::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( 'BV_Hurtigreg_Innstillinger', 'rens' ),
			'default'           => BV_Hurtigreg_Innstillinger::standard(),
		) );
	}

	/** Forslag til personvernerklæringen, under Innstillinger → Personvern → Veiledning. */
	public static function personvern(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$utlop = BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' );
		$slett = BV_Hurtigreg_Innstillinger::tall( 'slett_dager' );
		wp_add_privacy_policy_content(
			'BV hurtigregistrering (ByggChat)',
			wp_kses_post( sprintf(
				'<h2>Hurtigregistrering til ByggChat</h2>'
				. '<p><strong>Formål:</strong> Gi rask tilgang til ByggChat, for eksempel fra en QR-kode på stand eller arrangement.</p>'
				. '<p><strong>Hva vi lagrer:</strong> Navn, e-postadresse, tidspunkt for registrering og bekreftelse, og en kampanjekode som viser hvor du kom fra (for eksempel «rollup1»).</p>'
				. '<p><strong>Lagringstid:</strong> Fullfører du ikke registreringen innen %1$d dager, stenges tilgangen. %2$d dager etter det slettes navn og e-postadresse. Kampanjekode og tidspunkter beholdes uten personopplysninger, til statistikk.</p>'
				. '<p><strong>Sletting:</strong> Du kan slette kontoen selv når som helst fra ByggChat eller med lenken i e-postene fra oss.</p>'
				. '<p><strong>Fullfører du registreringen,</strong> gjelder de vanlige vilkårene for brukere av BIM Verdi.</p>',
				$utlop,
				$slett
			) )
		);
	}

	public static function kjor_na(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Ingen tilgang.' );
		}
		check_admin_referer( 'bv_hurtigreg_kjor' );
		BV_Hurtigreg_Cron::kjor();
		wp_safe_redirect( add_query_arg( array( 'page' => self::SIDE, 'fane' => 'logg', 'kjort' => 1 ), admin_url( 'users.php' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Siden
	 * ------------------------------------------------------------------ */

	public static function side(): void {
		$fane  = sanitize_key( $_GET['fane'] ?? 'statistikk' ); // phpcs:ignore WordPress.Security.NonceVerification
		$faner = array( 'statistikk' => 'Statistikk', 'innstillinger' => 'Innstillinger', 'logg' => 'Logg' );
		if ( ! current_user_can( 'manage_options' ) ) {
			unset( $faner['innstillinger'] );
		}
		if ( ! isset( $faner[ $fane ] ) ) {
			$fane = 'statistikk';
		}
		?>
		<div class="wrap">
			<h1>Hurtigregistrering for ByggChat</h1>
			<?php if ( ! (int) BV_Hurtigreg_Innstillinger::hent( 'aktiv' ) ) : ?>
				<div class="notice notice-warning"><p>Skjemaet er <strong>skrudd av</strong>.</p></div>
			<?php endif; ?>
			<?php if ( ! BV_Hurtigreg_Innstillinger::turnstile_paa() ) : ?>
				<div class="notice notice-warning"><p>Cloudflare Turnstile er ikke satt opp. Skjemaet er beskyttet bare av honeypot og ratebegrensning.</p></div>
			<?php endif; ?>
			<?php if ( ! defined( 'BYGGCHAT_PRIVATE_KEY_FILE' ) ) : ?>
				<div class="notice notice-error"><p>BYGGCHAT_PRIVATE_KEY_FILE er ikke definert i wp-config.php. Hurtigbrukere kommer ikke inn i ByggChat før nøkkelen er på plass.</p></div>
			<?php endif; ?>
			<nav class="nav-tab-wrapper">
				<?php foreach ( $faner as $slug => $navn ) : ?>
					<a class="nav-tab<?php echo $slug === $fane ? ' nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => self::SIDE, 'fane' => $slug ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html( $navn ); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php
			if ( 'innstillinger' === $fane ) {
				self::innstillinger();
			} elseif ( 'logg' === $fane ) {
				self::logg();
			} else {
				self::statistikk();
			}
			?>
		</div>
		<?php
	}

	/** @return array<int,array<string,mixed>> */
	public static function tall_per_kampanje(): array {
		global $wpdb;
		$um = $wpdb->usermeta;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results( $wpdb->prepare(
			"SELECT COALESCE(NULLIF(k.meta_value, ''), '') AS kampanje,
				COUNT(*) AS registrerte,
				SUM(b.umeta_id IS NOT NULL) AS bekreftede,
				SUM(f.umeta_id IS NOT NULL) AS fullforte,
				SUM(u.umeta_id IS NOT NULL) AS utlopte,
				MIN(CAST(o.meta_value AS UNSIGNED)) AS forste,
				MAX(CAST(o.meta_value AS UNSIGNED)) AS siste
			FROM {$um} o
			LEFT JOIN {$um} k ON k.user_id = o.user_id AND k.meta_key = %s
			LEFT JOIN {$um} b ON b.user_id = o.user_id AND b.meta_key = %s
			LEFT JOIN {$um} f ON f.user_id = o.user_id AND f.meta_key = %s
			LEFT JOIN {$um} u ON u.user_id = o.user_id AND u.meta_key = %s
			WHERE o.meta_key = %s
			GROUP BY kampanje
			ORDER BY registrerte DESC",
			BV_Hurtigreg::META_KAMPANJE,
			BV_Hurtigreg::META_BEKREFTET,
			BV_Hurtigreg::META_FULLFORT,
			BV_Hurtigreg::META_UTLOPT,
			BV_Hurtigreg::META_OPPRETTET
		), ARRAY_A );
	}

	private static function statistikk(): void {
		$rader  = self::tall_per_kampanje();
		$sum    = array( 'registrerte' => 0, 'bekreftede' => 0, 'fullforte' => 0, 'utlopte' => 0 );
		$stengt = BV_Hurtigreg_Innstillinger::stengte_kampanjer();
		?>
		<p>Antall per kampanjekode (<code>?k=</code> i adressen). «Utløpte» er alle som ble stengt etter <?php echo (int) BV_Hurtigreg_Innstillinger::tall( 'utlop_dager' ); ?> dager uten å fullføre, også de som senere er anonymisert.</p>
		<table class="widefat striped" style="max-width:960px">
			<thead><tr>
				<th>Kampanjekode</th><th style="text-align:right">Registrerte</th><th style="text-align:right">Bekreftede</th>
				<th style="text-align:right">Fullførte</th><th style="text-align:right">Utløpte</th><th style="text-align:right">Fullført-andel</th><th>Første – siste</th>
			</tr></thead>
			<tbody>
			<?php if ( ! $rader ) : ?>
				<tr><td colspan="7">Ingen hurtigregistreringer ennå.</td></tr>
			<?php endif; ?>
			<?php foreach ( $rader as $r ) :
				foreach ( $sum as $k => $v ) {
					$sum[ $k ] = $v + (int) $r[ $k ];
				}
				$andel = (int) $r['registrerte'] ? round( 100 * (int) $r['fullforte'] / (int) $r['registrerte'] ) . ' %' : '–';
				?>
				<tr>
					<td>
						<?php echo '' === $r['kampanje'] ? '<em>(ingen kode)</em>' : '<code>' . esc_html( $r['kampanje'] ) . '</code>'; ?>
						<?php if ( '' !== $r['kampanje'] && in_array( $r['kampanje'], $stengt, true ) ) : ?> <span style="color:#b32d2e">stengt</span><?php endif; ?>
					</td>
					<td style="text-align:right"><?php echo (int) $r['registrerte']; ?></td>
					<td style="text-align:right"><?php echo (int) $r['bekreftede']; ?></td>
					<td style="text-align:right"><?php echo (int) $r['fullforte']; ?></td>
					<td style="text-align:right"><?php echo (int) $r['utlopte']; ?></td>
					<td style="text-align:right"><?php echo esc_html( $andel ); ?></td>
					<td><?php echo esc_html( wp_date( 'j.n.Y', (int) $r['forste'] ) . ' – ' . wp_date( 'j.n.Y', (int) $r['siste'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<?php if ( count( $rader ) > 1 ) : ?>
			<tfoot><tr>
				<th>Sum</th>
				<th style="text-align:right"><?php echo (int) $sum['registrerte']; ?></th>
				<th style="text-align:right"><?php echo (int) $sum['bekreftede']; ?></th>
				<th style="text-align:right"><?php echo (int) $sum['fullforte']; ?></th>
				<th style="text-align:right"><?php echo (int) $sum['utlopte']; ?></th>
				<th></th><th></th>
			</tr></tfoot>
			<?php endif; ?>
		</table>
		<p><a href="<?php echo esc_url( add_query_arg( 'bv_hurtig', '1', admin_url( 'users.php' ) ) ); ?>">Se hurtigbrukerne i brukerlisten →</a></p>
		<?php
	}

	private static function innstillinger(): void {
		$o = BV_Hurtigreg_Innstillinger::alle();
		$n = BV_Hurtigreg_Innstillinger::OPTION;
		$tall = static function ( string $nokkel, string $etikett, string $hjelp = '' ) use ( $o, $n ) {
			printf(
				'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" min="0" class="small-text" id="%1$s" name="%3$s[%1$s]" value="%4$d">%5$s</td></tr>',
				esc_attr( $nokkel ),
				esc_html( $etikett ),
				esc_attr( $n ),
				(int) $o[ $nokkel ],
				$hjelp ? ' <span class="description">' . esc_html( $hjelp ) . '</span>' : ''
			);
		};
		$har_konstant = defined( 'BV_HURTIGREG_TURNSTILE_SECRET' ) && BV_HURTIGREG_TURNSTILE_SECRET;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'bv_hurtigreg' ); ?>

			<h2>Skjemaet</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Hurtigregistrering</th><td>
					<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[aktiv]" value="1" <?php checked( (int) $o['aktiv'], 1 ); ?>> Skjemaet på /chat/ er åpent</label>
				</td></tr>
				<tr><th scope="row"><label for="stengte_kampanjer">Stengte kampanjekoder</label></th><td>
					<textarea id="stengte_kampanjer" name="<?php echo esc_attr( $n ); ?>[stengte_kampanjer]" rows="3" class="regular-text code"><?php echo esc_textarea( (string) $o['stengte_kampanjer'] ); ?></textarea>
					<p class="description">Én per linje, f.eks. <code>rollup1</code>. Lenker med disse kodene viser ikke skjemaet.</p>
				</td></tr>
			</table>

			<h2>Kvote i ByggChat</h2>
			<table class="form-table" role="presentation">
				<?php
				$tall( 'kvote_ubekreftet', 'Ubekreftet hurtigbruker', 'spørsmål' );
				$tall( 'kvote_bekreftet', 'Bekreftet hurtigbruker', 'spørsmål' );
				?>
			</table>
			<p class="description">Sendes til ByggChat i adgangskortet (<code>hurtig.kvote</code>). ByggChat håndhever kvoten.</p>

			<h2>Utløp og opprydding</h2>
			<table class="form-table" role="presentation">
				<?php
				$tall( 'utlop_dager', 'Steng tilgangen etter', 'dager uten fullføring' );
				$tall( 'slett_dager', 'Anonymiser etter ytterligere', 'dager' );
				$tall( 'paaminnelse_1_dager', 'Første påminnelse etter', 'dager' );
				$tall( 'paaminnelse_2_for', 'Andre påminnelse', 'dager før utløp' );
				$tall( 'lenke_dager', 'Lenken i velkomst-e-posten virker i', 'dager' );
				?>
			</table>

			<h2>Misbruksvern</h2>
			<table class="form-table" role="presentation">
				<?php
				$tall( 'ip_per_time', 'Maks registreringer per IP', 'per time. På stand deler mange samme nett, så sett den høyere under arrangementer.' );
				$tall( 'epost_per_dogn', 'Maks e-poster per adresse', 'per døgn' );
				?>
				<tr><th scope="row"><label for="turnstile_site">Turnstile site key</label></th><td>
					<input type="text" class="regular-text code" id="turnstile_site" name="<?php echo esc_attr( $n ); ?>[turnstile_site]" value="<?php echo esc_attr( (string) $o['turnstile_site'] ); ?>">
				</td></tr>
				<tr><th scope="row"><label for="turnstile_secret">Turnstile secret key</label></th><td>
					<?php if ( $har_konstant ) : ?>
						<p>Hentes fra <code>BV_HURTIGREG_TURNSTILE_SECRET</code> i wp-config.php.</p>
					<?php else : ?>
						<input type="password" class="regular-text code" id="turnstile_secret" name="<?php echo esc_attr( $n ); ?>[turnstile_secret]" value="" autocomplete="new-password"
							placeholder="<?php echo '' !== (string) $o['turnstile_secret'] ? esc_attr( '•••••••• (lagret, la stå tom for å beholde)' ) : ''; ?>">
						<?php if ( '' !== (string) $o['turnstile_secret'] ) : ?>
							<label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[turnstile_secret_slett]" value="1"> Fjern lagret nøkkel</label>
						<?php endif; ?>
						<p class="description">Bør heller ligge i wp-config.php: <code>define( 'BV_HURTIGREG_TURNSTILE_SECRET', '…' );</code></p>
					<?php endif; ?>
				</td></tr>
			</table>

			<h2>Annet</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="ga4_id">GA4-måle-ID</label></th><td>
					<input type="text" class="regular-text code" id="ga4_id" name="<?php echo esc_attr( $n ); ?>[ga4_id]" value="<?php echo esc_attr( (string) $o['ga4_id'] ); ?>">
					<p class="description">Hendelsene <code>bv_hurtigreg_registrert</code> og <code>bv_hurtigreg_fullfort</code> med parameteren <code>kampanje</code>. Sendes bare når brukeren har godtatt analysecookies.</p>
				</td></tr>
				<tr><th scope="row"><label for="byggchat_url">ByggChat-adresse</label></th><td>
					<input type="url" class="regular-text code" id="byggchat_url" name="<?php echo esc_attr( $n ); ?>[byggchat_url]" value="<?php echo esc_attr( (string) $o['byggchat_url'] ); ?>">
				</td></tr>
			</table>

			<?php submit_button( 'Lagre innstillingene' ); ?>
		</form>
		<?php
	}

	private static function logg(): void {
		$logg  = get_option( BV_Hurtigreg_Cron::LOGG_OPTION, array() );
		$neste = wp_next_scheduled( BV_Hurtigreg::CRON_HOOK );
		?>
		<?php if ( ! empty( $_GET['kjort'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Oppryddingen er kjørt.</p></div>
		<?php endif; ?>
		<p>Neste daglige kjøring: <strong><?php echo $neste ? esc_html( wp_date( 'j.n.Y \k\l. H:i', $neste ) ) : 'ikke planlagt'; ?></strong></p>
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bv_hurtigreg_kjor">
				<?php wp_nonce_field( 'bv_hurtigreg_kjor' ); ?>
				<?php submit_button( 'Kjør oppryddingen nå', 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
		<table class="widefat striped" style="max-width:960px;margin-top:16px">
			<thead><tr><th>Tid</th><th>Sjekket</th><th>Påminnelse 1</th><th>Påminnelse 2</th><th>Stengt</th><th>Anonymisert</th><th>Venter på foretak</th><th>Feil</th></tr></thead>
			<tbody>
			<?php if ( ! $logg ) : ?>
				<tr><td colspan="8">Ingen kjøringer ennå.</td></tr>
			<?php endif; ?>
			<?php foreach ( (array) $logg as $r ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( 'j.n.Y H:i', (int) $r['tid'] ) ); ?></td>
					<td><?php echo (int) ( $r['sjekket'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['paaminnelse_1'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['paaminnelse_2'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['deaktivert'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['anonymisert'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['venter_foretak'] ?? 0 ); ?></td>
					<td><?php echo (int) ( $r['feil'] ?? 0 ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Brukerlisten
	 * ------------------------------------------------------------------ */

	public static function brukervisning( $visninger ) {
		$antall = count( get_users( array(
			'fields'     => 'ID',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array( 'key' => BV_Hurtigreg::META_OPPRETTET, 'compare' => 'EXISTS' ),
				array( 'key' => BV_Hurtigreg::META_FULLFORT, 'compare' => 'NOT EXISTS' ),
			),
		) ) );
		$aktiv = ! empty( $_GET['bv_hurtig'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$visninger['bv_hurtig'] = sprintf(
			'<a href="%s"%s>Hurtigbrukere <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'bv_hurtig', '1', admin_url( 'users.php' ) ) ),
			$aktiv ? ' class="current" aria-current="page"' : '',
			$antall
		);
		return $visninger;
	}

	/** ?bv_hurtig=1 viser hurtigbrukere som ikke har fullført, også utløpte uten rolle. */
	public static function filtrer_brukere( WP_User_Query $q ): void {
		global $pagenow;
		if ( 'users.php' !== $pagenow || empty( $_GET['bv_hurtig'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$q->set( 'meta_query', array(
			array( 'key' => BV_Hurtigreg::META_OPPRETTET, 'compare' => 'EXISTS' ),
			array( 'key' => BV_Hurtigreg::META_FULLFORT, 'compare' => 'NOT EXISTS' ),
		) );
	}

	public static function kolonne( $kolonner ) {
		$kolonner['bv_hurtig'] = 'Hurtigbruker';
		return $kolonner;
	}

	public static function kolonneverdi( $verdi, $kolonne, $id ) {
		if ( 'bv_hurtig' !== $kolonne || ! BV_Hurtigreg_Bruker::er_hurtig( (int) $id ) ) {
			return $verdi;
		}
		$navn = array(
			'ubekreftet'  => 'Ubekreftet',
			'bekreftet'   => 'Bekreftet',
			'fullfort'    => 'Fullført',
			'utlopt'      => 'Utløpt',
			'anonymisert' => 'Anonymisert',
		);
		$fase     = BV_Hurtigreg_Bruker::fase( (int) $id );
		$kampanje = (string) get_user_meta( (int) $id, BV_Hurtigreg::META_KAMPANJE, true );
		return esc_html( $navn[ $fase ] ?? $fase ) . ( '' !== $kampanje ? '<br><code>' . esc_html( $kampanje ) . '</code>' : '' );
	}
}
