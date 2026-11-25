<?php
/**
 * Lay Blueprints — Snapshot.
 *
 * Captures the FULL state of the site relevant to Lay Blueprints before a
 * destructive import:
 *   - Lay-owned wp_options rows
 *   - theme_mods blob
 *   - All posts of in-scope types: title, slug, content, status, parent,
 *     dates, meta (filtered through KnownKeys), term assignments
 *   - All terms in in-scope taxonomies (with hierarchy)
 *   - Remote Blueprint placeholder attachments, so rollback works even
 *     when the next import cleans old placeholder media from the library
 *   - Active plugin list (informational)
 *
 * Restore is total: wipes current in-scope content + recreates posts with
 * their ORIGINAL IDs preserved (via wp_insert_post + import_id), restores
 * meta + layouts + term assignments + options + theme_mods.
 *
 * This is intentionally tighter scope than a full Blueprint export: it
 * doesn't ship media (the original media library is unchanged on the same
 * site, so attachment IDs in the restored data are still valid).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_Snapshot {

	const SUBDIR              = 'lay-blueprints-backups';
	const SCHEMA              = 'lay-blueprints-snapshot/v2';
	const POST_TYPES_IN_SCOPE = array( 'post', 'page', 'lay_news', 'lay_template' );
	const TAXONOMIES_IN_SCOPE = array( 'category', 'post_tag', 'lay_news_category' );

	/**
	 * @return array{ path: string, taken_at: int }|WP_Error
	 */
	public static function capture( $job_id ) {
		$dir = self::ensure_backup_dir();
		if ( is_wp_error( $dir ) ) { return $dir; }

		$options_result = LayBlueprint_OptionsCollector::collect();
		$thememods      = get_option( 'theme_mods_' . wp_get_theme()->get_stylesheet(), array() );
		$posts          = self::capture_posts();
		$terms          = self::capture_terms();
		$blueprint_media = self::capture_blueprint_media();
		$active_plugins = (array) get_option( 'active_plugins', array() );

		$snapshot = array(
			'schema'         => self::SCHEMA,
			'taken_at'       => time(),
			'site_url'       => home_url( '/' ),
			'lay_version'    => LayBlueprint_Schema::lay_theme_version(),
			'options'        => $options_result['values'],
			'theme_mods'     => is_array( $thememods ) ? $thememods : array(),
			'posts'          => $posts,
			'terms'          => $terms,
			'blueprint_media'=> $blueprint_media,
			'active_plugins' => $active_plugins,
		);

		$path = trailingslashit( $dir ) . $job_id . '.json';
		$json = wp_json_encode( $snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( $json === false ) {
			return new WP_Error( 'snapshot_encode_failed', 'Could not JSON-encode snapshot.' );
		}
		if ( file_put_contents( $path, $json ) === false ) {
			return new WP_Error( 'snapshot_write_failed', 'Could not write snapshot file: ' . $path );
		}

		return array( 'path' => $path, 'taken_at' => $snapshot['taken_at'] );
	}

	/**
	 * Full restore: wipes current in-scope content + recreates from snapshot.
	 *
	 * @return array audit | WP_Error
	 */
	public static function restore_full( $path ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'snapshot_unreadable', 'Snapshot not found: ' . $path );
		}
		$json = file_get_contents( $path );
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || ! isset( $data['schema'] ) ) {
			return new WP_Error( 'snapshot_invalid', 'Snapshot file is invalid or corrupt.' );
		}

		$audit = array(
			'wiped_posts'              => 0,
			'wiped_terms'              => 0,
			'wiped_blueprint_media'    => 0,
			'restored_terms'           => 0,
			'restored_posts'           => 0,
			'restored_blueprint_media' => 0,
		);

		// 1. Wipe current in-scope content (matches Importer's destructive cleanup).
		$audit['wiped_blueprint_media'] = self::delete_current_blueprint_media();
		foreach ( self::POST_TYPES_IN_SCOPE as $post_type ) {
			$existing = get_posts( array(
				'post_type' => $post_type, 'post_status' => 'any',
				'numberposts' => -1, 'fields' => 'ids',
			) );
			foreach ( (array) $existing as $pid ) {
				if ( wp_delete_post( $pid, true ) ) { $audit['wiped_posts']++; }
			}
		}
		$default_cat = (int) get_option( 'default_category' );
		foreach ( self::TAXONOMIES_IN_SCOPE as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$existing = get_terms( array(
				'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids',
			) );
			if ( is_wp_error( $existing ) ) { continue; }
			foreach ( (array) $existing as $tid ) {
				if ( $taxonomy === 'category' && (int) $tid === $default_cat ) { continue; }
				if ( wp_delete_term( $tid, $taxonomy ) === true ) { $audit['wiped_terms']++; }
			}
		}

		// 2. Restore terms (parents-first; sorted upstream by capture).
		$slug_to_new = array();
		if ( ! empty( $data['terms'] ) && is_array( $data['terms'] ) ) {
			foreach ( $data['terms'] as $taxonomy => $entries ) {
				if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
				foreach ( (array) $entries as $entry ) {
					$slug = isset( $entry['slug'] ) ? (string) $entry['slug'] : '';
					$name = isset( $entry['name'] ) ? (string) $entry['name'] : $slug;
					if ( $slug === '' ) { continue; }
					$existing_term = get_term_by( 'slug', $slug, $taxonomy );
					if ( $existing_term && ! is_wp_error( $existing_term ) ) {
						$slug_to_new[ $taxonomy . ':' . $slug ] = (int) $existing_term->term_id;
						continue;
					}
					$parent_new_id = 0;
					if ( ! empty( $entry['parent_slug'] ) && isset( $slug_to_new[ $taxonomy . ':' . $entry['parent_slug'] ] ) ) {
						$parent_new_id = (int) $slug_to_new[ $taxonomy . ':' . $entry['parent_slug'] ];
					}
					$result = wp_insert_term( $name, $taxonomy, array(
						'slug'        => $slug,
						'description' => isset( $entry['description'] ) ? (string) $entry['description'] : '',
						'parent'      => $parent_new_id,
					) );
					if ( ! is_wp_error( $result ) ) {
						$slug_to_new[ $taxonomy . ':' . $slug ] = (int) $result['term_id'];
						$audit['restored_terms']++;
					}
				}
			}
		}

		// 3. Restore Blueprint placeholder attachments before posts/options
		//    that may reference their original attachment IDs.
		if ( ! empty( $data['blueprint_media'] ) && is_array( $data['blueprint_media'] ) ) {
			foreach ( $data['blueprint_media'] as $entry ) {
				if ( self::restore_blueprint_media_attachment( $entry ) ) {
					$audit['restored_blueprint_media']++;
				}
			}
		}

		// 4. Restore posts. Pass 1 creates (preserves original ID via import_id);
		//    Pass 2 sets parent + meta + layouts + term assignments.
		if ( ! empty( $data['posts'] ) && is_array( $data['posts'] ) ) {
			foreach ( $data['posts'] as $entry ) {
				$args = self::build_post_insert_args( $entry );
				$result = wp_insert_post( $args, true );
				if ( ! is_wp_error( $result ) ) {
					$audit['restored_posts']++;
				}
			}
			foreach ( $data['posts'] as $entry ) {
				$post_id = (int) $entry['ID'];
				if ( ! get_post( $post_id ) ) { continue; }

				if ( ! empty( $entry['post_parent'] ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_parent' => (int) $entry['post_parent'] ) );
				}

				if ( ! empty( $entry['meta'] ) && is_array( $entry['meta'] ) ) {
					foreach ( $entry['meta'] as $mk => $mv ) {
						update_post_meta( $post_id, $mk, $mv );
					}
				}

				if ( ! empty( $entry['layouts'] ) && is_array( $entry['layouts'] ) ) {
					foreach ( $entry['layouts'] as $lk => $lv ) {
						update_post_meta( $post_id, $lk, wp_slash( (string) $lv ) );
					}
				}

				if ( ! empty( $entry['terms'] ) && is_array( $entry['terms'] ) ) {
					foreach ( $entry['terms'] as $tax => $slugs ) {
						if ( ! taxonomy_exists( $tax ) ) { continue; }
						$slugs = array_filter( (array) $slugs, 'is_string' );
						if ( ! empty( $slugs ) ) {
							wp_set_object_terms( $post_id, $slugs, $tax, false );
						}
					}
				}
			}
		}

		// 5. Restore options + theme_mods (overwrites whatever import wrote).
		if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
			foreach ( $data['options'] as $name => $value ) {
				update_option( $name, $value );
			}
		}
		if ( isset( $data['theme_mods'] ) && is_array( $data['theme_mods'] ) ) {
			update_option( 'theme_mods_' . wp_get_theme()->get_stylesheet(), $data['theme_mods'] );
		}

		flush_rewrite_rules( false );
		wp_cache_flush();

		return $audit;
	}

	private static function build_post_insert_args( array $entry ) {
		$args = array(
			'import_id'      => (int) $entry['ID'],
			'post_type'      => isset( $entry['post_type'] )      ? $entry['post_type']      : 'post',
			'post_status'    => isset( $entry['post_status'] )    ? $entry['post_status']    : 'publish',
			'post_title'     => isset( $entry['post_title'] )     ? $entry['post_title']     : '',
			'post_name'      => isset( $entry['post_name'] )      ? $entry['post_name']      : '',
			'post_content'   => isset( $entry['post_content'] )   ? $entry['post_content']   : '',
			'post_excerpt'   => isset( $entry['post_excerpt'] )   ? $entry['post_excerpt']   : '',
			'menu_order'     => isset( $entry['menu_order'] )     ? (int) $entry['menu_order'] : 0,
			'comment_status' => isset( $entry['comment_status'] ) ? $entry['comment_status'] : 'closed',
			'ping_status'    => isset( $entry['ping_status'] )    ? $entry['ping_status']    : 'closed',
		);
		if ( ! empty( $entry['post_date_gmt'] ) ) {
			$args['post_date_gmt'] = $entry['post_date_gmt'];
			$args['post_date']     = get_date_from_gmt( $entry['post_date_gmt'] );
		}
		return $args;
	}

	private static function capture_blueprint_media() {
		$attachments = get_posts( array(
			'post_type'        => 'attachment',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
			'meta_key'         => LayBlueprint_RemoteAttachmentFilters::META_KEY,
			'fields'           => 'ids',
		) );

		$out = array();
		foreach ( (array) $attachments as $attachment_id ) {
			$post = get_post( (int) $attachment_id );
			if ( ! $post ) { continue; }
			$remote = get_post_meta( $post->ID, LayBlueprint_RemoteAttachmentFilters::META_KEY, true );
			if ( ! $remote ) { continue; }
			$out[] = array(
				'ID'             => (int) $post->ID,
				'post_title'     => $post->post_title,
				'post_excerpt'   => $post->post_excerpt,
				'post_content'   => $post->post_content,
				'post_mime_type' => $post->post_mime_type,
				'post_date_gmt'  => $post->post_date_gmt,
				'remote_url'     => LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote ),
				'metadata'       => wp_get_attachment_metadata( $post->ID ),
				'alt'            => get_post_meta( $post->ID, '_wp_attachment_image_alt', true ),
			);
		}
		return $out;
	}

	private static function restore_blueprint_media_attachment( array $entry ) {
		$old_id = (int) ( $entry['ID'] ?? 0 );
		$remote = isset( $entry['remote_url'] ) ? LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $entry['remote_url'] ) : '';
		if ( $old_id <= 0 || $remote === '' ) {
			return 0;
		}

		$args = array(
			'import_id'      => $old_id,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_title'     => isset( $entry['post_title'] ) ? (string) $entry['post_title'] : 'Blueprint Placeholder',
			'post_excerpt'   => isset( $entry['post_excerpt'] ) ? (string) $entry['post_excerpt'] : '',
			'post_content'   => isset( $entry['post_content'] ) ? (string) $entry['post_content'] : '',
			'post_mime_type' => isset( $entry['post_mime_type'] ) ? (string) $entry['post_mime_type'] : 'image/jpeg',
			'guid'           => $remote,
		);
		if ( ! empty( $entry['post_date_gmt'] ) ) {
			$args['post_date_gmt'] = $entry['post_date_gmt'];
			$args['post_date']     = get_date_from_gmt( $entry['post_date_gmt'] );
		}

		$attachment_id = wp_insert_attachment( $args, false, 0 );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		update_post_meta( $attachment_id, LayBlueprint_RemoteAttachmentFilters::META_KEY, $remote );
		update_post_meta( $attachment_id, LayBlueprint_MediaImporter::META_MARKER, LayBlueprint_MediaImporter::MARKER_REMOTE_PLACEHOLDER );
		if ( isset( $entry['metadata'] ) && is_array( $entry['metadata'] ) ) {
			wp_update_attachment_metadata( $attachment_id, $entry['metadata'] );
		}
		if ( isset( $entry['alt'] ) && $entry['alt'] !== '' ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $entry['alt'] );
		}

		return (int) $attachment_id;
	}

	private static function delete_current_blueprint_media() {
		$attachments = get_posts( array(
			'post_type'        => 'attachment',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_key'         => LayBlueprint_RemoteAttachmentFilters::META_KEY,
		) );
		$deleted = 0;
		foreach ( (array) $attachments as $attachment_id ) {
			if ( wp_delete_attachment( (int) $attachment_id, true ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}

	private static function capture_posts() {
		$out = array();
		foreach ( self::POST_TYPES_IN_SCOPE as $post_type ) {
			$posts = get_posts( array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			) );
			foreach ( (array) $posts as $post ) {
				$out[] = self::serialize_post( $post );
			}
		}
		return $out;
	}

	private static function serialize_post( WP_Post $post ) {
		$meta_raw = get_post_meta( $post->ID );
		$meta     = array();
		$layouts  = array();
		foreach ( (array) $meta_raw as $key => $values ) {
			$decoded = array_map( 'maybe_unserialize', (array) $values );
			$value   = count( $decoded ) === 1 ? $decoded[0] : $decoded;
			if ( $key === '_gridder_json' || $key === '_phone_gridder_json' ) {
				$layouts[ $key ] = is_string( $value ) ? $value : (string) wp_json_encode( $value );
				continue;
			}
			if ( ! LayBlueprint_KnownKeys::should_export_post_meta( $key ) ) { continue; }
			$meta[ $key ] = $value;
		}

		$terms = array();
		foreach ( get_object_taxonomies( $post->post_type ) as $tax ) {
			$got = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'all' ) );
			if ( is_wp_error( $got ) || empty( $got ) ) { continue; }
			$terms[ $tax ] = array_values( array_map( function ( $t ) { return $t->slug; }, $got ) );
		}

		return array(
			'ID'             => (int) $post->ID,
			'post_type'      => $post->post_type,
			'post_status'    => $post->post_status,
			'post_title'     => $post->post_title,
			'post_name'      => $post->post_name,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_parent'    => (int) $post->post_parent,
			'menu_order'     => (int) $post->menu_order,
			'post_date_gmt'  => $post->post_date_gmt,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'meta'           => $meta,
			'layouts'        => $layouts,
			'terms'          => $terms,
		);
	}

	private static function capture_terms() {
		$out = array();
		foreach ( self::TAXONOMIES_IN_SCOPE as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
			$id_to_slug = array();
			foreach ( $terms as $t ) { $id_to_slug[ (int) $t->term_id ] = $t->slug; }
			$entries = array();
			foreach ( $terms as $t ) {
				$entries[] = array(
					'slug'        => $t->slug,
					'name'        => $t->name,
					'description' => $t->description,
					'parent_slug' => ( $t->parent && isset( $id_to_slug[ (int) $t->parent ] ) ) ? $id_to_slug[ (int) $t->parent ] : null,
				);
			}
			usort( $entries, function ( $a, $b ) {
				if ( $a['parent_slug'] === null && $b['parent_slug'] !== null ) { return -1; }
				if ( $a['parent_slug'] !== null && $b['parent_slug'] === null ) { return 1; }
				return strcmp( $a['slug'], $b['slug'] );
			} );
			$out[ $taxonomy ] = $entries;
		}
		return $out;
	}

	private static function ensure_backup_dir() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads_dir_error', $uploads['error'] );
		}
		$dir = trailingslashit( $uploads['basedir'] ) . self::SUBDIR;
		if ( ! file_exists( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'snapshot_mkdir', 'Could not create backup directory: ' . $dir );
		}
		$index    = trailingslashit( $dir ) . 'index.php';
		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $index ) )    { @file_put_contents( $index,    "<?php // Silence is golden.\n" ); }
		if ( ! file_exists( $htaccess ) ) { @file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" ); }
		return $dir;
	}
}
