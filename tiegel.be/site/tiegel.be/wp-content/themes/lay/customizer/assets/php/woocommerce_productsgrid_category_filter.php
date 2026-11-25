<?php

$section = 'productsgrid_filter';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Product Thumbnail Grid Category Filter', 'customizer_productsgrid_category_filter_title'),
      'priority' => 22,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description' => lay_t('<p>These are settings for the Category Filter of a Product Thumbnail Grid Element.</p><p>How to activate the Category Filter: Check "Show Category Filter" when adding a Product Thumbnail Grid via "+WooCommerce" &rarr; "+Product Thumbnail Grid" in the Gridder.</p>', 'customizer_productsgrid_category_filter_description')
   )
);

$wp_customize->add_setting( 'pgf_transition',
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
        'pgf_transition',
        array(
            'label'          => lay_t('Transition', 'customizer_productsgrid_category_filter_transition_label'),
            'section'        => $section,
            'settings'       => 'pgf_transition',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array(
               'fade_out_fade_in_2' => lay_t('Fade out fade in 2', 'customizer_productsgrid_category_filter_transition_fade_out_fade_in_2'),
               'fade_out_fade_in' => lay_t('Fade out fade in', 'customizer_productsgrid_category_filter_transition_fade_out_fade_in'),
               'none' => lay_t('None', 'customizer_productsgrid_category_filter_transition_none'),
            )
        )
    )
);

$wp_customize->add_setting( 'pgf_spacebetween_2',
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
        'pgf_spacebetween_2',
        array(
            'label'          => lay_t('Space between (px)', 'customizer_productsgrid_category_filter_space_between_label'),
            'section'        => $section,
            'settings'       => 'pgf_spacebetween_2',
            'type'           => 'number',
            'priority'       => 2,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'pgf_spacebelow',
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
        'pgf_spacebelow',
        array(
            'label'          => lay_t('Space below (px)', 'customizer_productsgrid_category_filter_space_below_label'),
            'section'        => $section,
            'settings'       => 'pgf_spacebelow',
            'type'           => 'number',
            'priority'       => 5,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'pgf_textformat',
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
        'pgf_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_productsgrid_category_filter_text_format_label'),
            'section'        => $section,
            'settings'       => 'pgf_textformat',
            'type'           => 'select',
            'priority'       => 10,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);
$wp_customize->selective_refresh->add_partial( 'pgf_textformat', array(
    'selector' => '.lay-productsgrid-filter',
    'container_inclusive' => false,
    'render_callback' => function() {
        echo LayProductsgridFunctions::lay_get_productsgrid_category_filter_markup();
    },
) );

$wp_customize->add_setting( 'pgf_fontfamily',
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
        'pgf_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_productsgrid_category_filter_font_family_label'),
            'section'        => $section,
            'settings'       => 'pgf_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35
        )
    )
);

$wp_customize->add_setting( 'pgf_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pgf_fontsize',
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
        'pgf_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_productsgrid_category_filter_font_size_label'),
            'section'        => $section,
            'settings'       => 'pgf_fontsize',
            'unit_setting'   => 'pgf_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38
        )
    )
);

$wp_customize->add_setting( 'pgf_fontweight',
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
        'pgf_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_productsgrid_category_filter_font_weight_label'),
            'section'        => $section,
            'settings'       => 'pgf_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45
        )
    )
);

$wp_customize->add_setting( 'pgf_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pgf_color',
   array(
     'label' => lay_t('Text Color', 'customizer_productsgrid_category_filter_text_color_label'),
      'section' => $section,
      'settings' => 'pgf_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'pgf_letterspacing',
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
        'pgf_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_productsgrid_category_filter_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'pgf_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'pgf_align',
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
        'pgf_align',
        array(
            'label'          => lay_t('Alignment', 'customizer_productsgrid_category_filter_alignment_label'),
            'section'        => $section,
            'settings'       => 'pgf_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_productsgrid_category_filter_alignment_left'),
               'center' => lay_t('center', 'customizer_productsgrid_category_filter_alignment_center'),
               'right' => lay_t('right', 'customizer_productsgrid_category_filter_alignment_right'),
            ),
        )
    )
);

$wp_customize->add_setting( 'pgf_opacity',
   array(
      'default' => 50,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pgf_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_productsgrid_category_filter_opacity_label'),
      'section' => $section,
      'settings' => 'pgf_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 60,
   )
) );

$wp_customize->add_setting( 'pgf_mouseover_opacity',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pgf_mouseover_opacity',
   array(
     'label' => lay_t('Mouseover Opacity (%)', 'customizer_productsgrid_category_filter_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'pgf_mouseover_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 65,
   )
) );


$wp_customize->add_setting( 'pgf_active_color',
   array(
      'default' => get_theme_mod('pgf_active_color', Customizer::$defaults['color']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pgf_active_color',
   array(
     'label' => lay_t('Active Text Color', 'customizer_productsgrid_category_filter_active_text_color_label'),
      'section' => $section,
      'settings' => 'pgf_active_color',
      'priority'   => 70
   )
) );

$wp_customize->add_setting( 'pgf_active_opacity',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pgf_active_opacity',
   array(
     'label' => lay_t('Active Opacity (%)', 'customizer_productsgrid_category_filter_active_opacity_label'),
      'section' => $section,
      'settings' => 'pgf_active_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 70,
   )
) );

$wp_customize->add_setting( 'pgf_active_strokewidth',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'pgf_active_strokewidth',
   array(
     'label' => lay_t('Active Underline Strokewidth (px)', 'customizer_productsgrid_category_filter_active_underline_strokewidth_label'),
      'description' => lay_t('Underline Offset settings are the ones used in "Customize" → "Links in Texts"', 'customizer_filter_underline_offset_description'),
      'section' => $section,
      'settings' => 'pgf_active_strokewidth',
      'type'           => 'number',
      'priority' => 75,
   )
) );

$wp_customize->add_setting( 'pgf_oneline',
   array(
      'default' => get_theme_mod('pgf_oneline', 1),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pgf_oneline',
        array(
            'label'          => lay_t('For Mobile put tags in a scrollable line', 'customizer_productsgrid_category_filter_mobile_scrollable_label'),
            'section'        => $section,
            'settings'       => 'pgf_oneline',
            'type'           => 'checkbox',
            'priority'       => 80,
        )
    )
);

$wp_customize->add_setting( 'pgf_spacebetween_mobile',
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
        'pgf_spacebetween_mobile',
        array(
            'label'          => lay_t('Mobile Space between (px)', 'customizer_productsgrid_category_filter_mobile_space_between_label'),
            'section'        => $section,
            'settings'       => 'pgf_spacebetween_mobile',
            'type'           => 'number',
            'priority'       => 90,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);