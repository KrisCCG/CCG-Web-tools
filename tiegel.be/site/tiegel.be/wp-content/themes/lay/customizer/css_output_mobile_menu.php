<?php
class CSS_Output_Mobile_Menu{

    public function __construct(){
        add_action( 'wp_enqueue_scripts', array( $this, 'lay_customize_css' ), 20 );
    }

    // when using style_2 or style_3, the menu div must always be fixed
    // the burger icon and menubar can be position absolute / not fixed
    public static function get_mobile_menu_isfixed(){
        $menu_style = get_theme_mod('mobile_menu_style', 'style_1');
        if( $menu_style == 'style_2' || $menu_style == 'style_3' || $menu_style == 'style_4' ) {
           return '1';
        } else {
            return get_theme_mod('mobile_menu_isfixed', '1');
        }
    }

    public static function generate_is_fixed_css($selector, $is_fixed){
        $return = $selector;

        if($is_fixed == '1'){
            $return .= '{position: fixed;}';
        }
        else{
            $return .= '{position: absolute;}';
        }
        return $return;

    }

    public static function lay_customize_css(){
        $mobile_menu_style = get_theme_mod('mobile_menu_style', 'style_1');
        $mobileStyles = "";
        $sharedStyles = "";

        switch( $mobile_menu_style ) {
            case 'style_1':
            break;
            case 'style_2':
            case 'style_3':
            case 'style_4':
                $spacetop_mu = CSS_Output::get_mu('mobile_menu_spacetop_mu', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav>ul', 'padding-top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
                if ( get_theme_mod('mobile_menu_vertically_center_menupoints', false) ) {
                    $mobileStyles .= 'nav.mobile-nav{display:flex;flex-direction:column;justify-content:center;}';
                    $y_offset = get_theme_mod('mobile_menu_vertically_center_y_offset', 0);
                    if ( $y_offset !== 0 && $y_offset !== '' ) {
                        $mobileStyles .= 'nav.mobile-nav>ul{margin-top:' . intval( $y_offset ) . 'px;}';
                    }
                }
            break;            
            case 'style_desktop_menu':
            break;
            case 'pill_menu_style':
                // Generate pill style CSS for mobile menu
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'padding', 'mobile_pill_menu_padding', '15', '', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'backdrop-filter', 'mobile_pill_menu_background_blur', '10', 'blur(', 'px)');
                
                // Generate background color with transparency
                $mobileStyles .= CSS_Output::generate_rgba('nav.mobile-nav', 'background-color', 'mobile_pill_menu_color', '#BDBDBD', get_theme_mod('mobile_pill_menu_transparency', 60));
                
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'border-radius', 'mobile_pill_menu_border_radius', '32', '', 'px');
                
                // Pill element styles - Mobile
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav .mobile-menu-pill', 'padding', 'mobile_pill_padding', '8', '', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav .mobile-menu-pill', 'background', 'mobile_pill_color', '#ffffff', '', '');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav .mobile-menu-pill', 'border-radius', 'mobile_pill_border_radius', '28', '', 'px');
            break;
        }

        // Add positioning for pill menu style
        if(get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style'){
			$position = get_theme_mod('mobile_menu_position', 'right');
            switch($position){
                case 'left':
					// this is actually top-left
					$spaceleft_mu = CSS_Output::get_mu('mobile_menu_spaceleft_mu', '%');
					$spacetop_mu = CSS_Output::get_mu('mobile_menu_spacetop_mu', 'px');
					$mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'left', 'mobile_menu_spaceleft', '5', '', $spaceleft_mu);
					$mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
				break;
                case 'right':
					// this is actually top-right
					$spaceright_mu = CSS_Output::get_mu('mobile_menu_spaceright_mu', '%');
					$spacetop_mu = CSS_Output::get_mu('mobile_menu_spacetop_mu', 'px');
					$mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'right', 'mobile_menu_spaceright', '5', '', $spaceright_mu);
					$mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
				break;
				case 'center':
					// this is actually top-center
					$mobileStyles .=
					'nav.mobile-nav{right: auto; left: 50%;
					-webkit-transform: translateX(-50%);
					-moz-transform: translateX(-50%);
					-ms-transform: translateX(-50%);
					-o-transform: translateX(-50%);
					transform: translateX(-50%);}';
					$spacetop_mu = CSS_Output::get_mu('mobile_menu_spacetop_mu', 'px');
					$mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
                break;
                case 'bottom-left':
                    $spaceleft_mu = CSS_Output::get_mu('mobile_menu_spaceleft_mu', '%');
                    $spacebottom_mu = CSS_Output::get_mu('mobile_menu_spacebottom_mu', 'px');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'left', 'mobile_menu_spaceleft', '5', '', $spaceleft_mu);
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                break;
                case 'bottom-center':
                    $spacebottom_mu = CSS_Output::get_mu('mobile_menu_spacebottom_mu', 'px');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                    $mobileStyles .=
                    'nav.mobile-nav{right: auto; left: 50%;
                    -webkit-transform: translateX(-50%);
                    -moz-transform: translateX(-50%);
                    -ms-transform: translateX(-50%);
                    -o-transform: translateX(-50%);
                    transform: translateX(-50%);}';
                break;
                case 'bottom-right':
                    $spaceright_mu = CSS_Output::get_mu('mobile_menu_spaceright_mu', '%');
                    $spacebottom_mu = CSS_Output::get_mu('mobile_menu_spacebottom_mu', 'px');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'right', 'mobile_menu_spaceright', '5', '', $spaceright_mu);
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                break;
            }

            $val = get_theme_mod('mobile_menu_spacebetween', '5');
            $mobileStyles .= '.laynav.mobile-nav ul{
                gap: '.$val.'px;
            }';
        }

        // mobile menu
        $txtColor = Customizer::$defaults['mobile_menu_txt_color'];
        $lighterBgColor = Customizer::$defaults['mobile_menu_light_color'];
        $darker = Customizer::$defaults['mobile_menu_dark_color'];

        $mobileStyles .= CSS_Output::generate_hide_mobile_menu();

        // mobile icon left or right
        $mobile_menu_icon_position = get_theme_mod('burger_icon_position', 'right');
        if($mobile_menu_icon_position == 'left'){
            $mobileStyles .= '.lay-mobile-icons-wrap{left: 0;}';
        } else {
            $mobileStyles .= '.lay-mobile-icons-wrap{right: 0;}';
        }

        if(get_theme_mod('mobile_menu_style', 'style_1') == 'style_desktop_menu' || get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style'){

            // getting it here for hide on scroll down and other positioning:
            $spacetop_mu = CSS_Output::get_mu('mobile_menu_spacetop_mu', 'px');
            $spacetop = get_theme_mod('mobile_menu_spacetop', '12');
            $spacebottom_mu = CSS_Output::get_mu('mobile_menu_spacebottom_mu', 'px');
            $spacebottom = get_theme_mod('mobile_menu_spacebottom', '12');

            $pos = get_theme_mod('mobile_menu_position', 'right');

            switch($pos){
                case 'left':
                    // top left
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
                    $spaceleft_mu = CSS_Output::get_mu('mobile_menu_spaceleft_mu', '%');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'left', 'mobile_menu_spaceleft', '5', '', $spaceleft_mu);
                    break;
                case 'right':
                    // top right
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
                    $spaceright_mu = CSS_Output::get_mu('mobile_menu_spaceright_mu', '%');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'right', 'mobile_menu_spaceright', '5', '', $spaceright_mu);
                break;
                case 'center':
                    // top center
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'top', 'mobile_menu_spacetop', '12', '', $spacetop_mu);
                    $mobileStyles .=
                    'nav.mobile-nav{right: auto; left: 50%;
                    -webkit-transform: translateX(-50%);
                    -moz-transform: translateX(-50%);
                    -ms-transform: translateX(-50%);
                    -o-transform: translateX(-50%);
                    transform: translateX(-50%);}';
                break;
                case 'bottom-left':
                    $spaceleft_mu = CSS_Output::get_mu('mobile_menu_spaceleft_mu', '%');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'left', 'mobile_menu_spaceleft', '5', '', $spaceleft_mu);
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                break;
                case 'bottom-center':
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                    $mobileStyles .=
                    'nav.mobile-nav{right: auto; left: 50%;
                    -webkit-transform: translateX(-50%);
                    -moz-transform: translateX(-50%);
                    -ms-transform: translateX(-50%);
                    -o-transform: translateX(-50%);
                    transform: translateX(-50%);}';
                break;
                case 'bottom-right':
                    $spaceright_mu = CSS_Output::get_mu('mobile_menu_spaceright_mu', '%');
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'right', 'mobile_menu_spaceright', '5', '', $spaceright_mu);
                    $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav', 'bottom', 'mobile_menu_spacebottom', '12', '', $spacebottom_mu);
                break;
            }

            // any top position
            if($pos == 'left' || $pos == 'right' || $pos == 'center'){
                // hide on scroll down
                $mobileStyles .= 'body.lay-hide-phone-menu .mobile_menu_move_away_when_scrolling_down:not(.mobile-menu-position-center) {
                    transform: translateY(calc(-'.$spacetop.$spacetop_mu.' - 100% - 10px));
                }';
                $mobileStyles .= 'body.lay-hide-phone-menu .mobile_menu_move_away_when_scrolling_down.mobile-menu-position-center {
                    transform: translate(-50%, calc(-'.$spacetop.$spacetop_mu.' - 100% - 10px));
                }';
            }

            // bottom position
            if($pos == 'bottom-left' || $pos == 'bottom-center' || $pos == 'bottom-right'){
                $mobileStyles .= 'body.lay-hide-phone-menu .mobile_menu_move_away_when_scrolling_down:not(.mobile-menu-position-center) {
                    transform: translateY(calc('.$spacebottom.$spacebottom_mu.' + 100% + 10px));
                }';
                $mobileStyles .= 'body.lay-hide-phone-menu .mobile_menu_move_away_when_scrolling_down.mobile-menu-position-center {
                    transform: translate(-50%, calc('.$spacebottom.$spacebottom_mu.' + 100% + 10px));
                }';
            }


            $mobileStyles .= CSS_Output::nav_generate_menupoints_arrangement('nav.mobile-nav li', 'mobile_menu_arrangement');
            // $selector, $mod_name, $default, $prefix='', $postfix='', $arrangement_mod_name
            $mobileStyles .= CSS_Output::nav_generate_menupoints_spacebetween_css('nav.mobile-nav li', 'mobile_menu_spacebetween', '5', '', 'px', 'mobile_menu_arrangement');
            if( get_theme_mod('mobile_menu_arrangement', 'horizontal') == 'vertical' ) {
                $val = get_theme_mod('mobile_menu_spacebetween', '5');
                $mobileStyles .= '.laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style).arrangement-vertical .sub-menu{
                    margin-top: '.$val.'px;
                }';
            }

            $mobile_submenu_offset = get_theme_mod('mobile_menu_spacebetween', '5');
            $mobileStyles .= '.laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style, .mobile-menu-style-pill_menu_style).arrangement-horizontal .menu-item-has-children.show-submenu-mobile > .sub-menu{
                top: calc(100% + '.$mobile_submenu_offset.'px);
            }';
            $mobileStyles .= '.laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style, .mobile-menu-style-pill_menu_style).arrangement-horizontal.mobile-menu-position-bottom-left .menu-item-has-children.show-submenu-mobile > .sub-menu,
                .laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style, .mobile-menu-style-pill_menu_style).arrangement-horizontal.mobile-menu-position-bottom-center .menu-item-has-children.show-submenu-mobile > .sub-menu,
                .laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style, .mobile-menu-style-pill_menu_style).arrangement-horizontal.mobile-menu-position-bottom-right .menu-item-has-children.show-submenu-mobile > .sub-menu{
                top: auto!important;
                bottom: calc(100% + '.$mobile_submenu_offset.'px)!important;
            }';

            if( !get_theme_mod('menu_submenu_allow_textwrap', false) ) {
                $mobileStyles .= '.laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style) .sub-menu .menu-item a{white-space:nowrap!important;}';
            }

            // On mobile, submenu button/pill styling should only depend on the mobile menu style.
            // `menu_submenu_use_button_style` remains a desktop/tablet-only setting.
            if( $mobile_menu_style == 'button_menu_style' ) {
                $submenu_button_slug = get_theme_mod('mobile_menu_button_style_variant', 'laybutton1');
                if( !in_array($submenu_button_slug, array('laybutton1', 'laybutton2', 'laybutton3'), true) ) {
                    $submenu_button_slug = 'laybutton1';
                }

                $submenu_space_between_submenu_points = get_theme_mod('mobile_menu_spacebetween', 5);

                $submenu_button_selector = '.laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item > a';
                $submenu_button_hover_selector_base = '.no-touchdevice .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item > a:hover';
                $submenu_button_hover_selector = $submenu_button_hover_selector_base;
                $submenu_button_active_selector = '.laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item.current-menu-item > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item.current_page_item > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item.current-menu-parent > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item.current-menu-ancestor > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item.current-lang > a';

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
                    $submenu_button_hover_selector = '.no-touchdevice .laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item' . $not_parts . ' > a:hover';
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

                $mobileStyles .= FormatsManager::get_textformat_css_for_selector(
                    get_theme_mod($submenu_button_slug . '_textformat', 'Default'),
                    $submenu_button_selector,
                    array('spaces' => false, 'important' => $force_styles, 'media_queries' => true, 'include_padding_bottom' => false)
                );

                $mobileStyles .=
                    '.laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu{
                        text-align:left!important;
                    }';

                $mobileStyles .=
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
                // Keep last submenu button padding consistent when base submenu CSS sets last-child padding-bottom to 0.
                $mobileStyles .=
                    '.laynav.mobile-nav.mobile-menu-style-button_menu_style .sub-menu .menu-item:last-child > a{
                        padding-bottom:' . get_theme_mod($submenu_button_slug . '_paddingbottom', 5) . 'px' . $important . ';
                    }';

                if ($use_hover_state) {
                    $submenu_transition = get_theme_mod($submenu_button_slug . '_animate_mouseover', false) == true
                        ? 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease' . $important . ';'
                        : '';
                    $mobileStyles .=
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
                    $mobileStyles .=
                        $submenu_button_active_selector .
                        '{
                            color:' . get_theme_mod($submenu_button_slug . '_active_text_color', '#000000') . $important . ';
                            background-color:' . $active_bg . $important . ';
                            border:' . $stable_border_width . 'px solid ' . $active_border_color . $important . ';
                            '.$submenu_active_transition.'
                        }';
                }

                // Ensure vertical gap between submenu buttons comes from "Space between Submenu Points".
                // We use CSS `gap` instead of margin-bottom so button padding/height stays stable.
                // Scope to the "open" state to not override the base hide/show submenu logic.
                $mobileStyles .=
                    '.laynav.mobile-nav.mobile-menu-style-button_menu_style .menu-item-has-children.show-submenu-mobile > .sub-menu{
                        display:flex!important;
                        flex-direction:column!important;
                        gap:' . $submenu_space_between_submenu_points . 'px!important;
                    }';
                $mobileStyles .=
                    '.laynav.mobile-nav.mobile-menu-style-button_menu_style .menu-item-has-children.show-submenu-mobile > .sub-menu .menu-item{
                        margin-bottom:0!important;
                    }';
            }

            if( !get_theme_mod('menu_menupoints_allow_textwrap', false) ) {
                $mobileStyles .= '.laynav.mobile-nav:is(.mobile-menu-style-style_desktop_menu, .mobile-menu-style-button_menu_style) > ul > li > a{white-space:nowrap!important;}';
            }
        }

        // todo: test this:
        // mobile menu fixed?
        // when using style 2 or 3, the actual menu must always be fixed, the menubar and menuicons can ne absolute/not fixed
        $menu_div_fixed = CSS_Output_Mobile_Menu::get_mobile_menu_isfixed();
        $mobileStyles .= CSS_Output_Mobile_Menu::generate_is_fixed_css('nav.mobile-nav', $menu_div_fixed);
        // todo: test this with .lay-mobile-icons-wrap
        $mobileStyles .= CSS_Output::generate_is_fixed_css('.navbar, .lay-mobile-icons-wrap', 'mobile_menu_isfixed');


        // mobile site title

        // mobile site title hide, not using "generate_hide_css" here because shown state is display flex, not display block
        $default = get_theme_mod('st_hide', 0);
        $m_st_hide = get_theme_mod('m_st_hide', $default);
        if($m_st_hide == "1"){
            $mobileStyles .= ".mobile-title.text, .mobile-title.image{display:none;}";
        }

        $mobileStyles .= CSS_Output::generate_css('.mobile-title.image img', 'height', 'm_st_img_height', 30, '', 'px');

        $mobile_textstyle = get_theme_mod('m_st_textformat', 'Default');

        if($mobile_textstyle != ''){
            $tstyle = FormatsManager::get_format_css_phone_menu( $mobile_textstyle, '.mobile-title.text', array( 'spaces' => false ) );
            $sharedStyles .= $tstyle;
        } else {
            $default = get_theme_mod('st_fontfamily', Customizer::$defaults['fontfamily']);
            $mobileStyles .= CSS_Output::generate_css('.mobile-title.text', 'font-family', 'm_st_fontfamily', $default, '', '');
    
            $m_st_fontsize_mu = CSS_Output::get_mu('m_st_fontsize_mu', 'px');
            $mobileStyles .= CSS_Output::generate_css('.mobile-title.text', 'font-size', 'mobile_menu_sitetitle_fontsize', Customizer::$defaults['mobile_menu_fontsize'], '', $m_st_fontsize_mu);
    
            $default = get_theme_mod('st_fontweight', Customizer::$defaults['fontweight']);
            $mobileStyles .= CSS_Output::generate_css('.mobile-title.text', 'font-weight', 'm_st_fontweight', $default, '', '');
    
            $default = get_theme_mod('st_letterspacing' , Customizer::$defaults['letterspacing']);
            $mobileStyles .= CSS_Output::generate_css('.mobile-title.text', 'letter-spacing', 'm_st_letterspacing', $default, '', 'em');
        }

        $default = get_theme_mod('st_color', Customizer::$defaults['color']);
        $mobileStyles .= CSS_Output::generate_css('.mobile-title.text', 'color', 'm_st_color', $default, '', '');

        // mobile site title position
        $m_st_position = get_theme_mod('m_st_position', 'center');
        $mobile_hide_menu = get_theme_mod('mobile_hide_menu', 0);

        $m_st_isfixed = get_theme_mod('m_st_isfixed', 1);
        if($m_st_isfixed == "0"){
            $mobileStyles .= '.mobile-title{position:absolute;}';
        }else{
            $mobileStyles .= '.mobile-title{position:fixed;}';
        }

        $m_st_spacetop_mu = CSS_Output::get_mu('m_st_spacetop_mu', 'px');
        $mobileStyles .= CSS_Output::generate_css('.mobile-title', 'top', 'm_st_spacetop', 12, '', $m_st_spacetop_mu);

        if($m_st_position == "center"){
            // use top space
            $mobileStyles .= '.mobile-title{width: 100%}';
        }else if($m_st_position == "left"){
            // use top space and left space
            $m_st_spaceleft_mu = CSS_Output::get_mu('m_st_spaceleft_mu', '%');
            $mobileStyles .= CSS_Output::generate_css('.mobile-title', 'left', 'm_st_spaceleft', 5,'', $m_st_spaceleft_mu);
        }else if($m_st_position == "right"){
            $m_st_spaceright_mu = CSS_Output::get_mu('m_st_spaceright_mu', '%');
            $mobileStyles .= CSS_Output::generate_css('.mobile-title', 'right', 'm_st_spaceright', 5,'', $m_st_spaceright_mu);
        }

        $m_st_spacetop_mu = CSS_Output::get_mu('m_st_spacetop_mu', 'px');
        $m_st_spacetop = get_theme_mod('m_st_spacetop', 12);
        $mobileStyles .= 
        'body.lay-hide-phone-menu .mobile_sitetitle_move_away_when_scrolling_down {
            transform: translateY(calc(-'.$m_st_spacetop.$m_st_spacetop_mu.' - 100% - 10px));
        }';

        // mobile site title position

        if($m_st_position == "center"){
            $mobileStyles .=
            '.navbar{
                text-align: center;
            }
            .mobile-title{
                text-align:center; padding: 0 44px;
            }
            .mobile-title.text > span{
                margin: 0 auto;
            }';
        }else if($m_st_position == "left"){
            $mobileStyles .=
            '.navbar{
                text-align: left;
            }
            .mobile-title{
                text-align:left; padding: 0 44px 0 0;
            }';
        }

        // mobile navbar background color with rgba
        $mobile_menubar_height = 0;
        $mobile_hide_menubar = get_theme_mod('mobile_hide_menubar');
        if($mobile_hide_menubar == 1){
            $mobileStyles .= '.navbar{display:none;}';
            $mobileStyles .= '.lay-mobile-icons-wrap{padding-top:'.get_theme_mod('mobile_menu_icon_burger_spacetop', '10').'px;}';

            if($mobile_menu_icon_position == 'right'){
                $mobileStyles .= '.lay-mobile-icons-wrap{padding-right:'.get_theme_mod('mobile_menu_icon_burger_spaceright', '10').'px;}';
                $hs = get_theme_mod('mobile_menu_icon_burger_spaceright', '10');
                $hs = (int)$hs;
                $mobileStyles .= '.laynav .burger-wrap{padding-left:'.($hs - 3).'px;}';
            } else {
                $mobileStyles .= '.lay-mobile-icons-wrap{padding-left:'.get_theme_mod('mobile_menu_icon_burger_spaceleft', '10').'px;}';
                $hs = get_theme_mod('mobile_menu_icon_burger_spaceleft', '10');
                $hs = (int)$hs;
                $mobileStyles .= '.laynav .burger-wrap{padding-right:'.($hs - 3).'px;}';
            }

            $mobileStyles .= '.laynav .burger-wrap{padding-top:'.get_theme_mod('mobile_menu_icon_burger_spacetop', '10').'px;}';
        }else{
            $mobile_menubar_height = get_theme_mod('mobile_menubar_height', 40);
            // burger top position
            switch( get_theme_mod('burger_icon_type', 'default_thin') ){
                case 'default':
                case 'default_thin':
                    if( $mobile_menubar_height > 20 ){
                        $mobileStyles .= '.burger-wrap{padding-top:'.(($mobile_menubar_height-20)/2).'px;}';
                        if($mobile_menu_icon_position == 'right'){
                            $mobileStyles .= '.lay-mobile-icons-wrap{padding-right:'.(($mobile_menubar_height-20)/2).'px;}';
                            $mobileStyles .= '.laynav .burger-wrap{padding-left:'.((($mobile_menubar_height-20)/2) - 3).'px;}';
                        } else {
                            $mobileStyles .= '.lay-mobile-icons-wrap{padding-left:'.(($mobile_menubar_height-20)/2).'px;}';
                            $mobileStyles .= '.laynav .burger-wrap{padding-right:'.((($mobile_menubar_height-20)/2) - 3).'px;}';
                        }

                    }
                break;
                case 'new':
                    if( $mobile_menubar_height > 20 ){
                        $mobileStyles .= '.burger-wrap{padding-top:'.(($mobile_menubar_height-30)/2).'px;}';
                        if($mobile_menu_icon_position == 'right'){
                            $mobileStyles .= '.lay-mobile-icons-wrap{padding-right:'.(($mobile_menubar_height-30)/2).'px;}';
                            $mobileStyles .= '.laynav .burger-wrap{padding-left:'.((($mobile_menubar_height-30)/2) + 6).'px;}';
                        } else {
                            $mobileStyles .= '.lay-mobile-icons-wrap{padding-left:'.(($mobile_menubar_height-30)/2).'px;}';
                            $mobileStyles .= '.laynav .burger-wrap{padding-right:'.((($mobile_menubar_height-30)/2) + 6).'px;}';
                        }
                    }
                break;
            }

            if( get_theme_mod('lay_woocommerce_cart_icon_mobile_use_text', 0) == 1 ) {
                $mobileStyles .= '.lay-cart-icon-wrap{padding-top:'.get_theme_mod('lay_woocommerce_cart_icon_mobile_text_spacetop', 10).'px;}';
                if( $mobile_menubar_height > 20 ){
                    $mobileStyles .= '.lay-cart-icon-wrap{padding-left:8px;padding-right:8px;}';
                    $mobileStyles .= '.lay-cart-icon-wrap{height:'.($mobile_menubar_height).'px;}';
                }
            } else {
                // woocommerce cart icon height. attention: current cart icon is 20px tall. if i use a different icon height, i need to change that number below
                if( $mobile_menubar_height > 20 ){
                    $offset = -2;
                    $mobileStyles .= '.lay-cart-icon-wrap{padding-top:'.( ( ($mobile_menubar_height-20) / 2 ) + $offset ) .'px;}';
                    $mobileStyles .= '.lay-cart-icon-wrap{padding-left:'.(($mobile_menubar_height-20)/2).'px;}';
                    $mobileStyles .= '.lay-cart-icon-wrap{height:'.($mobile_menubar_height - $offset).'px;}';
                }
            }

            $burgerWrapHeight = (int)$mobile_menubar_height - (int)get_theme_mod('mobile_menu_icon_burger_spacetop', '10');
            $mobileStyles .= '.burger-wrap{height:'.$burgerWrapHeight.'px;}';
        }

        // custom / text burger: outer spacing (padding-top, padding-right/left) from theme mod; same for burger inside nav (e.g. style_2 close button)
        $burger_icon_type = get_theme_mod('burger_icon_type', 'default_thin');
        if( get_theme_mod('mobile_hide_menubar') == 1 || $burger_icon_type == 'custom' || $burger_icon_type == 'text' ) {
            $spacetop = get_theme_mod('mobile_menu_icon_burger_spacetop', '10');
            $spaceright = get_theme_mod('mobile_menu_icon_burger_spaceright', '10');
            $spaceleft = get_theme_mod('mobile_menu_icon_burger_spaceleft', '10');
            $mobileStyles .= '.lay-mobile-icons-wrap{padding-top:'.$spacetop.'px;}';
            $mobileStyles .= '.laynav .burger-wrap{padding-top:'.$spacetop.'px;}';
            if($mobile_menu_icon_position == 'right'){
                $mobileStyles .= '.lay-mobile-icons-wrap{padding-right:'.$spaceright.'px;}';
                $mobileStyles .= '.laynav .burger-wrap{padding-right:'.$spaceright.'px;}';
            } else {
                $mobileStyles .= '.lay-mobile-icons-wrap{padding-left:'.$spaceleft.'px;}';
                $mobileStyles .= '.laynav .burger-wrap{padding-left:'.$spaceleft.'px;}';
            }
        }


        $mobileStyles .= '.burger-custom{width:'.get_theme_mod('mobile_menu_icon_burger_width', '25').'px;}';
        $mobileStyles .= '.mobile-menu-close-custom{width:'.get_theme_mod('mobile_menu_icon_close_width', '25').'px;}';

        // body padding top
        $mobile_hide_menu = get_theme_mod('mobile_hide_menu', 0);
        $mobile_hide_menubar = get_theme_mod('mobile_hide_menubar', 0);
        // note to myself: cannot do body padding-top here, when we use a store notification

        if($mobile_hide_menu != '1' && $mobile_hide_menubar != '1'){
            $mobileStyles .= 'body{padding-top:'.$mobile_menubar_height.'px;}';
            // for phone cover, i need to set a margin-top for the inner row, because top aligned texts in a 100vh row are "position absolute"
            // and i dont want those texts to be underneath the navbar:
            $mobileStyles .= 'body>.lay-content .cover-enabled-on-phone .cover-region{top:'.$mobile_menubar_height.'px;}';
            // fix for positioning
            $mobileStyles .= 'body>.lay-content .cover-enabled-on-phone .cover-region .lay-imagehover-on-element{top:-'.$mobile_menubar_height.'px;}';
            // also, i need to set a height that is calc(100vh - navbarheight):
            $mobileStyles .= CSS_Output_First_Row_100vh_Heights::get_first_row_100vh_heights();
        }

        // Page setting "hide menu": when body has .lay-hide-menu, treat mobile_menubar_height as 0 (same rules, value 0)
        $mobileStyles .= 'body.lay-hide-menu{padding-top:0!important;}';
        $mobileStyles .= 'body.lay-hide-menu>.lay-content .cover-enabled-on-phone .cover-region{top:0!important;}';
        $mobileStyles .= 'body.lay-hide-menu>.lay-content .cover-enabled-on-phone .cover-region .lay-imagehover-on-element{top:0!important;}';
        $mobileStyles .= CSS_Output_First_Row_100vh_Heights::get_first_row_100vh_heights_for_zero_navbar();

        $mobileStyles .= CSS_Output::generate_css('.navbar', 'height', 'mobile_menubar_height', 40, '', 'px');

        $mobile_menu_bar_background_color = get_theme_mod('mobile_menu_bar_color', $lighterBgColor);
        $rgb = CSS_Output::hex2rgb($mobile_menu_bar_background_color);
        $alpha = (int)get_theme_mod('mobile_menu_bar_opacity', 100);
        $alpha /= 100;
        if($alpha < 0){ $alpha = 0; }
        else if($alpha > 1){ $alpha = 1; }
        $mobileStyles .= '.navbar{background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.')}';

        $mobile_menu_bar_blurry = get_theme_mod('mobile_menu_bar_blurry', '');
        if( $mobile_menu_bar_blurry == '1' ) {
            $blur = get_theme_mod('mobile_menu_bar_blur_amount', 20);
            $mobileStyles .= 
            '.navbar{
                -webkit-backdrop-filter: saturate(180%) blur('.$blur.'px);
                backdrop-filter: saturate(180%) blur('.$blur.'px);
            }';
        }

        $mobileStyles .= CSS_Output::generate_css('.navbar', 'border-bottom-color', 'mobile_menu_bar_border_color', $darker);
        $mobileStyles .= CSS_Output::generate_css('.burger-default span, .burger-default span:before, .burger-default span:after', 'background-color', 'mobile_menu_burger_icon_color', $txtColor);
        $mobileStyles .= CSS_Output::generate_css('.burger-new .bread-crust-top, .burger-new .bread-crust-bottom', 'background', 'mobile_menu_burger_icon_color', $txtColor);
        $mobileStyles .= CSS_Output::generate_css('.lay-cart-icon-wrap', 'color', 'mobile_menu_burger_icon_color', '#000');

        $mobile_menu_bar_show_border = get_theme_mod('mobile_menu_bar_show_border', "1");
        if($mobile_menu_bar_show_border == "0"){
            $mobileStyles .= '.navbar{border-bottom-width: 0;}';
        }

        $use_desktop_menu_as_mobile_menu = (get_theme_mod('mobile_menu_style', 'style_1') == 'style_desktop_menu' || get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style') ? 1 : 0;
        $use_pill_menu_as_mobile_menu = get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style' ? 1 : 0;
        if($use_desktop_menu_as_mobile_menu != 1 && $use_pill_menu_as_mobile_menu != 1){
            // mobile menu points color
            $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'border-bottom-color', 'mobile_menu_points_underline_color', $darker);
            $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav a', 'color', 'mobile_menu_text_color', $txtColor);
    
            // mobile menu background color with rgba
            $mobile_menu_background_color = get_theme_mod('mobile_menu_background_color', $lighterBgColor);
            $rgb = CSS_Output::hex2rgb($mobile_menu_background_color);
            $alpha = (int)get_theme_mod('mobile_menu_background_opacity', 100);
            $alpha /= 100;
            if($alpha < 0){ $alpha = 0; }
            else if($alpha > 1){ $alpha = 1; }
            switch( $mobile_menu_style ){
                case 'style_1':
                    $mobileStyles .= 'nav.mobile-nav li>a{background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.')}';
                break;
                default:
                    $mobileStyles .= 'nav.mobile-nav{background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.')}';
                break;
            }

            if( get_theme_mod('mobile_menu_blurry', false) == '1' ) {
                $blur = get_theme_mod('mobile_menu_blur_amount', 20);
                switch( $mobile_menu_style ){
                    case 'style_1':
                        $mobileStyles .= 'nav.mobile-nav li>a{
                            -webkit-backdrop-filter: saturate(180%) blur('.$blur.'px);
                            backdrop-filter: saturate(180%) blur('.$blur.'px);
                        }';
                    break;
                    default:
                        $mobileStyles .= 'nav.mobile-nav{
                            -webkit-backdrop-filter: saturate(180%) blur('.$blur.'px);
                            backdrop-filter: saturate(180%) blur('.$blur.'px);
                        }';
                    break;
                }
            }

            // mobile background color of current-menu-item with rgba
            // needed to add .current_page_item for woocommerce pages
            $mobile_menu_current_menu_item_background_color = get_theme_mod('mobile_menu_current_menu_item_background_color', $darker);
            $rgb = CSS_Output::hex2rgb($mobile_menu_current_menu_item_background_color);
            $mobileStyles .= 'nav.mobile-nav li.current-menu-item>a, nav.mobile-nav li.current_page_item>a{background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.')}';

            $mobile_menu_current_menu_item_color = get_theme_mod('mobile_menu_current_menu_item_color', $txtColor);
            $rgb = CSS_Output::hex2rgb($mobile_menu_current_menu_item_color);
            $mobileStyles .= 'nav.mobile-nav li.current-menu-item>a, nav.mobile-nav li.current_page_item>a{color:rgb('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].')}';
            $mobileStyles .= 'nav.mobile-nav li.current-menu-item>a, nav.mobile-nav li.current_page_item>a{text-decoration-color:rgb('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].')}';

        }
        // mobile menu fontsizes
        $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'font-size', 'mobile_menu_fontsize', Customizer::$defaults['mobile_menu_fontsize'], '', 'px');

        switch( $mobile_menu_style ){
            case 'style_1':
            case 'style_2':
            case 'style_3':
            case 'style_4':
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'padding-left', 'mobile_menupoint_paddingleft', 10, '', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'padding-right', 'mobile_menupoint_paddingright', 10, '', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'padding-top', 'mobile_menupoint_paddingtop', 10, '', 'px');
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'padding-bottom', 'mobile_menupoint_paddingbottom', 10, '', 'px');
            break;
            case 'style_desktop_menu':
            case 'button_menu_style':
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav a', 'color', 'mobile_menu_text_color', $txtColor);
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav a>span', 'text-decoration-color', 'mobile_menu_text_color', $txtColor);

                
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li.current-menu-item>a, nav.mobile-nav li.current_page_item>a', 'color', 'mobile_menu_current_menu_item_color', $txtColor);
                $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li.current-menu-item>a>span, nav.mobile-nav li.current_page_item>a>span', 'text-decoration-color', 'mobile_menu_current_menu_item_color', $txtColor);

            break;
        }

        // Mobile menu "Button Style": style top-level points as laybutton1/2/3.
        if( $mobile_menu_style == 'button_menu_style' ) {
            $mobile_button_slug = get_theme_mod('mobile_menu_button_style_variant', 'laybutton1');
            if( !in_array($mobile_button_slug, array('laybutton1', 'laybutton2', 'laybutton3'), true) ) {
                $mobile_button_slug = 'laybutton1';
            }

            $button_selector = '.laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li > a';
            $button_hover_selector = '.no-touchdevice .laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li > a:hover';
            $button_active_selector = '.laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li.current-menu-item > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li.current_page_item > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li.current-menu-parent > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li.current-menu-ancestor > a, .laynav.mobile-nav.mobile-menu-style-button_menu_style > ul > li.current-lang > a';

            $base_bg_opacity = get_theme_mod($mobile_button_slug . '_background_opacity', 100);
            $base_bg = get_theme_mod($mobile_button_slug . '_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($mobile_button_slug . '_background_color', '#ffffff'), $base_bg_opacity);
            $hover_bg_opacity = get_theme_mod($mobile_button_slug . '_mo_background_opacity', 100);
            $hover_bg = get_theme_mod($mobile_button_slug . '_mo_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($mobile_button_slug . '_mo_background_color', '#ffffff'), $hover_bg_opacity);
            $active_bg_opacity = get_theme_mod($mobile_button_slug . '_active_background_opacity', 100);
            $active_bg = get_theme_mod($mobile_button_slug . '_active_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod($mobile_button_slug . '_active_background_color', '#ffffff'), $active_bg_opacity);

            $use_hover_state = get_theme_mod($mobile_button_slug . '_use_mouseover', false) == true;
            $use_active_state = get_theme_mod($mobile_button_slug . '_use_active_state', false) == true;

            $normal_border_width = (int) get_theme_mod($mobile_button_slug . '_borderwidth', 1);
            $hover_border_width = (int) get_theme_mod($mobile_button_slug . '_mo_borderwidth', 1);
            $active_border_width = (int) get_theme_mod($mobile_button_slug . '_active_borderwidth', 1);
            $stable_border_width = $normal_border_width;
            if ($use_hover_state) {
                $stable_border_width = max($stable_border_width, $hover_border_width);
            }
            if ($use_active_state) {
                $stable_border_width = max($stable_border_width, $active_border_width);
            }

            $normal_border_color = $normal_border_width > 0 ? get_theme_mod($mobile_button_slug . '_border_color', '#000000') : 'transparent';
            $hover_border_color = $hover_border_width > 0 ? get_theme_mod($mobile_button_slug . '_mo_border_color', '#000000') : 'transparent';
            $active_border_color = $active_border_width > 0 ? get_theme_mod($mobile_button_slug . '_active_border_color', '#000000') : 'transparent';
            $force_styles = get_theme_mod($mobile_button_slug . '_force_styles', false);
            $important = $force_styles ? '!important' : '';
            $button_blurry_background = get_theme_mod($mobile_button_slug . '_blurry_background', false);
            $button_blur = max(0, (int) get_theme_mod($mobile_button_slug . '_blur', 10));

            $mobileStyles .= FormatsManager::get_textformat_css_for_selector(
                get_theme_mod($mobile_button_slug . '_textformat', 'Default'),
                $button_selector,
                array('spaces' => false, 'important' => $force_styles, 'media_queries' => true, 'include_padding_bottom' => false)
            );

            $mobileStyles .=
                $button_selector .
                '{
                    color:' . get_theme_mod($mobile_button_slug . '_text_color', '#000000') . $important . ';
                    border-radius:' . get_theme_mod($mobile_button_slug . '_borderradius', 0) . 'px' . $important . ';
                    background-color:' . $base_bg . $important . ';
                    border:' . $stable_border_width . 'px solid ' . $normal_border_color . $important . ';
                    padding-left:' . get_theme_mod($mobile_button_slug . '_paddingleftright', 15) . 'px' . $important . ';
                    padding-right:' . get_theme_mod($mobile_button_slug . '_paddingleftright', 15) . 'px' . $important . ';
                    padding-top:' . get_theme_mod($mobile_button_slug . '_paddingtop', 5) . 'px' . $important . ';
                    padding-bottom:' . get_theme_mod($mobile_button_slug . '_paddingbottom', 5) . 'px' . $important . ';
                    margin:0' . $important . ';
                    display:inline-block!important;
                    width:auto!important;
                    box-sizing:border-box;
                    text-decoration:none;
                    ' . ($button_blurry_background ? '-webkit-backdrop-filter: blur(' . $button_blur . 'px)' . $important . ';backdrop-filter: blur(' . $button_blur . 'px)' . $important . ';' : '') . '
                }';

            if ($use_hover_state) {
                $button_transition = get_theme_mod($mobile_button_slug . '_animate_mouseover', false) == true
                    ? 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease' . $important . ';'
                    : '';
                $mobileStyles .=
                    $button_hover_selector .
                    '{
                        color:' . get_theme_mod($mobile_button_slug . '_mo_text_color', '#000000') . $important . ';
                        background-color:' . $hover_bg . $important . ';
                        border:' . $stable_border_width . 'px solid ' . $hover_border_color . $important . ';
                        '.$button_transition.'
                    }';
            }

            if ($use_active_state) {
                $button_active_transition = get_theme_mod($mobile_button_slug . '_animate_active_state', false) == true
                    ? 'transition: color 200ms ease, background-color 200ms ease, border-color 200ms ease, border-width 200ms ease' . $important . ';'
                    : '';
                $mobileStyles .=
                    $button_active_selector .
                    '{
                        color:' . get_theme_mod($mobile_button_slug . '_active_text_color', '#000000') . $important . ';
                        background-color:' . $active_bg . $important . ';
                        border:' . $stable_border_width . 'px solid ' . $active_border_color . $important . ';
                        '.$button_active_transition.'
                    }';
            }
        }

        $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li, nav.mobile-nav li a', 'text-align', 'mobile_menu_text_align', 'left');
        $mobileStyles .= CSS_Output::generate_css('nav.mobile-nav li a', 'line-height', 'mobile_menupoint_lineheight', 1, '', 'em');


		// strokewidth for active menu point
		$strokewidth = get_theme_mod('mobile_menu_current_menu_item_underline_strokewidth', 0);
        if( $strokewidth > 0 ) {
			$defaultUnderlineOffset = get_theme_mod('link_underline_offset', 3);
			$defaultUnderlineOffsetMu = get_theme_mod('link_underline_offset_mu', 'px');

            $mobileStyles .= 'nav.mobile-nav li.current-menu-item>a:not(.lay-menu-button)>span,
                nav.mobile-nav li.current_page_item>a:not(.lay-menu-button)>span { 
                text-decoration-thickness:'.$strokewidth.'px;
                text-decoration-style: solid;
                text-decoration-line: underline;
            }';
			$underline_offset = get_theme_mod('mobile_menu_current_menu_item_underline_offset', $defaultUnderlineOffset);
			$mobileStyles .= 'nav.mobile-nav li.current-menu-item>a:not(.lay-menu-button)>span,
                nav.mobile-nav li.current_page_item>a:not(.lay-menu-button)>span{ 
				text-underline-offset: '.$underline_offset.$defaultUnderlineOffsetMu.';
			}';
        }

        $breakpoint = get_option('lay_breakpoint', 600);
        $breakpoint = (int)$breakpoint;

        $breakpoint_phone_menu = get_option('lay_phone_menu_breakpoint', $breakpoint);
        $breakpoint_phone_menu = (int)$breakpoint_phone_menu;

        wp_add_inline_style( 'frontend-style',
        '/* customizer css mobile menu */
            @media (max-width: '.($breakpoint_phone_menu).'px){'
                .$mobileStyles.
            '}
            '.$sharedStyles
        );
    }

    public static function echo_lay_customize_css(){
        echo CSS_Output_Mobile_Menu::lay_customize_css();
    }

}
new CSS_Output_Mobile_Menu();