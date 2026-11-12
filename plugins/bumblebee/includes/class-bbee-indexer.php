<?php
/**
 * Indeksering av kunnskapskilder (WordPress-innhold + PDF-vedlegg).
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Indexer
 */
class BBEE_Indexer {

	const CHUNK_SIZE    = 1200;
	const CHUNK_OVERLAP = 150;

	/**
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'bbee_chunks';
	}

	/**
	 * Opprett databasetabellen.
	 */
	public static function create_table() {
		global $wpdb;
		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			source_type VARCHAR(20) NOT NULL DEFAULT 'post',
			source_id VARCHAR(64) NOT NULL DEFAULT '',
			post_type VARCHAR(64) NOT NULL DEFAULT '',
			chunk_index INT(11) NOT NULL DEFAULT 0,
			title TEXT NOT NULL,
			url TEXT NOT NULL,
			content LONGTEXT NOT NULL,
			embedding LONGTEXT NULL,
			embed_model VARCHAR(64) NOT NULL DEFAULT '',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY source (source_type, source_id),
			KEY embed_model (embed_model),
			FULLTEXT KEY ft_content (content, title)
		) ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Koble opp automatiske hooks.
	 */
	public function register_hooks() {
		add_action( 'save_post', array( $this, 'on_save_post' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'on_delete_post' ) );
		add_action( 'wp_trash_post', array( $this, 'on_delete_post' ) );
		add_action( 'add_attachment', array( $this, 'on_save_attachment' ) );
		add_action( 'edit_attachment', array( $this, 'on_save_attachment' ) );
		add_action( 'delete_attachment', array( $this, 'on_delete_attachment' ) );
		add_action( 'bbee_embed_pending', array( $this, 'run_embed_pending' ) );
	}

	/**
	 * @return array
	 */
	protected function indexed_post_types() {
		$settings = BBEE_Plugin::get_settings();
		$types    = isset( $settings['post_types'] ) ? (array) $settings['post_types'] : array( 'post', 'page' );
		return apply_filters( 'bbee_indexed_post_types', $types );
	}

	/**
	 * @param int     $post_id Post-ID.
	 * @param WP_Post $post    Post.
	 * @param bool    $update  Oppdatering?
	 */
	public function on_save_post( $post_id, $post = null, $update = false ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( null === $post ) {
			$post = get_post( $post_id );
		}
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		// Hentet tekst skal bort med en gang hvis kilden ikke er publisert
		// eller ikke lenger er «ikke_reservert».
		if ( self::KILDE_POST_TYPE === $post->post_type
			&& ( 'publish' !== $post->post_status || ! self::kilde_kan_hentes( $post_id ) ) ) {
			$this->delete_kilde_chunks( $post_id );
		}
		if ( ! in_array( $post->post_type, $this->indexed_post_types(), true ) ) {
			return;
		}
		if ( 'publish' !== $post->post_status ) {
			$this->delete_post_chunks( $post_id );
			return;
		}
		$this->index_post( $post );
	}

	/**
	 * @param int $post_id Post-ID.
	 */
	public function on_delete_post( $post_id ) {
		$this->delete_post_chunks( $post_id );
		$this->delete_kilde_chunks( $post_id );
	}

	/* ---- Hentet tekst fra kunnskapskilder (source_type «kilde») ---- */

	const KILDE_POST_TYPE   = 'kunnskapskilde';
	const KILDE_SOURCE_TYPE = 'kilde';

	/**
	 * Kan teksten bak denne kunnskapskilden hentes maskinelt?
	 * Bare «ikke_reservert» gir ja. «reservert», «ukjent» og tomt gir nei.
	 *
	 * @param int $post_id Kunnskapskilde-ID.
	 * @return bool
	 */
	public static function kilde_kan_hentes( $post_id ) {
		return 'ikke_reservert' === (string) get_post_meta( (int) $post_id, 'maskinell_bruk', true );
	}

	/**
	 * Del hentet tekst i biter og fjern det som ikke kan brukes:
	 * - linjer med tegnrot (BBEE_Crawler::rens_tegnrot),
	 * - hele biter med tegnrot eller på et annet språk enn norsk, svensk,
	 *   dansk eller engelsk (BBEE_Crawler::bit_grunn).
	 * Brukes både når biter lagres og når CLI-en teller biter, så tallene stemmer.
	 *
	 * @param string $text    Tekst.
	 * @param array  $droppet Fylles med {grunn, tekst} for hver bit som ble fjernet,
	 *                        og {grunn: 'tegnrot (linjer)', linjer} for fjernede linjer.
	 * @return array Biter som kan lagres.
	 */
	public function kilde_biter( $text, &$droppet = null ) {
		$droppet = array();
		$fjernet = array();
		$text    = BBEE_Crawler::rens_tegnrot( (string) $text, $fjernet );
		if ( ! empty( $fjernet ) ) {
			$droppet[] = array( 'grunn' => 'tegnrot (linjer)', 'linjer' => $fjernet );
		}
		$ut = array();
		foreach ( $this->split_into_chunks( $text ) as $chunk ) {
			$grunn = BBEE_Crawler::bit_grunn( $chunk );
			if ( '' !== $grunn ) {
				$droppet[] = array( 'grunn' => $grunn, 'tekst' => $chunk );
				continue;
			}
			$ut[] = $chunk;
		}
		return $ut;
	}

	/**
	 * Slett hentet tekst for én kunnskapskilde.
	 *
	 * @param int $post_id Kunnskapskilde-ID.
	 * @return int
	 */
	public function delete_kilde_chunks( $post_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->delete( $table, array( 'post_id' => (int) $post_id, 'source_type' => self::KILDE_SOURCE_TYPE ), array( '%d', '%s' ) );
	}

	/**
	 * Lagre tekst fra én hentet side eller PDF som tekstbiter.
	 * Lager ikke embeddings. Det gjør «wp bbee embed».
	 *
	 * @param int    $kilde_id Kunnskapskilde-ID.
	 * @param string $url      Den faktiske siden eller PDF-en.
	 * @param string $title    Tittel.
	 * @param string $text     Tekst.
	 * @param array  $lager    Valgfri: md5( tittel + "\n" + tekst ) => ID til en eldre bit
	 *                         med embedding. Treff kopierer embeddingen.
	 * @return int|array Antall biter, eller {biter, gjenbrukt} når $lager er gitt.
	 */
	public function index_kilde_page( $kilde_id, $url, $title, $text, $lager = null ) {
		global $wpdb;

		$text = trim( (string) $text );
		if ( '' === $text ) {
			return is_array( $lager ) ? array( 'biter' => 0, 'gjenbrukt' => 0 ) : 0;
		}

		// Tegnrot og tekst på andre språk lagres ikke (se kilde_biter()).
		$chunks    = $this->kilde_biter( $text );
		$title     = '' !== trim( (string) $title ) ? $title : $url;
		$now       = current_time( 'mysql' );
		$table     = self::table_name();
		$count     = 0;
		$gjenbrukt = 0;

		foreach ( $chunks as $i => $chunk ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->insert(
				$table,
				array(
					'post_id'     => (int) $kilde_id,
					'source_type' => self::KILDE_SOURCE_TYPE,
					'source_id'   => (string) (int) $kilde_id,
					'post_type'   => self::KILDE_POST_TYPE,
					'chunk_index' => $i,
					'title'       => $title,
					'url'         => $url,
					'content'     => $chunk,
					'updated_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
			if ( ! $ok ) {
				continue;
			}
			$count++;
			$h = md5( $title . "\n" . $chunk );
			if ( is_array( $lager ) && isset( $lager[ $h ] ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$table} AS n JOIN {$table} AS o ON o.id = %d SET n.embedding = o.embedding, n.embed_model = o.embed_model WHERE n.id = %d",
						$lager[ $h ],
						(int) $wpdb->insert_id
					)
				);
				$gjenbrukt++;
			}
		}
		return is_array( $lager ) ? array( 'biter' => $count, 'gjenbrukt' => $gjenbrukt ) : $count;
	}

	/**
	 * Bytt ut all hentet tekst for én kunnskapskilde.
	 *
	 * Biter med samme tittel og tekst som før beholder embeddingen sin, så
	 * en ny lagring av samme innhold ikke koster nye API-kall. Gamle biter
	 * slettes først når de nye er lagret. Alt skjer i én transaksjon.
	 *
	 * @param int   $kilde_id Kunnskapskilde-ID.
	 * @param array $sider    Liste med array( url, tittel, tekst ).
	 * @return array {biter, gjenbrukt, slettet}
	 */
	public function erstatt_kilde_sider( $kilde_id, $sider ) {
		global $wpdb;
		$table    = self::table_name();
		$kilde_id = (int) $kilde_id;

		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$gamle = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, content, (embedding IS NOT NULL AND embedding <> '') AS har_emb FROM {$table} WHERE post_id = %d AND source_type = %s",
				$kilde_id,
				self::KILDE_SOURCE_TYPE
			),
			ARRAY_A
		);
		$lager   = array();
		$gml_ids = array();
		foreach ( (array) $gamle as $r ) {
			$gml_ids[] = (int) $r['id'];
			if ( (int) $r['har_emb'] ) {
				$lager[ md5( $r['title'] . "\n" . $r['content'] ) ] = (int) $r['id'];
			}
		}
		unset( $gamle );

		$ut = array( 'biter' => 0, 'gjenbrukt' => 0, 'slettet' => 0 );
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $sider as $s ) {
			list( $url, $tittel, $tekst ) = $s;
			$r                = $this->index_kilde_page( $kilde_id, $url, $tittel, $tekst, $lager );
			$ut['biter']     += $r['biter'];
			$ut['gjenbrukt'] += $r['gjenbrukt'];
		}
		foreach ( array_chunk( $gml_ids, 500 ) as $del ) {
			$in             = implode( ',', array_map( 'intval', $del ) );
			// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
			$ut['slettet'] += (int) $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$in})" );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $ut;
	}

	/**
	 * Katalogtekst for en kunnskapskilde: utvalgte ACF-felt med etiketter.
	 * Listen er en hviteliste. Interne felt (utgiver_epost, sharepoint_lenke,
	 * rettigheter_notat, kontaktperson, rettighetsfelt) kommer aldri med.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	protected function kunnskapskilde_to_text( $post ) {
		$fields = array(
			'kort_beskrivelse'      => 'Kort beskrivelse',
			'detaljert_beskrivelse' => 'Beskrivelse',
			'utgiver'               => 'Utgiver',
			'kildetype'             => 'Kilde-kategori',
			'dataformat'            => 'Dataformat',
			'spraak'                => 'Språk',
			'versjon'               => 'Versjon',
			'tilgang'               => 'Tilgang',
			'geografisk_gyldighet'  => 'Geografisk gyldighet',
			'geografisk_fylker'     => 'Fylke(r)',
			'geografisk_kommuner'   => 'Kommune(r)',
			'lovpalagte_standarder' => 'Lovpålagte standarder',
			'anbefalte_standarder'  => 'Anbefalte standarder',
			'tilknyttet_bedrift'    => 'Tilknyttet foretak',
			'ekstern_lenke'         => 'Lenke',
		);
		$fields = apply_filters( 'bbee_kunnskapskilde_felt', $fields, $post );

		$lines = array();
		foreach ( $fields as $key => $label ) {
			$text = $this->acf_value_to_text( $post->ID, $key );
			if ( '' !== $text ) {
				$lines[] = $label . ': ' . $text;
			}
		}

		// Kategori-taksonomien er ikke offentlig, så terms_to_text tar den ikke med.
		$terms = get_the_terms( $post, 'kunnskapskildekategori' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$lines[] = 'Kategori: ' . implode( ', ', wp_list_pluck( $terms, 'name' ) );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Gjør en ACF-verdi om til lesbar tekst. Valgfelt får etiketten, ikke koden.
	 * Rene tallkoder uten etikett (f.eks. kommunenummer) hoppes over.
	 *
	 * @param int    $post_id Post-ID.
	 * @param string $key     Feltnavn.
	 * @return string
	 */
	protected function acf_value_to_text( $post_id, $key ) {
		$raw = get_post_meta( $post_id, $key, true );
		if ( '' === $raw || null === $raw || array() === $raw ) {
			return '';
		}
		$values = is_array( $raw ) ? $raw : array( $raw );

		$choices = array();
		$type    = '';
		if ( function_exists( 'get_field_object' ) ) {
			$fo = get_field_object( $key, $post_id, false, false );
			if ( is_array( $fo ) ) {
				$type    = isset( $fo['type'] ) ? (string) $fo['type'] : '';
				$choices = ! empty( $fo['choices'] ) && is_array( $fo['choices'] ) ? $fo['choices'] : array();
			}
		}

		$out = array();
		foreach ( $values as $v ) {
			if ( ! is_scalar( $v ) || '' === trim( (string) $v ) ) {
				continue;
			}
			$v = (string) $v;
			if ( 'post_object' === $type || 'tilknyttet_bedrift' === $key ) {
				$t = get_the_title( (int) $v );
				if ( '' !== $t ) {
					$out[] = html_entity_decode( wp_strip_all_tags( $t ), ENT_QUOTES, 'UTF-8' );
				}
				continue;
			}
			if ( isset( $choices[ $v ] ) ) {
				$out[] = wp_strip_all_tags( (string) $choices[ $v ] );
				continue;
			}
			if ( ! empty( $choices ) || ( in_array( $type, array( 'select', 'checkbox', 'radio' ), true ) && ctype_digit( $v ) ) ) {
				// Kode uten etikett: tallkoder er støy, andre koder gjøres lesbare.
				if ( ctype_digit( $v ) ) {
					continue;
				}
				$out[] = str_replace( '_', ' ', $v );
				continue;
			}
			$out[] = $this->flatten_meta( $v );
		}
		return trim( implode( ', ', array_filter( $out ) ) );
	}

	/**
	 * @param int $post_id Post-ID.
	 * @return int
	 */
	public function delete_post_chunks( $post_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->delete( $table, array( 'post_id' => (int) $post_id, 'source_type' => 'post' ), array( '%d', '%s' ) );
	}

	/**
	 * Indekser ett innlegg.
	 *
	 * @param WP_Post $post Post.
	 * @return int
	 */
	public function index_post( $post ) {
		global $wpdb;

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}
		if ( ! $post instanceof WP_Post ) {
			return 0;
		}

		$this->delete_post_chunks( $post->ID );

		$plain = $this->post_to_text( $post );
		if ( '' === trim( $plain ) ) {
			return 0;
		}

		$chunks = $this->split_into_chunks( $plain );
		$title  = get_the_title( $post );
		$url    = get_permalink( $post );
		$now    = current_time( 'mysql' );
		$table  = self::table_name();
		$count  = 0;

		foreach ( $chunks as $i => $chunk ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->insert(
				$table,
				array(
					'post_id'     => $post->ID,
					'source_type' => 'post',
					'source_id'   => (string) $post->ID,
					'post_type'   => $post->post_type,
					'chunk_index' => $i,
					'title'       => $title,
					'url'         => $url,
					'content'     => $chunk,
					'updated_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
			if ( $ok ) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$this->schedule_embedding();
		}
		return $count;
	}

	/**
	 * Indekser tekst fra én crawlet nettside (kilde-type «url»).
	 *
	 * @param string $source_id Kilde-ID.
	 * @param string $url        URL.
	 * @param string $title      Tittel.
	 * @param string $text       Tekst.
	 * @return int
	 */
	public function index_url_page( $source_id, $url, $title, $text ) {
		global $wpdb;

		$text = trim( (string) $text );
		if ( '' === $text ) {
			return 0;
		}

		$chunks = $this->split_into_chunks( $text );
		$title  = '' !== trim( (string) $title ) ? $title : $url;
		$now    = current_time( 'mysql' );
		$table  = self::table_name();
		$count  = 0;

		foreach ( $chunks as $i => $chunk ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->insert(
				$table,
				array(
					'post_id'     => 0,
					'source_type' => 'url',
					'source_id'   => (string) $source_id,
					'post_type'   => 'url',
					'chunk_index' => $i,
					'title'       => $title,
					'url'         => $url,
					'content'     => $chunk,
					'updated_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
			if ( $ok ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * @param string $source_id Kilde-ID.
	 * @return int
	 */
	public function delete_url_source_chunks( $source_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->delete( $table, array( 'source_type' => 'url', 'source_id' => (string) $source_id ), array( '%s', '%s' ) );
	}

	/* ---- PDF-vedlegg ---- */

	/**
	 * @return bool
	 */
	protected function media_pdf_enabled() {
		$settings = BBEE_Plugin::get_settings();
		return ! empty( $settings['index_media_pdf'] ) && BBEE_Pdf::available();
	}

	/**
	 * @param int $attachment_id Vedleggs-ID.
	 */
	public function on_save_attachment( $attachment_id ) {
		if ( ! $this->media_pdf_enabled() ) {
			return;
		}
		if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
			return;
		}
		$this->index_pdf_attachment( $attachment_id );
	}

	/**
	 * @param int $attachment_id Vedleggs-ID.
	 */
	public function on_delete_attachment( $attachment_id ) {
		$this->delete_pdf_chunks( $attachment_id );
	}

	/**
	 * @param int $attachment_id Vedleggs-ID.
	 * @return int
	 */
	public function delete_pdf_chunks( $attachment_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->delete( $table, array( 'source_type' => 'pdf', 'source_id' => (string) $attachment_id ), array( '%s', '%s' ) );
	}

	/**
	 * @param int $attachment_id Vedleggs-ID.
	 * @return int
	 */
	public function index_pdf_attachment( $attachment_id ) {
		global $wpdb;

		$this->delete_pdf_chunks( $attachment_id );

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return 0;
		}

		$text = BBEE_Pdf::extract_from_file( $path );
		if ( is_wp_error( $text ) || '' === trim( (string) $text ) ) {
			return 0;
		}

		$title  = get_the_title( $attachment_id );
		$url    = wp_get_attachment_url( $attachment_id );
		$chunks = $this->split_into_chunks( $text );
		$now    = current_time( 'mysql' );
		$table  = self::table_name();
		$count  = 0;

		foreach ( $chunks as $i => $chunk ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->insert(
				$table,
				array(
					'post_id'     => (int) $attachment_id,
					'source_type' => 'pdf',
					'source_id'   => (string) $attachment_id,
					'post_type'   => 'attachment',
					'chunk_index' => $i,
					'title'       => $title,
					'url'         => $url ? $url : '',
					'content'     => $chunk,
					'updated_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);
			if ( $ok ) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$this->schedule_embedding();
		}
		return $count;
	}

	/**
	 * @return int
	 */
	public function reindex_media_pdfs() {
		if ( ! $this->media_pdf_enabled() ) {
			return 0;
		}
		$chunks = 0;
		$paged  = 1;
		do {
			$q = new WP_Query(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'post_mime_type' => 'application/pdf',
					'posts_per_page' => 50,
					'paged'          => $paged,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			if ( ! $q->have_posts() ) {
				break;
			}
			foreach ( $q->posts as $att_id ) {
				$chunks += $this->index_pdf_attachment( $att_id );
			}
			$paged++;
		} while ( count( $q->posts ) === 50 );

		return $chunks;
	}

	/* ---- Tekstbehandling ---- */

	/**
	 * @param WP_Post $post Post.
	 * @return string
	 */
	protected function post_to_text( $post ) {
		$content = $post->post_content;
		if ( has_blocks( $content ) ) {
			$content = do_blocks( $content );
		}
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content, false );
		$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
		$content = preg_replace( "/[ \t]+/", ' ', $content );
		$content = preg_replace( "/\n{3,}/", "\n\n", $content );

		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';

		// Tittelen er alltid med, slik at også innhold uten brødtekst (f.eks. foretak) kan finnes.
		$title = trim( html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ) );

		$parts = array_filter(
			array(
				'' !== $title ? $title : '',
				$this->terms_to_text( $post ),
				self::KILDE_POST_TYPE === $post->post_type ? $this->kunnskapskilde_to_text( $post ) : '',
				$this->meta_to_text( $post ),
				trim( $excerpt ),
				trim( $content ),
			)
		);
		return trim( implode( "\n\n", $parts ) );
	}

	/**
	 * Kategorier/taksonomier (f.eks. bransjekategorier, temagrupper, deltakernivå) som tekst.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	protected function terms_to_text( $post ) {
		$lines = array();
		$taxes = get_object_taxonomies( $post->post_type, 'objects' );
		foreach ( (array) $taxes as $tax ) {
			if ( empty( $tax->public ) || in_array( $tax->name, array( 'post_format', 'nav_menu', 'link_category' ), true ) ) {
				continue;
			}
			$terms = get_the_terms( $post, $tax->name );
			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}
			$names = array();
			foreach ( $terms as $t ) {
				$names[] = $t->name;
			}
			$label   = isset( $tax->labels->name ) ? $tax->labels->name : $tax->name;
			$lines[] = $label . ': ' . implode( ', ', $names );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Egendefinerte felt som skal med i indeksen, per innholdstype.
	 * Settes under innstillinger (én linje per type: «foretak: felt1, felt2»)
	 * eller via filteret `bbee_meta_fields`.
	 *
	 * @param string $post_type Innholdstype.
	 * @return array
	 */
	protected function meta_fields_for( $post_type ) {
		$settings = BBEE_Plugin::get_settings();
		$map      = array();
		if ( ! empty( $settings['index_meta_fields'] ) ) {
			foreach ( preg_split( '/\r?\n/', (string) $settings['index_meta_fields'] ) as $line ) {
				if ( false === strpos( $line, ':' ) ) {
					continue;
				}
				list( $type, $fields ) = array_map( 'trim', explode( ':', $line, 2 ) );
				if ( '' === $type ) {
					continue;
				}
				$map[ sanitize_key( $type ) ] = array_filter( array_map( 'trim', explode( ',', $fields ) ) );
			}
		}
		$fields = isset( $map[ $post_type ] ) ? $map[ $post_type ] : array();
		$fields = apply_filters( 'bbee_meta_fields', $fields, $post_type );
		return array_values( array_unique( array_filter( array_map( 'strval', (array) $fields ) ) ) );
	}

	/**
	 * @param WP_Post $post Post.
	 * @return string
	 */
	protected function meta_to_text( $post ) {
		$lines = array();
		foreach ( $this->meta_fields_for( $post->post_type ) as $key ) {
			$value = get_post_meta( $post->ID, $key, true );
			$text  = $this->flatten_meta( $value );
			if ( '' === $text ) {
				continue;
			}
			$lines[] = str_replace( '_', ' ', ltrim( $key, '_' ) ) . ': ' . $text;
		}
		return implode( "\n", $lines );
	}

	/**
	 * @param mixed $value Metaverdi.
	 * @return string
	 */
	protected function flatten_meta( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $v ) {
				$t = $this->flatten_meta( $v );
				if ( '' !== $t ) {
					$out[] = $t;
				}
			}
			return implode( ', ', $out );
		}
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$t = trim( html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' ) );
		return $t;
	}

	/**
	 * @param string $text Tekst.
	 * @return array
	 */
	public function split_into_chunks( $text ) {
		$text = trim( $text );
		if ( '' === $text ) {
			return array();
		}
		if ( $this->mb_len( $text ) <= self::CHUNK_SIZE ) {
			return array( $text );
		}

		$segments = preg_split( '/(?<=[\.\!\?])\s+|\n{2,}/u', $text );
		if ( ! is_array( $segments ) ) {
			$segments = array( $text );
		}

		$chunks  = array();
		$current = '';
		foreach ( $segments as $segment ) {
			$segment = trim( $segment );
			if ( '' === $segment ) {
				continue;
			}
			$candidate = ( '' === $current ) ? $segment : $current . ' ' . $segment;
			if ( $this->mb_len( $candidate ) > self::CHUNK_SIZE && '' !== $current ) {
				$chunks[] = $current;
				$overlap  = $this->mb_tail( $current, self::CHUNK_OVERLAP );
				$current  = trim( $overlap . ' ' . $segment );
			} else {
				$current = $candidate;
			}
		}
		if ( '' !== trim( $current ) ) {
			$chunks[] = trim( $current );
		}

		$final = array();
		foreach ( $chunks as $chunk ) {
			if ( $this->mb_len( $chunk ) <= self::CHUNK_SIZE * 1.5 ) {
				$final[] = $chunk;
				continue;
			}
			foreach ( $this->hard_split( $chunk, self::CHUNK_SIZE ) as $piece ) {
				$final[] = $piece;
			}
		}
		return $final;
	}

	/**
	 * @param string $text Tekst.
	 * @param int    $size Maks.
	 * @return array
	 */
	protected function hard_split( $text, $size ) {
		$pieces = array();
		$len    = $this->mb_len( $text );
		for ( $i = 0; $i < $len; $i += $size ) {
			$pieces[] = function_exists( 'mb_substr' ) ? mb_substr( $text, $i, $size ) : substr( $text, $i, $size );
		}
		return $pieces;
	}

	/**
	 * @param string $s Streng.
	 * @return int
	 */
	protected function mb_len( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : strlen( $s );
	}

	/**
	 * @param string $s Streng.
	 * @param int    $n Antall.
	 * @return string
	 */
	protected function mb_tail( $s, $n ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $s, -$n ) : substr( $s, -$n );
	}

	/* ---- Embeddings ---- */

	/**
	 * Planlegg embedding-generering hvis leverandøren er konfigurert.
	 */
	public function schedule_embedding() {
		$embed = new BBEE_Embeddings();
		if ( ! $embed->is_configured() ) {
			return;
		}
		if ( ! wp_next_scheduled( 'bbee_embed_pending' ) ) {
			wp_schedule_single_event( time() + 10, 'bbee_embed_pending' );
		}
	}

	/**
	 * Cron-callback for embeddings.
	 */
	public function run_embed_pending() {
		$result = $this->embed_pending_batch( 64 );
		if ( is_array( $result ) && ! empty( $result['remaining'] ) ) {
			wp_schedule_single_event( time() + 5, 'bbee_embed_pending' );
		}
	}

	/**
	 * @param int $batch Batch-størrelse.
	 * @return array
	 */
	public function embed_pending_batch( $batch = 64 ) {
		global $wpdb;
		$table = self::table_name();

		$embed = new BBEE_Embeddings();
		if ( ! $embed->is_configured() ) {
			return array( 'done' => 0, 'remaining' => 0, 'error' => 'not_configured' );
		}
		$model_id = $embed->model_id();

		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, content FROM {$table}
				WHERE embedding IS NULL OR embedding = '' OR embed_model <> %s
				ORDER BY id ASC LIMIT %d",
				$model_id,
				(int) $batch
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array( 'done' => 0, 'remaining' => 0 );
		}

		$texts = array();
		foreach ( $rows as $r ) {
			$texts[] = trim( $r['title'] . "\n" . $r['content'] );
		}

		$vectors = $embed->embed( $texts, 'document' );
		if ( is_wp_error( $vectors ) ) {
			return array( 'done' => 0, 'remaining' => $this->count_pending( $model_id ), 'error' => $vectors->get_error_message(), 'tokens' => 0 );
		}
		$tokens = (int) $embed->last_usage_tokens;

		$done = 0;
		foreach ( $rows as $i => $r ) {
			if ( ! isset( $vectors[ $i ] ) ) {
				continue;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$table,
				array( 'embedding' => wp_json_encode( $vectors[ $i ] ), 'embed_model' => $model_id ),
				array( 'id' => (int) $r['id'] ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			$done++;
		}

		return array( 'done' => $done, 'remaining' => $this->count_pending( $model_id ), 'tokens' => $tokens );
	}

	/**
	 * Anslått antall tokens i biter som venter på embedding (tittel + tekst).
	 *
	 * @param string $model_id Modell-ID.
	 * @return int
	 */
	public function estimate_pending_tokens( $model_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$chars = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(CHAR_LENGTH(title) + 1 + CHAR_LENGTH(content)), 0) FROM {$table} WHERE embedding IS NULL OR embedding = '' OR embed_model <> %s",
				$model_id
			)
		);
		return self::estimate_tokens_from_chars( $chars );
	}

	/**
	 * Grovt anslag: ca. 3,5 tegn per token for norsk tekst (litt strengere enn 4).
	 *
	 * @param int $chars Antall tegn.
	 * @return int
	 */
	public static function estimate_tokens_from_chars( $chars ) {
		return (int) ceil( (int) $chars / 3.5 );
	}

	/**
	 * @param string $model_id Modell-ID.
	 * @return int
	 */
	public function count_pending( $model_id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE embedding IS NULL OR embedding = '' OR embed_model <> %s",
				$model_id
			)
		);
	}

	/* ---- Full reindeksering + statistikk ---- */

	/**
	 * @return array
	 */
	public function reindex_all() {
		global $wpdb;

		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $table, array( 'source_type' => 'post' ), array( '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $table, array( 'source_type' => 'pdf' ), array( '%s' ) );

		$types     = $this->indexed_post_types();
		$paged     = 1;
		$per_page  = 100;
		$posts_ct  = 0;
		$chunks_ct = 0;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => $types,
					'post_status'            => 'publish',
					'posts_per_page'         => $per_page,
					'paged'                  => $paged,
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);
			if ( ! $query->have_posts() ) {
				break;
			}
			foreach ( $query->posts as $post ) {
				$chunks_ct += $this->index_post( $post );
				$posts_ct++;
			}
			$paged++;
			wp_reset_postdata();
		} while ( count( $query->posts ) === $per_page );

		$pdf_chunks = $this->reindex_media_pdfs();
		$chunks_ct += $pdf_chunks;

		update_option( 'bbee_last_indexed', current_time( 'mysql' ), false );
		$this->schedule_embedding();

		return array( 'posts' => $posts_ct, 'chunks' => $chunks_ct, 'pdf_chunks' => $pdf_chunks );
	}

	/**
	 * @return array
	 */
	public function stats() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$chunks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$posts = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$table} WHERE source_type = 'post'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$url_chunks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE source_type = 'url'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$pdf_chunks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE source_type = 'pdf'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$kilde_chunks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE source_type = 'kilde'" );

		$embed    = new BBEE_Embeddings();
		$model_id = $embed->model_id();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$embedded = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE embedding IS NOT NULL AND embedding <> '' AND embed_model = %s",
				$model_id
			)
		);

		return array(
			'chunks'        => $chunks,
			'posts'         => $posts,
			'url_chunks'    => $url_chunks,
			'pdf_chunks'    => $pdf_chunks,
			'kilde_chunks'  => $kilde_chunks,
			'pdf_available' => BBEE_Pdf::available(),
			'embedded'      => $embedded,
			'embed_ready'   => $embed->is_configured(),
			'embed_model'   => $model_id,
			'last_indexed'  => get_option( 'bbee_last_indexed', '' ),
		);
	}
}
