<?php

$section = 'thumbnailgrid_filter';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Thumbnail Grid Category Filter', 'customizer_thumbnailgrid_category_filter_title'),
      'priority' => 10,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel',
      'description' => lay_t('<p>These are settings for the Category Filter of a Thumbnail Grid Element.</p><p>How to activate the Category Filter: Check "Show Category Filter" when adding a Thumbnail Grid via "+More" &rarr; "+Thumbnail Grid" in the Gridder.</p>', 'customizer_thumbnailgrid_category_filter_description')
   )
);

$wp_customize->add_setting( 'tgf_transition',
   array(
      'default' => 'fade_out_fade_in_2',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_transition',
        array(
            'label'          => lay_t('Transition', 'customizer_thumbnailgrid_category_filter_transition_label'),
            'section'        => $section,
            'settings'       => 'tgf_transition',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array(
               'fade_out_fade_in_2' => lay_t('Fade out fade in 2', 'customizer_thumbnailgrid_category_filter_transition_fade_out_fade_in_2'),
               'fade_out_fade_in' => lay_t('Fade out fade in', 'customizer_thumbnailgrid_category_filter_transition_fade_out_fade_in'),
               'none' => lay_t('None', 'customizer_thumbnailgrid_category_filter_transition_none'),
            )
        )
    )
);

$wp_customize->add_setting( 'tgf_spacebetween_2',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_spacebetween_2',
        array(
            'label'          => lay_t('Space between (px)', 'customizer_thumbnailgrid_category_filter_space_between_label'),
            'section'        => $section,
            'settings'       => 'tgf_spacebetween_2',
            'type'           => 'number',
            'priority'       => 2,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'tgf_spacebelow',
   array(
      'default' => '20',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_spacebelow',
        array(
            'label'          => lay_t('Space below (px)', 'customizer_thumbnailgrid_category_filter_space_below_label'),
            'section'        => $section,
            'settings'       => 'tgf_spacebelow',
            'type'           => 'number',
            'priority'       => 5,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'tgf_textformat',
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
        'tgf_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_thumbnailgrid_category_filter_text_format_label'),
            'section'        => $section,
            'settings'       => 'tgf_textformat',
            'type'           => 'select',
            'priority'       => 10,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);
$wp_customize->selective_refresh->add_partial( 'tgf_textformat', array(
    'selector' => '.lay-thumbnailgrid-filter',
    'container_inclusive' => false,
    'render_callback' => function() {
        echo LayThumnbailgridFunctions::lay_get_thumbnailgrid_category_filter_markup();
    },
) );

$wp_customize->add_setting( 'tgf_fontfamily',
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
        'tgf_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_thumbnailgrid_category_filter_font_family_label'),
            'section'        => $section,
            'settings'       => 'tgf_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35
        )
    )
);

$wp_customize->add_setting( 'tgf_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'tgf_fontsize',
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
        'tgf_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_thumbnailgrid_category_filter_font_size_label'),
            'section'        => $section,
            'settings'       => 'tgf_fontsize',
            'unit_setting'   => 'tgf_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38
        )
    )
);

$wp_customize->add_setting( 'tgf_fontweight',
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
        'tgf_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_thumbnailgrid_category_filter_font_weight_label'),
            'section'        => $section,
            'settings'       => 'tgf_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45
        )
    )
);

$wp_customize->add_setting( 'tgf_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tgf_color',
   array(
     'label' => lay_t('Text Color', 'customizer_thumbnailgrid_category_filter_text_color_label'),
      'section' => $section,
      'settings' => 'tgf_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'tgf_letterspacing',
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
        'tgf_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_thumbnailgrid_category_filter_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'tgf_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'tgf_align',
   array(
      'default' => 'left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_align',
        array(
            'label'          => lay_t('Alignment', 'customizer_thumbnailgrid_category_filter_alignment_label'),
            'section'        => $section,
            'settings'       => 'tgf_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_thumbnailgrid_category_filter_alignment_left'),
               'center' => lay_t('center', 'customizer_thumbnailgrid_category_filter_alignment_center'),
               'right' => lay_t('right', 'customizer_thumbnailgrid_category_filter_alignment_right'),
            ),
        )
    )
);

$wp_customize->add_setting( 'tgf_opacity',
   array(
      'default' => 50,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'tgf_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_thumbnailgrid_category_filter_opacity_label'),
      'section' => $section,
      'settings' => 'tgf_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 60,
   )
) );

$wp_customize->add_setting( 'tgf_mouseover_opacity',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'tgf_mouseover_opacity',
   array(
     'label' => lay_t('Mouseover Opacity (%)', 'customizer_thumbnailgrid_category_filter_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'tgf_mouseover_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 65,
   )
) );


$wp_customize->add_setting( 'tgf_active_color',
   array(
      'default' => get_theme_mod('tgf_active_color', Customizer::$defaults['color']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tgf_active_color',
   array(
     'label' => lay_t('Active Text Color', 'customizer_thumbnailgrid_category_filter_active_text_color_label'),
      'section' => $section,
      'settings' => 'tgf_active_color',
      'priority'   => 70
   )
) );

$wp_customize->add_setting( 'tgf_active_opacity',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'tgf_active_opacity',
   array(
     'label' => lay_t('Active Opacity (%)', 'customizer_thumbnailgrid_category_filter_active_opacity_label'),
      'section' => $section,
      'settings' => 'tgf_active_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 70,
   )
) );

$wp_customize->add_setting( 'tgf_active_strokewidth',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'tgf_active_strokewidth',
   array(
     'label' => lay_t('Active Underline Strokewidth (px)', 'customizer_thumbnailgrid_category_filter_active_underline_strokewidth_label'),
      'description' => lay_t('Underline Offset settings are the ones used in "Customize" → "Links in Texts"', 'customizer_filter_underline_offset_description'),
      'section' => $section,
      'settings' => 'tgf_active_strokewidth',
      'type'           => 'number',
      'priority' => 75,
   )
) );

$wp_customize->add_setting( 'tgf_oneline',
   array(
      'default' => get_theme_mod('tgf_oneline', 1),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_oneline',
        array(
            'label'          => lay_t('For Mobile put tags in a scrollable line', 'customizer_thumbnailgrid_category_filter_mobile_scrollable_label'),
            'section'        => $section,
            'settings'       => 'tgf_oneline',
            'type'           => 'checkbox',
            'priority'       => 80,
        )
    )
);

$wp_customize->add_setting( 'tgf_spacebetween_mobile',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgf_spacebetween_mobile',
        array(
            'label'          => lay_t('Mobile Space between (px)', 'customizer_thumbnailgrid_category_filter_mobile_space_between_label'),
            'section'        => $section,
            'settings'       => 'tgf_spacebetween_mobile',
            'type'           => 'number',
            'priority'       => 90,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);