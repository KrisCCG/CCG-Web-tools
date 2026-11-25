<?php
// todo: video thumbnail new
// todo: test mouseover image

class LayThumbnail{

    // Debug flag: Set to true to show category and tag names on thumbnails
    const SHOW_DEBUG_INFO = false;

    public $el;    
    // for elementgrid, sameheight
    public $height;
    public $title_only;

    // todo: remove title only
    public function __construct($el, $title_only = false, $height = false){
        $this->el = $el;
        $this->title_only = $title_only;
        $this->height = $height;
    }

    public function getMarkup($options = array()){
        $isInsideThumbnailGridWithFilter = array_key_exists('isInsideThumbnailGridWithFilter', $options) ? $options['isInsideThumbnailGridWithFilter'] : false;
        if($isInsideThumbnailGridWithFilter == 1){
            $isInsideThumbnailGridWithFilter = true;
        }

        $pb = $this->el['ar'] * 100;

		$markup = '';
        
        $img = LayElFunctions::getLazyImg($this->el);

        $mo_thumb = array_key_exists('mo_sizes', $this->el) ? $this->el['mo_sizes'] : false;
        $mo_thumb_img = "";
        $has_mo_thumb_class = "";

        if( LayFrontend_Options::$misc_options_thumbnail_mouseover_image && is_array($mo_thumb) && !empty($mo_thumb) ){
            
            if ( LTUtility::$isTouchDevice  ) {
                switch(LayFrontend_Options::$fi_mo_touchdevice_behaviour) {
                    case 'mo_dont_show':
                        // 'Do not show mouseover state' means: dont show mouseover image
                        $has_mo_thumb_class = "";
                        $mo_thumb_img = "";
                    break;
                    case 'mo_on_tap_alt':
                        // 'When touching thumbnail, show mouseover state' means: show video, but show mouseover image on tap
                        $mo_thumb_img = LayElFunctions::getMouseOverThumbImg($this->el);
                        $has_mo_thumb_class = "has-mouseover-img";
                    break;
                    case 'mo_always':
                        // 'Always show mouseover state' means: always show mouseover image
                        $img = LayElFunctions::getMouseOverThumbImg($this->el);
                        $mo_thumb_img = "";
                        $has_mo_thumb_class = "";
                    break;
                    case 'mo_on_tap':
                        // 'Show mouseover state on touch or tap, tap again to enter project' means: show video, but show mouseover image on tap, tap again to enter project
                        $mo_thumb_img = LayElFunctions::getMouseOverThumbImg($this->el);
                        $has_mo_thumb_class = "has-mouseover-img";
                    break;
                }
            } else {
                $mo_thumb_img = LayElFunctions::getMouseOverThumbImg($this->el);
                $has_mo_thumb_class = "has-mouseover-img";
            }

        }

        $innerMarkup = $img.$mo_thumb_img;

        $postid = $this->el['postid'];

        $useVideo = false;
        if( LayFrontend_Options::$misc_options_thumbnail_video ) {
            $useVideo = true;
        }
        if(LTUtility::$isTouchDevice && LayFrontend_Options::$misc_options_thumbnail_video_disabled_for_touch_devices){
            $useVideo = false;
        }

        if( $useVideo ) {
            $video_url = array_key_exists('video_url', $this->el) ? $this->el['video_url'] : false;

            $useExternalVideo = get_post_meta($postid, 'lay_useExternalVideo', false);
            $useExternalVideoOn = is_array($useExternalVideo) && count($useExternalVideo) > 0 && $useExternalVideo[0] == 'on' ? "on" : "";
            $externalVideoUrl = get_post_meta($postid, 'lay_externalVideoUrl', true);
            $reallyUsingExternalVideo = false;

            if($useExternalVideoOn == 'on' && $externalVideoUrl != "" && $externalVideoUrl != false) {
                $video_url = $externalVideoUrl;
                $reallyUsingExternalVideo = true;
            } 

            // if on touch device and we have "playsinline" support, then show video with playsinline attribute and autoplay
            // otherwise show image
            // playsinline is supported from iOS 10 WebKit and Chrome > 53
            if( $video_url != false && $video_url != "") {


                if( $reallyUsingExternalVideo ) {
                    $externalVideoW = get_post_meta($postid, 'lay_externalVideoW', true);
                    $externalVideoH = get_post_meta($postid, 'lay_externalVideoH', true);
                    $w = (int)$externalVideoW;
                    if( $w != 0 ) {
                        $pb = (int)$externalVideoH / $w * 100;
                    }
                } else {
                    $w = (int)$this->el['video_w'];
                    if( $w != 0 ) {
                        $pb = (int)$this->el['video_h'] / $w * 100;
                    }
                }

                // is touch device and has playsinline support
                if ( LTUtility::$isTouchDevice && LTUtility::$supportsPlaysInlineOnTouchDevice ) {

                    if($has_mo_thumb_class == "has-mouseover-img") {
                        // we have a mouseoverimage and a video thumbnail and are on touch device:

                        // 'mo_dont_show' => 'Do not show mouseover state',
                        // 'mo_on_tap_alt' => 'When touching thumbnail, show mouseover state',
                        // 'mo_always' => 'Always show mouseover state',
                        // 'mo_on_tap' => 'Show mouseover state on touch or tap, tap again to enter project'

                        $autoplay = false;
                        if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_dont_show' ){
                            $autoplay = true;
                        }
        
                        $model = array(
                            'autoplay' => $autoplay,
                            'loop' => true,
                            'mute' => true,
                            'mp4' => $video_url,
                            'inside_thumbnailgrid_with_filter' => $isInsideThumbnailGridWithFilter
                        );
        
                        $innerMarkup = LayElFunctions::getHTML5VideoMarkupSimple($model);

                        switch(LayFrontend_Options::$fi_mo_touchdevice_behaviour) {
                            case 'mo_dont_show':
                                // 'Do not show mouseover state' means: show video, but dont show mouseover image
                                $has_mo_thumb_class = "";
                            break;
                            case 'mo_on_tap_alt':
                                // 'When touching thumbnail, show mouseover state' means: show video, but show mouseover image on tap
                                $innerMarkup .= $mo_thumb_img;
                            break;
                            case 'mo_always':
                                // 'Always show mouseover state' means: always show mouseover image
                                $innerMarkup = $mo_thumb_img;
                                $has_mo_thumb_class = "";
                            break;
                            case 'mo_on_tap':
                                // 'Show mouseover state on touch or tap, tap again to enter project' means: show video, but show mouseover image on tap, tap again to enter project
                                $innerMarkup .= $mo_thumb_img;
                            break;
                        }

                    } else {
        
                        // we just have a video thumbnail and are on touch device. we dont have a mouseover image.
                        $model = array(
                            'autoplay' => true,
                            'loop' => true,
                            'mute' => true,
                            'mp4' => $video_url,
                            'inside_thumbnailgrid_with_filter' => $isInsideThumbnailGridWithFilter
                        );
        
                        $innerMarkup = LayElFunctions::getHTML5VideoMarkupSimple($model);
                        $has_mo_thumb_class = "";

                    }
                }
                // is not touch device
                if( !LTUtility::$isTouchDevice ) {
                    // when not touchdevice, use mouseover image
                    // when the video thumbnail mouseover behaviour is set to only "play_on_mouseover", then don't use a autoplay attribute!
                    $autoplay = true;
                    if( LayFrontend_Options::$video_thumbnail_mouseover_behaviour == 'play_on_mouseover' ){
                        $autoplay = false;
                    }
                    $model = array(
                        'autoplay' => $autoplay,
                        'loop' => true,
                        'mute' => true,
                        'mp4' => $video_url,
                        'inside_thumbnailgrid_with_filter' => $isInsideThumbnailGridWithFilter
                    );
                    // todo: test this
                    $innerMarkup = LayElFunctions::getHTML5VideoMarkupSimple($model).$mo_thumb_img;
                }
            }
        }
        // #video thumbnail new
       

        $postid = $this->el['postid'];
        $project_link = Setup::get_project_link($postid);
        $link = $project_link['url'];
        $link_target = $project_link['target'];
        $linkHrefAttrs = 'href="' . esc_attr($link) . '"' . ( $link_target !== '' ? ' target="' . esc_attr($link_target) . '" rel="noopener"' : '' );

        $title = $this->el['title'];
        $descr = array_key_exists('descr', $this->el) ? $this->el['descr'] : false;
        $descr = self::apply_qtranslate_to_markup($descr);
        // Remove empty paragraph tags (including <p>&nbsp;</p>, <p> </p>, <p></p>, etc.)
        if( $descr !== false && $descr != '' ) {
            // Remove empty p tags with &nbsp; or whitespace only
            $descr = preg_replace('/<p[^>]*>\s*(&nbsp;|\s)*\s*<\/p>/i', '', $descr);
            // Clean up any extra whitespace that might be left
            $descr = trim($descr);
        }

        $tags = get_the_tags($this->el['postid']);
        $tagArray = array();
        if ($tags) {
            usort($tags, function($a, $b) {
                return $a->term_order - $b->term_order;
            });
            foreach($tags as $tag) {
                $tagArray []= $tag->name;
            }
        }
        // $tagText = implode(', ', $tagArray);
        $tagText = '';
        for($k=0; $k<count($tagArray); $k++){
            $tagText .= '<span>'.$tagArray[$k].'</span>';
            // if($k < count($tagArray)-1){
            // 	$tagText .= ', ';
            // }
        }
        $tagMarkup = '';
        if( $tagText != '' ) {
            $tagMarkup = '<span class="thumbnail-tags '.LayFrontend_Options::$ptags_textformat.'">'.$tagText.'</span>';
        }

        // Apply filter to allow custom markup to be added
        $tagMarkup = apply_filters('lay_thumbnail_tag_markup', $tagMarkup, $postid);
        

        $cats_wp_terms_array = get_the_category($postid);
        $catids = array();

        for( $i = 0; $i<count($cats_wp_terms_array); $i++ ) {
            $cat = $cats_wp_terms_array[$i];
            $catids []= $cat->term_id;
        }

        $dataAttrs = 'data-id="'.$postid.'" data-catid="'.LayElFunctions::stringifyCatIds($catids).'" data-title="'.htmlspecialchars(__($title)).'" data-type="project"';

        $mo_always = "";
        if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == "mo_always" && LTUtility::$isTouchDevice == true ){
            $mo_always = "hover";
        }

        // $mo_always .= ' touchdevice-behaviour-'.LayFrontend_Options::$fi_mo_touchdevice_behaviour;
        

        $showDescr = false;
        if( $descr != false && LayFrontend_Options::$activate_project_description && trim($descr) != ""){
            $showDescr = true;
        }

        // Detect links in description (for special handling to avoid nested anchors)
        $descr_has_link = $showDescr && $descr !== false && $descr !== '' && preg_match('/<\s*a\b[^>]*>/i', $descr) === 1;

        $pt_position = LayFrontend_Options::$pt_position;
        $pd_position = LayFrontend_Options::$pd_position;
        $pd_position_related_to_title = LayFrontend_Options::$pd_position_related_to_title;
        $descrAboveTitle = ( $pd_position_related_to_title === 'above-title' );

        $title_only_display = ""; // Hide image
        $title_only_inlineblock = ""; // Set thumb container to inline, so the imagehover trigger area ends with the text

        if ( $this->title_only == true ) {
            // Since we don't display an image there is no point to position the text
            $aboveImageTextExists = false;
            $pt_on_image = false;
            $onImageTextExists = false;
            $belowImageTextExists = true;

            $title_only_display = "display: none;";
            $title_only_inlineblock = "display: inline-block";
        } else {
            $aboveImageTextExists = $pt_position == "above-image" || ($pd_position == "above-image" && $showDescr) ? true : false;
            $pt_on_image = strpos($pt_position, 'on-image') === false ? false : true;
            $onImageTextExists = $pt_on_image || ($pd_position == "on-image" && $showDescr) ? true : false;
            $belowImageTextExists = $pt_position == "below-image" || ($pd_position == "below-image" && $showDescr) ? true : false;
        }

        $do_disable = $project_link['disabled'];

        // If description contains links, we need to avoid nested anchors by:
        // - turning the outer thumb into a div
        // - using separate links for image (thumb-rel) and title
        $use_split_link_structure = $descr_has_link && !$do_disable;

        // For on-image description with split structure, strip anchor tags but keep their inner content
        $descr_for_on_image = $descr;
        if ( $use_split_link_structure && $onImageTextExists && $showDescr && $descr_for_on_image !== false && $descr_for_on_image !== '' ) {
            // Unwrap <a> tags: keep inner HTML, remove the anchor itself
            $descr_for_on_image = preg_replace('/<\s*a\b[^>]*>(.*?)<\s*\/\s*a\s*>/is', '$1', $descr_for_on_image);
        }

        $anchortag = ($do_disable || $use_split_link_structure) ? 'div' : 'a';
        $href = (!$do_disable && !$use_split_link_structure) ? $linkHrefAttrs : '';

        $orientation = $pb > 100 ? 'portrait' : 'landscape';

        $no_animate_in_on_scroll_class = '';
        if ( is_array( $options ) && ! empty( $options['scrollAnimationGridEl'] ) ) {
            $no_animate_in_on_scroll_class = ' no-animate-in-on-scroll';
        }

        $markup =
        '<'.$anchortag.' class="thumb '.$orientation.' '.$mo_always.' '.$has_mo_thumb_class.' '.($do_disable == true ? 'link-disabled' : '').$no_animate_in_on_scroll_class.'" '.$href.' style="'.$title_only_inlineblock.'" '.$dataAttrs.'>';
            if($aboveImageTextExists){
                $markup .= '<div class="lay-textformat-parent above-image">';
                if($pd_position == "above-image" && $showDescr && $descrAboveTitle){
                    $markup .= '<span class="descr">'.$descr.'</span>';
                }
                if($pt_position == "above-image"){
                    if(LayFrontend_Options::$ptags_position == "above-title"){
                        if ( $use_split_link_structure ) {
                            $markup .= $tagMarkup.'<a class="lay-ignore-linktext-style" '.$linkHrefAttrs.' '.$dataAttrs.'><span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span></a>';
                        } else {
                            $markup .= $tagMarkup.'<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>';
                        }
                    } else {
                        if ( $use_split_link_structure ) {
                            $markup .= '<a class="lay-ignore-linktext-style" '.$linkHrefAttrs.' '.$dataAttrs.'><span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span></a>'.$tagMarkup;
                        } else {
                            $markup .= '<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>'.$tagMarkup;
                        }
                    }
                }
                if($pd_position == "above-image" && $showDescr && !$descrAboveTitle){
                    $markup .= '<span class="descr">'.$descr.'</span>';
                }
                $markup .= '</div>';
            }
        $thumb_rel_css = $this->getThumbRelCSS();
        $thumb_rel_style_attr = $thumb_rel_css !== '' ? ' style="'.esc_attr($thumb_rel_css).'"' : '';

        // open thumb-rel
        if ( $use_split_link_structure ) {
            // Image (and on-image title/descr) become the main project link
            $markup .= '<a class="thumb-rel lay-ignore-linktext-style" '.$linkHrefAttrs.' '.$dataAttrs.$thumb_rel_style_attr.'>';
        } else {
            $markup .= '<div class="thumb-rel"'.$thumb_rel_style_attr.'>';
        }
        if( $onImageTextExists ){
            $markup .= '<div class="lay-textformat-parent titlewrap-on-image">';
            if( $pd_position == "on-image" && $showDescr && $descrAboveTitle ){
                $markup .= '<span class="descr">'.$descr_for_on_image.'</span>';
            }
            if( $pt_on_image ){
                // On-image title sits INSIDE `.thumb-rel`, which is itself an
                // `<a>` in the split-link path (line 344). Wrapping the title
                // in another `<a>` would nest anchors — the HTML parser then
                // splits the outer anchor and produces two empty `<a class=
                // "thumb-rel">` ghosts plus a relocated `.titlewrap-on-image`.
                // Span is fine in both paths: in normal mode the outer
                // `<a class="thumb">` makes it clickable, in split-link mode
                // the surrounding `<a class="thumb-rel">` does.
                if(LayFrontend_Options::$ptags_position == "above-title"){
                    $markup .= $tagMarkup.'<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>';
                } else {
                    $markup .= '<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>'.$tagMarkup;
                }
            }
            if( $pd_position == "on-image" && $showDescr && !$descrAboveTitle ){
                $markup .= '<span class="descr">'.$descr_for_on_image.'</span>';
            }
            $markup .= '</div>';
        }

        $sidebar_css = $this->getSidebarCSS();
        
        $ph_style_parts = array();
        if($this->height == false) {
            $ph_style_parts[] = 'padding-bottom:'.Lay_LayoutFunctions::replaceCommaWithDot($pb).'%;';
        }

        if($sidebar_css != '') {
            $ph_style_parts[] = $sidebar_css;
        }

        if($title_only_display != '') {
            $ph_style_parts[] = $title_only_display;
        }

        $pbstyle = '';
        if(count($ph_style_parts) > 0) {
            $pbstyle = 'style="'.implode('', $ph_style_parts).'"';
        }

        //empty span for bgcolor
        $markup .=
            '<div class="ph" '.$pbstyle.'>'
            .$innerMarkup
            .'<span></span>'
        .'</div>';
        
        // Debug: Show category names and tag names
        $category_debug = '';
        $tag_debug = '';
        if (self::SHOW_DEBUG_INFO && array_key_exists('postid', $this->el) && !empty($this->el['postid'])) {
            $post_id = $this->el['postid'];
            
            // Get categories
            $categories = get_the_category($post_id);
            if ($categories && !is_wp_error($categories) && !empty($categories)) {
                $category_names = array();
                foreach ($categories as $category) {
                    $category_names[] = $category->cat_name;
                }
                if (!empty($category_names)) {
                    $category_debug = '<div style="font-family:Arial, sans-serif;position:absolute;top:5px;left:5px;color:#000;background-color:rgba(255,255,255,0.8);padding:2px 5px;font-size:10px;z-index:9999;line-height:1.2;">' . esc_html(implode(', ', $category_names)) . '</div>';
                }
            }
            
            // Get tags
            $tags = get_the_tags($post_id);
            if ($tags && !is_wp_error($tags) && !empty($tags)) {
                $tag_names = array();
                foreach ($tags as $tag) {
                    $tag_names[] = $tag->name;
                }
                if (!empty($tag_names)) {
                    // Position underneath categories (approximately 20px down if categories exist, otherwise 5px)
                    $top_position = !empty($category_debug) ? '25px' : '5px';
                    $tag_debug = '<div style="font-family:Arial, sans-serif;position:absolute;top:'.$top_position.';left:5px;color:#000;background-color:rgba(255,255,255,0.8);padding:2px 5px;font-size:10px;z-index:9999;line-height:1.2;">' . esc_html(implode(', ', $tag_names)) . '</div>';
                }
            }
        }
        
        $markup .= $category_debug;
        $markup .= $tag_debug;
        if ( $use_split_link_structure ) {
            $markup .= '</a>'; // close thumb-rel link
        } else {
            $markup .= '</div>'; //close thumb rel
        }
        if( $belowImageTextExists ) {
            $markup .=
            '<div class="lay-textformat-parent below-image">';
            if( ($pd_position == "below-image" || $this->title_only) && $showDescr && $descrAboveTitle ){
                $markup .= '<span class="descr">'.$descr.'</span>';
            }
            if( $pt_position == "below-image" || $this->title_only ) {
                if(LayFrontend_Options::$ptags_position == "above-title"){
                    if ( $use_split_link_structure ) {
                        $markup .= $tagMarkup.'<a class="lay-ignore-linktext-style" '.$linkHrefAttrs.' '.$dataAttrs.'><span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span></a>';
                    } else {
                        $markup .= $tagMarkup.'<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>';
                    }
                } else {
                    if ( $use_split_link_structure ) {
                        $markup .= '<a class="lay-ignore-linktext-style" '.$linkHrefAttrs.' '.$dataAttrs.'><span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span></a>'.$tagMarkup;
                    } else {
                        $markup .= '<span class="title '.LayFrontend_Options::$pt_textformat.'">'.$title.'</span>'.$tagMarkup;
                    }
                }
            }
            if( ($pd_position == "below-image" || $this->title_only) && $showDescr && !$descrAboveTitle ){
                $markup .= '<span class="descr">'.$descr.'</span>';
            }
            $markup .= '</div>';
        }
        $markup .= '</'.$anchortag.'>';
        $display_style = array_key_exists('display', $this->el) && $this->el['display'] == false ? 'style="display:none;"' : '';
        $filter_id_attr = array_key_exists('filter_id', $this->el) ? 'data-filterid="'.$this->el['filter_id'].'"' : '';

        $tagArray_ids = array();
        if ($tags) {
            usort($tags, function($a, $b) {
                return $a->term_order - $b->term_order;
            });
            foreach($tags as $tag) {
                $tagArray_ids []= $tag->term_id;
            }
        }

        $classes = array_key_exists('classes', $this->el) ? $this->el['classes'] : '';

        // When the parent thumbnailgrid passed a `scrollAnimationGridEl`
        // option (set by `thumbnailgrid.php` when its scrollAnimation has
        // `animateIndividualElements` on), wrap our INNER markup with the
        // scroll-anim wrapper using the GRID's animation config. We wrap
        // INSIDE `.thumbnail-wrap` (not around it) because masonry positions
        // `.thumbnail-wrap` items absolutely and our scroll-anim transform
        // needs to live on a separate element so it doesn't fight the
        // masonry-applied positioning.
        if ( is_array( $options ) && ! empty( $options['scrollAnimationGridEl'] ) ) {
            $markup = LayElFunctions::wrapMarkupWithScrollAnimationFromEl( $markup, $options['scrollAnimationGridEl'] );
        }
        return '<div class="thumbnail-wrap '.$classes.'" data-tags="['.implode(',',$tagArray_ids).']" '.$display_style.' '.$filter_id_attr.'>'.$markup.'</div>';
    }

    public function getSidebarCSS(){
        $sidebar_css_for_ph_container = '';

        if(array_key_exists('borderRadius', $this->el)) {
            $br = $this->el['borderRadius'];
            $br_css = '';
            $br_css .= 'border-radius: '.$br['topLeft'].$br['topLeftMu'].' '.$br['topRight'].$br['topRightMu'].' '.$br['bottomRight'].$br['bottomRightMu'].' '.$br['bottomLeft'].$br['bottomLeftMu'].';';
            if (array_key_exists('cornerShape', $br) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round') {
                $br_css .= 'corner-shape: '.$br['cornerShape'].';';
            }
            $br_css .= 'overflow: hidden;';
            $sidebar_css_for_ph_container .= $br_css;
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
            $sidebar_css_for_ph_container .= $border_css;
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
            
            $sidebar_css_for_ph_container .= $shadow_css;
        }

        return $sidebar_css_for_ph_container;
    }

    private function getThumbRelCSS(){
        if(!array_key_exists('borderRadius', $this->el)) {
            return '';
        }

        $br = $this->el['borderRadius'];
        $radius_keys = array('topLeft', 'topRight', 'bottomRight', 'bottomLeft');
        $has_radius = false;
        foreach($radius_keys as $key) {
            if(array_key_exists($key, $br) && floatval($br[$key]) > 0) {
                $has_radius = true;
                break;
            }
        }
        if(!$has_radius) {
            return '';
        }

        $br_css = '';
        $br_css .= 'border-radius: '.$br['topLeft'].$br['topLeftMu'].' '.$br['topRight'].$br['topRightMu'].' '.$br['bottomRight'].$br['bottomRightMu'].' '.$br['bottomLeft'].$br['bottomLeftMu'].';';
        if (array_key_exists('cornerShape', $br) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round') {
            $br_css .= 'corner-shape: '.$br['cornerShape'].';';
        }
        $br_css .= 'overflow: hidden;';

        return $br_css;
    }

    private static function apply_qtranslate_to_markup($markup){
        if( !is_string($markup) || $markup === '' ){
            return $markup;
        }
        if( class_exists('Lay_Layout') && Lay_Layout::is_building_raw_multilingual_layout_cache() ){
            return $markup;
        }
        if( class_exists('Setup') ){
            return Setup::apply_qtranslate_with_fallback($markup);
        }
        return $markup;
    }
}
