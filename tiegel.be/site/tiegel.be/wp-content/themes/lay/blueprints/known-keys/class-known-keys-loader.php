<?php
/**
 * Lay Blueprints — Known Keys Loader
 *
 * Loads the build-time-generated lay-blueprint-known-keys.json, applies the
 * three filter hooks (additions, patterns, exclusions), and exposes the final
 * lists of option/theme-mod keys that the exporter is allowed to read.
 *
 * Filters (any plugin/module can hook in):
 *   apply_filters( 'lay_blueprint_export_options',     $static_keys )
 *   apply_filters( 'lay_blueprint_export_theme_mods',  $theme_mod_keys )
 *   apply_filters( 'lay_blueprint_export_post_meta',   $post_meta_keys )
 *   apply_filters( 'lay_blueprint_export_patterns',    $patterns )
 *   apply_filters( 'lay_blueprint_exclude_options',    $exclusion_patterns )
 *   apply_filters( 'lay_blueprint_exclude_post_meta',  $post_meta_exclusion_patterns )
 *
 * See readme.md in this dir for full developer docs.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_KnownKeys {

	const JSON_RELATIVE_PATH = '/blueprints/known-keys/lay-blueprint-known-keys.json';

	/**
	 * Default exclusion patterns. Matches options that are site-specific state
	 * and must NEVER end up in a portable Blueprint export:
	 *
	 *   - License keys, license-check timestamps
	 *   - Update-checker state (Kernl, PUC)
	 *   - Per-installation UI state (sidebar position, panel expanded/collapsed)
	 *   - Per-post UI state (active gridder page/project tracking by ID)
	 *   - "Have you seen this tip" flags
	 *   - One-time migration markers
	 *
	 * Plugins can extend or override via the `lay_blueprint_exclude_options`
	 * filter.
	 */
	const DEFAULT_EXCLUSIONS = array(
		// License + update tracking
		'#^lay_license_#',
		'#^lay_latest_version_kernl$#',
		'#^lay_do_plugin_update_#',
		'#^lay_plugin(s)?_update_#',
		'#^lay_version_for_updating_plugins$#',
		'#^lay_last_known_ssl_state$#',
		'#^external_updates-#',
		'#^external_theme_updates-#',
		'#^puc_external_updates_#',
		// Per-installation UI state
		'#^lay_sidebar_minimized$#',
		'#^lay_layers_visibility$#',
		'#^lay_guides_visibility$#',
		'#^lay_element_layers_window_#',
		'#^lay_table_column_widths$#',
		'#^lay_table_config$#',
		'#^lay_admin_color_changed$#',
		'#^lay_nightmode$#',
		'#^lay_ask_ai_position$#',
		'#^lay_shortcode_occurences$#',
		'#^lay_search_post_array$#',
		'#^lay-search-post-array$#',
		'#^lay_default_cat_was_renamed$#',
		'#^lay_project_link_cache_version$#',
		'#^lay_show_link_imagehover_setting$#',
		// Per-post / per-term active-page UI state
		'#^phone_gridder_active_(page|project|category)_\d+$#',
		// Tip-seen flags
		'#^lay-(page|project|search|affiliate)-#',
		// Help-box expansion state
		'#^expand-how_to_use_#',
		// theme_mods_* belongs to other themes (or is the active theme's blob,
		// which we already export separately via the ThemeModsCollector)
		'#^theme_mods_#',
		// Third-party plugin compat options that show up in source via dynamic update_option
		'#^scporder_#',
		// Per-category layout backup snapshots (transient state, not user data)
		'#^\d+_category_gridder_json_last$#',
		'#^\d+_phone_category_gridder_json_last$#',
	);

	/**
	 * WordPress core post-meta keys that the static analyzer can't discover
	 * (because they're never written by Lay code via update_post_meta — WP
	 * itself manages them) but which carry data we DO need in a Blueprint.
	 *
	 *   _thumbnail_id        Featured Image (used by Project Thumbnails etc.)
	 *   _wp_page_template    Page template selection
	 *   _menu_item_*         Menu item structure (handled by menu collector,
	 *                        but listed for completeness when a custom export
	 *                        also walks meta directly)
	 */
	const ALWAYS_INCLUDE_POST_META = array(
		'_thumbnail_id',
		'_wp_page_template',
	);

	/**
	 * Default post-meta exclusion patterns. Matches per-post state that's
	 * either WP-internal scaffolding or third-party plugin data we don't own.
	 */
	const DEFAULT_POST_META_EXCLUSIONS = array(
		// WP-internal
		'#^_edit_lock$#',
		'#^_edit_last$#',
		'#^_pingme$#',
		'#^_encloseme$#',
		'#^_trash_meta_#',
		// Lay's own transient layout state
		'#^_gridder_json_(last|preview)$#',
		'#^_phone_gridder_json_(last|preview)$#',
		// Third-party SEO / page builders / WPML
		'#^_yoast_#',
		'#^_aioseop_#',
		'#^_wpml_#',
		'#^_genesis_#',
		'#^_elementor_#',
	);

	private static $loaded = null;

	/**
	 * Return the assembled allowlist + patterns + exclusions.
	 */
	public static function get_all() {
		if ( self::$loaded !== null ) {
			return self::$loaded;
		}

		$raw = self::load_json();

		$static_options    = isset( $raw['static_options'] ) ? $raw['static_options'] : array();
		$static_theme_mods = isset( $raw['static_theme_mods'] ) ? $raw['static_theme_mods'] : array();
		$static_post_meta  = isset( $raw['static_post_meta'] ) ? $raw['static_post_meta'] : array();
		$patterns          = isset( $raw['patterns'] ) ? $raw['patterns'] : array();

		// Always-include WP-standard post meta keys (not in source via update_post_meta,
		// but structurally required for a usable Blueprint).
		$static_post_meta = array_merge( $static_post_meta, self::ALWAYS_INCLUDE_POST_META );

		$static_options    = apply_filters( 'lay_blueprint_export_options',    $static_options );
		$static_theme_mods = apply_filters( 'lay_blueprint_export_theme_mods', $static_theme_mods );
		$static_post_meta  = apply_filters( 'lay_blueprint_export_post_meta',  $static_post_meta );
		$patterns          = apply_filters( 'lay_blueprint_export_patterns',   $patterns );
		$exclusions        = apply_filters( 'lay_blueprint_exclude_options',   self::DEFAULT_EXCLUSIONS );
		$post_meta_excl    = apply_filters( 'lay_blueprint_exclude_post_meta', self::DEFAULT_POST_META_EXCLUSIONS );

		// Normalize patterns: callers may pass either full structured entries
		// (as emitted by the static analyzer) or bare regex strings via the filter.
		$normalized_patterns = array();
		foreach ( $patterns as $p ) {
			if ( is_string( $p ) ) {
				$normalized_patterns[] = array(
					'regex'    => $p,
					'template' => $p,
					'kind'     => 'option',
				);
			} elseif ( is_array( $p ) && ! empty( $p['regex'] ) ) {
				$normalized_patterns[] = array(
					'regex'    => $p['regex'],
					'template' => isset( $p['template'] ) ? $p['template'] : $p['regex'],
					'kind'     => isset( $p['kind'] ) ? $p['kind'] : 'option',
				);
			}
		}

		self::$loaded = array(
			'static_options'        => array_values( array_unique( array_filter( (array) $static_options, 'is_string' ) ) ),
			'static_theme_mods'     => array_values( array_unique( array_filter( (array) $static_theme_mods, 'is_string' ) ) ),
			'static_post_meta'      => array_values( array_unique( array_filter( (array) $static_post_meta, 'is_string' ) ) ),
			'patterns'              => $normalized_patterns,
			'exclusions'            => array_values( array_filter( (array) $exclusions, 'is_string' ) ),
			'post_meta_exclusions'  => array_values( array_filter( (array) $post_meta_excl, 'is_string' ) ),
			'meta'                  => isset( $raw['stats'] ) ? $raw['stats'] : array(),
			'generated_at'          => isset( $raw['generated_at'] ) ? $raw['generated_at'] : null,
		);

		return self::$loaded;
	}

	/**
	 * Whether the option name is in the allowlist (static or pattern) AND not
	 * in the exclusion list. This is the single check the exporter calls.
	 */
	public static function should_export_option( $option_name ) {
		if ( self::is_excluded( $option_name ) ) {
			return false;
		}
		$all = self::get_all();
		if ( in_array( $option_name, $all['static_options'], true ) ) {
			return true;
		}
		foreach ( $all['patterns'] as $p ) {
			if ( $p['kind'] !== 'theme_mod' && self::regex_match( $p['regex'], $option_name ) ) {
				return true;
			}
		}
		return false;
	}

	public static function is_excluded( $option_name ) {
		$all = self::get_all();
		foreach ( $all['exclusions'] as $regex ) {
			if ( self::regex_match( $regex, $option_name ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a given post-meta key should be carried in the Blueprint.
	 * Used by the content-collector when walking each post's meta.
	 */
	public static function should_export_post_meta( $meta_key ) {
		if ( self::is_post_meta_excluded( $meta_key ) ) {
			return false;
		}
		$all = self::get_all();
		if ( in_array( $meta_key, $all['static_post_meta'], true ) ) {
			return true;
		}
		foreach ( $all['patterns'] as $p ) {
			if ( $p['kind'] === 'post_meta' && self::regex_match( $p['regex'], $meta_key ) ) {
				return true;
			}
		}
		return false;
	}

	public static function is_post_meta_excluded( $meta_key ) {
		$all = self::get_all();
		foreach ( $all['post_meta_exclusions'] as $regex ) {
			if ( self::regex_match( $regex, $meta_key ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Diagnostics: scan the wp_options table and report which keys match,
	 * which are excluded, and which fall through (uncovered).
	 *
	 * Used by the WP-CLI `lay-blueprints diagnose` command (Phase 5) and by
	 * the audit panel in the admin UI.
	 */
	public static function diagnose() {
		global $wpdb;
		$rows = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options}" );

		$matched_static  = array();
		$matched_pattern = array();
		$excluded        = array();
		$uncovered       = array();

		$all = self::get_all();
		$static_set = array_flip( $all['static_options'] );

		foreach ( $rows as $name ) {
			if ( self::is_excluded( $name ) ) {
				$excluded[] = $name;
				continue;
			}
			if ( isset( $static_set[ $name ] ) ) {
				$matched_static[] = $name;
				continue;
			}
			$matched = null;
			foreach ( $all['patterns'] as $p ) {
				if ( self::regex_match( $p['regex'], $name ) ) {
					$matched = $p['template'];
					break;
				}
			}
			if ( $matched !== null ) {
				$matched_pattern[] = array( 'name' => $name, 'template' => $matched );
				continue;
			}
			$uncovered[] = $name;
		}

		return array(
			'matched_static'  => $matched_static,
			'matched_pattern' => $matched_pattern,
			'excluded'        => $excluded,
			'uncovered'       => $uncovered,
		);
	}

	/**
	 * Match a regex either in JS-style form ("^foo$") or PHP delimited form
	 * ("#^foo$#"). The static analyzer emits JS-style; the exclusion list and
	 * filter-supplied patterns may use either.
	 */
	private static function regex_match( $regex, $subject ) {
		$is_delimited = ( strlen( $regex ) >= 2 && $regex[0] === $regex[ strlen( $regex ) - 1 ] && ! ctype_alnum( $regex[0] ) );
		$pattern = $is_delimited ? $regex : '#' . $regex . '#';
		// Suppress warnings on malformed user-supplied patterns.
		$result = @preg_match( $pattern, $subject );
		return $result === 1;
	}

	private static function load_json() {
		$path = self::resolve_json_path();
		if ( ! $path || ! is_readable( $path ) ) {
			return array();
		}
		$json = file_get_contents( $path );
		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : array();
	}

	private static function resolve_json_path() {
		// Theme dir (production install): /wp-content/themes/lay/blueprints/known-keys/...
		if ( function_exists( 'get_template_directory' ) ) {
			$candidate = get_template_directory() . self::JSON_RELATIVE_PATH;
			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}
		// Fallback: relative to this file (works when loaded from src/ during testing).
		$candidate = dirname( __FILE__ ) . '/lay-blueprint-known-keys.json';
		if ( is_readable( $candidate ) ) {
			return $candidate;
		}
		return null;
	}

	/**
	 * Reset the in-memory cache (test helper / for unit-test contexts).
	 */
	public static function _reset_cache() {
		self::$loaded = null;
	}
}
