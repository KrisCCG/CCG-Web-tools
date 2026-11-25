<?php
get_header(); 
?>
<?php
global $post;
echo LaySearch::getMarkup();

// Check for password protection on singular content, frontpage, or 404 pages (which show frontpage)
$show_password_form = false;
$original_post = $post;

if ( is_singular() && post_password_required( $post ) ) {
    $show_password_form = true;
} elseif ( is_front_page() || is_404() ) {
    // For frontpage or 404 pages, check if the frontpage is password protected
    $frontpage_type = get_theme_mod('frontpage_type', 'category');
    $frontpage_id = null;
    
    switch ($frontpage_type) {
        case 'page':
            $frontpage_id = get_theme_mod('frontpage_select_page');
            if ( class_exists('PLL_Switcher') && function_exists('pll_get_post') ) {
                $frontpage_id = pll_get_post($frontpage_id);
            }
            if ( defined('ICL_SITEPRESS_VERSION') ) {
                $frontpage_id = apply_filters('wpml_object_id', $frontpage_id, 'page');
            }
            if ( $frontpage_id && post_password_required( $frontpage_id ) ) {
                $show_password_form = true;
                // Set post to frontpage for password form
                $post = get_post($frontpage_id);
            }
            break;
        case 'project':
            $frontpage_id = get_theme_mod('frontpage_select_project');
            if ( class_exists('PLL_Switcher') && function_exists('pll_get_post') ) {
                $frontpage_id = pll_get_post($frontpage_id);
            }
            if ( defined('ICL_SITEPRESS_VERSION') ) {
                $frontpage_id = apply_filters('wpml_object_id', $frontpage_id, 'post');
            }
            if ( $frontpage_id && post_password_required( $frontpage_id ) ) {
                $show_password_form = true;
                // Set post to frontpage for password form
                $post = get_post($frontpage_id);
            }
            break;
        case 'deck':
            // Skip if Deck feature was disabled after this theme_mod was saved.
            if ( ! post_type_exists('lay_deck') ) { break; }
            $frontpage_id = get_theme_mod('frontpage_select_deck');
            if ( class_exists('PLL_Switcher') && function_exists('pll_get_post') ) {
                $frontpage_id = pll_get_post($frontpage_id);
            }
            if ( defined('ICL_SITEPRESS_VERSION') ) {
                $frontpage_id = apply_filters('wpml_object_id', $frontpage_id, 'lay_deck');
            }
            if ( $frontpage_id && post_password_required( $frontpage_id ) ) {
                $show_password_form = true;
                $post = get_post($frontpage_id);
            }
            break;
        // Categories cannot be password protected, so no need to handle them here
    }
}

if ( $show_password_form ) {
    echo get_the_password_form();
    // Restore original $post if it was changed for frontpage/404 page
    if ( isset($original_post) && $post !== $original_post ) {
        $post = $original_post;
    }
} else {
    echo LayIntro::getMarkup(); 
    
	if ( class_exists('WooCommerce') && have_posts() ) {
			$content = get_the_content();
			if(trim($content) != '') {
				?>
				<div id="lay-woocommerce" class="lay-textformat-parent">
				<?php                
					the_content();
				?>
				</div><!-- #lay-woocommerce -->
				<?php
			}		
	}

$layout = Lay_Layout::getLayoutInit();
echo $layout;
?>
<div id="lay-overlays">
<?php
echo Lay_Overlays::getOverlays($layout);
?>
</div>
<?php
echo ProjectsOverlaysOptions::getMarkup();
echo Lay_Layout::getFixedHeader();
?>
<?php
}
get_footer();
