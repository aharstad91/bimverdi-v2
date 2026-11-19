<?php
/**
 * Admin-innstillinger, indeks-/crawl-/temagruppe-verktøy og automatisering.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Admin
 */
class BBEE_Admin {

	const MENU_SLUG = 'bbee-settings';

	/** @var BBEE_Indexer */
	protected $indexer;
	/** @var BBEE_Crawler */
	protected $crawler;

	/**
	 * @param BBEE_Indexer $indexer Indekserer.
	 * @param BBEE_Crawler $crawler Crawler.
	 */
	public function __construct( $indexer, $crawler = null ) {
		$this->indexer = $indexer;
		$this->crawler = $crawler;
	}

	/**
	 * Koble opp hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_bbee_reindex', array( $this, 'handle_reindex' ) );
		add_action( 'admin_post_bbee_embed', array( $this, 'handle_embed' ) );
		add_action( 'admin_post_bbee_add_url', array( $this, 'handle_add_url' ) );
		add_action( 'admin_post_bbee_crawl', array( $this, 'handle_crawl' ) );
		add_action( 'admin_post_bbee_remove_url', array( $this, 'handle_remove_url' ) );
		add_action( 'admin_post_bbee_themes', array( $this, 'handle_themes' ) );
		add_action( 'admin_post_bbee_clear_log', array( $this, 'handle_clear_log' ) );
		add_filter( 'plugin_action_links_' . BBEE_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * @param array $links Lenker.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'options-general.php?page=' . self::MENU_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Innstillinger', 'bumblebee' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Legg til menypunkter.
	 */
	public function add_menu() {
		add_options_page( __( 'BV Bumblebee', 'bumblebee' ), __( 'BV Bumblebee', 'bumblebee' ), 'manage_options', self::MENU_SLUG, array( $this, 'render_page' ) );
		add_options_page( __( 'BV Bumblebee – Temagrupper', 'bumblebee' ), __( 'BV Bumblebee – Temagrupper', 'bumblebee' ), 'manage_options', 'bbee-themes', array( $this, 'render_themes_page' ) );
	}

	/**
	 * Registrer innstillinger.
	 */
	public function register_settings() {
		register_setting( 'bbee_settings_group', BBEE_OPTION_KEY, array( 'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_settings' ) ) );
	}

	/**
	 * @param array $input Rå input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		// Rå verdier fra databasen: nøkler satt i wp-config.php skal aldri kopieres inn i databasen.
		$current = BBEE_Plugin::get_settings( true );
		$out     = $current;

		if ( BBEE_Plugin::key_from_constant( 'api_key' ) ) {
			$out['api_key'] = '';
		} elseif ( isset( $input['api_key'] ) ) {
			$out['api_key'] = trim( sanitize_text_field( $input['api_key'] ) );
		}
		if ( isset( $input['model'] ) ) {
			$out['model'] = sanitize_text_field( $input['model'] );
		}
		if ( isset( $input['assistant_name'] ) ) {
			$out['assistant_name'] = sanitize_text_field( $input['assistant_name'] );
		}
		if ( isset( $input['welcome_message'] ) ) {
			$out['welcome_message'] = sanitize_textarea_field( $input['welcome_message'] );
		}
		if ( isset( $input['system_prompt'] ) ) {
			$out['system_prompt'] = sanitize_textarea_field( $input['system_prompt'] );
		}
		if ( isset( $input['max_sources'] ) ) {
			$out['max_sources'] = min( 20, max( 1, (int) $input['max_sources'] ) );
		}
		if ( isset( $input['max_tokens'] ) ) {
			$out['max_tokens'] = min( 4096, max( 256, (int) $input['max_tokens'] ) );
		}

		$valid_types = get_post_types( array( 'public' => true ), 'names' );
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$types = array();
			foreach ( $input['post_types'] as $t ) {
				$t = sanitize_key( $t );
				if ( isset( $valid_types[ $t ] ) ) {
					$types[] = $t;
				}
			}
			$out['post_types'] = ! empty( $types ) ? $types : array( 'post', 'page' );
		}

		if ( isset( $input['retriever'] ) ) {
			$mode             = sanitize_key( $input['retriever'] );
			$out['retriever'] = in_array( $mode, array( 'fulltext', 'semantic', 'hybrid' ), true ) ? $mode : 'hybrid';
		}

		if ( isset( $input['embed_provider'] ) ) {
			$p                     = sanitize_key( $input['embed_provider'] );
			$out['embed_provider'] = in_array( $p, array( 'voyage', 'openai' ), true ) ? $p : 'voyage';
		}
		if ( BBEE_Plugin::key_from_constant( 'embed_api_key' ) ) {
			$out['embed_api_key'] = '';
		} elseif ( isset( $input['embed_api_key'] ) ) {
			$out['embed_api_key'] = trim( sanitize_text_field( $input['embed_api_key'] ) );
		}
		if ( isset( $input['embed_model'] ) ) {
			$out['embed_model'] = sanitize_text_field( $input['embed_model'] );
		}

		$out['rerank_enabled'] = ! empty( $input['rerank_enabled'] );
		if ( isset( $input['rerank_provider'] ) ) {
			$p                      = sanitize_key( $input['rerank_provider'] );
			$out['rerank_provider'] = in_array( $p, array( 'voyage', 'cohere' ), true ) ? $p : 'voyage';
		}
		if ( BBEE_Plugin::key_from_constant( 'rerank_api_key' ) ) {
			$out['rerank_api_key'] = '';
		} elseif ( isset( $input['rerank_api_key'] ) ) {
			$out['rerank_api_key'] = trim( sanitize_text_field( $input['rerank_api_key'] ) );
		}
		if ( isset( $input['rerank_model'] ) ) {
			$out['rerank_model'] = sanitize_text_field( $input['rerank_model'] );
		}

		$out['crawl_respect_robots'] = ! empty( $input['crawl_respect_robots'] );
		$out['crawl_follow_pdf']     = ! empty( $input['crawl_follow_pdf'] );
		if ( isset( $input['crawl_delay_ms'] ) ) {
			$out['crawl_delay_ms'] = min( 5000, max( 0, (int) $input['crawl_delay_ms'] ) );
		}
		if ( isset( $input['crawl_user_agent'] ) ) {
			$out['crawl_user_agent'] = sanitize_text_field( $input['crawl_user_agent'] );
		}

		$out['index_media_pdf'] = ! empty( $input['index_media_pdf'] );
		if ( isset( $input['index_meta_fields'] ) ) {
			$out['index_meta_fields'] = sanitize_textarea_field( $input['index_meta_fields'] );
		}

		$out['show_theme_on_sources'] = ! empty( $input['show_theme_on_sources'] );
		if ( isset( $input['theme_group_post_type'] ) ) {
			$pt        = sanitize_key( $input['theme_group_post_type'] );
			$valid_all = get_post_types( array(), 'names' );
			$out['theme_group_post_type'] = isset( $valid_all[ $pt ] ) ? $pt : 'theme_group';
		}
		if ( isset( $input['theme_secondary_threshold'] ) ) {
			$out['theme_secondary_threshold'] = max( 0, min( 1, (float) $input['theme_secondary_threshold'] ) );
		}

		// Automatisering.
		$out['nightly_reindex'] = ! empty( $input['nightly_reindex'] );
		$out['nightly_recrawl'] = ! empty( $input['nightly_recrawl'] );
		// Cron-planen synkroniseres ved neste sidelast i BBEE_Plugin::init()
		// (å kalle update_option her ville utløst sanitize på nytt = rekursjon).

		// Logg og sperrede domener.
		$out['log_enabled'] = ! empty( $input['log_enabled'] );
		if ( isset( $input['blocked_domains'] ) ) {
			$out['blocked_domains'] = sanitize_textarea_field( $input['blocked_domains'] );
		}
		if ( isset( $input['allowed_users'] ) ) {
			$out['allowed_users'] = sanitize_textarea_field( $input['allowed_users'] );
		}

		// Popup-boble.
		$out['popup_enabled']         = ! empty( $input['popup_enabled'] );
		$out['popup_show_logged_out'] = ! empty( $input['popup_show_logged_out'] );
		if ( isset( $input['popup_position'] ) ) {
			$pos                   = sanitize_text_field( $input['popup_position'] );
			$valid_pos             = array( 'top-right', 'top-left', 'bottom-right', 'bottom-left' );
			$out['popup_position'] = in_array( $pos, $valid_pos, true ) ? $pos : 'top-right';
		}

		return $out;
	}

	/**
	 * Håndter «reindekser nå».
	 */
	public function handle_reindex() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_reindex' );
		$stats    = $this->indexer->reindex_all();
		$redirect = add_query_arg(
			array( 'page' => self::MENU_SLUG, 'bbee_indexed' => 1, 'posts' => (int) $stats['posts'], 'chunks' => (int) $stats['chunks'] ),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Håndter «generer embeddings».
	 */
	public function handle_embed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_embed' );

		$embed     = new BBEE_Embeddings();
		$done      = 0;
		$error     = '';
		$remaining = 0;

		if ( ! $embed->is_configured() ) {
			$error = 'not_configured';
		} else {
			$start = time();
			do {
				$res = $this->indexer->embed_pending_batch( 64 );
				if ( ! empty( $res['error'] ) ) {
					$error = $res['error'];
					break;
				}
				$done     += (int) $res['done'];
				$remaining = (int) $res['remaining'];
			} while ( $remaining > 0 && ( time() - $start ) < 20 );

			if ( $remaining > 0 ) {
				$this->indexer->schedule_embedding();
			}
		}

		$redirect = add_query_arg(
			array( 'page' => self::MENU_SLUG, 'bbee_embedded' => 1, 'done' => $done, 'remaining' => $remaining, 'embed_error' => rawurlencode( $error ) ),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Legg til en ny URL-kilde.
	 */
	public function handle_add_url() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_add_url' );

		$data = array(
			'url'         => isset( $_POST['bbee_url'] ) ? wp_unslash( $_POST['bbee_url'] ) : '', // phpcs:ignore
			'depth'       => isset( $_POST['bbee_depth'] ) ? (int) $_POST['bbee_depth'] : 1,
			'same_domain' => ! empty( $_POST['bbee_same_domain'] ),
			'path_prefix' => isset( $_POST['bbee_path_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['bbee_path_prefix'] ) ) : '',
			'max_pages'   => isset( $_POST['bbee_max_pages'] ) ? (int) $_POST['bbee_max_pages'] : 25,
		);

		$result = BBEE_Crawler::add_source( $data );
		$args   = array( 'page' => self::MENU_SLUG );
		if ( is_wp_error( $result ) ) {
			$args['bbee_url_error'] = rawurlencode( $result->get_error_message() );
		} else {
			if ( $this->crawler ) {
				$this->crawler->start( $result );
			}
			$args['bbee_url_added'] = 1;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Start (re)crawl av en kilde.
	 */
	public function handle_crawl() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_crawl' );
		$id = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
		if ( $id && $this->crawler ) {
			$this->crawler->start( $id );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'bbee_crawl_started' => 1 ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Fjern en URL-kilde.
	 */
	public function handle_remove_url() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_remove_url' );
		$id = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
		if ( $id ) {
			BBEE_Crawler::remove_source( $id, $this->indexer );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'bbee_url_removed' => 1 ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Skriv ut et nøkkelfelt. Er nøkkelen satt som konstant i wp-config.php,
	 * vises den aldri (hverken i HTML eller i databasen).
	 *
	 * @param string $key         Innstillingsnøkkel (api_key, embed_api_key, rerank_api_key).
	 * @param string $id          Felt-ID (valgfri).
	 * @param array  $settings    Gjeldende innstillinger.
	 * @param string $placeholder Plassholder (valgfri).
	 */
	protected function render_key_input( $key, $id, $settings, $placeholder = '' ) {
		$consts = BBEE_Plugin::key_constants();
		if ( BBEE_Plugin::key_from_constant( $key ) ) {
			printf(
				'<input type="password" class="regular-text" disabled value="" placeholder="%s" /> <span class="description">%s <code>%s</code></span>',
				esc_attr__( '•••••• satt i wp-config.php', 'bumblebee' ),
				esc_html__( 'Nøkkelen hentes fra', 'bumblebee' ),
				esc_html( $consts[ $key ] )
			);
			return;
		}
		printf(
			'<input name="%1$s[%2$s]" %3$s type="password" class="regular-text" autocomplete="off" %4$s value="%5$s" />',
			esc_attr( BBEE_OPTION_KEY ),
			esc_attr( $key ),
			'' !== $id ? 'id="' . esc_attr( $id ) . '"' : '',
			'' !== $placeholder ? 'placeholder="' . esc_attr( $placeholder ) . '"' : '',
			esc_attr( isset( $settings[ $key ] ) ? $settings[ $key ] : '' )
		);
	}

	/**
	 * Spørsmålslogg i admin: oppsummering og de siste spørsmålene.
	 */
	protected function render_log_section() {
		$log   = array_reverse( BBEE_Log::all(), true );
		$total = count( $log );
		$none  = 0;
		$up    = 0;
		$down  = 0;
		foreach ( $log as $row ) {
			if ( empty( $row['n'] ) ) {
				$none++;
			}
			if ( 'up' === $row['fb'] ) {
				$up++;
			} elseif ( 'down' === $row['fb'] ) {
				$down++;
			}
		}
		$clear_url = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_clear_log' ), 'bbee_clear_log' );
		?>
		<h2 class="title"><?php esc_html_e( 'Spørsmålslogg', 'bumblebee' ); ?></h2>
		<?php if ( ! BBEE_Log::enabled() ) : ?>
			<p class="description"><?php esc_html_e( 'Loggen er slått av (se «Sperrede domener og spørsmålslogg» over).', 'bumblebee' ); ?></p>
		<?php endif; ?>
		<?php if ( 0 === $total ) : ?>
			<p><?php esc_html_e( 'Ingen spørsmål logget ennå.', 'bumblebee' ); ?></p>
		<?php else : ?>
			<p>
				<?php
				printf(
					/* translators: 1: totalt, 2: uten kilder, 3: tommel opp, 4: tommel ned */
					esc_html__( '%1$d spørsmål. %2$d uten dekning i kildene (mulige hull i innholdet). Tilbakemeldinger: %3$d nyttig, %4$d ikke nyttig.', 'bumblebee' ),
					(int) $total,
					(int) $none,
					(int) $up,
					(int) $down
				);
				?>
				<a href="<?php echo esc_url( $clear_url ); ?>" class="button button-secondary" style="margin-left:8px;" onclick="return confirm('<?php echo esc_js( __( 'Tømme hele spørsmålsloggen?', 'bumblebee' ) ); ?>');"><?php esc_html_e( 'Tøm loggen', 'bumblebee' ); ?></a>
			</p>
			<table class="widefat striped" style="max-width:960px;">
				<thead>
					<tr>
						<th style="width:140px;"><?php esc_html_e( 'Tidspunkt', 'bumblebee' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Bruker', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Spørsmål', 'bumblebee' ); ?></th>
						<th style="width:70px;"><?php esc_html_e( 'Kilder', 'bumblebee' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Tilbakemelding', 'bumblebee' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$shown = 0;
					foreach ( $log as $row ) :
						if ( $shown++ >= 50 ) {
							break;
						}
						$fb = '–';
						if ( 'up' === $row['fb'] ) {
							$fb = '👍';
						} elseif ( 'down' === $row['fb'] ) {
							$fb = '👎';
						}
						?>
						<tr>
							<td><?php echo esc_html( wp_date( 'd.m.Y H:i', (int) $row['t'] ) ); ?></td>
							<td><?php echo esc_html( $this->log_user_label( isset( $row['u'] ) ? (int) $row['u'] : 0 ) ); ?></td>
							<td><?php echo esc_html( $row['q'] ); ?></td>
							<td><?php echo empty( $row['n'] ) ? '<strong>0</strong>' : (int) $row['n']; // phpcs:ignore ?></td>
							<td><?php echo esc_html( $fb ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $total > 50 ) : ?>
				<p class="description"><?php esc_html_e( 'Viser de 50 siste.', 'bumblebee' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Visningsnavn for en bruker i spørsmålsloggen.
	 *
	 * @param int $user_id Bruker-ID (0 = ukjent, f.eks. gamle oppføringer).
	 * @return string
	 */
	protected function log_user_label( $user_id ) {
		if ( $user_id <= 0 ) {
			return '–';
		}
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : __( 'Slettet bruker', 'bumblebee' );
	}

	/**
	 * Håndter «tøm loggen».
	 */
	public function handle_clear_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_clear_log' );
		BBEE_Log::clear();
		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'bbee_log_cleared' => 1 ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Bygg temagruppe-profiler og klassifiser.
	 */
	public function handle_themes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Ingen tilgang.', 'bumblebee' ) );
		}
		check_admin_referer( 'bbee_themes' );

		$themes   = new BBEE_Themes();
		$args     = array( 'page' => 'bbee-themes' );
		$profiles = $themes->build_profiles();
		if ( is_wp_error( $profiles ) ) {
			$args['themes_error'] = rawurlencode( $profiles->get_error_message() );
			wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
			exit;
		}
		$result = $themes->classify_all();
		if ( is_wp_error( $result ) ) {
			$args['themes_error'] = rawurlencode( $result->get_error_message() );
		} else {
			$args['themes_done'] = 1;
			$args['groups']      = (int) $profiles['count'];
			$args['resources']   = (int) $result['resources'];
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Oversiktssiden for temagrupper.
	 */
	public function render_themes_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$themes    = new BBEE_Themes();
		$run_url    = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_themes' ), 'bbee_themes' );
		$overview  = $themes->overview();
		$resources = $themes->resources( 300, 0 );
		$last_run   = $themes->last_run();
		$embed     = new BBEE_Embeddings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BV Bumblebee – Temagrupper', 'bumblebee' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Semantisk kobling av alle ressurser mot de 6 temagruppene (hovedkategorier). Primærgruppen vises på kildekortene i chatten.', 'bumblebee' ); ?></p>

			<?php if ( isset( $_GET['themes_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					printf(
						/* translators: 1: grupper, 2: ressurser */
						esc_html__( 'Ferdig: %1$d temagrupper, %2$d ressurser klassifisert.', 'bumblebee' ),
						isset( $_GET['groups'] ) ? (int) $_GET['groups'] : 0, // phpcs:ignore
						isset( $_GET['resources'] ) ? (int) $_GET['resources'] : 0 // phpcs:ignore
					);
					?>
				</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['themes_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( rawurldecode( wp_unslash( $_GET['themes_error'] ) ) ) ); // phpcs:ignore ?></p></div>
			<?php endif; ?>

			<?php if ( ! $embed->is_configured() ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Temagruppe-kobling krever embeddings. Sett opp en embedding-nøkkel under Innstillinger → BV Bumblebee, og generer embeddings først.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>

			<p>
				<a href="<?php echo esc_url( $run_url ); ?>" class="button button-primary"><?php esc_html_e( 'Bygg profiler og klassifiser alle ressurser', 'bumblebee' ); ?></a>
				<?php if ( $last_run ) : ?>
					<span class="description" style="margin-left:8px;"><?php echo esc_html( sprintf( __( 'Sist kjørt: %s', 'bumblebee' ), $last_run ) ); ?></span>
				<?php endif; ?>
			</p>

			<?php if ( ! empty( $overview ) ) : ?>
				<h2><?php esc_html_e( 'Fordeling per primær temagruppe', 'bumblebee' ); ?></h2>
				<table class="widefat striped" style="max-width:500px;">
					<thead><tr><th><?php esc_html_e( 'Temagruppe', 'bumblebee' ); ?></th><th><?php esc_html_e( 'Antall ressurser', 'bumblebee' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $overview as $o ) : ?>
							<tr><td><?php echo esc_html( $o['title'] ); ?></td><td><?php echo (int) $o['count']; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $resources ) ) : ?>
				<h2><?php esc_html_e( 'Ressurser og temagrupper', 'bumblebee' ); ?></h2>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Ressurs', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Type', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Primær', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Score', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Sekundære', 'bumblebee' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $resources as $r ) :
							$groups = json_decode( $r['groups'], true );
							$sec    = array();
							if ( is_array( $groups ) ) {
								foreach ( array_slice( $groups, 1 ) as $g ) {
									$sec[] = $g['title'] . ' (' . number_format_i18n( (float) $g['score'], 2 ) . ')';
								}
							}
							?>
							<tr>
								<td><a href="<?php echo esc_url( $r['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $r['title'] ); ?></a></td>
								<td><code><?php echo esc_html( $r['resource_type'] ); ?></code></td>
								<td><?php echo esc_html( $r['primary_group_title'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (float) $r['primary_score'], 2 ) ); ?></td>
								<td><?php echo esc_html( implode( ', ', $sec ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php elseif ( $embed->is_configured() ) : ?>
				<p><?php esc_html_e( 'Ingen klassifiserte ressurser ennå. Klikk knappen over for å kjøre koblingen.', 'bumblebee' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Hovedinnstillingssiden.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings     = BBEE_Plugin::get_settings();
		$stats        = $this->indexer->stats();
		$public_types = get_post_types( array( 'public' => true ), 'objects' );
		$reindex_url  = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_reindex' ), 'bbee_reindex' );
		$embed_url    = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_embed' ), 'bbee_embed' );
		$next_nightly = wp_next_scheduled( 'bbee_nightly_reindex' );
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'BV Bumblebee', 'bumblebee' ); ?>
				<span style="font-size:13px;font-weight:600;color:#fff;background:#4a9d3f;padding:2px 10px;border-radius:999px;vertical-align:middle;">v<?php echo esc_html( BBEE_VERSION ); ?></span>
			</h1>
			<p class="description" style="margin-top:4px;"><?php esc_html_e( 'Chatten er kun tilgjengelig for innloggede, registrerte brukere.', 'bumblebee' ); ?></p>

			<?php if ( isset( $_GET['bbee_indexed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					printf(
						/* translators: 1: kilder, 2: tekstbiter */
						esc_html__( 'Indeksering fullført: %1$d kilder, %2$d tekstbiter.', 'bumblebee' ),
						isset( $_GET['posts'] ) ? (int) $_GET['posts'] : 0, // phpcs:ignore
						isset( $_GET['chunks'] ) ? (int) $_GET['chunks'] : 0 // phpcs:ignore
					);
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['bbee_embedded'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<?php $embed_error = isset( $_GET['embed_error'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['embed_error'] ) ) ) : ''; // phpcs:ignore ?>
				<?php if ( 'not_configured' === $embed_error ) : ?>
					<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Kan ikke lage embeddings: legg inn en embedding-API-nøkkel først.', 'bumblebee' ); ?></p></div>
				<?php elseif ( '' !== $embed_error ) : ?>
					<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sprintf( __( 'Feil under embedding: %s', 'bumblebee' ), $embed_error ) ); ?></p></div>
				<?php else : ?>
					<div class="notice notice-success is-dismissible"><p>
						<?php
						printf(
							/* translators: 1: ferdige, 2: gjenstår */
							esc_html__( 'Embeddings generert: %1$d ferdige. %2$d gjenstår (kjøres videre i bakgrunnen).', 'bumblebee' ),
							isset( $_GET['done'] ) ? (int) $_GET['done'] : 0, // phpcs:ignore
							isset( $_GET['remaining'] ) ? (int) $_GET['remaining'] : 0 // phpcs:ignore
						);
						?>
					</p></div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( empty( $settings['api_key'] ) ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Legg inn en Anthropic API-nøkkel nedenfor for å ta i bruk chatten.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['bbee_url_added'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Nettside-kilde lagt til. Crawling er startet i bakgrunnen.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['bbee_crawl_started'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Crawling startet. Innholdet oppdateres i bakgrunnen.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['bbee_log_cleared'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Spørsmålsloggen er tømt.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['bbee_url_removed'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Nettside-kilde fjernet.', 'bumblebee' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['bbee_url_error'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( rawurldecode( wp_unslash( $_GET['bbee_url_error'] ) ) ) ); // phpcs:ignore ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'bbee_settings_group' ); ?>

				<h2 class="title"><?php esc_html_e( 'AI-tilkobling (Anthropic Claude)', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_api_key"><?php esc_html_e( 'API-nøkkel', 'bumblebee' ); ?></label></th>
						<td>
							<?php $this->render_key_input( 'api_key', 'bbee_api_key', $settings ); ?>
							<p class="description"><?php esc_html_e( 'Anthropic API-nøkkel (starter med «sk-ant-»). Hentes fra console.anthropic.com. Tryggest er å sette den i wp-config.php:', 'bumblebee' ); ?> <code>define( 'BBEE_ANTHROPIC_KEY', 'sk-ant-…' );</code> <?php esc_html_e( '(overstyrer feltet her; samme for BBEE_EMBED_KEY og BBEE_RERANK_KEY).', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_model"><?php esc_html_e( 'Modell', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[model]" id="bbee_model" type="text" class="regular-text" value="<?php echo esc_attr( $settings['model'] ); ?>" />
							<p class="description"><?php esc_html_e( 'F.eks. claude-sonnet-4-5 (anbefalt) eller claude-haiku-4-5 (raskere/billigere).', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_max_tokens"><?php esc_html_e( 'Maks svarlengde (tokens)', 'bumblebee' ); ?></label></th>
						<td><input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[max_tokens]" id="bbee_max_tokens" type="number" min="256" max="4096" step="64" value="<?php echo esc_attr( $settings['max_tokens'] ); ?>" /></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Kunnskapskilder', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Innholdstyper som indekseres', 'bumblebee' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $public_types as $type ) :
									if ( 'attachment' === $type->name ) {
										continue;
									}
									$checked = in_array( $type->name, (array) $settings['post_types'], true );
									?>
									<label style="display:inline-block;margin:0 16px 6px 0;">
										<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[post_types][]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( $checked ); ?> />
										<?php echo esc_html( $type->labels->name ); ?> <code><?php echo esc_html( $type->name ); ?></code>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Publisert innhold i valgte typer indekseres automatisk når det lagres.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PDF-er i mediebiblioteket', 'bumblebee' ); ?></th>
						<td>
							<?php $pdf_ok = class_exists( 'BBEE_Pdf' ) && BBEE_Pdf::available(); ?>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[index_media_pdf]" value="1" <?php checked( ! empty( $settings['index_media_pdf'] ) ); ?> <?php disabled( ! $pdf_ok ); ?> />
								<?php esc_html_e( 'Les og indekser tekst fra PDF-er som er lastet opp i mediebiblioteket', 'bumblebee' ); ?>
							</label>
							<?php if ( ! $pdf_ok ) : ?>
								<p class="description" style="color:#a12622;"><?php esc_html_e( 'PDF-tolkning er ikke tilgjengelig på serveren (mangler zlib/iconv). Kontakt vertsleverandøren.', 'bumblebee' ); ?></p>
							<?php else : ?>
								<p class="description"><?php esc_html_e( 'Kjør «Reindekser alt innhold nå» etter at du har huket av, for å lese eksisterende PDF-er. Tekst-PDF-er fungerer best; skannede PDF-er (bilder) gir lite tekst.', 'bumblebee' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_meta_fields"><?php esc_html_e( 'Egendefinerte felt som indekseres', 'bumblebee' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[index_meta_fields]" id="bbee_meta_fields" rows="3" class="large-text code" placeholder="foretak: beskrivelse, org_nr"><?php echo esc_textarea( $settings['index_meta_fields'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Én linje per innholdstype: «type: felt1, felt2». Brukes når informasjonen ligger i egne felt og ikke i hovedteksten. Tittel og kategorier (f.eks. bransje og temagrupper) indekseres alltid. Kjør «Reindekser alt innhold nå» etter endring.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_max_sources"><?php esc_html_e( 'Maks kildeutdrag per svar', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[max_sources]" id="bbee_max_sources" type="number" min="1" max="20" value="<?php echo esc_attr( $settings['max_sources'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Anbefalt 5–6. Flere gir mer kontekst, men også mer støy.', 'bumblebee' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Søk og gjenfinning', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_retriever"><?php esc_html_e( 'Søkemetode', 'bumblebee' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[retriever]" id="bbee_retriever">
								<option value="fulltext" <?php selected( $settings['retriever'], 'fulltext' ); ?>><?php esc_html_e( 'Fulltekst (nøkkelord) – ingen ekstra nøkkel', 'bumblebee' ); ?></option>
								<option value="semantic" <?php selected( $settings['retriever'], 'semantic' ); ?>><?php esc_html_e( 'Semantisk (embeddings)', 'bumblebee' ); ?></option>
								<option value="hybrid" <?php selected( $settings['retriever'], 'hybrid' ); ?>><?php esc_html_e( 'Hybrid (semantisk + fulltekst) – anbefalt', 'bumblebee' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Semantisk og hybrid krever embedding-nøkkel og genererte embeddings. Uten dette faller søket trygt tilbake til fulltekst.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_embed_provider"><?php esc_html_e( 'Embedding-leverandør', 'bumblebee' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[embed_provider]" id="bbee_embed_provider">
								<option value="voyage" <?php selected( $settings['embed_provider'], 'voyage' ); ?>>Voyage AI</option>
								<option value="openai" <?php selected( $settings['embed_provider'], 'openai' ); ?>>OpenAI</option>
							</select>
							<p class="description"><?php esc_html_e( 'Voyage er Anthropics anbefalte embedding-partner. Krever egen API-nøkkel fra valgt leverandør.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_embed_api_key"><?php esc_html_e( 'Embedding-API-nøkkel', 'bumblebee' ); ?></label></th>
						<td><?php $this->render_key_input( 'embed_api_key', 'bbee_embed_api_key', $settings ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_embed_model"><?php esc_html_e( 'Embedding-modell', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[embed_model]" id="bbee_embed_model" type="text" class="regular-text" value="<?php echo esc_attr( $settings['embed_model'] ); ?>" />
							<p class="description"><?php esc_html_e( 'F.eks. voyage-3.5 (Voyage) eller text-embedding-3-small (OpenAI). Bytter du modell, må embeddings genereres på nytt.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Reranking', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[rerank_enabled]" value="1" <?php checked( ! empty( $settings['rerank_enabled'] ) ); ?> />
								<?php esc_html_e( 'Slå på reranking (sorterer kandidatene på nytt for høyere presisjon)', 'bumblebee' ); ?>
							</label>
							<p style="margin-top:8px;">
								<select name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[rerank_provider]">
									<option value="voyage" <?php selected( $settings['rerank_provider'], 'voyage' ); ?>>Voyage AI</option>
									<option value="cohere" <?php selected( $settings['rerank_provider'], 'cohere' ); ?>>Cohere</option>
								</select>
								<?php $this->render_key_input( 'rerank_api_key', '', $settings, __( 'Rerank-API-nøkkel', 'bumblebee' ) ); ?>
								<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[rerank_model]" type="text" class="regular-text" placeholder="<?php esc_attr_e( 'Modell, f.eks. rerank-2.5', 'bumblebee' ); ?>" value="<?php echo esc_attr( $settings['rerank_model'] ); ?>" />
							</p>
							<p class="description"><?php esc_html_e( 'Valgfritt. Gir best effekt sammen med hybrid søk og en høyere «maks kildeutdrag».', 'bumblebee' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Temagrupper (hovedkategorier)', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_theme_pt"><?php esc_html_e( 'Innholdstype for temagrupper', 'bumblebee' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[theme_group_post_type]" id="bbee_theme_pt">
								<?php foreach ( $public_types as $type ) :
									if ( 'attachment' === $type->name ) {
										continue;
									}
									?>
									<option value="<?php echo esc_attr( $type->name ); ?>" <?php selected( $settings['theme_group_post_type'], $type->name ); ?>><?php echo esc_html( $type->labels->name ); ?> (<?php echo esc_html( $type->name ); ?>)</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Innholdstypen som inneholder de 6 temagruppene (standard: theme_group). Beskrivelsen deres brukes til å bygge den semantiske profilen.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_theme_thr"><?php esc_html_e( 'Terskel for sekundære grupper', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[theme_secondary_threshold]" id="bbee_theme_thr" type="number" min="0" max="1" step="0.05" value="<?php echo esc_attr( $settings['theme_secondary_threshold'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Cosine-likhet (0–1). Primærgruppen tildeles alltid; andre grupper over terskelen blir sekundære. 0,4 er et fornuftig utgangspunkt.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Vis på kildekort', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[show_theme_on_sources]" value="1" <?php checked( ! empty( $settings['show_theme_on_sources'] ) ); ?> />
								<?php esc_html_e( 'Vis primær temagruppe som merke på kildekortene i chatten', 'bumblebee' ); ?>
							</label>
							<p class="description">
								<?php
								printf(
									/* translators: %s: lenke */
									wp_kses_post( __( 'Kjør selve koblingen og se fordelingen på <a href="%s">oversiktssiden for temagrupper</a>.', 'bumblebee' ) ),
									esc_url( admin_url( 'options-general.php?page=bbee-themes' ) )
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Automatisering', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Nattlig reindeksering', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[nightly_reindex]" value="1" <?php checked( ! empty( $settings['nightly_reindex'] ) ); ?> />
								<?php esc_html_e( 'Reindekser basen automatisk hver natt kl 02 (nettstedets tidssone)', 'bumblebee' ); ?>
							</label>
							<?php if ( $next_nightly ) : ?>
								<p class="description"><?php echo esc_html( sprintf( __( 'Neste planlagte kjøring: %s', 'bumblebee' ), get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_nightly ), 'Y-m-d H:i' ) ) ); ?></p>
							<?php endif; ?>
							<p class="description"><?php esc_html_e( 'Merk: WP-Cron fyrer av på trafikk. Har siden lite trafikk kl 02, kjøres jobben ved første besøk etter kl 02. For presis kjøring, sett opp en ekte systemcron som kaller wp-cron.php (se readme).', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Nattlig crawl', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[nightly_recrawl]" value="1" <?php checked( ! empty( $settings['nightly_recrawl'] ) ); ?> />
								<?php esc_html_e( 'Crawl også nettsider-kildene på nytt hver natt (av som standard – vær skånsom mot eksterne servere)', 'bumblebee' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Nettsider (crawl) – globale innstillinger', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Respekter robots.txt', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[crawl_respect_robots]" value="1" <?php checked( ! empty( $settings['crawl_respect_robots'] ) ); ?> />
								<?php esc_html_e( 'Følg reglene i nettstedenes robots.txt (anbefalt)', 'bumblebee' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_crawl_delay"><?php esc_html_e( 'Pause mellom sider (ms)', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[crawl_delay_ms]" id="bbee_crawl_delay" type="number" min="0" max="5000" step="100" value="<?php echo esc_attr( $settings['crawl_delay_ms'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Vær skånsom mot eksterne servere. 500 ms er et fornuftig utgangspunkt.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_crawl_ua"><?php esc_html_e( 'User-agent (valgfri)', 'bumblebee' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[crawl_user_agent]" id="bbee_crawl_ua" type="text" class="regular-text" value="<?php echo esc_attr( $settings['crawl_user_agent'] ); ?>" placeholder="BumblebeeBot/1.0" />
							<p class="description"><?php esc_html_e( 'Tom = standard «BumblebeeBot» med nettstedets adresse.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'PDF-lenker', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[crawl_follow_pdf]" value="1" <?php checked( ! empty( $settings['crawl_follow_pdf'] ) ); ?> />
								<?php esc_html_e( 'Følg og les PDF-lenker crawleren finner (innenfor samme dybde/begrensning)', 'bumblebee' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Visning – flytende chat-boble (popup)', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Flytende boble', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[popup_enabled]" value="1" <?php checked( ! empty( $settings['popup_enabled'] ) ); ?> />
								<?php esc_html_e( 'Vis en flytende chat-boble i hjørnet på hele nettstedet (du trenger da ikke kortkoden)', 'bumblebee' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_popup_pos"><?php esc_html_e( 'Plassering', 'bumblebee' ); ?></label></th>
						<td>
							<select name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[popup_position]" id="bbee_popup_pos">
								<?php $pos = $settings['popup_position'] ?? 'top-right'; ?>
								<option value="top-right" <?php selected( $pos, 'top-right' ); ?>><?php esc_html_e( 'Oppe til høyre', 'bumblebee' ); ?></option>
								<option value="top-left" <?php selected( $pos, 'top-left' ); ?>><?php esc_html_e( 'Oppe til venstre', 'bumblebee' ); ?></option>
								<option value="bottom-right" <?php selected( $pos, 'bottom-right' ); ?>><?php esc_html_e( 'Nederst til høyre', 'bumblebee' ); ?></option>
								<option value="bottom-left" <?php selected( $pos, 'bottom-left' ); ?>><?php esc_html_e( 'Nederst til venstre', 'bumblebee' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Utloggede besøkende', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[popup_show_logged_out]" value="1" <?php checked( ! empty( $settings['popup_show_logged_out'] ) ); ?> />
								<?php esc_html_e( 'Vis boblen også til utloggede (de får en «Logg inn»-boks når de åpner den)', 'bumblebee' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Av som standard: boblen vises kun til innloggede brukere, i tråd med at chatten er tilgangsbegrenset.', 'bumblebee' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Sperrede domener og spørsmålslogg', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_blocked_domains"><?php esc_html_e( 'Sperrede domener', 'bumblebee' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[blocked_domains]" id="bbee_blocked_domains" rows="3" class="large-text code" placeholder="example.org"><?php echo esc_textarea( $settings['blocked_domains'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Ett domene per linje. Crawleren henter aldri innhold herfra, og svar bruker aldri tekst derfra. Disse er alltid sperret, fordi lisensierte standarder ikke kan gjengis uten avtale:', 'bumblebee' ); ?>
								<code>standard.no, iso.org, cen.eu, cenelec.eu, iec.ch</code>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_allowed_users"><?php esc_html_e( 'Hvem får bruke chatten', 'bumblebee' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[allowed_users]" id="bbee_allowed_users" rows="3" class="large-text code" placeholder="brukernavn eller e-post"><?php echo esc_textarea( isset( $settings['allowed_users'] ) ? $settings['allowed_users'] : '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Ett brukernavn eller én e-post per linje. Hvis feltet er tomt, får alle innloggede bruke chatten. Administratorer har alltid tilgang.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Spørsmålslogg', 'bumblebee' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[log_enabled]" value="1" <?php checked( ! empty( $settings['log_enabled'] ) ); ?> />
								<?php esc_html_e( 'Logg spørsmål og tilbakemeldinger (tommel opp/ned), med hvem som spurte', 'bumblebee' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Lagrer spørsmålsteksten, tidspunkt, hvem som spurte (innlogget bruker), om det ble funnet kilder og tilbakemelding. Ingen IP. Loggen vises bare for administratorer. Maks 500 oppføringer. Spørsmålsteksten sendes til Anthropic og embedding-leverandøren: si fra om det i personvernerklæringen.', 'bumblebee' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Tekst og oppførsel', 'bumblebee' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_assistant_name"><?php esc_html_e( 'Navn på assistenten', 'bumblebee' ); ?></label></th>
						<td><input name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[assistant_name]" id="bbee_assistant_name" type="text" class="regular-text" value="<?php echo esc_attr( $settings['assistant_name'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_welcome"><?php esc_html_e( 'Velkomstmelding', 'bumblebee' ); ?></label></th>
						<td><textarea name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[welcome_message]" id="bbee_welcome" rows="2" class="large-text"><?php echo esc_textarea( $settings['welcome_message'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_system"><?php esc_html_e( 'Systemprompt', 'bumblebee' ); ?></label></th>
						<td>
							<textarea name="<?php echo esc_attr( BBEE_OPTION_KEY ); ?>[system_prompt]" id="bbee_system" rows="8" class="large-text code"><?php echo esc_textarea( $settings['system_prompt'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Instruksjonen som styrer hvordan assistenten svarer, inkludert kravet om kildehenvisning.', 'bumblebee' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Lagre innstillinger', 'bumblebee' ) ); ?>
			</form>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Indeks', 'bumblebee' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: 1: kilder, 2: tekstbiter */
					esc_html__( 'Indeksert nå: %1$d kilder / %2$d tekstbiter.', 'bumblebee' ),
					(int) $stats['posts'],
					(int) $stats['chunks']
				);
				?>
				<?php if ( ! empty( $stats['url_chunks'] ) ) : ?>
					<br /><?php printf( esc_html__( 'Herav fra crawlede nettsider: %d tekstbiter.', 'bumblebee' ), (int) $stats['url_chunks'] ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $stats['pdf_chunks'] ) ) : ?>
					<br /><?php printf( esc_html__( 'Herav fra PDF-er: %d tekstbiter.', 'bumblebee' ), (int) $stats['pdf_chunks'] ); ?>
				<?php endif; ?>
				<br /><?php printf( esc_html__( 'Embeddings klare: %1$d av %2$d tekstbiter.', 'bumblebee' ), (int) $stats['embedded'], (int) $stats['chunks'] ); ?>
				<?php if ( empty( $stats['embed_ready'] ) ) : ?>
					<em>(<?php esc_html_e( 'ingen embedding-nøkkel satt', 'bumblebee' ); ?>)</em>
				<?php endif; ?>
				<?php if ( ! empty( $stats['last_indexed'] ) ) : ?>
					<br /><?php echo esc_html( sprintf( __( 'Sist full indeksering: %s', 'bumblebee' ), $stats['last_indexed'] ) ); ?>
				<?php endif; ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $reindex_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Reindekser alt innhold nå', 'bumblebee' ); ?></a>
				<?php if ( ! empty( $stats['embed_ready'] ) ) : ?>
					<a href="<?php echo esc_url( $embed_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Generer / oppdater embeddings', 'bumblebee' ); ?></a>
				<?php endif; ?>
			</p>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Nettsider som kunnskapskilder', 'bumblebee' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Legg til en URL, så henter Bumblebee teksten og indekserer den. Velg hvor mange nivå med lenker som skal følges: dybde 1 = bare denne siden, dybde 2 = siden + direkte underlenker, dybde 3 = ett nivå til.', 'bumblebee' ); ?></p>

			<?php $sources = class_exists( 'BBEE_Crawler' ) ? BBEE_Crawler::get_sources() : array(); ?>
			<?php if ( ! empty( $sources ) ) : ?>
				<table class="widefat striped" style="max-width:900px;margin-bottom:16px;">
					<thead><tr>
						<th><?php esc_html_e( 'URL', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Dybde', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Begrensning', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Sider / biter', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Status', 'bumblebee' ); ?></th>
						<th><?php esc_html_e( 'Handling', 'bumblebee' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( $sources as $src ) :
							$crawl_url  = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_crawl&source=' . rawurlencode( $src['id'] ) ), 'bbee_crawl' );
							$remove_url = wp_nonce_url( admin_url( 'admin-post.php?action=bbee_remove_url&source=' . rawurlencode( $src['id'] ) ), 'bbee_remove_url' );
							$limit_txt  = ! empty( $src['same_domain'] ) ? __( 'samme domene', 'bumblebee' ) : __( 'alle domener', 'bumblebee' );
							if ( ! empty( $src['path_prefix'] ) ) {
								$limit_txt .= ' · ' . $src['path_prefix'];
							}
							?>
							<tr>
								<td><a href="<?php echo esc_url( $src['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $src['url'] ); ?></a></td>
								<td><?php echo (int) $src['depth']; ?></td>
								<td><?php echo esc_html( $limit_txt ); ?></td>
								<td><?php echo (int) $src['pages']; ?> / <?php echo (int) $src['chunks']; ?></td>
								<td><?php echo esc_html( $src['status'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $crawl_url ); ?>" class="button button-small"><?php esc_html_e( 'Crawl på nytt', 'bumblebee' ); ?></a>
									<a href="<?php echo esc_url( $remove_url ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Fjerne denne kilden og alt innhold fra den?', 'bumblebee' ) ); ?>');"><?php esc_html_e( 'Fjern', 'bumblebee' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:900px;background:#fff;border:1px solid #dcdcde;padding:12px 16px;border-radius:6px;">
				<input type="hidden" name="action" value="bbee_add_url" />
				<?php wp_nonce_field( 'bbee_add_url' ); ?>
				<h3 style="margin-top:4px;"><?php esc_html_e( 'Legg til nettside', 'bumblebee' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bbee_url"><?php esc_html_e( 'URL', 'bumblebee' ); ?></label></th>
						<td><input name="bbee_url" id="bbee_url" type="url" class="regular-text" placeholder="https://www.eksempel.no/tema/side" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_depth"><?php esc_html_e( 'Dybde', 'bumblebee' ); ?></label></th>
						<td>
							<select name="bbee_depth" id="bbee_depth">
								<option value="1"><?php esc_html_e( '1 – bare denne siden', 'bumblebee' ); ?></option>
								<option value="2"><?php esc_html_e( '2 – siden + direkte underlenker', 'bumblebee' ); ?></option>
								<option value="3"><?php esc_html_e( '3 – ett nivå til', 'bumblebee' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Begrensninger', 'bumblebee' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="bbee_same_domain" value="1" checked /> <?php esc_html_e( 'Følg kun lenker på samme domene (anbefalt)', 'bumblebee' ); ?>
							</label>
							<label>
								<?php esc_html_e( 'Kun stier som starter med:', 'bumblebee' ); ?>
								<input type="text" name="bbee_path_prefix" class="regular-text" placeholder="/tema/bygg-kart-og-eiendom/" />
							</label>
							<p class="description"><?php esc_html_e( 'La sti-feltet stå tomt for å tillate hele domenet. Sti-begrensning hindrer at crawleren «rømmer» ut i hele nettstedet.', 'bumblebee' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bbee_max_pages"><?php esc_html_e( 'Maks antall sider', 'bumblebee' ); ?></label></th>
						<td><input name="bbee_max_pages" id="bbee_max_pages" type="number" min="1" max="200" value="25" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Legg til og crawl', 'bumblebee' ), 'primary', 'submit', false ); ?>
			</form>

			<hr />

			<?php $this->render_log_section(); ?>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Slik viser du chatten', 'bumblebee' ); ?></h2>
			<p><?php esc_html_e( 'Legg inn denne kortkoden på en side eller i et innlegg:', 'bumblebee' ); ?></p>
			<p><code>[bumblebee]</code></p>
			<p><?php esc_html_e( 'Valgfrie parametere:', 'bumblebee' ); ?> <code>[bumblebee height="560" placeholder="Still et spørsmål …"]</code></p>
			<p class="description"><?php esc_html_e( 'Eller slå på den flytende chat-boblen under «Visning» over – da vises chatten i hjørnet på hele nettstedet uten kortkode.', 'bumblebee' ); ?></p>
		</div>
		<?php
	}
}
