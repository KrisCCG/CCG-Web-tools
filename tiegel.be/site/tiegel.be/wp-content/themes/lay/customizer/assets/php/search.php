<?php
function is_autosuggest_shown(){
    $is_hidden = get_theme_mod('search_autosuggest_hide', false);
    $is_shown = !$is_hidden;
    return $is_shown;
}

function show_search_textformat(){
    $v = get_theme_mod('search_use_textformat', false);
    if( $v == 1 ) {
        return true;
    }
    return false;
}

function show_search_fontfamily(){
    $v = get_theme_mod('search_use_textformat', false);
    if( $v == 1 ) {
        return false;
    }
    return true;
}

$section = 'search_section';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Search', 'customizer_search_title'),
      'priority' => 50,
      'capability' => 'edit_theme_options',
      'description' => lay_t('How to use the Search feature: Create a "Custom Link" menu point with a URL of "#search" (without "") in Appearance → Menus. When clicking on that link, the search overlay you see here will appear.', 'customizer_search_section_description')
   )
);

$wp_customize->add_setting( 'search_placeholder_text', array(
   'default'    => 'Type your search…',
   'type'       => 'theme_mod',
   'capability' => 'edit_theme_options',
   'transport'  => 'postMessage',
 )
);
$wp_customize->add_control( 'search_placeholder_text', array(
   'label'      => lay_t('Placeholder Text', 'customizer_search_placeholder_label'),
   'type'       => 'text',
   'section'    => $section,
   'priority'   => 0
 )
);


$wp_customize->add_setting( 'search_fontfamily',
   array(
      'default' => Customizer::$defaults['fontfamily'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'search_fontfamily',
        array(
            'label'          => lay_t('Font Family for Input and Auto Suggestions', 'customizer_search_font_family_label'),
            'section'        => $section,
            'settings'       => 'search_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 0,
            // 'active_callback'=> 'show_search_fontfamily'
        )
    )
);

$wp_customize->add_setting( 'search_use_textformat',
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
        'search_use_textformat',
        array(
            'label'          => lay_t('Use Textformat for Input', 'customizer_search_use_textformat_label'),
            'section'        => $section,
            'settings'       => 'search_use_textformat',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'search_textformat',
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
        'search_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_search_text_format_label'),
            'section'        => $section,
            'settings'       => 'search_textformat',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'show_search_textformat'
        )
    )
);

$wp_customize->add_setting( 'search_background_color',
   array(
      'default' => '#ffffff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_background_color',
   array(
     'label' => lay_t('Background Color', 'customizer_search_background_color_label'),
      'section' => $section,
      'settings' => 'search_background_color',
      'priority'   => 0,
   )
) );

$wp_customize->add_setting( 'search_background_opacity',
   array(
      'default' => '85',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'search_background_opacity',
   array(
     'label' => lay_t('Background Opacity (%)', 'customizer_search_background_opacity_label'),
      'section' => $section,
      'settings' => 'search_background_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 0,
   )
) );

$wp_customize->add_setting( 'search_background_blurry',
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
        'search_background_blurry',
        array(
            'label'          => lay_t('Blurry background', 'customizer_search_blurry_background_label'),
            'section'        => $section,
            'settings'       => 'search_background_blurry',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'search_close_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_close_color',
   array(
     'label' => lay_t('"X" Icon Color', 'customizer_search_close_color_label'),
      'section' => $section,
      'settings' => 'search_close_color',
      'priority'   => 0,
   )
) );

$wp_customize->add_setting( 'search_bar_placeholder_color',
   array(
      'default' => '#ccc',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_bar_placeholder_color',
   array(
     'label' => lay_t('"Type your search…" Text Color', 'customizer_search_placeholder_color_label'),
      'section' => $section,
      'settings' => 'search_bar_placeholder_color',
      'priority'   => 0,
   )
) );

$wp_customize->add_setting( 'search_text_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_text_color',
   array(
     'label' => lay_t('Entered Search Text Color', 'customizer_search_text_color_label'),
      'section' => $section,
      'settings' => 'search_text_color',
      'priority'   => 0,
   )
) );

$wp_customize->add_setting( 'search_text_selection_color',
   array(
      'default' => '#f5f5f5',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_text_selection_color',
   array(
     'label' => lay_t('Search Text Selection Background Color (Double-click on entered text in search bar. The background color is this color.)', 'customizer_search_selection_color_label'),
      'section' => $section,
      'settings' => 'search_text_selection_color',
      'priority'   => 0,
   )
) );

$wp_customize->add_setting( 'search_autosuggest_hide',
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
        'search_autosuggest_hide',
        array(
            'label'          => lay_t('Hide Auto-Suggestions', 'customizer_search_hide_autosuggest_label'),
            'section'        => $section,
            'settings'       => 'search_autosuggest_hide',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'search_autosuggest_text_color',
   array(
      'default' => '#aaa',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_autosuggest_text_color',
   array(
     'label' => lay_t('Autosuggest Text Color (suggestion list when searching for something)', 'customizer_search_autosuggest_text_color_label'),
      'section' => $section,
      'settings' => 'search_autosuggest_text_color',
      'priority'   => 0,
      'active_callback' => 'is_autosuggest_shown',
   )
) );

$wp_customize->add_setting( 'search_autosuggest_mouseover_text_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'search_autosuggest_mouseover_text_color',
   array(
     'label' => lay_t('Autosuggest Text Mouseover Color', 'customizer_search_autosuggest_mouseover_color_label'),
      'section' => $section,
      'settings' => 'search_autosuggest_mouseover_text_color',
      'priority'   => 0,
      'active_callback' => 'is_autosuggest_shown'
   )
) );

$wp_customize->add_setting( 'search_desktop_columns',
   array(
      'default' => '4',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'search_desktop_columns',
        array(
            'label'          => lay_t('Desktop Columns Amount', 'customizer_search_desktop_columns_label'),
            'section'        => $section,
            'settings'       => 'search_desktop_columns',
            'type'           => 'number',
            'input_attrs'    => array('min' => '1', 'step' => '1'),
            'priority'       => 100,
            'active_callback' => 'is_autosuggest_shown'
        )
    )
);

$wp_customize->add_setting( 'search_tablet_columns',
   array(
      'default' => '3',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'search_tablet_columns',
        array(
            'label'          => lay_t('Tablet Columns Amount', 'customizer_search_tablet_columns_label'),
            'section'        => $section,
            'settings'       => 'search_tablet_columns',
            'type'           => 'number',
            'input_attrs'    => array('min' => '1', 'step' => '1'),
            'priority'       => 101,
            'active_callback' => 'is_autosuggest_shown'
        )
    )
);

$wp_customize->add_setting( 'search_phone_columns',
   array(
      'default' => '3',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'search_phone_columns',
        array(
            'label'          => lay_t('Phone Columns Amount', 'customizer_search_phone_columns_label'),
            'section'        => $section,
            'settings'       => 'search_phone_columns',
            'type'           => 'number',
            'input_attrs'    => array('min' => '1', 'step' => '1'),
            'priority'       => 102,
            'active_callback' => 'is_autosuggest_shown'
        )
    )
);

$wp_customize->add_setting( 'search_max_width',
   array(
      'default' => '0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'search_max_width',
        array(
            'label'          => lay_t('Max-Width (px)', 'customizer_search_max_width_label'),
            'section'        => $section,
            'settings'       => 'search_max_width',
            'type'           => 'number',
            'input_attrs'    => array('min' => '0', 'step' => '1'),
            'priority'       => 103,
            'description'    => lay_t('0 is off', 'customizer_zero_is_off_description'),
            'active_callback' => 'is_autosuggest_shown'
        )
    )
);