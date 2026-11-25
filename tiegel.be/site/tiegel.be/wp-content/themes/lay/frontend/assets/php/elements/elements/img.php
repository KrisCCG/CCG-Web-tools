<?php
// todo: caption
class LayImg{

    public $el;
    // for elementgrid, sameheight
    public $height;

    public function __construct($el, $height = false){
        $this->el = $el;
        $this->height = $height;
    }


    public function getMarkup($options = array()){

		$in_elementgrid = array_key_exists('in-elementgrid', $options) ? $options['in-elementgrid'] : false;
		$grid_container_selector = array_key_exists('grid_container_selector', $options) ? $options['grid_container_selector'] : '';
		$no_custom_phone_layout = !empty($options['no_custom_phone_layout']);
		$has_car_phone = array_key_exists('customAspectRatioPhone', $this->el) && is_array($this->el['customAspectRatioPhone'])
			&& !empty($this->el['customAspectRatioPhone']['enableCustomAspectRatioPhone']);
		$use_phone_ar_stylesheet = !$in_elementgrid && $grid_container_selector !== '' && $no_custom_phone_layout && $has_car_phone;
		if ( $use_phone_ar_stylesheet ) {
			$this->el['_use_phone_ar_stylesheet'] = true;
		}

        $el = $this->el;
        $pb = $this->el['ar'] * 100;
        if(array_key_exists('customAspectRatio', $this->el) && array_key_exists('enableCustomAspectRatio', $this->el['customAspectRatio']) && $this->el['customAspectRatio']['enableCustomAspectRatio']) {
            $car = $this->el['customAspectRatio'];
            $w = isset($car['width']) && $car['width'] > 0 ? (float)$car['width'] : 16;
            $h = isset($car['height']) && $car['height'] > 0 ? (float)$car['height'] : 9;
            $pb = ($h / $w) * 100;
        }
        $markup = '';

        $attid = $this->el['attid'];

        $additional_css = $this->getSidebarCSS($options);
        $this->el['sidebar_css'] = $additional_css;

        $img = LayElFunctions::getLazyImg($this->el);
        $is_png_or_gif = strpos($this->el['cont'], '.png') !== false || strpos($this->el['cont'], '.gif') !== false;
        $markup .= $img;

        $lightboxoff = array_key_exists('lightboxoff', $this->el) && $this->el['lightboxoff'] == true ? 'lightboxoff' : '';
        $openingAnchor = '';
        $endingAnchor = '';

        $imageLink = array_key_exists('imagelink', $this->el) ? $this->el['imagelink'] : false;

        if( $imageLink != false ){
            $lightboxoff = 'lightboxoff';
            if( is_array($imageLink) ) {
                $linkUrl = LTUtility::getFullURL($imageLink['url']);
                // is this not a link to an internal page / category / post?
                if( $imageLink['type'] == '' ) {
                    // need to use the real URL and not change it through "LTUtility::getFullURL"
                    $linkUrl = $imageLink['url'];
                }
                if( $imageLink['type'] != 'category' && $imageLink['type'] != '' && $imageLink['id'] != 'null' && $imageLink['id'] != '' ) {
                    // need to do this so link will be updated with qtranslate!
                    $linkUrl = get_permalink($imageLink['id']);
                }

                $openingAnchor = '<a href="'.$linkUrl.'"';
                if($imageLink['newtab'] == true){
                    $openingAnchor .= ' target="_blank"';
                } else {
                    if( $imageLink['id'] != '' ) {
                        $openingAnchor .= ' data-id="'.$imageLink['id'].'"';
                    }
                    if( $imageLink['type'] != '' ) {
                        $openingAnchor .= ' data-type="'.$imageLink['type'].'"';
                    }
                    if( $imageLink['title'] != '' ) {
                        $openingAnchor .= ' data-title="'.__($imageLink['title']).'"';
                    }
                    if( array_key_exists('catid', $imageLink) && $imageLink['catid'] != '' ) {
                        $openingAnchor .= ' data-catid="'.LayElFunctions::stringifyCatIds($imageLink['catid']).'"';
                    }
                }
            } else {
                // for backwards compatibility
                $newtab = array_key_exists('newtab', $this->el) ? $this->el['newtbab'] : false;
                $openingAnchor = '<a href="'.LTUtility::getFullURL($imageLink).'"'; 
                if( $newtab == true ) {
                    $openingAnchor .= ' target="_blank"';
                }
            }
            $openingAnchor .= '>';
            $endingAnchor = '</a>';
        }

        $isscrolltotop = array_key_exists( 'isscrolltotop', $this->el ) ? $this->el['isscrolltotop'] : false;
        if( $isscrolltotop == true ) {
            $isscrolltotop = 'scrolltotop';
        } else {
            $isscrolltotop = '';
        }
        $lightbox_caption = array_key_exists('lightbox_caption', $this->el) ? $this->el['lightbox_caption'] : '';
        if( $lightbox_caption == '' ) {
            switch( LayFrontend_Options::$lightbox_default_caption ) {
                case 'caption':
                    $lightbox_caption = wp_get_attachment_caption($attid);
                    if( $lightbox_caption != '' ) {
                        $lightbox_caption = '<p class="_'.LayFrontend_Options::$lightbox_captionTextformat.'_no_spaces">'.$lightbox_caption.'</p>';
                    }
                break;
                case 'title':
                    $lightbox_caption = get_the_title($attid);
                    if( $lightbox_caption != '' ) {
                        $lightbox_caption = '<p class="_'.LayFrontend_Options::$lightbox_captionTextformat.'_no_spaces">'.$lightbox_caption.'</p>';
                    }
                break;
                case 'elementcaption':
                    if( array_key_exists( 'caption', $this->el ) && $this->el['caption'] != '' ) {
                        $lightbox_caption = $this->el['caption']; 
                    }
                break;
            }
        }
        $lightbox_caption_attr = $lightbox_caption != '' ? 'data-lightbox-caption="'.htmlspecialchars( $lightbox_caption, ENT_QUOTES, 'UTF-8' ).'"' : '';

        $extra_class = $is_png_or_gif ? 'image-transparent-bg' : '';

        $caption = LayElFunctions::getCaptionMarkup($this->el);

        // Get border radius CSS for .ph container if border radius is enabled
        // Use the same logic as getSidebarCSS() - just check if borderRadius exists
        $ph_border_radius_css = '';
        if(array_key_exists('borderRadius', $this->el)) {
            $br = $this->el['borderRadius'];
            $ph_border_radius_css = 'border-radius: '.$br['topLeft'].$br['topLeftMu'].' '.$br['topRight'].$br['topRightMu'].' '.$br['bottomRight'].$br['bottomRightMu'].' '.$br['bottomLeft'].$br['bottomLeftMu'].';';
            if (array_key_exists('cornerShape', $br) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round') {
                $ph_border_radius_css .= ' corner-shape: '.$br['cornerShape'].';';
            }
        }

        // When phone AR is in stylesheet, .ph padding-bottom goes in media queries instead of inline
        $ph_inline_style = ( $use_phone_ar_stylesheet ? '' : 'padding-bottom:'.Lay_LayoutFunctions::replaceCommaWithDot($pb).'%;' ) . ( $ph_border_radius_css ? ' '.$ph_border_radius_css : '' );
        $pbstyle = $ph_inline_style !== '' ? 'style="'.trim($ph_inline_style).'"' : '';
        $pb_markup = '<div class="ph" '.$pbstyle.'></div>';
        if($this->height != false) {
            $pb_markup = '';
        }

        $text_on_element_gradient = LayElFunctions::getTextOnElementGradientMarkup($this->el);
        $text_on_element_overlay = LayElFunctions::getTextOnElementOverlayMarkup($this->el, array('grid_container_selector' => $grid_container_selector));

        $orientation = $pb > 100 ? 'portrait' : 'landscape';

        $extra_class .= ' '.$orientation;

		$divCSS = '';
		$has_custom_aspect_ratio = array_key_exists('customAspectRatio', $this->el) && array_key_exists('enableCustomAspectRatio', $this->el['customAspectRatio']) && $this->el['customAspectRatio']['enableCustomAspectRatio'];
		if(!$has_custom_aspect_ratio && array_key_exists('fixedHeight', $this->el) && array_key_exists('enableFixedHeight', $this->el['fixedHeight']) && $this->el['fixedHeight']['enableFixedHeight']) {
			$fixedHeight = $this->el['fixedHeight'];
			$divCSS = 'style="height: calc('.$fixedHeight['height'].');"';
			$pb_markup = '';
		}

		// do we have rounded corners?
		if(array_key_exists('borderRadius', $this->el) && array_key_exists('enableBorderRadius', $this->el['borderRadius']) && $this->el['borderRadius']['enableBorderRadius']) {
			// is any value bigger than 0?
			if($this->el['borderRadius']['topLeft'] > 0 || $this->el['borderRadius']['topRight'] > 0 || $this->el['borderRadius']['bottomRight'] > 0 || $this->el['borderRadius']['bottomLeft'] > 0) {
				// does $extra_class contain image-transparent-bg?
				// because if we have rounded corners, we want no background color for the padding bottom container
				if(strpos($extra_class, 'image-transparent-bg') !== false) {
					$extra_class .= ' fixed-height-image-transparent-bg';
				}
			}
		}

		// Scroll-driven animations are no longer applied here — they're
		// applied centrally by LayEl::getMarkup → LayElFunctions::wrapWithScrollAnimation
		// which wraps the whole element markup (including the .ph placeholder
		// and caption) so they scale together.

		if( $in_elementgrid == true ) {
            // if were in an elementgrid, we want the caption to be outside the img, so custom aspect ratio works without hiding the caption
            // and we dont need the height style for fixed height
			$return = '<div class="img '.$extra_class.' '.$lightboxoff.' '.$isscrolltotop.'" data-id='.$attid.' '.$lightbox_caption_attr.' >'.$openingAnchor.$markup.$endingAnchor.$pb_markup.$text_on_element_gradient.$text_on_element_overlay.'</div>'.$caption;
		}
		else {
            // i changed the caption to be outside the img here too, so we can use fixed height without hiding the caption
			$return = '<div '.$divCSS.' class="img '.$extra_class.' '.$lightboxoff.' '.$isscrolltotop.'" data-id='.$attid.' '.$lightbox_caption_attr.' >'.$openingAnchor.$markup.$endingAnchor.$pb_markup.$text_on_element_gradient.$text_on_element_overlay.'</div>'.$caption;
		}

		// Custom aspect ratio (phone): output style block so it isn't overridden by inline styles; include desktop query so inline can be omitted
		if ( $use_phone_ar_stylesheet ) {
			$selector = Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, ' .col.id-' . (int) $this->el['relid'] . ' img' );
			$breakpoint = isset(LayFrontend_Options::$breakpoint) ? (int) LayFrontend_Options::$breakpoint : (int) get_option('lay_breakpoint', 600);
			// Desktop: same as current (customAspectRatio, fixedHeight, or natural)
			$desktop_css = '';
			if ( array_key_exists('customAspectRatio', $this->el) && array_key_exists('enableCustomAspectRatio', $this->el['customAspectRatio']) && $this->el['customAspectRatio']['enableCustomAspectRatio'] ) {
				$car = $this->el['customAspectRatio'];
				$dw = isset($car['width']) && $car['width'] > 0 ? (int) $car['width'] : 16;
				$dh = isset($car['height']) && $car['height'] > 0 ? (int) $car['height'] : 9;
				$desktop_css = 'aspect-ratio:'.$dw.'/'.$dh.';object-fit: '.(isset($car['objectFit']) ? $car['objectFit'] : 'cover').';object-position: '.(isset($car['objectPosition']) ? $car['objectPosition'] : 'center center').';';
			} elseif ( array_key_exists('fixedHeight', $this->el) && array_key_exists('enableFixedHeight', $this->el['fixedHeight']) && $this->el['fixedHeight']['enableFixedHeight'] ) {
				$fh = $this->el['fixedHeight'];
				$desktop_css = 'height: calc('.$fh['height'].');object-fit: '.$fh['objectFit'].';object-position: '.$fh['objectPosition'].';';
			} else {
				$full = array_key_exists('attid', $this->el) ? wp_get_attachment_image_src( $this->el['attid'], 'full' ) : false;
				if ( $full !== false && is_array($full) && $full[1] > 0 && $full[2] > 0 ) {
					$desktop_css = 'aspect-ratio:'.(int)$full[1].'/'.(int)$full[2].';';
				}
			}
			$car = $this->el['customAspectRatioPhone'];
			$pw = isset($car['width']) && $car['width'] > 0 ? (int) $car['width'] : 16;
			$ph_val = isset($car['height']) && $car['height'] > 0 ? (int) $car['height'] : 9;
			$phone_css = 'aspect-ratio:'.$pw.'/'.$ph_val.';object-fit: '.(isset($car['objectFit']) ? $car['objectFit'] : 'cover').';object-position: '.(isset($car['objectPosition']) ? $car['objectPosition'] : 'center center').';';
			$selector_ph = Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_container_selector, ' .col.id-' . (int) $this->el['relid'] . ' .ph' );
			$desktop_ph_pb = Lay_LayoutFunctions::replaceCommaWithDot( $pb );
			$desktop_ph_css = 'padding-bottom:'.$desktop_ph_pb.'%;';
			$phone_ph_pb = $pw > 0 ? Lay_LayoutFunctions::replaceCommaWithDot( ( $ph_val / $pw ) * 100 ) : 56.25;
			$phone_ph_css = 'padding-bottom:'.$phone_ph_pb.'%;';
			$style = '';
			if ( $desktop_css !== '' ) {
				$style .= '@media (min-width: '.($breakpoint + 1).'px) { '.$selector.' { '.$desktop_css.' } '.$selector_ph.' { '.$desktop_ph_css.' } }';
			}
			$style .= '@media (max-width: '.$breakpoint.'px) { '.$selector.' { '.$phone_css.' } '.$selector_ph.' { '.$phone_ph_css.' } }';
			$return = '<style>'.$style.'</style>' . $return;
		}

        return $return;
    }


    public function getSidebarCSS($options = array()){
		$use_phone_ar_stylesheet = !empty($options['no_custom_phone_layout']) && array_key_exists('grid_container_selector', $options) && $options['grid_container_selector'] !== ''
			&& array_key_exists('customAspectRatioPhone', $this->el) && is_array($this->el['customAspectRatioPhone'])
			&& !empty($this->el['customAspectRatioPhone']['enableCustomAspectRatioPhone']);

        $sidebar_css_for_img_tag = '';

        if(array_key_exists('borderRadius', $this->el)) {
            $br = $this->el['borderRadius'];
            $br_css = '';
            $br_css .= 'border-radius: '.$br['topLeft'].$br['topLeftMu'].' '.$br['topRight'].$br['topRightMu'].' '.$br['bottomRight'].$br['bottomRightMu'].' '.$br['bottomLeft'].$br['bottomLeftMu'].';';
            if (array_key_exists('cornerShape', $br) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round') {
                $br_css .= 'corner-shape: '.$br['cornerShape'].';';
            }
            $br_css .= 'overflow: hidden;';
            $sidebar_css_for_img_tag .= $br_css;
        }

        // Handle border
        if(array_key_exists('border', $this->el) && $this->el['border']['enableBorder']) {
            $border = $this->el['border'];
            $borderColor = $border['borderColor'];
            $borderOpacity = $border['borderOpacity'] / 100;
            $borderWidth = $border['borderWidth'];
            
            // Convert hex to rgba for opacity
            $r = hexdec(substr($borderColor, 1, 2));
            $g = hexdec(substr($borderColor, 3, 2));
            $b = hexdec(substr($borderColor, 5, 2));
            
            $border_css = '';
            $border_css .= 'border-width: '.$borderWidth['top'].'px '.$borderWidth['right'].'px '.$borderWidth['bottom'].'px '.$borderWidth['left'].'px;';
            $border_css .= 'border-color: rgba('.$r.','.$g.','.$b.','.$borderOpacity.');';
            $border_css .= 'border-style: solid;';
            $sidebar_css_for_img_tag .= $border_css;
        }

        // Handle shadow
        if(array_key_exists('shadow', $this->el) && $this->el['shadow']['enableShadow']) {
            $shadow = $this->el['shadow'];
            $shadowColor = $shadow['color'];
            $shadowOpacity = $shadow['opacity'] / 100;
            
            // Convert hex to rgba for opacity
            $r = hexdec(substr($shadowColor, 1, 2));
            $g = hexdec(substr($shadowColor, 3, 2));
            $b = hexdec(substr($shadowColor, 5, 2));
            
            $inset = $shadow['inset'] ? 'inset ' : '';
            $shadow_css = '';
            $shadow_css .= 'box-shadow: '.$inset.$shadow['offsetX'].'px '.$shadow['offsetY'].'px '.$shadow['blur'].'px '.$shadow['spread'].'px rgba('.$r.','.$g.','.$b.','.$shadowOpacity.');';
            
            $sidebar_css_for_img_tag .= $shadow_css;
        }

		// Omit aspect-ratio/object-fit/object-position from inline when we output phone AR in a style block (so media queries win)
		if ( !$use_phone_ar_stylesheet ) {
			if(array_key_exists('customAspectRatio', $this->el) && array_key_exists('enableCustomAspectRatio', $this->el['customAspectRatio']) && $this->el['customAspectRatio']['enableCustomAspectRatio']) {
				$car = $this->el['customAspectRatio'];
				$w = isset($car['width']) && $car['width'] > 0 ? (int)$car['width'] : 16;
				$h = isset($car['height']) && $car['height'] > 0 ? (int)$car['height'] : 9;
				$sidebar_css_for_img_tag .= 'aspect-ratio:'.$w.'/'.$h.';';
				$sidebar_css_for_img_tag .= 'object-fit: '.(isset($car['objectFit']) ? $car['objectFit'] : 'cover').';';
				$sidebar_css_for_img_tag .= 'object-position: '.(isset($car['objectPosition']) ? $car['objectPosition'] : 'center center').';';
			}
			else if(array_key_exists('fixedHeight', $this->el) && array_key_exists('enableFixedHeight', $this->el['fixedHeight']) && $this->el['fixedHeight']['enableFixedHeight']) {
				$fixedHeight = $this->el['fixedHeight'];
				$fixedHeightCSS = '';
				$fixedHeightCSS .= 'height: calc('.$fixedHeight['height'].');';
				$fixedHeightCSS .= 'object-fit: '.$fixedHeight['objectFit'].';';
				$fixedHeightCSS .= 'object-position: '.$fixedHeight['objectPosition'].';';
				$sidebar_css_for_img_tag .= $fixedHeightCSS;
			}
		}

        return $sidebar_css_for_img_tag;
    }

}