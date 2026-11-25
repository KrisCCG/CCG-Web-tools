<?php

class LayNewsElement{

    public $el;
    public $config;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        $this->config = $this->el['config'];
        $relid = isset($this->el['relid']) ? $this->el['relid'] : '';
        
        // PERFORMANCE: Cache rendered markup
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
            
            $cache_key = 'newselement_markup_' . $current_lang . '_' . md5(serialize($this->config));
            
            // Skip cache in customizer to show fresh content with updated settings
            if (!is_customize_preview()) {
                $cached = get_transient($cache_key);
                if ($cached !== false) {
                    return $cached;
                }
            }
        }
        
        $markup = LayNews::get_news_feed( $this->config, 'frontend' );
        
        // Cache markup (permanent until post update)
        if (!$randomorder && !$skip_cache_for_raw_layout) {
            set_transient($cache_key, $markup, 0);
        }
        
        return $markup;
    }

}
