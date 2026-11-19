<?php
/**
 * REST API for chatten.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Rest
 */
class BBEE_Rest {

	const NAMESPACE = 'bbee/v1';

	/** @var BBEE_Retriever */
	protected $retriever;

	/**
	 * @param BBEE_Retriever $retriever Gjenfinner.
	 */
	public function __construct( $retriever ) {
		$this->retriever = $retriever;
	}

	/**
	 * Koble opp hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registrer ruter.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'message' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field' ),
					'history' => array( 'required' => false, 'type' => 'array' ),
					'nonce'   => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/feedback',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_feedback' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'log_id' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
					'value'  => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
					'nonce'  => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);
	}

	/**
	 * Tommel opp/ned på et svar.
	 *
	 * @param WP_REST_Request $request Forespørsel.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_feedback( $request ) {
		$ok = BBEE_Log::feedback( (string) $request->get_param( 'log_id' ), (string) $request->get_param( 'value' ) );
		if ( ! $ok ) {
			return new WP_Error( 'bbee_bad_feedback', __( 'Kunne ikke registrere tilbakemeldingen.', 'bumblebee' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Kun innloggede (registrerte) brukere, med gyldig nonce.
	 *
	 * @param WP_REST_Request $request Forespørsel.
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'bbee_login_required', __( 'Du må være innlogget for å bruke Bumblebee.', 'bumblebee' ), array( 'status' => 401 ) );
		}

		$allowed = apply_filters( 'bbee_user_can_chat', BBEE_Plugin::user_can_chat(), $request );
		if ( ! $allowed ) {
			return new WP_Error( 'bbee_forbidden', __( 'Kontoen din har ikke tilgang til Bumblebee.', 'bumblebee' ), array( 'status' => 403 ) );
		}

		$nonce = $request->get_param( 'nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_header( 'X-WP-Nonce' );
		}
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'bbee_bad_nonce', __( 'Ugyldig eller manglende sikkerhets-token. Last siden på nytt.', 'bumblebee' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * @param WP_REST_Request $request Forespørsel.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_chat( $request ) {
		$settings = BBEE_Plugin::get_settings();
		$message  = trim( (string) $request->get_param( 'message' ) );

		if ( '' === $message ) {
			return new WP_Error( 'bbee_empty', __( 'Spørsmålet er tomt.', 'bumblebee' ), array( 'status' => 400 ) );
		}

		$client = new BBEE_Claude_Client( $settings['api_key'], $settings['model'], $settings['max_tokens'] );
		if ( ! $client->is_configured() ) {
			// Bumblebee: bruk OpenAI når ingen Anthropic-nøkkel er satt.
			$openai = BBEE_OpenAI_Client::from_constants(
				$settings['max_tokens'],
				isset( $settings['openai_chat_model'] ) ? (string) $settings['openai_chat_model'] : '',
				isset( $settings['openai_temperature'] ) ? $settings['openai_temperature'] : BBEE_OpenAI_Client::DEFAULT_TEMPERATURE
			);
			if ( $openai ) {
				$client = $openai;
			}
		}
		if ( ! $client->is_configured() ) {
			return new WP_Error( 'bbee_not_configured', __( 'Bumblebee er ikke ferdig satt opp ennå (mangler API-nøkkel).', 'bumblebee' ), array( 'status' => 503 ) );
		}

		$max_sources = isset( $settings['max_sources'] ) ? (int) $settings['max_sources'] : 6;
		$n_chunks    = max( $max_sources, (int) $settings['context_chunks'] );
		$hits        = $this->retriever->search( $message, $n_chunks );
		$neighbors   = $this->retriever->neighbors( $hits, (int) $settings['context_neighbors'] );

		list( $context, $sources ) = $this->build_context( $hits, $neighbors, (int) $settings['context_max_chars'] );

		if ( empty( $sources ) ) {
			return rest_ensure_response(
				array(
					'answer'      => __( 'Jeg fant ikke dekning for dette spørsmålet i kunnskapskildene på nettstedet. Prøv gjerne å omformulere, eller still et mer spesifikt spørsmål.', 'bumblebee' ),
					'sources'     => array(),
					'no_coverage' => true,
					'log_id'      => BBEE_Log::add( $message, 0 ),
				)
			);
		}

		$system     = (string) $settings['system_prompt'];
		$history    = $this->sanitize_history( $request->get_param( 'history' ) );
		$messages   = $history;
		$messages[] = array( 'role' => 'user', 'content' => $this->build_user_prompt( $message, $context ) );

		$result = $client->chat( $system, $messages );
		if ( is_wp_error( $result ) ) {
			$status = 502;
			$data   = $result->get_error_data();
			if ( is_array( $data ) && isset( $data['status'] ) ) {
				$status = (int) $data['status'];
			}
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => $status ) );
		}

		$answer                 = $this->normalize_plain_citations( $result['text'], count( $sources ) );
		list( $answer, $cited ) = $this->resolve_citations( $answer, count( $sources ) );
		$answer                 = $this->tidy_answer( $answer, ! empty( $cited ) );

		// Opprydningen kan ha fjernet en løs henvisning uten tekst foran. Tell bare det som står igjen.
		preg_match_all( '/\[(\d+)\]/u', $answer, $left );
		$cited = array_intersect_key( $cited, array_flip( array_map( 'intval', $left[1] ) ) );

		foreach ( $sources as $i => $src ) {
			$sources[ $i ]['cited'] = isset( $cited[ $src['n'] ] );
		}

		// Ingen dekning bare når svaret ikke har noen gyldige henvisninger. Et svar som åpner med
		// «fant ikke dekning», men likevel bygger på kildene, beholder kildene (runde 2, spørsmål 1).
		$no_coverage = empty( $cited );
		if ( $no_coverage ) {
			// Kildene vises ikke, så henvisningene i teksten fjernes også.
			$answer = trim( preg_replace( '/[ \t]+([\.,;:])/u', '$1', preg_replace( '/\s*\[\d+\]/u', '', $answer ) ) );
			$cited  = array();
			foreach ( $sources as $i => $src ) {
				$sources[ $i ]['cited'] = false;
			}
		}

		// Brukeren ser bare kildene svaret viser til. Numrene er de samme som i teksten.
		$shown = array_values(
			array_filter(
				$sources,
				function ( $src ) {
					return ! empty( $src['cited'] );
				}
			)
		);

		return rest_ensure_response(
			array(
				'answer'      => $answer,
				'sources'     => $shown,
				'all_sources' => $sources,
				'no_coverage' => $no_coverage,
				'log_id'      => BBEE_Log::add( $message, count( $shown ) ),
			)
		);
	}

	/**
	 * Sier første setning at modellen ikke fant dekning? «Delvis» teller ikke.
	 *
	 * @param string $answer Svar.
	 * @return bool
	 */
	protected function opens_with_no_coverage( $answer ) {
		$first = preg_split( '/(?<=[\.\!\?])\s+/u', trim( (string) $answer ), 2 );
		$first = isset( $first[0] ) ? $first[0] : '';
		$pattern = (string) apply_filters( 'bbee_no_coverage_pattern', '/fant ikke (noen )?dekning|ingen dekning/iu' );
		return (bool) preg_match( $pattern, $first ) && ! preg_match( '/delvis/iu', $first );
	}

	/**
	 * Rydd i henvisningene i svaret og finn hvilke kilder som er brukt.
	 *
	 * - «[1, 3]» og «[1–3]» blir «[1][3]» og «[1][2][3]», så alle grensesnitt forstår dem.
	 * - Numre som ikke finnes i kildelisten, fjernes fra teksten.
	 *
	 * @param string $text  Svar fra modellen.
	 * @param int    $count Antall kilder i listen.
	 * @return array{0:string,1:array} Svartekst og sett med brukte numre (n => true).
	 */
	protected function resolve_citations( $text, $count ) {
		$cited = array();
		$text  = preg_replace_callback(
			'/\[\s*(\d+(?:\s*(?:,|–|-|og)\s*\d+)*)\s*\]/u',
			function ( $m ) use ( $count, &$cited ) {
				$nums  = array();
				$parts = preg_split( '/\s*(,|og)\s*/u', $m[1] );
				foreach ( $parts as $part ) {
					if ( preg_match( '/^(\d+)\s*[–-]\s*(\d+)$/u', $part, $r ) ) {
						$a = (int) $r[1];
						$b = (int) $r[2];
						if ( $b >= $a && $b - $a <= 10 ) {
							$nums = array_merge( $nums, range( $a, $b ) );
						}
					} else {
						$nums[] = (int) $part;
					}
				}
				$out = '';
				foreach ( array_unique( $nums ) as $n ) {
					if ( $n >= 1 && $n <= $count ) {
						$cited[ $n ] = true;
						$out        .= '[' . $n . ']';
					}
				}
				return $out;
			},
			(string) $text
		);
		// Fjern doble mellomrom foran tegnsetting etter fjernede henvisninger.
		$text = preg_replace( '/[ \t]+([\.,;:])/u', '$1', $text );
		return array( trim( $text ), $cited );
	}

	/**
	 * Bygg kildeutdragene til modellen.
	 *
	 * Én nummerert oppføring per unik URL. Alle biter fra samme URL står under
	 * samme nummer, i rekkefølge på siden. Nabobiter slås sammen, og overlappen
	 * mellom dem fjernes. Nummeret er det samme som i kildelisten som returneres.
	 *
	 * @param array $hits      Treff i rangert rekkefølge.
	 * @param array $neighbors Nabobiter (id => rad).
	 * @param int   $max_chars Tak på tegn i utdragene.
	 * @return array{0:string,1:array} Kontekst og kildeliste.
	 */
	protected function build_context( $hits, $neighbors = array(), $max_chars = 30000 ) {
		$settings  = BBEE_Plugin::get_settings();
		$themes    = ( ! empty( $settings['show_theme_on_sources'] ) && class_exists( 'BBEE_Themes' ) ) ? new BBEE_Themes() : null;
		$max_chars = max( 2000, (int) $max_chars );

		// 1) Velg biter innenfor taket: først treffene, så naboene til de beste treffene.
		$groups = array(); // key => array( 'first' => rad, 'chunks' => chunk_index => tekst ).
		$used   = 0;
		$add    = function ( $row ) use ( &$groups, &$used, $max_chars ) {
			if ( ! empty( $row['url'] ) && BBEE_Plugin::is_blocked_url( $row['url'] ) ) {
				return false;
			}
			$text = $this->trim_text( $row['content'], 2500 );
			$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
			if ( $used + $len > $max_chars ) {
				return false;
			}
			$key = '' !== $row['url'] ? $row['url'] : ( 'post-' . $row['post_id'] );
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array( 'first' => $row, 'chunks' => array() );
			}
			$idx = isset( $row['chunk_index'] ) ? (int) $row['chunk_index'] : 0;
			if ( isset( $groups[ $key ]['chunks'][ $idx ] ) ) {
				return true;
			}
			$groups[ $key ]['chunks'][ $idx ] = $text;
			$used                            += $len;
			return true;
		};

		foreach ( $hits as $hit ) {
			$add( $hit );
		}
		if ( ! empty( $neighbors ) ) {
			// Naboer i samme rekkefølge som treffene de hører til.
			foreach ( $hits as $hit ) {
				foreach ( $neighbors as $nb ) {
					if ( (int) $nb['post_id'] === (int) $hit['post_id'] && $nb['url'] === $hit['url']
						&& abs( (int) $nb['chunk_index'] - (int) $hit['chunk_index'] ) <= 2 ) {
						$add( $nb );
					}
				}
			}
		}

		// 2) Nummerer gruppene og sett sammen teksten.
		$context_parts = array();
		$sources       = array();
		$n             = 0;
		foreach ( $groups as $group ) {
			$n++;
			$first  = $group['first'];
			$chunks = $group['chunks'];
			ksort( $chunks );

			$text = '';
			$prev = null;
			foreach ( $chunks as $idx => $chunk ) {
				if ( null === $prev ) {
					$text = $chunk;
				} elseif ( $idx === $prev + 1 ) {
					$text = $this->join_overlap( $text, $chunk );
				} else {
					$text .= "\n[…]\n" . $chunk;
				}
				$prev = $idx;
			}

			$context_parts[] = sprintf( "[%d] Tittel: %s\nURL: %s\nUtdrag:\n%s", $n, $first['title'], $first['url'], $text );

			$source = array(
				'n'       => $n,
				'title'   => $first['title'],
				'url'     => $first['url'],
				'post_id' => $first['post_id'],
			);
			if ( $themes ) {
				$stype = isset( $first['source_type'] ) ? $first['source_type'] : 'post';
				$sid   = isset( $first['source_id'] ) ? $first['source_id'] : (string) $first['post_id'];
				$grp   = $themes->primary_for( $stype, $sid );
				if ( $grp ) {
					$source['group'] = $grp['title'];
				}
			}
			$sources[] = $source;
		}

		return array( implode( "\n\n---\n\n", $context_parts ), $sources );
	}

	/**
	 * Slå sammen to nabobiter og fjern overlappen.
	 * Indekseren lar en bit starte med slutten av forrige bit (ca. 150 tegn).
	 *
	 * @param string $a Første bit.
	 * @param string $b Neste bit.
	 * @return string
	 */
	protected function join_overlap( $a, $b ) {
		$probe_len = 40;
		$blen      = function_exists( 'mb_strlen' ) ? mb_strlen( $b ) : strlen( $b );
		if ( $blen > $probe_len ) {
			$probe = mb_substr( $b, 0, $probe_len );
			$tail  = mb_substr( $a, -400 );
			$pos   = mb_strrpos( $tail, $probe );
			if ( false !== $pos ) {
				$keep = mb_strlen( $a ) - mb_strlen( $tail ) + $pos;
				return mb_substr( $a, 0, $keep ) . $b;
			}
		}
		return $a . "\n" . $b;
	}

	/**
	 * Brukermeldingen til modellen: kildene, spørsmålet og en kort påminnelse om de viktigste reglene.
	 * Hele regelsettet står i systemprompten.
	 *
	 * @param string $message Spørsmål.
	 * @param string $context Kontekst.
	 * @return string
	 */
	protected function build_user_prompt( $message, $context ) {
		return "KILDER (hver kilde har ett nummer; bruk bare disse som grunnlag):\n\n"
			. $context
			. "\n\n=====\n\nSPØRSMÅL: " . $message
			. "\n\nSvar på norsk etter disse reglene:\n"
			. "- Start med 2–4 setninger som svarer direkte: hovedregelen først, så unntak. Ta med de viktigste tallene og navnene fra kilden. "
			. "Legg bare til detaljer som tilfører noe nytt. Vanligvis under 150 ord.\n"
			. "- Sett [n] rett etter hver påstand, inne i setningen eller punktet. Aldri henvisninger alene på en linje, og aldri numre i ren tekst.\n"
			. "- Skriv «krav», «skal» eller «må» bare når kilden er en lov eller forskrift, eller selv sier at noe er et krav. "
			. "Ellers: «anbefaler», «beskriver», «ifølge». Behold modalverbet fra kilden: «bør» er en anbefaling, "
			. "også når spørsmålet sier «skal», og «skal» i en kilde blir «ifølge X skal», ikke «anbefaler».\n"
			. "- Gjengi «må ikke» (forbud) og «trenger ikke» (ingen plikt) presist, slik kilden sier det.\n"
			. "- Ikke avslutt med hva kildene ikke sier. Nevn en mangel bare når den gjelder kjernen i spørsmålet.\n"
			. "- Hvis kildene bare svarer delvis, si det kort i første setning, og svar med det kildene sier.\n"
			. "- Hvis kildene ikke sier noe om temaet, skriv bare «Jeg fant ikke dekning for dette i kunnskapskildene.» uten henvisninger.";
	}

	/**
	 * Gjør henvisninger i ren tekst om til klammer, f.eks. «(3,4,5)» til «[3][4][5]».
	 *
	 * Endrer bare når alle tallene finnes i kildelisten. «(2,5)» kan være et desimaltall,
	 * så to tall med komma uten mellomrom endres bare når «kilde» står foran.
	 *
	 * @param string $text  Svar fra modellen.
	 * @param int    $count Antall kilder i listen.
	 * @return string
	 */
	protected function normalize_plain_citations( $text, $count ) {
		if ( $count < 1 ) {
			return (string) $text;
		}
		return (string) preg_replace_callback(
			'/\s?\(\s*(kilde(?:ne|r)?\s+)?(\d{1,2}(?:\s*(?:,|og|&)\s*\d{1,2})*)\s*\)/iu',
			function ( $m ) use ( $count ) {
				$has_word = '' !== trim( $m[1] );
				$nums     = preg_split( '/\s*(?:,|og|&)\s*/u', $m[2] );
				// Uten «kilde» foran: krev minst to tall (ett tall i parentes er ofte noe annet).
				if ( ! $has_word && count( $nums ) < 2 ) {
					return $m[0];
				}
				// «(2,5)» uten «kilde» kan være et desimaltall.
				if ( ! $has_word && 2 === count( $nums ) && preg_match( '/^\d+,\d+$/u', $m[2] ) ) {
					return $m[0];
				}
				$out = '';
				foreach ( $nums as $n ) {
					$n = (int) $n;
					if ( $n < 1 || $n > $count ) {
						return $m[0];
					}
					$out .= '[' . $n . ']';
				}
				return ' ' . $out;
			},
			(string) $text
		);
	}

	/**
	 * Enkel opprydding i svaret etter at henvisningene er løst opp.
	 *
	 * - Henvisninger alene på en linje flyttes til slutten av forrige linje med tekst.
	 * - Like henvisninger rett etter hverandre slås sammen, og rekkefølgen blir stigende.
	 * - Et svar med henvisninger mister et siste avsnitt som bare gjentar «fant ikke dekning».
	 *
	 * @param string $text      Svar.
	 * @param bool   $has_cites Har svaret gyldige henvisninger?
	 * @return string
	 */
	protected function tidy_answer( $text, $has_cites ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		// Fjern tomme mellomtitler som «Detaljer:», som bare gjør svaret lengre.
		$text  = preg_replace( '/^[ \t]*(?:\*\*)?(?:Detaljer|Flere detaljer|Utdypning)(?:\*\*)?:?(?:\*\*)?[ \t]*$/mu', '', $text );
		$lines = explode( "\n", $text );
		$out   = array();
		foreach ( $lines as $line ) {
			$bare = trim( $line );
			// Linjen består bare av henvisninger (evt. med punktum eller listetegn).
			if ( '' !== $bare && preg_match( '/^(?:[-*•]\s*)?((?:\[\d+\]\s*)+)[\.\s]*$/u', $bare, $m ) ) {
				$cites = preg_replace( '/\s+/u', '', $m[1] );
				// Finn forrige linje med tekst.
				for ( $i = count( $out ) - 1; $i >= 0; $i-- ) {
					if ( '' !== trim( $out[ $i ] ) ) {
						break;
					}
				}
				if ( $i >= 0 ) {
					$prev = rtrim( $out[ $i ] );
					// Sett henvisningen foran avsluttende punktum, så den hører til setningen.
					if ( preg_match( '/^(.*?)((?:\[\d+\])*)([\.\!\?:]?)$/su', $prev, $p ) ) {
						$prev = ( '' === $p[2] ? rtrim( $p[1] ) . ' ' : $p[1] ) . $p[2] . $cites . $p[3];
					}
					$out[ $i ] = $prev;
					// Fjern tomme linjer mellom (de ble lagt til før henvisningslinjen).
					array_splice( $out, $i + 1 );
				}
				// Ingen tekst foran: dropp linjen.
				continue;
			}
			$out[] = $line;
		}
		$text = implode( "\n", $out );

		// Sorter og fjern doble numre i hver sammenhengende rekke av henvisninger.
		$text = preg_replace_callback(
			'/(?:\[\d+\]){2,}/u',
			function ( $m ) {
				preg_match_all( '/\d+/', $m[0], $d );
				$nums = array_unique( array_map( 'intval', $d[0] ) );
				sort( $nums );
				return '[' . implode( '][', $nums ) . ']';
			},
			$text
		);

		// Et siste avsnitt som bare gjentar standardsetningen om manglende dekning, i et svar som har dekning.
		if ( $has_cites ) {
			$paras = preg_split( '/\n\s*\n/u', trim( $text ) );
			if ( count( $paras ) > 1 ) {
				$last = trim( end( $paras ) );
				$words = count( preg_split( '/\s+/u', $last ) );
				if ( ! preg_match( '/\[\d+\]/u', $last ) && $words <= 16
					&& preg_match( '/fant ikke (noen )?dekning|ingen dekning/iu', $last ) ) {
					array_pop( $paras );
					$text = implode( "\n\n", $paras );
				}
			}
		}

		// Mellomrom mellom ord og henvisning: «elementer[2]» blir «elementer [2]».
		$text = preg_replace( '/(?<=[\p{L}\p{N}\)])(\[\d+\])/u', ' $1', $text );

		// Ikke flere enn én tom linje på rad.
		$text = preg_replace( '/\n{3,}/u', "\n\n", $text );
		return trim( $text );
	}

	/**
	 * @param mixed $history Historikk.
	 * @return array
	 */
	protected function sanitize_history( $history ) {
		if ( empty( $history ) || ! is_array( $history ) ) {
			return array();
		}
		$out     = array();
		$history = array_slice( $history, -8 );
		foreach ( $history as $m ) {
			if ( ! is_array( $m ) || empty( $m['role'] ) || ! isset( $m['content'] ) ) {
				continue;
			}
			$role    = ( 'assistant' === $m['role'] ) ? 'assistant' : 'user';
			$content = sanitize_textarea_field( (string) $m['content'] );
			if ( '' === trim( $content ) ) {
				continue;
			}
			$out[] = array( 'role' => $role, 'content' => $content );
		}
		return $out;
	}

	/**
	 * @param string $text Tekst.
	 * @param int    $max  Maks.
	 * @return string
	 */
	protected function trim_text( $text, $max ) {
		$text = trim( (string) $text );
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		if ( $len <= $max ) {
			return $text;
		}
		$cut = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $max ) : substr( $text, 0, $max );
		return rtrim( $cut ) . '…';
	}
}
