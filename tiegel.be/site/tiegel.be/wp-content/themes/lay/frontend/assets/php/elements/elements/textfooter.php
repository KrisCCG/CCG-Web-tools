<?php

class LayTextFooter{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        
        $el = $this->el['cont'];
        $content = $el['content'];
        $align = $el['alignment'];

        $wrap = array_key_exists('flexWrap', $el) ? ($el['flexWrap'] ? 'flex-wrap: wrap;' : '') : 'flex-wrap: wrap;';
        $whitespace = array_key_exists('linebreaks', $el) ? ($el['linebreaks'] ? 'normal' : 'nowrap') : 'nowrap';

        $markup = '<div class="lay-textfooter-inner lay-textformat-parent lay-textfooter-align-'.$align.'" style="'.$wrap.'; white-space: '.$whitespace.';">';
        for ($i=0; $i < count($content); $i++) { 
            $item = $content[$i];
            $marginright = $i < count($content) - 1 ? 'margin-right:'.$el['spacebetween'].'px;' : '';
            $marginbottom = 'margin-bottom:'.$el['spacebelow'].'px;';
            $markup .= '<div style="'.$marginright.$marginbottom.'" class="lay-textfooter-item _'.$el['textformat'].'_no_spaces">'.$item['cont'].'</div>';
        }
        $markup .= '</div>';

        return $markup;
    }

}