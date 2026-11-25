<?php

class Lay_Overlays{


    public function __construct(){
        add_action('wp_head', array($this, 'exclude_overlay_pages_from_indexing'));
    }

    public static function echo_overlay(){
        echo Lay_Overlays::getOverlays();
    }

    public static function getOverlays($layoutMarkup){
        $val = get_option('misc_options_activate_overlay_feature', '');
		if($val == "on" && !MiscOptions::$locked){


            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'page',
                'fields' => 'ids',
                'post_status' => 'publish',
            );
            
            $query = new WP_Query( $args );
            $overlay_markups = '';

            $breakpoint = get_option('lay_breakpoint', 600);
            $breakpoint = (int)$breakpoint;

            $sharedStyle = '';
            $desktopStyle = '';
            $mobileStyle = '';

            if ( $query->have_posts() ) {
                foreach ($query->posts as $post_id){
                    $array = get_post_meta($post_id, 'overlay_settings');

                    if( is_array($array) && array_key_exists(0, $array) ) {
                        $array = $array[0];
                        $use_as_overlay = array_key_exists('use_as_overlay', $array) ? $array['use_as_overlay'] : '';
                        
                        if($use_as_overlay == 'on') {
                            $overlay_burger = array_key_exists('overlay_burger', $array) ? $array['overlay_burger'] : '';
                            // get overlay if its linked in menu or in layout
                            // OR if we have "show burger icon" enabled for this page

                            // current id is not part of to be loaded page's id array
                            // and overlay burger is not turned on
                            // THIS IS THE IMPORTANT PART :D!
                            // if( !in_array( $post_id, $ids) && $overlay_burger != 'on' ) {
                            // if( $overlay_burger != 'on' ) {
                                // continue;
                            // }

                            $lay_preopen_overlay = array_key_exists('lay_preopen_overlay', $array) ? $array['lay_preopen_overlay'] : '';
                            $lay_keep_sitetitle_above_overlay = array_key_exists('lay_keep_sitetitle_above_overlay', $array) ? $array['lay_keep_sitetitle_above_overlay'] : '';
                    
                            // Get layout (which includes layoutObj with the desktop_arr containing background settings)
                            $layout = Lay_Layout::getLayout($post_id, 'page', array(
                                'full_layout' => false,
                                'image_hover' => true,
                                'page_overlay' => true,
                            ));
                            
                            // Extract background settings from layoutObj (desktop_arr)
                            $desktop_arr = is_array($layout) && array_key_exists('layoutObj', $layout) ? $layout['layoutObj'] : null;
                            
                            $desktop_bg_color = is_array($desktop_arr) && array_key_exists('bgColor', $desktop_arr) ? $desktop_arr['bgColor'] : null;
                            $desktop_bg_opacity_value = is_array($desktop_arr) && array_key_exists('bgOpacity', $desktop_arr) && $desktop_arr['bgOpacity'] !== null ? (float)$desktop_arr['bgOpacity'] : 100;
                            $desktop_bg_opacity_value = max(0, min(100, $desktop_bg_opacity_value));
                            $desktop_bg_blur_value = is_array($desktop_arr) && array_key_exists('bgBlur', $desktop_arr) && $desktop_arr['bgBlur'] !== null ? (float)$desktop_arr['bgBlur'] : 0;
                            $desktop_has_custom_bg_opacity = $desktop_bg_opacity_value < 100;
                            $desktop_has_custom_bg_blur = $desktop_bg_blur_value > 0;
                            $desktop_has_custom_bg_color = ($desktop_bg_color !== null && $desktop_bg_color !== '');
                            $desktop_has_custom_bg_effects = $desktop_has_custom_bg_opacity || $desktop_has_custom_bg_blur || $desktop_has_custom_bg_color;
                            
                            // Check if there's a background video (which would override background color/blur)
                            $desktop_has_bg_video = false;
                            if (is_array($desktop_arr) && array_key_exists('bgVideo', $desktop_arr) && is_array($desktop_arr['bgVideo'])) {
                                $bgVideo = $desktop_arr['bgVideo'];
                                $useExternal = array_key_exists('useExternalVideo', $bgVideo) && ($bgVideo['useExternalVideo'] === true || $bgVideo['useExternalVideo'] === 'true' || $bgVideo['useExternalVideo'] === 1 || $bgVideo['useExternalVideo'] === '1');
                                if ($useExternal) {
                                    $desktop_has_bg_video = !empty($bgVideo['externalVideoUrl']);
                                } else {
                                    $desktop_has_bg_video = !empty($bgVideo['mp4']);
                                }
                            }
                            
                            // Check if there's a background image
                            $desktop_has_bg_image = false;
                            if (is_array($desktop_arr) && array_key_exists('bgImage', $desktop_arr) && !empty($desktop_arr['bgImage'])) {
                                $desktop_has_bg_image = true;
                            }
                            
                            // Determine if we have any custom background settings from desktop gridder JSON
                            // Only add the transparent background class if we have actual custom background settings
                            // Default values: bgColor empty, opacity 100%, blur 0, no video, no image = NO custom bg
                            $desktop_has_custom_bg = false;
                            // Check if bgColor is explicitly set (not null/empty)
                            if ($desktop_bg_color !== null && $desktop_bg_color !== '') {
                                $desktop_has_custom_bg = true;
                            }
                            // Check if we have non-default opacity (less than 100%)
                            if ($desktop_bg_opacity_value < 100) {
                                $desktop_has_custom_bg = true;
                            }
                            // Check if we have non-default blur (greater than 0)
                            if ($desktop_bg_blur_value > 0) {
                                $desktop_has_custom_bg = true;
                            }
                            // Check if we have background video
                            if ($desktop_has_bg_video) {
                                $desktop_has_custom_bg = true;
                            }
                            // Check if we have background image
                            if ($desktop_has_bg_image) {
                                $desktop_has_custom_bg = true;
                            }
                            
                            // Get CPL (Custom Phone Layout) background settings
                            $cpl_json = get_post_meta($post_id, '_phone_gridder_json', true);
                            $cpl_arr = !empty($cpl_json) ? json_decode($cpl_json, true) : null;
                            $cpl_exists = is_array($cpl_arr);
                            
                            // Determine CPL background settings
                            $phone_has_custom_bg = false;
                            if ($cpl_exists) {
                                // CPL exists - check its own background settings
                                $cpl_bg_color = is_array($cpl_arr) && array_key_exists('bgColor', $cpl_arr) ? $cpl_arr['bgColor'] : null;
                                $cpl_bg_opacity_value = is_array($cpl_arr) && array_key_exists('bgOpacity', $cpl_arr) && $cpl_arr['bgOpacity'] !== null ? (float)$cpl_arr['bgOpacity'] : 100;
                                $cpl_bg_opacity_value = max(0, min(100, $cpl_bg_opacity_value));
                                $cpl_bg_blur_value = is_array($cpl_arr) && array_key_exists('bgBlur', $cpl_arr) && $cpl_arr['bgBlur'] !== null ? (float)$cpl_arr['bgBlur'] : 0;
                                $cpl_has_custom_bg_opacity = $cpl_bg_opacity_value < 100;
                                $cpl_has_custom_bg_blur = $cpl_bg_blur_value > 0;
                                $cpl_has_custom_bg_color = ($cpl_bg_color !== null && $cpl_bg_color !== '');
                                $cpl_has_custom_bg_effects = $cpl_has_custom_bg_opacity || $cpl_has_custom_bg_blur || $cpl_has_custom_bg_color;
                                
                                $cpl_has_bg_video = false;
                                if (is_array($cpl_arr) && array_key_exists('bgVideo', $cpl_arr) && is_array($cpl_arr['bgVideo'])) {
                                    $cpl_bg_video = $cpl_arr['bgVideo'];
                                    $useExternal = array_key_exists('useExternalVideo', $cpl_bg_video) && ($cpl_bg_video['useExternalVideo'] === true || $cpl_bg_video['useExternalVideo'] === 'true' || $cpl_bg_video['useExternalVideo'] === 1 || $cpl_bg_video['useExternalVideo'] === '1');
                                    if ($useExternal) {
                                        $cpl_has_bg_video = !empty($cpl_bg_video['externalVideoUrl']);
                                    } else {
                                        $cpl_has_bg_video = !empty($cpl_bg_video['mp4']);
                                    }
                                }
                                
                                $cpl_has_bg_image = false;
                                if (is_array($cpl_arr) && array_key_exists('bgImage', $cpl_arr) && !empty($cpl_arr['bgImage'])) {
                                    $cpl_has_bg_image = true;
                                }
                                
                                // Check if CPL has custom background
                                // Only add the transparent background class if we have actual custom background settings
                                // Default values: bgColor empty, opacity 100%, blur 0, no video, no image = NO custom bg
                                // Check if bgColor is explicitly set (not null/empty)
                                if ($cpl_bg_color !== null && $cpl_bg_color !== '') {
                                    $phone_has_custom_bg = true;
                                }
                                // Check if we have non-default opacity (less than 100%)
                                if ($cpl_bg_opacity_value < 100) {
                                    $phone_has_custom_bg = true;
                                }
                                // Check if we have non-default blur (greater than 0)
                                if ($cpl_bg_blur_value > 0) {
                                    $phone_has_custom_bg = true;
                                }
                                // Check if we have background video
                                if ($cpl_has_bg_video) {
                                    $phone_has_custom_bg = true;
                                }
                                // Check if we have background image
                                if ($cpl_has_bg_image) {
                                    $phone_has_custom_bg = true;
                                }
                            } else {
                                // No CPL exists - phone uses desktop background, so check desktop
                                $phone_has_custom_bg = $desktop_has_custom_bg;
                            }
                            // $height = $array['overlay_set_height'] == 'on' ? 'height:'.$array['overlay_height'].$array['overlay_height_mu'].';' : '';
                            $height = '';
                            // data-settings='.json_encode($array).'

                            $close_markup = '';
                            $close = array_key_exists('close', $array) ? $array['close'] : '';
                            $close_color = array_key_exists('close_color', $array) ? $array['close_color'] : '#000000';
							$close_text_color = array_key_exists('close_text_color', $array) ? $array['close_text_color'] : '#000000';
                            $close_size = array_key_exists('close_size', $array) ? $array['close_size'] : 40;
                            $close_size_mu = array_key_exists('close_size_mu', $array) ? $array['close_size_mu'] : 'px';

                            $close_padding = array_key_exists('close_padding', $array) ? $array['close_padding'] : 20;
                            $close_padding_mu = array_key_exists('close_padding_mu', $array) ? $array['close_padding_mu'] : 'px';

                            $close_position = array_key_exists('close_position', $array) ? $array['close_position'] : 'topright';

                            $close_size_phone = array_key_exists('close_size_phone', $array) ? $array['close_size_phone'] : 25;
                            $close_size_phone_mu = array_key_exists('close_size_phone_mu', $array) ? $array['close_size_phone_mu'] : 'px';

                            $close_padding_phone = array_key_exists('close_padding_phone', $array) ? $array['close_padding_phone'] : 15;
                            $close_padding_phone_mu = array_key_exists('close_padding_phone_mu', $array) ? $array['close_padding_phone_mu'] : 'px';

                            $use_custom_close_icon = array_key_exists('use_custom_close_icon', $array) ? $array['use_custom_close_icon'] : '';
                            $custom_close_icon_width = array_key_exists('custom_close_icon_width', $array) ? $array['custom_close_icon_width'] : 40;
                            $custom_close_icon_width_mu = array_key_exists('custom_close_icon_width_mu', $array) ? $array['custom_close_icon_width_mu'] : 'px';

                            $custom_close_phone_icon_width = array_key_exists('custom_close_phone_icon_width', $array) ? $array['custom_close_phone_icon_width'] : 30;
                            $custom_close_phone_icon_width_mu = array_key_exists('custom_close_phone_icon_width_mu', $array) ? $array['custom_close_phone_icon_width_mu'] : 'px';

                            $overlay_width = array_key_exists('overlay_width', $array) ? $array['overlay_width'] : 100;
                            $overlay_width_mu = array_key_exists('overlay_width_mu', $array) ? $array['overlay_width_mu'] : '%';

                            $overlay_phone_width = array_key_exists('overlay_phone_width', $array) ? $array['overlay_phone_width'] : 100;
                            $overlay_phone_width_mu = array_key_exists('overlay_phone_width_mu', $array) ? $array['overlay_phone_width_mu'] : '%';

                            $disable_scroll = array_key_exists('lay_disable_scroll', $array) ? $array['lay_disable_scroll'] : 'on';

                            if( $close == 'on' ) {

                                $custom_close_icon = array_key_exists('custom_close_icon', $array) ? $array['custom_close_icon'] : '';
                                $custom_close_icon_img_src = '';
                                if($custom_close_icon != ''){
                                    $custom_close_icon_img_src = wp_get_attachment_image_src( $custom_close_icon, 'full' );
                                    if(is_array($custom_close_icon_img_src)) {
                                        $custom_close_icon_img_src = $custom_close_icon_img_src[0];
                                    }
                                }

                                $use_custom_close_icon = array_key_exists('use_custom_close_icon', $array) ? $array['use_custom_close_icon'] : '';
                                $close_type = array_key_exists('close_type', $array) ? $array['close_type'] : '';
                                if($use_custom_close_icon == "on") {
                                    $close_type = 'custom';
                                }

                                switch($close_type) {
                                    case 'custom':
                                        $close_markup = '<div class="overlay-close custom-overlay-close-icon"><img src="'.$custom_close_icon_img_src.'" alt=""/></div>';
                                    break;
                                    case 'default':
                                        $close_markup = '<div class="overlay-close standard-overlay-close-icon"></div>';
                                    break;
                                    case 'text':
                                        $close_markup = '<div class="overlay-close text-overlay-close _'.$array['lay_overlay_close_textformat'].'_no_spaces">'.$array['lay_overlay_close_text'].'</div>';
                                    break;
                                }

                                $closeDesktop = self::cornerPositionPadding($close_position, $close_padding, $close_padding_mu);
                                $closePhone   = self::cornerPositionPadding($close_position, $close_padding_phone, $close_padding_phone_mu);

                                $sharedStyle .= '
                                    .overlay-id-'.$post_id.' .standard-overlay-close-icon::before{
                                        color: '.$close_color.';
                                    }
                                    .overlay-id-'.$post_id.' .text-overlay-close{
                                        color: '.$close_text_color.';
                                    }';

                                $desktopStyle .= '
                                        .overlay-id-'.$post_id.' .standard-overlay-close-icon::before{
                                            font-size: '.$close_size.($close_size_mu == 'px' ? $close_size_mu : 'vw').';
                                        }
                                        .overlay-id-'.$post_id.' .overlay-close{
                                            '.$closeDesktop['position'].$closeDesktop['padding'].'
                                        }
                                        .overlay-id-'.$post_id.' .custom-overlay-close-icon img{
                                            width: '.$custom_close_icon_width.($custom_close_icon_width_mu == 'px' ? $custom_close_icon_width_mu : 'vw').';
                                        }';
                                $mobileStyle .='
                                        .overlay-id-'.$post_id.' .standard-overlay-close-icon::before{
                                            font-size: '.$close_size_phone.($close_size_phone_mu == 'px' ? $close_size_phone_mu : 'vw').';
                                        }
                                        .overlay-id-'.$post_id.' .overlay-close{
                                            '.$closePhone['position'].$closePhone['padding'].'
                                        }
                                        .overlay-id-'.$post_id.' .custom-overlay-close-icon img{
                                            width: '.$custom_close_phone_icon_width.($custom_close_phone_icon_width_mu == 'px' ? $custom_close_phone_icon_width_mu : 'vw').';
                                        }';
                            } else {
                                $sharedStyle .= '
                                    .overlay-id-'.$post_id.' .overlay-close{
                                        display: none;
                                    }';
                            }

                            $desktopStyle .='
                                    .overlay-id-'.$post_id.'{
                                        width:'.$overlay_width.$overlay_width_mu.';
                                    }';
                                
                            $mobileStyle .= '
                                    .overlay-id-'.$post_id.'{
                                        width:'.$overlay_phone_width.$overlay_phone_width_mu.';
                                    }
                                ';
                            
                            $background = array_key_exists('background', $array) ? $array['background'] : '';
                            $background_opacity = array_key_exists('background_opacity', $array) ? $array['background_opacity'] : 20;
                            $background_color = array_key_exists('background_color', $array) ? $array['background_color'] : '#ffffff';
                            $background_blur = array_key_exists('background_blur', $array) ? $array['background_blur'] : 0;
                            $has_overlay_background = ($background === 'on');
                            
                            // note: '<div>'.$close_markup.'</div>'
                            // wrapping $close_markup into another div, so position fixed inside position fixed works: https://codepen.io/andreasmb/pen/LpEmgj?css-preprocessor=less
                            $overlay_classes = array(
                                'lay-overlay',
                                'lay-'.$array['overlay_animation'],
                                'lay-phone-'.$array['overlay_phone_animation'],
                                'overlay-id-'.$post_id,
                            );
                            if ( $lay_preopen_overlay == 'on' ) {
                                $overlay_classes[] = 'lay-preopen-overlay';
                            }
                            // Add custom bg classes separately for desktop and phone
                            // Desktop overlay gets transparent background only if desktop layout has custom bg
                            if ( $desktop_has_custom_bg ) {
                                $overlay_classes[] = 'lay-desktop-overlay-has-custom-bg';
                            }
                            // Phone overlay gets transparent background only if phone layout (CPL or desktop fallback) has custom bg
                            if ( $phone_has_custom_bg ) {
                                $overlay_classes[] = 'lay-phone-overlay-has-custom-bg';
                            }
                            // Keep old class for backwards compatibility (if either has custom bg)
                            if ( $desktop_has_custom_bg || $phone_has_custom_bg ) {
                                $overlay_classes[] = 'lay-overlay-has-custom-bg';
                            }

                            $overlay_markups .=
                            '<div class="'.implode(' ', $overlay_classes).'" data-link="'.get_permalink($post_id).'" data-id="'.$post_id.'" data-type="page" data-disable-scroll="'.$disable_scroll.'" data-keep-sitetitle-above="'.($lay_keep_sitetitle_above_overlay == 'on' ? 'on' : '').'">'.$layout['markup']
                                .'<div>'.$close_markup.'</div>'
                            .'</div>';
                            
                            $style_active = array_key_exists('style_active', $array) ? $array['style_active'] : '';
                            if( $style_active == 'on' ) {

                                $active_color = array_key_exists('active_color', $array) ? $array['active_color'] : '#000000';
                                $active_opacity = array_key_exists('active_opacity', $array) ? $array['active_opacity'] : 100;                        

                                // Also target descendants so inline `style="color:..."` on inner spans
                                // (which the customer may have added via the editor) gets overridden.
                                // `color` isn't truly inherited past elements that set their own color,
                                // so the rule on the <a> alone won't reach nested <span style="color:...">.
                                $sharedStyle .= '.overlay-id-'.$post_id.' a.current-link, .overlay-id-'.$post_id.' a.current-link *, .overlay-id-'.$post_id.' li.current-lang a, .overlay-id-'.$post_id.' li.current-lang a * {
                                        opacity: '.((int)$active_opacity/100).'!important;
                                        color: '.$active_color.'!important;
                                    }';
                            }

                            // Build background style from overlay_settings (for .lay-overlay-bg element - background next to overlay)
                            $bg_style = '';
                            if( $background == 'on' ) {
                                $rgb = CSS_Output::hex2rgb($background_color);
                                $alpha = (int)$background_opacity;
                                $alpha /= 100;
                                if($alpha < 0){ $alpha = 0; }
                                else if($alpha > 1){ $alpha = 1; }                    
                                
                                $bg_style = 'background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.');';
                                if( $background_blur != 0 ) {
                                    $bg_style .= 'backdrop-filter:blur('.$background_blur.'px);-webkit-backdrop-filter:blur('.$background_blur.'px);';
                                }
                                // Also add class if overlay_settings background is enabled
                                if ( !in_array('lay-overlay-has-custom-bg', $overlay_classes) ) {
                                    $overlay_classes[] = 'lay-overlay-has-custom-bg';
                                }
                            }
                            
                            // If gridder JSON has custom bg effects, apply to .lay-overlay container (like project overlays)
                            // Background video/image is already in the layout markup with opacity/blur applied
                            $applied_custom_bg_class = false;
                            
                            if ($cpl_exists) {
                                // Desktop styles (desktop JSON)
                            if ( $desktop_has_custom_bg_effects && !$desktop_has_bg_video ) {
                                    $desktop_overlay_bg_style = self::buildBackgroundStyle($desktop_bg_color, $desktop_bg_opacity_value, $desktop_bg_blur_value);
                                    if ( !empty($desktop_overlay_bg_style) ) {
                                        $desktopStyle .= '.overlay-id-'.$post_id.'{'.$desktop_overlay_bg_style.'}';
                                        $applied_custom_bg_class = true;
                                    }
                                }
                                // Phone styles (CPL JSON)
                                if ( $cpl_has_custom_bg_effects && !$cpl_has_bg_video ) {
                                    $mobile_overlay_bg_style = self::buildBackgroundStyle($cpl_bg_color, $cpl_bg_opacity_value, $cpl_bg_blur_value);
                                    if ( !empty($mobile_overlay_bg_style) ) {
                                        $mobileStyle .= '.overlay-id-'.$post_id.'{'.$mobile_overlay_bg_style.'}';
                                        $applied_custom_bg_class = true;
                                    }
                                    }
                                } else {
                                // No custom phone layout - use shared style from desktop JSON
                                if ( $desktop_has_custom_bg_effects && !$desktop_has_bg_video ) {
                                    $overlay_bg_style = self::buildBackgroundStyle($desktop_bg_color, $desktop_bg_opacity_value, $desktop_bg_blur_value);
                                if ( !empty($overlay_bg_style) ) {
                                    $sharedStyle .= '.overlay-id-'.$post_id.'{'.$overlay_bg_style.'}';
                                        $applied_custom_bg_class = true;
                                    }
                                }
                                }
                                
                                // Ensure class is added for gridder JSON bg effects
                            if ( $applied_custom_bg_class && !in_array('lay-overlay-has-custom-bg', $overlay_classes) ) {
                                    $overlay_classes[] = 'lay-overlay-has-custom-bg';
                            }
                            
                            if( !empty($bg_style) ) {
                                $overlay_markups .= '<div class="lay-overlay-bg overlay-bg-id-'.$post_id.'" style="'.$bg_style.'" data-link="'.get_permalink($post_id).'"></div>';
                            }
                            
                            // Burger color from overlay metabox only. Page-specific burger color is applied via CSS on that page (frontend.php).
                            $burger_color = array_key_exists( 'burger_color', $array ) ? $array['burger_color'] : '#000000';
                            $burger_size = array_key_exists('burger_size', $array) ? $array['burger_size'] : 26;
                            $burger_size_mu = array_key_exists('burger_size_mu', $array) ? $array['burger_size_mu'] : 'px';

                            $burger_padding = array_key_exists('burger_padding', $array) ? $array['burger_padding'] : 20;
                            $burger_padding_mu = array_key_exists('burger_padding_mu', $array) ? $array['burger_padding_mu'] : 'px';

                            $burger_position = array_key_exists('burger_position', $array) ? $array['burger_position'] : 'topright';
                            $hide_burger_on_phone = array_key_exists('hide_burger_on_phone', $array) ? $array['hide_burger_on_phone'] : '';
                            $burger_size_phone = array_key_exists('burger_size_phone', $array) ? $array['burger_size_phone'] : 20;
                            $burger_size_phone_mu = array_key_exists('burger_size_phone_mu', $array) ? $array['burger_size_phone_mu'] : 'px';

                            $burger_padding_phone = array_key_exists('burger_padding_phone', $array) ? $array['burger_padding_phone'] : 15;
                            $burger_padding_phone_mu = array_key_exists('burger_padding_phone_mu', $array) ? $array['burger_padding_phone_mu'] : 'px';

                            $overlay_burger_hidewhenscrolling = array_key_exists('overlay_burger_hidewhenscrolling', $array) ? $array['overlay_burger_hidewhenscrolling'] : '';
                            $overlay_burger_fadeout_whenscrolling = array_key_exists('overlay_burger_fadeout_whenscrolling', $array) ? $array['overlay_burger_fadeout_whenscrolling'] : '';

                            if( $overlay_burger == 'on' ) {
                                $use_custom_burger_icon = array_key_exists('use_custom_burger_icon', $array) ? $array['use_custom_burger_icon'] : '';
                                $burger_type = array_key_exists('burger_type', $array) ? $array['burger_type'] : '';
                                // Migration: if old checkbox was used, convert to new type value
                                if($use_custom_burger_icon == "on" && empty($burger_type)) {
                                    $burger_type = 'custom';
                                }
                                if(empty($burger_type)) {
                                    $burger_type = 'default';
                                }

                                $overlay_burger_scroll_data = '';
                                if ( $overlay_burger_hidewhenscrolling == 'on' ) {
                                    $overlay_burger_scroll_data .= ' data-lay-burger-hide-on-scroll="1"';
                                }
                                if ( $overlay_burger_fadeout_whenscrolling == 'on' ) {
                                    $overlay_burger_scroll_data .= ' data-lay-burger-fade-on-scroll="1"';
                                }
                                
                                $lay_overlay_burger_text = array_key_exists('lay_overlay_burger_text', $array) ? $array['lay_overlay_burger_text'] : 'Menu';
                                $burger_text_color = array_key_exists('burger_text_color', $array) ? $array['burger_text_color'] : '#000000';
                                $lay_overlay_burger_textformat = array_key_exists('lay_overlay_burger_textformat', $array) ? $array['lay_overlay_burger_textformat'] : 'Default';
                                
                                $custom_burger_icon_width = array_key_exists('custom_burger_icon_width', $array) ? $array['custom_burger_icon_width'] : 40;
                                $custom_burger_icon_width_mu = array_key_exists('custom_burger_icon_width_mu', $array) ? $array['custom_burger_icon_width_mu'] : 'px';

                                $custom_burger_phone_icon_width = array_key_exists('custom_burger_phone_icon_width', $array) ? $array['custom_burger_phone_icon_width'] : 30;
                                $custom_burger_phone_icon_width_mu = array_key_exists('custom_burger_phone_icon_width_mu', $array) ? $array['custom_burger_phone_icon_width_mu'] : 'px';

                                $custom_burger_icon = array_key_exists('custom_burger_icon', $array) ? $array['custom_burger_icon'] : '';
                                $custom_burger_icon_img_src = '';
                                if($custom_burger_icon != ''){
                                    $custom_burger_icon_img_src = wp_get_attachment_image_src( $custom_burger_icon, 'full' );
                                    if(is_array($custom_burger_icon_img_src)) {
                                        $custom_burger_icon_img_src = $custom_burger_icon_img_src[0];
                                    }
                                }

                                if( $burger_type == 'custom' ) {
                                    $overlay_markups .= '<a rel="nofollow" class="overlay-burger custom-overlay-burger burger-overlay-id-'.$post_id.'" href="'.get_permalink($post_id).'" data-type="page" data-id="'.$post_id.'"'.$overlay_burger_scroll_data.'>
                                        <img src="'.$custom_burger_icon_img_src.'" alt="">
                                    </a>';
                                } else if( $burger_type == 'text' ) {
                                    $overlay_markups .= '<a rel="nofollow" class="overlay-burger text-overlay-burger burger-overlay-id-'.$post_id.'" href="'.get_permalink($post_id).'" data-type="page" data-id="'.$post_id.'"'.$overlay_burger_scroll_data.'><span class="_'.$lay_overlay_burger_textformat.'_no_spaces">'.$lay_overlay_burger_text.'</span></a>';
                                } else {
                                    $overlay_markups .= '<a rel="nofollow" class="overlay-burger standard-overlay-burger burger-overlay-id-'.$post_id.'" href="'.get_permalink($post_id).'" data-type="page" data-id="'.$post_id.'"'.$overlay_burger_scroll_data.'></a>';
                                }


                                $burgerDesktop = self::cornerPositionPadding($burger_position, $burger_padding, $burger_padding_mu);
                                $burgerPhone   = self::cornerPositionPadding($burger_position, $burger_padding_phone, $burger_padding_phone_mu);

                                $sharedStyle .= '
                                    .burger-overlay-id-'.$post_id.'.standard-overlay-burger::before{
                                        color: '.$burger_color.';
                                    }
                                    .burger-overlay-id-'.$post_id.'.text-overlay-burger span{
                                        color: '.$burger_text_color.';
                                    }';

                                $desktopStyle .= '
                                        .burger-overlay-id-'.$post_id.'.standard-overlay-burger::before{
                                            font-size: '.$burger_size.($burger_size_mu == 'px' ? $burger_size_mu : 'vw').';
                                        }
                                        .burger-overlay-id-'.$post_id.'.custom-overlay-burger img{
                                            width: '.$custom_burger_icon_width.($custom_burger_icon_width_mu == 'px' ? $custom_burger_icon_width_mu : 'vw').';
                                        }
                                        .burger-overlay-id-'.$post_id.'.overlay-burger{
                                            '.$burgerDesktop['position'].$burgerDesktop['padding'].'
                                        }';

                                $mobileStyle .=
                                        '.burger-overlay-id-'.$post_id.'.standard-overlay-burger::before{
                                            font-size: '.$burger_size_phone.($burger_size_phone_mu == 'px' ? $burger_size_phone_mu : 'vw').';
                                        }
                                        .burger-overlay-id-'.$post_id.'.custom-overlay-burger img{
                                            width: '.$custom_burger_phone_icon_width.($custom_burger_phone_icon_width_mu == 'px' ? $custom_burger_phone_icon_width_mu : 'vw').';
                                        }
                                        .burger-overlay-id-'.$post_id.'.overlay-burger{
                                            '.$burgerPhone['position'].$burgerPhone['padding'].'
                                        }
                                        '.($hide_burger_on_phone == 'on' ? '.burger-overlay-id-'.$post_id.'.overlay-burger{display:none;}' : '').'
                                    ';
                            }

                        }

                    }
                }

                $breakpoint_phone_menu = get_option('lay_phone_menu_breakpoint', $breakpoint);
                $breakpoint_phone_menu = (int)$breakpoint_phone_menu;

                if( $overlay_markups != '') {
                    $overlay_markups .= '<style>
                        '.$sharedStyle.'
                    @media (min-width: '.($breakpoint_phone_menu+1).'px){
                        '.$desktopStyle.'
                    }
                    @media (max-width: '.($breakpoint_phone_menu).'px){
                        '.$mobileStyle.'
                    }
                </style>';
                }

                return $overlay_markups;
            }

        }
        return '';
    }

    /**
     * Check if current page is the frontpage (Lay theme way)
     */
    private static function is_frontpage() {
        global $post;
        
        // Check if WordPress considers it the frontpage
        if (is_front_page() || is_null($post)) {
            return true;
        }
        
        // Same check for deck slides (frontpage_type='deck').
        if (is_singular('lay_deck')) {
            $frontpage_type = get_theme_mod('frontpage_type', 'category');
            if ($frontpage_type === 'deck') {
                $current_id = get_queried_object_id();
                $frontpage_deck_id = get_theme_mod('frontpage_select_deck');
                if ($frontpage_deck_id) {
                    if (class_exists('PLL_Switcher') && function_exists('pll_get_post')) {
                        $frontpage_deck_id = pll_get_post($frontpage_deck_id);
                    }
                    if (defined('ICL_SITEPRESS_VERSION')) {
                        $frontpage_deck_id = apply_filters('wpml_object_id', $frontpage_deck_id, 'lay_deck');
                    }
                    return $current_id == $frontpage_deck_id;
                }
            }
        }

        // Also check if current page matches the frontpage page ID (for when frontpage type is 'page')
        if (is_page()) {
            $frontpage_type = get_theme_mod('frontpage_type', 'category');
            if ($frontpage_type === 'page') {
                $current_page_id = get_queried_object_id();
                $frontpage_page_id = get_theme_mod('frontpage_select_page');

                if ($frontpage_page_id) {
                    // Handle Polylang translations
                    if (class_exists('PLL_Switcher') && function_exists('pll_get_post')) {
                        $frontpage_page_id = pll_get_post($frontpage_page_id);
                    }
                    // Handle WPML translations
                    if (defined('ICL_SITEPRESS_VERSION')) {
                        $frontpage_page_id = apply_filters('wpml_object_id', $frontpage_page_id, 'page');
                    }
                    
                    return $current_page_id == $frontpage_page_id;
                }
            }
        }
        
        return false;
    }

    /**
     * Exclude overlay pages from search engine indexing
     */
    public static function exclude_overlay_pages_from_indexing() {
        // Don't add noindex to frontpage
        if (self::is_frontpage()) {
            return;
        }
        
        // Check if overlay feature is activated
        $val = get_option('misc_options_activate_overlay_feature', '');
        if($val != "on" || MiscOptions::$locked) {
            return;
        }

        // Check if current page is an overlay page
        if (is_page()) {
            $current_page_id = get_queried_object_id();
            $overlay_settings = get_post_meta($current_page_id, 'overlay_settings', true);
            
            if ($overlay_settings && is_array($overlay_settings) && isset($overlay_settings['use_as_overlay']) && $overlay_settings['use_as_overlay'] === 'on') {
                echo '<meta name="robots" content="noindex, follow">';
            }
        }
    }

    /**
     * Position+padding for a corner-anchored icon (overlay close, burger).
     * - Small spacing (≤ 20px / ≤ 1vw): keep the icon flush to the corner
     *   and use 2-sided padding facing outward, so the click target spills
     *   into the corner — matters for small icons.
     * - Larger spacing: a big padding just bloats the bounding box without
     *   meaningfully helping clickability, so offset the icon by that amount
     *   and drop the padding.
     */
    private static function cornerPositionPadding($position, $value, $mu) {
        $css_mu = ($mu == 'px') ? 'px' : 'vw';
        $exceedsThreshold = ($css_mu == 'px') ? ((float)$value > 20) : ((float)$value > 1);
        $val_w_mu = $value . $css_mu;

        $isBottom = ($position == 'bottomleft' || $position == 'bottomright');
        $isRight  = ($position == 'topright' || $position == 'bottomright');
        $vAxis    = $isBottom ? 'bottom' : 'top';
        $vAxisOpp = $isBottom ? 'top'    : 'bottom';
        $hAxis    = $isRight  ? 'right'  : 'left';
        $hAxisOpp = $isRight  ? 'left'   : 'right';

        if ($exceedsThreshold) {
            $position_css = $vAxis.':'.$val_w_mu.';'.$vAxisOpp.':auto;'.$hAxis.':'.$val_w_mu.';'.$hAxisOpp.':auto;';
            $padding_css  = 'padding:0;';
        } else {
            $position_css = $vAxis.':0;'.$vAxisOpp.':auto;'.$hAxis.':0;'.$hAxisOpp.':auto;';
            switch($position) {
                case 'topright':    $padding_css = 'padding:'.$val_w_mu.' '.$val_w_mu.' 0 0;'; break;
                case 'topleft':     $padding_css = 'padding:'.$val_w_mu.' 0 0 '.$val_w_mu.';'; break;
                case 'bottomright': $padding_css = 'padding:0 '.$val_w_mu.' '.$val_w_mu.' 0;'; break;
                case 'bottomleft':  $padding_css = 'padding:0 0 '.$val_w_mu.' '.$val_w_mu.';'; break;
                default:            $padding_css = 'padding:0;';
            }
        }
        return ['position' => $position_css, 'padding' => $padding_css];
    }

    /**
     * Build background style string from color, opacity, and blur values
     *
     * @param string|null $bgColor Background color (hex, rgba, or named color)
     * @param float $bgOpacity Opacity value (0-100)
     * @param float $bgBlur Blur value in pixels
     * @return string CSS style string
     */
    private static function buildBackgroundStyle($bgColor, $bgOpacity, $bgBlur) {
        if ($bgColor == '' || $bgColor == null) {
            $bgColor = 'transparent';
        }

        $opacity = is_numeric($bgOpacity) ? (float)$bgOpacity : 100;
        $opacity = max(0, min(100, $opacity));
        $blur = is_numeric($bgBlur) ? (float)$bgBlur : 0;

        $style = '';
        if ($bgColor != 'transparent') {
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bgColor)) {
                $rgb = CSS_Output::hex2rgb($bgColor);
                $alpha = $opacity / 100;
                $style .= 'background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.');';
            } else {
                if ($opacity < 100) {
                    $style .= 'background-color:'.$bgColor.';';
                    $style .= 'opacity:'.($opacity / 100).';';
                } else {
                    $style .= 'background-color:'.$bgColor.';';
                }
            }
        } else {
            $style .= 'background-color:'.$bgColor.';';
        }

        if ($blur > 0) {
            $style .= 'backdrop-filter:blur('.$blur.'px);-webkit-backdrop-filter:blur('.$blur.'px);';
        }
        
        return $style;
    }

}

new Lay_Overlays();