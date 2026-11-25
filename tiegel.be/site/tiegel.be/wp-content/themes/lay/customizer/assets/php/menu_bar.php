<?php

$section = 'navigationbar_options';

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Menu Bar', 'customizer_menu_bar_title'),
      'priority' => 60,
      'capability' => 'edit_theme_options',
      'panel' => 'navigation_panel',
      'description' => lay_t('These options are for a fixed background bar for the primary menu.', 'customizer_menu_bar_description')
   ) 
);

// navbar should be hidden by default. we used to show it by default
// not sure if this is the right way to go
$navbar_hide_default = get_theme_mod('navbar_hide', 'value_was_never_set');
if($navbar_hide_default == 'value_was_never_set'){
    $navbar_hide_default = true;
} else {
    $navbar_hide_default = false;
}

$wp_customize->add_setting( 'navbar_hide',
   array(
      'default' => $navbar_hide_default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'navbar_hide',
        array(
            'label'          => lay_t('hide', 'customizer_menu_bar_hide_label'),
            'section'        => $section,
            'settings'       => 'navbar_hide',
            'type'           => 'checkbox',
            'priority'       => 4,
        )
    )
);

// navbar hide on scroll down
// for backwards compatibility, default is: $mod = get_theme_mod('nav_hidewhenscrolling', "");
$navbar_hidewhenscrolling_default = get_theme_mod('nav_hidewhenscrolling', "");
$wp_customize->add_setting( 'navbar_hidewhenscrolling',
   array(
      'default' => $navbar_hidewhenscrolling_default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'navbar_hidewhenscrolling',
        array(
            'label'          => lay_t('move away when scrolling down', 'customizer_menu_bar_move_away_label'),
            'section'        => $section,
            'settings'       => 'navbar_hidewhenscrolling',
            'type'           => 'checkbox',
            'priority'       => 7,
        )
    )
);

$navbar_fadeout_whenscrolling_default = get_theme_mod('fadeout_whenscrolling', "");
$wp_customize->add_setting( 'navbar_fadeout_whenscrolling',
   array(
      'default' => $navbar_fadeout_whenscrolling_default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'navbar_fadeout_whenscrolling',
        array(
            'label'          => lay_t('fade out when scrolling down', 'customizer_menu_bar_fade_out_label'),
            'section'        => $section,
            'settings'       => 'navbar_fadeout_whenscrolling',
            'type'           => 'checkbox',
            'priority'       => 7,
        )
    )
);

$wp_customize->add_setting( 'navbar_height_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);
$wp_customize->add_setting( 'navbar_height',
   array(
      'default' => '60',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'navbar_height',
        array(
            'label'          => lay_t('Height', 'customizer_menu_bar_height_label'),
            'section'        => $section,
            'settings'       => 'navbar_height',
            'unit_setting'   => 'navbar_height_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 11
        )
    )
);

$wp_customize->add_setting( 'navbar_color',
   array(
      'default' => '#fff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'navbar_color',
   array(
      'label' => lay_t('Color', 'customizer_menu_bar_color_label'),
      'section' => $section,
      'settings' => 'navbar_color',
      'priority'   => 40
   )
) );

$wp_customize->add_setting( 'navbar_opacity',
   array(
      'default' => '90',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'navbar_opacity',
   array(
      'label' => lay_t('Opacity (%)', 'customizer_menu_bar_opacity_label'),
      'section' => $section,
      'settings' => 'navbar_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 41,
   )
) );

$wp_customize->add_setting( 'navbar_blurry',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navbar_blurry',
   array(
      'label'          => lay_t('make blurry', 'customizer_menu_bar_blurry_label'),
      'section'        => $section,
      'settings'       => 'navbar_blurry',
      'type'           => 'checkbox',
      'priority'       => 43,
      'description' => lay_t('Only works if menubar is a little transparent ("Opacity" less than 100)', 'customizer_menu_bar_blurry_description')
   )
) );

$wp_customize->add_setting( 'navbar_blur_amount',
   array(
      'default' => '20',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'navbar_blur_amount',
   array(
      'label' => lay_t('Blur Amount (px)', 'customizer_menu_bar_blur_amount_label'),
      'section' => $section,
      'settings' => 'navbar_blur_amount',
      'type' => 'number',
      'input_attrs' => array('min' => '0', 'max' => '100'),
      'priority' => 44,
   )
) );

$wp_customize->add_setting( 'navbar_show_border',
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
        'navbar_show_border',
        array(
            'label'          => lay_t('show border', 'customizer_menu_bar_show_border_label'),
            'section'        => $section,
            'settings'       => 'navbar_show_border',
            'type'           => 'checkbox',
            'priority'       => 50,
        )
    )
);

$wp_customize->add_setting( 'navbar_border_color',
   array(
      'default' => '#ccc',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'navbar_border_color',
   array(
      'label' => lay_t('Border color', 'customizer_menu_bar_border_color_label'),
      'section' => $section,
      'settings' => 'navbar_border_color',
      'priority'   => 55
   )
) );