<?php
require get_template_directory().'/frontend/assets/php/layout_functions.php';
require get_template_directory().'/frontend/assets/php/header/header.php';
require get_template_directory().'/frontend/assets/php/footer/footer.php';
require get_template_directory().'/frontend/assets/php/row/row_functions.php';
require get_template_directory().'/frontend/assets/php/row/row.php';
require get_template_directory().'/frontend/assets/php/col/col.php';
require get_template_directory().'/frontend/assets/php/elements/el_functions.php';
require get_template_directory().'/frontend/assets/php/elements/thumbnailgrid_functions.php';
require get_template_directory().'/frontend/assets/php/elements/el.php';
require get_template_directory().'/frontend/assets/php/cover/cover.php';
require get_template_directory().'/frontend/assets/php/cover/cover_functions.php';
require get_template_directory().'/layout_cache/layout_cache.php';
require get_template_directory().'/frontend/assets/php/object_pool.php';

// todo: add lay-col-needs-leftframe-margin
class Lay_Layout{
    public static $building_raw_multilingual_layout_cache = false;

    public static function is_building_raw_multilingual_layout_cache() {
        return self::$building_raw_multilingual_layout_cache === true;
    }

    private static function set_qtranslate_language_for_ajax_request() {
        if ( ! isset($_POST['lang']) || $_POST['lang'] === '' ) {
            return null;
        }
        if ( ! isset($GLOBALS['q_config']) || ! is_array($GLOBALS['q_config']) ) {
            return null;
        }

        $requested_lang = sanitize_key(wp_unslash($_POST['lang']));
        $enabled_languages = isset($GLOBALS['q_config']['enabled_languages']) && is_array($GLOBALS['q_config']['enabled_languages'])
            ? $GLOBALS['q_config']['enabled_languages']
            : array();

        if ( ! in_array($requested_lang, $enabled_languages, true) ) {
            return null;
        }

        $previous_lang = isset($GLOBALS['q_config']['language']) ? $GLOBALS['q_config']['language'] : null;
        $GLOBALS['q_config']['language'] = $requested_lang;

        return $previous_lang;
    }

    public function __construct(){
        add_action( 'wp_ajax_get_laytheme_layout', array( $this, 'getLayoutAjax' ) );
        add_action( 'wp_ajax_nopriv_get_laytheme_layout', array( $this, 'getLayoutAjax' ) );
    }

    public static function getLayoutAjax(){
        $id = $_POST['id'];
        $type = $_POST['type'];
        $password = $_POST['password'];
        $is_project_overlay = $_POST['is_project_overlay'];

        // Check if this is a cache build request (for admin cache building)
        $is_cache_build = isset($_POST['is_cache_build']) && $_POST['is_cache_build'] === 'true';
        $previous_qtranslate_lang = self::set_qtranslate_language_for_ajax_request();

        $layout = Lay_Layout::getLayout($id, $type, array(
            'password' => $password,
            'full_layout' => true,
            'image_hover' => true,
            'project_overlay' => $is_project_overlay,
            'cache_build' => $is_cache_build,
        ));

        echo json_encode(array('layout' => $layout));
        if ( $previous_qtranslate_lang !== null && isset($GLOBALS['q_config']) && is_array($GLOBALS['q_config']) ) {
            $GLOBALS['q_config']['language'] = $previous_qtranslate_lang;
        }
        wp_die();
    }

	public static function getFixedHeader(){
		if( LayFrontend_Options::$misc_options_header == false ) {
			return '';
		}
		$headerId = Lay_Header::getHeaderId();
		if( $headerId == '' ) {
			return '';
		}
		$layout = Lay_Layout::getLayout($headerId, 'page', array('full_layout' => false));
		
		$headerPosition = LayFrontend_Options::$headerPosition;
		$fixedHeaderClass = '';
		switch($headerPosition){
			case 'top':
				$fixedHeaderClass = 'lay-fixed-header-top';
				break;
			case 'center':
				$fixedHeaderClass = 'lay-fixed-header-center';
				break;
			case 'bottom':
				$fixedHeaderClass = 'lay-fixed-header-bottom';
				break;
		}

		$fixedHeaderClass .= LayFrontend_Options::$headerTransparentBackground == 'on' ? ' lay-fixed-header-transparent' : '';
		$fixedHeaderClass .= LayFrontend_Options::$headerHideOnPhone == 'on' ? ' lay-fixed-header-hide-on-phone' : '';
		
		return '<div class="lay-fixed-header '.$fixedHeaderClass.'">'.$layout['markup'].'</div>';
	}

    public static function getLayoutInit($skipDirectLoadCheck = false){
        $data = Frontend::$current_type_id_slug_catid;
        $id = $data["id"];
        $type = $data["type"];
        $slug = $data["slug"];

        // Direct-load: the project's markup is rendered INSIDE the overlay
        // container by ProjectsOverlaysOptions::getMarkup(). Returning empty
        // here keeps the underlying-page area (rendered before getMarkup() in
        // index.php) blank until the JS bootstrap lazy-fetches the underlay.
        // The overlay-side call passes $skipDirectLoadCheck=true so it can
        // get the actual project markup.
        if (!$skipDirectLoadCheck && class_exists('LayDirectLoadOverlay') && LayDirectLoadOverlay::isActive()) {
            return '<div class="lay-content lay-direct-load-underlay-placeholder"></div>';
        }

        $layout = Lay_Layout::getLayout($id, $type);

        // If layout has no markup (e.g. password protected), return empty string
        if (!isset($layout['markup'])) {
            return '';
        }

        $layout['markup'] = apply_filters( 'lay_layout_init', $layout['markup'], $id, $type, $slug );

        return $layout['markup'];
    }

    // I need layoutObj, type and obj for the newpageshown event
    // getLayout returns an array of "markup", "layoutObj", "type", "obj". These are needed for the newpageshown javascript event
    // It also returns a "notification" array member, used for password protection status, so my javascript can shake the password input on wrongpassword

    // todo: split this function? getLayout

    // when full_layout == false, then cover and footer are not returned (e.g. news full content layout)
    // $options: password, full_layout, image_hover, project_overlay, cache_build, page_overlay
    /**
     * Translate cached/raw markup to the current visitor's language.
     *
     * Cached markup is stored multilingual (`[:en]…[:de]…[:]`); this is
     * called both on cache-HIT and on cache-MISS after the cache write so
     * the response always reflects the current language. Currently
     * implements qTranslate-XT — for Polylang/WPML, `the_content` carries
     * their filters and is applied separately by the caller.
     */
    public static function translate_layout_markup_for_response($markup) {
        if ( ! is_string($markup) || $markup === '' ) {
            return $markup;
        }
        if ( ! function_exists('qtranxf_getLanguage') ) {
            return $markup;
        }
        $current_lang = qtranxf_getLanguage();
        // Filter each `[:en]…[:de]…[:]` block separately. This matches the
        // Gridder preview: current language, then default language, then first
        // non-empty translation.
        if ( class_exists('Setup') ) {
            $markup = Setup::apply_qtranslate_with_fallback($markup);
        }
        // Convert internal hrefs / data-link to current-language URLs.
        if ( function_exists('qtranxf_convertURL') ) {
            $markup = preg_replace_callback(
                '/(\s)(href|data-link)="([^"]+)"/',
                function($matches) use ($current_lang) {
                    return $matches[1] . $matches[2] . '="' . qtranxf_convertURL($matches[3], $current_lang) . '"';
                },
                $markup
            );
        }
        return $markup;
    }

    public static function getLayout($id, $type, $options = array()) {
        $opts = array_merge(array(
            'password' => '',
            'full_layout' => true,
            'image_hover' => true,
            'project_overlay' => false,
            'cache_build' => false,
            'page_overlay' => false,
        ), $options);

        $password = $opts['password'];
        $getFullLayout = $opts['full_layout'];
        $getImageHover = $opts['image_hover'];
        $is_project_overlay = $opts['project_overlay'];
        $is_cache_build = $opts['cache_build'];
        $is_page_overlay = $opts['page_overlay'];

        if ($is_project_overlay === '' || $is_project_overlay === 'false') {
            $is_project_overlay = false;
        }
        if ($is_project_overlay === 'true' || $is_project_overlay === true) {
            $is_project_overlay = true;
        }
        
        // Cache frequently used options in static variable (avoid repeated get_option calls)
        static $cached_options = null;

        if ($cached_options === null) {
            $cached_options = array(
                'breakpoint' => get_option('lay_breakpoint', 600),
                'tablet_breakpoint' => get_option('lay_tablet_breakpoint', 1024),
                'hide_current_project' => get_option('misc_options_hide_current_project_from_thumbnailgrid', ''),
                'show_password_protected' => get_option('misc_options_show_password_protected_posts_in_thumbnailgrid', true),
                'enable_content_filter' => get_option('misc_options_enable_content_filter', '')
            );
        }
        
        // Build raw multilingual markup whenever qTranslate-XT is active.
        // qTranslate normally filters get_post_meta(), so `_gridder_json`
        // would already be stripped to the current language before Lay can
        // apply its empty-language fallback. We strip that filter while
        // building and restore it before returning.
        $will_cache_this_build = $is_cache_build || LayFrontend_Options::$cache_active;
        $should_build_raw_multilingual_layout = $will_cache_this_build || function_exists( 'qtranxf_getLanguage' );
        $removed_qtranx_meta_filters = array();

		if(LayFrontend_Options::$cache_active) {
			$shortcodes = LayShortcodes::$shortcodes_array;
			$get_cached_layout = true;
			
			// Disable cache if in custom preview mode
			if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
				$get_cached_layout = false;
			}
			
			foreach($shortcodes as $shortcode) {
				$id_ = $shortcode['info']['id'];
				if($id == $id_) {
					$get_cached_layout = false;
					break;
				}
			}
			if($get_cached_layout) {
				// Check cache first
				$cached_layout = Lay_Layout_Cache::get($id);
				
				// trying to get cached layout by id
				if ($cached_layout !== false) {
					// CACHE HIT - Skip all object creation, JSON decoding, and markup building!
                    // Filter the cached markup through translation plugin for current language
					if (isset($cached_layout['markup'])) {
						// Cached markup is stored raw multilingual; translate it to the
						// visitor's current language at every read so language switches keep working.
						$cached_layout['markup'] = Lay_Layout::translate_layout_markup_for_response($cached_layout['markup']);
						// Polylang / WPML hook into `the_content`. qTranslate is handled
						// above; the_content also runs shortcodes / wpautop / others.
						if ( get_option('misc_options_enable_content_filter', '') == 'on' ) {
							$cached_layout['markup'] = apply_filters('the_content', $cached_layout['markup']);
						}
					}
					// Ensure page_settings_styles exists for AJAX (older cache entries may not have it)
					if ( ! isset( $cached_layout['page_settings_styles'] ) && isset( $cached_layout['obj']['id'], $cached_layout['obj']['type'] ) ) {
						$cached_layout['page_settings_styles'] = Frontend::get_page_settings_styles_for_data( array( 'id' => $cached_layout['obj']['id'], 'type' => $cached_layout['obj']['type'] ) );
                    }
					// Older cache entries predate `deck_hide_chrome` — backfill so
					// AJAX navigation can still toggle the body class correctly.
					if ( ! isset( $cached_layout['obj']['deck_hide_chrome'] ) && isset( $cached_layout['obj']['type'] ) ) {
						$cached_layout['obj']['deck_hide_chrome'] = ( $cached_layout['obj']['type'] === 'lay_deck'
							&& get_option('misc_options_activate_deck_feature', '') === 'on'
							&& get_option('misc_options_deck_hide_chrome', 'on') !== '' );
					}
					return $cached_layout;
				} else {
					// CACHE MISS - will build everything from scratch

                }
			}
		}

        if( ($type == 'project' || $type == 'page' || $type == 'lay_news' || $type == 'lay_deck' || $type == 'product') && post_password_required( $id ) ) {
            return array('password' => 'protected');
        }

        // We're past the cache-HIT and password-protected early returns —
        // we'll definitely build markup now. If qTranslate is active or this
        // build will be cached, strip the qTranslate get_post_metadata filter
        // so post_meta reads come back raw multilingual.
        if ($should_build_raw_multilingual_layout) {
            self::$building_raw_multilingual_layout_cache = true;
            global $wp_filter;
            if (isset($wp_filter['get_post_metadata'])) {
                foreach ($wp_filter['get_post_metadata']->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback_name => $callback_data) {
                        if (strpos($callback_name, 'qtranx') !== false || strpos($callback_name, 'qtrans') !== false) {
                            remove_filter('get_post_metadata', $callback_data['function'], $priority);
                            $removed_qtranx_meta_filters[] = array(
                                'function' => $callback_data['function'],
                                'priority' => $priority,
                                'accepted_args' => isset($callback_data['accepted_args']) ? $callback_data['accepted_args'] : 1,
                            );
                        }
                    }
                }
            }
        }

        // test
        $desktop_json = '';
        $cpl_json = '';
        $desktop_footer_json = '';
        $cpl_footer_json = '';

        // for newpageshown event:
        $layoutObj;
        $obj = array( 
            'id' => '',
            'type' => '',
            'slug' => '',
            'catid' => '',
            'footerid' => '',
            'title' => ''
        );

        // cover
        $cover_on_desktop = LayCoverFunctions::hasCover( $type, $id );
        $desktop_cover_is_100vh_set_by_user = false;
        $cover_on_phone = $cover_on_desktop && LayFrontend_Options::$cover_disable_for_phone == false ? true : false;

        // disable cover for desktop and phone when magnetic slides is active, because cover feature is incompatible with magnetic slides feature
        $magnetic_slides_active = false;
        if( class_exists('LayThemeMagneticSlides') && method_exists('LayThemeMagneticSlides', 'isActiveHere') && $getFullLayout == true ){
            $magnetic_slides_active = LayThemeMagneticSlides::isActiveHere($id, $type);
            if( $magnetic_slides_active == true ) {
                $cover_on_desktop = false;
            }
        }
        $magnetic_slides_active_on_phone = false;
        if( class_exists('LayThemeMagneticSlides') && method_exists('LayThemeMagneticSlides', 'activeOnPhone') && $getFullLayout == true ){
            if( LayThemeMagneticSlides::activeOnPhone() == true && $magnetic_slides_active ) {
                $magnetic_slides_active_on_phone = true;
            }
            if( $magnetic_slides_active_on_phone == true ){
                $cover_on_phone = false;
            }
        }

        $get_simpler_markup_for_scrollslider = false;
        if( class_exists('LayThemeScrollSlider') && method_exists('LayThemeScrollSlider', 'isActiveHere') && $getFullLayout == true ){
            // Don't enable scrollslider for project overlays
            if( $is_project_overlay !== true && LayThemeScrollSlider::isActiveHere($id, $type) == true) {
                if(get_option( 'ss_element_layout', 'random' ) == 'random') {
                    $get_simpler_markup_for_scrollslider = true;
                }
                $cover_on_desktop = false;
                $cover_on_phone = false;
            }
        }

        $getOpeningAndClosingRowMarkup = true;
        $getOpeningAndClosingRowMarkupForCPL = true;

        if( $get_simpler_markup_for_scrollslider == true ) {
            $getOpeningAndClosingRowMarkup = false;
        }

        // if scrollslider is enabled for phone, and is active here and we use random layout, then we dont need the row opening and closing markup
        // Don't enable scrollslider for project overlays
        if( class_exists('LayThemeScrollSlider') && method_exists('LayThemeScrollSlider', 'isActiveHere') && $is_project_overlay !== true && LayThemeScrollSlider::isActiveHere($id, $type) == true && $getFullLayout == true && get_option( 'ss_element_layout', 'random' ) == 'random' ){
            $isEnabledForPhone = get_option( 'ss_disable_for_phone', '' ) == 'on' ? false : true;
            if( $isEnabledForPhone ) {
                $getOpeningAndClosingRowMarkupForCPL = false;
            } else {
                $getOpeningAndClosingRowMarkupForCPL = true;
            }
        }

        if( $getFullLayout == false ) {
            $cover_on_desktop = false;
            $cover_on_phone = false;
        }

        // PERFORMANCE OPTIMIZATION: Batch database queries
        
        switch($type){
            case "archive":
                $queried_obj = get_queried_object();
                // "shop" page is an archive - get ALL post meta at once
                $all_meta = get_post_meta($id);
                $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';
                $obj['id'] = $id;
                // https://developer.wordpress.org/reference/functions/get_queried_object/
                // todo: should i use $queried_obj->slug; here instead? 
                $obj['slug'] = $queried_obj->name;
                $obj['type'] = 'archive';
                $obj['catid'] = '';
                $obj['title'] = $queried_obj->name;
            break;
            case "category":
                $desktop_json = get_option($id."_category_gridder_json", '');
                $cpl_json = get_option( $id.'_phone_category_gridder_json', '' );
                $term = get_term($id);
                $obj['id'] = $term->term_id;
                $obj['slug'] = $term->slug;
                $obj['type'] = 'category';
                $obj['catid'] = array($term->term_id);
                $obj['title'] = $term->name;
            break;
            case "project":
                // OPTIMIZED: Get post and all meta in one query, then get categories
                $post = get_post($id);
                
                // Check for custom preview mode first
                if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
                    $preview_id = Gridder_Custom_Preview::get_preview_post_id();
                    if ( $preview_id == $id ) {
                        $preview_desktop = get_post_meta($id, '_gridder_json_preview', true);
                        $preview_phone = get_post_meta($id, '_phone_gridder_json_preview', true);
                        
                        if ( !empty($preview_desktop) ) {
                            $desktop_json = $preview_desktop;
                            $cpl_json = !empty($preview_phone) ? $preview_phone : '';
                            goto after_json_load;
                        }
                    }
                }
                
                $all_meta = get_post_meta($id);
                $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';
                
                after_json_load:
                
                $obj['id'] = $post->ID;
                $obj['slug'] = $post->post_name;
                $obj['type'] = 'project';
                $obj['catid'] = wp_get_post_categories($post->ID, array('fields'=>'ids'));
                $obj['title'] = $post->post_title;
            break;
            case "product":
                if(LayFrontend_Options::$gridder_active_for_products) {
                    // OPTIMIZED: Get post and all meta at once
                    $post = get_post($id);
                    
                    // Check for custom preview mode first
                    if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
                        $preview_id = Gridder_Custom_Preview::get_preview_post_id();
                        if ( $preview_id == $id ) {
                            $preview_desktop = get_post_meta($id, '_gridder_json_preview', true);
                            $preview_phone = get_post_meta($id, '_phone_gridder_json_preview', true);
                            
                            if ( !empty($preview_desktop) ) {
                                $desktop_json = $preview_desktop;
                                $cpl_json = !empty($preview_phone) ? $preview_phone : '';
                                goto after_json_load_product;
                            }
                        }
                    }
                    
                    $all_meta = get_post_meta($id);
                    $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                    $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';
                    
                    after_json_load_product:
                    
                    $obj['id'] = $post->ID;
                    $obj['slug'] = $post->post_name;
                    $obj['type'] = 'product';
                    $obj['catid'] = wp_get_post_categories($post->ID, array('fields'=>'ids'));
                    $obj['title'] = $post->post_title;
                }
            break;
            case "page":
                // OPTIMIZED: Get post and all meta at once
                $page = get_post($id);
                
                // Check for custom preview mode first
                if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
                    $preview_id = Gridder_Custom_Preview::get_preview_post_id();
                    if ( $preview_id == $id ) {
                        $preview_desktop = get_post_meta($id, '_gridder_json_preview', true);
                        $preview_phone = get_post_meta($id, '_phone_gridder_json_preview', true);
                        
                        if ( !empty($preview_desktop) ) {
                            $desktop_json = $preview_desktop;
                            $cpl_json = !empty($preview_phone) ? $preview_phone : '';
                            goto after_json_load_page;
                        }
                    }
                }
                
                $all_meta = get_post_meta($id);
                $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';
                
                after_json_load_page:
                
                $obj['id'] = $page->ID;
                $obj['slug'] = $page->post_name;
                $obj['type'] = 'page';
                $obj['catid'] = '';
                $obj['title'] = $page->post_title;
            break;
            case "news":
            case "lay_news":
                // OPTIMIZED: Get post and all meta at once
                $lay_news = get_post($id);

                // Check for custom preview mode first
                if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
                    $preview_id = Gridder_Custom_Preview::get_preview_post_id();
                    if ( $preview_id == $id ) {
                        $preview_desktop = get_post_meta($id, '_gridder_json_preview', true);
                        $preview_phone = get_post_meta($id, '_phone_gridder_json_preview', true);

                        if ( !empty($preview_desktop) ) {
                            $desktop_json = $preview_desktop;
                            $cpl_json = !empty($preview_phone) ? $preview_phone : '';
                            goto after_json_load_news;
                        }
                    }
                }

                $all_meta = get_post_meta($id);
                $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';

                after_json_load_news:

                $obj['id'] = $lay_news->ID;
                $obj['slug'] = $lay_news->post_name;
                $obj['type'] = 'lay_news';
                $obj['catid'] = '';
                $obj['title'] = $lay_news->post_title;
            break;
            case "deck":
            case "lay_deck":
                $lay_deck = get_post($id);

                // Custom preview mode (parity with the news/page branches above).
                if ( class_exists('Gridder_Custom_Preview') && Gridder_Custom_Preview::is_custom_preview() ) {
                    $preview_id = Gridder_Custom_Preview::get_preview_post_id();
                    if ( $preview_id == $id ) {
                        $preview_desktop = get_post_meta($id, '_gridder_json_preview', true);
                        $preview_phone = get_post_meta($id, '_phone_gridder_json_preview', true);

                        if ( !empty($preview_desktop) ) {
                            $desktop_json = $preview_desktop;
                            $cpl_json = !empty($preview_phone) ? $preview_phone : '';
                            goto after_json_load_deck;
                        }
                    }
                }

                $all_meta = get_post_meta($id);
                $desktop_json = isset($all_meta['_gridder_json'][0]) ? $all_meta['_gridder_json'][0] : '';
                $cpl_json = isset($all_meta['_phone_gridder_json'][0]) ? $all_meta['_phone_gridder_json'][0] : '';

                after_json_load_deck:

                $obj['id'] = $lay_deck->ID;
                $obj['slug'] = $lay_deck->post_name;
                $obj['type'] = 'lay_deck';
                $obj['catid'] = '';
                $obj['title'] = $lay_deck->post_title;
            break;
			default:
				$obj['id'] = $id;
				$obj['slug'] = '';
				$obj['type'] = $type;
				$obj['catid'] = '';
				$obj['title'] = '';
			break;
        }
        
        $obj['horizontal_scroll_active'] = false;
        if( $getFullLayout == true && class_exists('LayHorizontalScroll') ){
            $obj['horizontal_scroll_active'] = LayHorizontalScroll::should_activate( $obj['id'], $obj['type'], $is_project_overlay === true );
        }



        // no content, like when someone didnt create a page yet but that page is shown on frontend
        // if( $desktop_json == '' ) {
        //     return array(
        //         'markup' => '<div class="lay-content lay-empty"></div>',
        //         'type' => $type,
        //         'layoutObj' => '',
        //         'obj' => '',
        //         'notification' => 'success'
        //     );
        // }

        // Get footer data
        $obj['footerid'] = Lay_Footer::getFooterId($obj['id'], $obj['type']);
        $footer_json_array = Lay_Footer::getFooterJSON($id, $type);

        if( is_array( $footer_json_array ) ) {
            $desktop_footer_json = array_key_exists('desktop', $footer_json_array) ? $footer_json_array['desktop'] : '';
            $cpl_footer_json = array_key_exists('phone', $footer_json_array) ? $footer_json_array['phone'] : '';
        }

        // decode JSON (CACHE MISS PATH - this is skipped on cache hit)
        $desktop_arr = json_decode($desktop_json, true);
        $cpl_arr = json_decode($cpl_json, true);
        $footer_desktop_arr = json_decode($desktop_footer_json, true);
        $cpl_footer_arr = json_decode($cpl_footer_json, true);
        $obj['has_custom_phone_layout'] = LayFrontend_Options::$phone_layout_active && is_array($cpl_arr);

        // Page settings: hide site title / menu / footer (for newpageshown body classes when using AJAX navigation)
        if ( $obj['type'] === 'category' ) {
            $obj['hide_site_title'] = get_term_meta( (int) $obj['id'], '_lay_hide_site_title', true ) === 'on';
            $obj['hide_menu'] = get_term_meta( (int) $obj['id'], '_lay_hide_menu', true ) === 'on';
            $obj['hide_menu_2'] = get_term_meta( (int) $obj['id'], '_lay_hide_menu_2', true ) === 'on';
            $obj['hide_menu_3'] = get_term_meta( (int) $obj['id'], '_lay_hide_menu_3', true ) === 'on';
            $obj['hide_menu_4'] = get_term_meta( (int) $obj['id'], '_lay_hide_menu_4', true ) === 'on';
            $obj['hide_footer'] = get_term_meta( (int) $obj['id'], '_lay_hide_footer', true ) === 'on';
        } else {
            $obj['hide_site_title'] = get_post_meta( (int) $obj['id'], '_lay_hide_site_title', true ) === 'on';
            $obj['hide_menu'] = get_post_meta( (int) $obj['id'], '_lay_hide_menu', true ) === 'on';
            $obj['hide_menu_2'] = get_post_meta( (int) $obj['id'], '_lay_hide_menu_2', true ) === 'on';
            $obj['hide_menu_3'] = get_post_meta( (int) $obj['id'], '_lay_hide_menu_3', true ) === 'on';
            $obj['hide_menu_4'] = get_post_meta( (int) $obj['id'], '_lay_hide_menu_4', true ) === 'on';
            $obj['hide_footer'] = get_post_meta( (int) $obj['id'], '_lay_hide_footer', true ) === 'on';
        }

        // Mirror the body-class condition from Frontend::add_body_classes so
        // AJAX navigation onto a deck page can re-add `lay-deck-hide-chrome`.
        // Without this, navigating from a normal page to a deck slide swaps
        // the type-* class but leaves chrome visible.
        $obj['deck_hide_chrome'] = ( $obj['type'] === 'lay_deck'
            && get_option('misc_options_activate_deck_feature', '') === 'on'
            && get_option('misc_options_deck_hide_chrome', 'on') !== '' );

        // for newpageshown event:
        $layoutObj = $desktop_arr;

        $desktop_bg_color = is_array($desktop_arr) && array_key_exists('bgColor', $desktop_arr) ? $desktop_arr['bgColor'] : '';
        $desktop_bg_opacity_value = is_array($desktop_arr) && array_key_exists('bgOpacity', $desktop_arr) && $desktop_arr['bgOpacity'] !== null ? (float)$desktop_arr['bgOpacity'] : 100;
        $desktop_bg_opacity_value = max(0, min(100, $desktop_bg_opacity_value));
        $desktop_bg_blur_value = is_array($desktop_arr) && array_key_exists('bgBlur', $desktop_arr) && $desktop_arr['bgBlur'] !== null ? (float)$desktop_arr['bgBlur'] : 0;
        $desktop_has_custom_bg_opacity = $desktop_bg_opacity_value < 100;
        $desktop_has_custom_bg_blur = $desktop_bg_blur_value > 0;
        $desktop_has_custom_bg_effects = $desktop_has_custom_bg_opacity || $desktop_has_custom_bg_blur;

        // For CPL: Read directly from cpl_arr (phone_gridder_json_field) if it exists, otherwise use desktop values
        $cpl_exists = is_array($cpl_arr);
        if ($cpl_exists) {
            // Read directly from cpl_arr (phone layout has its own background values)
            $cpl_bg_color = is_array($cpl_arr) && array_key_exists('bgColor', $cpl_arr) ? $cpl_arr['bgColor'] : null;
            $cpl_bg_opacity_value = is_array($cpl_arr) && array_key_exists('bgOpacity', $cpl_arr) && $cpl_arr['bgOpacity'] !== null ? (float)$cpl_arr['bgOpacity'] : 100;
            $cpl_bg_opacity_value = max(0, min(100, $cpl_bg_opacity_value));
            $cpl_bg_blur_value = is_array($cpl_arr) && array_key_exists('bgBlur', $cpl_arr) && $cpl_arr['bgBlur'] !== null ? (float)$cpl_arr['bgBlur'] : 0;
            $cpl_bg_image = is_array($cpl_arr) && array_key_exists('bgImage', $cpl_arr) ? $cpl_arr['bgImage'] : null;
            $cpl_bg_video = is_array($cpl_arr) && array_key_exists('bgVideo', $cpl_arr) ? $cpl_arr['bgVideo'] : null;
        } else {
            // No CPL exists - use desktop values for automatically generated phone layout
            $cpl_bg_color = $desktop_bg_color;
            $cpl_bg_opacity_value = $desktop_bg_opacity_value;
            $cpl_bg_blur_value = $desktop_bg_blur_value;
            $cpl_bg_image = is_array($desktop_arr) && array_key_exists('bgImage', $desktop_arr) ? $desktop_arr['bgImage'] : null;
            $cpl_bg_video = is_array($desktop_arr) && array_key_exists('bgVideo', $desktop_arr) ? $desktop_arr['bgVideo'] : null;
        }
        
        $cpl_has_custom_bg_opacity = $cpl_bg_opacity_value < 100;
        $cpl_has_custom_bg_blur = $cpl_bg_blur_value > 0;
        $cpl_has_custom_bg_effects = $cpl_has_custom_bg_opacity || $cpl_has_custom_bg_blur;

        $desktop_has_bg_video = Lay_Layout::hasBackgroundVideo($desktop_arr);
        $cpl_has_bg_video = $cpl_bg_video !== null && $cpl_bg_video !== '';
        $desktop_has_bg_image = Lay_Layout::hasBackgroundImage($desktop_arr);
        $cpl_has_bg_image = $cpl_bg_image !== null && $cpl_bg_image !== '';

        $desktop_background_media_markup = '';
        $cpl_background_media_markup = '';

        // CACHE MISS PATH - Build markup from scratch (this is skipped on cache hit)

        // prepare Rows
        $desktop_rows = false;
        $cpl_rows = false;
        $desktop_footer_rows = false;
        $cpl_footer_rows = false;
        $desktop_cover_row = false;
        $cpl_cover_rows = false;

        // markups
        $desktop_freely_placed_els = '';
        $desktop_cover_markup = false;
        $desktop_cover_freely_placed_els = false;
        $desktop_markup = false;
        $cpl_markup = false;
        $cpl_freely_placed_els = '';
        $cpl_cover_freely_placed_els = false;
        $cpl_cover_markup = false;
        $desktop_footer_markup = false;
        $footer_desktop_freely_placed_els = false;
        
        $cpl_footer_markup = false;
        $cpl_row_col_el_markup = false;
        $cpl_footer_freely_placed_els = false;

        // DESKTOP – options for custom aspect ratio (phone): only when no CPL, pass selector so img can output phone media-query style
        $desktop_el_options = array();
        // Project overlay: markup is injected into #lay-project-overlay (see layout_controller.js); .lay-content is not body>.lay-content, so scope #grid under #lay-project-overlay.
        $desktop_grid_root = ( $is_project_overlay === true ) ? '#lay-project-overlay > .lay-content' : 'body > .lay-content';
        if ( !$cpl_exists ) {
            $desktop_el_options['no_custom_phone_layout'] = true;
            // Cover row renders under .cover-region-desktop … .cover-inner, not inside #grid — same dual-root pattern as with CPL.
            if ( $cover_on_desktop ) {
                $desktop_el_options['grid_container_selector'] = $desktop_grid_root . ' #grid.id-' . $id . ', .cover-region-desktop.id-' . $id . ' .cover-inner';
            } else {
                $desktop_el_options['grid_container_selector'] = $desktop_grid_root . ' #grid';
            }
        } else {
            // CPL exists: desktop grid and phone grid both carry columns with the same relids; scope CSS (carousel fixed height, etc.).
            // Desktop cover lives under .cover-region-desktop, not #grid (same pattern as background-image CSS).
            $desktop_el_options['grid_container_selector'] = $desktop_grid_root . ' #grid.id-' . $id . ', .cover-region-desktop.id-' . $id . ' .cover-inner';
        }
        $footer_el_options = array();
        if ( !$cpl_exists ) {
            $footer_el_options['no_custom_phone_layout'] = true;
        }
        // So text max-width (pxwidth) and similar CSS can target #footer / #footer-custom-phone-grid (see LayText).
        $footer_el_options['grid_container_selector'] = '#footer';

        // DESKTOP
        if( is_array($desktop_arr)  ){
            $desktop_freely_placed_els = Lay_Layout::getFreelyPlacedCols($desktop_arr, $obj['id'], 'desktop', $cover_on_desktop ? 'outside-cover': 'all', $desktop_el_options, $getImageHover);
            $desktop_rows = Lay_Layout::prepareRows($desktop_arr, $cover_on_desktop);
        }
        // split up rows into cover row and desktop rows
        if( $cover_on_desktop && is_array( $desktop_rows ) ) {
            // https://www.php.net/manual/en/function.array-shift.php
            $desktop_cover_row = array_shift( $desktop_rows );
            $desktop_cover_is_100vh_set_by_user = Lay_LayoutFunctions::getRowIs100vh($desktop_cover_row);
            $desktop_cover_row['row100vh'] = true;
            $desktop_cover_row['rowcustomheight'] = '';
            $desktop_cover_row = array($desktop_cover_row);
            $desktop_cover_markup = Lay_Layout::getRowColElMarkup($desktop_cover_row, $obj['id'], 'desktop', $getImageHover, $getOpeningAndClosingRowMarkup, $desktop_el_options);
            $desktop_cover_freely_placed_els = Lay_Layout::getFreelyPlacedCols($desktop_arr, $obj['id'], 'desktop', 'in-cover', $desktop_el_options, $getImageHover);
        }
        if( is_array($desktop_arr) ){
            $desktop_markup = Lay_Layout::getRowColElMarkup($desktop_rows, $obj['id'], 'desktop', $getImageHover, $getOpeningAndClosingRowMarkup, $desktop_el_options);
        }

        // CPL – same relids as desktop; pass grid_container_selector so element CSS (e.g. carousel fixed height) is scoped per grid (cover uses .cover-region-phone).
        $cpl_el_options = array(
            'grid_container_selector' => '#custom-phone-grid.id-' . $id . ', .cover-region-phone.id-' . $id . ' .cover-inner',
        );
        // CPL
        if( is_array($cpl_arr) ){
            $cpl_freely_placed_els = Lay_Layout::getFreelyPlacedCols($cpl_arr, $obj['id'], 'phone', $cover_on_phone ? 'outside-cover': 'all', $cpl_el_options, $getImageHover);
            $cpl_rows = Lay_Layout::prepareRows($cpl_arr, $cover_on_phone);
        }
        // split up rows into cover row and phone rows
        if( $cover_on_phone && is_array($cpl_arr) ){
            // https://www.php.net/manual/en/function.array-shift.php
            $cpl_cover_rows = array_shift( $cpl_rows );
            $cpl_cover_rows['row100vh'] = true;
            $cpl_cover_rows['rowcustomheight'] = '';
            $cpl_cover_rows = array($cpl_cover_rows);
            $cpl_cover_markup = Lay_Layout::getRowColElMarkup($cpl_cover_rows, $obj['id'], 'phone', $getImageHover, $getOpeningAndClosingRowMarkupForCPL, $cpl_el_options);
            $cpl_cover_freely_placed_els = Lay_Layout::getFreelyPlacedCols($cpl_arr, $obj['id'], 'phone', 'in-cover', $cpl_el_options, $getImageHover);
        }
        if( is_array($cpl_arr) ){
            $cpl_row_col_el_markup = Lay_Layout::getRowColElMarkup($cpl_rows, $obj['id'], 'phone', $getImageHover, $getOpeningAndClosingRowMarkupForCPL, $cpl_el_options);
        }

        // FOOTER
        if( is_array($footer_desktop_arr) && $getFullLayout == true ){
            $desktop_footer_rows = Lay_Layout::prepareRows($footer_desktop_arr, false);
            $desktop_footer_markup = Lay_Layout::getRowColElMarkup($desktop_footer_rows, $obj['id'], 'desktop', $getImageHover, true, $footer_el_options);
            $footer_desktop_freely_placed_els = Lay_Layout::getFreelyPlacedCols($footer_desktop_arr, $obj['id'], 'desktop', 'all', $footer_el_options, $getImageHover);
        }
        if( is_array($cpl_footer_arr) && $getFullLayout == true ){
            $cpl_footer_rows = Lay_Layout::prepareRows($cpl_footer_arr, false);
            $footer_phone_el_options = $footer_el_options;
            if ( LayFrontend_Options::$phone_layout_active ) {
                $footer_phone_el_options['grid_container_selector'] = '#footer-custom-phone-grid';
            }
            $cpl_footer_markup = Lay_Layout::getRowColElMarkup($cpl_footer_rows, $obj['id'], 'phone', $getImageHover, true, $footer_phone_el_options);
            $cpl_footer_freely_placed_els = Lay_Layout::getFreelyPlacedCols($cpl_footer_arr, $obj['id'], 'phone', 'all', $footer_phone_el_options, $getImageHover);
        }

        // Custom Phone Layout
        $cpl_exists_html_class = 'nocustomphonegrid';
        $cover_cpl_class = '';
        $cpl_background_color_projectoverlay = '';
        if( LayFrontend_Options::$phone_layout_active && is_array($cpl_arr) ) {
            // Cover CPL
            $cover_cpl_class = 'cpl-nocover';
            $cpl_cover_rowgutter_css = '';
            $cover_cpl_css = '';
            // todo: && !isFullscreenSliderActive()
            // if( cover_controller.hasCover(type, obj.id) && !isFullscreenSliderActive() ){
            if( $cover_on_phone ) {
                $cover_cpl_class = 'cpl-hascover';
                $cover_cpl_css = Lay_LayoutFunctions::getGridCSS($cpl_arr, '.cover-region-phone', true);
                $cover_cpl_css .= Lay_LayoutFunctions::getHorizontalGridCSS($cpl_arr, '.cover-region-phone', true);
                // set main grid padding-top to first rowgutter
                $cpl_cover_rowgutter_css = Lay_LayoutFunctions::getFirstRowGutterAsPaddingTopForCoverCSS($cpl_arr, '#custom-phone-grid.id-'.$id, true);
                // need .cover-inner so the grid's background color can overwrite the global background color set in customizer
                $cpl_cover_markup = 
                '<div class="cover-region cover-region-phone _100vh id-'.$id.'"">
                    <div class="cover-inner _100vh">
                        '.$cpl_cover_markup.'
                        '.$cpl_cover_freely_placed_els.'
                    </div>
                    '.Lay_LayoutFunctions::getCoverDownArrowMarkup(array('phoneOnly' => false)).'
                </div>';
            } else {
                $cpl_cover_markup = '';
            }

            $cpl_exists_html_class = 'hascustomphonegrid';
            $cpl_css = Lay_LayoutFunctions::getGridCSS($cpl_arr, '#custom-phone-grid.id-'.$id, true);
            $cpl_css .= Lay_LayoutFunctions::getHorizontalGridCSS($cpl_arr, '#custom-phone-grid.id-'.$id, true);
            $cpl_background_color_css = '';
            $cpl_frame_css = Lay_LayoutFunctions::getGridFrameCSS($cpl_arr, '#custom-phone-grid.id-'.$id, '#custom-phone-grid.id-'.$id, true);
            if( $cover_on_phone ) {
                $cpl_frame_css = Lay_LayoutFunctions::getGridFrameCSS($cpl_arr, '.cover-region-phone.id-'.$id.' .cover-inner', '#custom-phone-grid.id-'.$id, true);
                $cpl_background_color_css = Lay_LayoutFunctions::getBackgroundCSS($cpl_arr, '.cover-region-phone.id-'.$id.' .cover-inner');
            }
            $cpl_background_color_css .= Lay_LayoutFunctions::getBackgroundCSS($cpl_arr, '#custom-phone-grid.id-'.$id);
            $cpl_rows_css = Lay_LayoutFunctions::getRowsMarginBottomCSS($cpl_arr, $cpl_rows, '#custom-phone-grid.id-'.$id, true);
            
            $cpl_background_color_projectoverlay = '';
            if( $is_project_overlay === true ) {
                $bgColor = $cpl_bg_color;
                $phoneOverlayOpacity = $cpl_bg_opacity_value;
                $phoneOverlayBlur = $cpl_bg_blur_value;
                if( $cpl_has_bg_video ) {
                    $bgColor = 'transparent';
                    $phoneOverlayOpacity = 100;
                    $phoneOverlayBlur = 0;
                } elseif( $bgColor == '' || $bgColor == null ) {
                    $bgColor = ProjectsOverlaysOptions::$bg_color;
                }
                // Override opacity and blur if setting is enabled
                if( LayFrontend_Options::$po_override_overlay_bg_opacity_blur ) {
                    $phoneOverlayOpacity = LayFrontend_Options::$po_overlay_bg_opacity;
                    $phoneOverlayBlur = LayFrontend_Options::$po_overlay_bg_blur;
                }
                $stylePhone = Lay_Layout::buildBackgroundStyle($bgColor, $phoneOverlayOpacity, $phoneOverlayBlur);
                $cpl_background_color_projectoverlay = '<style>@media (max-width: '.(LayFrontend_Options::$breakpoint).'px){ #lay-project-overlay{'.$stylePhone.'} }</style>';
                if( $cpl_has_custom_bg_effects && !$cpl_has_bg_video ) {
                    $cpl_background_color_css = '';
                }
            }

            $cpl_background_image_css = Lay_LayoutFunctions::getBackgroundImageCSS($cpl_arr, '#custom-phone-grid.id-'.$id.', .cover-region-phone.id-'.$id.' .cover-inner');     

            if( $cpl_has_bg_image ) {
                $cpl_background_media_markup .= Lay_Layout::buildBackgroundImageMarkup($cpl_bg_image, 'lay-background-image-mobile', $cpl_bg_opacity_value, $cpl_bg_blur_value);
                $cpl_background_image_css = '';
            }
            
            // Add CPL background video if it exists
            if( $cpl_has_bg_video && is_array($cpl_bg_video) ) {
                $cpl_background_media_markup .= Lay_Layout::buildBackgroundVideoMarkup($cpl_bg_video, 'lay-background-video-mobile', $cpl_bg_opacity_value, $cpl_bg_blur_value);
            }

            $cpl_has_background_media = $cpl_has_bg_image || $cpl_has_bg_video;
            $cpl_empty_class = Lay_Layout::isEmptyLayout($cpl_rows) && $cpl_freely_placed_els == '' && $cpl_background_image_css == '' && !$cpl_has_background_media ? 'lay-empty' : 'lay-not-empty';

            $cpl_markup = 
                '<!-- Start CPL Layout -->'
                .$cpl_cover_rowgutter_css
                .$cover_cpl_css
                .$cpl_cover_markup;
                if( $cover_on_phone ) {
                    // need cover content wrap, so i can set global bg color and bg image to it
                    // the inner div: .grid can now be set to the grid-specific background-color, overwriting .cover-content's background
                    // i cannot just set the global background color or background image to an outer div like .lay-content, because then ->
                    // when cover would be active and i would scroll down, the transparent div that is on top of the cover would not hide it beneath
                    $cpl_markup .= '<div class="cover-content cover-content-phone">';
                }
                // need .grid-inner wrapper so I can have a fullscreen slider that is horizontal
                // For project overlays, check if we have actual custom background settings
                $cpl_project_overlay_has_custom_bg = false;
                if ($is_project_overlay === true) {
                    // Only add custom bg class if we have actual custom background settings
                    // Default values: bgColor empty, opacity 100%, blur 0, no video, no image = NO custom bg
                    // Check if bgColor is explicitly set (not null/empty)
                    if ($cpl_bg_color !== null && $cpl_bg_color !== '') {
                        $cpl_project_overlay_has_custom_bg = true;
                    }
                    // Check if we have non-default opacity (less than 100%)
                    if ($cpl_bg_opacity_value < 100) {
                        $cpl_project_overlay_has_custom_bg = true;
                    }
                    // Check if we have non-default blur (greater than 0)
                    if ($cpl_bg_blur_value > 0) {
                        $cpl_project_overlay_has_custom_bg = true;
                    }
                    // Check if we have background video
                    if ($cpl_has_bg_video) {
                        $cpl_project_overlay_has_custom_bg = true;
                    }
                    // Check if we have background image
                    if ($cpl_has_bg_image) {
                        $cpl_project_overlay_has_custom_bg = true;
                    }
                }
                $cpl_markup .= 
                '<div id="custom-phone-grid" class="grid '.$cpl_empty_class.' id-'.$id.' '.($is_project_overlay === true ? 'project-overlay-grid'.($cpl_project_overlay_has_custom_bg ? ' project-overlay-grid-has-custom-bg' : '') : '').'">';
                // For project overlays, don't include background media here - it will be added outside #lay-project-overlay
                if( $is_project_overlay !== true ) {
                    $cpl_markup .= $cpl_background_media_markup;
                }
                $cpl_markup .= '<div class="grid-inner">
                        '.$cpl_frame_css.'
                        '.$cpl_rows_css.'
                        '.$cpl_css.'
                        '.$cpl_background_color_css.'
                        '.$cpl_background_color_projectoverlay.'
                        '.$cpl_background_image_css.'
                        '.$cpl_freely_placed_els.'
                        '.$cpl_row_col_el_markup.'
                    </div>
                </div>';
                if( $cover_on_phone ) {
                    $cpl_markup .= '</div>';
                }
                $cpl_markup .= 
                '<!-- End CPL Layout -->';
        }

        // Footer Custom Phone Layout
        $footer_cpl_markup = '';
        $footer_cpl_exists_html_class = 'footer-nocustomphonegrid';
        if( LayFrontend_Options::$phone_layout_active && is_array($cpl_footer_arr) ) {
            $footer_cpl_exists_html_class = 'footer-hascustomphonegrid';
            $footer_cpl_css = Lay_LayoutFunctions::getGridCSS($cpl_footer_arr, '#footer-custom-phone-grid', true);
            $footer_cpl_css .= Lay_LayoutFunctions::getHorizontalGridCSS($cpl_footer_arr, '#footer-custom-phone-grid', true);

            $footer_cpl_frame_css = Lay_LayoutFunctions::getGridFrameCSS($cpl_footer_arr, '#footer-custom-phone-grid', '#footer-custom-phone-grid', true);
            $footer_cpl_rows_css = Lay_LayoutFunctions::getRowsMarginBottomCSS($cpl_footer_arr, $cpl_footer_rows, '#footer-custom-phone-grid', true);
            $footer_cpl_background_color_css = Lay_LayoutFunctions::getBackgroundCSS($cpl_footer_arr, '#footer-custom-phone-grid');
            $footer_cpl_background_image_css = Lay_LayoutFunctions::getBackgroundImageCSS($cpl_footer_arr, '#footer-custom-phone-grid');     

            $footer_cpl_markup = 
            '<div id="footer-custom-phone-grid" class="grid">
                '.$footer_cpl_frame_css.'
                '.$footer_cpl_rows_css.'
                '.$footer_cpl_css.'
                '.$footer_cpl_background_color_css.'
                '.$footer_cpl_background_image_css.'
                '.$cpl_footer_markup.'
                '.$cpl_footer_freely_placed_els.'
            </div>';
        }

        // Desktop CSS
        $desktop_css = '';
        $desktop_frame_css = '';
        $desktop_rows_css = '';
        $background_color_css = '';
        $background_color_projectoverlay = '';
        $background_image_css = '';
        
        if( is_array($desktop_arr) ) {
            $desktop_css = Lay_LayoutFunctions::getGridCSS($desktop_arr, '#grid.id-'.$id, false);
            $desktop_css .= Lay_LayoutFunctions::getHorizontalGridCSS($desktop_arr, '#grid.id-'.$id, false);
            $desktop_frame_css = Lay_LayoutFunctions::getGridFrameCSS($desktop_arr, '#grid.id-'.$id, '#grid.id-'.$id, false);
 
            if( $cover_on_desktop ){
                $desktop_frame_css = Lay_LayoutFunctions::getGridFrameCSS($desktop_arr, '.cover-region-desktop .cover-inner', '#grid.id-'.$id, false);
                // need to set background color for .cover-inner too because it is not inside #grid div
                $background_color_css = Lay_LayoutFunctions::getBackgroundCSS($desktop_arr, '.cover-region-desktop.id-'.$id.' .cover-inner');
            }
            $background_color_css .= Lay_LayoutFunctions::getBackgroundCSS($desktop_arr, '#grid.id-'.$id);
            $desktop_rows_css = Lay_LayoutFunctions::getRowsMarginBottomCSS($desktop_arr, $desktop_rows, '#grid.id-'.$id, false);

            if( $is_project_overlay === true ) {
                $bgColor = $desktop_bg_color;
                $desktopOverlayOpacity = $desktop_bg_opacity_value;
                $desktopOverlayBlur = $desktop_bg_blur_value;
                if( $desktop_has_bg_video ) {
                    $bgColor = 'transparent';
                    $desktopOverlayOpacity = 100;
                    $desktopOverlayBlur = 0;
                } elseif( $bgColor == '' || $bgColor == null ) {
                    $bgColor = ProjectsOverlaysOptions::$bg_color;
                }
                // Override opacity and blur if setting is enabled
                if( LayFrontend_Options::$po_override_overlay_bg_opacity_blur ) {
                    $desktopOverlayOpacity = LayFrontend_Options::$po_overlay_bg_opacity;
                    $desktopOverlayBlur = LayFrontend_Options::$po_overlay_bg_blur;
                }
                $style = Lay_Layout::buildBackgroundStyle($bgColor, $desktopOverlayOpacity, $desktopOverlayBlur);
                if( LayFrontend_Options::$phone_layout_active && is_array($cpl_arr) ) {
                    // cpl exists, so use media query:
                    $background_color_projectoverlay = '<style>@media (min-width: '.(LayFrontend_Options::$breakpoint + 1).'px){ #lay-project-overlay{'.$style.'} }</style>';
                } else {
                    $background_color_projectoverlay = '<style>#lay-project-overlay{'.$style.'}</style>';
                }
                if( $desktop_has_custom_bg_effects && !$desktop_has_bg_video ) {
                    $background_color_css = '';
                }
            }

            $background_image_css = Lay_LayoutFunctions::getBackgroundImageCSS($desktop_arr, '#grid.id-'.$id.', .cover-region-desktop.id-'.$id.' .cover-inner');     

            if( array_key_exists('bgVideo', $desktop_arr) && is_array($desktop_arr['bgVideo']) ) {
                $desktop_background_media_markup .= Lay_Layout::buildBackgroundVideoMarkup($desktop_arr['bgVideo'], 'lay-background-video-desktop', $desktop_bg_opacity_value, $desktop_bg_blur_value);
            }

            if( $desktop_has_bg_image ) {
                $desktop_background_media_markup .= Lay_Layout::buildBackgroundImageMarkup($desktop_arr['bgImage'], 'lay-background-image-desktop', $desktop_bg_opacity_value, $desktop_bg_blur_value);
                $background_image_css = '';
            }
        }
        
        // Is there a footer?

        // Footer Desktop CSS
        $desktop_footer_css = '';
        $desktop_frame_footer_css = '';
        $desktop_rows_footer_css = '';
        $background_color_footer_css = '';

        if( is_array($footer_desktop_arr) ){
            $desktop_footer_css = Lay_LayoutFunctions::getGridCSS($footer_desktop_arr, '#footer', false);
            $desktop_footer_css .= Lay_LayoutFunctions::getHorizontalGridCSS($footer_desktop_arr, '#footer', false);
            $desktop_frame_footer_css = Lay_LayoutFunctions::getGridFrameCSS($footer_desktop_arr, '#footer', '#footer', false);
            $desktop_rows_footer_css = Lay_LayoutFunctions::getRowsMarginBottomCSS($footer_desktop_arr, $desktop_footer_rows, '#footer', false);
            $background_color_footer_css = Lay_LayoutFunctions::getBackgroundCSS($footer_desktop_arr, '#footer');    
        }

        // Cover
        // todo: cover disabled for phone
        $cover_desktop_class = 'nocover';
        // todo: && !isFullscreenSliderActive()
        // if( cover_controller.hasCover(type, obj.id) && !isFullscreenSliderActive() ){
        $desktop_cover_rowgutter_css = '';
        $cover_desktop_css = '';
        if( $cover_on_desktop ){
            $cover_desktop_class = 'hascover';
            $cover_desktop_css = Lay_LayoutFunctions::getGridCSS($desktop_arr, '.cover-region', false);
            $cover_desktop_css .= Lay_LayoutFunctions::getHorizontalGridCSS($desktop_arr, '.cover-region', false);
            // set main grid padding-top to first rowgutter
            $desktop_cover_rowgutter_css = Lay_LayoutFunctions::getFirstRowGutterAsPaddingTopForCoverCSS($desktop_arr, '#grid.id-'.$id);
            
            // need .cover-inner so the grid's background color can overwrite the global background color set in customizer

            // ok so when we have a cover on desktop, but cover is disabled for phone, 
            // i dont want the cover row to be 100vh on APL if the user hasn't set it to be 100vh explicitly for phone
            // APL = automatically generated phone layout
            $_100vh_set_by_user_class = $desktop_cover_is_100vh_set_by_user ? '_100vh-set-by-user' : '_100vh-not-set-by-user';
            $desktop_cover_markup = 
            '<div class="cover-region cover-region-desktop _100vh '.$_100vh_set_by_user_class.' id-'.$id.'"">
                <div class="cover-inner _100vh">
                    '.$desktop_cover_markup.'
                    '.$desktop_cover_freely_placed_els.'
                </div>
                '.Lay_LayoutFunctions::getCoverDownArrowMarkup(array('phoneOnly' => false)).'
            </div>';
        }

        // this is not for cpl but just so i can disable cover via css on phone version of the page
        $cover_disabled_on_phone_class = LayFrontend_Options::$cover_disable_for_phone == true ? 'cover-disabled-on-phone' : 'cover-enabled-on-phone';

        if( $getFullLayout == false ) {
            $cover_disabled_on_phone_class = 'cover-disabled-on-phone';
        }

        // $hover_image_markup = '';
        // if( is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) && $getImageHover ) {
        //     switch(LayFrontend_Options::$imagehover_imageposition){
        //         case 'centerfixed':
        //         case 'leftbackground':
        //         case 'rightbackground':
        //         case 'fullsize':
        //             $all_rows = array($desktop_rows, $cpl_rows, $desktop_footer_rows, $cpl_footer_rows, $desktop_cover_row, $cpl_cover_rows);
        //             $array_to_merge_to = array();
        //             LayRowFunctions::mergeAllRows($all_rows, $array_to_merge_to);
        //             $hover_image_markup = '<div class="lay-imagehover-region">'.LayThemeImagehover::getMarkup( $array_to_merge_to, $desktop_markup.$desktop_freely_placed_els.$desktop_footer_markup.$footer_desktop_freely_placed_els ).'</div>';
        //     }
        // }

        // todo: add lay-empty class to cover row too, and cpl cover row and to footer!
        $desktop_has_background_media = $desktop_has_bg_image || $desktop_has_bg_video;
        $desktop_empty_class = Lay_Layout::isEmptyLayout($desktop_rows) && $desktop_freely_placed_els == '' && $background_image_css == '' && !$desktop_has_background_media ? 'lay-empty' : 'lay-not-empty';

        // create a new filter where we can append things to $markup. add $obj
        // to the filter so we can use it in the filter function

        $markupFilter = apply_filters( 'laytheme_filter_content', $obj );

        // PERFORMANCE OPTIMIZATION: Use array buffering instead of string concatenation
        
        // Add desktop background media for mobile when no CPL exists
        if( LayFrontend_Options::$phone_layout_active && !$cpl_exists ) {
            // No CPL exists - add desktop background media with mobile classes for mobile devices
            if( $desktop_has_bg_video && is_array($desktop_arr) && array_key_exists('bgVideo', $desktop_arr) && is_array($desktop_arr['bgVideo']) ) {
                $desktop_background_media_markup .= Lay_Layout::buildBackgroundVideoMarkup($desktop_arr['bgVideo'], 'lay-background-video-mobile', $desktop_bg_opacity_value, $desktop_bg_blur_value);
            }
            if( $desktop_has_bg_image && is_array($desktop_arr) && array_key_exists('bgImage', $desktop_arr) ) {
                $desktop_background_media_markup .= Lay_Layout::buildBackgroundImageMarkup($desktop_arr['bgImage'], 'lay-background-image-mobile', $desktop_bg_opacity_value, $desktop_bg_blur_value);
            }
        }
        
        // Note: CPL background video is already added to $cpl_background_media_markup above (inside the CPL markup section)
        // This section only adds desktop background media for mobile when no CPL exists

        $markup_parts = array();
        $markup_parts[] = '<div class="lay-content '.$cpl_exists_html_class.' '.$footer_cpl_exists_html_class.' '.$cover_desktop_class.' '.$cover_cpl_class.' '.$cover_disabled_on_phone_class.'">';
        $markup_parts[] = '<!-- Start Desktop Layout -->';
        $markup_parts[] = $desktop_cover_rowgutter_css;
        $markup_parts[] = $cover_desktop_css;
        $markup_parts[] = $desktop_cover_markup;
        
        if( $cover_on_desktop == true ) {
            // need cover content wrap, so i can set global bg color and bg image to it
            // the inner div: .grid can now be set to the grid-specific background-color, overwriting .cover-content's background
            // i cannot just set the global background color or background image to an outer div like .lay-content, because then ->
            // when cover would be active and i would scroll down, the transparent div that is on top of the cover would not hide it beneath
            $markup_parts[] = '<div class="cover-content cover-content-desktop">';
        }
        
        // Apply filter and continue building
        $markup_temp = implode('', $markup_parts);
        $markup_temp = apply_filters( 'lay_before_desktop_grid', $markup_temp, $obj );
        $markup_parts = array($markup_temp);
        
        // need .grid-inner wrapper so I can have a fullscreen slider that is horizontal
        $grid_classes = array('grid', $desktop_empty_class, 'id-'.$id);
        if( $get_simpler_markup_for_scrollslider == true ) {
            $grid_classes[] = 'simpler-markup-for-scrollslider';
        }
        if( $is_project_overlay === true ) {
            $grid_classes[] = 'project-overlay-grid';
            // Only add custom bg class if we have actual custom background settings
            // Default values: bgColor empty, opacity 100%, blur 0, no video, no image = NO custom bg
            $desktop_project_overlay_has_custom_bg = false;
            // Check if bgColor is explicitly set (not null/empty)
            if ($desktop_bg_color !== null && $desktop_bg_color !== '') {
                $desktop_project_overlay_has_custom_bg = true;
            }
            // Check if we have non-default opacity (less than 100%)
            if ($desktop_bg_opacity_value < 100) {
                $desktop_project_overlay_has_custom_bg = true;
            }
            // Check if we have non-default blur (greater than 0)
            if ($desktop_bg_blur_value > 0) {
                $desktop_project_overlay_has_custom_bg = true;
            }
            // Check if we have background video
            if ($desktop_has_bg_video) {
                $desktop_project_overlay_has_custom_bg = true;
            }
            // Check if we have background image
            if ($desktop_has_bg_image) {
                $desktop_project_overlay_has_custom_bg = true;
            }
            if ($desktop_project_overlay_has_custom_bg) {
                $grid_classes[] = 'project-overlay-grid-has-custom-bg';
            }
        }
        $markup_parts[] = '<div id="grid" class="'.implode(' ', array_filter($grid_classes)).'">';
        
        $markup_temp = implode('', $markup_parts);
        $markup_temp = apply_filters( 'lay_after_desktop_grid_opening_tag', $markup_temp, $obj );
        $markup_parts = array($markup_temp);
        
        // For project overlays, don't include background media here - it will be added outside #lay-project-overlay
        if( $is_project_overlay !== true ) {
            $markup_parts[] = $desktop_background_media_markup;
        }
        $markup_parts[] = '<div class="grid-inner">';
        $markup_parts[] = $desktop_frame_css;
        $markup_parts[] = $desktop_rows_css;
        $markup_parts[] = $desktop_css;
        $markup_parts[] = $background_color_css;
        $markup_parts[] = $background_color_projectoverlay;
        $markup_parts[] = $background_image_css;
        $markup_parts[] = $desktop_freely_placed_els;
        $markup_parts[] = $desktop_markup;
        $markup_parts[] = '</div>';
        
        $markup_temp = implode('', $markup_parts);
        $markup_temp = apply_filters( 'lay_after_desktop_grid_closing_tag', $markup_temp, $obj );
        $markup_parts = array($markup_temp);
        
        $markup_parts[] = '</div>';
        
        if( $cover_on_desktop == true ) {
            // closing .cover-content
            $markup_parts[] = '</div>';
        }
        
        $markup_parts[] = '<!-- End Desktop Layout -->';
        
        $markup_temp = implode('', $markup_parts);
        $markup_temp = apply_filters( 'lay_after_desktop_grid', $markup_temp, $obj );
        $markup_parts = array($markup_temp);
        
        $markup_parts[] = $cpl_markup;
        
        if( $getFullLayout == true ) {
            $markup_parts[] = '<div id="footer-region">';
            
            if( is_array($footer_desktop_arr) ){
                // need "grid" class here to make expandrow work! 
                /*
                var $row = jQuery(this)
                .closest('.grid')
			    .find('.row-id-' + expandArr[i])
                */
                $markup_parts[] = '<div id="footer" class="footer grid">';
                $markup_parts[] = $desktop_frame_footer_css;
                $markup_parts[] = $desktop_rows_footer_css;
                $markup_parts[] = $desktop_footer_css;
                $markup_parts[] = $background_color_footer_css;
                $markup_parts[] = $footer_desktop_freely_placed_els;
                $markup_parts[] = $desktop_footer_markup;
                $markup_parts[] = '</div>';
            }
            
            $markup_parts[] = $footer_cpl_markup;
            $markup_parts[] = '</div>';
        }
        
        if(is_string($markupFilter)) {
            $markup_parts[] = $markupFilter;
        }
        
        $markup_parts[] = '</div>';

        // Final implode to create the markup string
        $markup = implode('', $markup_parts);

        // apply content hook for plugins:
        // Skip while building raw multilingual markup. We re-apply this on the
        // translated response copy below, so non-cached qTranslate requests can
        // still use Lay's empty-language fallback.
        if( $cached_options['enable_content_filter'] == "on" && !$should_build_raw_multilingual_layout ) {
            $markup = apply_filters( 'the_content', $markup );
        }

        // For project overlays, store background media separately to be inserted outside #lay-project-overlay
        $project_overlay_background_media = '';
        if( $is_project_overlay === true ) {
            $project_overlay_background_media = $desktop_background_media_markup;
            // Also include CPL background media if it exists
            if( $cpl_background_media_markup != '' ) {
                $project_overlay_background_media .= $cpl_background_media_markup;
            }
        }

        // Document title (Yoast SEO) for AJAX navigation so <title> matches full page load — only when Yoast is active
        $document_title = '';
        if ( defined( 'WPSEO_VERSION' ) ) {
            $document_title = Lay_Layout::get_document_title_for_layout( $id, $type, $obj );
        }

        $page_custom_css = '';
        $page_custom_desktop_css = '';
        $page_custom_mobile_css = '';
        if( $type === 'category' ){
            $page_custom_css = get_term_meta( $id, '_lay_page_custom_css', true );
            $page_custom_desktop_css = get_term_meta( $id, '_lay_page_custom_desktop_css', true );
            $page_custom_mobile_css = get_term_meta( $id, '_lay_page_custom_mobile_css', true );
        }else{
            $page_custom_css = get_post_meta( $id, '_lay_page_custom_css', true );
            $page_custom_desktop_css = get_post_meta( $id, '_lay_page_custom_desktop_css', true );
            $page_custom_mobile_css = get_post_meta( $id, '_lay_page_custom_mobile_css', true );
        }
        $page_css_scope = get_option('misc_options_page_css_scope', 'body');

        $page_css_markup = '';
        if ( $is_page_overlay ) {
            $page_css_prefix = '.overlay-id-'.$id;
        } elseif ( $is_project_overlay === true ) {
            // Project overlay content lives inside #lay-project-overlay; grid has class .id-{id}
            $page_css_prefix = '#lay-project-overlay .id-'.$id;
        } else {
            $page_css_prefix = $page_css_scope === 'layout' ? '.id-'.$id : 'body.id-'.$id;
        }
        if( $page_custom_css !== '' ) {
            $scoped_page_css = Frontend::scope_custom_css_with_prefix($page_custom_css, $page_css_prefix);
            $page_css_markup .= '<style id="lay-page-custom-css-'.$id.'">'.$scoped_page_css.'</style>';
        }
        if( $page_custom_desktop_css !== '' ) {
            $scoped_page_desktop_css = Frontend::scope_custom_css_with_prefix($page_custom_desktop_css, $page_css_prefix);
            $page_css_markup .= '<style id="lay-page-custom-css-desktop-'.$id.'">@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){'.$scoped_page_desktop_css.'}</style>';
        }
        if( $page_custom_mobile_css !== '' ) {
            $scoped_page_mobile_css = Frontend::scope_custom_css_with_prefix($page_custom_mobile_css, $page_css_prefix);
            $page_css_markup .= '<style id="lay-page-custom-css-mobile-'.$id.'">@media (max-width: '.MiscOptions::$phone_breakpoint.'px){'.$scoped_page_mobile_css.'}</style>';
        }
        if( $page_css_markup !== '' ) {
            $markup = preg_replace('/(<div class="lay-content[^"]*">)/', '$1'.$page_css_markup, $markup, 1);
        }

        $layout_data = array(
            'markup' => $markup,
            'type' => $type,
            'layoutObj' => $layoutObj,
            'obj' => $obj,
            'notification' => 'success',
            'document_title' => $document_title,
            'page_custom_css' => $page_custom_css,
            'page_custom_desktop_css' => $page_custom_desktop_css,
            'page_custom_mobile_css' => $page_custom_mobile_css,
            'page_settings_styles' => Frontend::get_page_settings_styles_for_data( array( 'id' => $obj['id'], 'type' => $obj['type'] ) )
        );
        
        // Add background media for project overlays
        if( $project_overlay_background_media != '' ) {
            $layout_data['project_overlay_background_media'] = $project_overlay_background_media;
        }

        // Cache the layout data only if cache is enabled.
        // The markup at this point is RAW multilingual when $will_cache_this_build
        // is true (qTranslate's get_post_metadata filter was removed above), so
        // future cache hits can translate per-request.
        if (LayFrontend_Options::$cache_active) {
            Lay_Layout_Cache::set($id, $layout_data);
        }

        // Now that the raw markup is safely cached, translate the *response*
        // copy for the current request. This mirrors what the cache-HIT path
        // does, so the user always sees their current language regardless of
        // whether their request was a hit or a miss. We also restore the
        // qTranslate `get_post_metadata` filter so any downstream code in
        // this request sees translated meta as before.
        if ( $should_build_raw_multilingual_layout ) {
            foreach ( $removed_qtranx_meta_filters as $cb ) {
                add_filter('get_post_metadata', $cb['function'], $cb['priority'], $cb['accepted_args']);
            }
            self::$building_raw_multilingual_layout_cache = false;
            $layout_data['markup'] = Lay_Layout::translate_layout_markup_for_response($layout_data['markup']);
            if ( $cached_options['enable_content_filter'] == 'on' ) {
                $layout_data['markup'] = apply_filters('the_content', $layout_data['markup']);
            }
        }

        // Clear object pool after building layout (for next request)
        Lay_Object_Pool::clearPool();

        return $layout_data;
    }

    /**
     * Get the document title for the given layout (e.g. Yoast SEO title).
     * Temporarily sets the main query so wp_get_document_title() returns the correct title.
     *
     * @param string|int $id   Layout id (post id or term id).
     * @param string     $type Layout type: project, page, product, lay_news, category, archive.
     * @param array      $obj  Layout obj with at least 'title' for fallback.
     * @return string Document title for <title> tag.
     */
    public static function get_document_title_for_layout( $id, $type, $obj ) {
        $fallback = isset( $obj['title'] ) ? $obj['title'] : '';
        if ( ! function_exists( 'wp_get_document_title' ) ) {
            return Setup::apply_qtranslate_to_document_title( $fallback );
        }
        global $wp_query, $post;
        $backup_query = $wp_query;
        $backup_post = isset( $post ) ? $post : null;
        try {
            if ( $type === 'project' || $type === 'page' || $type === 'product' || $type === 'lay_news' || $type === 'news' || $type === 'lay_deck' || $type === 'deck' ) {
                $post = get_post( $id );
                if ( ! $post || ! $post->post_type ) {
                    return Setup::apply_qtranslate_to_document_title( $fallback );
                }
                // Prefer Yoast's API so we get the exact title Yoast would output (e.g. "Kunsthalle" not "Site - Kunsthalle")
                if ( function_exists( 'YoastSEO' ) && is_object( YoastSEO() ) ) {
                    $meta = YoastSEO()->meta;
                    if ( $meta && method_exists( $meta, 'for_post' ) ) {
                        try {
                            $surface = $meta->for_post( (int) $id );
                            if ( $surface && ! empty( $surface->title ) ) {
                                return Setup::apply_qtranslate_to_document_title( $surface->title );
                            }
                        } catch ( \Exception $e ) {
                            // Fall through to wp_get_document_title()
                        }
                    }
                }
                $wp_query = new \WP_Query( array(
                    'p'         => (int) $id,
                    'post_type' => $post->post_type,
                    'post_status' => array( 'publish', 'private' ),
                ) );
                if ( ! empty( $wp_query->posts ) ) {
                    $post = $wp_query->posts[0];
                }
            } elseif ( $type === 'category' ) {
                $term = get_term( $id );
                if ( ! $term || is_wp_error( $term ) ) {
                    return Setup::apply_qtranslate_to_document_title( $fallback );
                }
                $wp_query = new \WP_Query( array( 'cat' => (int) $id ) );
            } else {
                return Setup::apply_qtranslate_to_document_title( $fallback );
            }
            $title = wp_get_document_title();
            return $title !== '' ? $title : Setup::apply_qtranslate_to_document_title( $fallback );
        } finally {
            $wp_query = $backup_query;
            if ( $backup_post !== null ) {
                $post = $backup_post;
            } else {
                unset( $post );
            }
        }
    }

    // a layout is empty if there are no elements inside of it
    // todo: layout should not be empty if a row is empty but has a background image or background video
    public static function isEmptyLayout($layout_array){
        if( is_array($layout_array) && count($layout_array) > 0 ){
            $isempty = true;
            for ($i=0; $i < count($layout_array); $i++) { 
                if( array_key_exists('columns', $layout_array[$i]) ) {
                    if( !empty($layout_array[$i]['columns']) ) {
                        $isempty = false;
                        break;
                    }
                }
                if( array_key_exists('webglslideshow', $layout_array[$i]) ) {
                    if( !empty($layout_array[$i]['webglslideshow']) ) {
                        $isempty = false;
                        break;
                    }
                }
                if( array_key_exists('bgimage', $layout_array[$i]) ) {
                    if( !empty($layout_array[$i]['bgimage']) ) {
                        $isempty = false;
                        break;
                    }
                }
                if( array_key_exists('bgvideo', $layout_array[$i]) ) {
                    if( !empty($layout_array[$i]['bgvideo']) ) {
                        $isempty = false;
                        break;
                    }
                }
            }

            return $isempty;
        }
        return true;
    }

    public static function getFreelyPlacedCols($grid_arr, $postid, $device, $specifier = 'all', $options = array(), $getImageHover = true) {
        // $specifier can be "all" or "in-cover" or "outside-cover"
        // this is for splitting up markup into cover and markup below
        $freelyPlacedEls = array();
        $cols = $grid_arr['cont'];
        $markup = '';
        for( $i=0; $i<count($cols); $i++ ){
            $col = $cols[$i];
            if ( array_key_exists('placeFreely', $col) ){
                if( $specifier == 'in-cover' ) {
                    if($col['row'] > 0) {
                        continue;
                    }
                } else if( $specifier == 'outside-cover' ) {
                    if($col['row'] == 0) {
                        continue;
                    }
                }
                $el_options = array_merge($options, array('device' => $device));
                $layCol = Lay_Object_Pool::getCol($col, false, $options);
                $layEl = Lay_Object_Pool::getEl($col, false, $postid, $device);
                $elMarkup = $layEl->getMarkup($el_options);
                if( $elMarkup != '' ) {
                    // PERFORMANCE: Use array buffering for freely placed elements
                    $markup_parts = array();
                    $markup_parts[] = $layCol->getOpeningMarkup();
                    $markup_parts[] = $elMarkup;
                    $markup_parts[] = $layCol->getClosingMarkup();

                    // Mirror getRowColElMarkup(): emit `lay-imagehover-on-element`
                    // siblings for any imagehover links inside this element. Without
                    // this, place-freely elements with imagehover links never show
                    // a hover image because there's no image markup to reveal.
                    if( $getImageHover && is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) && class_exists('LayThemeImagehover') && method_exists('LayThemeImagehover', 'getImageFromMarkup2') ) {
                        $imagesArray2 = LayThemeImagehover::getImageFromMarkup2( $elMarkup );
                        if( is_array($imagesArray2) ) {
                            foreach( $imagesArray2 as $image ) {
                                $isScroll = false;
                                if( $image['showon'] == 'onscrollonly' || $image['showon'] == 'onscrollandhover' || ($image['showon'] == 'onscrollfortouchdevices_onhoverfordesktop' && class_exists('LTUtility') && LTUtility::$isTouchDevice) ) {
                                    $isScroll = true;
                                }

                                if( property_exists('LayThemeImagehover', 'clickable') && LayThemeImagehover::$clickable == "on" && $isScroll && array_key_exists('href', $image) && $image['href'] != '' ) {
                                    $linkAttrs = '';
                                    if( array_key_exists('href', $image) )      { $linkAttrs .= ' href="'.$image['href'].'"'; }
                                    if( array_key_exists('dataId', $image) )    { $linkAttrs .= ' data-id="'.$image['dataId'].'"'; }
                                    if( array_key_exists('dataType', $image) )  { $linkAttrs .= ' data-type="'.$image['dataType'].'"'; }
                                    if( array_key_exists('dataTitle', $image) ) { $linkAttrs .= ' data-title="'.$image['dataTitle'].'"'; }
                                    if( array_key_exists('dataCatid', $image) ) { $linkAttrs .= ' data-catid="'.$image['dataCatid'].'"'; }

                                    $markup_parts[] = '<a class="lay-imagehover-on-element" '.$linkAttrs.' data-transition="'.LayFrontend_Options::$imagehover_transition.'" data-imageposition='.$image['position'].' data-hoverimageposition='.$image['position'].' data-hoverimageshowon="'.$image['showon'].'" data-hoverimagepositionforscroll='.$image['positionforscroll'].' data-hoverimagepreactivated="'.$image['preactivated'].'">'.$image['media'].'</a>';
                                } else {
                                    $markup_parts[] = '<div class="lay-imagehover-on-element" data-transition="'.LayFrontend_Options::$imagehover_transition.'" data-imageposition='.$image['position'].' data-hoverimageposition='.$image['position'].' data-hoverimageshowon="'.$image['showon'].'" data-hoverimagepositionforscroll='.$image['positionforscroll'].' data-hoverimagepreactivated="'.$image['preactivated'].'">'.$image['media'].'</div>';
                                }
                            }
                        }
                    }

                    $markup .= implode('', $markup_parts);
                }
            }
        }
        return $markup;
    }

    public static function prepareRows($grid_arr, $cover_active) {
        if( $grid_arr == null ) {
            return false;
        }

        $cols = $grid_arr['cont'];
        $rowAttrs = array_key_exists( 'rowAttrs', $grid_arr ) ? $grid_arr['rowAttrs'] : false;
        $rowAmt = count($grid_arr['rowGutters']) + 1;
        $rows = array();
        $cover = null;


        // create row objects in an array, possibly with row attributes
        for( $i = 0; $i < $rowAmt; $i++ ){
            $rows []= array();
            if( is_array($rowAttrs) ) {
                if( array_key_exists($i, $rowAttrs) ) {
                    $rows[$i] = $rowAttrs[$i];
                }
            }
            // add row gutter
            if( array_key_exists($i, $grid_arr['rowGutters']) ){
                $rows[$i]['rowGutter'] = $grid_arr['rowGutters'][$i];
            }
            $rows[$i]['columns'] = array();
        }

        if( $cover_active == true ) {
            $rows[0]['row100vh'] = 1;
            $rows[0]['rowcustomheight'] = '';
        }

        // loop through all columns and put them into their according row's .columns
        if( $cols ) {

            $reorder_cols = false;

            for( $i=0; $i<count($cols); $i++ ){
                if ( is_array($cols[$i]) && !array_key_exists('placeFreely', $cols[$i]) ){
                    $rowIx = $cols[$i]['row'];
                    $type = $cols[$i]['type'];
                    if( is_array($rows[$rowIx]) && array_key_exists('row100vh', $rows[$rowIx]) && $rows[$rowIx]['row100vh'] == 1 ||
                    is_array($rows[$rowIx]) && array_key_exists('rowcustomheight', $rows[$rowIx]) && $rows[$rowIx]['rowcustomheight'] != '' || 
                    $type == 'vl' ) {
                        // Remove row100vh and rowcustomheight from rows that contain thumbnailgrid or productsgrid
                        $applyAbsolutePosition = true;
                        if( $type == 'thumbnailgrid' || $type == 'productsgrid' ) {
                            if( array_key_exists('config', $cols[$i]) && is_array($cols[$i]['config']) ) {
                                $config = $cols[$i]['config'];
                                $showCatFilter = array_key_exists('showfilter', $config) && ( $config['showfilter'] === true || $config['showfilter'] === 'true' );
                                $showTagFilter = array_key_exists('showtagfilter', $config) && ( $config['showtagfilter'] === true || $config['showtagfilter'] === 'true' );
                                if( $showCatFilter || $showTagFilter ) {
                                    $applyAbsolutePosition = false;
                                }
                            }
                         }
                         if( $applyAbsolutePosition ) {
                            if( array_key_exists('classes', $cols[$i]) ) {
                                $cols[$i]['classes'] .= ' absolute-position';
                                $reorder_cols = true;
                            } else {
                                $cols[$i]['classes'] = 'absolute-position';
                                $reorder_cols = true;
                            }
                         }
                    }
                    if( !is_array($rows[$rowIx]) ) {
                        $rows[$rowIx] = array();
                    }
                    $rows[$rowIx]['columns'] []= $cols[$i];
                }
            }
            
            // Remove row100vh and rowcustomheight from rows that contain thumbnailgrid or productsgrid
            for( $i = 0; $i < count($rows); $i++ ) {
                if( is_array($rows[$i]) && array_key_exists('columns', $rows[$i]) && is_array($rows[$i]['columns']) ) {
                    $hasThumbnailgridOrProductsgrid = false;
                    foreach( $rows[$i]['columns'] as $col ) {
                        if( array_key_exists('type', $col) && ( $col['type'] == 'thumbnailgrid' || $col['type'] == 'productsgrid' ) ) {
                            $config = array_key_exists('config', $col) && is_array($col['config']) ? $col['config'] : array();
                            $showCatFilter = array_key_exists('showfilter', $config) && ( $config['showfilter'] === true || $config['showfilter'] === 'true' );
                            $showTagFilter = array_key_exists('showtagfilter', $config) && ( $config['showtagfilter'] === true || $config['showtagfilter'] === 'true' );
                            if( $showCatFilter || $showTagFilter ) {
                            $hasThumbnailgridOrProductsgrid = true;
                            break;
                            }
                        }
                    }
                    if( $hasThumbnailgridOrProductsgrid ) {
                        // Remove row100vh and rowcustomheight attributes
                        if( array_key_exists('row100vh', $rows[$i]) ) {
                            unset($rows[$i]['row100vh']);
                        }
                        if( array_key_exists('rowcustomheight', $rows[$i]) ) {
                            unset($rows[$i]['rowcustomheight']);
                        }
                    }
                }
            }

            // reorder cols
            // the columns that are absolute positioned should always come after cols that are not
            // this way, the "first-child" class will be used correctly so the frame-left value is correct
            // this is for the rare case when an absolutely positioned element has col-0 for example and an image too

            if( $reorder_cols == true ) {
                for( $i=0; $i<count($rows); $i++ ) {
                    $cols = $rows[$i]['columns'];

                    $cols_new = array();
                    $cols_start = array();
                    $cols_end = array();
                    for( $x=0; $x<count($cols); $x++ ) {
                        if( array_key_exists('classes', $cols[$x]) && strpos($cols[$x]['classes'], 'absolute-position') !== false ){
                            $cols_end []= $cols[$x];
                        } else {
                            $cols_start []= $cols[$x];
                        }
                    }
                    $cols_new = array_merge( $cols_start, $cols_end );

                    $rows[$i]['columns'] = $cols_new;
                }
            }
            // #reorder cols

            // set classes
            for( $i=0; $i<count($rows); $i++ ) {
                $cols = $rows[$i]['columns'];
                for( $x=0; $x<count($cols); $x++ ) {
                    if( $x == 0 ) {
                        if( array_key_exists('classes', $cols[$x]) ) {
                            $rows[$i]['columns'][$x]['classes'] .= ' first-child';
                        } else {
                            $rows[$i]['columns'][$x]['classes'] = 'first-child';
                        }
                    } else {
                        if( array_key_exists('classes', $cols[$x]) ) {
                            $rows[$i]['columns'][$x]['classes'] .= ' not-first-child';
                        } else {
                            $rows[$i]['columns'][$x]['classes'] = 'not-first-child';
                        }
                    }

                }
            }

            // add horizontal grid info
            if( array_key_exists('horizontalGrid', $grid_arr) && $grid_arr['horizontalGrid']['show'] == true ) {
                for( $i=0; $i<count($rows); $i++ ) {
                    $cols = $rows[$i]['columns'];
                    for( $x=0; $x<count($cols); $x++ ) {
                        $rows[$i]['columns'][$x]['horizontalGrid'] = $grid_arr['horizontalGrid'];
                    }
                }
            }


        }

        return $rows;
    }

    public static function getRowColElMarkup($rows, $postid, $device, $getImageHover, $getOpeningAndClosingRowMarkup = true, $options = array()) {

        $video_count = 0;
        for( $i = 0; $i < count($rows); $i++ ){
            $row = $rows[$i];
            for( $i2=0; $i2<count($row['columns']); $i2++ ){
                $col = $row['columns'][$i2];
                if( $col['type'] == 'video' ){
                    $video_count++;
                }
            }
        }

        $markup = '';
        for( $i = 0; $i < count($rows); $i++ ){

            $row = $rows[$i];
            $row['custom_order_in_apl'] = false;
            if( array_key_exists('columns', $row) && is_array($row['columns']) ) {
                foreach( $row['columns'] as $col ) {
                    if( !empty($col['aplOrderEnable']) ) {
                        $row['custom_order_in_apl'] = true;
                        break;
                    }
                }
            }
            if( $i == 0 ){
                if( array_key_exists('classes', $row) ){
                    $row['classes'] .= ' first-row';
                }else{
                    $row['classes'] = 'first-row';
                }
                
            }
            if($getOpeningAndClosingRowMarkup){
                $layRow = Lay_Object_Pool::getRow($row, $i);
                $layRowOpeningMarkup = $layRow->getOpeningMarkup();
                $markup .= $layRowOpeningMarkup;
            }

            // add imagehover markup for row background imagehover images
            if( is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) && class_exists('LayThemeImagehover') && $getImageHover ) {
                if($getOpeningAndClosingRowMarkup){
                    $imagesArrayForRows = LayThemeImagehover::getImageFromMarkup2( $layRowOpeningMarkup );
                    
                    if(is_array($imagesArrayForRows)){
                        $row_imagehover_parts = array();
                        foreach ($imagesArrayForRows as $image) {
                            $row_imagehover_parts[] = '<div class="lay-imagehover-on-element" data-transition="'.LayFrontend_Options::$imagehover_transition.'" data-imageposition='.$image['position'].' data-hoverimageposition='.$image['position'].' data-hoverimageshowon="'.$image['showon'].'" data-hoverimagepositionforscroll='.$image['positionforscroll'].' data-hoverimagepreactivated="'.$image['preactivated'].'">'.$image['media'].'</div>';
                        }
                        $markup .= implode('', $row_imagehover_parts);
                    }
                }
            }

            // when row is 100vh, the first column inside that is not a text and not a html element needs class lay-col-needs-leftframe-margin
            $isRowWhereElsArePositionAbsolute = false;
            if( array_key_exists('row100vh', $row) && $row['row100vh'] == true || array_key_exists('rowcustomheight', $row) && $row['rowcustomheight'] == true ){
                $isRowWhereElsArePositionAbsolute = true;
            }
            if( $isRowWhereElsArePositionAbsolute == true ) {
                for( $i3=0; $i3<count($row['columns']); $i3++ ) {
                    $col = $row['columns'][$i3];
                    if( $col['type'] != 'text' && $col['type'] != 'html' ) {
                        if( array_key_exists('classes', $col) ){
                            $row['columns'][$i3]['classes'] .= ' lay-col-needs-leftframe-margin';
                        } else {
                            $row['columns'][$i3]['classes'] = 'lay-col-needs-leftframe-margin';
                        }
                        break;
                    }
                }
            }

            // Add class to cols inside collapsed rows for performance (avoid closest() in JS)
            $rowIsCollapsed = array_key_exists('collapse', $row) && $row['collapse'] == true;
            if( $rowIsCollapsed ) {
                for( $i3=0; $i3<count($row['columns']); $i3++ ) {
                    if( array_key_exists('classes', $row['columns'][$i3]) ){
                        $row['columns'][$i3]['classes'] .= ' inside-collapsed-row';
                    } else {
                        $row['columns'][$i3]['classes'] = 'inside-collapsed-row';
                    }
                }
            }

            // PERFORMANCE: Use array buffering for column/element markup
            $col_markup_parts = array();
            
            $el_options = array_merge($options, array('device' => $device));
            $same_height_includes = array(
                'text' => array_key_exists('sameHeightIncludeText', $row) ? $row['sameHeightIncludeText'] === true : true,
                'image' => array_key_exists('sameHeightIncludeImages', $row) ? $row['sameHeightIncludeImages'] === true : false,
                'video' => array_key_exists('sameHeightIncludeVideos', $row) ? $row['sameHeightIncludeVideos'] === true : false,
                'stack' => array_key_exists('sameHeightIncludeStacks', $row) ? $row['sameHeightIncludeStacks'] === true : false,
            );
            $same_height_participant_count = 0;
            if( array_key_exists('sameHeightTextElements', $row) && $row['sameHeightTextElements'] === true && array_key_exists('columns', $row) && is_array($row['columns']) ) {
                foreach( $row['columns'] as $same_height_col ) {
                    $same_height_col_type = array_key_exists('type', $same_height_col) ? $same_height_col['type'] : '';
                    if( !empty($same_height_col['placeFreely']) || $same_height_col_type === 'vl' ) {
                        continue;
                    }
                    $same_height_matches =
                        ( $same_height_includes['text'] && $same_height_col_type === 'text' ) ||
                        ( $same_height_includes['image'] && $same_height_col_type === 'img' ) ||
                        ( $same_height_includes['video'] && ( $same_height_col_type === 'html5video' || $same_height_col_type === 'video' ) ) ||
                        ( $same_height_includes['stack'] && $same_height_col_type === 'stack' );
                    if( $same_height_matches ) {
                        $same_height_participant_count++;
                    }
                }
            }
            for( $i2=0; $i2<count($row['columns']); $i2++ ){
                $col = $row['columns'][$i2];
                if( $same_height_participant_count > 1 && $same_height_includes['image'] && array_key_exists('type', $col) && $col['type'] === 'img' && empty($col['placeFreely']) ) {
                    $col['_lay_same_height_image_participant'] = true;
                }
                // Pass the row's inner-space values down to VL cols so they can anchor
                // their endpoints to the inner-space boundary when stickTo flags are on.
                if( array_key_exists('type', $col) && $col['type'] === 'vl' ) {
                    $col['_lay_row_padding_top'] = array_key_exists('rowPaddingTop', $row) ? $row['rowPaddingTop'] : 0;
                    $col['_lay_row_padding_top_mu'] = array_key_exists('rowPaddingTopMu', $row) ? $row['rowPaddingTopMu'] : 'px';
                    $col['_lay_row_padding_bottom'] = array_key_exists('rowPaddingBottom', $row) ? $row['rowPaddingBottom'] : 0;
                    $col['_lay_row_padding_bottom_mu'] = array_key_exists('rowPaddingBottomMu', $row) ? $row['rowPaddingBottomMu'] : 'px';
                }
                $layCol = Lay_Object_Pool::getCol($col, $isRowWhereElsArePositionAbsolute, $options);
                $layEl = Lay_Object_Pool::getEl($col, false, $postid, $device, $video_count);
                
                $elMarkup = $layEl->getMarkup($el_options);
                if( $elMarkup != '' ) {
                    $col_markup_parts[] = $layCol->getOpeningMarkup();
                    $col_markup_parts[] = $elMarkup;
                    $col_markup_parts[] = $layCol->getClosingMarkup();
                    // add imagehover markup
                    if( is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) && $getImageHover && method_exists('LayThemeImagehover', 'getImageFromMarkup2') ) {
                        // todo: does it work for stack element?
                        $imagesArray2 = LayThemeImagehover::getImageFromMarkup2( $elMarkup );
                        if(is_array($imagesArray2)){
                            foreach ($imagesArray2 as $image) {

                                $isScroll = false;
                                if($image['showon'] == 'onscrollonly' || $image['showon'] == 'onscrollandhover' || $image['showon'] == 'onscrollfortouchdevices_onhoverfordesktop' && class_exists('LTUtility') && LTUtility::$isTouchDevice) {
                                    $isScroll = true;
                                }

                                // clicking on imagehover image so its a link should only work if it is an image that is shown on scroll
                                // otherwise it would just flicker around because the linked hoverimage is not pointer events "none"

                                if( property_exists('LayThemeImagehover', 'clickable') && LayThemeImagehover::$clickable == "on" && $isScroll && array_key_exists('href', $image) && $image['href'] != '') {
                                    // 'href' => $href, 'dataId' => $dataId, 'dataType' => $dataType, 'dataTitle' => $dataTitle, 'dataCatid' => $dataCatid, 
                                    $linkAttrs = '';
                                    if( array_key_exists('href', $image) ) {
                                        $linkAttrs .= ' href="'.$image['href'].'"';
                                    }
                                    if( array_key_exists('dataId', $image) ) {
                                        $linkAttrs .= ' data-id="'.$image['dataId'].'"';
                                    }
                                    if( array_key_exists('dataType', $image) ) {
                                        $linkAttrs .= ' data-type="'.$image['dataType'].'"';
                                    }
                                    if( array_key_exists('dataTitle', $image) ) {
                                        $linkAttrs .= ' data-title="'.$image['dataTitle'].'"';
                                    }
                                    if( array_key_exists('dataCatid', $image) ) {
                                        $linkAttrs .= ' data-catid="'.$image['dataCatid'].'"';
                                    }

                                    $col_markup_parts[] = '<a class="lay-imagehover-on-element" '.$linkAttrs.' data-transition="'.LayFrontend_Options::$imagehover_transition.'" data-imageposition='.$image['position'].' data-hoverimageposition='.$image['position'].' data-hoverimageshowon="'.$image['showon'].'" data-hoverimagepositionforscroll='.$image['positionforscroll'].' data-hoverimagepreactivated="'.$image['preactivated'].'">'.$image['media'].'</a>';
                                }else{
                                    $col_markup_parts[] = '<div class="lay-imagehover-on-element" data-transition="'.LayFrontend_Options::$imagehover_transition.'" data-imageposition='.$image['position'].' data-hoverimageposition='.$image['position'].' data-hoverimageshowon="'.$image['showon'].'" data-hoverimagepositionforscroll='.$image['positionforscroll'].' data-hoverimagepreactivated="'.$image['preactivated'].'">'.$image['media'].'</div>';
                                }
                            }
                        }

                    }
                }
            }
            
            // Append all column markup at once
            $markup .= implode('', $col_markup_parts);
            
            if($getOpeningAndClosingRowMarkup){
                $markup .= $layRow->getClosingMarkup();
            }
        }
        return $markup;
    }

    private static function hasBackgroundVideo($layout_arr) {
        if (!is_array($layout_arr) || !array_key_exists('bgVideo', $layout_arr) || !is_array($layout_arr['bgVideo'])) {
            return false;
        }
        $bgVideo = $layout_arr['bgVideo'];
        $useExternal = array_key_exists('useExternalVideo', $bgVideo) && ($bgVideo['useExternalVideo'] === true || $bgVideo['useExternalVideo'] === 'true' || $bgVideo['useExternalVideo'] === 1 || $bgVideo['useExternalVideo'] === '1');
        if ($useExternal) {
            if (!array_key_exists('externalVideoUrl', $bgVideo)) {
                return false;
            }
            $url = is_string($bgVideo['externalVideoUrl']) ? trim($bgVideo['externalVideoUrl']) : '';
            return $url !== '';
        }
        if (!array_key_exists('mp4', $bgVideo)) {
            return false;
        }
        $mp4 = is_string($bgVideo['mp4']) ? trim($bgVideo['mp4']) : '';
        return $mp4 !== '';
    }

    private static function buildBackgroundStyle($bgColor, $bgOpacity = 100, $bgBlur = 0) {
        $style = '';
        if ($bgColor == '' || $bgColor == null) {
            $bgColor = 'transparent';
        }

        $opacity = is_numeric($bgOpacity) ? (float)$bgOpacity : 100;
        $opacity = max(0, min(100, $opacity));
        $blur = is_numeric($bgBlur) ? (float)$bgBlur : 0;

        if ($opacity < 100 && $bgColor != 'transparent') {
            if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $bgColor)) {
                $rgb = CSS_Output::hex2rgb($bgColor);
                $alpha = $opacity / 100;
                $style .= 'background-color:rgba('.$rgb['red'].','.$rgb['green'].','.$rgb['blue'].','.$alpha.');';
            } else {
                $style .= 'background-color:'.$bgColor.';';
                $style .= 'opacity:'.($opacity / 100).';';
            }
        } else {
            $style .= 'background-color:'.$bgColor.';';
        }

        if ($blur > 0) {
            $style .= 'backdrop-filter:blur('.$blur.'px);-webkit-backdrop-filter:blur('.$blur.'px);';
        }

        return $style;
    }

    private static function buildBackgroundVideoMarkup($bgVideo, $additionalClass = '', $bgOpacity = 100, $bgBlur = 0) {
        if (!is_array($bgVideo)) {
            return '';
        }

        $useExternal = false;
        if (array_key_exists('useExternalVideo', $bgVideo)) {
            $useExternalValue = $bgVideo['useExternalVideo'];
            $useExternal = $useExternalValue === true || $useExternalValue === 'true' || $useExternalValue === 1 || $useExternalValue === '1';
        }

        $source_url = '';
        if ($useExternal && array_key_exists('externalVideoUrl', $bgVideo)) {
            $source_url = esc_url($bgVideo['externalVideoUrl']);
        } elseif (array_key_exists('mp4', $bgVideo) && $bgVideo['mp4'] != '') {
            $source_url = esc_url(LTUtility::getFullURL($bgVideo['mp4']));
        }

        if ($source_url == '') {
            return '';
        }

        $poster_attr = '';
        if (array_key_exists('image', $bgVideo) && is_array($bgVideo['image']) && array_key_exists('full', $bgVideo['image']) && $bgVideo['image']['full'] != '') {
            $poster_value = $bgVideo['image']['full'];
            if (is_array($poster_value) && array_key_exists('url', $poster_value)) {
                $poster_value = $poster_value['url'];
            }
            $poster_attr = ' poster="'.esc_url(LTUtility::getFullURL($poster_value)).'"';
        }

        $video_type = class_exists('LTUtility') ? LTUtility::getVideoMimeTypeFromUrl($source_url) : 'video/mp4';
        $classNames = trim('lay-background-video '.$additionalClass);
        $styleAttr = self::buildBackgroundMediaStyleAttribute($bgOpacity, $bgBlur);
        $video  = '<video class="'.esc_attr($classNames).'" playsinline muted autoplay loop preload="auto"'.$poster_attr.$styleAttr.' aria-hidden="true">';
        $video .= '<source src="'.$source_url.'" type="'.esc_attr($video_type).'" />';
        $video .= '</video>';

        return $video;
    }

    private static function buildBackgroundImageMarkup($bgImage, $additionalClass = '', $bgOpacity = 100, $bgBlur = 0) {
        if ($bgImage === null) {
            return '';
        }

        // Check if bgImage has an attid (new format) and use getLazyImgByImageId
        if (is_array($bgImage) && array_key_exists('attid', $bgImage) && !empty($bgImage['attid'])) {
            $classNames = trim('lay-background-image '.$additionalClass);
            $styleAttr = self::buildBackgroundMediaStyleAttribute($bgOpacity, $bgBlur);
            $imgTag = LayElFunctions::getLazyImgByImageId($bgImage['attid'], '', $classNames);
            
            // Prepare attributes to add
            $attrsToAdd = '';
            if (strpos($imgTag, 'aria-hidden') === false) {
                $attrsToAdd .= ' aria-hidden="true"';
            }
            if ($styleAttr !== '') {
                $attrsToAdd .= $styleAttr;
            }
            
            // Add attributes before the closing /> or >
            if ($attrsToAdd !== '') {
                if (substr(trim($imgTag), -2) === '/>') {
                    $imgTag = preg_replace('/\s*\/>$/', $attrsToAdd.' />', $imgTag);
                } else {
                    $imgTag = preg_replace('/>$/u', $attrsToAdd.'>', $imgTag);
                }
            }
            
            return $imgTag;
        }

        // Fallback to old format (string URL or array with url/full)
        $source_url = '';
        if (is_string($bgImage)) {
            $source_url = trim($bgImage);
        } elseif (is_array($bgImage)) {
            if (array_key_exists('url', $bgImage)) {
                $source_url = trim($bgImage['url']);
            } elseif (array_key_exists('full', $bgImage) && is_array($bgImage['full']) && array_key_exists('url', $bgImage['full'])) {
                $source_url = trim($bgImage['full']['url']);
            }
        }

        if ($source_url === '') {
            return '';
        }

        $source_url = LTUtility::getFullURL($source_url);
        $classNames = trim('lay-background-image '.$additionalClass);
        $styleAttr = self::buildBackgroundMediaStyleAttribute($bgOpacity, $bgBlur);

        return '<img class="'.esc_attr($classNames).'" src="'.esc_url($source_url).'" alt="" loading="lazy" decoding="async" aria-hidden="true"'.$styleAttr.' />';
    }

    private static function buildBackgroundMediaStyleAttribute($bgOpacity, $bgBlur) {
        $styles = array();
        $opacity = self::normalizeBackgroundOpacityValue($bgOpacity);
        if ($opacity < 1) {
            $styles[] = 'opacity:'.$opacity.';';
        }
        if ($bgBlur > 0) {
            $styles[] = 'filter:blur('.$bgBlur.'px);-webkit-filter:blur('.$bgBlur.'px);';
        }
        if (empty($styles)) {
            return '';
        }
        return ' style="'.implode('', $styles).'"';
    }

    private static function normalizeBackgroundOpacityValue($bgOpacity) {
        $opacity = is_numeric($bgOpacity) ? (float)$bgOpacity : 100;
        $opacity = max(0, min(100, $opacity));
        return $opacity / 100;
    }

    private static function hasBackgroundImage($layout_arr) {
        if (!is_array($layout_arr) || !array_key_exists('bgImage', $layout_arr)) {
            return false;
        }
        $bgImage = $layout_arr['bgImage'];
        if ($bgImage === null) {
            return false;
        }
        if (is_string($bgImage)) {
            return trim($bgImage) !== '';
        }
        if (is_array($bgImage)) {
            if (array_key_exists('url', $bgImage) && trim((string)$bgImage['url']) !== '') {
                return true;
            }
            if (array_key_exists('full', $bgImage) && is_array($bgImage['full']) && array_key_exists('url', $bgImage['full']) && trim((string)$bgImage['full']['url']) !== '') {
                return true;
            }
        }
        return false;
    }

}

new Lay_Layout();
