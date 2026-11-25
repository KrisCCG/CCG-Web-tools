<?php

// note: when adding default shopformats here, they also need to be added to array in row_view.js of formatsmanager
// and in formatsmanager.php so they are added even if they dont exist in the json yet

$section = 'lay_woocommerce_order_received';
$descr = "";

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Order Received Page', 'customizer_woocommerce_order_received_title'),
      'priority' => 99,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description'    => $descr
   )
);


$wp_customize->add_setting( 'lay_woocommerce_orderpage_style',
   array(
      'default' => 'style_1',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_orderpage_style',
        array(
            'label'          => lay_t('Style', 'customizer_woocommerce_order_received_style_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_orderpage_style',
            'type'           => 'radio',
            'priority'       => 0,
            'choices'        => array(
               'style_1' => lay_t('Style 1', 'customizer_woocommerce_order_received_style_1'),
               'style_2' => lay_t('Style 2', 'customizer_woocommerce_order_received_style_2'),
            )
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_orderpage_thankyou',
   array(
      'default' => 'Shop_Order_Received',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_orderpage_thankyou',
        array(
            'label'          => lay_t('Thank You Text Format', 'customizer_woocommerce_order_received_thank_you_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_orderpage_thankyou',
            'type'           => 'select',
            'priority'       => 0,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_orderpage_titles',
   array(
      'default' => 'Shop_Order_Received',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_orderpage_titles',
        array(
            'label'          => lay_t('Titles Text Format', 'customizer_woocommerce_order_received_titles_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_orderpage_titles',
            'type'           => 'select',
            'priority'       => 5,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_orderpage',
   array(
      'default' => 'Shop_Order_Received',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_orderpage',
        array(
            'label'          => lay_t('Copy Text Format', 'customizer_woocommerce_order_received_copy_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_orderpage',
            'type'           => 'select',
            'priority'       => 5,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);