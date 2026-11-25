<?php

$section = 'menu_submenu';

function lay_show_menu_submenu_keep_open(){
   $mod = get_theme_mod('menu_submenu_show_on', 'hover');
   return $mod == 'click';
}

function lay_show_menu_submenu_button_style_select(){
   return (bool) get_theme_mod('menu_submenu_use_button_style', false);
}

$wp_customize->add_section( $section, 
   array(
      'title' => lay_t('Submenus', 'customizer_menu_submenu_title'),
      'priority' => 60,
      'capability' => 'edit_theme_options',
      'panel' => 'navigation_panel',
      'description' => lay_t('Create a submenu in "Appearances" &rarr; "Menus" by drag and dropping menu points.', 'customizer_menu_submenu_description')
   ) 
);

$wp_customize->add_setting( 'menu_submenu_textformat',
    array(
    'default' => get_theme_mod('nav_textformat', 'Default'),
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_menu_submenu_text_format_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_textformat',
            'type'           => 'select',
            'priority'       => -1,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'is_mobile_menu_hidden'
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_show_on',
   array(
      'default' => 'hover',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_show_on',
        array(
            'label'          => lay_t('Show submenu on', 'customizer_menu_submenu_show_on_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_show_on',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array(
            	'hover' => lay_t('mouse over', 'customizer_menu_submenu_show_on_mouse_over'),
            	'click' => lay_t('click', 'customizer_menu_submenu_show_on_click'),
            	'always' => lay_t('always show', 'customizer_menu_submenu_show_on_always'),
            ),
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_keep_open',
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
        'menu_submenu_keep_open',
        array(
            'label'          => lay_t('Keep Submenu open on submenu link click', 'customizer_menu_submenu_keep_open_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_keep_open',
            'type'           => 'checkbox',
            'priority'       => 1,
            'active_callback' => 'lay_show_menu_submenu_keep_open',
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_keep_open_2',
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
        'menu_submenu_keep_open_2',
        array(
            'label'          => lay_t('Keep Submenu open until another menu point that is not inside a submenu is clicked', 'customizer_menu_submenu_keep_open_until_outside_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_keep_open_2',
            'type'           => 'checkbox',
            'priority'       => 1,
            'active_callback' => 'lay_show_menu_submenu_keep_open',
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_follow_parent_link_on_click_to_open_submenu',
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
        'menu_submenu_follow_parent_link_on_click_to_open_submenu',
        array(
            'label'          => lay_t('When clicking on link that opens a submenu, also show this link\'s page', 'customizer_menu_submenu_follow_parent_link_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_follow_parent_link_on_click_to_open_submenu',
            'description' => lay_t('If your website does a hard refresh on navigating, the submenu won\'t stay opened. Like when you have WooCommerce enabled, use a Shortcode or have Disable Ajax Mode enabled.', 'customizer_menu_submenu_keep_open_description'),
            'type'           => 'checkbox',
            'priority'       => 1,
            'active_callback' => 'lay_show_menu_submenu_keep_open',
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_type',
   array(
      'default' => 'vertical',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_type',
        array(
            'label'          => lay_t('Submenu Type', 'customizer_menu_submenu_type_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_type',
            'type'           => 'select',
            'priority'       => 5,
            'choices'        => array(
            	'vertical' => lay_t('vertical', 'customizer_menu_submenu_type_vertical'),
            	'horizontal' => lay_t('horizontal', 'customizer_menu_submenu_type_horizontal'),
            ),
            'description' => lay_t('If the menu points alignment of your menu is "vertical", the submenu type will always be vertical too.', 'customizer_menu_submenu_type_description')
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_spacetop',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'menu_submenu_spacetop',
   array(
      'label' => lay_t('Space between Submenu and Menu (px)', 'customizer_menu_submenu_space_between_label'),
      'section' => $section,
      'settings' => 'menu_submenu_spacetop',
      'type'           => 'number',
      'input_attrs' => array('min' => '0', 'step' => 1),
      'priority' => 10,
   )
) );

$wp_customize->add_setting( 'menu_submenu_spacearound',
   array(
      'default' => 10,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'menu_submenu_spacearound',
   array(
      'label' => lay_t('Space Around (px)', 'customizer_menu_submenu_space_around_label'),
      'section' => $section,
      'settings' => 'menu_submenu_spacearound',
      'type'           => 'number',
      'input_attrs' => array('min' => '1', 'step' => 1),
      'priority' => 20,
   )
) );

$wp_customize->add_setting( 'menu_submenu_space_between_submenu_points',
   array(
      'default' => 4,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'menu_submenu_space_between_submenu_points',
   array(
      'label' => lay_t('Space between Submenu Points (px)', 'customizer_menu_submenu_space_between_points_label'),
      'section' => $section,
      'settings' => 'menu_submenu_space_between_submenu_points',
      'type'           => 'number',
      'input_attrs' => array('min' => '0', 'step' => 1),
      'priority' => 30,
   )
) );

$wp_customize->add_setting( 'menu_submenu_allow_textwrap',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => function( $value ) {
         return (bool) $value;
      },
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_allow_textwrap',
        array(
            'label'          => lay_t('Allow submenu text wrap', 'customizer_menu_submenu_allow_textwrap_label'),
            'description'    => lay_t('When off, submenu links stay on one line on all desktop menus.', 'customizer_menu_submenu_allow_textwrap_description'),
            'section'        => $section,
            'settings'       => 'menu_submenu_allow_textwrap',
            'type'           => 'checkbox',
            'priority'       => 32,
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_use_button_style',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => function( $value ) {
         return (bool) $value;
      },
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_use_button_style',
        array(
            'label'          => lay_t('Button Style', 'customizer_menu_submenu_button_style_label'),
            'description'    => lay_t('Use one of the three global button styles for submenu links.', 'customizer_menu_submenu_button_style_description'),
            'section'        => $section,
            'settings'       => 'menu_submenu_use_button_style',
            'type'           => 'checkbox',
            'priority'       => -3,
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_button_style_variant',
   array(
      'default' => 'laybutton1',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
      'sanitize_callback' => function( $value ) {
         $allowed = array('laybutton1', 'laybutton2', 'laybutton3');
         return in_array($value, $allowed, true) ? $value : 'laybutton1';
      },
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'menu_submenu_button_style_variant',
        array(
            'label'          => lay_t('Button Style Variant', 'customizer_menu_submenu_button_style_variant_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_button_style_variant',
            'type'           => 'select',
            'priority'       => -2,
            'choices'        => array(
               'laybutton1' => lay_t('Button 1', 'button_1'),
               'laybutton2' => lay_t('Button 2', 'button_2'),
               'laybutton3' => lay_t('Button 3', 'button_3'),
            ),
            'active_callback' => 'lay_show_menu_submenu_button_style_select',
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_show_background',
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
        'menu_submenu_show_background',
        array(
            'label'          => lay_t('Show Background', 'customizer_menu_submenu_show_background_label'),
            'section'        => $section,
            'settings'       => 'menu_submenu_show_background',
            'type'           => 'checkbox',
            'priority'       => 40,
        )
    )
);

$wp_customize->add_setting( 'menu_submenu_background_color',
   array(
      'default' => '#fff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'menu_submenu_background_color',
   array(
      'label' => lay_t('Background Color', 'customizer_menu_submenu_background_color_label'),
      'section' => $section,
      'settings' => 'menu_submenu_background_color',
      'priority'   => 50
   )
) );

$wp_customize->add_setting( 'menu_submenu_background_opacity',
   array(
      'default' => '100',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new Range_With_Number_Custom_Control(
   $wp_customize,
   'menu_submenu_background_opacity',
   array(
      'label' => lay_t('Background Opacity (%)', 'customizer_menu_submenu_background_opacity_label'),
      'section' => $section,
      'settings' => 'menu_submenu_background_opacity',
      'input_attrs' => array('min' => 0, 'max' => 100, 'step' => 1),
      'priority' => 60,
   )
) );

$wp_customize->add_setting( 'menu_submenu_blurry',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'menu_submenu_blurry',
   array(
      'label'          => lay_t('make blurry', 'customizer_menu_submenu_blurry_label'),
      'section'        => $section,
      'settings'       => 'menu_submenu_blurry',
      'type'           => 'checkbox',
      'priority'       => 70,
      'description' => lay_t('Only works if background is a little transparent ("Opacity" less than 100)', 'customizer_menu_submenu_blurry_description')
   )
) );

$wp_customize->add_setting( 'submenu_blur_amount',
   array(
      'default' => '20',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'menu_submenu_blur_amount',
   array(
      'label' => lay_t('Blur Amount (px)', 'customizer_menu_submenu_blur_amount_label'),
      'section' => $section,
      'settings' => 'menu_submenu_blur_amount',
      'type' => 'number',
      'input_attrs' => array('min' => '0', 'max' => '100'),
      'priority' => 80,
   )
) );