<?php

$section = 'navigation_active_options';

$wp_customize->add_section( $section, 
    array(
        'title' => lay_t('Menu Point Active', 'customizer_menu_active_title'),
        'priority' => 40,
        'capability' => 'edit_theme_options',
        'panel' => 'navigation_panel',
        'description' => lay_t('Menu point of current "Project", "Page" or "Category"', 'customizer_menu_active_description')
    )
);

$wp_customize->add_setting( 'navactive_color',
    array(
        'default' => Customizer::$defaults['color'],
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
    $wp_customize,
    'navactive_color',
    array(
        'label' => lay_t('Text Color', 'customizer_menu_active_text_color_label'),
        'section' => $section,
        'settings' => 'navactive_color',
        'priority'   => 40
    )
) );

$wp_customize->add_setting( 'navactive_opacity',
    array(
        'default' => '100',
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'postMessage',
    ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
    $wp_customize,
    'navactive_opacity',
    array(
        'label' => lay_t('Opacity (%)', 'customizer_menu_active_opacity_label'),
        'section' => $section,
        'settings' => 'navactive_opacity',
        'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
        'priority' => 41,
    )
) );

$wp_customize->add_setting( 'navactive_fontweight',
    array(
        'default' => Customizer::$defaults['fontweight'],
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);      
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'navactive_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_menu_active_font_weight_label'),
            'section'        => $section,
            'settings'       => 'navactive_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'navactive_underline_strokewidth',
    array(
        'default' => Customizer::$defaults['underline_width'],
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);      
$wp_customize->add_control( new WP_Customize_Control(
    $wp_customize,
    'navactive_underline_strokewidth',
    array(
        'label' => lay_t('Underline Strokewidth (px)', 'customizer_menu_active_underline_strokewidth_label'),
        'section' => $section,
        'settings' => 'navactive_underline_strokewidth',
        'type'           => 'number',
        'input_attrs' => array('min' => '1'),
        'priority' => 80,
    )
) );

$default = get_theme_mod('link_underline_offset', 3);
$wp_customize->add_setting( 'navactive_link_underline_offset',
   array(
      'default' => $default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navactive_link_underline_offset',
   array(
      'label' => lay_t('Underline Offset', 'customizer_menu_active_underline_offset_label'),
      'section' => $section,
      'settings' => 'navactive_link_underline_offset',
      'type'           => 'number',
      'priority' => 100,
   )
) );