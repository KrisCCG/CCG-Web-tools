<?php

/*

I already have all the min-height CSS in _100vh.scss
However, when the user has a mobile bar, this php file generates the CSS that is needed for the first row
Otherwise, this php file is not even included and the standard css by _100vh.scss is used

In general: Only the first row should be 100vh - browserbarheight - mobile bar height
All other 100vh rows should be really just 100vh, cause when you scroll down, the full 100vh will be shown

*/

// covers are at the top and on phone, need to have a height of 100vh - browserbars - navbarheight
// same values as in _100vh scss
// we only need this when a navbar is shown
// i also need this for first row only


// todo: when using a fullpage slider, i would be ideal to always just inherit the height from fp-tableCell
// todo: think about this. when a 100vh row is the second row and on mobile the menu bar is fixed. should the second, third row etc maybe still be 100vh - menubarheight? √
// as of now for fullpage js rows will always only be 100vh, because fullpage js' containers are always 100vh anyways and ios bars will never disappear when scrolling and standard autoscroll is active

class CSS_Output_First_Row_100vh_Heights{

        public static $safari_iphone_barheight_scrolled_up;
        public static $safari_iphone_X_barheight_scrolled_up;

        public static $chrome_android_barheight_scrolled_up;
        public static $chrome_iphone_X_barheight_scrolled_up;
        public static $chrome_iphone_barheight_scrolled_up;

        public static $safari_ipad_barheight_scrolled_up;
        public static $chrome_ipad_barheight_scrolled_up;

        public static $safari_ipad_no_homebutton_scrolled_up;
        public static $chrome_ipad_no_homebutton_scrolled_up;

        public static $mobile_menubar_height;

    public function __construct(){
        // this is for safari on iphone and ipad

        // note: make sure these values are the same as in _100vh.scss !

        CSS_Output_First_Row_100vh_Heights::$safari_iphone_barheight_scrolled_up = '75px';
        CSS_Output_First_Row_100vh_Heights::$safari_iphone_X_barheight_scrolled_up = '80px';

        CSS_Output_First_Row_100vh_Heights::$chrome_android_barheight_scrolled_up = '56px';
        CSS_Output_First_Row_100vh_Heights::$chrome_iphone_X_barheight_scrolled_up = '109px';
        CSS_Output_First_Row_100vh_Heights::$chrome_iphone_barheight_scrolled_up = '71px';

        CSS_Output_First_Row_100vh_Heights::$safari_ipad_barheight_scrolled_up = '64px';
        CSS_Output_First_Row_100vh_Heights::$chrome_ipad_barheight_scrolled_up = '75px';

        CSS_Output_First_Row_100vh_Heights::$safari_ipad_no_homebutton_scrolled_up = '31px';
        CSS_Output_First_Row_100vh_Heights::$chrome_ipad_no_homebutton_scrolled_up = '73px';

        CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height = get_theme_mod('mobile_menubar_height', 40).'px';
    }

    public static function get_first_row_100vh_heights(){
        // why do we use min-height instead of height???
        $selectors = array(
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled) body>.lay-content .row.first-row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled) body>.lay-content .row.first-row ._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'body:not(.woocommerce-page).touchdevice.sticky-footer-option-enabled>.lay-content' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled).show-project-overlay #lay-project-overlay .row.first-row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled).show-project-overlay #lay-project-overlay .row.first-row ._100vh' ),
			// cover min height
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .cover-inner._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .row-inner._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .column-wrap._100vh' ),
			array( 'toSet' => 'min-height', 'selector' => '.cover-enabled-on-phone .cover-region' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region .cover-inner' ),
			// cover height
			array( 'toSet' => 'height', 'selector' => '.cover-region-phone._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .cover-inner._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .row._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .row-inner._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .column-wrap._100vh' ),
			array( 'toSet' => 'height', 'selector' => '.cover-enabled-on-phone .cover-region' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region .cover-inner' ),
			// carousel
            array( 'toSet' => 'min-height', 'selector' => 'body>.lay-content .row.first-row .col .lay-carousel._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html.show-project-overlay #lay-project-overlay .row.first-row .col .lay-carousel._100vh' ),
			// fullpage js
            array( 'toSet' => 'height', 'selector' => 'body:not(.fp-autoscroll) .fullpage-wrapper' ),
			// scrollsnap
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .vertical-scrollsnap .first-row' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .horizontal-scrollsnap .first-row' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .first-row .row-inner' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .first-row .column-wrap' ),
        );

        // just using 100svh now instead of 100vh

        $markup = '';

        if(CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height != 0) {
            // for projects overlay, only use 100vh, not 100svh:
            $markup = '
            /* make sure the space-top in a project overlay is the same as when opened not in a project overlay and space bottom too: */
            html.show-project-overlay #lay-project-overlay{
                padding-top: '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.';
                height: calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }';
        }

        // for projects overlay, only use 100vh, not 100svh:
        $markup .= 'html.show-project-overlay body:not(.woocommerce-page).touchdevice.sticky-footer-option-enabled #lay-project-overlay>.lay-content{
            min-height: calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
        }';

		// COVERS: when we are on a desktop device and just resize to phone size:
		$markup .= '
		.cover-region-phone._100vh,
		.cover-region-phone .cover-inner._100vh,
		.cover-region-phone .row._100vh,
		.cover-region-phone .row-inner._100vh,
		.cover-region-phone .column-wrap._100vh,
		.cover-enabled-on-phone .cover-region,
		.cover-region .cover-inner,
        .cover-region .cover-inner ._100vh{
			min-height: calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
			height: calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
		}';

        foreach( $selectors as $selector_array ) {
            $selector = $selector_array['selector'];
            $css_property = $selector_array['toSet'];

			$markup .= 
			'/* iphone (iphone with homebutton) safari */
            html.is-iphone.is-safari '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_iphone_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* iphonex (iphone without homebutton) safari */
            html.is-iphone-no-homebutton.is-safari '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_iphone_X_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* android chrome */
            html.is-android.is-chrome '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_android_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* iphonex (iphone without homebutton) chrome */
            html.is-iphone-no-homebutton.is-chrome '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_iphone_X_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* iphone (iphone with homebutton) chrome */
            html.is-iphone.is-chrome '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_iphone_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* ipad chrome */
            html.is-ipad.is-chrome '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_ipad_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }
            /* ipad safari */
            html.is-ipad.is-safari '.$selector .'{
                '.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_ipad_barheight_scrolled_up.' - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
				'.$css_property.': calc(100svh - '.CSS_Output_First_Row_100vh_Heights::$mobile_menubar_height.')!important;
            }';
        }

        return $markup;
    }

    /**
     * Same as get_first_row_100vh_heights() but for body.lay-hide-menu (page setting: hide menu).
     * Uses 0px for navbar height so cover/100vh calculations don't reserve space for the hidden navbar.
     */
    public static function get_first_row_100vh_heights_for_zero_navbar(){
        $zero = '0px';
        $prefix = 'body.lay-hide-menu ';

        $selectors = array(
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled) body>.lay-content .row.first-row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled) body>.lay-content .row.first-row ._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'body:not(.woocommerce-page).touchdevice.sticky-footer-option-enabled>.lay-content' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled).show-project-overlay #lay-project-overlay .row.first-row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html:not(.fp-enabled).show-project-overlay #lay-project-overlay .row.first-row ._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .cover-inner._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .row._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .row-inner._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region-phone .column-wrap._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-enabled-on-phone .cover-region' ),
            array( 'toSet' => 'min-height', 'selector' => '.cover-region .cover-inner' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .cover-inner._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .row._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .row-inner._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region-phone .column-wrap._100vh' ),
            array( 'toSet' => 'height', 'selector' => '.cover-enabled-on-phone .cover-region' ),
            array( 'toSet' => 'height', 'selector' => '.cover-region .cover-inner' ),
            array( 'toSet' => 'min-height', 'selector' => 'body>.lay-content .row.first-row .col .lay-carousel._100vh' ),
            array( 'toSet' => 'min-height', 'selector' => 'html.show-project-overlay #lay-project-overlay .row.first-row .col .lay-carousel._100vh' ),
            array( 'toSet' => 'height', 'selector' => 'body:not(.fp-autoscroll) .fullpage-wrapper' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .vertical-scrollsnap .first-row' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .horizontal-scrollsnap .first-row' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .first-row .row-inner' ),
            array( 'toSet' => 'height', 'selector' => 'html.scrollsnap-enabled .first-row .column-wrap' ),
        );

        $markup = '';

        $markup .= '
        /* page setting: hide menu – treat navbar height as 0 */
        html.show-project-overlay body.lay-hide-menu #lay-project-overlay{
            padding-top: '.$zero.';
            height: calc(100vh - '.$zero.')!important;
        }';

        $markup .= 'html.show-project-overlay body.lay-hide-menu:not(.woocommerce-page).touchdevice.sticky-footer-option-enabled #lay-project-overlay>.lay-content{
            min-height: calc(100vh - '.$zero.')!important;
        }';

        $markup .= '
        body.lay-hide-menu .cover-region-phone._100vh,
        body.lay-hide-menu .cover-region-phone .cover-inner._100vh,
        body.lay-hide-menu .cover-region-phone .row._100vh,
        body.lay-hide-menu .cover-region-phone .row-inner._100vh,
        body.lay-hide-menu .cover-region-phone .column-wrap._100vh,
        body.lay-hide-menu .cover-enabled-on-phone .cover-region,
        body.lay-hide-menu .cover-region .cover-inner,
        body.lay-hide-menu .cover-region .cover-inner ._100vh{
            min-height: calc(100vh - '.$zero.')!important;
            height: calc(100vh - '.$zero.')!important;
        }';

        foreach( $selectors as $selector_array ) {
            $sel = $selector_array['selector'];
            // Scope to body.lay-hide-menu: replace body> with body.lay-hide-menu>; body:not( with body.lay-hide-menu:not(; or for html-only selectors insert body.lay-hide-menu after html part; else prefix
            if ( strpos( $sel, 'body>' ) !== false ) {
                $selector = str_replace( 'body>', 'body.lay-hide-menu>', $sel );
            } elseif ( strpos( $sel, 'body:not(' ) !== false ) {
                $selector = str_replace( 'body:not(', 'body.lay-hide-menu:not(', $sel );
            } elseif ( substr( $sel, 0, 4 ) === 'html' && strpos( $sel, 'body' ) === false ) {
                $selector = preg_replace( '/ /', ' body.lay-hide-menu ', $sel, 1 );
            } else {
                $selector = $prefix . $sel;
            }
            $css_property = $selector_array['toSet'];

            $markup .=
            'html.is-iphone.is-safari '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_iphone_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-iphone-no-homebutton.is-safari '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_iphone_X_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-android.is-chrome '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_android_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-iphone-no-homebutton.is-chrome '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_iphone_X_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-iphone.is-chrome '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_iphone_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-ipad.is-chrome '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$chrome_ipad_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}
            html.is-ipad.is-safari '.$selector.'{'.$css_property.': calc(100vh - '.CSS_Output_First_Row_100vh_Heights::$safari_ipad_barheight_scrolled_up.' - '.$zero.')!important;'.$css_property.': calc(100svh - '.$zero.')!important;}';
        }

        return $markup;
    }

}
new CSS_Output_First_Row_100vh_Heights();