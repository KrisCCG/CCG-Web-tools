<?php

$section = 'intro_section';

// seems that option "intro_use_svg_overlay" is only saved, when "update" is clicked in customizer
// so it seems like i cannot use "active callback" with the function below, because it won't actually show/hide controls unless "update" is clicked in the customizer
// i just changed to to be a theme mod instead of an option
function lay_is_intro_svg_overlay_active() {
	return get_theme_mod('intro_use_svg_overlay', 0) == 1 ? true : false;
}

$wp_customize->add_section($section, [
	'title' => lay_t('Intro', 'customizer_intro_title'),
	'priority' => 45,
	'capability' => 'edit_theme_options',
	'description' => lay_t('The Intro will only show up if you either use a text overlay, image overlay or use a "Media"', 'customizer_intro_section_description'),
]);

$honp_default = true;
if (get_theme_mod('intro_hide_on_phone', 'not_set') == 'not_set') {
	$honp_default = false;
}

$wp_customize->add_setting('intro_hide_on_phone', [
	'default' => $honp_default,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_hide_on_phone', [
		'label' => lay_t('Hide on Phone', 'customizer_intro_hide_on_phone_label'),
		'section' => $section,
		'settings' => 'intro_hide_on_phone',
		'type' => 'checkbox',
		'priority' => 0,
	]),
);

$wp_customize->add_setting('intro_movement', [
	'default' => false,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_movement', [
		'label' => lay_t('Follow mouse movement', 'customizer_intro_follow_mouse_label'),
		'section' => $section,
		'settings' => 'intro_movement',
		'type' => 'checkbox',
		'priority' => 0,
	]),
);

$wp_customize->add_setting('intro_disable_scroll', [
	'default' => false,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_disable_scroll', [
		'label' => lay_t('Disable scroll while Intro is shown', 'customizer_intro_disable_scroll_label'),
		'section' => $section,
		'settings' => 'intro_disable_scroll',
		'type' => 'checkbox',
		'priority' => 1,
	]),
);

$wp_customize->add_setting('intro_background_opacity', [
	'default' => 100,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Range_With_Number_Custom_Control($wp_customize, 'intro_background_opacity', [
		'label' => lay_t('Background Opacity (%)', 'customizer_intro_background_opacity_label'),
		'section' => $section,
		'settings' => 'intro_background_opacity',
		'input_attrs' => ['min' => 0, 'max' => 100, 'step' => 1],
		'priority' => 8,
	]),
);

$wp_customize->add_setting('intro_background_color', [
	'default' => '#fff',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Color_Control($wp_customize, 'intro_background_color', [
		'label' => lay_t('Background Color', 'customizer_intro_background_color_label'),
		'section' => $section,
		'settings' => 'intro_background_color',
		'priority' => 9,
	]),
);

$wp_customize->add_setting('intro_opacity', [
	'default' => '100',
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Range_With_Number_Custom_Control($wp_customize, 'intro_opacity', [
		'label' => lay_t('Intro Opacity (%)', 'customizer_intro_opacity_label'),
		'section' => $section,
		'settings' => 'intro_opacity',
		'input_attrs' => ['min' => 0, 'max' => 100, 'step' => 1],
		'priority' => 10,
	]),
);

$wp_customize->add_setting('intro_media_brightness', [
	'default' => '100',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Range_With_Number_Custom_Control($wp_customize, 'intro_media_brightness', [
		'label' => lay_t('Media Brightness (%)', 'customizer_intro_media_brightness_label'),
		'section' => $section,
		'settings' => 'intro_media_brightness',
		'input_attrs' => ['min' => 0, 'max' => 200, 'step' => 1],
		'priority' => 11,
	]),
);

$wp_customize->add_setting('intro_transition', [
	'default' => 'zoom',
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_transition', [
		'label' => lay_t('Transition', 'customizer_intro_transition_label'),
		'section' => $section,
		'settings' => 'intro_transition',
		'type' => 'select',
		'priority' => 15,
		'choices' => [
			'zoom' => lay_t('Zoom out and fade out', 'customizer_intro_transition_zoom'),
			'fade' => lay_t('Fade out', 'customizer_intro_transition_fade'),
			'up' => lay_t('Go up', 'customizer_intro_transition_up'),
			'upfade' => lay_t('Go up and Fade out', 'customizer_intro_transition_upfade'),
		],
	]),
);

$wp_customize->add_setting('intro_transition_duration', [
	'default' => 500,
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_transition_duration', [
		'label' => lay_t('Transition duration (milliseconds)', 'customizer_intro_transition_duration_label'),
		'section' => $section,
		'settings' => 'intro_transition_duration',
		'type' => 'number',
		'priority' => 20,
	]),
);

$wp_customize->add_setting('intro_hide_after', [
	'default' => 4000,
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_hide_after', [
		'label' => lay_t('Hide after waiting (milliseconds)', 'customizer_intro_hide_after_label'),
		'section' => $section,
		'description' => lay_t('Set to 0 to turn off', 'customizer_intro_hide_after_description'),
		'settings' => 'intro_hide_after',
		'type' => 'number',
		'priority' => 25,
	]),
);

// should be called intro_desktop, but it is this name for backwards compatibility
$wp_customize->add_setting('intro_image', [
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Upload_Control($wp_customize, 'intro_image', [
		'label' => lay_t('Landscape Intro Media', 'customizer_intro_landscape_media_label'),
		'description' => lay_t('for Desktop (image or .mp4)', 'customizer_intro_landscape_description'),
		'section' => $section,
		'settings' => 'intro_image',
		'priority' => 30,
	]),
);

$wp_customize->add_setting('phone_intro', [
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Upload_Control($wp_customize, 'phone_intro', [
		'label' => lay_t('Portrait Intro Media', 'customizer_intro_portrait_media_label'),
		'description' => lay_t('for Smartphones (image or .mp4)', 'customizer_intro_portrait_description'),
		'section' => $section,
		'settings' => 'phone_intro',
		'priority' => 35,
	]),
);

$wp_customize->add_setting('intro_custom_html', [
	'default' => '',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control('intro_custom_html', [
	'label' => lay_t('Custom HTML in Intro Container', 'customizer_intro_custom_html_label'),
	'description' =>
		lay_t('You need to write your own custom CSS to properly size this in Lay Options → Custom CSS & HTML. Malformed HTML can break the intro and your website. Also please use CSS position absolute or position fixed on your HTML for the setting "Follow mouse movement" to still work correctly.', 'customizer_intro_custom_html_description'),
	'section' => $section,
	'settings' => 'intro_custom_html',
	'priority' => 36,
	'type' => 'textarea',
]);

$wp_customize->add_setting('intro_use_svg_overlay', [
	'default' => false,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_use_svg_overlay', [
		'label' => lay_t('Image Overlay', 'customizer_intro_image_overlay_label'),
		'section' => $section,
		'settings' => 'intro_use_svg_overlay',
		'type' => 'checkbox',
		'priority' => 40,
	]),
);

$wp_customize->add_setting('intro_svg_overlay', [
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Upload_Control($wp_customize, 'intro_svg_overlay', [
		'label' => lay_t('Overlay Image', 'customizer_intro_overlay_image_label'),
		'section' => $section,
		'settings' => 'intro_svg_overlay',
		'description' => lay_t('you can use a .svg file', 'customizer_intro_svg_overlay_description'),
		'priority' => 45,
		'active_callback' => 'lay_is_intro_svg_overlay_active',
	]),
);

// option can be % (deprecated)
// overlay width - using combined control
$wp_customize->add_setting('intro_svg_overlay_width_in', [
	'default' => '%',
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_setting('intro_svg_overlay_width', [
	'default' => '30',
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_svg_overlay_width', [
		'label' => lay_t('Desktop Image overlay width', 'customizer_intro_overlay_width_desktop_label'),
		'section' => $section,
		'settings' => 'intro_svg_overlay_width',
		'unit_setting' => 'intro_svg_overlay_width_in',
		'unit_choices' => ['px' => 'px', '%' => '%'],
		'step' => '1',
		'priority' => 50,
		'active_callback' => 'lay_is_intro_svg_overlay_active',
	]),
);

// for phone, use desktop defaults
$svg_overlay_width = get_option('intro_svg_overlay_width', '30');
$mu = get_option('intro_svg_overlay_width_in', '%');

$wp_customize->add_setting('phone_intro_svg_overlay_width_in', [
	'default' => $mu,
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_setting('phone_intro_svg_overlay_width', [
	'default' => $svg_overlay_width,
	'type' => 'option',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'phone_intro_svg_overlay_width', [
		'label' => lay_t('Phone Image overlay width', 'customizer_intro_overlay_width_phone_label'),
		'section' => $section,
		'settings' => 'phone_intro_svg_overlay_width',
		'unit_setting' => 'phone_intro_svg_overlay_width_in',
		'unit_choices' => ['px' => 'px', '%' => '%'],
		'step' => '1',
		'priority' => 56,
		'active_callback' => 'lay_is_intro_svg_overlay_active',
	]),
);

// text!

$wp_customize->add_setting('intro_use_text_overlay', [
	'default' => false,
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_use_text_overlay', [
		'label' => lay_t('Text Overlay', 'customizer_intro_text_overlay_label'),
		'section' => $section,
		'settings' => 'intro_use_text_overlay',
		'type' => 'checkbox',
		'priority' => 60,
	]),
);

$wp_customize->add_setting('intro_text', [
	'default' => '',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);

$wp_customize->add_control('intro_text', [
	'label' => lay_t('Intro Text', 'customizer_intro_text_label'),
	'section' => $section,
	'priority' => 62,
	'type' => 'textarea',
]);

// text position

$wp_customize->add_setting('intro_text_position', [
	'default' => 'center-left',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'refresh',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_position', [
		'label' => lay_t('Position', 'customizer_intro_text_position_label'),
		'section' => $section,
		'settings' => 'intro_text_position',
		'type' => 'select',
		'priority' => 65,
		'choices' => [
			'top-left' => lay_t('top left', 'customizer_intro_text_position_top_left'),
			'top-center' => lay_t('top center', 'customizer_intro_text_position_top_center'),
			'top-right' => lay_t('top right', 'customizer_intro_text_position_top_right'),
			'center-left' => lay_t('center left', 'customizer_intro_text_position_center_left'),
			'center' => lay_t('center', 'customizer_intro_text_position_center'),
			'center-right' => lay_t('center right', 'customizer_intro_text_position_center_right'),
			'bottom-left' => lay_t('bottom left', 'customizer_intro_text_position_bottom_left'),
			'bottom-center' => lay_t('bottom center', 'customizer_intro_text_position_bottom_center'),
			'bottom-right' => lay_t('bottom right', 'customizer_intro_text_position_bottom_right'),
		],
	]),
);

// space top - using combined control
$wp_customize->add_setting('intro_text_spacetop_mu', [
	'default' => 'px',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_setting('intro_text_spacetop', [
	'default' => Customizer::$defaults['intro_text_spacetop'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_text_spacetop', [
		'label' => lay_t('Space Top', 'customizer_intro_text_space_top_label'),
		'section' => $section,
		'settings' => 'intro_text_spacetop',
		'unit_setting' => 'intro_text_spacetop_mu',
		'unit_choices' => ['px' => 'px', 'vw' => '%'],
		'step' => '0.1',
		'priority' => 70,
	]),
);

// space bottom - using combined control
$wp_customize->add_setting('intro_text_spacebottom_mu', [
	'default' => 'px',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_setting('intro_text_spacebottom', [
	'default' => Customizer::$defaults['intro_text_spacebottom'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_text_spacebottom', [
		'label' => lay_t('Space Bottom', 'customizer_intro_text_space_bottom_label'),
		'section' => $section,
		'settings' => 'intro_text_spacebottom',
		'unit_setting' => 'intro_text_spacebottom_mu',
		'unit_choices' => ['px' => 'px', 'vw' => '%'],
		'step' => '0.1',
		'priority' => 80,
	]),
);

// spaceleft - using combined control
$wp_customize->add_setting('intro_text_spaceleft_mu', [
	'default' => '%',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_setting('intro_text_spaceleft', [
	'default' => Customizer::$defaults['intro_text_spaceleft'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_text_spaceleft', [
		'label' => lay_t('Space Left', 'customizer_intro_text_space_left_label'),
		'section' => $section,
		'settings' => 'intro_text_spaceleft',
		'unit_setting' => 'intro_text_spaceleft_mu',
		'unit_choices' => ['%' => '%', 'px' => 'px'],
		'step' => '0.1',
		'priority' => 90,
	]),
);

// spaceright - using combined control
$wp_customize->add_setting('intro_text_spaceright_mu', [
	'default' => '%',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_setting('intro_text_spaceright', [
	'default' => Customizer::$defaults['intro_text_spaceright'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_text_spaceright', [
		'label' => lay_t('Space Right', 'customizer_intro_text_space_right_label'),
		'section' => $section,
		'settings' => 'intro_text_spaceright',
		'unit_setting' => 'intro_text_spaceright_mu',
		'unit_choices' => ['%' => '%', 'px' => 'px'],
		'step' => '0.1',
		'priority' => 100,
	]),
);

$wp_customize->add_setting('intro_text_textformat', [
	'default' => 'Default',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_textformat', [
		'label' => lay_t('Text Format', 'customizer_intro_text_format_label'),
		'section' => $section,
		'settings' => 'intro_text_textformat',
		'type' => 'select',
		'priority' => 120,
		'choices' => Customizer::$textformatsSelect,
	]),
);

$wp_customize->add_setting('intro_text_fontfamily', [
	'default' => Customizer::$defaults['fontfamily'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_fontfamily', [
		'label' => lay_t('Font Family', 'customizer_intro_text_font_family_label'),
		'section' => $section,
		'settings' => 'intro_text_fontfamily',
		'type' => 'select',
		'choices' => Customizer::$fonts,
		'priority' => 130,
	]),
);

$wp_customize->add_setting('intro_text_fontsize_mu', [
	'default' => 'px',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_setting('intro_text_fontsize', [
	'default' => Customizer::$defaults['fontsize'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
// http://codex.wordpress.org/Class_Reference/WP_Customize_Control
$wp_customize->add_control(
	new Spacing_With_Unit_Custom_Control($wp_customize, 'intro_text_fontsize', [
		'label' => lay_t('Font Size', 'customizer_intro_text_font_size_label'),
		'section' => $section,
		'settings' => 'intro_text_fontsize',
		'unit_setting' => 'intro_text_fontsize_mu',
		'unit_choices' => ['px' => 'px', 'vw' => '%'],
		'step' => '0.1',
		'priority' => 140,
	]),
);

$wp_customize->add_setting('intro_text_fontweight', [
	'default' => Customizer::$defaults['fontweight'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_fontweight', [
		'label' => lay_t('Font Weight', 'customizer_intro_text_font_weight_label'),
		'section' => $section,
		'settings' => 'intro_text_fontweight',
		'type' => 'select',
		'choices' => array_flip(FormatsManager::$fontWeights),
		'priority' => 160,
	]),
);

$wp_customize->add_setting('intro_text_lineheight', [
	'default' => Customizer::$defaults['lineheight'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_lineheight', [
		'label' => lay_t('Line Height', 'customizer_intro_text_line_height_label'),
		'section' => $section,
		'settings' => 'intro_text_lineheight',
		'type' => 'number',
		'input_attrs' => ['step' => '0.01'],
		'priority' => 165,
	]),
);

$wp_customize->add_setting('intro_text_color', [
	'default' => Customizer::$defaults['color'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Color_Control($wp_customize, 'intro_text_color', [
		'label' => lay_t('Text Color', 'customizer_intro_text_color_label'),
		'section' => $section,
		'settings' => 'intro_text_color',
		'priority' => 170,
	]),
);

$wp_customize->add_setting('intro_text_letterspacing', [
	'default' => Customizer::$defaults['letterspacing'],
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_letterspacing', [
		'label' => lay_t('Letter Spacing (em)', 'customizer_intro_text_letter_spacing_label'),
		'section' => $section,
		'settings' => 'intro_text_letterspacing',
		'input_attrs' => ['step' => '0.01'],
		'type' => 'number',
		'priority' => 180,
	]),
);

$wp_customize->add_setting('intro_text_align', [
	'default' => 'left',
	'type' => 'theme_mod',
	'capability' => 'edit_theme_options',
	'transport' => 'postMessage',
]);
$wp_customize->add_control(
	new WP_Customize_Control($wp_customize, 'intro_text_align', [
		'label' => lay_t('Text Align', 'customizer_intro_text_align_label'),
		'section' => $section,
		'settings' => 'intro_text_align',
		'type' => 'select',
		'priority' => 190,
		'choices' => [
			'left' => lay_t('left', 'customizer_intro_text_align_left'),
			'center' => lay_t('center', 'customizer_intro_text_align_center'),
			'right' => lay_t('right', 'customizer_intro_text_align_right'),
		],
	]),
);
