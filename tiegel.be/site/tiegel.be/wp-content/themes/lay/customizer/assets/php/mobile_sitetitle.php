<?php

function is_mobile_sitetitle_hidden(){
    return get_theme_mod('m_st_hide') == 1 ? false : true;
}

function mobile_show_sitetitle_control(){
    return get_theme_mod('m_st_txt_or_img') == 'html' || get_theme_mod('m_st_hide') == 1 ? true : false;

}

function mobile_sitetitle_show_move_away_when_scrolling_down(){
    if(get_theme_mod('m_st_hide') == 1) {
        return false;
    }
    if(get_theme_mod('m_st_isfixed') != 1) {
        return false;
    }
    return true;
}

function show_m_st_link_controls(){
    return get_theme_mod('m_st_link_to_different_page', false) == true;
}

$section = 'mobile_sitetitle';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Site Title', 'customizer_mobile_sitetitle_section_title'),
      'priority' => 10,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel'
   )
);

$wp_customize->add_setting( 'm_st_hide',
   array(
      'default' => get_theme_mod('st_hide', 0),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_hide',
        array(
            'label'          => lay_t('hide', 'customizer_mobile_sitetitle_hide_label'),
            'section'        => $section,
            'settings'       => 'm_st_hide',
            'type'           => 'checkbox',
            'priority'       => 1,
        )
    )
);

// only makes sense to show/use this control when mobile menu is hidden
$wp_customize->add_setting( 'm_st_isfixed',
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
        'm_st_isfixed',
        array(
            'label'          => lay_t('is fixed', 'customizer_mobile_sitetitle_is_fixed_label'),
            'section'        => $section,
            'settings'       => 'm_st_isfixed',
            'type'           => 'checkbox',
            'priority'       => 3,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);


$wp_customize->add_setting( 'mobile_sitetitle_move_away_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_sitetitle_move_away_when_scrolling_down',
   array(
      'label'          => lay_t('move away when scrolling down', 'customizer_mobile_sitetitle_move_away_label'),
      'section'        => $section,
      'settings'       => 'mobile_sitetitle_move_away_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 3,
      'active_callback' => 'mobile_sitetitle_show_move_away_when_scrolling_down'
   )
) );

$wp_customize->add_setting( 'mobile_sitetitle_fade_out_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_sitetitle_fade_out_when_scrolling_down',
   array(
      'label'          => lay_t('fade out when scrolling down', 'customizer_mobile_sitetitle_fade_out_label'),
      'section'        => $section,
      'settings'       => 'mobile_sitetitle_fade_out_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 3,
      'active_callback' => 'mobile_sitetitle_show_move_away_when_scrolling_down'
   )
) );


$wp_customize->add_setting( 'm_st_txt_or_img',
   array(
      'default' => get_theme_mod('st_txt_or_img', 'text'),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_txt_or_img',
        array(
            'label'          => lay_t('Text, Image or HTML', 'customizer_mobile_sitetitle_text_image_html_label'),
            'section'        => $section,
            'settings'       => 'm_st_txt_or_img',
            'type'           => 'select',
            'priority'       => 5,
            'choices'        => array(
               'text' => lay_t('Text', 'customizer_mobile_sitetitle_text_choice'),
               'image' => lay_t('Image', 'customizer_mobile_sitetitle_image_choice'),
               'html' => lay_t('HTML', 'customizer_mobile_sitetitle_html_choice'),
            ),
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_html', array(
    'default'    => '',
    'type'       => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport'  => 'postMessage',
  )
);
$wp_customize->add_control( 'm_st_html', array(
    'label'      => lay_t('Site Title HTML', 'customizer_mobile_sitetitle_html_label'),
    'type'       => 'textarea',
    'section'    => $section,
    'priority'   => 10,
    'active_callback' => 'mobile_show_sitetitle_control'
  )
);

// Link to different page
$wp_customize->add_setting( 'm_st_link_to_different_page',
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
        'm_st_link_to_different_page',
        array(
            'label'          => lay_t('link to a different page', 'customizer_mobile_sitetitle_link_to_different_page_label'),
            'section'        => $section,
            'settings'       => 'm_st_link_to_different_page',
            'type'           => 'checkbox',
            'priority'       => 11,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_link_type',
   array(
      'default' => 'category',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => 'sanitize_text_field',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_link_type',
        array(
            'label'          => lay_t('Link to', 'customizer_mobile_sitetitle_link_to_label'),
            'section'        => $section,
            'settings'       => 'm_st_link_type',
            'type'           => 'select',
            'priority'       => 12,
            'choices'        => array(
               'category' => lay_t('Category', 'customizer_mobile_sitetitle_link_type_category'),
               'page' => lay_t('Page', 'customizer_mobile_sitetitle_link_type_page'),
               'project' => lay_t('Project', 'customizer_mobile_sitetitle_link_type_project'),
            ),
            'active_callback' => 'show_m_st_link_controls',
        )
    )
);

$wp_customize->add_setting( 'm_st_link_select_category',
   array(
      'default' => '1',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Category_Dropdown_Custom_Control(
        $wp_customize,
        'm_st_link_select_category',
        array(
            'label'          => lay_t('Choose a Category', 'customizer_mobile_sitetitle_choose_category_label'),
            'section'        => $section,
            'settings'       => 'm_st_link_select_category',
            'priority'       => 13,
            'active_callback' => function() { return get_theme_mod('m_st_link_to_different_page', false) == true && get_theme_mod('m_st_link_type', 'category') === 'category'; },
        ),
        array(
            'type'                     => 'post',
            'child_of'                 => 0,
            'parent'                   => '',
            'orderby'                  => 'name',
            'order'                    => 'ASC',
            'hide_empty'               => 0,
            'hierarchical'             => 1,
            'exclude'                  => '',
            'include'                  => '',
            'number'                   => '',
            'taxonomy'                 => 'category',
            'pad_counts'               => false
      )
    )
);

$wp_customize->add_setting( 'm_st_link_select_page',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Post_Dropdown_Custom_Control(
        $wp_customize,
        'm_st_link_select_page',
        array(
            'label'          => lay_t('Choose a Page', 'customizer_mobile_sitetitle_choose_page_label'),
            'section'        => $section,
            'settings'       => 'm_st_link_select_page',
            'priority'       => 14,
            'active_callback' => function() { return get_theme_mod('m_st_link_to_different_page', false) == true && get_theme_mod('m_st_link_type', 'category') === 'page'; },
        ),
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
      )
    )
);

$wp_customize->add_setting( 'm_st_link_select_project',
   array(
      'default' => '',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Post_Dropdown_Custom_Control(
        $wp_customize,
        'm_st_link_select_project',
        array(
            'label'          => lay_t('Choose a Project', 'customizer_mobile_sitetitle_choose_project_label'),
            'section'        => $section,
            'settings'       => 'm_st_link_select_project',
            'priority'       => 15,
            'active_callback' => function() { return get_theme_mod('m_st_link_to_different_page', false) == true && get_theme_mod('m_st_link_type', 'category') === 'project'; },
        ),
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
        )
    )
);

$wp_customize->add_setting( 'm_st_image',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
  new WP_Customize_Upload_Control(
  $wp_customize,
  'm_st_image',
  array(
    'label'      => lay_t('Image', 'customizer_mobile_sitetitle_image_label'),
    'section'    => $section,
    'settings'   => 'm_st_image',
    'description' => lay_t('Use a small image', 'customizer_sitetitle_small_image_description'),
    'priority'   => 10,
    'active_callback' => 'is_mobile_sitetitle_hidden',
  ) )
);

$wp_customize->add_setting( 'm_st_img_height',
   array(
      'default' => 30,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_img_height',
        array(
            'label'          => lay_t('Image Height (px)', 'customizer_mobile_sitetitle_image_height_label'),
            'section'        => $section,
            'settings'       => 'm_st_img_height',
            'type'           => 'number',
            'priority'       => 13,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_position',
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
        'm_st_position',
        array(
            'label'          => lay_t('Position', 'customizer_mobile_sitetitle_position_label'),
            'section'        => $section,
            'settings'       => 'm_st_position',
            'type'           => 'select',
            'priority'       => 21,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_mobile_sitetitle_position_left'),
               'center' => lay_t('center', 'customizer_mobile_sitetitle_position_center'),
               'right' => lay_t('right', 'customizer_mobile_sitetitle_position_right'),
            ),
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

// space top - using combined control
$wp_customize->add_setting( 'm_st_spacetop_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'm_st_spacetop',
   array(
      'default' => 12,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'm_st_spacetop',
        array(
            'label'          => lay_t('Space Top', 'customizer_mobile_sitetitle_space_top_label'),
            'section'        => $section,
            'settings'       => 'm_st_spacetop',
            'unit_setting'   => 'm_st_spacetop_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 22,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);
// spaceleft - using combined control
$wp_customize->add_setting( 'm_st_spaceleft_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'm_st_spaceleft',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'm_st_spaceleft',
        array(
            'label'          => lay_t('Space Left', 'customizer_mobile_sitetitle_space_left_label'),
            'section'        => $section,
            'settings'       => 'm_st_spaceleft',
            'unit_setting'   => 'm_st_spaceleft_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 25,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);
// spaceright - using combined control
$wp_customize->add_setting( 'm_st_spaceright_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'm_st_spaceright',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'm_st_spaceright',
        array(
            'label'          => lay_t('Space Right', 'customizer_mobile_sitetitle_space_right_label'),
            'section'        => $section,
            'settings'       => 'm_st_spaceright',
            'unit_setting'   => 'm_st_spaceright_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 25,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);
//

$wp_customize->add_setting( 'm_st_textformat',
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
        'm_st_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_mobile_sitetitle_text_format_label'),
            'section'        => $section,
            'settings'       => 'm_st_textformat',
            'type'           => 'select',
            'priority'       => 32,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_fontfamily',
   array(
      'default' => get_theme_mod('st_fontfamily', Customizer::$defaults['fontfamily']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_mobile_sitetitle_font_family_label'),
            'section'        => $section,
            'settings'       => 'm_st_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'mobile_menu_sitetitle_fontsize',
   array(
      'default' => Customizer::$defaults['mobile_menu_fontsize'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'mobile_menu_sitetitle_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_mobile_sitetitle_font_size_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_sitetitle_fontsize',
            'unit_setting'   => 'm_st_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '1',
            'priority'       => 38,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_fontweight',
   array(
      'default' => get_theme_mod('st_fontweight', Customizer::$defaults['fontweight']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_mobile_sitetitle_font_weight_label'),
            'section'        => $section,
            'settings'       => 'm_st_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);

$wp_customize->add_setting( 'm_st_color',
   array(
      'default' => get_theme_mod('st_color', Customizer::$defaults['color']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'm_st_color',
   array(
     'label' => lay_t('Text Color', 'customizer_mobile_sitetitle_text_color_label'),
      'section' => $section,
      'settings' => 'm_st_color',
      'priority'   => 50,
      'active_callback' => 'is_mobile_sitetitle_hidden',
   )
) );

$wp_customize->add_setting( 'm_st_letterspacing',
   array(
      'default' => get_theme_mod('st_letterspacing' , Customizer::$defaults['letterspacing']),
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'm_st_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_mobile_sitetitle_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'm_st_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55,
            'active_callback' => 'is_mobile_sitetitle_hidden',
        )
    )
);
