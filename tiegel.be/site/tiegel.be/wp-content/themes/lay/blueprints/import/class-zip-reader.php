<?php
/**
 * Lay Blueprints — ZIP Reader.
 *
 * Opens a staged Lay Blueprint ZIP file, validates it has the expected entries,
 * extracts manifest.json + blueprint.json, and exposes font extraction by
 * zip-path. Used by Validator (preflight) and Importer (full apply).
 *
 * Stays open for the lifetime of the LayBlueprint_ZipReader instance — the
 * caller is responsible for calling close() when done. Uses PHP ZipArchive.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ZipReader {

	/** @var ZipArchive|null */
	private $zip = null;

	/** @var string */
	private $path;

	private function __construct( $path ) {
		$this->path = (string) $path;
	}

	/**
	 * Factory. Returns a reader or WP_Error if the file doesn't open / is
	 * missing required entries.
	 *
	 * @param string $path
	 * @return self|WP_Error
	 */
	public static function open( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'no_ziparchive', 'PHP ZipArchive extension is not available on this server.' );
		}
		if ( ! is_string( $path ) || ! is_readable( $path ) ) {
			return new WP_Error( 'zip_unreadable', 'Cannot read ZIP file: ' . (string) $path );
		}

		$reader      = new self( $path );
		$reader->zip = new ZipArchive();
		$result      = $reader->zip->open( $path, ZipArchive::RDONLY );
		if ( $result !== true ) {
			return new WP_Error( 'zip_open_failed', 'Failed to open Blueprint ZIP (zip code ' . (int) $result . ').' );
		}

		// Sanity-check required entries exist.
		foreach ( array( LayBlueprint_Schema::ENTRY_MANIFEST, LayBlueprint_Schema::ENTRY_BLUEPRINT ) as $required ) {
			if ( $reader->zip->locateName( $required ) === false ) {
				$reader->close();
				return new WP_Error( 'zip_missing_entry', 'Blueprint is missing ' . $required );
			}
		}

		return $reader;
	}

	public function manifest() {
		$json = $this->read_entry( LayBlueprint_Schema::ENTRY_MANIFEST );
		if ( is_wp_error( $json ) ) { return $json; }
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'manifest_invalid_json', 'manifest.json could not be parsed as JSON.' );
		}
		return $decoded;
	}

	public function blueprint() {
		$json = $this->read_entry( LayBlueprint_Schema::ENTRY_BLUEPRINT );
		if ( is_wp_error( $json ) ) { return $json; }
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'blueprint_invalid_json', 'blueprint.json could not be parsed as JSON.' );
		}
		return $decoded;
	}

	/**
	 * Extract a single entry to a temporary file on disk. Returns the path or
	 * WP_Error. Used by FontsImporter to materialise font files before
	 * sideloading them as attachments.
	 *
	 * @param string $zip_path
	 * @param string $target_dir absolute directory; must be writable
	 * @return string|WP_Error
	 */
	public function extract_to( $zip_path, $target_dir ) {
		if ( ! $this->zip ) {
			return new WP_Error( 'zip_closed', 'ZIP reader is closed.' );
		}
		if ( $this->zip->locateName( $zip_path ) === false ) {
			return new WP_Error( 'zip_entry_missing', 'Entry not found in ZIP: ' . $zip_path );
		}
		$basename = basename( $zip_path );
		$target   = trailingslashit( $target_dir ) . $basename;
		$stream   = $this->zip->getStream( $zip_path );
		if ( ! $stream ) {
			return new WP_Error( 'zip_stream_failed', 'Could not open stream for ' . $zip_path );
		}
		$out = fopen( $target, 'wb' );
		if ( ! $out ) {
			fclose( $stream );
			return new WP_Error( 'extract_open_failed', 'Could not open target for writing: ' . $target );
		}
		while ( ! feof( $stream ) ) {
			$chunk = fread( $stream, 65536 );
			if ( $chunk === false ) { break; }
			fwrite( $out, $chunk );
		}
		fclose( $stream );
		fclose( $out );
		return $target;
	}

	public function close() {
		if ( $this->zip ) {
			$this->zip->close();
			$this->zip = null;
		}
	}

	private function read_entry( $entry ) {
		if ( ! $this->zip ) {
			return new WP_Error( 'zip_closed', 'ZIP reader is closed.' );
		}
		$contents = $this->zip->getFromName( $entry );
		if ( $contents === false ) {
			return new WP_Error( 'zip_read_failed', 'Could not read entry ' . $entry );
		}
		return $contents;
	}
}
