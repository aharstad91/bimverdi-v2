<?php
/**
 * Hovedorkestrator for Bumblebee.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Plugin
 */
class BBEE_Plugin {

	/** @var BBEE_Plugin|null */
	private static $instance = null;

	/** @var BBEE_Indexer */
	public $indexer;

	/** @var BBEE_Retriever */
	public $retriever;

	/** @var BBEE_Rest */
	public $rest;

	/** @var BBEE_Admin */
	public $admin;

	/** @var BBEE_Shortcode */
	public $shortcode;

	/** @var BBEE_Crawler */
	public $crawler;

	/**
	 * Hent singleton.
	 *
	 * @return BBEE_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Standardinnstillinger.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'api_key'         => '',
			'model'           => 'claude-sonnet-4-5',
			'post_types'      => array( 'post', 'page' ),
			'max_sources'     => 6,
			'max_tokens'      => 1500,

			// Svarmodell når OpenAI brukes (konstanten BBEE_OPENAI_CHAT_MODEL i wp-config.php vinner).
			'openai_chat_model'  => 'gpt-4.1-mini',
			'openai_temperature' => 0.2,
			'system_prompt'   => self::default_system_prompt(),
			'welcome_message' => __( 'Hei! Still meg et spørsmål, så finner jeg svar i kunnskapskildene på nettstedet.', 'bumblebee' ),
			'assistant_name'  => 'Bumblebee',

			// Gjenfinning: fulltext | semantic | hybrid.
			'retriever'       => 'hybrid',

			// Kontekst til språkmodellen.
			'candidate_pool'    => 60,    // Kandidater fra hvert søk (mening og fulltekst) før omrangering.
			'context_chunks'    => 10,    // Biter etter omrangering som går til modellen (minst max_sources).
			'context_neighbors' => 1,     // Nabobiter fra samme side på hver side av et treff.
			'context_max_chars' => 30000, // Tak på tegn i kildeutdragene (ca. 8 500 tokens).

			// Embeddings (semantisk søk).
			'embed_provider'  => 'openai', // voyage | openai
			'embed_api_key'   => '',
			'embed_model'     => 'voyage-3.5',

			// Reranking (valgfritt).
			'rerank_enabled'  => false,
			'rerank_provider' => 'voyage', // voyage | cohere
			'rerank_api_key'  => '',
			'rerank_model'    => 'rerank-2.5',

			// Webcrawler.
			'crawl_respect_robots' => true,
			'crawl_delay_ms'       => 500,
			'crawl_user_agent'     => '',
			'crawl_follow_pdf'     => true,

			// PDF-er i mediebiblioteket.
			'index_media_pdf'      => false,

			// Temagrupper (hovedkategorier) og semantisk kobling.
			'theme_group_post_type'     => 'theme_group',
			'theme_secondary_threshold' => 0.4,
			'show_theme_on_sources'     => true,

			// Automatisering.
			'nightly_reindex' => true,  // Daglig reindeksering kl 02 (nettstedets tidssone).
			'nightly_recrawl' => false, // Også crawle nettsider-kildene nattlig.

			// Flytende popup-boble (hele nettstedet).
			'popup_enabled'         => false,
			'popup_position'        => 'top-left', // top-right | top-left | bottom-right | bottom-left
			'popup_show_logged_out' => true,    // Vis boble også til utloggede (de får «logg inn»-boks).

			// Egendefinerte felt som indekseres, én linje per innholdstype: «foretak: felt1, felt2».
			'index_meta_fields'     => '',

			// Spørsmålslogg og tilbakemelding (tommel opp/ned).
			'log_enabled'           => true,

			// Ekstra sperrede domener for crawleren (ett per linje). Kommer i tillegg til standardlisten.
			'blocked_domains'       => '',

			// Hvem får bruke chatten. Tom = alle innloggede. Ellers ett brukernavn eller én e-post per linje.
			// Administratorer har alltid tilgang.
			'allowed_users'         => '',
		);
	}

	/**
	 * Standard systemprompt (byggfaglig, kildehenvisning påkrevd).
	 *
	 * @return string
	 */
	public static function default_system_prompt() {
		return "Du er Bumblebee, en kunnskapsassistent for byggenæringen på nettstedet BIM Verdi. "
			. "Målgruppen er fagfolk i bygg, anlegg og eiendom (BAE).\n\n"
			. "GRUNNREGLER\n"
			. "- Svar bare ut fra de nummererte kildene som følger med spørsmålet. Ikke bruk egen forhåndskunnskap.\n"
			. "- Hvis kildene ikke sier noe om temaet i spørsmålet, skriv bare: «Jeg fant ikke dekning for dette i kunnskapskildene.» "
			. "Ikke gjett, og ikke fyll ut med generell kunnskap. Sett da ingen kildehenvisning.\n"
			. "- Hvis kildene bare svarer på en del av spørsmålet, si det kort i første setning (f.eks. «Kildene svarer bare delvis på dette.»). "
			. "Svar så med det kildene faktisk sier, med henvisninger. Skriv ikke «fant ikke dekning» i et svar som bygger på kildene.\n"
			. "- Hvis kildene omtaler temaet med andre ord eller begreper enn spørsmålet, svar ut fra kildene og bruk ordene deres.\n"
			. "- Hvis kildene sier ulike ting, vis hva hver kilde sier.\n\n"
			. "KRAV, ANBEFALING OG VEILEDNING\n"
			. "- Bruk ordet «krav» (og «skal», «må», «påbudt») bare i to tilfeller:\n"
			. "  a) Kilden er en lov eller forskrift, eller gjengir en bestemt paragraf i en lov eller forskrift (f.eks. TEK17, SAK10, plan- og bygningsloven).\n"
			. "  b) Kilden selv sier at noe er et krav, skal oppfylles eller er obligatorisk (f.eks. et kravdokument fra en byggherre, "
			. "eller kriterier i en sertifiserings- eller kvalitetsordning). Gjengi da kravet slik kilden formulerer det, og si hvem som stiller det.\n"
			. "- Alt annet er anbefaling eller veiledning. Det gjelder veiledere, rapporter, metoder, artikler og råd. "
			. "Skriv da «X anbefaler», «veilederen beskriver», «metoden legger til grunn» eller «ifølge X».\n"
			. "- En veiledning til en forskrift kan gjengi et krav. Da er det et krav, og du sier hvilken paragraf det står i, hvis kilden sier det. "
			. "Når veiledningen sier «bør», gir eksempler eller viser en løsning som oppfyller kravet, er det veiledning.\n"
			. "- Behold modalverbet fra kilden, i begge retninger. Hvis spørsmålet sier «skal» eller «må», men kilden sier «bør», skriv at kilden anbefaler det. "
			. "Hvis en kilde som ikke er lov eller forskrift sier «skal» (f.eks. kriterier i en ordning), skriv «ifølge X skal …». Ikke svekk det til «anbefaler».\n"
			. "- Hvis kilden ikke sier tydelig om noe er et krav eller et råd, beskriv det nøytralt («kilden beskriver …»).\n"
			. "- Ikke legg til en egen setning som sier at svaret er «et krav» eller «en anbefaling».\n"
			. "- Gjengi plikter og forbud med samme styrke som kilden: «må ikke» og «skal ikke» betyr at noe er forbudt. "
			. "«Trenger ikke», «har ikke plikt til» og «er ikke pålagt» betyr at noe ikke er påkrevd. «Bør ikke» er et råd. Ikke bytt mellom disse.\n"
			. "- Ikke trekk juridiske eller faglige slutninger som ikke står i kildene. Ikke sett sammen flere kilder til en ny regel. "
			. "Hvis spørsmålet krever en konkret vurdering, si at kildene ikke avgjør den, og gjengi hva kildene sier.\n"
			. "- Hvis en kilde bare nevner en paragraf, et kapittel eller et dokument ved navn (f.eks. i en innholdsfortegnelse eller meny), "
			. "har du ikke innholdet. Ikke beskriv hva som står der.\n"
			. "- Ikke overfør tall og vilkår fra én situasjon til en annen (f.eks. fra én bygningstype til en annen) uten at kilden sier det.\n\n"
			. "KILDEHENVISNING\n"
			. "- Sett henvisning [n] rett etter påstanden den underbygger, i samme setning eller samme punkt. n er nummeret på kilden i listen.\n"
			. "- Hver faktapåstand skal ha en henvisning. Hvis du ikke finner en kilde for en påstand, ikke skriv den.\n"
			. "- Skriv henvisninger bare som [n]. Én kilde per klamme: [1][3]. Skriv aldri «(1, 3)», «kilde 3» eller numre i ren tekst.\n"
			. "- Sett aldri henvisninger alene på en egen linje, og ikke samle dem til slutt i svaret.\n"
			. "- Bruk bare numre som finnes i listen. Ikke finn opp kilder, titler eller URL-er.\n\n"
			. "FORM\n"
			. "- Svar på norsk (bokmål).\n"
			. "- Start med et direkte svar på 2–4 setninger. Det skal kunne stå alene. Gi hovedregelen først, og unntakene etterpå.\n"
			. "- Ta med de viktigste tallene og navnene fra kilden (f.eks. tall, frister, standarder og hvem som sier det) i kortsvaret.\n"
			. "- Legg deretter bare til detaljer som tilfører noe nytt, f.eks. tall, vilkår, unntak eller trinn. Bruk korte punkter. "
			. "Hvis kortsvaret er nok, stopp der.\n"
			. "- Hold svaret kort: vanligvis under 150 ord. Bruk mer bare når spørsmålet ber om en liste eller en prosess med mange trinn.\n"
			. "- Ikke bruk overskrifter som «Detaljer:». Ikke avslutt med oppsummering, konklusjon eller gjentakelse.\n"
			. "- Ikke avslutt med et avsnitt om hva kildene ikke sier. Si at noe mangler bare når det gjelder kjernen i spørsmålet "
			. "(f.eks. at et tall eller en frist brukeren spør om ikke står i kildene), og si det da i én kort setning.\n"
			. "- Gjengi tall, frister, krav og standardnavn (f.eks. TEK17, NS 3420) ordrett slik de står i kilden.\n"
			. "- Ikke gjenta spørsmålet. Ikke legg til markedsføring eller høflighetsfraser.";
	}

	/**
	 * Konstanter i wp-config.php som overstyrer nøkler i databasen.
	 *
	 * @return array settings-nøkkel => konstantnavn
	 */
	public static function key_constants() {
		return array(
			'api_key'        => 'BBEE_ANTHROPIC_KEY',
			'embed_api_key'  => 'BUMBLEBEE_OPENAI_KEY',
			'rerank_api_key' => 'BBEE_RERANK_KEY',
		);
	}

	/**
	 * Er denne nøkkelen satt som konstant i wp-config.php?
	 *
	 * @param string $setting_key Innstillingsnøkkel.
	 * @return bool
	 */
	public static function key_from_constant( $setting_key ) {
		$map = self::key_constants();
		return isset( $map[ $setting_key ] ) && defined( $map[ $setting_key ] ) && '' !== trim( (string) constant( $map[ $setting_key ] ) );
	}

	/**
	 * Hent innstillinger (flettet med standardverdier).
	 *
	 * Nøkler satt som konstanter i wp-config.php overstyrer databasen.
	 * Bruk $raw = true for å få det som faktisk ligger i databasen
	 * (brukes når innstillinger lagres, så konstant-nøkler aldri havner i DB).
	 *
	 * @param bool $raw Uten konstant-overstyring.
	 * @return array
	 */
	public static function get_settings( $raw = false ) {
		$saved = get_option( BBEE_OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = wp_parse_args( $saved, self::default_settings() );
		if ( ! $raw ) {
			foreach ( self::key_constants() as $key => $const ) {
				if ( self::key_from_constant( $key ) ) {
					$settings[ $key ] = trim( (string) constant( $const ) );
				}
			}
		}
		return $settings;
	}

	/**
	 * Får brukeren bruke chatten?
	 *
	 * Krever innlogging. Hvis listen «allowed_users» har innhold, må
	 * brukernavnet eller e-posten stå i den. Administratorer har alltid tilgang.
	 *
	 * @param int $user_id Bruker-ID. 0 = gjeldende bruker.
	 * @return bool
	 */
	public static function user_can_chat( $user_id = 0 ) {
		$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		if ( user_can( $user, 'manage_options' ) ) {
			return true;
		}
		$settings = self::get_settings();
		$raw      = trim( (string) ( isset( $settings['allowed_users'] ) ? $settings['allowed_users'] : '' ) );
		if ( '' === $raw ) {
			return true;
		}
		$list = array_filter( array_map( 'strtolower', array_map( 'trim', (array) preg_split( '/[\s,;]+/', $raw ) ) ) );
		return in_array( strtolower( $user->user_login ), $list, true ) || in_array( strtolower( $user->user_email ), $list, true );
	}

	/**
	 * Domener som aldri skal hentes av crawleren. Lisensierte standarder
	 * kan ikke gjengis uten avtale, så disse er sperret som standard.
	 * Egne domener legges til under innstillinger, eller via filteret
	 * `bbee_blocked_domains`.
	 *
	 * @return array Domenenavn med små bokstaver, uten «www.».
	 */
	public static function blocked_domains() {
		$list     = array( 'standard.no', 'iso.org', 'cen.eu', 'cenelec.eu', 'iec.ch' );
		$settings = self::get_settings();
		if ( ! empty( $settings['blocked_domains'] ) ) {
			$extra = preg_split( '/[\s,;]+/', (string) $settings['blocked_domains'] );
			foreach ( (array) $extra as $d ) {
				$d = strtolower( trim( $d ) );
				$d = preg_replace( '#^https?://#', '', $d );
				$d = preg_replace( '#/.*$#', '', $d );
				$d = preg_replace( '/^www\./', '', $d );
				if ( '' !== $d ) {
					$list[] = $d;
				}
			}
		}
		$list = apply_filters( 'bbee_blocked_domains', $list );
		return array_values( array_unique( array_filter( array_map( 'strval', (array) $list ) ) ) );
	}

	/**
	 * Er denne URL-en på et sperret domene (eller underdomene av det)?
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_blocked_url( $url ) {
		$host = wp_parse_url( (string) $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = preg_replace( '/^www\./', '', strtolower( $host ) );
		foreach ( self::blocked_domains() as $d ) {
			if ( $host === $d || ( strlen( $host ) > strlen( $d ) && substr( $host, -( strlen( $d ) + 1 ) ) === '.' . $d ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Kjøres ved plugin-aktivering.
	 */
	public static function activate() {
		require_once BBEE_PLUGIN_DIR . 'includes/class-bbee-indexer.php';
		require_once BBEE_PLUGIN_DIR . 'includes/class-bbee-themes.php';
		BBEE_Indexer::create_table();
		BBEE_Themes::create_table();

		update_option( 'bbee_db_version', BBEE_VERSION, false );

		if ( false === get_option( BBEE_OPTION_KEY, false ) ) {
			add_option( BBEE_OPTION_KEY, self::default_settings() );
		}

		// Planlegg en engangs full indeksering rett etter aktivering.
		if ( ! wp_next_scheduled( 'bbee_reindex_all' ) ) {
			wp_schedule_single_event( time() + 15, 'bbee_reindex_all' );
		}
	}

	/**
	 * Kjøres ved deaktivering.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'bbee_reindex_all' );
		wp_clear_scheduled_hook( 'bbee_embed_pending' );
		wp_clear_scheduled_hook( 'bbee_nightly_reindex' );
		wp_clear_scheduled_hook( 'bbee_nightly_classify' );
		wp_clear_scheduled_hook( 'bbee_vector_sync' );
		if ( function_exists( 'wp_unschedule_hook' ) ) {
			wp_unschedule_hook( 'bbee_crawl_batch' );
		}
	}

	/**
	 * Initialiser komponentene og koble opp WordPress-hooks.
	 */
	public function init() {
		load_plugin_textdomain( 'bumblebee', false, dirname( BBEE_PLUGIN_BASENAME ) . '/languages' );

		$this->maybe_upgrade();

		$this->indexer   = new BBEE_Indexer();
		$this->retriever = new BBEE_Retriever();
		$this->rest      = new BBEE_Rest( $this->retriever );
		$this->shortcode = new BBEE_Shortcode();
		$this->crawler   = new BBEE_Crawler( $this->indexer );

		$this->indexer->register_hooks();
		$this->rest->register_hooks();
		$this->shortcode->register_hooks();
		$this->crawler->register_hooks();

		if ( is_admin() ) {
			$this->admin = new BBEE_Admin( $this->indexer, $this->crawler );
			$this->admin->register_hooks();
		}

		// WP-CLI: wp bbee hent-kilder | embed | indekser | status.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			BBEE_CLI::register( $this->indexer );
		}

		// Bakgrunns-reindeksering (cron).
		add_action( 'bbee_reindex_all', array( $this->indexer, 'reindex_all' ) );

		// Automatisering: nattlig reindeksering.
		add_action( 'bbee_nightly_reindex', array( $this, 'run_nightly_reindex' ) );
		add_action( 'bbee_nightly_classify', array( $this, 'run_nightly_classify' ) );
		$this->sync_nightly_schedule();

		// Hold det kompakte vektorlageret i takt etter nye embeddings (etter indekserens egen jobb).
		add_action( 'bbee_embed_pending', array( $this, 'sync_vector_store' ), 20 );
		add_action( 'bbee_nightly_reindex', array( $this, 'sync_vector_store' ), 20 );
		add_action( 'bbee_vector_sync', array( $this, 'sync_vector_store' ) );
	}

	/**
	 * Kjør databaseoppgraderinger når plugin-versjonen har endret seg.
	 */
	protected function maybe_upgrade() {
		$installed = get_option( 'bbee_db_version', '' );
		if ( BBEE_VERSION === $installed ) {
			return;
		}
		BBEE_Indexer::create_table();
		BBEE_Themes::create_table();
		update_option( 'bbee_db_version', BBEE_VERSION, false );

		// Bumblebee: ingen automatisk merkevare-migrering (popup skal ikke slås på).
	}

	/**
	 * Engangs-oppsett av Bumblebee-merkevaren: navn, plassering og aktivering
	 * av popup-boblen. Kjøres kun én gang per «trinn».
	 */
	protected function apply_busybim_branding() {
		if ( ! get_option( 'bbee_busybim_applied', false ) ) {
			$s = get_option( BBEE_OPTION_KEY, array() );
			if ( is_array( $s ) ) {
				$s['assistant_name'] = 'Bumblebee';
				$s['popup_enabled']  = true;
				if ( ! empty( $s['system_prompt'] ) ) {
					$s['system_prompt'] = str_replace( 'Bumblebee ALFA pilot', 'Bumblebee', $s['system_prompt'] );
				}
				update_option( BBEE_OPTION_KEY, $s );
			}
			update_option( 'bbee_busybim_applied', '1', false );
		}

		// Trinn 2: plassering øverst til venstre + vis boble til utloggede.
		if ( ! get_option( 'bbee_busybim_v2', false ) ) {
			$s = get_option( BBEE_OPTION_KEY, array() );
			if ( is_array( $s ) ) {
				$s['popup_position']        = 'top-left';
				$s['popup_show_logged_out'] = true;
				update_option( BBEE_OPTION_KEY, $s );
			}
			update_option( 'bbee_busybim_v2', '1', false );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Nattlig automatisering                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Hold den nattlige cron-planen i tråd med innstillingen.
	 */
	public function sync_nightly_schedule() {
		$settings = self::get_settings();
		$enabled  = ! empty( $settings['nightly_reindex'] );
		$next     = wp_next_scheduled( 'bbee_nightly_reindex' );

		if ( $enabled && ! $next ) {
			wp_schedule_event( self::next_run_timestamp( 2 ), 'daily', 'bbee_nightly_reindex' );
		} elseif ( ! $enabled && $next ) {
			wp_unschedule_event( $next, 'bbee_nightly_reindex' );
		}
	}

	/**
	 * Neste forekomst av et gitt klokkeslett i nettstedets tidssone, som UTC-timestamp.
	 *
	 * @param int $hour Time (0–23).
	 * @return int
	 */
	public static function next_run_timestamp( $hour ) {
		try {
			$tz   = wp_timezone();
			$now  = new DateTime( 'now', $tz );
			$next = new DateTime( 'today', $tz );
			$next->setTime( (int) $hour, 0, 0 );
			if ( $next <= $now ) {
				$next->modify( '+1 day' );
			}
			return $next->getTimestamp();
		} catch ( Exception $e ) {
			return time() + DAY_IN_SECONDS;
		}
	}

	/**
	 * Nattlig jobb: full reindeksering (+ valgfri crawl, + oppfølgende klassifisering).
	 */
	public function run_nightly_reindex() {
		$this->indexer->reindex_all();

		$settings = self::get_settings();
		if ( ! empty( $settings['nightly_recrawl'] ) ) {
			foreach ( BBEE_Crawler::get_sources() as $src ) {
				if ( ! empty( $src['id'] ) ) {
					$this->crawler->start( $src['id'] );
				}
			}
		}

		// Re-klassifiser temagrupper etter at embeddings er oppdatert (med forsinkelse).
		if ( get_option( BBEE_Themes::VECTORS_OPTION, false ) ) {
			if ( ! wp_next_scheduled( 'bbee_nightly_classify' ) ) {
				wp_schedule_single_event( time() + 20 * MINUTE_IN_SECONDS, 'bbee_nightly_classify' );
			}
		}
	}

	/**
	 * Synkroniser vektorlageret (BBEE_Vector_Store) med bit-tabellen.
	 */
	public function sync_vector_store() {
		$embed = new BBEE_Embeddings();
		if ( ! class_exists( 'BBEE_Vector_Store' ) || ! $embed->is_configured() ) {
			return;
		}
		$r = BBEE_Vector_Store::sync( $embed->model_id(), 20.0 );
		// Ikke ferdig: fortsett i en ny cron-kjøring.
		if ( empty( $r['done'] ) && ! wp_next_scheduled( 'bbee_vector_sync' ) ) {
			wp_schedule_single_event( time() + 5, 'bbee_vector_sync' );
		}
	}

	/**
	 * Oppfølgende nattlig temagruppe-klassifisering.
	 */
	public function run_nightly_classify() {
		$themes = new BBEE_Themes();
		$themes->build_profiles();
		$themes->classify_all();
	}
}
