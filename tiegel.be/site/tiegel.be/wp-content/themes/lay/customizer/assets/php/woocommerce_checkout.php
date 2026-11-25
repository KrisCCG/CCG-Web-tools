<?php
$section = 'woocommerce_checkout';

// textformat
$wp_customize->add_setting( 'lay_woocommerce_checkout_product_textformat',
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
        'lay_woocommerce_checkout_product_textformat',
        array(
            'label'          => lay_t('Checkout Text Format', 'customizer_woocommerce_checkout_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_product_textformat',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$checkout_textformat = get_theme_mod('lay_woocommerce_checkout_product_textformat', 'Shop_Big');
$wp_customize->add_setting( 'lay_woocommerce_checkout_headlines_textformat',
   array(
      'default' => $checkout_textformat,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_checkout_headlines_textformat',
        array(
            'label'          => lay_t('Checkout Headlines Text Format', 'customizer_woocommerce_checkout_headlines_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_headlines_textformat',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$checkout_textformat = get_theme_mod('lay_woocommerce_checkout_product_textformat', 'Shop_Big');
$wp_customize->add_setting( 'lay_woocommerce_checkout_ship_to_different_address_textformat',
   array(
      'default' => $checkout_textformat,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_checkout_ship_to_different_address_textformat',
        array(
            'label'          => lay_t('"Ship to a different address?" Text Format', 'customizer_woocommerce_checkout_ship_to_different_address_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_ship_to_different_address_textformat',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_checkout_input_bg_color',
   array(
      'default' => '#ffffff',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_checkout_input_bg_color',
   array(
     'label' => lay_t('Inputs Background Color', 'customizer_woocommerce_checkout_inputs_background_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_checkout_input_bg_color',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_checkout_input_show_borders',
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
        'lay_woocommerce_checkout_input_show_borders',
        array(
            'label'          => lay_t('Show Input Borders', 'customizer_woocommerce_checkout_show_input_borders_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_input_show_borders',
            'type'           => 'checkbox',
            'priority'       => 20,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_checkout_input_border_color',
   array(
      'default' => '#000000',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_checkout_input_border_color',
   array(
     'label' => lay_t('Inputs Border Color', 'customizer_woocommerce_checkout_inputs_border_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_checkout_input_border_color',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_checkout_use_placeholder_instead_of_labels',
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
        'lay_woocommerce_checkout_use_placeholder_instead_of_labels',
        array(
            'label'          => lay_t('Use placeholder text instead of labels', 'customizer_woocommerce_checkout_use_placeholder_instead_of_labels_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_use_placeholder_instead_of_labels',
            'type'           => 'checkbox',
            'priority'       => 11,
            'description'   => lay_t('When enabled, checkout inputs show normal placeholder text and labels are hidden. When disabled, floating labels are used and placeholders are hidden.', 'customizer_woocommerce_checkout_use_placeholder_instead_of_labels_description')
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_checkout_input_placeholder_text_color',
   array(
      'default' => '#868686',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_checkout_input_placeholder_text_color',
   array(
     'label' => lay_t('Input Placeholder Text Color', 'customizer_woocommerce_checkout_input_placeholder_text_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_checkout_input_placeholder_text_color',
      'priority'   => 20
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_checkout_space_between_inputs',
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
        'lay_woocommerce_checkout_space_between_inputs',
        array(
            'label'          => lay_t('Space between Inputs (px)', 'customizer_woocommerce_checkout_space_between_inputs_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_space_between_inputs',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 20
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_checkout_additional_fields_gap',
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
        'lay_woocommerce_checkout_additional_fields_gap',
        array(
            'label'          => lay_t('Additional information Fields Gap (px)', 'customizer_woocommerce_checkout_additional_fields_gap_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_additional_fields_gap',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 20,
            'description' => lay_t('This gap may be needed if you have additional fields in your checkout form. If not, you can set it to 0.', 'customizer_woocommerce_checkout_additional_fields_gap_description')
        )
    )
);

// .shipping_address
$wp_customize->add_setting( 'lay_woocommerce_checkout_space_under_shipping_address',
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
        'lay_woocommerce_checkout_space_under_shipping_address',
        array(
            'label'          => lay_t('Space between Shipping Address and Order Notes (px)', 'customizer_woocommerce_checkout_shipping_order_notes_gap_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_space_under_shipping_address',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 20,
            'description' => lay_t('This space will be shown if "Ship to a different address?" is enabled.', 'customizer_woocommerce_checkout_shipping_address_gap_description')
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_checkout_coupon_gap',
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
        'lay_woocommerce_checkout_coupon_gap',
        array(
            'label'          => lay_t('Gap between coupon input field and button (px)', 'customizer_woocommerce_checkout_coupon_gap_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_coupon_gap',
            'input_attrs'    => array('step' => '1', 'min' => '0', 'max' => '100'),
            'type'           => 'number',
            'priority'       => 20,
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_checkout_order_review',
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
        'lay_woocommerce_checkout_order_review',
        array(
            'label'          => lay_t('Checkout Totals Text Format', 'customizer_woocommerce_checkout_totals_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_order_review',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_checkout_payment',
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
        'lay_woocommerce_checkout_payment',
        array(
            'label'          => lay_t('Checkout Payment Text Format', 'customizer_woocommerce_checkout_payment_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_checkout_payment',
            'type'           => 'select',
            'priority'       => 40,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);