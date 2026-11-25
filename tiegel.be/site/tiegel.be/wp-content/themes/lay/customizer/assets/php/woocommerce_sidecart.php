<?php
// todo: see if default values are ok
$section = 'lay_woocommerce_sidecart';


$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Side Cart', 'customizer_woocommerce_sidecart_title'),
      'priority' => 23,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
   )
);

$wp_customize->add_setting( 'scm-width',
   array(
      'default' => Lay_SideCart::$sidecart_style_options['scm-width'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'scm-width',
        array(
            'label'          => lay_t('Width (px)', 'customizer_woocommerce_sidecart_width_label'),
            'section'        => $section,
            'settings'       => 'scm-width',
            'input_attrs'    => array('step' => '1', 'min' => '0'),
            'type'           => 'number',
            'priority'       => 0
        )
    )
);

// Header
// $wp_customize->add_setting( 'sch-txtcolor',
//    array(
//       'default' => Lay_SideCart::$sidecart_style_options['sch-txtcolor'],
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control( new WP_Customize_Color_Control(
//    $wp_customize,
//    'sch-txtcolor',
//    array(
//       'label' => 'Header Text Color',
//       'section' => $section,
//       'settings' => 'sch-txtcolor',
//       'priority'   => 10
//    )
// ) );

// $wp_customize->add_setting( 'sch-bgcolor',
//    array(
//       'default' => Lay_SideCart::$sidecart_style_options['sch-bgcolor'],
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control( new WP_Customize_Color_Control(
//    $wp_customize,
//    'sch-bgcolor',
//    array(
//       'label' => 'Header Background Color',
//       'section' => $section,
//       'settings' => 'sch-bgcolor',
//       'priority'   => 20
//    )
// ) );

// Body
$wp_customize->add_setting( 'scb-bgcolor',
   array(
      'default' => Lay_SideCart::$sidecart_style_options['scb-bgcolor'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'scb-bgcolor',
   array(
     'label' => lay_t('Cart Background Color', 'customizer_woocommerce_sidecart_cart_background_color_label'),
      'section' => $section,
      'settings' => 'scb-bgcolor',
      'priority'   => 30
   )
) );

$wp_customize->add_setting( 'scb-txtcolor',
   array(
      'default' => Lay_SideCart::$sidecart_style_options['scb-txtcolor'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'scb-txtcolor',
   array(
     'label' => lay_t('Cart Text Color', 'customizer_woocommerce_sidecart_cart_text_color_label'),
      'section' => $section,
      'settings' => 'scb-txtcolor',
      'priority'   => 40
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_remove_item_text',
   array(
      'default' => 'Remove',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_remove_item_text',
        array(
            'label'          => lay_t('Remove Item Text', 'customizer_woocommerce_sidecart_remove_item_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_remove_item_text',
            'type'           => 'text',
            'priority'       => 40
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_remove_item_color',
   array(
      'default' => '#aeaeae',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_remove_item_color',
   array(
     'label' => lay_t('"Remove" Color', 'customizer_woocommerce_sidecart_remove_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_remove_item_color',
      'priority'   => 42
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_sidecart_image_position',
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
        'lay_woocommerce_sidecart_image_position',
        array(
            'label'          => lay_t('Product Image Position', 'customizer_woocommerce_sidecart_product_image_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_image_position',
            'type'           => 'select',
            'priority'       => 42,
            'choices'        => array(
               'left' => lay_t('Left', 'customizer_woocommerce_sidecart_image_position_left'),
               'right' => lay_t('Right', 'customizer_woocommerce_sidecart_image_position_right'),
            )
        )
    )
);

$wp_customize->add_setting( 'scbp-imgw',
   array(
      'default' => Lay_SideCart::$sidecart_style_options['scbp-imgw'],
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'scbp-imgw',
        array(
            'label'          => lay_t('Product Image Width (%)', 'customizer_woocommerce_sidecart_product_image_width_label'),
            'section'        => $section,
            'settings'       => 'scbp-imgw',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 45
        )
    )
);

// lines
$wp_customize->add_setting( 'lay_woocommerce_sidecart_line_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_sidecart_line_color',
   array(
     'label' => lay_t('Line Color', 'customizer_woocommerce_sidecart_line_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_sidecart_line_color',
      'priority'   => 50
   )
) );

// header textformat
// $wp_customize->add_setting( 'lay_woocommerce_sidecart_header_textformat',
//    array(
//       'default' => 'Default',
//       'type' => 'theme_mod',
//       'capability' => 'edit_theme_options',
//       'transport' => 'refresh',
//    )
// );
// $wp_customize->add_control(
//     new WP_Customize_Control(
//         $wp_customize,
//         'lay_woocommerce_sidecart_header_textformat',
//         array(
//             'label'          => 'Header Text Format',
//             'section'        => $section,
//             'settings'       => 'lay_woocommerce_sidecart_header_textformat',
//             'type'           => 'select',
//             'priority'       => 60,
//             'choices'        => Customizer::$textformatsSelect
//         )
//     )
// );

// body textformat
$wp_customize->add_setting( 'lay_woocommerce_sidecart_body_textformat',
   array(
      'default' => 'Shop_Cart',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_sidecart_body_textformat',
        array(
            'label'          => lay_t('Body Text Format', 'customizer_woocommerce_sidecart_body_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_body_textformat',
            'type'           => 'select',
            'priority'       => 60,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

// notices under subtotal textformat
// "Shipping, taxes, and discounts calculated at checkout."
$wp_customize->add_setting( 'lay_woocommerce_sidecart_notices_textformat',
   array(
      'default' => 'Shop_Small',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_sidecart_notices_textformat',
        array(
            'label'          => lay_t('Footer Text Format ("Shipping, taxes, and discounts calculated at checkout")', 'customizer_woocommerce_sidecart_footer_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_notices_textformat',
            'type'           => 'select',
            'priority'       => 60,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_sidecart_hide_footer_text',
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
        'lay_woocommerce_sidecart_hide_footer_text',
        array(
            'label'          => lay_t('Hide Footer Text', 'customizer_woocommerce_sidecart_hide_footer_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_hide_footer_text',
            'type'           => 'checkbox',
            'priority'       => 60,
        )
    )
);

// subtotal textformat
$wp_customize->add_setting( 'lay_woocommerce_sidecart_footer_textformat',
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
        'lay_woocommerce_sidecart_footer_textformat',
        array(
            'label'          => lay_t('Subtotal Text Format', 'customizer_woocommerce_sidecart_subtotal_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_footer_textformat',
            'type'           => 'select',
            'priority'       => 60,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_sidecart_background_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_sidecart_background_color',
   array(
     'label' => lay_t('Background Color', 'customizer_woocommerce_sidecart_background_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_sidecart_background_color',
      'priority'   => 70
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_sidecart_background_opacity',
   array(
      'default' => 30,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new Range_With_Number_Custom_Control(
        $wp_customize,
        'lay_woocommerce_sidecart_background_opacity',
        array(
            'label'          => lay_t('Background Opacity (%)', 'customizer_woocommerce_sidecart_background_opacity_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_sidecart_background_opacity',
            'input_attrs'    => array('step' => 1, 'min' => 0, 'max' => 100),
            'priority'       => 80
        )
    )
);