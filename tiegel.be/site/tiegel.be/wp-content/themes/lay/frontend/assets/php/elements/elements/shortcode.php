<?php
// todo: test if shortcode works
class LayShortcode{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        $rendered = do_shortcode($this->el['cont']);

        // Smart Loading off (default): write the shortcode output straight
        // into the markup. Same behaviour as the original Lay Shortcode element.
        if( !LayFrontend_Options::$enable_html_shortcode_smart_loading ) {
            return '<div class="lay-shortcode lay-textformat-parent">'.$rendered.'</div>';
        }

        // Smart Loading on: store the rendered HTML in data-embed and let
        // load_cpl_or_desktop_content_controller's [data-embed] loop hydrate
        // only the variant that matches the current screen size. Prevents
        // duplicate script IDs / double-mount issues when the same shortcode
        // is pasted into both desktop and custom phone layouts.
        // data-embed-fp lets the JS injector pair up desktop and CPL twins
        // and move the live DOM between them on resize, so embeds whose
        // scripts only init once keep working across the phone breakpoint.
        $fp = md5($rendered);
        return '<div class="lay-shortcode lay-textformat-parent" data-embed="'.htmlspecialchars($rendered, ENT_QUOTES, 'UTF-8').'" data-embed-fp="'.$fp.'"></div>';
    }

}
