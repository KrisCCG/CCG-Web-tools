<?php

class Cmb2Manager {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_menu() {
		$post_type_object = get_post_type_object( 'lay_custom_element' );
		if ( ! $post_type_object ) {
			return;
		}

		add_submenu_page(
			'manage-layoptions',
			$post_type_object->labels->name,
			$post_type_object->labels->name,
			$post_type_object->cap->edit_posts,
			'edit.php?post_type=lay_custom_element'
		);
	}

	public static function enqueue_assets( $hook ) {
		if ( ! self::is_lay_custom_element_screen( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'cmb2manager-styles',
			Setup::$uri . '/cmb2manager/assets/css/cmb2manager.css',
			array(),
			Setup::$ver
		);

		wp_enqueue_script(
			'cmb2manager-app',
			Setup::$uri . '/cmb2manager/assets/js/cmb2manager.umd.js',
			array(),
			Setup::$ver,
			true
		);

		wp_enqueue_script(
			'lay-collapse-and-expand-explanations',
			Setup::$uri . '/setup/assets/js/collapse_and_expand_explanations.js',
			array( 'jquery', 'underscore' ),
			Setup::$ver,
			true
		);
		wp_localize_script( 'lay-collapse-and-expand-explanations', 'setupPassedData', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * List table (edit.php) or post edit / new screen for lay_custom_element.
	 */
	private static function is_lay_custom_element_screen( $hook ) {
		if ( $hook === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'lay_custom_element' ) {
			return true;
		}
		if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( $screen && $screen->post_type === 'lay_custom_element' ) {
				return true;
			}
		}
		return false;
	}
}

Cmb2Manager::init();
