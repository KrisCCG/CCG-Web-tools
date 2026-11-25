<?php

// todo: different css for desktop, tablet, phone

class LayThumbnailgrid{

    public $el;
    public $config;
    public $postid;

    public function __construct($el, $postid){
        $this->el = $el;
        $this->postid = $postid;
    }

    public function getMarkup($options = array()){
        $this->config = $this->el['config'];
		$relid = array_key_exists('relid', $this->el) ? $this->el['relid'] : '';
        
        // PERFORMANCE OPTIMIZATION: Cache entire rendered markup (skip if random order)
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
            
            // Include scrollAnimation in the cache key — when
            // `animateIndividualElements` is on, the per-thumbnail markup
            // includes the scroll-anim wrapper baked in, so a config change
            // there must bust the cached markup.
            $scrollAnimationForCache = isset( $this->el['scrollAnimation'] ) ? $this->el['scrollAnimation'] : null;
            // Markup-version constant: bump when we change the per-thumbnail
            // HTML structure in `thumbnail.php` so deploys invalidate every
            // cached transient (otherwise pages keep serving the old markup
            // until something else rotates `lay_project_link_cache_version`,
            // which only fires on post saves).
            $markup_cache_key = 'thumbgrid_markup_v3_' . $current_lang . '_' . get_option('lay_project_link_cache_version', 0) . '_' . md5(serialize(array(
                'config' => $this->config,
                'postid' => $this->postid,
                'relid' => $relid,
                'scrollAnimation' => $scrollAnimationForCache,
            )));
            
            // Skip cache in customizer to show fresh content with updated settings
            if (!is_customize_preview()) {
                $cached_markup = get_transient($markup_cache_key);
                
                if ($cached_markup !== false) {
                    return $cached_markup;
                }
            }
        }
        
        $thumbs_data_array = array();
        $thumbs_array = array();
		$id_selector = $relid != '' ? '.id-'.$relid : '';

        $limitThumbnails = array_key_exists('limitThumbnails', $this->config) ? $this->config['limitThumbnails'] : 0;

        $rowsDefaults = array('desktop' => 3, 'tablet' => 3, 'phone' => 6);
        foreach ($rowsDefaults as $device => $defaultRows) {
            if (!array_key_exists($device, $this->config)) {
                continue;
            }
            if (!array_key_exists('rowsToShow', $this->config[$device]) || $this->config[$device]['rowsToShow'] === '') {
                $this->config[$device]['rowsToShow'] = $defaultRows;
            } else {
                $this->config[$device]['rowsToShow'] = intval($this->config[$device]['rowsToShow']);
            }
        }

        $showMoreButtonEnabled = isset($this->config['showMoreButton']) && ($this->config['showMoreButton'] === true || $this->config['showMoreButton'] === 'true');
        $hasRowLimit = false;
        foreach ($rowsDefaults as $device => $defaultRows) {
            if (!array_key_exists($device, $this->config)) {
                continue;
            }
            $rows = isset($this->config[$device]['rowsToShow']) ? intval($this->config[$device]['rowsToShow']) : $defaultRows;
            if ($rows > 0) {
                $hasRowLimit = true;
                break;
            }
        }
        $showMoreButton = $showMoreButtonEnabled && $hasRowLimit;
        $allowedStyles = array('laybutton1', 'laybutton2', 'laybutton3');
        $showMoreButtonStyle = isset($this->config['showMoreButtonStyle']) ? $this->config['showMoreButtonStyle'] : 'laybutton1';
        if (!in_array($showMoreButtonStyle, $allowedStyles, true)) {
            $showMoreButtonStyle = 'laybutton1';
        }
        $moreButtonText = isset($this->config['moreButtonText']) ? $this->config['moreButtonText'] : __('Load More', 'laytheme');
        $allowedAlignments = array('start', 'center', 'end');
        $moreButtonAlignment = isset($this->config['moreButtonAlignment']) ? $this->config['moreButtonAlignment'] : 'center';
        if (!in_array($moreButtonAlignment, $allowedAlignments, true)) {
            $moreButtonAlignment = 'center';
        }
        $moreButtonSpaceTop = isset($this->config['moreButtonSpaceTop']) ? intval($this->config['moreButtonSpaceTop']) : 20;

        $useImagehover = false;
        if( isset($this->config['useImagehover']) && class_exists('LayThemeImagehover') ) {
            $useImagehover = $this->config['useImagehover'] ? true : false;
        }
        $use_tag_filter = ( array_key_exists('showtagfilter', $this->config) && ($this->config['showtagfilter'] === 'true' || $this->config['showtagfilter'] === true) ) ? true : false;
        $use_cat_filter = ( array_key_exists('showfilter', $this->config) && ($this->config['showfilter'] === 'true' || $this->config['showfilter'] === true) ) ? true : false;
        $sort_by_date_only = ( array_key_exists('sortbydate', $this->config) && ($this->config['sortbydate'] === 'true' || $this->config['sortbydate'] === true) ) ? true : false;
        $show_all_projects = ( array_key_exists('showAllProjects', $this->config) && ($this->config['showAllProjects'] === 'true' || $this->config['showAllProjects'] === true) ) ? true : false;

        // Get breakpoints for responsive design
        $phone_breakpoint = get_option('lay_breakpoint', 600);
        $phone_breakpoint = (int)$phone_breakpoint;
        $tablet_breakpoint = get_option('lay_tablet_breakpoint', 1024);
        $tablet_breakpoint = (int)$tablet_breakpoint;

        // Device-specific aspect ratio settings
        $customAspectRatioDesktop = array_key_exists('customAspectRatio', $this->config['desktop']) ? $this->config['desktop']['customAspectRatio'] : false;
        $customAspectRatioWDesktop = array_key_exists('customAspectRatioW', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioW'] : 16;
        $customAspectRatioHDesktop = array_key_exists('customAspectRatioH', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioH'] : 9;

        $customAspectRatioTablet = array_key_exists('customAspectRatio', $this->config['tablet']) ? $this->config['tablet']['customAspectRatio'] : false;
        $customAspectRatioWTablet = array_key_exists('customAspectRatioW', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioW'] : 16;
        $customAspectRatioHTablet = array_key_exists('customAspectRatioH', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioH'] : 9;

        $customAspectRatioPhone = array_key_exists('customAspectRatio', $this->config['phone']) ? $this->config['phone']['customAspectRatio'] : false;
        $customAspectRatioWPhone = array_key_exists('customAspectRatioW', $this->config['phone']) ? $this->config['phone']['customAspectRatioW'] : 16;
        $customAspectRatioHPhone = array_key_exists('customAspectRatioH', $this->config['phone']) ? $this->config['phone']['customAspectRatioH'] : 9;

        $customAspectRatioObjectPositionDesktop = array_key_exists('customAspectRatioObjectPosition', $this->config['desktop']) ? $this->config['desktop']['customAspectRatioObjectPosition'] : 'center center';
        $customAspectRatioObjectPositionTablet = array_key_exists('customAspectRatioObjectPosition', $this->config['tablet']) ? $this->config['tablet']['customAspectRatioObjectPosition'] : 'center center';
        $customAspectRatioObjectPositionPhone = array_key_exists('customAspectRatioObjectPosition', $this->config['phone']) ? $this->config['phone']['customAspectRatioObjectPosition'] : 'center center';

        // if filter is true, load all thumbnails, not just the ones of the current category
        // When filters are enabled (category or tag), always fetch all projects so filters can work properly

        
        if( $use_cat_filter == true || $use_tag_filter == true ) {
            // Always fetch all projects when filters are enabled
            // Pass the default category ID so custom sort can work properly
            $default_cat_id = $this->config['cat_id'];

            // if showAllProjects is true, this will also return all thumbnails:
            $thumbs_data_array = LayThumnbailgridFunctions::get_thumbnails_data_for_thumbnailgrid_all_projects( $this->config, $this->postid, $default_cat_id );
            
            // while we return all thumbnails, we initially only show the thumbnails of the chosen category, if showAllProjects is false:
            foreach( $thumbs_data_array as $thumb_data ) {
                $display = in_array($default_cat_id, $thumb_data['categories']) ? true : false;
                $thumb_data['classes'] = $display ? 'show-filtered' : 'hide-filtered';
                $thumb_data['display'] = $display;
                $thumb_data['filter_id'] = '['.implode(',', $thumb_data['categories']).']';
                $thumbs_array []= new LayThumbnail( $thumb_data, $useImagehover, false );
            }

        } else {
            // No filters enabled - only get markup of thumbnails that need to be shown
            if($show_all_projects){
                $thumbs_data_array = LayThumnbailgridFunctions::get_thumbnails_data_for_thumbnailgrid_all_projects( $this->config, $this->postid, false );
                foreach( $thumbs_data_array as $thumb_data ) {
                    $thumbs_array []= new LayThumbnail( $thumb_data, $useImagehover, false );
                }
            } else {
                $thumbs_data_array = LayThumnbailgridFunctions::get_thumbnails_data_for_thumbnailgrid_by_catid( $this->config['cat_id'], $this->config, $this->postid );
                foreach( $thumbs_data_array as $thumb_data ) {
                    $thumbs_array []= new LayThumbnail( $thumb_data, $useImagehover, false );
                }
            }
        }
        
        $thumb_count = count($thumbs_array);

        // when not using any filters, only return limited amount of thumbnails
        // otherwise, return all thumbnails and hiding them is done with js
        if( $limitThumbnails > 0 && $use_cat_filter == false && $use_tag_filter == false ) {
            $thumbs_array_clone = [];
            $limitThumbnails = $limitThumbnails < count($thumbs_array) ? $limitThumbnails : count($thumbs_array);
            for( $i = 0; $i < $limitThumbnails; $i++ ) {
                $thumbs_array_clone []= $thumbs_array[$i];
            }
            $thumbs_array = $thumbs_array_clone;
        }

        // Filter thumbnails by visible tag filters if onlyShowVisibleTagFilters is enabled
        $only_show_visible_tag_filters = array_key_exists('onlyShowVisibleTagFilters', $this->config) && ($this->config['onlyShowVisibleTagFilters'] === true || $this->config['onlyShowVisibleTagFilters'] === 'true');
        if( $only_show_visible_tag_filters && $use_tag_filter ) {
            // Get visible tag IDs from sortedTagFilters
            $visible_tag_ids = array();
            if( array_key_exists('sortedTagFilters', $this->config) && is_array($this->config['sortedTagFilters']) ) {
                foreach( $this->config['sortedTagFilters'] as $tag_filter ) {
                    // Default to visible if not specified (backward compatibility)
                    if( !array_key_exists('visible', $tag_filter) || $tag_filter['visible'] !== false ) {
                        if( array_key_exists('id', $tag_filter) ) {
                            $visible_tag_ids[] = intval($tag_filter['id']);
                        }
                    }
                }
            }

            // Filter out thumbnails that don't have at least one visible tag
            if( count($visible_tag_ids) > 0 ) {
                $filtered_thumbs_array = array();
                foreach( $thumbs_array as $thumb ) {
                    $post_id = $thumb->el['postid'];
                    $tags = get_the_tags($post_id);
                    $thumbnail_tag_ids = array();
                    if( $tags ) {
                        foreach( $tags as $tag ) {
                            $thumbnail_tag_ids[] = intval($tag->term_id);
                        }
                    }
                    // Check if thumbnail has at least one visible tag
                    $has_visible_tag = count(array_intersect($thumbnail_tag_ids, $visible_tag_ids)) > 0;
                    if( $has_visible_tag ) {
                        $filtered_thumbs_array[] = $thumb;
                    }
                }
                $thumbs_array = $filtered_thumbs_array;
            } else {
                // No visible tags configured, hide all thumbnails
                $thumbs_array = array();
            }
        }
        
        $thumbs_markup = '';
        $isInsideThumbnailGridWithFilter = $use_tag_filter || $use_cat_filter;

        // When this grid has `animateIndividualElements` enabled in its
        // scrollAnimation, pass the grid el down so each `LayThumbnail`
        // wraps its inner markup with the shared scroll-anim config (each
        // thumbnail then scrolls in independently).
        $scrollAnimationGridEl = null;
        if ( isset( $this->el['scrollAnimation'] )
            && is_array( $this->el['scrollAnimation'] )
            && ! empty( $this->el['scrollAnimation']['enableScrollAnimation'] )
            && ! empty( $this->el['scrollAnimation']['animateIndividualElements'] ) ) {
            $scrollAnimationGridEl = $this->el;
        }

        foreach( $thumbs_array as $thumb ) {
            // hide projectthumbnail of currently shown project
            if( LayFrontend_Options::$hide_current_project_from_thumbnailgrid == 'on' && $thumb->el['postid'] == $this->postid ){
                continue;
            }
            $thumbs_markup .= $thumb->getMarkup(array(
                'isInsideThumbnailGridWithFilter' => $isInsideThumbnailGridWithFilter,
                'scrollAnimationGridEl' => $scrollAnimationGridEl,
            ));
        }

        $filter = '';
        $tagsfilter = '';
        $class = '';

        if( $use_cat_filter ) {
            $class .= ' use-filter';
        } else {
            $class .= ' no-cats-filter';
        }

        if( $use_tag_filter ) {
            $class .= ' use-tags-filter';
        } else {
            $class .= ' no-tags-filter';
        }

        $dataAttrs = '';

        if( $useImagehover ) {
            $dataAttrs = 'data-imagehover';
        }

        $filter = LayThumnbailgridFunctions::lay_get_thumbnailgrid_filter_markup($this->config);

        // Generate dynamic CSS for device-specific aspect ratios
        $style = '';
        if( $customAspectRatioDesktop || $customAspectRatioTablet || $customAspectRatioPhone ) {
            $style = '<style>';
            
            // Desktop styles (min-width: tablet_breakpoint + 1)
            if( $customAspectRatioDesktop ) {
                $aspectRatioDesktop = ($customAspectRatioHDesktop / $customAspectRatioWDesktop) * 100;
                $style .= '
                @media (min-width: '.($tablet_breakpoint+1).'px){
                    '.$id_selector.' .thumb-collection .thumb .ph {
						padding-bottom: '.$aspectRatioDesktop.'%!important;
                    }
                    '.$id_selector.' .thumb-collection .thumb img,
					.thumb-collection .thumb video {
                        object-fit: cover;
						height: 100%!important;
						object-position: '.esc_attr($customAspectRatioObjectPositionDesktop).';
                    }
                }';
            }
            
            // Tablet styles (max-width: tablet_breakpoint and min-width: phone_breakpoint + 1)
            if( $customAspectRatioTablet ) {
                $aspectRatioTablet = ($customAspectRatioHTablet / $customAspectRatioWTablet) * 100;
                $style .= '
                @media (max-width: '.($tablet_breakpoint).'px) and (min-width: '.($phone_breakpoint+1).'px){
                    '.$id_selector.' .thumb-collection .thumb .ph {
						padding-bottom: '.$aspectRatioTablet.'%!important;
                    }
                    '.$id_selector.' .thumb-collection .thumb img,
					.thumb-collection .thumb video {
                        object-fit: cover;
						height: 100%!important;
						object-position: '.esc_attr($customAspectRatioObjectPositionTablet).';
                    }
                }';
            }
            
            // Phone styles (max-width: phone_breakpoint)
            if( $customAspectRatioPhone ) {
                $aspectRatioPhone = ($customAspectRatioHPhone / $customAspectRatioWPhone) * 100;
                $style .= '
                @media (max-width: '.($phone_breakpoint).'px){
                    '.$id_selector.' .thumb-collection .thumb .ph {
						padding-bottom: '.$aspectRatioPhone.'%!important;
					}
                    '.$id_selector.' .thumb-collection .thumb img,
					.thumb-collection .thumb video {
                        object-fit: cover;
						height: 100%!important;
						object-position: '.esc_attr($customAspectRatioObjectPositionPhone).';
                    }
                }';
            }
            
            $style .= '</style>';
        }

        // Rounded Corners (px) — applied to `.thumb-rel` (the wrapper around
        // .ph + on-image text overlays) with overflow:hidden so all inner
        // content (image, custom-aspect-ratio padding box, on-image text
        // gradient, etc.) gets clipped at the rounded corner. Scoped via
        // `$id_selector` so only this grid instance is affected.
        $roundedCorners = array_key_exists('roundedCorners', $this->config) ? intval($this->config['roundedCorners']) : 0;
        if ( $roundedCorners > 0 ) {
            $roundedCornersCss = $id_selector . ' .thumb-collection .thumb .thumb-rel {' .
                'border-radius:' . $roundedCorners . 'px;' .
                'overflow:hidden;' .
                '}';
            if ( $style !== '' ) {
                $style = str_replace( '</style>', $roundedCornersCss . '</style>', $style );
            } else {
                $style = '<style>' . $roundedCornersCss . '</style>';
            }
        }

        // Build final markup
        $loadmore_markup = '';
        if ($showMoreButton) {
            $loadmore_markup = '<div class="lay-thumbnailgrid-load-more-wrap" style="justify-content:'.$moreButtonAlignment.';margin-top:'.$moreButtonSpaceTop.'px;">
                <div class="lay-thumbnailgrid-load-more '.esc_attr($showMoreButtonStyle).'">'.esc_html($moreButtonText).'</div>
            </div>';
        }

        $final_markup = '<div class="thumbs-collection-region layout-type-'.$this->config['layoutType'].' '.$class.'" data-config="'.htmlspecialchars(json_encode($this->config), ENT_QUOTES, 'UTF-8').'" '.$dataAttrs.' data-rows-shown="1">
            <div class="lay-thumbnailgrid-filter-wrap">'.$filter.'</div>
            <div class="thumb-collection">
                '.$thumbs_markup.'
            </div>
            '.$loadmore_markup.'
        </div>'.$style;
        
        // PERFORMANCE: Cache the rendered markup (skip if random order)
        if (!$randomorder && !$skip_cache_for_raw_layout && isset($markup_cache_key)) {
            set_transient($markup_cache_key, $final_markup, 0); // 0 = never expires (cleared on post save/delete)
        }
        
        return $final_markup;
    }

}
