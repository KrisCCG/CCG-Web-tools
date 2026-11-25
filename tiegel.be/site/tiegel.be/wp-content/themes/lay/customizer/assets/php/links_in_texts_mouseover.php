<?php

$section = 'links_in_texts_mouseover_options';
$panel = 'linksintexts_panel';

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Links in Texts Mouseover', 'customizer_links_in_texts_mouseover_title'),
      'priority' => 10,
      'capability' => 'edit_theme_options',
      'panel' => $panel
   ) 
);

$wp_customize->add_setting( 'link_hover_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'link_hover_color',
   array(
      'label' => lay_t('Text Color', 'customizer_links_in_texts_mouseover_text_color_label'),
      'section' => $section,
      'settings' => 'link_hover_color',
      'priority'   => 10
   )
) );

$wp_customize->add_setting( 'link_force_hover_color',
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
        'link_force_hover_color',
        array(
            'label' => lay_t('Force Mouseover Styles', 'customizer_links_in_texts_mouseover_force_styles_label'),
            'description' => lay_t('If checked, the mouseover styles will be applied to all links in text elements, even if you applied different styles in the texteditor or by custom CSS.', 'customizer_links_mouseover_force_styles_description'),
            'section'        => $section,
            'settings'       => 'link_force_hover_color',
            'type'           => 'checkbox',
            'priority'       => 11,
        )
    )
);

$wp_customize->add_setting( 'link_hover_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'link_hover_opacity',
   array(
      'label' => lay_t('Opacity (%)', 'customizer_links_in_texts_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'link_hover_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 20,
   )
) );

$wp_customize->add_setting( 'link_hover_underline_strokewidth',
   array(
      'default' => get_theme_mod('link_underline_strokewidth', 1),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'link_hover_underline_strokewidth',
   array(
      'label' => lay_t('Underline Strokewidth (px)', 'customizer_links_in_texts_mouseover_underline_strokewidth_label'),
      'section' => $section,
      'settings' => 'link_hover_underline_strokewidth',
      'type'           => 'number',
      'priority' => 30,
   )
) );

$wp_customize->add_setting( 'link_hover_fontweight',
   array(
      'default' => get_theme_mod('link_fontweight', 'default'),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'link_hover_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_links_in_texts_mouseover_font_weight_label'),
            'section'        => $section,
            'settings'       => 'link_hover_fontweight',
            'type'           => 'select',
            'priority'       => 25,
            'choices'        => array(
                'default' => lay_t('Default', 'customizer_links_in_texts_mouseover_weight_default'),
                '100' => lay_t('Thin (100)', 'customizer_links_in_texts_mouseover_weight_100'),
                '200' => lay_t('Extra Light (200)', 'customizer_links_in_texts_mouseover_weight_200'),
                '300' => lay_t('Light (300)', 'customizer_links_in_texts_mouseover_weight_300'),
                '400' => lay_t('Normal (400)', 'customizer_links_in_texts_mouseover_weight_400'),
                '500' => lay_t('Medium (500)', 'customizer_links_in_texts_mouseover_weight_500'),
                '600' => lay_t('Semi Bold (600)', 'customizer_links_in_texts_mouseover_weight_600'),
                '700' => lay_t('Bold (700)', 'customizer_links_in_texts_mouseover_weight_700'),
                '800' => lay_t('Extra Bold (800)', 'customizer_links_in_texts_mouseover_weight_800'),
                '900' => lay_t('Black (900)', 'customizer_links_in_texts_mouseover_weight_900')
            )
        )
    )
);