<?php

function lay_show_add_to_cart_controls(){
    return get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0);
}

function lay_show_add_to_cart_textformat(){
    if( get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0) && get_theme_mod('lay_woocommerce_pt_use_custom_textformat', 0) ) {
        return true;
    }
    return false;
}

function lay_show_brands_settings(){
    if( get_theme_mod('lay_woocommerce_pt_show_brands', 0) ) {
        return true;
    }
    return false;
}

function lay_show_shortdescr_controls(){
    return get_theme_mod('lay_woocommerce_pt_show_shortdescr', 0);
}

$section = 'lay_woocommerce_product_thumbnails';

// taken from woocommerce
if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
    $regen_description = ''; // Nothing to report; Jetpack will handle magically.
} elseif ( apply_filters( 'woocommerce_background_image_regeneration', true ) && ! is_multisite() ) {
    $regen_description = lay_t('After publishing your changes, new image sizes will be generated automatically.', 'after_publishing_your_changes_new_image_sizes_will_be_generated_automatically');
} elseif ( apply_filters( 'woocommerce_background_image_regeneration', true ) && is_multisite() ) {
    /* translators: 1: tools URL 2: regen thumbs url */
    $regen_description = sprintf( lay_t('After publishing your changes, new image sizes may not be shown until you regenerate thumbnails. You can do this from the <a href="%1$s" target="_blank">tools section in WooCommerce</a> or by using a plugin such as <a href="%2$s" target="_blank">Regenerate Thumbnails</a>.', 'after_publishing_your_changes_new_image_sizes_may_not_be_shown_until_you_regenerate_thumbnails_you_can_do_this_from_the_tools_section_in_woocommerce_or_by_using_a_plugin_such_as_regenerate_thumbnails'), admin_url( 'admin.php?page=wc-status&tab=tools' ), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
} else {
    /* translators: %s: regen thumbs url */
    $regen_description = sprintf( lay_t('After publishing your changes, new image sizes may not be shown until you <a href="%s" target="_blank">Regenerate Thumbnails</a>.', 'after_publishing_your_changes_new_image_sizes_may_not_be_shown_until_you_regenerate_thumbnails'), 'https://en-gb.wordpress.org/plugins/regenerate-thumbnails/' );
}

$wp_customize->add_section(
    $section,
    array(
        'title'       => lay_t('Product Thumbnails', 'customizer_woocommerce_product_thumbnails_title'),
        'description' => $regen_description,
        'priority'    => 20,
        'panel'       => 'woocommerce',
    )
);

// https://wordpress.stackexchange.com/questions/258269/how-to-remove-customizers-section-and-move-control-straight-to-panel-using-chil
$control = $wp_customize->get_control( 'woocommerce_thumbnail_cropping' );
if ( $control ) {
    $control->section = $section;
}

// lay theme added
$wp_customize->add_setting( 'lay_woocommerce_image_type',
   array(
      'default' => 'woocommerce',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_image_type',
        array(
            'label'          => lay_t('Thumbnail image type', 'customizer_woocommerce_product_thumbnails_image_type_label'),
            'description'    => lay_t('"WooCommerce type" gives you options for cropping. "Lay Theme type" can potentially lead to faster loading and better image quality. "WooCommerce type" is recommended because all images should have the same aspect ratio for the design to work best.', 'customizer_woocommerce_thumbnail_type_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_image_type',
            'type'           => 'radio',
            'priority'       => -10,
            'choices' => array(
                'woocommerce' => lay_t('WooCommerce type', 'customizer_woocommerce_product_thumbnails_image_type_woocommerce'),
                'laytheme' => lay_t('Lay Theme type', 'customizer_woocommerce_product_thumbnails_image_type_laytheme'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_thumbnail_image_width',
    array(
    'default' => 300,
    'type' => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
    )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_thumbnail_image_width',
        array(
            'label'          => lay_t('Thumbnail resolution (width in px)', 'customizer_woocommerce_product_thumbnails_resolution_label'),
            'description'    => lay_t('The higher the number, the better the quality and bigger the file size. 600 is a good value.', 'customizer_woocommerce_thumbnail_resolution_description'),
            'section'        => $section,
            'settings'       => 'woocommerce_thumbnail_image_width',
            'type'           => 'number',
            'priority'       => 4,
        )
    )
);

// inner space
$wp_customize->add_setting( 'lay_woocommerce_pt_inner_space',
   array(
      'default' => 10,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_inner_space',
        array(
            'label'          => lay_t('Inner Space (Left, Right, Bottom) (px)', 'customizer_woocommerce_product_thumbnails_inner_space_label'),
            'description'    => lay_t('Title, Short Description, Price, Out of Stock can all have a position on the image. This is the space between the edge of the image and those texts.', 'customizer_woocommerce_thumbnail_inner_space_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_inner_space',
            'type'           => 'number',
            'priority'       => 6,
        )
    )
);


// product title textformat
$wp_customize->add_setting( 'lay_woocommerce_pt_textformat',
   array(
      'default' => 'Shop_Product_Thumbnails',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_textformat',
        array(
            'label'          => lay_t('Title Text Format', 'customizer_woocommerce_product_thumbnails_title_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_textformat',
            'type'           => 'select',
            'priority'       => 10,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_pt_title_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_title_position',
        array(
            'label'          => lay_t('Title Position', 'customizer_woocommerce_product_thumbnails_title_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_title_position',
            'type'           => 'select',
            'choices'        => array(
                'on-image-top-left' => lay_t('on image top left', 'customizer_woocommerce_product_thumbnails_title_position_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_woocommerce_product_thumbnails_title_position_top_right'),
                'on-image' => lay_t('on image center', 'customizer_woocommerce_product_thumbnails_title_position_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_woocommerce_product_thumbnails_title_position_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_woocommerce_product_thumbnails_title_position_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_woocommerce_product_thumbnails_title_position_below'),
            ),
            'priority' => 12
        )
    )
);


// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_spacetop',
   array(
      'default' => '12',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_spacetop',
        array(
            'label'          => lay_t('Title Space Top (px)', 'customizer_woocommerce_product_thumbnails_title_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_spacetop',
            'type'           => 'number',
            'priority'       => 20,
        )
    )
);


// show short descr
$wp_customize->add_setting( 'lay_woocommerce_pt_show_shortdescr',
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
        'lay_woocommerce_pt_show_shortdescr',
        array(
            'label'          => lay_t('Show Short Description', 'customizer_woocommerce_product_thumbnails_show_short_description_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_show_shortdescr',
            'type'           => 'checkbox',
            'priority'       => 21,
        )
    )
);


// product short descr textformat
$wp_customize->add_setting( 'lay_woocommerce_pt_shortdescr_textformat',
   array(
      'default' => 'Shop_Product_Thumbnails',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_shortdescr_textformat',
        array(
            'label'          => lay_t('Short Description Text Format', 'customizer_woocommerce_product_thumbnails_short_description_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_shortdescr_textformat',
            'type'           => 'select',
            'priority'       => 25,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'lay_show_shortdescr_controls',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_shortdescr_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_shortdescr_position',
        array(
            'label'          => lay_t('Short Description Position', 'customizer_woocommerce_product_thumbnails_short_description_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_shortdescr_position',
            'type'           => 'select',
            'choices'        => array(
                'on-image-top-left' => lay_t('on image top left', 'customizer_woocommerce_product_thumbnails_short_description_position_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_woocommerce_product_thumbnails_short_description_position_top_right'),
                'on-image' => lay_t('on image center', 'customizer_woocommerce_product_thumbnails_short_description_position_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_woocommerce_product_thumbnails_short_description_position_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_woocommerce_product_thumbnails_short_description_position_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_woocommerce_product_thumbnails_short_description_position_below'),
            ),
            'priority' => 27
        )
    )
);

// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_shortdescr_spacetop',
   array(
      'default' => '12',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_shortdescr_spacetop',
        array(
            'label'          => lay_t('Short Description Space Top (px)', 'customizer_woocommerce_product_thumbnails_short_description_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_shortdescr_spacetop',
            'type'           => 'number',
            'priority'       => 28,
            'active_callback' => 'lay_show_shortdescr_controls',
        )
    )
);

// product price textformat
$wp_customize->add_setting( 'lay_woocommerce_pt_price_textformat',
   array(
      'default' => 'Shop_Product_Thumbnails',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_price_textformat',
        array(
            'label'          => lay_t('Price Text Format', 'customizer_woocommerce_product_thumbnails_price_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_price_textformat',
            'type'           => 'select',
            'priority'       => 30,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_price_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_price_position',
        array(
            'label'          => lay_t('Price Position', 'customizer_woocommerce_product_thumbnails_price_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_price_position',
            'type'           => 'select',
            'choices'        => array(
                'on-image-top-left' => lay_t('on image top left', 'customizer_woocommerce_product_thumbnails_price_position_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_woocommerce_product_thumbnails_price_position_top_right'),
                'on-image' => lay_t('on image center', 'customizer_woocommerce_product_thumbnails_price_position_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_woocommerce_product_thumbnails_price_position_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_woocommerce_product_thumbnails_price_position_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_woocommerce_product_thumbnails_price_position_below'),
            ),
            'priority' => 31
        )
    )
);

// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_price_spacetop',
   array(
      'default' => '0',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_price_spacetop',
        array(
            'label'          => lay_t('Price Space Top (px)', 'customizer_woocommerce_product_thumbnails_price_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_price_spacetop',
            'type'           => 'number',
            'priority'       => 35,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_outofstock_text',
   array(
      'default' => 'Out of stock',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_outofstock_text',
        array(
            'label'          => lay_t('"Out of Stock" Text', 'customizer_woocommerce_product_thumbnails_out_of_stock_text_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_outofstock_text',
            'type'           => 'text',
            'priority'       => 35,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_outofstock_textformat',
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
        'lay_woocommerce_pt_outofstock_textformat',
        array(
            'label'          => lay_t('"Out of Stock" Text Format', 'customizer_woocommerce_product_thumbnails_out_of_stock_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_outofstock_textformat',
            'type'           => 'select',
            'priority'       => 35,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_outofstock_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_outofstock_position',
        array(
            'label'          => lay_t('"Out of Stock" Position', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_outofstock_position',
            'type'           => 'select',
            'choices'        => array(
                'on-image-top-left' => lay_t('on image top left', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_top_right'),
                'on-image' => lay_t('on image center', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_woocommerce_product_thumbnails_out_of_stock_position_below'),
            ),
            'priority' => 36
        )
    )
);

// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_outofstock_spacetop',
   array(
      'default' => '2',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_outofstock_spacetop',
        array(
            'label'          => lay_t('"Out of stock" Space Top (px)', 'customizer_woocommerce_product_thumbnails_out_of_stock_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_outofstock_spacetop',
            'type'           => 'number',
            'priority'       => 37,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_show_add_to_cart_button',
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
        'lay_woocommerce_pt_show_add_to_cart_button',
        array(
            'label'          => lay_t('Show Add to Cart Button', 'customizer_woocommerce_product_thumbnails_show_add_to_cart_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_show_add_to_cart_button',
            'type'           => 'checkbox',
            'priority'       => 50,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_add_to_cart_position',
   array(
      'default' => 'under',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_add_to_cart_position',
        array(
            'label'          => lay_t('Add to Cart Position', 'customizer_woocommerce_product_thumbnails_add_to_cart_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_add_to_cart_position',
            'type'           => 'select',
            'choices'        => array(
                'under' => lay_t('Under', 'customizer_woocommerce_product_thumbnails_add_to_cart_position_under'),
                'left' => lay_t('Left of Text', 'customizer_woocommerce_product_thumbnails_add_to_cart_position_left'),
                'right' => lay_t('Right of Text', 'customizer_woocommerce_product_thumbnails_add_to_cart_position_right'),
            ),
            'priority'       => 51,
            'active_callback' => function() {
                return get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0);
            },
        )
    )
);

// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_cart_button_spacetop',
   array(
      'default' => '15',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_cart_button_spacetop',
        array(
            'label'          => lay_t('Add to Cart Button Space Top (px)', 'customizer_woocommerce_product_thumbnails_add_to_cart_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_cart_button_spacetop',
            'type'           => 'number',
            'priority'       => 51,
            'active_callback' => 'lay_show_add_to_cart_controls',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_use_custom_textformat',
   array(
      'default' => 1,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_use_custom_textformat',
        array(
            'label'          => lay_t('Use Custom Textformat for Add to Cart Button', 'customizer_woocommerce_product_thumbnails_add_to_cart_use_custom_textformat_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_use_custom_textformat',
            'type'           => 'checkbox',
            'priority'       => 52,
            'active_callback' => 'lay_show_add_to_cart_controls',
        )
    )
);

// textformat
$wp_customize->add_setting( 'lay_woocommerce_pt_add_to_cart_textformat',
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
        'lay_woocommerce_pt_add_to_cart_textformat',
        array(
            'label'          => lay_t('Add to Cart Button Text Format', 'customizer_woocommerce_product_thumbnails_add_to_cart_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_add_to_cart_textformat',
            'type'           => 'select',
            'priority'       => 70,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'lay_show_add_to_cart_textformat',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_show_brands',
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
        'lay_woocommerce_pt_show_brands',
        array(
            'label'          => lay_t('Show Brands', 'customizer_woocommerce_product_thumbnails_show_brands_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_show_brands',
            'type'           => 'checkbox',
            'priority'       => 80,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_pt_show_brands',
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
        'lay_woocommerce_pt_show_brands',
        array(
            'label'          => lay_t('Show Brands', 'customizer_woocommerce_product_thumbnails_show_brands_products_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_show_brands',
            'type'           => 'checkbox',
            'priority'       => 90,
        )
    )
);

// textformat
$wp_customize->add_setting( 'lay_woocommerce_pt_brands_textformat',
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
        'lay_woocommerce_pt_brands_textformat',
        array(
            'label'          => lay_t('Brands Text Format', 'customizer_woocommerce_product_thumbnails_brands_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_brands_textformat',
            'type'           => 'select',
            'priority'       => 100,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'lay_show_brands_settings',
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_pt_brands_position',
   array(
      'default' => 'below-image',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_brands_position',
        array(
            'label'          => lay_t('Brands Position', 'customizer_woocommerce_product_thumbnails_brands_position_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_brands_position',
            'type'           => 'select',
            'choices'        => array(
                'on-image-top-left' => lay_t('on image top left', 'customizer_woocommerce_product_thumbnails_brands_position_top_left'),
                'on-image-top-right' => lay_t('on image top right', 'customizer_woocommerce_product_thumbnails_brands_position_top_right'),
                'on-image' => lay_t('on image center', 'customizer_woocommerce_product_thumbnails_brands_position_center'),
                'on-image-bottom-left' => lay_t('on image bottom left', 'customizer_woocommerce_product_thumbnails_brands_position_bottom_left'),
                'on-image-bottom-right' => lay_t('on image bottom right', 'customizer_woocommerce_product_thumbnails_brands_position_bottom_right'),
                'below-image' => lay_t('below image', 'customizer_woocommerce_product_thumbnails_brands_position_below'),
            ),
            'priority' => 105,
            'active_callback' => 'lay_show_brands_settings'
        )
    )
);

// spacetop
$wp_customize->add_setting( 'lay_woocommerce_pt_brands_spacetop',
   array(
      'default' => '10',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_pt_brands_spacetop',
        array(
            'label'          => lay_t('Brands Space Top (px)', 'customizer_woocommerce_product_thumbnails_brands_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_pt_brands_spacetop',
            'type'           => 'number',
            'priority'       => 110,
            'active_callback' => 'lay_show_brands_settings',
        )
    )
);