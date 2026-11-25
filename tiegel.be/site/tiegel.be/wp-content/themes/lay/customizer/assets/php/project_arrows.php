<?php

function lay_is_project_and_project_arrows_active(){
  $return = false;
  if(is_single()){
    $return = true;
  }
  else if( is_home() || is_front_page() ){
    $type = get_theme_mod('frontpage_type', 'category');
    if($type == 'project'){
      $return = true;
    }
  }
  $val = get_option('misc_options_show_project_arrows', '');
  if($val != 'on' || MiscOptions::$locked){
    $return = false;
  }
  return $return;
}

$section = 'projectarrows_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Project Arrows', 'customizer_project_arrows_title'),
      'priority' => 80,
      'capability' => 'edit_theme_options',
      'active_callback' => 'lay_is_project_and_project_arrows_active',
      'description' => lay_t('When "Arrow Type" is "Text" underline settings of "Links in Texts" will be used', 'customizer_project_arrows_description')
   )
);

$wp_customize->add_setting( 'pa_hide_on_phone',
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
        'pa_hide_on_phone',
        array(
            'label'          => lay_t('Hide for phone layout', 'customizer_project_arrows_hide_on_phone_label'),
            'section'        => $section,
            'settings'       => 'pa_hide_on_phone',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'pa_hide_prev',
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
        'pa_hide_prev',
        array(
            'label'          => lay_t('Hide Previous Arrow', 'customizer_project_arrows_hide_previous_label'),
            'section'        => $section,
            'settings'       => 'pa_hide_prev',
            'type'           => 'checkbox',
            'priority'       => 4,
        )
    )
);

$wp_customize->add_setting( 'pa_type',
   array(
      'default' => 'icon',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pa_type',
        array(
            'label'          => lay_t('Arrow Type', 'customizer_project_arrows_type_label'),
            'section'        => $section,
            'settings'       => 'pa_type',
            'type'           => 'select',
            'choices'        =>
            array(
              'icon' => lay_t('Icon', 'customizer_project_arrows_type_icon'),
              'text' => lay_t('Text', 'customizer_project_arrows_type_text'),
              'custom-image' => lay_t('Custom Image', 'customizer_project_arrows_type_custom_image'),
              'project-thumbnails' => lay_t('Project Thumbnails', 'customizer_project_arrows_type_project_thumbnails'),
          ),
            'priority' => 5
        )
    )
);

$wp_customize->add_setting( 'pa_icon',
   array(
      'default' => '&#9654;',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pa_icon',
        array(
            'label'          => lay_t('Arrow', 'customizer_project_arrows_arrow_label'),
            'section'        => $section,
            'settings'       => 'pa_icon',
            'type'           => 'select',
            'choices'        =>
            array(
              '&#9654;' => '&#9654;',
              '&#9656;' => '&#9656;',
              '&#9655;' => '&#9655;',
              '&#9657;' => '&#9657;',
              '&#9659;' => '&#9659;',
              '&#10095;' => '&#10095;',
              '&#9002;' => '&#9002;',
              '&rsaquo;' => '&rsaquo;',
              '&#9679;' => '&#9679;',
              '&#8226;' => '&#8226;',
              '&#9673;' => '&#9673;',
              '&#9678;' => '&#9678;',
              '&#8413;' => '&#8413;',
              '&#9676;' => '&#9676;',
              '&#9675;' => '&#9675;',
              '&#10686;' => '&#10686;',
              '&#10687;' => '&#10687;',
              '&#9898;' => '&#9898;',
              '&#128309;' => '&#128309;',
              '&#128308;' => '&#128308;',
              '&#9899;' => '&#9899;',
              '&#9755;' => '&#9755;',
              '&#9758;' => '&#9758;',
              '&#128073;' => '&#128073;',
          ),
            'priority' => 12
        )
    )
);


$wp_customize->add_setting( 'pa_next_text', array(
    'default'    => 'Next',
    'type'       => 'theme_mod',
    'capability' => 'manage_options',
    'transport' => 'postMessage',
  )
);
$wp_customize->add_control( 'pa_next_text', array(
    'label'      => lay_t('Next Text', 'customizer_project_arrows_next_text_label'),
    'section'    => $section,
    'priority'   => 12
  )
);

$wp_customize->add_setting( 'pa_prev_text', array(
    'default'    => 'Previous',
    'type'       => 'theme_mod',
    'capability' => 'manage_options',
    'transport' => 'postMessage',
  )
);
$wp_customize->add_control( 'pa_prev_text', array(
    'label'      => lay_t('Previous Text', 'customizer_project_arrows_previous_text_label'),
    'section'    => $section,
    'priority'   => 12
  )
);


$wp_customize->add_setting( 'pa_image',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
  new WP_Customize_Upload_Control(
  $wp_customize,
  'pa_image',
  array(
    'label'      => lay_t('Image', 'customizer_project_arrows_image_label'),
    'section'    => $section,
    'settings'   => 'pa_image',
    'description' => lay_t('Use an arrow pointing to the right.', 'customizer_project_arrows_image_description'),
    'priority'   => 12
  ) )
);

$wp_customize->add_setting( 'pa_position',
   array(
      'default' => 'center',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pa_position',
        array(
            'label'          => lay_t('Position', 'customizer_project_arrows_position_label'),
            'section'        => $section,
            'settings'       => 'pa_position',
            'type'           => 'select',
            'priority'       => 15,
            'choices'        => array(
              'top' => lay_t('top', 'customizer_project_arrows_position_top'),
              'center' => lay_t('center', 'customizer_project_arrows_position_center'),
              'bottom' => lay_t('bottom', 'customizer_project_arrows_position_bottom'),
            )
        )
    )
);

// space top/bottom - using combined control
$wp_customize->add_setting( 'pa_spacetopbottom_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pa_spacetopbottom',
   array(
      'default' => 20,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pa_spacetopbottom',
        array(
            'label'          => lay_t('Space Top/Bottom', 'customizer_project_arrows_space_top_bottom_label'),
            'section'        => $section,
            'settings'       => 'pa_spacetopbottom',
            'unit_setting'   => 'pa_spacetopbottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 16
        )
    )
);

// space left/right - using combined control
$wp_customize->add_setting( 'pa_spaceleftright_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pa_spaceleftright',
   array(
      'default' => 20,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pa_spaceleftright',
        array(
            'label'          => lay_t('Space Left/Right', 'customizer_project_arrows_space_left_right_label'),
            'section'        => $section,
            'settings'       => 'pa_spaceleftright',
            'unit_setting'   => 'pa_spaceleftright_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 18
        )
    )
);

$wp_customize->add_setting( 'pa_icon_size',
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
        'pa_icon_size',
        array(
            'label'          => lay_t('Icon Size (px)', 'customizer_project_arrows_icon_size_label'),
            'section'        => $section,
            'settings'       => 'pa_icon_size',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 22
        )
    )
);

$wp_customize->add_setting( 'pa_padding',
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
        'pa_padding',
        array(
            'label'          => lay_t('Clickable Space around (px)', 'customizer_project_arrows_clickable_space_label'),
            'section'        => $section,
            'settings'       => 'pa_padding',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 24
        )
    )
);

$wp_customize->add_setting( 'pa_icon_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pa_icon_color',
   array(
     'label' => lay_t('Icon Color', 'customizer_project_arrows_icon_color_label'),
      'section' => $section,
      'settings' => 'pa_icon_color',
      'priority'   => 25
   )
) );

$wp_customize->add_setting( 'pa_width_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pa_width',
   array(
      'default' => 10,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pa_width',
        array(
            'label'          => lay_t('Width', 'customizer_project_arrows_width_label'),
            'section'        => $section,
            'settings'       => 'pa_width',
            'unit_setting'   => 'pa_width_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 25
        )
    )
);

// text
$wp_customize->add_setting( 'pa_textformat',
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
        'pa_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_project_arrows_text_format_label'),
            'section'        => $section,
            'settings'       => 'pa_textformat',
            'type'           => 'select',
            'priority'       => 31,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'pa_fontfamily',
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
        'pa_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_project_arrows_font_family_label'),
            'section'        => $section,
            'settings'       => 'pa_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35
        )
    )
);

$wp_customize->add_setting( 'pa_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pa_fontsize',
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
        'pa_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_project_arrows_font_size_label'),
            'section'        => $section,
            'settings'       => 'pa_fontsize',
            'unit_setting'   => 'pa_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38
        )
    )
);

$wp_customize->add_setting( 'pa_fontweight',
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
        'pa_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_project_arrows_font_weight_label'),
            'section'        => $section,
            'settings'       => 'pa_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45
        )
    )
);

$wp_customize->add_setting( 'pa_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pa_color',
   array(
     'label' => lay_t('Text Color', 'customizer_project_arrows_text_color_label'),
      'section' => $section,
      'settings' => 'pa_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'pa_letterspacing',
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
        'pa_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_project_arrows_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'pa_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

// #text

$wp_customize->add_setting( 'pa_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pa_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_project_arrows_opacity_label'),
      'section' => $section,
      'settings' => 'pa_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 80,
   )
) );

$wp_customize->add_setting( 'pa_mouseover_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pa_mouseover_color',
   array(
     'label' => lay_t('Mouseover Color', 'customizer_project_arrows_mouseover_color_label'),
      'section' => $section,
      'settings' => 'pa_mouseover_color',
      'priority'   => 90
   )
) );

$wp_customize->add_setting( 'pa_mouseover_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pa_mouseover_opacity',
   array(
     'label' => lay_t('Mouseover Opacity (%)', 'customizer_project_arrows_mouseover_opacity_label'),
      'section' => $section,
      'settings' => 'pa_mouseover_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 100,
   )
) );
