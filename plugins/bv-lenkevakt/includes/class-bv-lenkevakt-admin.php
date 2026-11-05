<?php
/**
 * Admin: rapportside, innstillinger, statuskolonne.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Admin {

	const KAPABILITET = 'manage_options';

	private BV_Lenkevakt_Innstillinger $innstillinger;
	private BV_Lenkevakt_Planlegger $planlegger;
	private BV_Lenkevakt_Logg $logg;

	public function __construct( BV_Lenkevakt_Innstillinger $innstillinger, BV_Lenkevakt_Planlegger $planlegger, BV_Lenkevakt_Logg $logg ) {
		$this->innstillinger = $innstillinger;
		$this->planlegger    = $planlegger;
		$this->logg          = $logg;
	}

	const OPT_FORELDER = 'bimverdi_lenkevakt_menyforelder';

	public function koble(): void {
		// Prioritet 100: alle innholdstypenes egne menyer er registrert innen da,
		// så vi kan sjekke hvor kunnskapskildene faktisk ligger.
		add_action( 'admin_menu', array( $this, 'meny' ), 100 );
		add_filter( 'plugin_action_links_' . plugin_basename( BV_LENKEVAKT_FIL ), array( $this, 'plugin_lenker' ) );
		add_action( 'admin_post_bv_lenkevakt_lagre', array( $this, 'handter_lagre' ) );
		add_action( 'admin_post_bv_lenkevakt_start', array( $this, 'handter_start' ) );
		add_action( 'admin_post_bv_lenkevakt_rad', array( $this, 'handter_rad' ) );
		add_action( 'admin_notices', array( $this, 'varsler' ) );

		foreach ( (array) $this->innstillinger->hent( 'posttyper' ) as $posttype ) {
			add_filter( "manage_{$posttype}_posts_columns", array( $this, 'kolonne' ) );
			add_action( "manage_{$posttype}_posts_custom_column", array( $this, 'kolonne_innhold' ), 10, 2 );
			add_filter( "manage_edit-{$posttype}_sortable_columns", array( $this, 'sorterbar' ) );
		}
		add_action( 'pre_get_posts', array( $this, 'sortering' ) );
	}

	private function hovedposttype(): string {
		$typer = (array) $this->innstillinger->hent( 'posttyper' );
		return (string) ( reset( $typer ) ?: 'kunnskapskilde' );
	}

	/**
	 * Finn ut hvor i menyen kunnskapskildene faktisk ligger.
	 *
	 * En innholdstype kan være registrert med `show_in_menu` satt til en annen
	 * meny, eller til false. Da finnes ikke `edit.php?post_type=…` som forelder,
	 * og en underside hektet der blir usynlig. Vi sjekker derfor mot de virkelige
	 * menyglobalene, og faller tilbake til egen toppnivåmeny hvis vi ikke finner hjem.
	 */
	private function resolver_forelder(): string {
		$posttype = $this->hovedposttype();
		$objekt   = get_post_type_object( $posttype );
		$forelder = '';

		if ( $objekt ) {
			if ( is_string( $objekt->show_in_menu ) && '' !== $objekt->show_in_menu ) {
				$forelder = $objekt->show_in_menu;
			} elseif ( false !== $objekt->show_in_menu ) {
				$forelder = 'edit.php?post_type=' . $posttype;
			}
		}

		if ( '' !== $forelder && ! $this->finnes_i_meny( $forelder ) ) {
			$forelder = '';
		}

		return $forelder;
	}

	private function finnes_i_meny( string $slug ): bool {
		global $menu, $submenu;

		if ( isset( $submenu[ $slug ] ) ) {
			return true;
		}
		foreach ( (array) $menu as $oppforing ) {
			if ( isset( $oppforing[2] ) && $oppforing[2] === $slug ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * URL til en av våre sider, uansett hvor menyen endte opp.
	 */
	public static function side_url( string $side = 'bv-lenkevakt', array $ekstra = array() ): string {
		$forelder = (string) get_option( self::OPT_FORELDER, '' );

		if ( '' === $forelder || false === strpos( $forelder, '.php' ) ) {
			return add_query_arg( array_merge( array( 'page' => $side ), $ekstra ), admin_url( 'admin.php' ) );
		}

		$deler = wp_parse_url( $forelder );
		$basis = $deler['path'] ?? $forelder;
		$args  = array();
		if ( ! empty( $deler['query'] ) ) {
			wp_parse_str( $deler['query'], $args );
		}

		return add_query_arg( array_merge( $args, array( 'page' => $side ), $ekstra ), admin_url( $basis ) );
	}

	public function meny(): void {
		$forelder = $this->resolver_forelder();
		update_option( self::OPT_FORELDER, $forelder, false );

		if ( '' !== $forelder ) {
			add_submenu_page(
				$forelder,
				'BV lenkevakt',
				'BV lenkevakt',
				self::KAPABILITET,
				'bv-lenkevakt',
				array( $this, 'side_rapport' )
			);
			add_submenu_page(
				$forelder,
				'BV lenkevakt — innstillinger',
				'— Innstillinger',
				self::KAPABILITET,
				'bv-lenkevakt-innstillinger',
				array( $this, 'side_innstillinger' )
			);
			return;
		}

		// Fant ikke kunnskapskildenes meny — stå på egne bein.
		add_menu_page(
			'BV lenkevakt',
			'BV lenkevakt',
			self::KAPABILITET,
			'bv-lenkevakt',
			array( $this, 'side_rapport' ),
			'dashicons-admin-links',
			58
		);
		add_submenu_page(
			'bv-lenkevakt',
			'BV lenkevakt — rapport',
			'Rapport',
			self::KAPABILITET,
			'bv-lenkevakt',
			array( $this, 'side_rapport' )
		);
		add_submenu_page(
			'bv-lenkevakt',
			'BV lenkevakt — innstillinger',
			'Innstillinger',
			self::KAPABILITET,
			'bv-lenkevakt-innstillinger',
			array( $this, 'side_innstillinger' )
		);
	}

	/**
	 * Snarveier fra Tillegg-lista — alltid tilgjengelige, uansett menyoppsett.
	 */
	public function plugin_lenker( array $lenker ): array {
		array_unshift(
			$lenker,
			'<a href="' . esc_url( self::side_url( 'bv-lenkevakt' ) ) . '">Rapport</a>',
			'<a href="' . esc_url( self::side_url( 'bv-lenkevakt-innstillinger' ) ) . '">Innstillinger</a>'
		);
		return $lenker;
	}

	/* -------------------------------------------------- Kolonne i listevisningen */

	public function kolonne( array $kolonner ): array {
		$ny = array();
		foreach ( $kolonner as $nokkel => $etikett ) {
			$ny[ $nokkel ] = $etikett;
			if ( 'title' === $nokkel ) {
				$ny['bv_lenkestatus'] = 'Lenkestatus';
			}
		}
		if ( ! isset( $ny['bv_lenkestatus'] ) ) {
			$ny['bv_lenkestatus'] = 'Lenkestatus';
		}
		return $ny;
	}

	public function kolonne_innhold( string $kolonne, int $post_id ): void {
		if ( 'bv_lenkestatus' !== $kolonne ) {
			return;
		}

		$status = (string) get_post_meta( $post_id, BV_Lenkevakt::META_STATUS, true );
		$sist   = (string) get_post_meta( $post_id, BV_Lenkevakt::META_SIST_SJEKKET, true );

		if ( '' === $status ) {
			echo '<span style="color:#8c8f94;">Ikke sjekket</span>';
			return;
		}

		printf(
			'<span style="color:%1$s;">&#9679;</span> %2$s',
			esc_attr( BV_Lenkevakt::status_farge( $status ) ),
			esc_html( BV_Lenkevakt::status_etikett( $status ) )
		);

		$kode = (int) get_post_meta( $post_id, BV_Lenkevakt::META_KODE, true );
		if ( $kode ) {
			echo ' <span style="color:#8c8f94;">(' . absint( $kode ) . ')</span>';
		}
		if ( $sist ) {
			echo '<br><span style="color:#8c8f94;font-size:12px;">' . esc_html( $this->dato( $sist ) ) . '</span>';
		}
	}

	public function sorterbar( array $kolonner ): array {
		$kolonner['bv_lenkestatus'] = 'bv_lenkestatus';
		return $kolonner;
	}

	public function sortering( WP_Query $sporring ): void {
		if ( ! is_admin() || ! $sporring->is_main_query() ) {
			return;
		}
		if ( 'bv_lenkestatus' !== $sporring->get( 'orderby' ) ) {
			return;
		}
		$sporring->set( 'meta_key', BV_Lenkevakt::META_STATUS );
		$sporring->set( 'orderby', 'meta_value' );
	}

	/* -------------------------------------------------- Rapportside */

	public function side_rapport(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			wp_die( 'Du har ikke tilgang til denne siden.' );
		}

		$filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'avvik'; // phpcs:ignore WordPress.Security.NonceVerification
		$siste  = $this->planlegger->siste_kjoring();
		$neste  = BV_Lenkevakt_Planlegger::neste_kjoring();
		$igjen  = $this->planlegger->kolengde();

		echo '<div class="wrap">';
		echo '<h1>BV lenkevakt</h1>';
		echo '<p class="description" style="max-width:60em;">Sjekker de eksterne lenkene én gang i uka. Permanent flyttede lenker skrives om automatisk. Er lenka borte '
			. absint( $this->innstillinger->hent( 'terskel_brutt' ) ) . ' kjøringer på rad, settes <em>kunnskapskilden</em> til utkast — '
			. 'innlegg, sider og arrangementer blir aldri avpublisert, bare rapportert.</p>';

		if ( $this->innstillinger->hent( 'torrkjoring' ) ) {
			echo '<div class="notice notice-warning inline"><p><strong>Tørrkjøring er på.</strong> BV lenkevakt rapporterer, men endrer ingenting.</p></div>';
		}

		echo '<div style="display:flex;gap:28px;flex-wrap:wrap;align-items:center;margin:18px 0 6px;padding:14px 0;border-top:1px solid #dcdcde;border-bottom:1px solid #dcdcde;">';
		echo '<div><strong>Neste kjøring</strong><br>' . esc_html( $neste ? wp_date( 'j. M Y H:i', $neste ) : 'ikke planlagt' ) . '</div>';
		echo '<div><strong>Sist fullført</strong><br>' . esc_html( ! empty( $siste['slutt'] ) ? $this->dato( (string) $siste['slutt'] ) : 'aldri' ) . '</div>';
		echo '<div><strong>Sjekket sist</strong><br>' . absint( $siste['totalt'] ?? 0 ) . ' kilder</div>';
		if ( $igjen > 0 ) {
			echo '<div><strong>Kjører nå</strong><br>' . absint( $igjen ) . ' igjen i køen</div>';
		}
		echo '<div style="margin-left:auto;">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bv_lenkevakt_start' );
		echo '<input type="hidden" name="action" value="bv_lenkevakt_start">';
		echo '<button type="submit" class="button button-primary">Kjør kontroll nå</button>';
		echo '</form></div>';
		echo '</div>';

		$this->filterlenker( $filter );
		$this->tabell( $filter );

		echo '</div>';
	}

	private function filterlenker( string $aktiv ): void {
		$valg = array(
			'avvik'                          => 'Avvik',
			'alle'                           => 'Alle',
			BV_Lenkevakt::STATUS_BRUTT       => 'Brutte',
			BV_Lenkevakt::STATUS_ADVARSEL    => 'Trenger blikk',
			BV_Lenkevakt::STATUS_OPPDATERT   => 'Oppdaterte',
			BV_Lenkevakt::STATUS_MANGLER     => 'Uten lenke',
			'kladdet'                        => 'Satt til kladd',
		);

		echo '<ul class="subsubsub" style="margin:14px 0 8px;">';
		$i = 0;
		foreach ( $valg as $nokkel => $etikett ) {
			$i++;
			$url = self::side_url( 'bv-lenkevakt', array( 'status' => $nokkel ) );
			printf(
				'<li>%s<a href="%s"%s>%s</a>%s</li>',
				'',
				esc_url( $url ),
				$aktiv === $nokkel ? ' class="current"' : '',
				esc_html( $etikett ),
				$i < count( $valg ) ? ' |' : ''
			);
		}
		echo '</ul><div style="clear:both;"></div>';
	}

	private function tabell( string $filter ): void {
		$argumenter = array(
			'post_type'      => (array) $this->innstillinger->hent( 'posttyper' ),
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		if ( 'kladdet' === $filter ) {
			$argumenter['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => BV_Lenkevakt::META_DRAFTET,
					'compare' => 'EXISTS',
				),
			);
		} elseif ( 'avvik' === $filter ) {
			$argumenter['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => BV_Lenkevakt::META_STATUS,
					'value'   => array( BV_Lenkevakt::STATUS_BRUTT, BV_Lenkevakt::STATUS_ADVARSEL, BV_Lenkevakt::STATUS_MANGLER, BV_Lenkevakt::STATUS_OPPDATERT, BV_Lenkevakt::STATUS_MIDLERTIDIG ),
					'compare' => 'IN',
				),
			);
		} elseif ( 'alle' !== $filter ) {
			$argumenter['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'   => BV_Lenkevakt::META_STATUS,
					'value' => $filter,
				),
			);
		}

		$sporring = new WP_Query( $argumenter );

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>'
			. '<th style="width:24%;">Kilde</th>'
			. '<th style="width:10%;">Publisering</th>'
			. '<th style="width:13%;">Lenkestatus</th>'
			. '<th>Lenke</th>'
			. '<th style="width:12%;">Sist sjekket</th>'
			. '<th style="width:15%;">Handling</th>'
			. '</tr></thead><tbody>';

		if ( ! $sporring->have_posts() ) {
			echo '<tr><td colspan="6">Ingen kilder i denne visningen. Kjør en kontroll for å fylle lista.</td></tr>';
		}

		while ( $sporring->have_posts() ) {
			$sporring->the_post();
			$post_id = get_the_ID();

			$status    = (string) get_post_meta( $post_id, BV_Lenkevakt::META_STATUS, true );
			$kode      = (int) get_post_meta( $post_id, BV_Lenkevakt::META_KODE, true );
			$melding   = (string) get_post_meta( $post_id, BV_Lenkevakt::META_MELDING, true );
			$url       = (string) get_post_meta( $post_id, BV_Lenkevakt::META_SJEKKET_URL, true );
			$sist      = (string) get_post_meta( $post_id, BV_Lenkevakt::META_SIST_SJEKKET, true );
			$foreslatt = (string) get_post_meta( $post_id, BV_Lenkevakt::META_FORESLATT, true );
			$draftet   = (string) get_post_meta( $post_id, BV_Lenkevakt::META_DRAFTET, true );
			$paa_rad   = (int) get_post_meta( $post_id, BV_Lenkevakt::META_FEIL_PAA_RAD, true );

			echo '<tr>';

			echo '<td><strong><a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '">' . esc_html( get_the_title() ) . '</a></strong>';
			if ( $paa_rad > 0 ) {
				echo '<br><span style="color:#bf8700;font-size:12px;">Brutt ' . absint( $paa_rad ) . ' kjøring(er) på rad</span>';
			}
			echo '</td>';

			$publisering = $this->publisering( get_post_status( $post_id ) );
			echo '<td><span style="color:' . esc_attr( $publisering['farge'] ) . ';font-weight:600;">'
				. esc_html( $publisering['etikett'] ) . '</span>';
			if ( $draftet ) {
				echo '<br><span style="color:#8c8f94;font-size:12px;">av BV lenkevakt ' . esc_html( $this->dato( $draftet ) ) . '</span>';
			}
			echo '</td>';

			echo '<td><span style="color:' . esc_attr( BV_Lenkevakt::status_farge( $status ) ) . ';">&#9679;</span> '
				. esc_html( BV_Lenkevakt::status_etikett( $status ) )
				. ( $kode ? ' <span style="color:#8c8f94;">(' . absint( $kode ) . ')</span>' : '' ) . '</td>';

			echo '<td style="word-break:break-all;">';
			if ( $url ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a>';
			} else {
				echo '<span style="color:#8c8f94;">—</span>';
			}
			if ( $foreslatt ) {
				echo '<br><span style="color:#bf8700;font-size:12px;">Foreslått: ' . esc_html( $foreslatt ) . '</span>';
			}
			if ( $melding ) {
				echo '<br><span style="color:#646970;font-size:12px;">' . esc_html( $melding ) . '</span>';
			}
			echo '</td>';

			echo '<td>' . esc_html( $sist ? $this->dato( $sist ) : '—' ) . '</td>';

			echo '<td>';
			echo $this->radknapp( $post_id, 'sjekk', 'Sjekk nå' ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( $foreslatt ) {
				echo ' ' . $this->radknapp( $post_id, 'godta', 'Bruk ny lenke' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( $draftet ) {
				echo ' ' . $this->radknapp( $post_id, 'gjenopprett', 'Publiser igjen' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</td>';

			echo '</tr>';
		}

		wp_reset_postdata();
		echo '</tbody></table>';
	}

	private function radknapp( int $post_id, string $handling, string $etikett ): string {
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'bv_lenkevakt_rad',
					'handling' => $handling,
					'post'     => $post_id,
				),
				admin_url( 'admin-post.php' )
			),
			'bv_lenkevakt_rad_' . $post_id
		);
		return '<a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html( $etikett ) . '</a>';
	}

	/* -------------------------------------------------- Innstillingsside */

	public function side_innstillinger(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			wp_die( 'Du har ikke tilgang til denne siden.' );
		}

		$i        = $this->innstillinger->alle();
		$kandidat = $this->innstillinger->finn_lenkefelt();

		echo '<div class="wrap"><h1>BV lenkevakt — innstillinger</h1>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bv_lenkevakt_lagre' );
		echo '<input type="hidden" name="action" value="bv_lenkevakt_lagre">';

		echo '<table class="form-table" role="presentation">';

		// Posttyper.
		echo '<tr><th scope="row">Innholdstyper</th><td>';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) {
			$kan_kladdes = BV_Lenkevakt_Planlegger::kan_kladdes( $pt->name );
			printf(
				'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="posttyper[]" value="%s"%s> %s%s</label>',
				esc_attr( $pt->name ),
				in_array( $pt->name, (array) $i['posttyper'], true ) ? ' checked' : '',
				esc_html( $pt->labels->name ),
				$kan_kladdes ? '' : ' <span style="color:#8c8f94;font-size:12px;">— sjekkes og rapporteres, settes aldri til utkast</span>'
			);
		}
		echo '<p class="description"><strong>Kun kunnskapskilder kan avpubliseres automatisk.</strong> Innlegg, sider, arrangementer og alt annet blir bare kontrollert og rapportert — en brutt lenke der er én lenke som må rettes, ikke grunn til å ta ned siden.</p></td></tr>';

		// Metanøkler.
		echo '<tr><th scope="row">Lenkefelt</th><td>';
		if ( empty( $kandidat ) ) {
			echo '<p class="description">Fant ingen felt med http-adresser ennå.</p>';
		} else {
			foreach ( $kandidat as $rad ) {
				printf(
					'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="metanokler[]" value="%1$s"%2$s> <code>%1$s</code> <span style="color:#8c8f94;">(%3$d kilder)</span></label>',
					esc_attr( $rad['nokkel'] ),
					in_array( $rad['nokkel'], (array) $i['metanokler'], true ) ? ' checked' : '',
					absint( $rad['antall'] )
				);
			}
		}
		echo '<p class="description">La alt stå uhuket for å la BV lenkevakt finne lenkefeltene selv på hver kilde.</p></td></tr>';

		// Tidspunkt.
		echo '<tr><th scope="row">Ukentlig kjøring</th><td>';
		echo '<select name="ukedag">';
		foreach ( array( 1 => 'mandag', 2 => 'tirsdag', 3 => 'onsdag', 4 => 'torsdag', 5 => 'fredag', 6 => 'lørdag', 7 => 'søndag' ) as $nr => $navn ) {
			printf( '<option value="%d"%s>%s</option>', $nr, (int) $i['ukedag'] === $nr ? ' selected' : '', esc_html( ucfirst( $navn ) ) );
		}
		echo '</select> ';
		printf( '<input type="time" name="klokkeslett" value="%s">', esc_attr( $i['klokkeslett'] ) );
		echo '<p class="description">Nettstedets tidssone. Endringer trer i kraft når du lagrer.</p></td></tr>';

		// Automatikk.
		echo '<tr><th scope="row">Automatiske handlinger</th><td>';
		printf(
			'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="auto_oppdater_301" value="1"%s> Skriv om lenken automatisk ved permanent flytting (301/308)</label>',
			$i['auto_oppdater_301'] ? ' checked' : ''
		);
		printf(
			'<label style="display:block;margin-bottom:6px;"><input type="checkbox" name="auto_draft_404" value="1"%s> Sett kunnskapskilden til utkast når lenken er borte (404/410/dødt domene) — gjelder aldri innlegg, sider eller arrangementer</label>',
			$i['auto_draft_404'] ? ' checked' : ''
		);
		printf(
			'<label style="display:block;"><input type="checkbox" name="torrkjoring" value="1"%s> Tørrkjøring — rapporter, men endre ingenting</label>',
			$i['torrkjoring'] ? ' checked' : ''
		);
		echo '</td></tr>';

		echo '<tr><th scope="row">Karantene</th><td>';
		printf( '<input type="number" name="terskel_brutt" min="1" max="5" value="%d" class="small-text"> kjøringer på rad før kladd', absint( $i['terskel_brutt'] ) );
		echo '<p class="description">Med ukentlig kjøring betyr 2 at en kilde må være borte to uker på rad. 403 og 5xx teller aldri som brutt.</p></td></tr>';

		echo '<tr><th scope="row">Rapport på e-post</th><td>';
		printf( '<label><input type="checkbox" name="e_post_aktiv" value="1"%s> Send ukentlig sammendrag</label><br><br>', $i['e_post_aktiv'] ? ' checked' : '' );
		printf( '<input type="email" name="e_post_mottaker" value="%s" class="regular-text">', esc_attr( $i['e_post_mottaker'] ) );
		echo '</td></tr>';

		echo '<tr><th scope="row">Avansert</th><td>';
		printf( '<label>Tidsavbrudd <input type="number" name="timeout" min="5" max="60" value="%d" class="small-text"> sekunder</label><br><br>', absint( $i['timeout'] ) );
		printf( '<label>Kilder per bolk <input type="number" name="batchstorrelse" min="5" max="100" value="%d" class="small-text"></label>', absint( $i['batchstorrelse'] ) );
		printf(
			'<br><br><label><input type="checkbox" name="sjekk_kladder" value="1"%s> Sjekk også kilder som allerede ligger som kladd</label>',
			$i['sjekk_kladder'] ? ' checked' : ''
		);
		echo '<p class="description">Bolkene kjøres med ett minutts mellomrom, så en full runde på 250 kilder tar rundt ti minutter.</p></td></tr>';

		echo '</table>';
		submit_button( 'Lagre innstillinger' );
		echo '</form></div>';
	}

	/* -------------------------------------------------- Handlere */

	public function handter_lagre(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			wp_die( 'Ingen tilgang.' );
		}
		check_admin_referer( 'bv_lenkevakt_lagre' );

		$rent = $this->innstillinger->rens( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$this->innstillinger->lagre( $rent );
		BV_Lenkevakt_Planlegger::planlegg_ukentlig();

		wp_safe_redirect( self::side_url( 'bv-lenkevakt-innstillinger', array( 'bv_lenkevakt' => 'lagret' ) ) );
		exit;
	}

	public function handter_start(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			wp_die( 'Ingen tilgang.' );
		}
		check_admin_referer( 'bv_lenkevakt_start' );

		$this->planlegger->start_runde( true );

		wp_safe_redirect( self::side_url( 'bv-lenkevakt', array( 'bv_lenkevakt' => 'startet' ) ) );
		exit;
	}

	public function handter_rad(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			wp_die( 'Ingen tilgang.' );
		}

		$post_id  = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$handling = isset( $_GET['handling'] ) ? sanitize_key( wp_unslash( $_GET['handling'] ) ) : '';

		check_admin_referer( 'bv_lenkevakt_rad_' . $post_id );

		$melding = 'ukjent';

		switch ( $handling ) {
			case 'sjekk':
				$this->planlegger->sjekk_post( $post_id, 'manuell' );
				$melding = 'sjekket';
				break;
			case 'gjenopprett':
				$melding = $this->planlegger->gjenopprett( $post_id ) ? 'gjenopprettet' : 'feilet';
				break;
			case 'godta':
				$melding = $this->planlegger->godta_foreslatt( $post_id ) ? 'godtatt' : 'feilet';
				break;
		}

		wp_safe_redirect( self::side_url( 'bv-lenkevakt', array( 'bv_lenkevakt' => $melding ) ) );
		exit;
	}

	public function varsler(): void {
		$this->reparasjonsvarsel();

		if ( empty( $_GET['bv_lenkevakt'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$kode = sanitize_key( wp_unslash( $_GET['bv_lenkevakt'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		$tekster = array(
			'lagret'         => array( 'success', 'Innstillingene er lagret, og den ukentlige kjøringen er planlagt på nytt.' ),
			'startet'        => array( 'success', 'Kontrollen er startet. Den kjører i bolker i bakgrunnen — oppdater siden om et par minutter.' ),
			'sjekket'        => array( 'success', 'Kilden er sjekket på nytt.' ),
			'gjenopprettet'  => array( 'success', 'Kilden er publisert igjen.' ),
			'godtatt'        => array( 'success', 'Den nye lenken er tatt i bruk.' ),
			'feilet'         => array( 'error', 'Handlingen kunne ikke gjennomføres.' ),
		);

		if ( ! isset( $tekster[ $kode ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $tekster[ $kode ][0] ),
			esc_html( $tekster[ $kode ][1] )
		);
	}

	/**
	 * Varsel etter oppryddingen i 1.0.1 — vises til den er lukket.
	 */
	private function reparasjonsvarsel(): void {
		if ( ! current_user_can( self::KAPABILITET ) ) {
			return;
		}

		$resultat = get_option( BV_Lenkevakt_Planlegger::OPT_REPARERT, false );
		if ( ! is_array( $resultat ) || empty( $resultat['gjenopprettet'] ) ) {
			return;
		}

		if ( ! empty( $resultat['varslet'] ) ) {
			return;
		}

		$deler = array();
		$sum   = 0;
		foreach ( (array) $resultat['gjenopprettet'] as $posttype => $ider ) {
			$objekt  = get_post_type_object( (string) $posttype );
			$navn    = $objekt ? $objekt->labels->name : $posttype;
			$antall  = count( (array) $ider );
			$sum    += $antall;
			$deler[] = sprintf( '%d %s', $antall, mb_strtolower( (string) $navn ) );
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>BV lenkevakt ryddet opp.</strong> %s er publisert igjen: %s. Fra og med denne versjonen settes kun kunnskapskilder til utkast automatisk — annet innhold blir bare rapportert.</p></div>',
			esc_html( 1 === $sum ? 'Én post' : $sum . ' poster' ),
			esc_html( implode( ', ', $deler ) )
		);

		$resultat['varslet'] = 1;
		update_option( BV_Lenkevakt_Planlegger::OPT_REPARERT, $resultat, false );
	}

	/**
	 * Norsk etikett og farge for postens publiseringstilstand.
	 *
	 * @return array{etikett:string,farge:string}
	 */
	private function publisering( string $poststatus ): array {
		$kart = array(
			'publish' => array( 'Publisert', '#1a7f37' ),
			'draft'   => array( 'Utkast', '#b32d2e' ),
			'pending' => array( 'Til gjennomgang', '#bf8700' ),
			'future'  => array( 'Planlagt', '#2271b1' ),
			'private' => array( 'Privat', '#646970' ),
			'trash'   => array( 'I papirkurven', '#8c8f94' ),
		);

		$treff = $kart[ $poststatus ] ?? array( ucfirst( $poststatus ), '#8c8f94' );

		return array(
			'etikett' => $treff[0],
			'farge'   => $treff[1],
		);
	}

	private function dato( string $mysql ): string {
		$ts = strtotime( $mysql );
		return $ts ? wp_date( 'j. M Y H:i', $ts ) : $mysql;
	}
}
