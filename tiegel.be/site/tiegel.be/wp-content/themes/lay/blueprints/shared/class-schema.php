<?php
/**
 * Lay Blueprints — Schema constants and helpers.
 *
 * The schema_version embedded in every exported manifest. Bumped only on
 * non-backwards-compatible structural changes. Forward-compatible additions
 * (new optional keys) don't bump.
 *
 * Migration chain for old blueprints lives in shared/schema/ (added when
 * v1 → v2 happens).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_Schema {

	const SCHEMA_VERSION    = 1;
	const FILE_EXTENSION    = 'layblueprint.zip';
	const ENTRY_BLUEPRINT   = 'blueprint.json';
	const ENTRY_MANIFEST    = 'manifest.json';
	const ENTRY_README      = 'README.txt';
	const ENTRY_FONTS_DIR   = 'fonts/';

	/**
	 * Build the empty top-level structure that all collectors fill in.
	 */
	public static function empty_blueprint() {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'theme_mods'     => array(),
			'options'        => array(),
			'menus'          => array(),
			'content'        => array(),     // Phase 3 fills this
			'media_manifest' => array(),     // Phase 3 fills this
			'fonts_manifest' => array(),     // Phase 3 fills this
		);
	}

	public static function lay_theme_version() {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return null;
		}
		$theme = wp_get_theme();
		return $theme ? $theme->get( 'Version' ) : null;
	}

	public static function build_filename( $site_name ) {
		$slug = sanitize_title( $site_name ?: 'site' );
		if ( $slug === '' ) {
			$slug = 'site';
		}
		$date = gmdate( 'Y-m-d' );
		return $slug . '-' . $date . '.' . self::FILE_EXTENSION;
	}
}
