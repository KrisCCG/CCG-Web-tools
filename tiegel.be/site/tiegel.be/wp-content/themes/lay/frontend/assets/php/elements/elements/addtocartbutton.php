<?php

class LayAddToCartButton{

    public $el;
    public $config;

    public function __construct($el){
        $this->el = $el;
    }

    public function getMarkup($options = array()){
        if( class_exists('WooCommerce') ) {
            $this->config = $this->el['config'];
            return Lay_WooCommerce::build_lay_addtocartbutton_markup( $this->config );
        }
        return '';
    }

}