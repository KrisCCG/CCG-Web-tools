<?php

$section = 'projecttags_options';

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Project Tags', 'customizer_project_tags_title'),
      'priority' => 29,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel'
   ) 
);

$wp_customize->add_setting( 'ptags_visibility',
   array(
      'default' => 'always-show',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_visibility',
        array(
            'label'          => lay_t('Visibility', 'customizer_project_tags_visibility_label'),
            'section'        => $section,
            'settings'       => 'ptags_visibility',
            'type'           => 'select',
            'choices'        => array(
               'always-show' => lay_t('always show', 'customizer_project_tags_visibility_always_show'),
               'hide' => lay_t('hide', 'customizer_project_tags_visibility_hide'),
            ),
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'ptags_position',
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
        'ptags_position',
        array(
            'label'          => lay_t('Position', 'customizer_project_tags_position_label'),
            'section'        => $section,
            'settings'       => 'ptags_position',
            'type'           => 'select',
            'priority'       => 1,
            'choices'        => array(
               'below-title' => lay_t('below project title', 'customizer_project_tags_position_below_title'),
               'above-title' => lay_t('above project title', 'customizer_project_tags_position_above_title'),
            ),
        )
    )
);

$wp_customize->add_setting( 'ptags_spacetop',
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
        'ptags_spacetop',
        array(
            'label'          => lay_t('Space Top (px)', 'customizer_project_tags_space_top_label'),
            'section'        => $section,
            'settings'       => 'ptags_spacetop',
            'type'           => 'number',
            'priority' => 10,
        )
    )
);

$wp_customize->add_setting( 'ptags_spacebottom',
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
        'ptags_spacebottom',
        array(
            'label'          => lay_t('Space Bottom (px)', 'customizer_project_tags_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'ptags_spacebottom',
            'type'           => 'number',
            'priority' => 20,
        )
    )
);



$wp_customize->add_setting( 'ptags_textformat',
   array(
      'default' => 'Default',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_project_tags_text_format_label'),
            'section'        => $section,
            'settings'       => 'ptags_textformat',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'ptags_fontfamily',
   array(
      'default' => Customizer::$defaults['fontfamily'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_project_tags_font_family_label'),
            'section'        => $section,
            'settings'       => 'ptags_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority' => 40
        )
    )
);

$wp_customize->add_setting( 'ptags_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'ptags_fontsize',
   array(
      'default' => Customizer::$defaults['fontsize'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'ptags_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_project_tags_font_size_label'),
            'section'        => $section,
            'settings'       => 'ptags_fontsize',
            'unit_setting'   => 'ptags_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority' => 50
        )
    )
);

$wp_customize->add_setting( 'ptags_fontweight',
   array(
      'default' => Customizer::$defaults['fontweight'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_project_tags_font_weight_label'),
            'section'        => $section,
            'settings'       => 'ptags_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 70
        )
    )
);

$wp_customize->add_setting( 'ptags_lineheight',
   array(
      'default' => Customizer::$defaults['lineheight'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_lineheight',
        array(
            'label'          => lay_t('Line Height', 'customizer_project_tags_line_height_label'),
            'section'        => $section,
            'settings'       => 'ptags_lineheight',
            'type'           => 'number',
            'input_attrs'    => array('step' => '0.01'),
            'priority'       => 75
        )
    )
);

$wp_customize->add_setting( 'ptags_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'ptags_color',
   array(
     'label' => lay_t('Text Color', 'customizer_project_tags_text_color_label'),
      'section' => $section,
      'settings' => 'ptags_color',
      'priority' => 80,
   )
) );

$wp_customize->add_setting( 'ptags_letterspacing',
   array(
      'default' => Customizer::$defaults['letterspacing'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_project_tags_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'ptags_letterspacing',
            'type'           => 'number',
            'input_attrs'    => array('step' => '0.01'),
            'priority'       => 90
        )
    )
);

$wp_customize->add_setting( 'ptags_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'ptags_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_project_tags_opacity_label'),
      'section' => $section,
      'settings' => 'ptags_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 100,
   )
) );

$wp_customize->add_setting( 'ptags_align',
   array(
      'default' => 'left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'ptags_align',
        array(
            'label'          => lay_t('Text Align', 'customizer_project_tags_text_align_label'),
            'section'        => $section,
            'settings'       => 'ptags_align',
            'type'           => 'select',
            'priority' => 110,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_project_tags_text_align_left'),
               'center' => lay_t('center', 'customizer_project_tags_text_align_center'),
               'right' => lay_t('right', 'customizer_project_tags_text_align_right'),
            )
        )
    )
);