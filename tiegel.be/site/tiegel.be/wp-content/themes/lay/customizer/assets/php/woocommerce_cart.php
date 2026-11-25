<?php
$section = 'lay_woocommerce_cart';


$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Cart', 'customizer_woocommerce_cart_title'),
      'priority' => 15,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_proceed_to_checkout_button_text',
   array(
      'default' => 'Proceed to checkout',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_proceed_to_checkout_button_text',
        array(
            'label'          => lay_t('Button Text', 'customizer_woocommerce_cart_button_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_proceed_to_checkout_button_text',
            'type'           => 'text',
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_normal_cart_product_list_bg_color',
   array(
      'default' => '#ffffff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_normal_cart_product_list_bg_color',
   array(
     'label' => lay_t('Product List Background Color', 'customizer_woocommerce_cart_product_list_background_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_normal_cart_product_list_bg_color',
      'priority'   => 42
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_normal_cart_remove_item_color',
   array(
      'default' => '#aeaeae',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_normal_cart_remove_item_color',
   array(
     'label' => lay_t('"Remove" Color', 'customizer_woocommerce_cart_remove_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_normal_cart_remove_item_color',
      'priority'   => 42
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_cart_product_image_width',
   array(
      'default' => 120,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_cart_product_image_width',
        array(
            'label'          => lay_t('Product Image Width (px)', 'customizer_woocommerce_cart_product_image_width_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_product_image_width',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 45
        )
    )
);

// lines

$wp_customize->add_setting( 'lay_woocommerce_cart_show_line',
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
        'lay_woocommerce_cart_show_line',
        array(
            'label'          => lay_t('Show Border', 'customizer_woocommerce_cart_show_border_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_show_line',
            'type'           => 'checkbox',
            'priority'       => 50,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_line_color',
   array(
      'default' => '#000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_cart_line_color',
   array(
     'label' => lay_t('Border Color', 'customizer_woocommerce_cart_border_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_cart_line_color',
      'priority'   => 50
   )
) );

// products textformat
$wp_customize->add_setting( 'lay_woocommerce_cart_product_textformat',
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
        'lay_woocommerce_cart_product_textformat',
        array(
            'label'          => lay_t('Product List Text Format', 'customizer_woocommerce_cart_product_list_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_product_textformat',
            'type'           => 'select',
            'priority'       => 60,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

// totals textformat
$wp_customize->add_setting( 'lay_woocommerce_cart_collaterals_textformat',
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
        'lay_woocommerce_cart_collaterals_textformat',
        array(
            'label'          => lay_t('Product Totals Format', 'customizer_woocommerce_cart_product_totals_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_collaterals_textformat',
            'type'           => 'select',
            'priority'       => 60,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

