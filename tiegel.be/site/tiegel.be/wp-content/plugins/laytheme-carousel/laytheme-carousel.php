<?php
/**
 * Plugin Name: Lay Theme Carousel
 * Plugin URI: https://laytheme.com/addons/
 * Description: Use carousels (slideshows) to show your images. Only works with Lay Theme!
 * Version: 3.1.2
 * Author: 100k Studio
 * Author URI: http://100k.studio
 */

class LayThemeCarousel{

	public static $url;
	public static $dir;
	public static $ver;


	public function __construct(){
		LayThemeCarousel::$url = plugins_url( 'laytheme-carousel' );
		LayThemeCarousel::$dir = plugin_dir_path( __FILE__ );
		LayThemeCarousel::$ver = '3.1.2';

		// admin panel
		add_action( 'lay_after_projectpage_gridder_buttons', array( $this, 'add_carousel_button_to_gridder_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gridder_carouseapp_styles' ) );
		// TODO: delete filter?
		// add_action( 'lay_before_projectpage_gridder_modals', array( $this, 'add_modal_markup' ) );
		add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array($this, 'add_action_links') );
		// add_action( 'lay_gridder_context_menu_end', array($this, 'add_toggle_fullscreen_contextmenu_button') );

		// frontend
		add_action( 'init', array( $this, 'register_vendor' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_carousel_scripts' ), 11 );
		// add_action( 'wp_enqueue_scripts', array( $this, 'frontend_carousel_styles' ) );
		add_action( 'wp_footer', array( $this, 'frontend_carousel_styles' ) );
	}

	public function add_toggle_fullscreen_contextmenu_button(){
		echo 
		'<button type="button" class="js-carousel-fullscreen-button btn btn-default"><span class="glyphicon glyphicon-ok"></span> Make Carousel Fullscreen</button>';
	}

	public function add_action_links( $links ){
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=manage-laycarousel' ) . '">Settings</a>',
		);
		return array_merge( $links, $mylinks );
	}

	public static function add_carousel_button_to_gridder_header(){
		echo
		'<button type="button" class="btn btn-default add-carousel-js">
			<span class="glyphicon glyphicon-plus"></span> Carousel
		</button>';
	}

	public static function gridder_carouseapp_styles(){
		$screen = get_current_screen();
		if ( $screen->id == 'post' || $screen->id == 'page' || $screen->id == 'edit-category' || $screen->id == 'lay_news' || $screen->id == 'product') {
			wp_enqueue_style( 'gridder-laycarousel-style', LayThemeCarousel::$url.'/gridder/assets/css/gridder.style.css', array(), LayThemeCarousel::$ver );
		}
	}

	public static function register_vendor(){
		// Only register Vimeo script if it's not disabled in settings
		// This helps with GDPR compliance by allowing users to disable the Vimeo script
		$disable_vimeo = get_option( 'misc_options_disable_vimeo_player_script', '' );
		if( $disable_vimeo != 'on' ){
			wp_register_script( 'vimeo', 'https://player.vimeo.com/api/player.js', array(), LayThemeCarousel::$ver, true );
		}
	}

	public static function get_dom_cursor_asset_data( $option_name, $default_url, $default_hotspot_x = 0, $default_hotspot_y = 0 ){
		$asset = array(
			'src' => $default_url,
			'hotspotX' => $default_hotspot_x,
			'hotspotY' => $default_hotspot_y,
			'width' => null,
			'height' => null,
		);

		$cursor = get_option( $option_name, '' );
		if( $cursor != '' ){
			$cursor = wp_get_attachment_image_src( $cursor, 'full' );
			if( $cursor ){
				$asset['src'] = $cursor[0];
				$asset['hotspotX'] = $cursor[1] / 2;
				$asset['hotspotY'] = $cursor[2] / 2;
				$asset['width'] = (int) $cursor[1];
				$asset['height'] = (int) $cursor[2];
			}
		}

		return $asset;
	}

	public static function frontend_carousel_scripts(){
		$transition = get_option( 'laycarousel_transition', "sliding" );
		$autoplaySpeed = get_option( 'laycarousel_autoplaySpeed', "2000" );
        $autoplayDelay = get_option( 'laycarousel_autoplayDelay', "0" );
        $transitionSpeed = get_option( 'laycarousel_transitionSpeed', '300' );

		$mousecursor = get_option( 'laycarousel_mousecursor', "leftright" );
		$domCursorFeatureAvailable = function_exists('lay_dom_cursor_manager_available') && lay_dom_cursor_manager_available();
		$domCursor = get_theme_mod( 'lay_cursor_use_dom_cursor', 0 ) == 1 && $domCursorFeatureAvailable;
		$showCaptions = get_option( 'laycarousel_showCaptions', "on" );
		$captionTextformat = get_option( 'laycarousel_captionTextformat', "Default" );
		$numberTextformat = get_option( 'laycarousel_numberTextformat', "Default" );
		$showCircles = get_option( 'laycarousel_showCircles', "" );
		$showNumbers = get_option( 'laycarousel_showNumbers', "" );
		$numbersPosition = get_option( 'laycarousel_numbersPosition', "right" );
		$captionsPosition = get_option( 'laycarousel_captionsPosition', "left" );
		$showArrowButtons = get_option( 'laycarousel_showArrowButtons', "" );
		$pauseAutoplayOnHover = get_option( 'laycarousel_pauseAutoplayOnHover', "on" );
		$lazyload = get_option( 'laycarousel_lazyload', "on" );
		$alignSink = get_option( 'laycarousel_align_sink', "" ); 
		$contentAlignment = get_option( 'laycarousel_alignment', "bottom" );

		$rightButton = '';
		$laycarousel_rightbutton = get_option( 'laycarousel_rightbutton', '' );
		if($laycarousel_rightbutton != ''){
			$laycarousel_rightbutton = wp_get_attachment_image_src( $laycarousel_rightbutton, 'full' );
			if($laycarousel_rightbutton){
				$rightButton = $laycarousel_rightbutton[0];
			}
		}
		$alt_rightButton = '';
		$alt_laycarousel_rightbutton = get_option( 'laycarousel_altrightbutton', '' );
		if($alt_laycarousel_rightbutton != ''){
			$alt_laycarousel_rightbutton = wp_get_attachment_image_src( $alt_laycarousel_rightbutton, 'full' );
			if($alt_laycarousel_rightbutton){
				$alt_rightButton = $alt_laycarousel_rightbutton[0];
			}
		}
		// register swiper
		wp_enqueue_script( 'laycarousel-app', LayThemeCarousel::$url.'/frontend/assets/js/carousel.plugin.min.js', array( 'swiper' ), LayThemeCarousel::$ver, true);

		$showArrowButtonsForTouchDevices = get_option( 'laycarousel_showArrowButtonsForTouchDevices', "" );
		$laycarousel_sink_position = get_option( 'laycarousel_sink_position', 'below' );
		$laycarousel_buttons_position = get_option( 'laycarousel_buttons_position', 'on' );
		$textalignment = get_option( 'laycarousel_text_alignment', "middle" );
		$loop = get_option( 'laycarousel_loop', 'on' ) == 'on' ? true : false;
        $less_sizing = get_option( 'laycarousel_disable_sizing', "" ) == 'on' ? true : false;
		$plugin_url = plugin_dir_url( __FILE__ ) . 'frontend/assets/img/';
		$dom_cursor_assets = array(
			'prev' => LayThemeCarousel::get_dom_cursor_asset_data( 'laycarousel_arrowleft', $plugin_url . 'cursor_prev.svg', 9, 9 ),
			'next' => LayThemeCarousel::get_dom_cursor_asset_data( 'laycarousel_arrowright', $plugin_url . 'cursor_next.svg', 9, 9 ),
		);

		$textSlideWidthDesktop = get_option( 'laycarousel_textslide_width', "400" );
		$textSlideWidthTablet = get_option( 'laycarousel_textslide_width_tablet', $textSlideWidthDesktop );
		$textSlideWidthPhone = get_option( 'laycarousel_textslide_width_phone', $textSlideWidthDesktop );

		wp_localize_script( 'laycarousel-app', 'layCarouselPassedData',
			array(
                'horizontalAlignment' => get_option( 'laycarousel_horizontal_alignment', "center" ),
				'youtube' => get_option( 'laycarousel_youtube', "" ),
				'loop' => $loop,
				'transition' => $transition,
				'autoplaySpeed' => (int)$autoplaySpeed,
                'autoplayDelay' => (int)$autoplayDelay,
                'transitionSpeed' => (int)$transitionSpeed,
				'mousecursor' => $mousecursor,
				'domCursor' => $domCursor,
				'showCaptions' => $showCaptions,
				'captionTextformat' => $captionTextformat,
				'numberTextformat' => $numberTextformat,
				'showCircles' => $showCircles,
				'showNumbers' => $showNumbers,
				'captionsPosition' => $captionsPosition,
				'numbersPosition' => $numbersPosition,
				'showArrowButtons' => $showArrowButtons,
				'pauseAutoplayOnHover' => $pauseAutoplayOnHover,
				'rightButton' => $rightButton,
				'lazyload' => $lazyload,
				'alt_rightButton' => $alt_rightButton,
				'showArrowButtonsForTouchDevices' => $showArrowButtonsForTouchDevices,
				'alignSink' => $alignSink,
				'contentAlignment' => $contentAlignment,
				'sinkPosition' => $laycarousel_sink_position,
				'buttonsPosition' => $laycarousel_buttons_position,
				'textAlignment' => $textalignment,
                'lessSizing' => $less_sizing,
                'originalImages' => get_option( 'misc_options_showoriginalimages', "" ),
                'loopTouchDevice' => LayCarouselPluginOptions::$laycarousel_loop_touchdevice,
				'textSlideWidthDesktop' => $textSlideWidthDesktop,
				'textSlideWidthTablet' => $textSlideWidthTablet,
				'textSlideWidthPhone' => $textSlideWidthPhone,
				'domCursorAssets' => $dom_cursor_assets,
		  	)
		);

	}

	public static function frontend_carousel_styles(){
		wp_enqueue_style( 'laycarousel-style', LayThemeCarousel::$url.'/frontend/assets/css/frontend.style.css', array(), LayThemeCarousel::$ver );

		$sink_position = get_option('laycarousel_sink_position', 'below');
		$domCursorFeatureAvailable = function_exists('lay_dom_cursor_manager_available') && lay_dom_cursor_manager_available();
		$use_dom_cursor = get_theme_mod( 'lay_cursor_use_dom_cursor', 0 ) == 1 && $domCursorFeatureAvailable;
		// custom cursors
		$cursorMarkup = "";

		if( !$use_dom_cursor ){
			$left_cursor = get_option( 'laycarousel_arrowleft', '' );
			if($left_cursor != ''){
				$left_cursor = wp_get_attachment_image_src( $left_cursor, 'full' );
				if($left_cursor){
					$cursorMarkup .= '.col .lay-carousel.cursor-left{cursor:url("'.$left_cursor[0].'") '.($left_cursor[1]/2).' '.($left_cursor[2]/2).', w-resize;}';
				}
			}
	
			$right_cursor = get_option( 'laycarousel_arrowright', '' );
			if($right_cursor != ''){
				$right_cursor = wp_get_attachment_image_src( $right_cursor, 'full' );
				if($right_cursor){
					$cursorMarkup .= '.col .lay-carousel.cursor-right{cursor:url("'.$right_cursor[0].'") '.($right_cursor[1]/2).' '.($right_cursor[2]/2).', e-resize;}';
				}
			}

			$cursorMarkup .= '.col .lay-carousel.cursor-left .flickity-prev-next-button-wrap,
				.col .lay-carousel.cursor-right .flickity-prev-next-button-wrap{cursor:auto;}
				.col .lay-carousel.cursor-left .flickity-prev-next-button-wrap .flickity-prev-next-button,
				.col .lay-carousel.cursor-left .flickity-prev-next-button-wrap .flickity-prev-next-button *,
				.col .lay-carousel.cursor-right .flickity-prev-next-button-wrap .flickity-prev-next-button,
				.col .lay-carousel.cursor-right .flickity-prev-next-button-wrap .flickity-prev-next-button *{cursor:pointer;}';
		}

		$domCursorCss = '';
		if( $use_dom_cursor ){
			$domCursorCss = '.lay-carousel.dom-cursor-enabled{cursor:none!important;}
			.lay-carousel.dom-cursor-enabled *{cursor:none!important;}
			.lay-carousel.dom-cursor-enabled .flickity-prev-next-button-wrap{cursor:auto!important;}
			.lay-carousel.dom-cursor-enabled .flickity-prev-next-button-wrap .flickity-prev-next-button,
			.lay-carousel.dom-cursor-enabled .flickity-prev-next-button-wrap .flickity-prev-next-button *{cursor:pointer!important;}';
		}
	
		$spaceTopCaptions = get_option( 'laycarousel_spaceBetween', "0" );
		$spaceBottomCaptions = get_option( 'laycarousel_spaceBottomCaptions', "0" );
		$captionsTopBottomCSS = '';
		switch( $sink_position ){
			case 'below':
				$captionsTopBottomCSS .= 'margin-top:'.$spaceTopCaptions.'px;';
			break;
			case 'ontop':
				$captionsTopBottomCSS .= 'padding-bottom:'.$spaceBottomCaptions.'px;';
			break;
            case 'above':
                $captionsTopBottomCSS .= 'margin-bottom:'.$spaceBottomCaptions.'px;';
            break;
		}

		$captionsPosition = get_option( 'laycarousel_captionsPosition', 'left' );
		$captionsLeftRightCSS = '';
		switch( $captionsPosition ) {
			case 'left':
				$captionsLeftRightCSS .= 'padding-left:'.get_option( 'laycarousel_caption_space_left', "0" ).'px;';
			break;
			case 'right':
				$captionsLeftRightCSS .= 'padding-right:'.get_option( 'laycarousel_caption_space_right', "0" ).'px;';
			break;
		}

		$spaceTopNumbers = get_option( 'laycarousel_spaceBetweenForNumbers', "0" );
		$spaceBottomNumbers = get_option( 'laycarousel_spaceBottomNumbers', "0" );
		$numbersCSS = '';
		switch( $sink_position ){
			case 'below':
				$numbersCSS .= 'margin-top:'.$spaceTopNumbers.'px;';
			break;
			case 'ontop':
				$numbersCSS .= 'padding-bottom:'.$spaceBottomNumbers.'px;';
			break;
            case 'above':
                $numbersCSS .= 'padding-bottom:'.$spaceBottomNumbers.'px;';
            break;
		}

		$numbersPosition = get_option( 'laycarousel_numbersPosition', 'left' );
		switch( $numbersPosition ) {
			case 'left':
				$numbersCSS .= 'padding-left:'.get_option( 'laycarousel_numbers_space_left', "0" ).'px;';
			break;
			case 'right':
				$numbersCSS .= 'padding-right:'.get_option( 'laycarousel_numbers_space_right', "0" ).'px;';
			break;
		}

        // if numbers and captions have same position, use space right of numbers so there is some space
        // between them
        if( $numbersPosition == $captionsPosition ) {
            $numbersCSS .= 'padding-right:'.get_option( 'laycarousel_numbers_space_right', "0" ).'px;';
        }

				// space between edge of slide and text slide
		$textspace = get_option( 'laycarousel_textspace', "0" );
		$textspace_tablet = get_option( 'laycarousel_textspace_tablet', $textspace );
		$textspace_phone = get_option( 'laycarousel_textspace_phone', $textspace );
		
		// text slide width
		$textslide_width = get_option( 'laycarousel_textslide_width', "400" );
		$textslide_width_tablet = get_option( 'laycarousel_textslide_width_tablet', $textslide_width );
		$textslide_width_phone = get_option( 'laycarousel_textslide_width_phone', $textslide_width );
		
		// get dynamic breakpoints
		$tablet_breakpoint = (int)get_option( 'lay_tablet_breakpoint', 1024 );
		$phone_breakpoint = (int)get_option( 'lay_breakpoint', 600 );

		//space between arrow buttons and carousel edge
		$laycarousel_buttonspace = get_option( 'laycarousel_buttonspace', "10" );
		$laycarousel_buttonspace_vertical = get_option( 'laycarousel_buttonspace_vertical', "10" );
		$laycarousel_buttonspace_grouped = get_option( 'laycarousel_buttonspace_grouped', "10" );
		$laycarousel_show_button_circle = get_option( 'laycarousel_show_button_circle', '' );
		$laycarousel_button_circle_color = get_option( 'laycarousel_button_circle_color', '#e8e8e8' );
		$laycarousel_button_circle_color = sanitize_hex_color( $laycarousel_button_circle_color ) ?: '#e8e8e8';
		$laycarousel_use_button_circle_mouseover_color = get_option( 'laycarousel_use_button_circle_mouseover_color', '' );
		$laycarousel_button_circle_mouseover_color = get_option( 'laycarousel_button_circle_mouseover_color', '#d8d8d8' );
		$laycarousel_button_circle_mouseover_color = sanitize_hex_color( $laycarousel_button_circle_mouseover_color ) ?: '#d8d8d8';
		$laycarousel_button_circle_padding = (int) get_option( 'laycarousel_button_circle_padding', "7" );
		$laycarousel_button_circle_icon_horizontal_offset = (int) get_option( 'laycarousel_button_circle_icon_horizontal_offset', "1" );
		$laycarousel_set_custom_button_width = get_option( 'laycarousel_set_custom_button_width', '' );
		$laycarousel_button_width = (int) get_option( 'laycarousel_button_width', "20" );
		$laycarousel_buttons_position = get_option( 'laycarousel_buttons_position', 'on' );
		$laycarousel_buttons_arrangement = get_option( 'laycarousel_buttons_arrangement', 'spread' );

		// buttons outside of carousel for desktop/tablet version and buttonspace:
		$buttonPlacementCSS = '';
		$buttonPlacementCSS .= '.lay-carousel-wrap .flickity-prev-next-button-wrap{ pointer-events:none; }
			.lay-carousel-wrap .flickity-prev-next-button-wrap .flickity-prev-next-button{ pointer-events:auto; }';
		$buttonPlacementCSS .= '.flickity-prev-next-button.next{ right:'.$laycarousel_buttonspace.'px; }
			    .flickity-prev-next-button.previous{ left:'.$laycarousel_buttonspace.'px; }';

		switch ( $laycarousel_buttons_arrangement ) {
			case 'grouped_left':
				$arrangementJustify = 'flex-start';
				$arrangementGap = (int)$laycarousel_buttonspace_grouped;
				break;
			case 'grouped_right':
				$arrangementJustify = 'flex-end';
				$arrangementGap = (int)$laycarousel_buttonspace_grouped;
				break;
			default:
				$arrangementJustify = 'space-between';
				$arrangementGap = 0;
				break;
		}

		switch ( $laycarousel_buttons_position ) {
			case 'above':
				$buttonPlacementCSS .= '.lay-carousel-wrap .flickity-prev-next-button-wrap{ display:flex; justify-content:'.$arrangementJustify.'; gap:'.$arrangementGap.'px; box-sizing:border-box; width:100%; padding-left:'.$laycarousel_buttonspace.'px; padding-right:'.$laycarousel_buttonspace.'px; margin-bottom:'.$laycarousel_buttonspace_vertical.'px; }
				.lay-carousel-wrap .flickity-prev-next-button-wrap .flickity-prev-next-button{ position:relative; top:auto; bottom:auto; left:auto; right:auto; float:none; margin-top:0; margin-bottom:0; -webkit-transform:none; transform:none; }';
			break;
			case 'below':
				$buttonPlacementCSS .= '.lay-carousel-wrap .flickity-prev-next-button-wrap{ display:flex; justify-content:'.$arrangementJustify.'; gap:'.$arrangementGap.'px; box-sizing:border-box; width:100%; padding-left:'.$laycarousel_buttonspace.'px; padding-right:'.$laycarousel_buttonspace.'px; margin-top:'.$laycarousel_buttonspace_vertical.'px; }
				.lay-carousel-wrap .flickity-prev-next-button-wrap .flickity-prev-next-button{ position:relative; top:auto; bottom:auto; left:auto; right:auto; float:none; margin-top:0; margin-bottom:0; -webkit-transform:none; transform:none; }';
			break;
			default:
				$buttonPlacementCSS .= '.lay-carousel-wrap .flickity-prev-next-button{ top:50%; bottom:auto; -webkit-transform:translateY(-50%); transform:translateY(-50%); }';
			break;
		}

		$buttonCircleCSS = '';
		if( $laycarousel_show_button_circle === 'on' ){
			$buttonCircleCSS = '.lay-carousel-wrap .flickity-prev-next-button:not(.is-carousel-custom-button){ border-radius:9999px; background:'.$laycarousel_button_circle_color.'; padding:'.$laycarousel_button_circle_padding.'px; box-sizing:content-box; }';
			if( $laycarousel_use_button_circle_mouseover_color === 'on' ){
				$buttonCircleCSS .= '.no-touchdevice .lay-carousel-wrap .flickity-prev-next-button:not(.is-carousel-custom-button):hover{ background:'.$laycarousel_button_circle_mouseover_color.'; }';
			}
		}

		$buttonCircleIconOffsetCSS = '';
		if( $laycarousel_show_button_circle === 'on' ){
			$buttonCircleIconOffsetCSS = '.lay-carousel-wrap .flickity-prev-next-button.previous:not(.is-carousel-custom-button) svg{ -webkit-transform:translateX(-'.$laycarousel_button_circle_icon_horizontal_offset.'px); transform:translateX(-'.$laycarousel_button_circle_icon_horizontal_offset.'px); }
			.lay-carousel-wrap .flickity-prev-next-button.next:not(.is-carousel-custom-button) svg{ -webkit-transform:translateX('.$laycarousel_button_circle_icon_horizontal_offset.'px); transform:translateX('.$laycarousel_button_circle_icon_horizontal_offset.'px); }';
		}

		$buttonWidthCSS = '';
		if( $laycarousel_set_custom_button_width === 'on' ){
			$buttonWidthCSS = '.lay-carousel-wrap.default-arrowbuttons .flickity-prev-next-button:not(.is-carousel-custom-button){ width:auto; height:auto; }
			.lay-carousel-wrap .flickity-prev-next-button:not(.is-carousel-custom-button) svg{ position:static; display:block; width:'.$laycarousel_button_width.'px; height:auto; left:auto; top:auto; -webkit-transform:none; transform:none; }';
		}

		if(
			$laycarousel_buttons_position === 'on'
			&& get_option( 'laycarousel_arrow_buttons_outside', '' ) == 'on'
			&& class_exists('MiscOptions')
		) {
			$buttonPlacementCSS .= '@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){
				.place-buttons-outside .flickity-prev-next-button.previous {
					-webkit-transform: translate(-100%, -50%);
					transform: translate(-100%, -50%);
				}
				.place-buttons-outside .flickity-prev-next-button.next {
					-webkit-transform: translate(100%, -50%);
					transform: translate(100%, -50%);
				}
				.flickity-prev-next-button.next{ right:-'.$laycarousel_buttonspace.'px; }
				.flickity-prev-next-button.previous{ left:-'.$laycarousel_buttonspace.'px; }
			}';
		}
		if(
			$laycarousel_buttons_position === 'on'
			&& get_option( 'laycarousel_arrow_buttons_outside_for_phone', '' ) == 'on'
			&& class_exists('MiscOptions')
		) {
			$buttonPlacementCSS .= '@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
				.place-buttons-outside .flickity-prev-next-button.previous {
					-webkit-transform: translate(-100%, -50%);
					transform: translate(-100%, -50%);
				}
				.place-buttons-outside .flickity-prev-next-button.next {
					-webkit-transform: translate(100%, -50%);
					transform: translate(100%, -50%);
				}
				.flickity-prev-next-button.next{ right:-'.$laycarousel_buttonspace.'px; }
				.flickity-prev-next-button.previous{ left:-'.$laycarousel_buttonspace.'px; }
			}';
		}

		$spaceTopCircles = get_option( 'laycarousel_spaceBetweenForCircles', "10" );
		$spaceBottomCircles = get_option( 'laycarousel_spaceBottomCircles', "0" );
		$spaceBetweenCircles = get_option( 'laycarousel_navigation_space_between_circles', '6' );
		$circlesSize = get_option( 'laycarousel_navigation_circles_size', '10' );
		$circlesColor = get_option( 'laycarousel_navigation_circles_color', '#333333' );
		$circlesColor = sanitize_hex_color( $circlesColor ) ?: '#333333';
		$circlesCSS = '';
		switch( $sink_position ){
			case 'below':
				$circlesCSS .= 'padding-top:'.$spaceTopCircles.'px;';
			break;
			case 'ontop':
				$circlesCSS .= 'padding-bottom:'.$spaceBottomCircles.'px;';
			break;
            case 'above':
				$circlesCSS .= 'padding-bottom:'.$spaceBottomCircles.'px;';
			break;
		}
		$circlesBulletsCSS = '.laycarousel-bullets .swiper-pagination-bullet{ width:'.(int)$circlesSize.'px; height:'.(int)$circlesSize.'px; margin:0 '.(int)$spaceBetweenCircles.'px; background:'.esc_attr( $circlesColor ).'; } .laycarousel-bullets .swiper-pagination-bullet:first-child{ margin-left:0; } .laycarousel-bullets .swiper-pagination-bullet:last-child{ margin-right:0; }';

		// need selector like this so margin-top doesnt get overwritten by textformat: ".lay-carousel-sink-parent.lay-textformat-parent"
		wp_add_inline_style( 'laycarousel-style',
			'.lay-carousel-caption-under-slide-caption,
            .lay-carousel-sink-parent .sink-caption{ '.$captionsTopBottomCSS.$captionsLeftRightCSS.' }
			.lay-carousel-sink-parent .numbers,
            .lay-carousel-caption-under-slide-number{ '.$numbersCSS.' }
			.laycarousel-bullets{ '.$circlesCSS.' }
			'.$circlesBulletsCSS
			.$domCursorCss
			.$cursorMarkup
			.$buttonPlacementCSS
			.$buttonCircleCSS
			.$buttonWidthCSS
			.$buttonCircleIconOffsetCSS
			.'@media (min-width: '.($tablet_breakpoint+1).'px) {
				.lay-carousel .slide-text{ padding-left:'.$textspace.'px; padding-right:'.$textspace.'px; }
			}'
			.'@media (max-width: '.$tablet_breakpoint.'px) and (min-width: '.($phone_breakpoint+1).'px) {
				.lay-carousel .slide-text{ padding-left:'.$textspace_tablet.'px; padding-right:'.$textspace_tablet.'px; }
			}'
			.'@media (max-width: '.$phone_breakpoint.'px) {
				.lay-carousel .slide-text{ padding-left:'.$textspace_phone.'px; padding-right:'.$textspace_phone.'px; }
			}'
			.'@media (min-width: '.($tablet_breakpoint+1).'px) {
				.lay-carousel-wrap.show-multiple-slides .lay-carousel-slide-text{ width:'.$textslide_width.'px; }
				.col:not(.type-html):not(.type-shortcode):not(.type-text):not(.cmb2-element).type-carousel .lay-carousel-wrap.show-multiple-slides.no-fixed-slides-width .lay-carousel-slide.lay-carousel-slide-text{ width:'.$textslide_width.'px; }
			}'
			.'@media (max-width: '.$tablet_breakpoint.'px) and (min-width: '.($phone_breakpoint+1).'px) {
				.lay-carousel-wrap.show-multiple-slides .lay-carousel-slide-text{ width:'.$textslide_width_tablet.'px; }
				.col:not(.type-html):not(.type-shortcode):not(.type-text):not(.cmb2-element).type-carousel .lay-carousel-wrap.show-multiple-slides.no-fixed-slides-width .lay-carousel-slide.lay-carousel-slide-text{ width:'.$textslide_width_tablet.'px; }
			}'
			.'@media (max-width: '.$phone_breakpoint.'px) {
				.lay-carousel-wrap.show-multiple-slides .lay-carousel-slide-text{ width:'.$textslide_width_phone.'px; }
				.col:not(.type-html):not(.type-shortcode):not(.type-text):not(.cmb2-element).type-carousel .lay-carousel-wrap.show-multiple-slides.no-fixed-slides-width .lay-carousel-slide.lay-carousel-slide-text{ width:'.$textslide_width_phone.'px; }
			}'
		);
	}
}
new LayThemeCarousel();

// settings
require plugin_dir_path(__FILE__).'settings/settings.php';
// gridder
require plugin_dir_path(__FILE__).'gridder/gridder.php';
// frontend
require plugin_dir_path(__FILE__).'frontend/assets/php/carouselOptions.php';
require plugin_dir_path(__FILE__).'frontend/assets/php/carousel.php';

$disable_updates = get_option('misc_options_disable_lay_updates', '');
if($disable_updates != "on"){
	require 'plugin_update_check.php';
	$MyUpdateChecker = new PluginUpdateChecker_2_0 (
		'https://kernl.us/api/v1/updates/56b09a95ffb0be4f0769e299/',
		__FILE__,
		'laytheme-carousel',
		1
	);
}
