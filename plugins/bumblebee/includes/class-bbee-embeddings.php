<?php
/**
 * Embeddings-lag for semantisk søk (Voyage AI / OpenAI).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Embeddings
 */
class BBEE_Embeddings {

	const VOYAGE_URL = 'https://api.voyageai.com/v1/embeddings';
	const OPENAI_URL = 'https://api.openai.com/v1/embeddings';

	/** @var string */
	protected $provider;
	/** @var string */
	protected $api_key;
	/** @var string */
	protected $model;

	/** @var int Tokens brukt i siste kall, ifølge API-et (0 hvis ukjent). */
	public $last_usage_tokens = 0;

	/**
	 * @param array|null $settings Innstillinger.
	 */
	public function __construct( $settings = null ) {
		if ( null === $settings ) {
			$settings = BBEE_Plugin::get_settings();
		}
		$this->provider = ! empty( $settings['embed_provider'] ) ? $settings['embed_provider'] : 'voyage';
		$this->api_key  = isset( $settings['embed_api_key'] ) ? trim( (string) $settings['embed_api_key'] ) : '';
		$this->model    = ! empty( $settings['embed_model'] ) ? $settings['embed_model'] : $this->default_model();
	}

	/**
	 * @return string
	 */
	protected function default_model() {
		return ( 'openai' === $this->provider ) ? 'text-embedding-3-small' : 'voyage-3.5';
	}

	/**
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->api_key;
	}

	/**
	 * @return string
	 */
	public function model_id() {
		return $this->provider . ':' . $this->model;
	}

	/**
	 * @param string $text   Tekst.
	 * @param string $intent query|document.
	 * @return array|WP_Error
	 */
	public function embed_one( $text, $intent = 'document' ) {
		$vectors = $this->embed( array( (string) $text ), $intent );
		if ( is_wp_error( $vectors ) ) {
			return $vectors;
		}
		return isset( $vectors[0] ) ? $vectors[0] : new WP_Error( 'bbee_embed_empty', __( 'Tomt embedding-svar.', 'bumblebee' ) );
	}

	/**
	 * @param array  $texts  Tekster.
	 * @param string $intent query|document.
	 * @return array|WP_Error
	 */
	public function embed( $texts, $intent = 'document' ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'bbee_embed_no_key', __( 'Ingen embedding-API-nøkkel er satt.', 'bumblebee' ) );
		}
		$this->last_usage_tokens = 0;
		$texts = array_values( array_map( 'strval', (array) $texts ) );
		if ( empty( $texts ) ) {
			return array();
		}
		if ( 'openai' === $this->provider ) {
			return $this->embed_openai( $texts );
		}
		return $this->embed_voyage( $texts, $intent );
	}

	/**
	 * @param array  $texts  Tekster.
	 * @param string $intent query|document.
	 * @return array|WP_Error
	 */
	protected function embed_voyage( $texts, $intent ) {
		$body = array(
			'model'      => $this->model,
			'input'      => $texts,
			'input_type' => ( 'query' === $intent ) ? 'query' : 'document',
		);
		$data = $this->request( self::VOYAGE_URL, array( 'Authorization' => 'Bearer ' . $this->api_key ), $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		return $this->extract_vectors( $data );
	}

	/**
	 * @param array $texts Tekster.
	 * @return array|WP_Error
	 */
	protected function embed_openai( $texts ) {
		$body = array( 'model' => $this->model, 'input' => $texts );
		$data = $this->request( self::OPENAI_URL, array( 'Authorization' => 'Bearer ' . $this->api_key ), $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( isset( $data['usage']['total_tokens'] ) ) {
			$this->last_usage_tokens = (int) $data['usage']['total_tokens'];
		}
		return $this->extract_vectors( $data );
	}

	/**
	 * @param array $data Svar.
	 * @return array|WP_Error
	 */
	protected function extract_vectors( $data ) {
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return new WP_Error( 'bbee_embed_bad', __( 'Uventet embedding-svar.', 'bumblebee' ) );
		}
		$rows = $data['data'];
		usort(
			$rows,
			function ( $a, $b ) {
				$ai = isset( $a['index'] ) ? (int) $a['index'] : 0;
				$bi = isset( $b['index'] ) ? (int) $b['index'] : 0;
				return $ai - $bi;
			}
		);
		$out = array();
		foreach ( $rows as $row ) {
			if ( ! isset( $row['embedding'] ) || ! is_array( $row['embedding'] ) ) {
				return new WP_Error( 'bbee_embed_bad', __( 'Manglende vektor i embedding-svar.', 'bumblebee' ) );
			}
			$out[] = self::normalize( array_map( 'floatval', $row['embedding'] ) );
		}
		return $out;
	}

	/**
	 * @param string $url     URL.
	 * @param array  $headers Headere.
	 * @param array  $body    Body.
	 * @return array|WP_Error
	 */
	protected function request( $url, $headers, $body ) {
		$headers  = array_merge( array( 'Content-Type' => 'application/json' ), $headers );
		$response = wp_remote_post( $url, array( 'timeout' => 60, 'headers' => $headers, 'body' => wp_json_encode( $body ) ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$msg = __( 'Feil fra embedding-API.', 'bumblebee' );
			if ( isset( $data['error']['message'] ) ) {
				$msg = $data['error']['message'];
			} elseif ( isset( $data['detail'] ) ) {
				$msg = is_string( $data['detail'] ) ? $data['detail'] : wp_json_encode( $data['detail'] );
			}
			return new WP_Error( 'bbee_embed_http', $msg, array( 'status' => $code ) );
		}
		return is_array( $data ) ? $data : new WP_Error( 'bbee_embed_json', __( 'Kunne ikke tolke embedding-svar.', 'bumblebee' ) );
	}

	/**
	 * @param array $vec Vektor.
	 * @return array
	 */
	public static function normalize( $vec ) {
		$sum = 0.0;
		foreach ( $vec as $v ) {
			$sum += $v * $v;
		}
		if ( $sum <= 0 ) {
			return $vec;
		}
		$norm = sqrt( $sum );
		foreach ( $vec as $i => $v ) {
			$vec[ $i ] = $v / $norm;
		}
		return $vec;
	}

	/**
	 * @param array $a Vektor a.
	 * @param array $b Vektor b.
	 * @return float
	 */
	public static function dot( $a, $b ) {
		$sum = 0.0;
		$n   = min( count( $a ), count( $b ) );
		for ( $i = 0; $i < $n; $i++ ) {
			$sum += $a[ $i ] * $b[ $i ];
		}
		return $sum;
	}
}

/**
 * Kompakt vektorlager for semantisk søk over hele indeksen.
 *
 * Bakgrunn: Vektorene ligger som JSON i wp_bbee_chunks (ca. 32 kB per bit).
 * Med over 27 000 biter er det for tregt å dekode alt per søk. Derfor lagrer
 * vi en kopi som pakket float32 i en egen tabell:
 *
 * - v_short: de første SHORT_DIMS dimensjonene, normalisert på nytt.
 *   text-embedding-3-small er trent slik at en avkortet vektor fortsatt
 *   gir god likhet (samme grep som API-parameteren «dimensions»). Vi
 *   trenger altså ingen ny embedding.
 * - v_full: hele vektoren som float32. Brukes til å regne nøyaktig poeng
 *   for de beste kandidatene fra grovsøket.
 *
 * Søket går i to trinn: grovsøk med v_short over alle biter, så nøyaktig
 * poeng med v_full for de beste kandidatene.
 *
 * Tabellen holdes i takt med wp_bbee_chunks uten endringer i indekseren:
 * sync() legger til biter som har fått embedding og fjerner biter som er slettet.
 */
class BBEE_Vector_Store {

	const SHORT_DIMS = 256;
	const DB_VERSION = '1';

	/**
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'bbee_vectors';
	}

	/**
	 * Lag tabellen hvis den mangler.
	 */
	public static function ensure_table() {
		if ( self::DB_VERSION === get_option( 'bbee_vectors_db', '' ) ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
			chunk_id BIGINT UNSIGNED NOT NULL,
			embed_model VARCHAR(64) NOT NULL DEFAULT '',
			v_short BLOB NOT NULL,
			v_full BLOB NOT NULL,
			PRIMARY KEY  (chunk_id),
			KEY embed_model (embed_model)
			) {$charset};"
		);
		update_option( 'bbee_vectors_db', self::DB_VERSION, false );
	}

	/**
	 * Pakk en vektor som float32 (maskinens byte-rekkefølge).
	 *
	 * @param array $vec Vektor.
	 * @return string
	 */
	public static function pack_vec( $vec ) {
		return pack( 'g*', ...array_map( 'floatval', array_values( $vec ) ) );
	}

	/**
	 * Lag den korte vektoren: de første SHORT_DIMS verdiene, normalisert på nytt.
	 *
	 * @param array $vec Full vektor.
	 * @return array
	 */
	public static function shorten( $vec ) {
		return BBEE_Embeddings::normalize( array_slice( array_values( $vec ), 0, self::SHORT_DIMS ) );
	}

	/**
	 * Er lageret i takt med bit-tabellen for denne modellen?
	 * Billig sjekk: antall, største ID og sum av ID-er (bruker indeksen på embed_model).
	 *
	 * @param string $model_id Modell-ID.
	 * @return bool
	 */
	public static function in_sync( $model_id ) {
		$st = self::state( $model_id );
		return $st['chunks'] === $st['vectors'] && $st['chunks_sig'] === $st['vectors_sig'];
	}

	/**
	 * Antall biter med embedding og antall vektorer i lageret, med en enkel signatur.
	 *
	 * @param string $model_id Modell-ID.
	 * @return array{chunks:int,vectors:int,chunks_sig:string,vectors_sig:string}
	 */
	public static function state( $model_id ) {
		global $wpdb;
		self::ensure_table();
		$chunks  = BBEE_Indexer::table_name();
		$vectors = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$a = (array) $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS n, COALESCE(MAX(id),0) AS mx, COALESCE(SUM(id),0) AS sm FROM {$chunks} WHERE embed_model = %s", $model_id ), ARRAY_A );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$b = (array) $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS n, COALESCE(MAX(chunk_id),0) AS mx, COALESCE(SUM(chunk_id),0) AS sm FROM {$vectors} WHERE embed_model = %s", $model_id ), ARRAY_A );
		return array(
			'chunks'      => isset( $a['n'] ) ? (int) $a['n'] : 0,
			'vectors'     => isset( $b['n'] ) ? (int) $b['n'] : 0,
			'chunks_sig'  => ( isset( $a['mx'] ) ? $a['mx'] : '' ) . ':' . ( isset( $a['sm'] ) ? $a['sm'] : '' ),
			'vectors_sig' => ( isset( $b['mx'] ) ? $b['mx'] : '' ) . ':' . ( isset( $b['sm'] ) ? $b['sm'] : '' ),
		);
	}

	/**
	 * Bring lageret i takt med bit-tabellen.
	 *
	 * @param string $model_id    Modell-ID.
	 * @param float  $max_seconds Tidsgrense (0 = ingen grense).
	 * @return array{added:int,removed:int,done:bool}
	 */
	public static function sync( $model_id, $max_seconds = 0.0 ) {
		global $wpdb;
		self::ensure_table();
		$chunks  = BBEE_Indexer::table_name();
		$vectors = self::table_name();
		$start   = microtime( true );

		// Fjern vektorer for biter som er slettet eller har fått en annen modell.
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$removed = (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE v FROM {$vectors} v LEFT JOIN {$chunks} c ON c.id = v.chunk_id AND c.embed_model = v.embed_model
				WHERE c.id IS NULL OR v.embed_model <> %s",
				$model_id
			)
		);

		// Finn ID-er som mangler. Bare ID-er her: å lese JSON-kolonnen for alle rader er tregt.
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$missing = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT c.id FROM {$chunks} c LEFT JOIN {$vectors} v ON v.chunk_id = c.id
				WHERE c.embed_model = %s AND v.chunk_id IS NULL ORDER BY c.id",
				$model_id
			)
		);

		$added = 0;
		$done  = true;
		foreach ( array_chunk( array_map( 'intval', (array) $missing ), 200 ) as $batch ) {
			if ( $max_seconds > 0 && ( microtime( true ) - $start ) > $max_seconds ) {
				$done = false;
				break;
			}
			$in = implode( ',', $batch );
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, embedding FROM {$chunks} WHERE embed_model = %s AND id IN ({$in})", $model_id ), ARRAY_A );
			if ( empty( $rows ) ) {
				continue;
			}
			$values = array();
			$params = array();
			foreach ( $rows as $row ) {
				$vec = json_decode( (string) $row['embedding'], true );
				if ( ! is_array( $vec ) || empty( $vec ) ) {
					// Tom eller ødelagt vektor: lagre en tom rad, så vi ikke prøver igjen hver gang.
					$short = '';
					$full  = '';
				} else {
					$full  = self::pack_vec( $vec );
					$short = self::pack_vec( self::shorten( $vec ) );
				}
				// Binærdata sendes som heks, så spørringen bare inneholder ASCII.
				$values[] = '(%d, %s, UNHEX(%s), UNHEX(%s))';
				$params[] = (int) $row['id'];
				$params[] = $model_id;
				$params[] = bin2hex( $short );
				$params[] = bin2hex( $full );
			}
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $wpdb->prepare( "REPLACE INTO {$vectors} (chunk_id, embed_model, v_short, v_full) VALUES " . implode( ',', $values ), $params ) );
			$added += count( $rows );
		}
		return array( 'added' => $added, 'removed' => $removed, 'done' => $done );
	}

	/**
	 * Nøyaktig cosinuspoeng for gitte biter (brukes for fulltekst-treff i hybrid søk).
	 *
	 * @param array  $qvec     Spørsmålsvektor (normalisert, full lengde).
	 * @param string $model_id Modell-ID.
	 * @param array  $ids      Bit-ID-er.
	 * @return array chunk_id => poeng.
	 */
	public static function score_ids( $qvec, $model_id, $ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', (array) $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$vectors = self::table_name();
		$in      = implode( ',', $ids );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT chunk_id, v_full FROM {$vectors} WHERE embed_model = %s AND chunk_id IN ({$in})", $model_id ), ARRAY_N );
		$qvec = array_values( $qvec );
		$n    = count( $qvec );
		$out  = array();
		foreach ( (array) $rows as $row ) {
			if ( '' === $row[1] ) {
				continue;
			}
			$v = unpack( 'g*', $row[1] );
			$m = min( $n, count( $v ) );
			$s = 0.0;
			for ( $i = 0; $i < $m; $i++ ) {
				$s += $qvec[ $i ] * $v[ $i + 1 ];
			}
			$out[ (int) $row[0] ] = $s;
		}
		return $out;
	}

	/**
	 * Semantisk søk over hele lageret.
	 *
	 * @param array  $qvec     Spørsmålsvektor (normalisert, full lengde).
	 * @param string $model_id Modell-ID.
	 * @param int    $limit    Antall treff.
	 * @param int    $rescore  Antall kandidater fra grovsøket som får nøyaktig poeng.
	 * @return array chunk_id => poeng, sortert synkende.
	 */
	public static function search( $qvec, $model_id, $limit, $rescore = 400 ) {
		global $wpdb;
		$vectors = self::table_name();
		$qvec    = array_values( $qvec );
		$qshort  = self::shorten( $qvec );
		$dims    = count( $qshort );
		$rescore = max( $limit, (int) $rescore );

		// Trinn 1: grovsøk med korte vektorer. Hentes i blokker, så minnet holder seg lavt.
		$scores = array();
		$last   = 0;
		$block  = 4000;
		while ( true ) {
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT chunk_id, v_short FROM {$vectors} WHERE embed_model = %s AND chunk_id > %d ORDER BY chunk_id LIMIT %d",
					$model_id,
					$last,
					$block
				),
				ARRAY_N
			);
			if ( empty( $rows ) ) {
				break;
			}
			foreach ( $rows as $row ) {
				$last = (int) $row[0];
				if ( '' === $row[1] ) {
					continue;
				}
				$v = unpack( 'g*', $row[1] );
				$s = 0.0;
				for ( $i = 0; $i < $dims; $i++ ) {
					$s += $qshort[ $i ] * $v[ $i + 1 ];
				}
				$scores[ $last ] = $s;
			}
			if ( count( $rows ) < $block ) {
				break;
			}
		}
		if ( empty( $scores ) ) {
			return array();
		}
		arsort( $scores );
		$top = array_slice( $scores, 0, $rescore, true );

		// Trinn 2: nøyaktig poeng med hele vektoren for de beste kandidatene.
		$ids = implode( ',', array_map( 'intval', array_keys( $top ) ) );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows  = $wpdb->get_results( "SELECT chunk_id, v_full FROM {$vectors} WHERE chunk_id IN ({$ids})", ARRAY_N );
		$exact = array();
		$n     = count( $qvec );
		foreach ( (array) $rows as $row ) {
			if ( '' === $row[1] ) {
				continue;
			}
			$v = unpack( 'g*', $row[1] );
			$m = min( $n, count( $v ) );
			$s = 0.0;
			for ( $i = 0; $i < $m; $i++ ) {
				$s += $qvec[ $i ] * $v[ $i + 1 ];
			}
			$exact[ (int) $row[0] ] = $s;
		}
		arsort( $exact );
		return array_slice( $exact, 0, $limit, true );
	}
}
