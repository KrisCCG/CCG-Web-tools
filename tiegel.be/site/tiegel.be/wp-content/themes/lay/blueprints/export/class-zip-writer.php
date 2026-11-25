<?php
/**
 * Lay Blueprints — ZIP Writer.
 *
 * Builds the .layblueprint.zip file in wp-content/uploads/lay-blueprints-tmp/.
 * Contains:
 *   manifest.json   metadata, compat block, audit list
 *   blueprint.json  the actual data payload
 *   README.txt      human-readable info for the recipient
 *   fonts/...       (added in Phase 3 when fonts are in scope)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ZipWriter {

	const TMP_SUBDIR = 'lay-blueprints-tmp';

	/**
	 * Write a ZIP file. Returns the absolute path on disk on success or a
	 * WP_Error on failure.
	 *
	 * @param string $job_id    Used as the on-disk filename stem.
	 * @param string $filename  User-facing filename (e.g. mysite-2026-04-25.layblueprint.zip).
	 * @param array  $manifest
	 * @param array  $blueprint
	 * @param string $readme_text
	 * @param array<string,string> $extra_files  Map of zip_relative_path → absolute_fs_path. Added verbatim.
	 * @return string|WP_Error
	 */
	public static function write( $job_id, $filename, array $manifest, array $blueprint, $readme_text, array $extra_files = array() ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'no_ziparchive', 'PHP ZipArchive extension is not available on this server.' );
		}

		$tmp_dir = self::ensure_tmp_dir();
		if ( is_wp_error( $tmp_dir ) ) {
			return $tmp_dir;
		}

		$path = trailingslashit( $tmp_dir ) . $job_id . '.' . LayBlueprint_Schema::FILE_EXTENSION;

		// Stale leftover from a previous run — overwrite cleanly.
		if ( file_exists( $path ) ) {
			@unlink( $path );
		}

		$zip = new ZipArchive();
		$open = $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		if ( $open !== true ) {
			return new WP_Error( 'zip_open_failed', 'Could not open ZIP for writing: ' . $open );
		}

		$ok = true;
		$ok = $ok && $zip->addFromString( LayBlueprint_Schema::ENTRY_MANIFEST,  self::json_encode( $manifest )  );
		$ok = $ok && $zip->addFromString( LayBlueprint_Schema::ENTRY_BLUEPRINT, self::json_encode( $blueprint ) );
		$ok = $ok && $zip->addFromString( LayBlueprint_Schema::ENTRY_README,    (string) $readme_text );

		if ( ! $ok ) {
			$zip->close();
			@unlink( $path );
			return new WP_Error( 'zip_write_failed', 'Failed to write blueprint entries to ZIP.' );
		}

		foreach ( $extra_files as $zip_rel => $fs_path ) {
			if ( ! is_string( $zip_rel ) || $zip_rel === '' ) { continue; }
			if ( ! is_string( $fs_path ) || ! is_readable( $fs_path ) ) { continue; }
			// Reject path-traversal in zip_rel just in case a hostile filter rewrote it.
			if ( strpos( $zip_rel, '..' ) !== false || strpos( $zip_rel, "\0" ) !== false ) { continue; }
			$zip->addFile( $fs_path, $zip_rel );
		}

		$close = $zip->close();
		if ( ! $close ) {
			return new WP_Error( 'zip_close_failed', 'Failed to finalise ZIP.' );
		}

		// Tag with intended user-facing filename for the download endpoint.
		// We store it alongside the file in JobState, not in a sidecar.

		return $path;
	}

	private static function ensure_tmp_dir() {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads_dir_error', $uploads['error'] );
		}
		$dir = trailingslashit( $uploads['basedir'] ) . self::TMP_SUBDIR;
		if ( ! file_exists( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return new WP_Error( 'tmp_dir_mkdir', 'Could not create temp dir: ' . $dir );
			}
			// Block direct web access — placeholder index.
			@file_put_contents( trailingslashit( $dir ) . 'index.php', "<?php // Silence is golden.\n" );
			// Belt-and-suspenders: deny via .htaccess for Apache.
			@file_put_contents( trailingslashit( $dir ) . '.htaccess', "Order deny,allow\nDeny from all\n" );
		}
		return $dir;
	}

	private static function json_encode( array $payload ) {
		return wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Delete temp ZIPs older than $hours hours. Called opportunistically
	 * from Exporter::start(), no separate cron yet.
	 */
	public static function purge_old_files( $hours = 24 ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return;
		}
		$dir = trailingslashit( $uploads['basedir'] ) . self::TMP_SUBDIR;
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$cutoff = time() - ( $hours * HOUR_IN_SECONDS );
		$ext    = '.' . LayBlueprint_Schema::FILE_EXTENSION;
		foreach ( (array) glob( trailingslashit( $dir ) . '*' . $ext ) as $file ) {
			if ( @filemtime( $file ) < $cutoff ) {
				@unlink( $file );
			}
		}
	}
}
