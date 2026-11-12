<?php
/**
 * Omrangering: lokal vekting av mening og ord, og valgfri ekstern reranker (Voyage / Cohere).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Reranker
 */
class BBEE_Reranker {

	const VOYAGE_URL = 'https://api.voyageai.com/v1/rerank';
	const COHERE_URL = 'https://api.cohere.com/v2/rerank';

	/** @var bool */
	protected $enabled;
	/** @var string */
	protected $provider;
	/** @var string */
	protected $api_key;
	/** @var string */
	protected $model;

	/**
	 * @param array|null $settings Innstillinger.
	 */
	public function __construct( $settings = null ) {
		if ( null === $settings ) {
			$settings = BBEE_Plugin::get_settings();
		}
		$this->enabled  = ! empty( $settings['rerank_enabled'] );
		$this->provider = ! empty( $settings['rerank_provider'] ) ? $settings['rerank_provider'] : 'voyage';
		$this->api_key  = isset( $settings['rerank_api_key'] ) ? trim( (string) $settings['rerank_api_key'] ) : '';
		$this->model    = ! empty( $settings['rerank_model'] ) ? $settings['rerank_model'] : $this->default_model();
	}

	/**
	 * @return string
	 */
	protected function default_model() {
		return ( 'cohere' === $this->provider ) ? 'rerank-v3.5' : 'rerank-2.5';
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		return $this->enabled && '' !== $this->api_key;
	}

	/**
	 * Lokal omrangering uten API: vekter meningstreff og ordtreff sammen.
	 *
	 * Hver kandidat har «sem» (cosinuslikhet mot spørsmålet, -1..1) og «ft»
	 * (MySQL-fulltekstpoeng, 0 og oppover). Begge skaleres til 0..1 innenfor
	 * kandidatlisten. Meningstreff teller mest, men en bit som også har mange
	 * av ordene i spørsmålet, rykker opp. Det hjelper på tall, paragrafer og
	 * faguttrykk som meningssøket alene kan bomme på.
	 *
	 * @param array $candidates Kandidater med «sem» og «ft».
	 * @param float $w_sem      Vekt for meningstreff (0..1). Resten går til ordtreff.
	 * @return array Kandidatene sortert etter «score» (synkende).
	 */
	public static function fuse( $candidates, $w_sem = 0.8 ) {
		if ( empty( $candidates ) ) {
			return array();
		}
		$w_sem = (float) apply_filters( 'bbee_fuse_semantic_weight', $w_sem );
		$w_sem = max( 0.0, min( 1.0, $w_sem ) );

		$sems = array();
		$fts  = array();
		foreach ( $candidates as $c ) {
			$sems[] = isset( $c['sem'] ) ? (float) $c['sem'] : 0.0;
			$fts[]  = isset( $c['ft'] ) ? (float) $c['ft'] : 0.0;
		}
		$smin = min( $sems );
		$smax = max( $sems );
		$fmax = max( $fts );

		foreach ( $candidates as $i => $c ) {
			$sem_n = ( $smax > $smin ) ? ( $sems[ $i ] - $smin ) / ( $smax - $smin ) : 1.0;
			// Kvadratrot demper store fulltekstpoeng fra lange biter med mange gjentakelser.
			$ft_n = ( $fmax > 0 ) ? sqrt( $fts[ $i ] / $fmax ) : 0.0;

			$candidates[ $i ]['score'] = $w_sem * $sem_n + ( 1.0 - $w_sem ) * $ft_n;
		}

		usort(
			$candidates,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] < $b['score'] ) ? 1 : -1;
			}
		);
		return $candidates;
	}

	/**
	 * @param string $query     Spørsmål.
	 * @param array  $documents Kandidater.
	 * @param int    $top_n     Antall.
	 * @return array
	 */
	public function rerank( $query, $documents, $top_n ) {
		if ( ! $this->is_active() || empty( $documents ) ) {
			return array_slice( $documents, 0, $top_n );
		}
		$texts = array();
		foreach ( $documents as $doc ) {
			$texts[] = isset( $doc['content'] ) ? (string) $doc['content'] : '';
		}
		$order = ( 'cohere' === $this->provider )
			? $this->rerank_cohere( $query, $texts, $top_n )
			: $this->rerank_voyage( $query, $texts, $top_n );

		if ( is_wp_error( $order ) || empty( $order ) ) {
			return array_slice( $documents, 0, $top_n );
		}
		$out = array();
		foreach ( $order as $entry ) {
			$idx = $entry['index'];
			if ( isset( $documents[ $idx ] ) ) {
				$doc                 = $documents[ $idx ];
				$doc['rerank_score'] = $entry['score'];
				$out[]               = $doc;
			}
		}
		return array_slice( $out, 0, $top_n );
	}

	/**
	 * @param string $query Spørsmål.
	 * @param array  $texts Tekster.
	 * @param int    $top_n Antall.
	 * @return array|WP_Error
	 */
	protected function rerank_voyage( $query, $texts, $top_n ) {
		$body = array( 'query' => $query, 'documents' => $texts, 'model' => $this->model, 'top_k' => $top_n );
		$data = $this->request( self::VOYAGE_URL, array( 'Authorization' => 'Bearer ' . $this->api_key ), $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$out = array();
		if ( ! empty( $data['data'] ) && is_array( $data['data'] ) ) {
			foreach ( $data['data'] as $r ) {
				$out[] = array(
					'index' => isset( $r['index'] ) ? (int) $r['index'] : 0,
					'score' => isset( $r['relevance_score'] ) ? (float) $r['relevance_score'] : 0.0,
				);
			}
		}
		return $out;
	}

	/**
	 * @param string $query Spørsmål.
	 * @param array  $texts Tekster.
	 * @param int    $top_n Antall.
	 * @return array|WP_Error
	 */
	protected function rerank_cohere( $query, $texts, $top_n ) {
		$body = array( 'query' => $query, 'documents' => $texts, 'model' => $this->model, 'top_n' => $top_n );
		$data = $this->request( self::COHERE_URL, array( 'Authorization' => 'Bearer ' . $this->api_key ), $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$out = array();
		if ( ! empty( $data['results'] ) && is_array( $data['results'] ) ) {
			foreach ( $data['results'] as $r ) {
				$out[] = array(
					'index' => isset( $r['index'] ) ? (int) $r['index'] : 0,
					'score' => isset( $r['relevance_score'] ) ? (float) $r['relevance_score'] : 0.0,
				);
			}
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
		$response = wp_remote_post( $url, array( 'timeout' => 45, 'headers' => $headers, 'body' => wp_json_encode( $body ) ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $data['message'] ) ? $data['message'] : __( 'Feil fra rerank-API.', 'bumblebee' );
			return new WP_Error( 'bbee_rerank_http', $msg, array( 'status' => $code ) );
		}
		return is_array( $data ) ? $data : new WP_Error( 'bbee_rerank_json', __( 'Kunne ikke tolke rerank-svar.', 'bumblebee' ) );
	}
}
