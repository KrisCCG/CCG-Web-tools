<?php

function show_browser_tab_title(){
    return get_theme_mod('st_use_different_tab_title', '') == 'on' ? true : false;
}

function show_sitetitle_control(){
    return get_theme_mod('st_hide', '') == 'on' ? false : true;
}

function show_st_link_controls(){
    return get_theme_mod('st_link_to_different_page', false) == true;
}


$section = 'sitetitle_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Site Title', 'customizer_sitetitle_section_title'),
      'priority' => 10,
      'capability' => 'edit_theme_options',
      'panel' => 'sitetitle_panel'
   )
);

$wp_customize->add_setting( 'st_hide',
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
        'st_hide',
        array(
            'label'          => lay_t('hide', 'customizer_sitetitle_hide_label'),
            'section'        => $section,
            'settings'       => 'st_hide',
            'type'           => 'checkbox',
            'priority'       => 4,
        )
    )
);

$wp_customize->add_setting( 'st_txt_or_img',
   array(
      'default' => 'text',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'st_txt_or_img',
        array(
            'label'          => lay_t('Text, Image or HTML', 'customizer_sitetitle_text_image_html_label'),
            'section'        => $section,
            'settings'       => 'st_txt_or_img',
            'type'           => 'select',
            'priority'       => 5,
            'choices'        => array(
               'text' => lay_t('Text', 'customizer_sitetitle_text_choice'),
               'image' => lay_t('Image', 'customizer_sitetitle_image_choice'),
               'html' => lay_t('HTML', 'customizer_sitetitle_html_choice'),
            )
        )
    )
);

$wp_customize->add_setting( 'st_image',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
  new WP_Customize_Upload_Control(
  $wp_customize,
  'st_image',
  array(
    'label'      => lay_t('Image', 'customizer_sitetitle_image_label'),
    'section'    => $section,
    'settings'   => 'st_image',
    'description' => lay_t('Use a small image', 'customizer_sitetitle_small_image_description'),
    'priority'   => 10
  ) )
);

$wp_customize->add_setting( 'blogname', array(
    'default'    => get_option( 'blogname' ),
    'type'       => 'option',
    'capability' => 'manage_options',
    'transport' => 'postMessage',
  )
);

$wp_customize->add_control( 'blogname', array(
    'label'      => lay_t('Site Title', 'customizer_sitetitle_site_title_label'),
    'type'       => 'text',
    'section'    => $section,
    'priority'   => 10
  )
);

$wp_customize->add_setting( 'st_use_different_tab_title',
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
        'st_use_different_tab_title',
        array(
            'label'          => lay_t('use different title for browser tab', 'customizer_sitetitle_use_different_tab_title_label'),
            'section'        => $section,
            'settings'       => 'st_use_different_tab_title',
            'type'           => 'checkbox',
            'priority'       => 11,
        )
    )
);


$wp_customize->add_setting( 'browser_tab_title', array(
    'default'    => 'Tab Title',
    'type'       => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport'  => 'postMessage',
  )
);
$wp_customize->add_control( 'browser_tab_title', array(
    'label'      => lay_t('Title for Browser Tab', 'customizer_sitetitle_browser_tab_title_label'),
    'type'       => 'text',
    'section'    => $section,
    'priority'   => 11,
    'active_callback' => 'show_browser_tab_title',
  )
);

// Link to different page
$wp_customize->add_setting( 'st_link_to_different_page',
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
        'st_link_to_different_page',
        array(
            'label'          => lay_t('link to a different page', 'customizer_sitetitle_link_to_different_page_label'),
            'section'        => $section,
            'settings'       => 'st_link_to_different_page',
            'type'           => 'checkbox',
            'priority'       => 12,
        )
    )
);

$wp_customize->add_setting( 'st_link_type',
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
        'st_link_type',
        array(
            'label'          => lay_t('Link to', 'customizer_sitetitle_link_to_label'),
            'section'        => $section,
            'settings'       => 'st_link_type',
            'type'           => 'select',
            'priority'       => 13,
            'choices'        => array(
               'category' => lay_t('Category', 'customizer_sitetitle_link_type_category'),
               'page' => lay_t('Page', 'customizer_sitetitle_link_type_page'),
               'project' => lay_t('Project', 'customizer_sitetitle_link_type_project'),
            ),
            'active_callback' => 'show_st_link_controls',
        )
    )
);

$wp_customize->add_setting( 'st_link_select_category',
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
        'st_link_select_category',
        array(
            'label'          => lay_t('Choose a Category', 'customizer_sitetitle_choose_category_label'),
            'section'        => $section,
            'settings'       => 'st_link_select_category',
            'priority'       => 14,
            'active_callback' => function() { return get_theme_mod('st_link_to_different_page', false) == true && get_theme_mod('st_link_type', 'category') === 'category'; },
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

$wp_customize->add_setting( 'st_link_select_page',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Post_Dropdown_Custom_Control(
        $wp_customize,
        'st_link_select_page',
        array(
            'label'          => lay_t('Choose a Page', 'customizer_sitetitle_choose_page_label'),
            'section'        => $section,
            'settings'       => 'st_link_select_page',
            'priority'       => 15,
            'active_callback' => function() { return get_theme_mod('st_link_to_different_page', false) == true && get_theme_mod('st_link_type', 'category') === 'page'; },
        ),
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
      )
    )
);

$wp_customize->add_setting( 'st_link_select_project',
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
        'st_link_select_project',
        array(
            'label'          => lay_t('Choose a Project', 'customizer_sitetitle_choose_project_label'),
            'section'        => $section,
            'settings'       => 'st_link_select_project',
            'priority'       => 16,
            'active_callback' => function() { return get_theme_mod('st_link_to_different_page', false) == true && get_theme_mod('st_link_type', 'category') === 'project'; },
        ),
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
        )
    )
);

$wp_customize->add_setting( 'site_title_html', array(
    'default'    => '',
    'type'       => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport'  => 'postMessage',
  )
);
$wp_customize->add_control( 'site_title_html', array(
    'label'      => lay_t('Site Title HTML', 'customizer_sitetitle_html_label'),
    'type'       => 'textarea',
    'section'    => $section,
    'priority'   => 10,
    'active_callback' => 'show_sitetitle_control'
  )
);

$wp_customize->add_setting( 'st_img_width_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'st_img_width',
   array(
      'default' => Customizer::$defaults['st_img_width'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'st_img_width',
        array(
            'label'          => lay_t('Image Width', 'customizer_sitetitle_image_width_label'),
            'section'        => $section,
            'settings'       => 'st_img_width',
            'unit_setting'   => 'st_img_width_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '1',
            'priority'       => 11,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_image_alignment_in_relation_to_tagline',
   array(
      'default' => 'left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'st_image_alignment_in_relation_to_tagline',
        array(
            'label'          => lay_t('Image alignment in relation to tagline', 'customizer_sitetitle_image_alignment_label'),
            'section'        => $section,
            'settings'       => 'st_image_alignment_in_relation_to_tagline',
            'type'           => 'select',
            'priority'       => 14,
            'choices'        => array(
               'left' => lay_t('left', 'customizer_sitetitle_image_alignment_left'),
               'center' => lay_t('center', 'customizer_sitetitle_image_alignment_center'),
               'right' => lay_t('right', 'customizer_sitetitle_image_alignment_right'),
            ),
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_isfixed',
   array(
      'default' => true,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'st_isfixed',
        array(
            'label'          => lay_t('is fixed', 'customizer_sitetitle_is_fixed_label'),
            'section'        => $section,
            'settings'       => 'st_isfixed',
            'type'           => 'checkbox',
            'priority'       => 15,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_hidewhenscrolling',
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
        'st_hidewhenscrolling',
        array(
            'label'          => lay_t('move away when scrolling down', 'customizer_sitetitle_move_away_label'),
            'section'        => $section,
            'settings'       => 'st_hidewhenscrolling',
            'type'           => 'checkbox',
            'priority'       => 17,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_fadeout_whenscrolling',
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
        'st_fadeout_whenscrolling',
        array(
            'label'          => lay_t('fade out when scrolling down', 'customizer_sitetitle_fade_out_label'),
            'section'        => $section,
            'settings'       => 'st_fadeout_whenscrolling',
            'type'           => 'checkbox',
            'priority'       => 17,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_position',
   array(
      'default' => 'top-left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'st_position',
        array(
            'label'          => lay_t('Position', 'customizer_sitetitle_position_label'),
            'section'        => $section,
            'settings'       => 'st_position',
            'type'           => 'select',
            'priority'       => 21,
            'choices'        => array(
               'top-left' => lay_t('top left', 'customizer_sitetitle_position_top_left'),
               'top-center' => lay_t('top center', 'customizer_sitetitle_position_top_center'),
               'top-right' => lay_t('top right', 'customizer_sitetitle_position_top_right'),
               'center' => lay_t('center', 'customizer_sitetitle_position_center'),
               'bottom-left' => lay_t('bottom left', 'customizer_sitetitle_position_bottom_left'),
               'bottom-center' => lay_t('bottom center', 'customizer_sitetitle_position_bottom_center'),
               'bottom-right' => lay_t('bottom right', 'customizer_sitetitle_position_bottom_right'),
            ),
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

// space top - using combined control
$wp_customize->add_setting( 'st_spacetop_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'st_spacetop',
   array(
      'default' => Customizer::$defaults['st_spacetop'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'st_spacetop',
        array(
            'label'          => lay_t('Space Top', 'customizer_sitetitle_space_top_label'),
            'section'        => $section,
            'settings'       => 'st_spacetop',
            'unit_setting'   => 'st_spacetop_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 22,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

// space bottom - using combined control
$wp_customize->add_setting( 'st_spacebottom_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'st_spacebottom',
   array(
      'default' => Customizer::$defaults['st_spacebottom'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'st_spacebottom',
        array(
            'label'          => lay_t('Space Bottom', 'customizer_sitetitle_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'st_spacebottom',
            'unit_setting'   => 'st_spacebottom_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 22,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

// spaceleft - using combined control
$wp_customize->add_setting( 'st_spaceleft_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'st_spaceleft',
   array(
      'default' => Customizer::$defaults['st_spaceleft'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'st_spaceleft',
        array(
            'label'          => lay_t('Space Left', 'customizer_sitetitle_space_left_label'),
            'section'        => $section,
            'settings'       => 'st_spaceleft',
            'unit_setting'   => 'st_spaceleft_mu',
            'unit_choices'   => array('%' => '%', 'px' => 'px'),
            'step'           => '0.1',
            'priority'       => 25,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

// spaceright - using combined control
$wp_customize->add_setting( 'st_spaceright_mu',
   array(
      'default' => '%',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'st_spaceright',
   array(
      'default' => Customizer::$defaults['st_spaceright'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'st_spaceright',
        array(
            'label'          => lay_t('Space Right', 'customizer_sitetitle_space_right_label'),
            'section'        => $section,
            'settings'       => 'st_spaceright',
            'unit_setting'   => 'st_spaceright_mu',
            'step'           => '0.1',
            'priority'       => 27,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_textformat',
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
        'st_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_sitetitle_text_format_label'),
            'section'        => $section,
            'settings'       => 'st_textformat',
            'type'           => 'select',
            'priority'       => 31,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_fontfamily',
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
        'st_fontfamily',
        array(
            'label'          => lay_t('Font Family', 'customizer_sitetitle_font_family_label'),
            'section'        => $section,
            'settings'       => 'st_fontfamily',
            'type'           => 'select',
            'choices'        => Customizer::$fonts,
            'priority'       => 35,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_fontsize_mu',
   array(
      'default' => 'px',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_setting( 'st_fontsize',
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
        'st_fontsize',
        array(
            'label'          => lay_t('Font Size', 'customizer_sitetitle_font_size_label'),
            'section'        => $section,
            'settings'       => 'st_fontsize',
            'unit_setting'   => 'st_fontsize_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 38,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_fontweight',
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
        'st_fontweight',
        array(
            'label'          => lay_t('Font Weight', 'customizer_sitetitle_font_weight_label'),
            'section'        => $section,
            'settings'       => 'st_fontweight',
            'type'           => 'select',
            'choices'        => array_flip(FormatsManager::$fontWeights),
            'priority'       => 45,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_color',
   array(
      'default' => Customizer::$defaults['color'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'st_color',
   array(
     'label' => lay_t('Text Color', 'customizer_sitetitle_text_color_label'),
      'section' => $section,
      'settings' => 'st_color',
      'priority'   => 50,
      'active_callback' => 'show_sitetitle_control'
   )
) );

$wp_customize->add_setting( 'st_letterspacing',
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
        'st_letterspacing',
        array(
            'label'          => lay_t('Letter Spacing (em)', 'customizer_sitetitle_letter_spacing_label'),
            'section'        => $section,
            'settings'       => 'st_letterspacing',
            'input_attrs'    => array('step' => '0.01'),
            'type'           => 'number',
            'priority'       => 55,
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_align',
   array(
      'default' => 'left',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'st_align',
        array(
            'label'          => lay_t('Text Align', 'customizer_sitetitle_text_align_label'),
            'section'        => $section,
            'settings'       => 'st_align',
            'type'           => 'select',
            'priority' => 57,
            'choices'        => array(
             'left' => lay_t('left', 'customizer_sitetitle_text_align_left'),
             'center' => lay_t('center', 'customizer_sitetitle_text_align_center'),
             'right' => lay_t('right', 'customizer_sitetitle_text_align_right'),
            ),
            'description'   => lay_t('Text-align is only important if you use multiline text for your site title.', 'customizer_sitetitle_text_align_description'),
            'active_callback' => 'show_sitetitle_control'
        )
    )
);

$wp_customize->add_setting( 'st_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'st_opacity',
   array(
     'label' => lay_t('Opacity (%)', 'customizer_sitetitle_opacity_label'),
      'section' => $section,
      'settings' => 'st_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 60,
      'active_callback' => 'show_sitetitle_control'
   )
) );

$wp_customize->add_setting( 'st_underline_strokewidth',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'st_underline_strokewidth',
   array(
     'label' => lay_t('Underline Strokewidth (px)', 'customizer_sitetitle_underline_strokewidth_label'),
      'section' => $section,
      'settings' => 'st_underline_strokewidth',
      'type' => 'number',
      'priority' => 70,
      'active_callback' => 'show_sitetitle_control'
   )
) );

$default = get_theme_mod('link_underline_offset', 3);
$wp_customize->add_setting( 'st_link_underline_offset',
   array(
      'default' => $default,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'st_link_underline_offset',
   array(
     'label' => lay_t('Underline Offset', 'customizer_sitetitle_underline_offset_label'),
      'section' => $section,
      'settings' => 'st_link_underline_offset',
      'type'           => 'number',
      'priority' => 80,
      'active_callback' => 'show_sitetitle_control'
   )
) );
