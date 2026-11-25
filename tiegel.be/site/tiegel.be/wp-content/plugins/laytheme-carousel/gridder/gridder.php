<?php
class LayThemeCarouselGridder {
	public static function init() {
		add_action( 'admin_enqueue_scripts', 'LayThemeCarouselGridder::lg_localize_gridder_js_with_carousel', 11 );
	}

	public static function lg_localize_gridder_js_with_carousel() {
		$showArrowButtons = get_option( 'laycarousel_showArrowButtonsForTouchDevices', "" ) == "on" || get_option( 'laycarousel_showArrowButtons', "" ) == "on";
		$transition = get_option( 'laycarousel_transition', "sliding" );
		$loop = get_option( 'laycarousel_loop', 'on' ) == 'on' ? true : false;
		
        $autoplaySpeed = get_option( 'laycarousel_autoplaySpeed', '2000' );
        $autoplayDelay = get_option( 'laycarousel_autoplayDelay', '0' );
        $transitionSpeed = get_option( 'laycarousel_transitionSpeed', '300' );

		$array = array( 
			'transition' => $transition,
			'showArrowButtons' => $showArrowButtons,
			'loop' => $loop,
            'autoplaySpeed' => (int)$autoplaySpeed,
            'autoplayDelay' => (int)$autoplayDelay,
            'transitionSpeed' => (int)$transitionSpeed
		);
		wp_localize_script( 'gridder-app', 'carouselPassedData', $array ); 
	}
}

LayThemeCarouselGridder::init();