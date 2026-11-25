<?php
/**
 * Administrasjon: metabokser, kolonner, filtrering, CSV-eksport og innstillinger.
 *
 * @package BV_Pilot_Prosjekt
 */

defined( 'ABSPATH' ) || exit;

class BVP_Admin {

	const OPTION      = 'bvp_settings';
	const NONCE       = 'bvp_admin_save';
	const EXPORT_ACTION = 'bvp_export_csv';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . BVP_Post_Type::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( __CLASS__, 'export_csv' ) );

		add_filter( 'manage_' . BVP_Post_Type::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . BVP_Post_Type::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . BVP_Post_Type::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_filters_query' ) );
		add_action( 'admin_notices', array( __CLASS__, 'export_button' ) );
		add_action( 'admin_notices', array( __CLASS__, 'avviste_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'eierskap_notice' ) );
		add_action( 'admin_post_bvp_skjul_eierskap', array( __CLASS__, 'skjul_eierskap' ) );
	}

	/**
	 * Innstillinger med standardverdier.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'notify_admin'  => 1,
			'notify_kladd'  => 1,
			'notify_email'  => get_option( 'admin_email' ),
			'send_receipt'  => 1,
			'kontakt_epost' => '',
			'kontakt_url'   => 'https://bimverdi.no/tilbakemelding/',
			'hjelp_url'     => '',
			'skjema_url'    => '',
		);

		return wp_parse_args( (array) get_option( self::OPTION, array() ), $defaults );
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen || BVP_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'bvp-form', BVP_URL . 'assets/css/bvp-form.css', array(), BVP_VERSION );
		wp_enqueue_script( 'bvp-form', BVP_URL . 'assets/js/bvp-form.js', array(), BVP_VERSION, true );
	}

	/* ---------------------------------------------------------------------
	 * Metabokser
	 * ------------------------------------------------------------------ */

	public static function meta_boxes() {
		foreach ( BVP_Fields::sections() as $section ) {
			if ( ! empty( $section['hidden'] ) ) {
				continue;
			}

			add_meta_box(
				'bvp-' . $section['key'],
				$section['title'] . ( 'admin' === $section['audience'] ? ' (kun administrator)' : '' ),
				array( __CLASS__, 'render_meta_box' ),
				BVP_Post_Type::POST_TYPE,
				'normal',
				'admin' === $section['audience'] ? 'default' : 'high',
				array( 'section' => $section )
			);
		}

		add_meta_box(
			'bvp-access',
			'Deling og tilgang',
			array( __CLASS__, 'render_access_box' ),
			BVP_Post_Type::POST_TYPE,
			'normal',
			'high'
		);

		if ( BVP_Fields::hidden() ) {
			add_meta_box(
				'bvp-hidden',
				'Skjulte felt med lagrede svar',
				array( __CLASS__, 'render_hidden_box' ),
				BVP_Post_Type::POST_TYPE,
				'normal',
				'low'
			);
		}

		add_meta_box(
			'bvp-status',
			'Status og innsending',
			array( __CLASS__, 'render_status_box' ),
			BVP_Post_Type::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * @param WP_Post $post
	 * @param array   $box
	 */
	public static function render_meta_box( $post, $box ) {
		static $nonce_printed = false;

		if ( ! $nonce_printed ) {
			wp_nonce_field( self::NONCE, 'bvp_admin_nonce' );
			$nonce_printed = true;
		}

		echo '<div class="bvp bvp-admin" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
		BVP_Renderer::section( $box['args']['section'], $post->ID, array(), array() );
		echo '</div>';
	}

	/**
	 * Delingsvalg i wp-admin.
	 *
	 * @param WP_Post $post
	 */
	public static function render_access_box( $post ) {
		echo '<div class="bvp bvp-admin" data-bvp-tale="' . esc_attr( BVP_Form::talesprak() ) . '">';
		BVP_Access::render_box( $post->ID );
		echo '</div>';
	}

	/**
	 * Svar som ligger lagret på felt som nå er skjult.
	 *
	 * @param WP_Post $post
	 */
	public static function render_hidden_box( $post ) {
		$rader = array();

		foreach ( BVP_Fields::hidden() as $key => $field ) {
			$text = BVP_Renderer::value_to_text( $field, BVP_Repository::get_value( $post->ID, $key ) );

			if ( '' !== trim( (string) $text ) ) {
				$rader[ $field['label'] ] = $text;
			}
		}

		if ( empty( $rader ) ) {
			echo '<p class="description">Ingen lagrede svar på skjulte felt for denne registreringen.</p>';
			return;
		}

		echo '<p class="description">Feltene er skjult i skjemaeditoren. Svarene vises her, men ikke i skjemaet, e-posten eller CSV-eksporten.</p>';
		echo '<table class="widefat striped"><tbody>';

		foreach ( $rader as $label => $text ) {
			printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), esc_html( $text ) );
		}

		echo '</tbody></table>';
	}

	/**
	 * @param WP_Post $post
	 */
	public static function render_status_box( $post ) {
		$innsendt = get_post_meta( $post->ID, '_bvp_innsendt_dato', true );
		$author   = get_userdata( (int) $post->post_author );

		echo '<p><strong>Status:</strong> ' . esc_html( BVP_Post_Type::status_label( $post ) ) . '</p>';
		echo '<p><strong>Registrert av:</strong> ' . esc_html( $author ? $author->display_name : 'ukjent' ) . '</p>';

		if ( $innsendt ) {
			echo '<p><strong>Innsendt:</strong> ' . esc_html( mysql2date( 'j. F Y H:i', $innsendt ) ) . '</p>';
		}

		echo '<p class="description">Kladd = «Utkast», Innsendt = «Publisert». Endre status i Publiser-boksen.</p>';
	}

	/**
	 * Lagre felt fra wp-admin.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['bvp_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvp_admin_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw    = isset( $_POST['bvp'] ) && is_array( $_POST['bvp'] ) ? wp_unslash( $_POST['bvp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$values = BVP_Repository::sanitize( $raw, 'all' );

		foreach ( BVP_Fields::all( 'all' ) as $key => $field ) {
			if ( in_array( $field['type'], array( 'file', 'files' ), true ) ) {
				continue;
			}
			if ( array_key_exists( $key, $values ) ) {
				update_post_meta( $post_id, BVP_Post_Type::meta_key( $key ), $values[ $key ] );
			}
		}

		if ( isset( $_POST['bvp_remove'] ) && is_array( $_POST['bvp_remove'] ) ) {
			foreach ( wp_unslash( $_POST['bvp_remove'] ) as $key => $ids ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				foreach ( (array) $ids as $attachment_id ) {
					BVP_Repository::remove_attachment( $post_id, sanitize_key( $key ), absint( $attachment_id ) );
				}
			}
		}

		BVP_Repository::handle_uploads( $_FILES, $post_id, 'all' );

		// Valg som ikke kunne lagres vises som melding øverst på siden.
		$avviste = BVP_Repository::avviste();

		if ( $avviste ) {
			set_transient( 'bvp_avviste_' . get_current_user_id(), $avviste, HOUR_IN_SECONDS );
		}

		if ( isset( $_POST['bvp_access'] ) && is_array( $_POST['bvp_access'] ) ) {
			BVP_Access::save( $post_id, wp_unslash( $_POST['bvp_access'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( 'publish' === $post->post_status && ! get_post_meta( $post_id, '_bvp_innsendt_dato', true ) ) {
			update_post_meta( $post_id, '_bvp_innsendt_dato', current_time( 'mysql' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Listevisning
	 * ------------------------------------------------------------------ */

	public static function columns( $columns ) {
		$new = array(
			'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'bvp_id'        => 'ID',
			'title'         => 'Tittel',
			'bvp_prosjekt'  => 'Prosjekt',
			'bvp_status'    => 'Status',
			'bvp_foretak'   => 'Foretak',
			'bvp_kontakt'   => 'Kontakt',
			'bvp_tilgang'   => 'Deling',
			'bvp_in'        => 'Til IN?',
			'bvp_innsendt'  => 'Innsendt',
			'date'          => 'Sist endret',
		);

		return $new;
	}

	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'bvp_id':
				echo '<code>#' . (int) $post_id . '</code>';
				break;

			case 'bvp_prosjekt':
				echo esc_html( BVP_Repository::prosjekt_tekst( $post_id ) ?: '–' );
				break;

			case 'bvp_status':
				printf(
					'<span class="bvp-status bvp-status--%1$s">%2$s</span>',
					esc_attr( 'publish' === get_post_status( $post_id ) ? 'innsendt' : 'kladd' ),
					esc_html( BVP_Post_Type::status_label( $post_id ) )
				);
				break;

			case 'bvp_foretak':
				echo esc_html( BVP_Repository::get_value( $post_id, 'foretaksnavn' ) );
				break;

			case 'bvp_kontakt':
				$navn  = trim( BVP_Repository::get_value( $post_id, 'reg_fornavn' ) . ' ' . BVP_Repository::get_value( $post_id, 'reg_etternavn' ) );
				$epost = BVP_Repository::get_value( $post_id, 'reg_epost' );
				echo esc_html( $navn );
				if ( is_email( $epost ) ) {
					printf( '<br><a href="mailto:%1$s">%1$s</a>', esc_attr( $epost ) );
				}
				break;

			case 'bvp_tilgang':
				echo esc_html( BVP_Access::summary( $post_id ) );
				break;

			case 'bvp_in':
				echo esc_html( BVP_Repository::get_value( $post_id, 'send_til_in' ) );
				break;

			case 'bvp_innsendt':
				$innsendt = get_post_meta( $post_id, '_bvp_innsendt_dato', true );
				echo $innsendt ? esc_html( mysql2date( 'j.m.Y', $innsendt ) ) : '&ndash;';
				break;
		}
	}

	public static function sortable_columns( $columns ) {
		$columns['bvp_foretak'] = 'bvp_foretak';
		$columns['bvp_id']      = 'ID';
		return $columns;
	}

	public static function filters() {
		$screen = get_current_screen();

		if ( ! $screen || BVP_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$valgt = isset( $_GET['bvp_filter_in'] ) ? sanitize_text_field( wp_unslash( $_GET['bvp_filter_in'] ) ) : '';

		echo '<select name="bvp_filter_in">';
		echo '<option value="">Alle (til IN?)</option>';
		foreach ( array( 'Ja', 'Ikke enda' ) as $option ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $option ),
				selected( $valgt, $option, false ),
				esc_html( $option )
			);
		}
		echo '</select>';
	}

	public static function apply_filters_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( BVP_Post_Type::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( ! empty( $_GET['bvp_filter_in'] ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => BVP_Post_Type::meta_key( 'send_til_in' ),
						'value' => sanitize_text_field( wp_unslash( $_GET['bvp_filter_in'] ) ),
					),
				)
			);
		}

		if ( 'bvp_foretak' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', BVP_Post_Type::meta_key( 'foretaksnavn' ) );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Liste over registreringer der eierskapet hadde flyttet seg.
	 */
	public static function eierskap_notice() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || BVP_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$rapport = get_option( 'bvp_eierskap_rapport' );

		if ( ! is_array( $rapport ) || ! $rapport ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>Kontroller eierskapet på disse registreringene.</strong> ';
		echo 'En feil før versjon 0.42.0 gjorde at den som sist lagret ble eier. Den som registrerte er nå lagt til som medredaktør, så ingen har mistet tilgangen. ';
		echo 'Er eieren feil, bytt den under «Hvem eier registreringen?».</p><ul style="margin-left:1.5em;list-style:disc">';

		foreach ( array_slice( $rapport, 0, 30 ) as $rad ) {
			printf(
				'<li><a href="%1$s">#%2$d %3$s</a> - registrert av %4$s, eier nå %5$s</li>',
				esc_url( get_edit_post_link( $rad['id'] ) ),
				(int) $rad['id'],
				esc_html( $rad['tittel'] ),
				esc_html( $rad['registrant'] ),
				esc_html( $rad['eier'] )
			);
		}

		echo '</ul>';
		printf(
			'<p><a class="button" href="%s">Ferdig - skjul lista</a></p></div>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bvp_skjul_eierskap' ), 'bvp_skjul_eierskap' ) )
		);
	}

	/** Skjul lista over eierskap. */
	public static function skjul_eierskap() {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'bvp_skjul_eierskap' ) ) {
			wp_die( 'Ingen tilgang.' );
		}

		delete_option( 'bvp_eierskap_rapport' );

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE ) );
		exit;
	}

	/**
	 * Melding om valg som ikke kunne lagres.
	 */
	public static function avviste_notice() {
		$nokkel  = 'bvp_avviste_' . get_current_user_id();
		$avviste = get_transient( $nokkel );

		if ( ! is_array( $avviste ) || ! $avviste ) {
			return;
		}

		delete_transient( $nokkel );

		echo '<div class="notice notice-warning"><p><strong>Dette valget kunne ikke lagres:</strong></p><ul style="margin-left:1.5em;list-style:disc">';

		foreach ( $avviste as $avvist ) {
			printf(
				'<li>«%1$s» i feltet %2$s - %3$s.</li>',
				esc_html( isset( $avvist['tittel'] ) ? $avvist['tittel'] : '' ),
				esc_html( isset( $avvist['felt'] ) ? $avvist['felt'] : '' ),
				esc_html( isset( $avvist['grunn'] ) ? $avvist['grunn'] : '' )
			);
		}

		echo '</ul><p>Alt annet er lagret.</p></div>';
	}

	/**
	 * Eksportknapp over listen.
	 */
	public static function export_button() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit' !== $screen->base || BVP_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::EXPORT_ACTION ),
			self::EXPORT_ACTION
		);

		printf(
			'<div class="notice notice-info bvp-export-notice"><p>Eksporter alle piloter/prosjekter med alle felt: <a class="button button-primary" href="%s">Last ned CSV</a></p></div>',
			esc_url( $url )
		);
	}

	/* ---------------------------------------------------------------------
	 * CSV-eksport
	 * ------------------------------------------------------------------ */

	public static function export_csv() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( 'Du har ikke tilgang til å eksportere piloter/prosjekter.', 'Ingen tilgang', array( 'response' => 403 ) );
		}

		check_admin_referer( self::EXPORT_ACTION );

		$fields = BVP_Fields::all( 'all' );
		$posts  = get_posts(
			array(
				'post_type'      => BVP_Post_Type::POST_TYPE,
				'post_status'    => array( 'draft', 'pending', 'publish' ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$filename = 'bv-pilot-prosjekt-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM for Excel.

		$header = array( 'ID', 'Status', 'Registrert av (bruker)', 'Opprettet', 'Innsendt', 'Sist endret' );
		foreach ( $fields as $field ) {
			$header[] = $field['label'];
		}
		fputcsv( $out, $header, ';' );

		foreach ( $posts as $post ) {
			$author = get_userdata( (int) $post->post_author );
			$row    = array(
				$post->ID,
				BVP_Post_Type::status_label( $post ),
				$author ? $author->user_login : '',
				get_the_date( 'Y-m-d H:i', $post ),
				(string) get_post_meta( $post->ID, '_bvp_innsendt_dato', true ),
				get_the_modified_date( 'Y-m-d H:i', $post ),
			);

			foreach ( $fields as $key => $field ) {
				// Felt som ikke gjelder for denne registreringen står tomme.
				$row[] = BVP_Repository::depends_met_for_post( $field, $post->ID )
					? BVP_Renderer::value_to_text( $field, BVP_Repository::get_value( $post->ID, $key ) )
					: '';
			}

			fputcsv( $out, $row, ';' );
		}

		fclose( $out );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Innstillinger
	 * ------------------------------------------------------------------ */

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . BVP_Post_Type::POST_TYPE,
			'Innstillinger for BV-pilot/prosjekt',
			'Innstillinger',
			'manage_options',
			'bvp-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'bvp_settings_group',
			self::OPTION,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		return array(
			'notify_admin'  => empty( $input['notify_admin'] ) ? 0 : 1,
			'notify_kladd'  => empty( $input['notify_kladd'] ) ? 0 : 1,
			'notify_email'  => isset( $input['notify_email'] ) ? sanitize_text_field( $input['notify_email'] ) : get_option( 'admin_email' ),
			'send_receipt'  => empty( $input['send_receipt'] ) ? 0 : 1,
			'kontakt_epost' => isset( $input['kontakt_epost'] ) ? sanitize_email( $input['kontakt_epost'] ) : '',
			'kontakt_url'   => isset( $input['kontakt_url'] ) ? esc_url_raw( $input['kontakt_url'] ) : '',
			'hjelp_url'     => isset( $input['hjelp_url'] ) ? esc_url_raw( $input['hjelp_url'] ) : '',
			'skjema_url'    => isset( $input['skjema_url'] ) ? esc_url_raw( $input['skjema_url'] ) : '',
		);
	}

	public static function settings_page() {
		$settings = self::get_settings();
		$page_id  = self::find_shortcode_page();
		?>
		<div class="wrap">
			<h1>Innstillinger for BV-pilot/prosjekt</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'bvp_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Varsle ved innsending</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[notify_admin]" value="1" <?php checked( ! empty( $settings['notify_admin'] ) ); ?>>
								Send e-post til BIM Verdi når et pilot/prosjekt sendes inn eller oppdateres
							</label>
							<br>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[notify_kladd]" value="1" <?php checked( ! empty( $settings['notify_kladd'] ) ); ?>>
								Send også e-post når noen starter en ny registrering (første gang den lagres som kladd)
							</label>
							<p class="description">Kladd vises ikke i oversiktene på nettsiden, bare her i wp-admin. Med dette valget får dere vite om dem med en gang.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bvp-notify-email">Mottakere</label></th>
						<td>
							<input type="text" class="regular-text" id="bvp-notify-email" name="<?php echo esc_attr( self::OPTION ); ?>[notify_email]" value="<?php echo esc_attr( $settings['notify_email'] ); ?>">
							<p class="description">Skill flere adresser med komma.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bvp-kontakt-url">Kontaktlenke for deltakere</label></th>
						<td>
							<input type="url" class="regular-text" id="bvp-kontakt-url" name="<?php echo esc_attr( self::OPTION ); ?>[kontakt_url]" value="<?php echo esc_attr( $settings['kontakt_url'] ); ?>" placeholder="https://bimverdi.no/tilbakemelding/">
							<p class="description">Vises til dem som mangler brukerprofil eller foretakskobling.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bvp-kontakt-epost">Kontakt-e-post (reserve)</label></th>
						<td>
							<input type="email" class="regular-text" id="bvp-kontakt-epost" name="<?php echo esc_attr( self::OPTION ); ?>[kontakt_epost]" value="<?php echo esc_attr( $settings['kontakt_epost'] ); ?>">
							<p class="description">Brukes bare hvis kontaktlenken over står tom.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bvp-hjelp-url">Lenke til «Slik blir du deltaker»</label></th>
						<td>
							<input type="url" class="regular-text" id="bvp-hjelp-url" name="<?php echo esc_attr( self::OPTION ); ?>[hjelp_url]" value="<?php echo esc_attr( $settings['hjelp_url'] ); ?>" placeholder="https://bimverdi.no/bli-deltaker/">
							<p class="description">Vises som knapp for dem som ikke har brukerprofil. La stå tom for å skjule knappen.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bvp-skjema-url">Adresse til skjemasiden</label></th>
						<td>
							<input type="url" class="regular-text" id="bvp-skjema-url" name="<?php echo esc_attr( self::OPTION ); ?>[skjema_url]" value="<?php echo esc_attr( $settings['skjema_url'] ); ?>" placeholder="https://bimverdi.no/tjenester-og-verktoy/prosjekt/">
							<p class="description">Brukes av «Mine prosjekter» på min-side. La stå tom, så finnes siden med kortkoden automatisk.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Kvittering til bruker</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[send_receipt]" value="1" <?php checked( ! empty( $settings['send_receipt'] ) ); ?>>
								Send kopi av registreringen til den som registrerte den
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2>Slik legger du skjemaet ut på nettstedet</h2>
			<p>Lag en side (f.eks. «Registrer BV-pilot/prosjekt»), og sett inn kortkoden:</p>
			<p><code>[bv_pilot_prosjekt]</code></p>
			<p>Valgfri overskrift: <code>[bv_pilot_prosjekt tittel="BV-pilot/prosjekt - rask avklaring"]</code></p>
			<?php if ( $page_id ) : ?>
				<p>Kortkoden er funnet på siden
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php echo esc_html( get_the_title( $page_id ) ); ?></a>
					(<a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>">rediger</a>).</p>
			<?php else : ?>
				<p><em>Kortkoden er ikke funnet på noen publisert side ennå.</em></p>
			<?php endif; ?>
			<h2>Endre feltene i skjemaet</h2>
			<p>Feltene redigeres under <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . BVP_Post_Type::POST_TYPE . '&page=bvp-form-editor' ) ); ?>">BV-pilot/prosjekt &rarr; Skjemaeditor</a>.</p>

			<p>Innloggede brukere ser sine egne registreringer og kan lagre kladd, sende inn og oppdatere i etterkant.
				Seksjonene «Tillegg for administrator» og «Effektberegninger» vises kun her i wp-admin.</p>
		</div>
		<?php
	}

	/**
	 * Finn en publisert side som inneholder kortkoden.
	 *
	 * @return int
	 */
	protected static function find_shortcode_page() {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				's'              => BVP_Form::SHORTCODE,
			)
		);

		foreach ( $pages as $page ) {
			if ( has_shortcode( $page->post_content, BVP_Form::SHORTCODE ) ) {
				return (int) $page->ID;
			}
		}

		return 0;
	}
}
