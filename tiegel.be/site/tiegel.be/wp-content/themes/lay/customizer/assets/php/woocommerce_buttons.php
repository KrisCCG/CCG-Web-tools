<?php
$section = 'lay_woocommerce_buttons';

function lay_show_button_border_width(){
   return get_theme_mod('lay_woocommerce_button_style', 'filled') == 'outlines' ? true : false;
}

$wp_customize->add_section(
    $section,
    array(
        'title'       => lay_t('Buttons', 'customizer_woocommerce_buttons_title'),
        'priority'    => 30,
        'panel'       => 'woocommerce',
    )
);

$wp_customize->add_setting( 'lay_woocommerce_button_textformat',
   array(
      'default' => 'Shop_Big',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_button_textformat',
        array(
            'label'          => lay_t('Text Format', 'customizer_woocommerce_buttons_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_button_textformat',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_button_style',
   array(
      'default' => 'filled',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_button_style',
        array(
            'label'          => lay_t('Button Style', 'customizer_woocommerce_buttons_style_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_button_style',
            'type'           => 'radio',
            'priority'       => 5,
            'choices'        => array(
               'filled' => lay_t('Filled', 'customizer_woocommerce_buttons_style_filled'),
               'outlines' => lay_t('Outlines', 'customizer_woocommerce_buttons_style_outlines'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_button_border_width',
   array(
      'default' => 1,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_button_border_width',
        array(
            'label'          => lay_t('Border Width (px)', 'customizer_woocommerce_buttons_border_width_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_button_border_width',
            'priority'       => 6,
            'input_attrs'    => array('step' => '1', 'min' => 0),
            'type'           => 'number',
            'active_callback' => 'lay_show_button_border_width'
        )
    )
);

// $wp_customize->add_setting( 'lay_woocommerce_button_border_radius',
//    array(
//       'default' => 0,
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control(
//     new WP_Customize_Control(
//         $wp_customize,
//         'lay_woocommerce_button_border_radius',
//         array(
//             'label'          => 'Border Roundness (px)',
//             'section'        => $section,
//             'settings'       => 'lay_woocommerce_button_border_radius',
//             'priority'       => 7,
//             'input_attrs'    => array('step' => '1', 'min' => 0),
//             'type'           => 'number'
//         )
//     )
// );

$wp_customize->add_setting( 'lay_woocommerce_button_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_button_color',
   array(
     'label' => lay_t('Button Color', 'customizer_woocommerce_buttons_button_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_button_color',
      'priority'   => 10
   )
) );


$wp_customize->add_setting( 'lay_woocommerce_button_text_color',
   array(
      'default' => '#fff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_button_text_color',
   array(
     'label' => lay_t('Text Color', 'customizer_woocommerce_buttons_text_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_button_text_color',
      'priority'   => 15
   )
) );


$wp_customize->add_setting( 'lay_woocommerce_mouseover_button_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_mouseover_button_color',
   array(
     'label' => lay_t('Mouseover Button Color', 'customizer_woocommerce_buttons_mouseover_button_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_mouseover_button_color',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_button_mouseover_text_color',
   array(
      'default' => '#fff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_button_mouseover_text_color',
   array(
     'label' => lay_t('Mouseover Text Color', 'customizer_woocommerce_buttons_mouseover_text_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_button_mouseover_text_color',
      'priority'   => 20
   )
) );


$wp_customize->add_setting( 'lay_woocommerce_animate_mouseover_button_color',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_animate_mouseover_button_color',
        array(
            'label'          => lay_t('Animate Mouseover Color', 'customizer_woocommerce_buttons_animate_mouseover_color_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_animate_mouseover_button_color',
            'type'           => 'checkbox',
            'priority'       => 30,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_add_to_cart_button_height',
   array(
      'default' => 52,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_add_to_cart_button_height',
        array(
            'label'          => lay_t('Add to Cart Button Height (px)', 'customizer_woocommerce_buttons_add_to_cart_height_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_add_to_cart_button_height',
            'priority'       => 40,
            'input_attrs'    => array('step' => '1', 'min' => 0),
            'type'           => 'number',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_button_border_radius',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_button_border_radius',
        array(
            'label'          => lay_t('Roundness (px)', 'customizer_woocommerce_buttons_roundness_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_button_border_radius',
            'priority'       => 39,
            'input_attrs'    => array('step' => '1', 'min' => 0),
            'type'           => 'number',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_button_padding_left_right',
   array(
      'default' => 15,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_button_padding_left_right',
        array(
            'label'          => lay_t('Padding Left/Right (px)', 'customizer_woocommerce_buttons_padding_left_right_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_button_padding_left_right',
            'priority'       => 40,
            'input_attrs'    => array('step' => '1', 'min' => 0),
            'type'           => 'number',
        )
    )
);

// $wp_customize->add_setting( 'lay_woocommerce_button_padding_top_bottom',
//    array(
//       'default' => 11,
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control(
//     new WP_Customize_Control(
//         $wp_customize,
//         'lay_woocommerce_button_padding_top_bottom',
//         array(
//             'label'          => 'Padding Top/Bottom (px)',
//             'section'        => $section,
//             'settings'       => 'lay_woocommerce_button_padding_top_bottom',
//             'priority'       => 50,
//             'input_attrs'    => array('step' => '1', 'min' => 0),
//             'type'           => 'number',
// 						'description'    => 'This affects the following buttons: "Return" on empty cart page,  but it does not affect "Add To Cart" buttons'
//         )
//     )
// );
