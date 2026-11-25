<?php

class LayHTML{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        $cont = $this->el['cont'];

        // Smart Loading off (default): write the user's HTML straight into
        // the markup. Same behaviour as the original Lay HTML element.
        if( !LayFrontend_Options::$enable_html_shortcode_smart_loading ) {
            return $cont;
        }

        // Smart Loading on: store the HTML in data-embed and let
        // load_cpl_or_desktop_content_controller's [data-embed] loop hydrate
        // only the variant that matches the current screen size. Prevents
        // duplicate script IDs / double-mount issues when the same HTML is
        // pasted into both desktop and custom phone layouts.
        // data-embed-fp lets the JS injector pair up desktop and CPL twins
        // and move the live DOM between them on resize, so embeds whose
        // scripts only init once (ThriveCart, Calendly, …) keep working
        // when the user crosses the phone breakpoint.
        // .lay-html is `display: contents` so the wrapper is invisible to
        // the layout — the user's HTML behaves as a direct child of .col.
        $fp = md5($cont);
        return '<div class="lay-html" data-embed="'.htmlspecialchars($cont, ENT_QUOTES, 'UTF-8').'" data-embed-fp="'.$fp.'"></div>';
    }

}
