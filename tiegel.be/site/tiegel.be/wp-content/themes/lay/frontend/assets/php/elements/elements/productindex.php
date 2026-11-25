<?php

class LayProductIndex{

    public $el;
    public $config;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        $this->config = $this->el['config'];
        $relid = isset($this->el['relid']) ? $this->el['relid'] : '';
        $skip_cache_for_raw_layout = class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache();
        
        // PERFORMANCE: Cache rendered markup
        // Get current language for multilingual support (qTranslate, Polylang, WPML)
        $current_lang = 'default';
        if (function_exists('qtranxf_getLanguage')) {
            $current_lang = qtranxf_getLanguage();
        } elseif (function_exists('pll_current_language')) {
            $current_lang = pll_current_language();
        } elseif (defined('ICL_LANGUAGE_CODE')) {
            $current_lang = ICL_LANGUAGE_CODE;
        }
        
        $cache_key = 'productindex_markup_' . $current_lang . '_' . md5(serialize(array('config' => $this->config, 'relid' => $relid)));
        
        // Skip cache in customizer to show fresh content with updated settings
        if (!$skip_cache_for_raw_layout && !is_customize_preview()) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $markup = ProductIndex::get_productindex( $this->config, 'desktop', $this->el['relid'] );
        
        // Cache markup (permanent until post update)
        if (!$skip_cache_for_raw_layout) {
            set_transient($cache_key, $markup, 0);
        }
        
        return $markup;
    }

}
