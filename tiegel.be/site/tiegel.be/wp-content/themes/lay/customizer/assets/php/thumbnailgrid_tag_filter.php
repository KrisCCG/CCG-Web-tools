<?php

$section = 'thumbnailgrid_tag_filter';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Thumbnail Grid Tag Filter', 'customizer_thumbnailgrid_tag_filter_title'),
      'priority' => 11,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel',
      'description' => lay_t('<p>These are settings for the Tag Filter of a Thumbnail Grid Element.</p><p>How to activate the Tag Filter: Check "Show Tag Filter" when adding a Thumbnail Grid via "+More" &rarr; "+Thumbnail Grid" in the Gridder.</p>', 'customizer_thumbnailgrid_tag_filter_description')
   )
);

$wp_customize->add_setting( 'tgtf_spacebetween',
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
        'tgtf_spacebetween',
        array(
            'label'          => lay_t('Space between Tags (px)', 'customizer_thumbnailgrid_tag_filter_space_between_label'),
            'section'        => $section,
            'settings'       => 'tgtf_spacebetween',
            'type'           => 'number',
            'priority'       => 2,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);

$wp_customize->add_setting( 'tgtf_spacebelow',
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
        'tgtf_spacebelow',
        array(
            'label'          => lay_t('Space below (px)', 'customizer_thumbnailgrid_tag_filter_space_below_label'),
            'section'        => $section,
            'settings'       => 'tgtf_spacebelow',
            'type'           => 'number',
            'priority'       => 5,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'tgtf_textformat',
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
        'tgtf_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_thumbnailgrid_tag_filter_text_format_label'),
            'section'        => $section,
            'settings'       => 'tgtf_textformat',
            'type'           => 'select',
            'priority'       => 10,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);
$wp_customize->selective_refresh->add_partial( 'tgtf_textformat', array(
    'selector' => '.lay-thumbnailgrid-tagfilter',
    'container_inclusive' => false,
    'render_callback' => function() {
        echo LayThumnbailgridFunctions::lay_get_thumbnailgrid_tagfilter_markup();
    },
) );

$wp_customize->add_setting( 'tgtf_fontfamily',
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
        'tgtf_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_thumbnailgrid_tag_filter_font_family_label'),
            'section'        => $section,
            'settings'       => 'tgtf_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35
        )
    )
);

$wp_customize->add_setting( 'tgtf_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'tgtf_fontsize',
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
        'tgtf_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_thumbnailgrid_tag_filter_font_size_label'),
            'section'        => $section,
            'settings'       => 'tgtf_fontsize',
            'unit_setting'   => 'tgtf_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38
        )
    )
);

$wp_customize->add_setting( 'tgtf_fontweight',
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
        'tgtf_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_thumbnailgrid_tag_filter_font_weight_label'),
            'section'        => $section,
            'settings'       => 'tgtf_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45
        )
    )
);

$wp_customize->add_setting( 'tgtf_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tgtf_color',
   array(
     'label' => lay_t('Text Color', 'customizer_thumbnailgrid_tag_filter_text_color_label'),
      'section' => $section,
      'settings' => 'tgtf_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'tgtf_letterspacing',
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
        'tgtf_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_thumbnailgrid_tag_filter_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'tgtf_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'tgtf_align',
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
        'tgtf_align',
        array(
            'label'          => lay_t('Alignment', 'customizer_thumbnailgrid_tag_filter_alignment_label'),
            'section'        => $section,
            'settings'       => 'tgtf_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_thumbnailgrid_tag_filter_alignment_left'),
               'center' => lay_t('center', 'customizer_thumbnailgrid_tag_filter_alignment_center'),
               'right' => lay_t('right', 'customizer_thumbnailgrid_tag_filter_alignment_right'),
            ),
        )
    )
);

$wp_customize->add_setting( 'tgtf_bubble_color',
   array(
      'default' => '#eeeeee',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tgtf_bubble_color',
   array(
     'label' => lay_t('Bubble Color', 'customizer_thumbnailgrid_tag_filter_bubble_color_label'),
      'section' => $section,
      'settings' => 'tgtf_bubble_color',
      'priority'   => 60
   )
) );

$wp_customize->add_setting( 'tgtf_bubble_active_color',
   array(
      'default' => '#d0d0d0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'tgtf_bubble_active_color',
   array(
     'label' => lay_t('Active/Mouseover Bubble Color', 'customizer_thumbnailgrid_tag_filter_bubble_active_color_label'),
      'section' => $section,
      'settings' => 'tgtf_bubble_active_color',
      'priority'   => 65
   )
) );

$wp_customize->add_setting( 'tgtf_bubble_border_radius',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgtf_bubble_border_radius',
        array(
            'label'          => lay_t('Bubble Border Radius', 'customizer_thumbnailgrid_tag_filter_bubble_border_radius_label'),
            'section'        => $section,
            'settings'       => 'tgtf_bubble_border_radius',
            'type'           => 'number',
            'input_attrs'    => array('min' => '0', 'step' => '1'),
            'priority'       => 70
        )
    )
);

$wp_customize->add_setting( 'tgtf_oneline',
   array(
      'default' => get_theme_mod('tgtf_oneline', 1),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'tgtf_oneline',
        array(
            'label'          => lay_t('For Mobile put tags in a scrollable line', 'customizer_thumbnailgrid_tag_filter_mobile_scrollable_label'),
            'section'        => $section,
            'settings'       => 'tgtf_oneline',
            'type'           => 'checkbox',
            'priority'       => 80,
        )
    )
);

$wp_customize->add_setting( 'tgtf_spacebetween_mobile',
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
        'tgtf_spacebetween_mobile',
        array(
            'label'          => lay_t('Mobile Space between Tags (px)', 'customizer_thumbnailgrid_tag_filter_mobile_space_between_label'),
            'section'        => $section,
            'settings'       => 'tgtf_spacebetween_mobile',
            'type'           => 'number',
            'priority'       => 90,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);