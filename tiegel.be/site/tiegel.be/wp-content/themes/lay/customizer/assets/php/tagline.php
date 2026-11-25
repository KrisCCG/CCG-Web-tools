<?php

function is_tagline_hidden(){
    return get_theme_mod('tagline_hide') == 1 ? false : true;
}

$section = 'tagline_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Site Tagline', 'customizer_tagline_title'),
      'priority' => 20,
      'capability' => 'edit_theme_options',
      'panel' => 'sitetitle_panel'
   )
);

$wp_customize->add_setting( 'tagline_hide',
   array(
      'default' => "1",
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tagline_hide',
        array(
            'label'          => lay_t('hide', 'customizer_tagline_hide_label'),
            'section'        => $section,
            'settings'       => 'tagline_hide',
            'type'           => 'checkbox',
            'priority'       => 4,
        )
    )
);

$wp_customize->add_setting( 'blogdescription', array(
    'default'    => get_option( 'blogdescription' ),
    'type'       => 'option',
    'capability' => 'manage_options',
    'transport' => 'refresh',
  )
);

$wp_customize->add_control( 'blogdescription', array(
    'label'      => lay_t('Tagline', 'customizer_tagline_label'),
    'section'    => $section,
    'priority'   => 10,
    'type'       => 'textarea',
    'active_callback' => 'is_tagline_hidden'
  )
);

// space top
$wp_customize->add_setting( 'tagline_spacetop',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tagline_spacetop',
        array(
            'label'          => lay_t('Space Top (px)', 'customizer_tagline_space_top_label'),
            'section'        => $section,
            'settings'       => 'tagline_spacetop',
            'priority'       => 23,
            'input_attrs'    => array('step' => '0.1'),
            'type'           => 'number',
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_textformat',
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
        'tagline_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_tagline_text_format_label'),
            'section'        => $section,
            'settings'       => 'tagline_textformat',
            'type'           => 'select',
            'priority'       => 31,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_fontfamily',
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
        'tagline_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_tagline_font_family_label'),
            'section'        => $section,
            'settings'       => 'tagline_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35,
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'tagline_fontsize',
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
        'tagline_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_tagline_font_size_label'),
            'section'        => $section,
            'settings'       => 'tagline_fontsize',
            'unit_setting'   => 'tagline_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38,
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_fontweight',
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
        'tagline_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_tagline_font_weight_label'),
            'section'        => $section,
            'settings'       => 'tagline_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45,
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tagline_color',
   array(
     'label' => lay_t('Text Color', 'customizer_tagline_text_color_label'),
      'section' => $section,
      'settings' => 'tagline_color',
      'priority'   => 50,
      'active_callback' => 'is_tagline_hidden'
   )
) );

$wp_customize->add_setting( 'tagline_letterspacing',
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
        'tagline_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_tagline_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'tagline_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55,
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_align',
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
        'tagline_align',
        array(
            'label'          => lay_t('Text Align', 'customizer_tagline_text_align_label'),
            'section'        => $section,
            'settings'       => 'tagline_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_tagline_text_align_left'),
               'center' => lay_t('center', 'customizer_tagline_text_align_center'),
               'right' => lay_t('right', 'customizer_tagline_text_align_right'),
            ),
            'active_callback' => 'is_tagline_hidden'
        )
    )
);

$wp_customize->add_setting( 'tagline_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'tagline_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_tagline_opacity_label'),
      'section' => $section,
      'settings' => 'tagline_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 60,
      'active_callback' => 'is_tagline_hidden'
   )
) );
