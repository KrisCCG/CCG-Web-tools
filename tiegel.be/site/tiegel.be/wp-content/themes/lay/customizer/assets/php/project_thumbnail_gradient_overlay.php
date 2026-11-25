<?php

$section = 'pt_gradient_overlay_section';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Gradient Overlay', 'customizer_pt_gradient_overlay_title'),
      'priority' => 22,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel'
   )
);

// Gradient top (refresh so preview updates when toggled)
$wp_customize->add_setting( 'pt_gradient_top',
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
      'pt_gradient_top',
      array(
         'label'          => lay_t('Gradient Top', 'customizer_pt_gradient_top_label'),
         'section'        => $section,
         'settings'       => 'pt_gradient_top',
         'type'           => 'checkbox',
         'priority'       => 10,
      )
   )
);

// Gradient bottom (refresh so preview updates when toggled)
$wp_customize->add_setting( 'pt_gradient_bottom',
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
      'pt_gradient_bottom',
      array(
         'label'          => lay_t('Gradient Bottom', 'customizer_pt_gradient_bottom_label'),
         'section'        => $section,
         'settings'       => 'pt_gradient_bottom',
         'type'           => 'checkbox',
         'priority'       => 15,
      )
   )
);

// Gradient color
$wp_customize->add_setting( 'pt_gradient_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pt_gradient_color',
   array(
      'label'   => lay_t('Gradient Color', 'customizer_pt_gradient_color_label'),
      'section' => $section,
      'settings' => 'pt_gradient_color',
      'priority' => 20,
   )
) );

// Gradient opacity
$wp_customize->add_setting( 'pt_gradient_opacity',
   array(
      'default' => '50',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pt_gradient_opacity',
   array(
      'label'       => lay_t('Opacity (%)', 'customizer_pt_gradient_opacity_label'),
      'section'     => $section,
      'settings'    => 'pt_gradient_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority'    => 25,
   )
) );
