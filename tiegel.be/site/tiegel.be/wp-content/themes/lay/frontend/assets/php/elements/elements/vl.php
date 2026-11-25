<?php

class LayVl{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        return '<div class="lay-vl"></div>';
    }

}