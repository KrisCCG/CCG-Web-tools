<?php
class MediaQueryCSS{

	public static $desktop;
	public static $phone;
	public static $woocommerce_desktop;
	public static $woocommerce_phone;
	public static $phone_menu_breakpoint_above;
	public static $phone_menu_breakpoint_under;
	public static $intro;

	public function __construct(){
		MediaQueryCSS::$phone_menu_breakpoint_above = require get_template_directory().'/frontend/style_phone_menu_breakpoint_above.php';
		MediaQueryCSS::$phone_menu_breakpoint_under = require get_template_directory().'/frontend/style_phone_menu_breakpoint_under.php';
		MediaQueryCSS::$desktop = require get_template_directory().'/frontend/style_desktop.php';
		MediaQueryCSS::$phone = require get_template_directory().'/frontend/style_phone.php';
		MediaQueryCSS::$intro = require get_template_directory().'/frontend/style_intro.php';
		if( class_exists( 'WooCommerce' ) ) {
			MediaQueryCSS::$woocommerce_desktop = require get_template_directory().'/woocommerce_integration/style_desktop.php';
			MediaQueryCSS::$woocommerce_phone = require get_template_directory().'/woocommerce_integration/style_phone.php';
		}
	}
}

new MediaQueryCSS();
