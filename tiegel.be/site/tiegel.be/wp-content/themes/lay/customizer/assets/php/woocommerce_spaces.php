<?php

$section = 'lay_woocommerce_spaces';
$descr = "";

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('WooCommerce Spaces', 'customizer_woocommerce_spaces_title'),
      'priority' => 0,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description'    => $descr
   )
);

$wp_customize->add_setting( 'lay_woocommerce_space_leftright_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'lay_woocommerce_space_leftright',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'lay_woocommerce_space_leftright',
        array(
            'label'          => lay_t('Space left and right of Content', 'customizer_woocommerce_spaces_left_right_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_space_leftright',
            'unit_setting'   => 'lay_woocommerce_space_leftright_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 29
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_space_top_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'lay_woocommerce_space_top',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'lay_woocommerce_space_top',
        array(
            'label'          => lay_t('Space Top', 'customizer_woocommerce_spaces_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_space_top',
            'unit_setting'   => 'lay_woocommerce_space_top_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 39
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_space_bottom_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'lay_woocommerce_space_bottom',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'lay_woocommerce_space_bottom',
        array(
            'label'          => lay_t('Space Bottom', 'customizer_woocommerce_spaces_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_space_bottom',
            'unit_setting'   => 'lay_woocommerce_space_bottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 49
        )
    )
);