<?php
$layElementTypes = ["project","thumbnail","text","embed","html","shortcode","hr","vl","thumbnailgrid","elementgrid","carousel","img","html5video","video","stack","productsgrid","addtocartbutton","textfooter","projectindex","productindex","socialmediaicons",'news','marquee','table','accordion'];

class LayCol{

    public $col_classes = 'col';
    public $col_id = '';
    public $type = '';
    public $inline_css = '';
    public $yvel = '';
    public $offset_data_attrs = '';
    public $data_attrs = '';
    public $phone_space_style = '';

    public function __construct($col, $inside100vhRow = false, $options = array()){
        // TYPE
        $this->type = $col['type'];
        // padding-left/right is controlled via the element "type" (see $col['type']).
        // Historically we only supported this for `text`, but the same sidebar controls
        // should work for `stack` and `img` too.
        $supports_padding_left_right = in_array($this->type, ['text', 'stack', 'img'], true);
        
        // CSS
        $hasInlineCSS = false;
        $css = '';
        $spaceabove = array_key_exists( 'spaceabove', $col ) ? $col['spaceabove'] : 0;
        $spacebelow = array_key_exists( 'spacebelow', $col ) ? $col['spacebelow'] : 0;
        // VL anchors its top/bottom to the row inner-space boundary via stickTo flags
        // (see vl element). Suppress spaceabove/spacebelow padding for vl.
        if ($this->type === 'vl') {
            $spaceabove = 0;
            $spacebelow = 0;
            $stickTop = !empty($col['stickToRowInnerSpaceTop']);
            $stickBottom = !empty($col['stickToRowInnerSpaceBottom']);
            if ($stickTop || $stickBottom) {
                // Compute top + height with the row's padding values (preserving its
                // original unit; '%' becomes 'vw' since row padding-top:50% on the
                // frontend resolves to 50vw — see padding-top logic further down).
                $top_expr = '0px';
                $bot_expr = '0px';
                if ($stickTop) {
                    $v = isset($col['_lay_row_padding_top']) ? (float) $col['_lay_row_padding_top'] : 0;
                    $u = isset($col['_lay_row_padding_top_mu']) ? $col['_lay_row_padding_top_mu'] : 'px';
                    if ($u === '%') { $u = 'vw'; }
                    $top_expr = $v . $u;
                }
                if ($stickBottom) {
                    $v = isset($col['_lay_row_padding_bottom']) ? (float) $col['_lay_row_padding_bottom'] : 0;
                    $u = isset($col['_lay_row_padding_bottom_mu']) ? $col['_lay_row_padding_bottom_mu'] : 'px';
                    if ($u === '%') { $u = 'vw'; }
                    $bot_expr = $v . $u;
                }
                // top + height (override CSS bottom:0). Using calc lets us keep the
                // original unit per side, since absolute-positioned inline-block
                // elements don't auto-stretch from top+bottom alone.
                $css .= 'top:' . $top_expr . ';bottom:auto;height:calc(100% - ' . $top_expr . ' - ' . $bot_expr . ');';
                $hasInlineCSS = true;
            }
        }
        $offsetx = array_key_exists('offsetx', $col) ? $col['offsetx'] : 0;
        $offsety = array_key_exists('offsety', $col) ? $col['offsety'] : 0;
        $offset_enabled = !( array_key_exists( 'enableOffset', $col ) && $col['enableOffset'] === false );
        if( !$offset_enabled ) {
            $offsetx = 0;
            $offsety = 0;
        }
        $absolute_position = array_key_exists('absolute_position', $col) ? $col['absolute_position'] : false;
        $stack_has_border_or_shadow_or_rounded_corners_css = false;

        $absolute_position_class_exists = false;
        if( array_key_exists('classes', $col) && strpos($col['classes'], 'absolute-position') !== false ) {
            $absolute_position_class_exists = true;
        }

        $place_at_end_of_col = array_key_exists('placeAtEndOfCol', $col) ? $col['placeAtEndOfCol'] : false;

        $spaceaboveMu = array_key_exists('spaceaboveMu', $col ) ? $col['spaceaboveMu'] : LayFrontend_Options::$spaces_mu;
        $spacebelowMu = array_key_exists('spacebelowMu', $col ) ? $col['spacebelowMu'] : LayFrontend_Options::$spaces_mu;
        $offsetxMu = array_key_exists('offsetxMu', $col ) ? $col['offsetxMu'] : LayFrontend_Options::$offsets_mu;
        if( $offsetxMu == '%' ) {
            $offsetxMu = 'vw';
        }
        $offsetyMu = array_key_exists('offsetyMu', $col ) ? $col['offsetyMu'] : LayFrontend_Options::$offsets_mu;

        if( $offsetx == 0 && $offsety == 0 ){
            $this->col_classes .= ' no-offset';
        }else{
            $this->col_classes .= ' has-offset';
        }

        // Mark cols with manually set outer-space margin-bottom so Magnetic Slides won't override it
        $has_custom_margin_bottom = false;
        if ( array_key_exists( 'margin', $col ) && isset( $col['margin']['bottom'] ) && (float) $col['margin']['bottom'] != 0 ) {
            $has_custom_margin_bottom = true;
        }
        if ( !$has_custom_margin_bottom && array_key_exists( 'marginPhone', $col ) && !empty( $col['marginPhone']['enableMarginPhone'] ) && isset( $col['marginPhone']['bottom'] ) && (float) $col['marginPhone']['bottom'] != 0 ) {
            $has_custom_margin_bottom = true;
        }
        if ( $has_custom_margin_bottom ) {
            $this->col_classes .= ' has-custom-margin-bottom';
        }

        if(array_key_exists('opacity', $col)){
            $opacity = $col['opacity'];
            $css .= 'opacity:'.Lay_LayoutFunctions::replaceCommaWithDot($opacity).';';
            $hasInlineCSS = true;
        }

        // Handle spaceleft and spaceright (needed for media-query block too)
        $spaceleft = array_key_exists('spaceleft', $col) ? $col['spaceleft'] : 0;
        $spaceright = array_key_exists('spaceright', $col) ? $col['spaceright'] : 0;
        $spaceleftMu = array_key_exists('spaceleftMu', $col) ? $col['spaceleftMu'] : LayFrontend_Options::$spaces_mu;
        $spacerightMu = array_key_exists('spacerightMu', $col) ? $col['spacerightMu'] : LayFrontend_Options::$spaces_mu;

        $use_phone_space_media_queries = !empty($options['no_custom_phone_layout'])
            && !empty($options['grid_container_selector'])
            && (
                (array_key_exists('paddingPhone', $col) && !empty($col['paddingPhone']['enablePaddingPhone']))
                || (array_key_exists('marginPhone', $col) && !empty($col['marginPhone']['enableMarginPhone']))
            );

        // When there is a custom phone layout (no_custom_phone_layout = false) or no phone overrides,
        // margin and padding are always output inline from $col. Phone media queries are only used
        // when there is no custom phone layout and the element has paddingPhone or marginPhone enabled.
        if (!$use_phone_space_media_queries) {
            if( $spaceabove != 0 ) {
                if($spaceaboveMu == '%') {
                    $spaceaboveMu = 'vw';
                }
                $css .= 'padding-top: '.$spaceabove.$spaceaboveMu.';';
                $hasInlineCSS = true;
            }
            if( $spacebelow != 0 ) {
                if($spacebelowMu == '%') {
                    $spacebelowMu = 'vw';
                }
                $css .= 'padding-bottom: '.$spacebelow.$spacebelowMu.';';
                $hasInlineCSS = true;
            }
            if( $supports_padding_left_right && $spaceleft != 0 ) {
                if($spaceleftMu == '%') {
                    $spaceleftMu = 'vw';
                }
                $css .= 'padding-left: '.$spaceleft.$spaceleftMu.';';
                $hasInlineCSS = true;
            }
            if( $supports_padding_left_right && $spaceright != 0 ) {
                if($spacerightMu == '%') {
                    $spacerightMu = 'vw';
                }
                $css .= 'padding-right: '.$spaceright.$spacerightMu.';';
                $hasInlineCSS = true;
            }
            if( $supports_padding_left_right && ($spaceleft != 0 || $spaceright != 0) ) {
                $this->col_classes .= ' use-border-box';
            }
            if(array_key_exists('margin', $col)) {
                $margin = $col['margin'];
                $topVal = (float) ( isset( $margin['top'] ) ? $margin['top'] : 0 );
                $bottomVal = (float) ( isset( $margin['bottom'] ) ? $margin['bottom'] : 0 );
                if ( $topVal != 0 || $bottomVal != 0 ) {
                    $topMu = isset( $margin['topMu'] ) && $margin['topMu'] == '%' ? 'vw' : ( isset( $margin['topMu'] ) ? $margin['topMu'] : 'px' );
                    $bottomMu = isset( $margin['bottomMu'] ) && $margin['bottomMu'] == '%' ? 'vw' : ( isset( $margin['bottomMu'] ) ? $margin['bottomMu'] : 'px' );
                    $css .= 'margin-top: '.$margin['top'].$topMu.';';
                    $css .= 'margin-bottom: '.$margin['bottom'].$bottomMu.';';
                    $hasInlineCSS = true;
                }
            }
        } else {
            $breakpoint = isset(LayFrontend_Options::$breakpoint) ? (int) LayFrontend_Options::$breakpoint : (int) get_option('lay_breakpoint', 600);
            $selector = Lay_LayoutFunctions::grid_container_scoped_selectors( $options['grid_container_selector'], ' .col.id-' . $col['relid'] );
            $desktop_css = '';
            if ($spaceabove != 0) {
                $mu = $spaceaboveMu == '%' ? 'vw' : $spaceaboveMu;
                $desktop_css .= 'padding-top:'.$spaceabove.$mu.';';
            }
            if ($spacebelow != 0) {
                $mu = $spacebelowMu == '%' ? 'vw' : $spacebelowMu;
                $desktop_css .= 'padding-bottom:'.$spacebelow.$mu.';';
            }
            if( $supports_padding_left_right && $spaceleft != 0 ) {
                $mu = $spaceleftMu == '%' ? 'vw' : $spaceleftMu;
                $desktop_css .= 'padding-left:'.$spaceleft.$mu.';';
            }
            if( $supports_padding_left_right && $spaceright != 0 ) {
                $mu = $spacerightMu == '%' ? 'vw' : $spacerightMu;
                $desktop_css .= 'padding-right:'.$spaceright.$mu.';';
            }
            if (array_key_exists('margin', $col)) {
                $margin = $col['margin'];
                $topVal = (float) ( isset( $margin['top'] ) ? $margin['top'] : 0 );
                $bottomVal = (float) ( isset( $margin['bottom'] ) ? $margin['bottom'] : 0 );
                if ( $topVal != 0 || $bottomVal != 0 ) {
                    $topMu = isset( $margin['topMu'] ) && $margin['topMu'] == '%' ? 'vw' : ( isset( $margin['topMu'] ) ? $margin['topMu'] : 'px' );
                    $bottomMu = isset( $margin['bottomMu'] ) && $margin['bottomMu'] == '%' ? 'vw' : ( isset( $margin['bottomMu'] ) ? $margin['bottomMu'] : 'px' );
                    $desktop_css .= 'margin-top:'.$margin['top'].$topMu.';margin-bottom:'.$margin['bottom'].$bottomMu.';';
                }
            }
            $phone_css = '';
            $use_padding_phone = array_key_exists('paddingPhone', $col) && !empty($col['paddingPhone']['enablePaddingPhone']);
            $use_margin_phone = array_key_exists('marginPhone', $col) && !empty($col['marginPhone']['enableMarginPhone']);
            if ($use_padding_phone) {
                $ph = $col['paddingPhone'];
                $t = isset($ph['top']) ? $ph['top'] : 0;
                $b = isset($ph['bottom']) ? $ph['bottom'] : 0;
                $l = isset($ph['left']) ? $ph['left'] : 0;
                $r = isset($ph['right']) ? $ph['right'] : 0;
                $tMu = isset($ph['topMu']) && $ph['topMu'] == '%' ? 'vw' : (isset($ph['topMu']) ? $ph['topMu'] : LayFrontend_Options::$spaces_mu);
                $bMu = isset($ph['bottomMu']) && $ph['bottomMu'] == '%' ? 'vw' : (isset($ph['bottomMu']) ? $ph['bottomMu'] : LayFrontend_Options::$spaces_mu);
                $lMu = isset($ph['leftMu']) && $ph['leftMu'] == '%' ? 'vw' : (isset($ph['leftMu']) ? $ph['leftMu'] : LayFrontend_Options::$spaces_mu);
                $rMu = isset($ph['rightMu']) && $ph['rightMu'] == '%' ? 'vw' : (isset($ph['rightMu']) ? $ph['rightMu'] : LayFrontend_Options::$spaces_mu);
                if ($t != 0) $phone_css .= 'padding-top:'.$t.$tMu.';';
                if ($b != 0) $phone_css .= 'padding-bottom:'.$b.$bMu.';';
                if( $supports_padding_left_right && $l != 0 ) $phone_css .= 'padding-left:'.$l.$lMu.';';
                if( $supports_padding_left_right && $r != 0 ) $phone_css .= 'padding-right:'.$r.$rMu.';';
                if( $supports_padding_left_right && ($l != 0 || $r != 0) ) $this->col_classes .= ' use-border-box';
            } else {
                if ($spaceabove != 0) { $mu = $spaceaboveMu == '%' ? 'vw' : $spaceaboveMu; $phone_css .= 'padding-top:'.$spaceabove.$mu.';'; }
                if ($spacebelow != 0) { $mu = $spacebelowMu == '%' ? 'vw' : $spacebelowMu; $phone_css .= 'padding-bottom:'.$spacebelow.$mu.';'; }
                if( $supports_padding_left_right && $spaceleft != 0 ) { $mu = $spaceleftMu == '%' ? 'vw' : $spaceleftMu; $phone_css .= 'padding-left:'.$spaceleft.$mu.';'; }
                if( $supports_padding_left_right && $spaceright != 0 ) { $mu = $spacerightMu == '%' ? 'vw' : $spacerightMu; $phone_css .= 'padding-right:'.$spaceright.$mu.';'; }
                if( $supports_padding_left_right && ($spaceleft != 0 || $spaceright != 0) ) $this->col_classes .= ' use-border-box';
            }
            if ($use_margin_phone) {
                $mh = $col['marginPhone'];
                $t = isset($mh['top']) ? $mh['top'] : 0;
                $b = isset($mh['bottom']) ? $mh['bottom'] : 0;
                $tMu = isset($mh['topMu']) && $mh['topMu'] == '%' ? 'vw' : (isset($mh['topMu']) ? $mh['topMu'] : LayFrontend_Options::$spaces_mu);
                $bMu = isset($mh['bottomMu']) && $mh['bottomMu'] == '%' ? 'vw' : (isset($mh['bottomMu']) ? $mh['bottomMu'] : LayFrontend_Options::$spaces_mu);
                $phone_css .= 'margin-top:'.$t.$tMu.';';
                $phone_css .= 'margin-bottom:'.$b.$bMu.';';
            } else {
                if (array_key_exists('margin', $col)) {
                    $margin = $col['margin'];
                    $topVal = (float) ( isset( $margin['top'] ) ? $margin['top'] : 0 );
                    $bottomVal = (float) ( isset( $margin['bottom'] ) ? $margin['bottom'] : 0 );
                    if ( $topVal != 0 || $bottomVal != 0 ) {
                        $topMu = isset( $margin['topMu'] ) && $margin['topMu'] == '%' ? 'vw' : ( isset( $margin['topMu'] ) ? $margin['topMu'] : 'px' );
                        $bottomMu = isset( $margin['bottomMu'] ) && $margin['bottomMu'] == '%' ? 'vw' : ( isset( $margin['bottomMu'] ) ? $margin['bottomMu'] : 'px' );
                        $phone_css .= 'margin-top:'.$margin['top'].$topMu.';margin-bottom:'.$margin['bottom'].$bottomMu.';';
                    }
                }
            }
            if ( $desktop_css !== '' || $phone_css !== '' ) {
                $this->phone_space_style = '<style>';
                $this->phone_space_style .= '@media (min-width: '.($breakpoint + 1).'px){ '.$selector.' { '.$desktop_css.' } }';
                $this->phone_space_style .= '@media (max-width: '.$breakpoint.'px){ '.$selector.' { '.$phone_css.' } }';
                $this->phone_space_style .= '</style>';
            }
        }

        // Handle border radius
        if(array_key_exists('borderRadius', $col)) {
            $br = $col['borderRadius'];
            $br_css = '';
            $br_css .= 'border-radius: '.$br['topLeft'].$br['topLeftMu'].' '.$br['topRight'].$br['topRightMu'].' '.$br['bottomRight'].$br['bottomRightMu'].' '.$br['bottomLeft'].$br['bottomLeftMu'].';';
            if (array_key_exists('cornerShape', $br) && $br['cornerShape'] !== '' && $br['cornerShape'] !== 'round') {
                $br_css .= 'corner-shape: '.$br['cornerShape'].';';
            }
            $br_css .= 'overflow: hidden;';
            // For img/project/postThumbnail, border/shadow is handled by the element itself (getSidebarCSS method)
            if($this->type != 'img' && $this->type != 'project' && $this->type != 'postThumbnail'){
                $css .= $br_css;
                $hasInlineCSS = true;
            }

            if($this->type == 'stack'){
                $stack_has_border_or_shadow_or_rounded_corners_css = true;
            }
        }

        // Handle border
        if(array_key_exists('border', $col) && $col['border']['enableBorder']) {
            $border = $col['border'];
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
            // For img/project/postThumbnail, border/shadow is handled by the element itself (getSidebarCSS method)
            if($this->type != 'img' && $this->type != 'project' && $this->type != 'postThumbnail'){
                $css .= $border_css;
                $hasInlineCSS = true;
            }

            if($this->type == 'stack'){
                $stack_has_border_or_shadow_or_rounded_corners_css = true;
            }
        }

        // Handle background color
        if(array_key_exists('bgColor', $col) && $col['bgColor']['enableBgColor']) {
            $bgColor = $col['bgColor'];
            $bgColorHex = $bgColor['bgColor'];
            $bgOpacity = $bgColor['bgOpacity'] / 100;
            
            // Convert hex to rgba for opacity
            $r = hexdec(substr($bgColorHex, 1, 2));
            $g = hexdec(substr($bgColorHex, 3, 2));
            $b = hexdec(substr($bgColorHex, 5, 2));
            
            $css .= 'background-color: rgba('.$r.','.$g.','.$b.','.$bgOpacity.');';
            $hasInlineCSS = true;
        }

        // Handle shadow
        if(array_key_exists('shadow', $col) && $col['shadow']['enableShadow']) {
            $shadow = $col['shadow'];
            $shadowColor = $shadow['color'];
            $shadowOpacity = $shadow['opacity'] / 100;
            
            // Convert hex to rgba for opacity
            $r = hexdec(substr($shadowColor, 1, 2));
            $g = hexdec(substr($shadowColor, 3, 2));
            $b = hexdec(substr($shadowColor, 5, 2));
            
            $inset = $shadow['inset'] ? 'inset ' : '';
            $shadow_css = '';
            $shadow_css .= 'box-shadow: '.$inset.$shadow['offsetX'].'px '.$shadow['offsetY'].'px '.$shadow['blur'].'px '.$shadow['spread'].'px rgba('.$r.','.$g.','.$b.','.$shadowOpacity.');';

            // For img/project/postThumbnail, border/shadow is handled by the element itself (getSidebarCSS method)
            if($this->type != 'img' && $this->type != 'project' && $this->type != 'postThumbnail'){
                $css .= $shadow_css;
                $hasInlineCSS = true;
            }

            if($this->type == 'stack'){
                $stack_has_border_or_shadow_or_rounded_corners_css = true;
            }
        }

        if( $offsetx != 0 || $offsety != 0 ) {
            $this->offset_data_attrs .= ' data-offsetx="'.$offsetx.'" data-offsetxmu='.$offsetxMu.' data-offsety="'.$offsety.'" data-offsetymu='.$offsetyMu.'';
            $y_offset_markup = $offsety.$offsetyMu;

            // need to use translate3d so on safari when fs slider is active, z-index will work properly
            $css .= 'transform:translate3d('.$offsetx.$offsetxMu.', '.$y_offset_markup.', 0);';
            $hasInlineCSS = true;
        }

        if( !empty($col['_lay_same_height_image_participant']) && $this->type == 'img' ) {
            $same_height_image_ratio = 0;
            if( array_key_exists('customAspectRatio', $col) && is_array($col['customAspectRatio']) && !empty($col['customAspectRatio']['enableCustomAspectRatio']) ) {
                $car_width = isset($col['customAspectRatio']['width']) ? (float) $col['customAspectRatio']['width'] : 0;
                $car_height = isset($col['customAspectRatio']['height']) ? (float) $col['customAspectRatio']['height'] : 0;
                if( $car_width > 0 && $car_height > 0 ) {
                    $same_height_image_ratio = $car_height / $car_width;
                }
            } elseif( isset($col['ar']) && (float) $col['ar'] > 0 ) {
                $same_height_image_ratio = (float) $col['ar'];
            } elseif( isset($col['w']) && (float) $col['w'] > 0 && isset($col['h']) && (float) $col['h'] > 0 ) {
                $same_height_image_ratio = (float) $col['h'] / (float) $col['w'];
            }
            if( $same_height_image_ratio > 0 ) {
                $css .= 'aspect-ratio:1/'.Lay_LayoutFunctions::replaceCommaWithDot($same_height_image_ratio).';';
                $hasInlineCSS = true;
            }
        }


        // for backwards compatibility
        $isProjectLink = array_key_exists( 'postid', $col ) ? true : false;
        if(($this->type == 'img' && $isProjectLink) || $this->type == 'postThumbnail'){
            $this->type = 'project';
        }
        
        //  ID
        $this->col_id = array_key_exists('html_id', $col) ? 'id="'.$col['html_id'].'"' : '';

        // instead of only in placefree mode, we now use a zIndex always, for when horizontalGrid is active or normal mode 
        if( array_key_exists('zIndex', $col) ) {
            $hasInlineCSS = true;
            $css .= 'z-index:'.((int)$col['zIndex']+2).';';
        }

        // CLASSES
        if( array_key_exists('placeFreely', $col) ) {
            $hasInlineCSS = true;
            // `no-row-hoverimage` here so the imagehover JS's link selector
            // (`.no-row-hoverimage a[data-hoverimageid]…`) matches links inside
            // place-freely cols — they don't sit in a row, which is what the
            // class normally lives on.
            $this->col_classes .= ' place-freely no-row-hoverimage';
            $css .= 'position:absolute;left:'.$col['left'].'%;top:calc((100vw - var(--scrollbarWidth)) * '.($col['top'] / 100).');width:'.$col['width'].'%;';
        } else {
            $this->col_classes .= ' place-normal';
            if( $absolute_position_class_exists ) {
                // doing ' push-'.$col['push'] should work too, but
                // pushes are calculated wrong in older lay theme versions for absolute position elements
                // because these elements were not absolute position before
                // so we just do this for backwards compatibility

                // dont remove this we need it for position absolute!
                $this->col_classes .= ' push-'.$col['col'];
            } else {
                $this->col_classes .= ' push-'.$col['push'];
            }
            $this->col_classes .= ' span-'.$col['colspan'];

            $this->col_classes .= ' colstart-'.$col['col'];
            $this->col_classes .= ' colend-'.($col['col'] + $col['colspan']);
            $this->col_classes .= $place_at_end_of_col ? ' place-at-end-of-col' : '';
    
            if( array_key_exists('frameOverflow', $col) ) {
                switch( $col['frameOverflow'] ){
                    case 'left':
                        $this->col_classes .= ' frame-overflow-left';
                        break;
                    case 'both':
                        $this->col_classes .= ' frame-overflow-both';
                        break;
                    case 'right':
                        $this->col_classes .= ' frame-overflow-right';
                        break;
                    case '':
                        $this->col_classes .= ' no-frame-overflow';
                    break;
                    default:
                        $this->col_classes .= ' no-frame-overflow';
                    break;
                }
            } else {
                $this->col_classes .= ' no-frame-overflow';
            }

            if( array_key_exists('horizontalIndex', $col) && array_key_exists('horizontalGrid', $col) ) {
                $this->col_classes .= ' uses-horizontal-grid';
                if( array_key_exists('stickytop', $col) ) {
                    // horizontal grid items still need align top when using stickytop so sticky works
					if( array_key_exists('align', $col) ) {
                        $this->col_classes .= ' align-'.$col['align'];
                    }
                }
            } else {
				if( array_key_exists('align', $col) ) {
                    $this->col_classes .= ' align-'.$col['align'];
                }
            }
        }

        $this->col_classes .= array_key_exists('classes', $col) ? ' '.$col['classes'] : '';


        if( array_key_exists('yvel', $col) && $col['yvel'] != 1 && LayFrontend_Options::$simple_parallax ){
            $this->col_classes .= ' has-parallax';
        }else{
            $this->col_classes .= ' no-parallax';
        }

        $this->col_classes .= ' type-'.$this->type;
        $this->col_classes .= ' id-'.$col['relid'];

        if( !empty($col['aplOrderEnable']) ) {
            $order = isset($col['aplOrder']) ? max(0, min(50, (int)$col['aplOrder'])) : 0;
            $this->col_classes .= ' apl-order-'.$order;
        } else {
            $this->col_classes .= ' apl-order-default';
        }

        $lightboxoff = array_key_exists('lightboxoff', $col) && $col['lightboxoff'] == true && $this->type == 'img' ? 'lightboxoff' : '';

        $this->col_classes .= ' '.$lightboxoff;

        if( $this->type == 'carousel' ) {
            if( array_key_exists('carousel_options', $col) ){
                $copts = $col['carousel_options'];
                if( array_key_exists('fixedHeight', $copts) && trim($copts['fixedHeight']) === '100vh' ){
                    $use_phone = array_key_exists('useCustomFixedHeightPhone', $copts) && $copts['useCustomFixedHeightPhone']
                        && array_key_exists('fixedHeightPhone', $copts) && trim($copts['fixedHeightPhone']) !== '';
                    if ( !$use_phone ) {
                        $this->col_classes .= ' contains_100vh-carousel';
                    } else {
                        $this->col_classes .= ' no_100vh-carousel';
                    }
                }else{
                    $this->col_classes .= ' no_100vh-carousel';
                }
            }else{
                $this->col_classes .= ' no_100vh-carousel';
            }
        }


        // YVEL
        if( array_key_exists( 'yvel', $col ) && $col['yvel'] != 0 ){
            $this->yvel = 'data-yvel="'.$col['yvel'].'"';
        }

        $stickytop = array_key_exists( 'stickytop', $col ) ? $col['stickytop'] : false;
        // $this->data_attrs = $stickytop;
        if( $stickytop !== false && $stickytop !== null && $stickytop !== 'null' && $stickytop !== '' ){
            $this->col_classes .= ' lay-sticky';

            $hasInlineCSS = true;
            $css .= 'position:sticky;top:'.$stickytop.'px;';

            // $this->data_attrs .= ' data-sticky-top="'.$stickytop.'"';
            // $this->data_attrs .= ' data-sticky-for="'.( array_key_exists( 'stickyfor', $col ) ? $col['stickyfor'] : 1 ).'"';
        } else {
            $this->col_classes .= ' no-sticky';
        }

        // Per-element Scroll Animation (sidebar): when enabled, opt the col
        // out of the legacy .below/.in-view scroll-into-view system so the
        // two transform pipelines don't fight. The new feature drives the
        // transform live in lay/.../scroll_animations.js.
        if ( array_key_exists( 'scrollAnimation', $col )
            && is_array( $col['scrollAnimation'] )
            && !empty( $col['scrollAnimation']['enableScrollAnimation'] ) ) {
            $this->col_classes .= ' no-animate-in-on-scroll';
        }

        if( array_key_exists( 'fs_maxdim', $col ) ) {
            $this->data_attrs .= ' data-fullscreenslider-maxdim="'.$col['fs_maxdim'].'"';
        }
        
        if ( class_exists( 'LayElFunctions' ) && LayElFunctions::shouldApplyScrollAnimationToColumn( $col ) ) {
            $sa = LayElFunctions::getScrollAnimationDataAttrs( $col );
            if ( is_array( $sa ) && $sa['attrs'] !== '' ) {
                $base_transform = '';
                $base_opacity = '';
                if ( preg_match( '/transform\s*:\s*([^;]+);/i', $css, $matches ) ) {
                    $base_transform = trim( $matches[1] );
                    $css = preg_replace( '/transform\s*:\s*[^;]+;/i', '', $css, 1 );
                }
                if ( preg_match( '/opacity\s*:\s*([^;]+);/i', $css, $matches ) ) {
                    $base_opacity = trim( $matches[1] );
                    $css = preg_replace( '/opacity\s*:\s*[^;]+;/i', '', $css, 1 );
                }
                $scroll_animation_style_css = LayElFunctions::getScrollAnimationInitialStyleCss( $sa, $base_transform, $base_opacity );
                if ( $scroll_animation_style_css !== '' ) {
                    $css .= $scroll_animation_style_css;
                    $hasInlineCSS = true;
                }
                $this->data_attrs .= ' ' . $sa['attrs'];
                if ( $base_transform !== '' ) {
                    $this->data_attrs .= ' data-lay-scroll-anim-base-transform="' . esc_attr( $base_transform ) . '"';
                }
                if ( $base_opacity !== '' ) {
                    $this->data_attrs .= ' data-lay-scroll-anim-base-opacity="' . esc_attr( $base_opacity ) . '"';
                }
                $this->col_classes .= ' lay-scroll-anim-col';
            }
        }

        if( $hasInlineCSS ) {
            $trimmed_css = trim($css);
            if($trimmed_css != '') {
                $this->inline_css = 'style="'.$trimmed_css.'"';
            }
        }
        if( $this->type == 'thumbnailgrid' || $this->type == 'elementgrid' || $this->type == 'productsgrid' ) {
            if( array_key_exists('config', $col) ) {
                if( $col['config']['layoutType'] == 'masonry' ) {
                    $this->col_classes .= ' contains-masonry-layout';
                }
            }
        }

        $text_animation_enabled = $this->type == 'text'
            && array_key_exists( 'textAnimation', $col )
            && is_array( $col['textAnimation'] )
            && isset( $col['textAnimation']['enableTextAnimation'] )
            && ( $col['textAnimation']['enableTextAnimation'] === true || $col['textAnimation']['enableTextAnimation'] === 'true' || $col['textAnimation']['enableTextAnimation'] === 1 || $col['textAnimation']['enableTextAnimation'] === '1' );
        if( $text_animation_enabled ) {
            // Turn off the legacy scroll-in animation so text animations own the
            // text reveal. If the text column itself has a visible box style,
            // keep it eligible for navigation-in animations so background,
            // border and shadow don't appear before the transition.
            if ( $this->textAnimatedColumnHasVisibleBox( $col ) ) {
                $this->col_classes .= ' no-animate-in-on-scroll';
            } else {
                $this->col_classes .= ' only-animate-out no-animate-in-on-scroll';
            }
        }
        
        global $layElementTypes;
        if(is_array($layElementTypes)){
            if( !in_array($this->type, $layElementTypes) ) {
                $this->col_classes .= ' cmb2-element';
            }
        }
        if($stack_has_border_or_shadow_or_rounded_corners_css == true){
            $this->col_classes .= ' stack-has-border-or-shadow-or-rounded-corners';
        }

    }

    public function getOpeningMarkup(){
        return $this->phone_space_style . '<div class="'.$this->col_classes.'" '.$this->col_id.' '.$this->data_attrs.' data-type="'.$this->type.'" '.$this->inline_css.' '.$this->yvel.' '.$this->offset_data_attrs.'>';
    }

    public function getClosingMarkup(){
        return '</div>';
    }

    private function textAnimatedColumnHasVisibleBox( $col ) {
        if ( array_key_exists( 'bgColor', $col ) && is_array( $col['bgColor'] ) && !empty( $col['bgColor']['enableBgColor'] ) ) {
            $opacity = isset( $col['bgColor']['bgOpacity'] ) ? (float) $col['bgColor']['bgOpacity'] : 100;
            if ( $opacity > 0 ) {
                return true;
            }
        }

        if ( array_key_exists( 'border', $col ) && is_array( $col['border'] ) && !empty( $col['border']['enableBorder'] ) ) {
            $opacity = isset( $col['border']['borderOpacity'] ) ? (float) $col['border']['borderOpacity'] : 100;
            $border_width = isset( $col['border']['borderWidth'] ) && is_array( $col['border']['borderWidth'] ) ? $col['border']['borderWidth'] : array();
            $has_border_width = false;
            foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
                if ( isset( $border_width[$side] ) && (float) $border_width[$side] > 0 ) {
                    $has_border_width = true;
                    break;
                }
            }
            if ( $opacity > 0 && $has_border_width ) {
                return true;
            }
        }

        if ( array_key_exists( 'shadow', $col ) && is_array( $col['shadow'] ) && !empty( $col['shadow']['enableShadow'] ) ) {
            $opacity = isset( $col['shadow']['opacity'] ) ? (float) $col['shadow']['opacity'] : 100;
            if ( $opacity > 0 ) {
                return true;
            }
        }

        return false;
    }

}
