<?php 

class Lay_Header{

    public function __construct(){
        add_action('wp_head', array($this, 'exclude_header_pages_from_indexing'));
    }

    // todo: maybe add throw an exception https://laravel-news.com/php-7-3-json-error-handling
    // todo: test if header was set, but i delete the header page
    public static function getHeaderId(){
        $headerId = get_option('lay_header_page', '');
        
        if( class_exists( 'PLL_Switcher' ) ){
            $headerId = pll_get_post($headerId);
        }
        // wpml exists?: https://wpml.org/forums/topic/check-if-wpml-is-active/
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $headerId = apply_filters( 'wpml_object_id', $headerId, 'page' );
        }

        return $headerId;
    }

    /**
     * Check if current page is the frontpage (Lay theme way)
     */
    private static function is_frontpage() {
        global $post;
        
        // Check if WordPress considers it the frontpage
        if (is_front_page() || is_null($post)) {
            return true;
        }
        
        // Same check for deck slides (frontpage_type='deck').
        if (is_singular('lay_deck')) {
            $frontpage_type = get_theme_mod('frontpage_type', 'category');
            if ($frontpage_type === 'deck') {
                $current_id = get_queried_object_id();
                $frontpage_deck_id = get_theme_mod('frontpage_select_deck');
                if ($frontpage_deck_id) {
                    if (class_exists('PLL_Switcher') && function_exists('pll_get_post')) {
                        $frontpage_deck_id = pll_get_post($frontpage_deck_id);
                    }
                    if (defined('ICL_SITEPRESS_VERSION')) {
                        $frontpage_deck_id = apply_filters('wpml_object_id', $frontpage_deck_id, 'lay_deck');
                    }
                    return $current_id == $frontpage_deck_id;
                }
            }
        }

        // Also check if current page matches the frontpage page ID (for when frontpage type is 'page')
        if (is_page()) {
            $frontpage_type = get_theme_mod('frontpage_type', 'category');
            if ($frontpage_type === 'page') {
                $current_page_id = get_queried_object_id();
                $frontpage_page_id = get_theme_mod('frontpage_select_page');

                if ($frontpage_page_id) {
                    // Handle Polylang translations
                    if (class_exists('PLL_Switcher') && function_exists('pll_get_post')) {
                        $frontpage_page_id = pll_get_post($frontpage_page_id);
                    }
                    // Handle WPML translations
                    if (defined('ICL_SITEPRESS_VERSION')) {
                        $frontpage_page_id = apply_filters('wpml_object_id', $frontpage_page_id, 'page');
                    }
                    
                    return $current_page_id == $frontpage_page_id;
                }
            }
        }
        
        return false;
    }

    /**
     * Exclude header pages from search engine indexing
     */
    public static function exclude_header_pages_from_indexing() {
        $val = get_option('misc_options_header', '');
        if($val != "on" || MiscOptions::$locked) {
            return;
        }

        // Don't add noindex to frontpage
        if (self::is_frontpage()) {
            return;
        }
        
        // Check if current page is a header page
        if (is_page()) {
            $current_page_id = get_queried_object_id();
            $headerId = get_option('lay_header_page', '');
            
            // Check if current page ID is the header page
            if ($headerId && $headerId !== '' && $current_page_id == $headerId) {
                echo '<meta name="robots" content="noindex, follow">';
            }
        }
    }

}
new Lay_Header();
