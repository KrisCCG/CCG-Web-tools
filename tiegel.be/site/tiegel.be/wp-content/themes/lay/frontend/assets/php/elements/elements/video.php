<?php
class LayVideo{

    public $el;
    public $height;
    public $video_count;

    public function __construct($el, $height = false, $video_count = 0){
        $this->el = $el;
        $this->height = $height;
        $this->video_count = $video_count;
    }

    public function getMarkup($options = array()){
        $caption = LayElFunctions::getCaptionMarkup($this->el);
        $iframe = $this->el['iframe'];

        $pb =  $this->el['ar'] * 100;
        $pb_str = Lay_LayoutFunctions::replaceCommaWithDot($pb);

        $fixedHeightEnabled = false;
        $fixedHeightStyle = '';
        if(array_key_exists('fixedHeight', $this->el) && array_key_exists('enableFixedHeight', $this->el['fixedHeight']) && $this->el['fixedHeight']['enableFixedHeight']) {
            $fixedHeightEnabled = true;
            $fixedHeight = $this->el['fixedHeight'];
            $fixedHeightStyle = ' style="height: calc('.$fixedHeight['height'].');"';
            $iframe = $this->addFixedHeightIframeStyles($iframe);
        }

        // Detect video provider
        $isVimeo = false;
        $isYoutube = false;
        $src = '';
        if(preg_match('/src="([^"]+)"/', $iframe, $matches)) {
            $src = str_replace('&amp;', '&', $matches[1]);
            if(strpos($src, 'player.vimeo.com') !== false) {
                $isVimeo = true;
            } else if(strpos($src, 'youtube.com/embed') !== false || strpos($src, 'youtu.be') !== false) {
                $isYoutube = true;
            }
        }

        $customLayControls = false;
        if($isVimeo && isset($this->el['customLayControls']) && $this->el['customLayControls'] == true) {
            $customLayControls = true;
        }

        $showShareButton = false;
        if($customLayControls && isset($this->el['showShareButton']) && $this->el['showShareButton'] == true) {
            $showShareButton = true;
        }

        $customLayControlsClass = '';
        if($customLayControls && $isVimeo) {
            $customLayControlsClass = 'lay-vimeo-custom-controls';
            // add controls 1 
            // $iframe = str_replace('controls=0', 'controls=1', $iframe);
        }

        $showShareButtonClass = '';
        if($showShareButton && $isVimeo) {
            $showShareButtonClass = 'lay-vimeo-show-share-button';
        }

        // Check iframe URL for autoplay parameter
        $autoplay = false;
        if($isVimeo && isset($this->el['iframe'])) {
            $iframeSrc = $this->el['iframe'];
            if(preg_match('/src="([^"]+)"/', $iframeSrc, $matches)) {
                $src = str_replace('&amp;', '&', $matches[1]);
                $query = parse_url($src, PHP_URL_QUERY);
                if($query) {
                    parse_str($query, $params);
                    $autoplay = isset($params['autoplay']) && $params['autoplay'] === '1';
                }
            }
        }

        // make src to data-src
        // Somehow, when using project overlays and lazy loading iframes, we get a history issue where we need to click back twice to go back to the main page underneath a project overlay.
        // so we do:
        if(LayFrontend_Options::$project_overlay_active == false) {
            $iframe = str_replace( 'src="', 'data-src="', $iframe );
        }

        // For Vimeo with autoplay, always add autopause=0
        if($isVimeo && $autoplay) {
            // Check if autopause is already in the URL
            if(preg_match('/src="([^"]+)"/', $iframe, $matches)) {
                $iframeSrc = $matches[1];
                // Check if autopause parameter already exists
                if(strpos($iframeSrc, 'autopause=') === false) {
                    // Add autopause=0 to the URL
                    $separator = (strpos($iframeSrc, '?') !== false) ? '&' : '?';
                    $newSrc = $iframeSrc . $separator . 'autopause=0';
                    $iframe = str_replace('src="' . $iframeSrc . '"', 'src="' . $newSrc . '"', $iframe);
                }
            }
        }


        $autoplayClass = '';
        if($autoplay) {
            $autoplayClass = 'lay-vimeo-autoplay';
        } else {
            $autoplayClass = 'lay-vimeo-paused';
        }

        $videoProviderClass = '';
        if($isVimeo) {
            $videoProviderClass = 'lay-vimeo-video';
        } else if($isYoutube) {
            $videoProviderClass = 'lay-youtube-video';
        }

        $markup = '';
        if($this->height == false && $fixedHeightEnabled == false) {
            $markup = 
            '<div class="video '.$customLayControlsClass.' '.$showShareButtonClass.' '.$autoplayClass.' '.$videoProviderClass.'">
                <div class="ph" style="padding-bottom:'.$pb_str.'%;">
                    '.$iframe.'
                </div>
                '.$caption.'
            </div>';
        } else {
            $markup = 
            '<div class="video '.$customLayControlsClass.' '.$showShareButtonClass.' '.$autoplayClass.' '.$videoProviderClass.'"'.$fixedHeightStyle.'>
                '.$iframe.'
                '.$caption.'
            </div>';
        }

        // Allow filtering the video markup
        $filter_args = array(
            'isVimeo' => $isVimeo,
            'isYoutube' => $isYoutube,
            'pb' => $pb,
            'pb_str' => $pb_str,
            'caption' => $caption,
            'src' => $src,
            'iframe' => $iframe,
            'el' => $this->el,
            'height' => $this->height,
            'video_count' => $this->video_count,
            'customLayControls' => $customLayControls,
            'customLayControlsClass' => $customLayControlsClass,
            'showShareButton' => $showShareButton,
            'showShareButtonClass' => $showShareButtonClass,
            'autoplayClass' => $autoplayClass,
            'videoProviderClass' => $videoProviderClass
        );

        $markup = apply_filters('laytheme_video_element_markup', $markup, $filter_args);

        return $markup;
    }

    private function addFixedHeightIframeStyles($iframe){
        if(strpos($iframe, '<iframe') === false) {
            return $iframe;
        }
        if(preg_match('/style="[^"]*"/i', $iframe)) {
            return preg_replace('/style="([^"]*)"/i', 'style="$1; width:100%; height:100%;"', $iframe, 1);
        }
        return preg_replace('/<iframe\b/i', '<iframe style="width:100%; height:100%;"', $iframe, 1);
    }

}