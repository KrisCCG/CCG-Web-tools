<?php


class GridderAccordion{

	public function __construct(){
		add_action('wp_ajax_save_accordion_config', array($this, 'save_accordion_config'));
	}

	public function save_accordion_config(){
		$config = $_POST['config'];
        update_option('lay_accordion_config', $config);
        wp_die();
	}
}
new GridderAccordion();
