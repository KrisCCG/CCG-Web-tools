<?php

$section = 'projectdescr_options';

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Project Description', 'customizer_project_description_title'),
      'priority' => 30,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel'
   ) 
);

$wp_customize->add_setting( 'pd_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_position',
        array(
            'label'          => lay_t('Position', 'customizer_project_description_position_label'),
            'description'    => lay_t('The "on image" Position depends on the "on image" position of your Project Title. In "Customize" &rarr; "Project Thumbnails" &rarr; "Project Title" the position can be for example "on image bottom left" or "on image top left". This way your Project Description also be "on image bottom left".', 'customizer_project_description_position_description'),
            'section'        => $section,
            'settings'       => 'pd_position',
            'type'           => 'select',
            'choices'        => array(
               'above-image' => lay_t('above image', 'customizer_project_description_position_above_image'),
               'on-image' => lay_t('on image, grouped with Project Title Position', 'customizer_project_description_position_on_image'),
               'below-image' => lay_t('below image', 'customizer_project_description_position_below_image'),
            ),
            'priority' => 10
        )
    )
);

$wp_customize->add_setting( 'pd_position_related_to_title',
   array(
      'default' => 'below-title',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_position_related_to_title',
        array(
            'label'          => lay_t('Position related to Title', 'customizer_project_description_position_related_to_title_label'),
            'description'    => lay_t('Only changes the order when description and title are in the same place: both above image, both on image, or both below image. If they are in different places (e.g. title below, description above), this setting has no effect.', 'customizer_project_description_position_related_to_title_description'),
            'section'        => $section,
            'settings'       => 'pd_position_related_to_title',
            'type'           => 'select',
            'choices'        => array(
               'above-title' => lay_t('above project title', 'customizer_project_description_position_above_title'),
               'below-title' => lay_t('below project title', 'customizer_project_description_position_below_title'),
            ),
            'priority' => 15
        )
    )
);

$wp_customize->add_setting( 'pd_visibility',
   array(
      'default' => 'always-show',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_visibility',
        array(
            'label'          => lay_t('Visibility', 'customizer_project_description_visibility_label'),
            'section'        => $section,
            'settings'       => 'pd_visibility',
            'type'           => 'select',
            'choices'        => array(
               'always-show' => lay_t('always show', 'customizer_project_description_visibility_always_show'),
               'show-on-mouseover' => lay_t('show on mouseover', 'customizer_project_description_visibility_show_on_mouseover'),
            ),
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'pd_animate_visibility',
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
        'pd_animate_visibility',
        array(
            'label'          => lay_t('animate Visibility', 'customizer_project_description_animate_visibility_label'),
            'section'        => $section,
            'settings'       => 'pd_animate_visibility',
            'type'           => 'checkbox',
            'priority'       => 5,
        )
    )
);

$wp_customize->add_setting( 'pd_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pd_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_project_description_opacity_label'),
      'section' => $section,
      'settings' => 'pd_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 100,
   )
) );

$wp_customize->add_setting( 'pd_spacetop',
   array(
      'default' => '0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_spacetop',
        array(
            'label'          => lay_t('Space Top (px)', 'customizer_project_description_space_top_label'),
            'section'        => $section,
            'settings'       => 'pd_spacetop',
            'type'           => 'number',
            'priority' => 110,
        )
    )
);

$wp_customize->add_setting( 'pd_spacebottom',
   array(
      'default' => '0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_spacebottom',
        array(
            'label'          => lay_t('Space Bottom (px)', 'customizer_project_description_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'pd_spacebottom',
            'type'           => 'number',
            'priority' => 120,
        )
    )
);

// Force textformat
$wp_customize->add_setting( 'pd_force_textformat',
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
        'pd_force_textformat',
        array(
            'label'          => lay_t('Force Text Format', 'customizer_project_description_force_textformat_label'),
            'section'        => $section,
            'settings'       => 'pd_force_textformat',
            'type'           => 'checkbox',
            'priority'       => 130,
        )
    )
);

$wp_customize->add_setting( 'pd_force_textformat_select',
   array(
      'default' => 'Default',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pd_force_textformat_select',
        array(
            'label'          => lay_t('Text Format', 'customizer_project_description_force_textformat_select_label'),
            'section'        => $section,
            'settings'       => 'pd_force_textformat_select',
            'type'           => 'select',
            'choices'        => Customizer::$textformatsSelect,
            'priority'       => 135,
        )
    )
);

// Force color
$wp_customize->add_setting( 'pd_force_color',
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
        'pd_force_color',
        array(
            'label'          => lay_t('Force Color', 'customizer_project_description_force_color_label'),
            'section'        => $section,
            'settings'       => 'pd_force_color',
            'type'           => 'checkbox',
            'priority'       => 140,
        )
    )
);

$wp_customize->add_setting( 'pd_force_color_value',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pd_force_color_value',
   array(
      'label'   => lay_t('Color', 'customizer_project_description_force_color_value_label'),
      'section' => $section,
      'settings' => 'pd_force_color_value',
      'priority' => 145,
   )
) );