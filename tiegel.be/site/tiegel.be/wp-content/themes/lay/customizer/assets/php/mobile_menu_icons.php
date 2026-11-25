<?php
$section = 'mobile_menu_icons';

function mobile_menuicons_show_move_away_when_scrolling_down(){
    return true;
}
function show_mobile_burger_icon_textformat(){
    if( get_theme_mod('burger_icon_type', 'default_thin') == 'text' ){
        return true;
    }
    return false;
}
function is_icon_color_active(){
   if( get_theme_mod('burger_icon_type', 'default_thin') == 'default' ){
      return true;
   }
   if( get_theme_mod('burger_icon_type', 'default_thin') == 'default_thin' ){
      return true;
   }
   if( get_theme_mod('burger_icon_type', 'default_thin') == 'new' ){
      return true;
   }
   return false;
}
function show_animate_burger(){
   return get_theme_mod('burger_icon_type', 'default_thin') == 'custom' || get_theme_mod('burger_icon_type', 'default_thin') == 'text' ? false : true;
}
function is_burger_type_custom(){
    return get_theme_mod('burger_icon_type', 'default_thin') == 'custom' ? true : false;
}
function show_space_options(){
   if( get_theme_mod('burger_icon_type', 'default_thin') == 'custom' ? true : false ) {
      return true;
   }
   if( get_theme_mod('mobile_hide_menubar') == 1 ) {
      return true;
   }
   if( get_theme_mod('burger_icon_type', 'default_thin') == 'text' ? true : false ) {
    return true;
 }
   return false;
}

function show_space_options_right(){
    $return = show_space_options();
    if( get_theme_mod('burger_icon_position', 'right') == 'left' ){
        $return = false;
    }
    return $return;
}

function show_space_options_left(){
    $return = show_space_options();
    if( get_theme_mod('burger_icon_position', 'right') == 'right' ){
        $return = false;
    }
    return $return;
}

$wp_customize->add_setting( 'mobile_menuicons_move_away_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menuicons_move_away_when_scrolling_down',
   array(
      'label'          => lay_t('move away when scrolling down', 'customizer_mobile_menu_icons_move_away_label'),
      'section'        => $section,
      'settings'       => 'mobile_menuicons_move_away_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 0,
      'active_callback' => 'mobile_menuicons_show_move_away_when_scrolling_down'
   )
) );

$wp_customize->add_setting( 'mobile_menuicons_fade_out_when_scrolling_down',
   array(
      'default' => false,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);      
$wp_customize->add_control( new WP_Customize_Control(
   $wp_customize,
   'mobile_menuicons_fade_out_when_scrolling_down',
   array(
      'label'          => lay_t('fade out when scrolling down', 'customizer_mobile_menu_icons_fade_out_label'),
      'section'        => $section,
      'settings'       => 'mobile_menuicons_fade_out_when_scrolling_down',
      'type'           => 'checkbox',
      'priority'       => 0,
      'active_callback' => 'mobile_menuicons_show_move_away_when_scrolling_down'
   )
) );


$wp_customize->add_setting( 'burger_icon_type',
   array(
      'default' => 'default',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'burger_icon_type',
        array(
            'label'          => lay_t('Burger Icon Type', 'customizer_mobile_menu_icons_burger_type_label'),
            'section'        => $section,
            'settings'       => 'burger_icon_type',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array('default' => 'Default', 'default_thin' => 'Default Thin', 'new' => 'New', 'custom' => 'Custom', 'text' => 'Text'),
        )
    )
);

$wp_customize->add_setting( 'burger_icon_position',
   array(
      'default' => 'right',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'burger_icon_position',
        array(
            'label'          => lay_t('Burger Icon Position', 'customizer_mobile_menu_icons_burger_position_label'),
            'section'        => $section,
            'settings'       => 'burger_icon_position',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => array('right' => 'Top Right', 'left' => 'Top Left'),
        )
    )
);

$wp_customize->add_setting( 'mobile_burger_icon_textformat',
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
        'mobile_burger_icon_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_mobile_menu_icons_text_format_label'),
            'section'        => $section,
            'settings'       => 'mobile_burger_icon_textformat',
            'type'           => 'select',
            'priority'       => 1,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'show_mobile_burger_icon_textformat',
        )
    )
);

$wp_customize->add_setting( 'mobile_burger_textcolor',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_burger_textcolor',
   array(
      'label' => lay_t('Text Color', 'customizer_mobile_menu_icons_text_color_label'),
      'section' => $section,
      'settings' => 'mobile_burger_textcolor',
      'priority'   => 1,
      'active_callback' => 'show_mobile_burger_icon_textformat',
    )
) );

$wp_customize->add_setting( 'mobile_burger_icon_text_open',
   array(
      'default' => 'Menu',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_burger_icon_text_open',
        array(
            'label'          => lay_t('Burger Text Open', 'customizer_mobile_menu_icons_burger_text_open_label'),
            'section'        => $section,
            'settings'       => 'mobile_burger_icon_text_open',
            'type'           => 'text',
            'priority'       => 1,
            'active_callback' => 'show_mobile_burger_icon_textformat',
        )
    )
);

$wp_customize->add_setting( 'mobile_burger_icon_text_close',
   array(
      'default' => 'Close',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_burger_icon_text_close',
        array(
            'label'          => lay_t('Burger Text Close', 'customizer_mobile_menu_icons_burger_text_close_label'),
            'section'        => $section,
            'settings'       => 'mobile_burger_icon_text_close',
            'type'           => 'text',
            'priority'       => 1,
            'active_callback' => 'show_mobile_burger_icon_textformat',
        )
    )
);


$wp_customize->add_setting( 'mobile_burger_icon_animate',
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
        'mobile_burger_icon_animate',
        array(
            'label'          => lay_t('Animate Burger', 'customizer_mobile_menu_icons_animate_label'),
            'section'        => $section,
            'settings'       => 'mobile_burger_icon_animate',
            'type'           => 'checkbox',
            'priority'       => 0,
            'active_callback'=> 'show_animate_burger'
        )
    )
);

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Mobile Menu Icons', 'customizer_mobile_menu_icons_title'),
      'priority' => 5,
      'capability' => 'edit_theme_options',
      'panel' => 'mobile_panel',
      'description' => lay_t('Icons only show up if "Mobile Menu Style" is not "Desktop Style"', 'customizer_mobile_menu_icons_description')
   )
);

$txtColor = Customizer::$defaults['mobile_menu_txt_color'];
$wp_customize->add_setting( 'mobile_menu_burger_icon_color',
   array(
      'default' => $txtColor,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'mobile_menu_burger_icon_color',
   array(
      'label' => lay_t('Burger Icon Color', 'customizer_mobile_menu_icons_burger_icon_color_label'),
      'section' => $section,
      'settings' => 'mobile_menu_burger_icon_color',
      'priority'   => 1,
      'active_callback' => 'is_icon_color_active',
   )
) );

$wp_customize->add_setting( 'mobile_menu_icon_burger',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
  new WP_Customize_Upload_Control(
  $wp_customize,
  'mobile_menu_icon_burger',
  array(
    'label'      => lay_t('Burger Icon', 'customizer_mobile_menu_icons_burger_icon_label'),
    'section'    => $section,
    'settings'   => 'mobile_menu_icon_burger',
    'description' => lay_t('Can be a SVG', 'customizer_can_be_svg_description'),
    'priority'   => 0,
    'active_callback' => 'is_burger_type_custom',
  ) )
);

$wp_customize->add_setting( 'mobile_menu_icon_burger_width',
   array(
      'default' => '25',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_icon_burger_width',
        array(
            'label'          => lay_t('Burger Icon Width (px)', 'customizer_mobile_menu_icons_burger_icon_width_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_icon_burger_width',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 5,
            'active_callback' => 'is_burger_type_custom',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_icon_burger_spacetop',
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
        'mobile_menu_icon_burger_spacetop',
        array(
            'label'          => lay_t('Space Top Burger Icon (px)', 'customizer_mobile_menu_icons_burger_icon_space_top_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_icon_burger_spacetop',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 10,
            'active_callback' => 'show_space_options',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_icon_burger_spaceright',
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
        'mobile_menu_icon_burger_spaceright',
        array(
            'label'          => lay_t('Space Right Burger Icon (px)', 'customizer_mobile_menu_icons_burger_icon_space_right_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_icon_burger_spaceright',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 20,
            'active_callback' => 'show_space_options_right',
        )
    )
);

$wp_customize->add_setting( 'mobile_menu_icon_burger_spaceleft',
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
        'mobile_menu_icon_burger_spaceleft',
        array(
            'label'          => lay_t('Space Left Burger Icon (px)', 'customizer_mobile_menu_icons_burger_icon_space_left_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_icon_burger_spaceleft',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 20,
            'active_callback' => 'show_space_options_left',
        )
    )
);


$wp_customize->add_setting( 'mobile_menu_icon_close',
   array(
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
  new WP_Customize_Upload_Control(
  $wp_customize,
  'mobile_menu_icon_close',
  array(
    'label'      => lay_t('Close Icon', 'customizer_mobile_menu_icons_close_icon_label'),
    'section'    => $section,
    'settings'   => 'mobile_menu_icon_close',
    'description' => lay_t('Can be a SVG', 'customizer_can_be_svg_description'),
    'priority'   => 40,
    'active_callback' => 'is_burger_type_custom',
  ) )
);

$wp_customize->add_setting( 'mobile_menu_icon_close_width',
   array(
      'default' => '25',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'mobile_menu_icon_close_width',
        array(
            'label'          => lay_t('Close Icon Width (px)', 'customizer_mobile_menu_icons_close_icon_width_label'),
            'section'        => $section,
            'settings'       => 'mobile_menu_icon_close_width',
            'type'           => 'number',
            'input_attrs'    => array('step' => '1'),
            'priority'       => 45,
            'active_callback' => 'is_burger_type_custom',
        )
    )
);

// $wp_customize->add_setting( 'mobile_menu_icon_close_spacetop',
//    array(
//       'default' => '10',
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control(
//     new WP_Customize_Control(
//         $wp_customize,
//         'mobile_menu_icon_close_spacetop',
//         array(
//             'label'          => 'Space Top Close Icon (px)',
//             'section'        => $section,
//             'settings'       => 'mobile_menu_icon_close_spacetop',
//             'type'           => 'number',
//             'input_attrs'    => array('step' => '1'),
//             'priority'       => 50,
//             'active_callback' => 'is_burger_type_custom',
//         )
//     )
// );

// $wp_customize->add_setting( 'mobile_menu_icon_close_spaceright',
//    array(
//       'default' => '10',
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control(
//     new WP_Customize_Control(
//         $wp_customize,
//         'mobile_menu_icon_close_spaceright',
//         array(
//             'label'          => 'Space Right Close Icon (px)',
//             'section'        => $section,
//             'settings'       => 'mobile_menu_icon_close_spaceright',
//             'type'           => 'number',
//             'input_attrs'    => array('step' => '1'),
//             'priority'       => 60,
//             'active_callback' => 'is_burger_type_custom',
//         )
//     )
// );