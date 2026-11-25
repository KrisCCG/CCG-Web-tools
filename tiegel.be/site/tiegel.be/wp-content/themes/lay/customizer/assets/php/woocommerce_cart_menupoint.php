<?php
$section = 'lay_woocommerce_cart_menupoint';


$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Cart Menupoint', 'customizer_cart_menupoint_title'),
      'priority' => 16,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description' => lay_t('<a target="_blank" href="https://laytheme.com/documentation/shop-setup.html#cart-menu-point">How to add a Cart Menupoint</a>', 'customizer_woocommerce_cart_menupoint_description'),
   )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_menupoint_hide_empty',
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
        'lay_woocommerce_cart_menupoint_hide_empty',
        array(
            'label'          => lay_t('Hide when empty', 'customizer_cart_menupoint_hide_empty_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_menupoint_hide_empty',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_menupoint_hide_empty_mobile',
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
        'lay_woocommerce_cart_menupoint_hide_empty_mobile',
        array(
            'label'          => lay_t('Hide when empty for mobile', 'customizer_cart_menupoint_hide_empty_mobile_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_menupoint_hide_empty_mobile',
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);