<?php

class LayHr{

    public $el;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        return '<div class="lay-hr"></div>';
    }

}