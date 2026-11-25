<?php

class ButtonsManager {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_head', array( __CLASS__, 'buttonsmanager_textformats_css' ), 10 );
		add_action( 'wp_ajax_lay_buttonsmanager_save', array( __CLASS__, 'ajax_save_buttons' ) );
	}

	public static function register_menu() {
		add_submenu_page(
			'manage-layoptions',
			lay_t('Buttons', 'buttons'),
			lay_t('Buttons', 'buttons'),
			'manage_options',
			'manage-buttons',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		require_once Setup::$dir . '/buttonsmanager/markup.php';
	}

	public static function buttonsmanager_textformats_css() {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'manage-buttons' ) !== false ) {
			$formatsJsonString = FormatsManager::getDefaultFormatsJson();
			$formatsJsonArr = json_decode($formatsJsonString, true);

			$formatsCSS = '';

			for ($i = 0; $i<count($formatsJsonArr); $i++) {
				$format = $formatsJsonArr[$i];
				$formatname = $format["formatname"];
				$selector = '#lay-buttonsmanager-root ._'.$formatname.'_no_spaces';
				
				// Use public method to get CSS for this format (without media queries for admin area)
				$formatCSS = FormatsManager::get_textformat_css_for_selector($formatname, $selector, false, false, false);
				
				if ($formatname == "Default") {
					// "Default" textformat - output immediately
					echo
						'<!-- default text format "Default" -->
						<style class="textformats-style">
							'.$formatCSS.'
						</style>';
				} else {
					// custom textformats - collect for later output
					$formatsCSS .= $formatCSS;
				}
			}

			if ($formatsCSS != "") {
				echo
				'<!-- custom text formats -->
				<style class="textformats-style" id="custom-textformats-style">
					'.$formatsCSS.'
				</style>';
			}
		}
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'manage-buttons' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'buttonsmanager-styles',
			Setup::$uri . '/buttonsmanager/assets/css/buttonsmanager.css',
			array(),
			Setup::$ver
		);

		wp_enqueue_script(
			'buttonsmanager-app',
			Setup::$uri . '/buttonsmanager/assets/js/buttonsmanager.umd.js',
			array(),
			Setup::$ver,
			true
		);

		wp_enqueue_script(
			'lay-collapse-and-expand-explanations',
			Setup::$uri . '/setup/assets/js/collapse_and_expand_explanations.js',
			array( 'jquery', 'underscore' ),
			Setup::$ver,
			true
		);
		wp_localize_script( 'lay-collapse-and-expand-explanations', 'setupPassedData', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );

		wp_localize_script(
			'buttonsmanager-app',
			'layButtonsManagerData',
			self::get_passed_data()
		);
	}

	private static function get_passed_data() {
		$bg_color_palette = get_option( 'misc_options_colorpalette', '#000, #fff, #f00, #0f0, #00f, #ff0, #0ff, #f0f' );
		$bg_color_palette = Setup::turnTo6DigitHexString($bg_color_palette);
		
		//turn into array
		$bg_color_palette = explode( ',', $bg_color_palette );

		return array(
			'initialButtons'             => self::get_buttons_settings(),
			'textFormats'                => array_values( Customizer::$textformatsSelect ?? array( 'Default' ) ),
			'ajaxUrl'                    => admin_url( 'admin-ajax.php' ),
			'nonce'                      => wp_create_nonce( 'lay_buttonsmanager_nonce' ),
			'i18n'                       => self::get_i18n_strings(),
			'expandWhatAreButtonsStatus' => get_option( 'expand-what_are_buttons', 'collapsed' ),
			'templateDirectoryUri'       => get_template_directory_uri(),
			'bgColorPalette'             => $bg_color_palette,
		);
	}

	private static function get_i18n_strings() {
		return array(
			'headline'                        => lay_t('Buttons', 'buttons'),
			'usageTitle'                      => lay_t('How to use Buttons?', 'how_to_use_buttons'),
			'description'                     => sprintf(
				lay_t('<p>Configure the three button styles below. Previously, these settings were in Customize &rarr; Buttons.</p>
			<p>Insert a button in a text element: Create a link in a text, then select <img src="%s" style="width: 20px; height: 20px; vertical-align: middle; display: inline-block;" alt="Button 1" /> or <img src="%s" style="width: 20px; height: 20px; vertical-align: middle; display: inline-block;" alt="Button 2" /> or <img src="%s" style="width: 20px; height: 20px; vertical-align: middle; display: inline-block;" alt="Button 3" /> to apply the button style.</p>
			<p>These are also used for "Load More" buttons for thumbnailgrid, news element, elementgrid and productsgrid.<br/>
			Or use them in your HTML, anywhere you apply <code>.laybutton1</code>, <code>.laybutton2</code>, or <code>.laybutton3</code>.</p>', 'configure_the_three_button_styles_below_previously_these_settings_were_in_customize_andrarr_buttons_insert_a_button_in_a_text_element_create_a_link_in_a_text_then_select_or_or_to_apply_the_button_style_these_are_also_used_for_load_more_buttons_for_thumbnailgrid_news_element_elementgrid_and_productsgrid_or_use_them_in_your_html_anywhere_you_apply_laybutton1_laybutton2_or_laybutton3'),
				esc_url( get_template_directory_uri() . '/gridder/assets/img/1.svg' ),
				esc_url( get_template_directory_uri() . '/gridder/assets/img/2.svg' ),
				esc_url( get_template_directory_uri() . '/gridder/assets/img/3.svg' )
			),
			'usageImageAlt'                   => lay_t('Button usage example', 'button_usage_example'),
			'menuButtonUsageText'             => lay_t('Use buttons for your menu:<br/>In Customize &rarr; Menu Style &rarr; Primary Menu,<br/>set "Menu Style" to "Button".', 'use_buttons_for_your_menu_in_customize_menu_style_primary_menu_set_menu_style_to_button'),
			'save'                            => lay_t('Save Changes', 'save_changes'),
			'saving'                          => lay_t('Saving…', 'saving'),
			'saved'                           => lay_t('Buttons updated.', 'buttons_updated'),
			'error'                           => lay_t('Saving buttons failed. Please try again.', 'saving_buttons_failed_please_try_again'),
			'generalSection'                  => lay_t('General', 'general'),
			'spacingSection'                  => lay_t('Spacing', 'spacing'),
			'mouseoverSection'                => lay_t('Mouseover', 'mouseover'),
			'activeStateSection'              => lay_t('Active State', 'active_state'),
			'activeStateTooltipText'          => lay_t('When your menu uses menu style "button" this will be used for the active menu points. Set your menu to button style in "Customize" → "Menu Style" → "Primary Menu".', 'active_state_tooltip_menu_button_style'),
			'textFormatLabel'                 => lay_t('Text Format', 'text_format'),
			'textColorLabel'                  => lay_t('Text Color', 'text_color'),
			'borderColorLabel'                => lay_t('Border Color', 'border_color'),
			'backgroundColorLabel'            => lay_t('Background Color', 'background_color'),
			'transparentBackgroundLabel'      => lay_t('Transparent background', 'transparent_background'),
			'blurryBackgroundLabel'           => lay_t('Blurry background', 'blurry_background'),
			'blurryBackgroundTooltipText'     => lay_t('Background opacity must be less than 100% for blur to show.', 'background_opacity_must_be_less_than_100_for_blur_to_show'),
			'blurLabel'                       => lay_t('Blur (px)', 'blur_px'),
			'backgroundOpacityLabel'          => lay_t('Background Opacity', 'background_opacity'),
			'borderWidthLabel'                => lay_t('Border Width (px)', 'border_width_px'),
			'borderRadiusLabel'               => lay_t('Border Radius (px)', 'border_radius_px'),
			'paddingLeftRightLabel'           => lay_t('Inner Space Left/Right (px)', 'inner_space_left_right_px'),
			'paddingTopLabel'                 => lay_t('Inner Space Top (px)', 'inner_space_top_px'),
			'paddingBottomLabel'              => lay_t('Inner Space Bottom (px)', 'inner_space_bottom_px'),
			'forceStylesLabel'                => lay_t('Force Styles', 'force_styles'),
			'marginLeftRightLabel'            => lay_t('Outer Space Left/Right (px)', 'outer_space_left_right_px'),
			'marginTopLabel'                  => lay_t('Outer Space Top (px)', 'outer_space_top_px'),
			'marginBottomLabel'               => lay_t('Outer Space Bottom (px)', 'outer_space_bottom_px'),
			'mouseoverTextColorLabel'         => lay_t('Text Color', 'mouseover_text_color'),
			'mouseoverBorderColorLabel'       => lay_t('Border Color', 'mouseover_border_color'),
			'mouseoverBackgroundColorLabel'   => lay_t('Background Color', 'mouseover_background_color'),
			'mouseoverTransparentBackgroundLabel' => lay_t('Transparent background', 'transparent_mouseover_background'),
			'mouseoverBackgroundOpacityLabel' => lay_t('Background Opacity', 'mouseover_background_opacity'),
			'mouseoverBorderWidthLabel'       => lay_t('Border Width (px)', 'mouseover_border_width_px'),
			'useMouseover'                    => lay_t('Enable mouseover', 'enable_mouseover'),
			'animateMouseover'                => lay_t('Animate', 'animate_mouseover'),
			'useActiveState'                  => lay_t('Enable active state', 'enable_active_state'),
			'animateActiveState'              => lay_t('Animate', 'animate_active_state'),
			'previewLabel'                    => lay_t('Preview', 'preview'),
			'previewDefaultLabel'             => lay_t('Default', 'default'),
			'previewHoverLabel'               => lay_t('Hover', 'hover'),
			'copySuccess'                     => lay_t('Copied!', 'copied'),
			'buttonUsageInfo'                 => lay_t('Use <code>.%slug%</code> to apply this button style.', 'use_lug_to_apply_this_button_style'),
		);
	}

	private static function get_buttons_settings() {
		$buttons = array();
		$definitions = self::get_field_definitions();

		foreach ( $definitions as $def ) {
			$slug   = $def['slug'];
			$button = array(
				'slug'                 => $slug,
				'label'                => $def['label'],
				'previewText'          => $def['preview'],
				'textformat'           => get_theme_mod( "{$slug}_textformat", $def['defaults']['textformat'] ),
				'textColor'            => self::get_theme_mod_color( "{$slug}_text_color", $def['defaults']['text_color'] ),
				'borderColor'          => self::get_theme_mod_color( "{$slug}_border_color", $def['defaults']['border_color'] ),
				'backgroundColor'      => self::get_theme_mod_color( "{$slug}_background_color", $def['defaults']['background_color'] ),
				'backgroundTransparent'=> self::get_theme_mod_bool( "{$slug}_background_transparent", $def['defaults']['background_transparent'] ),
				'blurryBackground'     => self::get_theme_mod_bool( "{$slug}_blurry_background", $def['defaults']['blurry_background'] ),
				'blur'                 => self::get_theme_mod_int( "{$slug}_blur", $def['defaults']['blur'] ),
				'backgroundOpacity'    => self::get_theme_mod_int( "{$slug}_background_opacity", $def['defaults']['background_opacity'] ),
				'borderWidth'          => self::get_theme_mod_int( "{$slug}_borderwidth", $def['defaults']['borderwidth'] ),
				'borderRadius'         => self::get_theme_mod_int( "{$slug}_borderradius", $def['defaults']['borderradius'] ),
				'paddingLeftRight'     => self::get_theme_mod_int( "{$slug}_paddingleftright", $def['defaults']['paddingleftright'] ),
				'paddingTop'           => self::get_theme_mod_int( "{$slug}_paddingtop", $def['defaults']['paddingtop'] ),
				'paddingBottom'        => self::get_theme_mod_int( "{$slug}_paddingbottom", $def['defaults']['paddingbottom'] ),
				'forceStyles'          => self::get_theme_mod_bool( "{$slug}_force_styles", $def['defaults']['force_styles'] ),
				'marginLeftRight'      => self::get_theme_mod_int( "{$slug}_marginleftright", $def['defaults']['marginleftright'] ),
				'marginTop'            => self::get_theme_mod_int( "{$slug}_margintop", $def['defaults']['margintop'] ),
				'marginBottom'         => self::get_theme_mod_int( "{$slug}_marginbottom", $def['defaults']['marginbottom'] ),
				'mouseover'            => array(
					'use'                  => self::get_theme_mod_bool( "{$slug}_use_mouseover", false ),
					'animate'              => self::get_theme_mod_bool( "{$slug}_animate_mouseover", false ),
					'textColor'            => self::get_theme_mod_color( "{$slug}_mo_text_color", $def['defaults']['mo_text_color'] ),
					'borderColor'          => self::get_theme_mod_color( "{$slug}_mo_border_color", $def['defaults']['mo_border_color'] ),
					'backgroundColor'      => self::get_theme_mod_color( "{$slug}_mo_background_color", $def['defaults']['mo_background_color'] ),
					'backgroundTransparent'=> self::get_theme_mod_bool( "{$slug}_mo_background_transparent", $def['defaults']['mo_background_transparent'] ),
					'backgroundOpacity'    => self::get_theme_mod_int( "{$slug}_mo_background_opacity", $def['defaults']['mo_background_opacity'] ),
					'borderWidth'          => self::get_theme_mod_int( "{$slug}_mo_borderwidth", $def['defaults']['mo_borderwidth'] ),
				),
				'activeState'          => array(
					'use'                  => self::get_theme_mod_bool( "{$slug}_use_active_state", false ),
					'animate'              => self::get_theme_mod_bool( "{$slug}_animate_active_state", false ),
					'textColor'            => self::get_theme_mod_color( "{$slug}_active_text_color", $def['defaults']['active_text_color'] ),
					'borderColor'          => self::get_theme_mod_color( "{$slug}_active_border_color", $def['defaults']['active_border_color'] ),
					'backgroundColor'      => self::get_theme_mod_color( "{$slug}_active_background_color", $def['defaults']['active_background_color'] ),
					'backgroundTransparent'=> self::get_theme_mod_bool( "{$slug}_active_background_transparent", $def['defaults']['active_background_transparent'] ),
					'backgroundOpacity'    => self::get_theme_mod_int( "{$slug}_active_background_opacity", $def['defaults']['active_background_opacity'] ),
					'borderWidth'          => self::get_theme_mod_int( "{$slug}_active_borderwidth", $def['defaults']['active_borderwidth'] ),
				),
			);

			$buttons[] = $button;
		}

		return $buttons;
	}

	private static function get_field_definitions() {
		return array(
			array(
				'slug'    => 'laybutton1',
				'label'   => lay_t('Button 1', 'button_1'),
				'preview' => lay_t('Button 1', 'button_1'),
				'defaults'=> array(
					'textformat'               => 'Default',
					'text_color'               => '#000000',
					'border_color'             => '#000000',
					'background_color'         => '#ffffff',
					'background_transparent'   => false,
					'blurry_background'        => false,
					'blur'                     => 10,
					'background_opacity'       => 100,
					'borderwidth'              => 1,
					'borderradius'             => 0,
					'paddingleftright'         => 15,
					'paddingtop'               => 5,
					'paddingbottom'            => 5,
					'force_styles'             => false,
					'marginleftright'          => 0,
					'margintop'                => 0,
					'marginbottom'             => 0,
					'mo_text_color'            => '#000000',
					'mo_border_color'          => '#000000',
					'mo_background_color'      => '#ffffff',
					'mo_background_transparent'=> false,
					'mo_background_opacity'    => 100,
					'mo_borderwidth'           => 1,
					'active_text_color'            => '#000000',
					'active_border_color'          => '#000000',
					'active_background_color'      => '#ffffff',
					'active_background_transparent'=> false,
					'active_background_opacity'    => 100,
					'active_borderwidth'           => 1,
				),
			),
			array(
				'slug'    => 'laybutton2',
				'label'   => lay_t('Button 2', 'button_2'),
				'preview' => lay_t('Button 2', 'button_2'),
				'defaults'=> array(
					'textformat'               => 'Default',
					'text_color'               => '#000000',
					'border_color'             => '#000000',
					'background_color'         => '#ffffff',
					'background_transparent'   => false,
					'blurry_background'        => false,
					'blur'                     => 10,
					'background_opacity'       => 100,
					'borderwidth'              => 1,
					'borderradius'             => 100,
					'paddingleftright'         => 20,
					'paddingtop'               => 5,
					'paddingbottom'            => 5,
					'force_styles'             => false,
					'marginleftright'          => 0,
					'margintop'                => 0,
					'marginbottom'             => 0,
					'mo_text_color'            => '#000000',
					'mo_border_color'          => '#000000',
					'mo_background_color'      => '#ffffff',
					'mo_background_transparent'=> false,
					'mo_background_opacity'    => 100,
					'mo_borderwidth'           => 1,
					'active_text_color'            => '#000000',
					'active_border_color'          => '#000000',
					'active_background_color'      => '#ffffff',
					'active_background_transparent'=> false,
					'active_background_opacity'    => 100,
					'active_borderwidth'           => 1,
				),
			),
			array(
				'slug'    => 'laybutton3',
				'label'   => lay_t('Button 3', 'button_3'),
				'preview' => lay_t('Button 3', 'button_3'),
				'defaults'=> array(
					'textformat'               => 'Default',
					'text_color'               => '#000000',
					'border_color'             => '#000000',
					'background_color'         => '#eeeeee',
					'background_transparent'   => false,
					'blurry_background'        => false,
					'blur'                     => 10,
					'background_opacity'       => 100,
					'borderwidth'              => 0,
					'borderradius'             => 100,
					'paddingleftright'         => 20,
					'paddingtop'               => 5,
					'paddingbottom'            => 5,
					'force_styles'             => false,
					'marginleftright'          => 0,
					'margintop'                => 0,
					'marginbottom'             => 0,
					'mo_text_color'            => '#000000',
					'mo_border_color'          => '#000000',
					'mo_background_color'      => '#ffffff',
					'mo_background_transparent'=> false,
					'mo_background_opacity'    => 100,
					'mo_borderwidth'           => 1,
					'active_text_color'            => '#000000',
					'active_border_color'          => '#000000',
					'active_background_color'      => '#ffffff',
					'active_background_transparent'=> false,
					'active_background_opacity'    => 100,
					'active_borderwidth'           => 1,
				),
			),
		);
	}

	private static function get_theme_mod_color( $key, $default ) {
		$value = get_theme_mod( $key, $default );
		return $value ? $value : $default;
	}

	private static function get_theme_mod_int( $key, $default ) {
		return (int) get_theme_mod( $key, $default );
	}

	private static function get_theme_mod_bool( $key, $default ) {
		return (bool) get_theme_mod( $key, $default );
	}

	public static function ajax_save_buttons() {
		check_ajax_referer( 'lay_buttonsmanager_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => lay_t('Not allowed.', 'not_allowed') ), 403 );
		}

		$payload = isset( $_POST['buttons'] ) ? json_decode( wp_unslash( $_POST['buttons'] ), true ) : array();

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => lay_t('Invalid payload.', 'invalid_payload') ), 400 );
		}

		$allowed_slugs = array( 'laybutton1', 'laybutton2', 'laybutton3' );
		$textformats   = array_values( Customizer::$textformatsSelect ?? array( 'Default' ) );

		foreach ( $payload as $button ) {
			if ( empty( $button['slug'] ) || ! in_array( $button['slug'], $allowed_slugs, true ) ) {
				continue;
			}

			$slug = $button['slug'];

			set_theme_mod( "{$slug}_textformat", in_array( $button['textformat'], $textformats, true ) ? $button['textformat'] : 'Default' );
			set_theme_mod( "{$slug}_text_color", self::sanitize_color( $button['textColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_border_color", self::sanitize_color( $button['borderColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_background_color", self::sanitize_color( $button['backgroundColor'] ?? '#ffffff' ) );
			set_theme_mod( "{$slug}_background_transparent", ! empty( $button['backgroundTransparent'] ) );
			set_theme_mod( "{$slug}_blurry_background", ! empty( $button['blurryBackground'] ) );
			set_theme_mod( "{$slug}_blur", max( 0, (int) ( $button['blur'] ?? 10 ) ) );
			$background_opacity = isset( $button['backgroundOpacity'] ) ? (int) $button['backgroundOpacity'] : 100;
			$background_opacity = max( 0, min( 100, $background_opacity ) );
			set_theme_mod( "{$slug}_background_opacity", $background_opacity );
			set_theme_mod( "{$slug}_borderwidth", (int) ( $button['borderWidth'] ?? 0 ) );
			set_theme_mod( "{$slug}_borderradius", (int) ( $button['borderRadius'] ?? 0 ) );
			set_theme_mod( "{$slug}_paddingleftright", (int) ( $button['paddingLeftRight'] ?? 0 ) );
			set_theme_mod( "{$slug}_paddingtop", (int) ( $button['paddingTop'] ?? 0 ) );
			set_theme_mod( "{$slug}_paddingbottom", (int) ( $button['paddingBottom'] ?? 0 ) );
			set_theme_mod( "{$slug}_force_styles", ! empty( $button['forceStyles'] ) );
			set_theme_mod( "{$slug}_marginleftright", (int) ( $button['marginLeftRight'] ?? 0 ) );
			set_theme_mod( "{$slug}_margintop", (int) ( $button['marginTop'] ?? 0 ) );
			set_theme_mod( "{$slug}_marginbottom", (int) ( $button['marginBottom'] ?? 0 ) );

			$mouseover = $button['mouseover'] ?? array();

			set_theme_mod( "{$slug}_use_mouseover", ! empty( $mouseover['use'] ) );
			set_theme_mod( "{$slug}_animate_mouseover", ! empty( $mouseover['animate'] ) );
			set_theme_mod( "{$slug}_mo_text_color", self::sanitize_color( $mouseover['textColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_mo_border_color", self::sanitize_color( $mouseover['borderColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_mo_background_color", self::sanitize_color( $mouseover['backgroundColor'] ?? '#ffffff' ) );
			set_theme_mod( "{$slug}_mo_background_transparent", ! empty( $mouseover['backgroundTransparent'] ) );
			$mouseover_opacity = isset( $mouseover['backgroundOpacity'] ) ? (int) $mouseover['backgroundOpacity'] : 100;
			$mouseover_opacity = max( 0, min( 100, $mouseover_opacity ) );
			set_theme_mod( "{$slug}_mo_background_opacity", $mouseover_opacity );
			set_theme_mod( "{$slug}_mo_borderwidth", (int) ( $mouseover['borderWidth'] ?? 0 ) );

			$active_state = $button['activeState'] ?? array();
			set_theme_mod( "{$slug}_use_active_state", ! empty( $active_state['use'] ) );
			set_theme_mod( "{$slug}_animate_active_state", ! empty( $active_state['animate'] ) );
			set_theme_mod( "{$slug}_active_text_color", self::sanitize_color( $active_state['textColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_active_border_color", self::sanitize_color( $active_state['borderColor'] ?? '#000000' ) );
			set_theme_mod( "{$slug}_active_background_color", self::sanitize_color( $active_state['backgroundColor'] ?? '#ffffff' ) );
			set_theme_mod( "{$slug}_active_background_transparent", ! empty( $active_state['backgroundTransparent'] ) );
			$active_state_opacity = isset( $active_state['backgroundOpacity'] ) ? (int) $active_state['backgroundOpacity'] : 100;
			$active_state_opacity = max( 0, min( 100, $active_state_opacity ) );
			set_theme_mod( "{$slug}_active_background_opacity", $active_state_opacity );
			set_theme_mod( "{$slug}_active_borderwidth", (int) ( $active_state['borderWidth'] ?? 0 ) );
		}

		wp_send_json_success( array( 'message' => lay_t('Buttons updated.', 'buttons_updated') ) );
	}

	private static function sanitize_color( $value ) {
		$color = sanitize_hex_color( $value );
		if ( ! $color ) {
			$color = '#000000';
		}
		return $color;
	}
}

ButtonsManager::init();

