<?php

$section = 'mobile_background';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Background', 'customizer_mobile_background_title'),
      'priority' => 40,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel'
   )
);

$wp_customize->add_setting( 'mobile_bg_image',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Upload_Control(
   $wp_customize,
   'mobile_bg_image',
   array(
     'label' => lay_t('Background Image', 'customizer_mobile_background_image_label'),
      'section' => $section,
      'settings' => 'mobile_bg_image',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'mobile_bg_position',
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
        'mobile_bg_position',
        array(
            'label'          => lay_t('Background Image Position', 'customizer_mobile_background_image_position_label'),
            'section'        => $section,
            'settings'       => 'mobile_bg_position',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => array(
               'standard' => lay_t('Standard', 'customizer_mobile_background_image_position_standard'),
               'stretch' => lay_t('Stretched', 'customizer_mobile_background_image_position_stretch'),
               'center' => lay_t('Centered', 'customizer_mobile_background_image_position_center'),
            ),
            'description'    => lay_t('Note that on iPhone Safari <a href="https://stackoverflow.com/questions/23236158/how-to-replicate-background-attachment-fixed-on-ios" target="_blank">background images cannot be fixed</a>.', 'customizer_mobile_background_fixed_note')
        )
    )
);

$wp_customize->add_setting( 'bg_video_mobile',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Upload_Control(
   $wp_customize,
   'bg_video_mobile',
   array(
      'description' => lay_t('This video should be a .mp4 and portrait format. The video wont have sound on the website.', 'customizer_mobile_background_video_description'),
     'label' => lay_t('Background Video Mobile', 'customizer_mobile_background_video_label'),
      'section' => $section,
      'settings' => 'bg_video_mobile',
      'priority'   => 40
   )
) );