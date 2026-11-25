<?php
/**
 * Lay Blueprints — Menus Collector.
 *
 * Walks WP nav menus and converts to a portable structure that doesn't
 * depend on local term/post IDs. Items reference their parent by index
 * within the menu, and any object_id (page/post link target) is kept as
 * { post_type, slug } for the importer to resolve against newly-imported
 * content.
 *
 * Phase 2 only collects the structure — actually wiring object_id ↔
 * imported-content happens in Phase 4 (when content is in scope).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_MenusCollector {

	/**
	 * @return array{ values: array, audit: array{ count: int } }
	 */
	public static function collect() {
		if ( ! function_exists( 'wp_get_nav_menus' ) ) {
			return self::empty_result();
		}

		$menus  = wp_get_nav_menus();
		$result = array();

		foreach ( (array) $menus as $menu ) {
			if ( ! ( $menu instanceof WP_Term ) ) {
				continue;
			}

			$items_raw = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
			if ( ! is_array( $items_raw ) ) {
				$items_raw = array();
			}

			// Map original ID → index for parent resolution.
			$index_by_id = array();
			foreach ( $items_raw as $idx => $item ) {
				$index_by_id[ (int) $item->ID ] = $idx;
			}

			$items = array();
			foreach ( $items_raw as $item ) {
				$parent_idx = isset( $index_by_id[ (int) $item->menu_item_parent ] )
					? $index_by_id[ (int) $item->menu_item_parent ]
					: null;

				$linked_object = self::resolve_link_target( $item );

				$items[] = array(
					'title'         => $item->title,
					'url'           => $item->url,
					'menu_order'    => (int) $item->menu_order,
					'parent_index'  => $parent_idx,
					'object_type'   => $item->object,        // post_type/taxonomy/custom
					'type'          => $item->type,          // post_type/taxonomy/custom
					'linked_object' => $linked_object,       // null for custom URLs
					'classes'       => is_array( $item->classes ) ? array_values( array_filter( $item->classes ) ) : array(),
					'target'        => $item->target,
					'xfn'           => $item->xfn,
					'description'   => $item->description,
				);
			}

			$result[] = array(
				'name'        => $menu->name,
				'slug'        => $menu->slug,
				'description' => $menu->description,
				'items'       => $items,
			);
		}

		return array(
			'values' => $result,
			'audit'  => array(
				'count'         => count( $result ),
				'locations_set' => count( self::collect_locations() ),
			),
		);
	}

	/**
	 * Translate the active theme's `nav_menu_locations` (theme_location ⇒
	 * menu_term_id) into a portable form keyed by menu slug instead.
	 *
	 *   { 'primary' => 5,    'mobile' => 7 }   →
	 *   { 'primary' => 'main-menu', 'mobile' => 'mobile-menu' }
	 *
	 * Importer reverses this back to term IDs of newly-created menus.
	 */
	public static function collect_locations() {
		$locations = function_exists( 'get_nav_menu_locations' ) ? get_nav_menu_locations() : array();
		if ( ! is_array( $locations ) || empty( $locations ) ) { return array(); }
		$out = array();
		foreach ( $locations as $loc => $term_id ) {
			$term_id = (int) $term_id;
			if ( $term_id <= 0 ) { continue; }
			$term = get_term( $term_id, 'nav_menu' );
			if ( ! $term || is_wp_error( $term ) ) { continue; }
			$out[ $loc ] = $term->slug;
		}
		return $out;
	}

	private static function resolve_link_target( $item ) {
		$object_id = (int) $item->object_id;
		if ( $object_id <= 0 ) {
			return null;
		}
		if ( $item->type === 'post_type' ) {
			$post = get_post( $object_id );
			if ( ! $post ) {
				return null;
			}
			return array(
				'kind'      => 'post',
				'post_type' => $post->post_type,
				'slug'      => $post->post_name,
			);
		}
		if ( $item->type === 'taxonomy' ) {
			$term = get_term( $object_id );
			if ( ! $term || is_wp_error( $term ) ) {
				return null;
			}
			return array(
				'kind'     => 'term',
				'taxonomy' => $term->taxonomy,
				'slug'     => $term->slug,
			);
		}
		return null;
	}

	private static function empty_result() {
		return array(
			'values' => array(),
			'audit'  => array( 'count' => 0 ),
		);
	}
}
