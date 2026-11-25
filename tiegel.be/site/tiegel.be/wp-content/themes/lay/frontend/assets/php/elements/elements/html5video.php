<?php

// todo: css, html5video with placeholder image, image has transition transform and moves around on init of page

class LayHTML5Video{

    public $el;
    // for elementgrid, sameheight
    public $height;

    public function __construct($el, $height = false){
        $this->el = $el;
        $this->height = $height;
    }

    public function getMarkup($options = array()){
        $caption = LayElFunctions::getCaptionMarkup($this->el);

        $show_placeholder_image = $this->el['cont'] == '' ? false : true;
        // no need to show placeholder image when autoplay is true
        if( $this->el['autoplay'] == true ) {
            $show_placeholder_image = false;
        }
        $nolazyload = false;
        if( Setup::$is_firefox == true && LayFrontend_Options::$enable_video_lazyloading_for_firefox == false ) {
            $nolazyload = true;
        }
        if( Setup::$is_safari == true && LayFrontend_Options::$enable_video_lazyloading_for_safari == false ) {
            $nolazyload = true;
        }
        if( Setup::$is_instagram == true) {
            $nolazyload = true;
        }
        if(LayFrontend_Options::$videos_lazyload == false) {
            $nolazyload = true;
        }

        $customLayControls = false;
        if(isset($this->el['customLayControls']) && $this->el['customLayControls'] == true) {
            $customLayControls = true;
        }

        $grid_container_selector = isset($options['grid_container_selector']) ? $options['grid_container_selector'] : '';
        $videoConfig = array(
            'model' => $this->el,
            'captionMarkup' => $caption,
            'showPlaceholderImage' => $show_placeholder_image,
            'usePaddingPlaceholder' => true,
            'classPrefix' => '',
            'noLazyLoad' => $nolazyload,
            'customLayControls' => $customLayControls,
            'grid_container_selector' => $grid_container_selector
        );

        if( $this->height != false ){
            $videoConfig['usePaddingPlaceholder'] = false;
        }
        $markup = LayElFunctions::getHTML5VideoMarkup($videoConfig);
        return $markup;
    }

}