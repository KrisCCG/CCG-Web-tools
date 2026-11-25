<?php 

class Lay_Footer{

    public function __construct(){
        add_action('wp_head', array($this, 'exclude_footer_pages_from_indexing'));
    }

    // todo: maybe add throw an exception https://laravel-news.com/php-7-3-json-error-handling
    // todo: test if footer was set, but i delete the footer page
    public static function getFooterId($postid, $type){

        if( class_exists( 'WooCommerce' ) && LayFrontend_Options::$footer_active_in_woocommerce_pages == 'all' ){
            // https://developer.woocommerce.com/docs/conditional-tags-in-woocommerce/
            if( $type == 'product' || is_woocommerce() || is_cart() || is_checkout() || is_account_page() ){

                $footerId = LayFrontend_Options::$woocommercePagesFooterId;
                if( class_exists( 'PLL_Switcher' ) ){
                    $footerId = pll_get_post($footerId);
                }
                // wpml exists?: https://wpml.org/forums/topic/check-if-wpml-is-active/
                if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
                    $footerId = apply_filters( 'wpml_object_id', $footerId, 'page' );
                }
                return $footerId;
            }
        }

        $footerId = "";
        switch($type){
            case 'project':
                switch(LayFrontend_Options::$footer_active_in_projects){
                    case 'all':
                        $footerId = LayFrontend_Options::$projectsFooterId;
                    break;
                    case 'individual':
                        $arr = json_decode(LayFrontend_Options::$individual_project_footers, true);
                        if( is_array($arr) ){
                            if( array_key_exists($postid, $arr) ){
                                $footerId = $arr[$postid];
                            }
                        }
                    break;
                }
            break;
            case 'page':
                switch(LayFrontend_Options::$footer_active_in_pages){
                    case 'all':
                        $footerId = LayFrontend_Options::$pagesFooterId;
                    break;
                    case 'individual':
                        $arr = json_decode(LayFrontend_Options::$individual_page_footers, true);
                        if( is_array($arr) ){
                            if( array_key_exists($postid, $arr) ){
                                $footerId = $arr[$postid];
                            }
                        }
                    break;
                }
            break;
            case 'archive':
            case 'category':
                switch(LayFrontend_Options::$footer_active_in_categories){
                    case 'all':
                        $footerId = LayFrontend_Options::$categoriesFooterId;
                    break;
                    case 'individual':
                        $arr = json_decode(LayFrontend_Options::$individual_category_footers, true);
                        if( is_array($arr) ){
                            if( array_key_exists($postid, $arr) ){
                                $footerId = $arr[$postid];
                            }
                        }
                    break;
                }
            break;
            case 'news':
            case 'lay_news':
                switch(LayFrontend_Options::$footer_active_in_news){
                    case 'all':
                        $footerId = LayFrontend_Options::$newsFooterId;
                    break;
                    case 'individual':
                        $arr = json_decode(LayFrontend_Options::$individual_news_footers, true);
                        if( is_array($arr) ){
                            if( array_key_exists($postid, $arr) ){
                                $footerId = $arr[$postid];
                            }
                        }
                    break;
                }
            break;
            case 'deck':
            case 'lay_deck':
                switch(LayFrontend_Options::$footer_active_in_deck){
                    case 'all':
                        $footerId = LayFrontend_Options::$deckFooterId;
                    break;
                    case 'individual':
                        $arr = json_decode(LayFrontend_Options::$individual_deck_footers, true);
                        if( is_array($arr) ){
                            if( array_key_exists($postid, $arr) ){
                                $footerId = $arr[$postid];
                            }
                        }
                    break;
                }
            break;
        }

        if( class_exists( 'PLL_Switcher' ) ){
            $footerId = pll_get_post($footerId);
        }
        // wpml exists?: https://wpml.org/forums/topic/check-if-wpml-is-active/
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
            $footerId = apply_filters( 'wpml_object_id', $footerId, 'page' );
        }

        return $footerId;
    }

    public static function getFooterJSON($postid, $type){

        $footerId = Lay_Footer::getFooterId($postid, $type);
        if($footerId != ''){
            $desktop = get_post_meta( $footerId, '_gridder_json', true );
            $phone = get_post_meta( $footerId, '_phone_gridder_json', true );
            return array( 'desktop' => $desktop, 'phone' => $phone );
        }
        return false;
    }

    // missing: showFooter from footer_controller.js and destroyFooter

    /**
     * Check if current page is the frontpage (Lay theme way)
     */
    private static function is_frontpage() {
        global $post;
        
        // Check if WordPress considers it the frontpage
        if (is_front_page() || is_null($post)) {
            return true;
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
        
        return false;
    }

    /**
     * Exclude footer pages from search engine indexing
     */
    public static function exclude_footer_pages_from_indexing() {
        // Don't add noindex to frontpage
        if (self::is_frontpage()) {
            return;
        }
        
        // Check if current page is a footer page
        if (is_page()) {
            $current_page_id = get_queried_object_id();
            $footerIds = array();
            
            // Add all footer page IDs to the array (only if not empty string)
            if (isset(LayFrontend_Options::$projectsFooterId) && LayFrontend_Options::$projectsFooterId && LayFrontend_Options::$projectsFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$projectsFooterId;
            }
            if (isset(LayFrontend_Options::$pagesFooterId) && LayFrontend_Options::$pagesFooterId && LayFrontend_Options::$pagesFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$pagesFooterId;
            }
            if (isset(LayFrontend_Options::$categoriesFooterId) && LayFrontend_Options::$categoriesFooterId && LayFrontend_Options::$categoriesFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$categoriesFooterId;
            }
            if (isset(LayFrontend_Options::$newsFooterId) && LayFrontend_Options::$newsFooterId && LayFrontend_Options::$newsFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$newsFooterId;
            }
            if (isset(LayFrontend_Options::$deckFooterId) && LayFrontend_Options::$deckFooterId && LayFrontend_Options::$deckFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$deckFooterId;
            }
            if (isset(LayFrontend_Options::$woocommercePagesFooterId) && LayFrontend_Options::$woocommercePagesFooterId && LayFrontend_Options::$woocommercePagesFooterId !== '') {
                $footerIds[] = LayFrontend_Options::$woocommercePagesFooterId;
            }
            
            // Also check individual footer mappings
            $individual_page_footers = json_decode(LayFrontend_Options::$individual_page_footers, true);
            if (is_array($individual_page_footers)) {
                foreach ($individual_page_footers as $footer_id) {
                    if ($footer_id && $footer_id !== '') {
                        $footerIds[] = $footer_id;
                    }
                }
            }
            
            $individual_project_footers = json_decode(LayFrontend_Options::$individual_project_footers, true);
            if (is_array($individual_project_footers)) {
                foreach ($individual_project_footers as $footer_id) {
                    if ($footer_id && $footer_id !== '') {
                        $footerIds[] = $footer_id;
                    }
                }
            }
            
            $individual_news_footers = json_decode(LayFrontend_Options::$individual_news_footers, true);
            if (is_array($individual_news_footers)) {
                foreach ($individual_news_footers as $footer_id) {
                    if ($footer_id && $footer_id !== '') {
                        $footerIds[] = $footer_id;
                    }
                }
            }

            $individual_deck_footers = json_decode(LayFrontend_Options::$individual_deck_footers, true);
            if (is_array($individual_deck_footers)) {
                foreach ($individual_deck_footers as $footer_id) {
                    if ($footer_id && $footer_id !== '') {
                        $footerIds[] = $footer_id;
                    }
                }
            }
            
            $individual_category_footers = json_decode(LayFrontend_Options::$individual_category_footers, true);
            if (is_array($individual_category_footers)) {
                foreach ($individual_category_footers as $footer_id) {
                    if ($footer_id && $footer_id !== '') {
                        $footerIds[] = $footer_id;
                    }
                }
            }
            
            // Check if current page ID is in the footer IDs array
            if (in_array($current_page_id, $footerIds)) {
                echo '<meta name="robots" content="noindex, follow">';
            }
        }
    }

}
new Lay_Footer();