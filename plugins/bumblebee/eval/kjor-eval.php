<?php
/**
 * Kjører et spørsmålssett gjennom chat-ruten og lagrer svarene.
 *
 * Bruk:
 *   wp eval-file wp-content/plugins/bumblebee/eval/kjor-eval.php <sporsmalssett.json> <utfil.json> [gjentakelser]
 *
 * Hvert spørsmål går gjennom POST /bbee/v1/chat som testbrukeren «Claude AI».
 * Ingen historikk. Ett chat-kall per spørsmål.
 * Retrieveren lager i tillegg én embedding av spørsmålet (hybrid søk).
 * Skriptet teller alle kall til OpenAI og lagrer tokenbruken.
 *
 * Gjentakelser (valgfritt, standard 1): Hele settet kjøres så mange ganger.
 * Svarene fra modellen varierer, så flere kjøringer viser hvor stabilt et resultat er.
 *
 * Format på utfilen:
 * - Med 1 kjøring: en liste med én rad per spørsmål (samme format som før).
 * - Med flere kjøringer: et objekt med «gjentakelser» og «kjoringer».
 *   Hver kjøring har «kjoring» (nummer) og «svar» (lista med rader).
 *
 * Felt per rad:
 * - id, sporsmal, svar
 * - kilder: kildene brukeren ser (bare siterte), med n, title og url
 * - alle_kilder: alle kildene modellen fikk, med n, cited, title, url og post_id
 * - no_coverage: true når ruten meldte «ingen dekning»
 * - ms: svartid for hele kallet i millisekunder
 * - openai_kall: hvert kall til OpenAI med endepunkt, status, modell og tokenbruk
 * - feil: bare når ruten svarte med feilstatus
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "Kjør skriptet med wp eval-file.\n";
	return;
}

$bbee_args = isset( $args ) ? (array) $args : array();
if ( count( $bbee_args ) < 2 ) {
	WP_CLI::error( 'Bruk: wp eval-file kjor-eval.php <sporsmalssett.json> <utfil.json> [gjentakelser]' );
}

$bbee_inn          = $bbee_args[0];
$bbee_ut           = $bbee_args[1];
$bbee_gjentakelser = 1;
if ( isset( $bbee_args[2] ) ) {
	if ( ! ctype_digit( (string) $bbee_args[2] ) || (int) $bbee_args[2] < 1 ) {
		WP_CLI::error( 'Gjentakelser må være et heltall fra 1 og opp.' );
	}
	$bbee_gjentakelser = (int) $bbee_args[2];
}

if ( ! is_readable( $bbee_inn ) ) {
	WP_CLI::error( "Finner ikke spørsmålssettet: $bbee_inn" );
}

$bbee_sett = json_decode( file_get_contents( $bbee_inn ), true );
if ( ! is_array( $bbee_sett ) || empty( $bbee_sett ) ) {
	WP_CLI::error( 'Spørsmålssettet er tomt eller ikke gyldig JSON.' );
}

$bbee_bruker = get_user_by( 'login', 'Claude AI' );
if ( ! $bbee_bruker ) {
	WP_CLI::error( 'Finner ikke testbrukeren «Claude AI».' );
}
wp_set_current_user( $bbee_bruker->ID );

// Tell kall til OpenAI og samle tokenbruk per spørsmål.
$GLOBALS['bbee_eval_kall'] = array();
add_action(
	'http_api_debug',
	function ( $response, $context, $class, $parsed_args, $url ) {
		if ( false === strpos( (string) $url, 'api.openai.com' ) ) {
			return;
		}
		$kall = array(
			'endepunkt' => (string) wp_parse_url( $url, PHP_URL_PATH ),
			'status'    => is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response ),
		);
		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $data['usage'] ) && is_array( $data['usage'] ) ) {
				$kall['usage'] = $data['usage'];
			}
			if ( isset( $data['model'] ) ) {
				$kall['modell'] = $data['model'];
			}
		}
		$GLOBALS['bbee_eval_kall'][] = $kall;
	},
	10,
	5
);

/**
 * Anslår kostnaden i USD for en liste med OpenAI-kall.
 * Prisene er listepriser per million tokens og bare et anslag til budsjettloggen.
 *
 * @param array $kall_liste Kall slik http_api_debug-kroken lagrer dem.
 * @return array Tokens og anslått kostnad.
 */
function bbee_eval_kostnad( $kall_liste ) {
	$sum = array(
		'chat_inn'    => 0,
		'chat_ut'     => 0,
		'embed'       => 0,
		'usd_anslag'  => 0.0,
		'antall_kall' => count( $kall_liste ),
	);
	foreach ( $kall_liste as $kall ) {
		$u = isset( $kall['usage'] ) ? $kall['usage'] : array();
		if ( false !== strpos( $kall['endepunkt'], 'embeddings' ) ) {
			$sum['embed'] += isset( $u['prompt_tokens'] ) ? (int) $u['prompt_tokens'] : 0;
		} else {
			$sum['chat_inn'] += isset( $u['prompt_tokens'] ) ? (int) $u['prompt_tokens'] : 0;
			$sum['chat_ut']  += isset( $u['completion_tokens'] ) ? (int) $u['completion_tokens'] : 0;
		}
	}
	// gpt-4.1-mini: 0,40 inn og 1,60 ut. text-embedding-3-small: 0,02. Alt per million tokens.
	$sum['usd_anslag'] = round( ( $sum['chat_inn'] * 0.40 + $sum['chat_ut'] * 1.60 + $sum['embed'] * 0.02 ) / 1000000, 5 );
	return $sum;
}

$bbee_kjoringer    = array();
$bbee_feil_paa_rad = 0;
$bbee_antall       = count( $bbee_sett );
$bbee_alle_kall    = array();

/**
 * Skriver utfilen. Én kjøring gir det gamle listeformatet, flere gir et objekt.
 *
 * @param string $fil        Utfilen.
 * @param array  $kjoringer  Alle kjøringer så langt.
 * @param int    $antall     Ønsket antall gjentakelser.
 */
function bbee_eval_lagre( $fil, $kjoringer, $antall ) {
	if ( 1 === $antall ) {
		$data = isset( $kjoringer[0]['svar'] ) ? $kjoringer[0]['svar'] : array();
	} else {
		$data = array(
			'gjentakelser' => $antall,
			'kjoringer'    => $kjoringer,
		);
	}
	file_put_contents( $fil, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

for ( $bbee_k = 1; $bbee_k <= $bbee_gjentakelser; $bbee_k++ ) {
	$bbee_kjoringer[] = array(
		'kjoring' => $bbee_k,
		'svar'    => array(),
	);
	$bbee_ki          = count( $bbee_kjoringer ) - 1;

	if ( $bbee_gjentakelser > 1 ) {
		WP_CLI::log( sprintf( '--- Kjøring %d av %d ---', $bbee_k, $bbee_gjentakelser ) );
	}

	foreach ( $bbee_sett as $bbee_nr => $bbee_q ) {
		$bbee_id  = isset( $bbee_q['id'] ) ? $bbee_q['id'] : ( $bbee_nr + 1 );
		$bbee_spm = isset( $bbee_q['sporsmal'] ) ? (string) $bbee_q['sporsmal'] : '';
		if ( '' === trim( $bbee_spm ) ) {
			continue;
		}

		$GLOBALS['bbee_eval_kall'] = array();

		$bbee_req = new WP_REST_Request( 'POST', '/bbee/v1/chat' );
		$bbee_req->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$bbee_req->set_param( 'message', $bbee_spm );

		$bbee_start = microtime( true );
		$bbee_res   = rest_do_request( $bbee_req );
		$bbee_ms    = (int) round( ( microtime( true ) - $bbee_start ) * 1000 );

		$bbee_data   = $bbee_res->get_data();
		$bbee_status = $bbee_res->get_status();
		$bbee_rad    = array(
			'id'          => $bbee_id,
			'sporsmal'    => $bbee_spm,
			'svar'        => '',
			'kilder'      => array(),
			'alle_kilder' => array(),
			'no_coverage' => null,
			'ms'          => $bbee_ms,
		);

		if ( $bbee_status >= 400 ) {
			$bbee_rad['feil'] = array(
				'status'  => $bbee_status,
				'melding' => is_array( $bbee_data ) && isset( $bbee_data['message'] ) ? $bbee_data['message'] : '',
			);
			$bbee_feil_paa_rad++;
		} else {
			$bbee_feil_paa_rad       = 0;
			$bbee_rad['svar']        = isset( $bbee_data['answer'] ) ? (string) $bbee_data['answer'] : '';
			$bbee_rad['no_coverage'] = ! empty( $bbee_data['no_coverage'] );
			if ( ! empty( $bbee_data['sources'] ) && is_array( $bbee_data['sources'] ) ) {
				foreach ( $bbee_data['sources'] as $bbee_kilde ) {
					$bbee_rad['kilder'][] = array(
						'n'     => isset( $bbee_kilde['n'] ) ? $bbee_kilde['n'] : null,
						'title' => isset( $bbee_kilde['title'] ) ? $bbee_kilde['title'] : '',
						'url'   => isset( $bbee_kilde['url'] ) ? $bbee_kilde['url'] : '',
					);
				}
			}
			// Alle kildene modellen fikk, også de den ikke siterte.
			if ( ! empty( $bbee_data['all_sources'] ) && is_array( $bbee_data['all_sources'] ) ) {
				foreach ( $bbee_data['all_sources'] as $bbee_kilde ) {
					$bbee_rad['alle_kilder'][] = array(
						'n'       => isset( $bbee_kilde['n'] ) ? $bbee_kilde['n'] : null,
						'cited'   => ! empty( $bbee_kilde['cited'] ),
						'title'   => isset( $bbee_kilde['title'] ) ? $bbee_kilde['title'] : '',
						'url'     => isset( $bbee_kilde['url'] ) ? $bbee_kilde['url'] : '',
						'post_id' => isset( $bbee_kilde['post_id'] ) ? $bbee_kilde['post_id'] : null,
					);
				}
			}
		}

		$bbee_rad['openai_kall'] = $GLOBALS['bbee_eval_kall'];
		$bbee_alle_kall          = array_merge( $bbee_alle_kall, $GLOBALS['bbee_eval_kall'] );

		$bbee_kjoringer[ $bbee_ki ]['svar'][] = $bbee_rad;

		WP_CLI::log(
			sprintf(
				'[%d/%d] id %s – %d ms – %d siterte av %d kilder – %s – %d OpenAI-kall',
				$bbee_nr + 1,
				$bbee_antall,
				$bbee_id,
				$bbee_ms,
				count( $bbee_rad['kilder'] ),
				count( $bbee_rad['alle_kilder'] ),
				$bbee_rad['no_coverage'] ? 'ingen dekning' : 'dekning',
				count( $bbee_rad['openai_kall'] )
			)
		);

		// Lagre underveis, så et avbrudd ikke koster en ny kjøring.
		bbee_eval_lagre( $bbee_ut, $bbee_kjoringer, $bbee_gjentakelser );

		// Stopp ved tre feil på rad (f.eks. kvote eller nøkkel), så vi ikke bruker penger på ingenting.
		if ( $bbee_feil_paa_rad >= 3 ) {
			WP_CLI::error( 'Tre feil på rad. Kjøringen stopper. Se ' . $bbee_ut );
		}
	}
}

$bbee_kost = bbee_eval_kostnad( $bbee_alle_kall );
WP_CLI::log(
	sprintf(
		'OpenAI: %d kall, %d chat-tokens inn, %d ut, %d embedding-tokens. Anslått kostnad: %.4f USD.',
		$bbee_kost['antall_kall'],
		$bbee_kost['chat_inn'],
		$bbee_kost['chat_ut'],
		$bbee_kost['embed'],
		$bbee_kost['usd_anslag']
	)
);
WP_CLI::success( sprintf( '%d kjøring(er) à %d spørsmål lagret i %s', $bbee_gjentakelser, $bbee_antall, $bbee_ut ) );
