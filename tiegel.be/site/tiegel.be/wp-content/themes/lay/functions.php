<?php

// Translations are now handled by translator.php (JSON-based via lay_t()).
// The legacy `load_theme_textdomain('lay', ...)` loader and the lay/languages
// .po/.mo files are gone — see lay/translations/sync_translations.js for how
// keys are kept in sync with the locale JSONs.

/* LIVERELOAD_INJECT */

if ( ! defined( 'LAY_ENABLE_BLUEPRINTS' ) ) {
	define( 'LAY_ENABLE_BLUEPRINTS', false );
}

if ( ! defined( 'LAY_ENABLE_SETUP_WIZARD' ) ) {
	define( 'LAY_ENABLE_SETUP_WIZARD', false );
}

require get_template_directory().'/translations/translator.php';
require get_template_directory().'/utility.php';
require get_template_directory().'/cache_invalidation/cache_invalidation.php';
require get_template_directory().'/search/search.php';
require get_template_directory().'/fontmanager/fontmanager.php';
require get_template_directory().'/qtranslatex_integration/qtranslatex_integration.php';
require get_template_directory().'/polylang_integration/polylang_integration.php';
require get_template_directory().'/gridder/constants.php';
require get_template_directory().'/update_plugins_after_theme_update/update_plugins_after_theme_update.php';
require get_template_directory().'/setup/setup.php';
require get_template_directory().'/options/misc_options.php';
require get_template_directory().'/templates/templates.php';
require get_template_directory().'/projectdescription/projectdescription.php';

if(get_option('misc_options_activate_ask_ai', 'on') == 'on'){
	require get_template_directory().'/ai/ask_ai.php';
}
require get_template_directory().'/gridder/project_thumbnail.php';
require get_template_directory().'/gridder/projectindex.php';
require get_template_directory().'/gridder/productindex.php';
require get_template_directory().'/news/news.php';
require get_template_directory().'/news/gridder_for_news.php';
require get_template_directory().'/deck/deck.php';
require get_template_directory().'/setup/attachment_dimensions/attachment_dimensions.php';
require get_template_directory().'/menu/page_is_projects_overview_metabox.php';
if(is_admin()){
	require get_template_directory().'/options/set_frontpage.php';
}

if( class_exists('WooCommerce') ) {
    require get_template_directory().'/gridder/productsgrid.php';
    require get_template_directory().'/gridder/product_thumbnail.php';
	require get_template_directory().'/woocommerce_integration/woocommerce.php';
	require get_template_directory().'/woocommerce_integration/woocommerce_function_overrides.php';
	require get_template_directory().'/woocommerce_integration/woocommerce_textformats.php';
	require get_template_directory().'/customizer/sidecart.php';
	require get_template_directory().'/woocommerce_integration/image_mouseover_thumbnails.php';
	require get_template_directory().'/woocommerce_integration/variation_images.php';
}

if ( is_plugin_active('cmb2/init.php') ) {
	require get_template_directory().'/gridder/cmb2_integration.php';
}

require get_template_directory().'/gridder/gridder.php';
require get_template_directory().'/gridder/custom_preview.php';
require get_template_directory().'/gridder/category_gridder.php';
require get_template_directory().'/gridder/thumbnailgrid.php';
require get_template_directory().'/gridder/table.php';
require get_template_directory().'/gridder/accordion.php';
require get_template_directory().'/gridder/scroll_animation_presets.php';
require get_template_directory().'/formatsmanager/formatsmanager.php';
require get_template_directory().'/buttonsmanager/buttonsmanager.php';
if ( LAY_ENABLE_BLUEPRINTS ) {
	require get_template_directory().'/blueprints/blueprints.php';
}
if ( LAY_ENABLE_SETUP_WIZARD ) {
	require get_template_directory().'/setup-wizard/setup-wizard.php';
}
require get_template_directory().'/cmb2manager/lay_custom_element_cpt.php';
require get_template_directory().'/frontend/media_query_css.php';
require get_template_directory().'/frontend/frontend.php';
require get_template_directory().'/gridder/gridder_css_output.php';
require get_template_directory().'/customizer/css_output_mobile_first_row_100vh_heights.php';
require get_template_directory().'/customizer/css_output.php';
require get_template_directory().'/customizer/scroll_transitions_css_output.php';
require get_template_directory().'/customizer/css_output_mobile_menu.php';
require get_template_directory().'/customizer/customizer.php';
require get_template_directory().'/intro/intro.php';
require get_template_directory().'/menu/menu_consts.php';
require get_template_directory().'/menu/menu.php';
require get_template_directory().'/menu/menu_manager.php';
require get_template_directory().'/menu/menu_customizer.php';
require get_template_directory().'/menu/menu_customizer_manager.php';
require get_template_directory().'/menu/custom_mobile_menu.php';
require get_template_directory().'/options/gridder_defaults.php';
if ( is_plugin_active( 'cmb2/init.php' ) ) {
	require get_template_directory() . '/cmb2manager/cmb2manager.php';
}
require get_template_directory().'/options/css_html_options.php';
require get_template_directory().'/options/footer_options.php';
require get_template_directory().'/options/footer_options_individual_select_metaboxes.php';

if(get_option('misc_options_header', '') == "on"){
	require get_template_directory().'/options/header_options.php';
}

if( ! class_exists( 'LayLicenseKey' ) ){
	// just checking, because ppl might have activated laygridder plugin which has the same class atm
	require get_template_directory().'/options/license_key.php';
}
require get_template_directory().'/options/projectsoverlays_options.php';
require get_template_directory().'/options/projectsoverlays_direct_load.php';
require get_template_directory().'/options/horizontal_scroll_options.php';
require get_template_directory().'/options/horizontal_scroll_individual_metaboxes.php';


require get_template_directory().'/options/cover_options.php';
require get_template_directory().'/options/cover_options_individual_metaboxes.php';
require get_template_directory().'/options/overlay_metabox.php';
require get_template_directory().'/thumbnails/image_mouseover_thumbnails.php';
require get_template_directory().'/thumbnails/video_thumbnails.php';
require get_template_directory().'/updatejson/update_thumbnails.php';
require get_template_directory().'/updatejson/update_imagelinks.php';
require get_template_directory().'/updatejson/update_row_bg_links.php';
require get_template_directory().'/frontend/shortcodes.php';
require get_template_directory().'/presets/presets.php';

// new files for php frontend
require get_template_directory().'/frontend/assets/php/frontend_options.php';
require get_template_directory().'/frontend/assets/php/layout.php';
require get_template_directory().'/frontend/assets/php/overlays/overlays.php';
require get_template_directory().'/frontend/horizontal_scroll.php';

add_action('after_setup_theme', 'laytheme_load_plugins');
function laytheme_load_plugins(){
	if(is_admin()){
		if(!function_exists('m4c_duplicate_post_scripts')){
			include_once(get_template_directory().'/assets/plugins/post-duplicator/m4c-postduplicator.php');
		}
        if(!class_exists('Post_Duplicator_Polylang')){
			include_once(get_template_directory().'/assets/plugins/post-duplicator-polylang/post-duplicator-polylang.php');
		}
        if ( get_option( 'misc_options_disable_simple_custom_post_order', '' ) !== 'on' && ! class_exists( 'SCPO_Engine' ) ) {
			include_once( get_template_directory() . '/assets/plugins/simple-custom-post-order/simple-custom-post-order.php' );
		}
		if(!function_exists('rfi_return_post_types')){
			include_once(get_template_directory().'/assets/plugins/require-featured-image-and-title/require-featured-image-and-title.php');
		}
		if(!class_exists('WPSVG')){
			include_once(get_template_directory().'/assets/plugins/wp-svg-images/wp-svg-images.php');
		}
	}
}

$disable_updates = get_option('misc_options_disable_lay_updates', '');
if($disable_updates != "on"){
	require 'kernl-update-checker/kernl-update-checker.php';
	$KernlUpdateChecker = Puc_v4_FactoryKernl::buildUpdateChecker(
		'https://kernl.us/api/v1/theme-updates/56a0b3327ba9bc01527df76c/',
		__FILE__,
		'lay'
	);
}
