<?php

$section = 'links_in_texts_options';
$panel = 'linksintexts_panel';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Links in Texts', 'customizer_links_in_texts_title'),
      'priority' => 10,
      'capability' => 'edit_theme_options',
      'panel' => $panel
   )
);

$wp_customize->add_setting( 'link_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'link_color',
   array(
      'label' => lay_t('Text Color', 'customizer_links_in_texts_text_color_label'),
      'section' => $section,
      'settings' => 'link_color',
      'priority'   => 10
   )
) );

$wp_customize->add_setting( 'link_underline_strokewidth',
   array(
      'default' => 1,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'link_underline_strokewidth',
   array(
      'label' => lay_t('Underline Strokewidth (px)', 'customizer_links_in_texts_underline_strokewidth_label'),
      'section' => $section,
      'settings' => 'link_underline_strokewidth',
      'type'           => 'number',
      'priority' => 20,
   )
) );

$wp_customize->add_setting( 'link_underline_offset_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'link_underline_offset',
   array(
      'default' => 3,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'link_underline_offset',
        array(
            'label'          => lay_t('Underline Offset', 'customizer_links_in_texts_underline_offset_label'),
            'description'    => lay_t('"Em" is based on font size.', 'customizer_links_in_texts_em_description'),
            'section'        => $section,
            'settings'       => 'link_underline_offset',
            'unit_setting'   => 'link_underline_offset_mu',
            'unit_choices'   => array('px' => 'px', 'em' => 'em'),
            'step'           => '0.1',
            'priority'       => 20
        )
    )
);


$wp_customize->add_setting( 'link_fontweight',
   array(
      'default' => 'default',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'link_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_links_in_texts_font_weight_label'),
            'section'        => $section,
            'settings'       => 'link_fontweight',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => array(
                'default' => lay_t('Default', 'customizer_links_in_texts_weight_default'),
                '100' => lay_t('Thin (100)', 'customizer_links_in_texts_weight_100'),
                '200' => lay_t('Extra Light (200)', 'customizer_links_in_texts_weight_200'),
                '300' => lay_t('Light (300)', 'customizer_links_in_texts_weight_300'),
                '400' => lay_t('Normal (400)', 'customizer_links_in_texts_weight_400'),
                '500' => lay_t('Medium (500)', 'customizer_links_in_texts_weight_500'),
                '600' => lay_t('Semi Bold (600)', 'customizer_links_in_texts_weight_600'),
                '700' => lay_t('Bold (700)', 'customizer_links_in_texts_weight_700'),
                '800' => lay_t('Extra Bold (800)', 'customizer_links_in_texts_weight_800'),
                '900' => lay_t('Black (900)', 'customizer_links_in_texts_weight_900')
            )
        )
    )
);