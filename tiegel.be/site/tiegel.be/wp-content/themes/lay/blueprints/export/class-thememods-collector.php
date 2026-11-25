<?php
/**
 * Lay Blueprints — Theme Mods Collector.
 *
 * WordPress stores all set_theme_mod() values under a single option named
 * theme_mods_{theme_slug}. We dump that whole blob — no per-key allowlist,
 * because future Lay updates can add new theme_mods without us tracking
 * them, and the blob auto-includes them.
 *
 * The whole-blob approach is intentional: any cruft from old/removed mods
 * is harmless on import (WP just ignores unknown mods).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ThemeModsCollector {

	/**
	 * @return array{ values: array<string,mixed>, audit: array{ count: int, source_option: string } }
	 */
	public static function collect() {
		$theme = wp_get_theme();
		$slug  = $theme ? $theme->get_stylesheet() : 'lay';
		$mods  = get_option( 'theme_mods_' . $slug, array() );

		// get_theme_mods() also injects 0_filter_keys and similar internals.
		// We use the raw option to keep the export close to what's in the DB.
		if ( ! is_array( $mods ) ) {
			$mods = array();
		}

		// Strip WP-internal keys that aren't user data.
		unset( $mods[0] );                  // index pointer
		unset( $mods['0'] );                // serialized variant
		unset( $mods['nav_menu_locations'] ); // handled separately by menus collector

		return array(
			'values' => $mods,
			'audit'  => array(
				'count'         => count( $mods ),
				'source_option' => 'theme_mods_' . $slug,
			),
		);
	}
}
