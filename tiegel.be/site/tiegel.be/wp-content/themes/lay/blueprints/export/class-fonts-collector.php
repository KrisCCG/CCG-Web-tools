<?php
/**
 * Lay Blueprints — Fonts Collector.
 *
 * FontManager stores its config under the option `fontmanager_json` as a
 * JSON string of webfont entries. Each entry has either:
 *   - { type: 'file', filename, fontname, filetype: 'woff'|'woff2'|'truetype',
 *       url, id }   ← a custom upload, attachment-backed
 *   - { type: 'link', cssUrl, fontname, ... }   ← Google Fonts / external
 *   - { type: 'script', html, fontname, ... }   ← arbitrary @font-face HTML
 *
 * For 'file' entries we ship the actual font file inside the ZIP under
 * fonts/{slug}.{ext} and record a fonts_manifest entry.
 *
 * For 'link' / 'script' entries the URL or HTML is portable already — no
 * file shipping needed; just include the entry.
 *
 * Returns the manifest entries plus a list of {zip_path → fs_path} pairs
 * the ZipWriter should add to the archive.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_FontsCollector {

	const ZIP_DIR = 'fonts/';

	/** @var LayBlueprint_MediaResolver */
	private $resolver;

	public function __construct( LayBlueprint_MediaResolver $resolver ) {
		$this->resolver = $resolver;
	}

	/**
	 * @return array{ values: array, files: array<string,string>, audit: array }
	 */
	public function collect() {
		$json    = get_option( 'fontmanager_json', '' );
		$entries = $this->safe_decode( $json );

		$out_entries = array();
		$out_files   = array(); // zip_path → absolute fs path
		$audit       = array(
			'count'         => 0,
			'file_entries'  => 0,
			'link_entries'  => 0,
			'script_entries' => 0,
			'missing_files' => 0,
		);

		foreach ( (array) $entries as $idx => $entry ) {
			if ( ! is_array( $entry ) ) { continue; }

			$kind = self::classify_entry( $entry );
			$audit[ $kind . '_entries' ]++;

			if ( $kind === 'file' ) {
				$record = $this->process_file_entry( $entry, $out_files, $audit );
			} else {
				// link / script: keep as-is. Names get exported verbatim.
				$record = array(
					'kind'    => $kind,
					'entry'   => $entry,
				);
			}
			$out_entries[] = $record;
			$audit['count']++;
		}

		return array(
			'values' => $out_entries,
			'files'  => $out_files,
			'audit'  => $audit,
		);
	}

	private function process_file_entry( array $entry, array &$out_files, array &$audit ) {
		$attid    = isset( $entry['id'] )       ? (int) $entry['id']        : 0;
		$filename = isset( $entry['filename'] ) ? (string) $entry['filename'] : '';
		$url      = isset( $entry['url'] )      ? (string) $entry['url']     : '';

		$abs_path = $attid > 0 ? get_attached_file( $attid ) : '';
		if ( ! $abs_path && $url !== '' ) {
			// Fallback: derive path from URL inside the uploads dir.
			$abs_path = self::url_to_local_path( $url );
		}

		if ( ! $abs_path || ! is_readable( $abs_path ) ) {
			$audit['missing_files']++;
			return array(
				'kind'  => 'file',
				'entry' => $entry,
				'note'  => 'file_not_found',
			);
		}

		// Stable name inside the ZIP.
		$basename = $filename !== ''
			? sanitize_file_name( $filename )
			: ( $attid . '-' . sanitize_file_name( basename( $abs_path ) ) );
		$zip_rel  = self::ZIP_DIR . $basename;

		// Avoid collisions if two fonts have the same filename.
		$candidate = $zip_rel;
		$n = 1;
		while ( isset( $out_files[ $candidate ] ) ) {
			$candidate = self::ZIP_DIR . pathinfo( $basename, PATHINFO_FILENAME ) . '-' . $n . '.' . pathinfo( $basename, PATHINFO_EXTENSION );
			$n++;
		}
		$zip_rel = $candidate;
		$out_files[ $zip_rel ] = $abs_path;

		// Register the attachment so the manifest also has an entry — the
		// importer sideloads from the zip path, but tracks ID mapping there.
		if ( $attid > 0 ) {
			$this->resolver->register( $attid, 'font', 'fontmanager:' . $basename );
		}

		return array(
			'kind'         => 'file',
			'old_attid'    => $attid,
			'fontname'     => isset( $entry['fontname'] ) ? (string) $entry['fontname'] : '',
			'filetype'     => isset( $entry['filetype'] ) ? (string) $entry['filetype'] : '',
			'filename'     => $filename,
			'zip_path'     => $zip_rel,
			'entry'        => $entry,
		);
	}

	private static function classify_entry( array $entry ) {
		// FontManager doesn't always emit a 'type' field — infer from shape.
		if ( isset( $entry['type'] ) && in_array( $entry['type'], array( 'file', 'link', 'script' ), true ) ) {
			return $entry['type'];
		}
		if ( isset( $entry['html'] ) && $entry['html'] !== '' )       { return 'script'; }
		if ( isset( $entry['cssUrl'] ) && $entry['cssUrl'] !== '' )   { return 'link'; }
		if ( isset( $entry['url'] ) && $entry['url'] !== '' )         { return 'file'; }
		if ( isset( $entry['filename'] ) && $entry['filename'] !== '' ) { return 'file'; }
		return 'link';
	}

	private function safe_decode( $json ) {
		if ( ! is_string( $json ) || $json === '' ) { return array(); }
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = json_decode( wp_unslash( $json ), true );
		}
		return is_array( $decoded ) ? $decoded : array();
	}

	private static function url_to_local_path( $url ) {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) { return ''; }
		$baseurl = rtrim( $uploads['baseurl'], '/' );
		$basedir = rtrim( $uploads['basedir'], '/' );
		// Match http and https.
		$baseurl_no_scheme = preg_replace( '#^https?://#i', '', $baseurl );
		$url_no_scheme     = preg_replace( '#^https?://#i', '', $url );
		if ( strpos( $url_no_scheme, $baseurl_no_scheme ) !== 0 ) { return ''; }
		$relative = substr( $url_no_scheme, strlen( $baseurl_no_scheme ) );
		return $basedir . $relative;
	}
}
