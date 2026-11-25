<?php
function is_mobile_pill_style(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
         return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style' ){
         return true;
    }
    return false;
}

function is_mobile_menu_visible_and_not_pill(){
    if( get_theme_mod('mobile_hide_menu', false) == 1 ){
         return false;
    }
    if( get_theme_mod('mobile_menu_style', 'style_1') == 'pill_menu_style' ){
         return false;
    }
    return true;
}

// Mobile Pill Style Controls
// This file contains all customizer controls related to the mobile pill menu style

// ========================================
// MOBILE PILL ELEMENT CONTROLS
// ========================================

// Mobile Pill Color
$wp_customize->add_setting( 'mobile_pill_color',
    array(
    'default' => '#ffffff',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'mobile_pill_color',
        array(
            'label'          => lay_t('Pill Color', 'customizer_mobile_menu_pill_color_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_color',
            'priority'       => 99,
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Padding
$wp_customize->add_setting( 'mobile_pill_padding',
    array(
    'default' => 8,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_pill_padding',
        array(
            'label'          => lay_t('Pill Padding', 'customizer_mobile_menu_pill_padding_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_padding',
            'type'           => 'number',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 50, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Border Radius
$wp_customize->add_setting( 'mobile_pill_border_radius',
    array(
    'default' => 28,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_pill_border_radius',
        array(
            'label'          => lay_t('Pill Border Radius', 'customizer_mobile_menu_pill_border_radius_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_border_radius',
            'type'           => 'number',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 100, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// ========================================
// MOBILE PILL MENU CONTROLS
// ========================================

// Mobile Pill Menu Color
$wp_customize->add_setting( 'mobile_pill_menu_color',
    array(
    'default' => '#BDBDBD',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Color_Control(
        $wp_customize,
        'mobile_pill_menu_color',
        array(
            'label'          => lay_t('Pill Menu Color', 'customizer_mobile_menu_pill_menu_color_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_menu_color',
            'priority'       => 99,
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Menu Transparency
$wp_customize->add_setting( 'mobile_pill_menu_transparency',
    array(
    'default' => 60,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Range_With_Number_Custom_Control(
        $wp_customize,
        'mobile_pill_menu_transparency',
        array(
            'label'          => lay_t('Pill Menu Background Opacity', 'customizer_mobile_menu_pill_menu_background_opacity_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_menu_transparency',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 100, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Menu Background Blur
$wp_customize->add_setting( 'mobile_pill_menu_background_blur',
    array(
    'default' => 10,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Range_With_Number_Custom_Control(
        $wp_customize,
        'mobile_pill_menu_background_blur',
        array(
            'label'          => lay_t('Pill Menu Background Blur', 'customizer_mobile_menu_pill_menu_background_blur_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_menu_background_blur',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 20, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Menu Padding
$wp_customize->add_setting( 'mobile_pill_menu_padding',
    array(
    'default' => 15,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_pill_menu_padding',
        array(
            'label'          => lay_t('Pill Menu Padding', 'customizer_mobile_menu_pill_menu_padding_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_menu_padding',
            'type'           => 'number',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 100, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);

// Mobile Pill Menu Border Radius
$wp_customize->add_setting( 'mobile_pill_menu_border_radius',
    array(
    'default' => 32,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_pill_menu_border_radius',
        array(
            'label'          => lay_t('Pill Menu Border Radius', 'customizer_mobile_menu_pill_menu_border_radius_label'),
            'section'        => $section,
            'settings'       => 'mobile_pill_menu_border_radius',
            'type'           => 'number',
            'priority'       => 99,
            'input_attrs'    => array('min' => 0, 'max' => 100, 'step' => 1),
            'active_callback' => 'is_mobile_pill_style'
        )
    )
);
