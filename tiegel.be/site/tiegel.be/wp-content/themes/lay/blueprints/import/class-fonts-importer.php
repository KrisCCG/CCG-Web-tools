<?php
/**
 * Lay Blueprints — Fonts Importer.
 *
 * For each fonts_manifest entry of kind='file': extract the font from the
 * ZIP into the uploads dir and create an attachment so FontManager renders
 * @font-face URLs against the new attachment ID.
 *
 * link/script entries don't ship files; they're written verbatim.
 *
 * Reconstructs fontmanager_json with new attachment IDs in place of old.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_FontsImporter {

	/** @var LayBlueprint_IdMap */
	private $id_map;

	/** @var LayBlueprint_ZipReader */
	private $zip;

	public function __construct( LayBlueprint_IdMap $id_map, LayBlueprint_ZipReader $zip ) {
		$this->id_map = $id_map;
		$this->zip    = $zip;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * @return array audit { count, files_imported, link_entries, script_entries, errors }
	 */
	public function import( array $fonts_manifest ) {
		$audit = array(
			'count'           => count( $fonts_manifest ),
			'files_imported'  => 0,
			'link_entries'    => 0,
			'script_entries'  => 0,
			'errors'          => array(),
		);

		$rebuilt = array();
		$tmp_dir = self::ensure_tmp_dir();

		foreach ( $fonts_manifest as $entry ) {
			if ( ! is_array( $entry ) ) { continue; }
			$kind = isset( $entry['kind'] ) ? (string) $entry['kind'] : 'file';

			if ( $kind === 'link' || $kind === 'script' ) {
				$audit[ $kind . '_entries' ]++;
				$rebuilt[] = isset( $entry['entry'] ) ? $entry['entry'] : array();
				continue;
			}

			if ( $kind !== 'file' ) {
				$audit['errors'][] = 'Unknown font entry kind: ' . $kind;
				continue;
			}

			$zip_path = isset( $entry['zip_path'] ) ? (string) $entry['zip_path'] : '';
			if ( $zip_path === '' ) {
				$audit['errors'][] = 'File font entry missing zip_path';
				continue;
			}

			$extracted = $tmp_dir ? $this->zip->extract_to( $zip_path, $tmp_dir ) : new WP_Error( 'no_tmp', 'No tmp dir' );
			if ( is_wp_error( $extracted ) ) {
				$audit['errors'][] = 'Extract failed for ' . $zip_path . ': ' . $extracted->get_error_message();
				continue;
			}

			$new_attachment_id = self::create_attachment( $extracted );
			if ( is_wp_error( $new_attachment_id ) ) {
				$audit['errors'][] = 'Attachment creation failed for ' . $zip_path . ': ' . $new_attachment_id->get_error_message();
				@unlink( $extracted );
				continue;
			}

			// Map the old attachment ID (may be 0/missing in older exports).
			if ( ! empty( $entry['old_attid'] ) && ! $this->id_map->has( (int) $entry['old_attid'] ) ) {
				$this->id_map->set( (int) $entry['old_attid'], $new_attachment_id );
			}

			// Reconstruct the fontmanager entry with the new ID + URL.
			$base   = isset( $entry['entry'] ) && is_array( $entry['entry'] ) ? $entry['entry'] : array();
			$base['id']  = $new_attachment_id;
			$base['url'] = wp_get_attachment_url( $new_attachment_id );
			if ( ! empty( $entry['fontname'] ) )  { $base['fontname']  = $entry['fontname']; }
			if ( ! empty( $entry['filetype'] ) )  { $base['filetype']  = $entry['filetype']; }
			if ( ! empty( $entry['filename'] ) )  { $base['filename']  = $entry['filename']; }
			$rebuilt[] = $base;

			$audit['files_imported']++;
		}

		// Write the new fontmanager_json.
		update_option( 'fontmanager_json', wp_json_encode( $rebuilt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );

		return $audit;
	}

	private static function create_attachment( $file_path ) {
		// Ensure file is inside uploads (move it from tmp).
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads_dir_error', $uploads['error'] );
		}
		$target_dir = trailingslashit( $uploads['path'] );
		if ( ! file_exists( $target_dir ) && ! wp_mkdir_p( $target_dir ) ) {
			return new WP_Error( 'mkdir_failed', 'Could not create uploads dir: ' . $target_dir );
		}
		$basename = wp_unique_filename( $target_dir, basename( $file_path ) );
		$dest     = $target_dir . $basename;
		if ( ! @rename( $file_path, $dest ) ) {
			// Fall back to copy + remove.
			if ( ! @copy( $file_path, $dest ) ) {
				return new WP_Error( 'move_failed', 'Could not move font file to uploads.' );
			}
			@unlink( $file_path );
		}

		$attachment = array(
			'guid'           => trailingslashit( $uploads['url'] ) . $basename,
			'post_mime_type' => self::guess_mime( $dest ),
			'post_title'     => sanitize_text_field( pathinfo( $basename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $dest, 0 );
		if ( is_wp_error( $attachment_id ) ) { return $attachment_id; }

		// Generate metadata (font files don't have meaningful metadata but
		// WP expects some data to be set).
		$meta = wp_generate_attachment_metadata( $attachment_id, $dest );
		wp_update_attachment_metadata( $attachment_id, $meta );

		return $attachment_id;
	}

	private static function guess_mime( $path ) {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		switch ( $ext ) {
			case 'woff':  return 'font/woff';
			case 'woff2': return 'font/woff2';
			case 'ttf':   return 'font/ttf';
			case 'otf':   return 'font/otf';
			case 'eot':   return 'application/vnd.ms-fontobject';
		}
		return 'application/octet-stream';
	}

	private static function ensure_tmp_dir() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) { return null; }
		$dir = trailingslashit( $uploads['basedir'] ) . 'lay-blueprints-tmp';
		if ( ! file_exists( $dir ) && ! wp_mkdir_p( $dir ) ) { return null; }
		return $dir;
	}
}
