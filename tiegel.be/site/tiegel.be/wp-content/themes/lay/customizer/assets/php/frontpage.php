<?php

$section = 'frontpage_options';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Front Page', 'customizer_frontpage_title'),
      'priority' => 99,
      'capability' => 'edit_theme_options',
      'description' => sprintf(lay_t('This is the same setting as in <a href="%s">Settings &rarr; Set Frontpage</a>', 'customizer_frontpage_description'), admin_url('options-general.php?page=set-frontpage')),
   )
);

$wp_customize->add_setting( 'frontpage_type',
   array(
      'default' => 'category',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => 'sanitize_text_field',
   )
);
$frontpage_type_choices = array(
   'category' => lay_t('Category', 'customizer_frontpage_type_category'),
   'page' => lay_t('Page', 'customizer_frontpage_type_page'),
   'project' => lay_t('Project', 'customizer_frontpage_type_project'),
);
if ( get_option('misc_options_activate_deck_feature', '') === 'on' ) {
   $frontpage_type_choices['deck'] = lay_t('Deck Slide', 'customizer_frontpage_type_deck');
}
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'frontpage_type',
        array(
            'label'          => lay_t('Front Page displays', 'customizer_frontpage_displays_label'),
            'section'        => $section,
            'settings'       => 'frontpage_type',
            'type'           => 'radio',
            'priority'       => 10,
            'choices'        => $frontpage_type_choices,
        )
    )
);

$wp_customize->add_setting( 'frontpage_select_category',
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
        'frontpage_select_category',
        array(
            'label'          => lay_t('Choose a Category', 'customizer_frontpage_choose_category_label'),
            'section'        => $section,
            'settings'       => 'frontpage_select_category',
            'priority'       => 20,
            'active_callback' => function() { return get_theme_mod('frontpage_type', 'category') === 'category'; },
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

$wp_customize->add_setting( 'frontpage_select_page',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Post_Dropdown_Custom_Control(
        $wp_customize,
        'frontpage_select_page',
        array(
            'label'          => lay_t('Choose a Page', 'customizer_frontpage_choose_page_label'),
            'section'        => $section,
            'settings'       => 'frontpage_select_page',
            'priority'       => 30,
            'active_callback' => function() { return get_theme_mod('frontpage_type', 'category') === 'page'; },
        ),
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
      )
    )
);

$wp_customize->add_setting( 'frontpage_select_project',
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
        'frontpage_select_project',
        array(
            'label'          => lay_t('Choose a Project', 'customizer_frontpage_choose_project_label'),
            'section'        => $section,
            'settings'       => 'frontpage_select_project',
            'priority'       => 40,
            'active_callback' => function() { return get_theme_mod('frontpage_type', 'category') === 'project'; },
        ),
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page'   => -1,
        )
    )
);

if ( get_option('misc_options_activate_deck_feature', '') === 'on' ) {
   $wp_customize->add_setting( 'frontpage_select_deck',
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
           'frontpage_select_deck',
           array(
               'label'          => lay_t('Choose a Deck Slide', 'customizer_frontpage_choose_deck_label'),
               'section'        => $section,
               'settings'       => 'frontpage_select_deck',
               'priority'       => 50,
               'active_callback' => function() { return get_theme_mod('frontpage_type', 'category') === 'deck'; },
           ),
           array(
               'post_type'      => 'lay_deck',
               'post_status'    => 'publish',
               'posts_per_page'   => -1,
           )
       )
   );
}
