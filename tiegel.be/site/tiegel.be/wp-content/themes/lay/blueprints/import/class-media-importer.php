<?php
/**
 * Lay Blueprints — Media Importer (remote-stub mode).
 *
 * Instead of downloading every placeholder image into the local media
 * library, we create a virtual WP attachment for each manifest entry —
 * a wp_posts row with NO file on disk and a `_lay_blueprint_remote_url`
 * meta pointing at the lay-blueprint-media service.
 *
 * Frontend rendering keeps working unchanged because the runtime filters
 * in LayBlueprint_RemoteAttachmentFilters intercept wp_get_attachment_url
 * and wp_get_attachment_image_src, returning the remote URL (with
 * dynamically-rewritten dims for sized lookups).
 *
 * Why this is better than sideloading:
 *  - Import is instant (no HTTP, no Sharp processing on the WP side)
 *  - No disk usage for placeholder images
 *  - Replacing a placeholder with a real image later is a normal WP
 *    "Replace media" action
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_MediaImporter {

	const META_MARKER = '_lay_blueprint_media';
	const MARKER_REMOTE_PLACEHOLDER = 'remote_placeholder';

	/** @var LayBlueprint_IdMap */
	private $id_map;

	/** @var LayBlueprint_ZipReader|null Used to pull real_file entries out of the ZIP. */
	private $zip = null;

	public function __construct( LayBlueprint_IdMap $id_map, $zip_reader = null ) {
		$this->id_map = $id_map;
		if ( $zip_reader instanceof LayBlueprint_ZipReader ) {
			$this->zip = $zip_reader;
		}
	}

	/**
	 * @param array  $manifest blueprint.media_manifest entries
	 * @param callable|null $on_progress Optional callback($n_done, $n_total)
	 * @return array audit { stubs_created, missing, failed, fonts_skipped, total }
	 */
	public function import( array $manifest, $on_progress = null ) {
		$total          = count( $manifest );
		$stubs_created  = 0;
		$missing        = 0;
		$failed         = 0;
		$fonts_skipped  = 0;
		$done           = 0;

		// Suspend expensive listeners that fire per wp_insert_attachment.
		// Without this, every stub triggers Lay's Layout_Cache_Invalidator,
		// post-meta update hooks, search re-indexers etc. — typically 200ms
		// per attachment. We snapshot the relevant action lists, blank them
		// for the duration of stub creation, and restore at the end.
		$suspend = ! defined( 'LAY_BLUEPRINTS_NO_HOOK_SUSPEND' );
		$saved_filters = array();
		if ( $suspend ) {
			global $wp_filter;
			foreach ( array( 'save_post', 'save_post_attachment', 'wp_insert_post', 'edit_post', 'attachment_updated', 'add_attachment', 'wp_after_insert_post', 'updated_post_meta', 'added_post_meta', 'transition_post_status' ) as $hook ) {
				if ( isset( $wp_filter[ $hook ] ) ) {
					$saved_filters[ $hook ] = $wp_filter[ $hook ];
					unset( $wp_filter[ $hook ] );
				}
			}
			wp_suspend_cache_invalidation( true );
			kses_remove_filters();
			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );
		}

		foreach ( $manifest as $entry ) {
			$done++;
			if ( ! is_array( $entry ) || empty( $entry['old_id'] ) ) { continue; }
			$old_id = (int) $entry['old_id'];

			// Fonts handled separately by FontsImporter (real files from ZIP).
			if ( isset( $entry['kind'] ) && $entry['kind'] === 'font' ) {
				$fonts_skipped++;
				continue;
			}

			// Real file entries (option assets like cursors): extract from
			// ZIP and create a true attachment so the cursor renders from
			// the local site, not the placeholder service.
			if ( isset( $entry['kind'] ) && $entry['kind'] === 'real_file' ) {
				$attachment_id = $this->import_real_file( $entry );
				if ( ! $attachment_id ) {
					$this->id_map->mark_missing( $old_id, 'real_file_import_failed' );
					$failed++;
				} else {
					$this->id_map->set( $old_id, $attachment_id );
					$stubs_created++;
				}
				if ( $on_progress ) { call_user_func( $on_progress, $done, $total ); }
				continue;
			}

			if ( ! isset( $entry['kind'] ) || $entry['kind'] === 'missing' || empty( $entry['placeholder_url'] ) ) {
				$this->id_map->mark_missing( $old_id, isset( $entry['note'] ) ? (string) $entry['note'] : 'no_placeholder' );
				$missing++;
				if ( $on_progress ) { call_user_func( $on_progress, $done, $total ); }
				continue;
			}

			$attachment_id = $this->create_remote_stub( $entry );
			if ( ! $attachment_id ) {
				$this->id_map->mark_missing( $old_id, 'stub_creation_failed' );
				$failed++;
				if ( $on_progress ) { call_user_func( $on_progress, $done, $total ); }
				continue;
			}

			$this->id_map->set( $old_id, $attachment_id );
			$stubs_created++;
			if ( $on_progress ) { call_user_func( $on_progress, $done, $total ); }
		}

		if ( $suspend ) {
			global $wp_filter;
			foreach ( $saved_filters as $hook => $callbacks ) {
				$wp_filter[ $hook ] = $callbacks;
			}
			wp_suspend_cache_invalidation( false );
			kses_init_filters();
			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );
		}

		return array(
			'total'          => $total,
			'stubs_created'  => $stubs_created,
			'missing'        => $missing,
			'failed'         => $failed,
			'fonts_skipped'  => $fonts_skipped,
		);
	}

	/**
	 * Extract a real_file entry from the ZIP (cursors / icons / functional
	 * assets) and create a real WP attachment with the file in uploads/.
	 * Skips the remote-stub filter so frontends serve the actual file.
	 */
	private function import_real_file( array $entry ) {
		if ( ! $this->zip ) { return 0; }
		$zip_path = isset( $entry['zip_path'] ) ? (string) $entry['zip_path'] : '';
		if ( $zip_path === '' ) { return 0; }

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) { return 0; }
		$tmp_dir = trailingslashit( $uploads['basedir'] ) . 'lay-blueprints-tmp';
		if ( ! file_exists( $tmp_dir ) && ! wp_mkdir_p( $tmp_dir ) ) { return 0; }

		$extracted = $this->zip->extract_to( $zip_path, $tmp_dir );
		if ( is_wp_error( $extracted ) ) { return 0; }

		$basename = wp_unique_filename( $uploads['path'], basename( $extracted ) );
		$dest     = trailingslashit( $uploads['path'] ) . $basename;
		if ( ! @rename( $extracted, $dest ) ) {
			if ( ! @copy( $extracted, $dest ) ) { return 0; }
			@unlink( $extracted );
		}

		$mime = isset( $entry['mime'] ) ? (string) $entry['mime'] : '';
		if ( $mime === '' ) {
			$ft   = wp_check_filetype( $dest );
			$mime = $ft['type'] ?: 'application/octet-stream';
		}

		$attachment_id = wp_insert_attachment( array(
			'guid'           => trailingslashit( $uploads['url'] ) . $basename,
			'post_mime_type' => $mime,
			'post_title'     => isset( $entry['title'] ) && $entry['title'] !== '' ? (string) $entry['title'] : pathinfo( $basename, PATHINFO_FILENAME ),
			'post_content'   => '',
			'post_excerpt'   => isset( $entry['caption'] ) ? (string) $entry['caption'] : '',
			'post_status'    => 'inherit',
		), $dest, 0 );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) { return 0; }

		$meta = wp_generate_attachment_metadata( $attachment_id, $dest );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		if ( ! empty( $entry['w'] ) ) {
			$meta['width'] = (int) $entry['w'];
		}
		if ( ! empty( $entry['h'] ) ) {
			$meta['height'] = (int) $entry['h'];
		}
		if ( empty( $meta['file'] ) ) {
			$meta['file'] = _wp_relative_upload_path( $dest );
		}
		if ( empty( $meta['mime-type'] ) ) {
			$meta['mime-type'] = $mime;
		}
		if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
			$meta['sizes'] = array();
		}
		wp_update_attachment_metadata( $attachment_id, $meta );

		if ( ! empty( $entry['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $entry['alt'] );
		}

		return (int) $attachment_id;
	}

	/**
	 * Create a virtual WP attachment that points at a remote URL via meta.
	 * No file is written to disk; wp_get_attachment_url filters return
	 * the remote URL at render time.
	 */
	private function create_remote_stub( array $entry ) {
		$remote = LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $entry['placeholder_url'] );
		$mime   = isset( $entry['mime'] ) ? (string) $entry['mime'] : 'image/jpeg';

		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_title'     => isset( $entry['title'] ) && $entry['title'] !== '' ? (string) $entry['title'] : 'Blueprint Placeholder',
			'post_excerpt'   => isset( $entry['caption'] ) ? (string) $entry['caption'] : '',
			'post_content'   => '',
			'post_mime_type' => $mime,
			'guid'           => $remote,
		);
		// wp_insert_attachment signature: (array $args, $file = false, $parent_post_id = 0)
		// Passing false for $file leaves _wp_attached_file unset, which is what we want.
		$attachment_id = wp_insert_attachment( $args, false, 0 );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		// Mark as a remote-stub: this is what the runtime filters look for.
		update_post_meta( $attachment_id, LayBlueprint_RemoteAttachmentFilters::META_KEY, $remote );
		update_post_meta( $attachment_id, self::META_MARKER, self::MARKER_REMOTE_PLACEHOLDER );

		// Synthetic metadata. Lay frontend reads width/height for srcset
		// fallback paths; WordPress and other plugins often check for a
		// thumbnail size before they render an image preview.
		$w = (int) ( $entry['w'] ?? 0 );
		$h = (int) ( $entry['h'] ?? 0 );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', array(
			'width'     => $w,
			'height'    => $h,
			'file'      => $remote,
			'mime-type' => $mime,
			'sizes'     => self::build_synthetic_sizes( $remote, $w, $h, $mime ),
		) );

		// Alt text from the source's media library.
		if ( ! empty( $entry['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', (string) $entry['alt'] );
		}

		return (int) $attachment_id;
	}

	private static function build_synthetic_sizes( $remote, $orig_w, $orig_h, $mime ) {
		$orig_w = (int) $orig_w;
		$orig_h = (int) $orig_h;
		if ( $orig_w <= 0 || $orig_h <= 0 ) {
			return array();
		}

		$sizes = array();
		foreach ( array( 'thumbnail', 'medium', 'medium_large', 'large' ) as $size ) {
			list( $tw, $th ) = LayBlueprint_RemoteAttachmentFilters::compute_target_dims( $size, $orig_w, $orig_h );
			$url = LayBlueprint_RemoteAttachmentFilters::rewrite_dims( $remote, $tw, $th );
			$sizes[ $size ] = array(
				'file'      => self::synthetic_size_filename( $url, $size, $tw, $th ),
				'width'     => $tw,
				'height'    => $th,
				'mime-type' => $mime,
				'url'       => $url,
			);
		}
		return $sizes;
	}

	private static function synthetic_size_filename( $url, $size, $w, $h ) {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$seed = 'remote';
		$pic_index = array_search( 'pic', $parts, true );
		if ( $pic_index !== false && isset( $parts[ $pic_index + 1 ] ) ) {
			$seed = sanitize_file_name( (string) $parts[ $pic_index + 1 ] );
		} else {
			$solid_index = array_search( 'solid', $parts, true );
			if ( $solid_index !== false && isset( $parts[ $solid_index + 1 ] ) ) {
				$seed = sanitize_file_name( (string) $parts[ $solid_index + 1 ] );
			}
		}
		return 'lay-blueprint-' . $seed . '-' . sanitize_key( $size ) . '-' . (int) $w . 'x' . (int) $h . '.webp';
	}
}
