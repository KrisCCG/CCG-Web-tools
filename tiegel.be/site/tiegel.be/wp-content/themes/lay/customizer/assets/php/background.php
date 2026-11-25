<?php

$section = 'background_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Background', 'customizer_background_title'),
      'priority' => 40,
      'capability' => 'edit_theme_options',
      'description' => lay_t('Background settings for the entire website. If you set a background color in your Gridder, for that page the Gridder background color will show.', 'customizer_background_description'),
   )
);

$wp_customize->add_setting( 'bg_color',
   array(
      'default' => '#ffffff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'bg_color',
   array(
     'label' => lay_t('Background Color', 'customizer_background_color_label'),
      'section' => $section,
      'settings' => 'bg_color',
      'priority'   => 10
   )
) );

$wp_customize->add_setting( 'bg_image',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Upload_Control(
   $wp_customize,
   'bg_image',
   array(
     'label' => lay_t('Background Image Desktop', 'customizer_background_image_desktop_label'),
      'section' => $section,
      'settings' => 'bg_image',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'bg_position',
   array(
      'default' => 'standard',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'bg_position',
        array(
            'label'          => lay_t('Background Image Position', 'customizer_background_image_position_label'),
            'section'        => $section,
            'settings'       => 'bg_position',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => array(
               'standard' => lay_t('Standard', 'customizer_background_image_position_standard'),
               'stretch' => lay_t('Stretched and Fixed', 'customizer_background_image_position_stretch'),
               'center' => lay_t('Centered and Fixed', 'customizer_background_image_position_center'),
            )
        )
    )
);


$wp_customize->add_setting( 'bg_video',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Upload_Control(
   $wp_customize,
   'bg_video',
   array(
      'description' => lay_t('This video should be a .mp4 and landscape format. The video wont have sound on the website.', 'customizer_background_video_description'),
     'label' => lay_t('Background Video Desktop', 'customizer_background_video_desktop_label'),
      'section' => $section,
      'settings' => 'bg_video',
      'priority'   => 40
   )
) );