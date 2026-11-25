<?php

class LayText{

    public $el;
    public $device;
    public $postid;

    public function __construct($el, $device = 'desktop', $postid = -1){
        $this->el = $el;
        $this->device = $device;
        $this->postid = $postid;
    }

    public function getMarkup($options = array()){
        $isscrolltotop = array_key_exists('isscrolltotop', $this->el) ? $this->el['isscrolltotop'] : false;
        if( $isscrolltotop == true ) {
            $isscrolltotop = 'scrolltotop';
        } else {
            $isscrolltotop = '';
        }

        $style = '';
        if( array_key_exists('pxwidth', $this->el) && $this->el['pxwidth'] != null ) {
            $style .= '<style>';
            $selector_grid = '#grid';
            $selector_cover = '.cover-region-desktop';
            if( $this->device == 'phone' ) {
                $selector_grid = '#custom-phone-grid';
                $selector_cover = '.cover-region-phone';
            }
            // Footer / other regions: layout passes grid_container_selector (e.g. #footer, #footer-custom-phone-grid).
            // Those containers are not scoped with .id-{postid} like #grid / #custom-phone-grid.
            if( array_key_exists('grid_container_selector', $options) && $options['grid_container_selector'] !== '' && $options['grid_container_selector'] !== null ) {
                $selector_grid = $options['grid_container_selector'];
                $selector_cover = '';
            } else if( $this->postid != -1 ) {
                $selector_grid .= '.id-'.$this->postid;
                $selector_cover .= '.id-'.$this->postid;
            }
            // So text width applies in both #grid and cover region (first row can be cover), or only the explicit grid when in footer.
            // layout.php may pass comma-separated grid_container_selector (grid + cover-inner); split like other scoped element CSS.
            if( $selector_cover !== '' ) {
                $selector_parts = array();
                $selector_parts[] = $selector_grid . ' .id-' . $this->el['relid'];
                $selector_parts[] = $selector_cover . ' .id-' . $this->el['relid'];
                $selector = implode(', ', $selector_parts);
                $selector_text_parts = array();
                $selector_text_parts[] = $selector_grid . ' .text_' . $this->el['relid'];
                $selector_text_parts[] = $selector_cover . ' .text_' . $this->el['relid'];
                $selector_text = implode(', ', $selector_text_parts);
            } else {
                $selector = Lay_LayoutFunctions::grid_container_scoped_selectors( $selector_grid, ' .id-' . $this->el['relid'] );
                $selector_text = Lay_LayoutFunctions::grid_container_scoped_selectors( $selector_grid, ' .text_' . $this->el['relid'] );
            }
            $align = array_key_exists('align', $this->el['pxwidth']) ? $this->el['pxwidth']['align'] : 'left';
            $justifyContent = 'flex-start';
            if( $align === 'center' ) {
                $justifyContent = 'center';
            } else if( $align === 'right' ) {
                $justifyContent = 'flex-end';
            }
            if( array_key_exists('desktop', $this->el['pxwidth']) ){
                $style .= '@media (min-width: '.(LayFrontend_Options::$tablet_breakpoint + 1).'px) { '.$selector.'{display:flex;justify-content:'.$justifyContent.';} '.$selector_text.'{width:'.$this->el['pxwidth']['desktop']['val'].$this->el['pxwidth']['desktop']['mu'].';} }';
            }
            if( array_key_exists('tablet', $this->el['pxwidth']) ){
                $style .= '@media (min-width: '.(LayFrontend_Options::$breakpoint + 1).'px) and (max-width: '.LayFrontend_Options::$tablet_breakpoint.'px) { '.$selector.'{display:flex;justify-content:'.$justifyContent.';} '.$selector_text.'{width:'.$this->el['pxwidth']['tablet']['val'].$this->el['pxwidth']['tablet']['mu'].';} }';
            }
            if( array_key_exists('phone', $this->el['pxwidth']) ){
                $style .= '@media (max-width: '.(LayFrontend_Options::$breakpoint).'px) { '.$selector.'{display:flex;justify-content:'.$justifyContent.';} '.$selector_text.'{width:'.$this->el['pxwidth']['phone']['val'].$this->el['pxwidth']['phone']['mu'].';} }';
            }
            $style .= '</style>';
        }

        $markup = $style;
        $text_class = array_key_exists('relid', $this->el) ? 'text_'.$this->el['relid'] : ''; 

        $text_animation = array_key_exists('textAnimation', $this->el) && array_key_exists('animationType', $this->el['textAnimation']) ? $this->el['textAnimation']['animationType'] : '';
        $text_animation = str_replace(' ', '_', $text_animation);
        $text_animation_active = array_key_exists('textAnimation', $this->el) && array_key_exists('enableTextAnimation', $this->el['textAnimation']) ? $this->el['textAnimation']['enableTextAnimation'] : false;
        $text_animation_top = array_key_exists('textAnimation', $this->el) && array_key_exists('top', $this->el['textAnimation']) ? $this->el['textAnimation']['top'] : 80;
        $text_animation_duration = array_key_exists('textAnimation', $this->el) && array_key_exists('duration', $this->el['textAnimation']) ? $this->el['textAnimation']['duration'] : 1000;
        $text_animation_delay = array_key_exists('textAnimation', $this->el) && array_key_exists('delay', $this->el['textAnimation']) ? $this->el['textAnimation']['delay'] : 0;
        $allowed_text_animation_easings = array(
            'linear',
            'easeInQuad', 'easeInCubic', 'easeInQuart', 'easeInSine', 'easeInExpo',
            'easeOutQuad', 'easeOutCubic', 'easeOutQuart', 'easeOutQuint', 'easeOutSine', 'easeOutExpo', 'easeOutCirc', 'easeOutBack',
            'easeInOutQuad', 'easeInOutCubic', 'easeInOutQuart', 'easeInOutSine', 'easeInOutExpo', 'easeInOutCirc', 'easeInOutBack',
        );
        $text_animation_easing = 'easeOutCubic';
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'easing', $this->el['textAnimation'] ) ) {
            $cand_easing = $this->el['textAnimation']['easing'];
            if ( is_string( $cand_easing ) && in_array( $cand_easing, $allowed_text_animation_easings, true ) ) {
                $text_animation_easing = $cand_easing;
            }
        }
        $line_mask_padding_bottom = false;
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'lineMaskPaddingBottom', $this->el['textAnimation'] ) ) {
            $v = $this->el['textAnimation']['lineMaskPaddingBottom'];
            $line_mask_padding_bottom = ( $v === true || $v === 'true' || $v === 1 || $v === '1' );
        }
        $enable_scroll_scrub = false;
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'enableScrollScrub', $this->el['textAnimation'] ) ) {
            $v = $this->el['textAnimation']['enableScrollScrub'];
            $enable_scroll_scrub = ( $v === true || $v === 'true' || $v === 1 || $v === '1' );
        }
        $scrub_start_at = 0;
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'scrubStartAt', $this->el['textAnimation'] ) ) {
            $cand = $this->el['textAnimation']['scrubStartAt'];
            if ( is_numeric( $cand ) ) {
                $scrub_start_at = floatval( $cand );
            }
        }
        $scrub_end_at_top = 90;
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'scrubEndAtTop', $this->el['textAnimation'] ) ) {
            $cand = $this->el['textAnimation']['scrubEndAtTop'];
            if ( is_numeric( $cand ) ) {
                $scrub_end_at_top = floatval( $cand );
            }
        }
        $scrub_end_at_full_exit = false;
        if ( array_key_exists( 'textAnimation', $this->el ) && array_key_exists( 'scrubEndAtFullExit', $this->el['textAnimation'] ) ) {
            $v = $this->el['textAnimation']['scrubEndAtFullExit'];
            $scrub_end_at_full_exit = ( $v === true || $v === 'true' || $v === 1 || $v === '1' );
        }

        // find text $tags$ in $this->el['cont'] and replace them with comma separated list of tags
        // does text contain $tags$ string?
        if( strpos($this->el['cont'], '$tags$') !== false ) {
            $tags = get_the_tags($this->postid);
            $tagArray = array();
            if ($tags) {
                usort($tags, function($a, $b) {
                    return $a->term_order - $b->term_order;
                });
                foreach($tags as $tag) {
                    $tagArray []= $tag->name;
                }

                $tagArray = '<span class="lay-tags-in-text"><span class="lay-tag-in-text">'.implode('</span><span class="lay-tag-in-text-delimiter">, </span><span class="lay-tag-in-text">', $tagArray).'</span></span>';
                $this->el['cont'] =  str_replace('$tags$', $tagArray, $this->el['cont']);
            } else {
                $this->el['cont'] = str_replace('$tags$', '', $this->el['cont']);
            }
        }

        // find text $categories$ in $this->el['cont'] and replace them with comma separated list of categories
        // does text contain $categories$ string?
        if( strpos($this->el['cont'], '$categories$') !== false ) {
            $categories = get_the_terms($this->postid, 'category');
            $categoryArray = array();
            if ($categories) {
                usort($categories, function($a, $b) {
                    return $a->term_order - $b->term_order;
                });
                foreach($categories as $category) {
                    $categoryArray []= $category->name;
                }

                $categoryArray = '<span class="lay-categories-in-text"><span class="lay-category-in-text">'.implode('</span><span class="lay-category-in-text-delimiter">, </span><span class="lay-category-in-text">', $categoryArray).'</span></span>';
                $this->el['cont'] = str_replace('$categories$', $categoryArray, $this->el['cont']);
            } else {
                $this->el['cont'] = str_replace('$categories$', '', $this->el['cont']);
            }
        }

        $attrs = '';
        if($text_animation_active == 'true') {
            // no-animation turns off my default animations so that we only use our gsap text animation
            $text_class .= ' lay-text-animation';
            $attrs = 'data-top="'.$text_animation_top.'" data-duration="'.$text_animation_duration.'" data-delay="'.$text_animation_delay.'" data-animation-type="'.$text_animation.'" data-animation-easing="'.esc_attr( $text_animation_easing ).'"';
            $is_masked_type = ( $text_animation === 'masked_lines' || $text_animation === 'masked_words' || $text_animation === 'masked_characters' );
            if ( $is_masked_type && $line_mask_padding_bottom ) {
                $attrs .= ' data-line-mask-padding-bottom="true"';
            }
            $attrs .= ' data-animation-sync-with-navigation="true"';
            if ( $enable_scroll_scrub ) {
                // Scroll-driven scrub: animation progress = function of element's
                // viewport position. The frontend `text_animation_scrub` controller
                // reads these attributes; the IO-driven `playAnimation` path skips
                // any element with `data-animation-scrub="true"`.
                $attrs .= ' data-animation-scrub="true"';
                if ( $scrub_start_at > 0 ) {
                    $attrs .= ' data-animation-scrub-start-at="' . esc_attr( $scrub_start_at ) . '"';
                }
                $attrs .= ' data-animation-scrub-end-at-top="' . esc_attr( $scrub_end_at_top ) . '"';
                if ( $scrub_end_at_full_exit ) {
                    $attrs .= ' data-animation-scrub-end-at-full-exit="true"';
                }
            }
        }


        $openingAnchor = '';
        $endingAnchor = '';
        $imageLink = array_key_exists('imagelink', $this->el) ? $this->el['imagelink'] : false;
        if( $imageLink != false ){
            if( is_array($imageLink) ) {
                $linkUrl = LTUtility::getFullURL($imageLink['url']);
                if( $imageLink['type'] == '' ) {
                    // External/manual URL: keep original value
                    $linkUrl = $imageLink['url'];
                }
                $openingAnchor = '<a href="'.$linkUrl.'"';
                if( array_key_exists('newtab', $imageLink) && $imageLink['newtab'] == true ){
                    $openingAnchor .= ' target="_blank"';
                } else {
                    if( array_key_exists('id', $imageLink) && $imageLink['id'] != '' ) {
                        $openingAnchor .= ' data-id="'.$imageLink['id'].'"';
                    }
                    if( array_key_exists('type', $imageLink) && $imageLink['type'] != '' ) {
                        $openingAnchor .= ' data-type="'.$imageLink['type'].'"';
                    }
                    if( array_key_exists('title', $imageLink) && $imageLink['title'] != '' ) {
                        $openingAnchor .= ' data-title="'.__($imageLink['title']).'"';
                    }
                    if( array_key_exists('catid', $imageLink) && $imageLink['catid'] != '' ) {
                        $openingAnchor .= ' data-catid="'.LayElFunctions::stringifyCatIds($imageLink['catid']).'"';
                    }
                }
            } else {
                // Backwards compatibility
                $openingAnchor = '<a href="'.LTUtility::getFullURL($imageLink).'"';
            }
            $openingAnchor .= '>';
            $endingAnchor = '</a>';
        }

        $textContent = $this->el['cont'];
        if( $openingAnchor != '' ) {
            // Avoid invalid nested anchors when the whole textbox is linked.
            $textContent = preg_replace('/<\s*\/?\s*a\b[^>]*>/i', '', $textContent);
        }

        // lang on the text root: Pretext line-split (Hypher) uses paragraph.closest('[lang]'); without this,
        // the first match is often <html>. Polylang/WPML hook determine_locale() so this tracks the active locale.
        $text_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
        $text_lang_attr = '';
        if ( is_string( $text_locale ) && $text_locale !== '' ) {
            $text_lang_attr = ' lang="' . esc_attr( strtolower( str_replace( '_', '-', $text_locale ) ) ) . '"';
        }

        $markup .= '<div class="text lay-textformat-parent '.$text_class.' '.$isscrolltotop.'" '.$attrs.$text_lang_attr.'>'.$openingAnchor.$textContent.$endingAnchor.'</div>';
        return $markup;
    }

}
