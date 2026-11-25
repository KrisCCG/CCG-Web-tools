<?php

function mobile_menubar_show_move_away_when_scrolling_down(){
    if( get_theme_mod('mobile_hide_menubar') == 1 ) {
        return false;
    }
    if( get_theme_mod('m_menubar_isfixed') != 1 ) {
        return false;
    }
    return true;
}

function is_mobile_menubar_hidden(){
   return get_theme_mod('mobile_hide_menubar') == 1 ? false : true;
}

function is_mobile_menubar_hidden_and_blurry_active(){
   $mobile_menubar_hidden = get_theme_mod('mobile_hide_menubar') == 1 ? true : false;
   $is_blurry = get_theme_mod('mobile_menu_bar_blurry', false) == 1 ? true : false;
   if( $is_blurry == false || $mobile_menubar_hidden == true ) {
      return false;
   }
   return true;
}

$txtColor = Customizer::$defaults['mobile_menu_txt_color'];
$lighterBgColor = Customizer::$defaults['mobile_menu_light_color'];
$darker = Customizer::$defaults['mobile_menu_dark_color'];

$section = 'mobile_menu_bar';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Menu Bar', 'customizer_mobile_menu_bar_title'),
      'priority' => 5,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel',
   )
);

$wp_customize->add_setting( 'mobile_hide_menubar',
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
        'mobile_hide_menubar',
        array(
            'label'          => lay_t('hide', 'customizer_mobile_menu_bar_hide_label'),
            'section'        => $section,
            'settings'       => 'mobile_hide_menubar',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'm_menubar_isfixed',
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
        'm_menubar_isfixed',
        array(
            'label'          => lay_t('is fixed', 'customizer_mobile_menu_bar_fixed_label'),
            'section'        => $section,
            'settings'       => 'm_menubar_isfixed',
            'type'           => 'checkbox',
            'priority'       => 3,
            'active_callback' => 'is_mobile_menubar_hidden',
        )
    )
);



$wp_customize->add_setting( 'mobile_menubar_move_away_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menubar_move_away_when_scrolling_down',
   array(
      'label'          => lay_t('move away when scrolling down', 'customizer_mobile_menu_bar_move_away_label'),
      'section'        => $section,
      'settings'       => 'mobile_menubar_move_away_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 5,
      'active_callback' => 'mobile_menubar_show_move_away_when_scrolling_down'
   )
) );

$wp_customize->add_setting( 'mobile_menubar_fade_out_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menubar_fade_out_when_scrolling_down',
   array(
      'label'          => lay_t('fade out when scrolling down', 'customizer_mobile_menu_bar_fade_out_label'),
      'section'        => $section,
      'settings'       => 'mobile_menubar_fade_out_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 5,
      'active_callback' => 'mobile_menubar_show_move_away_when_scrolling_down'
   )
) );

$wp_customize->add_setting( 'mobile_menubar_height',
   array(
      'default' => 40,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menubar_height',
        array(
            'label'          => lay_t('Height (px)', 'customizer_mobile_menu_bar_height_label'),
            'section'        => $section,
            'settings'       => 'mobile_menubar_height',
            'type'           => 'number',
            'priority'       => 10,
            'active_callback' => 'is_mobile_menubar_hidden',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_bar_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'mobile_menu_bar_opacity',
   array(
      'label' => lay_t('Menu Bar Opacity (%)', 'customizer_mobile_menu_bar_opacity_label'),
      'section' => $section,
      'settings' => 'mobile_menu_bar_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 20,
      'active_callback' => 'is_mobile_menubar_hidden',
   )
) );

$wp_customize->add_setting( 'mobile_menu_bar_blurry',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_bar_blurry',
   array(
      'label'          => lay_t('make Blurry', 'customizer_mobile_menu_bar_blurry_label'),
      'section'        => $section,
      'settings'       => 'mobile_menu_bar_blurry',
      'type'           => 'checkbox',
      'priority'       => 22,
      'description' => lay_t('Only works if menubar is a little transparent ("Opacity" less than 100)', 'customizer_menu_bar_blurry_description'),
      'active_callback' => 'is_mobile_menubar_hidden',
   )
) );

$wp_customize->add_setting( 'mobile_menu_bar_blur_amount',
   array(
      'default' => '20',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menu_bar_blur_amount',
   array(
      'label' => lay_t('Blur Amount (px)', 'customizer_mobile_menu_bar_blur_amount_label'),
      'section' => $section,
      'settings' => 'mobile_menu_bar_blur_amount',
      'type' => 'number',
      'input_attrs' => array('min' => '0', 'max' => '100'),
      'priority' => 25,
      'active_callback' => 'is_mobile_menubar_hidden_and_blurry_active',
   )
) );

$wp_customize->add_setting( 'mobile_menu_bar_color',
   array(
      'default' => $lighterBgColor,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_bar_color',
   array(
      'label' => lay_t('Menu Bar Background Color', 'customizer_mobile_menu_bar_background_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_bar_color',
      'priority'   => 30,
      'active_callback' => 'is_mobile_menubar_hidden',
   )
) );

$wp_customize->add_setting( 'mobile_menu_bar_show_border',
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
        'mobile_menu_bar_show_border',
        array(
            'label'          => lay_t('Show Menu Bar Border', 'customizer_mobile_menu_bar_show_border_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_bar_show_border',
            'type'           => 'checkbox',
            'priority'       => 35,
            'active_callback' => 'is_mobile_menubar_hidden',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_bar_border_color',
   array(
      'default' => $darker,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_bar_border_color',
   array(
      'label' => lay_t('Menu Bar Border Color', 'customizer_mobile_menu_bar_border_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_bar_border_color',
      'priority'   => 40,
      'active_callback' => 'is_mobile_menubar_hidden',
   )
) );
