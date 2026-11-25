<?php


class GridderTable{

	public function __construct(){
		add_action('wp_ajax_save_table_config', array($this, 'save_table_config'));
		add_action('wp_ajax_save_table_column_widths', array($this, 'save_table_column_widths'));
	}

	public function save_table_config(){
		$config = $_POST['config'];
        update_option('lay_table_config', $config);
        wp_die();
	}

	public function save_table_column_widths(){
		$columnWidths = $_POST['columnWidths'];
        update_option('lay_table_column_widths', $columnWidths);
        wp_die();
	}
}
new GridderTable();