<?php

class LayElementGrid{

    public $el;
    public $config;
    public $elements;
    public $device;

    public function __construct($el, $device = 'desktop'){
        
        $this->el = $el;
        $this->device = $device;
    }

    public function getMarkup($options = array()){
        $this->config = $this->el['config'];
        $randomorder = array_key_exists('randomOrder', $this->config) ? $this->config['randomOrder'] : false;
        $relid = isset($this->el['relid']) ? $this->el['relid'] : '';
        $skip_cache_for_raw_layout = class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache();
        
        // PERFORMANCE: Cache rendered markup (skip if random order)
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
            
            // relid in key so cached markup (with .id-{relid} in styles) is per-instance (same rule as thumbnailgrid)
            // Include scrollAnimation in the cache key so toggling
            // `animateIndividualElements` (which bakes the scroll-anim wrap
            // into each item's inner markup) busts the cache.
            $scrollAnimationForCache = isset( $this->el['scrollAnimation'] ) ? $this->el['scrollAnimation'] : null;
            $cache_key = 'elementgrid_markup_' . $current_lang . '_' . md5(serialize(array(
                'config' => $this->config,
                'device' => $this->device,
                'relid' => $relid,
                'scrollAnimation' => $scrollAnimationForCache,
            )));
            
            // Skip cache in customizer to show fresh content with updated settings
            if (!is_customize_preview()) {
                $cached = get_transient($cache_key);
                if ($cached !== false) {
                    return $cached;
                }
            }
        }
        
        $this->elements = $this->config['elements'];
        if($randomorder){
            shuffle($this->elements);
        }
        $markup = '';
        $device = $this->device;

        $showMoreButtonEnabled = array_key_exists('showMoreButton', $this->config) && ($this->config['showMoreButton'] === 'true' || $this->config['showMoreButton'] === true);
        $rowsDefaults = array('desktop' => 3, 'tablet' => 3, 'phone' => 6);
        foreach (array('desktop', 'tablet', 'phone') as $deviceKey) {
            if (!array_key_exists($deviceKey, $this->config)) {
                continue;
            }
            if (!array_key_exists('rowsToShow', $this->config[$deviceKey])) {
                $this->config[$deviceKey]['rowsToShow'] = $rowsDefaults[$deviceKey];
            }
        }
        $hasRowLimit = false;
        foreach (array('desktop', 'tablet', 'phone') as $deviceKey) {
            if (!array_key_exists($deviceKey, $this->config)) {
                continue;
            }
            $rows = array_key_exists('rowsToShow', $this->config[$deviceKey]) ? intval($this->config[$deviceKey]['rowsToShow']) : $rowsDefaults[$deviceKey];
            if ($rows > 0) {
                $hasRowLimit = true;
                break;
            }
        }
        $showMoreButton = $showMoreButtonEnabled && $hasRowLimit;
        $moreButtonAlignment = array_key_exists('moreButtonAlignment', $this->config) ? $this->config['moreButtonAlignment'] : 'center';
        $allowedAlignments = array('start', 'center', 'end');
        if( !in_array($moreButtonAlignment, $allowedAlignments, true) ) {
            $moreButtonAlignment = 'center';
        }
        $moreButtonSpaceTop = array_key_exists('moreButtonSpaceTop', $this->config) ? intval($this->config['moreButtonSpaceTop']) : 20;
        $showMoreButtonStyle = array_key_exists('showMoreButtonStyle', $this->config) ? $this->config['showMoreButtonStyle'] : 'laybutton1';
        $allowedStyles = array('laybutton1', 'laybutton2', 'laybutton3');
        if( !in_array($showMoreButtonStyle, $allowedStyles, true) ) {
            $showMoreButtonStyle = 'laybutton1';
        }
        $moreButtonText = array_key_exists('moreButtonText', $this->config) ? $this->config['moreButtonText'] : __('Load More', 'laytheme');
        $sameheight = $this->config['layoutType'] == 'same-height';
        $height = false;
        if($sameheight){
            $height = true;
        }

        $lightboxoff_all_images = array_key_exists('lightboxoff', $this->el) ? $this->el['lightboxoff'] : false;

        $ix = 0;
        foreach( $this->elements as $el ) {
            $caption = '';
            switch( $el['type'] ) {
                case 'img':
                case 'html5video':
                    $el['captionMarkup'] = LayElFunctions::getCaptionMarkup($el);
            }
            $element = false;
            $sizeDesktop = array_key_exists('desktop', $el) ? $el['desktop']['size'] : 1;
            $sizeTablet = array_key_exists('tablet', $el) ? $el['tablet']['size'] : 1;
            $sizePhone = array_key_exists('phone', $el) ? $el['phone']['size'] : 1;

            switch( $el['type'] ) {
                case "postThumbnail":
                    $element = new LayThumbnail($el, false, $height);
                break;
                case "text":
                    $element = new LayText($el, $device);
                break;
                case "img":
                    if(!array_key_exists('lightboxoff', $el)) {
                        $el['lightboxoff'] = $lightboxoff_all_images;
                    }
                    $element = new LayImg($el, $height);
                break;
                case "html5video":
                    $element = new LayHTML5Video($el, $height);
                break;
                case "video":
                    $element = new LayVideo($el, $height);
                break;
                case "carousel":
                    if($sameheight){
                        // todo: carousels in sameheight elementgrid should be able to have different heights for desktop, tablet, phone
                        // here, we only set the desktop height :D
                        $h = $this->config['desktop']['elementHeight'];
                        $el['carousel_options']['fixedHeight'] = $h.'px';
                        $el['carousel_options']['size'] = 'fixedHeight';
                        // also we set this class to prevent the carousel js from setting widths with js as this breaks the carousel width :D
                        $el['carousel_options']['extraclass'] = 'in-same-height-elementgrid';
                        $element = new LayCarousel($el);

                    } else {
                        $element = new LayCarousel($el);
                    }
                break;
            }
            if( $element ) {
                $elementClasses = 'element-wrap show-elementgrid';
                $inner = $element->getMarkup(array('in-elementgrid' => true));
                // When the parent elementgrid has `animateIndividualElements`
                // turned on in its scrollAnimation, wrap each inner item
                // (NOT the outer .element-wrap, which is masonry-positioned)
                // so each cell scrolls in independently with the grid's
                // shared scrollAnimation config. Also flag the wrap with
                // `no-animate-in-on-scroll` so the legacy in-view fade
                // (`element_animate_in_on_scroll.js` watches
                // `.col.type-elementgrid .element-wrap` and toggles `.below
                // { opacity: 0 }`) doesn't fight our new scroll-driven
                // animation on the inner wrap.
                if ( isset( $this->el['scrollAnimation'] )
                    && is_array( $this->el['scrollAnimation'] )
                    && ! empty( $this->el['scrollAnimation']['enableScrollAnimation'] )
                    && ! empty( $this->el['scrollAnimation']['animateIndividualElements'] ) ) {
                    $inner = LayElFunctions::wrapMarkupWithScrollAnimationFromEl( $inner, $this->el );
                    $elementClasses .= ' no-animate-in-on-scroll';
                }
                $markup .=
                '<div class="'.$elementClasses.'" data-size-desktop="'.$sizeDesktop.'" data-size-tablet="'.$sizeTablet.'" data-size-phone="'.$sizePhone.'">'
                    .$inner.
                '</div>';
            }
            $ix++;
        }

        unset( $this->config['elements'] );

        $caption = LayElFunctions::getCaptionMarkup($this->el);

        // Generate dynamic CSS for device-specific aspect ratios
        $style = '';
        $customAspectRatioDesktop = array_key_exists('customAspectRatio', $this->config['desktop']) ? $this->config['desktop']['customAspectRatio'] : false;
        $customAspectRatioTablet = array_key_exists('customAspectRatio', $this->config['tablet']) ? $this->config['tablet']['customAspectRatio'] : false;
        $customAspectRatioPhone = array_key_exists('customAspectRatio', $this->config['phone']) ? $this->config['phone']['customAspectRatio'] : false;

		$relid = array_key_exists('relid', $this->el) ? $this->el['relid'] : '';
		$id_selector = $relid != '' ? '.id-'.$relid : '';

		if(!$sameheight){
			if( $customAspectRatioDesktop || $customAspectRatioTablet || $customAspectRatioPhone ) {
				// Get breakpoints for responsive design
				$phone_breakpoint = get_option('lay_breakpoint', 600);
				$phone_breakpoint = (int)$phone_breakpoint;
				$tablet_breakpoint = get_option('lay_tablet_breakpoint', 1024);
				$tablet_breakpoint = (int)$tablet_breakpoint;

				$style = '<style>';
				
				// Desktop styles (min-width: tablet_breakpoint + 1)
				if( $customAspectRatioDesktop ) {
					$customAspectRatioWDesktop = array_key_exists('customAspectRatioW', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioW'] : 16;
					$customAspectRatioHDesktop = array_key_exists('customAspectRatioH', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioH'] : 9;
					$customAspectRatioObjectPositionDesktop = array_key_exists('customAspectRatioObjectPosition', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioObjectPosition'] : 'center center';
					$aspectRatioDesktop = ($customAspectRatioHDesktop / $customAspectRatioWDesktop) * 100;
					$style .= '
					@media (min-width: '.($tablet_breakpoint+1).'px){
						'.$id_selector.' .element-collection .img .ph,
						'.$id_selector.' .element-collection .video .ph,
						'.$id_selector.' .element-collection .html5video .ph,
						'.$id_selector.' .element-collection .thumb .ph{
							padding-bottom: '.$aspectRatioDesktop.'%!important;
						}
						'.$id_selector.' .element-collection .img img,
						'.$id_selector.' .element-collection .video iframe,
						'.$id_selector.' .element-collection .html5video video,
						'.$id_selector.' .element-collection .thumb img{
							object-fit: cover;
							height: 100%!important;
							object-position: '.esc_attr($customAspectRatioObjectPositionDesktop).';
						}
					}';
				}
				
				// Tablet styles (max-width: tablet_breakpoint and min-width: phone_breakpoint + 1)
				if( $customAspectRatioTablet ) {
					$customAspectRatioWTablet = array_key_exists('customAspectRatioW', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioW'] : 16;
					$customAspectRatioHTablet = array_key_exists('customAspectRatioH', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioH'] : 9;
					$customAspectRatioObjectPositionTablet = array_key_exists('customAspectRatioObjectPosition', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioObjectPosition'] : 'center center';
					$aspectRatioTablet = ($customAspectRatioHTablet / $customAspectRatioWTablet) * 100;
					$style .= '
					@media (max-width: '.($tablet_breakpoint).'px) and (min-width: '.($phone_breakpoint+1).'px){
						'.$id_selector.' .element-collection .img .ph,
						'.$id_selector.' .element-collection .video .ph,
						'.$id_selector.' .element-collection .html5video .ph,
						'.$id_selector.' .element-collection .thumb .ph{
							padding-bottom: '.$aspectRatioTablet.'%!important;
						}
						'.$id_selector.' .element-collection .img img,
						'.$id_selector.' .element-collection .video iframe,
						'.$id_selector.' .element-collection .html5video video,
						'.$id_selector.' .element-collection .thumb img{
							height: 100%!important;
							object-position: '.esc_attr($customAspectRatioObjectPositionTablet).';
							object-fit: cover;
						}
					}';
				}
				
				// Phone styles (max-width: phone_breakpoint)
				if( $customAspectRatioPhone ) {
					$customAspectRatioWPhone = array_key_exists('customAspectRatioW', $this->config['phone']) ? $this->config['phone']['customAspectRatioW'] : 16;
					$customAspectRatioHPhone = array_key_exists('customAspectRatioH', $this->config['phone']) ? $this->config['phone']['customAspectRatioH'] : 9;
					$customAspectRatioObjectPositionPhone = array_key_exists('customAspectRatioObjectPosition', $this->config['phone']) ? $this->config['phone']['customAspectRatioObjectPosition'] : 'center center';
					$aspectRatioPhone = ($customAspectRatioHPhone / $customAspectRatioWPhone) * 100;
					$style .= '
					@media (max-width: '.($phone_breakpoint).'px){
						'.$id_selector.' .element-collection .img .ph,
						'.$id_selector.' .element-collection .video .ph,
						'.$id_selector.' .element-collection .html5video .ph,
						'.$id_selector.' .element-collection .thumb .ph{
							padding-bottom: '.$aspectRatioPhone.'%!important;
						}
						'.$id_selector.' .element-collection .img img,
						'.$id_selector.' .element-collection .video iframe,
						'.$id_selector.' .element-collection .html5video video,
						'.$id_selector.' .element-collection .thumb img{
							object-fit: cover;
							height: 100%!important;
							object-position: '.esc_attr($customAspectRatioObjectPositionPhone).';
						}
					}';
				}
				
				$style .= '</style>';
			}
		}

		$roundedCorners = array_key_exists('roundedCorners', $this->config) ? intval($this->config['roundedCorners']) : 0;
		if( $roundedCorners > 0 ) {
			$roundedCornersCss = $id_selector.' .element-collection .img img,'.
				$id_selector.' .element-collection .img .ph,'.
				$id_selector.' .element-collection .html5video video,'.
				$id_selector.' .element-collection .html5video .ph,'.
				$id_selector.' .element-collection .video iframe,'.
				$id_selector.' .element-collection .video .ph,'.
				$id_selector.' .element-collection .thumb img,'.
				$id_selector.' .element-collection .thumb .ph{'.
				'border-radius:'.$roundedCorners.'px;'.
				'}';
			if( $style !== '' ) {
				$style = str_replace('</style>', $roundedCornersCss.'</style>', $style);
			} else {
				$style = '<style>'.$roundedCornersCss.'</style>';
			}
		}

		// Space above caption - responsive per device
		$phone_breakpoint = get_option('lay_breakpoint', 600);
		$phone_breakpoint = (int)$phone_breakpoint;
		$tablet_breakpoint = get_option('lay_tablet_breakpoint', 1024);
		$tablet_breakpoint = (int)$tablet_breakpoint;

		$captionSpaceAboveDesktop = array_key_exists('captionSpaceAbove', $this->config['desktop']) ? floatval($this->config['desktop']['captionSpaceAbove']) : 0;
		$captionSpaceAboveDesktopMu = array_key_exists('captionSpaceAboveMu', $this->config['desktop']) ? $this->config['desktop']['captionSpaceAboveMu'] : 'px';
		$captionSpaceAboveTablet = array_key_exists('captionSpaceAbove', $this->config['tablet']) ? floatval($this->config['tablet']['captionSpaceAbove']) : 0;
		$captionSpaceAboveTabletMu = array_key_exists('captionSpaceAboveMu', $this->config['tablet']) ? $this->config['tablet']['captionSpaceAboveMu'] : 'px';
		$captionSpaceAbovePhone = array_key_exists('captionSpaceAbove', $this->config['phone']) ? floatval($this->config['phone']['captionSpaceAbove']) : 0;
		$captionSpaceAbovePhoneMu = array_key_exists('captionSpaceAboveMu', $this->config['phone']) ? $this->config['phone']['captionSpaceAboveMu'] : 'px';

		if( $captionSpaceAboveDesktop > 0 || $captionSpaceAboveTablet > 0 || $captionSpaceAbovePhone > 0 ) {
			$captionSpaceCss = '';
			if( $captionSpaceAboveDesktop > 0 ) {
				$captionSpaceCss .= '
				@media (min-width: '.($tablet_breakpoint+1).'px){
					'.$id_selector.' .element-collection .element-wrap .caption{
						margin-top: '.$captionSpaceAboveDesktop.$captionSpaceAboveDesktopMu.';
					}
				}';
			}
			if( $captionSpaceAboveTablet > 0 ) {
				$captionSpaceCss .= '
				@media (max-width: '.$tablet_breakpoint.'px) and (min-width: '.($phone_breakpoint+1).'px){
					'.$id_selector.' .element-collection .element-wrap .caption{
						margin-top: '.$captionSpaceAboveTablet.$captionSpaceAboveTabletMu.';
					}
				}';
			}
			if( $captionSpaceAbovePhone > 0 ) {
				$captionSpaceCss .= '
				@media (max-width: '.$phone_breakpoint.'px){
					'.$id_selector.' .element-collection .element-wrap .caption{
						margin-top: '.$captionSpaceAbovePhone.$captionSpaceAbovePhoneMu.';
					}
				}';
			}
			if( $captionSpaceCss !== '' ) {
				if( $style !== '' ) {
					$style = str_replace('</style>', $captionSpaceCss.'</style>', $style);
				} else {
					$style = '<style>'.$captionSpaceCss.'</style>';
				}
			}
		}

        $loadmore = '';
        if( $showMoreButton ) {
            $loadmore = '<div class="lay-elementgrid-load-more-wrap" style="justify-content:'.$moreButtonAlignment.';margin-top:'.$moreButtonSpaceTop.'px;">
                <div class="lay-elementgrid-load-more '.$showMoreButtonStyle.'">'.esc_html($moreButtonText).'</div>
            </div>';
        }

        $wrap_class = 'lay-elementgrid-wrap' . ( $relid != '' ? ' id-' . esc_attr( $relid ) : '' );
        $final_markup = '<div class="'.$wrap_class.'">
            <div class="elements-collection-region '.$this->config['layoutType'].'" data-config="'.htmlspecialchars(json_encode($this->config), ENT_QUOTES, 'UTF-8').'">
                <div class="element-collection">'
                    .$markup.
                '</div>
            </div>
            '.$loadmore.'
            '.$caption.'
        </div>'.$style;
        
        // PERFORMANCE: Cache the rendered markup (skip if random order)
        if (!$randomorder && !$skip_cache_for_raw_layout && isset($cache_key)) {
            set_transient($cache_key, $final_markup, 0);
        }
        
        return $final_markup;
    }

}
