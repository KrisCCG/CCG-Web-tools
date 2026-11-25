<?php
class CSS_Output{

	public function __construct(){
        // if i dont use this hook but set all vars on construct, the latest values are not set when changing these values in customize
        // instead, the old values will still be in use
        // add_action( 'wp_loaded', array($this, 'lay_customize_css') );

        // todo: i dont know why but i need to reload twice for changes made to thumbnailgrid filter customizer settings to show up correctly

        add_action( 'wp_enqueue_scripts', array( $this, 'lay_customize_css' ), 20 );
    }

    public static function lay_customize_css(){
		// $textformats_for_tablet = get_option( 'misc_options_textformats_for_tablet', "" ) == "on" ? true : false;
		//
		// $formatsJsonString = FormatsManager::getDefaultFormatsJson();
		// $formatsJsonArr = json_decode($formatsJsonString, true);

		// $sharedStyles apply to desktop, tablet and mobile
        $sharedStyles = "";
		// $desktopAndTabletStyles applies to desktop and tablet size
        $desktopAndTabletStyles = "";
		// $desktopStyles = "";
		$tabletStyles = "";
        $mobileStyles = "";

        $phoneMenuStylesUnderBreakpoint = "";
        $phoneMenuStylesAboveBreakpoint = "";

        // Default underline offset variables - used throughout the CSS generation
        $defaultUnderlineOffset = get_theme_mod('link_underline_offset', 3);
        $defaultUnderlineOffsetMu = get_theme_mod('link_underline_offset_mu', 'px');

        // woocommerce
        if( class_exists( 'WooCommerce' ) ) {

            $hide = get_theme_mod('lay_woocommerce_cart_menupoint_hide_empty', 0);
            if( $hide == 1 ) {
                $desktopAndTabletStyles .= '.laycart-empty{ display: none!important; }';
            }

            $hide = get_theme_mod('lay_woocommerce_cart_icon_mobile_hide_empty', 0);
            if( $hide == 1 ) {
                $mobileStyles .= '.laycarticon-empty{ display: none!important; }';
                $mobileStyles .= '.lay-mobile-icons-wrap .laycart-empty{ display: none!important; }';
            }

            $always_hide_icon_and_show_cart_mobile_menupoint_instead = get_theme_mod('lay_woocommerce_cart_icon_mobile_hide', 0);
            if($always_hide_icon_and_show_cart_mobile_menupoint_instead == 1) {
                $mobileStyles .= '.lay-cart-icon-wrap{ display: none!important; }';
            }

            $lay_woocommerce_cart_menupoint_hide_empty_mobile = get_theme_mod('lay_woocommerce_cart_menupoint_hide_empty_mobile', 0);
            if($lay_woocommerce_cart_menupoint_hide_empty_mobile == 1) {
                $mobileStyles .= '.laynav .laycart-empty{ display: none!important; }';
            }

            // single product: page wrap max-width + center (desktop/tablet breakpoint — see desktop/product_singleview.scss)
            $sp_page_max_w = get_theme_mod('lay_woocommerce_singleproduct_page_max_width', 1920);
            if( $sp_page_max_w === '' || $sp_page_max_w === null ) {
                $sp_page_max_w = 1920;
            }
            $sp_page_max_w = max(0, floatval($sp_page_max_w));
            $sp_center_page = get_theme_mod('lay_woocommerce_singleproduct_center_page', true);
            $sp_margin = ( $sp_center_page === true || $sp_center_page === 1 || $sp_center_page === '1' )
                ? 'margin-left:auto;margin-right:auto;'
                : 'margin-left:0;margin-right:0;';
            $sp_max_width_css = ( $sp_page_max_w == 0 ) ? 'max-width:none;' : 'max-width:'.$sp_page_max_w.'px;';
            $desktopAndTabletStyles .=
            '.lay-woocommerce-single-product-wrap{width:100%;'.$sp_max_width_css.$sp_margin.'}';

            $type = get_theme_mod('lay_woocommerce_singleproduct_layout_type', 'one_image_per_row');
            if( $type == 'one_image_per_row' || $type == 'carousel' ) {
                $width = get_theme_mod('lay_woocommerce_singleproduct_image_width', 48.9);
                $desktopAndTabletStyles .= 
                'body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.carousel .lay-woocommerce-gallery,
                body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.one_image_per_row .lay-woocommerce-gallery
                { width:'.$width.'%; }';
                $space_left = get_theme_mod('lay_woocommerce_singleproduct_image_space_left', 0);
                $desktopAndTabletStyles .=
                '.lay-woocommerce-single-product-wrap{ padding-left:'.$space_left.'%; }';
            }

            if( $type == 'carousel_with_thumbnails' ){
                $width = get_theme_mod('lay_woocommerce_singleproduct_image_width', 48.9);
                // width minus thumbnails width
                $desktopAndTabletStyles .= 
                'body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.carousel_with_thumbnails .lay-woocommerce-gallery
                { width: calc('.$width.'% - 135px); }';
                $space_left = get_theme_mod('lay_woocommerce_singleproduct_image_space_left', 0);
                $desktopAndTabletStyles .=
                '.lay-woocommerce-single-product-wrap{ padding-left:'.$space_left.'%; }';
            }

            // carousel custom height
            if( $type == 'carousel' || $type == 'carousel_with_thumbnails' ) {
                $lay_woocommerce_singleproduct_desktop_carousel_height = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_height', 'auto');
                $lay_woocommerce_singleproduct_desktop_carousel_custom_height = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_custom_height', 'calc(100vh - 200px)');

                if($lay_woocommerce_singleproduct_desktop_carousel_height == 'custom') {
                    $desktopAndTabletStyles .=
                    '.lay-woocommerce-single-product-wrap.carousel_with_thumbnails .lay-woocommerce-gallery,
                    .lay-woocommerce-single-product-wrap.carousel .lay-woocommerce-gallery{
                        height: '.$lay_woocommerce_singleproduct_desktop_carousel_custom_height.';
                    }';
                }
            }

            // Space between gallery images: one image per row / two images per row (Customizer; % in UI → vw in CSS)
            if( $type == 'one_image_per_row' || $type == 'two_images_per_row' ) {
                $space_between = get_theme_mod('lay_woocommerce_singleproduct_space_between_images', 2);
                if( $space_between === '' || $space_between === null ) {
                    $space_between = 2;
                }
                $space_between = floatval( $space_between );
                $space_mu = CSS_Output::get_mu('lay_woocommerce_singleproduct_space_between_images_mu', 'vw');
                $gap_css = $space_between.$space_mu;
                $half_css = ( $space_between / 2 ).$space_mu;

                if( $type == 'one_image_per_row' ) {
                    $sharedStyles .=
                    'body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.one_image_per_row .lay-woocommerce-gallery div {
                        margin-bottom: '.$gap_css.';
                    }
                    body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.one_image_per_row .lay-woocommerce-gallery div:last-child {
                        margin-bottom: 0;
                    }';
                }
                if( $type == 'two_images_per_row' ) {
                    $sharedStyles .=
                    '@media (min-width: 1025px) {
                        body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.two_images_per_row .lay-woocommerce-gallery .swiper-wrapper {
                            gap: '.$gap_css.';
                        }
                        body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.two_images_per_row .lay-woocommerce-gallery .swiper-slide {
                            width: calc(50% - '.$half_css.');
                        }
                    }
                    @media (max-width: 1024px) {
                        body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.two_images_per_row .lay-woocommerce-gallery .swiper-slide {
                            margin-bottom: '.$gap_css.';
                        }
                        body.single-product #lay-woocommerce .lay-woocommerce-single-product-wrap.two_images_per_row .lay-woocommerce-gallery .swiper-slide:last-child {
                            margin-bottom: 0;
                        }
                    }';
                }
            }

            // button text format
            $button_text_format = get_theme_mod('lay_woocommerce_button_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($button_text_format, '.button', array('spaces' => false));
            
            // project thumbnail custom add to cart button text format
            $show_add_to_cart_button = get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0);
            if( $show_add_to_cart_button ) {
                if( get_theme_mod('lay_woocommerce_pt_use_custom_textformat', 1) == true ) {
                    $format = get_theme_mod('lay_woocommerce_pt_add_to_cart_textformat', 'Shop_Small');
                    $sharedStyles .= FormatsManager::get_textformat_css_for_selector($format, '.lay_woocommerce_product_thumbnail_text_wrap .button', array('spaces' => false));
                }
                $add_to_cart_position = get_theme_mod('lay_woocommerce_pt_add_to_cart_position', 'under');
                if( $add_to_cart_position !== 'under' ){
                    $sharedStyles .= '.lay_woocommerce_product_thumbnail_text_wrap.below-image{display:flex;justify-content:space-between;}';
                    if( $add_to_cart_position === 'left' ){
                        $sharedStyles .= '.lay_woocommerce_product_thumbnail_text_wrap.below-image .add_to_cart_inline{order:0;}';
                        $sharedStyles .= '.below-image.lay_woocommerce_product_thumbnail_text_wrap > a{order:1;}';
                    } else if( $add_to_cart_position === 'right' ){
                        $sharedStyles .= '.lay_woocommerce_product_thumbnail_text_wrap.below-image .add_to_cart_inline{order:1;}';
                        $sharedStyles .= '.below-image.lay_woocommerce_product_thumbnail_text_wrap > a{order:0;}';
                    }
                }
            }

            $lay_woocommerce_space_leftright_mu = CSS_Output::get_mu('lay_woocommerce_space_leftright_mu', 'vw');
            $lay_woocommerce_space_top_mu = CSS_Output::get_mu('lay_woocommerce_space_top_mu', 'px');
            $lay_woocommerce_space_bottom_mu = CSS_Output::get_mu('lay_woocommerce_space_bottom_mu', 'px');

            // todo: are these selectors sufficient?
            $desktopAndTabletStyles .= CSS_Output::generate_css('#lay-woocommerce', 'margin-top', 'lay_woocommerce_space_top', 100, '', $lay_woocommerce_space_top_mu);    
            $desktopAndTabletStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-bottom', 'lay_woocommerce_space_bottom', 100, '', $lay_woocommerce_space_bottom_mu);
            $desktopAndTabletStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-left', 'lay_woocommerce_space_leftright', 2, '', $lay_woocommerce_space_leftright_mu);
            $desktopAndTabletStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-right', 'lay_woocommerce_space_leftright', 2, '', $lay_woocommerce_space_leftright_mu);

            // Single product: outer horizontal padding overrides global #lay-woocommerce left/right (same as WooCommerce » Space Left/Right, default 2vw)
            $sp_outer_left = get_theme_mod('lay_woocommerce_singleproduct_outer_space_left', 2);
            $sp_outer_right = get_theme_mod('lay_woocommerce_singleproduct_outer_space_right', 2);
            if( $sp_outer_left === '' || $sp_outer_left === null ) {
                $sp_outer_left = 2;
            }
            if( $sp_outer_right === '' || $sp_outer_right === null ) {
                $sp_outer_right = 2;
            }
            $sp_outer_left = floatval($sp_outer_left);
            $sp_outer_right = floatval($sp_outer_right);
            $desktopAndTabletStyles .=
            'body.single-product #lay-woocommerce{padding-left:'.$sp_outer_left.'vw;padding-right:'.$sp_outer_right.'vw;}';

            // todo: are these selectors sufficient?
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce', 'margin-top', 'lay_woocommerce_mobile_space_top', 15, '', 'px');    
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-bottom', 'lay_woocommerce_mobile_space_bottom', 25, '', 'px');
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-left', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce', 'padding-right', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');

            // for a single product, i set the padding left and right of #lay-woocommerce to 0. This way the sliders (product slideshow and related slideshow can go to the edges)
            // that is why only the text and buttons in .summary should have the padding left and right
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce .summary, .lay-woocommerce-gallery .swiper-slide, .upsells.products h2, .related.products h2, #lay-woocommerce .lay-woocommerce-related-products-scroller ul li', 'padding-left', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');
            $mobileStyles .= CSS_Output::generate_css('#lay-woocommerce .summary, .lay-woocommerce-gallery .swiper-slide, #lay-woocommerce .lay-woocommerce-related-products-scroller ul', 'padding-right', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');

            // space between images when mobile layout is stacked images:
            $mobileStyles .= CSS_Output::generate_css('.lay-woocommerce-single-product-wrap.mobile-images .swiper-slide', 'margin-bottom', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');


            $mobileStyles .= CSS_Output::generate_css('.cart-collaterals', 'padding-top', 'lay_woocommerce_mobile_space_leftright', 15, '', 'px');
            
            // thumbnail
            $sharedStyles .= CSS_Output::generate_css('.lay-woocommerce-product-thumbnail-title', 'margin-top', 'lay_woocommerce_pt_spacetop', '12','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay-woocommerce-product-thumbnail-price', 'margin-top', 'lay_woocommerce_pt_price_spacetop', '0','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_description', 'margin-top', 'lay_woocommerce_pt_shortdescr_spacetop', '12','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay-woocommerce-product-thumbnail-brand', 'margin-top', 'lay_woocommerce_pt_brands_spacetop', '10','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail-out_of_stock', 'margin-top', 'lay_woocommerce_pt_outofstock_spacetop', '2','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap .add_to_cart_inline', 'margin-top', 'lay_woocommerce_pt_cart_button_spacetop', '15','', 'px');
            
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-top-left', 'left', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-top-right', 'right', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-left', 'left', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-left', 'bottom', 'lay_woocommerce_pt_inner_space', '10','', 'px');

            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-right', 'right', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $sharedStyles .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-right', 'bottom', 'lay_woocommerce_pt_inner_space', '10','', 'px');

            // buttons
            $button_style = get_theme_mod('lay_woocommerce_button_style', 'filled');
            $button_color = get_theme_mod('lay_woocommerce_button_color', '#000');
            $button_mouseover_color = get_theme_mod('lay_woocommerce_mouseover_button_color', '#000');
            // $lay_woocommerce_button_padding_top_bottom = get_theme_mod('lay_woocommerce_button_padding_top_bottom', '11');
            $lay_woocommerce_button_padding_left_right = get_theme_mod('lay_woocommerce_button_padding_left_right', '15');
            $lay_woocommerce_button_text_color = get_theme_mod('lay_woocommerce_button_text_color', '#fff');
            $lay_woocommerce_add_to_cart_button_height = get_theme_mod('lay_woocommerce_add_to_cart_button_height', 52);
            $lay_woocommerce_add_to_cart_button_height = intval($lay_woocommerce_add_to_cart_button_height);

            $sharedStyles .= CSS_Output::generate_css('.add_to_cart_button, .single_add_to_cart_button, .add_to_cart_inline .button', 'height', 'lay_woocommerce_add_to_cart_button_height', $lay_woocommerce_add_to_cart_button_height, '', 'px');
            $sharedStyles .= CSS_Output::generate_css('.add_to_cart_button, .single_add_to_cart_button, .add_to_cart_inline .button', 'line-height', 'lay_woocommerce_add_to_cart_button_height', $lay_woocommerce_add_to_cart_button_height-2, '', 'px!important');



            // loading icon color in button
            $sharedStyles .= '
            .woocommerce a.button.loading::after, .woocommerce button.button.loading::after, .woocommerce input.button.loading::after, .woocommerce #respond input#submit.loading::after{
                border-color: '.$lay_woocommerce_button_text_color.' '.$lay_woocommerce_button_text_color.' rgba(0,0,0,0.1) rgba(0,0,0,0.1);
            }';

            $sharedStyles .= '#lay-woocommerce .button, #lay-woocommerce a.button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ color:'.$lay_woocommerce_button_text_color.'; opacity: 1; }';
            $sharedStyles .= '#lay-woocommerce a.button:hover, #lay-woocommerce .button:hover, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable:hover{ color:'.get_theme_mod('lay_woocommerce_button_mouseover_text_color', '#fff').'!important; }';

						// $sharedStyles .= '#lay-woocommerce .button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ padding-top:'.$lay_woocommerce_button_padding_top_bottom.'px; padding-bottom:'.$lay_woocommerce_button_padding_top_bottom.'px; }';
            $sharedStyles .= '#lay-woocommerce .button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ padding-left:'.$lay_woocommerce_button_padding_left_right.'px; padding-right:'.$lay_woocommerce_button_padding_left_right.'px; }';
            $sharedStyles .= '#lay-woocommerce .button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ border-radius:'.get_theme_mod('lay_woocommerce_button_border_radius', 0).'px; }';
            
            $button_border_width = get_theme_mod('lay_woocommerce_button_border_width', '1');

            switch($button_style){
                case 'filled':
                    $sharedStyles .= '
                    #lay-woocommerce .button,
					#lay-woocommerce a.button,
                    .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{
                        background-color: '.$button_color.';
                        border-bottom: none!important;
                        border: none!important;
                    }
                    #lay-woocommerce .button:hover,
					#lay-woocommerce a.button:hover,
                    .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable:hover{
                        background-color: '.$button_mouseover_color.';
                    }
                    ';
                break;
                case 'outlines':
                    $sharedStyles .= '
                    #lay-woocommerce .button, 
					#lay-woocommerce a.button,
                    .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable:hover{
                        border-color: '.$button_color.';
                        border-bottom: '.$button_border_width.'px solid '.$button_color.'!important;
                        border-width: '.$button_border_width.'px;
                        border-style: solid;
                        background-color: transparent;
                    }
                    #lay-woocommerce .button:hover,
					#lay-woocommerce a.button:hover,
                    .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable:hover{
                        border-color: '.$button_mouseover_color.';
                        border-bottom-color: '.$button_mouseover_color.'!important;
                        background-color: transparent;
                    }
                    ';
                break;
            }
            if( get_theme_mod('lay_woocommerce_animate_mouseover_button_color', 0) == true ) {
                $sharedStyles .= '#lay-woocommerce .button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ transition: background-color 300ms ease, border-color 300ms ease, color 300ms ease; }';
            } else {
                $sharedStyles .= '#lay-woocommerce .button, .lay-content a.button.add_to_cart_button, .lay-content a.button.product_type_variable{ transition: none; }';
            }

            // store notice
            $lay_woocommerce_store_notice_textformat = get_theme_mod('lay_woocommerce_store_notice_textformat', 'Shop_Medium');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($lay_woocommerce_store_notice_textformat, '.woocommerce-store-notice', array('spaces' => false));

            $lay_woocommerce_store_notice_background_color = get_theme_mod('lay_woocommerce_store_notice_background_color', '#e4e6e6');
            $lay_woocommerce_store_notice_text_color = get_theme_mod('lay_woocommerce_store_notice_text_color', '#000');
            $lay_woocommerce_store_notice_bottom_border_width = get_theme_mod('lay_woocommerce_store_notice_bottom_border_width', 0);
            $lay_woocommerce_store_notice_bottom_border_color = get_theme_mod('lay_woocommerce_store_notice_bottom_border_color', '#e4e6e6');
            $lay_woocommerce_store_notice_height = get_theme_mod('lay_woocommerce_store_notice_height', 40);

            $spaceright_mu = CSS_Output::get_mu('lay_woocommerce_store_notice_spaceright_mu', 'px');
            $sharedStyles .= CSS_Output::generate_css('.woocommerce-store-notice__dismiss-link', 'padding-right', 'lay_woocommerce_store_notice_spaceright', 12,'', $spaceright_mu);    

            if ( get_theme_mod('lay_woocommerce_show_dismiss_button', 1) != 1 ) {
                $sharedStyles .= '.woocommerce-store-notice__dismiss-link{ display: none; }';
            } 

            $sharedStyles .= '
            .woocommerce-store-notice .woocommerce-store-notice__dismiss-link svg > g > g{
                stroke: '.$lay_woocommerce_store_notice_text_color.';
            }
            .woocommerce-store-notice a{
                color:'.$lay_woocommerce_store_notice_text_color.'!important;
                border-bottom: 1px solid '.$lay_woocommerce_store_notice_text_color.';
            }
            .woocommerce-store-notice{ 
                color:'.$lay_woocommerce_store_notice_text_color.'!important;
                background-color:'.$lay_woocommerce_store_notice_background_color.';
                border-bottom-style: solid;
                border-bottom-color: '.$lay_woocommerce_store_notice_bottom_border_color.';
                border-bottom-width: '.$lay_woocommerce_store_notice_bottom_border_width.'px;
            }';

            // single product
            $headline_textformat = get_theme_mod('lay_woocommerce_singleproduct_headline_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($headline_textformat, '.lay-woocommerce-single-product-wrap h1.product_title', array('spaces' => false));
            
            $sharedStyles .= CSS_Output::generate_css('body.single-product .lay-woocommerce-single-product-wrap h1.product_title', 'margin-bottom', 'lay_woocommerce_singleproduct_headline_spacebelow', '0', '', 'em');

            $shortdescr_textformat = get_theme_mod('lay_woocommerce_singleproduct_shortdescription_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($shortdescr_textformat, '.lay-woocommerce-single-product-wrap .woocommerce-product-details__short-description', array('spaces' => false));
            
            $price_textformat = get_theme_mod('lay_woocommerce_singleproduct_price_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($price_textformat, '.lay-woocommerce-single-product-wrap .lay-woocommerce-summary-inner > .price', array('spaces' => false));

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product .lay-woocommerce-summary-inner>.price', 'margin-top', 'lay_woocommerce_singleproduct_price_spacetop', '1.3', '', 'em');
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product .lay-woocommerce-summary-inner>.price', 'margin-bottom', 'lay_woocommerce_singleproduct_price_spacebelow', '1.3', '', 'em');

            $qty_textformat = get_theme_mod('lay_woocommerce_singleproduct_qty_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($qty_textformat, 'body.single-product div.product .quantity input.qty', array('spaces' => false));
            
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .lay-woocommerce-product-text', 'width', 'lay_woocommerce_singleproduct_text_width_2', '30', '', 'vw');

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .summary', 'margin-top', 'lay_woocommerce_singleproduct_text_spacetop', '100', '', 'px');
            // for sticky
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .summary', 'top', 'lay_woocommerce_singleproduct_text_sticky_spacetop', '100', '', 'px');

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .summary', 'padding-left', 'lay_woocommerce_singleproduct_text_spaceleft', '2', '', '%');

            $related_headline_textformat = get_theme_mod('lay_woocommerce_singleproduct_related_products_headline_textformat', 'Shop_Product_Thumbnails');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($related_headline_textformat, 'body.single-product .related.products > h2, body.single-product .upsells.products > h2', array('spaces' => false));

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .upsells.products > h2', 'margin-top', 'lay_woocommerce_singleproduct_related_products_spacetop', '3', '', '%');
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .related.products > h2', 'margin-top', 'lay_woocommerce_singleproduct_related_products_spacetop', '3', '', '%');

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .upsells.products > h2', 'margin-bottom', 'lay_woocommerce_singleproduct_related_products_spacebelow', '1.5', '', '%');
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .related.products > h2', 'margin-bottom', 'lay_woocommerce_singleproduct_related_products_spacebelow', '1.5', '', '%');

            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .lay-woocommerce-related-products-scroller .products', 'gap', 'lay_woocommerce_singleproduct_related_products_space_between', '2', '', '%');
            $desktopAndTabletStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .lay-woocommerce-related-products-scroller .products li.product', 'width', 'lay_woocommerce_singleproduct_related_products_width', '23.5', '', '%');


            $variations_selectbox_textformat = get_theme_mod('lay_woocommerce_singleproduct_variations_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($variations_selectbox_textformat, '.lay-woocommerce-variations-select, html .woocommerce div.product form.cart .reset_variations', array('spaces' => false));

            // variations text like, variation price, variation description, stock
            $variation_text_textformat = get_theme_mod('lay_woocommerce_singleproduct_variations_text_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($variation_text_textformat, '.woocommerce-variation', array('spaces' => false));

            // tabs or product description text format
            $product_tabs_text_textformat = get_theme_mod('lay_woocommerce_singleproduct_product_description_textformat', 'Shop_Small');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($product_tabs_text_textformat, '.lay-woocommerce-product-tab', array('spaces' => false));
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($product_tabs_text_textformat, '.lay-woocommerce-product-description-outside', array('spaces' => false));
            
            $lay_woocommerce_singleproduct_hide_variations_name = get_theme_mod('lay_woocommerce_singleproduct_hide_variations_name', 0);
            if($lay_woocommerce_singleproduct_hide_variations_name == 1) {
                $sharedStyles .= '.lay-woocommerce-variations-select-selected-field{ display: none; }';
            }

            // breadcrumbs
            $lay_woocommerce_singleproduct_breadcrumbs_space_bottom = get_theme_mod('lay_woocommerce_singleproduct_breadcrumbs_space_bottom', 1);
            $sharedStyles .= CSS_Output::generate_css('.woocommerce .woocommerce-breadcrumb', 'margin-bottom', 'lay_woocommerce_singleproduct_breadcrumbs_space_bottom', $lay_woocommerce_singleproduct_breadcrumbs_space_bottom, '', 'em');
            $lay_woocommerce_singleproduct_breadcrumbs_color = get_theme_mod('lay_woocommerce_singleproduct_breadcrumbs_color', '#acacac');
            $sharedStyles .= CSS_Output::generate_css('.woocommerce .woocommerce-breadcrumb', 'color', 'lay_woocommerce_singleproduct_breadcrumbs_color', $lay_woocommerce_singleproduct_breadcrumbs_color, '', '!important');

            $sp_layout_for_dots = get_theme_mod( 'lay_woocommerce_singleproduct_layout_type', 'one_image_per_row' );
            $sp_mobile_for_dots = get_theme_mod( 'lay_woocommerce_singleproduct_layout_type_mobile', 'carousel' );
            $sp_has_carousel_dots = ( $sp_layout_for_dots === 'carousel' || $sp_layout_for_dots === 'carousel_with_thumbnails' ) || $sp_mobile_for_dots === 'carousel';
            if ( $sp_has_carousel_dots ) {
                $carousel_dots = get_theme_mod( 'lay_woocommerce_singleproduct_carousel_dots_color', '#333333' );
                $carousel_dots = sanitize_hex_color( $carousel_dots );
                if ( ! $carousel_dots ) {
                    $carousel_dots = '#333333';
                }
                $carousel_dots_esc = esc_attr( $carousel_dots );
                $sharedStyles .= 'body.single-product .lay-woocommerce-gallery .swiper-pagination-bullet{background:'.$carousel_dots_esc.';}';
            }

            $lay_woocommerce_singleproduct_show_breadcrumbs = get_theme_mod('lay_woocommerce_singleproduct_show_breadcrumbs', 0);
            if($lay_woocommerce_singleproduct_show_breadcrumbs == 0) {
                $sharedStyles .= '.woocommerce .woocommerce-breadcrumb{ display: none; }';
            }
            $breadcrumbs_textformat = get_theme_mod('lay_woocommerce_singleproduct_breadcrumbs_textformat', 'Shop_Small');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($breadcrumbs_textformat, '.woocommerce .woocommerce-breadcrumb', array('spaces' => false));
            // #breadcrumbs

            // brands
            $lay_woocommerce_singleproduct_brands_space_bottom = get_theme_mod('lay_woocommerce_singleproduct_brands_space_bottom', 1);
            $sharedStyles .= CSS_Output::generate_css('.lay-woocommerce-product-brands', 'margin-bottom', 'lay_woocommerce_singleproduct_brands_space_bottom', $lay_woocommerce_singleproduct_brands_space_bottom, '', 'em');
            $lay_woocommerce_singleproduct_brands_color = get_theme_mod('lay_woocommerce_singleproduct_brands_color', '#acacac');
            $sharedStyles .= CSS_Output::generate_css('.lay-woocommerce-product-brands', 'color', 'lay_woocommerce_singleproduct_brands_color', $lay_woocommerce_singleproduct_brands_color, '', '!important');

            $lay_woocommerce_singleproduct_show_brands = get_theme_mod('lay_woocommerce_singleproduct_show_brands', 0);
            if($lay_woocommerce_singleproduct_show_brands == 0) {
                $sharedStyles .= '.lay-woocommerce-product-brands{ display: none; }';
            }
            $brands_textformat = get_theme_mod('lay_woocommerce_singleproduct_brands_textformat', 'Shop_Small');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($brands_textformat, '.lay-woocommerce-product-brands', array('spaces' => false));
            // #brands

            $lay_woocommerce_singleproduct_tab_spacetop = get_theme_mod('lay_woocommerce_singleproduct_tab_spacetop', 0);
            $sharedStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .lay-woocommerce-product-tab, body.single-product #lay-woocommerce  .lay-woocommerce-product-description-outside', 'margin-top', 'lay_woocommerce_singleproduct_tab_spacetop', $lay_woocommerce_singleproduct_tab_spacetop, '', 'em');

            $lay_woocommerce_singleproduct_shortdescription_spacebottom = get_theme_mod('lay_woocommerce_singleproduct_shortdescription_spacebottom', 0);
            $sharedStyles .= CSS_Output::generate_css('body.single-product #lay-woocommerce .woocommerce-product-details__short-description', 'margin-bottom', 'lay_woocommerce_singleproduct_shortdescription_spacebottom', $lay_woocommerce_singleproduct_shortdescription_spacebottom, '', 'em');

            $lay_woocommerce_singleproduct_order_breadcrumbs = get_theme_mod('lay_woocommerce_singleproduct_order_breadcrumbs', 0); 
            $lay_woocommerce_singleproduct_order_title = get_theme_mod('lay_woocommerce_singleproduct_order_title', 1);
            $lay_woocommerce_singleproduct_order_brands = get_theme_mod('lay_woocommerce_singleproduct_order_brands', 2);
            $lay_woocommerce_singleproduct_order_short_description = get_theme_mod('lay_woocommerce_singleproduct_order_short_description', 3);
            $lay_woocommerce_singleproduct_order_price = get_theme_mod('lay_woocommerce_singleproduct_order_price', 4);
            $lay_woocommerce_singleproduct_order_form = get_theme_mod('lay_woocommerce_singleproduct_order_form', 5);
            $lay_woocommerce_singleproduct_order_description_outside_tabs = get_theme_mod('lay_woocommerce_singleproduct_order_description_outside_tabs', 6);
            $lay_woocommerce_singleproduct_order_tabs = get_theme_mod('lay_woocommerce_singleproduct_order_tabs', 7);
            $lay_woocommerce_singleproduct_order_other = get_theme_mod('lay_woocommerce_singleproduct_order_other', 8);
            
            $sharedStyles .= '
            .lay-woocommerce-summary-inner > * {
                order: '.$lay_woocommerce_singleproduct_order_other.';
            }
            .lay-woocommerce-summary-inner .woocommerce-breadcrumb {
                order: '.$lay_woocommerce_singleproduct_order_breadcrumbs.';
            }
            .lay-woocommerce-summary-inner .product_title{
                order: '.$lay_woocommerce_singleproduct_order_title.';
            }
            .lay-woocommerce-summary-inner .lay-woocommerce-product-brands {
                order: '.$lay_woocommerce_singleproduct_order_brands.';
            }
            .lay-woocommerce-summary-inner .price {
                order: '.$lay_woocommerce_singleproduct_order_price.';
            }
            .lay-woocommerce-summary-inner .woocommerce-product-details__short-description {
                order: '.$lay_woocommerce_singleproduct_order_short_description.';
            }
            .lay-woocommerce-summary-inner .cart {
                order: '.$lay_woocommerce_singleproduct_order_form.';
            }
            .lay-woocommerce-summary-inner .lay-woocommerce-product-description-outside {
                order: '.$lay_woocommerce_singleproduct_order_description_outside_tabs.';
            }
            .lay-woocommerce-summary-inner .lay-woocommerce-product-tab {
                order: '.$lay_woocommerce_singleproduct_order_tabs.';
            }';

            // side cart
            if( LTUtility::is_sidecart_active() ) {

                // remove
                $remove_text = get_theme_mod('lay_woocommerce_remove_item_text', 'Remove');
                $sharedStyles .= '.xoo-wsc-smr-del.xoo-wsc-icon-trash::before{ content:"'.$remove_text.'"; }';

                $sharedStyles .= CSS_Output::generate_css('.xoo-wsc-container .xoo-wsc-header, .xoo-wsc-container .xoo-wsc-product, .xoo-wsc-container .xoo-wsc-ft-totals', 'border-color', 'lay_woocommerce_sidecart_line_color', '#000', '', '');
                $sharedStyles .= CSS_Output::generate_css('.xoo-wsc-body .xoo-wsc-sm-info .xoo-wsc-smr-del', 'color', 'lay_woocommerce_remove_item_color', '#aeaeae', '', '!important');
                
                $lay_woocommerce_sidecart_image_position = get_theme_mod('lay_woocommerce_sidecart_image_position', 'left');
                if($lay_woocommerce_sidecart_image_position == 'left') {
                    $sharedStyles .= '.xoo-wsc-sum-col{ order: 2!important; }';
                    $sharedStyles .= '.xoo-wsc-img-col{ order: 1!important; }';
                } else {
                    $sharedStyles .= '.xoo-wsc-sum-col{ order: 1!important; }';
                    $sharedStyles .= '.xoo-wsc-img-col{ order: 2!important; }';
                }

                // $format = get_theme_mod('lay_woocommerce_sidecart_header_textformat', 'Default');
                // $sharedStyles .= FormatsManager::get_textformat_css_for_selector($format, '.xoo-wsc-header .xoo-wscb-count, .xoo-wsc-header .xoo-wsch-close:before', false);

                $format = get_theme_mod('lay_woocommerce_sidecart_body_textformat', 'Shop_Cart');
                $sharedStyles .= FormatsManager::get_textformat_css_for_selector($format, '.xoo-wsc-body .xoo-wsc-sm-info, .xoo-wsc-body .xoo-wsc-sm-info span.amount, .xoo-wsc-body .xoo-wsc-sm-info a, .xoo-wsc-body .xoo-wsc-sm-info .xoo-wsc-smr-del.xoo-wsc-icon-trash', array('spaces' => false, 'important' => true));

                $format = get_theme_mod('lay_woocommerce_sidecart_notices_textformat', 'Shop_Small');
                $sharedStyles .= FormatsManager::get_textformat_css_for_selector($format, '.xoo-wsc-container .xoo-wsc-footer-txt', array('spaces' => false));

                $format = get_theme_mod('lay_woocommerce_sidecart_footer_textformat', 'Shop_Big');
                $sharedStyles .= FormatsManager::get_textformat_css_for_selector($format, '.xoo-wsc-footer .xoo-wsc-ft-totals>div, .xoo-wsc-footer .xoo-wsc-ft-totals .amount', array('spaces' => false));
                
                $sharedStyles .= CSS_Output::generate_css('.xoo-wsc-opac', 'background-color', 'lay_woocommerce_sidecart_background_color', '#000', '', '!important');
                $sharedStyles .= CSS_Output::generate_opacity_css('.xoo-wsc-cart-active .xoo-wsc-opac', 'lay_woocommerce_sidecart_background_opacity', 30, true);

                $sharedStyles .= CSS_Output::generate_css('.xoo-wsc-body .xoo-wsc-sm-info, .xoo-wsc-body .xoo-wsc-sm-info span.amount, .xoo-wsc-body .xoo-wsc-sm-info a, .xoo-wsc-header .xoo-wscb-count, .xoo-wsc-footer .xoo-wsc-ft-totals>div, .xoo-wsc-footer .xoo-wsc-ft-totals .amount', 'color', 'scb-txtcolor', Lay_SideCart::$sidecart_style_options['scb-txtcolor'], '', '!important');
            
                // side cart buttons
                // button text format
                $button_text_format = get_theme_mod('lay_woocommerce_sidecart_button_textformat', 'Shop_Big');
                $sharedStyles .= FormatsManager::get_textformat_css_for_selector($button_text_format, '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn', array('spaces' => false));
            
                $button_style = get_theme_mod('lay_woocommerce_sidecart_button_style', 'filled');
                $button_color = get_theme_mod('lay_woocommerce_sidecart_button_color', '#000');
                $button_mouseover_color = get_theme_mod('lay_woocommerce_sidecart_mouseover_button_color', '#000');
                $lay_woocommerce_sidecart_button_padding_top_bottom = get_theme_mod('lay_woocommerce_sidecart_button_padding_top_bottom', '17');
                // $lay_woocommerce_sidecart_button_padding_left_right = get_theme_mod('lay_woocommerce_sidecart_button_padding_left_right', '20');
                $lay_woocommerce_sidecart_button_text_color = get_theme_mod('lay_woocommerce_sidecart_button_text_color', '#fff');

                $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{ color:'.$lay_woocommerce_sidecart_button_text_color.'; }';
                $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn:hover{ color:'.get_theme_mod('lay_woocommerce_sidecart_button_mouseover_text_color', '#fff').'; }';
                $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{ padding-top:'.$lay_woocommerce_sidecart_button_padding_top_bottom.'px; padding-bottom:'.$lay_woocommerce_sidecart_button_padding_top_bottom.'px; }';
                // $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{ padding-left:'.$lay_woocommerce_sidecart_button_padding_left_right.'px; padding-right:'.$lay_woocommerce_sidecart_button_padding_left_right.'px; }';
                $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{ border-radius:'.get_theme_mod('lay_woocommerce_sidecart_button_border_radius', 0).'px }';

                $button_border_width = get_theme_mod('lay_woocommerce_sidecart_button_border_width', '0');

                switch($button_style){
                    case 'filled':
                        $sharedStyles .= '
                        .xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{
                            background-color: '.$button_color.';
                            border-bottom: none!important;
                            border: none!important;
                        }
                        .xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn:hover{
                            background-color: '.$button_mouseover_color.';
                        }
                        ';
                    break;
                    case 'outlines':
                        $sharedStyles .= '
                        .xoo-wsc-container .xoo-wsc-ft-buttons-cont .xoo-wsc-ft-btn{
                            border-color: '.$button_color.';
                            border-bottom: '.$button_border_width.'px solid '.$button_color.'!important;
                            border-width: '.$button_border_width.'px;
                            border-style: solid;
                            background-color: transparent;
                        }
                        .xoo-wsc-container .xoo-wsc-ft-buttons-cont .button:hover{
                            border-color: '.$button_mouseover_color.';
                            border-bottom-color: '.$button_mouseover_color.'!important;
                            background-color: transparent;
                        }
                        ';
                    break;
                }

                if( get_theme_mod('lay_woocommerce_sidecart_animate_mouseover_button_color', 0) == true ) {
                    $sharedStyles .= 'span.xoo-wsc-footer-txt{ display:none; }';
                } else {
                    $sharedStyles .= '.xoo-wsc-container .xoo-wsc-ft-buttons-cont .button{ transition: none; }';
                }

                if( get_theme_mod('lay_woocommerce_sidecart_hide_footer_text', 0) == true ) {
                    $sharedStyles .= 'span.xoo-wsc-footer-txt{ display: none!important; }';
                }
            }

            // my account
            $myaccount_textformat = get_theme_mod('lay_woocommerce_checkout_product_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($myaccount_textformat, '.woocommerce-MyAccount-content, .woocommerce-MyAccount-content button, .woocommerce-MyAccount-navigation', array('spaces' => false));
            // login page
            $login_textformat = get_theme_mod('lay_woocommerce_checkout_product_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($myaccount_textformat, '.woocommerce-account #lay-woocommerce .woocommerce,
            .woocommerce-account #lay-woocommerce .woocommerce .woocommerce-form-login,
            .woocommerce-account #lay-woocommerce .woocommerce .woocommerce-form-login label,
            .woocommerce-account #lay-woocommerce .woocommerce h2', array('spaces' => false));

            // checkout
            $checkout_textformat = get_theme_mod('lay_woocommerce_checkout_product_textformat', 'Shop_Big');
            // also need to target select, because germanized woocommerce plugin can give a title (mr, mrs, etc) to the checkout that is a selectbox
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($checkout_textformat, '#lay-woocommerce .checkout.woocommerce-checkout input, #lay-woocommerce .checkout.woocommerce-checkout select, #lay-woocommerce .checkout.woocommerce-checkout .lay-woocommerce-checkout-column-1 .form-row label, #lay-woocommerce .checkout.woocommerce-checkout textarea, #lay-woocommerce .woocommerce-MyAccount-content input', array('spaces' => false));

            $lay_woocommerce_checkout_headlines_textformat = get_theme_mod('lay_woocommerce_checkout_headlines_textformat', $checkout_textformat);
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($lay_woocommerce_checkout_headlines_textformat, '#lay-woocommerce .checkout.woocommerce-checkout h3', array('spaces' => false));
            // ship-to-different-address
            $lay_woocommerce_checkout_ship_to_different_address_textformat = get_theme_mod('lay_woocommerce_checkout_ship_to_different_address_textformat', $checkout_textformat);
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($lay_woocommerce_checkout_ship_to_different_address_textformat, '#lay-woocommerce .checkout.woocommerce-checkout h3#ship-to-different-address', array('spaces' => false));
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($checkout_textformat, '#lay-woocommerce .checkout.woocommerce-checkout .create-account span', array('spaces' => false));

            $cart_review_textformat = get_theme_mod('lay_woocommerce_checkout_order_review', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($cart_review_textformat, '#lay-woocommerce #order_review th, #lay-woocommerce #order_review td', array('spaces' => false));

            $cart_payment_textformat = get_theme_mod('lay_woocommerce_checkout_payment', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($cart_payment_textformat, '#lay-woocommerce #payment', array('spaces' => false));

            $lay_woocommerce_checkout_additional_fields_gap = get_theme_mod('lay_woocommerce_checkout_additional_fields_gap', 30);
            $sharedStyles .= '.woocommerce-additional-fields{ gap: '.$lay_woocommerce_checkout_additional_fields_gap.'px; }';

            $lay_woocommerce_checkout_coupon_gap = get_theme_mod('lay_woocommerce_checkout_coupon_gap', 0);
            $sharedStyles .= '.lay-woocommerce-coupon-checkout #coupon_code{ margin-right: '.$lay_woocommerce_checkout_coupon_gap.'px; }';
	
            // make text small: "Save payment information to my account for future purchases."
            $payments_new_payment_method = 'Shop_Small';
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($payments_new_payment_method, '#lay-woocommerce .checkout.woocommerce-checkout label[for="wc-woocommerce_payments-new-payment-method"]', array('spaces' => false));

            // inputs bg color

            $checkout_inputs = '.lay-select-search-options, .checkout input.input-text, .woocommerce form.checkout textarea, .checkout .select2-container--default .select2-selection--single, .checkout .select2-dropdown, .checkout .select2-container--default .select2-search--dropdown .select2-search__field, .woocommerce-MyAccount-content input.input-text, .woocommerce-form-login input.input-text';

            $lay_woocommerce_checkout_input_bg_color = get_theme_mod('lay_woocommerce_checkout_input_bg_color', '#fff');
            $sharedStyles .= CSS_Output::generate_css($checkout_inputs, 'background-color', 'lay_woocommerce_checkout_input_bg_color', $lay_woocommerce_checkout_input_bg_color);

            $lay_woocommerce_checkout_input_show_borders = get_theme_mod('lay_woocommerce_checkout_input_show_borders', 1) == 1 ? true : false;
            if($lay_woocommerce_checkout_input_show_borders == false){
                $sharedStyles .= $checkout_inputs.'{ border: none!important; }';
            } else {
                $lay_woocommerce_checkout_input_border_color = get_theme_mod('lay_woocommerce_checkout_input_border_color', '#000');
                // $sharedStyles .= CSS_Output::generate_css($checkout_inputs, 'border', 'lay_woocommerce_checkout_input_border_color', '1px solid '.$lay_woocommerce_checkout_input_border_color, '', '!important');
                $sharedStyles .= $checkout_inputs.'{ border: 1px solid '.$lay_woocommerce_checkout_input_border_color.'!important; }';
            }

            $lay_woocommerce_checkout_input_placeholder_text_color = get_theme_mod('lay_woocommerce_checkout_input_placeholder_text_color', '#868686');
            $lay_woocommerce_checkout_use_placeholder_instead_of_labels = get_theme_mod('lay_woocommerce_checkout_use_placeholder_instead_of_labels', '' ) == 1;
            $sharedStyles .= '
            #lay-woocommerce .checkout.woocommerce-checkout textarea::placeholder,
            #lay-woocommerce .select2-selection__placeholder,
            #lay-woocommerce .checkout.woocommerce-checkout input#order_comments.input-text::placeholder,
            #lay-woocommerce .checkout.woocommerce-checkout input#coupon_code.input-text::placeholder {
                color: '.$lay_woocommerce_checkout_input_placeholder_text_color.';
            }';
            if ( $lay_woocommerce_checkout_use_placeholder_instead_of_labels ) {
                $sharedStyles .= '
                body.lay-checkout-use-placeholders #lay-woocommerce .checkout.woocommerce-checkout input.input-text::placeholder {
                    color: '.$lay_woocommerce_checkout_input_placeholder_text_color.';
                }';
            }
            $sharedStyles .= '
            .woocommerce #customer_details .form-row label {
                color: '.$lay_woocommerce_checkout_input_placeholder_text_color.' !important;
                abbr {
                    color: '.$lay_woocommerce_checkout_input_placeholder_text_color.' !important;
                }
            }
            .woocommerce form .form-row .required {
                color: '.$lay_woocommerce_checkout_input_placeholder_text_color.' !important;
            }';

            $lay_woocommerce_checkout_space_between_inputs = get_theme_mod('lay_woocommerce_checkout_space_between_inputs', 30);
            $sharedStyles .= '.woocommerce form .form-row, .woocommerce-additional-fields {
                margin-bottom: '.$lay_woocommerce_checkout_space_between_inputs.'px !important;
            }';
            $desktopAndTabletStyles .= '.woocommerce form .form-row-first, .woocommerce form .form-row-last, .woocommerce-page form .form-row-first, .woocommerce-page form .form-row-last {
                width: calc(50% - '.($lay_woocommerce_checkout_space_between_inputs / 2).'px) !important;
            }';

            $lay_woocommerce_checkout_space_under_shipping_address = get_theme_mod('lay_woocommerce_checkout_space_under_shipping_address', 30);
            $sharedStyles .= '.shipping_address {
                margin-bottom: '.$lay_woocommerce_checkout_space_under_shipping_address.'px !important;
            }';
            // normal cart
            $bordercolor = get_theme_mod('lay_woocommerce_cart_line_color', '#000');
            $showline = get_theme_mod('lay_woocommerce_cart_show_line', true);

            $cart_textformat = get_theme_mod('lay_woocommerce_cart_product_textformat', 'Shop_Cart');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($cart_textformat, '.lay-woocommerce-cart-inner', array('spaces' => false));

            $cart_collaterals_textformat = get_theme_mod('lay_woocommerce_cart_collaterals_textformat', 'Shop_Big');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($cart_collaterals_textformat, '.cart-collaterals, #coupon_code', array('spaces' => false));
            if($showline){
                $sharedStyles .= 
                '#lay-woocommerce .woocommerce-cart-form__cart-item,
                .woocommerce .lay-woocommerce-cart-totals-inner table{
                    border-color: '.$bordercolor.';
                }
                .cart-collaterals .actions{
                    border-color: '.$bordercolor.';
                }';
            } else {
                $sharedStyles .= 
                '#lay-woocommerce .woocommerce-cart-form__cart-item,
                .woocommerce .lay-woocommerce-cart-totals-inner table{
                    border: none;
                    border-width: 0!important;
                }
                .cart-collaterals .actions{
                    border: none;
                    border-width: 0!important;
                }';
            }

            $lay_woocommerce_normal_cart_product_list_bg_color = get_theme_mod('lay_woocommerce_normal_cart_product_list_bg_color', '#fff');
            $sharedStyles .= CSS_Output::generate_css('#lay-woocommerce .cart.woocommerce-cart-form__contents', 'background-color', 'lay_woocommerce_normal_cart_product_list_bg_color', $lay_woocommerce_normal_cart_product_list_bg_color);
            
            $sharedStyles .= 
            '.cart-column-1{
                width: '.get_theme_mod('lay_woocommerce_cart_product_image_width', 120).'px;
            }
            #lay-woocommerce .woocommerce-cart-form__cart-item a.lay-woocommerce-cart-remove-item{
                color: '.get_theme_mod('lay_woocommerce_normal_cart_remove_item_color', '#aeaeae').';
            }';

            // order page, the page that appears after sth was bought
            $orderpage_thankyou_textformat = get_theme_mod('lay_woocommerce_orderpage_thankyou', 'Shop_Order_Received');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($orderpage_thankyou_textformat, '.woocommerce-thankyou-order-received', array('spaces' => false));

            $orderpage_titles_textformat = get_theme_mod('lay_woocommerce_orderpage_titles', 'Shop_Order_Received');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($orderpage_titles_textformat, '.woocommerce-order .woocommerce-column__title, .woocommerce-order .woocommerce-order-details__title, .woocommerce-order .wc-bacs-bank-details-heading, .woocommerce-order .wc-bacs-bank-details-account-name', array('spaces' => false));

            $orderpage_textformat = get_theme_mod('lay_woocommerce_orderpage', 'Shop_Order_Received');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($orderpage_textformat, '.woocommerce-order, .woocommerce-order h2', array('spaces' => false));
						
            // add to cart button element:
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector('Shop_Medium', '.col.type-addtocartbutton .woocommerce-Price-amount', array('spaces' => false));

            // productsgrid filter category
            // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
            $pgf_textformat = get_theme_mod('pgf_textformat', 'Default');
            if($pgf_textformat == ""){
                // if no textformat was selected for st, generate css based on the individual customizer controls
                $pgf_fontsize_mu = CSS_Output::get_mu('pgf_fontsize_mu', 'px');
                $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-size', 'pgf_fontsize', Customizer::$defaults['fontsize'],'', $pgf_fontsize_mu);
                $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-weight', 'pgf_fontweight', Customizer::$defaults['fontweight']);
                $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'letter-spacing', 'pgf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
                $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-family', 'pgf_fontfamily', Customizer::$defaults['fontfamily']);
            }
            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'text-align', 'pgf_align', 'left');
            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'color', 'pgf_color', Customizer::$defaults['color']);

            $d = get_theme_mod('pgf_color', Customizer::$defaults['color']);
            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter .lay-filter-active', 'color', 'pgf_active_color', $d);

            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-filter', 'margin-bottom', 'pgf_spacebelow', '20', '', 'px');
            $desktopAndTabletStyles .= CSS_Output::generate_css('.lay-productsgrid-filter-anchor', 'margin-right', 'pgf_spacebetween_2', '10', '', 'px');
            $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .lay-productsgrid-filter-anchor', 'margin-right', 'pgf_spacebetween_mobile', '10', '', 'px');
            // $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .lay-productsgrid-filter-anchor', 'margin-left', 'pgf_spacebetween_mobile', '10', '', 'px');

            $mobileStyles .= CSS_Output::generate_css('.mobile-not-one-line .lay-productsgrid-filter-anchor', 'margin', 'pgf_spacebetween_mobile', '10', '', 'px');

            $sharedStyles .= CSS_Output::generate_opacity_css('.lay-productsgrid-filter-anchor', 'pgf_opacity', 50);

            $sharedStyles .= CSS_Output::generate_opacity_css('.lay-productsgrid-filter-anchor.lay-filter-active', 'pgf_active_opacity', 100);
            $sharedStyles .= CSS_Output::generate_opacity_css('@media (hover) {.lay-productsgrid-filter-anchor:hover', 'pgf_mouseover_opacity', 100);
            // close media query
            $sharedStyles .= '}';
            $pgf_active_strokewidth = get_theme_mod('pgf_active_strokewidth', 0);
            if( $pgf_active_strokewidth > 0 ) {
                $sharedStyles .= '
                .lay-productsgrid-filter-anchor{
                    text-decoration-thickness:'.$pgf_active_strokewidth.'px;
                    text-decoration-color: transparent;
                    text-decoration-style: solid;
                    text-decoration-line: underline;
                    text-underline-offset: '.$defaultUnderlineOffset.$defaultUnderlineOffsetMu.';
                }
                .lay-productsgrid-filter-anchor.lay-filter-active{
                    text-decoration-color: inherit;
                }';
            }

            // tags filter for productsgrid
            // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
            $pgtf_textformat = get_theme_mod('pgtf_textformat', 'Default');
            if($pgtf_textformat == ""){
                // if no textformat was selected for st, generate css based on the individual customizer controls
                $pgtf_fontsize_mu = CSS_Output::get_mu('pgtf_fontsize_mu', 'px');
                $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'font-size', 'pgtf_fontsize', Customizer::$defaults['fontsize'],'', $pgtf_fontsize_mu);
                $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'font-weight', 'pgtf_fontweight', Customizer::$defaults['fontweight']);
                $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'letter-spacing', 'pgtf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
                $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'font-family', 'pgtf_fontfamily', Customizer::$defaults['fontfamily']);
            }
            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'text-align', 'pgtf_align', 'left');
            $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'color', 'pgtf_color', Customizer::$defaults['color']);

            $sharedStyles .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'margin-bottom', 'pgtf_spacebelow', '20', '', 'px');
            $desktopAndTabletStyles .= CSS_Output::generate_css('.ptag-bubble', 'margin', 'pgtf_spacebetween', '10', '', 'px');
            
            $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .ptag-bubble:not(.lay-filter-hidden)', 'margin-right', 'pgtf_spacebetween_mobile', '10', '', 'px');
            $mobileStyles .= CSS_Output::generate_css('.mobile-not-one-line .ptag-bubble', 'margin', 'pgtf_spacebetween_mobile', '10', '', 'px');

            $desktopAndTabletStyles .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'margin-left', 'pgtf_spacebetween', '10', '-', 'px');
            $mobileStyles .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'margin-left', 'pgtf_spacebetween_mobile', '10', '-', 'px');

            $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'background-color', 'pgtf_bubble_color', '#eeeeee');
            $sharedStyles .= CSS_Output::generate_css('.ptag-bubble', 'border-radius', 'pgtf_bubble_border_radius', '100', '', 'px');
            $sharedStyles .= CSS_Output::generate_css('@media (hover:hover) {.ptag-bubble:hover', 'background-color', 'pgtf_bubble_active_color', '#d0d0d0');
            // close media query
            $sharedStyles .= '}';
            $sharedStyles .= CSS_Output::generate_css('.ptag-bubble.lay-tag-active', 'background-color', 'pgtf_bubble_active_color', '#d0d0d0');

            // related
            $lay_woocommerce_singleproduct_show_related_products = get_theme_mod('lay_woocommerce_singleproduct_show_related_products', 1);
            if($lay_woocommerce_singleproduct_show_related_products == 0) {
                $sharedStyles .= '.related.products{ display: none; }';
            }
        }
        // END WOOCOMMERCE

        // project title visibility
        $ptVisibility = get_theme_mod('pt_visibility', 'always-show');

        //project tags
        $sharedStyles .= CSS_Output::generate_visibility_css('.thumb .thumbnail-tags', 'ptags_visibility');
        // project tags should also only show up on mouseover if title should just show up on mouseover
        if($ptVisibility != 'show-on-mouseover'){
            $sharedStyles .= CSS_Output::generate_opacity_css('.thumb .thumbnail-tags', 'ptags_opacity', 100);
        }
        $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'margin-top', 'ptags_spacetop', '0','', 'px');
        $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'margin-bottom', 'ptags_spacebottom', '0','', 'px');
        if( get_theme_mod("ptags_textformat", "_Default") == "" ){
            $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'font-weight', 'ptags_fontweight', Customizer::$defaults['fontweight'], '', '');
            $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'letter-spacing', 'ptags_letterspacing', Customizer::$defaults['letterspacing'],'', 'em');
            $ptags_fontsize_mu = CSS_Output::get_mu('ptags_fontsize_mu', 'px');
            $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'font-size', 'ptags_fontsize', Customizer::$defaults['fontsize'],'', $ptags_fontsize_mu);
            $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'font-family', 'ptags_fontfamily', Customizer::$defaults['fontfamily']);
            $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'line-height', 'ptags_lineheight', Customizer::$defaults['lineheight'],'', '');
        }
        $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'text-align', 'ptags_align', 'left');
        $sharedStyles .= CSS_Output::generate_css('.thumb .thumbnail-tags', 'color', 'ptags_color', Customizer::$defaults['color']);

        // project title
        $sharedStyles .= CSS_Output::generate_mouseover_animate_visibility_css( '.thumb .title, .thumb .thumbnail-tags', 'pt_animate_visibility' );

        $sharedStyles .= CSS_Output::generate_visibility_css('.title', 'pt_visibility');
        if( $ptVisibility == 'show-on-mouseover' ) {
            $sharedStyles .= '.thumb .thumbnail-tags{opacity:0;}';
        }
        // project tags should also only show up on mouseover if title should just show up on mouseover
        $ptagsVisibility = get_theme_mod('ptags_visibility', 'always-show');
        if( $ptagsVisibility == 'always-show' && $ptVisibility == 'show-on-mouseover' ) {
            $sharedStyles .= CSS_Output::generate_opacity_css('.no-touchdevice .thumb:hover .thumbnail-tags, .touchdevice .thumb.hover .thumbnail-tags', 'ptags_opacity', 100);
        }

        if($ptVisibility == 'show-on-mouseover'){
            $sharedStyles .= CSS_Output::generate_opacity_css('.no-touchdevice .thumb:hover .title, .touchdevice .thumb.hover .title', 'pt_opacity', 100);  
        }else{
            $sharedStyles .= CSS_Output::generate_opacity_css('.title', 'pt_opacity', 100);
        }

        if( get_theme_mod("pt_textformat", "_Default") == "" ){
            $sharedStyles .= CSS_Output::generate_css('.title', 'font-weight', 'pt_fontweight', Customizer::$defaults['fontweight'], '', '');
            $sharedStyles .= CSS_Output::generate_css('.title', 'letter-spacing', 'pt_letterspacing', Customizer::$defaults['letterspacing'],'', 'em');
            $pt_fontsize_mu = CSS_Output::get_mu('pt_fontsize_mu', 'px');
            $sharedStyles .= CSS_Output::generate_css('.title', 'font-size', 'pt_fontsize', Customizer::$defaults['fontsize'],'', $pt_fontsize_mu);
            $sharedStyles .= CSS_Output::generate_css('.title', 'font-family', 'pt_fontfamily', Customizer::$defaults['fontfamily']);
            $sharedStyles .= CSS_Output::generate_css('.title', 'line-height', 'pt_lineheight', Customizer::$defaults['lineheight'],'', '');
        }
        $sharedStyles .= CSS_Output::generate_css('.title', 'text-align', 'pt_align', 'left');
        $sharedStyles .= CSS_Output::generate_css('.title', 'color', 'pt_color', Customizer::$defaults['color']);

        $pt_spacetop_mu = get_theme_mod('pt_spacetop_mu', 'px'); 
        $sharedStyles .= CSS_Output::generate_css('.below-image .title', 'margin-top', 'pt_spacetop', '5','', $pt_spacetop_mu);
        $sharedStyles .= CSS_Output::generate_css('.above-image .title', 'margin-bottom', 'pt_spacetop', '5','', $pt_spacetop_mu);

        $sharedStyles .= CSS_Output::pt_generate_position_css('.titlewrap-on-image');


        //project description
        $sharedStyles .= CSS_Output::generate_mouseover_animate_visibility_css( '.thumb .descr', 'pd_animate_visibility' );

        $sharedStyles .= CSS_Output::generate_visibility_css('.thumb .descr', 'pd_visibility');
        $pdVisibility = get_theme_mod('pd_visibility', 'always-show');
        if($pdVisibility == 'show-on-mouseover'){
            $sharedStyles .= CSS_Output::generate_opacity_css('.no-touchdevice .thumb:hover .descr, .touchdevice .thumb.hover .descr', 'pd_opacity', 100);
        }else{
            $sharedStyles .= CSS_Output::generate_opacity_css('.thumb .descr', 'pd_opacity', 100);
        }

        $sharedStyles .= CSS_Output::generate_css('.thumb .descr', 'margin-top', 'pd_spacetop', '0','', 'px');
        $sharedStyles .= CSS_Output::generate_css('.thumb .descr', 'margin-bottom', 'pd_spacebottom', '0','', 'px');

        // project description force textformat
        if ( get_theme_mod( 'pd_force_textformat', false ) ) {
            $pd_force_format = get_theme_mod( 'pd_force_textformat_select', 'Default' );
            if ( $pd_force_format != '' ) {
                $sharedStyles .= FormatsManager::get_textformat_css_for_selector( $pd_force_format, '.thumb .descr', array('spaces' => false, 'important' => true) );
            }
        }
        // project description force color
        if ( get_theme_mod( 'pd_force_color', false ) ) {
            $pd_force_color = get_theme_mod( 'pd_force_color_value', '#000000' );
            if ( $pd_force_color != '' ) {
                $sharedStyles .= '.thumb .descr *{ color: ' . $pd_force_color . ' !important; }';
            }
        }

        // project thumbnail gradient overlay
        $sharedStyles .= CSS_Output::generate_pt_gradient_overlay_css();

        // pd position
        $pd_position = get_theme_mod('pd_position', 'below-image');
        $pt_position = get_theme_mod('pt_position', 'below-image');
        // when pt position is below or above and pd pos is on-image, do center css
        // otherwise, the pt alignment defines where pt and pd are (can be on-image-top-left, or on-image-top-right, on-image-bottom-left etc)
        if( $pd_position == 'on-image' && strpos($pt_position, 'on-image') === false ){
            $sharedStyles .= 
            '.titlewrap-on-image{
                top: 50%;
                left: 50%;
                -webkit-transform: translate(-50%,-50%);
                -moz-transform: translate(-50%,-50%);
                -ms-transform: translate(-50%,-50%);
                -o-transform: translate(-50%,-50%);
                transform: translate(-50%,-50%);
            }';
        }

        $pt_follow_mouse = get_theme_mod('pt_follow_mouse', '');
        if($pt_follow_mouse == 1){
            $sharedStyles .= 'body.no-touchdevice .col.type-project .title{
                position: absolute;
                display: none;
                z-index: 99;
                transition: none!important;
                color: white!important;
                /*mix-blend-mode: difference;*/
            }';
        }

        // project thumbnail mouseover
        $mod = get_theme_mod('fi_mo_show_color', '');
        if($mod == "1"){
            $sharedStyles .= CSS_Output::generate_css('.thumb .ph span', 'background-color', 'fi_mo_background_color', '#fff', '', '');
            if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_on_tap_alt' ) {
                $sharedStyles .= CSS_Output::generate_featured_image_opacity_css('.thumb:hover .ph span, #search-results-region .thumb:hover .ph span', 'fi_mo_color_opacity', 50);
            } else {
                $sharedStyles .= CSS_Output::generate_featured_image_opacity_css('.no-touchdevice .thumb:hover .ph span, .touchdevice .thumb.hover .ph span, .no-touchdevice #search-results-region .thumb:hover .ph span, .touchdevice #search-results-region .thumb.hover .ph span', 'fi_mo_color_opacity', 50);
            }
            $sharedStyles .= CSS_Output::generate_fi_mouseover_animate_bgcolor_css();
        }

        $mo_brightness = get_theme_mod('fi_mo_change_brightness', '');
        $mo_grayscale = get_theme_mod('fi_mo_change_grayscale', '');
        if ($mo_brightness == '1' || $mo_grayscale == '1') {
            if ( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_on_tap_alt' ) {
                $sharedStyles .= CSS_Output::generate_fi_mouseover_brightness_grayscale_css('.thumb:hover .ph');
            } else {
                $sharedStyles .= CSS_Output::generate_fi_mouseover_brightness_grayscale_css('.no-touchdevice .thumb:hover .ph, .touchdevice .thumb.hover .ph');
            }
            $sharedStyles .= CSS_Output::generate_fi_mouseover_animate_brightness_css();
        }

        // generate_fi_mouseover_blur will also include brightness and grayscale filters if on
        $sharedStyles .= CSS_Output::generate_fi_mouseover_blur();
        $sharedStyles .= CSS_Output::generate_fi_mouseover_animate_blur();
        $sharedStyles .= CSS_Output::generate_fi_mouseover_zoom_css();

        // project arrows
        $pa_active = get_option('misc_options_show_project_arrows', '');

        if($pa_active == "on" && !MiscOptions::$locked){

            $pa_type = get_theme_mod('pa_type', 'icon');
            switch($pa_type){
                case 'icon':
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'color', 'pa_icon_color', '#000', '', '');
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'font-size', 'pa_icon_size', '20', '', 'px');
                break;
                case 'text':
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'font-family', 'pa_fontfamily', Customizer::$defaults['fontfamily']);
                    $pa_fontsize_mu = CSS_Output::get_mu('pa_fontsize_mu', 'px');
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'font-size', 'pa_fontsize', Customizer::$defaults['fontsize'],'', $pa_fontsize_mu);
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'font-weight', 'pa_fontweight', Customizer::$defaults['fontweight']);
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'letter-spacing', 'pa_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'color', 'pa_color', Customizer::$defaults['color'], '', '');
                break;
                case 'project-thumbnails':
                case 'custom-image':
                    $pa_width_mu = CSS_Output::get_mu('pa_width_mu', '%');
                    $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'width', 'pa_width', '10', '', $pa_width_mu);
                break;
            }

            $sharedStyles .= CSS_Output::generate_opacity_css('.project-arrow', 'pa_opacity', 100);
            $sharedStyles .= CSS_Output::generate_opacity_css('.no-touchdevice .project-arrow:hover', 'pa_mouseover_opacity', 100);

            $pa_spacetopbottom_mu = CSS_Output::get_mu('pa_spacetopbottom_mu', 'px');
            $sharedStyles .= CSS_Output::generate_css('.project-arrow.top', 'top', 'pa_spacetopbottom', '20', '', $pa_spacetopbottom_mu);
            $sharedStyles .= CSS_Output::generate_css('.project-arrow.bottom', 'bottom', 'pa_spacetopbottom', '20', '', $pa_spacetopbottom_mu);

            $pa_spaceleftright_mu = CSS_Output::get_mu('pa_spaceleftright_mu', 'px');
            $sharedStyles .= CSS_Output::generate_css('.project-arrow.pa-prev', 'left', 'pa_spaceleftright', '20', '', $pa_spaceleftright_mu);
            $sharedStyles .= CSS_Output::generate_css('.project-arrow.pa-next', 'right', 'pa_spaceleftright', '20', '', $pa_spaceleftright_mu);

            $sharedStyles .= CSS_Output::generate_css('.no-touchdevice .project-arrow:hover', 'color', 'pa_mouseover_color', '#000000', '', '');

            $sharedStyles .= CSS_Output::generate_css('.project-arrow', 'padding', 'pa_padding', '10', '', 'px');

            $mobileStyles .= CSS_Output::generate_hide_css('.project-arrow', 'pa_hide_on_phone');
        }

        // intro in intro.php

        // site title
        
		// if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$st_textformat = get_theme_mod('st_textformat', 'Default');
		if($st_textformat == ""){
			// if no textformat was selected for st, generate css based on the individual customizer controls
			$st_fontsize_mu = CSS_Output::get_mu('st_fontsize_mu', 'px');
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'font-size', 'st_fontsize', Customizer::$defaults['fontsize'],'', $st_fontsize_mu);
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'font-weight', 'st_fontweight', Customizer::$defaults['fontweight']);
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'letter-spacing', 'st_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'font-family', 'st_fontfamily', Customizer::$defaults['fontfamily']);
		}
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'text-align', 'st_align', 'left');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle-txt-inner', 'color', 'st_color', Customizer::$defaults['color']);

        $st_spacetop_mu = CSS_Output::get_mu('st_spacetop_mu', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle', 'top', 'st_spacetop', Customizer::$defaults['st_spacetop'], '', $st_spacetop_mu);

        // !woocommerce! sticky top nav! needs same margin-top as top value, this way sticky will work properly
        // $desktopAndTabletStyles .= CSS_Output::generate_css('body.lay-woocommerce-show-store-notice .sitetitle.position-top', 'margin-top', 'st_spacetop', Customizer::$defaults['st_spacetop'], '', $st_spacetop_mu);

        $spaceleft_mu = CSS_Output::get_mu('st_spaceleft_mu', '%');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle', 'left', 'st_spaceleft', Customizer::$defaults['st_spaceleft'],'', $spaceleft_mu);

        $spaceright_mu = CSS_Output::get_mu('st_spaceright_mu', '%');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle', 'right', 'st_spaceright', Customizer::$defaults['st_spaceright'],'', $spaceright_mu);

        $spacebottom_mu = CSS_Output::get_mu('st_spacebottom_mu', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle', 'bottom', 'st_spacebottom', Customizer::$defaults['st_spacebottom'], '', $spacebottom_mu);

        $st_img_width_mu = CSS_Output::get_mu('st_img_width_mu', 'vw');
        if($st_img_width_mu == "%"){
            $st_img_width_mu = "vw";
        }
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle img', 'width', 'st_img_width', '20', '', $st_img_width_mu);

        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_opacity_css('.sitetitle', 'st_opacity', 100);
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_position_css('.sitetitle', 'st_position', 'top-left');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_is_fixed_css('.sitetitle', 'st_isfixed');

        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_hide_css('.sitetitle', 'st_hide', true);
        // $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle.txt .sitetitle-txt-inner span', 'text-decoration-thickness', 'st_underline_strokewidth', Customizer::$defaults['underline_width'],'', 'px');
        $strokewidth = get_theme_mod('st_underline_strokewidth', Customizer::$defaults['underline_width']);
        if( $strokewidth > 0 ) {
            $phoneMenuStylesAboveBreakpoint .= '.sitetitle.txt .sitetitle-txt-inner span{ 
                text-decoration-thickness:'.$strokewidth.'px;
                text-decoration-style: solid;
                text-decoration-line: underline;
            }';
        }

		// need to apply image alignment to .sitetitle wrapper, because img is display inline, this will not affect text-align of possible multiline tagline
		$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.sitetitle.img', 'text-align', 'st_image_alignment_in_relation_to_tagline', 'left', '', '');

        // site title mouseover
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice .sitetitle.txt:hover .sitetitle-txt-inner span, .no-touchdevice .sitetitle:hover .tagline', 'color', 'stmouseover_color', Customizer::$defaults['color']);
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_opacity_css('.no-touchdevice .sitetitle:hover', 'stmouseover_opacity', 100);

        if( get_theme_mod('stmouseover_fontweight', 'default') != 'default' ) {
            $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice .sitetitle:hover span', 'font-weight', 'stmouseover_fontweight', '400');
        }

        $strokewidth = get_theme_mod('stmouseover_underline_strokewidth', Customizer::$defaults['underline_width']);
        if( $strokewidth > 0 ) {

			$defaultUnderlineOffset = get_theme_mod('link_underline_offset', 3);
			$defaultUnderlineOffsetMu = get_theme_mod('link_underline_offset_mu', 'px');

            $phoneMenuStylesAboveBreakpoint .= '.no-touchdevice .sitetitle.txt:hover .sitetitle-txt-inner span{ 
                text-decoration-thickness:'.$strokewidth.'px;
                text-decoration-style: solid;
                text-decoration-line: underline;
            }';
			$stmouseover_underline_offset = get_theme_mod('stmouseover_underline_offset', $defaultUnderlineOffset);
			$phoneMenuStylesAboveBreakpoint .= '.no-touchdevice .sitetitle.txt:hover .sitetitle-txt-inner span{ 
				text-underline-offset: '.$stmouseover_underline_offset.$defaultUnderlineOffsetMu.';
			}';
        } else {
            $phoneMenuStylesAboveBreakpoint .= '.no-touchdevice .sitetitle.txt:hover .sitetitle-txt-inner span{ 
                text-decoration: none;
            }';
        }

        // tagline
		$tagline_textformat = get_theme_mod('tagline_textformat', 'Default');
		if($tagline_textformat == ""){
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'font-family', 'tagline_fontfamily', Customizer::$defaults['fontfamily']);

			$tagline_fontsize_mu = CSS_Output::get_mu('tagline_fontsize_mu', 'px');
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'font-size', 'tagline_fontsize', Customizer::$defaults['fontsize'],'', $tagline_fontsize_mu);

			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'font-weight', 'tagline_fontweight', Customizer::$defaults['fontweight']);
			$phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'letter-spacing', 'tagline_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
		}
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'text-align', 'tagline_align', 'left');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'color', 'tagline_color', Customizer::$defaults['color']);
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.tagline', 'margin-top', 'tagline_spacetop', 5, '', 'px');

        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_opacity_css('.tagline', 'tagline_opacity', 100);

        // navigation / menu
        $menuStyles = &$phoneMenuStylesAboveBreakpoint;
        // this setting now doesn't show the desktop menu "primary menu" but it shows the "mobile-nav" in a desktop menu style
        $use_desktop_menu_as_mobile_menu = (get_theme_mod('mobile_menu_style', 'style_1') == 'style_desktop_menu' || get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style') ? 1 : 0;
        if($use_desktop_menu_as_mobile_menu == 1){
            $menuStyles = &$sharedStyles;
        }

        // get css of menus
        foreach( LayMenuCustomizerManager::$menu_customizer_instances as $menu ){
            $menu->get_css($phoneMenuStylesAboveBreakpoint, $phoneMenuStylesUnderBreakpoint, $menuStyles, $sharedStyles, $use_desktop_menu_as_mobile_menu);
        }

        // navigation active
        // added .current_page_item for woocommerce pages, like "Shop"
        // .current-menu-parent, .current-menu-ancestor is needed for submenu parent menu point 
        $active_menupoint_selectors = ['.current-menu-item', '.current_page_item', '.current-menu-parent', '.current-menu-ancestor', '.current-lang'];
        for( $i = 0; $i < count($active_menupoint_selectors); $i++ ) {
            $active_menupoint_target = $active_menupoint_selectors[$i];
            $menuStyles .= CSS_Output::generate_css('nav.laynav '.$active_menupoint_target.'>a', 'color', 'navactive_color', Customizer::$defaults['color']);
            $menuStyles .= CSS_Output::generate_css('nav.laynav '.$active_menupoint_target.'>a', 'font-weight', 'navactive_fontweight', Customizer::$defaults['fontweight']);
            $strokewidth = get_theme_mod('navactive_underline_strokewidth', Customizer::$defaults['underline_width']);
            if( $strokewidth > 0 ) {
                $menuStyles .= CSS_Output::generate_css('nav.laynav '.$active_menupoint_target.'>a:not(.lay-menu-button) span', 'text-decoration-color', 'navactive_color', Customizer::$defaults['color'],'', '');
                $menuStyles .= CSS_Output::generate_css('nav.laynav '.$active_menupoint_target.'>a:not(.lay-menu-button) span', 'text-decoration-thickness', 'navactive_underline_strokewidth', Customizer::$defaults['underline_width'],'', 'px');
                $menuStyles .= 'nav.laynav '.$active_menupoint_target.'>a:not(.lay-menu-button) span{
                    text-decoration-style: solid;
                    text-decoration-line: underline;
                }';
            } else {
                $menuStyles .= 'nav.laynav '.$active_menupoint_target.'>a:not(.lay-menu-button) span{
                    text-decoration: none;
                }';
            }
            $menuStyles .= CSS_Output::generate_opacity_css('nav.laynav '.$active_menupoint_target.'>a', 'navactive_opacity', 100);
        }
        // active style for polylang shortcode:
        // $lswitcher_shortcode = '.lay-polylang-switcher-shortcode';
        // $menuStyles .= CSS_Output::generate_css($lswitcher_shortcode.'>li.current-lang>a', 'color', 'navactive_color', Customizer::$defaults['color'], '', '!important');
        // $menuStyles .= CSS_Output::generate_css($lswitcher_shortcode.'>li.current-lang>a', 'font-weight', 'navactive_fontweight', Customizer::$defaults['fontweight'], '', '!important');
        // $strokewidth = get_theme_mod('navactive_underline_strokewidth', Customizer::$defaults['underline_width']);
        // if( $strokewidth > 0 ) {
        //     $menuStyles .= CSS_Output::generate_css($lswitcher_shortcode.'>li.current-lang>a', 'text-decoration-color', 'navactive_color', Customizer::$defaults['color'],'', '!important');
        //     $menuStyles .= CSS_Output::generate_css($lswitcher_shortcode.'>li.current-lang>a', 'text-decoration-thickness', 'navactive_underline_strokewidth', Customizer::$defaults['underline_width'],'', 'px!important');
        //     $menuStyles .= $lswitcher_shortcode.'>li.current-lang>a{
        //         text-decoration-style: solid!important;
        //         text-decoration-line: underline!important;
        //     }';
        // } else {
        //     $menuStyles .= $lswitcher_shortcode.'>li.current-lang>a{
        //         text-decoration: none!important;
        //     }';
        // }

        // what is this line, we dont need this no?
        $menuStyles .= CSS_Output::generate_opacity_css($active_menupoint_target.'>a', 'navactive_opacity', 100);

        // navigation mouseover
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice nav.laynav a:hover', 'color', 'navmouseover_color', Customizer::$defaults['color']);
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice nav.laynav a:not(.lay-menu-button):hover span', 'text-decoration-color', 'navmouseover_color', Customizer::$defaults['color']);
        if( get_theme_mod('navmouseover_fontweight', 'default') != 'default' ) {
            $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice nav.laynav a:hover', 'font-weight', 'navmouseover_fontweight', Customizer::$defaults['fontweight']);
        }


        $navmouseover_underline_strokewidth = get_theme_mod('navmouseover_underline_strokewidth', Customizer::$defaults['underline_width']);
        
        if($navmouseover_underline_strokewidth > 0){
            $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.no-touchdevice nav.laynav a:not(.lay-menu-button):hover span', 'text-decoration-thickness', 'navmouseover_underline_strokewidth', Customizer::$defaults['underline_width'],'', 'px');
            $phoneMenuStylesAboveBreakpoint .= '.no-touchdevice nav.laynav a:not(.lay-menu-button):hover span{ 
                text-decoration-style: solid;
                text-decoration-line: underline;
             }';
        } else {
            $phoneMenuStylesAboveBreakpoint .= '.no-touchdevice nav.laynav a:not(.lay-menu-button):hover span{ 
                text-decoration: none;
             }';
        }

        if( get_theme_mod('menu_submenu_use_button_style', false) ) {
            $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav .sub-menu .menu-item > a > span{
                text-decoration: none!important;
            }';
        }
        
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_opacity_css('.no-touchdevice nav.laynav a:hover', 'navmouseover_opacity', 100);

        // background

        // #rows-region and .cover-region only exist if there is a cover
        // need to set bg color and bg image also for .lay-content.hascover #grid and #footer-region to make cover feature work right
        // otherwise, the div below the first 100vh row will not overlay that row properly, because its bg would be transparent
        // a grid's background color needs to be able to overwrite the global background image and background color set here
        $background_targets = 'body, .hascover #footer-region, .cover-content, .cover-region';

        $sharedStyles .= CSS_Output::generate_css($background_targets, 'background-color', 'bg_color', '#ffffff');

        $bg_image = get_theme_mod('bg_image', "");
        $bg_video = get_theme_mod('bg_video', "");

        if($bg_image != "" && $bg_video == ""){
            $desktopAndTabletStyles .= $background_targets.'{ background-image:url('.$bg_image.'); }';
        }

        $bg_position = get_theme_mod('bg_position', 'standard');
        if($bg_position != 'standard'){
            switch ($bg_position) {
                case 'stretch':
                    $desktopAndTabletStyles .= $background_targets.'{ background-size:cover; background-attachment:fixed; background-repeat:no-repeat; background-position:center center;}';
                break;
                case 'center':
                    $desktopAndTabletStyles .= $background_targets.'{ background-repeat:no-repeat; background-attachment:fixed; background-position:center;}';
                break;
            }
        }

		// mobile background image
		$mobile_bg_image = get_theme_mod('mobile_bg_image', "");
        if($mobile_bg_image != ""){
            $mobileStyles .= $background_targets.'{ background-image:url('.$mobile_bg_image.'); }';
        }

        $mobile_bg_position = get_theme_mod('mobile_bg_position', 'standard');
        if($mobile_bg_position != 'standard'){
            switch ($mobile_bg_position) {
                case 'stretch':
                    $mobileStyles .= $background_targets.'{ background-size:cover; background-attachment:fixed; background-repeat:no-repeat; background-position:center center;}';
                break;
                case 'center':
                    $mobileStyles .= $background_targets.'{ background-repeat:no-repeat; background-attachment:fixed; background-position:center;}';
                break;
            }
        }

        // submenu
        $submenu_space_around = get_theme_mod('menu_submenu_spacearound', 10);
        $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav.arrangement-horizontal .sub-menu{ left:-'.$submenu_space_around.'px; padding:'.$submenu_space_around.'px; }';
        $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav.arrangement-vertical .sub-menu{ padding-left:'.$submenu_space_around.'px; padding-right:'.$submenu_space_around.'px; }';

        if( get_theme_mod('menu_submenu_show_on', 'hover') == 'always' ) {
            $phoneMenuStylesAboveBreakpoint .= '.menu-item-has-children>.sub-menu{ display:block!important; }';
        }

        // space between menu points
        $submenu_space_between_submenu_points = get_theme_mod('menu_submenu_space_between_submenu_points', 4);
        if( !get_theme_mod('menu_submenu_use_button_style', false) ) {
            // In non-button mode this spacing is created via padding-bottom on submenu <a>.
            $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav.arrangement-horizontal.submenu-type-vertical .menu-item-has-children .sub-menu li a{ padding-bottom: '.$submenu_space_between_submenu_points.'px; }';
        }
        // In horizontal submenu mode (one line), margin-right still defines spacing.
        $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav.arrangement-horizontal.submenu-type-horizontal .menu-item-has-children .sub-menu li{ margin-right: '.$submenu_space_between_submenu_points.'px; }';
        // In vertical submenu mode, this defines the gap between points (also used/overridden for button mode below).
        $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav.arrangement-vertical .menu-item-has-children .sub-menu li{ margin-bottom: '.$submenu_space_between_submenu_points.'px; }';

        // submenu spacetop/bottom
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-hover.position-top .menu-item-has-children:hover', 'padding-bottom', 'menu_submenu_spacetop', '0', '', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-click.position-top .menu-item-has-children.show-submenu-desktop', 'padding-bottom', 'menu_submenu_spacetop', '0', '', 'px');
        // show always
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-always.position-top .menu-item-has-children', 'padding-bottom', 'menu_submenu_spacetop', '0', '', 'px');

        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-hover.position-not-top .menu-item-has-children:hover', 'padding-top', 'menu_submenu_spacetop', '0', '', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-click.position-not-top .menu-item-has-children.show-submenu-desktop', 'padding-top', 'menu_submenu_spacetop', '0', '', 'px');
        // show always
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-horizontal.show-submenu-on-always.position-not-top .menu-item-has-children', 'padding-top', 'menu_submenu_spacetop', '0', '', 'px');

        // vertical
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-vertical .sub-menu', 'padding-top', 'menu_submenu_spacetop', '0', '', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.laynav.desktop-nav.arrangement-vertical .sub-menu', 'padding-bottom', 'menu_submenu_spacetop', '0', '', 'px');

        if( !get_theme_mod('menu_submenu_allow_textwrap', false) ) {
            $phoneMenuStylesAboveBreakpoint .= '.laynav.desktop-nav .sub-menu .menu-item a{white-space:nowrap!important;}';
        }

        if( get_theme_mod('menu_submenu_use_button_style', false) ) {
            $submenu_button_slug = get_theme_mod('menu_submenu_button_style_variant', 'laybutton1');
            if( !in_array($submenu_button_slug, array('laybutton1', 'laybutton2', 'laybutton3'), true) ) {
                $submenu_button_slug = 'laybutton1';
            }

            $submenu_button_selector = '.laynav.desktop-nav .sub-menu .menu-item > a';
            $submenu_button_hover_selector_base = '.no-touchdevice .laynav.desktop-nav .sub-menu .menu-item > a:hover';
            $submenu_button_hover_selector = $submenu_button_hover_selector_base;
            $submenu_button_active_selector = '.laynav.desktop-nav .sub-menu .menu-item.current-menu-item > a, .laynav.desktop-nav .sub-menu .menu-item.current_page_item > a, .laynav.desktop-nav .sub-menu .menu-item.current-menu-parent > a, .laynav.desktop-nav .sub-menu .menu-item.current-menu-ancestor > a, .laynav.desktop-nav .sub-menu .menu-item.current-lang > a';

            $base_bg_opacity = get_theme_mod($submenu_button_slug . '_background_opacity', 100);
            $base_bg = get_theme_mod($submenu_button_slug . '_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($submenu_button_slug . '_background_color', '#ffffff'), $base_bg_opacity);
            $hover_bg_opacity = get_theme_mod($submenu_button_slug . '_mo_background_opacity', 100);
            $hover_bg = get_theme_mod($submenu_button_slug . '_mo_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($submenu_button_slug . '_mo_background_color', '#ffffff'), $hover_bg_opacity);
            $active_bg_opacity = get_theme_mod($submenu_button_slug . '_active_background_opacity', 100);
            $active_bg = get_theme_mod($submenu_button_slug . '_active_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($submenu_button_slug . '_active_background_color', '#ffffff'), $active_bg_opacity);

            $use_hover_state = get_theme_mod($submenu_button_slug . '_use_mouseover', false) == true;
            $use_active_state = get_theme_mod($submenu_button_slug . '_use_active_state', false) == true;

            // If active state is enabled, hover must NOT override the active submenu point.
            if ($use_active_state) {
                $active_menu_item_classes = array(
                    'current-menu-item',
                    'current_page_item',
                    'current-menu-parent',
                    'current-menu-ancestor',
                    'current-lang',
                );
                $not_parts = '';
                foreach ($active_menu_item_classes as $cls) {
                    $not_parts .= ':not(.' . $cls . ')';
                }
                $submenu_button_hover_selector = '.no-touchdevice .laynav.desktop-nav .sub-menu .menu-item' . $not_parts . ' > a:hover';
            }
            $normal_border_width = (int) get_theme_mod($submenu_button_slug . '_borderwidth', 1);
            $hover_border_width = (int) get_theme_mod($submenu_button_slug . '_mo_borderwidth', 1);
            $active_border_width = (int) get_theme_mod($submenu_button_slug . '_active_borderwidth', 1);
            $stable_border_width = $normal_border_width;
            if ($use_hover_state) {
                $stable_border_width = max($stable_border_width, $hover_border_width);
            }
            if ($use_active_state) {
                $stable_border_width = max($stable_border_width, $active_border_width);
            }

            $normal_border_color = $normal_border_width > 0 ? get_theme_mod($submenu_button_slug . '_border_color', '#000000') : 'transparent';
            $hover_border_color = $hover_border_width > 0 ? get_theme_mod($submenu_button_slug . '_mo_border_color', '#000000') : 'transparent';
            $active_border_color = $active_border_width > 0 ? get_theme_mod($submenu_button_slug . '_active_border_color', '#000000') : 'transparent';
            $force_styles = get_theme_mod($submenu_button_slug . '_force_styles', false);
            $important = $force_styles ? '!important' : '';
            $submenu_blurry_background = get_theme_mod($submenu_button_slug . '_blurry_background', false);
            $submenu_blur = max(0, (int) get_theme_mod($submenu_button_slug . '_blur', 10));
            $submenu_space_between_submenu_points = get_theme_mod('menu_submenu_space_between_submenu_points', 4);

            $sharedStyles .= FormatsManager::get_textformat_css_for_selector(
                get_theme_mod($submenu_button_slug . '_textformat', 'Default'),
                $submenu_button_selector,
                array('spaces' => false, 'important' => $force_styles, 'media_queries' => true, 'include_padding_bottom' => false)
            );

            // Parent menu can be centered (e.g. nav.primary { text-align:center; }).
            // Force submenu container left alignment so inline-block submenu buttons don't center.
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav .sub-menu{
                    text-align:left!important;
                }';

            $phoneMenuStylesAboveBreakpoint .=
                $submenu_button_selector .
                '{
                    color:' . get_theme_mod($submenu_button_slug . '_text_color', '#000000') . $important . ';
                    border-radius:' . get_theme_mod($submenu_button_slug . '_borderradius', 0) . 'px' . $important . ';
                    background-color:' . $base_bg . $important . ';
                    border:' . $stable_border_width . 'px solid ' . $normal_border_color . $important . ';
                    padding-left:' . get_theme_mod($submenu_button_slug . '_paddingleftright', 15) . 'px' . $important . ';
                    padding-right:' . get_theme_mod($submenu_button_slug . '_paddingleftright', 15) . 'px' . $important . ';
                    padding-top:' . get_theme_mod($submenu_button_slug . '_paddingtop', 5) . 'px' . $important . ';
                    padding-bottom:' . get_theme_mod($submenu_button_slug . '_paddingbottom', 5) . 'px' . $important . ';
                    margin:0' . $important . ';
                    display:inline-block!important;
                    width:auto!important;
                    box-sizing:border-box;
                    ' . ($submenu_blurry_background ? '-webkit-backdrop-filter: blur(' . $submenu_blur . 'px)' . $important . ';backdrop-filter: blur(' . $submenu_blur . 'px)' . $important . ';' : '') . '
                        text-align:left!important;
                    text-decoration:none;
                }';
            // Override submenu.scss rule that forces last-child submenu padding-bottom to 0.
            // We must match its selector specificity and also add !important.
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav.arrangement-horizontal.submenu-type-vertical .menu-item-has-children .sub-menu li:last-child a{
                    padding-bottom:' . get_theme_mod($submenu_button_slug . '_paddingbottom', 5) . 'px !important;
                }';

            // Extra safety: same override without the exact submenu-type selector,
            // because some themes/markup variants still hit the SCSS last-child rule.
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav .menu-item-has-children .sub-menu li:last-child a{
                    padding-bottom:' . get_theme_mod($submenu_button_slug . '_paddingbottom', 5) . 'px !important;
                }';

            if ($use_hover_state) {
                $submenu_transition = get_theme_mod($submenu_button_slug . '_animate_mouseover', false) == true
                    ? 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease' . $important . ';'
                    : '';
                $phoneMenuStylesAboveBreakpoint .=
                    $submenu_button_hover_selector .
                    '{
                        color:' . get_theme_mod($submenu_button_slug . '_mo_text_color', '#000000') . $important . ';
                        background-color:' . $hover_bg . $important . ';
                        border:' . $stable_border_width . 'px solid ' . $hover_border_color . $important . ';
                        '.$submenu_transition.'
                    }';
            }

            if ($use_active_state) {
                $submenu_active_transition = get_theme_mod($submenu_button_slug . '_animate_active_state', false) == true
                    ? 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease' . $important . ';'
                    : '';
                $phoneMenuStylesAboveBreakpoint .=
                    $submenu_button_active_selector .
                    '{
                        color:' . get_theme_mod($submenu_button_slug . '_active_text_color', '#000000') . $important . ';
                        background-color:' . $active_bg . $important . ';
                        border:' . $stable_border_width . 'px solid ' . $active_border_color . $important . ';
                        '.$submenu_active_transition.'
                    }';
            }

            // Ensure vertical gap between submenu buttons comes from "Space between Submenu Points".
            // We use CSS `gap` (not margin-bottom) so button padding/height doesn't interfere with spacing.
            // IMPORTANT: scope to "shown" state, otherwise display:flex overrides the base "display:none" submenu logic.
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav.arrangement-horizontal.submenu-type-vertical .menu-item-has-children.show-submenu-desktop > .sub-menu{
                    display:flex!important;
                    flex-direction:column!important;
                    gap:' . $submenu_space_between_submenu_points . 'px!important;
                }';
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav.arrangement-horizontal.submenu-type-vertical .menu-item-has-children.show-submenu-desktop > .sub-menu li{
                    margin-bottom:0!important;
                }';

            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav.arrangement-vertical .menu-item-has-children.show-submenu-desktop > .sub-menu{
                    display:flex!important;
                    flex-direction:column!important;
                    gap:' . $submenu_space_between_submenu_points . 'px!important;
                }';
            $phoneMenuStylesAboveBreakpoint .=
                '.laynav.desktop-nav.arrangement-vertical .menu-item-has-children.show-submenu-desktop > .sub-menu li{
                    margin-bottom:0!important;
                }';
        }

        // todo: check mobile desktop menu style for submenus!
        $menu_submenu_show_background = get_theme_mod('menu_submenu_show_background', 0);
        if( $menu_submenu_show_background ) {
            $menu_submenu_background_opacity = get_theme_mod('menu_submenu_background_opacity', '100');
            $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_rgba('.sub-menu', 'background-color', 'menu_submenu_background_color', '#ffffff', $menu_submenu_background_opacity);

            $submenu_blurry = get_theme_mod('menu_submenu_blurry', '');
            if( $submenu_blurry == '1' ) {
                $blur = get_theme_mod('submenu_blur_amount', 20);
                $phoneMenuStylesAboveBreakpoint .= 
                '.sub-menu{
                    -webkit-backdrop-filter: saturate(180%) blur('.$blur.'px);
                    backdrop-filter: saturate(180%) blur('.$blur.'px);
                }';
            }
        
        }

        // navigation bar
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_navbar_position();

        $navbar_height_mu = CSS_Output::get_mu('navbar_height_mu', 'px');
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.navbar', 'height', 'navbar_height', '60', '', $navbar_height_mu);
        
		$navbar_hide_default = get_theme_mod('navbar_hide', 'value_was_never_set');
		if($navbar_hide_default == 'value_was_never_set'){
			$navbar_hide_default = true;
		} else {
			$navbar_hide_default = false;
		}
        $navbar_hide = get_theme_mod('navbar_hide', $navbar_hide_default);

        if($navbar_hide == '1') {
            $phoneMenuStylesAboveBreakpoint .= '.navbar{display:none;}';
        } else {
            $phoneMenuStylesAboveBreakpoint .= '.navbar{display:block;}';
        }

        $navbar_blurry = get_theme_mod('navbar_blurry', '');
        if( $navbar_blurry == '1' ) {
            $blur = get_theme_mod('navbar_blur_amount', 20);
            $phoneMenuStylesAboveBreakpoint .= 
            '.navbar{
                -webkit-backdrop-filter: saturate(180%) blur('.$blur.'px);
                backdrop-filter: saturate(180%) blur('.$blur.'px);
            }';
        }

        if(CSS_Output::navbar_get_hide_when_scrolling_down() == 1){
            $phoneMenuStylesAboveBreakpoint .=
            '.navbar{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';            
        }
        
        if(CSS_Output::nav_get_hide_when_scrolling_down('primary') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.primary{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_hide_when_scrolling_down('second_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.second_menu{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_hide_when_scrolling_down('third_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.third_menu{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_hide_when_scrolling_down('fourth_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.fourth_menu{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';
        }

        // fadeout when scrolling down
        if(CSS_Output::navbar_get_fadeout_when_scrolling_down() == 1){
            $phoneMenuStylesAboveBreakpoint .=
            '.navbar{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';            
        }
        
        if(CSS_Output::nav_get_fadeout_when_scrolling_down('primary') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.primary{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_fadeout_when_scrolling_down('second_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.second_menu{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_fadeout_when_scrolling_down('third_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.third_menu{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';
        }
        if(CSS_Output::nav_get_fadeout_when_scrolling_down('fourth_menu') == 1){
            $phoneMenuStylesAboveBreakpoint .=
            'nav.fourth_menu{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';
        }
        // # fade out when scrolling down

        $st_hidewhenscroll = CSS_Output::st_get_hide_when_scrolling_down();
        if($st_hidewhenscroll == 1){
            $phoneMenuStylesAboveBreakpoint .=
            '.sitetitle{
                -webkit-transition: transform 350ms ease;
                -moz-transition: transform 350ms ease;
                transition: transform 350ms ease;
            }';
        }

        $st_fadeout_whenscroll = CSS_Output::st_get_fadeout_when_scrolling_down();
        if($st_fadeout_whenscroll == 1){
            $phoneMenuStylesAboveBreakpoint .=
            '.sitetitle{
                -webkit-transition: transform 350ms ease, opacity 350ms ease;
                -moz-transition: transform 350ms ease, opacity 350ms ease;
                transition: transform 350ms ease, opacity 350ms ease;
            }';
        }
        // $desktopAndTabletStyles .= CSS_Output::generate_opacity_css('.navbar', 'navbar_opacity', 90);
        $menubar_opacity = get_theme_mod('navbar_opacity', 90);
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_rgba('.navbar', 'background-color', 'navbar_color', '#ffffff', $menubar_opacity);

        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_navbar_border_color_css();
        $phoneMenuStylesAboveBreakpoint .= CSS_Output::generate_css('.navbar', 'border-color', 'navbar_border_color', '#cccccc');

		$defaultUnderlineOffset = get_theme_mod('link_underline_offset', 3);
		$defaultUnderlineOffsetMu = get_theme_mod('link_underline_offset_mu', 'px');

        // links in texts and next project / prev project links, and in carousel captions, woocommerce, site title and navigation, and project arrows as text :D
        $st_underline_offset = get_theme_mod('st_link_underline_offset', $defaultUnderlineOffset);
		$sharedStyles .= '
        .sitetitle.txt .sitetitle-txt-inner span{
            text-underline-offset: '.$st_underline_offset.$defaultUnderlineOffsetMu.';
        }';

		// active link underline offset
		$navactive_link_underline_offset = get_theme_mod('navactive_link_underline_offset', $defaultUnderlineOffset);
		$sharedStyles .= 'nav.laynav a span{
			text-underline-offset: '.$navactive_link_underline_offset.$defaultUnderlineOffsetMu.';
		}';

		// mouseover link underline offset
		$navmouseover_link_underline_offset = get_theme_mod('navmouseover_link_underline_offset', $defaultUnderlineOffset);
		$sharedStyles .= 'nav.laynav a:hover span{
			text-underline-offset: '.$navmouseover_link_underline_offset.$defaultUnderlineOffsetMu.';
		}';

        $link_fontweight = get_theme_mod('link_fontweight', 'default');
        if( $link_fontweight != 'default' ) {
            $sharedStyles .= CSS_Output::generate_css(
                '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style),
                .lay-link-parent a:not(.laybutton),
                a.projectlink .lay-textformat-parent>*:not(.laybutton),
                .lay-carousel-sink .single-caption-inner a:not(.laybutton),
                .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton),
                .lay-marquee p a:not(.laybutton), .lay-marquee a:not(.laybutton),
                .lay-table a:not(.laybutton), .lay-table p a:not(.laybutton),
                .lay-accordion a:not(.laybutton), .lay-accordion p a:not(.laybutton),
                .link-in-text', 'font-weight', 'link_fontweight', '400');        
        }

        $sharedStyles .= CSS_Output::generate_css(
            '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style),
            .lay-link-parent a:not(.laybutton),
            a.projectlink .lay-textformat-parent>*:not(.laybutton),
            .lay-carousel-sink .single-caption-inner a:not(.laybutton),
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton),
            .lay-marquee p a:not(.laybutton), .lay-marquee a:not(.laybutton),
            .lay-table a:not(.laybutton), .lay-table p a:not(.laybutton),
            .lay-accordion a:not(.laybutton), .lay-accordion p a:not(.laybutton),
            .link-in-text', 'color', 'link_color', '#000');
        $strokewidth = get_theme_mod('link_underline_strokewidth', 1);

        if( intval($strokewidth) > 0 ) {
            $sharedStyles .= '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style),
            .lay-link-parent a:not(.laybutton),
            a.projectlink .lay-textformat-parent>*:not(.laybutton),
            .lay-carousel-sink .single-caption-inner a:not(.laybutton),
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton),
            .lay-marquee p a:not(.laybutton), .lay-marquee a:not(.laybutton),
            .lay-table a:not(.laybutton), .lay-table p a:not(.laybutton),
            .lay-accordion a:not(.laybutton), .lay-accordion p a:not(.laybutton),
            .link-in-text,
            .pa-text .pa-inner{
                text-decoration-thickness:'.$strokewidth.'px;
                text-decoration-style: solid;
                text-decoration-line: underline;
                text-underline-offset: '.$defaultUnderlineOffset.$defaultUnderlineOffsetMu.';
            }';
        }

        // links in texts mouseover / prev project links mouseover, and in carousel captions, woocommerce
        // doing this for color and fontweight only: this is for spans inside anchors: .no-touchdevice .lay-textformat-parent a:not(.laybutton):hover,
        // this is because when creating a character format for an anchor, it creates a span inside of the anchor
        // also doing this for .lay-textformat-parent a:not(.laybutton).hover span
        // which is used for scrolltrigger imagehoveraddon hover class
        // using !important here to overwrite inline css colors that may have been set in the texteditor
        $use_important = get_theme_mod('link_force_hover_color', false);
        $important = $use_important ? ' !important' : '';

        $sharedStyles .= CSS_Output::generate_css(
            '.no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover,
            .no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover span,
            .no-touchdevice .lay-link-parent a:not(.laybutton):hover,
            .no-touchdevice .lay-link-parent a:not(.laybutton):hover span,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover span,
            .no-touchdevice a.projectlink .lay-textformat-parent>*:not(.laybutton):hover,
            a.projectlink .lay-textformat-parent>*:not(.laybutton).hover,
            .no-touchdevice .lay-carousel-sink .single-caption-inner a:not(.laybutton):hover,
            .no-touchdevice .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):hover,
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton).hover,
            .no-touchdevice .lay-marquee p a:not(.laybutton):hover,
            .no-touchdevice .lay-marquee a:not(.laybutton):hover,
            .no-touchdevice .lay-table a:not(.laybutton):hover,
            .no-touchdevice .lay-table p a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion p a:not(.laybutton):hover,
            .no-touchdevice .link-in-text:hover,
            .link-in-text.hover',
            'color', 'link_hover_color', '#000', '', $important);

        $strokewidth = get_theme_mod('link_hover_underline_strokewidth', get_theme_mod('link_underline_strokewidth', 1));
		if( intval($strokewidth) > 0 ) {
			$underline_offset = $defaultUnderlineOffset;
            $sharedStyles .= 
            '.no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover,
            .no-touchdevice .lay-link-parent a:not(.laybutton):hover,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover,
            .no-touchdevice a.projectlink .lay-textformat-parent>*:not(.laybutton):hover,
            a.projectlink .lay-textformat-parent>*:not(.laybutton).hover,
            .no-touchdevice .lay-carousel-sink .single-caption-inner a:not(.laybutton):hover,
            .no-touchdevice .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):hover,
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton).hover,
            .no-touchdevice .lay-marquee p a:not(.laybutton):hover,
            .no-touchdevice .lay-marquee a:not(.laybutton):hover,
            .no-touchdevice .lay-table a:not(.laybutton):hover,
            .no-touchdevice .lay-table p a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion p a:not(.laybutton):hover,
            .no-touchdevice .link-in-text:hover,
            .no-touchdevice .link-in-text.hover,
            .no-touchdevice .pa-text:hover .pa-inner{
                text-decoration-thickness:'.$strokewidth.'px'.$important.';
                text-decoration-style: solid'.$important.';
                text-decoration-line: underline'.$important.';
                text-underline-offset: '.$underline_offset.$defaultUnderlineOffsetMu.';
            }';
        } else {
            $sharedStyles .= 
            '.no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover,
            .no-touchdevice .lay-link-parent a:not(.laybutton):hover,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover span,
            .no-touchdevice a.projectlink .lay-textformat-parent>*:not(.laybutton):hover,
            a.projectlink .lay-textformat-parent>*:not(.laybutton).hover,
            .no-touchdevice .lay-carousel-sink .single-caption-inner a:not(.laybutton):hover,
            .no-touchdevice .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):hover,
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton).hover,
            .no-touchdevice .lay-marquee p a:not(.laybutton):hover,
            .no-touchdevice .lay-marquee a:not(.laybutton):hover,
            .no-touchdevice .lay-table a:not(.laybutton):hover,
            .no-touchdevice .lay-table p a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion p a:not(.laybutton):hover,
            .no-touchdevice .link-in-text:hover,
            .no-touchdevice .link-in-text.hover,
            .no-touchdevice .pa-text:hover .pa-inner{
                text-decoration: none'.$important.';
             }';
        }

        $link_hover_fontweight = get_theme_mod('link_hover_fontweight', $link_fontweight);
        if( $link_hover_fontweight != 'default' ) {
            $sharedStyles .= 
            '.no-touchdevice .lay-link-parent a:not(.laybutton):hover,
            .no-touchdevice .lay-link-parent a:not(.laybutton):hover span,
            .no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover,
            .no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover span,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover,
            .no-touchdevice a.projectlink .lay-textformat-parent>*:not(.laybutton):hover,
            a.projectlink .lay-textformat-parent>*:not(.laybutton).hover,
            .no-touchdevice .lay-carousel-sink .single-caption-inner a:not(.laybutton):hover,
            .no-touchdevice .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):hover,
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton).hover,
            .no-touchdevice .lay-marquee p a:not(.laybutton):hover,
            .no-touchdevice .lay-marquee a:not(.laybutton):hover,
            .no-touchdevice .lay-table a:not(.laybutton):hover,
            .no-touchdevice .lay-table p a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion p a:not(.laybutton):hover,
            .no-touchdevice .link-in-text:hover,
            .no-touchdevice .link-in-text.hover,
            .no-touchdevice .pa-text:hover .pa-inner{
                font-weight: '.$link_hover_fontweight.$important.';
             }';
        }
        
        $sharedStyles .= CSS_Output::generate_opacity_css(
            '.no-touchdevice .lay-link-parent a:not(.laybutton):hover,
            .no-touchdevice .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style):hover,
            .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover,
            .no-touchdevice a.projectlink .lay-textformat-parent>*:not(.laybutton):hover,
            a.projectlink .lay-textformat-parent>*:not(.laybutton).hover,
            .no-touchdevice .lay-carousel-sink .single-caption-inner a:not(.laybutton):hover,
            .no-touchdevice .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):hover,
            .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton).hover,
            .no-touchdevice .lay-marquee p a:not(.laybutton):hover,
            .no-touchdevice .lay-marquee a:not(.laybutton):hover,
            .no-touchdevice .lay-table a:not(.laybutton):hover,
            .no-touchdevice .lay-table p a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion a:not(.laybutton):hover,
            .no-touchdevice .lay-accordion p a:not(.laybutton):hover,
            .no-touchdevice .link-in-text:hover,
            .no-touchdevice .link-in-text.hover,
            .no-touchdevice .pa-text:hover .pa-inner', 'link_hover_opacity', 100, $use_important);

        // need this for active states for phone imagehover addon active links
        // doing it for ".lay-textformat-parent a:not(.laybutton).hover span" too, because if user applies a character textformat, there can be a span inside of an anchor
        $mobileStyles .= CSS_Output::generate_css('.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover span, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover span', 'color', 'link_hover_color', '#000');
        $strokewidth = get_theme_mod('link_hover_underline_strokewidth', get_theme_mod('link_underline_strokewidth', 1));
        if( intval($strokewidth) > 0 ) {
            $mobileStyles .= 
            '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-link-parent a:not(.laybutton):hover, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover{
                text-decoration-thickness:'.$strokewidth.$important.'px;
                text-decoration-style: solid'.$important.';
                text-decoration-line: underline'.$important.';
             }';
        } else {
            $mobileStyles .= 
            '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-link-parent a:not(.laybutton).hover, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover{
                text-decoration: none'.$important.';
             }';
        }

        $link_hover_fontweight = get_theme_mod('link_hover_fontweight', $link_fontweight);
        if( $link_hover_fontweight != 'default' ) {
            $mobileStyles .= 
            '.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-link-parent a:not(.laybutton).hover, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover{
                font-weight: '.$link_hover_fontweight.$important.';
             }';
        }
        
        $mobileStyles .= CSS_Output::generate_opacity_css('.lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover, .lay-link-parent a:not(.laybutton).hover, .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton):not(.lay-ignore-linktext-style).hover', 'link_hover_opacity', 100);

        // thumbnailgrid filter category
        // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$tgf_textformat = get_theme_mod('tgf_textformat', 'Default');
		if($tgf_textformat == ""){
			// if no textformat was selected for st, generate css based on the individual customizer controls
			$tgf_fontsize_mu = CSS_Output::get_mu('tgf_fontsize_mu', 'px');
			$sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-size', 'tgf_fontsize', Customizer::$defaults['fontsize'],'', $tgf_fontsize_mu);
			$sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-weight', 'tgf_fontweight', Customizer::$defaults['fontweight']);
			$sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'letter-spacing', 'tgf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-family', 'tgf_fontfamily', Customizer::$defaults['fontfamily']);
        }
        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'color', 'tgf_color', Customizer::$defaults['color']);
        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'text-align', 'tgf_align', 'left');
        $d = get_theme_mod('tgf_color', Customizer::$defaults['color']);
        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter .lay-filter-active', 'color', 'tgf_active_color', $d);

        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'margin-bottom', 'tgf_spacebelow', '20', '', 'px');
        $desktopAndTabletStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-filter-anchor', 'margin-right', 'tgf_spacebetween_2', '10', '', 'px');
        $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .lay-thumbnailgrid-filter-anchor', 'margin-right', 'tgf_spacebetween_mobile', '10', '', 'px');
        // $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .lay-thumbnailgrid-filter-anchor', 'margin-left', 'tgf_spacebetween_mobile', '10', '', 'px');

        $mobileStyles .= CSS_Output::generate_css('.mobile-not-one-line .lay-thumbnailgrid-filter-anchor', 'margin', 'tgf_spacebetween_mobile', '10', '', 'px');

        $sharedStyles .= CSS_Output::generate_opacity_css('.lay-thumbnailgrid-filter-anchor', 'tgf_opacity', 50);

        $sharedStyles .= CSS_Output::generate_opacity_css('.lay-thumbnailgrid-filter-anchor.lay-filter-active', 'tgf_active_opacity', 100);
        $sharedStyles .= CSS_Output::generate_opacity_css('@media (hover) {.lay-thumbnailgrid-filter-anchor:hover', 'tgf_mouseover_opacity', 100);
        // close media query
        $sharedStyles .= '}';
        $tgf_active_strokewidth = get_theme_mod('tgf_active_strokewidth', 0);
        if( $tgf_active_strokewidth > 0 ) {
            $sharedStyles .= '
            .lay-thumbnailgrid-filter-anchor{
                text-decoration-thickness:'.$tgf_active_strokewidth.'px;
                text-decoration-color: transparent;
                text-decoration-style: solid;
                text-decoration-line: underline;
                text-underline-offset: '.$defaultUnderlineOffset.$defaultUnderlineOffsetMu.';
            }
            .lay-thumbnailgrid-filter-anchor.lay-filter-active{
                text-decoration-color: inherit;
            }';
        }

        // tags filter for thumbnailgrid
        // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$tgtf_textformat = get_theme_mod('tgtf_textformat', 'Default');
		if($tgtf_textformat == ""){
			// if no textformat was selected for st, generate css based on the individual customizer controls
			$tgtf_fontsize_mu = CSS_Output::get_mu('tgtf_fontsize_mu', 'px');
			$sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'font-size', 'tgtf_fontsize', Customizer::$defaults['fontsize'],'', $tgtf_fontsize_mu);
			$sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'font-weight', 'tgtf_fontweight', Customizer::$defaults['fontweight']);
			$sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'letter-spacing', 'tgtf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'font-family', 'tgtf_fontfamily', Customizer::$defaults['fontfamily']);
        }
        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'text-align', 'tgtf_align', 'left');
        $sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'color', 'tgtf_color', Customizer::$defaults['color']);

        $sharedStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'margin-bottom', 'tgtf_spacebelow', '20', '', 'px');
        $desktopAndTabletStyles .= CSS_Output::generate_css('.tag-bubble', 'margin', 'tgtf_spacebetween', '10', '', 'px');
        
        $mobileStyles .= CSS_Output::generate_css('.mobile-one-line .tag-bubble:not(.lay-filter-hidden)', 'margin-right', 'tgtf_spacebetween_mobile', '10', '', 'px');
        $mobileStyles .= CSS_Output::generate_css('.mobile-not-one-line .tag-bubble', 'margin', 'tgtf_spacebetween_mobile', '10', '', 'px');

        $desktopAndTabletStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'margin-left', 'tgtf_spacebetween', '10', '-', 'px');
        $mobileStyles .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'margin-left', 'tgtf_spacebetween_mobile', '10', '-', 'px');

        $sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'background-color', 'tgtf_bubble_color', '#eeeeee');
        $sharedStyles .= CSS_Output::generate_css('.tag-bubble', 'border-radius', 'tgtf_bubble_border_radius', '100', '', 'px');
        $sharedStyles .= CSS_Output::generate_css('@media (hover:hover) {.tag-bubble:hover', 'background-color', 'tgtf_bubble_active_color', '#d0d0d0');
        // close media query
        $sharedStyles .= '}';
        $sharedStyles .= CSS_Output::generate_css('.tag-bubble.lay-tag-active', 'background-color', 'tgtf_bubble_active_color', '#d0d0d0');

        // mobile
        // all of this just applies to automatically generated phone layouts (#grid), not custom phone layouts (.hascustomphonegrid)
        $mobile_space_between_elements_mu = CSS_Output::get_mu('mobile_space_between_elements_mu', '%');
        $mobile_space_leftright_mu = CSS_Output::get_mu('mobile_space_leftright_mu', 'vw');
        $mobile_space_top_mu = CSS_Output::get_mu('mobile_space_top_mu', 'vw');
        $mobile_space_bottom_mu = CSS_Output::get_mu('mobile_space_bottom_mu', 'vw');

        $mobile_space_top_footer_mu = CSS_Output::get_mu('mobile_space_top_footer_mu', 'vw');
        $mobile_space_bottom_footer_mu = CSS_Output::get_mu('mobile_space_bottom_footer_mu', 'vw');

        // on mobile, I just have margin between elements(cols), not between rows
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid .col, .lay-content.footer-nocustomphonegrid #footer .col', 'margin-bottom', 'mobile_space_between_elements', 5, '', $mobile_space_between_elements_mu);
        // for cols in cover
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid .cover-region .col', 'margin-bottom', 'mobile_space_between_elements', 5, '', $mobile_space_between_elements_mu);

        // when a row is empty and height of browserheight (probably with a row bg) then it should have a margin-bottom like a col
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid .row.empty._100vh, .lay-content.footer-nocustomphonegrid #footer .row.empty._100vh', 'margin-bottom', 'mobile_space_between_elements', 5, '', $mobile_space_between_elements_mu);
        // when a row has a background image, it should have a margin-bottom like a col 
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid .row.has-background, .lay-content.footer-nocustomphonegrid #footer .row.has-background', 'margin-bottom', 'mobile_space_between_elements', 5, '', $mobile_space_between_elements_mu);
				
        // space between when cover - this gives a "space between" to the top of the content region after the cover
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid.hascover #grid', 'padding-top', 'mobile_space_between_elements', 5, '', $mobile_space_between_elements_mu);

        // left and right space, only for APL
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid .row, .lay-content.nocustomphonegrid .cover-region-desktop .row, .lay-content.footer-nocustomphonegrid #footer .row', 'padding-left', 'mobile_space_leftright', 5, '', $mobile_space_leftright_mu);
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid .row, .lay-content.nocustomphonegrid .cover-region-desktop .row, .lay-content.footer-nocustomphonegrid #footer .row', 'padding-right', 'mobile_space_leftright', 5, '', $mobile_space_leftright_mu);

        // for APL when we have scrollslider and our markup is simpler, but scrollslider is not enabled on phone
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid.simpler-markup-for-scrollslider', 'padding-left', 'mobile_space_leftright', 5, '', $mobile_space_leftright_mu);
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid.simpler-markup-for-scrollslider', 'padding-right', 'mobile_space_leftright', 5, '', $mobile_space_leftright_mu);
        $mobileStyles .= '.lay-content.nocustomphonegrid #grid.simpler-markup-for-scrollslider{ box-sizing: border-box; }';

        // new: apl overflow
        $lr = get_theme_mod('mobile_space_leftright', 5);
        $lr_mu = $mobile_space_leftright_mu;

        $fovl_sel = '.frame-overflow-left';
        $fovr_sel = '.frame-overflow-right';
        $fovb_sel = '.frame-overflow-both';
        
        $do_overflow_on_apl = get_option('misc_options_enable_frame_overflows_for_apl', 'on');

        if($do_overflow_on_apl == 'on') {
                 // overflow both apl
                $mobileStyles .= 
                '.lay-content.nocustomphonegrid #grid .col'.$fovb_sel.',
                .lay-content.nocustomphonegrid .cover-region-desktop .col'.$fovb_sel.',
                .lay-content.footer-nocustomphonegrid #footer .col'.$fovb_sel.'{
                    width: calc( 100% + '.$lr.$lr_mu.' * 2 );
                    left: -'.$lr.$lr_mu.';
                }';

                // overflow left apl
                $mobileStyles .= 
                '.lay-content.nocustomphonegrid #grid .col'.$fovr_sel.',
                .lay-content.nocustomphonegrid .cover-region-desktop .col'.$fovr_sel.',
                .lay-content.footer-nocustomphonegrid #footer .col'.$fovr_sel.'{
                    width: calc( 100% + '.$lr.$lr_mu.' );
                }';

                // overflow right apl
                $mobileStyles .= 
                '.lay-content.nocustomphonegrid #grid .col'.$fovl_sel.',
                .lay-content.nocustomphonegrid .cover-region-desktop .col'.$fovl_sel.',
                .lay-content.footer-nocustomphonegrid #footer .col'.$fovl_sel.'{
                    width: calc( 100% + '.$lr.$lr_mu.' );
                    left: -'.$lr.$lr_mu.';
                }';   
        }

        // new: space top and bottom, only for APL
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid', 'padding-bottom', 'mobile_space_bottom', 5, '', $mobile_space_bottom_mu);
        $mobileStyles .= CSS_Output::generate_css('.lay-content.nocustomphonegrid #grid', 'padding-top', 'mobile_space_top', 5, '', $mobile_space_top_mu);

        // new: space top and bottom footer
        $mobileStyles .= CSS_Output::generate_css('.lay-content.footer-nocustomphonegrid #footer', 'padding-bottom', 'mobile_space_bottom_footer', 5, '', $mobile_space_bottom_footer_mu);
        $mobileStyles .= CSS_Output::generate_css('.lay-content.footer-nocustomphonegrid #footer', 'padding-top', 'mobile_space_top_footer', 5, '', $mobile_space_top_footer_mu);

        // cursor
        $cursor = get_theme_mod('lay_cursor', '');
        $use_dom_cursor = get_theme_mod('lay_cursor_use_dom_cursor', 0) == 1;
        if( $cursor != '' && !$use_dom_cursor ) {
            $cursor_css = 'url('.get_theme_mod('lay_cursor', '').') '.get_theme_mod('lay_cursor_x', 0).' '.get_theme_mod('lay_cursor_y', 0).', auto';
            // Set a global default cursor on the root/body so more specific
            // rules from addons and custom CSS can still override it.
            $sharedStyles .= 'html, body{ cursor: '.$cursor_css.'; }';
            $sharedStyles .= 'html body.lightbox-css-on > .lay-content .grid-inner .col[data-type=img] img, html body.lightbox-css-on #lay-project-overlay .grid-inner .col[data-type=img] img{ cursor: '.$cursor_css.'; }';
            $sharedStyles .= 'html body.lightbox-css-on > .lay-content .grid-inner .col[data-type=elementgrid] .img img, html body.lightbox-css-on #lay-project-overlay .grid-inner .col[data-type=elementgrid] .img img{ cursor: '.$cursor_css.'; }';

            if ( get_theme_mod( 'lay_cursor_keep_normal_cursor_for_links', 0 ) == 1 ) {
                $sharedStyles .= 'html a, html .lay-marquee a, html .lay-table a{ cursor: pointer!important; }';
            } else {
                // Browser default styles often set links to pointer.
                // If "Keep normal cursor for links" is off, force the custom cursor on links too.
                $sharedStyles .= 'html a, html a *, html .lay-marquee a, html .lay-marquee a *, html .lay-table a, html .lay-table a *{ cursor: '.$cursor_css.'!important; }';
                // Override common frontend UI selectors that explicitly set cursor:pointer in SCSS.
                // Keep this focused on pointer-only UI controls so specialized addon cursor classes can still win.
                $sharedStyles .= 'html .overlay-close, html .user-overlay-close, html .overlay-burger, html .overlay-burger *,
                    html .close-projects-overlay,
                    html .search-close,
                    html .suggest-item,
                    html .search-view .search-arrow,
                    html .search-view .search-clear,
                    html .lay-productsgrid-filter-anchor,
                    html .lay-thumbnailgrid-filter-anchor,
                    html .lay-filter-bubble,
                    html .scrolltotop,
                    html .html5video video.playpauseonclick,
                    html .html5video .html5-video-placeholder-image.playpauseonclick,
                    html .lay-thumbnailgrid-load-more,
                    html .lay-productsgrid-load-more,
                    html .lay-news-load-more,
                    html .lay-elementgrid-load-more,
                    html #lay-edit-button,
                    html .lay-accordion-title,
                    html .clickable-cover,
                    html #intro,
                    html .swiper-button-prev,
                    html .swiper-button-next,
                    html .swiper-pagination-clickable .swiper-pagination-bullet,
                    html .lay-productindex .lay-thumbnailgrid-filter-anchor,
                    html .lay-projectindex .lay-thumbnailgrid-filter-anchor,
                    html .burger,
                    html .burger-wrap,
                    html .burger-default,
                    html .mobile-menu-icon,
                    html .post-password-form input[type=\"submit\"],
                    html .bullet,
                    html .lay-table-cell-inner a[data-expand]{
                        cursor: '.$cursor_css.'!important;
                    }';
            }
        }

        // search
        // public static function generate_css( $selector, $style, $mod_name, $default, $prefix='', $postfix='' ) {

        // $search_autosuggest_hide = get_theme_mod('search_autosuggest_hide', false);
        $sharedStyles .= CSS_Output::generate_css('input#search-query, .suggest-item', 'font-family', 'search_fontfamily', Customizer::$defaults['fontfamily']);

        $search_bg_opacity = get_theme_mod('search_background_opacity', 85);
        $sharedStyles .= CSS_Output::generate_rgba('.search-view', 'background-color', 'search_background_color', '#ffffff', $search_bg_opacity);

        $sharedStyles .= CSS_Output::generate_css('input#search-query::selection', 'background', 'search_text_selection_color', '#f5f5f5', '', '');

        $sharedStyles .= CSS_Output::generate_css('.close-search', 'color', 'search_close_color', '#000000', '', '');
        // $sharedStyles .= CSS_Output::generate_css('input#search-query', 'border-bottom-color', 'search_bar_line_color', '#000000', '', '');
        // $sharedStyles .= CSS_Output::generate_css('.search-view .search-icon path', 'fill', 'search_bar_placeholder_color', '#ccc', '', '');
        $sharedStyles .= CSS_Output::generate_css('input#search-query::placeholder', 'color', 'search_bar_placeholder_color', '#ccc', '', '');
        $sharedStyles .= CSS_Output::generate_css('input#search-query', 'color', 'search_text_color', '#000', '', '');

        $sharedStyles .= CSS_Output::generate_css('.suggest-item', 'color', 'search_autosuggest_text_color', '#aaa', '', '');
        $sharedStyles .= CSS_Output::generate_css('@media (hover:hover) {.suggest-item:hover', 'color', 'search_autosuggest_mouseover_text_color', '#000', '', '');
        // close media query
        $sharedStyles .= '}';
        $blurry = get_theme_mod('search_background_blurry', true);
        if( $blurry == true ) {
            $sharedStyles .= '.search-view{ -webkit-backdrop-filter: saturate(180%) blur(10px);
            backdrop-filter: saturate(180%) blur(10px); }';
        }

        if( get_theme_mod('search_use_textformat', false) == 1 ) {
            $search_textformat = get_theme_mod('search_textformat', 'Default');
            $sharedStyles .= FormatsManager::get_textformat_css_for_selector($search_textformat, 'input#search-query', array('spaces' => false));
        }

        // search max-width
        $search_max_width = get_theme_mod('search_max_width', '0');
        if( intval($search_max_width) > 0 ) {
            $sharedStyles .= '#search-results-region{ max-width: '.$search_max_width.'px; margin-left: auto; margin-right: auto; }';
            $sharedStyles .= '#search-query,#search-autosuggest-list{ max-width: '.$search_max_width.'px; margin-left: auto; margin-right: auto; display: block; }';
        }
        
        // get_theme_mod('search_background_color', '#ffffff');

        // buttons — only transition what the mouseover settings change (no transform) so text animations stay correct
        $laybutton_mouseover_transition = 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease';

        $bgcolor1_opacity = get_theme_mod('laybutton1_background_opacity', 100);
        $bgcolor1 = get_theme_mod('laybutton1_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton1_background_color', '#ffffff'), $bgcolor1_opacity);
        $bgcolor1_mo_opacity = get_theme_mod('laybutton1_mo_background_opacity', 100);
        $bgcolor1_mo = get_theme_mod('laybutton1_mo_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton1_mo_background_color', '#ffffff'), $bgcolor1_mo_opacity);

        $laybutton1_textformat = get_theme_mod('laybutton1_textformat', 'Default');
        $force_styles_1 = get_theme_mod('laybutton1_force_styles', false);
        $laybutton1_border_width = (int) get_theme_mod('laybutton1_borderwidth', 1);
        $laybutton1_mo_border_width = (int) get_theme_mod('laybutton1_mo_borderwidth', 1);
        $laybutton1_stable_border_width = get_theme_mod('laybutton1_use_mouseover', false) ? max($laybutton1_border_width, $laybutton1_mo_border_width) : $laybutton1_border_width;
        $laybutton1_border_color = $laybutton1_border_width > 0 ? get_theme_mod('laybutton1_border_color', '#000000') : 'transparent';
        $laybutton1_mo_border_color = $laybutton1_mo_border_width > 0 ? get_theme_mod('laybutton1_mo_border_color', '#000000') : 'transparent';
        $laybutton1_blurry_background = get_theme_mod('laybutton1_blurry_background', false);
        $laybutton1_blur = max(0, (int) get_theme_mod('laybutton1_blur', 10));
        $sharedStyles .= FormatsManager::get_textformat_css_for_selector($laybutton1_textformat, '.laybutton1', array('spaces' => false, 'important' => $force_styles_1, 'media_queries' => true, 'include_padding_bottom' => false));
        $important_1 = $force_styles_1 ? '!important' : '';
        $sharedStyles .= 
        '.laybutton1{
            color: '.get_theme_mod('laybutton1_text_color', '#000').$important_1.';
            border-radius: '.get_theme_mod('laybutton1_borderradius', 0).'px'.$important_1.';
            background-color: '.$bgcolor1.$important_1.';
            border: '.$laybutton1_stable_border_width.'px solid '.$laybutton1_border_color.$important_1.';
            padding-left: '.get_theme_mod('laybutton1_paddingleftright', 15).'px'.$important_1.';
            padding-right: '.get_theme_mod('laybutton1_paddingleftright', 15).'px'.$important_1.';
            padding-top: '.get_theme_mod('laybutton1_paddingtop', 5).'px'.$important_1.';
            padding-bottom: '.get_theme_mod('laybutton1_paddingbottom', 5).'px'.$important_1.';
            margin-left: '.get_theme_mod('laybutton1_marginleftright', 0).'px'.$important_1.';
            margin-right: '.get_theme_mod('laybutton1_marginleftright', 0).'px'.$important_1.';
            margin-top: '.get_theme_mod('laybutton1_margintop', 0).'px'.$important_1.';
            margin-bottom: '.get_theme_mod('laybutton1_marginbottom', 0).'px'.$important_1.';
            '.($laybutton1_blurry_background ? '-webkit-backdrop-filter: blur('.$laybutton1_blur.'px)'.$important_1.';backdrop-filter: blur('.$laybutton1_blur.'px)'.$important_1.';' : '').'
            '.( get_theme_mod('laybutton1_animate_mouseover', false) == true ? $laybutton_mouseover_transition.$important_1.';' : '' ).'
        }';
        if( get_theme_mod('laybutton1_use_mouseover', false) == true ) {
            $sharedStyles .= 
            '.laybutton1:hover{
                color: '.get_theme_mod('laybutton1_mo_text_color', '#000').$important_1.';
                background-color: '.$bgcolor1_mo.$important_1.';
                border: '.$laybutton1_stable_border_width.'px solid '.$laybutton1_mo_border_color.$important_1.';
            }';
        }

        $bgcolor2_opacity = get_theme_mod('laybutton2_background_opacity', 100);
        $bgcolor2 = get_theme_mod('laybutton2_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton2_background_color', '#ffffff'), $bgcolor2_opacity);
        $bgcolor2_mo_opacity = get_theme_mod('laybutton2_mo_background_opacity', 100);
        $bgcolor2_mo = get_theme_mod('laybutton2_mo_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton2_mo_background_color', '#ffffff'), $bgcolor2_mo_opacity);

        $laybutton2_textformat = get_theme_mod('laybutton2_textformat', 'Default');
        $force_styles_2 = get_theme_mod('laybutton2_force_styles', false);
        $laybutton2_border_width = (int) get_theme_mod('laybutton2_borderwidth', 1);
        $laybutton2_mo_border_width = (int) get_theme_mod('laybutton2_mo_borderwidth', 1);
        $laybutton2_stable_border_width = get_theme_mod('laybutton2_use_mouseover', false) ? max($laybutton2_border_width, $laybutton2_mo_border_width) : $laybutton2_border_width;
        $laybutton2_border_color = $laybutton2_border_width > 0 ? get_theme_mod('laybutton2_border_color', '#000000') : 'transparent';
        $laybutton2_mo_border_color = $laybutton2_mo_border_width > 0 ? get_theme_mod('laybutton2_mo_border_color', '#000000') : 'transparent';
        $laybutton2_blurry_background = get_theme_mod('laybutton2_blurry_background', false);
        $laybutton2_blur = max(0, (int) get_theme_mod('laybutton2_blur', 10));
        $sharedStyles .= FormatsManager::get_textformat_css_for_selector($laybutton2_textformat, '.laybutton2', array('spaces' => false, 'important' => $force_styles_2, 'media_queries' => true, 'include_padding_bottom' => false));
        $important_2 = $force_styles_2 ? '!important' : '';
        $sharedStyles .= 
        '.laybutton2{
            color: '.get_theme_mod('laybutton2_text_color', '#000').$important_2.';
            border-radius: '.get_theme_mod('laybutton2_borderradius', 100).'px'.$important_2.';
            background-color: '.$bgcolor2.$important_2.';
            border: '.$laybutton2_stable_border_width.'px solid '.$laybutton2_border_color.$important_2.';
            padding-left: '.get_theme_mod('laybutton2_paddingleftright', 20).'px'.$important_2.';
            padding-right: '.get_theme_mod('laybutton2_paddingleftright', 20).'px'.$important_2.';
            padding-top: '.get_theme_mod('laybutton2_paddingtop', 5).'px'.$important_2.';
            padding-bottom: '.get_theme_mod('laybutton2_paddingbottom', 5).'px'.$important_2.';
            margin-left: '.get_theme_mod('laybutton2_marginleftright', 0).'px'.$important_2.';
            margin-right: '.get_theme_mod('laybutton2_marginleftright', 0).'px'.$important_2.';
            margin-top: '.get_theme_mod('laybutton2_margintop', 0).'px'.$important_2.';
            margin-bottom: '.get_theme_mod('laybutton2_marginbottom', 0).'px'.$important_2.';
            '.($laybutton2_blurry_background ? '-webkit-backdrop-filter: blur('.$laybutton2_blur.'px)'.$important_2.';backdrop-filter: blur('.$laybutton2_blur.'px)'.$important_2.';' : '').'
            '.( get_theme_mod('laybutton2_animate_mouseover', false) == true ? $laybutton_mouseover_transition.$important_2.';' : '' ).'
        }';
        if( get_theme_mod('laybutton2_use_mouseover', false) == true ) {
            $sharedStyles .= 
            '.laybutton2:hover{
                color: '.get_theme_mod('laybutton2_mo_text_color', '#000').$important_2.';
                background-color: '.$bgcolor2_mo.$important_2.';
                border: '.$laybutton2_stable_border_width.'px solid '.$laybutton2_mo_border_color.$important_2.';
            }';
        }

        $bgcolor3_opacity = get_theme_mod('laybutton3_background_opacity', 100);
        $bgcolor3 = get_theme_mod('laybutton3_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton3_background_color', '#eeeeee'), $bgcolor3_opacity);
        $bgcolor3_mo_opacity = get_theme_mod('laybutton3_mo_background_opacity', 100);
        $bgcolor3_mo = get_theme_mod('laybutton3_mo_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton3_mo_background_color', '#eeeeee'), $bgcolor3_mo_opacity);

        $laybutton3_textformat = get_theme_mod('laybutton3_textformat', 'Default');
        $force_styles_3 = get_theme_mod('laybutton3_force_styles', false);
        $laybutton3_border_width = (int) get_theme_mod('laybutton3_borderwidth', 0);
        $laybutton3_mo_border_width = (int) get_theme_mod('laybutton3_mo_borderwidth', 0);
        $laybutton3_stable_border_width = get_theme_mod('laybutton3_use_mouseover', false) ? max($laybutton3_border_width, $laybutton3_mo_border_width) : $laybutton3_border_width;
        $laybutton3_border_color = $laybutton3_border_width > 0 ? get_theme_mod('laybutton3_border_color', '#000000') : 'transparent';
        $laybutton3_mo_border_color = $laybutton3_mo_border_width > 0 ? get_theme_mod('laybutton3_mo_border_color', '#000000') : 'transparent';
        $laybutton3_blurry_background = get_theme_mod('laybutton3_blurry_background', false);
        $laybutton3_blur = max(0, (int) get_theme_mod('laybutton3_blur', 10));
        $sharedStyles .= FormatsManager::get_textformat_css_for_selector($laybutton3_textformat, '.laybutton3', array('spaces' => false, 'important' => $force_styles_3, 'media_queries' => true, 'include_padding_bottom' => false));
        $important_3 = $force_styles_3 ? '!important' : '';
        $sharedStyles .= 
        '.laybutton3{
            color: '.get_theme_mod('laybutton3_text_color', '#000').$important_3.';
            border-radius: '.get_theme_mod('laybutton3_borderradius', 100).'px'.$important_3.';
            background-color: '.$bgcolor3.$important_3.';
            border: '.$laybutton3_stable_border_width.'px solid '.$laybutton3_border_color.$important_3.';
            padding-left: '.get_theme_mod('laybutton3_paddingleftright', 20).'px'.$important_3.';
            padding-right: '.get_theme_mod('laybutton3_paddingleftright', 20).'px'.$important_3.';
            padding-top: '.get_theme_mod('laybutton3_paddingtop', 5).'px'.$important_3.';
            padding-bottom: '.get_theme_mod('laybutton3_paddingbottom', 5).'px'.$important_3.';
            margin-left: '.get_theme_mod('laybutton3_marginleftright', 0).'px'.$important_3.';
            margin-right: '.get_theme_mod('laybutton3_marginleftright', 0).'px'.$important_3.';
            margin-top: '.get_theme_mod('laybutton3_margintop', 0).'px'.$important_3.';
            margin-bottom: '.get_theme_mod('laybutton3_marginbottom', 0).'px'.$important_3.';
            '.($laybutton3_blurry_background ? '-webkit-backdrop-filter: blur('.$laybutton3_blur.'px)'.$important_3.';backdrop-filter: blur('.$laybutton3_blur.'px)'.$important_3.';' : '').'
            '.( get_theme_mod('laybutton3_animate_mouseover', false) == true ? $laybutton_mouseover_transition.$important_3.';' : '' ).'
        }';
        if( get_theme_mod('laybutton3_use_mouseover', false) == true ) {
            $sharedStyles .= 
            '.laybutton3:hover{
                color: '.get_theme_mod('laybutton3_mo_text_color', '#000').$important_3.';
                background-color: '.$bgcolor3_mo.$important_3.';
                border: '.$laybutton3_stable_border_width.'px solid '.$laybutton3_mo_border_color.$important_3.';
            }';
        }

        $breakpoint = get_option('lay_breakpoint', 600);
        $breakpoint = (int)$breakpoint;

        $breakpoint_phone_menu = get_option('lay_phone_menu_breakpoint', $breakpoint);
        $breakpoint_phone_menu = (int)$breakpoint_phone_menu;

        wp_add_inline_style( 'frontend-style',
        '/* customizer css */
            '.apply_filters('lay_shared_styles', $sharedStyles).'
            /* phone menu breakpoint above */
            @media (min-width: '.($breakpoint_phone_menu+1).'px){
                '.apply_filters('lay_phone_menu_styles_above_breakpoint', $phoneMenuStylesAboveBreakpoint).
            '}
            /* phone menu breakpoint under */
            @media (max-width: '.($breakpoint_phone_menu).'px){
                '.apply_filters('lay_phone_menu_styles_under_breakpoint', $phoneMenuStylesUnderBreakpoint).
            '}
            /* desktop and tablet */
            @media (min-width: '.($breakpoint+1).'px){'
                .apply_filters('lay_desktop_and_tablet_styles', $desktopAndTabletStyles).
            '}
            @media (max-width: '.($breakpoint).'px){'
                .apply_filters('lay_mobile_styles', $mobileStyles).
            '}'
        );
    }

    public static function get_mu( $theme_mod_name, $defaultmu ){
        // example: it's a fresh Lay Theme install. "px" is the default. but when a user never actually selects "px" and saves the customizer, a value of "" is saved
        $mu = get_theme_mod($theme_mod_name, $defaultmu);
        if($mu == ""){
            $mu = $defaultmu;
        }
        return $mu;
    }

    public static function navbar_get_hide_when_scrolling_down(){
		// get_theme_mod('nav_hidewhenscrolling', "") is default for backwards compatibility
		$navbar_hidewhenscrolling_default = get_theme_mod('nav_hidewhenscrolling', "");
		return get_theme_mod('navbar_hidewhenscrolling', $navbar_hidewhenscrolling_default);
	}

    public static function navbar_get_fadeout_when_scrolling_down(){
		$navbar_fadeout_whenscrolling_default = get_theme_mod('navbar_fadeout_whenscrolling', "");
		return get_theme_mod('navbar_fadeout_whenscrolling', $navbar_fadeout_whenscrolling_default);
	}

    public static function st_get_hide_when_scrolling_down(){
        $mod = get_theme_mod('st_hidewhenscrolling', "");

        if($mod == 1){
            $stisFixed = get_theme_mod('st_isfixed', '1');

            if($stisFixed == 1){
                return 1;
            }
        }

        return "";
    }

    public static function st_get_fadeout_when_scrolling_down(){
        $mod = get_theme_mod('st_fadeout_whenscrolling', "");

        if($mod == 1){
            $stisFixed = get_theme_mod('st_isfixed', '1');

            if($stisFixed == 1){
                return 1;
            }
        }

        return "";
    }

    public static function nav_get_fadeout_when_scrolling_down($identifier){

        $suffix = LayMenuManager::get_suffix_based_on_identifier($identifier);
        $mod = get_theme_mod('nav_fadeout_whenscrolling'.$suffix, "");

        if($mod == 1){
            $navisFixed = get_theme_mod('nav_isfixed'.$suffix, '1');

            if($navisFixed == 1){
                return 1;
            }
        }

        return "";
    }

    public static function nav_get_hide_when_scrolling_down($identifier){

        $suffix = LayMenuManager::get_suffix_based_on_identifier($identifier);
        $mod = get_theme_mod('nav_hidewhenscrolling'.$suffix, "");

        if($mod == 1){
            $navisFixed = get_theme_mod('nav_isfixed'.$suffix, '1');

            if($navisFixed == 1){
                return 1;
            }
        }

        return "";
    }

    public static function generate_fi_mouseover_blur(){
        $return = "";
        $mod = get_theme_mod('fi_mo_do_blur', '');
        if($mod == '1'){

            $filterMarkup = 'blur(2px) ';

            $mod = get_theme_mod('fi_mo_change_brightness', '');
            if($mod == "1"){
                $mod = (int)get_theme_mod('fi_mo_brightness', 50);
                $mod /= 100;
                if($mod < 0){ $mod = 0; }
                else if($mod > 2){ $mod = 2; }
                $filterMarkup .= 'brightness('.$mod.') ';
            }
            $mod = get_theme_mod('fi_mo_change_grayscale', '');
            if($mod == "1"){
                $mod = (int)get_theme_mod('fi_mo_grayscale', 50);
                $mod = max(0, min(100, $mod)) / 100;
                $filterMarkup .= 'grayscale('.$mod.')';
            }

            if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_on_tap_alt' ) {
                $return =
                '.thumb:hover .ph{
                    -webkit-filter: '.$filterMarkup.';
                    filter: '.$filterMarkup.';
                }';
            } else {
                $return =
                '.no-touchdevice .thumb:hover .ph, .touchdevice .thumb.hover .ph{
                    -webkit-filter: '.$filterMarkup.';
                    filter: '.$filterMarkup.';
                }';
            }



            return $return;
        }
    }

    public static function generate_fi_mouseover_animate_blur(){
        $return = "";
        if ( get_theme_mod( 'fi_mo_do_blur', '' ) != '1' ) {
            return $return;
        }
        $mod = get_theme_mod('fi_animate_blur', '1');
        if($mod == '1'){
            $return =
            '.thumb .ph{
                transition: -webkit-filter 400ms ease-out;
            }';
            return $return;
        }
    }

    public static function generate_fi_mouseover_animate_bgcolor_css(){
        $return = "";
        $mod = get_theme_mod('fi_animate_color', '1');
        if($mod == '1'){
            $return =
            '.thumb .ph span{
                -webkit-transition: all 400ms ease-out;
                -moz-transition: all 400ms ease-out;
                transition: all 400ms ease-out;
            }';
            return $return;
        }
    }

    public static function generate_fi_mouseover_animate_brightness_css(){
        $return = "";
        $mod = get_theme_mod('fi_mo_animate_brightness', false);
        if($mod == '1' || $mod === true){
            $return =
            '.thumb .ph{
                -webkit-transition: all 400ms ease-out;
                -moz-transition: all 400ms ease-out;
                transition: all 400ms ease-out;
            }';
            return $return;
        }
    }

    public static function generate_mouseover_animate_visibility_css( $selector, $mod_name ){
        $return = "";
        $mod = get_theme_mod($mod_name, '1');
        if($mod == '1'){
            $return =
            $selector.'{
                -webkit-transition: all 400ms ease-out;
                -moz-transition: all 400ms ease-out;
                transition: all 400ms ease-out;
            }';
            return $return;
        }
    }

    public static function generate_fi_mouseover_zoom_css(){
        $return = '';
        $mod = get_theme_mod('fi_mo_do_zoom', 'none');
        // case '1': zoom in
        switch ($mod) {
            case '1':
                if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_on_tap_alt' ) {
                    $return =
                    '.thumb:hover img,
                    .thumb:hover video{
                        -webkit-transform: translateZ(0) scale(1.05);
                        -moz-transform: translateZ(0) scale(1.05);
                        -ms-transform: translateZ(0) scale(1.05);
                        -o-transform: translateZ(0) scale(1.05);
                        transform: translateZ(0) scale(1.05);
                    }';
                } else {
                    $return =
                    '.no-touchdevice .thumb:hover img, .touchdevice .thumb.hover img,
                    .no-touchdevice .thumb:hover video, .touchdevice .thumb.hover video{
                        -webkit-transform: translateZ(0) scale(1.05);
                        -moz-transform: translateZ(0) scale(1.05);
                        -ms-transform: translateZ(0) scale(1.05);
                        -o-transform: translateZ(0) scale(1.05);
                        transform: translateZ(0) scale(1.05);
                    }';
                }

            break;
            case 'zoom-out':
                if( LayFrontend_Options::$fi_mo_touchdevice_behaviour == 'mo_on_tap_alt' ) {
                    // zoom out image and background color
                    $return =
                    '.thumb:hover img,
                    .thumb:hover video{
                        -webkit-transform: translateZ(0) scale(0.97);
                        -moz-transform: translateZ(0) scale(0.97);
                        -ms-transform: translateZ(0) scale(0.97);
                        -o-transform: translateZ(0) scale(0.97);
                        transform: translateZ(0) scale(0.97);
                    }
                    .thumb:hover .ph span{
                        -webkit-transform: translateZ(0) scale(0.97);
                        -moz-transform: translateZ(0) scale(0.97);
                        -ms-transform: translateZ(0) scale(0.97);
                        -o-transform: translateZ(0) scale(0.97);
                        transform: translateZ(0) scale(0.97);
                    }';
                } else {
                    // zoom out image and background color
                    $return =
                    '.no-touchdevice .thumb:hover img, .touchdevice .thumb.hover img,
                    .no-touchdevice .thumb:hover video, .touchdevice .thumb.hover video{
                        -webkit-transform: translateZ(0) scale(0.97);
                        -moz-transform: translateZ(0) scale(0.97);
                        -ms-transform: translateZ(0) scale(0.97);
                        -o-transform: translateZ(0) scale(0.97);
                        transform: translateZ(0) scale(0.97);
                    }
                    .no-touchdevice .thumb:hover .ph span, .touchdevice .thumb.hover .ph span{
                        -webkit-transform: translateZ(0) scale(0.97);
                        -moz-transform: translateZ(0) scale(0.97);
                        -ms-transform: translateZ(0) scale(0.97);
                        -o-transform: translateZ(0) scale(0.97);
                        transform: translateZ(0) scale(0.97);
                    }';
                }

            break;
        }
        return $return;
    }

    public static function nav_generate_menupoints_spacebetween_css_flex( $selector, $mod_name, $default, $prefix='', $postfix='', $arrangement_mod_name='' ) {
        $return = '';
        $mod = get_theme_mod($mod_name, $default);
        $arrangement = get_theme_mod($arrangement_mod_name, 'horizontal');

        $arrangement_style_phone = '';
        $arrangement_style = '';
        if($arrangement == "horizontal") {
            $arrangement_style = 'flex-direction:row;';
        }
        else if($arrangement == "vertical") {
            $arrangement_style = 'flex-direction:column;';
        }

        if ( ! empty( $mod ) ) {
            $return = $selector.' {gap:'.$prefix.$mod.$postfix.';}';
        }
        $return .= $selector.' {'.$arrangement_style.';}';
        return $return;
    }
    
    public static function nav_generate_menupoints_spacebetween_css( $selector, $mod_name, $default, $prefix='', $postfix='', $arrangement_mod_name='' ) {
        $return = '';
        $mod = get_theme_mod($mod_name, $default);
        $arrangement = get_theme_mod($arrangement_mod_name, 'horizontal');
        if($arrangement == "horizontal"){
            $style = "margin-right";
        }
        else if($arrangement == "vertical"){
            $style = "margin-bottom";
        }
        if ( ! empty( $mod ) ) {
                $return = sprintf('%s { %s:%s; }',
                $selector,
                $style,
                $prefix.$mod.$postfix
            );
        }
        return $return;
    }

    public static function nav_generate_menupoints_arrangement($selector, $mod_name){
        $return = $selector;
        $arrangement = get_theme_mod($mod_name, 'horizontal');
        if($arrangement == 'vertical'){
            $return .= '{display: block; }';
        }
        else if($arrangement == 'horizontal'){
            $return .= '{display: inline-block;}';
        }
        return $return;
    }

    public static function get_navbar_position(){
        $nav_position = get_theme_mod('nav_position', 'top-right');
        if (strpos($nav_position,'bottom') !== false){
            return 'bottom';
        }
        else if (strpos($nav_position,'top') !== false){
            return 'top';
        }       
    }

    public static function generate_navbar_position(){
        $nav_position = get_theme_mod('nav_position', 'top-right');
        if (strpos($nav_position,'bottom') !== false){
            return '.navbar{ bottom:0; top: auto; }';
        }
        else if (strpos($nav_position,'top') !== false){
            return '.navbar{ top:0; bottom: auto; }';
        }
    }

    public static function generate_navbar_border_color_css(){
        $nav_position = get_theme_mod('nav_position', 'top-right');
        $navbar_show_border = get_theme_mod('navbar_show_border', '');

        if($navbar_show_border == "1"){
            if (strpos($nav_position,'bottom') !== false){
                return '.navbar{ border-top-style: solid; border-top-width: 1px; }';
            }
            else if (strpos($nav_position,'top') !== false){
                return '.navbar{ border-bottom-style: solid; border-bottom-width: 1px; }';
            }
        }
    }

    public static function generate_featured_image_brightness_css($selector, $mod_name, $default){
        $return = $selector;
        $mod = (int)get_theme_mod($mod_name, $default);
        $mod /= 100;

        if($mod < 0){ $mod = 0; }
        else if($mod > 2){ $mod = 2; }
        $return .= '{filter: brightness('.$mod.'); -webkit-filter: brightness('.$mod.');}';

        return $return;
    }

    public static function generate_fi_mouseover_brightness_grayscale_css($selector) {
        $parts = array();
        if ( get_theme_mod('fi_mo_change_brightness', '') == '1' ) {
            $mod = (int) get_theme_mod('fi_mo_brightness', 50);
            $mod /= 100;
            $mod = max(0, min(2, $mod));
            $parts[] = 'brightness(' . $mod . ')';
        }
        if ( get_theme_mod('fi_mo_change_grayscale', '') == '1' ) {
            $mod = (int) get_theme_mod('fi_mo_grayscale', 50);
            $mod = max(0, min(100, $mod)) / 100;
            $parts[] = 'grayscale(' . $mod . ')';
        }
        if ( empty( $parts ) ) {
            return '';
        }
        $filter = implode( ' ', $parts );
        return $selector . '{filter: ' . $filter . '; -webkit-filter: ' . $filter . ';}';
    }

    public static function generate_featured_image_opacity_css($selector, $mod_name, $default){
        $return = $selector;
        $mod = (int)get_theme_mod($mod_name, $default);
        $mod /= 100;

        if($mod < 0){ $mod = 0; }
        else if($mod > 1){ $mod = 1; }
        $return .= '{opacity: '.$mod.';}';

        return $return;
    }

    public static function generate_brightness_css($selector, $mod_name, $default){
        $return = $selector;
        $mod = (int)get_theme_mod($mod_name, $default);
        $mod /= 100;

        if($mod < 0){ $mod = 0; }

        $return .= '{filter: brightness('.$mod.'); -webkit-filter: brightness('.$mod.');}';

        return $return;
    }

    public static function generate_opacity_css($selector, $mod_name, $default, $important = false){
        $return = $selector;
        $mod = (int)get_theme_mod($mod_name, $default);
        $mod /= 100;

        if($mod < 0){ $mod = 0; }
        else if($mod > 1){ $mod = 1; }

        $important_css = $important ? '!important' : '';
        $return .= '{opacity: '.$mod.$important_css.';}';

        return $return;
    }

    public static function generate_opacity_css_from_option($selector, $option_name, $default){
        $return = $selector;
        $val = (int)get_option($option_name, $default);
        $val /= 100;

        if($val < 0){ $val = 0; }
        else if($val > 1){ $val = 1; }

        $return .= '{opacity: '.$val.';}';

        return $return;
    }

    public static function generate_hide_mobile_menu(){
        $mod = get_theme_mod('mobile_hide_menu', 0);
        if($mod == '1'){
            return
            '.navbar{
                background-color: transparent!important;
                border-bottom: none!important;
                height: 0!important;
                min-height: 0!important;
            }
            .mobile-title.text{
                min-height: 0!important;
            }
            .lay-mobile-icons-wrap{
                display: none;
            }
            body{
                padding-top: 0!important;
            }
            nav.mobile-nav{
                display: none;
            }';
        }
    }

    public static function generate_hide_css($selector, $mod_name, $inline = false){
        $return = $selector;
        $mod = get_theme_mod($mod_name, 0);

        if($mod == '1'){
            $return .= '{display: none;}';
        }
        else{
            $block = $inline ? 'inline-block' : 'block';
            $return .= '{display: '.$block.';}';
        }
        return $return;
    }

    public static function pt_generate_position_css($selector){
        $return = $selector;
        $mod = get_theme_mod('pt_position', 'below-image');

        switch($mod){
            case 'on-image-top-left':
                $return .= '{
                    top: '.get_theme_mod('pt_onimage_spacetop', 10).get_theme_mod('pt_onimage_spacetop_mu', 'px').';
                    left: '.get_theme_mod('pt_onimage_spaceleft', 10).get_theme_mod('pt_onimage_spaceleft_mu', 'px').';
                }';
            break;
            case 'on-image-top-right':
                $return .= '{
                    top: '.get_theme_mod('pt_onimage_spacetop', 10).get_theme_mod('pt_onimage_spacetop_mu', 'px').';
                    right: '.get_theme_mod('pt_onimage_spaceright', 10).get_theme_mod('pt_onimage_spaceright_mu', 'px').';
                }';
            break;
            // (center)
            case 'on-image':
                $return .= '{
                    top: 50%;
                    left: 50%;
                    -webkit-transform: translate(-50%,-50%);
                    -moz-transform: translate(-50%,-50%);
                    -ms-transform: translate(-50%,-50%);
                    -o-transform: translate(-50%,-50%);
                    transform: translate(-50%,-50%);
                }';
            break;
            case 'on-image-bottom-left':
                $return .= '{
                    bottom: '.get_theme_mod('pt_onimage_spacebottom', 10).get_theme_mod('pt_onimage_spacebottom_mu', 'px').';
                    left: '.get_theme_mod('pt_onimage_spaceleft', 10).get_theme_mod('pt_onimage_spaceleft_mu', 'px').';
                }';
            break;
            case 'on-image-bottom-right':
                $return .= '{
                    bottom: '.get_theme_mod('pt_onimage_spacebottom', 10).get_theme_mod('pt_onimage_spacebottom_mu', 'px').';
                    right: '.get_theme_mod('pt_onimage_spaceright', 10).get_theme_mod('pt_onimage_spaceright_mu', 'px').';
                }';
            break;
            case 'below-image':
                $return .= '{}';
            break;
            case 'above-image':
                $return .= '{}';
            break;
        }

        return $return;
    }

    public static function generate_visibility_css($selector, $mod_name){
        $return = $selector;
        $mod = get_theme_mod($mod_name, 'always-show');

        if($mod == 'show-on-mouseover'){
            $return .= '{opacity: 0;}';
            return $return;
        }
        else if($mod == 'hide'){
            $return .= '{display:none!important;}';
            return $return;
        }

    }

    public static function generate_pt_gradient_overlay_css() {
        $top = get_theme_mod( 'pt_gradient_top', false );
        $bottom = get_theme_mod( 'pt_gradient_bottom', false );
        $color = get_theme_mod( 'pt_gradient_color', '#000000' );
        $opacity = (int) get_theme_mod( 'pt_gradient_opacity', 50 );
        $opacity = max( 0, min( 100, $opacity ) );
        $rgba = CSS_Output::get_color_with_opacity( $color, $opacity );

        $return = '';
        $sel_before = '.thumb-rel::before';
        $sel_after = '.thumb-rel::after';

        if ( $top ) {
            $return .= $sel_before . '{content:"";position:absolute;top:0;left:0;right:0;height:30%;background:linear-gradient(to bottom,' . $rgba . ',transparent);pointer-events:none;z-index:1;}';
        } else {
            $return .= $sel_before . '{display:none;}';
        }
        if ( $bottom ) {
            $return .= $sel_after . '{content:"";position:absolute;bottom:0;left:0;right:0;height:30%;background:linear-gradient(to top,' . $rgba . ',transparent);pointer-events:none;z-index:1;}';
        } else {
            $return .= $sel_after . '{display:none;}';
        }
        return $return;
    }

    public static function generate_is_fixed_css($selector, $mod_name){
        $return = $selector;
        $mod = get_theme_mod($mod_name, '1');

        if($mod == '1'){
            $return .= '{position: fixed;}';
        }
        else{
            $return .= '{position: absolute;}';
        }
        return $return;

    }

    public static function generate_position_css($selector, $mod_name, $default){
        $return = $selector;
        $mod = get_theme_mod($mod_name, $default);
        if ( ! empty( $mod ) ) {
            switch($mod){
                case 'top-left':
                    $return .= '{bottom: auto; right: auto;}';
                break;
                case 'top-center':
                    $return .=
                    '{bottom: auto; right: auto; left: 50%;
                    -webkit-transform: translateX(-50%);
                    -moz-transform: translateX(-50%);
                    -ms-transform: translateX(-50%);
                    -o-transform: translateX(-50%);
                    transform: translateX(-50%);}';
                break;
                case 'top-right':
                    $return .= '{bottom: auto; left: auto;}';
                break;
                case 'center-left':
                    $return .=
                    '{bottom: auto; right: auto; top:50%;
                    -webkit-transform: translate(0, -50%);
                    -moz-transform: translate(0, -50%);
                    -ms-transform: translate(0, -50%);
                    -o-transform: translate(0, -50%);
                    transform: translate(0, -50%);}';
                break;
                case 'center':
                    $return .=
                    '{bottom: auto; right: auto; left: 50%; top:50%;
                    -webkit-transform: translate(-50%, -50%);
                    -moz-transform: translate(-50%, -50%);
                    -ms-transform: translate(-50%, -50%);
                    -o-transform: translate(-50%, -50%);
                    transform: translate(-50%, -50%);}';
                break;
                case 'center-right':
                    $return .=
                    '{bottom: auto; left: auto; top:50%;
                    -webkit-transform: translate(auto, -50%);
                    -moz-transform: translate(auto, -50%);
                    -ms-transform: translate(auto, -50%);
                    -o-transform: translate(auto, -50%);
                    transform: translate(auto, -50%);}';
                break;
                case 'bottom-left':
                    $return .= '{top: auto; right: auto;}';
                break;
                case 'bottom-center':
                    $return .=
                    '{top: auto; right: auto; left: 50%;
                    -webkit-transform: translateX(-50%);
                    -moz-transform: translateX(-50%);
                    -ms-transform: translateX(-50%);
                    -o-transform: translateX(-50%);
                    transform: translateX(-50%);}';
                break;
                case 'bottom-right':
                    $return .= '{top: auto; left: auto;}';
                break;
            }
            return $return;
        }
    }

    // http://codex.wordpress.org/Theme_Customization_API#Sample_Theme_Customization_Class
    public static function generate_css( $selector, $style, $mod_name, $default, $prefix='', $postfix='' ) {
        $return = '';
        $mod = get_theme_mod($mod_name, $default);
        if ( isset( $mod ) ) {
            // Handle backdrop-filter with webkit prefix
            if ( $style === 'backdrop-filter' ) {
                $return = sprintf('%s { -webkit-backdrop-filter:%s; backdrop-filter:%s; }',
                $selector,
                $prefix.$mod.$postfix,
                $prefix.$mod.$postfix
                );
            } else {
                $return = sprintf('%s { %s:%s; }',
                $selector,
                $style,
                $prefix.$mod.$postfix
                );
            }
        }
        return $return;
    }

    public static function generate_rgba( $selector, $style, $mod_name, $default, $opacity ) {
        $return = '';
        $color = get_theme_mod($mod_name, $default);
        if ( isset( $color ) ) {
            $rgb_array = CSS_Output::hex2rgb($color);
            $opacity = (int)$opacity / 100;
            $rgba = 'rgba('.$rgb_array['red'].','.$rgb_array['green'].','.$rgb_array['blue'].','.$opacity.')';
            $return = sprintf('%s { %s:%s; }', $selector, $style, $rgba);
        }
        return $return;
    }

    public static function generate_rgba_from_option( $selector, $style, $mod_name, $default, $opacity ) {
        $return = '';
        $color = get_option($mod_name, $default);
        if ( isset( $color ) ) {
            $rgb_array = CSS_Output::hex2rgb($color);
            $opacity = (int)$opacity / 100;
            $rgba = 'rgba('.$rgb_array['red'].','.$rgb_array['green'].','.$rgb_array['blue'].','.$opacity.')';
            $return = sprintf('%s { %s:%s; }', $selector, $style, $rgba);
        }
        return $return;
    }

    public static function get_color_with_opacity( $color, $opacity ) {
        if ( $color === '' || $color === null || $color === 'transparent' ) {
            return 'transparent';
        }

        $opacity = is_numeric( $opacity ) ? (float) $opacity : 100;
        $opacity = max( 0, min( 100, $opacity ) );

        if ( $opacity >= 100 ) {
            return $color;
        }

        if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
            $rgb = CSS_Output::hex2rgb( $color );
            if ( $rgb !== false ) {
                $alpha = $opacity / 100;
                return 'rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.')';
            }
        }

        return $color;
    }

    public static function get_mobile_menu_txt_color(){
        $txtColor = get_theme_mod('nav_color', false);
        if($txtColor == false){

            $formatsJsonString = FormatsManager::getDefaultFormatsJson();
            $formatsJsonArr = json_decode($formatsJsonString, true);

            for($i = 0; $i<count($formatsJsonArr); $i++) {
                if($formatsJsonArr[$i]["formatname"] == "Default"){
                    $txtColor = $formatsJsonArr[$i]["color"];
                }
            }
        }
        return $txtColor;
    }

    public static function get_mobile_menu_light_color(){
        $bgColor = CSS_Output::get_mobile_basecolor();
        $lighterBgColor = CSS_Output::tintColor($bgColor, 7);
        return $lighterBgColor;
    }

    public static function get_mobile_menu_dark_color(){
        $bgColor = CSS_Output::get_mobile_basecolor();
        $darker = CSS_Output::tintColor($bgColor, -7);
        return $darker;
    }

    public static function get_mobile_basecolor(){
        $bgColor = '';

		$navbar_hide_default = get_theme_mod('navbar_hide', 'value_was_never_set');
		if($navbar_hide_default == 'value_was_never_set'){
			$navbar_hide_default = true;
		} else {
			$navbar_hide_default = false;
		}
        $navbar_hide = get_theme_mod('navbar_hide', $navbar_hide_default);

        if($navbar_hide != '1') {
            $bgColor = get_theme_mod('navbar_color', '#ffffff');
            $navbar_opacity = get_theme_mod('navbar_opacity', '90');
            $offset = 100 - (int)$navbar_opacity;
            $bgColor = CSS_Output::tintColor($bgColor, $offset);
        }
        else{
            $bgColor = get_theme_mod('bg_color', '#ffffff');
        }
        return $bgColor;
    }

    // http://www.kirupa.com/forum/showthread.php?366845-Make-hex-colours-lighter-with-PHP-(using-existing-code)
    public static function tintColor($color, $per){
        $color = substr( $color, 1 ); // Removes first character of hex string (#)
        if(strlen($color) == 3){
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        $rgb = ''; // Empty variable
        $per = $per/100*255; // Creates a percentage to work with. Change the middle figure to control color temperature

        if  ($per < 0 ){
            // DARKER
            $per =  abs($per); // Turns Neg Number to Pos Number
            for ($x=0;$x<3;$x++){
                $c = hexdec(substr($color,(2*$x),2)) - $per;
                $c = ($c < 0) ? 0 : dechex((int)$c);
                $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
            }
        }
        else{
            // LIGHTER
            for ($x=0;$x<3;$x++){
                $c = hexdec(substr($color,(2*$x),2)) + $per;
                $c = ($c > 255) ? 'ff' : dechex((int)$c);
                $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
            }
        }
        return '#'.$rgb;
    }

    

    public static function hex2rgb( $color ) {
        if(empty($color)) {
            return array( 'red' => 0, 'green' => 0, 'blue' => 0 );
        }
        if ( $color[0] == '#' ) {
                $color = substr( $color, 1 );
        }
        if ( strlen( $color ) == 6 ) {
                list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return false;
        }
        $r = hexdec( $r );
        $g = hexdec( $g );
        $b = hexdec( $b );
        return array( 'red' => $r, 'green' => $g, 'blue' => $b );
    }

    public static function echo_lay_customize_css(){
        echo CSS_Output::lay_customize_css();
    }

}
new CSS_Output();