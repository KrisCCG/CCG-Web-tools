<?php

$section = 'projecttitle_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Project Title', 'customizer_project_title_title'),
      'priority' => 20,
      'capability' => 'edit_theme_options',
      'panel' => 'projectlink_panel'
   )
);

$wp_customize->add_setting( 'pt_visibility',
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
        'pt_visibility',
        array(
            'label'          => lay_t('Visibility', 'customizer_project_title_visibility_label'),
            'section'        => $section,
            'settings'       => 'pt_visibility',
            'type'           => 'select',
            'choices'        => array(
                'always-show' => lay_t('always show', 'customizer_project_title_visibility_always_show'),
                'show-on-mouseover' => lay_t('show on mouseover', 'customizer_project_title_visibility_show_on_mouseover'),
                'hide' => lay_t('hide', 'customizer_project_title_visibility_hide'),
            ),
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'pt_follow_mouse',
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
        'pt_follow_mouse',
        array(
            'label'          => lay_t('Follow Mouse on Non-Touch Devices', 'customizer_project_title_follow_mouse_label'),
            'section'        => $section,
            'settings'       => 'pt_follow_mouse',
            'type'           => 'checkbox',
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'pt_animate_visibility',
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
        'pt_animate_visibility',
        array(
            'label'          => lay_t('animate Visibility', 'customizer_project_title_animate_visibility_label'),
            'section'        => $section,
            'settings'       => 'pt_animate_visibility',
            'type'           => 'checkbox',
            'priority'       => 5,
        )
    )
);

$wp_customize->add_setting( 'pt_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'pt_position',
        array(
            'label'          => lay_t('Position', 'customizer_project_title_position_label'),
            'section'        => $section,
            'settings'       => 'pt_position',
            'type'           => 'select',
            'choices'        => array(
                'above-image' => lay_t('above image', 'customizer_project_title_position_above_image'),
                'on-image-top-left' => lay_t('on image top left', 'customizer_project_title_position_on_image_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_project_title_position_on_image_top_right'),
                'on-image' => lay_t('on image center', 'customizer_project_title_position_on_image_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_project_title_position_on_image_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_project_title_position_on_image_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_project_title_position_below_image'),
            ),
            'priority' => 6
        )
    )
);

// space top - using combined control
$wp_customize->add_setting( 'pt_onimage_spacetop_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_onimage_spacetop',
    array(
    'default' => '10',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pt_onimage_spacetop',
        array(
            'label'          => lay_t('Space Top', 'customizer_project_title_space_top_label'),
            'section'        => $section,
            'settings'       => 'pt_onimage_spacetop',
            'unit_setting'   => 'pt_onimage_spacetop_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '1',
            'priority'       => 7,
            'active_callback'=> 'pt_show_spacetop_control'
        )
    )
);
function pt_show_spacetop_control(){
    $pt_position = get_theme_mod('pt_position', 'below-image');
    if( strpos( $pt_position, 'top' ) !== false ){
        return true;
    }
    return false;
}

// space bottom - using combined control
$wp_customize->add_setting( 'pt_onimage_spacebottom_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_onimage_spacebottom',
    array(
    'default' => '10',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pt_onimage_spacebottom',
        array(
            'label'          => lay_t('Space Bottom', 'customizer_project_title_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'pt_onimage_spacebottom',
            'unit_setting'   => 'pt_onimage_spacebottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '1',
            'priority'       => 8,
            'active_callback'=> 'pt_show_spacebottom_control'
        )
    )
);
function pt_show_spacebottom_control(){
    $pt_position = get_theme_mod('pt_position', 'below-image');
    if( strpos( $pt_position, 'bottom' ) !== false ){
        return true;
    }
    return false;
}

// space left - using combined control
$wp_customize->add_setting( 'pt_onimage_spaceleft_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_onimage_spaceleft',
    array(
    'default' => '10',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pt_onimage_spaceleft',
        array(
            'label'          => lay_t('Space Left', 'customizer_project_title_space_left_label'),
            'section'        => $section,
            'settings'       => 'pt_onimage_spaceleft',
            'unit_setting'   => 'pt_onimage_spaceleft_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '1',
            'priority'       => 9,
            'active_callback'=> 'pt_show_spaceleft_control'
        )
    )
);
function pt_show_spaceleft_control(){
    $pt_position = get_theme_mod('pt_position', 'below-image');
    if( strpos( $pt_position, 'left' ) !== false ){
        return true;
    }
    return false;
}

// space right - using combined control
$wp_customize->add_setting( 'pt_onimage_spaceright_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_onimage_spaceright',
    array(
    'default' => '10',
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'postMessage',
    )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pt_onimage_spaceright',
        array(
            'label'          => lay_t('Space Right', 'customizer_project_title_space_right_label'),
            'section'        => $section,
            'settings'       => 'pt_onimage_spaceright',
            'unit_setting'   => 'pt_onimage_spaceright_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '1',
            'priority'       => 10,
            'active_callback'=> 'pt_show_spaceright_control'
        )
    )
);
function pt_show_spaceright_control(){
    $pt_position = get_theme_mod('pt_position', 'below-image');
    if( strpos( $pt_position, 'right' ) !== false ){
        return true;
    }
    return false;
}

// pt_spacetop is only used for when title is above or below image.
// should be named space_between_pt_and_image


$wp_customize->add_setting( 'pt_spacetop_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_spacetop',
   array(
      'default' => '5',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'pt_spacetop',
        array(
            'label'          => lay_t('Space between Title and Image', 'customizer_project_title_space_between_label'),
            'section'        => $section,
            'settings'       => 'pt_spacetop',
            'unit_setting'   => 'pt_spacetop_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 10,
            'active_callback'=> 'pt_show_spacebetween_control' 
        )
    )
);
function pt_show_spacebetween_control(){
    $pt_position = get_theme_mod('pt_position', 'below-image');
    if( $pt_position == 'below-image' || $pt_position == 'above-image' ){
        return true;
    }
    return false;
}

$wp_customize->add_setting( 'pt_textformat',
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
        'pt_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_project_title_text_format_label'),
            'section'        => $section,
            'settings'       => 'pt_textformat',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'pt_fontfamily',
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
        'pt_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_project_title_font_family_label'),
            'section'        => $section,
            'settings'       => 'pt_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority' => 40
        )
    )
);

$wp_customize->add_setting( 'pt_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'pt_fontsize',
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
        'pt_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_project_title_font_size_label'),
            'section'        => $section,
            'settings'       => 'pt_fontsize',
            'unit_setting'   => 'pt_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority' => 50
        )
    )
);

$wp_customize->add_setting( 'pt_fontweight',
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
        'pt_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_project_title_font_weight_label'),
            'section'        => $section,
            'settings'       => 'pt_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 70
        )
    )
);

$wp_customize->add_setting( 'pt_lineheight',
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
        'pt_lineheight',
        array(
            'label'          => lay_t('Line Height', 'customizer_project_title_line_height_label'),
            'section'        => $section,
            'settings'       => 'pt_lineheight',
            'type'           => 'number',
            'input_attrs'    => array('step' => '0.01'),
            'priority'       => 75
        )
    )
);

$wp_customize->add_setting( 'pt_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'pt_color',
   array(
     'label' => lay_t('Text Color', 'customizer_project_title_text_color_label'),
      'section' => $section,
      'settings' => 'pt_color',
      'priority' => 80,
   )
) );

$wp_customize->add_setting( 'pt_letterspacing',
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
        'pt_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_project_title_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'pt_letterspacing',
            'type'           => 'number',
            'input_attrs'    => array('step' => '0.01'),
            'priority'       => 90
        )
    )
);

$wp_customize->add_setting( 'pt_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'pt_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_project_title_opacity_label'),
      'section' => $section,
      'settings' => 'pt_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 100,
   )
) );

$wp_customize->add_setting( 'pt_align',
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
        'pt_align',
        array(
            'label'          => lay_t('Text Align', 'customizer_project_title_text_align_label'),
            'section'        => $section,
            'settings'       => 'pt_align',
            'type'           => 'select',
            'priority' => 110,
            'choices'        => array(
                'left' => lay_t('left', 'customizer_project_title_text_align_left'),
                'center' => lay_t('center', 'customizer_project_title_text_align_center'),
                'right' => lay_t('right', 'customizer_project_title_text_align_right'),
            )
        )
    )
);
