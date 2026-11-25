<?php

class LayCMB2Integration {

	public static function init(){
		add_action( 'admin_enqueue_scripts', 'LayCMB2Integration::lay_localize_gridder_js_with_cmb2', 11 );
		// add_action( 'lay_after_gridder_buttons', 'LayCMB2Integration::lay_echo_add_buttons' );
		add_action( 'lay_echo_cmb2_modals', 'LayCMB2Integration::lay_echo_metaboxes' );
		add_filter( 'cmb2_script_dependencies', 'LayCMB2Integration::add_cmb2_script_dependencies', 10, 1 );
	}

	public static function lay_localize_gridder_js_with_cmb2() {
        
		$metaboxes = apply_filters( 'lay_cmb2_modals', array());
		$array = array();
        
		foreach ($metaboxes as $metabox) {
			$metabox_instance = cmb2_get_metabox($metabox['id']);

            $fullwidth = false;
            $dropdown_id = false;
            $frameoverflow = false;
            $resizable = true;

            $previewimage = false;

            if( array_key_exists('dropdown_id', $metabox) ){
                $dropdown_id = $metabox['dropdown_id'];
            }
            if( array_key_exists('fullwidth', $metabox) ){
                $fullwidth = $metabox['fullwidth'];
            }
            if( array_key_exists('frameoverflow', $metabox) ){
                $frameoverflow = $metabox['frameoverflow'];
            }
            if( array_key_exists('resizable', $metabox) ){
                $resizable = $metabox['resizable'];
            }
            if( array_key_exists('previewimage', $metabox) ){
                $previewimage = $metabox['previewimage'];
            }

			$array []= array(
                'dropdown_id' => $dropdown_id,
                'title' => $metabox_instance->meta_box['title'],
				'cmb_id' => $metabox_instance->cmb_id,
				'meta_box' => $metabox_instance->meta_box,
                'fullwidth' => $fullwidth,
                'resizable' => $resizable,
                'frameoverflow' => $frameoverflow,
                'previewimage' => $previewimage
			);
		}

        $dropdowns = apply_filters('gridder_dropdown_button', array());

		wp_localize_script( 'gridder-app', 'cmb2LgPassedData', $array ); 
        wp_localize_script( 'gridder-app', 'cmb2LgDropdowns', $dropdowns ); 
	}


	public static function lay_echo_metaboxes() {
		// https://developer.wordpress.org/reference/functions/apply_filters/
		$modals = apply_filters( 'lay_cmb2_modals', array());

		$screen = get_current_screen();
		$processed_ids = array(); // Track processed modal IDs to avoid duplicates

        foreach ($modals as $modal) {
            // Skip if we've already processed this modal ID
            if (in_array($modal['id'], $processed_ids)) {
                continue;
            }
            
            $metabox_instance = cmb2_get_metabox($modal['id']);

            // add modal
            echo
            '<div id="'.$modal['id'].'" class="laygridder-cmb2-metabox lay-input-modal">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">'.$metabox_instance->meta_box['title'].'</h3>
                            <button type="button" class="close close-modal-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>
                        </div>
                    <div class="panel-body">
                        '.cmb2_metabox_form($modal['id'], '', array('echo'=>false)).'
                    </div>
                    <div class="panel-footer clearfix">
                    <label id="cmb2-insert-at-end">
                        <input type="checkbox" id="insert-at-end" /> Insert at end of layout
                    </label>
                    <button type="button" class="btn btn-default btn-lg btn-lg-cmb2-ok">Ok</button>
                    </div>
                </div>
                <div class="background"></div>
            </div>';
            
            // Mark this ID as processed
            $processed_ids[] = $modal['id'];
        }
	}

	// add datepicker and timepicker as dependencies
	public static function add_cmb2_script_dependencies($dependencies) {
		$dependencies['jquery-ui-core'] = 'jquery-ui-core';
		$dependencies['jquery-ui-datepicker'] = 'jquery-ui-datepicker';
		$dependencies['jquery-ui-datetimepicker'] = 'jquery-ui-datetimepicker';
		return $dependencies;
	}
}

LayCMB2Integration::init();