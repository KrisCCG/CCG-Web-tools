<?php
$section = 'lay_woocommerce_cart_icon_mobile';

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Cart Icon Mobile', 'customizer_cart_icon_mobile_title'),
      'priority' => 16,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description' => lay_t('This is just a cart icon on the top right of your website.', 'customizer_woocommerce_cart_icon_mobile_description'),
   )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_hide',
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
        'lay_woocommerce_cart_icon_mobile_hide',
        array(
            'label'          => lay_t('Always hide icon', 'customizer_cart_icon_mobile_hide_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_hide',
            'description'    => lay_t('(Cart menupoint <a target="_blank" href="https://laytheme.com/documentation/shop-setup.html#cart-menu-point">needs to be added to mobile menu</a>)', 'customizer_woocommerce_cart_icon_mobile_menupoint_note'),
            'type'           => 'checkbox',
            'priority'       => 0,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_hide_empty',
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
        'lay_woocommerce_cart_icon_mobile_hide_empty',
        array(
            'label'          => lay_t('Hide Icon/Text when empty', 'customizer_cart_icon_mobile_hide_empty_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_hide_empty',
            'type'           => 'checkbox',
            'priority'       => 5,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_use_text',
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
        'lay_woocommerce_cart_icon_mobile_use_text',
        array(
            'label'          => lay_t('Use Text instead of Icon', 'customizer_cart_icon_mobile_use_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_use_text',
            'type'           => 'checkbox',
            'priority'       => 15,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_text',
   array(
      'default' => 'Cart',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_cart_icon_mobile_text',
        array(
            'label'          => lay_t('Text', 'customizer_cart_icon_mobile_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_text',
            'type'           => 'text',
            'priority'       => 15,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_text_spacetop',
   array(
      'default' => 10,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   ) 
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_cart_icon_mobile_text_spacetop',
        array(
            'label'          => lay_t('Text Space Top (px)', 'customizer_cart_icon_mobile_text_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_text_spacetop',
            'type'           => 'number',
            'priority'       => 15,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_cart_icon_mobile_textformat',
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
        'lay_woocommerce_cart_icon_mobile_textformat',
        array(
            'label'          => lay_t('Textformat', 'customizer_cart_icon_mobile_textformat_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_cart_icon_mobile_textformat',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);