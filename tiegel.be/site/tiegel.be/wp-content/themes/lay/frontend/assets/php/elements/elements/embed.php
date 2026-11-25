<?php

class LayEmbed{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        $markup = "";
        $w = (int)$this->el['w'];
        $h = (int)$this->el['h'];
        $caption = LayElFunctions::getCaptionMarkup($this->el);
        $cont = $this->el['cont'];
        $escaped = htmlspecialchars($cont, ENT_QUOTES, 'UTF-8');
        // Fingerprint so the JS injector can pair up desktop and CPL twins
        // and move the live DOM between them on resize, instead of
        // re-hydrating (which would re-trigger embed scripts and may fail
        // for embeds whose init is one-shot).
        $fp = md5($cont);
        if($w != 0 && $h != 0){
            $pb = $h/$w*100;
            $markup = '<div class="ph" style="padding-bottom:'.Lay_LayoutFunctions::replaceCommaWithDot($pb).'%;" data-embed="'.$escaped.'" data-embed-fp="'.$fp.'"></div>'.$caption;
        }else{
            $markup =  '<div data-embed="'.$escaped.'" data-embed-fp="'.$fp.'"></div>'.$caption;
        }
        return $markup;
    }

}
