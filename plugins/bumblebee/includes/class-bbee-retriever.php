<?php
/**
 * Gjenfinning av relevante kildeutdrag (fulltekst / semantisk / hybrid + rerank).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Retriever
 */
class BBEE_Retriever {

	/** @var array Måletall fra siste søk (ms per trinn, antall kandidater). */
	public $last_timing = array();

	/**
	 * Finn de mest relevante bitene for et spørsmål.
	 *
	 * Hybrid (standard): meningssøk over hele indeksen + fulltekstsøk.
	 * Kandidatene fra begge får både meningspoeng og ordpoeng, og
	 * BBEE_Reranker::fuse() vekter dem sammen.
	 *
	 * @param string $query Spørsmål.
	 * @param int    $limit Antall.
	 * @return array
	 */
	public function search( $query, $limit = 6 ) {
		$query = trim( wp_strip_all_tags( (string) $query ) );
		$limit = max( 1, (int) $limit );

		$override = apply_filters( 'bbee_retriever_results', null, $query, $limit );
		if ( is_array( $override ) ) {
			return $override;
		}
		if ( '' === $query ) {
			return array();
		}

		$this->last_timing = array();
		$t0       = microtime( true );
		$settings = BBEE_Plugin::get_settings();
		$mode     = ! empty( $settings['retriever'] ) ? $settings['retriever'] : 'hybrid';
		$reranker = new BBEE_Reranker( $settings );
		$embed    = new BBEE_Embeddings( $settings );

		$can_semantic = $embed->is_configured() && $this->has_embeddings( $embed->model_id() );
		$pool         = max( $limit * 4, (int) $settings['candidate_pool'] );

		if ( 'semantic' === $mode && $can_semantic ) {
			$results = $this->semantic_search( $query, $pool, $embed );
		} elseif ( 'hybrid' === $mode && $can_semantic ) {
			$results = $this->hybrid_search( $query, $pool, $embed );
		} else {
			$results = $this->fulltext_search( $query, $pool );
			if ( empty( $results ) ) {
				$results = $this->like_search( $query, $pool );
			}
		}

		// Sikkerhetsnett: aldri gi videre tekst fra sperrede domener.
		$results = array_values(
			array_filter(
				$results,
				function ( $r ) {
					return empty( $r['url'] ) || ! BBEE_Plugin::is_blocked_url( $r['url'] );
				}
			)
		);

		$caps = $this->type_caps( $query );
		if ( ! empty( $caps ) ) {
			$results = $this->apply_type_caps( $results, $caps );
		}

		if ( $reranker->is_active() && count( $results ) > 1 ) {
			$results = $reranker->rerank( $query, $results, $limit );
		} else {
			$results = array_slice( $results, 0, $limit );
		}
		$this->last_timing['totalt_ms'] = (int) round( ( microtime( true ) - $t0 ) * 1000 );
		return $results;
	}

	/**
	 * Hent nabobiter (samme side, chunk_index ± $radius) for treffene.
	 *
	 * @param array $hits   Treff fra search().
	 * @param int   $radius Antall naboer på hver side.
	 * @return array chunk-id => rad (uten treffene selv).
	 */
	public function neighbors( $hits, $radius = 1 ) {
		global $wpdb;
		$radius = max( 0, (int) $radius );
		if ( 0 === $radius || empty( $hits ) ) {
			return array();
		}
		$table  = BBEE_Indexer::table_name();
		$where  = array();
		$params = array();
		$have   = array();
		foreach ( $hits as $h ) {
			$have[ (int) $h['id'] ] = true;
			if ( ! isset( $h['chunk_index'] ) ) {
				continue;
			}
			$i        = (int) $h['chunk_index'];
			$where[]  = '(post_id = %d AND url = %s AND chunk_index BETWEEN %d AND %d)';
			$params[] = (int) $h['post_id'];
			$params[] = (string) $h['url'];
			$params[] = $i - $radius;
			$params[] = $i + $radius;
		}
		if ( empty( $where ) ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content FROM {$table} WHERE " . implode( ' OR ', $where ),
				$params
			),
			ARRAY_A
		);
		$out = array();
		foreach ( $this->normalize_rows( $rows ) as $r ) {
			if ( isset( $have[ $r['id'] ] ) ) {
				continue;
			}
			$out[ $r['id'] ] = $r;
		}
		return $out;
	}

	/**
	 * Tak på antall treff per innholdstype.
	 *
	 * Verktøyregisteret har over halvparten av bitene i indeksen. Uten tak
	 * fyller korte verktøybeskrivelser opp svarene på fagspørsmål. Vi lar
	 * derfor maks 2 verktøytreff slippe gjennom. Taket gjelder ikke når
	 * spørsmålet handler om verktøy eller programvare.
	 *
	 * @param string $query Spørsmål.
	 * @return array post_type => maks antall treff.
	 */
	protected function type_caps( $query ) {
		$caps = array( 'verktoy' => 2 );
		if ( preg_match( '/verktøy|verktoy|programvare|software|applikasjon|\bapp\b|\bapper\b|plugin|\btools?\b/iu', (string) $query ) ) {
			$caps = array();
		}
		return (array) apply_filters( 'bbee_type_caps', $caps, $query );
	}

	/**
	 * Fjern treff over taket. Rekkefølgen ellers beholdes.
	 *
	 * @param array $results Treff.
	 * @param array $caps    post_type => maks.
	 * @return array
	 */
	protected function apply_type_caps( $results, $caps ) {
		$count = array();
		$out   = array();
		foreach ( $results as $r ) {
			$type = isset( $r['post_type'] ) ? $r['post_type'] : '';
			if ( isset( $caps[ $type ] ) ) {
				$count[ $type ] = isset( $count[ $type ] ) ? $count[ $type ] + 1 : 1;
				if ( $count[ $type ] > (int) $caps[ $type ] ) {
					continue;
				}
			}
			$out[] = $r;
		}
		return $out;
	}

	/**
	 * @param string $model_id Modell-ID.
	 * @return bool
	 */
	protected function has_embeddings( $model_id ) {
		global $wpdb;
		$table = BBEE_Indexer::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$one = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE embedding IS NOT NULL AND embedding <> '' AND embed_model = %s LIMIT 1",
				$model_id
			)
		);
		return ! empty( $one );
	}

	/**
	 * Gjør vektorlageret klart. Synkroniserer nye eller slettede biter innen en kort tidsgrense.
	 *
	 * @param string $model_id Modell-ID.
	 * @return bool Om lageret kan brukes.
	 */
	protected function vector_store_ready( $model_id ) {
		if ( ! class_exists( 'BBEE_Vector_Store' ) || ! apply_filters( 'bbee_use_vector_store', true ) ) {
			return false;
		}
		if ( BBEE_Vector_Store::in_sync( $model_id ) ) {
			return true;
		}
		// Små endringer tas med en gang. Store endringer fullføres av cron (bbee_vector_sync).
		$r = BBEE_Vector_Store::sync( $model_id, (float) apply_filters( 'bbee_vector_sync_seconds', 1.0 ) );
		if ( empty( $r['done'] ) && ! wp_next_scheduled( 'bbee_vector_sync' ) ) {
			wp_schedule_single_event( time() + 5, 'bbee_vector_sync' );
		}
		if ( ! empty( $r['done'] ) ) {
			return true;
		}
		// Bruk lageret bare når nesten alle biter er med. Ellers gammel metode til cron er ferdig.
		$st = BBEE_Vector_Store::state( $model_id );
		return $st['chunks'] > 0 && $st['vectors'] / $st['chunks'] >= 0.95;
	}

	/**
	 * Meningssøk. Bruker vektorlageret (hele indeksen) når det finnes.
	 *
	 * @param string          $query Spørsmål.
	 * @param int             $limit Antall.
	 * @param BBEE_Embeddings $embed Klient.
	 * @param array|null      $qvec  Ferdig spørsmålsvektor (valgfri).
	 * @return array
	 */
	protected function semantic_search( $query, $limit, $embed, $qvec = null ) {
		if ( null === $qvec ) {
			$t    = microtime( true );
			$qvec = $embed->embed_one( $query, 'query' );
			$this->last_timing['embedding_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );
		}
		if ( is_wp_error( $qvec ) ) {
			$fallback = $this->fulltext_search( $query, $limit );
			return ! empty( $fallback ) ? $fallback : $this->like_search( $query, $limit );
		}

		$t     = microtime( true );
		$model = $embed->model_id();
		if ( $this->vector_store_ready( $model ) ) {
			$scores = BBEE_Vector_Store::search( $qvec, $model, $limit );
			$rows   = $this->rows_by_ids( array_keys( $scores ) );
			$out    = array();
			foreach ( $scores as $id => $score ) {
				if ( isset( $rows[ $id ] ) ) {
					$row          = $rows[ $id ];
					$row['score'] = (float) $score;
					$row['sem']   = (float) $score;
					$out[]        = $row;
				}
			}
			$this->last_timing['meningssok_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );
			return $out;
		}

		// Reserve: gammel metode (JSON-vektorer, begrenset utvalg ved stor indeks).
		$rows = $this->fetch_embedding_rows( $model, $query );
		if ( empty( $rows ) ) {
			return array();
		}

		$scored = array();
		foreach ( $rows as $row ) {
			$vec = json_decode( $row['embedding'], true );
			if ( ! is_array( $vec ) || empty( $vec ) ) {
				continue;
			}
			$row['score'] = BBEE_Embeddings::dot( $qvec, $vec );
			$row['sem']   = $row['score'];
			unset( $row['embedding'] );
			$scored[] = $row;
		}

		usort(
			$scored,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] < $b['score'] ) ? 1 : -1;
			}
		);
		$this->last_timing['meningssok_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );
		return array_slice( $this->normalize_rows( $scored ), 0, $limit );
	}

	/**
	 * Hybrid søk: meningssøk og fulltekst, slått sammen med lokal omrangering.
	 *
	 * @param string          $query Spørsmål.
	 * @param int             $limit Antall.
	 * @param BBEE_Embeddings $embed Klient.
	 * @return array
	 */
	protected function hybrid_search( $query, $limit, $embed ) {
		$t    = microtime( true );
		$qvec = $embed->embed_one( $query, 'query' );
		$this->last_timing['embedding_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );

		$semantic = is_wp_error( $qvec ) ? array() : $this->semantic_search( $query, $limit, $embed, $qvec );

		$t        = microtime( true );
		$fulltext = $this->fulltext_search( $query, $limit );
		if ( empty( $fulltext ) ) {
			$fulltext = $this->like_search( $query, $limit );
		}
		$this->last_timing['fulltekst_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );

		$t    = microtime( true );
		$cand = array();
		foreach ( $semantic as $doc ) {
			$doc['ft']            = 0.0;
			$cand[ $doc['id'] ]   = $doc;
		}
		foreach ( $fulltext as $doc ) {
			if ( isset( $cand[ $doc['id'] ] ) ) {
				$cand[ $doc['id'] ]['ft'] = (float) $doc['score'];
				continue;
			}
			$doc['ft']          = (float) $doc['score'];
			$doc['sem']         = null;
			$cand[ $doc['id'] ] = $doc;
		}
		if ( empty( $cand ) ) {
			return array();
		}

		// Fyll inn manglende poeng, så alle kandidater kan sammenlignes på begge mål.
		$need_sem = array();
		$need_ft  = array();
		foreach ( $cand as $id => $doc ) {
			if ( null === $doc['sem'] ) {
				$need_sem[] = $id;
			}
			if ( 0.0 === $doc['ft'] ) {
				$need_ft[] = $id;
			}
		}
		if ( ! empty( $need_sem ) && ! is_wp_error( $qvec ) && class_exists( 'BBEE_Vector_Store' ) ) {
			$sem = BBEE_Vector_Store::score_ids( $qvec, $embed->model_id(), $need_sem );
			foreach ( $need_sem as $id ) {
				$cand[ $id ]['sem'] = isset( $sem[ $id ] ) ? $sem[ $id ] : 0.0;
			}
		}
		if ( ! empty( $need_ft ) ) {
			$ft = $this->fulltext_scores( $query, $need_ft );
			foreach ( $ft as $id => $score ) {
				$cand[ $id ]['ft'] = $score;
			}
		}
		foreach ( $cand as $id => $doc ) {
			if ( null === $doc['sem'] ) {
				$cand[ $id ]['sem'] = 0.0;
			}
		}

		$out = BBEE_Reranker::fuse( array_values( $cand ) );
		$this->last_timing['omrangering_ms'] = (int) round( ( microtime( true ) - $t ) * 1000 );
		$this->last_timing['kandidater']     = count( $cand );
		return array_slice( $out, 0, $limit );
	}

	/**
	 * Fulltekstpoeng for gitte biter.
	 *
	 * @param string $query Spørsmål.
	 * @param array  $ids   Bit-ID-er.
	 * @return array id => poeng.
	 */
	protected function fulltext_scores( $query, $ids ) {
		global $wpdb;
		$boolean = $this->to_boolean_query( $query );
		if ( '' === $boolean || empty( $ids ) ) {
			return array();
		}
		$table = BBEE_Indexer::table_name();
		$in    = implode( ',', array_map( 'intval', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, MATCH(content, title) AGAINST (%s IN BOOLEAN MODE) AS score FROM {$table} WHERE id IN ({$in})", $boolean ),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[ (int) $r['id'] ] = (float) $r['score'];
		}
		return $out;
	}

	/**
	 * Hent rader for gitte ID-er.
	 *
	 * @param array $ids Bit-ID-er.
	 * @return array id => rad.
	 */
	protected function rows_by_ids( $ids ) {
		global $wpdb;
		if ( empty( $ids ) ) {
			return array();
		}
		$table = BBEE_Indexer::table_name();
		$in    = implode( ',', array_map( 'intval', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content FROM {$table} WHERE id IN ({$in})", ARRAY_A );
		$out  = array();
		foreach ( $this->normalize_rows( $rows ) as $r ) {
			$out[ $r['id'] ] = $r;
		}
		return $out;
	}

	/**
	 * @param string $model_id Modell-ID.
	 * @param string $query    Spørsmål.
	 * @return array
	 */
	protected function fetch_embedding_rows( $model_id, $query ) {
		global $wpdb;
		$table     = BBEE_Indexer::table_name();
		$max_brute = (int) apply_filters( 'bbee_semantic_max_bruteforce', 6000 );

		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE embedding IS NOT NULL AND embedding <> '' AND embed_model = %s",
				$model_id
			)
		);

		if ( $total <= $max_brute ) {
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content, embedding
					FROM {$table}
					WHERE embedding IS NOT NULL AND embedding <> '' AND embed_model = %s",
					$model_id
				),
				ARRAY_A
			);
		}

		$boolean = $this->to_boolean_query( $query );
		if ( '' === $boolean ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content, embedding
				FROM {$table}
				WHERE embed_model = %s AND embedding IS NOT NULL AND embedding <> ''
					AND MATCH(content, title) AGAINST (%s IN BOOLEAN MODE)
				ORDER BY MATCH(content, title) AGAINST (%s IN BOOLEAN MODE) DESC
				LIMIT 400",
				$model_id,
				$boolean,
				$boolean
			),
			ARRAY_A
		);
	}

	/**
	 * @param string $query Spørsmål.
	 * @param int    $limit Antall.
	 * @return array
	 */
	protected function fulltext_search( $query, $limit ) {
		global $wpdb;
		$table   = BBEE_Indexer::table_name();
		$boolean = $this->to_boolean_query( $query );
		if ( '' === $boolean ) {
			return array();
		}
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$sql = $wpdb->prepare(
			"SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content,
				MATCH(content, title) AGAINST (%s IN BOOLEAN MODE) AS score
			FROM {$table}
			WHERE MATCH(content, title) AGAINST (%s IN BOOLEAN MODE)
			ORDER BY score DESC
			LIMIT %d",
			$boolean,
			$boolean,
			$limit
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return $this->normalize_rows( $rows );
	}

	/**
	 * @param string $query Spørsmål.
	 * @param int    $limit Antall.
	 * @return array
	 */
	protected function like_search( $query, $limit ) {
		global $wpdb;
		$table = BBEE_Indexer::table_name();
		$words = $this->keywords( $query );
		if ( empty( $words ) ) {
			return array();
		}
		$where  = array();
		$params = array();
		foreach ( $words as $word ) {
			$like     = '%' . $wpdb->esc_like( $word ) . '%';
			$where[]  = '(content LIKE %s OR title LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		$where_sql = implode( ' OR ', $where );
		$params[]  = $limit;
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$sql = $wpdb->prepare(
			"SELECT id, post_id, source_type, source_id, post_type, chunk_index, title, url, content, 0 AS score
			FROM {$table}
			WHERE {$where_sql}
			LIMIT %d",
			$params
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		return $this->normalize_rows( $rows );
	}

	/**
	 * @param array|null $rows Rader.
	 * @return array
	 */
	protected function normalize_rows( $rows ) {
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'id'          => isset( $row['id'] ) ? (int) $row['id'] : 0,
				'post_id'     => (int) $row['post_id'],
				'source_type' => isset( $row['source_type'] ) ? (string) $row['source_type'] : 'post',
				'source_id'   => isset( $row['source_id'] ) ? (string) $row['source_id'] : (string) $row['post_id'],
				'post_type'   => isset( $row['post_type'] ) ? (string) $row['post_type'] : '',
				'chunk_index' => isset( $row['chunk_index'] ) ? (int) $row['chunk_index'] : 0,
				'title'       => (string) $row['title'],
				'url'         => (string) $row['url'],
				'content'     => (string) $row['content'],
				'score'       => isset( $row['score'] ) ? (float) $row['score'] : 0.0,
			);
			if ( isset( $row['sem'] ) ) {
				$out[ count( $out ) - 1 ]['sem'] = (float) $row['sem'];
			}
		}
		return $out;
	}

	/**
	 * @param string $query Spørsmål.
	 * @return string
	 */
	protected function to_boolean_query( $query ) {
		$words = $this->keywords( $query );
		if ( empty( $words ) ) {
			return '';
		}
		$terms = array();
		foreach ( $words as $word ) {
			$clean = preg_replace( '/[+\-><\(\)~*\"@]+/u', '', $word );
			if ( $this->mb_len( $clean ) < 2 ) {
				continue;
			}
			$terms[] = $clean . '*';
		}
		return implode( ' ', $terms );
	}

	/**
	 * @param string $query Spørsmål.
	 * @return array
	 */
	protected function keywords( $query ) {
		$query = function_exists( 'mb_strtolower' ) ? mb_strtolower( $query, 'UTF-8' ) : strtolower( $query );
		$query = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $query );
		$parts = preg_split( '/\s+/u', trim( $query ) );
		if ( ! is_array( $parts ) ) {
			return array();
		}
		$stop = $this->stopwords();
		$out  = array();
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( '' === $p || $this->mb_len( $p ) < 3 ) {
				continue;
			}
			if ( in_array( $p, $stop, true ) ) {
				continue;
			}
			$out[ $p ] = $p;
		}
		return array_values( $out );
	}

	/**
	 * @return array
	 */
	protected function stopwords() {
		$list = array(
			'og', 'i', 'jeg', 'det', 'at', 'en', 'et', 'den', 'til', 'er', 'som', 'på',
			'de', 'med', 'han', 'av', 'ikke', 'der', 'så', 'var', 'meg', 'seg', 'men',
			'ett', 'har', 'om', 'vi', 'min', 'mitt', 'ha', 'hadde', 'hun', 'nå', 'over',
			'da', 'ved', 'fra', 'du', 'ut', 'sin', 'dem', 'oss', 'opp', 'man', 'kan',
			'hans', 'hvor', 'eller', 'hva', 'skal', 'selv', 'her', 'alle', 'vil', 'ble',
			'deg', 'kunne', 'inn', 'når', 'være', 'kom', 'noen', 'noe', 'ville', 'dere',
			'for', 'the', 'and', 'that', 'this', 'what', 'how', 'why', 'who', 'was',
			'hvordan', 'hvorfor', 'hvem', 'hvilke', 'hvilken', 'hvilket', 'finnes',
		);
		return apply_filters( 'bbee_stopwords', $list );
	}

	/**
	 * @param string $s Streng.
	 * @return int
	 */
	protected function mb_len( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : strlen( $s );
	}
}
