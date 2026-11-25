<?php

$section = 'featuredimage_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Project Thumbnail Mouseover', 'customizer_project_thumbnail_mouseover_title'),
      'priority' => 21,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel'
   )
);


// brightness
$wp_customize->add_setting( 'fi_mo_change_brightness',
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
        'fi_mo_change_brightness',
        array(
            'label'          => lay_t('change Brightness', 'customizer_project_thumbnail_mouseover_change_brightness_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_change_brightness',
            'type'           => 'checkbox',
            'priority'       => 1,
        )
    )
);

$wp_customize->add_setting( 'fi_mo_brightness',
   array(
      'default' => '50',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'fi_mo_brightness',
   array(
    'label' => lay_t('Brightness (%)', 'customizer_project_thumbnail_mouseover_brightness_label'),
    'section' => $section,
    'settings' => 'fi_mo_brightness',
    'input_attrs' => array('min' => '0', 'max' => '200'),
    'description' => lay_t('Can be a value between 0-200', 'customizer_project_thumbnail_mouseover_brightness_description'),
    'type' => 'number',
    'priority' => 2,
   )
) );

// #brightness

// grayscale
$wp_customize->add_setting( 'fi_mo_change_grayscale',
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
        'fi_mo_change_grayscale',
        array(
            'label'          => lay_t('change Grayscale', 'customizer_project_thumbnail_mouseover_change_grayscale_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_change_grayscale',
            'type'           => 'checkbox',
            'priority'       => 4,
        )
    )
);

$wp_customize->add_setting( 'fi_mo_grayscale',
   array(
      'default' => '50',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'fi_mo_grayscale',
   array(
    'label' => lay_t('Grayscale (%)', 'customizer_project_thumbnail_mouseover_grayscale_label'),
    'section' => $section,
    'settings' => 'fi_mo_grayscale',
    'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
    'priority' => 5,
   )
) );

$wp_customize->add_setting( 'fi_mo_animate_brightness',
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
        'fi_mo_animate_brightness',
        array(
            'label'          => lay_t('animate Brightness/Grayscale', 'customizer_project_thumbnail_mouseover_animate_brightness_grayscale_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_animate_brightness',
            'type'           => 'checkbox',
            'priority'       => 6,
        )
    )
);
// #grayscale

$wp_customize->add_setting( 'fi_mo_show_color',
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
        'fi_mo_show_color',
        array(
            'label'          => lay_t('show Background Color', 'customizer_project_thumbnail_mouseover_show_background_color_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_show_color',
            'type'           => 'checkbox',
            'priority'       => 10,
        )
    )
);

$wp_customize->add_setting( 'fi_mo_background_color',
   array(
      'default' => '#fff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'fi_mo_background_color',
   array(
     'label' => lay_t('Background Color', 'customizer_project_thumbnail_mouseover_background_color_label'),
      'section' => $section,
      'settings' => 'fi_mo_background_color',
      'priority'   => 15
   )
) );

$wp_customize->add_setting( 'fi_mo_color_opacity',
   array(
      'default' => '50',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'fi_mo_color_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_project_thumbnail_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'fi_mo_color_opacity',
      'input_attrs' => array('min' => '0', 'max' => '100'),
      'type' => 'number',
      'priority' => 20,
   )
) );

$wp_customize->add_setting( 'fi_animate_color',
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
        'fi_animate_color',
        array(
            'label'          => lay_t('animate Background Color', 'customizer_project_thumbnail_mouseover_animate_background_color_label'),
            'section'        => $section,
            'settings'       => 'fi_animate_color',
            'type'           => 'checkbox',
            'priority'       => 22,
        )
    )
);

$wp_customize->add_setting( 'fi_mo_do_zoom',
   array(
      'default' => 'none',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'fi_mo_do_zoom',
        array(
            'label'          => lay_t('Zoom', 'customizer_project_thumbnail_mouseover_zoom_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_do_zoom',
            'type'           => 'select',
            'priority'       => 25,
            'choices'        => array(
               'none' => lay_t('none', 'customizer_project_thumbnail_mouseover_zoom_none'),
               '1' => lay_t('zoom in', 'customizer_project_thumbnail_mouseover_zoom_in'),
               'zoom-out' => lay_t('zoom out', 'customizer_project_thumbnail_mouseover_zoom_out'),
            )
        )
    )
);

$wp_customize->add_setting( 'fi_mo_do_blur',
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
        'fi_mo_do_blur',
        array(
            'label'          => lay_t('blur', 'customizer_project_thumbnail_mouseover_blur_label'),
            'section'        => $section,
            'settings'       => 'fi_mo_do_blur',
            'type'           => 'checkbox',
            'priority'       => 30,
        )
    )
);

$wp_customize->add_setting( 'fi_animate_blur',
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
        'fi_animate_blur',
        array(
            'label'          => lay_t('animate blur', 'customizer_project_thumbnail_mouseover_animate_blur_label'),
            'section'        => $section,
            'settings'       => 'fi_animate_blur',
            'type'           => 'checkbox',
            'priority'       => 35,
            'description'    => lay_t('(pretty slow on most browsers)', 'customizer_project_thumbnail_mouseover_animate_blur_description')
        )
    )
);

$wp_customize->add_setting( 'fi_mo_touchdevice_behaviour',
   array(
      'default' => 'mo_dont_show',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'fi_mo_touchdevice_behaviour',
        array(
            'label'          => lay_t('Behaviour on Mobile Devices', 'customizer_project_thumbnail_mouseover_mobile_behaviour_label'),
            'description'    => lay_t('You need to use a mobile device to test this.', 'customizer_project_thumbnail_mouseover_mobile_test_description'),
            'section'        => $section,
            'settings'       => 'fi_mo_touchdevice_behaviour',
            'type'           => 'radio',
            'priority'       => 40,
            'choices'        => array(
               'mo_dont_show' => lay_t('Do not show mouseover state', 'customizer_project_thumbnail_mouseover_mobile_dont_show'),
               'mo_on_tap_alt' => lay_t('When touching thumbnail, show mouseover state', 'customizer_project_thumbnail_mouseover_mobile_on_tap_alt'),
               'mo_always' => lay_t('Always show mouseover state', 'customizer_project_thumbnail_mouseover_mobile_always'),
               'mo_on_tap' => lay_t('Show mouseover state on touch or tap, tap again to enter project', 'customizer_project_thumbnail_mouseover_mobile_on_tap'),
            )
        )
    )
);

$val = get_option('misc_options_thumbnail_video', '');
if($val == "on" && !MiscOptions::$locked){
    $wp_customize->add_setting( 'fi_mo_video_behaviour',
    array(
       'default' => 'autoplay',
       'type' => 'theme_mod',
       'capability' => 'edit_theme_options',
       'transport' => 'refresh'
    )
 );
 $wp_customize->add_control(
     new WP_Customize_Control(
         $wp_customize,
         'fi_mo_video_behaviour',
         array(
             'label'          => lay_t('Video Thumbnail Behaviour', 'customizer_project_thumbnail_mouseover_video_behaviour_label'),
             'section'        => $section,
             'settings'       => 'fi_mo_video_behaviour',
             'type'           => 'radio',
             'priority'       => 45,
             'choices'        => array(
                'autoplay' => lay_t('Autoplay', 'customizer_project_thumbnail_mouseover_video_autoplay'),
                'play_on_mouseover' => lay_t('Play on mouseover', 'customizer_project_thumbnail_mouseover_video_play_on_mouseover'),
                'pause_on_mouseover' => lay_t('Pause on mouseover', 'customizer_project_thumbnail_mouseover_video_pause_on_mouseover'),
             )
         )
     )
 );
}


$val = get_option('misc_options_thumbnail_mouseover_image', '');
if($val == "on"){
    $wp_customize->add_setting( 'fi_mo_bg_as_fallback',
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
         'fi_mo_bg_as_fallback',
         array(
             'label'           => lay_t('Use Background Color as fallback behind Mouseover Image', 'customizer_project_thumbnail_mouseover_bg_as_fallback_label'),
             'section'         => $section,
             'settings'        => 'fi_mo_bg_as_fallback',
             'type'            => 'checkbox',
             'priority'        => 12,
             'active_callback' => function() { return get_theme_mod('fi_mo_show_color', false) == 1; },
         )
     )
 );

    $wp_customize->add_setting( 'thumb_mo_image_has_transition',
    array(
       'default' => false,
       'type' => 'theme_mod',
       'capability' => 'edit_theme_options',
       'transport' => 'refresh'
    )
 );
 $wp_customize->add_control(
     new WP_Customize_Control(
         $wp_customize,
         'thumb_mo_image_has_transition',
         array(
             'label'          => lay_t('Fade on mouseover for "Mouseover Image for Thumbnails"', 'customizer_project_thumbnail_mouseover_image_transition_label'),
             'description'    => lay_t('This is for when you have activated "Lay Options" → "Enable Mouseover Image for Thumbnails".', 'customizer_project_thumbnail_mouseover_image_transition_description'),
             'section'        => $section,
             'settings'       => 'thumb_mo_image_has_transition',
             'type'           => 'checkbox',
             'priority'       => 50,
         )
     )
 );
}
