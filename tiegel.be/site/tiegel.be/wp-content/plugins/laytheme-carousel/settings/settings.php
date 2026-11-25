<?php

class LayThemeCarouselSettings{

	public function __construct(){
		add_action( 'admin_init', array($this, 'carousel_settings_api_init') );
		add_action( 'admin_menu', array($this, 'carousel_setup_menu'), 20 );
		add_action( 'admin_enqueue_scripts', array($this, 'carousel_settings_enqueue_scripts') );
	}

	public function carousel_settings_enqueue_scripts($hook){
        if( strpos($hook, 'manage-laycarousel') !== false ){
			wp_enqueue_script( 'laycarousel_settings_showhide', plugin_dir_url( __FILE__ ).'/assets/js/settings_showhide.js', array(), LayThemeCarousel::$ver );
			wp_enqueue_media();
			wp_enqueue_script( 'laycarousel_settings_image_upload', plugin_dir_url( __FILE__ ).'/assets/js/image_upload.js', array(), LayThemeCarousel::$ver );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_add_inline_script( 'wp-color-picker', "jQuery(function(){ jQuery('.laycarousel-navigation-circles-color-picker, .laycarousel-button-circle-color-picker, .laycarousel-button-circle-mouseover-color-picker').wpColorPicker(); });" );
			wp_add_inline_script( 'wp-color-picker', "jQuery(function(){ jQuery('form[action=\"options.php\"]').on('submit', function(){ var h = window.location.hash; if(!h) return; var \$ref = jQuery(this).find('input[name=\"_wp_http_referer\"]'); if(!\$ref.length) return; var url = \$ref.val().split('#')[0]; \$ref.val(url + h); }); });" );
			wp_add_inline_style( 'wp-color-picker', "html, body[class*='manage-laycarousel'] { scroll-behavior: smooth; }" );
		}
	}

	public function carousel_setup_menu(){
		add_submenu_page( 'manage-layoptions', 'Carousel Addon', 'Carousel Addon', 'manage_options', 'manage-laycarousel', array($this, 'carousel_options_markup') );
	}

	public function carousel_options_markup(){
		require_once( LayThemeCarousel::$dir.'/settings/carousel_options_markup.php' );
	}

	public function carousel_settings_api_init(){
		add_settings_section(
			'laycarouselsoptions_general_section',
			'General',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-general"></div>')
		);
		add_settings_section(
			'laycarouselsoptions_mousecursor_section',
			'Mouse Cursor',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-mousecursor"></div>')
		);
		add_settings_section(
			'laycarouselsoptions_circles_section',
			'Circles as Navigation',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-circles"></div>')
		);
		add_settings_section(
			'laycarouselsoptions_captions_section',
			'Captions for Slides',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-captions"></div>')
		);
		add_settings_section(
			'laycarouselsoptions_numbers_section',
			'Numbers',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-numbers"></div>')
		);
		add_settings_section(
			'laycarouselsoptions_buttons_section',
			'Previous/Next Buttons',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-buttons"></div>')
		);

		add_settings_section(
			'laycarouselsoptions_text_spacings_section',
			'Text Spacings',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-text-spacings"></div>')
		);

		add_settings_section(
			'laycarouselsoptions_other_section',
			'Other',
			'',
			'manage-laycarousel',
			array('before_section' => '<div id="lc-other"></div>')
		);
		
		add_settings_field(
			'laycarousel_transition',
			'Transition Type',
			array($this, 'laycarousel_transition_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_transition' );

        add_settings_field(
			'laycarousel_transitionSpeed',
			'Transition Speed',
			array($this, 'laycarousel_transitionSpeed_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_transitionSpeed' );

		add_settings_field(
			'laycarousel_sink_position',
			'Position of Captions, Numbers and Circles',
			array($this, 'laycarousel_sink_position_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_sink_position' );

		add_settings_field(
			'laycarousel_align_sink',
			'Align Captions and Numbers to width of slides',
			array($this, 'laycarousel_align_sink_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_align_sink' );

		// autoplay
        add_settings_field(
			'laycarousel_autoplayDelay',
			'Wait until autoplay starts',
			array($this, 'laycarousel_autoplayDelay_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_autoplayDelay' );

		add_settings_field(
			'laycarousel_autoplaySpeed',
			'Autoplay Speed',
			array($this, 'laycarousel_autoplaySpeed_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_autoplaySpeed' );

		add_settings_field(
			'laycarousel_pauseAutoplayOnHover',
			'Pause Autoplay on Mouseover',
			array($this, 'laycarousel_pauseAutoplayOnHover_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_pauseAutoplayOnHover' );

		add_settings_field(
			'laycarousel_loop',
			'Loop Slides',
			array($this, 'laycarousel_loop_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_loop' );

        add_settings_field(
			'laycarousel_loop_touchdevice',
			'Loop Slides on Touch Devices too',
			array($this, 'laycarousel_loop_touchdevice_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_loop_touchdevice' );

		add_settings_field(
			'laycarousel_lazyload',
			'Lazyload images',
			array($this, 'laycarousel_lazyload_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_lazyload' );

		// vertical alignment
		add_settings_field(
			'laycarousel_alignment',
			'Vertical Alignment of Slides',
			array($this, 'laycarousel_alignment_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_alignment' );

		add_settings_field(
			'laycarousel_text_alignment',
			'Vertical Alignment of Text Slides',
			array($this, 'laycarousel_text_alignment_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_text_alignment' );
		// 

        add_settings_field(
			'laycarousel_horizontal_alignment',
			'Horizontal Alignment of Slides',
			array($this, 'laycarousel_horizontal_alignment_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_general_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_horizontal_alignment' );

		// a textslide setting
		add_settings_field(
			'laycarousel_textspace',
			'Horizontal Space around Text in Slides (Desktop)',
			array($this, 'laycarousel_textspace_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textspace' );

		add_settings_field(
			'laycarousel_textspace_tablet',
			'Horizontal Space around Text in Slides (Tablet)',
			array($this, 'laycarousel_textspace_tablet_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textspace_tablet' );

		add_settings_field(
			'laycarousel_textspace_phone',
			'Horizontal Space around Text in Slides (Phone)',
			array($this, 'laycarousel_textspace_phone_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textspace_phone' );

		add_settings_field(
			'laycarousel_textslide_width',
			'Text Slide Width (Desktop)',
			array($this, 'laycarousel_textslide_width_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textslide_width' );

		add_settings_field(
			'laycarousel_textslide_width_tablet',
			'Text Slide Width (Tablet)',
			array($this, 'laycarousel_textslide_width_tablet_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textslide_width_tablet' );

		add_settings_field(
			'laycarousel_textslide_width_phone',
			'Text Slide Width (Phone)',
			array($this, 'laycarousel_textslide_width_phone_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_text_spacings_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_textslide_width_phone' );
		//

		// mouse cursor
		add_settings_field(
			'laycarousel_mousecursor',
			'Mouse Cursor Controls',
			array($this, 'laycarousel_mousecursor_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_mousecursor_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_mousecursor' );

		add_settings_field(
			'laycarousel_dom_cursor',
			'Use DOM Element as Cursor',
			array($this, 'laycarousel_dom_cursor_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_mousecursor_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_dom_cursor', array($this, 'laycarousel_dom_cursor_sync_to_theme_mod') );

		add_settings_field(
			'laycarousel_arrowleft',
			'Custom Mouse Cursor "Previous"',
			array($this, 'laycarousel_arrowleft_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_mousecursor_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_arrowleft' );

		add_settings_field(
			'laycarousel_arrowright',
			'Custom Mouse Cursor "Next"',
			array($this, 'laycarousel_arrowright_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_mousecursor_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_arrowright' );

		// circles as navigation
		add_settings_field(
			'laycarousel_showCircles',
			'Show Circles',
			array($this, 'laycarousel_showCircles_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_showCircles' );

		add_settings_field(
			'laycarousel_spaceBetweenForCircles',
			'Space Top',
			array($this, 'laycarousel_spaceBetweenForCircles_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBetweenForCircles' );

		add_settings_field(
			'laycarousel_spaceBottomCircles',
			'Space Bottom',
			array($this, 'laycarousel_spaceBottomCircles_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBottomCircles' );

		add_settings_field(
			'laycarousel_navigation_space_between_circles',
			'Navigation Space between Circles',
			array($this, 'laycarousel_navigation_space_between_circles_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_navigation_space_between_circles' );

		add_settings_field(
			'laycarousel_navigation_circles_size',
			'Navigation Circles Size',
			array($this, 'laycarousel_navigation_circles_size_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_navigation_circles_size' );

		add_settings_field(
			'laycarousel_navigation_circles_color',
			'Navigation Circles Color',
			array($this, 'laycarousel_navigation_circles_color_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_circles_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_navigation_circles_color' );

		// captions
		add_settings_field(
			'laycarousel_showCaptions',
			'Show Slide Captions',
			array($this, 'laycarousel_showCaptions_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_showCaptions' );

		add_settings_field(
			'laycarousel_captionsPosition',
			'Position',
			array($this, 'laycarousel_captionsPosition_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_captionsPosition' );

		add_settings_field(
			'laycarousel_captionTextformat',
			'Textformat',
			array($this, 'laycarousel_captionTextformat_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_captionTextformat' );

		add_settings_field(
			'laycarousel_spaceBetween',
			'Space Top',
			array($this, 'laycarousel_spaceBetween_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBetween' );

		add_settings_field(
			'laycarousel_spaceBottomCaptions',
			'Space Bottom',
			array($this, 'laycarousel_spaceBottomCaptions_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBottomCaptions' );

		add_settings_field(
			'laycarousel_caption_space_left',
			'Space Left',
			array($this, 'laycarousel_caption_space_left_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_caption_space_left' );

		add_settings_field(
			'laycarousel_caption_space_right',
			'Space Right',
			array($this, 'laycarousel_caption_space_right_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_captions_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_caption_space_right' );
		// 

		// numbers
		add_settings_field(
			'laycarousel_showNumbers',
			'Show Numbers',
			array($this, 'laycarousel_showNumbers_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_showNumbers' );

		add_settings_field(
			'laycarousel_numbersPosition',
			'Position',
			array($this, 'laycarousel_numbersPosition_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_numbersPosition' );

		add_settings_field(
			'laycarousel_numberTextformat',
			'Textformat',
			array($this, 'laycarousel_numberTextformat_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_numberTextformat' );

		add_settings_field(
			'laycarousel_spaceBetweenForNumbers',
			'Space Top',
			array($this, 'laycarousel_spaceBetweenForNumbers_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBetweenForNumbers' );

		add_settings_field(
			'laycarousel_spaceBottomNumbers',
			'Space Bottom',
			array($this, 'laycarousel_spaceBottomNumbers_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_spaceBottomNumbers' );

		add_settings_field(
			'laycarousel_numbers_space_left',
			'Space Left',
			array($this, 'laycarousel_numbers_space_left_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_numbers_space_left' );

		add_settings_field(
			'laycarousel_numbers_space_right',
			'Space Right',
			array($this, 'laycarousel_numbers_space_right_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_numbers_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_numbers_space_right' );
		// 

		// prev/next button
		add_settings_field(
			'laycarousel_showArrowButtons',
			'Show Buttons',
			array($this, 'laycarousel_showArrowButtons_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_showArrowButtons' );

		add_settings_field(
			'laycarousel_showArrowButtonsForTouchDevices',
			'Show Buttons on Touchdevices',
			array($this, 'laycarousel_showArrowButtonsForTouchDevices_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_showArrowButtonsForTouchDevices' );

		add_settings_field(
			'laycarousel_rightbutton',
			'Custom "Next" Button',
			array($this, 'laycarousel_rightbutton_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_rightbutton' );

		add_settings_field(
			'laycarousel_altrightbutton',
			'Custom Alternative "Next" Button',
			array($this, 'laycarousel_altrightbutton_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_altrightbutton' );

		add_settings_field(
			'laycarousel_show_button_circle',
			'Show Circle around Button',
			array($this, 'laycarousel_show_button_circle_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_show_button_circle' );

		add_settings_field(
			'laycarousel_button_circle_color',
			'Button Circle Color',
			array($this, 'laycarousel_button_circle_color_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_button_circle_color' );

		add_settings_field(
			'laycarousel_use_button_circle_mouseover_color',
			'Use Button Circle Mouseover Color',
			array($this, 'laycarousel_use_button_circle_mouseover_color_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_use_button_circle_mouseover_color' );

		add_settings_field(
			'laycarousel_button_circle_mouseover_color',
			'Button Circle Mouseover Color',
			array($this, 'laycarousel_button_circle_mouseover_color_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_button_circle_mouseover_color' );

		add_settings_field(
			'laycarousel_button_circle_padding',
			'Circle Padding',
			array($this, 'laycarousel_button_circle_padding_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_button_circle_padding' );

		add_settings_field(
			'laycarousel_button_circle_icon_horizontal_offset',
			'Horizontal Offset of icon in circle',
			array($this, 'laycarousel_button_circle_icon_horizontal_offset_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_button_circle_icon_horizontal_offset' );

		add_settings_field(
			'laycarousel_set_custom_button_width',
			'Set custom button width',
			array($this, 'laycarousel_set_custom_button_width_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_set_custom_button_width' );

		add_settings_field(
			'laycarousel_button_width',
			'Button width',
			array($this, 'laycarousel_button_width_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_button_width' );

		add_settings_field(
			'laycarousel_buttons_position',
			'Buttons Position',
			array($this, 'laycarousel_buttons_position_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_buttons_position' );

		add_settings_field(
			'laycarousel_buttons_arrangement',
			'Buttons Arrangement',
			array($this, 'laycarousel_buttons_arrangement_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_buttons_arrangement' );

		add_settings_field(
			'laycarousel_buttonspace_grouped',
			'Space between buttons',
			array($this, 'laycarousel_buttonspace_grouped_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_buttonspace_grouped' );

		add_settings_field(
			'laycarousel_arrow_buttons_outside',
			'Place Buttons outside of Carousel',
			array($this, 'laycarousel_arrow_buttons_outside_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_arrow_buttons_outside' );

		add_settings_field(
			'laycarousel_arrow_buttons_outside_for_phone',
			'Place Buttons outside of Carousel for Phone Version',
			array($this, 'laycarousel_arrow_buttons_outside_for_phone_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_arrow_buttons_outside_for_phone' );

		add_settings_field(
			'laycarousel_buttonspace',
			'Space between buttons and left/right edge of carousel',
			array($this, 'laycarousel_buttonspace_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_buttonspace' );

		add_settings_field(
			'laycarousel_buttonspace_vertical',
			'Space between buttons and top/bottom edge of carousel',
			array($this, 'laycarousel_buttonspace_vertical_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_buttons_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_buttonspace_vertical' );

		// other
		add_settings_field(
			'laycarousel_youtube',
			'Enable YouTube pause video when leaving slide (adds extra YouTube Script)',
			array($this, 'laycarousel_youtube_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_other_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_youtube' );

        		add_settings_field(
			'laycarousel_disable_sizing',
			'Do less sizing by JavaScript',
			array($this, 'laycarousel_disable_sizing_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_other_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_disable_sizing' );

		add_settings_field(
			'laycarousel_project_tags_caption',
			'For Project Thumbnail Slides, add Project Tags to Caption',
			array($this, 'laycarousel_project_tags_caption_setting'),
			'manage-laycarousel',
			'laycarouselsoptions_other_section'
		);
		register_setting( 'manage-laycarousel', 'laycarousel_project_tags_caption' );
	}

    public function laycarousel_disable_sizing_setting(){
		$val = get_option( 'laycarousel_disable_sizing', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_disable_sizing" id="laycarousel_disable_sizing" '.$checked.'> <label>Use with caution! Could produce bugs like images not showing up.</label>';
    }

    public function laycarousel_project_tags_caption_setting(){
		$val = get_option( 'laycarousel_project_tags_caption', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_project_tags_caption" id="laycarousel_project_tags_caption" '.$checked.'>';
    }

	public function laycarousel_youtube_setting(){
		$val = get_option( 'laycarousel_youtube', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_youtube" id="laycarousel_youtube" '.$checked.'>';
	}

	public function laycarousel_showArrowButtonsForTouchDevices_setting(){
		$val = get_option( 'laycarousel_showArrowButtonsForTouchDevices', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_showArrowButtonsForTouchDevices" id="laycarousel_showArrowButtonsForTouchDevices" '.$checked.'>';
	}

	public function laycarousel_showArrowButtons_setting(){
		$val = get_option( 'laycarousel_showArrowButtons', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_showArrowButtons" id="laycarousel_showArrowButtons" '.$checked.'>';
	}

	public function laycarousel_arrow_buttons_outside_setting(){
		$val = get_option( 'laycarousel_arrow_buttons_outside', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_arrow_buttons_outside" id="laycarousel_arrow_buttons_outside" '.$checked.'>'; 
	}

	public function laycarousel_arrow_buttons_outside_for_phone_setting(){
		$val = get_option( 'laycarousel_arrow_buttons_outside_for_phone', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_arrow_buttons_outside_for_phone" id="laycarousel_arrow_buttons_outside_for_phone" '.$checked.'><br>
		<p>When turning this on the buttons might get cut off and might not be visible on the phone version.</p>'; 
	}

	public function laycarousel_arrowright_setting(){
		LayThemeCarouselSettings::laycarousel_arrow_setting('laycarousel_arrowright');
	}

	public function laycarousel_arrowleft_setting(){
		LayThemeCarouselSettings::laycarousel_arrow_setting('laycarousel_arrowleft');
	}

	public function laycarousel_rightbutton_setting(){
		LayThemeCarouselSettings::laycarousel_button_setting('laycarousel_rightbutton');
	}
	
	public function laycarousel_altrightbutton_setting(){
		LayThemeCarouselSettings::laycarousel_button_setting('laycarousel_altrightbutton');
	}

	public static function laycarousel_button_setting($option_name){
		// https://gist.github.com/hlashbrooke/9267467#file-class-php-L324
		$image_thumb_id = get_option( $option_name, '' );
		$image_thumb = "";
		$noimage_image = $image_thumb;
		$hideRemoveButtonCSS = '';
		if($image_thumb_id != ''){
			$image_thumb = wp_get_attachment_image_src( $image_thumb_id, 'full' );
			$image_thumb = $image_thumb[0];
		}
		else{
			$hideRemoveButtonCSS = 'style="display:none;"';
		}
		echo 
		'<img id="'.$option_name.'_preview" style="max-width: 200px;" class="image_preview" data-noimagesrc="'.$noimage_image.'" src="'.$image_thumb.'">
		<p style="margin-bottom: 10px;">"Previous" button will be the mirrored version of this</p>
		<input id="'.$option_name.'_button" style="margin-bottom: 5px;" type="button" class="image_upload_button button" value="Set image" /><br>
		<input id="'.$option_name.'_delete" '.$hideRemoveButtonCSS.' type="button" class="image_delete_button button" value="Remove image" />
		'.LayThemeCarouselSettings::get_svg_cursor_fix_hint_markup().'
		<input id="'.$option_name.'" class="image_data_field" type="hidden" name="'.$option_name.'" value="'.$image_thumb_id.'"/>';
	}

	public static function laycarousel_arrow_setting($option_name){
		// https://gist.github.com/hlashbrooke/9267467#file-class-php-L324
		$image_thumb_id = get_option( $option_name, '' );
		$image_thumb = "";
		$noimage_image = $image_thumb;
		$hideRemoveButtonCSS = '';
		if($image_thumb_id != ''){
			$image_thumb = wp_get_attachment_image_src( $image_thumb_id, 'full' );
			$image_thumb = $image_thumb[0];
		}
		else{
			$hideRemoveButtonCSS = 'style="display:none;"';
		}
		echo 
		'<img id="'.$option_name.'_preview" style="max-width: 200px;" class="image_preview" data-noimagesrc="'.$noimage_image.'" src="'.$image_thumb.'">
		<p style="margin-bottom: 10px;">Max. size: 128 * 128 px</p>
		<input id="'.$option_name.'_button" style="margin-bottom: 5px;" type="button" class="image_upload_button button" value="Set image" /><br>
		<input id="'.$option_name.'_delete" '.$hideRemoveButtonCSS.' type="button" class="image_delete_button button" value="Remove image" />
		'.LayThemeCarouselSettings::get_svg_cursor_fix_hint_markup().'
		<input id="'.$option_name.'" class="image_data_field" type="hidden" name="'.$option_name.'" value="'.$image_thumb_id.'"/>';
	}

	public static function get_svg_cursor_fix_hint_markup(){
		return '<div style="display:block;width:fit-content;max-width:420px;margin:2px 0 8px 0;margin-top:5px;padding:6px 9px;background:#fff;border:1px solid #b8dcff;border-radius:8px;font-size:11px;line-height:1.3;color:#1f2937;">SVG does not show on website? <a href="https://laythemeforum.com/topic/5190/svg-cursors-fix" target="_blank" rel="noopener noreferrer">Read this.</a></div>';
	}

	public function laycarousel_buttonspace_setting(){
		$val = get_option( 'laycarousel_buttonspace', "10" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_buttonspace" id="laycarousel_buttonspace"> <label>px</label>';	
	}

	public function laycarousel_buttonspace_vertical_setting(){
		$val = get_option( 'laycarousel_buttonspace_vertical', "10" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_buttonspace_vertical" id="laycarousel_buttonspace_vertical"> <label>px</label>';
	}

	public function laycarousel_buttonspace_grouped_setting(){
		$val = get_option( 'laycarousel_buttonspace_grouped', "10" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_buttonspace_grouped" id="laycarousel_buttonspace_grouped"> <label>px</label>';
	}

	public function laycarousel_show_button_circle_setting(){
		$val = get_option( 'laycarousel_show_button_circle', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_show_button_circle" id="laycarousel_show_button_circle" '.$checked.'>';
	}

	public function laycarousel_button_circle_color_setting(){
		$val = get_option( 'laycarousel_button_circle_color', '#e8e8e8' );
		echo '<input type="text" name="laycarousel_button_circle_color" id="laycarousel_button_circle_color" value="' . esc_attr( $val ) . '" class="laycarousel-button-circle-color-picker">';
	}

	public function laycarousel_button_circle_padding_setting(){
		$val = get_option( 'laycarousel_button_circle_padding', "7" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_button_circle_padding" id="laycarousel_button_circle_padding"> <label>px</label>';
	}

	public function laycarousel_use_button_circle_mouseover_color_setting(){
		$val = get_option( 'laycarousel_use_button_circle_mouseover_color', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_use_button_circle_mouseover_color" id="laycarousel_use_button_circle_mouseover_color" '.$checked.'>';
	}

	public function laycarousel_button_circle_mouseover_color_setting(){
		$val = get_option( 'laycarousel_button_circle_mouseover_color', '#d8d8d8' );
		echo '<input type="text" name="laycarousel_button_circle_mouseover_color" id="laycarousel_button_circle_mouseover_color" value="' . esc_attr( $val ) . '" class="laycarousel-button-circle-mouseover-color-picker">';
	}

	public function laycarousel_button_circle_icon_horizontal_offset_setting(){
		$val = get_option( 'laycarousel_button_circle_icon_horizontal_offset', "1" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_button_circle_icon_horizontal_offset" id="laycarousel_button_circle_icon_horizontal_offset"> <label>px</label>';
	}

	public function laycarousel_set_custom_button_width_setting(){
		$val = get_option( 'laycarousel_set_custom_button_width', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_set_custom_button_width" id="laycarousel_set_custom_button_width" '.$checked.'>';
	}

	public function laycarousel_button_width_setting(){
		$val = get_option( 'laycarousel_button_width', "20" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_button_width" id="laycarousel_button_width"> <label>px</label>';
	}

	public function laycarousel_buttons_arrangement_setting(){
		$val = get_option( 'laycarousel_buttons_arrangement', 'spread' );

		$selectedSpread = 'selected';
		$selectedGroupedLeft = '';
		$selectedGroupedRight = '';

		switch ($val) {
			case 'grouped_left':
				$selectedGroupedLeft = 'selected';
				$selectedSpread = '';
			break;
			case 'grouped_right':
				$selectedGroupedRight = 'selected';
				$selectedSpread = '';
			break;
		}

		echo
		'<select style="width:auto;" name="laycarousel_buttons_arrangement" id="laycarousel_buttons_arrangement">
			<option value="spread" '.$selectedSpread.'>Spread</option>
			<option value="grouped_left" '.$selectedGroupedLeft.'>Grouped Left</option>
			<option value="grouped_right" '.$selectedGroupedRight.'>Grouped Right</option>
		</select>';
	}

	public function laycarousel_buttons_position_setting(){
		$val = get_option( 'laycarousel_buttons_position', 'on' );

		$selectedAbove = '';
		$selectedOn = 'selected';
		$selectedBelow = '';

		switch ($val) {
			case 'above':
				$selectedAbove = 'selected';
				$selectedOn = '';
			break;
			case 'below':
				$selectedBelow = 'selected';
				$selectedOn = '';
			break;
		}

		echo
		'<select style="width:auto;" name="laycarousel_buttons_position" id="laycarousel_buttons_position">
			<option value="above" '.$selectedAbove.'>above carousel</option>
			<option value="on" '.$selectedOn.'>on carousel</option>
			<option value="below" '.$selectedBelow.'>below carousel</option>
		</select>';
	}

	public function laycarousel_textspace_setting(){
		$val = get_option( 'laycarousel_textspace', "0" );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textspace" id="laycarousel_textspace"> <label>px</label>';	
	}

	public function laycarousel_textspace_tablet_setting(){
		$val = get_option( 'laycarousel_textspace_tablet', get_option( 'laycarousel_textspace', "0" ) );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textspace_tablet" id="laycarousel_textspace_tablet"> <label>px</label>';	
	}

	public function laycarousel_textspace_phone_setting(){
		$val = get_option( 'laycarousel_textspace_phone', get_option( 'laycarousel_textspace', "0" ) );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textspace_phone" id="laycarousel_textspace_phone"> <label>px</label>';	
	}

	public function laycarousel_textslide_width_setting(){
		$val = get_option( 'laycarousel_textslide_width', "400" );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textslide_width" id="laycarousel_textslide_width"> <label>px</label>';
		echo '<p style="font-size: 10px; margin: 5px 0 0 0; font-style: italic; color: #666;">This width is just for when you use the "Slide" type with "Show multiple slides" enabled or "Horizontal Scroll" type.</p>';
	}

	public function laycarousel_textslide_width_tablet_setting(){
		$val = get_option( 'laycarousel_textslide_width_tablet', get_option( 'laycarousel_textslide_width', "400" ) );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textslide_width_tablet" id="laycarousel_textslide_width_tablet"> <label>px</label>';
		echo '<p style="font-size: 10px; margin: 5px 0 0 0; font-style: italic; color: #666;">This width is just for when you use the "Slide" type with "Show multiple slides" enabled or "Horizontal Scroll" type.</p>';
	}

	public function laycarousel_textslide_width_phone_setting(){
		$val = get_option( 'laycarousel_textslide_width_phone', get_option( 'laycarousel_textslide_width', "400" ) );
		echo '<input type="number" value="'.$val.'" step="1" min="0" name="laycarousel_textslide_width_phone" id="laycarousel_textslide_width_phone"> <label>px</label>';
		echo '<p style="font-size: 10px; margin: 5px 0 0 0; font-style: italic; color: #666;">This width is just for when you use the "Slide" type with "Show multiple slides" enabled or "Horizontal Scroll" type.</p>';
	}	

	// space bottom captions
	public function laycarousel_spaceBottomCaptions_setting(){
		$val = get_option( 'laycarousel_spaceBottomCaptions', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBottomCaptions" id="laycarousel_spaceBottomCaptions"> px';		
	}

	// space top captions
	public function laycarousel_spaceBetween_setting(){
		$val = get_option( 'laycarousel_spaceBetween', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBetween" id="laycarousel_spaceBetween"> px';		
	}

	// space left captions
	public function laycarousel_caption_space_left_setting(){
		$val = get_option( 'laycarousel_caption_space_left', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_caption_space_left" id="laycarousel_caption_space_left"> px';		
	}

	// space right captions
	public function laycarousel_caption_space_right_setting(){
		$val = get_option( 'laycarousel_caption_space_right', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_caption_space_right" id="laycarousel_caption_space_right"> px';		
	}

	// space bottom
	public function laycarousel_spaceBottomCircles_setting(){
		$val = get_option( 'laycarousel_spaceBottomCircles', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBottomCircles" id="laycarousel_spaceBottomCircles"> px';	 		
	}

	public function laycarousel_spaceBetweenForCircles_setting(){
		$val = get_option( 'laycarousel_spaceBetweenForCircles', "10" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBetweenForCircles" id="laycarousel_spaceBetweenForCircles"> px';	 		
	}

	public function laycarousel_navigation_space_between_circles_setting(){
		$val = get_option( 'laycarousel_navigation_space_between_circles', '6' );
		echo '<input type="number" value="' . esc_attr( $val ) . '" min="0" step="1" name="laycarousel_navigation_space_between_circles" id="laycarousel_navigation_space_between_circles"> <label for="laycarousel_navigation_space_between_circles"> (px) Space between circles of navigation</label>';
	}

	public function laycarousel_navigation_circles_size_setting(){
		$val = get_option( 'laycarousel_navigation_circles_size', '10' );
		echo '<input type="number" value="' . esc_attr( $val ) . '" min="1" step="1" name="laycarousel_navigation_circles_size" id="laycarousel_navigation_circles_size"> <label for="laycarousel_navigation_circles_size"> (px) Size of navigation circles</label>';
	}

	public function laycarousel_navigation_circles_color_setting(){
		$val = get_option( 'laycarousel_navigation_circles_color', '#333333' );
		echo '<input type="text" name="laycarousel_navigation_circles_color" id="laycarousel_navigation_circles_color" value="' . esc_attr( $val ) . '" class="laycarousel-navigation-circles-color-picker">';
	}

	// space bottom
	public function laycarousel_spaceBottomNumbers_setting(){
		$val = get_option( 'laycarousel_spaceBottomNumbers', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBottomNumbers" id="laycarousel_spaceBottomNumbers"> px';	 		

	}

	// Space Top
	public function laycarousel_spaceBetweenForNumbers_setting(){
		$val = get_option( 'laycarousel_spaceBetweenForNumbers', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_spaceBetweenForNumbers" id="laycarousel_spaceBetweenForNumbers"> px';	 		
	}

	// Space Left
	public function laycarousel_numbers_space_left_setting(){
		$val = get_option( 'laycarousel_numbers_space_left', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_numbers_space_left" id="laycarousel_numbers_space_left"> px';	 		
	}

	// Space Right
	public function laycarousel_numbers_space_right_setting(){
		$val = get_option( 'laycarousel_numbers_space_right', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_numbers_space_right" id="laycarousel_numbers_space_right"> px';	 		
	}

	public function laycarousel_numbersPosition_setting(){
		$val = get_option( 'laycarousel_numbersPosition', "right" );

		$selectedLeft = '';
		$selectedCenter = '';
		$selectedRight = '';

		switch ($val) {
			case 'left':
				$selectedLeft = 'selected';
			break;
			case 'center':
				$selectedCenter = 'selected';
			break;
			case 'right':
				$selectedRight = 'selected';
			break;
		}

		echo 
		'<select name="laycarousel_numbersPosition" id="laycarousel_numbersPosition">
			<option value="left" '.$selectedLeft.'>left</option>
			<option value="center" '.$selectedCenter.'>center</option>
			<option value="right" '.$selectedRight.'>right</option>
		</select>';  		
	}

	public function laycarousel_captionsPosition_setting(){
		$val = get_option( 'laycarousel_captionsPosition', "left" );

		$selectedLeft = '';
		$selectedCenter = '';
		$selectedRight = '';

		switch ($val) {
			case 'left':
				$selectedLeft = 'selected';
			break;
			case 'center':
				$selectedCenter = 'selected';
			break;
			case 'right':
				$selectedRight = 'selected';
			break;
		}

		echo 
		'<select name="laycarousel_captionsPosition" id="laycarousel_captionsPosition">
			<option value="left" '.$selectedLeft.'>left</option>
			<option value="center" '.$selectedCenter.'>center</option>
			<option value="right" '.$selectedRight.'>right</option>
		</select>'; 		
	}

	public function laycarousel_showNumbers_setting(){
		$val = get_option( 'laycarousel_showNumbers', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_showNumbers" id="laycarousel_showNumbers" '.$checked.'><label for="laycarousel_showNumbers"> (for example "1/4")</label>'; 		
	}

	public function laycarousel_showcircles_setting(){
		$val = get_option( 'laycarousel_showCircles', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_showCircles" id="laycarousel_showCircles" '.$checked.'>';
	}

	public function laycarousel_captionTextformat_setting(){
		// display a selectable list of textformats
		$val = get_option( 'laycarousel_captionTextformat', "Default" );

		echo '<select name="laycarousel_captionTextformat" id="laycarousel_captionTextformat">';
		foreach (Customizer::$textformatsSelect as $format) {
			$selected = ($val == $format) ? 'selected' : '';
			echo '<option value="'.$format.'" '.$selected.'>'.$format.'</option>';
		}	
		echo '</select>'; 
	}

	public function laycarousel_numberTextformat_setting(){
		// display a selectable list of textformats
		$val = get_option( 'laycarousel_numberTextformat', "Default" );

		echo '<select name="laycarousel_numberTextformat" id="laycarousel_numberTextformat">';
		foreach (Customizer::$textformatsSelect as $format) {
			$selected = ($val == $format) ? 'selected' : '';
			echo '<option value="'.$format.'" '.$selected.'>'.$format.'</option>';
		}	
		echo '</select>'; 
	}

	public function laycarousel_showCaptions_setting(){
		$val = get_option( 'laycarousel_showCaptions', "on" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_showCaptions" id="laycarousel_showCaptions" '.$checked.'>';
	}

    public function laycarousel_autoplayDelay_setting(){
		$val = get_option( 'laycarousel_autoplayDelay', "0" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_autoplayDelay" id="laycarousel_autoplayDelay"> <label for="laycarousel_autoplayDelay"> milliseconds</label>';		
	}

	public function laycarousel_autoplaySpeed_setting(){
		$val = get_option( 'laycarousel_autoplaySpeed', "2000" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_autoplaySpeed" id="laycarousel_autoplaySpeed"> <label for="laycarousel_autoplaySpeed"> milliseconds</label>';		
	}

    public function laycarousel_transitionSpeed_setting(){
		$val = get_option( 'laycarousel_transitionSpeed', "300" );
		echo '<input type="number" value="'.$val.'" min="0" step="1" name="laycarousel_transitionSpeed" id="laycarousel_transitionSpeed"> <label for="laycarousel_transitionSpeed"> milliseconds</label>';		
    }

	public function laycarousel_pauseAutoplayOnHover_setting(){
		$val = get_option( 'laycarousel_pauseAutoplayOnHover', "on" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_pauseAutoplayOnHover" id="laycarousel_pauseAutoplayOnHover" '.$checked.'>';
	}

    public function laycarousel_loop_touchdevice_setting(){
		$val = get_option( 'laycarousel_loop_touchdevice', "on" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_loop_touchdevice" id="laycarousel_loop_touchdevice" '.$checked.'> <label for="laycarousel_loop_touchdevice">( Looping on touch devices can be buggy especially on iOS Safari. Turn this off if you experience crashes. )</label>';
    }

	public function laycarousel_loop_setting(){
		$val = get_option( 'laycarousel_loop', "on" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_loop" id="laycarousel_loop" '.$checked.'>';
	}

	public function laycarousel_mousecursor_setting(){
		$val = get_option( 'laycarousel_mousecursor', "leftright" );

		$selectedLeftRight = '';
		$selectedRight = '';
		$selectedNone = '';
		$selectedGrab = '';
		$selectedPointer = '';

		switch ($val) {
			case 'leftright':
				$selectedLeftRight = 'selected';
			break;
			case 'right':
				$selectedRight = 'selected';
			break;
			case 'none':
				$selectedNone = 'selected';
			break;
			case 'grab':
				$selectedGrab = 'selected';
			break;
			case 'pointer':
				$selectedPointer = 'selected';
			break;
		}

		echo 
		'<select style="width:auto;" name="laycarousel_mousecursor" id="laycarousel_mousecursor">
			<option value="leftright" '.$selectedLeftRight.'>"Previous" and "Next" Cursors</option>
			<option value="right" '.$selectedRight.'>Only "Next" Cursor</option>
			<option value="grab" '.$selectedGrab.'>"Grab" Hand Cursor</option>
			<option value="pointer" '.$selectedPointer.'>"Pointer" Hand Cursor</option>
			<option value="none" '.$selectedNone.'>No Cursor</option>
		</select>'; 		
	}

	public function laycarousel_dom_cursor_setting(){
		$val = get_theme_mod( 'lay_cursor_use_dom_cursor', 0 ) == 1 ? 'on' : 'off';
		$checked = $val === 'on' ? 'checked' : '';
		echo
		'<input type="hidden" name="laycarousel_dom_cursor" value="off">
		<label for="laycarousel_dom_cursor">
			<input type="checkbox" name="laycarousel_dom_cursor" id="laycarousel_dom_cursor" value="on" '.$checked.'>
			Use DOM cursor rendering (global setting)
		</label>
		<p style="max-width: 460px; font-size: 10px; margin: 5px 0 0 0; font-style: italic; color: #666;">Try this if your cursor image looks blurry. Best results with SVG cursors. This is a global setting and applies to all site cursors and addon cursors.</p>';
	}

	public function laycarousel_dom_cursor_sync_to_theme_mod($value){
		$enabled = $value === 'on' ? 1 : 0;
		set_theme_mod('lay_cursor_use_dom_cursor', $enabled);
		return $enabled ? 'on' : 'off';
	}

	public function laycarousel_sink_position_setting(){
		$val = get_option( 'laycarousel_sink_position', "below" );

		$selectedBelow = '';
        $selectedAbove = '';
		$selectedOntop = '';

		switch ($val) {
			case 'below':
				$selectedBelow = 'selected';
			break;
			case 'ontop':
				$selectedOntop = 'selected';
			break;
            case 'above':
				$selectedAbove = 'selected';
			break;
		}

		echo 
		'<select style="width:auto;" name="laycarousel_sink_position" id="laycarousel_sink_position">
			<option value="below" '.$selectedBelow.'>Below Carousel</option>
            <option value="above" '.$selectedAbove.'>Above Carousel</option>
			<option value="ontop" '.$selectedOntop.'>On Carousel</option>
		</select>';	
	}

	public function laycarousel_transition_setting(){
		$val = get_option( 'laycarousel_transition', "sliding" );

		$selectedSliding = 'selected';
		$selectedFading = '';
		$selectedImmediate = '';
        $selectedHorizontalScroll = '';

		switch ($val) {
			case 'fading':
				$selectedSliding = '';
				$selectedFading = 'selected';
                $selectedHorizontalScroll = '';
				$selectedImmediate = '';
			break;
			case 'immediate':
				$selectedSliding = '';
				$selectedFading = '';
                $selectedHorizontalScroll = '';
				$selectedImmediate = 'selected';
			break;
            case 'scroll':
                $selectedSliding = '';
                $selectedFading = '';
                $selectedImmediate = '';
                $selectedHorizontalScroll = 'selected';
		}

		echo 
		'<select style="width:auto;" name="laycarousel_transition" id="laycarousel_transition">
			<option value="sliding" '.$selectedSliding.'>Slide (swipeable)</option>
			<option value="fading" '.$selectedFading.'>Fade</option>
            <option value="scroll" '.$selectedHorizontalScroll.'>Horizontal Scroll</option>
            <option value="immediate" '.$selectedImmediate.'>None</option>
		</select>';
	}

	public function laycarousel_lazyload_setting(){
		$val = get_option( 'laycarousel_lazyload', "on" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_lazyload" id="laycarousel_lazyload" '.$checked.'> <label for="laycarousel_lazyload">(Lazyload images when you scroll to them and lazyload non-visible images. Recommended for quicker loading time of your page.)</label>';
	}

	public function laycarousel_align_sink_setting(){
		$val = get_option( 'laycarousel_align_sink', "" );
		$checked = "";
		if( $val == "on" ){
			$checked = "checked";
		}
		echo '<input type="checkbox" name="laycarousel_align_sink" id="laycarousel_align_sink" '.$checked.'>';		
	}

    public function laycarousel_horizontal_alignment_setting(){
		$val = get_option( 'laycarousel_horizontal_alignment', "center" );
		
		$selectedLeft = '';
		$selectedCenter = 'selected';
		$selectedRight = '';

		switch ($val) {
			case 'left':
				$selectedLeft = 'selected';
				$selectedCenter = '';
				$selectedRight = '';
			break;
			case 'right':
				$selectedLeft = '';
				$selectedCenter = '';
				$selectedRight = 'selected';
			break;
		}

		echo 
		'<select name="laycarousel_horizontal_alignment" id="laycarousel_horizontal_alignment">
			<option value="left" '.$selectedLeft.'>left</option>
			<option value="center" '.$selectedCenter.'>center</option>
			<option value="right" '.$selectedRight.'>right</option>
		</select>';
    }

	public function laycarousel_alignment_setting(){
		$val = get_option( 'laycarousel_alignment', "bottom" );
		
		$selectedTop = '';
		$selectedMiddle = '';
		$selectedBottom = 'selected';

		switch ($val) {
			case 'top':
				$selectedTop = 'selected';
				$selectedMiddle = '';
				$selectedBottom = '';
			break;
			case 'middle':
				$selectedTop = '';
				$selectedMiddle = 'selected';
				$selectedBottom = '';
			break;
		}

		echo 
		'<select name="laycarousel_alignment" id="laycarousel_alignment">
			<option value="top" '.$selectedTop.'>top</option>
			<option value="middle" '.$selectedMiddle.'>center</option>
			<option value="bottom" '.$selectedBottom.'>bottom</option>
		</select>';
	}

	public function laycarousel_text_alignment_setting(){
		$val = get_option( 'laycarousel_text_alignment', "middle" );
		
		$selectedTop = '';
		$selectedMiddle = 'selected';
		$selectedBottom = '';

		switch ($val) {
			case 'top':
				$selectedTop = 'selected';
				$selectedMiddle = '';
				$selectedBottom = '';
			break;
			case 'bottom':
				$selectedTop = '';
				$selectedMiddle = '';
				$selectedBottom = 'selected';
			break;
		}

		echo 
		'<select name="laycarousel_text_alignment" id="laycarousel_text_alignment">
			<option value="top" '.$selectedTop.'>top</option>
			<option value="middle" '.$selectedMiddle.'>center</option>
			<option value="bottom" '.$selectedBottom.'>bottom</option>
		</select>';
	}
}

new LayThemeCarouselSettings();