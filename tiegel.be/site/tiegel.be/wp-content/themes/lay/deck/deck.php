<?php
/**
 * Lay Deck — slide-deck presentation feature.
 *
 * Each `lay_deck` post is one slide. Frontend nav:
 *   - Each slide is a normal page at /<slug>/{post-slug}/
 *   - A persistent counter widget (rendered by deck_controller in the bundled
 *     frontend app, outside the AJAX-swap content area) shows `< 2 / 10 >`
 *     and binds ←/→ keys.
 *   - Navigation between slides reuses Lay's existing AJAX nav, which means
 *     all the user's configured Navigation Animations apply automatically.
 *
 * Counter state on the JS side is computed from `layData.deckMeta` (a flat
 * list of all slides emitted by Frontend::get_deck_meta() at page load) plus
 * `body[data-id]` after each AJAX nav — no per-page inline data block needed.
 *
 * This file owns:
 *   - conditional CPT registration (gated by misc_options_activate_deck_feature)
 *   - permalink-flush on toggle / slug change (init prio 100, transient flag)
 *   - hooking lay_deck into Lay's gridder metaboxes + admin screen detection
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayDeck {

	const FLUSH_FLAG_OPTION = 'lay_deck_flush_needed';

	public function __construct() {
		// Toggle-/slug-change → flag for next-init flush.
		add_action( 'update_option_misc_options_activate_deck_feature', array( __CLASS__, 'mark_flush' ) );
		add_action( 'update_option_misc_options_deck_feature_slug',     array( __CLASS__, 'mark_flush' ) );

		// CPT registration when toggle is on.
		$enabled = get_option( 'misc_options_activate_deck_feature', '' );
		if ( $enabled === 'on' && ! MiscOptions::$locked ) {
			add_action( 'init', array( __CLASS__, 'register_post_type' ) );
			// Add lay_deck to gridder metabox CPT list so the gridder appears on edit screens.
			add_filter( 'lay_add_gridder_meta_boxes_to', array( __CLASS__, 'add_to_gridder_meta_boxes' ) );
			// Without this, gridder.php's `gridder_styles`/`gridder_scripts` see
			// `is_admin_page_with_a_gridder()` returning false for our screen,
			// so the React gridder bundle + CSS never enqueue and the Gridder
			// metabox renders empty. Same gating News uses (Gridder::$active_for_news).
			add_filter( 'lay_is_admin_page_with_a_gridder', array( __CLASS__, 'mark_deck_screen_as_gridder' ), 10, 2 );
		}

		// Run flush after init so the rewrite rules see the just-registered CPT.
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite' ), 100 );
	}

	public static function mark_flush() {
		update_option( self::FLUSH_FLAG_OPTION, 1, false );
	}

	public static function maybe_flush_rewrite() {
		if ( get_option( self::FLUSH_FLAG_OPTION ) ) {
			flush_rewrite_rules( false );
			delete_option( self::FLUSH_FLAG_OPTION );
		}
	}

	public static function register_post_type() {
		$slug = get_option( 'misc_options_deck_feature_slug', 'deck' );

		register_post_type( 'lay_deck', array(
			'labels' => array(
				'name'          => 'Deck',
				'singular_name' => 'Deck',
				'add_new_item'  => lay_t( 'Add New Deck Slide', 'deck_add_new_slide' ),
				'edit_item'     => lay_t( 'Edit Deck Slide',    'deck_edit_slide' ),
				'new_item'      => lay_t( 'New Deck Slide',     'deck_new_slide' ),
				'view_item'     => lay_t( 'View Deck Slide',    'deck_view_slide' ),
				'search_items'  => lay_t( 'Search Deck',        'deck_search' ),
				'all_items'     => 'Deck',
				'menu_name'     => 'Deck',
			),
			'show_in_menu'        => true,
			'menu_position'       => 6,
			'rewrite'             => array(
				'slug'       => $slug,
				'with_front' => false,
			),
			'public'              => true,
			'publicly_queryable'  => true,
			// `false` matches user spec — no archive view; deck is shared via direct slide URL.
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-slides',
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		) );
	}

	public static function add_to_gridder_meta_boxes( $post_types ) {
		if ( ! is_array( $post_types ) ) { $post_types = array(); }
		if ( ! in_array( 'lay_deck', $post_types, true ) ) {
			$post_types[] = 'lay_deck';
		}
		return $post_types;
	}

	public static function mark_deck_screen_as_gridder( $is_gridder, $screen ) {
		if ( $is_gridder ) { return $is_gridder; }
		if ( $screen && isset( $screen->id ) && $screen->id === 'lay_deck' ) {
			return true;
		}
		return $is_gridder;
	}
}

new LayDeck();
