<?php
/**
 * Klient mot OpenAI (Chat Completions). Samme grensesnitt som BBEE_Claude_Client.
 *
 * Brukes når ingen Anthropic-nøkkel er satt, men BUMBLEBEE_OPENAI_KEY finnes.
 * Modellen velges i denne rekkefølgen:
 *   1. konstanten BBEE_OPENAI_CHAT_MODEL i wp-config.php (overstyrer alt),
 *   2. innstillingen «openai_chat_model» i bbee_settings,
 *   3. DEFAULT_MODEL (gpt-4.1-mini).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_OpenAI_Client
 */
class BBEE_OpenAI_Client {

	const API_URL       = 'https://api.openai.com/v1/chat/completions';
	const DEFAULT_MODEL = 'gpt-4.1-mini';

	/** Lav temperatur gir jevnere svar fra gang til gang. */
	const DEFAULT_TEMPERATURE = 0.2;

	/** @var string */
	protected $api_key;
	/** @var string */
	protected $model;
	/** @var int */
	protected $max_tokens;
	/** @var float|null */
	protected $temperature;

	/**
	 * @param string     $api_key     Nøkkel.
	 * @param string     $model       Modell.
	 * @param int        $max_tokens  Maks tokens i svaret.
	 * @param float|null $temperature Temperatur (null = API-ets standard).
	 */
	public function __construct( $api_key, $model = '', $max_tokens = 1024, $temperature = self::DEFAULT_TEMPERATURE ) {
		$this->api_key     = trim( (string) $api_key );
		$this->model       = self::clean_model( $model );
		$this->max_tokens  = max( 256, (int) $max_tokens );
		$this->temperature = ( null === $temperature || '' === $temperature ) ? null : max( 0.0, min( 2.0, (float) $temperature ) );
	}

	/**
	 * Lager en klient fra wp-config-konstanten for nøkkelen, eller null hvis nøkkelen mangler.
	 *
	 * @param int        $max_tokens  Maks tokens i svaret.
	 * @param string     $model       Modell fra innstillingene (openai_chat_model). Tom = standard.
	 * @param float|null $temperature Temperatur fra innstillingene.
	 * @return BBEE_OpenAI_Client|null
	 */
	public static function from_constants( $max_tokens = 1024, $model = '', $temperature = self::DEFAULT_TEMPERATURE ) {
		if ( ! defined( 'BUMBLEBEE_OPENAI_KEY' ) || '' === trim( (string) BUMBLEBEE_OPENAI_KEY ) ) {
			return null;
		}
		// Konstanten i wp-config.php vinner over innstillingen.
		if ( defined( 'BBEE_OPENAI_CHAT_MODEL' ) && '' !== trim( (string) BBEE_OPENAI_CHAT_MODEL ) ) {
			$model = (string) BBEE_OPENAI_CHAT_MODEL;
		}
		return new self( BUMBLEBEE_OPENAI_KEY, $model, $max_tokens, $temperature );
	}

	/**
	 * Godta bare enkle modellnavn (bokstaver, tall, punktum, bindestrek, understrek).
	 *
	 * @param string $model Modell.
	 * @return string
	 */
	protected static function clean_model( $model ) {
		$model = trim( (string) $model );
		if ( '' === $model || ! preg_match( '/^[A-Za-z0-9._:-]{1,64}$/', $model ) ) {
			return self::DEFAULT_MODEL;
		}
		return $model;
	}

	/**
	 * @return string Modellen klienten bruker.
	 */
	public function model() {
		return $this->model;
	}

	/**
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->api_key;
	}

	/**
	 * @param string $system   Systemprompt.
	 * @param array  $messages Meldinger (role user/assistant, content).
	 * @return array|WP_Error Samme form som BBEE_Claude_Client::chat().
	 */
	public function chat( $system, $messages ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'bbee_no_api_key', __( 'Ingen API-nøkkel er satt for Bumblebee.', 'bumblebee' ) );
		}

		$body = array(
			'model'                 => $this->model,
			'max_completion_tokens' => $this->max_tokens,
			'messages'              => $this->build_messages( $system, $messages ),
		);
		// Resonneringsmodeller (o-serien, gpt-5) godtar ikke temperatur.
		if ( null !== $this->temperature && ! preg_match( '/^(o\d|gpt-5)/i', $this->model ) ) {
			$body['temperature'] = $this->temperature;
		}

		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = __( 'Feil fra OpenAI API.', 'bumblebee' );
			if ( isset( $data['error']['message'] ) ) {
				$msg = $data['error']['message'];
			}
			return new WP_Error( 'bbee_api_error', $msg, array( 'status' => $code ) );
		}

		$text = isset( $data['choices'][0]['message']['content'] ) ? (string) $data['choices'][0]['message']['content'] : '';

		return array(
			'text'  => trim( $text ),
			'usage' => isset( $data['usage'] ) ? $data['usage'] : array(),
			'model' => isset( $data['model'] ) ? $data['model'] : $this->model,
		);
	}

	/**
	 * @param string $system   Systemprompt.
	 * @param array  $messages Meldinger.
	 * @return array
	 */
	protected function build_messages( $system, $messages ) {
		$out = array();
		if ( '' !== trim( (string) $system ) ) {
			$out[] = array( 'role' => 'system', 'content' => (string) $system );
		}
		foreach ( (array) $messages as $m ) {
			if ( empty( $m['role'] ) || ! isset( $m['content'] ) ) {
				continue;
			}
			$role  = ( 'assistant' === $m['role'] ) ? 'assistant' : 'user';
			$out[] = array( 'role' => $role, 'content' => (string) $m['content'] );
		}
		return $out;
	}
}
