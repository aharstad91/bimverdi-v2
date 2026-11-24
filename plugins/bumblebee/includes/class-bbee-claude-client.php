<?php
/**
 * Klient mot Anthropic Claude (Messages API).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Claude_Client
 */
class BBEE_Claude_Client {

	const API_URL           = 'https://api.anthropic.com/v1/messages';
	const ANTHROPIC_VERSION = '2023-06-01';

	/** @var string */
	protected $api_key;
	/** @var string */
	protected $model;
	/** @var int */
	protected $max_tokens;

	/** @var int Sekunder før kallet gir opp. */
	protected $timeout = 60;

	/**
	 * Sett en egen tidsfrist, for eksempel for korte hjelpekall.
	 *
	 * @param int $seconds Sekunder.
	 * @return $this
	 */
	public function set_timeout( $seconds ) {
		$this->timeout = max( 1, (int) $seconds );
		return $this;
	}

	/**
	 * @param string $api_key    Nøkkel.
	 * @param string $model      Modell.
	 * @param int    $max_tokens Maks tokens.
	 */
	public function __construct( $api_key, $model = 'claude-sonnet-4-5', $max_tokens = 1024 ) {
		$this->api_key    = trim( (string) $api_key );
		$this->model      = $model ? $model : 'claude-sonnet-4-5';
		$this->max_tokens = max( 256, (int) $max_tokens );
	}

	/**
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->api_key;
	}

	/**
	 * @param string $system   Systemprompt.
	 * @param array  $messages Meldinger.
	 * @return array|WP_Error
	 */
	public function chat( $system, $messages ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'bbee_no_api_key', __( 'Ingen API-nøkkel er satt for Bumblebee.', 'bumblebee' ) );
		}

		$body = array(
			'model'      => $this->model,
			'max_tokens' => $this->max_tokens,
			'system'     => (string) $system,
			'messages'   => $this->sanitize_messages( $messages ),
		);

		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => $this->timeout,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $this->api_key,
					'anthropic-version' => self::ANTHROPIC_VERSION,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = __( 'Feil fra Anthropic API.', 'bumblebee' );
			if ( isset( $data['error']['message'] ) ) {
				$msg = $data['error']['message'];
			}
			return new WP_Error( 'bbee_api_error', $msg, array( 'status' => $code ) );
		}

		return array(
			'text'  => $this->extract_text( $data ),
			'usage' => isset( $data['usage'] ) ? $data['usage'] : array(),
			'model' => isset( $data['model'] ) ? $data['model'] : $this->model,
		);
	}

	/**
	 * @param array $data Respons.
	 * @return string
	 */
	protected function extract_text( $data ) {
		if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
			return '';
		}
		$parts = array();
		foreach ( $data['content'] as $block ) {
			if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
				$parts[] = $block['text'];
			}
		}
		return trim( implode( "\n", $parts ) );
	}

	/**
	 * @param array $messages Meldinger.
	 * @return array
	 */
	protected function sanitize_messages( $messages ) {
		$out = array();
		foreach ( (array) $messages as $m ) {
			if ( empty( $m['role'] ) || ! isset( $m['content'] ) ) {
				continue;
			}
			$role  = ( 'assistant' === $m['role'] ) ? 'assistant' : 'user';
			$out[] = array( 'role' => $role, 'content' => (string) $m['content'] );
		}
		if ( empty( $out ) || 'user' !== $out[0]['role'] ) {
			array_unshift( $out, array( 'role' => 'user', 'content' => '...' ) );
		}
		return $out;
	}
}
