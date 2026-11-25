<?php
/**
 * Lay Blueprints — Options Installer.
 *
 * Writes blueprint.options + blueprint.theme_mods into the target site's
 * wp_options table.
 *
 * Special-case handling:
 *
 *   - Pattern-keyed options where the key contains a term ID
 *     (`{term_id}_category_gridder_json`, `{term_id}_phone_category_gridder_json`)
 *     get their key rewritten using the TermIDMap.
 *
 *   - Layout values inside category-gridder-json options are walked by the
 *     ElementRewriter to rewrite attachment IDs.
 *
 *   - HTML-bearing options (none in V1, but the hook is here) would be
 *     rewritten via HtmlImportRewriter.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_OptionsInstaller {

	/**
	 * Patterns whose leading digits represent a term_id and need remapping
	 * via the TermIDMap before writing.
	 */
	const TERM_ID_KEY_PATTERNS = array(
		'#^(\d+)_category_gridder_json$#',
		'#^(\d+)_phone_category_gridder_json$#',
	);

	/**
	 * Options whose VALUE is a post-id reference. After import, the value
	 * needs to be remapped via the post IDMap to point at the freshly-
	 * inserted post. Without this, page_on_front and friends silently
	 * point at deleted/non-existent posts and Lay's frontend.php throws
	 * "Attempt to read property X on null".
	 */
	const POST_ID_VALUE_OPTIONS = array(
		'page_on_front',
		'page_for_posts',
		'wp_page_for_privacy_policy',
		'lay_header_page',
		'lay_footer_active_in_pages',
		'lay_pages_footer',
		'lay_projects_footer',
		'lay_categories_footer',
		'lay_news_footer',
		'lay_woocommerce_pages_footer',
	);

	/**
	 * Theme-mod keys whose VALUE is a post-id reference. Same problem as
	 * POST_ID_VALUE_OPTIONS but in the theme_mods_lay blob — Lay's frontend
	 * reads `frontpage_select_page` etc. and calls get_post() on the value.
	 */
	const POST_ID_VALUE_THEMEMODS = array(
		'frontpage_select_page',
		'frontpage_select_post',
		'frontpage_select_news',
	);

	const TERM_ID_VALUE_THEMEMODS = array(
		'frontpage_select_category',
	);

	/** @var LayBlueprint_IdMap */
	private $attachments;

	/** @var LayBlueprint_IdMap */
	private $terms;

	/** @var LayBlueprint_IdMap */
	private $posts;

	/** @var LayBlueprint_ElementRewriter */
	private $element_rewriter;

	public function __construct( LayBlueprint_IdMap $attachments, LayBlueprint_IdMap $terms, LayBlueprint_IdMap $posts = null ) {
		$this->attachments      = $attachments;
		$this->terms            = $terms;
		$this->posts            = $posts;
		$this->element_rewriter = new LayBlueprint_ElementRewriter( $attachments );
	}

	public function install( array $options ) {
		$audit = array(
			'written'             => 0,
			'remapped_term_keys'  => 0,
			'remapped_post_id_values' => 0,
			'resolved_media_anchors'  => 0,
			'skipped_unmapped'    => 0,
		);
		foreach ( $options as $key => $value ) {
			$mapped_key = $this->maybe_remap_term_key( $key );
			if ( $mapped_key === null ) {
				$audit['skipped_unmapped']++;
				continue;
			}
			if ( $mapped_key !== $key ) {
				$audit['remapped_term_keys']++;
			}

			$value_to_write = $this->maybe_rewrite_layout_value( $mapped_key, $value );
			$value_to_write = $this->maybe_rewrite_post_id_value( $mapped_key, $value_to_write, $audit );
			$value_to_write = $this->resolve_media_anchors( $value_to_write, $audit );
			update_option( $mapped_key, $value_to_write );
			$audit['written']++;
		}
		return $audit;
	}

	/**
	 * Recursive walk over an option value. Replaces every `lay-bp://attachment/{id}`
	 * anchor (planted at export time by OptionsMediaWalker) with the new
	 * attachment URL via the IDMap. Walks into nested arrays AND into JSON
	 * strings (decode → walk → re-encode).
	 */
	private function resolve_media_anchors( $value, array &$audit ) {
		if ( is_string( $value ) ) {
			if ( self::looks_like_json( $value ) ) {
				$decoded = json_decode( $value, true );
				if ( is_array( $decoded ) ) {
					$rewritten = $this->resolve_media_anchors( $decoded, $audit );
					$re = wp_json_encode( $rewritten, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					return is_string( $re ) ? $re : $value;
				}
			}
			return preg_replace_callback(
				'#lay-bp://attachment/(\d+)#',
				function ( $m ) use ( &$audit ) {
					$old_id = (int) $m[1];
					$new_id = $this->attachments->lookup( $old_id );
					if ( $new_id === null ) {
						return 'about:blank?lay-bp-missing=' . $old_id;
					}
					$url = wp_get_attachment_url( $new_id );
					if ( ! $url ) {
						return 'about:blank?lay-bp-no-url=' . $new_id;
					}
					$audit['resolved_media_anchors']++;
					return $url;
				},
				$value
			);
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->resolve_media_anchors( $v, $audit );
			}
		}
		return $value;
	}

	private static function looks_like_json( $s ) {
		if ( strlen( $s ) < 2 ) { return false; }
		$first = $s[0];
		$last  = $s[ strlen( $s ) - 1 ];
		return ( $first === '{' && $last === '}' ) || ( $first === '[' && $last === ']' );
	}

	/**
	 * Some options carry a post_id value (or array of post_ids) that has to
	 * be remapped from the source site's IDs to the target site's. Without
	 * this, page_on_front etc. point at non-existent posts and the
	 * frontend null-derefs.
	 */
	private function maybe_rewrite_post_id_value( $key, $value, array &$audit ) {
		if ( ! $this->posts ) { return $value; }
		if ( ! in_array( $key, self::POST_ID_VALUE_OPTIONS, true ) ) {
			return $value;
		}
		// Scalar id?
		if ( is_numeric( $value ) ) {
			$mapped = $this->posts->lookup( (int) $value );
			if ( $mapped !== null ) {
				$audit['remapped_post_id_values']++;
				return $mapped;
			}
			return 0; // dangling reference → clear it (Lay handles 0 gracefully)
		}
		// Array of ids (e.g. lay_*_footer_active_in_X)?
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				if ( is_numeric( $v ) ) {
					$mapped = $this->posts->lookup( (int) $v );
					if ( $mapped !== null ) {
						$out[ $k ] = $mapped;
						$audit['remapped_post_id_values']++;
					}
					// Drop dangling.
				} else {
					$out[ $k ] = $v;
				}
			}
			return $out;
		}
		return $value;
	}

	public function install_theme_mods( array $theme_mods ) {
		$theme_slug = wp_get_theme()->get_stylesheet();
		// Existing nav_menu_locations (set by MenusInstaller) shouldn't get
		// clobbered by overwriting the whole blob, so merge.
		$current = get_option( 'theme_mods_' . $theme_slug, array() );
		if ( ! is_array( $current ) ) { $current = array(); }
		$preserved_nav = isset( $current['nav_menu_locations'] ) ? $current['nav_menu_locations'] : null;

		$next = $theme_mods;

		// Remap post-id-bearing theme mods so that frontpage_select_page etc.
		// point at the imported post instead of the source-site id.
		$remapped_post_ids = 0;
		$remapped_term_ids = 0;
		if ( $this->posts ) {
			foreach ( self::POST_ID_VALUE_THEMEMODS as $tm_key ) {
				if ( ! isset( $next[ $tm_key ] ) ) { continue; }
				if ( is_numeric( $next[ $tm_key ] ) ) {
					$mapped = $this->posts->lookup( (int) $next[ $tm_key ] );
					if ( $mapped !== null ) {
						$next[ $tm_key ] = $mapped;
						$remapped_post_ids++;
					} else {
						$next[ $tm_key ] = 0;
					}
				}
			}
		}
		foreach ( self::TERM_ID_VALUE_THEMEMODS as $tm_key ) {
			if ( ! isset( $next[ $tm_key ] ) ) { continue; }
			if ( is_numeric( $next[ $tm_key ] ) ) {
				$mapped = $this->terms->lookup( (int) $next[ $tm_key ] );
				if ( $mapped !== null ) {
					$next[ $tm_key ] = $mapped;
					$remapped_term_ids++;
				} else {
					$next[ $tm_key ] = 0;
				}
			}
		}

		// Resolve any lay-bp:// anchors planted by OptionsMediaWalker
		// (e.g. cursor1.png and other URL values inside theme_mods).
		$anchor_audit = array( 'resolved_media_anchors' => 0 );
		$next = $this->resolve_media_anchors( $next, $anchor_audit );

		if ( $preserved_nav !== null ) {
			$next['nav_menu_locations'] = $preserved_nav;
		}

		update_option( 'theme_mods_' . $theme_slug, $next );
		return array(
			'count'                  => count( $next ),
			'remapped_post_ids'      => $remapped_post_ids,
			'remapped_term_ids'      => $remapped_term_ids,
			'resolved_media_anchors' => $anchor_audit['resolved_media_anchors'],
		);
	}

	private function maybe_remap_term_key( $key ) {
		foreach ( self::TERM_ID_KEY_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $key, $m ) ) {
				$old_term_id = (int) $m[1];
				$new_term_id = $this->terms->lookup( $old_term_id );
				if ( $new_term_id === null ) {
					// Term wasn't imported (probably because it didn't exist
					// on the source either). Skip the option — keeping it
					// would create dangling per-category layouts.
					return null;
				}
				return preg_replace( '#^\d+#', (string) $new_term_id, $key, 1 );
			}
		}
		return $key;
	}

	private function maybe_rewrite_layout_value( $key, $value ) {
		// All option keys ending in _gridder_json (with optional phone_) carry
		// a layout JSON string that may reference attachment IDs.
		if ( ! preg_match( '#_gridder_json$#', $key ) ) { return $value; }
		if ( ! is_string( $value ) || $value === '' )  { return $value; }
		$decoded = json_decode( wp_unslash( $value ), true );
		if ( ! is_array( $decoded ) ) { return $value; }
		$this->element_rewriter->rewrite( $decoded );
		$reencoded = wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return wp_slash( (string) $reencoded );
	}
}
