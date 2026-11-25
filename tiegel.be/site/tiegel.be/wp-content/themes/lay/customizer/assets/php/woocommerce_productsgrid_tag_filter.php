<?php

$section = 'productsgrid_tag_filter';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Product Thumbnail Grid Tag Filter', 'customizer_productsgrid_tag_filter_title'),
      'priority' => 22,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description' => lay_t('<p>These are settings for the Tag Filter of a Product Thumbnail Grid Element.</p><p>How to activate the Tag Filter: Check "Show Tag Filter" when adding a Product Thumbnail Grid via "+WooCommerce" &rarr; "+Product Thumbnail Grid" in the Gridder.</p>', 'customizer_productsgrid_tag_filter_description')
   )
);

$wp_customize->add_setting( 'pgtf_spacebetween',
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
        'pgtf_spacebetween',
        array(
            'label'          => lay_t('Space between Tags (px)', 'customizer_productsgrid_tag_filter_space_between_label'),
            'section'        => $section,
            'settings'       => 'pgtf_spacebetween',
            'type'           => 'number',
            'priority'       => 2,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);

$wp_customize->add_setting( 'pgtf_spacebelow',
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
        'pgtf_spacebelow',
        array(
            'label'          => lay_t('Space below (px)', 'customizer_productsgrid_tag_filter_space_below_label'),
            'section'        => $section,
            'settings'       => 'pgtf_spacebelow',
            'type'           => 'number',
            'priority'       => 5,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);


$wp_customize->add_setting( 'pgtf_textformat',
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
        'pgtf_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_productsgrid_tag_filter_text_format_label'),
            'section'        => $section,
            'settings'       => 'pgtf_textformat',
            'type'           => 'select',
            'priority'       => 10,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);
$wp_customize->selective_refresh->add_partial( 'pgtf_textformat', array(
    'selector' => '.lay-productsgrid-tagfilter',
    'container_inclusive' => true,
    'render_callback' => function() {
        echo LayProductsgridFunctions::lay_get_productsgrid_tagfilter_markup();
    },
) );

$wp_customize->add_setting( 'pgtf_fontfamily',
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
        'pgtf_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_productsgrid_tag_filter_font_family_label'),
            'section'        => $section,
            'settings'       => 'pgtf_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35
        )
    )
);

$wp_customize->add_setting( 'pgtf_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pgtf_fontsize',
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
        'pgtf_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_productsgrid_tag_filter_font_size_label'),
            'section'        => $section,
            'settings'       => 'pgtf_fontsize',
            'unit_setting'   => 'pgtf_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38
        )
    )
);

$wp_customize->add_setting( 'pgtf_fontweight',
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
        'pgtf_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_productsgrid_tag_filter_font_weight_label'),
            'section'        => $section,
            'settings'       => 'pgtf_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45
        )
    )
);

$wp_customize->add_setting( 'pgtf_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pgtf_color',
   array(
     'label' => lay_t('Text Color', 'customizer_productsgrid_tag_filter_text_color_label'),
      'section' => $section,
      'settings' => 'pgtf_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'pgtf_letterspacing',
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
        'pgtf_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_productsgrid_tag_filter_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'pgtf_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'pgtf_align',
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
        'pgtf_align',
        array(
            'label'          => lay_t('Alignment', 'customizer_productsgrid_tag_filter_alignment_label'),
            'section'        => $section,
            'settings'       => 'pgtf_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_productsgrid_tag_filter_alignment_left'),
               'center' => lay_t('center', 'customizer_productsgrid_tag_filter_alignment_center'),
               'right' => lay_t('right', 'customizer_productsgrid_tag_filter_alignment_right'),
            ),
        )
    )
);

$wp_customize->add_setting( 'pgtf_bubble_color',
   array(
      'default' => '#eeeeee',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pgtf_bubble_color',
   array(
     'label' => lay_t('Bubble Color', 'customizer_productsgrid_tag_filter_bubble_color_label'),
      'section' => $section,
      'settings' => 'pgtf_bubble_color',
      'priority'   => 60
   )
) );

$wp_customize->add_setting( 'pgtf_bubble_active_color',
   array(
      'default' => '#d0d0d0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pgtf_bubble_active_color',
   array(
     'label' => lay_t('Active/Mouseover Bubble Color', 'customizer_productsgrid_tag_filter_bubble_active_color_label'),
      'section' => $section,
      'settings' => 'pgtf_bubble_active_color',
      'priority'   => 65
   )
) );

$wp_customize->add_setting( 'pgtf_bubble_border_radius',
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
        'pgtf_bubble_border_radius',
        array(
            'label'          => lay_t('Bubble Border Radius', 'customizer_productsgrid_tag_filter_bubble_border_radius_label'),
            'section'        => $section,
            'settings'       => 'pgtf_bubble_border_radius',
            'type'           => 'number',
            'input_attrs'    => array('min' => '0', 'step' => '1'),
            'priority'       => 70
        )
    )
);

$wp_customize->add_setting( 'pgtf_oneline',
   array(
      'default' => get_theme_mod('pgtf_oneline', 1),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pgtf_oneline',
        array(
            'label'          => lay_t('For Mobile put tags in a scrollable line', 'customizer_productsgrid_tag_filter_mobile_scrollable_label'),
            'section'        => $section,
            'settings'       => 'pgtf_oneline',
            'type'           => 'checkbox',
            'priority'       => 80,
        )
    )
);

$wp_customize->add_setting( 'pgtf_spacebetween_mobile',
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
        'pgtf_spacebetween_mobile',
        array(
            'label'          => lay_t('Mobile Space between Tags (px)', 'customizer_productsgrid_tag_filter_mobile_space_between_label'),
            'section'        => $section,
            'settings'       => 'pgtf_spacebetween_mobile',
            'type'           => 'number',
            'priority'       => 90,
            'input_attrs'    => array('step' => '1', 'min' => 0),
        )
    )
);