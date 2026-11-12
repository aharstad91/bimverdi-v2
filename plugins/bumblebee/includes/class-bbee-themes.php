<?php
/**
 * Temagrupper som hovedkategorier + semantisk kobling av ressurser.
 *
 * @package Bumblebee
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BBEE_Themes
 */
class BBEE_Themes {

	const VECTORS_OPTION = 'bbee_theme_vectors';

	/**
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'bbee_resource_groups';
	}

	/**
	 * Opprett koblingstabellen.
	 */
	public static function create_table() {
		global $wpdb;
		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			resource_type VARCHAR(20) NOT NULL DEFAULT 'post',
			resource_id VARCHAR(64) NOT NULL DEFAULT '',
			title TEXT NOT NULL,
			url TEXT NOT NULL,
			primary_group_id VARCHAR(64) NOT NULL DEFAULT '',
			primary_group_title VARCHAR(191) NOT NULL DEFAULT '',
			primary_score FLOAT NOT NULL DEFAULT 0,
			groups LONGTEXT NULL,
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (resource_type, resource_id),
			KEY primary_group_id (primary_group_id)
		) ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * @return string
	 */
	public static function group_post_type() {
		$settings = BBEE_Plugin::get_settings();
		$pt       = ! empty( $settings['theme_group_post_type'] ) ? $settings['theme_group_post_type'] : 'theme_group';
		return apply_filters( 'bbee_theme_group_post_type', $pt );
	}

	/**
	 * @return float
	 */
	protected function secondary_threshold() {
		$settings = BBEE_Plugin::get_settings();
		$t        = isset( $settings['theme_secondary_threshold'] ) ? (float) $settings['theme_secondary_threshold'] : 0.4;
		return max( 0.0, min( 1.0, $t ) );
	}

	/**
	 * Bygg semantiske profiler for hver temagruppe.
	 *
	 * @return array|WP_Error
	 */
	public function build_profiles() {
		$embed = new BBEE_Embeddings();
		if ( ! $embed->is_configured() ) {
			return new WP_Error( 'bbee_theme_no_embed', __( 'Temagruppe-kobling krever at embeddings er satt opp.', 'bumblebee' ) );
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::group_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
			)
		);
		if ( ! $query->have_posts() ) {
			return new WP_Error( 'bbee_theme_none', __( 'Fant ingen temagrupper i valgt innholdstype.', 'bumblebee' ) );
		}

		$groups = array();
		$texts  = array();
		foreach ( $query->posts as $post ) {
			$title   = get_the_title( $post );
			$desc    = wp_strip_all_tags( $post->post_content );
			$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';
			$profile = trim( $title . "\n" . $excerpt . "\n" . $desc );
			$texts[] = '' !== $profile ? $profile : $title;
			$groups[] = array( 'id' => (string) $post->ID, 'title' => $title, 'url' => get_permalink( $post ) );
		}
		wp_reset_postdata();

		$vectors = $embed->embed( $texts, 'document' );
		if ( is_wp_error( $vectors ) ) {
			return $vectors;
		}

		$store = array( 'model' => $embed->model_id(), 'groups' => array() );
		foreach ( $groups as $i => $g ) {
			if ( ! isset( $vectors[ $i ] ) ) {
				continue;
			}
			$g['vector']                 = $vectors[ $i ];
			$store['groups'][ $g['id'] ] = $g;
		}
		update_option( self::VECTORS_OPTION, $store, false );

		return array( 'count' => count( $store['groups'] ) );
	}

	/**
	 * @return array|null
	 */
	protected function get_profiles() {
		$store = get_option( self::VECTORS_OPTION, null );
		if ( ! is_array( $store ) || empty( $store['groups'] ) ) {
			return null;
		}
		$embed = new BBEE_Embeddings();
		if ( ! empty( $store['model'] ) && $store['model'] !== $embed->model_id() ) {
			return null;
		}
		return $store['groups'];
	}

	/**
	 * Klassifiser alle ressurser mot temagruppene.
	 *
	 * @return array|WP_Error
	 */
	public function classify_all() {
		global $wpdb;

		$profiles = $this->get_profiles();
		if ( empty( $profiles ) ) {
			return new WP_Error( 'bbee_theme_no_profiles', __( 'Bygg temagruppe-profiler først.', 'bumblebee' ) );
		}

		$chunks_table = BBEE_Indexer::table_name();
		$group_pt     = self::group_post_type();

		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source_type, source_id, title, url, embedding
				FROM {$chunks_table}
				WHERE embedding IS NOT NULL AND embedding <> '' AND post_type <> %s
				ORDER BY source_type, source_id",
				$group_pt
			),
			ARRAY_A
		);
		if ( empty( $rows ) ) {
			return array( 'resources' => 0 );
		}

		$acc = array();
		foreach ( $rows as $r ) {
			$vec = json_decode( $r['embedding'], true );
			if ( ! is_array( $vec ) || empty( $vec ) ) {
				continue;
			}
			$key = $r['source_type'] . '|' . $r['source_id'];
			if ( ! isset( $acc[ $key ] ) ) {
				$acc[ $key ] = array(
					'type'  => $r['source_type'],
					'id'    => $r['source_id'],
					'title' => $r['title'],
					'url'   => $r['url'],
					'sum'   => array_fill( 0, count( $vec ), 0.0 ),
					'n'     => 0,
				);
			}
			$len = count( $acc[ $key ]['sum'] );
			for ( $i = 0; $i < $len; $i++ ) {
				$acc[ $key ]['sum'][ $i ] += isset( $vec[ $i ] ) ? $vec[ $i ] : 0.0;
			}
			$acc[ $key ]['n']++;
		}

		$threshold = $this->secondary_threshold();
		$table     = self::table_name();
		$now       = current_time( 'mysql' );
		$count     = 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		foreach ( $acc as $res ) {
			$centroid = $res['sum'];
			if ( $res['n'] > 0 ) {
				foreach ( $centroid as $i => $v ) {
					$centroid[ $i ] = $v / $res['n'];
				}
			}
			$centroid = BBEE_Embeddings::normalize( $centroid );

			$scores = array();
			foreach ( $profiles as $gid => $g ) {
				$scores[] = array(
					'id'    => (string) $gid,
					'title' => $g['title'],
					'url'   => isset( $g['url'] ) ? $g['url'] : '',
					'score' => round( BBEE_Embeddings::dot( $centroid, $g['vector'] ), 4 ),
				);
			}
			usort(
				$scores,
				function ( $a, $b ) {
					if ( $a['score'] === $b['score'] ) {
						return 0;
					}
					return ( $a['score'] < $b['score'] ) ? 1 : -1;
				}
			);

			$primary  = $scores[0];
			$assigned = array( $primary );
			foreach ( array_slice( $scores, 1 ) as $s ) {
				if ( $s['score'] >= $threshold ) {
					$assigned[] = $s;
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->replace(
				$table,
				array(
					'resource_type'       => $res['type'],
					'resource_id'         => (string) $res['id'],
					'title'               => $res['title'],
					'url'                 => $res['url'],
					'primary_group_id'    => $primary['id'],
					'primary_group_title' => $primary['title'],
					'primary_score'       => $primary['score'],
					'groups'              => wp_json_encode( $assigned ),
					'updated_at'          => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
			);
			$count++;
		}

		update_option( 'bbee_themes_last_run', $now, false );
		return array( 'resources' => $count );
	}

	/**
	 * @param string $type Type.
	 * @param string $id   ID.
	 * @return array|null
	 */
	public function primary_for( $type, $id ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT primary_group_id, primary_group_title, primary_score
				FROM {$table} WHERE resource_type = %s AND resource_id = %s",
				$type,
				(string) $id
			),
			ARRAY_A
		);
		if ( ! $row || '' === $row['primary_group_title'] ) {
			return null;
		}
		return array( 'id' => $row['primary_group_id'], 'title' => $row['primary_group_title'], 'score' => (float) $row['primary_score'] );
	}

	/**
	 * @return array
	 */
	public function overview() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			"SELECT primary_group_id, primary_group_title, COUNT(*) AS c
			FROM {$table}
			GROUP BY primary_group_id, primary_group_title
			ORDER BY c DESC",
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[] = array( 'group_id' => $r['primary_group_id'], 'title' => $r['primary_group_title'], 'count' => (int) $r['c'] );
		}
		return $out;
	}

	/**
	 * @param int $limit  Grense.
	 * @param int $offset Forskyvning.
	 * @return array
	 */
	public function resources( $limit = 200, $offset = 0 ) {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT resource_type, resource_id, title, url, primary_group_title, primary_score, groups
				FROM {$table}
				ORDER BY primary_group_title ASC, primary_score DESC
				LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @return string
	 */
	public function last_run() {
		return (string) get_option( 'bbee_themes_last_run', '' );
	}
}
