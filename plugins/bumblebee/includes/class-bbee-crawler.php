<?php
/**
 * Webcrawler for eksterne kunnskapskilder (HTML + PDF).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Crawler
 */
class BBEE_Crawler {

	const CRON_HOOK      = 'bbee_crawl_batch';
	const SOURCES_OPTION = 'bbee_url_sources';
	const PAGES_PER_RUN  = 5;
	const HARD_PAGE_CAP  = 200;

	/** @var BBEE_Indexer */
	protected $indexer;
	/** @var array */
	protected $robots_cache = array();

	/**
	 * @param BBEE_Indexer $indexer Indekserer.
	 */
	public function __construct( $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * Koble opp cron-hook.
	 */
	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'run_batch' ), 10, 1 );
	}

	/* ---- Lagring av URL-kilder ---- */

	/**
	 * @return array
	 */
	public static function get_sources() {
		$sources = get_option( self::SOURCES_OPTION, array() );
		return is_array( $sources ) ? $sources : array();
	}

	/**
	 * @param string $id ID.
	 * @return array|null
	 */
	public static function get_source( $id ) {
		$sources = self::get_sources();
		return isset( $sources[ $id ] ) ? $sources[ $id ] : null;
	}

	/**
	 * @param array $sources Kilder.
	 */
	public static function save_sources( $sources ) {
		update_option( self::SOURCES_OPTION, $sources, false );
	}

	/**
	 * @param array $data Felter.
	 * @return string|WP_Error
	 */
	public static function add_source( $data ) {
		$url = isset( $data['url'] ) ? esc_url_raw( trim( $data['url'] ) ) : '';
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return new WP_Error( 'bbee_bad_url', __( 'Ugyldig URL. Må starte med http:// eller https://.', 'bumblebee' ) );
		}

		if ( BBEE_Plugin::is_blocked_url( $url ) ) {
			return new WP_Error( 'bbee_blocked_domain', __( 'Dette domenet er sperret (lisensierte standarder kan ikke hentes inn). Se «Sperrede domener» i innstillingene.', 'bumblebee' ) );
		}

		$parts       = wp_parse_url( $url );
		$path_prefix = isset( $data['path_prefix'] ) ? trim( $data['path_prefix'] ) : '';

		$sources = self::get_sources();
		$id      = 'src_' . substr( md5( $url . microtime() ), 0, 10 );

		$sources[ $id ] = array(
			'id'           => $id,
			'url'          => $url,
			'host'         => isset( $parts['host'] ) ? $parts['host'] : '',
			'depth'        => max( 1, min( 3, isset( $data['depth'] ) ? (int) $data['depth'] : 1 ) ),
			'same_domain'  => ! empty( $data['same_domain'] ),
			'path_prefix'  => $path_prefix,
			'max_pages'    => max( 1, min( self::HARD_PAGE_CAP, isset( $data['max_pages'] ) ? (int) $data['max_pages'] : 25 ) ),
			'status'       => 'ny',
			'pages'        => 0,
			'chunks'       => 0,
			'last_crawled' => '',
		);
		self::save_sources( $sources );
		return $id;
	}

	/**
	 * @param string       $id      ID.
	 * @param BBEE_Indexer $indexer Indekserer.
	 */
	public static function remove_source( $id, $indexer ) {
		$sources = self::get_sources();
		if ( isset( $sources[ $id ] ) ) {
			unset( $sources[ $id ] );
			self::save_sources( $sources );
		}
		if ( $indexer ) {
			$indexer->delete_url_source_chunks( $id );
		}
		delete_option( 'bbee_crawl_state_' . $id );
		wp_clear_scheduled_hook( self::CRON_HOOK, array( $id ) );
	}

	/* ---- Crawl-kjøring ---- */

	/**
	 * @param string $id ID.
	 * @return bool
	 */
	public function start( $id ) {
		$source = self::get_source( $id );
		if ( ! $source ) {
			return false;
		}

		$this->indexer->delete_url_source_chunks( $id );

		$state = array(
			'queue'   => array( array( 'url' => $this->normalize_url( $source['url'] ), 'depth' => 0 ) ),
			'visited' => array(),
			'pages'   => 0,
			'chunks'  => 0,
		);
		update_option( 'bbee_crawl_state_' . $id, $state, false );

		$sources                  = self::get_sources();
		$sources[ $id ]['status'] = 'crawler …';
		$sources[ $id ]['pages']  = 0;
		$sources[ $id ]['chunks'] = 0;
		self::save_sources( $sources );

		if ( ! wp_next_scheduled( self::CRON_HOOK, array( $id ) ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK, array( $id ) );
		}
		return true;
	}

	/**
	 * @param string $id ID.
	 */
	public function run_batch( $id ) {
		$source = self::get_source( $id );
		if ( ! $source ) {
			return;
		}
		$state = get_option( 'bbee_crawl_state_' . $id, null );
		if ( ! is_array( $state ) ) {
			return;
		}

		$settings       = BBEE_Plugin::get_settings();
		$respect_robots = ! empty( $settings['crawl_respect_robots'] );
		$delay_ms       = isset( $settings['crawl_delay_ms'] ) ? (int) $settings['crawl_delay_ms'] : 500;
		$max_pages      = min( (int) $source['max_pages'], self::HARD_PAGE_CAP );

		$processed = 0;
		while ( $processed < self::PAGES_PER_RUN && ! empty( $state['queue'] ) && $state['pages'] < $max_pages ) {
			$item  = array_shift( $state['queue'] );
			$url   = $this->normalize_url( $item['url'] );
			$depth = (int) $item['depth'];

			if ( isset( $state['visited'][ $url ] ) ) {
				continue;
			}
			$state['visited'][ $url ] = 1;

			if ( $respect_robots && ! $this->robots_allowed( $url ) ) {
				continue;
			}

			$processed++;

			if ( $this->is_pdf_url( $url ) ) {
				$this->crawl_pdf( $id, $url, $state, $depth, $source );
				if ( $delay_ms > 0 ) {
					usleep( min( 2000, $delay_ms ) * 1000 );
				}
				continue;
			}

			$fetched = $this->fetch( $url );
			if ( is_wp_error( $fetched ) ) {
				continue;
			}

			$parsed = $this->extract( $fetched, $url );
			if ( '' !== trim( $parsed['text'] ) ) {
				$n = $this->indexer->index_url_page( $id, $url, $parsed['title'], $parsed['text'] );
				$state['pages']++;
				$state['chunks'] += (int) $n;
			}

			if ( $depth < (int) $source['depth'] ) {
				foreach ( $parsed['links'] as $link ) {
					$abs = $this->filter_link( $link, $url, $source );
					if ( $abs && ! isset( $state['visited'][ $abs ] ) ) {
						$state['queue'][] = array( 'url' => $abs, 'depth' => $depth + 1 );
					}
				}
			}

			if ( $delay_ms > 0 ) {
				usleep( min( 2000, $delay_ms ) * 1000 );
			}
		}

		update_option( 'bbee_crawl_state_' . $id, $state, false );

		$more    = ! empty( $state['queue'] ) && $state['pages'] < $max_pages;
		$sources = self::get_sources();
		if ( isset( $sources[ $id ] ) ) {
			$sources[ $id ]['pages']        = (int) $state['pages'];
			$sources[ $id ]['chunks']       = (int) $state['chunks'];
			$sources[ $id ]['status']       = $more ? 'crawler …' : 'fullført';
			$sources[ $id ]['last_crawled'] = current_time( 'mysql' );
			self::save_sources( $sources );
		}

		if ( $more ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK, array( $id ) );
		} else {
			$this->indexer->schedule_embedding();
			delete_option( 'bbee_crawl_state_' . $id );
		}
	}

	/* ---- Henting og uttrekk ---- */

	/**
	 * @param string $url URL.
	 * @return string|WP_Error
	 */
	protected function fetch( $url ) {
		if ( BBEE_Plugin::is_blocked_url( $url ) ) {
			return new WP_Error( 'bbee_blocked_domain', 'Sperret domene' );
		}
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'user-agent'  => $this->user_agent(),
				'headers'     => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'bbee_crawl_http', 'HTTP ' . $code );
		}
		$ctype = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $ctype && false === stripos( $ctype, 'html' ) ) {
			return new WP_Error( 'bbee_crawl_ctype', 'Ikke HTML' );
		}
		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * @param string $id    Kilde-ID.
	 * @param string $url   PDF-URL.
	 * @param array  $state Tilstand.
	 */
	protected function crawl_pdf( $id, $url, &$state, $depth = 0, $source = array() ) {
		if ( ! BBEE_Pdf::available() || BBEE_Plugin::is_blocked_url( $url ) ) {
			return;
		}
		$response = wp_remote_get( $url, array( 'timeout' => 30, 'redirection' => 3, 'user-agent' => $this->user_agent() ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return;
		}
		$text = BBEE_Pdf::extract_from_string( $body );
		if ( is_wp_error( $text ) || '' === trim( (string) $text ) ) {
			return;
		}
		$path  = wp_parse_url( $url, PHP_URL_PATH );
		$title = $path ? rawurldecode( basename( $path ) ) : $url;

		$n = $this->indexer->index_url_page( $id, $url, $title, $text );
		$state['pages']++;
		$state['chunks'] += (int) $n;

		// Følg lenker som står i PDF-en (samme regler som for nettsider: dybde, domene, sperrede domener).
		if ( ! empty( $source ) && $depth < (int) $source['depth'] ) {
			foreach ( BBEE_Pdf::extract_links_from_string( $body, $text ) as $link ) {
				$abs = $this->filter_link( $link, $url, $source );
				if ( $abs && ! isset( $state['visited'][ $abs ] ) ) {
					$state['queue'][] = array( 'url' => $abs, 'depth' => $depth + 1 );
				}
			}
		}
	}

	/**
	 * @param string $html HTML.
	 * @param string $base URL.
	 * @return array
	 */
	protected function extract( $html, $base ) {
		$title = '';
		$text  = '';
		$links = array();

		if ( '' === trim( $html ) || ! class_exists( 'DOMDocument' ) ) {
			return array( 'title' => $title, 'text' => $text, 'links' => $links );
		}

		$prev = libxml_use_internal_errors( true );
		$dom  = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$titles = $dom->getElementsByTagName( 'title' );
		if ( $titles->length > 0 ) {
			$title = trim( $titles->item( 0 )->textContent );
		}

		foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
			$href = $a->getAttribute( 'href' );
			if ( $href ) {
				$links[] = $href;
			}
		}

		foreach ( array( 'script', 'style', 'noscript', 'nav', 'header', 'footer', 'aside', 'form', 'svg' ) as $tag ) {
			$nodes = $dom->getElementsByTagName( $tag );
			for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
				$node = $nodes->item( $i );
				if ( $node && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}

		$node = null;
		foreach ( array( 'main', 'article' ) as $tag ) {
			$nodes = $dom->getElementsByTagName( $tag );
			if ( $nodes->length > 0 ) {
				$node = $nodes->item( 0 );
				break;
			}
		}
		if ( null === $node ) {
			$bodies = $dom->getElementsByTagName( 'body' );
			$node   = $bodies->length > 0 ? $bodies->item( 0 ) : null;
		}

		$fragment = $node ? $dom->saveHTML( $node ) : $dom->textContent;
		$text     = $this->html_to_text( $fragment );

		return array( 'title' => $title ? $title : $base, 'text' => $text, 'links' => $links );
	}

	/**
	 * @param string $html Fragment.
	 * @return string
	 */
	protected function html_to_text( $html ) {
		$html = preg_replace( '#<br[^>]*>#i', "\n", $html );
		$html = preg_replace( '#</?(p|div|li|ul|ol|section|article|main|aside|h[1-6]|tr|table|thead|tbody|blockquote|figure|figcaption|header|footer)[^>]*>#i', "\n", $html );
		$text = strip_tags( $html );
		return $this->clean_text( $text );
	}

	/**
	 * @param string $text Tekst.
	 * @return string
	 */
	protected function clean_text( $text ) {
		// Ugyldig UTF-8 får preg_replace med /u til å gi null, og da forsvinner
		// hele teksten. Rett opp tegnkodingen først.
		$text = self::gyldig_utf8( (string) $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = (string) preg_replace( "/[ \t]+/u", ' ', $text );
		$text = (string) preg_replace( "/\s*\n\s*/u", "\n", $text );
		$text = (string) preg_replace( "/\n{3,}/u", "\n\n", $text );
		return trim( $text );
	}

	/**
	 * Gjør en streng om til gyldig UTF-8. Ugyldige byte blir erstattet.
	 *
	 * @param string $text Tekst.
	 * @return string
	 */
	public static function gyldig_utf8( $text ) {
		$text = (string) $text;
		if ( '' === $text || ! function_exists( 'mb_check_encoding' ) || mb_check_encoding( $text, 'UTF-8' ) ) {
			return $text;
		}
		$gammel = ini_get( 'mbstring.substitute_character' );
		ini_set( 'mbstring.substitute_character', 'none' ); // phpcs:ignore WordPress.PHP.IniSet
		$text = mb_convert_encoding( $text, 'UTF-8', 'UTF-8' );
		ini_set( 'mbstring.substitute_character', (string) $gammel ); // phpcs:ignore WordPress.PHP.IniSet
		return (string) $text;
	}

	/* ---- Støy: sider uten fagverdi ---- */

	/**
	 * Er dette en side uten fagverdi (karriere, kontakt, om oss, personvern,
	 * nyhetsarkiv, arrangementer, innlogging, handlekurv og lignende)?
	 * Regelen ser på URL-en og eventuelt sidetittelen. Gjelder HTML-sider,
	 * ikke PDF-er (rapporter ligger ofte under f.eks. /om-oss/).
	 *
	 * @param string $url    URL.
	 * @param string $tittel Sidetittel (valgfri).
	 * @return string Grunn, eller tom streng hvis siden ikke er støy.
	 */
	public static function stoy_grunn( $url, $tittel = '' ) {
		$deler = wp_parse_url( (string) $url );
		$sti   = isset( $deler['path'] ) ? strtolower( rawurldecode( $deler['path'] ) ) : '';
		$query = isset( $deler['query'] ) ? strtolower( $deler['query'] ) : '';

		$seg = array();
		foreach ( explode( '/', $sti ) as $s ) {
			// Fjern filendelse og løpenummer, f.eks. «kontakt-oss.186555.aspx».
			$s = preg_replace( '/(\.\d+)?\.(aspx?|html?|php|jsp|cfm)$/', '', $s );
			if ( '' !== $s ) {
				$seg[] = $s;
			}
		}
		$antall = count( $seg );

		// Segmenter som er støy uansett hvor de står i stien.
		$overalt = '/^(karriere|karrierer|careers?|jobs?|jobb|jobber|ledige-?stillinger|stillinger|stilling|stillingsannonser|vacanc(y|ies)|jobbe?-(hos|i|for)-[a-z0-9-]+|work-(with|for)-us|join-(us|our-team)|velferd[a-z-]*|welfare|for-ansatte|ansattsider|volunteer[a-z0-9-]*|kontakt|kontakt-?oss|kontakt-?us|kontaktinformasjon|kontakt-[a-z0-9-]+|contact|contact-?us|personvern|personvernerklaer?ing|personvern-og-(informasjonssikkerhet|cookies|informasjonskapsler)|personvern-i-[a-z0-9-]+|privacy[a-z-]*|cookies?|cookie-[a-z-]+|informasjonskapsler|vilkar|bruksvilkar|terms|terms-[a-z-]+|tilgjengelighetserklaering|accessibility|accessibility-statement|presse|press|presserom|pressemeldinger|press-releases?|newsroom|media-?assets|mediebank|fotoweb|logg-?inn|login|log-in|sign-?in|sign-?up|min-?side|my-?account|handlekurv|cart|checkout|kasse|abonner|subscribe|nyhetsbrev|newsletters?|glemt-passord|arrangementer|arrangement|arrangementskalender|events?|event-?kalender|events-archive|kalender|calendar|sitemap|nettstedskart|bli-medlem|become-a-member|membership|medlemskap)$/';
		// Segmenter som er støy bare som siste ledd (oversikts- og listesider).
		$sist = '/^(nyheter|nyhetsarkiv|news|news-?archive|newsitems|news-and-media|aktuelt|siste-nytt|blogg?|arkiv|archives?|partners?|partnere|sponsorer|sponsors|members|medlemmer|team|our-team|leadership|ledelsen|styret|board|ansatte|medarbeidere|people|company|legal)$/';

		foreach ( $seg as $i => $s ) {
			if ( preg_match( $overalt, $s ) ) {
				return 'støy (url: ' . $s . ')';
			}
			// «Om oss»-sider: bare selve siden og ett nivå under. Dypere sider
			// (f.eks. prosjektomtaler under /om-oss/verktoy/) kan ha fagstoff.
			// «om-fylkeskommunen», «om-bygg21» o.l. teller bare som første ledd.
			if ( $antall - $i <= 2 && ( preg_match( '/^(om-?oss|about|about-?us|who-we-are|hvem-er-vi)$/', $s ) || ( 0 === $i && preg_match( '/^om-[a-z0-9-]+$/', $s ) ) ) ) {
				return 'støy (url: ' . $s . ')';
			}
			// Lister etter emneknagg/kategori og sider med sidetall.
			if ( $i < $antall - 1 && preg_match( '/^(tags?|kategori|kategorier|category|categories)$/', $s ) ) {
				return 'støy (liste: ' . $s . ')';
			}
			if ( $i === $antall - 2 && preg_match( '/^(page|side)$/', $s ) && ctype_digit( $seg[ $i + 1 ] ) ) {
				return 'støy (liste: side ' . $seg[ $i + 1 ] . ')';
			}
		}
		if ( $antall > 0 && preg_match( $sist, $seg[ $antall - 1 ] ) ) {
			return 'støy (liste: ' . $seg[ $antall - 1 ] . ')';
		}
		if ( '' !== $query && preg_match( '/(^|&)(mailpoet_router|eventdisplay|ical|tribe-bar-date|outlook-ical)(=|&|$)/', $query ) ) {
			return 'støy (url: nyhetsbrev/kalender)';
		}

		// Tittelen: se på første og siste ledd («Kontakt oss - Etaten» / «Etaten | Kontakt oss»).
		$tittel = trim( preg_replace( '/\s+/u', ' ', (string) $tittel ) );
		if ( '' !== $tittel ) {
			$ledd = preg_split( '/\s+[-–—|·:]\s+/u', $tittel );
			$ledd = array_unique( array( reset( $ledd ), end( $ledd ) ) );
			$tmon = '/^(kontakt|kontakt ?oss( på .{1,40})?|kontaktinformasjon|kontakt [a-zæøå]+|contact( us)?|om oss|about( us)?|ledige stillinger( i .{1,60})?|ledig stilling|stillinger|karriere|careers?|jobs?|jobb (hos|i) .{1,40}|jobbe (hos|i) .{1,40}|velferdsordninger|personvern(erklæring)?( (for|i) .{1,60})?|personvern og (informasjonssikkerhet|cookies|informasjonskapsler)|privacy( policy| notice| statement)?|cookies?( policy)?|informasjonskapsler|bruk av informasjonskapsler( .{1,40})?|logg inn|log ?in|sign ?in|handlekurv|shopping cart|cart|checkout|nyhetsarkiv|news ?archive|nyheter|news|presse(rom|meldinger)?|press( releases?)?|newsroom|arrangementer|kalender|events?( archive)?|calendar|nettstedskart|sitemap)$/u';
			foreach ( $ledd as $l ) {
				$l = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $l ) ) : strtolower( trim( $l ) );
				if ( '' !== $l && preg_match( $tmon, $l ) ) {
					return 'støy (tittel: ' . $l . ')';
				}
			}
		}
		return '';
	}

	/**
	 * Er dette en eldre versjon fra en endringshistorikk? Krever både
	 * en historikk-mappe i stien og et tegn på at versjonen er avsluttet:
	 * en lukket datoperiode i filnavnet (f.eks. «_01.07.2010-30.06.2011.pdf»)
	 * eller en versjonsparameter (f.eks. «?version=1.0»).
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function er_gammel_versjon( $url ) {
		$deler = wp_parse_url( (string) $url );
		$sti   = isset( $deler['path'] ) ? strtolower( rawurldecode( $deler['path'] ) ) : '';
		$query = isset( $deler['query'] ) ? strtolower( $deler['query'] ) : '';
		if ( ! preg_match( '#/(endringshistorikk|versjonshistorikk|historikk|tidligere-versjoner|historiske-versjoner|version-history|archived?-versions?)[^/]*/#', $sti ) ) {
			return false;
		}
		if ( preg_match( '/\d{2}\.\d{2}\.\d{2,4}\s*-?\s*\d{2}\.\d{2}\.\d{2,4}/', basename( $sti ) ) ) {
			return true;
		}
		return (bool) preg_match( '/(^|&)(version|versjon|ver|v)=/', $query );
	}

	/* ---- Språk og tegnrot ---- */

	/**
	 * Vanlige småord per språk. Brukes til å kjenne igjen språket i en tekst.
	 * Bare ord med minst to bokstaver. Ord som finnes i et tillatt språk
	 * (norsk, svensk, dansk, engelsk), teller aldri for et annet språk.
	 *
	 * @return array {tillatt: ord => true, andre: språk => (ord => true), kjente: ord => true}
	 */
	protected static function ordlister() {
		static $lister = null;
		if ( null !== $lister ) {
			return $lister;
		}
		$tillatt = array(
			'no' => 'og er det som på til av for med en et ei den de ikke har kan skal vil om fra ved eller også må være blir eit ein ikkje frå dei berre at seg sin sitt sine hva hvor når etter under over mellom gjennom slik dette disse denne var vi du jeg han hun hvis bare kun mot alle andre nye får gir hvordan hvilke både enn nå da så per se nr jf mv bl osv',
			'sv' => 'och är att som på för med en ett den det inte har kan ska av om från eller också vid till de sig hur när efter mellan genom detta dessa denna vara blir finns olika samt även',
			'da' => 'og er at det som på til af for med en et den ikke har kan skal vil om fra eller også ved de sig hvor når efter mellem gennem dette disse denne være bliver',
			'en' => 'the and of to in is for that with on as by are be this from at or an it not which have has can will should these their they was were been into also more other such than its our we may must all any but if how when where who what each',
		);
		$andre = array(
			'tysk'       => 'und der die das ist nicht mit von zu den dem des ein eine einer auf für sich auch werden wird sind oder bei nach aus wie durch über zur zum im',
			'fransk'     => 'le la les des du et est une un dans pour que qui sur pas par au aux ce cette sont avec ou plus ses leur être',
			'spansk'     => 'el la los las de del que en es por para con una un se al lo como más pero sus este esta son',
			'italiensk'  => 'il la di che per un una del della dei delle con non sono nel nella gli le si da al anche come questo',
			'nederlandsk' => 'de het een en van is dat op te in voor met zijn niet aan er ook als bij door worden wordt naar deze dit uit of kan maar',
			'portugisisk' => 'os as de do da dos das que em um uma para com não por se mais como ao foi são',
			'polsk'      => 'na się nie do że jest to jak dla przez od po oraz czy są przy',
			'finsk'      => 'ja on ei se että joka mutta kun tai myös ovat oli sekä kanssa',
		);
		$t = array();
		foreach ( $tillatt as $ord ) {
			foreach ( explode( ' ', $ord ) as $o ) {
				$t[ $o ] = true;
			}
		}
		$a      = array();
		$kjente = $t;
		foreach ( $andre as $sprak => $ord ) {
			$a[ $sprak ] = array();
			foreach ( explode( ' ', $ord ) as $o ) {
				if ( ! isset( $t[ $o ] ) ) {
					$a[ $sprak ][ $o ] = true;
					$kjente[ $o ]      = true;
				}
			}
		}
		$lister = array( 'tillatt' => $t, 'andre' => $a, 'kjente' => $kjente );
		return $lister;
	}

	/**
	 * Fjern lenker og e-postadresser før vi måler teksten. De har få vokaler
	 * og mange tegn, og skal ikke telle som tegnrot.
	 *
	 * @param string $t Tekst.
	 * @return string
	 */
	protected static function uten_lenker( $t ) {
		$u = preg_replace( '#(https?://|www\.)\S+|\S+@\S+\.\S+#iu', ' ', (string) $t );
		return null === $u ? (string) $t : $u;
	}

	/**
	 * Ord (minst to bokstaver), små bokstaver.
	 *
	 * @param string $t Tekst.
	 * @return array
	 */
	protected static function ord_i( $t ) {
		$t = function_exists( 'mb_strtolower' ) ? mb_strtolower( $t, 'UTF-8' ) : strtolower( $t );
		preg_match_all( '/\p{L}{2,}/u', $t, $m );
		return $m[0];
	}

	/**
	 * Er teksten på et annet språk enn norsk, svensk, dansk eller engelsk?
	 *
	 * To enkle trinn:
	 * 1. Skrift: Er under halvparten av bokstavene latinske, er teksten på
	 *    f.eks. gresk eller kyrillisk.
	 * 2. Vanlige småord: Teller ord som «und», «les», «het» klart flere enn
	 *    norske/svenske/danske/engelske småord, er teksten på et annet språk.
	 * Korte tekster og tabeller (få ord) blir aldri avvist.
	 *
	 * @param string $tekst Tekst.
	 * @return string Grunn, eller tom streng hvis teksten kan brukes.
	 */
	public static function sprak_grunn( $tekst ) {
		$t        = self::uten_lenker( $tekst );
		$bokstaver = (int) preg_match_all( '/\p{L}/u', $t );
		if ( $bokstaver < 80 ) {
			return '';
		}
		$latin = (int) preg_match_all( '/\p{Latin}/u', $t );
		if ( $latin / $bokstaver < 0.5 ) {
			$skrift = array(
				'gresk'    => '/\p{Greek}/u',
				'kyrillisk' => '/\p{Cyrillic}/u',
				'arabisk'  => '/\p{Arabic}/u',
				'hebraisk' => '/\p{Hebrew}/u',
				'kinesisk/japansk' => '/[\p{Han}\p{Hiragana}\p{Katakana}]/u',
				'koreansk' => '/\p{Hangul}/u',
				'thai'     => '/\p{Thai}/u',
			);
			$navn  = 'ikke-latinsk';
			$flest = 0;
			foreach ( $skrift as $s => $re ) {
				$n = (int) preg_match_all( $re, $t );
				if ( $n > $flest ) {
					$flest = $n;
					$navn  = $s;
				}
			}
			return 'språk (' . $navn . ' skrift)';
		}

		$ord = self::ord_i( $t );
		if ( count( $ord ) < 30 ) {
			return '';
		}
		$lister  = self::ordlister();
		$tillatt = 0;
		$treff   = array();
		$ulike   = array();
		foreach ( $ord as $o ) {
			if ( isset( $lister['tillatt'][ $o ] ) ) {
				$tillatt++;
				continue;
			}
			foreach ( $lister['andre'] as $sprak => $liste ) {
				if ( isset( $liste[ $o ] ) ) {
					$treff[ $sprak ]          = isset( $treff[ $sprak ] ) ? $treff[ $sprak ] + 1 : 1;
					$ulike[ $sprak ][ $o ] = true;
				}
			}
		}
		if ( ! empty( $treff ) ) {
			arsort( $treff );
			$sprak = (string) key( $treff );
			$n     = (int) reset( $treff );
			// Krav: nok bevis (minst 8 treff fordelt på minst 4 ulike ord), og klart flere
			// treff enn for de tillatte språkene. Ett ord som går igjen (f.eks. «se» i en
			// norsk tabell) er ikke nok.
			if ( $n >= 8 && count( $ulike[ $sprak ] ) >= 4 && $n > 2 * $tillatt ) {
				return 'språk (' . $sprak . ')';
			}
		}

		// 3. Ukjent språk (f.eks. ungarsk, tsjekkisk, litauisk): nesten ingen kjente
		// småord, og de fleste ordene har liten forbokstav. Navnelister og tabeller
		// har store forbokstaver og blir stående.
		$kjente = 0;
		foreach ( $ord as $o ) {
			if ( isset( $lister['kjente'][ $o ] ) ) {
				$kjente++;
			}
		}
		preg_match_all( '/\p{L}{2,}/u', $t, $m );
		$sma = 0;
		foreach ( $m[0] as $o ) {
			if ( preg_match( '/^\p{Ll}/u', $o ) ) {
				$sma++;
			}
		}
		// Kode og markup (XML, JSON, BNF) har også få kjente ord. Den hoppes over.
		$tokens = preg_split( '/\s+/u', trim( $t ) );
		$kode   = 0;
		foreach ( (array) $tokens as $tok ) {
			if ( preg_match( '/[<>={}_:]/u', $tok ) ) {
				$kode++;
			}
		}
		if ( $kode / max( 1, count( (array) $tokens ) ) >= 0.15 ) {
			return '';
		}
		if ( $kjente / count( $ord ) < 0.035 && ! empty( $m[0] ) && $sma / count( $m[0] ) > 0.7 ) {
			return 'språk (ukjent)';
		}
		return '';
	}

	/**
	 * Språksjekk for en hel side eller en hel PDF. Teksten deles i blokker på
	 * ca. 2 000 tegn, og hver blokk sjekkes med sprak_grunn(). Siden droppes
	 * bare når under 30 % av blokkene kan brukes. En flerspråklig side med en
	 * tydelig norsk/svensk/dansk/engelsk del blir altså stående. Da sjekkes
	 * bitene én for én ved lagring.
	 *
	 * @param string $tekst Tekst.
	 * @return string Grunn (det vanligste språket som ikke er tillatt), eller tom streng.
	 */
	public static function side_sprak_grunn( $tekst ) {
		$blokker = array();
		$blokk   = '';
		foreach ( explode( "\n", (string) $tekst ) as $linje ) {
			$blokk .= $linje . "\n";
			if ( strlen( $blokk ) >= 2000 ) {
				$blokker[] = $blokk;
				$blokk     = '';
			}
		}
		if ( '' !== trim( $blokk ) ) {
			$blokker[] = $blokk;
		}
		if ( empty( $blokker ) ) {
			return '';
		}
		$ok     = 0;
		$grunner = array();
		foreach ( $blokker as $b ) {
			$g = self::sprak_grunn( $b );
			if ( '' === $g ) {
				$ok++;
			} else {
				$grunner[ $g ] = isset( $grunner[ $g ] ) ? $grunner[ $g ] + 1 : 1;
			}
		}
		if ( empty( $grunner ) || $ok / count( $blokker ) >= 0.3 ) {
			return '';
		}
		arsort( $grunner );
		return (string) key( $grunner );
	}

	/**
	 * Er teksten (en tekstbit eller en PDF-side) tegnrot? Typiske årsaker er
	 * PDF-fonter uten riktig tegnkart. Sjekkene er generelle:
	 * - mange ugyldige tegn (�, kontrolltegn),
	 * - mange uvanlige spesialtegn,
	 * - løse bokstaver med mellomrom («B S L F S J O H»),
	 * - forskjøvet tegnkoding: nesten bare store bokstaver og nesten ingen kjente
	 *   småord («0VS PQFSBUJOH QSPGJUT»),
	 * - ord uten mellomrom (svært lange ord i snitt).
	 * Tabeller med tall og navnelister blir ikke avvist.
	 *
	 * @param string $tekst Tekst.
	 * @return string Grunn, eller tom streng.
	 */
	public static function tegnrot_grunn( $tekst ) {
		// Kontrolltegn (U+0000–U+001F) kommer fra fonter uten tegnkart for enkelte ord,
		// f.eks. uthevet tekst. De fjernes uansett før lagring, og teller ikke her.
		$t  = (string) preg_replace( '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u', ' ', self::uten_lenker( $tekst ) );
		$ns = (string) preg_replace( '/\s+/u', '', $t );
		$n  = function_exists( 'mb_strlen' ) ? mb_strlen( $ns, 'UTF-8' ) : strlen( $ns );
		if ( $n < 40 ) {
			return '';
		}
		// Rekker av � brukes ofte som prikkelinjer i innholdsfortegnelser. De teller ikke.
		$ugyldige = (int) preg_match_all( '/(?<!\x{FFFD})\x{FFFD}(?!\x{FFFD})|[\x{80}-\x{9F}]/u', $ns );
		if ( $ugyldige / $n > 0.03 ) {
			return 'tegnrot (ugyldige tegn)';
		}
		// Punktum, tabellstreker, kulepunkter og � (sjekket over) regnes som vanlige.
		$uvanlige = (int) preg_match_all( '/[^\p{L}\p{N}\p{M}.,:;!?\-–—()\[\]\/%|"\'«»§+=*•·…’‘“”&<>_▪■□●○◦✓✔\x{FFFD}]/u', $ns );
		if ( $uvanlige / $n > 0.2 ) {
			return 'tegnrot (mange spesialtegn)';
		}
		$tokens = preg_split( '/\s+/u', trim( $t ) );
		$tokens = is_array( $tokens ) ? $tokens : array();
		if ( count( $tokens ) >= 30 ) {
			$enkle = 0;
			foreach ( $tokens as $tok ) {
				if ( preg_match( '/^\p{L}$/u', $tok ) ) {
					$enkle++;
				}
			}
			if ( $enkle / count( $tokens ) > 0.5 ) {
				return 'tegnrot (løse bokstaver)';
			}
		}
		$ord = self::ord_i( $t );
		if ( count( $ord ) >= 20 ) {
			$lister = self::ordlister();
			$kjente = 0;
			foreach ( $ord as $o ) {
				if ( isset( $lister['kjente'][ $o ] ) ) {
					$kjente++;
				}
			}
			$bokstaver = (int) preg_match_all( '/\p{L}/u', $t );
			$latin     = (int) preg_match_all( '/\p{Latin}/u', $t );
			$store     = (int) preg_match_all( '/(?=\p{Latin})\p{Lu}/u', $t );
			// Bare for latinsk skrift. Gresk tekst med noen få forkortelser (BIM, CDE) skal
			// avvises av språksjekken, ikke her.
			if ( $kjente / count( $ord ) < 0.05 && $latin > 0 && $latin / max( 1, $bokstaver ) >= 0.5 && $store / $latin > 0.6 ) {
				return 'tegnrot (forskjøvet tegnkoding)';
			}
			preg_match_all( '/\p{L}+/u', $t, $m );
			$sum = 0;
			foreach ( $m[0] as $o ) {
				$sum += function_exists( 'mb_strlen' ) ? mb_strlen( $o, 'UTF-8' ) : strlen( $o );
			}
			if ( ! empty( $m[0] ) && $sum / count( $m[0] ) > 14 ) {
				return 'tegnrot (ord uten mellomrom)';
			}
		}
		return '';
	}

	/**
	 * Kan en tekstbit brukes? Sjekker tegnrot først, så språk.
	 *
	 * @param string $tekst Tekstbit.
	 * @return string Grunn, eller tom streng.
	 */
	public static function bit_grunn( $tekst ) {
		$g = self::tegnrot_grunn( $tekst );
		return '' !== $g ? $g : self::sprak_grunn( $tekst );
	}

	/**
	 * Er én linje tegnrot? Bare sterke tegn, fordi linjer er korte:
	 * - L1: under 20 % bokstaver og flere sjeldne tegn (# $ & @ ^ ~ \ { } ` " ') inne i
	 *   linjen, f.eks. «'0347"3-*( 0.#36,». Formler, tabeller og prikkelinjer har
	 *   ikke slike tegn og blir stående.
	 * - L2: mange ord som starter med et tegn rett foran store bokstaver
	 *   («-BTTF ,JMWS &JE»). Dette er typisk for forskjøvet tegnkoding.
	 * - L3: nesten bare løse bokstaver med mellomrom («B S L F S J O H T»).
	 *   Tabellstreker og tall teller ikke, så tabellrader blir stående.
	 *
	 * @param string $linje Linje.
	 * @return bool
	 */
	protected static function linje_er_rot( $linje ) {
		$ns = (string) preg_replace( '/\s+/u', '', (string) $linje );
		$n  = function_exists( 'mb_strlen' ) ? mb_strlen( $ns, 'UTF-8' ) : strlen( $ns );
		if ( $n < 8 ) {
			return false;
		}
		$bokstaver = (int) preg_match_all( '/\p{L}/u', $ns );
		$sjeldne   = (int) preg_match_all( '/[#$&@^~\\\\{}`"\'“”]/u', $ns );
		if ( $bokstaver / $n < 0.2 && $sjeldne >= 2 && $sjeldne / $n >= 0.15 ) {
			return true;
		}
		$tokens = preg_split( '/\s+/u', trim( (string) $linje ) );
		$tokens = is_array( $tokens ) ? $tokens : array();
		$antall = count( $tokens );
		if ( $antall >= 4 ) {
			$rare = 0;
			foreach ( $tokens as $tok ) {
				if ( preg_match( '/^[-,\'&)$%#"*+\/]\p{Lu}{2,}/u', $tok ) && ! preg_match( '/^(["\']).*\1$/u', $tok ) ) {
					$rare++;
				}
			}
			if ( $rare / $antall >= 0.3 ) {
				return true;
			}
		}
		$med_bokstav = 0;
		$enkle       = 0;
		foreach ( $tokens as $tok ) {
			if ( preg_match( '/\p{L}/u', $tok ) ) {
				$med_bokstav++;
				if ( preg_match( '/^\p{L}$/u', $tok ) ) {
					$enkle++;
				}
			}
		}
		if ( $med_bokstav >= 10 && $enkle / $med_bokstav >= 0.7 ) {
			return true;
		}
		return false;
	}

	/**
	 * Fjern linjer med tegnrot fra en tekst. En kort linje som står mellom to
	 * rot-linjer og ikke har ett eneste kjent ord, fjernes også (den er som
	 * regel en del av samme feilkodede blokk).
	 *
	 * @param string $tekst   Tekst.
	 * @param array  $fjernet Fylles med fjernede linjer.
	 * @return string
	 */
	public static function rens_tegnrot( $tekst, &$fjernet = null ) {
		$fjernet = array();
		$linjer  = explode( "\n", (string) $tekst );
		$rot     = array();
		foreach ( $linjer as $i => $l ) {
			$rot[ $i ] = self::linje_er_rot( $l );
		}
		if ( ! in_array( true, $rot, true ) ) {
			return (string) $tekst;
		}
		$lister = self::ordlister();
		$ant    = count( $linjer );
		for ( $i = 1; $i < $ant - 1; $i++ ) {
			if ( $rot[ $i ] || ! $rot[ $i - 1 ] || ! $rot[ $i + 1 ] ) {
				continue;
			}
			$l = trim( $linjer[ $i ] );
			if ( '' === $l || ( function_exists( 'mb_strlen' ) ? mb_strlen( $l, 'UTF-8' ) : strlen( $l ) ) > 40 ) {
				continue;
			}
			$kjent = false;
			foreach ( self::ord_i( $l ) as $o ) {
				if ( isset( $lister['kjente'][ $o ] ) ) {
					$kjent = true;
					break;
				}
			}
			if ( ! $kjent ) {
				$rot[ $i ] = 'mellom';
			}
		}
		$ut = array();
		foreach ( $linjer as $i => $l ) {
			if ( $rot[ $i ] ) {
				$fjernet[] = $l;
			} else {
				$ut[] = $l;
			}
		}
		return implode( "\n", $ut );
	}

	/* ---- Lenke-filtrering og URL-håndtering ---- */

	/**
	 * @param string $href   Lenke.
	 * @param string $base   Side-URL.
	 * @param array  $source Konfig.
	 * @return string|null
	 */
	protected function filter_link( $href, $base, $source ) {
		$href = trim( $href );
		if ( '' === $href ) {
			return null;
		}
		if ( preg_match( '~^\s*(mailto:|tel:|javascript:|data:|#)~i', $href ) ) {
			return null;
		}

		$abs = $this->absolutize( $base, $href );
		if ( ! $abs || ! preg_match( '#^https?://#i', $abs ) ) {
			return null;
		}

		$abs   = $this->normalize_url( $abs );
		$parts = wp_parse_url( $abs );
		if ( empty( $parts['host'] ) ) {
			return null;
		}

		if ( BBEE_Plugin::is_blocked_url( $abs ) ) {
			return null;
		}

		if ( ! empty( $source['same_domain'] ) ) {
			$lh      = preg_replace( '/^www\./', '', strtolower( $parts['host'] ) );
			$sh      = preg_replace( '/^www\./', '', strtolower( $source['host'] ) );
			$is_same = ( $lh === $sh );
			$is_sub  = ( strlen( $lh ) > strlen( $sh ) + 1 && substr( $lh, -( strlen( $sh ) + 1 ) ) === '.' . $sh );
			if ( ! $is_same && ! $is_sub ) {
				return null;
			}
		}

		if ( ! empty( $source['path_prefix'] ) ) {
			$path = isset( $parts['path'] ) ? $parts['path'] : '/';
			if ( 0 !== strpos( $path, $source['path_prefix'] ) ) {
				return null;
			}
		}

		if ( preg_match( '#\.(jpe?g|png|gif|svg|webp|zip|docx?|xlsx?|pptx?|mp4|mp3|css|js|xml|json|ics|csv)(\?|$)#i', $abs ) ) {
			return null;
		}

		// Sider uten fagverdi og eldre versjoner fra endringshistorikk hentes ikke.
		if ( ! $this->is_pdf_url( $abs ) && '' !== self::stoy_grunn( $abs ) ) {
			return null;
		}
		if ( self::er_gammel_versjon( $abs ) ) {
			return null;
		}

		if ( $this->is_pdf_url( $abs ) ) {
			$settings = BBEE_Plugin::get_settings();
			if ( empty( $settings['crawl_follow_pdf'] ) || ! BBEE_Pdf::available() ) {
				return null;
			}
		}

		return $abs;
	}

	/**
	 * @param string $url URL.
	 * @return bool
	 */
	protected function is_pdf_url( $url ) {
		return (bool) preg_match( '#\.pdf(\?|$)#i', $url );
	}

	/**
	 * @param string $base Basis.
	 * @param string $rel  Lenke.
	 * @return string|null
	 */
	protected function absolutize( $base, $rel ) {
		if ( preg_match( '#^https?://#i', $rel ) ) {
			return $rel;
		}
		$b = wp_parse_url( $base );
		if ( empty( $b['scheme'] ) || empty( $b['host'] ) ) {
			return null;
		}
		$origin = $b['scheme'] . '://' . $b['host'] . ( isset( $b['port'] ) ? ':' . $b['port'] : '' );

		if ( 0 === strpos( $rel, '//' ) ) {
			return $b['scheme'] . ':' . $rel;
		}
		if ( 0 === strpos( $rel, '/' ) ) {
			return $origin . $rel;
		}
		$path = isset( $b['path'] ) ? $b['path'] : '/';
		$dir  = preg_replace( '#/[^/]*$#', '/', $path );
		if ( '' === $dir ) {
			$dir = '/';
		}
		$combined = $dir . $rel;

		$segments = array();
		foreach ( explode( '/', $combined ) as $seg ) {
			if ( '.' === $seg || '' === $seg ) {
				if ( '' === $seg ) {
					$segments[] = '';
				}
				continue;
			}
			if ( '..' === $seg ) {
				array_pop( $segments );
				continue;
			}
			$segments[] = $seg;
		}
		$clean_path = '/' . ltrim( implode( '/', $segments ), '/' );
		return $origin . $clean_path;
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	protected function normalize_url( $url ) {
		$url  = trim( $url );
		$hash = strpos( $url, '#' );
		if ( false !== $hash ) {
			$url = substr( $url, 0, $hash );
		}
		$parts = wp_parse_url( $url );
		if ( isset( $parts['path'] ) && '/' !== $parts['path'] ) {
			$url = rtrim( $url, '/' );
		}
		return $url;
	}

	/* ---- robots.txt ---- */

	/**
	 * @param string $url URL.
	 * @return bool
	 */
	protected function robots_allowed( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return true;
		}
		$host = $parts['scheme'] . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';

		if ( ! isset( $this->robots_cache[ $host ] ) ) {
			$this->robots_cache[ $host ] = $this->fetch_robots( $host );
		}
		if ( isset( $parts['query'] ) && '' !== $parts['query'] ) {
			$path .= '?' . $parts['query'];
		}
		foreach ( $this->robots_cache[ $host ] as $rule ) {
			if ( '' !== $rule && $this->robots_treff( $path, $rule ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Treffer en Disallow-regel stien? Støtter «*» (hva som helst) og «$» (slutt).
	 *
	 * @param string $path Sti (med query).
	 * @param string $rule Regel.
	 * @return bool
	 */
	protected function robots_treff( $path, $rule ) {
		if ( false === strpos( $rule, '*' ) && '$' !== substr( $rule, -1 ) ) {
			return 0 === strpos( $path, $rule );
		}
		$slutt = '$' === substr( $rule, -1 );
		$rule  = $slutt ? substr( $rule, 0, -1 ) : $rule;
		$re    = str_replace( '\*', '.*', preg_quote( $rule, '#' ) );
		return (bool) preg_match( '#^' . $re . ( $slutt ? '$' : '' ) . '#', $path );
	}

	/**
	 * @param string $host Vert m/skjema.
	 * @return array
	 */
	protected function fetch_robots( $host ) {
		$response = wp_remote_get( $host . '/robots.txt', array( 'timeout' => 10, 'user-agent' => $this->user_agent() ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}
		$lines    = preg_split( '/\r\n|\r|\n/', (string) wp_remote_retrieve_body( $response ) );
		$disallow = array();
		$applies  = false;
		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/#.*$/', '', $line ) );
			if ( '' === $line ) {
				continue;
			}
			if ( preg_match( '/^user-agent\s*:\s*(.+)$/i', $line, $m ) ) {
				$ua      = strtolower( trim( $m[1] ) );
				$applies = ( '*' === $ua || false !== strpos( $ua, 'bumblebee' ) );
			} elseif ( $applies && preg_match( '/^disallow\s*:\s*(.*)$/i', $line, $m ) ) {
				$rule = trim( $m[1] );
				if ( '' !== $rule ) {
					$disallow[] = $rule;
				}
			}
		}
		return $disallow;
	}

	/**
	 * @return string
	 */
	protected function user_agent() {
		$settings = BBEE_Plugin::get_settings();
		if ( ! empty( $settings['crawl_user_agent'] ) ) {
			return $settings['crawl_user_agent'];
		}
		return 'BumblebeeBot/1.0 (+' . home_url( '/' ) . ')';
	}
}
