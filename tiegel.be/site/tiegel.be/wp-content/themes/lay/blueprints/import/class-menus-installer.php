<?php
/**
 * Lay Blueprints — Menus Installer.
 *
 * Recreates WP nav menus from blueprint.menus[]. For each entry:
 *   - wp_create_nav_menu (or fetch existing same-slug)
 *   - For each item, wp_update_nav_menu_item with object_id resolved by
 *     looking up linked_object.{kind, post_type, slug} → new post_id (or
 *     {taxonomy, slug} → new term_id). Falls back to a Custom URL link if
 *     the target can't be found.
 *   - Sets parent via parent_index → previously-created item ID.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_MenusInstaller {

	public function install( array $menus, array $nav_menu_locations = array() ) {
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

		$audit = array(
			'menus_created'  => 0,
			'menus_reused'   => 0,
			'items_added'    => 0,
			'items_failed'   => 0,
			'items_unlinked' => 0,
			'locations_set'  => 0,
		);

		// Track slug → new term_id so we can wire nav_menu_locations after.
		$slug_to_new_id = array();

		foreach ( $menus as $menu ) {
			if ( ! is_array( $menu ) || empty( $menu['name'] ) ) { continue; }

			$existing_term = get_term_by( 'slug', $menu['slug'], 'nav_menu' );
			if ( $existing_term && ! is_wp_error( $existing_term ) ) {
				$menu_id = (int) $existing_term->term_id;
				wp_delete_nav_menu( $menu_id ); // destructive: wipe and recreate
			}

			$menu_id = wp_create_nav_menu( $menu['name'] );
			if ( is_wp_error( $menu_id ) ) { continue; }
			$audit['menus_created']++;
			// `wp_create_nav_menu()` may sluggify the name differently from
			// the source — fetch the actual created term and use ITS slug.
			$created_term = get_term( $menu_id, 'nav_menu' );
			if ( $created_term && ! is_wp_error( $created_term ) ) {
				$slug_to_new_id[ $created_term->slug ] = (int) $created_term->term_id;
			}
			// Also key by the source slug so location lookup with original slug works.
			if ( ! empty( $menu['slug'] ) ) {
				$slug_to_new_id[ $menu['slug'] ] = $menu_id;
			}

			// Walk items in order, recording new IDs to wire parents.
			$items = isset( $menu['items'] ) ? (array) $menu['items'] : array();
			$new_item_id_by_index = array();
			foreach ( $items as $idx => $item ) {
				$new_id = $this->add_item( $menu_id, $item, $idx, $new_item_id_by_index, $audit );
				if ( $new_id > 0 ) {
					$new_item_id_by_index[ $idx ] = $new_id;
					$audit['items_added']++;
				} else {
					$audit['items_failed']++;
				}
			}
		}

		// Wire nav_menu_locations: source { theme_loc: menu_slug } →
		// target { theme_loc: new_menu_term_id }.
		if ( ! empty( $nav_menu_locations ) ) {
			$theme_slug = wp_get_theme()->get_stylesheet();
			$current_mods = get_option( 'theme_mods_' . $theme_slug, array() );
			if ( ! is_array( $current_mods ) ) { $current_mods = array(); }
			$resolved_locations = array();
			foreach ( $nav_menu_locations as $loc => $menu_slug ) {
				if ( isset( $slug_to_new_id[ $menu_slug ] ) ) {
					$resolved_locations[ $loc ] = $slug_to_new_id[ $menu_slug ];
					$audit['locations_set']++;
				}
			}
			if ( ! empty( $resolved_locations ) ) {
				$current_mods['nav_menu_locations'] = $resolved_locations;
				update_option( 'theme_mods_' . $theme_slug, $current_mods );
			}
		}

		return $audit;
	}

	private function add_item( $menu_id, $item, $idx, array $new_item_id_by_index, array &$audit ) {
		$args = array(
			'menu-item-title'       => isset( $item['title'] )       ? (string) $item['title']       : '',
			'menu-item-status'      => 'publish',
			'menu-item-position'    => isset( $item['menu_order'] )  ? (int) $item['menu_order']     : ( $idx + 1 ),
			'menu-item-classes'     => isset( $item['classes'] ) && is_array( $item['classes'] ) ? implode( ' ', $item['classes'] ) : '',
			'menu-item-target'      => isset( $item['target'] )      ? (string) $item['target']      : '',
			'menu-item-xfn'         => isset( $item['xfn'] )         ? (string) $item['xfn']         : '',
			'menu-item-description' => isset( $item['description'] ) ? (string) $item['description'] : '',
		);

		// Parent.
		if ( isset( $item['parent_index'] ) && $item['parent_index'] !== null && isset( $new_item_id_by_index[ (int) $item['parent_index'] ] ) ) {
			$args['menu-item-parent-id'] = (int) $new_item_id_by_index[ (int) $item['parent_index'] ];
		}

		// Type / object resolution.
		$linked = isset( $item['linked_object'] ) && is_array( $item['linked_object'] ) ? $item['linked_object'] : null;

		if ( $linked && $linked['kind'] === 'post' ) {
			$post = get_page_by_path( $linked['slug'], OBJECT, $linked['post_type'] );
			if ( $post ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = $post->post_type;
				$args['menu-item-object-id'] = (int) $post->ID;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = isset( $item['url'] ) ? (string) $item['url'] : '#';
				$audit['items_unlinked']++;
			}
		} elseif ( $linked && $linked['kind'] === 'term' ) {
			$term = get_term_by( 'slug', $linked['slug'], $linked['taxonomy'] );
			if ( $term && ! is_wp_error( $term ) ) {
				$args['menu-item-type']      = 'taxonomy';
				$args['menu-item-object']    = $term->taxonomy;
				$args['menu-item-object-id'] = (int) $term->term_id;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = isset( $item['url'] ) ? (string) $item['url'] : '#';
				$audit['items_unlinked']++;
			}
		} else {
			$args['menu-item-type'] = 'custom';
			$args['menu-item-url']  = isset( $item['url'] ) ? (string) $item['url'] : '#';
		}

		$new_id = wp_update_nav_menu_item( $menu_id, 0, $args );
		return is_wp_error( $new_id ) ? 0 : (int) $new_id;
	}
}
