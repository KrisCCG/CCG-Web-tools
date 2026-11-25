<?php

$section = 'cursor';

$wp_customize->add_section( $section,
   array(
      'title' => 'Cursor',
      'priority' => 100,
      'capability' => 'edit_theme_options',
   )
);

$wp_customize->add_setting( 'lay_cursor',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Upload_Control(
   $wp_customize,
   'lay_cursor',
   array(
      'label' => lay_t('Cursor Image', 'customizer_cursor_image_label'),
      'description' => lay_t('Max size: 128px * 128px', 'customizer_cursor_max_size_description'),
      'section' => $section,
      'settings' => 'lay_cursor',
      'priority'   => 0
   )
) );

$wp_customize->add_setting( 'lay_cursor_x',
    array(
        'default' => '0',
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);
$wp_customize->add_control( new WP_Customize_Control(
    $wp_customize,
    'lay_cursor_x',
    array(
       'label' => lay_t('Cursor X Click Point', 'customizer_cursor_x_label'),
       'section' => $section,
       'description' => lay_t('Should be 0 or half of image width', 'customizer_cursor_x_description'),
       'settings' => 'lay_cursor_x',
       'type' => 'number',
       'priority' => 11,
    )
) );

$wp_customize->add_setting( 'lay_cursor_y',
    array(
        'default' => '0',
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);
$wp_customize->add_control( new WP_Customize_Control(
    $wp_customize,
    'lay_cursor_y',
    array(
       'label' => lay_t('Cursor Y Click Point', 'customizer_cursor_y_label'),
       'section' => $section,
       'description' => lay_t('Should be 0 or half of image height', 'customizer_cursor_y_description'),
       'settings' => 'lay_cursor_y',
       'type' => 'number',
       'priority' => 12,
    )
) );

$wp_customize->add_setting( 'lay_cursor_keep_normal_cursor_for_links',
    array(
        'default' => 0,
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);
$wp_customize->add_setting( 'lay_cursor_use_dom_cursor',
    array(
        'default' => 0,
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'transport' => 'refresh',
    )
);
$wp_customize->add_control( new WP_Customize_Control(
    $wp_customize,
    'lay_cursor_use_dom_cursor',
    array(
       'label' => lay_t('Use DOM Element as Cursor', 'customizer_cursor_use_dom_cursor_label'),
       'description' => lay_t('Try this setting if your cursor image is not sharp. Make sure to use a .svg as your cursor image too. Enabling this, will automatically enable this for all cursors of the whole site and all addon custom cursors too, this is a global setting.', 'customizer_cursor_use_dom_cursor_description'),
       'section' => $section,
       'settings' => 'lay_cursor_use_dom_cursor',
       'type' => 'checkbox',
       'priority' => 13,
    )
) );
$wp_customize->add_control( new WP_Customize_Control(
    $wp_customize,
    'lay_cursor_keep_normal_cursor_for_links',
    array(
       'label' => lay_t('Keep normal cursor for links', 'customizer_cursor_keep_normal_cursor_for_links_label'),
       'section' => $section,
       'settings' => 'lay_cursor_keep_normal_cursor_for_links',
       'type' => 'checkbox',
       'priority' => 14,
    )
) );