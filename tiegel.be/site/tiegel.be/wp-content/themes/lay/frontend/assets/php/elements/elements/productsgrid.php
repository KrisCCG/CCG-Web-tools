<?php
require get_template_directory().'/frontend/assets/php/elements/productsgrid_functions.php';

class LayProductsgrid{

    public $el;
    public $config;

    public function __construct($el){
        $this->el = $el;

        add_action('wp_ajax_get_category_filter_list_for_thumbnailgrid', array($this, 'get_category_filter_list_for_thumbnailgrid'));
		add_action('wp_ajax_get_tag_list_for_thumbnailgrid', array($this, 'get_tag_list_for_thumbnailgrid'));
    }

    public function getMarkup($options = array()){
        if( class_exists('WooCommerce') ) {
            $this->config = $this->el['config'];
            $relid = isset($this->el['relid']) ? $this->el['relid'] : '';
            
            // PERFORMANCE: Cache rendered markup (skip if random order)
            $randomorder = isset($this->config['randomorder']) ? $this->config['randomorder'] : false;
            $skip_cache_for_raw_layout = class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache();
            if (!$randomorder && !$skip_cache_for_raw_layout) {
                // Get current language for multilingual support (qTranslate, Polylang, WPML)
                $current_lang = 'default';
                if (function_exists('qtranxf_getLanguage')) {
                    $current_lang = qtranxf_getLanguage();
                } elseif (function_exists('pll_current_language')) {
                    $current_lang = pll_current_language();
                } elseif (defined('ICL_LANGUAGE_CODE')) {
                    $current_lang = ICL_LANGUAGE_CODE;
                }
                
                $cache_key = 'productsgrid_markup_' . $current_lang . '_' . md5(serialize($this->config));
                
                // Skip cache in customizer to show fresh content with updated settings
                if (!is_customize_preview()) {
                    $cached = get_transient($cache_key);
                    if ($cached !== false) {
                        return $cached;
                    }
                }
            }
            
            $markup = LayProductsgridFunctions::build_lay_products_markup_with_filter( $this->config );
            
            // Cache markup (permanent until post update)
            if (!$randomorder && !$skip_cache_for_raw_layout) {
                set_transient($cache_key, $markup, 0);
            }
            
            return $markup;
        }
        return '';
    }

}
