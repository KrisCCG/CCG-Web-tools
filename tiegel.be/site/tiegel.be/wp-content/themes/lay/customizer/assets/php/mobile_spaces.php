<?php

$section = 'mobile_spaces';
$descr = "";
if(get_option('misc_options_extra_gridder_for_phone', 'on') == "on"){
  $descr = lay_t('These settings are only for automatically generated phone versions of your layouts. To change these spaces for your custom phone layouts go to "Lay Options" &rarr; "Gridder Defaults" &rarr; "Phone Gridder Defaults".', 'customizer_mobile_spaces_description');
}

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Spaces', 'customizer_mobile_spaces_title'),
      'priority' => 20,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel',
      'description'    => $descr
   )
);

$wp_customize->add_setting( 'mobile_space_between_elements_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_between_elements',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_between_elements',
        array(
            'label'          => lay_t('Space between Elements', 'customizer_mobile_spaces_between_elements_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_between_elements',
            'unit_setting'   => 'mobile_space_between_elements_mu',
            'unit_choices'   => array('px' => 'px', '%' => '%'),
            'step'           => '0.1',
            'priority'       => 19
        )
    )
);

$wp_customize->add_setting( 'mobile_space_leftright_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_leftright',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_leftright',
        array(
            'label'          => lay_t('Space left and right of Content', 'customizer_mobile_spaces_left_right_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_leftright',
            'unit_setting'   => 'mobile_space_leftright_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 29
        )
    )
);

$wp_customize->add_setting( 'mobile_space_top_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_top',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_top',
        array(
            'label'          => lay_t('Space Top', 'customizer_mobile_spaces_space_top_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_top',
            'unit_setting'   => 'mobile_space_top_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 39
        )
    )
);

$wp_customize->add_setting( 'mobile_space_bottom_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_bottom',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_bottom',
        array(
            'label'          => lay_t('Space Bottom', 'customizer_mobile_spaces_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_bottom',
            'unit_setting'   => 'mobile_space_bottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 49
        )
    )
);

// footer

$wp_customize->add_setting( 'mobile_space_top_footer_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_top_footer',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_top_footer',
        array(
            'label'          => lay_t('Space Top Footer', 'customizer_mobile_spaces_space_top_footer_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_top_footer',
            'unit_setting'   => 'mobile_space_top_footer_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'mobile_space_bottom_footer_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'mobile_space_bottom_footer',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_space_bottom_footer',
        array(
            'label'          => lay_t('Space Bottom Footer', 'customizer_mobile_spaces_space_bottom_footer_label'),
            'section'        => $section,
            'settings'       => 'mobile_space_bottom_footer',
            'unit_setting'   => 'mobile_space_bottom_footer_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 65
        )
    )
);