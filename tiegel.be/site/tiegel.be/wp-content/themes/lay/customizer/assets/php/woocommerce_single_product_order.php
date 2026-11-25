<?php

$section = 'lay_woocommerce_single_product_order';
$descr = lay_t('Change the order of Title, Price, Add to Cart button and more below. To automatically preview a different product here, go to  "Products" → "Sorting" and move a product to the top.', 'customizer_woocommerce_single_product_order_description');

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Single Product Elements Order', 'customizer_woocommerce_single_product_order_title'),
      'priority' => 6,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description'    => $descr
   )
);

// Keep all settings for backwards compatibility
$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_breadcrumbs',
   array(
      'default' => 0,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_title',
   array(
      'default' => 1,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_brands',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_short_description',
   array(
      'default' => 3,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_price',
   array(
      'default' => 4,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_form',
   array(
      'default' => 5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_description_outside_tabs',
   array(
      'default' => 6,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_tabs',
   array(
      'default' => 7,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_order_other',
   array(
      'default' => 8,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);

// Single drag-and-drop control that manages all order settings
$wp_customize->add_control(
    new Order_Custom_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_order_control',
        array(
            'label'          => lay_t('Elements Order', 'customizer_woocommerce_single_product_order_elements_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_order_title', // Primary setting for WordPress
            'priority'       => 0,
            'order_settings' => array(
                'breadcrumbs' => 'lay_woocommerce_singleproduct_order_breadcrumbs',
                'title' => 'lay_woocommerce_singleproduct_order_title',
                'brands' => 'lay_woocommerce_singleproduct_order_brands',
                'short_description' => 'lay_woocommerce_singleproduct_order_short_description',
                'price' => 'lay_woocommerce_singleproduct_order_price',
                'form' => 'lay_woocommerce_singleproduct_order_form',
                'description_outside_tabs' => 'lay_woocommerce_singleproduct_order_description_outside_tabs',
                'tabs' => 'lay_woocommerce_singleproduct_order_tabs',
                'other' => 'lay_woocommerce_singleproduct_order_other',
            ),
            'order_labels' => array(
                'breadcrumbs' => lay_t('Breadcrumbs', 'customizer_woocommerce_single_product_order_breadcrumbs_label'),
                'title' => lay_t('Title', 'customizer_woocommerce_single_product_order_title_label'),
                'brands' => lay_t('Brands', 'customizer_woocommerce_single_product_order_brands_label'),
                'short_description' => lay_t('Short Description', 'customizer_woocommerce_single_product_order_short_description_label'),
                'price' => lay_t('Price', 'customizer_woocommerce_single_product_order_price_label'),
                'form' => lay_t('Add to Cart, Variations, Quantity', 'customizer_woocommerce_single_product_order_form_label'),
                'description_outside_tabs' => lay_t('Description outside of Tabs', 'customizer_woocommerce_single_product_order_description_outside_tabs_label'),
                'tabs' => lay_t('Tabs or Description', 'customizer_woocommerce_single_product_order_tabs_label'),
                'other' => lay_t('Other (plugins like express checkout)', 'customizer_woocommerce_single_product_order_other_label'),
            ),
        )
    )
);