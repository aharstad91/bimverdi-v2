<?php
/**
 * Planlegging, kø og de automatiske handlingene.
 *
 * @package BimVerdi\Lenkevakt
 */

defined( 'ABSPATH' ) || exit;

class BV_Lenkevakt_Planlegger {

	const HOOK_UKENTLIG = 'bimverdi_lenkevakt_ukentlig';
	const HOOK_BATCH    = 'bimverdi_lenkevakt_batch';

	const OPT_KO        = 'bimverdi_lenkevakt_ko';
	const OPT_KJORING   = 'bimverdi_lenkevakt_kjoring';
	const OPT_SISTE     = 'bimverdi_lenkevakt_siste_kjoring';
	const OPT_REPARERT  = 'bimverdi_lenkevakt_reparasjon_101';

	/**
	 * De eneste innholdstypene som noen gang kan settes til utkast automatisk.
	 *
	 * Innlegg, sider og arrangementer er redaksjonelt innhold — en brutt lenke der
	 * er en feil i én lenke, ikke grunn til å avpublisere hele siden. De sjekkes og
	 * rapporteres, men røres aldri.
	 */
	const KAN_KLADDES = array( 'kunnskapskilde' );

	private BV_Lenkevakt_Innstillinger $innstillinger;
	private BV_Lenkevakt_Sjekker $sjekker;
	private BV_Lenkevakt_Logg $logg;
	private BV_Lenkevakt_Rapport $rapport;

	/** Fra best til verst — brukes til å finne postens samlede status. */
	private const RANGERING = array(
		BV_Lenkevakt::STATUS_IKKE_SJEKKBAR => 0,
		BV_Lenkevakt::STATUS_OK            => 1,
		BV_Lenkevakt::STATUS_OPPDATERT     => 2,
		BV_Lenkevakt::STATUS_MIDLERTIDIG   => 3,
		BV_Lenkevakt::STATUS_ADVARSEL      => 4,
		BV_Lenkevakt::STATUS_MANGLER       => 5,
		BV_Lenkevakt::STATUS_BRUTT         => 6,
	);

	public function __construct(
		BV_Lenkevakt_Innstillinger $innstillinger,
		BV_Lenkevakt_Sjekker $sjekker,
		BV_Lenkevakt_Logg $logg,
		BV_Lenkevakt_Rapport $rapport
	) {
		$this->innstillinger = $innstillinger;
		$this->sjekker       = $sjekker;
		$this->logg          = $logg;
		$this->rapport       = $rapport;
	}

	public function koble(): void {
		add_action( self::HOOK_UKENTLIG, array( $this, 'start_runde' ) );
		add_action( self::HOOK_BATCH, array( $this, 'kjor_batch' ) );
		add_action( 'admin_init', array( $this, 'kanskje_reparer' ) );
	}

	/* -------------------------------------------------- Planlegging */

	/**
	 * Planlegg den ukentlige kjøringen på valgt ukedag og klokkeslett (nettstedets tidssone).
	 */
	public static function planlegg_ukentlig(): void {
		$innstillinger = new BV_Lenkevakt_Innstillinger();
		$ukedag        = (int) $innstillinger->hent( 'ukedag' );
		$klokkeslett   = (string) $innstillinger->hent( 'klokkeslett' );

		wp_clear_scheduled_hook( self::HOOK_UKENTLIG );

		$sone  = wp_timezone();
		$naa   = new DateTimeImmutable( 'now', $sone );
		$dager = array( 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday' );
		$navn  = $dager[ $ukedag ] ?? 'monday';

		$neste = new DateTimeImmutable( 'next ' . $navn . ' ' . $klokkeslett, $sone );
		if ( $neste->getTimestamp() <= $naa->getTimestamp() ) {
			$neste = $neste->modify( '+1 week' );
		}

		wp_schedule_event( $neste->getTimestamp(), 'weekly', self::HOOK_UKENTLIG );
	}

	public static function avplanlegg_alt(): void {
		wp_clear_scheduled_hook( self::HOOK_UKENTLIG );
		wp_clear_scheduled_hook( self::HOOK_BATCH );
		delete_option( self::OPT_KO );
		delete_option( self::OPT_KJORING );
	}

	public static function neste_kjoring(): ?int {
		$ts = wp_next_scheduled( self::HOOK_UKENTLIG );
		return $ts ? (int) $ts : null;
	}

	/* -------------------------------------------------- Runde og kø */

	public function kjorer_naa(): bool {
		$kjoring = get_option( self::OPT_KJORING, array() );
		return is_array( $kjoring ) && ! empty( $kjoring['id'] ) && empty( $kjoring['ferdig'] );
	}

	/**
	 * Bygg kø og start første batch.
	 *
	 * @param bool  $manuell Startet fra admin.
	 * @param int[] $bare    Sjekk kun disse post-ID-ene (brukes av «Sjekk nå» på én rad).
	 */
	public function start_runde( bool $manuell = false, array $bare = array() ): string {
		if ( $this->kjorer_naa() && empty( $bare ) ) {
			return (string) ( get_option( self::OPT_KJORING )['id'] ?? '' );
		}

		$ko = ! empty( $bare ) ? array_map( 'absint', $bare ) : $this->finn_poster();

		$kjoring_id = gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 4, false, false );

		update_option(
			self::OPT_KJORING,
			array(
				'id'      => $kjoring_id,
				'start'   => current_time( 'mysql' ),
				'totalt'  => count( $ko ),
				'manuell' => $manuell ? 1 : 0,
				'delvis'  => empty( $bare ) ? 0 : 1,
				'ferdig'  => 0,
			),
			false
		);
		update_option( self::OPT_KO, $ko, false );

		if ( ! wp_next_scheduled( self::HOOK_BATCH ) ) {
			wp_schedule_single_event( time() + 5, self::HOOK_BATCH );
		}

		return $kjoring_id;
	}

	/**
	 * Alle poster som skal overvåkes.
	 */
	private function finn_poster(): array {
		$statuser = array( 'publish' );
		if ( $this->innstillinger->hent( 'sjekk_kladder' ) ) {
			$statuser[] = 'draft';
		}

		$publiserte = get_posts(
			array(
				'post_type'        => (array) $this->innstillinger->hent( 'posttyper' ),
				'post_status'      => $statuser,
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		// Kilder vi selv har satt til kladd sjekkes alltid, så vi ser når lenken er tilbake.
		$draftet = get_posts(
			array(
				'post_type'        => (array) $this->innstillinger->hent( 'posttyper' ),
				'post_status'      => 'draft',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => BV_Lenkevakt::META_DRAFTET, // phpcs:ignore WordPress.DB.SlowDBQuery
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		return array_values( array_unique( array_map( 'absint', array_merge( $publiserte, $draftet ) ) ) );
	}

	/**
	 * Kjør én batch fra køen.
	 */
	public function kjor_batch(): void {
		$ko      = (array) get_option( self::OPT_KO, array() );
		$kjoring = (array) get_option( self::OPT_KJORING, array() );

		if ( empty( $kjoring['id'] ) ) {
			delete_option( self::OPT_KO );
			return;
		}

		if ( empty( $ko ) ) {
			$this->avslutt_runde();
			return;
		}

		$antall = (int) $this->innstillinger->hent( 'batchstorrelse' );
		$denne  = array_splice( $ko, 0, $antall );
		update_option( self::OPT_KO, $ko, false );

		foreach ( $denne as $post_id ) {
			$this->sjekk_post( (int) $post_id, (string) $kjoring['id'] );
		}

		if ( ! empty( $ko ) ) {
			wp_schedule_single_event( time() + 60, self::HOOK_BATCH );
		} else {
			$this->avslutt_runde();
		}
	}

	/* -------------------------------------------------- Sjekk av én post */

	/**
	 * Sjekk alle lenkefelt på én post og utfør de automatiske handlingene.
	 */
	public function sjekk_post( int $post_id, string $kjoring = 'manuell' ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$nokler     = $this->innstillinger->metanokler_for_post( $post_id );
		$resultater = array();
		$verst      = BV_Lenkevakt::STATUS_IKKE_SJEKKBAR;
		$verste_res = null;

		if ( empty( $nokler ) ) {
			$resultat = array(
				'status'      => BV_Lenkevakt::STATUS_MANGLER,
				'kode'        => 0,
				'endelig_url' => '',
				'permanent'   => false,
				'melding'     => 'Fant ingen lenkefelt på kilden.',
				'hopp'        => array(),
			);
			$this->logg->skriv(
				array(
					'post_id'  => $post_id,
					'status'   => BV_Lenkevakt::STATUS_MANGLER,
					'melding'  => $resultat['melding'],
					'handling' => 'ingen',
					'kjoring'  => $kjoring,
				)
			);
			$this->lagre_tilstand( $post_id, $resultat, '' );
			return array( $resultat );
		}

		foreach ( $nokler as $nokkel ) {
			$url = (string) get_post_meta( $post_id, $nokkel, true );
			if ( '' === trim( $url ) ) {
				continue;
			}

			$resultat            = $this->sjekker->sjekk( $url );
			$resultat['nokkel']  = $nokkel;
			$resultat['url']     = $url;
			$handling            = $this->handle( $post_id, $nokkel, $url, $resultat );
			$resultat['handling'] = $handling;

			$this->logg->skriv(
				array(
					'post_id'     => $post_id,
					'meta_nokkel' => $nokkel,
					'url'         => $url,
					'ny_url'      => $resultat['endelig_url'],
					'http_kode'   => $resultat['kode'],
					'status'      => $resultat['status'],
					'handling'    => $handling,
					'melding'     => $resultat['melding'],
					'kjoring'     => $kjoring,
				)
			);

			$resultater[] = $resultat;

			if ( ( self::RANGERING[ $resultat['status'] ] ?? 0 ) >= ( self::RANGERING[ $verst ] ?? 0 ) ) {
				$verst      = $resultat['status'];
				$verste_res = $resultat;
			}
		}

		if ( null === $verste_res ) {
			$verste_res = array(
				'status'      => BV_Lenkevakt::STATUS_MANGLER,
				'kode'        => 0,
				'endelig_url' => '',
				'permanent'   => false,
				'melding'     => 'Lenkefeltet er tomt.',
			);
		}

		$this->lagre_tilstand( $post_id, $verste_res, $verste_res['url'] ?? '' );
		$this->kanskje_draft( $post_id, $verste_res, $kjoring );

		return $resultater;
	}

	/**
	 * Automatisk oppdatering ved permanent flytting.
	 *
	 * @return string Hva som faktisk ble gjort.
	 */
	private function handle( int $post_id, string $nokkel, string $url, array $resultat ): string {
		if ( BV_Lenkevakt::STATUS_OPPDATERT !== $resultat['status'] ) {
			return 'ingen';
		}

		$ny = $resultat['endelig_url'];

		if ( ! $this->innstillinger->hent( 'auto_oppdater_301' ) || $this->innstillinger->hent( 'torrkjoring' ) ) {
			update_post_meta( $post_id, BV_Lenkevakt::META_FORESLATT, $ny );
			return 'foreslatt';
		}

		update_post_meta( $post_id, BV_Lenkevakt::META_FORRIGE_URL, $url );
		update_post_meta( $post_id, $nokkel, $ny );
		delete_post_meta( $post_id, BV_Lenkevakt::META_FORESLATT );

		/**
		 * Handling: en lenke ble skrevet om automatisk.
		 */
		do_action( 'bv_lenkevakt_url_oppdatert', $post_id, $nokkel, $url, $ny );

		return 'oppdatert_url';
	}

	/**
	 * Karantene og eventuell flytting til kladd.
	 */
	private function kanskje_draft( int $post_id, array $resultat, string $kjoring ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$brutt   = BV_Lenkevakt::STATUS_BRUTT === $resultat['status'];
		$paa_rad = (int) get_post_meta( $post_id, BV_Lenkevakt::META_FEIL_PAA_RAD, true );

		if ( ! $brutt ) {
			if ( $paa_rad > 0 ) {
				delete_post_meta( $post_id, BV_Lenkevakt::META_FEIL_PAA_RAD );
			}

			// Var kilden satt til kladd av oss, og lenken svarer igjen? Da skal noen få vite det.
			if ( get_post_meta( $post_id, BV_Lenkevakt::META_DRAFTET, true )
				&& in_array( $resultat['status'], array( BV_Lenkevakt::STATUS_OK, BV_Lenkevakt::STATUS_OPPDATERT ), true ) ) {
				$this->logg->skriv(
					array(
						'post_id'  => $post_id,
						'status'   => $resultat['status'],
						'handling' => 'kan_gjenopprettes',
						'melding'  => 'Lenken svarer igjen. Kilden ligger som kladd og kan publiseres på nytt.',
						'kjoring'  => $kjoring,
					)
				);
			}
			return;
		}

		$paa_rad++;
		update_post_meta( $post_id, BV_Lenkevakt::META_FEIL_PAA_RAD, $paa_rad );

		$terskel = (int) $this->innstillinger->hent( 'terskel_brutt' );

		if ( $paa_rad < $terskel ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Hard sperre: bare kunnskapskilder kan avpubliseres av maskinen.
		if ( ! self::kan_kladdes( $post->post_type ) ) {
			$this->logg->skriv(
				array(
					'post_id'   => $post_id,
					'status'    => BV_Lenkevakt::STATUS_BRUTT,
					'http_kode' => (int) $resultat['kode'],
					'handling'  => 'kun_rapportert',
					'melding'   => sprintf(
						'Brutt lenke i innholdstypen «%s». Denne typen settes aldri til utkast automatisk — lenka må rettes manuelt.',
						$post->post_type
					),
					'kjoring'   => $kjoring,
				)
			);
			return;
		}
		if ( ! $this->innstillinger->hent( 'auto_draft_404' ) || $this->innstillinger->hent( 'torrkjoring' ) ) {
			$this->logg->skriv(
				array(
					'post_id'  => $post_id,
					'status'   => BV_Lenkevakt::STATUS_BRUTT,
					'handling' => 'draft_foreslatt',
					'melding'  => sprintf( 'Brutt %d uker på rad. Automatisk kladd er slått av.', $paa_rad ),
					'kjoring'  => $kjoring,
				)
			);
			return;
		}

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		update_post_meta( $post_id, BV_Lenkevakt::META_DRAFTET, current_time( 'mysql' ) );

		$this->logg->skriv(
			array(
				'post_id'   => $post_id,
				'status'    => BV_Lenkevakt::STATUS_BRUTT,
				'http_kode' => (int) $resultat['kode'],
				'handling'  => 'satt_til_kladd',
				'melding'   => sprintf( 'Brutt %d kjøringer på rad — flyttet til kladd.', $paa_rad ),
				'kjoring'   => $kjoring,
			)
		);

		/**
		 * Handling: en kunnskapskilde ble satt til kladd av BV lenkevakt.
		 */
		do_action( 'bv_lenkevakt_satt_til_kladd', $post_id, $resultat );
	}

	/**
	 * Kan denne innholdstypen settes til utkast av maskinen?
	 */
	public static function kan_kladdes( string $posttype ): bool {
		/**
		 * Filter: innholdstyper som kan avpubliseres automatisk ved brutt lenke.
		 * Utvid bare dette bevisst — redaksjonelt innhold hører ikke hjemme her.
		 */
		$tillatte = (array) apply_filters( 'bv_lenkevakt_posttyper_som_kan_kladdes', self::KAN_KLADDES );
		return in_array( $posttype, $tillatte, true );
	}

	/**
	 * Rydd opp etter en tidligere versjon: publiser igjen alt som overvåkingen
	 * har satt til utkast i innholdstyper som aldri skulle vært rørt.
	 *
	 * Trygg å kjøre flere ganger — den ser bare på poster som bærer vårt eget
	 * `_lenkevakt_draftet`-merke, altså poster som var publisert da vi tok dem ned.
	 *
	 * @return array Gjenopprettede poster, gruppert per innholdstype.
	 */
	public function gjenopprett_feilaktig_kladdede(): array {
		$kandidater = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => array( 'draft', 'pending' ),
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => BV_Lenkevakt::META_DRAFTET, // phpcs:ignore WordPress.DB.SlowDBQuery
				'suppress_filters' => true,
				'no_found_rows'    => true,
			)
		);

		$gjenopprettet = array();

		foreach ( $kandidater as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post || self::kan_kladdes( $post->post_type ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'publish',
				)
			);

			delete_post_meta( $post->ID, BV_Lenkevakt::META_DRAFTET );
			delete_post_meta( $post->ID, BV_Lenkevakt::META_FEIL_PAA_RAD );

			$this->logg->skriv(
				array(
					'post_id'  => $post->ID,
					'status'   => BV_Lenkevakt::STATUS_ADVARSEL,
					'handling' => 'gjenopprettet_automatisk',
					'melding'  => sprintf(
						'Publisert igjen ved opprydding: innholdstypen «%s» skal aldri settes til utkast av overvåkingen.',
						$post->post_type
					),
					'kjoring'  => 'reparasjon',
				)
			);

			$gjenopprettet[ $post->post_type ][] = $post->ID;
		}

		return $gjenopprettet;
	}

	/**
	 * Kjør oppryddingen én gang, og legg igjen et resultat til admin-varselet.
	 */
	public function kanskje_reparer(): void {
		if ( get_option( self::OPT_REPARERT, false ) ) {
			return;
		}

		$gjenopprettet = $this->gjenopprett_feilaktig_kladdede();

		update_option(
			self::OPT_REPARERT,
			array(
				'kjort'         => current_time( 'mysql' ),
				'gjenopprettet' => $gjenopprettet,
			),
			false
		);
	}

	/**
	 * Publiser en kilde vi selv satte til kladd.
	 */
	public function gjenopprett( int $post_id ): bool {
		if ( ! get_post_meta( $post_id, BV_Lenkevakt::META_DRAFTET, true ) ) {
			return false;
		}
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		delete_post_meta( $post_id, BV_Lenkevakt::META_DRAFTET );
		delete_post_meta( $post_id, BV_Lenkevakt::META_FEIL_PAA_RAD );

		$this->logg->skriv(
			array(
				'post_id'  => $post_id,
				'status'   => BV_Lenkevakt::STATUS_OK,
				'handling' => 'gjenopprettet',
				'melding'  => 'Publisert på nytt manuelt fra BV lenkevakt.',
				'kjoring'  => 'manuell',
			)
		);
		return true;
	}

	/**
	 * Ta i bruk en foreslått URL (når automatisk oppdatering er avslått).
	 */
	public function godta_foreslatt( int $post_id ): bool {
		$ny = (string) get_post_meta( $post_id, BV_Lenkevakt::META_FORESLATT, true );
		if ( '' === $ny ) {
			return false;
		}

		foreach ( $this->innstillinger->metanokler_for_post( $post_id ) as $nokkel ) {
			$gammel = (string) get_post_meta( $post_id, $nokkel, true );
			if ( '' === trim( $gammel ) ) {
				continue;
			}
			update_post_meta( $post_id, BV_Lenkevakt::META_FORRIGE_URL, $gammel );
			update_post_meta( $post_id, $nokkel, $ny );
			break;
		}

		delete_post_meta( $post_id, BV_Lenkevakt::META_FORESLATT );
		update_post_meta( $post_id, BV_Lenkevakt::META_STATUS, BV_Lenkevakt::STATUS_OK );

		$this->logg->skriv(
			array(
				'post_id'  => $post_id,
				'ny_url'   => $ny,
				'status'   => BV_Lenkevakt::STATUS_OK,
				'handling' => 'godtatt_manuelt',
				'melding'  => 'Foreslått lenke tatt i bruk manuelt.',
				'kjoring'  => 'manuell',
			)
		);
		return true;
	}

	/* -------------------------------------------------- Tilstand og avslutning */

	private function lagre_tilstand( int $post_id, array $resultat, string $url ): void {
		update_post_meta( $post_id, BV_Lenkevakt::META_STATUS, $resultat['status'] );
		update_post_meta( $post_id, BV_Lenkevakt::META_KODE, (int) $resultat['kode'] );
		update_post_meta( $post_id, BV_Lenkevakt::META_SIST_SJEKKET, current_time( 'mysql' ) );
		update_post_meta( $post_id, BV_Lenkevakt::META_MELDING, (string) $resultat['melding'] );
		if ( '' !== $url ) {
			update_post_meta( $post_id, BV_Lenkevakt::META_SJEKKET_URL, $url );
		}
	}

	private function avslutt_runde(): void {
		$kjoring = (array) get_option( self::OPT_KJORING, array() );
		if ( empty( $kjoring['id'] ) ) {
			return;
		}

		$kjoring['ferdig'] = 1;
		$kjoring['slutt']  = current_time( 'mysql' );

		$rader   = $this->logg->for_kjoring( (string) $kjoring['id'] );
		$telling = array();
		foreach ( $rader as $rad ) {
			$status             = (string) $rad['status'];
			$telling[ $status ] = ( $telling[ $status ] ?? 0 ) + 1;
		}
		$kjoring['telling'] = $telling;

		update_option( self::OPT_KJORING, $kjoring, false );
		update_option( self::OPT_SISTE, $kjoring, false );
		delete_option( self::OPT_KO );

		if ( empty( $kjoring['delvis'] ) ) {
			$this->rapport->send( (string) $kjoring['id'], $kjoring );
			$this->logg->rydd( 365 );
		}

		/**
		 * Handling: en full runde er ferdig.
		 */
		do_action( 'bv_lenkevakt_runde_ferdig', $kjoring );
	}

	public function siste_kjoring(): array {
		$siste = get_option( self::OPT_SISTE, array() );
		return is_array( $siste ) ? $siste : array();
	}

	public function kolengde(): int {
		return count( (array) get_option( self::OPT_KO, array() ) );
	}
}
