<?php
include_once(get_template_directory().'/assets/plugins/tinywp-mobile-detect/mobile-detect.php');

if( !function_exists('lay_dom_cursor_manager_available') ){
	function lay_dom_cursor_manager_available(){
		return true;
	}
}

// order of styles

// frontend style: frontend_styles
// desktop and phone styles: essential_head_styles_and_desktop_and_phone_styles
// textformats: frontend_textformats_css
// customizer styles: lay_customize_css
// custom styles: get_custom_lay_css

class Frontend{

	public static $topframe_mu;
	public static $bottomframe_mu;
	public static $current_type_id_slug_catid;

	private static function translate_qtranslate_text($text){
		if ( class_exists('Setup') ) {
			return Setup::apply_qtranslate($text);
		}
		return $text;
	}

	public function __construct(){
		// any customizer styles need to come after default styles in order to overwrite default styles
		// meaning customizer styles add_action needs priority number higher than frontend_styles
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 9 );
		// add_action( 'wp_enqueue_scripts', array($this, 'radio_channel_scripts'), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_styles' ), 9);
		// need to put this in head so elements are aligned top/bottom/middle without realigning themselves after css has loaded
		add_action( 'wp_enqueue_scripts', array( $this, 'essential_head_styles_and_desktop_and_phone_styles' ), 12 );
		add_action( 'rest_api_init', array( $this, 'include_griddermeta_in_term') );
		add_action( 'rest_api_init', array( $this, 'include_griddermeta_in_post') );
		add_action( 'rest_api_init', array( $this, 'include_griddermeta_in_product') );
		add_action( 'rest_api_init', array( $this, 'include_griddermeta_in_page') );

		add_action( 'wp_head', array($this, 'set_current_data') );
		add_filter( 'body_class', array( $this, 'add_body_classes'), 10, 1 );
		// Prevent Cloudflare Rocket Loader from delaying critical early scripts.
		add_filter( 'script_loader_tag', array( $this, 'disable_rocket_loader_for_critical_scripts' ), 10, 3 );
	}

	// need this because i put the frontend app in the footer, but ppl access window.laytheme in <head>
	public static function radio_channel_scripts(){

	}

	public static function set_current_data(){
		Frontend::$current_type_id_slug_catid = Frontend::get_current_type_id_slug_catid();
		$d = Frontend::$current_type_id_slug_catid;
	}

	public function disable_rocket_loader_for_critical_scripts($tag, $handle, $src){
		$critical_handles = array(
			'lay-frontend-events-channels',
			'frontend-critical-init',
		);

		if( !in_array($handle, $critical_handles, true) ){
			return $tag;
		}

		if( strpos($tag, 'data-cfasync=') !== false ){
			return $tag;
		}

		return preg_replace('/<script\b/', '<script data-cfasync="false"', $tag, 1);
	}

	public static function get_current_type_id_slug_catid(){
		$type = '';
		$id = '';
		$projectcatid = '';
		$slug = '';

		global $post;

		// post is null if page doesnt exist. then just show frontpage
		if(is_category()){
			$category = get_category( get_query_var( 'cat' ) );
			$cat_id = $category->cat_ID;

			$slug = $category->slug;
			$type = 'category';
			$id = $cat_id;
		}
		else if(is_archive()){
			$queried_obj = get_queried_object();
			$cat_id = '';
			$slug = $queried_obj->name;
            if( $slug == 'lay_news' ) {
                // instead of getting the archive, get a page that is called like the slug
                $slug = $queried_obj->rewrite['slug'];
                $page = get_page_by_path($slug);
                if( $page instanceof WP_Post ) {
                    $type = 'page';
                    $id = $page->ID;
                } else {
                    $type = 'archive';
                }
            } else {
                $type = 'archive';
			    // shop page is an archive even though it is under "pages"
                if( $slug == 'product' ){
                    // this is how to get the page id of the woocommerce shop page which is a product archive
                    // https://gist.github.com/Bradley-D/7287723
                    $id = get_option( 'woocommerce_shop_page_id' );
                }
            }
		}
		else if(is_front_page() || is_null($post)){

			$type = get_theme_mod('frontpage_type', 'category');
			switch ($type) {
				case 'category':
					$id = get_theme_mod('frontpage_select_category', 1);
                    $id = Frontend::get_polylang_translated_id($id, 'category');
					$category = get_category( $id );
					$slug = $category->slug;
				break;
				case 'project':
					$id = get_theme_mod('frontpage_select_project');
                    $id = Frontend::get_polylang_translated_id($id, 'project');
					$projectcatid = json_encode(wp_get_post_categories($id, array('fields'=>'ids')));
					$post = get_post($id);
					$slug = $post->post_name;
				break;
				case 'page':
					$id = get_theme_mod('frontpage_select_page');
                    $id = Frontend::get_polylang_translated_id($id, 'page');
					$post = get_post($id);
					$slug = $post->post_name;
				break;
				case 'deck':
					// Skip if Deck feature was disabled after this theme_mod was saved.
					if ( ! post_type_exists('lay_deck') ) { $type = ''; break; }
					$id = get_theme_mod('frontpage_select_deck');
                    $id = Frontend::get_polylang_translated_id($id, 'lay_deck');
					$post = get_post($id);
					if ($post) {
						$slug = $post->post_name;
						$type = 'lay_deck';
					} else {
						$type = '';
					}
				break;
			}

		}
		else if(is_single()){
			$posttype = get_post_type($post->ID);
			switch( $posttype ) {
				case 'post':
					$type = 'project';
				break;
				default:
					// could be "product"
					$type = $posttype;
				break;
			}
			$id = $post->ID;
			$slug = $post->post_name;
			$projectcatid = json_encode(wp_get_post_categories($id, array('fields'=>'ids')));
		}
		else if( class_exists('Woocommerce') && is_wc_endpoint_url() ){
			$type = 'custom';
			$id = $post->ID;
			$slug = $post->post_name;
		}
		else{
			$type = 'page';
			$id = $post->ID;
			$slug = $post->post_name;
		}

		return array(
			"type" => $type,
			"id" => $id,
			"projectcatid" => $projectcatid,
			"slug" => $slug,
            "footerid" => Lay_Footer::getFooterId($id, $type)
		);
	}

	public static function get_body_data() {
		$data = Frontend::$current_type_id_slug_catid;
        $horizontal_scroll_active = '0';
        if ( class_exists('LayHorizontalScroll') ) {
            $horizontal_scroll_active = LayHorizontalScroll::is_currently_active() ? '1' : '0';
        }
		return 'data-type="'.$data["type"].'" data-id="'.$data["id"].'" data-catid="'.$data["projectcatid"].'" data-slug="'.$data["slug"].'" data-footerid="'.$data['footerid'].'" data-horizontal-scroll-active="'.$horizontal_scroll_active.'"';
	}

	public static function add_body_classes($classes){
		$data = Frontend::$current_type_id_slug_catid;
		$data_is_array = is_array($data);
		$type_val = ($data_is_array && isset($data['type'])) ? $data['type'] : '(not set)';
		$id_val = ($data_is_array && isset($data['id'])) ? $data['id'] : '(not set)';
		$slug_val = ($data_is_array && isset($data['slug'])) ? $data['slug'] : '(not set)';
		$mobile_menu_style_class = 'mobile-menu-'.get_theme_mod('mobile_menu_style', 'style_1');
		$mobile_menu_do_animation = get_theme_mod('mobile_menu_do_animation', true) == 1 ? 'mobile-menu-has-animation' : 'mobile-menu-no-animation';
		
		$thumb_mo_image_has_transition = get_theme_mod('thumb_mo_image_has_transition', false) == 1 ? 'thumb-mo-image-has-transition' : 'thumb-mo-image-no-transition';
		$fi_mo_bg_as_fallback = get_theme_mod('fi_mo_bg_as_fallback', false) == 1 ? 'thumb-mo-bg-as-fallback' : '';

        $thumbnail_video_and_mouseover_image_behavior = get_option('misc_options_thumbnail_video_and_mouseover_image_behavior', 'image');

		$language = '';
		if( function_exists('qtranxf_getLanguage') ){
			$language = 'language-'.qtranxf_getLanguage();
		}
		$category_classes = '';
		if( is_array( $data ) && array_key_exists('projectcatid', $data) ) {
			$catids = json_decode($data["projectcatid"]);
			if( is_array( $catids ) ) {
				foreach( $catids as $catid ) {
					$category_classes .= 'category-'.$catid.' ';
				}
			}
		}

		// https://gist.github.com/Bradley-D/7287723
		/*
		get_option( 'woocommerce_shop_page_id' ); 
		get_option( 'woocommerce_cart_page_id' ); 
		get_option( 'woocommerce_checkout_page_id' );
		get_option( 'woocommerce_pay_page_id' ); 
		get_option( 'woocommerce_thanks_page_id' ); 
		get_option( 'woocommerce_myaccount_page_id' ); 
		get_option( 'woocommerce_edit_address_page_id' ); 
		get_option( 'woocommerce_view_order_page_id' ); 
		get_option( 'woocommerce_terms_page_id' ); 
		*/
		if( class_exists('WooCommerce') ) {
			$woocommerce_terms_page_id = get_option('woocommerce_terms_page_id');
			if( is_array( $data ) && $data["id"] == $woocommerce_terms_page_id ) {
				$classes['lay_woocommerce'] = 'terms-and-conditions woocommerce-page';
			}
			$privacy_policy_id = wc_privacy_policy_page_id();
			if( is_array( $data ) && $data["id"] == $privacy_policy_id ) {
				$classes['lay_woocommerce_2'] = 'woocommerce-page';
			}

            // lay-woocommerce-round-buttons
            // lay-woocommerce-big-buttons
            // lay-woocommerce-small-buttons

            // lay-woocommerce-big-swatches-buttons
            // lay-woocommerce-small-swatches-buttons
            // lay-woocommerce-round-swatches-buttons
            $qty_type = get_theme_mod('lay_woocommerce_singleproduct_quantity_type', 'big');
            $classes['lay_woocommerce_buttons'] = 'lay-woocommerce-'.$qty_type.'-buttons';

            if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() && get_theme_mod( 'lay_woocommerce_checkout_use_placeholder_instead_of_labels', '' ) == 1 ) {
                $classes['lay_checkout_use_placeholders'] = 'lay-checkout-use-placeholders';
            }
		}


		$detect = new TinyWP_Mobile_Detect;
		$touchdevice_class = $detect->isMobile() ? 'touchdevice' : 'no-touchdevice';

		// class to hide content while intro has not rendered yet
		$intro_loading = (is_front_page() && LayIntro::$isActive) ? 'intro-loading' : '';

		$mobile_hide_menubar = get_theme_mod('mobile_hide_menubar') == 1 ? 'mobile_menu_bar_hidden' : 'mobile_menu_bar_not_hidden';

		// add my classes to classes array
		$classes['lay_intro_loading'] = $intro_loading;
		$classes['lay_touchdevice_class'] = $touchdevice_class;
		if( is_array( $data ) ) {
			$classes['lay_type'] = 'type-'.$data["type"];
			$classes['lay_id'] = 'id-'.$data["id"];
			$classes['lay_slug'] = 'slug-'.$data["slug"];

			// On deck pages, opt-in to chrome-hide CSS unless the user
			// explicitly disabled it. Default ('on' when option is unset).
			if ( $data["type"] === 'lay_deck'
				&& get_option('misc_options_activate_deck_feature', '') === 'on'
				&& get_option('misc_options_deck_hide_chrome', 'on') !== '' ) {
				$classes['lay_deck_hide_chrome'] = 'lay-deck-hide-chrome';
			}
		}
		$classes['lay_mobile_menu_style_class'] = $mobile_menu_style_class;
		$classes['lay_mobile_menu_do_animation'] = $mobile_menu_do_animation;
		$classes['lay_thumb_mo_image_has_transition'] = $thumb_mo_image_has_transition;
		$classes['lay_fi_mo_bg_as_fallback'] = $fi_mo_bg_as_fallback;
        $classes['lay_thumbnail_video_and_mouseover_image_behavior'] = 'thumb-video-and-mouseover-image-behavior-show-'.$thumbnail_video_and_mouseover_image_behavior;
		$classes['lay_language'] = $language;
		$classes['lay_category_classes'] = $category_classes;
		$classes['lay_mobile_menubar_hidden'] = $mobile_hide_menubar;
		$classes['lay_mobile_burger_style'] = 'mobile_burger_style_'.get_theme_mod('burger_icon_type', 'default_thin');

		$classes['lay_transition_elements_on_scroll'] = get_option('misc_options_element_transition_on_scroll', '') == 'on' && !MiscOptions::$locked ? 'lay-transition-elements-on-scroll' : '';
		// woocommerce class
		if ( function_exists( 'is_store_notice_showing' ) ) {
			$classes['lay_wc_store_notice_showing'] = is_store_notice_showing() == 1 ? 'lay-woocommerce-show-store-notice' : 'lay-woocommerce-hide-store-notice';
		}

        $lay_sticky_footer = get_option('lay_sticky_footer', 'on');
        $classes['lay_sticky_footer'] = $lay_sticky_footer == 'on' ? 'sticky-footer-option-enabled' : 'sticky-footer-option-disabled';
        $classes['lay_intro'] = (is_front_page() && LayIntro::$isActive) ? 'intro-enabled' : 'intro-disabled';

		$val = get_option('misc_options_image_loading_blur', 'standard');
		
		$use_blur = $val == 'on' || $val == 'blur';
		$use_pixelate = $val == 'pixelated';
		$use_pixelate_animated = $val == 'pixelated-animated';

        $classes['lazyload_blur'] = $use_blur && get_option('misc_options_image_loading', 'on') == 'on' ? 'lazyload-blur' : '';
        $classes['lazyload_pixelated'] = $use_pixelate && get_option('misc_options_image_loading', 'on') == 'on' ? 'lazyload-pixelated' : '';
        $classes['lazyload_pixelated_animated'] = $use_pixelate_animated && get_option('misc_options_image_loading', 'on') == 'on' ? 'lazyload-pixelated-animated' : '';

        $protected = post_password_required();
        if( $protected ) {
            $classes['lay_protected'] = 'protected';
        } else {
            $classes['lay_protected'] = 'not-protected';
        }

		$max_width = get_option('misc_options_max_width', '0');
		$max_width = (int)$max_width;
		if( $max_width > 0 ) {
			$classes['lay_max_width'] = 'using-max-width';
		} else {
			$classes['lay_max_width'] = 'not-using-max-width';
		}

		// Page settings: hide site title / hide menu / hide footer (post meta or term meta)
		if ( is_array( $data ) && ! empty( $data['id'] ) ) {
			$hide_site_title = '';
			$hide_menu = '';
			$hide_menu_2 = '';
			$hide_menu_3 = '';
			$hide_menu_4 = '';
			$hide_footer = '';
			if ( $data['type'] === 'category' ) {
				$hide_site_title = get_term_meta( (int) $data['id'], '_lay_hide_site_title', true );
				$hide_menu = get_term_meta( (int) $data['id'], '_lay_hide_menu', true );
				$hide_menu_2 = get_term_meta( (int) $data['id'], '_lay_hide_menu_2', true );
				$hide_menu_3 = get_term_meta( (int) $data['id'], '_lay_hide_menu_3', true );
				$hide_menu_4 = get_term_meta( (int) $data['id'], '_lay_hide_menu_4', true );
				$hide_footer = get_term_meta( (int) $data['id'], '_lay_hide_footer', true );
			} else {
				$hide_site_title = get_post_meta( (int) $data['id'], '_lay_hide_site_title', true );
				$hide_menu = get_post_meta( (int) $data['id'], '_lay_hide_menu', true );
				$hide_menu_2 = get_post_meta( (int) $data['id'], '_lay_hide_menu_2', true );
				$hide_menu_3 = get_post_meta( (int) $data['id'], '_lay_hide_menu_3', true );
				$hide_menu_4 = get_post_meta( (int) $data['id'], '_lay_hide_menu_4', true );
				$hide_footer = get_post_meta( (int) $data['id'], '_lay_hide_footer', true );
			}
			if ( $hide_site_title === 'on' ) {
				$classes['lay_hide_sitetitle'] = 'lay-hide-sitetitle';
			}
			if ( $hide_menu === 'on' ) {
				$classes['lay_hide_menu'] = 'lay-hide-menu';
			}
			if ( $hide_menu_2 === 'on' ) {
				$classes['lay_hide_menu_2'] = 'lay-hide-menu-2';
			}
			if ( $hide_menu_3 === 'on' ) {
				$classes['lay_hide_menu_3'] = 'lay-hide-menu-3';
			}
			if ( $hide_menu_4 === 'on' ) {
				$classes['lay_hide_menu_4'] = 'lay-hide-menu-4';
			}
			if ( $hide_footer === 'on' ) {
				$classes['lay_hide_footer'] = 'lay-hide-footer';
			}
		}

		$type_class = isset($classes['lay_type']) ? $classes['lay_type'] : '(missing)';
		$id_class = isset($classes['lay_id']) ? $classes['lay_id'] : '(missing)';
		$slug_class = isset($classes['lay_slug']) ? $classes['lay_slug'] : '(missing)';
		return $classes;
	}

	public static function get_max_width_option_css(){
		$maxwidth = get_option( 'misc_options_max_width', '0' );

		if($maxwidth != '0'){
			echo '<!-- max width option -->';
			echo
			'<style>@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){.row-inner{margin-left:auto;margin-right:auto;max-width:'.$maxwidth.'px;}}</style>';
		}
	}

	public static function get_custom_lay_css(){
		if( !LayFrontend_Options::$turn_off_custom_code ) {
			$desktopCSS = get_option( 'misc_options_desktop_css', '' );

			if($desktopCSS != ''){
				echo '<!-- custom css for desktop version -->';
				echo '<style>@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){'.$desktopCSS.'}</style>';
			}
	
			$mobileCSS = get_option( 'misc_options_mobile_css', '' );
			if($mobileCSS != ''){
				echo '<!-- custom css for mobile version -->';
				echo '<style>@media (max-width: '.MiscOptions::$phone_breakpoint.'px){'.$mobileCSS.'}</style>';
			}
		}
	}

	/**
	 * Scope custom CSS to a prefix using CSS Nesting (modern browsers).
	 * Wraps the CSS in "prefix { ... }" so nested rules apply under the prefix.
	 * Example: body.id-4038 { .hello { color: red; } } → body.id-4038 .hello { color: red; }
	 * Requires CSS Nesting support (Chrome 112+, Safari 16.5+, Firefox 117+).
	 */
	public static function scope_custom_css_with_prefix($css, $prefix){
		$css = trim($css);
		if( $css === '' ){
			return $css;
		}
		$prefix = trim($prefix);
		if( $prefix === '' ){
			return $css;
		}
		return $prefix . " {\n" . $css . "\n}";
	}

	public static function get_custom_head_content() {

		if( !LayFrontend_Options::$turn_off_custom_code ) {
			$customHeadContent = get_option( 'misc_options_analytics_code', '' );
			if($customHeadContent != ''){
				echo '<!-- custom head content -->';
				echo $customHeadContent;
			}
		}

        $val = get_option('misc_options_use_revealing_transition_on_first_visit', '');
        $insertcss = false;
        if( $val == 'on' ) {
            $insertcss = true;
        }
        if( get_option( 'misc_options_navigation_transition_in_duration', '0.4' ) == 0 ) {
            $insertcss = false;
        }
        if( get_option( 'misc_options_navigation_transition_in', 'fade' ) == 'none' ) {
            $insertcss = false;
        }
        if( MiscOptions::$locked ) {
            $insertcss = false;
        }
        // Deck slides intentionally never play the first-visit reveal — without
        // this guard the server-rendered `body .lay-content { opacity: 0 }`
        // would stick because the JS reveal is suppressed for decks. Covers
        // both direct slide URLs and decks-as-frontpage (where WP's main query
        // is a different post but Lay still renders the slide via
        // Frontend::$current_type_id_slug_catid).
        $is_deck_view = is_singular( 'lay_deck' );
        if ( ! $is_deck_view && is_front_page() && get_theme_mod( 'frontpage_type', 'category' ) === 'deck' && post_type_exists( 'lay_deck' ) ) {
            $is_deck_view = true;
        }
        if ( $is_deck_view ) {
            $insertcss = false;
        }

        if( $insertcss ) {
            // need to hide content before showing it :)
            echo
            '<style id="lay-hide-wrap-css">
                body .lay-content{
                    opacity: 0;
                }
                /* because we need to wait for masonry to initalize before starting animation */
                .col.type-thumbnailgrid .thumbnail-wrap{opacity: 0;}
                .col.type-elementgrid .element-wrap{opacity: 0;}
            </style>';
            // we also fade in site title, menu, menubar etc
            // in fadeIn_SiteTitle_MenuBar_Menu_etc in animations_controller.js
            // need !important for transition bc of .laynav hidescrolldown css
            //
            // `.laynav li:not(.sub-menu li)` is also hidden so the stagger
            // path can fade each top-level nav item individually (instead of
            // fading the .laynav block as a whole). Sub-menu items are
            // excluded — they're hidden until hover/click and have their own
            // visibility behaviour. The matching show stylesheet appended
            // from JS targets the same selectors.
            echo
            '<style id="lay-hide-sitetitle-menubar-menu-etc">
                .sitetitle, .overlay-burger, .laynav, .laynav li:not(.sub-menu li), #lay_canvas, .navbar, .lay-fadein, .mobile-title, .burger-wrap, body #fp-nav, body .fp-slidesNav, .mobile-menu-style-style_desktop_menu, .lay-background-video, .lay-background-image{
                    opacity: 0;
                    transition: opacity '.Frontend::get_navigation_transition_in_duration_in_milliseconds().'ms ease!important;
                }
            </style>';
        }
	}

    public static function get_navigation_transition_in_duration_in_milliseconds(){
        $default = get_option( 'misc_options_navigation_transition_duration', '0.3' );
        if( $default == '' ) {
            $default = '0.3';
        }
        $default = (float)$default;
        $default /= 2;
        $default *= 1000;
        $val = get_option( 'misc_options_navigation_transition_in_duration', $default );
        return $val;
    }

    public static function get_navigation_transition_out_duration_in_milliseconds(){
        $default = get_option( 'misc_options_navigation_transition_duration', '0.3' );
        if( $default == '' ) {
            $default = '0.3';
        }
        $default = (float)$default;
        $default /= 2;
        $default *= 1000;
        $val = get_option( 'misc_options_navigation_transition_out_duration', $default );
        return $val;
    }

	// public static function get_transition_duration_in_milliseconds(){
	// 	$navigation_transition_duration = get_option( 'misc_options_navigation_transition_duration', '0.3' );
	// 	$navigation_transition_duration = (float)$navigation_transition_duration;
	// 	// to milliseconds
	// 	$navigation_transition_duration *= 1000;
	// 	return $navigation_transition_duration;
	// }

	public static function get_meta(){

		// https://codex.wordpress.org/Function_Reference/is_plugin_active
		// include needed to use is_plugin_active
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active('wordpress-seo/wp-seo.php')){
			// bail out early if yoast seo plugin is active, because it provides all these metatags already
			return;
		}

		$description = self::translate_qtranslate_text(get_option( 'misc_options_website_description', '' ));
		$fbimage_id = get_option( 'misc_options_fbimage', '' );
		$fbimage = wp_get_attachment_image_src( $fbimage_id, 'full' );

		$title = self::translate_qtranslate_text(get_bloginfo('name'));

		// for google
		if( is_single() ){
			global $post;
			$project_descr = get_post_meta( $post->ID, 'lay_project_description', true );
			$project_descr = self::translate_qtranslate_text($project_descr);
			$project_descr = strip_tags($project_descr);

			if($project_descr != ""){
				echo '<meta name="description" content="'.__($project_descr).'"/>';
			}

            $attid = get_post_thumbnail_id($post);
            $fbimage = wp_get_attachment_image_src($attid, 'full');
		}else{
			if($description != ''){
				echo '<meta name="description" content="'.__($description).'"/>';
			}
		}

		// og tags
		if($fbimage){
			echo
			'<meta property="og:image" content="'.$fbimage[0].'">
			<meta property="og:image:width" content="'.$fbimage[1].'">
			<meta property="og:image:height" content="'.$fbimage[2].'">';
		}
		echo
		'<meta property="og:title" content="'.__($title).'">
		<meta property="og:site_name" content="'.__($title).'">';

		if($description != ''){
			echo '<meta property="og:description" content="'.__($description).'">';
		}

		if($fbimage){
			echo
			'<meta name="twitter:card" content="summary_large_image">
			<meta name="twitter:title" content="'.__($title).'">
			<meta name="twitter:image" content="'.$fbimage[0].'">';
		}
		else{
			echo
			'<meta name="twitter:card" content="summary">
			<meta name="twitter:title" content="'.__($title).'">';
		}

		if($description != ''){
			echo '<meta name="twitter:description" content="'.__($description).'">';
		}

	}

	public static function get_polylang_translated_id($id, $type){
		// get correct id for translated post/cat/term
		if( class_exists( 'PLL_Switcher' ) && function_exists( 'pll_current_language' ) ){
			$currentId = $id;
			$currentType = $type;
			
			$current_language = pll_current_language();
			if( $currentType == 'category' ) {
				$id = pll_get_term($currentId, $current_language);
			} else {
				$id = pll_get_post($currentId, $current_language);
			}
			return $id;
		} else {
			return $id;
		}
	}

	public static function get_home_link_data(){
		$type = get_theme_mod('frontpage_type', 'category');
		$id = '';
		$projectcatid = '';

		switch ($type) {
			case 'category':
				$id = get_theme_mod('frontpage_select_category', 1);
				$id = Frontend::get_polylang_translated_id($id, 'category');
			break;
			case 'project':
				$id = get_theme_mod('frontpage_select_project');
				$id = Frontend::get_polylang_translated_id($id, 'project');
				$cats = get_the_category( $id );
				$catids = array();
				foreach( $cats as $cat ) {
					$catids []= $cat->term_id;
				}  
				$projectcatid = json_encode($catids);
			break;
			case 'page':
				$id = get_theme_mod('frontpage_select_page');
				$id = Frontend::get_polylang_translated_id($id, 'page');
			break;
			case 'deck':
				// Skip if Deck feature was disabled after this theme_mod was saved.
				if ( ! post_type_exists('lay_deck') ) { break; }
				$id = get_theme_mod('frontpage_select_deck');
				$id = Frontend::get_polylang_translated_id($id, 'lay_deck');
				$type = 'lay_deck';
			break;
		}

		$title = Setup::get_title();
		// we need to have an empty data-title here, otherwise the <title> tag wont be set properly
		$return = 'data-title="" data-type="'.$type.'" data-id="'.$id.'" data-catid="'.$projectcatid.'"';

		return $return;
	}

	public static function get_footer(){
		// sitewide background video
		$bg_video = get_theme_mod('bg_video', "");
		if( $bg_video != "" ) {
			echo '<video class="lay-sitewide-background-video" data-src="'.$bg_video.'" muted autoplay loop></video>';
		}
		$bg_video_mobile = get_theme_mod('bg_video_mobile', "");
		if( $bg_video_mobile != "" ) {
			echo '<video class="lay-sitewide-background-video-mobile" data-src="'.$bg_video_mobile.'" muted autoplay loop></video>';
		}

		if( !LayFrontend_Options::$turn_off_custom_code ) {
			$custom_html_bottom = get_option( 'misc_options_custom_htmlbottom', '' );
			if( $custom_html_bottom != "" ){
				echo $custom_html_bottom;
			}
		}

	}

	public static function get_project_arrows(){
		//project arrows
		$showArrows = get_option('misc_options_show_project_arrows', '');
		if($showArrows == "on" && !MiscOptions::$locked){

			$pa_hide_prev = get_theme_mod('pa_hide_prev', '');
			$pa_type = get_theme_mod('pa_type', 'icon');
			$pa_position = get_theme_mod('pa_position', 'center');
			$prevA = '';
			$nextA = '';
			// need to use "pa-inner" because there i can use transform: rotate.
			// when i would use transform: rotate on the parent element ".project-arrow" there is some z-index or stacking context bug on iphone safari,
			// causing the project arrow to disappear behind html5 videos 
			switch($pa_type){
				case 'icon':
					$icon = get_theme_mod('pa_icon', '&#9654;');
					$prevA = '<a data-type="project" class="pa-prev project-arrow pa-icon pa-mirror '.$pa_position.'"><div class="pa-inner">'.$icon.'</div></a>';
					$nextA = '<a data-type="project" class="pa-next project-arrow pa-icon '.$pa_position.'"><div class="pa-inner">'.$icon.'</div></a>';
				break;
				case 'text':
					$next = get_theme_mod('pa_next_text', 'Next');
					$prev = get_theme_mod('pa_prev_text', 'Previous');
					$prevA = '<a data-type="project" class="pa-prev project-arrow pa-text '.$pa_position.'"><div class="pa-inner">'.$prev.'</div></a>';
					$nextA = '<a data-type="project" class="pa-next project-arrow pa-text '.$pa_position.'"><div class="pa-inner">'.$next.'</div></a>';
				break;
				case 'project-thumbnails':
					$prevA = '<a data-type="project" class="pa-prev project-arrow pa-thumb '.$pa_position.'"></a>';
					$nextA = '<a data-type="project" class="pa-next project-arrow pa-thumb '.$pa_position.'"></a>';
				break;
				case 'custom-image':
					$src = get_theme_mod('pa_image', '');
					$prevA = '<a data-type="project" class="pa-prev project-arrow pa-img pa-mirror '.$pa_position.'"><div class="pa-inner"><img src="'.$src.'" alt=""></div></a>';
					$nextA = '<a data-type="project" class="pa-next project-arrow pa-img '.$pa_position.'"><div class="pa-inner"><img src="'.$src.'" alt=""></div></a>';
				break;
			}

			if($pa_hide_prev == "on"){
				echo $nextA;
			}
			else{
				echo $prevA;
				echo $nextA;
			}

		}
	}

	// Helper function to get site title link URL
	private static function get_sitetitle_link_url($prefix = 'st_'){
		$homeurl = get_bloginfo('url');
		
		// Check if custom link is enabled
		$link_enabled = get_theme_mod($prefix . 'link_to_different_page', false);
		if(!$link_enabled){
			return $homeurl;
		}
		
		// Get the link type
		$link_type = get_theme_mod($prefix . 'link_type', 'category');
		
		switch($link_type){
			case 'category':
				$category_id = get_theme_mod($prefix . 'link_select_category', '1');
				if($category_id){
					$category_link = get_category_link($category_id);
					if($category_link && !is_wp_error($category_link)){
						return $category_link;
					}
				}
				break;
			case 'page':
				$page_id = get_theme_mod($prefix . 'link_select_page', '');
				if($page_id){
					$page_link = get_permalink($page_id);
					if($page_link && !is_wp_error($page_link)){
						return $page_link;
					}
				}
				break;
			case 'project':
				$project_id = get_theme_mod($prefix . 'link_select_project', '');
				if($project_id){
					$project_link = get_permalink($project_id);
					if($project_link && !is_wp_error($project_link)){
						return $project_link;
					}
				}
				break;
		}
		
		// Fallback to home URL
		return $homeurl;
	}

	// Helper function to get site title link data attributes
	private static function get_sitetitle_link_data($prefix = 'st_'){
		// Check if custom link is enabled
		$link_enabled = get_theme_mod($prefix . 'link_to_different_page', false);
		if(!$link_enabled){
			// Use default home link data
			return self::get_home_link_data();
		}
		
		// Get the link type
		$type = get_theme_mod($prefix . 'link_type', 'category');
		$id = '';
		$projectcatid = '';
		
		switch($type){
			case 'category':
				$id = get_theme_mod($prefix . 'link_select_category', '1');
				$id = Frontend::get_polylang_translated_id($id, 'category');
				break;
			case 'page':
				$id = get_theme_mod($prefix . 'link_select_page', '');
				$id = Frontend::get_polylang_translated_id($id, 'page');
				break;
			case 'project':
				$id = get_theme_mod($prefix . 'link_select_project', '');
				$id = Frontend::get_polylang_translated_id($id, 'project');
				$cats = get_the_category($id);
				$catids = array();
				foreach($cats as $cat){
					$catids[] = $cat->term_id;
				}
				$projectcatid = json_encode($catids);
				break;
		}
		
		// Return data attributes similar to get_home_link_data()
		return 'data-title="" data-type="'.$type.'" data-id="'.$id.'" data-catid="'.$projectcatid.'"';
	}

	/**
	 * Returns page-settings CSS (menu colors, overlay burger) for a given page/category.
	 * Used for initial header output and for AJAX layout response so page settings apply after navigation.
	 *
	 * @param array $data Must have 'id' and 'type' (e.g. 'page', 'category').
	 * @return array Associative array of style id => inner CSS content (no <style> wrapper). Empty string means no rules.
	 */
	public static function get_page_settings_styles_for_data( $data ) {
		$result = array(
			'lay-page-settings-menu-colors' => '',
			'lay-page-settings-phone-menu-colors' => '',
			'lay-page-settings-overlay-burger-color' => '',
			'lay-page-settings-phone-overlay-burger-color' => '',
		);
		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			return $result;
		}
		$id = (int) $data['id'];
		$type = isset( $data['type'] ) ? $data['type'] : 'page';
		$is_category = $type === 'category';
		$get_meta = $is_category ? 'get_term_meta' : 'get_post_meta';

		$menu_color_enable = $get_meta( $id, '_lay_menu_color_enable', true ) === 'on';
		$menu_color = $menu_color_enable ? $get_meta( $id, '_lay_menu_color', true ) : '';
		$menu_bar_color_enable = $get_meta( $id, '_lay_menu_bar_color_enable', true ) === 'on';
		$menu_bar_color = $menu_bar_color_enable ? $get_meta( $id, '_lay_menu_bar_color', true ) : '';
		$site_title_color_enable = $get_meta( $id, '_lay_site_title_color_enable', true ) === 'on';
		$site_title_color = $site_title_color_enable ? $get_meta( $id, '_lay_site_title_color', true ) : '';
		$site_title_mouseover_color_enable = $get_meta( $id, '_lay_site_title_mouseover_color_enable', true ) === 'on';
		$site_title_mouseover_color = $site_title_mouseover_color_enable ? $get_meta( $id, '_lay_site_title_mouseover_color', true ) : '';
		$menu_mouseover_color_enable = $get_meta( $id, '_lay_menu_mouseover_color_enable', true ) === 'on';
		$menu_mouseover_color = $menu_mouseover_color_enable ? $get_meta( $id, '_lay_menu_mouseover_color', true ) : '';

		$breakpoint = (int) MiscOptions::$phone_breakpoint;
		$body_selector = 'body.id-' . $id;

		if ( $menu_color_enable && $menu_color !== '' ) {
			$menu_color_safe = esc_attr( sanitize_hex_color( $menu_color ) ?: '#000000' );
			$result['lay-page-settings-menu-colors'] .= $body_selector . ' nav.laynav a{color:' . $menu_color_safe . ';}';
			$result['lay-page-settings-menu-colors'] .= $body_selector . ' nav.laynav a span{text-decoration-color:' . $menu_color_safe . ';}';
		}
		if ( $menu_bar_color_enable && $menu_bar_color !== '' ) {
			$menu_bar_hex = sanitize_hex_color( $menu_bar_color ) ?: '#ffffff';
			$menubar_opacity = get_theme_mod( 'navbar_opacity', 90 );
			$menubar_opacity = max( 0, min( 100, (int) $menubar_opacity ) ) / 100;
			$rgb = CSS_Output::hex2rgb( $menu_bar_hex );
			if ( $rgb !== false ) {
				$rgba = 'rgba(' . (int) $rgb['red'] . ',' . (int) $rgb['green'] . ',' . (int) $rgb['blue'] . ',' . $menubar_opacity . ')';
				$result['lay-page-settings-menu-colors'] .= $body_selector . ' .navbar{background-color:' . esc_attr( $rgba ) . ';}';
			}
		}
		if ( $site_title_color_enable && $site_title_color !== '' ) {
			$site_title_color_safe = esc_attr( sanitize_hex_color( $site_title_color ) ?: '#000000' );
			$result['lay-page-settings-menu-colors'] .= $body_selector . ' .sitetitle-txt-inner{color:' . $site_title_color_safe . ';}';
			$result['lay-page-settings-menu-colors'] .= $body_selector . ' .sitetitle.txt .sitetitle-txt-inner span{text-decoration-color:' . $site_title_color_safe . ';}';
		}
		if ( $site_title_mouseover_color_enable && $site_title_mouseover_color !== '' ) {
			$site_title_mouseover_color_safe = esc_attr( sanitize_hex_color( $site_title_mouseover_color ) ?: '#000000' );
			$result['lay-page-settings-menu-colors'] .= $body_selector . '.no-touchdevice .sitetitle.txt:hover .sitetitle-txt-inner span{color:' . $site_title_mouseover_color_safe . ';}';
			$result['lay-page-settings-menu-colors'] .= $body_selector . '.no-touchdevice .sitetitle:hover .tagline{color:' . $site_title_mouseover_color_safe . ';}';
		}
		if ( $menu_mouseover_color_enable && $menu_mouseover_color !== '' ) {
			$menu_mouseover_color_safe = esc_attr( sanitize_hex_color( $menu_mouseover_color ) ?: '#000000' );
			$result['lay-page-settings-menu-colors'] .= $body_selector . '.no-touchdevice nav.laynav a:hover{color:' . $menu_mouseover_color_safe . ';}';
			$result['lay-page-settings-menu-colors'] .= $body_selector . '.no-touchdevice nav.laynav a:hover span{text-decoration-color:' . $menu_mouseover_color_safe . ';}';
		}
		if ( $result['lay-page-settings-menu-colors'] !== '' ) {
			$result['lay-page-settings-menu-colors'] = '@media (min-width: ' . ( $breakpoint + 1 ) . 'px){' . $result['lay-page-settings-menu-colors'] . '}';
		}

		$phone_site_title_color_enable = $get_meta( $id, '_lay_phone_site_title_color_enable', true ) === 'on';
		$phone_site_title_color = $phone_site_title_color_enable ? $get_meta( $id, '_lay_phone_site_title_color', true ) : '';
		$phone_menu_color_enable = $get_meta( $id, '_lay_phone_menu_color_enable', true ) === 'on';
		$phone_menu_color = $phone_menu_color_enable ? $get_meta( $id, '_lay_phone_menu_color', true ) : '';
		$phone_menu_bar_color_enable = $get_meta( $id, '_lay_phone_menu_bar_color_enable', true ) === 'on';
		$phone_menu_bar_color = $phone_menu_bar_color_enable ? $get_meta( $id, '_lay_phone_menu_bar_color', true ) : '';
		if ( ( $phone_menu_color_enable && $phone_menu_color !== '' ) || ( $phone_menu_bar_color_enable && $phone_menu_bar_color !== '' ) || ( $phone_site_title_color_enable && $phone_site_title_color !== '' ) ) {
			$result['lay-page-settings-phone-menu-colors'] = '@media (max-width: ' . $breakpoint . 'px){';
			if ( $phone_menu_color_enable && $phone_menu_color !== '' ) {
				$c = esc_attr( sanitize_hex_color( $phone_menu_color ) ?: '#000000' );
				$result['lay-page-settings-phone-menu-colors'] .= $body_selector . ' nav.laynav a{color:' . $c . ';}';
				$result['lay-page-settings-phone-menu-colors'] .= $body_selector . ' nav.laynav a span{text-decoration-color:' . $c . ';}';
			}
			if ( $phone_menu_bar_color_enable && $phone_menu_bar_color !== '' ) {
				$phone_menu_bar_hex = sanitize_hex_color( $phone_menu_bar_color ) ?: '#ffffff';
				$menubar_opacity = get_theme_mod( 'navbar_opacity', 90 );
				$menubar_opacity = max( 0, min( 100, (int) $menubar_opacity ) ) / 100;
				$rgb = CSS_Output::hex2rgb( $phone_menu_bar_hex );
				if ( $rgb !== false ) {
					$rgba = 'rgba(' . (int) $rgb['red'] . ',' . (int) $rgb['green'] . ',' . (int) $rgb['blue'] . ',' . $menubar_opacity . ')';
					$result['lay-page-settings-phone-menu-colors'] .= $body_selector . ' .navbar{background-color:' . esc_attr( $rgba ) . ';}';
				}
			}
			if ( $phone_site_title_color_enable && $phone_site_title_color !== '' ) {
				$c = esc_attr( sanitize_hex_color( $phone_site_title_color ) ?: '#000000' );
				// On phone the desktop `.sitetitle` is hidden (see scss/phone_menu_breakpoint_under/style.scss);
				// the visible mobile site title is `.mobile-title.text` (markup in frontend.php
				// echo on line ~1090). Color the text + its underline color via the inner <span>.
				$result['lay-page-settings-phone-menu-colors'] .= $body_selector . ' .mobile-title.text{color:' . $c . ';}';
				$result['lay-page-settings-phone-menu-colors'] .= $body_selector . ' .mobile-title.text span{text-decoration-color:' . $c . ';}';
			}
			$result['lay-page-settings-phone-menu-colors'] .= '}';
		}

		$page_overlay_burger_color_enable = $get_meta( $id, '_lay_page_overlay_burger_color_enable', true ) === 'on';
		$page_overlay_burger_color = $page_overlay_burger_color_enable ? $get_meta( $id, '_lay_page_overlay_burger_color', true ) : '';
		if ( $page_overlay_burger_color_enable && $page_overlay_burger_color !== '' ) {
			$burger_color_safe = esc_attr( sanitize_hex_color( $page_overlay_burger_color ) ?: '#000000' );
			$result['lay-page-settings-overlay-burger-color'] = 'body.id-' . $id . ' .standard-overlay-burger::before{color:' . $burger_color_safe . ';}';
			$result['lay-page-settings-overlay-burger-color'] .= 'body.id-' . $id . ' .text-overlay-burger span{color:' . $burger_color_safe . ';}';
		}

		$phone_page_overlay_burger_color_enable = $get_meta( $id, '_lay_phone_page_overlay_burger_color_enable', true ) === 'on';
		$phone_page_overlay_burger_color = $phone_page_overlay_burger_color_enable ? $get_meta( $id, '_lay_phone_page_overlay_burger_color', true ) : '';
		if ( $phone_page_overlay_burger_color_enable && $phone_page_overlay_burger_color !== '' ) {
			$phone_burger_color_safe = esc_attr( sanitize_hex_color( $phone_page_overlay_burger_color ) ?: '#000000' );
			$result['lay-page-settings-phone-overlay-burger-color'] = '@media (max-width: ' . $breakpoint . 'px){';
			$result['lay-page-settings-phone-overlay-burger-color'] .= 'body.id-' . $id . ' .standard-overlay-burger::before{color:' . $phone_burger_color_safe . ';}';
			$result['lay-page-settings-phone-overlay-burger-color'] .= 'body.id-' . $id . ' .text-overlay-burger span{color:' . $phone_burger_color_safe . ';}';
			$result['lay-page-settings-phone-overlay-burger-color'] .= '}';
		}

		// Per-page override for the mobile-menu burger icon (the one in the
		// mobile menubar). Mirrors the customizer setting `mobile_menu_burger_icon_color`
		// but scoped to this page only via `body.id-<id>`. Selectors match
		// css_output_mobile_menu.php so the page rule beats the global one.
		$phone_mobile_menu_burger_color_enable = $get_meta( $id, '_lay_phone_mobile_menu_burger_color_enable', true ) === 'on';
		$phone_mobile_menu_burger_color = $phone_mobile_menu_burger_color_enable ? $get_meta( $id, '_lay_phone_mobile_menu_burger_color', true ) : '';
		if ( $phone_mobile_menu_burger_color_enable && $phone_mobile_menu_burger_color !== '' ) {
			$mb_color_safe = esc_attr( sanitize_hex_color( $phone_mobile_menu_burger_color ) ?: '#000000' );
			$result['lay-page-settings-phone-mobile-menu-burger-color'] = '@media (max-width: ' . $breakpoint . 'px){';
			$result['lay-page-settings-phone-mobile-menu-burger-color'] .= 'body.id-' . $id . ' .burger-default span,body.id-' . $id . ' .burger-default span:before,body.id-' . $id . ' .burger-default span:after{background-color:' . $mb_color_safe . ';}';
			$result['lay-page-settings-phone-mobile-menu-burger-color'] .= 'body.id-' . $id . ' .burger-new .bread-crust-top,body.id-' . $id . ' .burger-new .bread-crust-bottom{background:' . $mb_color_safe . ';}';
			$result['lay-page-settings-phone-mobile-menu-burger-color'] .= 'body.id-' . $id . ' .lay-cart-icon-wrap{color:' . $mb_color_safe . ';}';
			$result['lay-page-settings-phone-mobile-menu-burger-color'] .= '}';
		}

		return $result;
	}

	// top custom html,
	// site title
	// mobile site title
	// mobile menu
	// desktop menu
	// menu bar
	// burger
	public static function get_header(){
        if ( post_password_required() && LayFrontend_Options::$hide_st_m_on_pw == 'on' ) {
            return;
        }
		// Page settings: hide site title / menu / footer via body classes (CSS)
		$data = Frontend::$current_type_id_slug_catid;
		// Always output all hide rules so AJAX navigation can toggle body classes and the correct rules apply
		echo '<style id="lay-page-settings-hide">';
		echo 'body.lay-hide-sitetitle .sitetitle, body.lay-hide-sitetitle .mobile-title{display:none!important;}';
		echo 'body.lay-hide-menu .laynav.primary, body.lay-hide-menu .navbar, body.lay-hide-menu .burger-wrap{display:none!important;}';
		echo 'body.lay-hide-menu-2 .laynav.second_menu{display:none!important;}';
		echo 'body.lay-hide-menu-3 .laynav.third_menu{display:none!important;}';
		echo 'body.lay-hide-menu-4 .laynav.fourth_menu{display:none!important;}';
		echo 'body.lay-hide-footer #footer-region{display:none!important;}';
		echo '</style>';
		// Page settings: menu/bar/site title colors and overlay burger colors (echo from shared helper for initial load)
		$page_settings_styles = self::get_page_settings_styles_for_data( $data );
		foreach ( $page_settings_styles as $style_id => $inner_css ) {
			if ( $inner_css !== '' ) {
				echo '<style id="' . esc_attr( $style_id ) . '">' . $inner_css . '</style>';
			}
		}
		// previously i put site title and menus in footer so when I get the flash of unstyled content on Chrome
		// at least it won't create a flash of extra space above the main grid but only underneath which won't be visible anyways

		// now i needed to put it back in the header because i need to use position sticky when a woocommerce store notice is shown
		$title = self::translate_qtranslate_text(get_bloginfo( 'name'));
		$homeurl = get_bloginfo('url');
		
		// Get custom link URLs and data attributes for site title and mobile site title
		$sitetitle_url = self::get_sitetitle_link_url('st_');
		$sitetitle_link_data = self::get_sitetitle_link_data('st_');
		$mobile_sitetitle_url = self::get_sitetitle_link_url('m_st_');
		$mobile_sitetitle_link_data = self::get_sitetitle_link_data('m_st_');

		$mobiletitle = '';

		$hideTagLine = get_theme_mod('tagline_hide', "1");

		$taglineMarkup = '';
		if($hideTagLine != "1"){
			$tagline = html_entity_decode(self::translate_qtranslate_text(get_bloginfo( 'description' )));

			if (strpos($tagline, '<br>') === false) {
				$tagline = nl2br($tagline);
			}

			$tagline_textformat = get_theme_mod('tagline_textformat', 'Default');
			if($tagline_textformat != ""){
				$tagline_textformat = '_'.$tagline_textformat;
			}
			$taglineMarkup = '<div class="tagline '.$tagline_textformat.'">'.$tagline.'</div>';
		}

		$txt_or_img_or_html = get_theme_mod('st_txt_or_img', 'text');
		$m_txt_or_img = get_theme_mod( 'm_st_txt_or_img', $txt_or_img_or_html );
		// if( $m_txt_or_img == 'html' ){
		// 	$m_txt_or_img = 'text';
		// }

		$title_with_linebreak = nl2br($title);

		$position = get_theme_mod('st_position', 'top-left');
		$position_class = strpos($position, 'top') !== -1 ? 'position-top' : 'position-not-top';

		$isfixed = get_theme_mod('st_isfixed', '1');
		$isfixed_class = $isfixed == '1' ? 'is-fixed' : 'is-not-fixed';
		// desktop
		switch ($txt_or_img_or_html) {
			case 'text':
				$st_textformat = get_theme_mod('st_textformat', 'Default');
				if($st_textformat != ""){
					$st_textformat = '_'.$st_textformat;
				}
				// we need a span el in here to make site title underline work well with multiline text
				echo
				'<a class="sitetitle '.$position_class.' '.$isfixed_class.' txt" href="'.$sitetitle_url.'" '.$sitetitle_link_data.'>
					<div class="sitetitle-txt-inner '.$st_textformat.'"><span>'.$title_with_linebreak.'</span></div>
					'.$taglineMarkup.'
				</a>';
			break;
			case 'image':
				$imgurl = LTUtility::filterURL(get_theme_mod('st_image'));
				echo
				'<a class="sitetitle '.$position_class.' '.$isfixed_class.' img" href="'.$sitetitle_url.'" '.$sitetitle_link_data.'>
					<img src="'.$imgurl.'" alt="'.$title.'">
					'.$taglineMarkup.'
				</a>';
			break;
			case 'html':
			echo
				'<a class="sitetitle '.$position_class.' '.$isfixed_class.' html" href="'.$sitetitle_url.'" '.$sitetitle_link_data.'>
					'.get_theme_mod('site_title_html', '').'
					'.$taglineMarkup.'
				</a>';
			break;
		}

		// mobile
		switch ($m_txt_or_img){
			case 'text':
				$mobiletitle = $title;
			break;
			case 'image':
				$default = get_theme_mod('st_image');
				$imgurl = LTUtility::filterURL(get_theme_mod('m_st_image', $default));
				$mobiletitle = '<img src="'.$imgurl.'" alt="'.$title.'">';
			break;
            case 'html':
                $mobiletitle = get_theme_mod('m_st_html', '');
            break;
		}

		echo LayMenuManager::get_mobile_menu();
		echo LayMenuManager::get_menu_markup();

		// dont show burger if there is no menu
		$hideburgerCSS = '';
		if(!LayMenuManager::at_least_one_menu_is_filled()){
			$hideburgerCSS = 'style="display:none;"';
		}

		$customBurgerImg = get_theme_mod('mobile_menu_icon_burger', '');
		$customCloseImg = get_theme_mod('mobile_menu_icon_close', '');
		$burger_icon_type = get_theme_mod('burger_icon_type', 'default_thin');

		$nav_position = get_theme_mod('nav_position', 'top-right');
		$navbar_position_class = '';
        if (strpos($nav_position,'top') !== false){
            $navbar_position_class = 'position-top';
        }else{
			$navbar_position_class = 'position-not-top';
		}
		$is_fixed = get_theme_mod('mobile_menu_isfixed', '1') == '1' ? 'is-fixed' : '';

        $mobile_menubar_move_away_when_scrolling_down = LayFrontend_Options::$mobile_menubar_move_away_when_scrolling_down ? 'mobile_menubar_move_away_when_scrolling_down' : '';
        $mobile_menubar_fade_out_when_scrolling_down = LayFrontend_Options::$mobile_menubar_fade_out_when_scrolling_down ? 'mobile_menubar_fade_out_when_scrolling_down' : '';
        echo
		'<div class="navbar '.$navbar_position_class.' '.$is_fixed.' '.$mobile_menubar_fade_out_when_scrolling_down.' '.$mobile_menubar_move_away_when_scrolling_down.'"></div>';
        $mobile_sitetitle_move_away_when_scrolling_down = LayFrontend_Options::$mobile_sitetitle_move_away_when_scrolling_down ? 'mobile_sitetitle_move_away_when_scrolling_down' : '';
        $mobile_sitetitle_fade_out_when_scrolling_down = LayFrontend_Options::$mobile_sitetitle_fade_out_when_scrolling_down ? 'mobile_sitetitle_fade_out_when_scrolling_down' : '';
		echo apply_filters('lay_after_navbar_markup', '');

		echo
		'<a class="mobile-title '.$m_txt_or_img.' '.$is_fixed.' '.$mobile_sitetitle_move_away_when_scrolling_down.' '.$mobile_sitetitle_fade_out_when_scrolling_down.'" href="'.$mobile_sitetitle_url.'" '.$mobile_sitetitle_link_data.'><span>'.$mobiletitle.'</span></a>';

        $mobile_menu_icon_position = get_theme_mod('burger_icon_position', 'right');
		$burgerWrap = '';
		if( $customBurgerImg != '' && $burger_icon_type == 'custom' ){
			$burgerWrap = 
			'<div class="burger-wrap '.$mobile_menu_icon_position.' burger-custom-wrap-open" '.$hideburgerCSS.'>
				<div class="burger-inner">
					<img src="'.$customBurgerImg.'" class="mobile-menu-icon burger-custom" '.$hideburgerCSS.'>
				</div>
			</div>';
		}
		if( $customCloseImg != '' && $burger_icon_type == 'custom' ){
			$burgerWrap .= 
			'<div class="burger-wrap '.$mobile_menu_icon_position.' burger-custom-wrap-close" '.$hideburgerCSS.' >
				<div class="burger-inner">
					<img src="'.$customCloseImg.'" class="mobile-menu-close-custom mobile-menu-icon" '.$hideburgerCSS.'>
				</div>
			</div>';
		}
		$burger_animated_class = get_theme_mod('mobile_menu_style', 'style_1') == 'style_2' ? 'burger-no-animation' : 'burger-has-animation';
		if( get_theme_mod('mobile_burger_icon_animate', false) == false ) {
			$burger_animated_class = 'burger-no-animation';
		}
		if( $burger_icon_type == "default" || $burger_icon_type == "default_thin" ){
			$burgerWrap = 
			'<div class="burger-wrap '.$mobile_menu_icon_position.' burger-wrap-default '.$burger_animated_class.' '.$burger_icon_type.'" '.$hideburgerCSS.'>
				<div class="burger-inner">
					<div class="burger burger-default mobile-menu-icon">
						<span></span>
						<span></span>
						<span></span>
					</div>
				</div>
			</div>';
		}
		if( $burger_icon_type == 'new' ){
			$burgerWrap = 
			'<div class="burger-wrap '.$mobile_menu_icon_position.' burger-wrap-new '.$burger_animated_class.'" '.$hideburgerCSS.'>
				<div class="burger-inner">
					<div class="burger burger-new mobile-menu-icon">
						<span class="bread-top">
							<span class="bread-crust-top"></span>
						</span>
						<span class="bread-bottom">
							<span class="bread-crust-bottom"></span>
						</span>
					</div>
				</div>
			</div>';
		}
        if( $burger_icon_type == 'text' ){
            $mobile_burger_icon_textformat = get_theme_mod('mobile_burger_icon_textformat', 'Default');
            $mobile_burger_icon_text_open = get_theme_mod('mobile_burger_icon_text_open', 'Menu');
            $mobile_burger_icon_text_close = get_theme_mod('mobile_burger_icon_text_close', 'Close');
            $mobile_burger_textcolor = get_theme_mod('mobile_burger_textcolor', '#000000');
            $burgerWrap = 
			'<div class="burger-wrap '.$mobile_menu_icon_position.' burger-wrap-text '.$burger_animated_class.' '.$burger_icon_type.' _'.$mobile_burger_icon_textformat.'_no_spaces" style="color:'.$mobile_burger_textcolor.';" '.$hideburgerCSS.' data-text-open="'.$mobile_burger_icon_text_open.'" data-text-close="'.$mobile_burger_icon_text_close.'">
				'.$mobile_burger_icon_text_open.'
			</div>';
        }

		$cartIconWrap = '';
		$woo = '';
		if( class_exists( 'WooCommerce' ) && class_exists( 'Lay_WooCommerce' ) ) {
			$cartIconWrap = Lay_WooCommerce::getCartIconWrap();
			$woo = 'contains-cart-icon';
		}

        $mobile_menuicons_move_away_when_scrolling_down = get_theme_mod('mobile_menuicons_move_away_when_scrolling_down', false) ? 'mobile_menuicons_move_away_when_scrolling_down' : '';
        $mobile_menuicons_fade_out_when_scrolling_down = get_theme_mod('mobile_menuicons_fade_out_when_scrolling_down', false) ? 'mobile_menuicons_fade_out_when_scrolling_down' : '';

        $mobile_menu_icon_position = get_theme_mod('burger_icon_position', 'right');
        $inner = $cartIconWrap.$burgerWrap;
        if($mobile_menu_icon_position == 'left'){
            $inner = $burgerWrap.$cartIconWrap;
        }
		
        echo '<div class="lay-mobile-icons-wrap '.$burger_icon_type.'-burger '.$woo.' '.$is_fixed.' '.$mobile_menuicons_move_away_when_scrolling_down.' '.$mobile_menuicons_fade_out_when_scrolling_down.'">'.$inner.'</div>';
		
		if( !LayFrontend_Options::$turn_off_custom_code ) {
			$custom_html_top = get_option( 'misc_options_custom_htmltop', '' );
			if( $custom_html_top != "" ){
				echo $custom_html_top;
			}
		}
	}

	// essential styles are the styles that need to be loaded in head for the website to render OK when most styles are only loaded in the footer
	public static function essential_head_styles_and_desktop_and_phone_styles(){
		// most of my css is loaded in the footer, however, i do need some css so the website will look normal until the css in the footer is loaded
		// need to put this in head, otherwise image will appear opacity:1; and only when the style is loaded it would be opacity:0; and then go back to opacity:1; once loaded
		$prefixes = array( '#search-results-region', '.col', '.project-arrow', '.background-image', '.background-video' );
		$opacity_css = '';
		foreach( $prefixes as $pref ) {
			// not loaded
			$opacity_css .= $pref . ' img.setsizes{opacity: 0;}';
			$opacity_css .= $pref . ' img.lay-gif{opacity: 0;}';
			$opacity_css .= $pref . ' img.lay-image-original{opacity: 0;}';
			$opacity_css .= $pref . ' img.carousel-img{opacity: 0;}';
			$opacity_css .= $pref . ' video.video-lazyload{opacity: 0;}';
			// loaded
			$opacity_css .= $pref . ' img.setsizes.loaded{opacity: 1;}';
            $opacity_css .= $pref . ' img.loaded-error{opacity: 1;}';
			$opacity_css .= $pref . ' img.lay-gif.loaded{opacity: 1;}';
			$opacity_css .= $pref . ' img.lay-image-original.loaded{opacity: 1;}';
			$opacity_css .= $pref . ' img.carousel-img.loaded{opacity: 1;}';
			$opacity_css .= $pref . ' video.loaded{opacity: 1;}';
		}

		wp_add_inline_style( 'frontend-style',
		'/* essential styles that need to be in head */
		html{
			overflow-y: scroll;
			/* needs to be min-height: 100% instead of height: 100%. doing height: 100% can cause a bug when doing scroll in jQuery for html,body  */
			min-height: 100%;
			/* prevent anchor font size from changing when rotating iphone to landscape mode */
			/* also: https://stackoverflow.com/questions/49589861/is-there-a-non-hacky-way-to-prevent-pinch-zoom-on-ios-11-3-safari */
			-webkit-text-size-adjust: none;
			/* prevent overflow while loading */
			overflow-x: hidden!important;
		}
		body{
			background-color: white;
			transition: background-color 300ms ease;
			min-height: 100%;
			margin: 0;
			width: 100%;
		}
		/* lay image opacity css */
		'.$opacity_css.'
		/* phone menu breakpoint above */
		@media (min-width: '.(MiscOptions::$phone_menu_breakpoint+1).'px){
			'.MediaQueryCSS::$phone_menu_breakpoint_above.'
		}
		/* phone menu breakpoint under */
		@media (max-width: '.(MiscOptions::$phone_menu_breakpoint).'px){
			'.MediaQueryCSS::$phone_menu_breakpoint_under.'
		}
		/* lay media query styles */
		@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){
			'.MediaQueryCSS::$desktop.'
			'.MediaQueryCSS::$woocommerce_desktop.'
		}
		@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
			'.MediaQueryCSS::$phone.'
			'.MediaQueryCSS::$woocommerce_phone.'
		}');

		// we make sure intro css gets loaded first, because sometimes content would show up before intro!
		$intro_active = get_option('misc_options_intro', '') == 'on' && !MiscOptions::$locked ? true : false;
		if( $intro_active ){
			wp_add_inline_style( 'frontend-style', MediaQueryCSS::$intro);
		}

		$cover_safari_fix = get_option('cover_safari_fix', '') === 'on';
		if( $cover_safari_fix ){
			wp_add_inline_style( 'frontend-style', '.cover-content{transform: translate3d(0,0,0)!important;}' );
		}
	}

	public function frontend_styles() {
		wp_enqueue_style( 'frontend-style', Setup::$uri."/frontend/assets/css/frontend.style.css", array(), Setup::$ver );
	}

	// for projects prev/next navigation
	public static function get_projects_meta($prevnext_navigate_through){

		$meta = array();

		switch($prevnext_navigate_through){
			case 'same_category':
				// create an array for each category that includes all projects of this category
				// a project meta only has one category

				$allCategoryIds = get_terms('category', array('fields' => 'ids'));

				foreach ($allCategoryIds as $key => $catid) {

					$args = array(
						'fields' => 'ids',
						'posts_per_page' => -1,
						'orderby' => 'menu_order',
						'post_type' => 'post',
						'cat' => $catid,
						'order' => 'ASC',
						// only published posts
						'post_status' => 'publish'
					);
                    
					$query = new WP_Query( $args );

					if ( $query->have_posts() ) {
						foreach ($query->posts as $id){

                            $link_disabled = false;
                            $disable_link_is_active = get_post_meta($id, 'disable_link_is_active', false);
                            if(is_array($disable_link_is_active) && array_key_exists(0, $disable_link_is_active) && $disable_link_is_active[0] == 'on'){
                                $link_disabled = true;
                            }

                            if($link_disabled){
                                continue;
                            }

							$meta[$catid] []= Frontend::get_project_meta($id, $catid);

						}
					};

				}
			break;
			case 'all_projects':
				// a project meta only has one category

				$args = array(
					'fields' => 'ids',
					'posts_per_page' => -1,
					'orderby' => 'menu_order',
					'post_type' => 'post',
					'order' => 'ASC',
					// only published posts
					'post_status' => 'publish'
				);
                
				$query = new WP_Query( $args );

				if ( $query->have_posts() ) {
					foreach ($query->posts as $id){

                        $link_disabled = false;
                        $disable_link_is_active = get_post_meta($id, 'disable_link_is_active', false);
                        if(is_array($disable_link_is_active) && array_key_exists(0, $disable_link_is_active) && $disable_link_is_active[0] == 'on'){
                            $link_disabled = true;
                        }

                        if($link_disabled){
                            continue;
                        }

						$catids = wp_get_post_categories( $id );
						$meta []= Frontend::get_project_meta($id, $catids);

					}
				};

			break;
		}

		return json_encode($meta);
	}

    // for news prev/next navigation
	public static function get_news_meta(){

		$meta = array();

        if( post_type_exists('lay_news') ) {
            $args = array(
                'fields' => 'ids',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'post_type' => 'lay_news',
                'order' => 'DESC'
            );
            
            $query = new WP_Query( $args );
            
            if ( $query->have_posts() ) {
                foreach ($query->posts as $id){

                    $link_disabled = false;
                    $disable_link_is_active = get_post_meta($id, 'disable_link_is_active', false);
                    if(is_array($disable_link_is_active) && array_key_exists(0, $disable_link_is_active) && $disable_link_is_active[0] == 'on'){
                        $link_disabled = true;
                    }
                    $external_link_is_active = get_post_meta($id, 'external_link_is_active', false);
                    if(is_array($external_link_is_active) && array_key_exists(0, $external_link_is_active) && $external_link_is_active[0] == 'on'){
                        $link_disabled = true;
                    }

                    if($link_disabled){
                        continue;
                    }
    
                    $catids = wp_get_post_categories($id, array( 'fields' => 'ids' ));
                    $array = array(
                        'title' => get_the_title($id),
                        'url' => get_permalink($id),
                        'id' => $id,
                        'catid' => $catids,
                    );
                    $meta []= $array;
    
                }
            };
        }

		return json_encode($meta);
	}

	// for deck prev/next navigation (TinyMCE laynextproject/layprevproject anchors).
	// Uses menu_order ASC so the order matches the visual slide order in admin
	// drag-reorder + the deck counter widget.
	public static function get_deck_meta(){
		$meta = array();
		if ( post_type_exists('lay_deck') ) {
			$args = array(
				'fields' => 'ids',
				'posts_per_page' => -1,
				'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
				'post_type' => 'lay_deck',
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				foreach ($query->posts as $id){
					$meta []= array(
						'title' => get_the_title($id),
						'url' => get_permalink($id),
						'id' => $id,
					);
				}
			}
		}
		return json_encode($meta);
	}

	public static function get_project_meta($id, $catid){
		$catids = wp_get_post_categories($id, array( 'fields' => 'ids' ));

		$array = array(
			'title' => get_the_title($id),
			'url' => get_permalink($id),
			'id' => $id,
			'catid' => $catids,
		);

		if( LayFrontend_Options::$pa_type == "project-thumbnails" ) {
			$thumb_id = get_post_thumbnail_id($id);
			$_512 = wp_get_attachment_image_src($thumb_id, '_512');
			$thumbar = 0;
			if($_512){
				if($_512[1] != 0){
					$thumbar = $_512[2] / $_512[1];
				}
			}

			$array['thumb'] = array(
				'_512' => isset( $_512[0] ) ? $_512[0] : '',
			);
			$array['thumbar'] = $thumbar;
		}

		return $array;
	}

	public function frontend_scripts(){
		wp_register_script( 'swiper', Setup::$uri."/frontend/assets/vendor/swiper.js", array(), Setup::$ver );

		// loading other vendor js in footer
		wp_register_script( 'vendor-frontend-footer', Setup::$uri.'/frontend/assets/js/vendor.min.js', array(), Setup::$ver, true );
		wp_enqueue_script( 'frontend-critical-init', Setup::$uri."/frontend/assets/js/frontend.critical-init.min.js", array('lay-frontend-events-channels'), Setup::$ver, false );
		wp_enqueue_script( 'frontend-app', Setup::$uri."/frontend/assets/js/frontend.app.min.js", array('jquery', 'vendor-frontend-footer'), Setup::$ver, true );

        // create events channel here, because ppl might use it in <head> even though it would normally be initialized in app js in footer
        // event channels like: window.laytheme.on: 
		wp_enqueue_script( 'lay-frontend-events-channels', Setup::$uri."/frontend/assets/js/events_channels.min.js", array(), Setup::$ver, false );


		$sep = get_option( 'misc_options_title_separator', '—' );
		wp_localize_script( 'frontend-app', 'passedDataHistory', array( 'titleprefix'=>Setup::get_title().' '.$sep.' ', 'title'=>Setup::get_title() ) );

		// is this a good idea?
		wp_deregister_script( 'wp-embed' );

		$pt_textformat = LayFrontend_Options::$pt_textformat;

		$pt_position = LayFrontend_Options::$pt_position;
		$pd_position = LayFrontend_Options::$pd_position;

		$st_position = LayFrontend_Options::$st_position;
		$st_hide = LayFrontend_Options::$st_hide;
		$navbar_hide = LayFrontend_Options::$navbar_hide;

		$showArrows = get_option('misc_options_show_project_arrows', '');
		$pa_type = LayFrontend_Options::$pa_type;
		$simple_parallax = get_option('misc_options_simple_parallax', '');

		$playicon = '';
		$playicon_id = get_option( 'misc_options_html5video_playicon', '' );
		if($playicon_id != ''){
			$playicon = wp_get_attachment_image_src( $playicon_id, 'full' );
			if( is_array($playicon) ) {
				$playicon = $playicon[0];
			} else {
				$playicon = '';
			}
		}

		$projectsFooterId = '';
		$footer_active_in_projects = get_option('lay_footer_active_in_projects', 'off');
		if($footer_active_in_projects=="all"){
			$projectsFooterId = get_option('lay_projects_footer', '');
		}

		$pagesFooterId = '';
		$footer_active_in_pages = get_option('lay_footer_active_in_pages', 'off');
		if($footer_active_in_pages=="all"){
			$pagesFooterId = get_option('lay_pages_footer', '');
		}

		$categoriesFooterId = '';
		$footer_active_in_categories = get_option('lay_footer_active_in_categories', 'off');
		if($footer_active_in_categories=="all"){
			$categoriesFooterId = get_option('lay_categories_footer', '');
		}

		$individual_project_footers = get_option('lay_individual_project_footers', '');
		$individual_page_footers = get_option('lay_individual_page_footers', '');
		$individual_category_footers = get_option('lay_individual_category_footers', '');

		$prevnext_navigate_through = get_option('misc_options_prevnext_navigate_through', 'all_projects');

		$fi_mo_touchdevice_behaviour = LayFrontend_Options::$fi_mo_touchdevice_behaviour;
		
        /* 
		i changed the misc option inputs from radio to checkbox
		this used to be either "instant_load" or "lazy_load"
		but now it can be '' or 'on'
		'on' is when lazyload is turned on, '' is when lazyload is off
		i need to make sure in my code the values 'instant_load' and 'lazy_load' are handled too
		*/
		$image_loading = get_option('misc_options_image_loading', 'on');
		if( $image_loading == 'on' ) {
			$image_loading = 'lazy_load';
		} else if ($image_loading == '') {
			$image_loading = 'instant_load';
		}

		$cover_scrolldown_on_click = get_option('cover_scrolldown_on_click', '');
		$cover_darken_when_scrolling = get_option('cover_darken_when_scrolling', '');
		$cover_parallaxmove_when_scrolling = get_option('cover_parallaxmove_when_scrolling', '');

		$misc_options_cover = get_option('misc_options_cover', '');

		$misc_options_max_width_apply_to_logo_and_nav = get_option('misc_options_max_width_apply_to_logo_and_nav', '');
		$misc_options_max_width_apply_to_burger = get_option('misc_options_max_width_apply_to_burger', '');
		$maxwidth = get_option( 'misc_options_max_width', '0' );

		$default = LayFrontend_Options::$default;
		$m_st_fontfamily = LayFrontend_Options::$m_st_fontfamily;

		$misc_options_showoriginalimages = get_option('misc_options_showoriginalimages', '');
		$misc_options_showoriginalimages_for_phone = get_option('misc_options_showoriginalimages_for_phone', '');

		$misc_options_thumbnail_mouseover_image = get_option('misc_options_thumbnail_mouseover_image', '');


        $phone_layout_active = get_option('misc_options_extra_gridder_for_phone', 'on');
		$frame_left = get_option( 'gridder_defaults_frame_left', LayConstants::gridder_defaults_frame_left );
        $frame_right = get_option( 'gridder_defaults_frame_right', LayConstants::gridder_defaults_frame_right );
        $frame_leftright_mu = get_option( 'gridder_defaults_frame_mu', LayConstants::gridder_defaults_frame_mu );

		// $bg_color = get_theme_mod('bg_color', '#ffffff');
		// if($bg_color == ""){
		// 	$bg_color = "#ffffff";
		// }
		// $bg_image = get_theme_mod('bg_image', "");

		$is_customize = is_customize_preview();
		$is_ssl = is_ssl();
		$has_www = LTUtility::has_www();

		$is_qtranslate_active = is_plugin_active('qtranslate-x/qtranslate.php') || is_plugin_active('qtranslate-xt-master/qtranslate.php') || class_exists('QTX_Translator');

		$sideCartPluginActive = LTUtility::is_sidecart_active();

		$element_transition_on_scroll = get_option('misc_options_element_transition_on_scroll', '');
		$misc_options_disable_ajax = get_option( 'misc_options_disable_ajax', "" );
		if( class_exists( 'WooCommerce' ) ) {
			$misc_options_disable_ajax = "on";
		}

		$anchorscroll_offset_desktop = get_option( 'misc_options_anchorscroll_offset_desktop', 0 );
		$anchorscroll_offset_phone = get_option( 'misc_options_anchorscroll_offset_phone', 0 );
		$anchorscroll_duration = intval( get_option( 'misc_options_anchorscroll_duration', 300 ) );
		$anchorscroll_easing = get_option( 'misc_options_anchorscroll_easing', 'easeInOutQuad' );
		$anchor_menu_active = get_option( 'misc_options_anchor_menu_active', 'on' );
		$anchor_menu_keep_active_until_next = get_option( 'misc_options_anchor_menu_keep_active_until_next', '' );
		$mouseover_show_scroll_hidden = get_option( 'misc_options_mouseover_show_scroll_hidden', 'on' );

        $projectsMeta = Frontend::get_projects_meta($prevnext_navigate_through);
        $newsMeta = Frontend::get_news_meta();
        $deckMeta = Frontend::get_deck_meta();
		$lay_sticky_footer = get_option('lay_sticky_footer', 'on');

		$spaces_mu = get_option( 'gridder_spaces_mu', LayConstants::gridder_spaces_mu );
		$offsets_mu = get_option( 'gridder_offsets_mu', LayConstants::gridder_offsets_mu );

        $key = get_option('lay_license_key', '');
        $shownag = 'dontshow';
        if( $key == '' ) {
            $shownag = 'show';
        }

        if( MiscOptions::$locked ) {
            $phone_layout_active = '';
            $misc_options_cover = '';
            $showArrows = '';
            $simple_parallax = '';
            $misc_options_thumbnail_mouseover_image = '';
            $element_transition_on_scroll = '';
        }

        $menu_amount = intval(get_option('misc_options_menu_amount', 1));
        if ( $menu_amount == 0 ) {
            $menu_amount = 1;
        }

        $honp_default = true;
        if( get_theme_mod('intro_hide_on_phone', 'not_set') == 'not_set' ) {
            $honp_default = false;
        }
        $intro_hide_on_phone = get_theme_mod('intro_hide_on_phone', $honp_default);

        $transition_in = get_option( 'misc_options_navigation_transition_in', 'fade' );
        if( $transition_in == '' ) {
            $transition_in = 'fade';
        }
        $transition_out = get_option( 'misc_options_navigation_transition_out', 'fade' );
        if( $transition_out == '' ) {
            $transition_out = 'fade';
        }
        $navigation_transition_in_duration = Frontend::get_navigation_transition_in_duration_in_milliseconds();
        $navigation_transition_out_duration = Frontend::get_navigation_transition_out_duration_in_milliseconds();
        if( $navigation_transition_in_duration == '' ) {
            $navigation_transition_in_duration = 300;
        }
        if( $navigation_transition_out_duration == '' ) {
            $navigation_transition_out_duration = 300;
        }
        if( $navigation_transition_in_duration == 0 ) {
            $transition_in = 'none';
        }
        if( $navigation_transition_out_duration == 0 ) {
            $transition_out = 'none';
        }

        $navigation_transition_out_easing = get_option( 'misc_options_navigation_transition_out_easing', 'easeInCubic' );
        if( $navigation_transition_out_easing == '' ) {
            $navigation_transition_out_easing = 'easeInCubic';
        }
        $navigation_transition_in_easing = get_option( 'misc_options_navigation_transition_in_easing', 'easeOutCubic' );
        if( $navigation_transition_in_easing == '' ) {
            $navigation_transition_in_easing = 'easeOutCubic';
        }
        $navigation_transition_use_revealing_transition_on_first_visit = get_option('misc_options_use_revealing_transition_on_first_visit', '');
        if( $navigation_transition_use_revealing_transition_on_first_visit == 'on' && MiscOptions::$locked ) {
            $navigation_transition_use_revealing_transition_on_first_visit = '';
        } 
        if( $navigation_transition_use_revealing_transition_on_first_visit == 'on' ) {
            // turn off when transition duration is 0 or transition type is none!
            if( get_option( 'misc_options_navigation_transition_in_duration', '0.4' ) == 0 ) {
                $navigation_transition_use_revealing_transition_on_first_visit = '';
            }
            if( get_option( 'misc_options_navigation_transition_in', 'fade' ) == 'none' ) {
                $navigation_transition_use_revealing_transition_on_first_visit = '';
            }
        }
        $navigation_transition_y_translate_desktop = get_option( 'misc_options_navigation_transition_y_translate_desktop', 100 );
        if( $navigation_transition_y_translate_desktop == '' ) {
            $navigation_transition_y_translate_desktop = 100;
        }
        $navigation_transition_y_translate_phone = get_option( 'misc_options_navigation_transition_y_translate_phone', 50 );
        if( $navigation_transition_y_translate_phone == '' ) {
            $navigation_transition_y_translate_phone = 100;
        }

        $po_custom_close_icon_attid = get_option( 'po_close_icon', '' );
        $po_custom_close_icon = '';
        if( $po_custom_close_icon_attid != '' ) {
            $po_custom_close_icon = wp_get_attachment_image_src( $po_custom_close_icon_attid, 'full' );
            if( is_array($po_custom_close_icon) ) {
                $po_custom_close_icon = $po_custom_close_icon[0];
            } else {
                $po_custom_close_icon = '';
            }
        }

        $po_behind_overlay_bg = get_option('po_background_behind_overlay', 'on');
        $po_animation = get_option('po_animation', 'slideup');
        if( $po_animation != 'slideup' && $po_animation != 'slidedown' ){
            $po_behind_overlay_bg = '';
        }

        $default_category_id = get_option('default_category');
        $default_product_cat_id = get_option( 'default_product_cat' );

        $lay_woocommerce_singleproduct_only_show_variation_images = get_theme_mod('lay_woocommerce_singleproduct_only_show_variation_images', '');

        $thumbnail_video_and_mouseover_image_behavior = get_option('misc_options_thumbnail_video_and_mouseover_image_behavior', 'image');
        $thumbnail_mouseover_image = get_option('misc_options_thumbnail_mouseover_image', '');
        $misc_options_alt_w100_h100_sizing = get_option( 'misc_options_alt_w100_h100_sizing', "" );

        $lay_woocommerce_singleproduct_layout_type_mobile = get_theme_mod('lay_woocommerce_singleproduct_layout_type_mobile', 'carousel');
        $lay_woocommerce_singleproduct_desktop_carousel_height = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_height', 'auto');
        $lay_woocommerce_singleproduct_desktop_carousel_slide_duration = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_slide_duration', 300);
        $lay_woocommerce_singleproduct_desktop_carousel_loop = get_theme_mod('lay_woocommerce_singleproduct_desktop_carousel_loop', '');
        $projects_overlays = class_exists( 'WooCommerce' ) || get_option( 'misc_options_disable_ajax', "" ) == "on" ? '' : get_option( 'misc_options_activate_projects_overlay_feature', "" );
        $disable_projectsoverlays_on_ig = get_option( 'misc_options_disable_projectsoverlays_on_ig', "" );
        $use_revealing_transition_when_opening_page_overlay = get_option('misc_options_use_revealing_transition_when_opening_page_overlay', '');
        $loadmore_remember = get_option('misc_options_loadmore_remember', 'on');

		$playicon = get_template_directory_uri().'/frontend/assets/img/play.svg';
		if(LayFrontend_Options::$playicon != ""){
			$playicon = LayFrontend_Options::$playicon;
		}

        $custom_taxonomies = Frontend::get_custom_taxonomies();

		$misc_options_scroll_transition_offset_desktop = get_option( 'misc_options_scroll_transition_offset_desktop', 90 );
        if($misc_options_scroll_transition_offset_desktop == ''){
            $misc_options_scroll_transition_offset_desktop = 90;
        }
        $misc_options_scroll_transition_offset_mobile = get_option( 'misc_options_scroll_transition_offset_mobile', 40 );
        if($misc_options_scroll_transition_offset_mobile == ''){
            $misc_options_scroll_transition_offset_mobile = 40;
        }

		// Frontend (preload, master cache): only when AJAX is on
		$cache = Lay_Layout_Cache::is_cache_enabled(true);
		$dom_cursor_customizer = array(
			'active' => false,
		);
		$customizer_cursor = get_theme_mod( 'lay_cursor', '' );
		if( get_theme_mod( 'lay_cursor_use_dom_cursor', 0 ) == 1 && $customizer_cursor != '' ){
			$cursor_width = null;
			$cursor_height = null;
			$cursor_attachment_id = attachment_url_to_postid( $customizer_cursor );
			if( $cursor_attachment_id ){
				$cursor_metadata = wp_get_attachment_metadata( $cursor_attachment_id );
				if( is_array( $cursor_metadata ) && isset( $cursor_metadata['width'] ) && isset( $cursor_metadata['height'] ) ){
					$cursor_width = (int) $cursor_metadata['width'];
					$cursor_height = (int) $cursor_metadata['height'];
				}else{
					$cursor_file = get_attached_file( $cursor_attachment_id );
					if( $cursor_file && file_exists( $cursor_file ) ){
						$cursor_size = @getimagesize( $cursor_file );
						if( is_array( $cursor_size ) && isset( $cursor_size[0] ) && isset( $cursor_size[1] ) ){
							$cursor_width = (int) $cursor_size[0];
							$cursor_height = (int) $cursor_size[1];
						}
					}
				}
			}
			$dom_cursor_customizer = array(
				'active' => true,
				'src' => esc_url_raw( $customizer_cursor ),
				'hotspotX' => (int) get_theme_mod( 'lay_cursor_x', 0 ),
				'hotspotY' => (int) get_theme_mod( 'lay_cursor_y', 0 ),
				'width' => $cursor_width,
				'height' => $cursor_height,
				'keepNormalCursorForLinks' => get_theme_mod( 'lay_cursor_keep_normal_cursor_for_links', 0 ) == 1,
			);
		}

        wp_localize_script( 'frontend-app', 'layData',
			array(
				'misc_options_scroll_transition_offset_desktop' => $misc_options_scroll_transition_offset_desktop,
				'misc_options_scroll_transition_offset_mobile' => $misc_options_scroll_transition_offset_mobile,
                'loadmore_remember' => $loadmore_remember,
				'cover_animation_easing' => get_option('cover_animation_easing', 'easeOutSine'),
				'cover_animation_duration' => get_option('cover_animation_duration', 400),
				'mobile_menu_disable_body_scroll' => get_theme_mod('mobile_menu_disable_body_scroll', false),
                'custom_taxonomies' => $custom_taxonomies,
                'default_category_id' => $default_category_id,
                'default_product_cat_id' =>  $default_product_cat_id,
                'thumbnail_mouseover_image' => $thumbnail_mouseover_image,
                'thumbnail_video_and_mouseover_image_behavior' => $thumbnail_video_and_mouseover_image_behavior,
                'loadingBlur' => get_option('misc_options_image_loading_blur', ''),
                'lazyLoading' => get_option('misc_options_image_loading', 'on'),
                'videoLazyLoading' => get_option('misc_options_videos_lazyload', ''),
                'pt_follow_mouse' => get_theme_mod('pt_follow_mouse', ''),
				'wpapiroot' => esc_url_raw( rest_url() ),
				'simple_parallax' => $simple_parallax,
				'pa_type' => $pa_type,
				'show_arrows' => $showArrows,
				'projectsMeta' => $projectsMeta,
                'newsMeta' => $newsMeta,
                'deckMeta' => $deckMeta,
                'template_directory_uri' => Setup::$uri,
				'playicon' => $playicon,
				// 'siteTitle' => get_bloginfo('name'),
				'nav_amount' => $menu_amount,
				'nav_customizer_properties' => LayMenuCustomizerManager::get_customizer_properties_for_js(),
				'st_hidewhenscrollingdown' => CSS_Output::st_get_hide_when_scrolling_down(),
                'st_fadeout_whenscrollingdown' => CSS_Output::st_get_fadeout_when_scrolling_down(),
				'st_hide' => $st_hide,
				'navbar_hide' => $navbar_hide,
				'navbar_position' => CSS_Output::get_navbar_position(),
				'navbar_hidewhenscrollingdown' => CSS_Output::navbar_get_hide_when_scrolling_down(),
                'navbar_fadeout_whenscrollingdown' => CSS_Output::navbar_get_fadeout_when_scrolling_down(),
				'st_position' => $st_position,
				'footer_active_in_projects' => $footer_active_in_projects,
				'footer_active_in_pages' => $footer_active_in_pages,
				'footer_active_in_categories' => $footer_active_in_categories,
				'projectsFooterId' => $projectsFooterId,
				'pagesFooterId' => $pagesFooterId,
				'categoriesFooterId' => $categoriesFooterId,
				'individual_project_footers' => $individual_project_footers,
				'individual_page_footers' => $individual_page_footers,
				'individual_category_footers' => $individual_category_footers,
				'prevnext_navigate_through' => $prevnext_navigate_through,
				'fi_mo_touchdevice_behaviour' => $fi_mo_touchdevice_behaviour,
				'image_loading' => $image_loading,
				'cover_scrolldown_on_click' => $cover_scrolldown_on_click,
				'cover_darken_when_scrolling' => $cover_darken_when_scrolling,
				'cover_parallaxmove_when_scrolling' => $cover_parallaxmove_when_scrolling,
				'cover_disable_for_phone' => get_option('cover_disable_for_phone', '') == 'on' ? true : false,
				'misc_options_cover' => $misc_options_cover,
			'misc_options_max_width_apply_to_logo_and_nav' => $misc_options_max_width_apply_to_logo_and_nav,
			'misc_options_max_width_apply_to_burger' => $misc_options_max_width_apply_to_burger,
			'maxwidth' => $maxwidth,
				'frame_left' => $frame_left,
                'frame_right' => $frame_right,
                'frame_leftright_mu' => $frame_leftright_mu,
				'm_st_fontfamily' => $m_st_fontfamily,
				'misc_options_showoriginalimages' => $misc_options_showoriginalimages,
                'misc_options_showoriginalimages_for_phone' => $misc_options_showoriginalimages_for_phone,
				'phone_layout_active' => $phone_layout_active,
                'horizontal_scroll_disable_for_phone' => get_option('horizontal_scroll_disable_for_phone', 'on') === 'on',
				'horizontal_scroll_show_scrollbar' => get_option('horizontal_scroll_show_scrollbar', 'off') === 'on',
				'breakpoint' => MiscOptions::$phone_breakpoint,
				'phone_menu_breakpoint' => MiscOptions::$phone_menu_breakpoint,
				'tabletbreakpoint' => get_option('lay_tablet_breakpoint', 1024),
				'shortcodes' => LayShortcodes::$shortcodes_array,
				'is_customize' => $is_customize,
				'misc_options_show_fps_counter' => current_user_can('manage_options') ? get_option('misc_options_show_fps_counter', '') : '',
				'mobile_hide_menubar' => get_theme_mod('mobile_hide_menubar', 0),
				'mobile_menu_style' => get_theme_mod('mobile_menu_style', 'style_1'),
				'mobile_burger_type' => get_theme_mod('burger_icon_type', 'default_thin'),
				'siteUrl' => get_site_url(),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'intro_active' => get_option('misc_options_intro', '') == 'on' && !MiscOptions::$locked ? true : false,
                'intro_transition_duration' => get_option( 'intro_transition_duration', '500' ),
				'intro_hide_after' => get_option( 'intro_hide_after', '4000' ),
				// seems to be 0 and 1 instead of 'on' and 'off', maybe because this option is set in the customizer
				'intro_movement' => get_theme_mod( 'intro_movement', 0 ) == 1 ? true : false,
				'intro_disable_scroll' => get_theme_mod( 'intro_disable_scroll', 0 ) == 1 ? true : false,
				'intro_landscape' => LayIntro::get_data('landscape'),
				'intro_portrait' => LayIntro::get_data('portrait'),
				'intro_use_svg_overlay' => get_theme_mod('intro_use_svg_overlay', 0) == 1 ? true : false,
				'intro_use_text_overlay' => get_theme_mod('intro_use_text_overlay', 0) == 1 ? true : false,
				'intro_text' => get_theme_mod('intro_text', ''),
				'is_frontpage' => is_front_page(),
				'intro_svg_url' => LayIntro::get_svg_overlay_url(),
                'intro_hide_on_phone' => $intro_hide_on_phone,
				'is_ssl' => $is_ssl,
				'has_www' => $has_www,
				'is_qtranslate_active' => $is_qtranslate_active,
				// Enabled qTranslate-XT language codes (e.g. ['en', 'de']).
				// preload.js uses this to derive a per-language cache key from
				// hovered/clicked URLs so that switching languages doesn't return
				// a stale single-language preload from the previous language.
				'qtranslate_languages' => ( $is_qtranslate_active && isset( $GLOBALS['q_config']['enabled_languages'] ) && is_array( $GLOBALS['q_config']['enabled_languages'] ) )
					? array_values( $GLOBALS['q_config']['enabled_languages'] )
					: array(),
				'qtranslate_default_language' => ( $is_qtranslate_active && isset( $GLOBALS['q_config']['default_language'] ) )
					? $GLOBALS['q_config']['default_language']
					: '',
				'qtranslate_current_language' => ( $is_qtranslate_active && function_exists('qtranxf_getLanguage') )
					? qtranxf_getLanguage()
					: '',
				'is_polylang_active' => class_exists('PLL_Switcher'),
				'video_thumbnail_mouseover_behaviour' => get_theme_mod('fi_mo_video_behaviour', 'autoplay'),
				'element_transition_on_scroll' => $element_transition_on_scroll,
				'mobile_navbar_fixed' => get_theme_mod('mobile_menu_isfixed', '1'),
				'misc_options_disable_ajax' => $misc_options_disable_ajax,
				'document_title_from_server' => defined( 'WPSEO_VERSION' ),
				'misc_options_smooth_scroll' => get_option('misc_options_smooth_scroll', ''),
				'misc_options_smooth_scroll_disable_mac' => get_option('misc_options_smooth_scroll_disable_mac', ''),
				'anchorscroll_offset_desktop' => $anchorscroll_offset_desktop,
				'anchorscroll_offset_phone' => $anchorscroll_offset_phone,
				'anchorscroll_duration' => $anchorscroll_duration,
				'anchorscroll_easing' => $anchorscroll_easing,
				'anchor_menu_active' => $anchor_menu_active,
				'anchor_menu_keep_active_until_next' => $anchor_menu_keep_active_until_next,
				'mouseover_show_scroll_hidden' => $mouseover_show_scroll_hidden,
				'tgf_transition' => get_theme_mod('tgf_transition', 'fade_out_fade_in_2'),
                'pgf_transition' => get_theme_mod('pgf_transition', 'fade_out_fade_in_2'),
				'sideCartPluginActive' => $sideCartPluginActive,
				'lay_woocommerce_mobile_space_leftright' => get_theme_mod('lay_woocommerce_mobile_space_leftright', 15),
				'woocommerce_active' => class_exists( 'WooCommerce' ),
				'search_hidden_projects_enabled' => apply_filters('lay_search_enable_hidden_projects', false),
                'lay_woocommerce_use_swatches' => strpos(get_theme_mod('lay_woocommerce_singleproduct_quantity_type', 'big'), 'swatch') === false ? false : true,
                'lay_woocommerce_singleproduct_only_show_variation_images' => $lay_woocommerce_singleproduct_only_show_variation_images,
                'lay_woocommerce_singleproduct_layout_type_mobile' => $lay_woocommerce_singleproduct_layout_type_mobile,
                'lay_woocommerce_singleproduct_desktop_carousel_height' => $lay_woocommerce_singleproduct_desktop_carousel_height,
                'lay_woocommerce_singleproduct_desktop_carousel_slide_duration' => $lay_woocommerce_singleproduct_desktop_carousel_slide_duration,
                'lay_woocommerce_singleproduct_desktop_carousel_loop' => $lay_woocommerce_singleproduct_desktop_carousel_loop,
                'lay_sticky_footer' => $lay_sticky_footer,
				'menu_submenu_show_on' => get_theme_mod('menu_submenu_show_on', 'hover'),
				'spaces_mu' => $spaces_mu,
				'offsets_mu' => $offsets_mu,
				'menu_submenu_keep_open' => LayFrontend_Options::$menu_submenu_keep_open,
                'menu_submenu_keep_open_2' => LayFrontend_Options::$menu_submenu_keep_open_2,
                'menu_submenu_follow_parent_link_on_click_to_open_submenu' => LayFrontend_Options::$menu_submenu_follow_parent_link_on_click_to_open_submenu,
                'misc_options_element_transition_animate_opacity' => get_option('misc_options_element_transition_animate_opacity', 'on'),
                'search_autosuggest_hide' => get_theme_mod( 'search_autosuggest_hide', 0 ) == 1 ? true : false,
                'shownag' => $shownag,
                'never_change_scrolling_position' => get_option('misc_options_never_change_scrolling_position', ''),
                'navigation_transition_in_duration' => $navigation_transition_in_duration,
                'navigation_transition_out_duration' => $navigation_transition_out_duration,
                'navigation_transition_in' => $transition_in,
                'navigation_transition_out' => $transition_out,
                'navigation_transition_out_easing' => $navigation_transition_out_easing,
                'navigation_transition_in_easing' => $navigation_transition_in_easing,
                'navigation_transition_use_revealing_transition_on_first_visit' => $navigation_transition_use_revealing_transition_on_first_visit,
                'navigation_transition_y_translate_desktop' => $navigation_transition_y_translate_desktop,
                'navigation_transition_y_translate_phone' => $navigation_transition_y_translate_phone,
                'youtube' => get_option( 'laycarousel_youtube', "" ),
                'projects_overlays' => $projects_overlays,
                // Direct-load: when present, JS bootstraps the page as a project
                // overlay sitting on the configured underlay. Null when this
                // request isn't a direct-load (or when the feature is disabled
                // by WooCommerce / disable-ajax / etc.).
                'direct_load_underlay' => class_exists('LayDirectLoadOverlay') ? LayDirectLoadOverlay::getJsData() : null,
                'po_show_close_icon' => get_option('po_show_close_icon', 'on'),
                'po_animation' => get_option('po_animation', 'slideup'),
                'po_opening_behaviour' => get_option('po_opening_behaviour', 'immediate'),
                'po_easing_function' => get_option('po_animation_easing_function', 'cubic-bezier(0.075, 0.82, 0.165, 1)'),
                'po_animation_duration' => get_option('po_animation_duration', '1200'),
                'po_use_custom_close_icon' => get_option('po_use_custom_close_button', ''),
                'po_custom_close_icon' => $po_custom_close_icon,
                'page_overlay_feature' => get_option('misc_options_activate_overlay_feature', ''),
                'po_background_behind_overlay' => $po_behind_overlay_bg,
                'misc_options_alt_w100_h100_sizing' => $misc_options_alt_w100_h100_sizing,
                'disable_projectsoverlays_on_ig' => $disable_projectsoverlays_on_ig,
                'use_revealing_transition_when_opening_page_overlay' => $use_revealing_transition_when_opening_page_overlay,
                'activate_lay_cache' => $cache,
				'dom_cursor_manager_available' => true,
				'dom_cursor_customizer' => $dom_cursor_customizer,
			)
		);

		// Minimal payload for the head-loaded critical init script.
		// Keep this small to avoid waiting for the full app bundle.
		$pretext_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$pretext_l = strtolower( str_replace( '_', '-', (string) $pretext_locale ) );
		if ( strpos( $pretext_l, 'de' ) === 0 || strpos( $pretext_l, 'nl' ) === 0 ) {
			$pretext_hyphenation_js = 'pretext_hyphenation_germanic.min.js';
		} else if ( strpos( $pretext_l, 'fr' ) === 0 || strpos( $pretext_l, 'es' ) === 0 || strpos( $pretext_l, 'it' ) === 0 || strpos( $pretext_l, 'pt' ) === 0 ) {
			$pretext_hyphenation_js = 'pretext_hyphenation_roman.min.js';
		} else {
			$pretext_hyphenation_js = 'pretext_hyphenation_en.min.js';
		}
		wp_localize_script(
			'frontend-critical-init',
			'layCriticalInitData',
			array(
				'breakpoint' => MiscOptions::$phone_breakpoint,
				'image_loading' => $image_loading,
				'template_uri' => Setup::$uri,
				'pretext_hyphenation_js' => $pretext_hyphenation_js,
			)
		);

        // split up some js so our main js file is smaller:
        if ( class_exists( 'WooCommerce' ) ){
            wp_enqueue_script( 'lay-woocommerce-frontend-app', Setup::$uri."/frontend/assets/js/lay_woocommerce.min.js", array('jquery', 'swiper', 'vendor-frontend-footer'), Setup::$ver, true );
        }
	}


	public function include_griddermeta_in_term(){
		register_rest_field( 'category',
		    'grid',
		    array(
		        'get_callback'    => array($this, 'get_term_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);

		register_rest_field( 'category',
		    'phonegrid',
		    array(
		        'get_callback'    => array($this, 'get_term_phone_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);
	}

	public function get_term_griddermeta( $object, $field_name, $request ){
		return get_option( $object['id'].'_category_gridder_json', '' );
	}

	public function get_term_phone_griddermeta( $object, $field_name, $request ){
		return get_option( $object['id'].'_phone_category_gridder_json', '' );
	}

	public function include_griddermeta_in_page(){
		register_rest_field( 'page',
		    'grid',
		    array(
		        'get_callback'    => array($this, 'get_post_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);

		register_rest_field( 'page',
		    'phonegrid',
		    array(
		        'get_callback'    => array($this, 'get_post_phone_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);
	}

	public function include_griddermeta_in_product(){
		register_rest_field( 'product',
		    'grid',
		    array(
		        'get_callback'    => array($this, 'get_post_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);

		register_rest_field( 'product',
		    'phonegrid',
		    array(
		        'get_callback'    => array($this, 'get_post_phone_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);
	}

	public function include_griddermeta_in_post(){
		register_rest_field( 'post',
		    'grid',
		    array(
		        'get_callback'    => array($this, 'get_post_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);

		register_rest_field( 'post',
		    'phonegrid',
		    array(
		        'get_callback'    => array($this, 'get_post_phone_griddermeta'),
		        'update_callback' => null,
		        'schema'          => null,
		    )
		);
	}

	public function get_post_phone_griddermeta( $object, $field_name, $request ){
		if ( $this->can_access_password_content( $object, $request ) ) {
			return get_post_meta( $object['id'], '_phone_gridder_json', true );
		} else {
			return '';
		}
	}

	public function get_post_griddermeta( $object, $field_name, $request ){
		if ( $this->can_access_password_content( $object, $request ) ) {
			return get_post_meta( $object['id'], '_gridder_json', true );
		} else {
			return '';
		}
	}

	// modified version, taken from: class-wp-rest-posts-controller.php
	public function can_access_password_content( $object, $request ) {
		$post = get_post( $object['id'] );
		$request_params = $request->get_params();

		if ( empty( $post->post_password ) ) {
			// No password required, can access content
			return true;
		}

		// Edit context always gets access to password-protected posts.
		if ( 'edit' === $request_params['context'] ) {
			return true;
		}

		// No password, no auth.
		if ( empty( $request_params['password'] ) ) {
			return false;
		}

		// Double-check the request password.
		return hash_equals( $post->post_password, $request_params['password'] );
	}

	public static function get_custom_taxonomies() {
		$taxonomies = array();
		$post_types = array('post', 'product', 'lay_news');
		
		foreach ($post_types as $post_type) {
			$post_type_taxonomies = get_object_taxonomies($post_type, 'names');
			// Exclude default taxonomies
			$exclude_taxonomies = array('category', 'post_tag', 'product_cat', 'product_tag', 'lay_news_category', 'post_format');
			$exclude_taxonomies = apply_filters('lay_search_exclude_taxonomies', $exclude_taxonomies);
			
			foreach ($post_type_taxonomies as $taxonomy) {
				if (!in_array($taxonomy, $exclude_taxonomies)) {
					$taxonomies[] = $taxonomy;
				}
			}
		}
		// Remove duplicates
		return array_unique($taxonomies);
	}

}
$frontend = new Frontend();
