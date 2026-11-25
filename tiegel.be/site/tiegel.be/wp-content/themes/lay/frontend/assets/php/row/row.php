<?php
// todo: everywhere I use showOriginalImages, also use is no Touchdevice
// todo: test gif

class LayRow {

    public $row_classes;
    public $row_inner_classes;
    public $row;
    public $index;
    // bg image
    public $backgroundImageMarkup;
    // bg video
    public $videoMarkup;
    public $webglslideshowMarkup;
    public $webglslideshowHalfStyles;
    public $html_classes;
    public $html_id;
    public $row_css;
    public $row_inner_css;
    public $rowcustomheight_css;
    public $column_wrap_classes;
    public $linkForEmptyBackground;
    // Phone overrides for row background (object-fit / object-position)
    public $phone_bg_override_css;

    public function __construct($row, $index) {
        $this->index = $index;
        $this->row = $row;
        $this->row_classes = '';
        $this->row_inner_classes = '';
        $this->backgroundImageMarkup = '';
        $this->webglslideshowMarkup = '';
        $this->webglslideshowHalfStyles = '';
        $this->row_css = '';
        $this->rowcustomheight_css = '';
        $this->row_inner_css = '';
        $this->phone_bg_override_css = '';

        

        $relid = array_key_exists('relid', $row) ? $row['relid'] : '';

        $bgcolor = array_key_exists('bgcolor', $row) ? $row['bgcolor'] : '';
		$rowGutter = array_key_exists('rowGutter', $row) ? $row['rowGutter'] : '';
		$row100vh = array_key_exists('row100vh', $row) ? $row['row100vh'] : '';
		$rowcustomheight = array_key_exists('rowcustomheight', $row) ? $row['rowcustomheight'] : '';

		$bgimage = array_key_exists('bgimage', $row) ? $row['bgimage'] : '';
        $bgimage2 = array_key_exists('bgimage2', $row) ? $row['bgimage2'] : '';

        // left
        $bgimage3 = array_key_exists('bgimage3', $row) ? $row['bgimage3'] : '';
        $doBgImage = false;
        if ( $bgimage != '' || $bgimage2 != '' || $bgimage3 != '' ) {
            $doBgImage = true;
        }
        $background_image_left_array = false;
        $background_image_right_array = false;

        $webglslideshow = array_key_exists('webglslideshow', $row) ? $row['webglslideshow'] : '';

        $styleChoice = '';
        $bgStyle = '';
        if ( $bgimage != '' &&  $bgimage2 != '' ) {
            // normal bg image and second bg image, making this left and right so it is compatible with old version
			$styleChoice = 'legacy_left1_right2';
            $bgStyle = 'left_right';
            $background_image_left_array = $bgimage;
            $background_image_right_array = $bgimage2;
		} else if ( $bgimage != '' &&  $bgimage3 != '' ) {
            // normal bg image and right bg image
			$styleChoice = 'onlyleft3';
            $bgStyle = 'left';
            $background_image_left_array = $bgimage3;
		} else if ( $bgimage2 != '' && $bgimage3 != '' ) {
			// left(3) and right(2)
			$styleChoice = 'left3_right2';
            $bgStyle = 'left_right';
            $background_image_left_array = $bgimage3;
            $background_image_right_array = $bgimage2;
		} else if ( $bgimage2 != '' && $bgimage3 == '' ) {
			$styleChoice = 'onlyright2';
            $bgStyle = 'right';
            $background_image_right_array = $bgimage2;
		} else if ( $bgimage2 == '' && $bgimage3 != '' ) {
			// only left (3)
			$styleChoice = 'onlyleft3';
            $bgStyle = 'left';
            $background_image_left_array = $bgimage3;
		}  else if ( $bgimage != '' ) {
			// normal bg image
			$styleChoice = 'bgimage';
            $bgStyle = 'normal';
            $background_image_left_array = $bgimage;
		}

		$row_bg_fit_raw = array_key_exists( 'rowBgObjectFit', $row ) ? $row['rowBgObjectFit'] : 'cover';
		$row_bg_fit = in_array( $row_bg_fit_raw, array( 'cover', 'contain', 'fill' ), true ) ? $row_bg_fit_raw : 'cover';
		// Cover uses setsizes-objectfit-cover so lazy sizes match object-fit:cover; other fits use setsizes + inline object-fit only.
		$row_bg_setsizes_cover_class = ( $row_bg_fit === 'cover' ) ? ' setsizes-objectfit-cover' : '';
		$row_bg_pos = ( array_key_exists( 'rowBgObjectPosition', $row ) && $row['rowBgObjectPosition'] !== '' && $row['rowBgObjectPosition'] !== null ) ? $row['rowBgObjectPosition'] : 'center center';
		$row_bg_pos_left = ( array_key_exists( 'rowBgObjectPositionLeft', $row ) && $row['rowBgObjectPositionLeft'] !== '' && $row['rowBgObjectPositionLeft'] !== null ) ? $row['rowBgObjectPositionLeft'] : $row_bg_pos;
		$row_bg_pos_right = ( array_key_exists( 'rowBgObjectPositionRight', $row ) && $row['rowBgObjectPositionRight'] !== '' && $row['rowBgObjectPositionRight'] !== null ) ? $row['rowBgObjectPositionRight'] : $row_bg_pos;
		$use_split_row_bg_pos = ( $styleChoice === 'left3_right2' || $styleChoice === 'legacy_left1_right2' );

		// Optional phone override for row background object-fit / object-position.
		// We override inline styles via CSS `!important` inside a max-width media query.
		$override_on_phone = array_key_exists('rowBgOverrideOnPhone', $row) ? $row['rowBgOverrideOnPhone'] : false;
		$override_on_phone = ($override_on_phone === true || $override_on_phone === 'true' || $override_on_phone == 1);
		$row_bg_fit_phone_raw = array_key_exists('rowBgObjectFitPhone', $row) ? $row['rowBgObjectFitPhone'] : $row_bg_fit;
		$row_bg_fit_phone = in_array( $row_bg_fit_phone_raw, array( 'cover', 'contain', 'fill' ), true ) ? $row_bg_fit_phone_raw : $row_bg_fit;
		$row_bg_pos_phone = ( array_key_exists('rowBgObjectPositionPhone', $row) && $row['rowBgObjectPositionPhone'] !== '' && $row['rowBgObjectPositionPhone'] !== null ) ? $row['rowBgObjectPositionPhone'] : $row_bg_pos;

		if ( $override_on_phone && $relid != '' ) {
			$breakpoint = isset(LayFrontend_Options::$breakpoint) ? (int) LayFrontend_Options::$breakpoint : (int) get_option('lay_breakpoint', 600);
			$fit_phone = esc_attr($row_bg_fit_phone);
			$pos_phone = esc_attr($row_bg_pos_phone);
			$row_suffix_video = ' .row-id-' . $relid . ' .background-video video';
			$row_suffix_image = ' .row-id-' . $relid . ' .background-image img';

			// Use the same path-style selector pattern as element style tags (img/col):
			// body > .lay-content #grid ... (plus custom phone grid), scoped via helper.
			$grid_scope = 'body > .lay-content #grid, body > .lay-content #custom-phone-grid';
			$sel_video = '';
			$sel_image = '';
			if ( class_exists( 'Lay_LayoutFunctions' ) && method_exists( 'Lay_LayoutFunctions', 'grid_container_scoped_selectors' ) ) {
				$sel_video = Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_scope, $row_suffix_video );
				$sel_image = Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_scope, $row_suffix_image );
			}
			// Fallback in case helper/scopes are unavailable.
			if ( $sel_video === '' ) {
				$sel_video = '.row-id-' . $relid . ' .background-video video';
			}
			if ( $sel_image === '' ) {
				$sel_image = '.row-id-' . $relid . ' .background-image img';
			}
			$this->phone_bg_override_css = '<style>@media (max-width: ' . $breakpoint . 'px){'
				. $sel_video . '{object-fit:' . $fit_phone . ' !important;object-position:' . $pos_phone . ' !important;}'
				. $sel_image . '{object-fit:' . $fit_phone . ' !important;object-position:' . $pos_phone . ' !important;}'
				. '}</style>';
		}

		$bgvideo = array_key_exists('bgvideo', $row) ? $row['bgvideo'] : '';
		$link = array_key_exists('link', $row) ? $row['link'] : '';
		$this->html_classes = array_key_exists('classes', $row) ? $row['classes'] : '';
		$this->html_id = array_key_exists('html_id', $row) ? $row['html_id'] : '';

		$row_has_bg = false;
		$linkMarkup = false;

        if( count($row['columns']) == 0 ){
            $this->row_classes .= ' empty';
        }
        if( count($row['columns']) == 1 ){
            $this->row_classes .= ' one-col-row';
        }
        $this->row_classes .= ' row-col-amt-'.count($row['columns']);

        if( count($row['columns']) > 0 ){
            $no_y_offsets = true;
            $no_sticky = true;
            $only_vls = true;
            $alignmentArr = [];
            $alignsAreUnique = true;
            $no_horizontal_grid = true;
            $absolutePositionVerticallyForAPLApplicable = true;

            $bigElementsAmount = 0;

            foreach( $row['columns'] as $col ) {
                if( $col['type'] != 'text' && $col['type'] != 'html' ) {
                    $bigElementsAmount++;
                }
                if( array_key_exists('align', $col) && in_array($col['align'], $alignmentArr) === true ) {
                    $alignsAreUnique = false;
                }
				if( array_key_exists('align', $col) ) {
                    $alignmentArr []=  $col['align'];
                }
                if( array_key_exists('offsety', $col) && $col['offsety'] != 0 ) {
                    $no_y_offsets = false;
                }
                if( array_key_exists('stickytop', $col) && $col['stickytop'] != 0 ) {
                    $no_sticky = false;
                }
                if( array_key_exists('horizontalIndex', $col) && $col['horizontalIndex'] != 0 ) {
                    $no_horizontal_grid = false;
                }
            }
            $hasSameVerticalAlignment = true;
            foreach( $row['columns'] as $col ) {
                if( $col['type'] != 'vl' ) {
                    $only_vls = false;
                    break;
                }
            }

            if( $bigElementsAmount > 1 ) {
                $absolutePositionVerticallyForAPLApplicable = false;
            }

            if( $alignsAreUnique && $absolutePositionVerticallyForAPLApplicable ) {
                $this->row_classes .= ' absolute-positioning-vertically-in-apl';
            }
            if( $no_sticky ) {
                $this->row_classes .= ' no-stickies';
            }
            if( $no_y_offsets ) {
                $this->row_classes .= ' no-y-offsets';
            }
            if( $no_horizontal_grid ) {
                $this->row_classes .= ' no-horizontal-grid';
            }
            if( $only_vls ) {
                $this->row_classes .= ' only-vertical-lines';
            }
            if( $webglslideshow ) {
                $this->row_classes .= ' webglslideshow';
            } else {
                $this->row_classes .= ' no-webglslideshow';
            }
        }
        if( array_key_exists( 'row100vh', $row ) && $row['row100vh'] == true ) {
            $this->row_classes .= ' _100vh';
            $this->row_inner_classes .= ' _100vh';
            $this->column_wrap_classes .= ' _100vh';
        }
        
        if( $rowcustomheight != '' ) {
            $this->row_classes .= ' rowcustomheight';
            $this->row_inner_classes .= ' rowcustomheight';
            $this->column_wrap_classes .= ' rowcustomheight';
            // because someone put " signs into row custom height and it made the whole grid not show up
            $this->rowcustomheight_css = 'min-height:calc('.htmlspecialchars($row['rowcustomheight'], ENT_QUOTES, 'UTF-8').');';
        }
        if( array_key_exists('custom_order_in_apl', $row) && $row['custom_order_in_apl'] ) {
            $this->column_wrap_classes .= ' custom-order-in-apl';
        }
        if( array_key_exists('link', $row ) ) {
            $this->row_classes .= ' row-has-link';
            if( is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) && array_key_exists('hoverimageid', $row['link']) && $row['link']['hoverimageid'] != null ) {
                $this->row_classes .= ' row-has-hoverimage';
            } else {
                $this->row_classes .= ' no-row-hoverimage';
            }
        } else {
            $this->row_classes .= ' no-row-hoverimage';
        }
        
        if( array_key_exists('columns', $row) && is_array($row['columns']) ) {
            for( $i3=0; $i3<count($row['columns']); $i3++ ) {
                $col = $row['columns'][$i3];
                if( array_key_exists('carousel_options', $col) ){
                    $copts = $col['carousel_options'];
                    if( array_key_exists('fixedHeight', $copts) && trim($copts['fixedHeight']) === '100vh' ){
                        $use_phone = array_key_exists('useCustomFixedHeightPhone', $copts) && $copts['useCustomFixedHeightPhone']
                            && array_key_exists('fixedHeightPhone', $copts) && trim($copts['fixedHeightPhone']) !== '';
                        if ( !$use_phone ) {
                            $this->row_classes .= ' contains_100vh-carousel';
                        }
                    }
                }
                if( count($row['columns']) == 1 && array_key_exists('type', $col) && $col['type'] == 'marquee' ) {
                    $this->row_classes .= ' only-marquee-row';
                }
            }
        }

        $setBgColorToOuterWrap = true;
        $rowBgClass = '';

		// link markup
		if ( is_array($link) && array_key_exists('url', $link) ) {
			$target = '';
			if ( $link['newtab'] == true) {
				$target = ' target="_blank"';
            }
            $catidAttrs = array_key_exists('catid', $link) ? ' data-catid="'.LayElFunctions::stringifyCatIds($link['catid']).'"' : '';
			$dataAttrs = $link['type'] == '' ? '' : 'data-id="'.$link['id'].'" data-type="'.$link['type'].'" data-title="'.__($link['title']).'"'.$catidAttrs;
            // imagehover link
            if( is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) ){
                if(array_key_exists('hoverimageid', $link) && $link['hoverimageid'] != null ) {
                    $dataAttrs .= ' data-hoverimageid="'.$link['hoverimageid'].'"';
                }
                if(array_key_exists('hoverimagetype', $link) && $link['hoverimagetype'] != null ) {
                    $dataAttrs .= ' data-hoverimagetype="'.$link['hoverimagetype'].'"';
                }
                if(array_key_exists('hoverimageposition', $link) && $link['hoverimageposition'] != null ) {
                    $dataAttrs .= ' data-hoverimageposition="'.$link['hoverimageposition'].'"';
                }
                if(array_key_exists('hoverimagepositionforscroll', $link) && $link['hoverimagepositionforscroll'] != null ) {
                    $dataAttrs .= ' data-hoverimagepositionforscroll="'.$link['hoverimagepositionforscroll'].'"';
                }
                if(array_key_exists('hoverimagepreactivated', $link) && $link['hoverimagepreactivated'] != null ) {
                    $dataAttrs .= ' data-hoverimagepreactivated="'.$link['hoverimagepreactivated'].'"';
                }
                if(array_key_exists('hoverimageshowon', $link) && $link['hoverimageshowon'] != null ) {
                    $dataAttrs .= ' data-hoverimageshowon="'.$link['hoverimageshowon'].'"';
                }
            }

            $linkAttr = '';
            if( $link['url'] == 'null' || $link['url'] == '' ) {
                $linkAttr = '';
            } else {
                $linkUrl = LTUtility::getFullURL($link['url']);
                if( $link['type'] != '' && $link['id'] != 'null' && $link['id'] != '' ) {
                    // need to do this so link will be updated with qtranslate!
                    $linkUrl = get_permalink($link['id']);
                    $linkAttr = 'href="'.$linkUrl.'"';
                } else {
                    // just a link without any id or type
                    $linkAttr = 'href="'.$linkUrl.'"';
                }
            }

            $linkMarkup = '<a '.$dataAttrs.' '.$linkAttr.' ' . $target;
		}

        // bg image
        if( $doBgImage ) {

            $background_images_array = array();
            if( is_array($background_image_left_array) ) {
                $background_images_array []= $background_image_left_array;
            }
            if( is_array($background_image_right_array) ) {
                $background_images_array []= $background_image_right_array;
            }

            $this->backgroundImageMarkup = '';
			if ($linkMarkup != false) {
				$this->backgroundImageMarkup = $linkMarkup;
				$rowBgClass .= 'row-bg-link';
			} else {
				$this->backgroundImageMarkup = '<div';
			}
            $rowBgClass .= ' background-image background-image-style-'.$bgStyle.' background-image-count-'.count($background_images_array);
            if( $bgStyle == 'normal' ) {
                $setBgColorToOuterWrap = false;
            }
            $row_has_bg = true;

            $bg_brightness_style = array_key_exists('bgimageopacity', $row) && $row['bgimageopacity'] != 100 ? 'style="-webkit-filter:brightness('.$row['bgimageopacity'].'%);filter:brightness('.$row['bgimageopacity'].'%);"' : '';
            $this->backgroundImageMarkup .= ' class="'.$rowBgClass.'" '.$bg_brightness_style.'>';

            // cannot use el_functions's getLazyImg function here, but I'll try to mimick it as best a possible
            // earlier lay theme versions do not save attid of an image, so here I care about this case for backwards compatibility
            for ($i=0; $i < count($background_images_array); $i++) { 

                $bgi = $background_images_array[$i];
                $full_src = LTUtility::getFullURL($bgi['url']);
                $smallest_src = $full_src;
                if( array_key_exists('_265', $bgi['sizes']) ) {
                    if( array_key_exists('url', $bgi['sizes']['_265']) ){
                        $smallest_src = LTUtility::getFullURL( $bgi['sizes']['_265']['url'] );
                    } else {
                        // backwards compatibility for when this did not contain url and w and h but the url was saved in ['sizes']['_265'] directly
                        $smallest_src = LTUtility::getFullURL( $bgi['sizes']['_265'] );
                    }
                } 
    
                $srcset = LayRowFunctions::getBgSet($bgi);
                $ar = LayRowFunctions::getBgAr($bgi);
                $isGif = substr($full_src, -4) == '.gif' ? true : false;
                
                $alt = get_bloginfo('name');

                $_alt = LTUtility::getAltTag($bgi);
                if( $_alt != '' ) {
                    $alt = $_alt;
                }
                
                // data w and data h are not the actual heights and widths of the original image. However, i need this data to calculate the correct sizes attribute, it is about the aspect ratio, not the real width and height of the original image
                $dataHdataW = LayRowFunctions::getBgDataWDataH($bgi);

                $pos_for_this = $row_bg_pos;
                if ( $use_split_row_bg_pos && count( $background_images_array ) === 2 ) {
                    $pos_for_this = ( $i === 0 ) ? $row_bg_pos_left : $row_bg_pos_right;
                }
                $row_bg_img_extra_style = ' style="'.esc_attr( 'object-fit:'.$row_bg_fit.';object-position:'.$pos_for_this.';' ).'"';
    
                switch( LayFrontend_Options::$image_loading ) {
                    case 'instant_load':
                        if( $isGif ) {
                            $this->backgroundImageMarkup .= '<img class="lay-gif" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_bg_img_extra_style.'/>';
                        }
                        else if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                            $this->backgroundImageMarkup .= '<img class="lay-image-original" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_bg_img_extra_style.'/>';
                        }
                        else{
                            $this->backgroundImageMarkup .= '<img class="lay-image-responsive setsizes'.$row_bg_setsizes_cover_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$smallest_src.'" data-srcset="'.$srcset.'" sizes="" alt="'.$alt.'" '.$dataHdataW.$row_bg_img_extra_style.'/>';
                        }
                    break;
                    case 'lazy_load':
                        // https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/parent-fit
                        // needs data-aspectratio: <!-- Usage of the data-aspectratio attribute: Divide width by height: 400/800 = data-aspectratio="0.5" -->
                        // todo: have imageW and imageH in json instead of this, also put imageAttid into json and get the image using that attid. To be compatible with cdn plugins
                        $aspect_ratio_for_lazysizes = array_key_exists('_265', $bgi['sizes']) ? $bgi['sizes']['_265']['w'] / $bgi['sizes']['_265']['h'] : '';
                        if( $isGif ) {
                            $this->backgroundImageMarkup .= '<img class="lay-gif lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_bg_img_extra_style.'/>';
                        }
                        if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                            $this->backgroundImageMarkup .= '<img class="lay-image-original lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_bg_img_extra_style.'/>';
                        }
                        else{
                            $this->backgroundImageMarkup .= '<img class="lazyload setsizes'.$row_bg_setsizes_cover_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$smallest_src.'" data-srcset="'.$srcset.'" data-aspectratio="'.$aspect_ratio_for_lazysizes.'" '.$dataHdataW.' alt="'.$alt.'"'.$row_bg_img_extra_style.'/>';
                        }
                    break;
                }
            }

			if ($linkMarkup != false) {
				$this->backgroundImageMarkup .= '</a>';
			} else {
				$this->backgroundImageMarkup .= '</div>';
			}
        }


        if($webglslideshow) {
            if(array_key_exists('random', $webglslideshow['carousel_options']) && $webglslideshow['carousel_options']['random'] == 1) {
                shuffle($webglslideshow['carousel']);
            }

            $this->webglslideshowMarkup = '
            <div class="lay-webgl-slideshow" 
            data-transition="'.$webglslideshow['carousel_options']['transition'].'" 
            data-autoplaySpeed="'.$webglslideshow['carousel_options']['autoplaySpeed'].'"
            data-transitionSpeed="'.$webglslideshow['carousel_options']['transitionSpeed'].'"
            data-autoplayDelay="'.$webglslideshow['carousel_options']['autoplayDelay'].'">
                <canvas></canvas>
                <div class="lay-webgl-slides">';
                for( $i = 0; $i < count($webglslideshow['carousel']); $i++ ) {
                    // todo: srcset 
                    // $src = LTUtility::getFullURL($webglslideshow['carousel'][$i]['cont']);
                    $slide = $webglslideshow['carousel'][$i];
                    $slide['extra_attrs'] = 'data-parent-fit="cover"';
                    $this->webglslideshowMarkup .= '<div class="lay-webgl-slide">'.LayElFunctions::getLazyImg($slide).'</div>';
                }
            $this->webglslideshowMarkup .= '
                </div>
            </div>';
            // Inline styles for "use only for left/right half" (desktop and phone)
            if ( $relid != '' ) {
                $opts = $webglslideshow['carousel_options'];
                $only_left = isset($opts['onlyLeftHalf']) && $opts['onlyLeftHalf'];
                $only_right = isset($opts['onlyRightHalf']) && $opts['onlyRightHalf'];
                $only_left_phone = isset($opts['onlyLeftHalfPhone']) && $opts['onlyLeftHalfPhone'];
                $only_right_phone = isset($opts['onlyRightHalfPhone']) && $opts['onlyRightHalfPhone'];
                if ( $only_left || $only_right || $only_left_phone || $only_right_phone ) {
                    $bp = (int) get_option('lay_breakpoint', 600);
                    $selector = '.row-id-' . $relid . ' .lay-webgl-slideshow';
                    $styles = array();
                    if ( $only_left ) {
                        $styles[] = '@media (min-width: ' . ($bp + 1) . 'px) { ' . $selector . ' { width: 50%; left: 0; right: auto; } }';
                    }
                    if ( $only_right ) {
                        $styles[] = '@media (min-width: ' . ($bp + 1) . 'px) { ' . $selector . ' { width: 50%; left: 50%; right: 0; } }';
                    }
                    if ( $only_left_phone ) {
                        $styles[] = '@media (max-width: ' . $bp . 'px) { ' . $selector . ' { width: 50%; left: 0; right: auto; } }';
                    }
                    if ( $only_right_phone ) {
                        $styles[] = '@media (max-width: ' . $bp . 'px) { ' . $selector . ' { width: 50%; left: 50%; right: 0; } }';
                    }
                    if ( !empty($styles) ) {
                        $this->webglslideshowHalfStyles = '<style>' . implode(' ', $styles) . '</style>';
                    }
                }
            }
        }

        // todo: test bg video and bg video with image set

        // bg video.
		// if on touch device and we have "playsinline" support, then show video with playsinline attribute
		// otherwise show image if available
		// playsinline is supported from iOS 10 WebKit and Chrome > 53
		// bg video, on touch devices just show image as background in the same way I use a normal row background image
		if( is_array($bgvideo) && array_key_exists('mp4', $bgvideo) ) {

			$setBgColorToOuterWrap = false;
			$row_has_bg = true;
			$this->videoMarkup = '';
			$mp4 = LTUtility::getFullURL($bgvideo['mp4']);
			$poster = array_key_exists('image', $bgvideo) ? '' : 'poster="'.LTUtility::getFullURL($bgvideo['image']['full']).'"';

            $useExternalVideo = array_key_exists('useExternalVideo', $bgvideo) ? $bgvideo['useExternalVideo'] : '';
            $externalVideoUrl = array_key_exists('externalVideoUrl', $bgvideo) ? $bgvideo['externalVideoUrl'] : '';

			$unmuteButtonMarkup = '';
			if (array_key_exists('unmutebutton', $bgvideo) && ($bgvideo['unmutebutton'] === true || $bgvideo['unmutebutton'] === 'true' || $bgvideo['unmutebutton'] == 1)) {
				$unmuteButtonMarkup = LayElFunctions::getUnmuteButtonMarkup($bgvideo);
				// Avoid invalid HTML inside an <a>. Using <span> keeps styling/JS behavior intact.
				if ($linkMarkup != false) {
					$unmuteButtonMarkup = str_replace('<div class="lay-mute-unmute-button', '<span class="lay-mute-unmute-button', $unmuteButtonMarkup);
					$unmuteButtonMarkup = str_replace('</div>', '</span>', $unmuteButtonMarkup);
				}
			}

            if($useExternalVideo) {
                $mp4 = $externalVideoUrl;
            }

			if ($linkMarkup != false) {
				$this->videoMarkup = $linkMarkup;
				$rowBgClass .= 'row-bg-link';
			} else {
				$this->videoMarkup = '<div';
            }
            
            // show normal video if on desktop or if on touchdevice and playsinline is supported
            // don't need poster when I'm not on touch device
            if ( LTUtility::$supportsPlaysInlineOnTouchDevice && LTUtility::$isTouchDevice || !LTUtility::$isTouchDevice ) {
                $video_inline_styles = array();
                if ( array_key_exists( 'bgimageopacity', $row ) && $row['bgimageopacity'] != 100 ) {
                    $video_inline_styles[] = '-webkit-filter:brightness('.$row['bgimageopacity'].'%)';
                    $video_inline_styles[] = 'filter:brightness('.$row['bgimageopacity'].'%)';
                }
                $video_inline_styles[] = 'object-fit:'.$row_bg_fit;
                $video_inline_styles[] = 'object-position:'.$row_bg_pos;
                $bg_brightness_style = 'style="'.esc_attr( implode( ';', $video_inline_styles ) ).'"';
                $rowBgClass .= ' background-video';
                $video_type = LTUtility::getVideoMimeTypeFromUrl($mp4);
                if( Setup::$is_firefox && LayFrontend_Options::$enable_video_lazyloading_for_firefox == false || Setup::$is_safari && LayFrontend_Options::$enable_video_lazyloading_for_safari == false || Setup::$is_instagram ) {
                    $this->videoMarkup .= ' class="'.$rowBgClass.'"><video '.$bg_brightness_style.' data-ar="'.$bgvideo['mp4Ar'].'" playsinline data-autoplay muted autoplay loop class="autoplay"><source src="'.$mp4.'" type="'.$video_type.'"></video>'.$unmuteButtonMarkup;
                } else {
                    $this->videoMarkup .= ' class="'.$rowBgClass.'"><video '.$bg_brightness_style.' data-ar="'.$bgvideo['mp4Ar'].'" preload="metadata" playsinline muted data-autoplay loop class="autoplay video-lazyload"><source data-src="'.$mp4.'" type="'.$video_type.'"></video>'.$unmuteButtonMarkup;
                }
            }
            // no playsinline support. just add background image if available
            else if( !LTUtility::$supportsPlaysInlineOnTouchDevice && LTUtility::$isTouchDevice ) {
                if( array_key_exists('image', $bgvideo) && $bgvideo['image'] != "" ) {
                    $full_src = LTUtility::getFullURL($bgvideo['image']['full']);
                    $smallest_src = LTUtility::getFullURL($bgvideo['image']['_265']);
                    $isGif = substr($full_src, -4) == '.gif' ? true : false;
                    $alt = '';
                    $srcset = '';
                    if( array_key_exists('imageAttid', $bgvideo) ){
                        $alt = LTUtility::getAltTag($bgvideo['imageAttid']);
                        $srcset = wp_get_attachment_image_srcset( $bgvideo['imageAttid'] );
                    }else{
                        $alt = get_bloginfo('name');
                        $srcset = LayRowFunctions::getBackgroundVideoBgSet( $bgvideo['image'] );
                    }
        
                    $ar = array_key_exists('imageAr', $bgvideo) ? $bgvideo['imageAr'] : '';
                    // i need data-ar so i can size the backgrounds in case browser has no object-fit
                    $rowBgClass .= ' background-image';
                    $this->videoMarkup .= ' class="'.$rowBgClass.'">';
                    // data w and data h are not the actual heights and widths of the original image. However, i need this data to calculate the correct sizes attribute, it is about the aspect ratio, not the real width and height of the original image
                    $dataHdataW = LayRowFunctions::getBgDataWDataH($bgimage);
                    $aspect_ratio_for_lazysizes = array_key_exists('imageW', $bgvideo) ? $bgvideo['imageW'] / $bgvideo['imageH'] : '';
                    $row_vid_fallback_img_style = ' style="'.esc_attr( 'object-fit:'.$row_bg_fit.';object-position:'.$row_bg_pos.';' ).'"';
                    // same as in getLazyImg
                    switch( LayFrontend_Options::$image_loading ) {
                        case 'instant_load':
                            if( $isGif ) {
                                $this->videoMarkup .= '<img class="lay-gif" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_vid_fallback_img_style.'/>';
                            }
                            // im just changing 'isDesktopOrTabletSize' to !LTUtility::$isPhone, i think that makes sense
                            else if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                                $this->videoMarkup .= '<img class="lay-image-original" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_vid_fallback_img_style.'/>';
                            }
                            else{
                                $this->videoMarkup .= '<img class="lay-image-responsive setsizes'.$row_bg_setsizes_cover_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$smallest_src.'" data-srcset="'.$srcset.'" sizes="" alt="'.$alt.'" '.$dataHdataW.$row_vid_fallback_img_style.'/>';
                            }
                        break;
                        case 'lazy_load':
                            // https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/parent-fit
                            // needs data-aspectratio: <!-- Usage of the data-aspectratio attribute: Divide width by height: 400/800 = data-aspectratio="0.5" -->
                            if( $isGif ) {
                                $this->videoMarkup .= '<img class="lay-gif lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_vid_fallback_img_style.'/>';
                            }
                            if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                                $this->videoMarkup .= '<img class="lay-image-original lazyload" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$full_src.'" alt="'.$alt.'"'.$row_vid_fallback_img_style.'/>';
                            }
                            else{
                                $this->videoMarkup .= '<img class="lazyload setsizes'.$row_bg_setsizes_cover_class.'" src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-ar="'.$ar.'" data-src="'.$smallest_src.'" data-srcset="'.$srcset.'" data-aspectratio="'.$aspect_ratio_for_lazysizes.'" data-parent-fit="'.esc_attr( $row_bg_fit ).'" alt="'.$alt.'"'.$row_vid_fallback_img_style.'/>';
                            }
                        break;
                    }
                }
            }

            if ($linkMarkup != false) {
                $this->videoMarkup .= '</a>';
            } else {
                $this->videoMarkup .= '</div>';
            }
        }

        $linkForEmptyBackground = '';
		if (!$row_has_bg && $linkMarkup != false) {
            $linkMarkup .= ' class="row-bg-link no-row-bg-link-children"></a>';
            $this->linkForEmptyBackground = $linkMarkup;
		}

		// set bg color only if there is no bgvideo or bgimage. because otherwise setting the bgcolor would overlay the bgvideo/-image, because it is applied to the wrapping container
		if( $bgcolor != '' && $setBgColorToOuterWrap ) {
            $row_has_bg = true;
            // adding it to row inner css as well because fullscreen slider sometimes removes that row css
            $this->row_css .= ' background-color:'.$bgcolor.';';
		}

        if( $bgcolor != '' ) {
            $row_has_bg = true;
            // im always setting bg color to row inner. people can have a svg or png as a background, so i cant just not set a bg color at all
            $this->row_inner_css .= ' background-color:'.$bgcolor.';';
        }

		// Row inner space (padding) from gridder rowAttrs
		$row_padding_top = array_key_exists( 'rowPaddingTop', $row ) ? $row['rowPaddingTop'] : null;
		$row_padding_top_mu = array_key_exists( 'rowPaddingTopMu', $row ) ? $row['rowPaddingTopMu'] : 'px';
		$row_padding_bottom = array_key_exists( 'rowPaddingBottom', $row ) ? $row['rowPaddingBottom'] : null;
		$row_padding_bottom_mu = array_key_exists( 'rowPaddingBottomMu', $row ) ? $row['rowPaddingBottomMu'] : 'px';
		if ( $row_padding_top !== null && $row_padding_top !== '' ) {
			$this->row_inner_css .= ' padding-top:' . (float) $row_padding_top . ( $row_padding_top_mu === '%' ? '%' : 'px' ) . ';';
		}
		if ( $row_padding_bottom !== null && $row_padding_bottom !== '' ) {
			$this->row_inner_css .= ' padding-bottom:' . (float) $row_padding_bottom . ( $row_padding_bottom_mu === '%' ? '%' : 'px' ) . ';';
		}

		if( $row_has_bg ) {
			$this->row_classes .= ' has-background';
		}

        if( $relid != '' ) {
            $this->row_classes .= ' row-id-'.$relid;
        }

        if( array_key_exists('sameHeightTextElements', $row) && $row['sameHeightTextElements'] === true ) {
            $same_height_media_fit = array_key_exists('sameHeightMediaFit', $row) ? $row['sameHeightMediaFit'] : 'cover';
            if( !in_array( $same_height_media_fit, array( 'cover', 'contain' ), true ) ) {
                $same_height_media_fit = 'cover';
            }
            $include_text = array_key_exists('sameHeightIncludeText', $row) ? $row['sameHeightIncludeText'] === true : true;
            $include_images = array_key_exists('sameHeightIncludeImages', $row) ? $row['sameHeightIncludeImages'] === true : false;
            $include_videos = array_key_exists('sameHeightIncludeVideos', $row) ? $row['sameHeightIncludeVideos'] === true : false;
            $include_stacks = array_key_exists('sameHeightIncludeStacks', $row) ? $row['sameHeightIncludeStacks'] === true : false;
            $same_height_participant_count = 0;
            if( array_key_exists('columns', $row) && is_array($row['columns']) ) {
                foreach( $row['columns'] as $col ) {
                    $col_type = array_key_exists('type', $col) ? $col['type'] : '';
                    if( !empty($col['placeFreely']) || $col_type === 'vl' ) {
                        continue;
                    }
                    $matches_same_height =
                        ( $include_text && $col_type === 'text' ) ||
                        ( $include_images && $col_type === 'img' ) ||
                        ( $include_videos && ( $col_type === 'html5video' || $col_type === 'video' ) ) ||
                        ( $include_stacks && $col_type === 'stack' );
                    if( $matches_same_height ) {
                        $same_height_participant_count++;
                    }
                }
            }
            if( $same_height_participant_count > 1 ) {
                $this->row_classes .= ' lay-row-same-height-elements';
                $this->row_classes .= $include_text ? ' lay-row-same-height-text-elements lay-row-same-height-include-text' : '';
                $this->row_classes .= $include_images ? ' lay-row-same-height-include-image' : '';
                $this->row_classes .= $include_videos ? ' lay-row-same-height-include-video' : '';
                $this->row_classes .= $include_stacks ? ' lay-row-same-height-include-stack' : '';
                $this->row_classes .= ' lay-row-same-height-media-'.$same_height_media_fit;
            }
        }
    }

    public function getOpeningMarkup( ){
        // todo: test row background color
        $id_attr = $this->html_id != '' ? 'id="'.$this->html_id.'"' : '';

		$collapse = array_key_exists('collapse', $this->row) ? $this->row['collapse'] : false;
        $data_collapsed = 'data-collapsed="';
        if( $collapse == true ) {
            $data_collapsed  .= 'true';
        } else {
            $data_collapsed  .= 'false';
        }
        $data_collapsed .= '"';

        // when row is collapsed, we dont add rowcustomheight_css as actual css
        // rowcustomheight_css is added through "data-original-rowcustomheight" and we use that

        
        $rowcustomheight_data_attr = '';
        if( trim($this->rowcustomheight_css) !== '' ) {
            $rowcustomheight_for_data_attribute = str_replace( 'min-height:calc(', '', $this->rowcustomheight_css );
            $rowcustomheight_for_data_attribute = str_replace( ');', '', $rowcustomheight_for_data_attribute );
            $rowcustomheight_data_attr = 'data-original-rowcustomheight="'.$rowcustomheight_for_data_attribute.'"';
        }

        $inner_css_for_rowcustomheight = '';
        $temp_css = $this->row_css;
        if( $collapse == true ) {
            $temp_css .= 'height:0px;';
            $this->row_classes .= ' hide-overflow';
        } else {
            // if row is not collapsed we add the row custom height css
            $temp_css .= $this->rowcustomheight_css;
        }

        if( trim($this->rowcustomheight_css) !== '' ) {
            $inner_css_for_rowcustomheight = $this->rowcustomheight_css;
        }        

        $style = $temp_css != '' ? 'style="'.$temp_css.'"' : '';
        return
        ($this->webglslideshowHalfStyles !== '' ? $this->webglslideshowHalfStyles : '')
        . ($this->phone_bg_override_css !== '' ? $this->phone_bg_override_css : '')
        .'<div class="row '.$this->row_classes.' '.$this->html_classes.' row-'.$this->index.'" '.$rowcustomheight_data_attr.' '.$data_collapsed.' '.$id_attr.' '.$style.'>
            '.$this->backgroundImageMarkup.'
            '.$this->videoMarkup.'
            '.$this->webglslideshowMarkup.'
            '.$this->linkForEmptyBackground.'        
            <div class="row-inner '.$this->row_inner_classes.'" style="'.$inner_css_for_rowcustomheight.'">
                    <div class="column-wrap '.$this->column_wrap_classes.'" style="'.$inner_css_for_rowcustomheight.' '.$this->row_inner_css.'">';
    }

    public function getClosingMarkup( ){
        return
        '</div>
            </div>
                </div>';
    }

}
