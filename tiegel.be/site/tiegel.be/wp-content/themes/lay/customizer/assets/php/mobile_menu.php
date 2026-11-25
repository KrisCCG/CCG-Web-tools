<?php

function is_mobile_menu_hidden(){
    return get_theme_mod('mobile_hide_menu', false) == 1 ? false : true;
}
function show_do_animation(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
        return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'style_desktop_menu' ) {
        return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style' ) {
        return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style' ) {
        return false;
    }
    return true;
}
function is_active_underline_color(){
   if( get_theme_mod('mobile_hide_menu', false) == 1 ){
      return false;
   }
   if( get_theme_mod('mobile_menu_style', 'style_1') == 'style_1' ){
      return true;
   }
   return false;
}
function is_active_background_opacity(){
   if( get_theme_mod('mobile_hide_menu', false) == 1 ){
        return false;
   } else {
        return true;
   }
}



function is_mobile_menu_style_2_3_or_4(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
        return false;
    }
    $style = get_theme_mod('mobile_menu_style', 'style_1');
    return $style === 'style_2' || $style === 'style_3' || $style === 'style_4';
}

function is_vertically_center_menupoints_enabled(){
    return is_mobile_menu_style_2_3_or_4() && get_theme_mod('mobile_menu_vertically_center_menupoints', false);
}

function is_mobile_button_menu_style_enabled(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
        return false;
    }
    return get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style';
}

function show_move_away_when_scrolling_down(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ) {
        return false;
    }
    if( get_theme_mod('mobile_menu_isfixed', false) != 1 ) {
        return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') != 'style_desktop_menu' && get_theme_mod('mobile_menu_style', 'style_1') != 'button_menu_style' ){
        return false;
    }
    return true;
}

function is_active_blurry(){
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'style_desktop_menu' ) {
        return false;
    }
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
        return false;
   }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style' ) {
        return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'button_menu_style' ) {
        return false;
    }
    return true;
}

$txtColor = Customizer::$defaults['mobile_menu_txt_color'];
$lighterBgColor = Customizer::$defaults['mobile_menu_light_color'];
$darker = Customizer::$defaults['mobile_menu_dark_color'];

$section = 'mobile_menu';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Menu', 'customizer_mobile_menu_title'),
      'priority' => 0,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel',
	  'description' => lay_t('Please note, if you want to build a mobile menu that has images, texts and more, you can alternatively use the "<a href="https://laytheme.com/documentation/overlays.html" target="_blank">Page Overlay</a>" feature.', 'customizer_mobile_menu_description'),
   )
);

$wp_customize->add_setting( 'mobile_hide_menu',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_hide_menu',
        array(
            'label'          => lay_t('hide', 'customizer_mobile_menu_hide_label'),
            'section'        => $section,
            'settings'       => 'mobile_hide_menu',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_style',
    array(
    'default' => 'style_1',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_style',
         array(
            'label'          => lay_t('Menu Style', 'customizer_mobile_menu_style_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_style',
            'type'           => 'radio',
            'priority'       => 0,
            'choices'        => array(
                'style_1' => lay_t('Style 1', 'customizer_mobile_menu_style_1'),
                'style_2' => lay_t('Style 2', 'customizer_mobile_menu_style_2'),
                'style_3' => lay_t('Style 3', 'customizer_mobile_menu_style_3'),
                'style_4' => lay_t('Style 4', 'customizer_mobile_menu_style_4'),
                'style_desktop_menu' => lay_t('Desktop Menu Style', 'customizer_mobile_menu_style_desktop'),
                'pill_menu_style' => lay_t('Pill Menu Style', 'customizer_mobile_menu_style_pill'),
                'button_menu_style' => lay_t('Button Style', 'customizer_mobile_menu_style_button'),
            ),
            'active_callback' => 'is_mobile_menu_hidden'
         )
   )
);

$wp_customize->add_setting( 'mobile_menu_button_style_variant',
   array(
      'default' => 'laybutton1',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => function( $value ) {
         $allowed = array('laybutton1', 'laybutton2', 'laybutton3');
         return in_array($value, $allowed, true) ? $value : 'laybutton1';
      },
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_button_style_variant',
        array(
            'label'          => lay_t('Button Style Variant', 'customizer_menu_submenu_button_style_variant_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_button_style_variant',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array(
                'laybutton1' => lay_t('Button 1', 'button_1'),
                'laybutton2' => lay_t('Button 2', 'button_2'),
                'laybutton3' => lay_t('Button 3', 'button_3'),
            ),
            'active_callback' => 'is_mobile_button_menu_style_enabled',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_vertically_center_menupoints',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_vertically_center_menupoints',
        array(
            'label'          => lay_t('Vertically center menu points', 'customizer_mobile_menu_vertically_center_menupoints_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_vertically_center_menupoints',
            'type'           => 'checkbox',
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_style_2_3_or_4',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_vertically_center_y_offset',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_vertically_center_y_offset',
        array(
            'label'          => lay_t('Y-Offset (px)', 'customizer_mobile_menu_vertically_center_y_offset_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_vertically_center_y_offset',
            'type'           => 'number',
            'input_attrs'    => array( 'step' => 1 ),
            'priority'       => 0,
            'active_callback' => 'is_vertically_center_menupoints_enabled',
        )
    )
);

// Include mobile pill controls
include get_template_directory().'/customizer/assets/php/mobile_menu_pill_controls.php';



$wp_customize->add_setting( 'mobile_menu_isfixed',
   array(
      'default' => true,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_isfixed',
        array(
            'label'          => lay_t('is fixed', 'customizer_mobile_menu_is_fixed_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_isfixed',
            'type'           => 'checkbox',
            'priority'       => 0,
            // Keep visible even when "Pill Menu Style" is enabled.
            'active_callback' => 'is_mobile_menu_hidden',
            'description' => lay_t('When using "Menu Style" &rarr; "Style 3", we recommend enabling this setting.', 'customizer_mobile_menu_style_3_recommend_fixed_description')
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_disable_body_scroll',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_disable_body_scroll',
        array(
            'label'          => lay_t('disable scroll of website when menu is open', 'customizer_mobile_menu_disable_body_scroll_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_disable_body_scroll',
            'type'           => 'checkbox',
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_move_away_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_move_away_when_scrolling_down',
   array(
      'label'          => lay_t('move away when scrolling down', 'customizer_mobile_menu_move_away_label'),
      'section'        => $section,
      'settings'       => 'mobile_menu_move_away_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 0,
      'active_callback' => 'show_move_away_when_scrolling_down'
   )
) );


$wp_customize->add_setting( 'mobile_menu_fade_out_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_fade_out_when_scrolling_down',
   array(
      'label'          => lay_t('fade out when scrolling down', 'customizer_mobile_menu_fade_out_label'),
      'section'        => $section,
      'settings'       => 'mobile_menu_fade_out_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 0,
      'active_callback' => 'show_move_away_when_scrolling_down'
   )
) );

$wp_customize->add_setting( 'mobile_menu_do_animation',
   array(
      'default' => true,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_do_animation',
        array(
            'label'          => lay_t('do Animation', 'customizer_mobile_menu_do_animation_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_do_animation',
            'type'           => 'checkbox',
            'priority'       => 0,
            'active_callback' => 'show_do_animation'
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_position',
   array(
      'default' => 'right',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_position',
        array(
            'label'          => lay_t('Position', 'customizer_mobile_menu_position_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_position',
            'type'           => 'select',
            'priority'       => 1,
            'choices'        => array(
                'left' => lay_t('top left', 'customizer_mobile_menu_position_top_left'),
                'center' => lay_t('top center', 'customizer_mobile_menu_position_top_center'),
                'right' => lay_t('top right', 'customizer_mobile_menu_position_top_right'),
                'bottom-left' => lay_t('bottom left', 'customizer_mobile_menu_position_bottom_left'),
                'bottom-center' => lay_t('bottom center', 'customizer_mobile_menu_position_bottom_center'),
                'bottom-right' => lay_t('bottom right', 'customizer_mobile_menu_position_bottom_right'),
            )
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_arrangement',
    array(
    'default' => 'horizontal',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_arrangement',
        array(
            'label'          => lay_t('Menu Points Arrangement', 'customizer_mobile_menu_arrangement_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_arrangement',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array(
               'horizontal' => lay_t('horizontal', 'customizer_mobile_menu_arrangement_horizontal'),
               'vertical' => lay_t('vertical', 'customizer_mobile_menu_arrangement_vertical'),
            ),
            'active_callback' => 'is_mobile_menu_hidden'
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_textformat',
    array(
    'default' => 'Default',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_mobile_menu_text_format_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_textformat',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'is_mobile_menu_hidden'
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_text_align',
   array(
      'default' => 'left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_text_align',
        array(
            'label'          => lay_t('Text Align', 'customizer_mobile_menu_text_align_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_text_align',
            'type'           => 'select',
            // Keep this after the "Position" control so the UI flows:
            // "Text Format" -> "Position" -> "Text Align".
            'priority'       => 2,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_mobile_menu_text_align_left'),
               'center' => lay_t('center', 'customizer_mobile_menu_text_align_center'),
               'right' => lay_t('right', 'customizer_mobile_menu_text_align_right'),
            ),
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);




// space top - using combined control
$wp_customize->add_setting( 'mobile_menu_spacetop_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'mobile_menu_spacetop',
   array(
      'default' => '12',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_menu_spacetop',
        array(
            'label'          => lay_t('Space Top', 'customizer_mobile_menu_space_top_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_spacetop',
            'unit_setting'   => 'mobile_menu_spacetop_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 0
        )
    )
);

// space bottom - using combined control
$wp_customize->add_setting( 'mobile_menu_spacebottom_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'mobile_menu_spacebottom',
   array(
      'default' => '12',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_menu_spacebottom',
        array(
            'label'          => lay_t('Space Bottom', 'customizer_mobile_menu_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_spacebottom',
            'unit_setting'   => 'mobile_menu_spacebottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 0
        )
    )
);

// space left - using combined control
$wp_customize->add_setting( 'mobile_menu_spaceleft_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'mobile_menu_spaceleft',
   array(
      'default' => '5',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_menu_spaceleft',
        array(
            'label'          => lay_t('Space Left', 'customizer_mobile_menu_space_left_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_spaceleft',
            'unit_setting'   => 'mobile_menu_spaceleft_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 0
        )
    )
);

// spaceright - using combined control
$wp_customize->add_setting( 'mobile_menu_spaceright_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'mobile_menu_spaceright',
   array(
      'default' => '5',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_menu_spaceright',
        array(
            'label'          => lay_t('Space Right', 'customizer_mobile_menu_space_right_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_spaceright',
            'unit_setting'   => 'mobile_menu_spaceright_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_spacebetween',
   array(
      'default' => '5',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_spacebetween',
        array(
            'label'          => lay_t('Space Between (px)', 'customizer_mobile_menu_space_between_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_spacebetween',
            'type'           => 'number',
            'priority'       => 0
        )
    )
);

//
$wp_customize->add_setting( 'mobile_menu_fontsize',
   array(
      'default' => Customizer::$defaults['mobile_menu_fontsize'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_fontsize',
        array(
            'label'          => lay_t('Font Size Menu Points (px)', 'customizer_mobile_menu_font_size_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_fontsize',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menupoint_lineheight',
   array(
      'default' => '1',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menupoint_lineheight',
        array(
            'label'          => lay_t('Menu Point Line Height', 'customizer_mobile_menu_line_height_label'),
            'section'        => $section,
            'settings'       => 'mobile_menupoint_lineheight',
            'type'           => 'number',
            'input_attrs'    => array('step' => '0.01'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menupoint_paddingtop',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menupoint_paddingtop',
        array(
            'label'          => lay_t('Menu Point Padding Top (px)', 'customizer_mobile_menu_padding_top_label'),
            'section'        => $section,
            'settings'       => 'mobile_menupoint_paddingtop',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_hidden',
        )
    )
);

$wp_customize->add_setting( 'mobile_menupoint_paddingleft',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menupoint_paddingleft',
        array(
            'label'          => lay_t('Menu Point Padding Left (px)', 'customizer_mobile_menu_padding_left_label'),
            'section'        => $section,
            'settings'       => 'mobile_menupoint_paddingleft',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menupoint_paddingbottom',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menupoint_paddingbottom',
        array(
            'label'          => lay_t('Menu Point Padding Bottom (px)', 'customizer_mobile_menu_padding_bottom_label'),
            'section'        => $section,
            'settings'       => 'mobile_menupoint_paddingbottom',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menupoint_paddingright',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
            new WP_Customize_Control(
        $wp_customize,
        'mobile_menupoint_paddingright',
        array(
            'label'          => lay_t('Menu Point Padding Right (px)', 'customizer_mobile_menu_point_padding_right_label'),
            'section'        => $section,
            'settings'       => 'mobile_menupoint_paddingright',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 0,
            'active_callback' => 'is_mobile_menu_visible_and_not_pill',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_background_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'mobile_menu_background_opacity',
   array(
     'label' => lay_t('Menu Points Background Opacity (%)', 'customizer_mobile_menu_points_background_opacity_label'),
      'section' => $section,
      'settings' => 'mobile_menu_background_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 0,
      'active_callback' => 'is_mobile_menu_visible_and_not_pill',
   )
) );

$wp_customize->add_setting( 'mobile_menu_blurry',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_blurry',
   array(
     'label'          => lay_t('make blurry', 'customizer_mobile_menu_make_blurry_label'),
      'section'        => $section,
      'settings'       => 'mobile_menu_blurry',
      'type'           => 'checkbox',
      'priority'       => 0,
      'description' => lay_t('<strong>Does not work for Menu Style 3 on mobile devices.</strong> Only works if background is a little transparent ("Opacity" less than 100)', 'customizer_mobile_menu_blurry_description'),
      'active_callback' => 'is_mobile_menu_visible_and_not_pill'
   )
) );

$wp_customize->add_setting( 'mobile_menu_blur_amount',
   array(
      'default' => '20',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_blur_amount',
   array(
     'label' => lay_t('Blur Amount (px)', 'customizer_mobile_menu_blur_amount_label'),
      'section' => $section,
      'settings' => 'mobile_menu_blur_amount',
      'type' => 'number',
      'input_attrs' => array('min' => '0', 'max' => '100'),
      'priority' => 0,
      'active_callback' => 'is_active_blurry'
   )
) );

$wp_customize->add_setting( 'mobile_menu_background_color',
   array(
      'default' => $lighterBgColor,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_background_color',
   array(
     'label' => lay_t('Menu Background Color', 'customizer_mobile_menu_background_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_background_color',
      'priority'   => 0,
      'active_callback' => 'is_mobile_menu_visible_and_not_pill',
   )
) );

$wp_customize->add_setting( 'mobile_menu_text_color',
   array(
      'default' => $txtColor,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_text_color',
   array(
     'label' => lay_t('Menu Points Text Color', 'customizer_mobile_menu_points_text_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_text_color',
      'priority'   => 0,
      'active_callback' => 'is_mobile_menu_hidden',
   )
) );

$wp_customize->add_setting( 'mobile_menu_points_underline_color',
   array(
      'default' => $darker,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_points_underline_color',
   array(
     'label' => lay_t('Menu Points Lines Color', 'customizer_mobile_menu_points_lines_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_points_underline_color',
      'priority'   => 0,
      'active_callback' => 'is_active_underline_color',
   )
) );

$wp_customize->add_setting( 'mobile_menu_current_menu_item_color',
   array(
      'default' => $txtColor,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_current_menu_item_color',
   array(
     'label' => lay_t('Active Menu Point Color', 'customizer_mobile_menu_active_point_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_current_menu_item_color',
      'priority'   => 0,
      'active_callback' => 'is_mobile_menu_hidden',
   )
) );

$wp_customize->add_setting( 'mobile_menu_current_menu_item_background_color',
   array(
      'default' => $darker,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_current_menu_item_background_color',
   array(
     'label' => lay_t('Active Menu Point Background Color', 'customizer_mobile_menu_active_point_background_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_current_menu_item_background_color',
      'priority'   => 0,
      'active_callback' => 'is_mobile_menu_visible_and_not_pill',
   )
) );


$wp_customize->add_setting( 'mobile_menu_current_menu_item_underline_strokewidth',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_current_menu_item_underline_strokewidth',
   array(
     'label' => lay_t('Active Menu Point Underline Strokewidth (px)', 'customizer_mobile_menu_active_point_underline_strokewidth_label'),
      'section' => $section,
      'settings' => 'mobile_menu_current_menu_item_underline_strokewidth',
      'type'           => 'number',
      'input_attrs' => array('min' => '1'),
      'priority' => 0,
   )
) );

$default = get_theme_mod('link_underline_offset', 3);
$wp_customize->add_setting( 'mobile_menu_current_menu_item_underline_offset',
	array(
		'default' => $default,
		'type' => 'theme_mod',
		'capability' => 'edit_theme_options',
		'transport' => 'refresh',
	)
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_current_menu_item_underline_offset',
   array(
     'label' => lay_t('Active Menu Point Underline Offset (px)', 'customizer_mobile_menu_active_point_underline_offset_label'),
      'section' => $section,
      'settings' => 'mobile_menu_current_menu_item_underline_offset',
      'type'           => 'number',
      'priority' => 0,
   )
) );