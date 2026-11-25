<?php
/**
 * CPT for user-defined CMB2-backed Gridder elements; taxonomy term slugs = dropdown IDs for Lay Gridder.
 */
class Lay_Custom_Element_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		self::register_post_type();
		self::register_taxonomy();
	}

	private static function register_post_type() {
		register_post_type(
			'lay_custom_element',
			array(
				'labels'              => array(
					'name'               => lay_t( 'Custom Elements', 'custom_elements' ),
					'singular_name'      => lay_t( 'Custom Element', 'custom_element_singular' ),
					'add_new_item'       => lay_t( 'Add New Custom Element', 'add_new_custom_element' ),
					'edit_item'          => lay_t( 'Edit Custom Element', 'edit_custom_element' ),
					'new_item'           => lay_t( 'New Custom Element', 'new_custom_element' ),
					'view_item'          => lay_t( 'View Custom Element', 'view_custom_element' ),
					'search_items'       => lay_t( 'Search Custom Elements', 'search_custom_elements' ),
					'not_found'          => lay_t( 'No custom elements found', 'no_custom_elements_found' ),
					'not_found_in_trash' => lay_t( 'No custom elements found in Trash', 'no_custom_elements_found_in_trash' ),
					'all_items'          => lay_t( 'Custom Elements', 'custom_elements' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-admin-customizer',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
			)
		);

		remove_post_type_support( 'lay_custom_element', 'editor' );
	}

	private static function register_taxonomy() {
		register_taxonomy(
			'lay_custom_element_dropdown',
			'lay_custom_element',
			array(
				'labels'            => array(
					'name'          => lay_t( 'Dropdowns', 'custom_element_dropdowns' ),
					'singular_name' => lay_t( 'Dropdown', 'custom_element_dropdown_singular' ),
					'search_items'  => lay_t( 'Search Dropdowns', 'search_custom_element_dropdowns' ),
					'all_items'     => lay_t( 'All Dropdowns', 'all_custom_element_dropdowns' ),
					'edit_item'     => lay_t( 'Edit Dropdown', 'edit_custom_element_dropdown' ),
					'update_item'   => lay_t( 'Update Dropdown', 'update_custom_element_dropdown' ),
					'add_new_item'  => lay_t( 'Add New Dropdown', 'add_new_custom_element_dropdown' ),
					'new_item_name' => lay_t( 'New Dropdown Name', 'new_custom_element_dropdown_name' ),
					'not_found'     => lay_t( 'No dropdowns found', 'no_custom_element_dropdowns_found' ),
				),
				'public'            => false,
				'publicly_queryable' => false,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'show_in_rest'      => false,
				'rewrite'           => false,
			)
		);
	}
}

Lay_Custom_Element_CPT::init();
