<?php

// todo: when editing carousel I can set "slides" → "center slides". does that interefere with the setting lay options → carousel addon → horizontal alignment of slides  ?

// todo: see if for example calling LayElFunctions::getHTML5VideoMarkup will break the website if that function doesn't exist
// todo: show original image only if desktop size? 

class LayCarouselPluginFrontend {

    public static $config;

    public function __construct(){

        LayCarouselPluginFrontend::$config = array(
            'numbersPosition' => '',
            'captionsPosition' => '',
            'showCaptions' => '',
            'showNumbers' => '',
        );


        LayCarouselPluginFrontend::$config['showCaptions'] = LayCarouselPluginOptions::$showCaptions;
        LayCarouselPluginFrontend::$config['showNumbers'] = LayCarouselPluginOptions::$showNumbers;

        if( LayCarouselPluginOptions::$showNumbers ) {
            LayCarouselPluginFrontend::$config['numbersPosition'] = 'numbers-'.LayCarouselPluginOptions::$numbersPosition;
        }
        if( LayCarouselPluginOptions::$showCaptions ) {
            LayCarouselPluginFrontend::$config['captionsPosition'] = 'captions-'.LayCarouselPluginOptions::$captionsPosition;
        }

        // if( LayCarouselPluginOptions::$showCircles ) {
        //     LayCarouselPluginFrontend::$config['showCaptions'] = false;
        //     LayCarouselPluginFrontend::$config['showNumbers'] = false;
        // }
    }

    /*
        caveat for captionsUnderSlides option:
        When using the setting "captions under slides" when show multiple slides is active,
        the slides (.slide-inner) themselves need a fixed-height and all of the parent containers cant have a fixed height.
        this way the carousels height will be determined by the height of the slide + the height of the captions under the slides

        for other settings we default back to the old way of positioning and setting height,
        meaning: set height on the outer container and position inner container with position: absolute;
    */

    public static function getMarkup($el, $render_options = array()){
        $captions = array();
        $i;
        $carousel = $el['carousel'];
        $options = array_key_exists('carousel_options', $el) ? $el['carousel_options'] : false;   
        $slidesWidth = '';
        $transition = LayCarouselPluginOptions::$transition;
        $classesWrap = '';
        $classesCarousel = '';
        $classesCarousel .= 'content-alignment-'.LayCarouselPluginOptions::$contentAlignment;     
        $autoplaySpeed = LayCarouselPluginOptions::$autoplaySpeed;
        $transitionSpeed = LayCarouselPluginOptions::$transitionSpeed;
        $autoplayDelay = LayCarouselPluginOptions::$autoplayDelay;
        $spacebetween = 0;
        $caption_of_carousel = array_key_exists('caption', $el) ? $el['caption'] : false;

        // if( LayCarouselPluginOptions::$sinkPosition == 'above' ) {
        //     $markup = $sink.$markup;
        // } else {
            // $markup .= $sink;
        // }

        // captions in sink or caption under slides. the difference here is: a caption is underneath each slide when showing multiple slides at once
        // instead of just a caption in the sink that is updated when the index of the carousel changes
        $captionsUnderSlides = false;
        if( array_key_exists('captionsUnderSlides', $options) && $options['captionsUnderSlides'] && LayCarouselPluginFrontend::$config['showCaptions'] ) {
            $captionsUnderSlides = true;
        }
        if( LayCarouselPluginOptions::$buttonsOutside ) {
            $classesWrap .= ' place-buttons-outside';
        } else {
            $classesWrap .= ' dont-place-buttons-outside';
        }

        // captions and numbers
        $captionsAndNumbersSamePosition = false;
        if( LayCarouselPluginOptions::$showNumbers && LayCarouselPluginOptions::$showCaptions ) {
            if ( LayCarouselPluginOptions::$numbersPosition == LayCarouselPluginOptions::$captionsPosition ) {
                $captionsAndNumbersSamePosition = true;
            }
        }
        if( $captionsAndNumbersSamePosition ) {
            $classesWrap .= " captions-and-numbers-same-position";
        } else {
            $classesWrap .= " captions-and-numbers-not-same-position";
        }

        if( $options ) {

            // turn off almost everything if transition is horizontal scroll
            if ( array_key_exists('transition', $options) && $options['transition'] == 'scroll'){
                $captionsUnderSlides = false;
                $options['loop']= false;
                $options['autoplay'] = false;
                $options['slidesWidth'] = '';
                $options['showMultipleSlides'] = false;
                $options['disableClick'] = false;
                $captionsUnderSlides = false;
                $options['freeScroll'] = false;
                $options['mousewheelScroll'] = false;
                $options['centeredSlides'] = false;
            }


            if( array_key_exists('transitionSpeed', $options ) ){
                $transitionSpeed = $options['transitionSpeed'];
            }
            if( array_key_exists('autoplaySpeed', $options ) ){
                $autoplaySpeed = $options['autoplaySpeed'];
            }
            if( array_key_exists('autoplayDelay', $options ) ){
                $autoplayDelay = $options['autoplayDelay'];
            }
            if( array_key_exists('spaceBetween', $options ) ){
                $spacebetween = $options['spaceBetween'];
            }
            if( array_key_exists('slidesWidth', $options) ){
                if( $options['slidesWidth'] != '' ) {
                    $slidesWidth = 'width:calc('.$options['slidesWidth'].');';
                }
            }
            if( array_key_exists('random', $options ) && $options['random'] ){
                shuffle($carousel);
            }
            if( $options['autoplay'] == true ){
                $autoplay = true;
                $classesCarousel .= ' autoplay';
            } else {
                $classesCarousel .= ' no-autoplay';
            }
            if ( array_key_exists('showMultipleSlides', $options) && $options['showMultipleSlides'] ) {
                $showMultipleSlides = true;
            } else {
                $captionsUnderSlides = false;
            }
            if ( array_key_exists('transition', $options) ){
                $transition = $options['transition'];
            }

            $loop = LayCarouselPluginOptions::$loop;
            if( array_key_exists('loop', $options ) ){
                $loop = $options['loop'];
            }
            // need to disable loop for touch devices, because it makes ios safari crash since like late 2023 :)
            // https://discussions.apple.com/thread/254843801?sortBy=best
            if( $transition == 'sliding' && class_exists('LTUtility') ) {
                // LayCarouselPluginOptions::$laycarousel_loop_touchdevice can be either 'on' or ''
                $loop = LTUtility::$isTouchDevice && LayCarouselPluginOptions::$laycarousel_loop_touchdevice == '' ? false : $loop;
            }
            $classesWrap .= $loop == true ? ' loop-slides' : ' dont-loop-slides hide-previous-related-ui';

            if($transition == 'scroll'){
                $classesWrap .= ' show-multiple-slides';
            } else {
                if ( array_key_exists('showMultipleSlides', $options) && $options['showMultipleSlides'] ) {
                    $classesWrap .= $options['showMultipleSlides'] == true ? ' show-multiple-slides' : ' dont-show-multiple-slides';
                } else {
                    $classesWrap .= ' dont-show-multiple-slides';
                }
            }

            $enableClickAndDrag = true;
            if ( array_key_exists('disableClick', $options) && $options['disableClick'] && $options['autoplay'] == true ) {
                $classesCarousel .= ' disable-click-and-drag';
                $classesWrap .= ' disable-click-and-drag';
                $enableClickAndDrag = false;
            } else {
                $classesCarousel .= ' enable-click-and-drag';
                $classesWrap .= ' enable-click-and-drag';
                $enableClickAndDrag = true;
            }
            if ( array_key_exists('freeScroll', $options) && $options['freeScroll'] && $enableClickAndDrag) {
                $classesCarousel .= ' free-scroll';
            } else {
                $classesCarousel .= ' no-free-scroll';
            }
            if ( array_key_exists('mousewheelScroll', $options) && $options['mousewheelScroll'] ){
                $classesCarousel .= ' mousewheel-scroll';
            }
            if ( $captionsUnderSlides ){
                $classesCarousel .= ' captions-under-slides';
            } else {
                $classesCarousel .= ' no-captions-under-slides';
            }
            if ( array_key_exists('size', $options) ){
                $classesWrap .= ' '.$options['size'];
            }
            if ( array_key_exists('fillSlides', $options) ){
                $classesWrap .= $options['fillSlides'] == true ? ' fill-slides' : ' dont-fill-slides';
            } else {
                $classesWrap .= ' dont-fill-slides';
            }
            if ( array_key_exists('slidesWidth', $options) ){
                if ( $options['slidesWidth'] == '' ||  $options['slidesWidth'] == "null" || $options['slidesWidth'] == null){
                    $classesWrap .= ' no-fixed-slides-width';
                } else {
                    $classesWrap .= ' fixed-slides-width';
                }
            } else {
                $classesWrap .= ' no-fixed-slides-width';
            }
            if ( array_key_exists('fixedHeight', $options) ){
                if ( $options['fixedHeight'] == 'auto' ) {
                    $classesCarousel .= ' fixed-height-auto';
                }else{
                    $classesCarousel .= ' no-fixed-height-auto';
                }
                if( $options['fixedHeight'] == '' ) {
                    $classesWrap .= ' no-fixed-height';
                }
            }
            else{
                $classesCarousel .= ' no-fixed-height-auto';
                $classesWrap .= ' no-fixed-height';
            }
            if( array_key_exists( 'centeredSlides', $options ) && $options['centeredSlides'] ) {
                $classesCarousel .= ' centered-slides';
            }
        }
    
        $classesCarousel .= ' transition-'.$transition;

        if( LayCarouselPluginOptions::$showCircles ) {
            $classesWrap .= ' show-circles';
        } else {
            $classesWrap .= ' no-circles';
        }
        if( $transition != 'sliding' ) {
            $classesWrap .= ' no-swiper';
        }

        if( $transition == 'fading' || $transition == 'immediate' ) {
            $classesWrap .= ' use-fade-transition';
        }

        $classesWrap .= ' wrap-transition-'.$transition;

        $classesWrap .= ' sink-'.LayCarouselPluginOptions::$sinkPosition;
        if( LayCarouselPluginOptions::$pauseAutoplayOnHover ){
            $classesWrap .= ' pause-autoplay-on-hover';
        }

        $firstAr = 9/16;
        $pb = 9/16 * 100;

        if( $carousel[0]['type'] != "text" ) {
            $firstAr = $carousel[0]['ar'];
            // Handle external videos
            if (array_key_exists('useExternalVideo', $carousel[0]) && $carousel[0]['useExternalVideo'] === true && array_key_exists('externalVideoH', $carousel[0]) && array_key_exists('externalVideoW', $carousel[0])) {
                $firstAr = $carousel[0]['externalVideoH'] / $carousel[0]['externalVideoW'];
            }
            $pb = $firstAr * 100;
        }

        $fixedHeight = null;
        $fixedHeightPhone = null;
        if ( array_key_exists('customAspectRatio', $options) == false  ) {
            if( $options['size'] == 'aspectRatioOfFirstElement' ) {
                if( $carousel[0]['type'] != "text" ) {
                    $firstAr = $carousel[0]['ar'];
                    // Handle external videos
                    if (array_key_exists('useExternalVideo', $carousel[0]) && $carousel[0]['useExternalVideo'] === true && array_key_exists('externalVideoH', $carousel[0]) && array_key_exists('externalVideoW', $carousel[0])) {
                        $firstAr = $carousel[0]['externalVideoH'] / $carousel[0]['externalVideoW'];
                    }
                    $pb = $firstAr * 100;
                }
                $showMultipleSlides = false;
            } else if ( $options['size'] == 'useCustomAspectRatio') {
                $firstAr = $options['customAspectRatioH'] / $options['customAspectRatioW'];
                $pb = $firstAr * 100;
            } else if ( $options['size'] == 'fixedHeight') {
                $fixedHeight = $options['fixedHeight'];
                if ( array_key_exists('useCustomFixedHeightPhone', $options) && $options['useCustomFixedHeightPhone'] && array_key_exists('fixedHeightPhone', $options) && trim($options['fixedHeightPhone']) != '' ) {
                    $fixedHeightPhone = $options['fixedHeightPhone'];
                }
            }
        } else if( $options['customAspectRatio'] == true ){
            $firstAr = $options['customAspectRatioH'] / $options['customAspectRatioW'];
            $pb = $firstAr * 100;
            $showMultipleSlides = false;
        }
        // Only use fullpage 100vh special case when desktop is exactly 100vh and no custom phone height is set
        if( $fixedHeight === '100vh' && !$fixedHeightPhone ){
            $classesCarousel .= ' _100vh';
            $classesWrap .= ' contains_100vh-carousel';
        }

        if ( array_key_exists('fixedHeight', $options) ){
            if ($options['fixedHeight'] == 'auto'){
                $classesCarousel .= ' fixed-height-auto';
            } else {
                $classesCarousel .= ' fixed-height-not-auto';
            }
        }else{
            $classesCarousel .= ' fixed-height-not-auto';
        }
        $classesCarousel .= ' text-alignment-'.LayCarouselPluginOptions::$textalignment;

        $autoplayData = 'data-autoplayspeed="'.$autoplaySpeed.'" data-autoplaydelay="'.$autoplayDelay.'"';

        // SINK START!
        for( $i=0; $i<count($carousel); $i++ ) {
            $caption = array_key_exists('caption', $carousel[$i]) ? $carousel[$i]['caption'] : '';
            $caption = self::apply_qtranslate_to_markup( $caption );
            $caption .= LayCarouselPluginFrontend::addProjectTagsToCaption($carousel[$i], $caption);
            $captions []= $caption;
        }

        $captionMarkup = '';

        // captions and numbers
        if( LayCarouselPluginFrontend::$config['showCaptions'] ){
            $allEmpty = true;
            if( $caption_of_carousel != '' ) {
                $allEmpty = false;
            }
            for ($i = 0; $i < count($captions); $i++) {
                $current_caption = '';
                if( trim($captions[$i]) != "" ) { 
                    $current_caption = $captions[$i];
                    $allEmpty = false;
                } else {
                    $current_caption = self::apply_qtranslate_to_markup( $caption_of_carousel );
                }

                // if numbers and caption are both for example "center", this will put the numbers inside of the caption, next to it, to the left
                // instead of overlapping it
                $numberMarkupInCaption = '';
                if( $captionsAndNumbersSamePosition ) {
                    $numberMarkupInCaption = '<span class="numbers _'.LayCarouselPluginOptions::$numberTextformat.'_no_spaces"></span>';
                }
                $caption_active_class = '';
                if( $i == 0 ) {
                    $caption_active_class = 'caption-active';
                }

                if ( $captionsAndNumbersSamePosition && LayCarouselPluginOptions::$numbersPosition == 'right' && LayCarouselPluginOptions::$captionsPosition == 'right' ) {
                    // if both right, number should be on right side! just my opinion :)
                    $inner = '<div class="sink-caption">'.$current_caption.'</div>'.$numberMarkupInCaption;
                } else {
                    $inner = $numberMarkupInCaption.'<div class="sink-caption">'.$current_caption.'</div>';
                }

                $captionMarkup .= 
                '<div class="single-caption ix-'.$i.' '.$caption_active_class.'"><div class="single-caption-inner _'.LayCarouselPluginOptions::$captionTextformat.'_no_spaces">'
                    .$inner
                .'</div></div>';
            };

            if(LayCarouselPluginFrontend::$config['showNumbers'] && $captionsAndNumbersSamePosition) {
                $allEmpty = false;
            }

            if($allEmpty){
                $captionMarkup = '';
            }else{
                $captionMarkup = '<div class="captions-wrap"><div class="captions-inner"><div class="captions-slider">'.$captionMarkup.'</div></div></div>';
            }
        }

        $sinkClass = LayCarouselPluginFrontend::$config['numbersPosition'].' '.LayCarouselPluginFrontend::$config['captionsPosition'];
        if( LayCarouselPluginFrontend::$config['showNumbers'] && LayCarouselPluginFrontend::$config['showCaptions'] ){
            if($captionMarkup == ''){
                $sinkClass .= " sink-only-numbers";
            }else{
                $sinkClass .= " sink-with-numbers-and-captions";
            }
        }
        else if( LayCarouselPluginFrontend::$config['showNumbers'] ){
            $sinkClass .= " sink-only-numbers";
        }
        else if( LayCarouselPluginFrontend::$config['showCaptions'] ){
            $sinkClass .= " sink-only-captions";
        }


        $numberMarkup = '<span class="numbers _'.LayCarouselPluginOptions::$numberTextformat.'_no_spaces"></span>';
        if( $captionsAndNumbersSamePosition && LayCarouselPluginFrontend::$config['showNumbers'] && LayCarouselPluginFrontend::$config['showCaptions']  ) {
            $numberMarkup = '';
        }

        $sink = '';

        // dont show sink when caption is under slides. the difference here is: a caption is underneath each slide when showing multiple slides at once
        // instead of just a caption in the sink that is updated when the index of the carousel changes
        if( ( LayCarouselPluginFrontend::$config['showNumbers'] || LayCarouselPluginFrontend::$config['showCaptions'] ) && !$captionsUnderSlides ) {
            $sink = 
            '<div class="lay-carousel-sink-parent">'
                .'<div class="lay-carousel-sink '.$sinkClass.'">'
                    .$captionMarkup
                    .$numberMarkup
                .'</div>'
            .'</div>';
        }

        // dots for fadecarousel, only for transition: none, scroll, fade!
        if( LayCarouselPluginOptions::$showCircles && $transition != "sliding" && $enableClickAndDrag){
            $sink .= '<div class="laycarousel-bullets swiper-pagination-bullets">';
            for( $j=0; $j<count($carousel); $j++){
                $sink .= '<li class="swiper-pagination-bullet"></li>';
            }
            $sink .= '</div>';
        }

        $sinkAbove = LayCarouselPluginOptions::$sinkPosition == 'above' ? $sink : '';
        $sinkBelow = '';
        if( LayCarouselPluginOptions::$sinkPosition != 'above' ) {
            $sinkBelow = $sink;
        }
        // # END SINK!


        $relid = array_key_exists('relid', $el) ? $el['relid'] : '';

        // Optional fixed heights via CSS instead of inline style, per-carousel based on relid.
        // When desktop + custom phone layout both contain a carousel, the same relid appears twice in the DOM;
        // scope with grid_container_selector + .col.id-{relid} (same pattern as LayImg) so rules don't collide.
        $heightCSS = '';
        $grid_sel = isset($render_options['grid_container_selector']) ? trim((string) $render_options['grid_container_selector']) : '';
        $relid_esc = esc_attr($relid);
        if ( $grid_sel !== '' && $relid !== '' && $relid !== null ) {
            $suffix = ' .col.id-' . (int) $relid . ' .lay-carousel[data-relid="' . $relid_esc . '"]';
            if ( class_exists( 'Lay_LayoutFunctions' ) && method_exists( 'Lay_LayoutFunctions', 'grid_container_scoped_selectors' ) ) {
                $selector = Lay_LayoutFunctions::grid_container_scoped_selectors( $grid_sel, $suffix );
            } else {
                $roots = array_map( 'trim', explode( ',', $grid_sel ) );
                $sel_parts = array();
                foreach ( $roots as $root ) {
                    if ( $root !== '' ) {
                        $sel_parts[] = $root . $suffix;
                    }
                }
                $selector = count( $sel_parts ) > 0 ? implode( ', ', $sel_parts ) : '';
            }
            if ( $selector === '' ) {
                $selector = '.lay-carousel[data-relid="' . $relid_esc . '"]';
            }
        } else {
            $selector = '.lay-carousel[data-relid="' . $relid_esc . '"]';
        }

        // Desktop / default height when not using the fullpage 100vh special case (exactly 100vh with no phone override)
        $use_fullpage_100vh = ( $fixedHeight === '100vh' && !$fixedHeightPhone );
        if ( $fixedHeight && $captionsUnderSlides == false && !$use_fullpage_100vh ) {
            $heightCSS .= $selector.'{ height: calc('.$fixedHeight.'); }';
        }

        // Phone-only override
        if ( $fixedHeightPhone ) {
            if ( class_exists('MiscOptions') && property_exists('MiscOptions', 'phone_breakpoint') ) {
                $phone_breakpoint = MiscOptions::$phone_breakpoint;
            } else {
                $phone_breakpoint = (int)get_option( 'lay_breakpoint', 600 );
            }
            $heightCSS .= '@media (max-width: '.$phone_breakpoint.'px){'.$selector.'{ height: calc('.$fixedHeightPhone.'); }}';
        }

        $heightStyleTag = '';
        if ( $heightCSS !== '' ) {
            $heightStyleTag = '<style>'.$heightCSS.'</style>';
        }

        $classesWrap .= ' horizontal-alignment-'.LayCarouselPluginOptions::$horizontalAlignment;
        if( array_key_exists('extraclass', $options) ) {
            $classesWrap .= ' '.$options['extraclass'];
        }

        $markup = $heightStyleTag.'<div class="lay-carousel-wrap '.$classesWrap.'">
        '.$sinkAbove.'
        <div class="lay-carousel '.$classesCarousel.'" data-relid="'.$relid.'" '.$autoplayData.' data-transitionspeed="'.$transitionSpeed.'" data-spacebetween="'.$spacebetween.'" ';
        if ( $fixedHeight == null ) {
            $markup .= 'style="padding-bottom:'.$pb.'%;" data-carouselAr="'.$firstAr.'"';
        }
        $markup .= '>';
        $slidesHeight = '';
        if ( $fixedHeight && $captionsUnderSlides ) {
            $slidesHeight = 'height: calc(' . $fixedHeight . ')';
        }
        if( $transition == 'sliding' || $transition == 'immediate' ) {
            $markup .= '<div class="swiper-container swiper"><div class="swiper-wrapper">';
        }
        if( $transition == 'scroll' ) {
            $markup .= '<div class="lay-carousel-scroller"><div class="lay-carousel-scroller-inner">';
        }
        $slideClass = '';
        if( $transition == 'sliding' ) {
            $slideClass = 'swiper-slide';
        }
        $captions = array();
        for( $i=0; $i<count($carousel); $i++ ) {
            $link = '';
            // dont create links for html5 videos because the function that gets the html5 video already creates a link
            if ( array_key_exists('imagelink', $carousel[$i]) && $carousel[$i]['type'] != 'html5video' ) {
                $linkTarget = $carousel[$i]['imagelink']['newtab'] ? ' target="_blank"' : '';
                $url = '';
                $rawUrl = '';
                if ( array_key_exists('url', $carousel[$i]['imagelink']) ) {
                    $rawUrl = trim((string) $carousel[$i]['imagelink']['url']);
                }
                if( method_exists( 'LTUtility', 'getFullURL' ) ){
                    $url = LTUtility::getFullURL($rawUrl);
                }
                $data_id = '';
                if( array_key_exists('id', $carousel[$i]['imagelink']) && $carousel[$i]['imagelink']['id'] != '' ) {
                    $data_id = 'data-id="'.$carousel[$i]['imagelink']['id'].'"';
                }
                $data_type = '';
                if( array_key_exists('type', $carousel[$i]['imagelink']) && $carousel[$i]['imagelink']['type'] != '' ) {
                    $data_type = 'data-type="'.$carousel[$i]['imagelink']['type'].'"';
                }
                $data_title = '';
                if( array_key_exists('title', $carousel[$i]['imagelink']) && $carousel[$i]['imagelink']['title'] != '' ) {
                    $data_title = 'data-title="'.__($carousel[$i]['imagelink']['title']).'"';
                }
                
                $data_title = '';
                $normalizedRawUrl = strtolower(rtrim($rawUrl, '/'));
                $normalizedResolvedUrl = strtolower(rtrim(trim((string) $url), '/'));
                // Some clients create invalid placeholders like "https://null" instead of an empty string.
                // Treat these as placeholders so we don't render href="https://null".
                $isNullLikeUrl =
                    preg_match('~^https?://null/?$~i', $normalizedRawUrl) === 1
                    || preg_match('~^https?://null/?$~i', $normalizedResolvedUrl) === 1;

                $isPlaceholderUrl =
                    $isNullLikeUrl
                    || in_array($normalizedRawUrl, array('', 'null', 'http:', 'https:'), true)
                    || in_array($normalizedResolvedUrl, array('', 'null', 'http:', 'https:'), true);
                $link = '<a '.$data_id.' '.$data_type.' '.$data_title.' href="'.$url.'" '.$linkTarget.'>';
                if($isPlaceholderUrl){
                    $link = '';
                }
            }
            // no use in using alt buttons when $enableClickAndDrag is false, bc i dont show any buttons when $enableClickAndDrag is false
            $use_alt_buttons = '';
            if(  array_key_exists('useAltButtons', $carousel[$i]) && $carousel[$i]['useAltButtons'] && $enableClickAndDrag ) {
                $use_alt_buttons = 'lay-use-alt-buttons';
            }
            // todo: add orientation class
            $orientation = '';
            if( array_key_exists('ar', $carousel[$i]) ) {
                $orientation = $carousel[$i]['ar'] < 1 ? 'orientation-landscape' : 'orientation-portrait';
            }

            $markup .= '<div class="lay-carousel-slide '.$orientation.' '.$slideClass.' '.$use_alt_buttons.' lay-carousel-slide-'.$carousel[$i]['type'].'" data-slide-type="'.$carousel[$i]['type'].'" data-slide-ix="'.$i.'" style="'.$slidesWidth.'">';
            $markup .= $link;
            $markup .= '<div class="slide-inner" style="'.$slidesHeight.'">';

            $caption = array_key_exists('caption', $carousel[$i]) ? $carousel[$i]['caption'] : '';
            $caption = self::apply_qtranslate_to_markup( $caption );
            $captions []= $caption;


            switch( $carousel[$i]['type'] ){
                case 'text':
                    $markup .= LayCarouselPluginFrontend::addText($carousel[$i]);
                break;
                case 'video':
                    $markup .= LayCarouselPluginFrontend::addYouTubeVimeoVideo($carousel[$i]);
                    // need placeholder div for for example: slidesWidth: 400px;, carouselHeight/fixedHeight: auto
                    $markup .= '<div class="ph" style="padding-bottom:'.($carousel[$i]['ar']*100).'%;"></div>';
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtBottomMarkup( $options, $el );
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtTopMarkup( $options, $el );
                break;
                case 'html5video':
                    $markup .= LayCarouselPluginFrontend::addHtml5Video($carousel[$i]);
                    // need placeholder div for for example: slidesWidth: 400px;, carouselHeight/fixedHeight: auto
                    $paddingBottom = $carousel[$i]['ar'] * 100;
                    if (array_key_exists('useExternalVideo', $carousel[$i]) && $carousel[$i]['useExternalVideo'] === true && array_key_exists('externalVideoH', $carousel[$i]) && array_key_exists('externalVideoW', $carousel[$i])) {
                        $paddingBottom = ($carousel[$i]['externalVideoH'] / $carousel[$i]['externalVideoW']) * 100;
                    }
                    $markup .= '<div class="ph" style="padding-bottom:'.$paddingBottom.'%;"></div>';
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtBottomMarkup( $options, $el );
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtTopMarkup( $options, $el );
                    $text_on_slide = LayCarouselPluginFrontend::getTextOnSlideOverlay($carousel[$i], $relid, $i, $options);
                    $markup .= $text_on_slide;
                break;
                case 'img':
                case 'project':
                case 'postThumbnail':
                case 'productThumbnail':
                    $fillSlides = false;
                    if( array_key_exists('fillSlides', $options) && $options['fillSlides'] == true ) {
                        $fillSlides = true;
                    }
                    $markup .= LayCarouselPluginFrontend::addImage($carousel[$i], $fillSlides);
                    // need placeholder div for for example: slidesWidth: 400px;, carouselHeight/fixedHeight: auto
                    $markup .= '<div class="ph" style="padding-bottom:'.($carousel[$i]['ar'] * 100).'%;"></div>';
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtBottomMarkup( $options, $el );
                    $markup .= LayCarouselPluginFrontend::getDarkGradientAtTopMarkup( $options, $el );
                    $text_on_slide = LayCarouselPluginFrontend::getTextOnSlideOverlay($carousel[$i], $relid, $i, $options);
                    $markup .= $text_on_slide;
                break;
            }
            // closing .lay-carousel-slide & link & .slide-inner
            $markup .= '</div>';
            // caption under slide instead of under carousel wrap:
            // just put number inside of here too xD??
            if ( $captionsUnderSlides ) {
                $markup .= 
                '<div class="lay-carousel-caption-under-slide '.LayCarouselPluginFrontend::$config['captionsPosition'].' '.LayCarouselPluginFrontend::$config['numbersPosition'].'">';
                if(LayCarouselPluginFrontend::$config['showNumbers']){
                    $markup .= '<span class="lay-carousel-caption-under-slide-number _'.LayCarouselPluginOptions::$numberTextformat.'_no_spaces">'.($i+1).'/'.count($carousel).'</span>';
                }

                $underslideCaption = '<div class="lay-carousel-caption-under-slide-caption-inner">'.$caption.'</div>';
                $underslideCaption .= LayCarouselPluginFrontend::addProjectTagsToCaption($carousel[$i], $underslideCaption);

                $markup .= 
                    '<div class="lay-carousel-caption-under-slide-caption _'.LayCarouselPluginOptions::$captionTextformat.'_no_spaces">'.$underslideCaption.'</div>
                </div>';
            }
            if ($link != '') {
                $markup .= '</a>';
            }
            $markup .= '</div>';
        }
        // closing .lay-carousel
        $markup .= '</div>';

        if( $transition == 'sliding' || $transition == 'immediate' ) {
            // closing swiper container
            $markup .= '</div>';
            // closing swiper wrapper
            $markup .= '</div>';
        }

        if( $transition == 'scroll' ) {
            // closing lay-carousel-scroller and lay-carousel-scroller-inner
            $markup .= '</div></div>';
        }

        $markup .= $sinkBelow;

        // closing .lay-carousel-wrap
        $markup .=
        '</div>';

        // not slide captions but normal caption like a caption for a video/image

        // if show slide captions is false, just show the carousel caption underneath it
        // otherwise im showing the carousel caption just like a slide caption!
        if( array_key_exists('caption', $el) && trim($el['caption'] != '') && LayCarouselPluginFrontend::$config['showCaptions'] == false ) {
            $markup .=
            '<div class="caption lay-textformat-parent">'
                .self::apply_qtranslate_to_markup( $el['caption'] )
            .'</div>';
        }

        if ( $transition == 'fading' ) {
            $markup .= '<style>.lay-carousel.transition-fading[data-relid="'.$relid.'"] .lay-carousel-slide.transition{ 
                -webkit-transition: opacity '.$transitionSpeed.'ms ease-out;
                -o-transition: opacity '.$transitionSpeed.'ms ease-out;
                transition: opacity '.$transitionSpeed.'ms ease-out; }</style>';
        }
        return $markup;
    }
    
    public static function addHtml5Video( $slide ) {

        // not setting autoplay to true because then also html5videos that are not in-view would autoplay
        // function "playPauseHtml5Videos" is autoplaying only in-view videos anyway, so we don't need the autoplay attribute
        // model.set('loop', true);
        // mute is always true, so autoplay can work
        $slide['mute'] = true;
        $slide['playpauseonclick'] = false;
        // how can a user have a video and unmute it?
        // 1: the slider should not go to the next image/video if you click it. only if you swipe it or click on the next/prev buttons
        // 2: the video must not autoplay and the user must click to play it
        // 2: alternative: the video can autoplay but there must be a unmute button
        $slide['autoplay'] = false;
        $slide['controls'] = false;
        $slide['playicon'] = false;
        $showPlaceHolderImage = true;

        // not sure why but firefox doesnt let me lazyload html5videos in carousels when entering the page not by refresh but by coming from a different page on the same lay theme website
        // thats why I do 'noLazyLoad' => true

        // why do i have playpauseonclick here AND in 'model'/$slide ?

        $nolazyload = false;
        if( property_exists('Setup', 'is_firefox') && Setup::$is_firefox == true ) {
            if( class_exists('LayFrontend_Options') && property_exists('LayFrontend_Options', 'enable_video_lazyloading_for_firefox') ) {
                if( LayFrontend_Options::$enable_video_lazyloading_for_firefox == false ) {
                    $nolazyload = true;
                }
            } else {
                $nolazyload = true;
            }
        }
        // we ran into issues with safari not lazyloading videos either :/
        if( property_exists('Setup', 'is_safari') && Setup::$is_safari == true ) {
            if( class_exists('LayFrontend_Options') && property_exists('LayFrontend_Options', 'enable_video_lazyloading_for_safari') ) {
                if( LayFrontend_Options::$enable_video_lazyloading_for_safari == false ) {
                    $nolazyload = true;
                }
            } else {
                $nolazyload = true;
            }
        }

        if( class_exists('LayFrontend_Options') && property_exists('LayFrontend_Options', 'videos_lazyload') ) {
            if(LayFrontend_Options::$videos_lazyload == false) {
                $nolazyload = true;
            }
        }

		// and instagram browser :D
		$browser = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : false;
		$is_instagram = strpos($browser, 'Instagram') !== false;
		if( $is_instagram ){
			$nolazyload = true;
		}

        $videoConfig = [
            'model' => $slide,
            'captionMarkup' => '',
            'showPlaceholderImage' => $showPlaceHolderImage,
            'usePosterAttribute' => true,
            'usePaddingPlaceholder' => false,
            'playpauseonclick' => false,
            'useWrap' => false,
            'noLazyLoad' => $nolazyload
        ];

        $markup = '';

        if( method_exists( 'LayElFunctions', 'getHTML5VideoMarkup') ) {
            $aspectRatio = $slide['ar'];
            if (array_key_exists('useExternalVideo', $slide) && $slide['useExternalVideo'] === true && array_key_exists('externalVideoH', $slide) && array_key_exists('externalVideoW', $slide)) {
                $aspectRatio = $slide['externalVideoH'] / $slide['externalVideoW'];
            }
            $markup = '<div class="video-slide" data-ar="'.$aspectRatio.'">'.LayElFunctions::getHTML5VideoMarkup($videoConfig).'</div>';
        }

        return $markup;
    }

    public static function addYouTubeVimeoVideo( $slide ){
        return '<div class="youtube-vimeo-video-slide" data-w="'.$slide['w'].'" data-h="'.$slide['h'].'" data-ar="'.$slide['ar'].'">'.$slide['iframe'].'</div>';
    }

    public static function addText( $slide ){
        return '<div class="slide-text lay-textformat-parent">'.$slide['cont'].'</div>';
    }
    
    // adds image or project thumbnail
    public static function addImage( $slide, $fillSlides ){
        $markup = '';
        $srcset = '';
        $sizesToBeFilled = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];
        $alt = '';
        $sizes = $slide['sizes'];
        $small = '';
        if( array_key_exists('_265', $sizes ) ){
            $small = $sizes['_265'];
        }else{
            $small = $sizes['full'];
        }
        
        if( method_exists( 'LTUtility', 'getFullURL' ) ) {
            $small = LTUtility::getFullURL($small);
        }
        $fullSrc = $sizes['full'];
        $lastFour = substr($fullSrc, -4);
        if( method_exists( 'LTUtility', 'getFullURL' ) ) {
            $fullSrc = LTUtility::getFullURL($sizes['full']);
        }
        $showOriginal = false;
        if( $lastFour == '.gif' ) {
            $showOriginal = true;
        }
        if(class_exists('LayFrontend_Options')) {
            if( LayFrontend_Options::$misc_options_showoriginalimages && !LTUtility::$isPhone || property_exists('LayFrontend_Options', 'misc_options_showoriginalimages_for_phone') && LayFrontend_Options::$misc_options_showoriginalimages_for_phone && LTUtility::$isPhone ){
                $showOriginal = true;
            }
        }


        // note to myself: i disabled this for now, because it seems like different srcset is set here compared to the img element
        // if( array_key_exists('attid', $slide) ) {
        //     $alt = get_post_meta( $slide['attid'], '_wp_attachment_image_alt', true );
        //     $srcset = wp_get_attachment_image_srcset( $slide['attid'] );
        // }

        // so instead, just this method of creating a srcset is used:

        // there is another freak bug where wp_get_attachment_image_srcset would not use my custom image sizes, but only some 150 and 768 square sizes http://leitform.ch/moschti, so I check if srcset contains _265, and if not create the custom srcset ( strpos($srcset, '265w') === false )
        // there was some freak bug with some website (http://a184.uk/), where wp_get_attachment_image_srcset would always return '' and i could not figure out why, so i do this instead of just else{
        if( $srcset == '' || $srcset == false || !array_key_exists('attid', $slide) || strpos($srcset, '265w') === false ) {
            // $srcset might be set already, because im doing strpos($srcset, '265w') === false, so reset it
            $srcset = '';
            if( !array_key_exists('attid', $slide) && array_key_exists('alt', $slide) ) {
                $alt = $slide['alt'];
            }
            foreach($sizes as $key => $value) {
                $key = (string)$key;

                if( array_search($key, $sizesToBeFilled) !== false ) {
                    $src = $value;
                    if( method_exists( 'LTUtility', 'getFullURL' ) ) {
                        $src = LTUtility::getFullURL($src);
                    }
        
                    $srcset = $src.' '.substr($key, 1).'w, ' . $srcset;
                    $ix = array_search($key, $sizesToBeFilled);

                    if($ix !== false){
                        array_splice( $sizesToBeFilled, $ix, 1 );
                    }
                }
            }
            // fill rest of sizes with full image
            for($i=0; $i<count($sizesToBeFilled); $i++){
                $srcset .= $fullSrc.' '.substr($sizesToBeFilled[$i], 1).'w, ';
            }
            $srcset = substr($srcset, 0, -2);
        }

        // if( class_exists('MiscOptions') && property_exists('MiscOptions', 'image_alt_tag') ) {
        //     $alt = $alt.' '.MiscOptions::$image_alt_tag;
        // }

        if( class_exists('LTUtility') && method_exists('LTUtility', 'getAltTag') ) {
            if( array_key_exists('attid', $slide) ) {
                $alt = LTUtility::getAltTag($slide['attid']);
            }
        }

        $hasLink = ( $slide['type'] == "project" || $slide['type'] == "postThumbnail" || $slide['type'] == "productThumbnail" ) ? true : false;
        if( $hasLink ){
            $slideLink = '';
            if( method_exists( 'LTUtility', 'filterURL' ) ) {
                $slideLink = LTUtility::filterURL($slide['link']);
            }
            if( array_key_exists('postid', $slide) ) {
                $slideLink = get_permalink( $slide['postid'] );
            }
			$slideLink = is_string($slideLink) ? trim($slideLink) : $slideLink;
			// Guard against invalid "null" URLs like "https://null" created by URL normalization.
			$isNullLikeUrl =
				$slideLink === null
				|| $slideLink === ''
				|| $slideLink === false
				|| (is_string($slideLink) && $slideLink === 'null')
				|| (is_string($slideLink) && (preg_match('~^https?://null/?$~i', $slideLink) === 1));

			if ($isNullLikeUrl) {
				$hasLink = false;
			} else {
				// Product permalinks are full WC page loads — flag them so
				// Lay's AJAX nav delegate (which handles data-type="project")
				// doesn't try to intercept them.
				$linkDataType = ( $slide['type'] == 'productThumbnail' ) ? 'product' : 'project';
				$markup .= '<a href="'.$slideLink.'" data-title="'.__($slide['title']).'" data-id="'.$slide['postid'].'" data-type="'.$linkDataType.'">';
			}
        }
        // data-aspectratio needed for https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/parent-fit
        $data_width_data_height_data_ar = '';
        if( array_key_exists('w', $slide) && array_key_exists('h', $slide) && $slide['w'] != 0 && $slide['h'] != 0 ){
            $data_width_data_height_data_ar = 'data-w="'.$slide['w'].'" data-h="'.$slide['h'].'" data-aspectratio="'.( $slide['w'] / $slide['h'] ).'"';
        }else if( array_key_exists('attid', $slide) ){
            // when carousel element is a project thumbnail "w" does not exist, so get w and h differently
            $full = wp_get_attachment_image_src( $slide['attid'], 'full' );
            if( $full != false && $full[1] != 0 && $full[2] != 0 ) {
                $data_width_data_height_data_ar = 'data-w="'.$full[1].'" data-h="'.$full[2].'" data-aspectratio="'.( $full[1] / $full[2] ).'"';
            }
        }
        
        $object_fit_data = $fillSlides ? 'data-parent-fit="cover"' : '';
        $loading_class = 'carousel-instant-img';
        if( LayCarouselPluginOptions::$lazyload == 'on' ) {
            $loading_class = 'carousel-lazy-img';
        }
        // need to do data-lay-src and data-lay-srcset because otherwise lazysizes will load this image if it is cached, even if i dont use a "lazyload" html class
        if( $showOriginal ) {
            $markup .= '<img class="carousel-img '.$loading_class.' carousel-original-img" '.$object_fit_data.' src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-lay-src="'.$fullSrc.'" data-ar="'.$slide['ar'].'" '.$data_width_data_height_data_ar.' alt="'.$alt.'"/>';
        }
        else{
            $markup .= '<img class="carousel-img '.$loading_class.' carousel-responsive-img" '.$object_fit_data.' src="data:image/png;base64,R0lGODlhFAAUAIAAAP///wAAACH5BAEAAAAALAAAAAAUABQAAAIRhI+py+0Po5y02ouz3rz7rxUAOw==" data-lay-src="'.$small.'" data-lay-srcset="'.$srcset.'" data-ar="'.$slide['ar'].'" '.$data_width_data_height_data_ar.' alt="'.$alt.'"/>';
        }
        if( $hasLink ){
            $markup .= '</a>';
        }

        return $markup;
    }

    /**
     * Convert hex color to rgba string (e.g. #000000 -> rgba(0,0,0,0.5)).
     *
     * @param string $hex Hex color e.g. #000000 or 000000
     * @param float  $alpha Alpha 0–1
     * @return string rgba(...)
     */
    private static function hex_to_rgba( $hex, $alpha = 0.5 ) {
        $hex = ltrim( (string) $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
            return 'rgba(0,0,0,' . (float) $alpha . ')';
        }
        $r = (int) hexdec( substr( $hex, 0, 2 ) );
        $g = (int) hexdec( substr( $hex, 2, 2 ) );
        $b = (int) hexdec( substr( $hex, 4, 2 ) );
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . (float) $alpha . ')';
    }

    /**
     * Gradient at bottom of slide (under text on slide, above image/video).
     * 30% height, slightly transparent color at bottom fading to transparent at top.
     *
     * @param array      $options     Element carousel_options (may contain darkGradientAtBottomColor)
     * @param array|null $carousel_el Full carousel element (for borderRadius / corner-shape on gradient)
     * @return string HTML or empty string
     */
    public static function getDarkGradientAtBottomMarkup( $options, $carousel_el = null ) {
        if ( ! $options || ! array_key_exists( 'darkGradientAtBottom', $options ) || ! $options['darkGradientAtBottom'] ) {
            return '';
        }
        $color = isset( $options['darkGradientAtBottomColor'] ) && $options['darkGradientAtBottomColor'] !== '' ? $options['darkGradientAtBottomColor'] : '#000000';
        $rgba  = self::hex_to_rgba( $color, 0.5 );
        $corners = is_array( $carousel_el ) && class_exists( 'LayElFunctions' ) ? LayElFunctions::getGradientOverlayCornersCss( $carousel_el, 'bottom' ) : '';
        $style = 'position:absolute;bottom:0;left:0;right:0;height:30%;background:linear-gradient(to top, ' . $rgba . ', transparent);pointer-events:none;z-index:1;' . $corners;
        return '<div class="lay-carousel-slide-dark-gradient" style="' . esc_attr( $style ) . '"></div>';
    }

    /**
     * Gradient at top of slide (under text on slide, above image/video).
     * 30% height, slightly transparent color at top fading to transparent at bottom.
     *
     * @param array      $options     Element carousel_options (may contain darkGradientAtTopColor)
     * @param array|null $carousel_el Full carousel element (for borderRadius / corner-shape on gradient)
     * @return string HTML or empty string
     */
    public static function getDarkGradientAtTopMarkup( $options, $carousel_el = null ) {
        if ( ! $options || ! array_key_exists( 'darkGradientAtTop', $options ) || ! $options['darkGradientAtTop'] ) {
            return '';
        }
        $color = isset( $options['darkGradientAtTopColor'] ) && $options['darkGradientAtTopColor'] !== '' ? $options['darkGradientAtTopColor'] : '#000000';
        $rgba  = self::hex_to_rgba( $color, 0.5 );
        $corners = is_array( $carousel_el ) && class_exists( 'LayElFunctions' ) ? LayElFunctions::getGradientOverlayCornersCss( $carousel_el, 'top' ) : '';
        $style = 'position:absolute;top:0;left:0;right:0;height:30%;background:linear-gradient(to bottom, ' . $rgba . ', transparent);pointer-events:none;z-index:1;' . $corners;
        return '<div class="lay-carousel-slide-dark-gradient-top" style="' . esc_attr( $style ) . '"></div>';
    }

    /**
     * Text on Slide overlay for carousel img/html5video slides.
     * Outputs the same overlay markup as img/html5video elements, scoped to the carousel slide.
     * When $options['textOnSlideSpacing'] is set, it is merged over the slide's textOnElement so spacing is carousel-level.
     *
     * @param array $slide Slide data with optional textOnElement
     * @param string $carousel_relid Carousel element relid
     * @param int $slide_index Slide index
     * @param array|null $options Carousel options (may contain textOnSlideSpacing)
     */
    public static function getTextOnSlideOverlay( $slide, $carousel_relid, $slide_index, $options = null ) {
        if ( !method_exists( 'LayElFunctions', 'getTextOnElementOverlayMarkup' ) ) {
            return '';
        }
        $slide_for_markup = $slide;
        if ( $options && is_array( $options ) && array_key_exists( 'textOnSlideSpacing', $options ) && is_array( $options['textOnSlideSpacing'] ) ) {
            $spacing = $options['textOnSlideSpacing'];
            $slide_for_markup = array_merge( $slide, array(
                'textOnElement' => array_merge(
                    array_key_exists( 'textOnElement', $slide ) && is_array( $slide['textOnElement'] ) ? $slide['textOnElement'] : array(),
                    $spacing
                ),
            ) );
        }
        $override_selector = '.lay-carousel[data-relid="' . esc_attr( $carousel_relid ) . '"] .lay-carousel-slide[data-slide-ix="' . (int) $slide_index . '"] .lay-text-on-element-overlay';
        return LayElFunctions::getTextOnElementOverlayMarkup( $slide_for_markup, array( 'override_selector' => $override_selector, 'relid' => $carousel_relid ) );
    }

    public static function addProjectTagsToCaption( $slide, $caption ){
        if(array_key_exists('type', $slide) && $slide['type'] == 'postThumbnail' && LayCarouselPluginOptions::$project_tags_caption){
            $postid = array_key_exists('postid', $slide) ? $slide['postid'] : 0;
			if( $postid == 0 ){
				return '';
			}

            // add tags
            $tags = get_the_tags($postid);
            $tagArray = array();
            if ($tags) {
                usort($tags, function($a, $b) {
                    return $a->term_order - $b->term_order;
                });
                foreach($tags as $tag) {
                    $tagArray []= $tag->name;
                }

            }
            $tagText = '';
            for($k=0; $k<count($tagArray); $k++){
                $tagText .= '<span>'.$tagArray[$k].'</span>';
            }
            $tagMarkup = '';
            if( $tagText != '' ) {
                $ptags_textformat = '';
                if( class_exists('LayFrontend_Options') && property_exists('LayFrontend_Options', 'ptags_textformat') ) {
                    $ptags_textformat = LayFrontend_Options::$ptags_textformat;
                }
                $tagMarkup = '<span class="thumbnail-tags '.$ptags_textformat.'">'.$tagText.'</span>';
            }

            // Apply filter to allow custom markup to be added
            $tagMarkup = apply_filters('lay_thumbnail_tag_markup', $tagMarkup, $postid);

            return $tagMarkup;
        }
        return '';
    }

    private static function apply_qtranslate_to_markup( $markup ) {
        if ( !is_string( $markup ) || $markup === '' ) {
            return $markup;
        }
        if ( function_exists('qtranxf_use') && function_exists('qtranxf_getLanguage') ) {
            return qtranxf_use( qtranxf_getLanguage(), $markup, false, false );
        }
        return $markup;
    }

}
new LayCarouselPluginFrontend();