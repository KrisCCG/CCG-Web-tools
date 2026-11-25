<?php

function show_image_space_settings(){
    $layout_type = get_theme_mod('lay_woocommerce_singleproduct_layout_type', 'one_image_per_row');
    
    return ($layout_type == 'one_image_per_row' || $layout_type == 'carousel') ? true : false;
}

function show_space_between_product_images(){
    $layout_type = get_theme_mod('lay_woocommerce_singleproduct_layout_type', 'one_image_per_row');
    return ($layout_type == 'one_image_per_row' || $layout_type == 'two_images_per_row') ? true : false;
}

function show_breadcrumbs(){
    return get_theme_mod('lay_woocommerce_singleproduct_show_breadcrumbs', false);
}

function wc_sp_is_carousel(){
    $layout_type = get_theme_mod('lay_woocommerce_singleproduct_layout_type', 'one_image_per_row');
    $val = $layout_type == 'carousel' || $layout_type == 'carousel_with_thumbnails' ? true : false;
    return $val;
}

function wc_sp_is_carousel_and_custom_height(){
    $layout_type = get_theme_mod('lay_woocommerce_singleproduct_layout_type', 'one_image_per_row');
    $val = $layout_type == 'carousel' || $layout_type == 'carousel_with_thumbnails' ? true : false;
    $custom_height = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_height', 'auto');
    return ($val && $custom_height == 'custom') ? true : false;
}

function wc_sp_any_carousel_for_dots(){
    if ( wc_sp_is_carousel() ) {
        return true;
    }
    $mobile = get_theme_mod( 'lay_woocommerce_singleproduct_layout_type_mobile', 'carousel' );
    return $mobile === 'carousel';
}

function show_sticky_settings(){
    return get_theme_mod('lay_woocommerce_singleproduct_sticky', true);
}

function show_brands(){
    return get_theme_mod('lay_woocommerce_singleproduct_show_brands', false);
}

$section = 'lay_woocommerce_single_product';
$descr = lay_t('To automatically preview a different product here, go to  "Products" → "Sorting" and move a product to the top.', 'customizer_woocommerce_single_product_section_description');

$wp_customize->add_section( $section,
   array(
      'title' => lay_t('Single Product', 'customizer_woocommerce_single_product_title'),
      'priority' => 5,
      'capability' => 'edit_theme_options',
      'panel' => 'woocommerce',
      'description'    => $descr
   )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_page_max_width',
   array(
      'default' => 1920,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_page_max_width',
        array(
            'label'          => lay_t('Max width of page (px)', 'customizer_woocommerce_single_product_page_max_width_label'),
            'description'    => lay_t('Set to 0 to turn off', 'customizer_woocommerce_single_product_page_max_width_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_page_max_width',
            'type'           => 'number',
            'priority'       => -2,
            'input_attrs'    => array(
                'min'  => 0,
                'step' => 1,
            ),
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_center_page',
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
        'lay_woocommerce_singleproduct_center_page',
        array(
            'label'          => lay_t('Center page on bigger screens', 'customizer_woocommerce_single_product_center_page_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_center_page',
            'type'           => 'checkbox',
            'priority'       => -1,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_layout_type',
   array(
      'default' => 'one_image_per_row',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_layout_type',
        array(
            'label'          => lay_t('Layout Type', 'customizer_woocommerce_single_product_layout_type_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_layout_type',
            'type'           => 'radio',
            'priority'       => 0,
            'choices'        => array(
               'two_images_per_row' => lay_t('Two Images per Row', 'customizer_woocommerce_single_product_layout_two_images'),
               'one_image_per_row' => lay_t('One Image per Row', 'customizer_woocommerce_single_product_layout_one_image'),
               'carousel' => lay_t('Carousel', 'customizer_woocommerce_single_product_layout_carousel'),
               'carousel_with_thumbnails' => lay_t('Carousel with Thumbnails', 'customizer_woocommerce_single_product_layout_carousel_thumbnails'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_space_between_images_mu',
   array(
      'default' => 'vw',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_setting( 'lay_woocommerce_singleproduct_space_between_images',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new Spacing_With_Unit_Custom_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_space_between_images',
        array(
            'label'          => lay_t('Space between Images', 'customizer_woocommerce_single_product_space_between_images_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_space_between_images',
            'unit_setting'   => 'lay_woocommerce_singleproduct_space_between_images_mu',
            'unit_choices'   => array('px' => 'px', 'vw' => '%'),
            'step'           => '0.1',
            'priority'       => 0,
            'active_callback' => 'show_space_between_product_images',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_carousel_buttons',
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
        'lay_woocommerce_singleproduct_show_carousel_buttons',
        array(
            'label'          => lay_t('Show Carousel Prev/Next Buttons', 'customizer_woocommerce_single_product_show_carousel_buttons_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_carousel_buttons',
            'type'           => 'checkbox',
            'priority'       => 1,
            'active_callback' => 'wc_sp_is_carousel',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_desktop_carousel_slide_duration',
   array(
      'default' => 300,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_desktop_carousel_slide_duration',
        array(
            'label'          => lay_t('Desktop Carousel Slide Duration', 'customizer_woocommerce_single_product_desktop_carousel_slide_duration_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_desktop_carousel_slide_duration',
            'type'           => 'number',
            'priority'       => 0,
            'active_callback' => 'wc_sp_is_carousel',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_desktop_carousel_height',
   array(
      'default' => 'auto',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_desktop_carousel_height',
        array(
            'label'          => lay_t('Desktop Carousel Height', 'customizer_woocommerce_single_product_desktop_carousel_height_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_desktop_carousel_height',
            'type'           => 'radio',
            'priority'       => 0,
            'choices'        => array(
                'auto' => lay_t('Auto', 'customizer_woocommerce_single_product_desktop_carousel_height_auto'),
                'custom' => lay_t('Custom', 'customizer_woocommerce_single_product_desktop_carousel_height_custom'),
            ),
            'active_callback' => 'wc_sp_is_carousel',
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_singleproduct_desktop_carousel_loop',
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
        'lay_woocommerce_singleproduct_desktop_carousel_loop',
        array(
            'label'          => lay_t('Desktop Carousel Loop Slides', 'customizer_woocommerce_single_product_desktop_carousel_loop_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_desktop_carousel_loop',
            'type'           => 'checkbox',
            'priority'       => 0,
            'active_callback' => 'wc_sp_is_carousel',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_desktop_carousel_custom_height',
   array(
      'default' => 'calc(100vh - 200px)',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_desktop_carousel_custom_height',
        array(
            'label'          => lay_t('Desktop Carousel Custom Height', 'customizer_woocommerce_single_product_desktop_carousel_custom_height_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_desktop_carousel_custom_height',
            'type'           => 'text',
            'priority'       => 0,
            'active_callback' => 'wc_sp_is_carousel_and_custom_height',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_layout_type_mobile',
   array(
      'default' => 'carousel',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh'
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_layout_type_mobile',
        array(
            'label'          => lay_t('Mobile Layout Type', 'customizer_woocommerce_single_product_mobile_layout_type_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_layout_type_mobile',
            'type'           => 'radio',
            'priority'       => 0,
            'choices'        => array(
                'carousel' => lay_t('Carousel', 'customizer_woocommerce_single_product_mobile_layout_carousel'),
               'images' => lay_t('Stacked Images', 'customizer_woocommerce_single_product_mobile_layout_images'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_carousel_dots_color',
   array(
      'default' => '#333333',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_singleproduct_carousel_dots_color',
   array(
     'label' => lay_t('Carousel Dots Color', 'customizer_woocommerce_single_product_carousel_dots_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_singleproduct_carousel_dots_color',
      'priority'   => 0,
      'active_callback' => 'wc_sp_any_carousel_for_dots'
   )
) );

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_text_spacetop',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_text_spacetop',
        array(
            'label'          => lay_t('Text Container Space Top(px)', 'customizer_woocommerce_single_product_text_container_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_text_spacetop',
            'type'           => 'number',
            'priority'       => 0
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_breadcrumbs',
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
        'lay_woocommerce_singleproduct_show_breadcrumbs',
        array(
            'label'          => lay_t('Show Breadcrumbs', 'customizer_woocommerce_single_product_show_breadcrumbs_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_breadcrumbs',
            'type'           => 'checkbox',
            'priority'       => 1,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_breadcrumbs_textformat',
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
        'lay_woocommerce_singleproduct_breadcrumbs_textformat',
        array(
            'label'          => lay_t('Breadcrumbs Text Format', 'customizer_woocommerce_single_product_breadcrumbs_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_breadcrumbs_textformat',
            'type'           => 'select',
            'priority'       => 1,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'show_breadcrumbs'
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_breadcrumbs_space_bottom',
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
        'lay_woocommerce_singleproduct_breadcrumbs_space_bottom',
        array(
            'label'          => lay_t('Breadcrumbs Space Bottom (em)', 'customizer_woocommerce_single_product_breadcrumbs_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_breadcrumbs_space_bottom',
            'type'           => 'number',
            'priority'       => 1,
            'active_callback' => 'show_breadcrumbs'
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_breadcrumbs_color',
   array(
      'default' => '#acacac',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_singleproduct_breadcrumbs_color',
   array(
     'label' => lay_t('Breadcrumbs Color', 'customizer_woocommerce_single_product_breadcrumbs_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_singleproduct_breadcrumbs_color',
      'priority'   => 1,
      'active_callback' => 'show_breadcrumbs'
   )
) );

// brands

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_brands',
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
        'lay_woocommerce_singleproduct_show_brands',
        array(
            'label'          => lay_t('Show Brands', 'customizer_woocommerce_single_product_show_brands_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_brands',
            'type'           => 'checkbox',
            'priority'       => 2,
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_singleproduct_brands_textformat',
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
        'lay_woocommerce_singleproduct_brands_textformat',
        array(
            'label'          => lay_t('Brands Text Format', 'customizer_woocommerce_single_product_brands_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_brands_textformat',
            'type'           => 'select',
            'priority'       => 2,
            'choices'        => Customizer::$textformatsSelect,
            'active_callback' => 'show_brands'
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_singleproduct_brands_space_bottom',
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
        'lay_woocommerce_singleproduct_brands_space_bottom',
        array(
            'label'          => lay_t('Brands Space Bottom (em)', 'customizer_woocommerce_single_product_brands_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_brands_space_bottom',
            'type'           => 'number',
            'priority'       => 2,
            'active_callback' => 'show_brands'
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_brands_color',
   array(
      'default' => '#acacac',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control( new WP_Customize_Color_Control(
   $wp_customize,
   'lay_woocommerce_singleproduct_brands_color',
   array(
     'label' => lay_t('Brands Color', 'customizer_woocommerce_single_product_brands_color_label'),
      'section' => $section,
      'settings' => 'lay_woocommerce_singleproduct_brands_color',
      'priority'   => 2,
      'active_callback' => 'show_brands'
   )
) );

// 

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_quantity_input',
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
        'lay_woocommerce_singleproduct_show_quantity_input',
        array(
            'label'          => lay_t('Show Quantity Input', 'customizer_woocommerce_single_product_show_quantity_input_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_quantity_input',
            'type'           => 'checkbox',
            'priority'       => 2,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_first_image',
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
        'lay_woocommerce_singleproduct_show_first_image',
        array(
            'label'          => lay_t('Show First Image', 'customizer_woocommerce_single_product_show_first_image_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_first_image',
            'type'           => 'checkbox',
            'priority'       => 2,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_only_show_variation_images',
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
        'lay_woocommerce_singleproduct_only_show_variation_images',
        array(
            'label'          => lay_t('When Variation is selected, only show the variation images', 'customizer_woocommerce_single_product_variation_only_images_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_only_show_variation_images',
            'type'           => 'checkbox',
            'priority'       => 2,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_text_width_2',
   array(
      'default' => 30,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_text_width_2',
        array(
            'label'          => lay_t('Description Text Width (%)', 'customizer_woocommerce_single_product_description_text_width_label'),
            'description'    => lay_t('This is the text width when your product only has text and not text in product tabs', 'customizer_woocommerce_single_product_text_width_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_text_width_2',
            'type'           => 'number',
            'priority'       => 2
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_quantity_type',
   array(
      'default' => 'big',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_quantity_type',
        array(
            'label'          => lay_t('Variation and Quantity Input Type', 'customizer_woocommerce_single_product_variation_quantity_type_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_quantity_type',
            'type'           => 'select',
            'priority'       => 2,
            'choices'        => array(
                'big' => lay_t('Big Dropdown', 'customizer_woocommerce_single_product_variation_quantity_big_dropdown'),
                'small' => lay_t('Small Dropdown', 'customizer_woocommerce_single_product_variation_quantity_small_dropdown'),
                'round' => lay_t('Round Dropdown', 'customizer_woocommerce_single_product_variation_quantity_round_dropdown'),
                'big-swatches' => lay_t('Big Swatches', 'customizer_woocommerce_single_product_variation_quantity_big_swatches'),
                'small-swatches' => lay_t('Small Swatches', 'customizer_woocommerce_single_product_variation_quantity_small_swatches'),
                'round-swatches' => lay_t('Round Swatches', 'customizer_woocommerce_single_product_variation_quantity_round_swatches'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_image_width',
   array(
      'default' => 48.9,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_image_width',
        array(
            'label'          => lay_t('Image Width (%)', 'customizer_woocommerce_single_product_image_width_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_image_width',
            'type'           => 'number',
            'priority'       => 2,
            'active_callback' => 'show_image_space_settings',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_image_space_left',
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
        'lay_woocommerce_singleproduct_image_space_left',
        array(
            'label'          => lay_t('Inner Space Left (%)', 'customizer_woocommerce_single_product_image_space_left_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_image_space_left',
            'type'           => 'number',
            'priority'       => 3,
            'active_callback' => 'show_image_space_settings',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_outer_space_left',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_outer_space_left',
        array(
            'label'          => lay_t('Outer Space Left (%)', 'customizer_woocommerce_single_product_outer_space_left_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_outer_space_left',
            'type'           => 'number',
            'priority'       => 4,
            'input_attrs'    => array(
                'min'  => 0,
                'step' => 0.1,
            ),
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_outer_space_right',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_outer_space_right',
        array(
            'label'          => lay_t('Outer Space Right (%)', 'customizer_woocommerce_single_product_outer_space_right_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_outer_space_right',
            'type'           => 'number',
            'priority'       => 5,
            'input_attrs'    => array(
                'min'  => 0,
                'step' => 0.1,
            ),
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_sticky',
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
        'lay_woocommerce_singleproduct_sticky',
        array(
            'label'          => lay_t('Make Text Sticky', 'customizer_woocommerce_single_product_make_text_sticky_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_sticky',
            'type'           => 'checkbox',
            'priority'       => 6,
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_text_sticky_spacetop',
   array(
      'default' => 100,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_text_sticky_spacetop',
        array(
            'label'          => lay_t('Text Sticky Space Top(px)', 'customizer_woocommerce_single_product_text_sticky_space_top_label'),
            'description'    => lay_t('Space between Text and top browser edge when scrolling down', 'customizer_woocommerce_single_product_sticky_spacing_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_text_sticky_spacetop',
            'type'           => 'number',
            'priority'       => 7,
            'active_callback' => 'show_sticky_settings',
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_text_spaceleft',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_text_spaceleft',
        array(
            'label'          => lay_t('Text Space Left(%)', 'customizer_woocommerce_single_product_text_space_left_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_text_spaceleft',
            'type'           => 'number',
            'priority'       => 8
        )
    )
);

// headline textformat
$wp_customize->add_setting( 'lay_woocommerce_singleproduct_headline_textformat',
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
        'lay_woocommerce_singleproduct_headline_textformat',
        array(
            'label'          => lay_t('Headline Text Format', 'customizer_woocommerce_single_product_headline_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_headline_textformat',
            'type'           => 'select',
            'priority'       => 9,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_headline_spacebelow',
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
        'lay_woocommerce_singleproduct_headline_spacebelow',
        array(
            'label'          => lay_t('Headline Space Bottom(em)', 'customizer_woocommerce_single_product_headline_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_headline_spacebelow',
            'type'           => 'number',
            'priority'       => 11
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_shortdescription_textformat',
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
        'lay_woocommerce_singleproduct_shortdescription_textformat',
        array(
            'label'          => lay_t('Short Description Text Format', 'customizer_woocommerce_single_product_short_description_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_shortdescription_textformat',
            'type'           => 'select',
            'priority'       => 15,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_singleproduct_shortdescription_spacebottom',
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
        'lay_woocommerce_singleproduct_shortdescription_spacebottom',
        array(
            'label'          => lay_t('Short Description Space Bottom(em)', 'customizer_woocommerce_single_product_short_description_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_shortdescription_spacebottom',
            'type'           => 'number',
            'priority'       => 15
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_price_textformat',
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
        'lay_woocommerce_singleproduct_price_textformat',
        array(
            'label'          => lay_t('Price Text Format', 'customizer_woocommerce_single_product_price_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_price_textformat',
            'type'           => 'select',
            'priority'       => 20,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_price_spacetop',
   array(
      'default' => 1.3,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_price_spacetop',
        array(
            'label'          => lay_t('Price Space Top(em)', 'customizer_woocommerce_single_product_price_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_price_spacetop',
            'type'           => 'number',
            'priority'       => 25
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_price_spacebelow',
   array(
      'default' => 1.3,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_price_spacebelow',
        array(
            'label'          => lay_t('Price Space Bottom(em)', 'customizer_woocommerce_single_product_price_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_price_spacebelow',
            'type'           => 'number',
            'priority'       => 30
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_variations_textformat',
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
        'lay_woocommerce_singleproduct_variations_textformat',
        array(
            'label'          => lay_t('Variations Selectbox & Variations Name Text Format', 'customizer_woocommerce_single_product_variations_selectbox_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_variations_textformat',
            'type'           => 'select',
            'priority'       => 32,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_hide_variations_name',
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
        'lay_woocommerce_singleproduct_hide_variations_name',
        array(
            'label'          => lay_t('Hide Variations Name', 'customizer_woocommerce_single_product_hide_variations_name_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_hide_variations_name',
            'type'           => 'checkbox',
            'priority'       => 33,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_variations_text_textformat',
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
        'lay_woocommerce_singleproduct_variations_text_textformat',
        array(
            'label'          => lay_t('Variations Text Text Format', 'customizer_woocommerce_single_product_variations_text_text_format_label'),
            'description'    => lay_t('This is the text that appears when a variation is selected. It can be the variation description and variation price.', 'customizer_woocommerce_single_product_variation_text_description'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_variations_text_textformat',
            'type'           => 'select',
            'priority'       => 33,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_qty_textformat',
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
        'lay_woocommerce_singleproduct_qty_textformat',
        array(
            'label'          => lay_t('Quantity Text Format', 'customizer_woocommerce_single_product_quantity_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_qty_textformat',
            'type'           => 'select',
            'priority'       => 35,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_product_description_textformat',
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
        'lay_woocommerce_singleproduct_product_description_textformat',
        array(
            'label'          => lay_t('Product Description/Tabs Text Format', 'customizer_woocommerce_single_product_description_tabs_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_product_description_textformat',
            'type'           => 'select',
            'priority'       => 36,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

// product tabs style

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_tabs_type',
   array(
      'default' => 'vertical',
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'refresh',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_tab_type',
        array(
            'label'          => lay_t('Tabs Type', 'customizer_woocommerce_single_product_tabs_type_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_tabs_type',
            'type'           => 'radio',
            'description' => lay_t('Get tabs by using a Plugin like https://wordpress.org/plugins/wb-custom-product-tabs-for-woocommerce/', 'customizer_woocommerce_single_product_tabs_plugin_description'),
            'priority'       => 39,
            'choices'        => array(
               'vertical' => lay_t('Vertical', 'customizer_woocommerce_single_product_tabs_type_vertical'),
               'horizontal' => lay_t('Horizontal', 'customizer_woocommerce_single_product_tabs_type_horizontal'),
            )
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_description_outside_tabs',
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
        'lay_woocommerce_singleproduct_description_outside_tabs',
        array(
            'label'          => lay_t('Product Description outside of Tabs', 'customizer_woocommerce_single_product_description_outside_tabs_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_description_outside_tabs',
            'type'           => 'checkbox',
            'priority'       => 39.5
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_tab_spacetop',
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
        'lay_woocommerce_singleproduct_tab_spacetop',
        array(
            'label'          => lay_t('Tabs / Description Space Top(em)', 'customizer_woocommerce_single_product_tabs_description_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_tab_spacetop',
            'type'           => 'number',
            'priority'       => 40
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_show_related_products',
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
        'lay_woocommerce_singleproduct_show_related_products',
        array(
            'label'          => lay_t('Show Related Products', 'customizer_woocommerce_single_product_show_related_products_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_show_related_products',
            'type'           => 'checkbox',
            'priority'       => 40,
        )
    )
);


$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_products_headline_textformat',
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
        'lay_woocommerce_singleproduct_related_products_headline_textformat',
        array(
            'label'          => lay_t('"Related Products" / "You may also like…" Headline Text Format', 'customizer_woocommerce_single_product_related_headline_text_format_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_related_products_headline_textformat',
            'type'           => 'select',
            'priority'       => 40,
            'choices'        => Customizer::$textformatsSelect
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_text', array(
    'default'    => 'Related Products',
    'type'       => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
  )
);

$wp_customize->add_control( 'lay_woocommerce_singleproduct_related_text', array(
    'label'      => lay_t('"Related Products" text', 'customizer_woocommerce_single_product_related_products_text_label'),
    'section'    => $section,
    'priority'   => 45,
    'type'       => 'text',
  )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_youmayalsolike_text', array(
    'default'    => 'You may also like',
    'type'       => 'theme_mod',
    'capability' => 'edit_theme_options',
    'transport' => 'refresh',
  )
);

$wp_customize->add_control( 'lay_woocommerce_singleproduct_youmayalsolike_text', array(
    'label'      => lay_t('"You may also like" text', 'customizer_woocommerce_single_product_you_may_also_like_text_label'),
    'section'    => $section,
    'description'=> lay_t('Headline for Upsells', 'customizer_woocommerce_single_product_upsells_headline_description'),
    'priority'   => 50,
    'type'       => 'text',
  )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_products_spacetop',
   array(
      'default' => 3,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_related_products_spacetop',
        array(
            'label'          => lay_t('"Related Products" / "You may also like…" Space Top(%)', 'customizer_woocommerce_single_product_related_space_top_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_related_products_spacetop',
            'type'           => 'number',
            'priority'       => 55
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_products_spacebelow',
   array(
      'default' => 1.5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_related_products_spacebelow',
        array(
            'label'          => lay_t('"Related Products" / "You may also like…" Space Bottom(%)', 'customizer_woocommerce_single_product_related_space_bottom_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_related_products_spacebelow',
            'type'           => 'number',
            'priority'       => 60
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_products_width',
   array(
      'default' => 23.5,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_related_products_width',
        array(
            'label'          => lay_t('"Related Products" / "You may also like…" Thumbnail Width(%)', 'customizer_woocommerce_single_product_related_thumbnail_width_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_related_products_width',
            'type'           => 'number',
            'priority'       => 60
        )
    )
);

$wp_customize->add_setting( 'lay_woocommerce_singleproduct_related_products_space_between',
   array(
      'default' => 2,
      'type' => 'theme_mod',
      'capability' => 'edit_theme_options',
      'transport' => 'postMessage',
   )
);
$wp_customize->add_control(
    new WP_Customize_Control(
        $wp_customize,
        'lay_woocommerce_singleproduct_related_products_space_between',
        array(
            'label'          => lay_t('"Related Products" / "You may also like…" Thumbnail Space between(%)', 'customizer_woocommerce_single_product_related_thumbnail_space_between_label'),
            'section'        => $section,
            'settings'       => 'lay_woocommerce_singleproduct_related_products_space_between',
            'type'           => 'number',
            'priority'       => 60
        )
    )
);