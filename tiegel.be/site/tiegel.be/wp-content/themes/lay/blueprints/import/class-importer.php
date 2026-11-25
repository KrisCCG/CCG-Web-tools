<?php
/**
 * Lay Blueprints — Importer (orchestrator).
 *
 * Phases (destructive default):
 *   1. Snapshot current state (for rollback)
 *   2. Cleanup: delete in-scope posts and non-default terms
 *   3. Import terms      → builds TermIDMap
 *   4. Import media      → builds attachment IDMap (sideloads placeholders)
 *   5. Import fonts      → adds to attachment IDMap (extracts files from ZIP)
 *   6. Install options + theme_mods (rewrites term-id keys + layout attids)
 *   7. Install menus
 *   8. Install content   → posts + meta + layouts (with HTML rewrites)
 *   9. flush_rewrite_rules
 *
 * Each step updates JobState progress + step label.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_Importer {

	const POST_TYPES_TO_DELETE = array( 'post', 'page', 'lay_news', 'lay_template' );
	const TAXONOMIES_TO_RESET  = array( 'category', 'post_tag', 'lay_news_category' );
	const LAST_RESTORE_POINT_OPTION = 'lay_blueprint_last_restore_point';

	public static function apply( $job_id ) {
		// Imports with hundreds of media sideloads exceed default PHP timeouts.
		// We're inside an admin REST request, no user-facing risk.
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '512M' );

		$state = LayBlueprint_JobState::get( $job_id );
		if ( ! $state ) {
			return new WP_Error( 'job_not_found', 'Unknown import job.' );
		}
		$staged_path = isset( $state['file_path'] ) ? $state['file_path'] : null;
		if ( ! $staged_path || ! is_readable( $staged_path ) ) {
			return new WP_Error( 'staged_missing', 'Staged blueprint file is gone; please re-upload.' );
		}

		$reader = LayBlueprint_ZipReader::open( $staged_path );
		if ( is_wp_error( $reader ) ) { return $reader; }

		$blueprint = $reader->blueprint();
		if ( is_wp_error( $blueprint ) ) {
			$reader->close();
			return $blueprint;
		}

		try {
			LayBlueprint_JobState::update( $job_id, array( 'status' => 'running', 'step' => 'Capturing snapshot', 'progress' => 5 ) );
			$snapshot = LayBlueprint_Snapshot::capture( $job_id );
			if ( is_wp_error( $snapshot ) ) {
				$reader->close();
				return LayBlueprint_JobState::fail( $job_id, $snapshot->get_error_message() );
			}
			$restore_point = self::remember_restore_point( $job_id, $snapshot, $blueprint );
			LayBlueprint_JobState::update( $job_id, array( 'meta' => array(
				'snapshot_path'  => $snapshot['path'],
				'restore_point'  => $restore_point,
			) ) );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Wiping existing content', 'progress' => 10 ) );
			$cleanup_audit = self::destructive_cleanup();

			$attachment_map = new LayBlueprint_IdMap( 'attachment' );
			$term_map       = new LayBlueprint_IdMap( 'term' );
			$post_map       = new LayBlueprint_IdMap( 'post' );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Importing categories', 'progress' => 20 ) );
			$term_importer = new LayBlueprint_TermImporter( $term_map );
			$term_audit    = $term_importer->import( isset( $blueprint['terms'] ) && is_array( $blueprint['terms'] ) ? $blueprint['terms'] : array() );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Creating media references', 'progress' => 35 ) );
			$media_importer = new LayBlueprint_MediaImporter( $attachment_map, $reader );
			$media_audit    = $media_importer->import(
				isset( $blueprint['media_manifest'] ) && is_array( $blueprint['media_manifest'] ) ? $blueprint['media_manifest'] : array(),
				function ( $done, $total ) use ( $job_id ) {
					if ( $total > 0 ) {
						$progress = 35 + (int) ( ( $done / $total ) * 25 ); // 35 → 60
						LayBlueprint_JobState::update( $job_id, array( 'progress' => $progress ) );
					}
				}
			);

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Importing fonts', 'progress' => 62 ) );
			$fonts_importer = new LayBlueprint_FontsImporter( $attachment_map, $reader );
			$fonts_audit    = $fonts_importer->import( isset( $blueprint['fonts_manifest'] ) && is_array( $blueprint['fonts_manifest'] ) ? $blueprint['fonts_manifest'] : array() );

			// Content first so the Posts IDMap is populated before options
			// that reference post ids (page_on_front, lay_*_footer, etc.).
			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Importing posts + layouts', 'progress' => 68 ) );
			$content_installer = new LayBlueprint_ContentInstaller( $attachment_map, $post_map );
			$content_audit     = $content_installer->install(
				isset( $blueprint['content'] ) && is_array( $blueprint['content'] ) ? $blueprint['content'] : array(),
				function ( $done, $total, $phase ) use ( $job_id ) {
					if ( $total > 0 ) {
						$base     = $phase === 'create' ? 68 : 80;
						$progress = $base + (int) ( ( $done / $total ) * 6 );
						LayBlueprint_JobState::update( $job_id, array( 'progress' => $progress ) );
					}
				}
			);

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Writing options + theme mods', 'progress' => 88 ) );
			$options_installer = new LayBlueprint_OptionsInstaller( $attachment_map, $term_map, $post_map );
			$options_audit     = $options_installer->install( isset( $blueprint['options'] ) && is_array( $blueprint['options'] ) ? $blueprint['options'] : array() );
			$thememods_audit   = $options_installer->install_theme_mods( isset( $blueprint['theme_mods'] ) && is_array( $blueprint['theme_mods'] ) ? $blueprint['theme_mods'] : array() );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Installing menus', 'progress' => 95 ) );
			$menus_installer  = new LayBlueprint_MenusInstaller();
			$menus_in_blueprint = isset( $blueprint['menus'] ) && is_array( $blueprint['menus'] ) ? $blueprint['menus'] : array();
			$nav_locations    = isset( $blueprint['nav_menu_locations'] ) && is_array( $blueprint['nav_menu_locations'] )
				? $blueprint['nav_menu_locations']
				: array();
			$menus_audit      = $menus_installer->install( $menus_in_blueprint, $nav_locations );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Finalising', 'progress' => 98 ) );
			flush_rewrite_rules( false );
			wp_cache_flush();

			$reader->close();

			$report = array(
				'cleanup'   => $cleanup_audit,
				'terms'     => $term_audit,
				'media'     => $media_audit,
				'fonts'     => $fonts_audit,
				'options'   => $options_audit,
				'theme_mods'=> $thememods_audit,
				'content'   => $content_audit,
				'menus'     => $menus_audit,
				'attachments_mapped' => $attachment_map->size(),
				'attachments_missing'=> $attachment_map->missing_count(),
				'terms_mapped'       => $term_map->size(),
				'posts_mapped'       => $post_map->size(),
			);

			LayBlueprint_JobState::update( $job_id, array(
				'status'   => 'complete',
				'progress' => 100,
				'step'     => 'Done',
				'meta'     => array( 'report' => $report ),
			) );
			return LayBlueprint_JobState::get( $job_id );
		} catch ( \Throwable $e ) {
			$reader->close();
			return LayBlueprint_JobState::fail( $job_id, $e->getMessage() );
		}
	}

	public static function rollback( $job_id ) {
		$state = LayBlueprint_JobState::get( $job_id );
		$restore_point = self::latest_restore_point();
		$snapshot_path = null;
		if ( $state && ! empty( $state['meta']['snapshot_path'] ) ) {
			$snapshot_path = $state['meta']['snapshot_path'];
		} elseif ( $restore_point && ! empty( $restore_point['snapshot_path'] ) ) {
			$snapshot_path = $restore_point['snapshot_path'];
		}
		if ( ! $snapshot_path ) {
			return new WP_Error( 'no_snapshot', 'No snapshot recorded for this job.' );
		}
		$result = LayBlueprint_Snapshot::restore_full( $snapshot_path );
		if ( is_wp_error( $result ) ) { return $result; }
		delete_option( self::LAST_RESTORE_POINT_OPTION );
		return array( 'restored' => true, 'snapshot_path' => $snapshot_path, 'audit' => $result );
	}

	public static function latest_restore_point() {
		$point = get_option( self::LAST_RESTORE_POINT_OPTION, null );
		if ( ! is_array( $point ) || empty( $point['snapshot_path'] ) || ! is_readable( $point['snapshot_path'] ) ) {
			return null;
		}
		return array(
			'job_id'       => isset( $point['job_id'] ) ? (string) $point['job_id'] : '',
			'taken_at'     => isset( $point['taken_at'] ) ? (int) $point['taken_at'] : 0,
			'blueprint'    => isset( $point['blueprint'] ) ? (string) $point['blueprint'] : '',
			'snapshot_path'=> (string) $point['snapshot_path'],
		);
	}

	public static function client_restore_point() {
		$point = self::latest_restore_point();
		if ( ! $point ) {
			return null;
		}
		unset( $point['snapshot_path'] );
		return $point;
	}

	private static function remember_restore_point( $job_id, array $snapshot, array $blueprint ) {
		$name = '';
		if ( ! empty( $blueprint['manifest']['site']['name'] ) ) {
			$name = (string) $blueprint['manifest']['site']['name'];
		} elseif ( ! empty( $blueprint['site']['name'] ) ) {
			$name = (string) $blueprint['site']['name'];
		}
		$point = array(
			'job_id'        => (string) $job_id,
			'taken_at'      => isset( $snapshot['taken_at'] ) ? (int) $snapshot['taken_at'] : time(),
			'blueprint'     => $name,
			'snapshot_path' => isset( $snapshot['path'] ) ? (string) $snapshot['path'] : '',
		);
		update_option( self::LAST_RESTORE_POINT_OPTION, $point, false );
		return self::client_restore_point();
	}

	private static function destructive_cleanup() {
		$audit = array( 'posts_deleted' => 0, 'terms_deleted' => 0, 'blueprint_media_deleted' => 0 );

		// Remote placeholder attachments from previous Blueprint imports have
		// no local files and are safe to remove before the next destructive
		// import. This keeps the Media Library from filling up while testing
		// several Blueprints in a row.
		$audit['blueprint_media_deleted'] = self::delete_previous_blueprint_media();

		// Delete posts of in-scope types.
		foreach ( self::POST_TYPES_TO_DELETE as $post_type ) {
			$posts = get_posts( array(
				'post_type'   => $post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			) );
			foreach ( (array) $posts as $pid ) {
				if ( wp_delete_post( $pid, true ) ) {
					$audit['posts_deleted']++;
				}
			}
		}

		// Delete non-default terms in in-scope taxonomies.
		$default_cat = (int) get_option( 'default_category' );
		foreach ( self::TAXONOMIES_TO_RESET as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			) );
			if ( is_wp_error( $terms ) ) { continue; }
			foreach ( (array) $terms as $tid ) {
				if ( $taxonomy === 'category' && (int) $tid === $default_cat ) { continue; }
				if ( wp_delete_term( $tid, $taxonomy ) === true ) {
					$audit['terms_deleted']++;
				}
			}
		}

		return $audit;
	}

	private static function delete_previous_blueprint_media() {
		$attachments = get_posts( array(
			'post_type'        => 'attachment',
			'post_status'      => 'any',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_query'       => array(
				'relation' => 'OR',
				array(
					'key'     => LayBlueprint_MediaImporter::META_MARKER,
					'value'   => LayBlueprint_MediaImporter::MARKER_REMOTE_PLACEHOLDER,
					'compare' => '=',
				),
				array(
					'key'     => LayBlueprint_RemoteAttachmentFilters::META_KEY,
					'compare' => 'EXISTS',
				),
			),
		) );

		$deleted = 0;
		foreach ( (array) $attachments as $attachment_id ) {
			if ( wp_delete_attachment( (int) $attachment_id, true ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}
}
