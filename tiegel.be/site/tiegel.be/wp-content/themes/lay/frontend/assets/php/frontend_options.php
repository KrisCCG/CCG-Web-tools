<?php
class LayFrontend_Options {
	public static $ptags_position;
	public static $hide_st_m_on_pw;
	public static $page_overlays;
	public static $textformats_lineheight_in_px;
	public static $pt_textformat;
	public static $ptags_textformat;
	public static $pt_position;
	public static $pd_position;
	public static $pd_position_related_to_title;
	public static $st_position;
	public static $st_hide;
	public static $navbar_hide;
	public static $showArrows;
	public static $pa_type;
	public static $simple_parallax;
	public static $playicon;
	public static $woocommercePagesFooterId;
	public static $footer_active_in_woocommerce_pages;
	public static $projectsFooterId;
	public static $footer_active_in_projects;
	public static $pagesFooterId;
	public static $footer_active_in_pages;
	public static $newsFooterId;
	public static $footer_active_in_news;
	public static $individual_news_footers;
	public static $deckFooterId;
	public static $footer_active_in_deck;
	public static $individual_deck_footers;
	public static $categoriesFooterId;
	public static $footer_active_in_categories;
	public static $individual_project_footers;
	public static $individual_page_footers;
	public static $individual_category_footers;
	// Header properties
	public static $headerPageId;
	public static $headerPosition;
	public static $headerTransparentBackground;
	public static $headerHideOnPhone;
	public static $misc_options_header;

	public static $prevnext_navigate_through;
	public static $navigation_transition_duration;
	public static $activate_project_description;
	public static $fi_mo_touchdevice_behaviour;
	public static $image_loading;
	public static $cover_active_in_projects;
	public static $cover_active_in_pages;
	public static $cover_active_in_categories;
	public static $cover_individual_project_ids;
	public static $cover_individual_page_ids;
	public static $cover_individual_category_ids;
	public static $cover_scrolldown_on_click;
	public static $cover_darken_when_scrolling;
	public static $cover_parallaxmove_when_scrolling;
	public static $misc_options_cover;
	public static $misc_options_max_width_apply_to_logo_and_nav;
	public static $misc_options_max_width_apply_to_burger;
	public static $maxwidth;
	public static $default;
	public static $m_st_fontfamily;
	public static $misc_options_showoriginalimages;
	public static $misc_options_showoriginalimages_for_phone;
	public static $cover_disable_for_phone;
	public static $phone_layout_active;
	public static $misc_options_thumbnail_video;
	public static $misc_options_thumbnail_mouseover_image;
	public static $misc_options_thumbnail_video_disabled_for_touch_devices;
	public static $frame_leftright;
	public static $bg_color;
	public static $bg_image;
	public static $is_customize;
	public static $is_ssl;
	public static $has_www;
	public static $is_qtranslate_active;
	public static $intro_text_textformat;
	public static $element_transition_on_scroll;
	public static $breakpoint;
	public static $video_thumbnail_mouseover_behaviour;
	public static $cover_down_arrow_type;
	public static $cover_down_arrow;
	public static $cover_down_arrow_phone;
	public static $cover_down_arrow_animate;
	public static $spaces_mu;
	public static $offsets_mu;
	public static $tablet_breakpoint;
	public static $turn_off_custom_code;
	public static $menu_submenu_keep_open;
	public static $menu_submenu_keep_open_2;
	public static $menu_submenu_follow_parent_link_on_click_to_open_submenu;
	public static $show_password_protected_posts_in_thumbnailgrid;
	public static $show_password_protected_posts_in_projectindex;
	public static $lightbox_default_caption;
	public static $lightbox_captionTextformat;
	public static $hide_current_project_from_thumbnailgrid;
	public static $enable_video_lazyloading_for_firefox;
	public static $enable_video_lazyloading_for_safari;
	// m menubar
	public static $mobile_menubar_move_away_when_scrolling_down;
	public static $mobile_menubar_fade_out_when_scrolling_down;
	// m st
	public static $mobile_sitetitle_move_away_when_scrolling_down;
	public static $mobile_sitetitle_fade_out_when_scrolling_down;
	// m menu
	public static $mobile_menu_move_away_when_scrolling_down;
	public static $mobile_menu_fade_out_when_scrolling_down;
	public static $imagehover_imageposition;

	public static $imagehover_transition;
	public static $misc_options_image_loading_blur;

	public static $gridder_active_for_products;
	public static $videos_lazyload;
	public static $enable_html_shortcode_smart_loading;

	public static $project_overlay_active;
	public static $po_override_overlay_bg_opacity_blur;
	public static $po_overlay_bg_opacity;
	public static $po_overlay_bg_blur;

	// woocommerce
	public static $woo_prth_topleft;
	public static $woo_prth_topright;
	public static $woo_prth_center;
	public static $woo_prth_bottomleft;
	public static $woo_prth_bottomright;
	public static $woo_prth_below;
	public static $lay_woocommerce_pt_show_shortdescr;
	public static $lay_woocommerce_pt_outofstock_text;
	public static $lay_woocommerce_pt_show_brands;
	public static $lay_woocommerce_pt_show_add_to_cart_button;
	public static $alt_tag_to_use;

	public static $cache_active;

	public function __construct() {
		// if i dont use this hook but set all vars on construct, the latest values are not set when changing these values in customize
		// instead, the old values will still be in use
		add_action('wp_loaded', [$this, 'set_vars']);
	}

	public static function set_vars() {
		// Server-side cache: use cache for full page loads even when "Disable Ajax" is on
		LayFrontend_Options::$cache_active = Lay_Layout_Cache::is_cache_enabled(false);
		LayFrontend_Options::$ptags_position = get_theme_mod('ptags_position', 'below-title');

		LayFrontend_Options::$hide_st_m_on_pw = get_option('misc_options_hide_menu_and_sitetitle_on_pw', '');
		LayFrontend_Options::$alt_tag_to_use = get_option('misc_options_image_alt_tag_to_use', 'alt');

		LayFrontend_Options::$page_overlays = get_option('misc_options_activate_overlay_feature', '') == 'on' ? true : false;
		LayFrontend_Options::$textformats_lineheight_in_px = get_option('misc_options_textformats_lineheight_in_px', '') == 'on' ? true : false;
		LayFrontend_Options::$lay_woocommerce_pt_show_shortdescr = get_theme_mod('lay_woocommerce_pt_show_shortdescr', 0);

		LayFrontend_Options::$lay_woocommerce_pt_outofstock_text = get_theme_mod('lay_woocommerce_pt_outofstock_text', 'Out of stock');

		LayFrontend_Options::$gridder_active_for_products = get_option('misc_options_activate_gridder_for_products', '') == 'on' ? true : false;
		LayFrontend_Options::$imagehover_transition = get_option('imagehover_transition', 'fade2');
		LayFrontend_Options::$imagehover_imageposition = get_option('imagehover_imageposition', 'followmousex');

		LayFrontend_Options::$lightbox_captionTextformat = get_option('lightbox_captionTextformat', 'Default');
		LayFrontend_Options::$lightbox_default_caption = get_option('lightbox_default_caption', 'none');
		LayFrontend_Options::$tablet_breakpoint = get_option('misc_options_textformats_tablet_breakpoint', 1024);
		LayFrontend_Options::$tablet_breakpoint = (int) LayFrontend_Options::$tablet_breakpoint;

		LayFrontend_Options::$spaces_mu = get_option('gridder_spaces_mu', LayConstants::gridder_spaces_mu);
		if (LayFrontend_Options::$spaces_mu == '%') {
			LayFrontend_Options::$spaces_mu = 'vw';
		}
		LayFrontend_Options::$offsets_mu = get_option('gridder_offsets_mu', LayConstants::gridder_offsets_mu);
		if (LayFrontend_Options::$offsets_mu == '%') {
			LayFrontend_Options::$offsets_mu = 'vw';
		}

		LayFrontend_Options::$cover_down_arrow_type = get_option('cover_down_arrow_type', 'none');

		$desktop_image_thumb_id = get_option('cover_down_arrow', '');
		$desktop_image_thumb = wp_get_attachment_image_src($desktop_image_thumb_id, 'full');
		if (is_array($desktop_image_thumb)) {
			LayFrontend_Options::$cover_down_arrow = '<img class="cover-down-arrow-desktop" src="' . $desktop_image_thumb[0] . '" alt="">';
		} else {
			LayFrontend_Options::$cover_down_arrow = Lay_LayoutFunctions::$downArrowDesktop;
		}

		$phone_image_thumb_id = get_option('cover_down_arrow_phone', '');
		$phone_image_thumb = wp_get_attachment_image_src($phone_image_thumb_id, 'full');
		if (is_array($phone_image_thumb)) {
			LayFrontend_Options::$cover_down_arrow_phone = '<img class="cover-down-arrow-phone" src="' . $phone_image_thumb[0] . '" alt="">';
		} else {
			if (is_array($desktop_image_thumb)) {
				LayFrontend_Options::$cover_down_arrow_phone = '<img class="cover-down-arrow-phone" src="' . $desktop_image_thumb[0] . '" alt="">';
			} else {
				LayFrontend_Options::$cover_down_arrow_phone = Lay_LayoutFunctions::$downArrowPhone;
			}
		}

		LayFrontend_Options::$cover_down_arrow_animate = get_option('cover_down_arrow_animate', '') == 'on' ? true : false;

		LayFrontend_Options::$enable_video_lazyloading_for_firefox = get_option('misc_options_enable_video_lazyloading_for_firefox', '') == 'on' ? true : false;
		LayFrontend_Options::$enable_video_lazyloading_for_safari = get_option('misc_options_enable_video_lazyloading_for_safari', '') == 'on' ? true : false;

		LayFrontend_Options::$videos_lazyload = get_option('misc_options_videos_lazyload', '') == 'on' ? true : false;

		LayFrontend_Options::$enable_html_shortcode_smart_loading = get_option('misc_options_enable_html_shortcode_smart_loading', '') == 'on' ? true : false;

		LayFrontend_Options::$project_overlay_active = get_option('misc_options_activate_projects_overlay_feature', '') == 'on' ? true : false;

		LayFrontend_Options::$po_override_overlay_bg_opacity_blur = get_option('po_override_overlay_bg_opacity_blur', '');
		LayFrontend_Options::$po_override_overlay_bg_opacity_blur = LayFrontend_Options::$po_override_overlay_bg_opacity_blur == 'on' ? true : false;
		LayFrontend_Options::$po_overlay_bg_opacity = get_option('po_overlay_bg_opacity', 100);
		LayFrontend_Options::$po_overlay_bg_blur = get_option('po_overlay_bg_blur', 0);

		LayFrontend_Options::$breakpoint = MiscOptions::$phone_breakpoint;

		LayFrontend_Options::$pt_textformat = get_theme_mod('pt_textformat', 'Default');
		if (LayFrontend_Options::$pt_textformat != '') {
			LayFrontend_Options::$pt_textformat = '_' . LayFrontend_Options::$pt_textformat;
		}

		LayFrontend_Options::$ptags_textformat = get_theme_mod('ptags_textformat', 'Default');
		if (LayFrontend_Options::$ptags_textformat != '') {
			LayFrontend_Options::$ptags_textformat = '_' . LayFrontend_Options::$ptags_textformat;
		}

		LayFrontend_Options::$pt_position = get_theme_mod('pt_position', 'below-image');
		LayFrontend_Options::$pd_position = get_theme_mod('pd_position', 'below-image');
		LayFrontend_Options::$pd_position_related_to_title = get_theme_mod('pd_position_related_to_title', 'below-title');

		LayFrontend_Options::$st_position = get_theme_mod('st_position', 'top-left');
		LayFrontend_Options::$st_hide = get_theme_mod('st_hide', 0);

		$navbar_hide_default = get_theme_mod('navbar_hide', 'value_was_never_set');
		if ($navbar_hide_default == 'value_was_never_set') {
			$navbar_hide_default = true;
		} else {
			$navbar_hide_default = false;
		}
		LayFrontend_Options::$navbar_hide = get_theme_mod('navbar_hide', $navbar_hide_default);

		LayFrontend_Options::$showArrows = get_option('misc_options_show_project_arrows', '');
		LayFrontend_Options::$showArrows = LayFrontend_Options::$showArrows == 'on' && !MiscOptions::$locked ? true : false;
		LayFrontend_Options::$pa_type = get_theme_mod('pa_type', 'icon');
		LayFrontend_Options::$simple_parallax = get_option('misc_options_simple_parallax', '');
		LayFrontend_Options::$simple_parallax = LayFrontend_Options::$simple_parallax == 'on' && !MiscOptions::$locked ? true : false;

		LayFrontend_Options::$show_password_protected_posts_in_thumbnailgrid = get_option('misc_options_show_password_protected_posts_in_thumbnailgrid', 'on');
		LayFrontend_Options::$show_password_protected_posts_in_thumbnailgrid = LayFrontend_Options::$show_password_protected_posts_in_thumbnailgrid == 'on' ? true : false;

		LayFrontend_Options::$show_password_protected_posts_in_projectindex = get_option('misc_options_show_password_protected_posts_in_projectindex', '');
		LayFrontend_Options::$show_password_protected_posts_in_projectindex = LayFrontend_Options::$show_password_protected_posts_in_projectindex == 'on' ? true : false;

		LayFrontend_Options::$playicon = '';
		$playicon_id = get_option('misc_options_html5video_playicon', '');
		if ($playicon_id != '') {
			LayFrontend_Options::$playicon = wp_get_attachment_image_src($playicon_id, 'full');
			if (is_array(LayFrontend_Options::$playicon)) {
				LayFrontend_Options::$playicon = LayFrontend_Options::$playicon[0];
			}
		}

		LayFrontend_Options::$projectsFooterId = '';
		LayFrontend_Options::$footer_active_in_projects = get_option('lay_footer_active_in_projects', 'off');
		if (LayFrontend_Options::$footer_active_in_projects == 'all') {
			LayFrontend_Options::$projectsFooterId = get_option('lay_projects_footer', '');
		}

		LayFrontend_Options::$pagesFooterId = '';
		LayFrontend_Options::$footer_active_in_pages = get_option('lay_footer_active_in_pages', 'off');
		if (LayFrontend_Options::$footer_active_in_pages == 'all') {
			LayFrontend_Options::$pagesFooterId = get_option('lay_pages_footer', '');
		}

		LayFrontend_Options::$woocommercePagesFooterId = '';
		LayFrontend_Options::$footer_active_in_woocommerce_pages = get_option('lay_footer_active_in_pages', LayFrontend_Options::$footer_active_in_pages);
		if (LayFrontend_Options::$footer_active_in_woocommerce_pages == 'individual') {
			LayFrontend_Options::$footer_active_in_woocommerce_pages = 'all';
		}
		if (LayFrontend_Options::$footer_active_in_woocommerce_pages == 'all') {
			LayFrontend_Options::$woocommercePagesFooterId = get_option('lay_woocommerce_pages_footer', LayFrontend_Options::$pagesFooterId);
		}

		LayFrontend_Options::$newsFooterId = '';
		LayFrontend_Options::$footer_active_in_news = get_option('lay_footer_active_in_news', 'off');
		if (LayFrontend_Options::$footer_active_in_news == 'all') {
			LayFrontend_Options::$newsFooterId = get_option('lay_news_footer', '');
		}

		LayFrontend_Options::$deckFooterId = '';
		LayFrontend_Options::$footer_active_in_deck = get_option('lay_footer_active_in_deck', 'off');
		if (LayFrontend_Options::$footer_active_in_deck == 'all') {
			LayFrontend_Options::$deckFooterId = get_option('lay_deck_footer', '');
		}

		LayFrontend_Options::$categoriesFooterId = '';
		LayFrontend_Options::$footer_active_in_categories = get_option('lay_footer_active_in_categories', 'off');
		if (LayFrontend_Options::$footer_active_in_categories == 'all') {
			LayFrontend_Options::$categoriesFooterId = get_option('lay_categories_footer', '');
		}

		LayFrontend_Options::$headerPageId = get_option('lay_header_page', '');
		LayFrontend_Options::$headerPosition = get_option('lay_header_position', 'top');
		LayFrontend_Options::$headerTransparentBackground = get_option('lay_header_transparent_background', 'on');
		LayFrontend_Options::$headerHideOnPhone = get_option('lay_header_hide_on_phone', 'off');
		LayFrontend_Options::$misc_options_header = get_option('misc_options_header', '');
		LayFrontend_Options::$misc_options_header = LayFrontend_Options::$misc_options_header == 'on' ? true : false;

		LayFrontend_Options::$individual_project_footers = get_option('lay_individual_project_footers', '');
		LayFrontend_Options::$individual_news_footers = get_option('lay_individual_news_footers', '');
		LayFrontend_Options::$individual_deck_footers = get_option('lay_individual_deck_footers', '');
		LayFrontend_Options::$individual_page_footers = get_option('lay_individual_page_footers', '');
		LayFrontend_Options::$individual_category_footers = get_option('lay_individual_category_footers', '');

		LayFrontend_Options::$prevnext_navigate_through = get_option('misc_options_prevnext_navigate_through', 'all_projects');

		// LayFrontend_Options::$navigation_transition_in_duration = Frontend::get_navigation_transition_in_duration_in_milliseconds();
		// LayFrontend_Options::$navigation_transition_out_duration = Frontend::get_navigation_transition_out_duration_in_milliseconds();

		LayFrontend_Options::$activate_project_description = get_option('misc_options_activate_project_description', '');
		LayFrontend_Options::$activate_project_description = LayFrontend_Options::$activate_project_description == 'on' && !MiscOptions::$locked ? true : false;

		LayFrontend_Options::$fi_mo_touchdevice_behaviour = get_theme_mod('fi_mo_touchdevice_behaviour', 'mo_dont_show');

		LayFrontend_Options::$hide_current_project_from_thumbnailgrid = get_option('misc_options_hide_current_project_from_thumbnailgrid', 'on');
		LayFrontend_Options::$misc_options_image_loading_blur = get_option('misc_options_image_loading_blur', 'standard');

		/* 
		i changed the misc option inputs from radio to checkbox
		this used to be either "instant_load" or "lazy_load"
		but now it can be '' or 'on'
		'on' is when lazyload is turned on, '' is when lazyload is off
		i need to make sure in my code the values 'instant_load' and 'lazy_load' are handled too
		*/
		LayFrontend_Options::$image_loading = get_option('misc_options_image_loading', 'on');
		if (LayFrontend_Options::$image_loading == 'on') {
			LayFrontend_Options::$image_loading = 'lazy_load';
		} elseif (LayFrontend_Options::$image_loading == '') {
			LayFrontend_Options::$image_loading = 'instant_load';
		}

		LayFrontend_Options::$cover_active_in_projects = get_option('cover_active_in_projects', 'off');
		LayFrontend_Options::$cover_active_in_pages = get_option('cover_active_in_pages', 'off');
		LayFrontend_Options::$cover_active_in_categories = get_option('cover_active_in_categories', 'off');

		// for an unknown reason in rare cases the arrays are saved as associative arrays, so flatten them here
		LayFrontend_Options::$cover_individual_project_ids = CoverOptions::get_individual_project_ids();
		LayFrontend_Options::$cover_individual_page_ids = CoverOptions::get_individual_page_ids();
		LayFrontend_Options::$cover_individual_category_ids = CoverOptions::get_individual_category_ids();

		LayFrontend_Options::$cover_scrolldown_on_click = get_option('cover_scrolldown_on_click', '');
		LayFrontend_Options::$cover_scrolldown_on_click = LayFrontend_Options::$cover_scrolldown_on_click == 'on' ? true : false;
		LayFrontend_Options::$cover_darken_when_scrolling = get_option('cover_darken_when_scrolling', '');
		LayFrontend_Options::$cover_darken_when_scrolling = LayFrontend_Options::$cover_darken_when_scrolling == 'on' ? true : false;
		LayFrontend_Options::$cover_parallaxmove_when_scrolling = get_option('cover_parallaxmove_when_scrolling', '');
		LayFrontend_Options::$cover_parallaxmove_when_scrolling = LayFrontend_Options::$cover_parallaxmove_when_scrolling == 'on' ? true : false;

		LayFrontend_Options::$misc_options_cover = get_option('misc_options_cover', '');
		LayFrontend_Options::$misc_options_cover = LayFrontend_Options::$misc_options_cover == 'on' && !MiscOptions::$locked ? true : false;

		LayFrontend_Options::$turn_off_custom_code = get_option('misc_setting_turn_off_custom_code', '') == 'on' ? true : false;

		LayFrontend_Options::$misc_options_max_width_apply_to_logo_and_nav = get_option('misc_options_max_width_apply_to_logo_and_nav', '');
		LayFrontend_Options::$misc_options_max_width_apply_to_logo_and_nav = LayFrontend_Options::$misc_options_max_width_apply_to_logo_and_nav == 'on' ? true : false;
		LayFrontend_Options::$misc_options_max_width_apply_to_burger = get_option('misc_options_max_width_apply_to_burger', '');
		LayFrontend_Options::$misc_options_max_width_apply_to_burger = LayFrontend_Options::$misc_options_max_width_apply_to_burger == 'on' ? true : false;
		LayFrontend_Options::$maxwidth = get_option('misc_options_max_width', '0');

		LayFrontend_Options::$default = get_theme_mod('st_fontfamily', Customizer::$defaults['fontfamily']);
		LayFrontend_Options::$m_st_fontfamily = get_theme_mod('m_st_fontfamily', LayFrontend_Options::$default);

		LayFrontend_Options::$misc_options_showoriginalimages = get_option('misc_options_showoriginalimages', '');
		LayFrontend_Options::$misc_options_showoriginalimages = LayFrontend_Options::$misc_options_showoriginalimages == 'on' ? true : false;

		LayFrontend_Options::$misc_options_showoriginalimages_for_phone = get_option('misc_options_showoriginalimages_for_phone', '');
		LayFrontend_Options::$misc_options_showoriginalimages_for_phone = LayFrontend_Options::$misc_options_showoriginalimages_for_phone == 'on' ? true : false;

		LayFrontend_Options::$cover_disable_for_phone = get_option('cover_disable_for_phone', '');
		LayFrontend_Options::$cover_disable_for_phone = LayFrontend_Options::$cover_disable_for_phone == 'on' ? true : false;

		LayFrontend_Options::$phone_layout_active = get_option('misc_options_extra_gridder_for_phone', 'on');
		LayFrontend_Options::$phone_layout_active = LayFrontend_Options::$phone_layout_active == 'on' ? true : false;

		LayFrontend_Options::$misc_options_thumbnail_video_disabled_for_touch_devices = get_option('misc_options_thumbnail_video_disabled_for_touch_devices', '');
		LayFrontend_Options::$misc_options_thumbnail_video_disabled_for_touch_devices = LayFrontend_Options::$misc_options_thumbnail_video_disabled_for_touch_devices == 'on' && !MiscOptions::$locked ? true : false;

		LayFrontend_Options::$misc_options_thumbnail_video = get_option('misc_options_thumbnail_video', '');
		LayFrontend_Options::$misc_options_thumbnail_video = LayFrontend_Options::$misc_options_thumbnail_video == 'on' && !MiscOptions::$locked ? true : false;
		LayFrontend_Options::$misc_options_thumbnail_mouseover_image = get_option('misc_options_thumbnail_mouseover_image', '');
		LayFrontend_Options::$misc_options_thumbnail_mouseover_image = LayFrontend_Options::$misc_options_thumbnail_mouseover_image == 'on' && !MiscOptions::$locked ? true : false;

		LayFrontend_Options::$frame_leftright = get_option('gridder_defaults_frame', LayConstants::gridder_defaults_frame);

		LayFrontend_Options::$bg_color = get_theme_mod('bg_color', '#ffffff');
		if (LayFrontend_Options::$bg_color == '') {
			LayFrontend_Options::$bg_color = '#ffffff';
		}
		LayFrontend_Options::$bg_image = get_theme_mod('bg_image', '');

		LayFrontend_Options::$is_customize = is_customize_preview();
		LayFrontend_Options::$is_ssl = is_ssl();
		LayFrontend_Options::$has_www = LTUtility::has_www();

		LayFrontend_Options::$is_qtranslate_active = is_plugin_active('qtranslate-x/qtranslate.php') || is_plugin_active('qtranslate-xt-master/qtranslate.php') || class_exists('QTX_Translator');

		LayFrontend_Options::$intro_text_textformat = get_theme_mod('intro_text_textformat', 'Default');
		if (LayFrontend_Options::$intro_text_textformat != '') {
			LayFrontend_Options::$intro_text_textformat = '_' . LayFrontend_Options::$intro_text_textformat;
		}

		LayFrontend_Options::$element_transition_on_scroll = get_option('misc_options_element_transition_on_scroll', '');
		LayFrontend_Options::$element_transition_on_scroll = LayFrontend_Options::$element_transition_on_scroll == 'on' ? true : false;
		LayFrontend_Options::$video_thumbnail_mouseover_behaviour = get_theme_mod('fi_mo_video_behaviour', 'autoplay');

		LayFrontend_Options::$menu_submenu_keep_open = get_theme_mod('menu_submenu_keep_open', false);
		LayFrontend_Options::$menu_submenu_keep_open_2 = get_theme_mod('menu_submenu_keep_open_2', false);

		LayFrontend_Options::$menu_submenu_follow_parent_link_on_click_to_open_submenu = get_theme_mod('menu_submenu_follow_parent_link_on_click_to_open_submenu', false);

		// m menubar
		LayFrontend_Options::$mobile_menubar_move_away_when_scrolling_down = get_theme_mod('mobile_menubar_move_away_when_scrolling_down', false);
		LayFrontend_Options::$mobile_menubar_fade_out_when_scrolling_down = get_theme_mod('mobile_menubar_fade_out_when_scrolling_down', false);
		if (get_theme_mod('mobile_hide_menubar', 0) == 1 || get_theme_mod('m_menubar_isfixed', 1) != 1) {
			LayFrontend_Options::$mobile_menubar_move_away_when_scrolling_down = false;
			LayFrontend_Options::$mobile_menubar_fade_out_when_scrolling_down = false;
		}
		// m st
		LayFrontend_Options::$mobile_sitetitle_move_away_when_scrolling_down = get_theme_mod('mobile_sitetitle_move_away_when_scrolling_down', false);
		LayFrontend_Options::$mobile_sitetitle_fade_out_when_scrolling_down = get_theme_mod('mobile_sitetitle_fade_out_when_scrolling_down', false);

		if (get_theme_mod('m_st_hide', 0) == 1 || get_theme_mod('m_st_isfixed', 1) != 1) {
			LayFrontend_Options::$mobile_sitetitle_move_away_when_scrolling_down = false;
			LayFrontend_Options::$mobile_sitetitle_fade_out_when_scrolling_down = false;
		}
		// m menu
		LayFrontend_Options::$mobile_menu_move_away_when_scrolling_down = get_theme_mod('mobile_menu_move_away_when_scrolling_down', false);
		LayFrontend_Options::$mobile_menu_fade_out_when_scrolling_down = get_theme_mod('mobile_menu_fade_out_when_scrolling_down', false);

		$mobile_menu_fixed = get_theme_mod('mobile_menu_isfixed', '1');
		// $mobile_menu_fixed can be "" or false :)
		if ($mobile_menu_fixed == 1 || $mobile_menu_fixed === '1') {
			$mobile_menu_fixed = true;
		} else {
			$mobile_menu_fixed = false;
		}
		$mobile_menu_hidden = get_theme_mod('mobile_hide_menu', '0');
		if ($mobile_menu_hidden == 1 || $mobile_menu_hidden === '1') {
			$mobile_menu_hidden = true;
		} else {
			$mobile_menu_hidden = false;
		}

		if ($mobile_menu_hidden || !$mobile_menu_fixed || (get_theme_mod('mobile_menu_style', 'style_1') != 'style_desktop_menu' && get_theme_mod('mobile_menu_style', 'style_1') != 'button_menu_style')) {
			LayFrontend_Options::$mobile_menu_move_away_when_scrolling_down = false;
			LayFrontend_Options::$mobile_menu_fade_out_when_scrolling_down = false;
		}

		if (class_exists('WooCommerce')) {
			// woocommerce product thumbnail text/button positions
			$lay_woocommerce_pt_title_position = get_theme_mod('lay_woocommerce_pt_title_position', 'below-image');
			$lay_woocommerce_pt_price_position = get_theme_mod('lay_woocommerce_pt_price_position', 'below-image');
			$lay_woocommerce_pt_outofstock_position = get_theme_mod('lay_woocommerce_pt_outofstock_position', 'below-image');
			$lay_woocommerce_pt_shortdescr_position = get_theme_mod('lay_woocommerce_pt_shortdescr_position', 'below-image');
			$lay_woocommerce_pt_brands_position = get_theme_mod('lay_woocommerce_pt_brands_position', 'below-image');

			$lay_woocommerce_pt_positions = [
				'lay_woocommerce_pt_title_position' => $lay_woocommerce_pt_title_position,
				'lay_woocommerce_pt_shortdescr_position' => $lay_woocommerce_pt_shortdescr_position,
				'lay_woocommerce_pt_brands_position' => $lay_woocommerce_pt_brands_position,
				'lay_woocommerce_pt_price_position' => $lay_woocommerce_pt_price_position,
				'lay_woocommerce_pt_outofstock_position' => $lay_woocommerce_pt_outofstock_position,
			];

			LayFrontend_Options::$woo_prth_topleft = [];
			LayFrontend_Options::$woo_prth_topright = [];
			LayFrontend_Options::$woo_prth_center = [];
			LayFrontend_Options::$woo_prth_bottomleft = [];
			LayFrontend_Options::$woo_prth_bottomright = [];
			// add to cart is always position below so i add it here (needs to be below because i cant put a link inside a link):
			LayFrontend_Options::$woo_prth_below = [];

			LayFrontend_Options::$lay_woocommerce_pt_show_brands = get_theme_mod('lay_woocommerce_pt_show_brands', 0) == 1 ? true : false;
			LayFrontend_Options::$lay_woocommerce_pt_show_add_to_cart_button = get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0) == 1 ? true : false;

			foreach ($lay_woocommerce_pt_positions as $key => $value) {
				if ($value == 'on-image-top-left') {
					LayFrontend_Options::$woo_prth_topleft[] = $key;
				}
				if ($value == 'on-image-top-right') {
					LayFrontend_Options::$woo_prth_topright[] = $key;
				}
				if ($value == 'on-image') {
					LayFrontend_Options::$woo_prth_center[] = $key;
				}
				if ($value == 'on-image-bottom-left') {
					LayFrontend_Options::$woo_prth_bottomleft[] = $key;
				}
				if ($value == 'on-image-bottom-right') {
					LayFrontend_Options::$woo_prth_bottomright[] = $key;
				}
				if ($value == 'below-image') {
					LayFrontend_Options::$woo_prth_below[] = $key;
				}
			}

			LayFrontend_Options::$woo_prth_below[] = 'pt_addtocart_position';
		}
	}
}

new LayFrontend_Options();
