<?php
class LayMarquee{
    public $el;
    public $device;

    private static function qtranslate_fallback($text){
        if( ! is_string($text) || strpos($text, '[:') === false ) {
            return $text;
        }
        if( class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache() ){
            return $text;
        }
        if( class_exists('Setup') ){
            return Setup::apply_qtranslate_with_fallback($text);
        }

        $current_lang = substr(get_locale(), 0, 2);
        if( preg_match_all('/\\[:([a-z]{2}(?:_[A-Z]{2})?)\\]([\\s\\S]*?)(?=\\[:[a-z]{2}(?:_[A-Z]{2})?\\]|\\[:\\]|$)/', $text, $matches, PREG_SET_ORDER) ){
            $first_non_empty = '';
            foreach( $matches as $match ){
                $value = isset($match[2]) ? $match[2] : '';
                if( strtolower($match[1]) === strtolower($current_lang) && trim($value) !== '' ){
                    return $value;
                }
                if( $first_non_empty === '' && trim($value) !== '' ){
                    $first_non_empty = $value;
                }
            }
            if( $first_non_empty !== '' ){
                return $first_non_empty;
            }
        }
        return preg_replace('/\\[:[a-z]{2}(?:_[A-Z]{2})?\\]|\\[:\\]/', '', $text);
    }

    public function __construct($el, $device = 'desktop'){
        $this->el = $el;
        $this->device = $device;
    }

    public function getMarkup($options = array()){
        // Resolve qTranslateXT bracket syntax (`[:en]…[:de]…[:]`) to the current
        // language's variant. Without this, the marquee JS sees all language
        // variants concatenated and renders only the first one (the user
        // reported it always showed German on a DE/EN site). `__()` was used
        // here historically but doesn't process qTranslate brackets. Use the
        // same fallback helper as the rest of the frontend markup.
        for($i=0; $i < count($this->el['config']['elements']); $i++) {
            if( $this->el['config']['elements'][$i]['type'] == 'text' ) {
                $cont = $this->el['config']['elements'][$i]['cont'];
                $this->el['config']['elements'][$i]['cont'] = self::qtranslate_fallback($cont);
            }
        }

        $config = $this->el['config'];
        $marqueeId = $this->el['relid'];

        // Scope all per-marquee CSS to its grid container so desktop and phone copies
        // of the same element (same relid) don't fight over the global selector.
        // Without this, the later <style> block in the DOM wins and forces both
        // copies to use the same spacebetween value.
        $gridSel = $this->device == 'phone' ? '#custom-phone-grid' : '#grid';
        $grid_container_selector = array_key_exists('grid_container_selector', $options) ? $options['grid_container_selector'] : '';

        // Build a scoped selector that ends with the full per-rule suffix.
        // Concatenating suffix onto a comma-joined base would only attach to
        // the last root — push the whole suffix into grid_container_scoped_selectors.
        $build_sel = function( $suffix ) use ( $gridSel, $marqueeId, $grid_container_selector ) {
            $full = ' .lay-marquee.marquee-id-' . (int) $marqueeId . $suffix;
            return $grid_container_selector !== ''
                ? Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, $full )
                : $gridSel . $full;
        };
        $build_has_images_sel = function( $suffix ) use ( $gridSel, $marqueeId, $grid_container_selector ) {
            $full = ' .lay-marquee.marquee-has-images.marquee-id-' . (int) $marqueeId . $suffix;
            return $grid_container_selector !== ''
                ? Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, $full )
                : $gridSel . $full;
        };

        // Pre-compute the most common variants used inside the heredoc-ish style
        // template below.
        $sel_root      = $build_sel( '' );
        $sel_img       = $build_sel( ' img' );
        $sel_inner     = $build_sel( ' > div > div > div > *' );
        $sel_inner_box = $build_sel( ' > div > div' );
        $sel_imgs_dd   = $build_has_images_sel( '>div>div' );
        $sel_imgs_d    = $build_has_images_sel( '>div' );

        $elements = $this->el['config']['elements'];
        $hasImage = false;
        for ($i=0; $i < count($elements); $i++) { 
            if( $elements[$i]['type'] == 'img' ) {
                $hasImage = true;
                break;
            }
        }

        $phone_breakpoint = get_option('lay_breakpoint', 600);
        $phone_breakpoint = (int)$phone_breakpoint;

		$tablet_breakpoint = get_option( 'lay_tablet_breakpoint', 1024 );
        $tablet_breakpoint = (int)$tablet_breakpoint;

        // $currentLayout = this.props.currentLayout
		$spacebetweenDesktop = $config['desktop']['spacebetween'];
        $spacetopDesktop = $config['desktop']['spacetop'];
        $spacetopDesktop_mu = array_key_exists('spacetop_mu', $config['desktop']) ? $config['desktop']['spacetop_mu'] : 'px';
        $spacebottomDesktop = $config['desktop']['spacebottom'];
        $spacebottomDesktop_mu = array_key_exists('spacebottom_mu', $config['desktop']) ? $config['desktop']['spacebottom_mu'] : 'px';

        $spacebetweenTablet = $config['tablet']['spacebetween'];
        $spacetopTablet = $config['tablet']['spacetop'];
        $spacetopTablet_mu = array_key_exists('spacetop_mu', $config['tablet']) ? $config['tablet']['spacetop_mu'] : 'px';
        $spacebottomTablet = $config['tablet']['spacebottom'];
        $spacebottomTablet_mu = array_key_exists('spacebottom_mu', $config['tablet']) ? $config['tablet']['spacebottom_mu'] : 'px';

        $spacebetweenPhone = $config['phone']['spacebetween'];
        $spacetopPhone = $config['phone']['spacetop'];
        $spacetopPhone_mu = array_key_exists('spacetop_mu', $config['phone']) ? $config['phone']['spacetop_mu'] : 'px';
        $spacebottomPhone = $config['phone']['spacebottom'];
        $spacebottomPhone_mu = array_key_exists('spacebottom_mu', $config['phone']) ? $config['phone']['spacebottom_mu'] : 'px';

        $bordertop = $config['borderTopWidth'];
        $borderbottom = $config['borderBottomWidth'];

        $bordercolor = $config['borderColor'];
        $backgroundcolor = $config['backgroundColor'];
        if( $backgroundcolor == '' ) {
            $backgroundcolor = 'transparent';
        }

        $alignment = $config['alignment'];

        $imageheightDesktop = $config['desktop']['imageheight'];
        $imageheightDesktop_mu = array_key_exists('imageheight_mu', $config['desktop']) ? $config['desktop']['imageheight_mu'] : 'px';
        $imageheightTablet = $config['tablet']['imageheight'];
        $imageheightTablet_mu = array_key_exists('imageheight_mu', $config['tablet']) ? $config['tablet']['imageheight_mu'] : 'px';
        $imageheightPhone = $config['phone']['imageheight'];
        $imageheightPhone_mu = array_key_exists('imageheight_mu', $config['phone']) ? $config['phone']['imageheight_mu'] : 'px';


        $css_alignment = '';
        switch ( $alignment ) {
            case 'top':
                $css_alignment = 'start';
            break;
            case 'middle':
                $css_alignment = 'center';
            break;
            case 'bottom':
                $css_alignment = 'end';
            break;
        }


        $style =
        '<style>
        @media (min-width: '.($tablet_breakpoint+1).'px){'
            .''.$sel_root.'{
                padding-top: '.$spacetopDesktop.$spacetopDesktop_mu.'!important;
                padding-bottom: '.$spacebottomDesktop.$spacebottomDesktop_mu.'!important;
            }
            '.$sel_img.'{
                height: '.$imageheightDesktop.$imageheightDesktop_mu.';
            }
            '.$sel_imgs_dd.',
            '.$sel_imgs_d.'{
                min-height: '.$imageheightDesktop.$imageheightDesktop_mu.';
            }
            '.$sel_inner.' {
                padding-right: '.($spacebetweenDesktop / 2).'px;
                padding-left: '.($spacebetweenDesktop / 2).'px;
                box-sizing: content-box!important;
            }
        }
        @media (max-width: '.($tablet_breakpoint).'px) and (min-width: '.($phone_breakpoint+1).'px){'
            .''.$sel_root.'{
                padding-top: '.$spacetopTablet.$spacetopTablet_mu.'!important;
                padding-bottom: '.$spacebottomTablet.$spacebottomTablet_mu.'!important;
            }
            '.$sel_img.'{
                height: '.$imageheightTablet.'px;
            }
            '.$sel_imgs_dd.',
            '.$sel_imgs_d.'{
                min-height: '.$imageheightTablet.$imageheightTablet_mu.';
            }
            '.$sel_inner.' {
                padding-right: '.($spacebetweenTablet / 2).'px;
                padding-left: '.($spacebetweenTablet / 2).'px;
                box-sizing: content-box!important;
            }
        }
        @media (max-width: '.($phone_breakpoint).'px){'
            .''.$sel_root.'{
                padding-top: '.$spacetopPhone.$spacetopPhone_mu.'!important;
                padding-bottom: '.$spacebottomPhone.$spacebottomPhone_mu.'!important;
            }
            '.$sel_img.'{
                height: '.$imageheightPhone.$imageheightPhone_mu.';
            }
            '.$sel_imgs_dd.',
            '.$sel_imgs_d.'{
                min-height: '.$imageheightPhone.$imageheightPhone_mu.';
            }
            '.$sel_inner.' {
                padding-right: '.($spacebetweenPhone / 2).'px;
                padding-left: '.($spacebetweenPhone / 2).'px;
                box-sizing: content-box!important;
            }
        }
        '.$sel_root.'{
            border-top: '.$bordertop.'px solid '.$bordercolor.';
            border-bottom: '.$borderbottom.'px solid '.$bordercolor.';
            background-color: '.$backgroundcolor.';
        }
        '.$sel_inner_box.'{
            align-items: '.$css_alignment.';
        }
        .lay-marquee ._Default:empty { padding: 0 !important; }
        </style>';

        $pauseOnHover = $config['pauseonhover'];
        $pauseOnHoverClass = $pauseOnHover ? 'marquee-pause-on-hover' : '';

        $startOnHover = array_key_exists('startonhover', $config) ? $config['startonhover'] : false;
        $startOnHoverClass = $startOnHover ? 'marquee-start-on-hover' : '';
        $hasImageClass = $hasImage ? 'marquee-has-images' : '';

        // Scroll-driven marquee — additive to the configured rate. The
        // attribute is a hint for the frontend module; only `.lay-marquee`
        // elements with this attribute are considered, so non-marquee
        // elements that happen to carry the field (e.g. via a preset) are
        // silently ignored.
        $scrollDriveAttr = '';
        // Marquee scroll-drive is conceptually scroll-position-bound, so it
        // only applies in scrub mode. Non-scrub (timed) mode treats marquee
        // like any other element type.
        $marquee_scrub_on = ! ( isset($this->el['scrollAnimation']['enableScrub']) && $this->el['scrollAnimation']['enableScrub'] === false );
        if ( $marquee_scrub_on && isset($this->el['scrollAnimation']['enableMarqueeScroll']) && $this->el['scrollAnimation']['enableMarqueeScroll'] === true ) {
            $factor = isset($this->el['scrollAnimation']['marqueeScrollFactor']) && is_numeric($this->el['scrollAnimation']['marqueeScrollFactor'])
                ? (float) $this->el['scrollAnimation']['marqueeScrollFactor']
                : 1;
            $scrollDriveAttr = ' data-lay-marquee-scroll-drive="' . htmlspecialchars((string) $factor, ENT_QUOTES, 'UTF-8') . '"';
            if ( isset($this->el['scrollAnimation']['marqueeScrollOneDirectionOnly']) && $this->el['scrollAnimation']['marqueeScrollOneDirectionOnly'] === true ) {
                $scrollDriveAttr .= ' data-lay-marquee-scroll-one-direction-only="true"';
            }
        }

        // i added an extra wrapper div to the marquee, so scrollslider addon can apply: parallax, inifinite scrolling, visbibility animation (observer). cause we need multiple parent divs to apply the animations to each of them
        // need to insert style after the markup instead of before, so scroll slider works correctly, cause scrollslider targets the first child for animation
        return '<div class="lay-marquee-wrapper"><div class="lay-marquee '.$hasImageClass.' '.$pauseOnHoverClass.' '.$startOnHoverClass.' marquee-id-'.$marqueeId.'" data-config="'.htmlspecialchars(json_encode($config), ENT_QUOTES, 'UTF-8').'"'.$scrollDriveAttr.'></div>'.$style.'</div>';
    }
}
