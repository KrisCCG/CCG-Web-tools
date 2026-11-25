<?php

$section = 'navigation_mouseover_options';

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Menu Point Mouseover', 'customizer_menu_mouseover_title'),
      'priority' => 35,
      'capability' => 'edit_theme_options',
      'panel' => 'navigation_panel'
   ) 
);

$wp_customize->add_setting( 'navmouseover_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'navmouseover_color',
   array(
     'label' => lay_t('Text Color', 'customizer_menu_mouseover_text_color_label'),
      'section' => $section,
      'settings' => 'navmouseover_color',
      'priority'   => 40
   )
) );

$wp_customize->add_setting( 'navmouseover_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'navmouseover_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_menu_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'navmouseover_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 41,
   )
) );

$wp_customize->add_setting( 'navmouseover_underline_strokewidth',
   array(
      'default' => Customizer::$defaults['underline_width'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navmouseover_underline_strokewidth',
   array(
     'label' => lay_t('Underline Strokewidth (px)', 'customizer_menu_mouseover_underline_strokewidth_label'),
      'section' => $section,
      'settings' => 'navmouseover_underline_strokewidth',
      'type'           => 'number',
      'input_attrs' => array('min' => '1'),
      'priority' => 80,
   )
) );

$wp_customize->add_setting( 'navmouseover_fontweight',
   array(
      'default' => 'default',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navmouseover_fontweight',
   array(
     'label' => lay_t('Font Weight', 'customizer_menu_mouseover_font_weight_label'),
      'section' => $section,
      'settings' => 'navmouseover_fontweight',
      'type'           => 'select',
      'choices'        => array(
            'default' => lay_t('Default', 'customizer_menu_mouseover_weight_default'),
            '100' => lay_t('Thin (100)', 'customizer_menu_mouseover_weight_100'),
            '200' => lay_t('Extra Light (200)', 'customizer_menu_mouseover_weight_200'),
            '300' => lay_t('Light (300)', 'customizer_menu_mouseover_weight_300'),
            '400' => lay_t('Normal (400)', 'customizer_menu_mouseover_weight_400'),
            '500' => lay_t('Medium (500)', 'customizer_menu_mouseover_weight_500'),
            '600' => lay_t('Semi Bold (600)', 'customizer_menu_mouseover_weight_600'),
            '700' => lay_t('Bold (700)', 'customizer_menu_mouseover_weight_700'),
            '800' => lay_t('Extra Bold (800)', 'customizer_menu_mouseover_weight_800'),
            '900' => lay_t('Black (900)', 'customizer_menu_mouseover_weight_900'),
      ),
        'priority' => 80,
   )
) );

$default = get_theme_mod('link_underline_offset', 3);
$wp_customize->add_setting( 'navmouseover_link_underline_offset',
   array(
      'default' => $default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navmouseover_link_underline_offset',
   array(
     'label' => lay_t('Underline Offset', 'customizer_menu_mouseover_underline_offset_label'),
      'section' => $section,
      'settings' => 'navmouseover_link_underline_offset',
      'type' => 'number',
      'priority' => 100,
   )
) );