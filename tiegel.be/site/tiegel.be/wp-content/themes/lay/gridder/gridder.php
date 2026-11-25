<?php

class Gridder{

	public static $tinymceSettings;
	public static $maxFontsize;
	public static $rowgutter_mu;
	public static $topframe_mu;
	public static $frame_mu;
	public static $bottomframe_mu;
	public static $phone_rowgutter_mu;
	public static $active_for_news;
	public static $undoRedoEnabled;

	public function __construct(){
		Gridder::$rowgutter_mu = get_option( 'gridder_defaults_rowgutter_mu', LayConstants::gridder_defaults_rowgutter_mu );
		Gridder::$topframe_mu = get_option( 'gridder_defaults_topframe_mu', LayConstants::gridder_defaults_topframe_mu );
		Gridder::$frame_mu = get_option( 'gridder_defaults_frame_mu', LayConstants::gridder_defaults_frame_mu );
		Gridder::$bottomframe_mu = get_option( 'gridder_defaults_bottomframe_mu', LayConstants::gridder_defaults_bottomframe_mu );
		Gridder::$phone_rowgutter_mu = get_option( 'phone_gridder_defaults_rowgutter_mu', LayConstants::phone_gridder_defaults_rowgutter_mu );
		Gridder::$active_for_news = get_option('misc_options_activate_news_feature', '') == 'on' && !MiscOptions::$locked ? true : false;
		Gridder::$undoRedoEnabled = get_option('misc_options_enable_gridder_undo', 'on') === 'on';

		// todo:
		// https://jonathannicol.com/blog/2015/02/19/clean-pasted-text-in-wordpress/
		
		// https://wordpress.stackexchange.com/questions/334832/how-to-disable-tinymces-paste-plugin-from-turning-image-urls-into-embedded-img
		Gridder::$tinymceSettings = array(
		    'media_buttons' => false,
		    'quicktags' => false,
				// 'smart_paste' => false,
				// 'paste_as_text' => true,
				// pass tinymce options here:
		    'tinymce' => array(
		        'toolbar1' => 'undo, redo, laylink, layunlink, laybutton1, laybutton2, laybutton3, layprevproject, laynextproject, layscrolltotop, layexpandrow, fontselect, fontsizeselect, lineheightselect, letterspacingselect, table, laynightmode',
		        'toolbar2' => 'styleselect, forecolor, bold, italic, underline, strikethrough, alignleft, aligncenter, alignright, charmap, nonbreaking, softhyphen, visualblocks, code, bullist, numlist, indent, outdent, ltr, rtl',
		        'toolbar3' => 'removeformat, style-p style-h1 style-h2 style-h3 style-h4 style-h5',
		        'toolbar4' => '',
						// https://www.tiny.cloud/docs-4x/configure/content-formatting/#removingaformat
						// https://www.tiny.cloud/docs-3x/reference/Configuration3x/Configuration3x@formats/
						
						// 'formats' => array(
						//     'removeformat' => json_encode(array('selector' => '*', 'remove' => 'all', 'attributes' => array('style', 'class'), 'split' => true, 'expand' => true, 'block_expand' => true, 'deep' => true, 'block' => 'p'))
						// )
					// removeformat is the clear format button. using this code i make sure all headlines are transformed into <p> tags and all style attributes are removed
					// i got this code by logging $in of tiny_mce_before_init filter
					'formats' => json_encode(array(
						'removeformat' => array(array(
							'selector' => '*',
							'attributes' => array('style', 'class'),
							'split' => true,
							'expand' => true,
							'deep' => true,
							'remove' => 'none',
							'block_expand' => true,
							'block' => 'p'
						)),
						'alignleft' => array(
							array('selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', 'styles' => array('textAlign' => 'left')),
							array('selector' => 'img,table,dl.wp-caption', 'classes' => 'alignleft')
						),
						'aligncenter' => array(
							array('selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', 'styles' => array('textAlign' => 'center')),
							array('selector' => 'img,table,dl.wp-caption', 'classes' => 'aligncenter')
						),
						'alignright' => array(
							array('selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', 'styles' => array('textAlign' => 'right')),
							array('selector' => 'img,table,dl.wp-caption', 'classes' => 'alignright')
						),
						'strikethrough' => array('inline' => 'del')
					))
		    )
		);

		Gridder::$maxFontsize = 300;

		add_action( 'add_meta_boxes', array( $this, 'add_gridder_meta_boxes' ) );

		add_action( 'save_post', array( $this, 'save_desktop_json' ) );
		add_action( 'save_post', array( $this, 'save_phone_json' ) );
		// Note: History is saved on beforeunload via AJAX, not here, to match when layout is actually saved

		add_action( 'admin_init', array( $this, 'register_lay_iris' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gridder_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gridder_scripts' ) );

		// tiny mce
		add_filter('tiny_mce_before_init', array( $this, 'tinymce_fontsizes') );
		add_filter('tiny_mce_before_init', array( $this, 'unhide_kitchensink') );
		add_filter('tiny_mce_before_init', array( $this, 'tinymce_config') );

		add_filter( 'mce_external_plugins', array( $this, 'tinymce_external_plugins') );
		add_action( 'admin_init', array( $this, 'my_deregister_editor_expand') );
		add_action( 'admin_enqueue_scripts', array( $this, 'deregister_wplink' ) );

		add_filter( 'tiny_mce_plugins', array( $this, 'tinymce_remove_default_plugins' ) );
		// #tiny mce

		// ajax
		add_action( 'wp_ajax_set_notice_hidden_via_ajax', array( $this, 'set_notice_hidden_via_ajax'));

		add_action('wp_ajax_get_embed', array($this, 'get_embed'));
		add_action('wp_ajax_nopriv_get_embed', array($this, 'get_embed'));

		add_action('wp_ajax_get_posts_for_laylinkmodal', array($this, 'get_posts_for_laylinkmodal'));
		add_action('wp_ajax_get_posts_for_laylinkmodal_json', array($this, 'get_posts_for_laylinkmodal_json'));
		
		add_action('wp_ajax_get_gridder_revision', array($this, 'get_gridder_revision' ));
		add_action('wp_ajax_lay_save_gridder_json', array($this, 'ajax_save_gridder_json'));
		add_action('wp_ajax_lay_save_page_css', array($this, 'ajax_save_page_css'));
		add_action('wp_ajax_lay_save_page_settings', array($this, 'ajax_save_page_settings'));

		add_action('wp_ajax_set_guides_visibility', array($this, 'set_guides_visibility'));
		add_action('wp_ajax_set_layers_visibility', array($this, 'set_layers_visibility'));
		add_action('wp_ajax_set_sidebar_minimized', array($this, 'set_sidebar_minimized'));


		add_action('wp_ajax_set_connect_left_right_frame', array($this, 'set_connect_left_right_frame'));
		add_action('wp_ajax_set_connect_top_bottom_frame', array($this, 'set_connect_top_bottom_frame'));

		add_action('wp_ajax_set_show_link_imagehover_setting', array($this, 'set_show_link_imagehover_setting'));

		add_action('wp_ajax_set_element_layers_window_position', array($this, 'set_element_layers_window_position'));
		add_action('wp_ajax_set_element_layers_window_expanded', array($this, 'set_element_layers_window_expanded'));

		add_action('wp_ajax_set_phone_gridder_active', array($this, 'set_phone_gridder_active'));
		add_action('wp_ajax_set_row_bg_video_unmute_button_position_default', array($this, 'set_row_bg_video_unmute_button_position_default'));

		add_action('wp_ajax_lay_save_gridder_sidebar_default', array($this, 'ajax_save_gridder_sidebar_default'));
	}

	/**
	 * Save a sidebar default (fixed height, aspect ratio, stickytop) so it is used when an element has no value.
	 */
	public static function ajax_save_gridder_sidebar_default() {
		$key = isset($_POST['key']) ? sanitize_key($_POST['key']) : '';
		$allowed = array(
			'fixed_height' => array('option' => 'gridder_fixed_height_default', 'sanitize' => 'sanitize_text_field'),
			'custom_aspect_ratio_width' => array('option' => 'gridder_custom_aspect_ratio_width_default', 'sanitize' => 'intval'),
			'custom_aspect_ratio_height' => array('option' => 'gridder_custom_aspect_ratio_height_default', 'sanitize' => 'intval'),
			'custom_aspect_ratio_phone_width' => array('option' => 'gridder_custom_aspect_ratio_phone_width_default', 'sanitize' => 'intval'),
			'custom_aspect_ratio_phone_height' => array('option' => 'gridder_custom_aspect_ratio_phone_height_default', 'sanitize' => 'intval'),
			'stickytop' => array('option' => 'gridder_stickytop_default', 'sanitize' => 'intval'),
		);
		if ($key === '' || !isset($allowed[$key])) {
			wp_send_json_error();
		}
		if (!isset($_POST['value'])) {
			wp_send_json_error();
		}
		$value = $_POST['value'];
		$spec = $allowed[$key];
		$option_name = $spec['option'];
		$value = call_user_func($spec['sanitize'], $value);
		if ($key === 'fixed_height' && $value === '') {
			$value = '100svh - 40px';
		}
		update_option($option_name, $value);
		wp_send_json_success();
	}

    public static function set_element_layers_window_expanded(){
        $expanded = array_key_exists('expanded', $_POST) ? $_POST['expanded'] : null;
        update_option('lay_element_layers_window_expanded', $expanded);
    }

    public static function set_element_layers_window_position(){
        $json = array_key_exists('coordinates', $_POST) ? $_POST['coordinates'] : null;
        update_option('lay_element_layers_window_position', json_encode($json));
    }

    public static function set_show_link_imagehover_setting() {
		$visible = $_POST['show_link_imagehover_setting'];
		update_option('lay_show_link_imagehover_setting', $visible);
    }

    public static function set_connect_top_bottom_frame() {
		$visible = $_POST['connect_top_bottom_frame'];
		update_option('lay_connect_top_bottom_frame', $visible);
    }

	public static function set_connect_left_right_frame() {
		$visible = $_POST['connect_left_right_frame'];
		update_option('lay_connect_left_right_frame', $visible);
	}

	// set visibility in database
	public static function set_guides_visibility() {
		$visible = $_POST['visibility'];
		update_option('lay_guides_visibility', $visible);
	}

	public static function set_sidebar_minimized() {
		$minimized = $_POST['minimized'];
		update_option('lay_sidebar_minimized', $minimized);
	}

    // set visibility in database
	public static function set_layers_visibility() {
		$visible = $_POST['visibility'];
		update_option('lay_layers_visibility', $visible);
	}

	public static function set_phone_gridder_active() {
		$active = $_POST['active'];
		$post_type = $_POST['post_type'];
		$id = $_POST['id'];
		
		$option_name = 'phone_gridder_active_' . $post_type . '_' . $id;
		
		if ($active === 'true' || $active === true) {
			update_option($option_name, 'true');
		} else {
			update_option($option_name, 'false');
		}
	}

	public static function set_row_bg_video_unmute_button_position_default() {
		$position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : 'bottom-right';
		$allowed = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
		if (!in_array($position, $allowed, true)) {
			$position = 'bottom-right';
		}
		update_option('gridder_row_bg_video_unmute_button_position_default', $position);
		wp_send_json_success();
	}


	public static function get_gridder_revision() {
		$id = intval($_POST['id']);
		$screen_base = $_POST['screen_base'];

		$gridderval = "";
		$phonegridderval = "";

		if ($screen_base == 'term') {
			$gridderval = get_option( $id.'_category_gridder_json_last', true );
			update_option( $id.'_category_gridder_json', $gridderval );

			$phonegridderval = get_option( $id.'_phone_category_gridder_json_last', true );
			update_option( $id.'_phone_category_gridder_json', $phonegridderval );
		}else{
			// post, page
			$gridderval = get_post_meta( $id, '_gridder_json_last', true );
			$gridderval = wp_slash($gridderval);
			update_post_meta( $id, '_gridder_json', $gridderval );

			$phonegridderval = get_post_meta( $id, '_phone_gridder_json_last', true );
			$phonegridderval = wp_slash($phonegridderval);
			update_post_meta( $id, '_phone_gridder_json', $phonegridderval );
		}

		$data = array(
			'gridder' => $gridderval,
			'gridder_phone' => $phonegridderval
		);

		wp_send_json($data);
		wp_die();
	}

	public static function get_embed(){
		$s = $_POST['layembedval'];
		
		// Check if it's an Instagram URL
		if (preg_match('#https?://(www\.)?instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)/?#', $s, $matches)) {
			// Generate Instagram embed using iframe approach
			// This works without requiring Facebook API credentials
			$content_type = $matches[2]; // 'p', 'reel', or 'tv'
			$post_id = $matches[3];
			$embed_url = 'https://www.instagram.com/p/' . $post_id . '/embed/';
			
			// Set dimensions based on content type
			// Note: These are standard dimensions. Actual post dimensions vary,
			// but cannot be retrieved without Instagram Graph API credentials.
			$width = 400;
			$height = 480;
			
			if ($content_type === 'reel') {
				// Reels are vertical video format (9:16 aspect ratio)
				$width = 400;
				$height = 711;
			} elseif ($content_type === 'tv') {
				// IGTV is also vertical format but can vary
				$width = 400;
				$height = 600;
			}
			// Regular posts (/p/) use default 400x480
			
			$embed_html = '<iframe src="' . esc_url($embed_url) . '" 
				width="' . $width . '" 
				height="' . $height . '" 
				frameborder="0" 
				scrolling="no" 
				allowtransparency="true"></iframe>';
			
			echo $embed_html;
		} else {
			// Use standard WordPress oEmbed for other services
			echo wp_oembed_get($s);
		}
		
		die();
	}

	public static function get_posts_for_laylinkmodal(){

		$post_types_to_query = array('post', 'page');

		$args = array(
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'post_type' => $post_types_to_query
		);
        
		$all_posts_query = new WP_Query( $args );

		$markup = '<div id="wp-link"><ul>';
		$ix = 0;

		if ( $all_posts_query->have_posts() ) {
			foreach ($all_posts_query->posts as $post){
				$id = $post->ID;
				$title = $post->post_title;
				$permalink = get_permalink($post);

				$alternate = $ix % 2 == 0 ? ' class="alternate"' : '';

				$post_type = $post->post_type;
				if($post_type == "post"){
					$post_type = "project";
				}
				$info = $post_type.' ';
				$info .= date('Y/m/d', strtotime($post->post_date));

				$cat_id = "";
				if($post->post_type == "post"){
					$cat = get_the_category($post->ID);
					$cat_id = $cat[0]->term_id;
				}

				$markup .=
				'<li'.$alternate.' data-type="'.$post_type.'" data-id="'.$id.'">
					<input type="hidden" class="item-permalink" value="'.$permalink.'">
					<input type="hidden" class="item-id" value="'.$id.'">
					<input type="hidden" class="item-type" value="'.$post_type.'">
					<input type="hidden" class="item-catid" value="'.$cat_id.'">
					<span class="item-title">'.$post->post_title.'</span>
					<span class="item-info">'.$info.'</span>
				</li>';
				$ix++;
			}
		}

		// categories
		$terms = get_terms( 'category', array(
		    'hide_empty' => false,
		) );
		foreach ($terms as $term) {

			$id = $term->term_id;
			$title = $term->name;
			$permalink = get_term_link($term);

			$alternate = $ix % 2 == 0 ? ' class="alternate"' : '';

			$markup .=
			'<li'.$alternate.' data-type="category" data-id="'.$id.'">
				<input type="hidden" class="item-permalink" value="'.$permalink.'">
				<input type="hidden" class="item-id" value="'.$id.'">
				<input type="hidden" class="item-type" value="category">
				<span class="item-title">'.$title.'</span>
				<span class="item-info">category</span>
			</li>';
			$ix++;
		}

		$markup .= '</ul></div>';

		echo '<input type="search" name="lay-imagelink-search-input" id="lay-imagelink-search-input" placeholder="Search"><div class="lay-imagelink-postlist-wrap">'.$markup.'</div>';
		die();
	}

	public static function get_posts_for_laylinkmodal_json(){
		$post_types_to_query = array('post', 'page');
		if( class_exists('WooCommerce') ) {
			$post_types_to_query = array('post', 'page', 'product');
		}
		$val = get_option('misc_options_activate_news_feature', '');
		if( $val == "on" && !MiscOptions::$locked ){
			$post_types_to_query []= 'lay_news';
		}
		$deck_val = get_option('misc_options_activate_deck_feature', '');
		if( $deck_val == "on" && !MiscOptions::$locked ){
			$post_types_to_query []= 'lay_deck';
		}

		$args = array(
			'posts_per_page' => -1,
			'orderby' => array('type' => 'DESC', 'date' => 'DESC'),
			'order' => 'ASC',
			'post_type' => $post_types_to_query
		);
        
		$all_posts_query = new WP_Query( $args );

		$posts = array();

		if ( $all_posts_query->have_posts() ) {
			foreach ($all_posts_query->posts as $post){
				$permalink = get_permalink($post);
				$date = date('Y/m/d', strtotime($post->post_date));
				
				$post_type = $post->post_type;
				if ($post_type == "post") {
					$post_type = "project";
				}
				if ($post_type == "lay_news") {
					$post_type = "news";
				}
				// NOTE: deliberately NOT mapping lay_deck → deck. Body data-type
				// stays 'lay_deck' (raw post_type, see Frontend::current_type_id_slug_catid),
				// so anchors inserted via this link modal must match exactly or
				// preloadCache keys would fragment (X_deck vs X_lay_deck).

				$catid = array(0);
				if ($post->post_type == "post") {
					$categories = get_the_category($post->ID);
					$catid = array();
					foreach($categories as $cat){
						$catid []= $cat->term_id;
					}
				}

				$posts[] = array(
					'url' => $permalink,
					'id' => $post->ID,
					'type' => $post_type,
					'title' => $post->post_title,
					'date' => $date,
					'catid' => json_encode($catid)
				);
			}
		}

		// categories
		$terms = get_terms( 'category', array(
				'hide_empty' => false,
		) );

		foreach ($terms as $term) {
			$id = $term->term_id;
			$title = $term->name;
			$permalink = get_term_link($term);

			$posts[] = array(
				'url' => $permalink,
				'id' => $id,
				'type' => 'category',
				'title' => $title,
				'catid' => $catid
			);
		}

		wp_send_json($posts);
		die();
	}

	public function set_notice_hidden_via_ajax(){
		$optionname = $_POST['optionname'];
		update_option( $optionname, 'hide' );
		echo get_option( $optionname, '' );
		die();
	}

	public function unhide_kitchensink($args){
		$args['wordpress_adv_hidden'] = false;
		return $args;
	}

	// no auto resize and no expand for tinymce
	// https://core.trac.wordpress.org/ticket/29360#comment:10
	public function my_deregister_editor_expand() {
		wp_deregister_script('editor-expand');
	}
	public function tinymce_config( $init ) {
		unset( $init['wp_autoresize_on'] );
		$init['browser_spellcheck'] = false;

		// $init['paste_remove_styles'] = true;
		// $init['paste_remove_spans'] = true;
		// $init['paste_strip_class_attributes'] = 'all';
		// $init['paste_as_text'] = true;
		// $init['paste_text_sticky'] = true;

		return $init;
	}

	// http://www.wpexplorer.com/wordpress-tinymce-tweaks/
	public function tinymce_fontsizes( $initArray ){

		$sizes = '';
		$space = '';

		for($i=1; $i<Gridder::$maxFontsize; $i++){
			if($i>1){
				$space = ' ';
			}
			$sizes .= $space.$i.'px';
		}

	    $initArray['fontsize_formats'] = $sizes;
	    return $initArray;
	}

    public function tinymce_styles( ) {

    }

	public function tinymce_remove_default_plugins($plugins){
		// array(
		// 	'charmap',
		// 	'colorpicker',
		// 	// 'hr',
		// 	'lists',
		// 	// 'media',
		// 	'paste',
		// 	// 'tabfocus',
		// 	'textcolor',
		// 	// 'fullscreen',
		// 	'wordpress',
		// 	// 'wpautoresize',
		// 	// 'wpeditimage',
		// 	// 'wpemoji',
		// 	// 'wpgallery',
		// 	// 'wplink',
		// 	// 'wpdialogs',
		// 	// 'wptextpattern',
		// 	// 'wpview',
		// 	// 'wpembed',
		// 	// 'image'
		// );
		$not_needed = array('hr', 'media', 'tabfocus', 'fullscreen', 'wpeditimage', 'wpemoji', 'wpgallery', 'wpdialogs', 'wptextpattern', 'wpview', 'wpembed', 'image');
            // $plugins []= "advcode";
		$plugins = array_diff($plugins, $not_needed);

		return $plugins;
	}

	public function deregister_wplink(){
		// Only deregister on post.php pages
		$screen = get_current_screen();
		if (!$screen || !in_array($screen->base, ['post', 'post-new'])) {
			return;
		}
		// Product long description still uses default link/unlink when Gridder is off for products — needs wplink.
		if ( $screen->post_type === 'product' && LayFrontend_Options::$gridder_active_for_products != true ) {
			return;
		}
		wp_deregister_script('wplink');
	}

	public function tinymce_external_plugins($plugins) {
		$plugins['keep_using_chosen_textformat'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/keep_using_chosen_textformat/plugin.js?ver='.Setup::$ver;
		$plugins['h1_h2_p_buttons'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/h1_h2_p_buttons/plugin.js?ver='.Setup::$ver;
		$plugins['laybuttons'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/laybuttons/plugin.js?ver='.Setup::$ver;
		$plugins['laynightmode'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/nightmode/plugin.js?ver='.Setup::$ver;
		$plugins['height'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/height/plugin.js?ver='.Setup::$ver;
		$plugins['letterspacingselect'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/letterspacing/plugin.js?ver='.Setup::$ver;
		$plugins['lineheightselect'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/lineheight/plugin.js?ver='.Setup::$ver;
		$plugins['code'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/code/plugin.min.js?ver='.Setup::$ver;
		$plugins['nonbreaking'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/nonbreaking/plugin.min.js?ver='.Setup::$ver;
		$plugins['softhyphen'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/softhyphen/plugin.js?ver='.Setup::$ver;
		$plugins['table'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/table/plugin.min.js?ver='.Setup::$ver;
		$plugins['laylink'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/link/plugin.js?ver='.Setup::$ver;
		$plugins['visualblocks'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/visualblocks/plugin.min.js?ver='.Setup::$ver;
		$plugins['laynextproject'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/laynextproject/plugin.js?ver='.Setup::$ver;
		$plugins['layprevproject'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/layprevproject/plugin.js?ver='.Setup::$ver;
		$plugins['layscrolltotop'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/layscrolltotop/plugin.js?ver='.Setup::$ver;
		$plugins['layexpandrow'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/expandrows/plugin.js?ver='.Setup::$ver;
		$plugins['pastehtmlprompt'] = Setup::$uri.'/gridder/assets/js/tinymce-plugins/pastehtmlprompt/plugin.js?ver='.Setup::$ver;
		$plugins['directionality'] = includes_url('js/tinymce/plugins/directionality/plugin.min.js');

		return $plugins;
	}

	public function add_gridder_meta_boxes(){

		$add_to = array('post', 'page');
		if(LayFrontend_Options::$gridder_active_for_products){
			$add_to []= 'product';
		}

		$add_to = apply_filters('lay_add_gridder_meta_boxes_to', $add_to);
		foreach($add_to as $post_type){
			add_meta_box( 'gridder-json-metabox', 'Gridder JSON', array( $this, 'gridder_json_metabox_callback'), $post_type, 'normal', 'high' );
			add_meta_box( 'gridder-phone-json-metabox', 'Gridder Phone JSON', array( $this, 'gridder_phone_json_metabox_callback'), $post_type, 'normal', 'high' );
			add_meta_box( 'gridder-metabox', 'Gridder', array( $this, 'gridder_metabox_callback'), $post_type, 'normal', 'high' );
		}
	}

	private function decode_gridder_json_gzip($value){
		if($value === '' || $value === null){
			return null;
		}
		if(!function_exists('gzdecode')){
			return null;
		}
		$decoded = base64_decode($value, true);
		if($decoded === false){
			return null;
		}
		$inflated = gzdecode($decoded);
		if($inflated === false){
			return null;
		}
		return $inflated;
	}

	private function get_gridder_json_from_post($raw_key, $compressed_key, $post_id, $context){
		$has_compressed = isset($_POST[$compressed_key]) && $_POST[$compressed_key] !== '';
		$has_raw = isset($_POST[$raw_key]) && $_POST[$raw_key] !== '';
		if($has_compressed){
			$decoded = $this->decode_gridder_json_gzip($_POST[$compressed_key]);
			if($decoded !== null){
				return array(
					'value' => $decoded,
					'source' => 'gzip'
				);
			}
		}
		if($has_raw){
			return array(
				'value' => $_POST[$raw_key],
				'source' => 'raw'
			);
		}
		return null;
	}

	private function normalize_gridder_json_for_save($json, $post_id, $context, $source){
		if(!is_string($json) || $json === ''){
			return null;
		}
		$decoded = json_decode($json, true);
		if($decoded === null && json_last_error() !== JSON_ERROR_NONE){
			$unslashed = wp_unslash($json);
			$decoded = json_decode($unslashed, true);
		}
		if($decoded === null && json_last_error() !== JSON_ERROR_NONE){
			$slashed = wp_slash($json);
			$decoded = json_decode($slashed, true);
		}
		if($decoded === null && json_last_error() !== JSON_ERROR_NONE){
			return null;
		}
		return wp_json_encode($decoded, JSON_UNESCAPED_SLASHES);
	}

	public function ajax_save_gridder_json(){
		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
		if($post_id <= 0){
			wp_send_json_error(array('message' => 'missing_post_id'));
		}
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if(!wp_verify_nonce($nonce, 'gridder_json_metabox')){
			wp_send_json_error(array('message' => 'invalid_nonce'));
		}
		if(!current_user_can('edit_post', $post_id)){
			wp_send_json_error(array('message' => 'forbidden'));
		}

		$desktop_gzip = isset($_POST['gridder_json_gzip']) ? wp_unslash($_POST['gridder_json_gzip']) : '';
		$phone_gzip = isset($_POST['phone_gridder_json_gzip']) ? wp_unslash($_POST['phone_gridder_json_gzip']) : '';

		$desktop_json = $this->decode_gridder_json_gzip($desktop_gzip);
		$phone_json = $phone_gzip !== '' ? $this->decode_gridder_json_gzip($phone_gzip) : null;

		if($desktop_json === null){
			wp_send_json_error(array('message' => 'desktop_decode_failed'));
		}

		$normalized_desktop = $this->normalize_gridder_json_for_save($desktop_json, $post_id, 'desktop', 'gzip');
		if($normalized_desktop === null){
			wp_send_json_error(array('message' => 'desktop_invalid_json'));
		}

		if(!wp_is_post_revision($post_id)){
			$prev = get_post_meta($post_id, '_gridder_json', true);
			$prev = wp_slash($prev);
			update_post_meta($post_id, '_gridder_json_last', $prev);
		}
		$normalized_desktop_slash = wp_slash($normalized_desktop);
		update_post_meta($post_id, '_gridder_json', $normalized_desktop_slash);
		update_post_meta($post_id, '_gridder_json_preview', $normalized_desktop_slash);

		if($phone_gzip === ''){
			// Gzip ON path: client sent empty string = delete phone layout; clear meta so it stays removed after save
			update_post_meta($post_id, '_phone_gridder_json', '');
			update_post_meta($post_id, '_phone_gridder_json_preview', '');
		} elseif($phone_json !== null){
			$normalized_phone = $this->normalize_gridder_json_for_save($phone_json, $post_id, 'phone', 'gzip');
			if($normalized_phone === null){
				wp_send_json_error(array('message' => 'phone_invalid_json'));
			}
			if(!wp_is_post_revision($post_id)){
				$prev_phone = get_post_meta($post_id, '_phone_gridder_json', true);
				$prev_phone = wp_slash($prev_phone);
				update_post_meta($post_id, '_phone_gridder_json_last', $prev_phone);
			}
			$normalized_phone_slash = wp_slash($normalized_phone);
			update_post_meta($post_id, '_phone_gridder_json', $normalized_phone_slash);
			update_post_meta($post_id, '_phone_gridder_json_preview', $normalized_phone_slash);
		}

		wp_send_json_success(array('saved' => true));
	}

	public function ajax_save_page_css(){
		$post_id = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
		if($post_id <= 0){
			wp_send_json_error(array('message' => 'missing_post_id'));
		}
		$target_type = isset($_POST['targetType']) ? sanitize_text_field(wp_unslash($_POST['targetType'])) : '';
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if(!wp_verify_nonce($nonce, 'lay_page_css_nonce')){
			wp_send_json_error(array('message' => 'invalid_nonce'));
		}
		if($target_type === 'category'){
			if(!current_user_can('edit_term', $post_id) && !current_user_can('manage_categories')){
				wp_send_json_error(array('message' => 'forbidden'));
			}
		}else{
			if(!current_user_can('edit_post', $post_id)){
				wp_send_json_error(array('message' => 'forbidden'));
			}
		}

		$css_type = isset($_POST['cssType']) ? sanitize_text_field(wp_unslash($_POST['cssType'])) : '';
		$css_value = isset($_POST['cssValue']) ? wp_unslash($_POST['cssValue']) : '';

		$map = array(
			'page_custom_css' => '_lay_page_custom_css',
			'page_custom_desktop_css' => '_lay_page_custom_desktop_css',
			'page_custom_mobile_css' => '_lay_page_custom_mobile_css'
		);

		if(!array_key_exists($css_type, $map)){
			wp_send_json_error(array('message' => 'invalid_css_type'));
		}

		if($target_type === 'category'){
			update_term_meta($post_id, $map[$css_type], $css_value);
		}else{
			update_post_meta($post_id, $map[$css_type], $css_value);
		}
		wp_send_json_success(array('saved' => true));
	}

	public function ajax_save_page_settings(){
		$post_id = isset($_POST['postId']) ? intval($_POST['postId']) : 0;
		$target_type = isset($_POST['targetType']) ? sanitize_text_field(wp_unslash($_POST['targetType'])) : '';
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		$hide_site_title_raw = isset($_POST['hide_site_title']) ? $_POST['hide_site_title'] : '(not set)';
		$hide_menu_raw = isset($_POST['hide_menu']) ? $_POST['hide_menu'] : '(not set)';
		$hide_footer_raw = isset($_POST['hide_footer']) ? $_POST['hide_footer'] : '(not set)';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Lay Page Settings] Request: postId=' . $post_id . ', targetType=' . $target_type . ', nonce=' . ( $nonce ? 'set' : 'missing' ) . ', hide_site_title=' . $hide_site_title_raw . ', hide_menu=' . $hide_menu_raw . ', hide_footer=' . $hide_footer_raw . ', POST_keys=' . implode( ',', array_keys( $_POST ) ) );
		}
		if($post_id <= 0){
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[Lay Page Settings] Abort: missing_post_id' );
			}
			wp_send_json_error(array('message' => 'missing_post_id'));
		}
		if(!wp_verify_nonce($nonce, 'lay_page_settings_nonce')){
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[Lay Page Settings] Abort: invalid_nonce' );
			}
			wp_send_json_error(array('message' => 'invalid_nonce'));
		}
		if($target_type === 'category'){
			if(!current_user_can('edit_term', $post_id) && !current_user_can('manage_categories')){
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
					error_log( '[Lay Page Settings] Abort: forbidden (category)' );
				}
				wp_send_json_error(array('message' => 'forbidden'));
			}
		}else{
			if(!current_user_can('edit_post', $post_id)){
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
					error_log( '[Lay Page Settings] Abort: forbidden (post)' );
				}
				wp_send_json_error(array('message' => 'forbidden'));
			}
		}

		$hide_site_title = isset($_POST['hide_site_title']) ? sanitize_text_field(wp_unslash($_POST['hide_site_title'])) : '';
		$hide_menu = isset($_POST['hide_menu']) ? sanitize_text_field(wp_unslash($_POST['hide_menu'])) : '';
		$hide_menu_2 = isset($_POST['hide_menu_2']) ? sanitize_text_field(wp_unslash($_POST['hide_menu_2'])) : '';
		$hide_menu_3 = isset($_POST['hide_menu_3']) ? sanitize_text_field(wp_unslash($_POST['hide_menu_3'])) : '';
		$hide_menu_4 = isset($_POST['hide_menu_4']) ? sanitize_text_field(wp_unslash($_POST['hide_menu_4'])) : '';
		$hide_footer = isset($_POST['hide_footer']) ? sanitize_text_field(wp_unslash($_POST['hide_footer'])) : '';

		$page_overlay_burger_color_enable = isset($_POST['page_overlay_burger_color_enable']) ? sanitize_text_field(wp_unslash($_POST['page_overlay_burger_color_enable'])) : '';
		$page_overlay_burger_color = isset($_POST['page_overlay_burger_color']) ? sanitize_hex_color(wp_unslash($_POST['page_overlay_burger_color'])) : '';
		$menu_color_enable = isset($_POST['menu_color_enable']) ? sanitize_text_field(wp_unslash($_POST['menu_color_enable'])) : '';
		$menu_color = isset($_POST['menu_color']) ? sanitize_hex_color(wp_unslash($_POST['menu_color'])) : '';
		$menu_bar_color_enable = isset($_POST['menu_bar_color_enable']) ? sanitize_text_field(wp_unslash($_POST['menu_bar_color_enable'])) : '';
		$menu_bar_color = isset($_POST['menu_bar_color']) ? sanitize_hex_color(wp_unslash($_POST['menu_bar_color'])) : '';
		$site_title_color_enable = isset($_POST['site_title_color_enable']) ? sanitize_text_field(wp_unslash($_POST['site_title_color_enable'])) : '';
		$site_title_color = isset($_POST['site_title_color']) ? sanitize_hex_color(wp_unslash($_POST['site_title_color'])) : '';
		$site_title_mouseover_color_enable = isset($_POST['site_title_mouseover_color_enable']) ? sanitize_text_field(wp_unslash($_POST['site_title_mouseover_color_enable'])) : '';
		$site_title_mouseover_color = isset($_POST['site_title_mouseover_color']) ? sanitize_hex_color(wp_unslash($_POST['site_title_mouseover_color'])) : '';
		$menu_mouseover_color_enable = isset($_POST['menu_mouseover_color_enable']) ? sanitize_text_field(wp_unslash($_POST['menu_mouseover_color_enable'])) : '';
		$menu_mouseover_color = isset($_POST['menu_mouseover_color']) ? sanitize_hex_color(wp_unslash($_POST['menu_mouseover_color'])) : '';
		$phone_site_title_color_enable = isset($_POST['phone_site_title_color_enable']) ? sanitize_text_field(wp_unslash($_POST['phone_site_title_color_enable'])) : '';
		$phone_site_title_color = isset($_POST['phone_site_title_color']) ? sanitize_hex_color(wp_unslash($_POST['phone_site_title_color'])) : '';
		$phone_menu_color_enable = isset($_POST['phone_menu_color_enable']) ? sanitize_text_field(wp_unslash($_POST['phone_menu_color_enable'])) : '';
		$phone_menu_color = isset($_POST['phone_menu_color']) ? sanitize_hex_color(wp_unslash($_POST['phone_menu_color'])) : '';
		$phone_menu_bar_color_enable = isset($_POST['phone_menu_bar_color_enable']) ? sanitize_text_field(wp_unslash($_POST['phone_menu_bar_color_enable'])) : '';
		$phone_menu_bar_color = isset($_POST['phone_menu_bar_color']) ? sanitize_hex_color(wp_unslash($_POST['phone_menu_bar_color'])) : '';
		$phone_page_overlay_burger_color_enable = isset($_POST['phone_page_overlay_burger_color_enable']) ? sanitize_text_field(wp_unslash($_POST['phone_page_overlay_burger_color_enable'])) : '';
		$phone_page_overlay_burger_color = isset($_POST['phone_page_overlay_burger_color']) ? sanitize_hex_color(wp_unslash($_POST['phone_page_overlay_burger_color'])) : '';
		$phone_mobile_menu_burger_color_enable = isset($_POST['phone_mobile_menu_burger_color_enable']) ? sanitize_text_field(wp_unslash($_POST['phone_mobile_menu_burger_color_enable'])) : '';
		$phone_mobile_menu_burger_color = isset($_POST['phone_mobile_menu_burger_color']) ? sanitize_hex_color(wp_unslash($_POST['phone_mobile_menu_burger_color'])) : '';

		if($target_type === 'category'){
			update_term_meta($post_id, '_lay_hide_site_title', $hide_site_title);
			update_term_meta($post_id, '_lay_hide_menu', $hide_menu);
			update_term_meta($post_id, '_lay_hide_menu_2', $hide_menu_2);
			update_term_meta($post_id, '_lay_hide_menu_3', $hide_menu_3);
			update_term_meta($post_id, '_lay_hide_menu_4', $hide_menu_4);
			update_term_meta($post_id, '_lay_hide_footer', $hide_footer);
			update_term_meta($post_id, '_lay_page_overlay_burger_color_enable', $page_overlay_burger_color_enable);
			update_term_meta($post_id, '_lay_page_overlay_burger_color', $page_overlay_burger_color);
			update_term_meta($post_id, '_lay_menu_color_enable', $menu_color_enable);
			update_term_meta($post_id, '_lay_menu_color', $menu_color);
			update_term_meta($post_id, '_lay_menu_bar_color_enable', $menu_bar_color_enable);
			update_term_meta($post_id, '_lay_menu_bar_color', $menu_bar_color);
			update_term_meta($post_id, '_lay_site_title_color_enable', $site_title_color_enable);
			update_term_meta($post_id, '_lay_site_title_color', $site_title_color);
			update_term_meta($post_id, '_lay_site_title_mouseover_color_enable', $site_title_mouseover_color_enable);
			update_term_meta($post_id, '_lay_site_title_mouseover_color', $site_title_mouseover_color);
			update_term_meta($post_id, '_lay_menu_mouseover_color_enable', $menu_mouseover_color_enable);
			update_term_meta($post_id, '_lay_menu_mouseover_color', $menu_mouseover_color);
			update_term_meta($post_id, '_lay_phone_site_title_color_enable', $phone_site_title_color_enable);
			update_term_meta($post_id, '_lay_phone_site_title_color', $phone_site_title_color);
			update_term_meta($post_id, '_lay_phone_menu_color_enable', $phone_menu_color_enable);
			update_term_meta($post_id, '_lay_phone_menu_color', $phone_menu_color);
			update_term_meta($post_id, '_lay_phone_menu_bar_color_enable', $phone_menu_bar_color_enable);
			update_term_meta($post_id, '_lay_phone_menu_bar_color', $phone_menu_bar_color);
			update_term_meta($post_id, '_lay_phone_page_overlay_burger_color_enable', $phone_page_overlay_burger_color_enable);
			update_term_meta($post_id, '_lay_phone_page_overlay_burger_color', $phone_page_overlay_burger_color);
			update_term_meta($post_id, '_lay_phone_mobile_menu_burger_color_enable', $phone_mobile_menu_burger_color_enable);
			update_term_meta($post_id, '_lay_phone_mobile_menu_burger_color', $phone_mobile_menu_burger_color);
		}else{
			update_post_meta($post_id, '_lay_hide_site_title', $hide_site_title);
			update_post_meta($post_id, '_lay_hide_menu', $hide_menu);
			update_post_meta($post_id, '_lay_hide_menu_2', $hide_menu_2);
			update_post_meta($post_id, '_lay_hide_menu_3', $hide_menu_3);
			update_post_meta($post_id, '_lay_hide_menu_4', $hide_menu_4);
			update_post_meta($post_id, '_lay_hide_footer', $hide_footer);
			update_post_meta($post_id, '_lay_page_overlay_burger_color_enable', $page_overlay_burger_color_enable);
			update_post_meta($post_id, '_lay_page_overlay_burger_color', $page_overlay_burger_color);
			update_post_meta($post_id, '_lay_menu_color_enable', $menu_color_enable);
			update_post_meta($post_id, '_lay_menu_color', $menu_color);
			update_post_meta($post_id, '_lay_menu_bar_color_enable', $menu_bar_color_enable);
			update_post_meta($post_id, '_lay_menu_bar_color', $menu_bar_color);
			update_post_meta($post_id, '_lay_site_title_color_enable', $site_title_color_enable);
			update_post_meta($post_id, '_lay_site_title_color', $site_title_color);
			update_post_meta($post_id, '_lay_site_title_mouseover_color_enable', $site_title_mouseover_color_enable);
			update_post_meta($post_id, '_lay_site_title_mouseover_color', $site_title_mouseover_color);
			update_post_meta($post_id, '_lay_menu_mouseover_color_enable', $menu_mouseover_color_enable);
			update_post_meta($post_id, '_lay_menu_mouseover_color', $menu_mouseover_color);
			update_post_meta($post_id, '_lay_phone_site_title_color_enable', $phone_site_title_color_enable);
			update_post_meta($post_id, '_lay_phone_site_title_color', $phone_site_title_color);
			update_post_meta($post_id, '_lay_phone_menu_color_enable', $phone_menu_color_enable);
			update_post_meta($post_id, '_lay_phone_menu_color', $phone_menu_color);
			update_post_meta($post_id, '_lay_phone_menu_bar_color_enable', $phone_menu_bar_color_enable);
			update_post_meta($post_id, '_lay_phone_menu_bar_color', $phone_menu_bar_color);
			update_post_meta($post_id, '_lay_phone_page_overlay_burger_color_enable', $phone_page_overlay_burger_color_enable);
			update_post_meta($post_id, '_lay_phone_page_overlay_burger_color', $phone_page_overlay_burger_color);
			update_post_meta($post_id, '_lay_phone_mobile_menu_burger_color_enable', $phone_mobile_menu_burger_color_enable);
			update_post_meta($post_id, '_lay_phone_mobile_menu_burger_color', $phone_mobile_menu_burger_color);
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( '[Lay Page Settings] Saved: postId=' . $post_id . ', hide_footer=' . $hide_footer );
		}
		wp_send_json_success(array('saved' => true));
	}

	public function save_desktop_json($post_id){

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['gridder_json_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['gridder_json_metabox_nonce'], 'gridder_json_metabox' ) ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Make sure that it is set.
		$gridder_json_result = $this->get_gridder_json_from_post('gridder_json_field', 'gridder_json_field_gzip', $post_id, 'desktop');
		if($gridder_json_result === null){
			return;
		}
		$gridder_json = $this->normalize_gridder_json_for_save(
			$gridder_json_result['value'],
			$post_id,
			'desktop',
			$gridder_json_result['source']
		);
		if($gridder_json === null){
			return;
		}

		// Save to preview/revision posts for WordPress preview functionality
		// Always save the meta data so previews work correctly
		if(!wp_is_post_revision($post_id)){
			// save last meta field value
			$value = get_post_meta( $post_id, '_gridder_json', true );
			$value = wp_slash($value);
			update_post_meta( $post_id, '_gridder_json_last', $value );
		}

		// Update the meta field in the database
		$gridder_json_slash = wp_slash($gridder_json);
		update_post_meta( $post_id, '_gridder_json', $gridder_json_slash );
		// Keep custom preview in sync on manual updates so refreshing a preview tab shows latest state
		update_post_meta( $post_id, '_gridder_json_preview', $gridder_json_slash );

		// Save page CSS (from Edit CSS modal; values are in hidden textareas)
		if ( isset( $_POST['lay_page_custom_css'] ) ) {
			update_post_meta( $post_id, '_lay_page_custom_css', wp_unslash( $_POST['lay_page_custom_css'] ) );
		}
		if ( isset( $_POST['lay_page_custom_desktop_css'] ) ) {
			update_post_meta( $post_id, '_lay_page_custom_desktop_css', wp_unslash( $_POST['lay_page_custom_desktop_css'] ) );
		}
		if ( isset( $_POST['lay_page_custom_mobile_css'] ) ) {
			update_post_meta( $post_id, '_lay_page_custom_mobile_css', wp_unslash( $_POST['lay_page_custom_mobile_css'] ) );
		}

		// Page settings: hide site title / hide menu / hide footer
		if ( isset( $_POST['lay_hide_site_title'] ) ) {
			update_post_meta( $post_id, '_lay_hide_site_title', sanitize_text_field( wp_unslash( $_POST['lay_hide_site_title'] ) ) );
		}
		if ( isset( $_POST['lay_hide_menu'] ) ) {
			update_post_meta( $post_id, '_lay_hide_menu', sanitize_text_field( wp_unslash( $_POST['lay_hide_menu'] ) ) );
		}
		if ( isset( $_POST['lay_hide_menu_2'] ) ) {
			update_post_meta( $post_id, '_lay_hide_menu_2', sanitize_text_field( wp_unslash( $_POST['lay_hide_menu_2'] ) ) );
		}
		if ( isset( $_POST['lay_hide_menu_3'] ) ) {
			update_post_meta( $post_id, '_lay_hide_menu_3', sanitize_text_field( wp_unslash( $_POST['lay_hide_menu_3'] ) ) );
		}
		if ( isset( $_POST['lay_hide_menu_4'] ) ) {
			update_post_meta( $post_id, '_lay_hide_menu_4', sanitize_text_field( wp_unslash( $_POST['lay_hide_menu_4'] ) ) );
		}
		if ( isset( $_POST['lay_hide_footer'] ) ) {
			update_post_meta( $post_id, '_lay_hide_footer', sanitize_text_field( wp_unslash( $_POST['lay_hide_footer'] ) ) );
		}
		if ( isset( $_POST['lay_page_overlay_burger_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_page_overlay_burger_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_page_overlay_burger_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_page_overlay_burger_color'] ) ) {
			update_post_meta( $post_id, '_lay_page_overlay_burger_color', sanitize_hex_color( wp_unslash( $_POST['lay_page_overlay_burger_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_menu_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_menu_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_menu_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_menu_color'] ) ) {
			update_post_meta( $post_id, '_lay_menu_color', sanitize_hex_color( wp_unslash( $_POST['lay_menu_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_menu_bar_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_menu_bar_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_menu_bar_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_menu_bar_color'] ) ) {
			update_post_meta( $post_id, '_lay_menu_bar_color', sanitize_hex_color( wp_unslash( $_POST['lay_menu_bar_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_site_title_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_site_title_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_site_title_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_site_title_color'] ) ) {
			update_post_meta( $post_id, '_lay_site_title_color', sanitize_hex_color( wp_unslash( $_POST['lay_site_title_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_site_title_mouseover_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_site_title_mouseover_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_site_title_mouseover_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_site_title_mouseover_color'] ) ) {
			update_post_meta( $post_id, '_lay_site_title_mouseover_color', sanitize_hex_color( wp_unslash( $_POST['lay_site_title_mouseover_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_menu_mouseover_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_menu_mouseover_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_menu_mouseover_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_menu_mouseover_color'] ) ) {
			update_post_meta( $post_id, '_lay_menu_mouseover_color', sanitize_hex_color( wp_unslash( $_POST['lay_menu_mouseover_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_phone_site_title_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_phone_site_title_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_phone_site_title_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_phone_site_title_color'] ) ) {
			update_post_meta( $post_id, '_lay_phone_site_title_color', sanitize_hex_color( wp_unslash( $_POST['lay_phone_site_title_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_phone_menu_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_phone_menu_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_phone_menu_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_phone_menu_color'] ) ) {
			update_post_meta( $post_id, '_lay_phone_menu_color', sanitize_hex_color( wp_unslash( $_POST['lay_phone_menu_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_phone_menu_bar_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_phone_menu_bar_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_phone_menu_bar_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_phone_menu_bar_color'] ) ) {
			update_post_meta( $post_id, '_lay_phone_menu_bar_color', sanitize_hex_color( wp_unslash( $_POST['lay_phone_menu_bar_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_phone_page_overlay_burger_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_phone_page_overlay_burger_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_phone_page_overlay_burger_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_phone_page_overlay_burger_color'] ) ) {
			update_post_meta( $post_id, '_lay_phone_page_overlay_burger_color', sanitize_hex_color( wp_unslash( $_POST['lay_phone_page_overlay_burger_color'] ) ) ?: '' );
		}
		if ( isset( $_POST['lay_phone_mobile_menu_burger_color_enable'] ) ) {
			update_post_meta( $post_id, '_lay_phone_mobile_menu_burger_color_enable', sanitize_text_field( wp_unslash( $_POST['lay_phone_mobile_menu_burger_color_enable'] ) ) );
		}
		if ( isset( $_POST['lay_phone_mobile_menu_burger_color'] ) ) {
			update_post_meta( $post_id, '_lay_phone_mobile_menu_burger_color', sanitize_hex_color( wp_unslash( $_POST['lay_phone_mobile_menu_burger_color'] ) ) ?: '' );
		}
	}

	public function gridder_json_metabox_callback( $post ){
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'gridder_json_metabox', 'gridder_json_metabox_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		// htmlspecialchars needed because textarea converts some chars
		$value = get_post_meta( $post->ID, '_gridder_json', true );
		$gzip_supported = function_exists('gzdecode') ? '1' : '0';
		$gzip_enabled = get_option('misc_options_enable_gridder_gzip_save', '') === 'on' ? '1' : '0';
		$gzip_allowed = ($gzip_supported === '1' && $gzip_enabled === '1') ? '1' : '0';

		echo '<p><strong>Gridder JSON</strong></p>';
		echo '<input type="hidden" id="gridder_json_gzip_supported" name="gridder_json_gzip_supported" value="'.$gzip_allowed.'" />';
		echo '<input type="hidden" id="gridder_json_saved_via_ajax" name="gridder_json_saved_via_ajax" value="" />';
		echo '<textarea id="gridder_json_field" name="gridder_json_field" style="width:100%;height:200px;">';
		echo htmlspecialchars($value);
		echo '</textarea>';

		// Page CSS: saved with post on Update (Edit CSS modal only writes into these fields)
		$page_custom_css = get_post_meta( $post->ID, '_lay_page_custom_css', true );
		$page_custom_desktop_css = get_post_meta( $post->ID, '_lay_page_custom_desktop_css', true );
		$page_custom_mobile_css = get_post_meta( $post->ID, '_lay_page_custom_mobile_css', true );
		echo '<textarea id="lay_page_custom_css_field" name="lay_page_custom_css" style="display:none;">' . esc_textarea( (string) $page_custom_css ) . '</textarea>';
		echo '<textarea id="lay_page_custom_desktop_css_field" name="lay_page_custom_desktop_css" style="display:none;">' . esc_textarea( (string) $page_custom_desktop_css ) . '</textarea>';
		echo '<textarea id="lay_page_custom_mobile_css_field" name="lay_page_custom_mobile_css" style="display:none;">' . esc_textarea( (string) $page_custom_mobile_css ) . '</textarea>';

		// Page settings: hide site title / hide menu / hide footer (saved with post on Update)
		$lay_hide_site_title = get_post_meta( $post->ID, '_lay_hide_site_title', true );
		$lay_hide_menu = get_post_meta( $post->ID, '_lay_hide_menu', true );
		$lay_hide_menu_2 = get_post_meta( $post->ID, '_lay_hide_menu_2', true );
		$lay_hide_menu_3 = get_post_meta( $post->ID, '_lay_hide_menu_3', true );
		$lay_hide_menu_4 = get_post_meta( $post->ID, '_lay_hide_menu_4', true );
		$lay_hide_footer = get_post_meta( $post->ID, '_lay_hide_footer', true );
		echo '<input type="hidden" id="lay_hide_site_title_field" name="lay_hide_site_title" value="' . esc_attr( (string) $lay_hide_site_title ) . '" />';
		echo '<input type="hidden" id="lay_hide_menu_field" name="lay_hide_menu" value="' . esc_attr( (string) $lay_hide_menu ) . '" />';
		echo '<input type="hidden" id="lay_hide_menu_2_field" name="lay_hide_menu_2" value="' . esc_attr( (string) $lay_hide_menu_2 ) . '" />';
		echo '<input type="hidden" id="lay_hide_menu_3_field" name="lay_hide_menu_3" value="' . esc_attr( (string) $lay_hide_menu_3 ) . '" />';
		echo '<input type="hidden" id="lay_hide_menu_4_field" name="lay_hide_menu_4" value="' . esc_attr( (string) $lay_hide_menu_4 ) . '" />';
		echo '<input type="hidden" id="lay_hide_footer_field" name="lay_hide_footer" value="' . esc_attr( (string) $lay_hide_footer ) . '" />';
		$lay_page_overlay_burger_color_enable = get_post_meta( $post->ID, '_lay_page_overlay_burger_color_enable', true );
		$lay_page_overlay_burger_color = get_post_meta( $post->ID, '_lay_page_overlay_burger_color', true );
		$lay_menu_color_enable = get_post_meta( $post->ID, '_lay_menu_color_enable', true );
		$lay_menu_color = get_post_meta( $post->ID, '_lay_menu_color', true );
		$lay_menu_bar_color_enable = get_post_meta( $post->ID, '_lay_menu_bar_color_enable', true );
		$lay_menu_bar_color = get_post_meta( $post->ID, '_lay_menu_bar_color', true );
		$lay_site_title_color_enable = get_post_meta( $post->ID, '_lay_site_title_color_enable', true );
		$lay_site_title_color = get_post_meta( $post->ID, '_lay_site_title_color', true );
		$lay_site_title_mouseover_color_enable = get_post_meta( $post->ID, '_lay_site_title_mouseover_color_enable', true );
		$lay_site_title_mouseover_color = get_post_meta( $post->ID, '_lay_site_title_mouseover_color', true );
		$lay_menu_mouseover_color_enable = get_post_meta( $post->ID, '_lay_menu_mouseover_color_enable', true );
		$lay_menu_mouseover_color = get_post_meta( $post->ID, '_lay_menu_mouseover_color', true );
		echo '<input type="hidden" id="lay_page_overlay_burger_color_enable_field" name="lay_page_overlay_burger_color_enable" value="' . esc_attr( (string) $lay_page_overlay_burger_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_page_overlay_burger_color_field" name="lay_page_overlay_burger_color" value="' . esc_attr( (string) $lay_page_overlay_burger_color ) . '" />';
		echo '<input type="hidden" id="lay_menu_color_enable_field" name="lay_menu_color_enable" value="' . esc_attr( (string) $lay_menu_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_menu_color_field" name="lay_menu_color" value="' . esc_attr( (string) $lay_menu_color ) . '" />';
		echo '<input type="hidden" id="lay_menu_bar_color_enable_field" name="lay_menu_bar_color_enable" value="' . esc_attr( (string) $lay_menu_bar_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_menu_bar_color_field" name="lay_menu_bar_color" value="' . esc_attr( (string) $lay_menu_bar_color ) . '" />';
		echo '<input type="hidden" id="lay_site_title_color_enable_field" name="lay_site_title_color_enable" value="' . esc_attr( (string) $lay_site_title_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_site_title_color_field" name="lay_site_title_color" value="' . esc_attr( (string) $lay_site_title_color ) . '" />';
		echo '<input type="hidden" id="lay_site_title_mouseover_color_enable_field" name="lay_site_title_mouseover_color_enable" value="' . esc_attr( (string) $lay_site_title_mouseover_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_site_title_mouseover_color_field" name="lay_site_title_mouseover_color" value="' . esc_attr( (string) $lay_site_title_mouseover_color ) . '" />';
		echo '<input type="hidden" id="lay_menu_mouseover_color_enable_field" name="lay_menu_mouseover_color_enable" value="' . esc_attr( (string) $lay_menu_mouseover_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_menu_mouseover_color_field" name="lay_menu_mouseover_color" value="' . esc_attr( (string) $lay_menu_mouseover_color ) . '" />';
		$lay_phone_site_title_color_enable = get_post_meta( $post->ID, '_lay_phone_site_title_color_enable', true );
		$lay_phone_site_title_color = get_post_meta( $post->ID, '_lay_phone_site_title_color', true );
		$lay_phone_menu_color_enable = get_post_meta( $post->ID, '_lay_phone_menu_color_enable', true );
		$lay_phone_menu_color = get_post_meta( $post->ID, '_lay_phone_menu_color', true );
		$lay_phone_menu_bar_color_enable = get_post_meta( $post->ID, '_lay_phone_menu_bar_color_enable', true );
		$lay_phone_menu_bar_color = get_post_meta( $post->ID, '_lay_phone_menu_bar_color', true );
		echo '<input type="hidden" id="lay_phone_site_title_color_enable_field" name="lay_phone_site_title_color_enable" value="' . esc_attr( (string) $lay_phone_site_title_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_phone_site_title_color_field" name="lay_phone_site_title_color" value="' . esc_attr( (string) $lay_phone_site_title_color ) . '" />';
		echo '<input type="hidden" id="lay_phone_menu_color_enable_field" name="lay_phone_menu_color_enable" value="' . esc_attr( (string) $lay_phone_menu_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_phone_menu_color_field" name="lay_phone_menu_color" value="' . esc_attr( (string) $lay_phone_menu_color ) . '" />';
		echo '<input type="hidden" id="lay_phone_menu_bar_color_enable_field" name="lay_phone_menu_bar_color_enable" value="' . esc_attr( (string) $lay_phone_menu_bar_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_phone_menu_bar_color_field" name="lay_phone_menu_bar_color" value="' . esc_attr( (string) $lay_phone_menu_bar_color ) . '" />';
		$lay_phone_page_overlay_burger_color_enable = get_post_meta( $post->ID, '_lay_phone_page_overlay_burger_color_enable', true );
		$lay_phone_page_overlay_burger_color = get_post_meta( $post->ID, '_lay_phone_page_overlay_burger_color', true );
		echo '<input type="hidden" id="lay_phone_page_overlay_burger_color_enable_field" name="lay_phone_page_overlay_burger_color_enable" value="' . esc_attr( (string) $lay_phone_page_overlay_burger_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_phone_page_overlay_burger_color_field" name="lay_phone_page_overlay_burger_color" value="' . esc_attr( (string) $lay_phone_page_overlay_burger_color ) . '" />';
		$lay_phone_mobile_menu_burger_color_enable = get_post_meta( $post->ID, '_lay_phone_mobile_menu_burger_color_enable', true );
		$lay_phone_mobile_menu_burger_color = get_post_meta( $post->ID, '_lay_phone_mobile_menu_burger_color', true );
		echo '<input type="hidden" id="lay_phone_mobile_menu_burger_color_enable_field" name="lay_phone_mobile_menu_burger_color_enable" value="' . esc_attr( (string) $lay_phone_mobile_menu_burger_color_enable ) . '" />';
		echo '<input type="hidden" id="lay_phone_mobile_menu_burger_color_field" name="lay_phone_mobile_menu_burger_color" value="' . esc_attr( (string) $lay_phone_mobile_menu_burger_color ) . '" />';
	}

	public function save_phone_json($post_id){

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['gridder_phone_json_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['gridder_phone_json_metabox_nonce'], 'gridder_phone_json_metabox' ) ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Gzip OFF path: read from POST textarea (phone_gridder_json_field) or gzip field.
		// When gzip is ON, the frontend saves via AJAX then submits with empty textareas; do not treat that as "delete".
		$saved_via_ajax = isset($_POST['gridder_json_saved_via_ajax']) && $_POST['gridder_json_saved_via_ajax'] === '1';
		$phone_gridder_json_result = $this->get_gridder_json_from_post('phone_gridder_json_field', 'phone_gridder_json_field_gzip', $post_id, 'phone');
		if($phone_gridder_json_result === null){
			if($saved_via_ajax){
				return;
			}
			// Gzip OFF: raw field submitted empty means "delete phone layout"
			$raw_phone = isset($_POST['phone_gridder_json_field']) ? wp_unslash($_POST['phone_gridder_json_field']) : null;
			if($raw_phone !== null && trim((string) $raw_phone) === ''){
				update_post_meta($post_id, '_phone_gridder_json', '');
				update_post_meta($post_id, '_phone_gridder_json_preview', '');
			}
			return;
		}
		$phone_gridder_json = $this->normalize_gridder_json_for_save(
			$phone_gridder_json_result['value'],
			$post_id,
			'phone',
			$phone_gridder_json_result['source']
		);
		if($phone_gridder_json === null){
			return;
		}

		// Save to preview/revision posts for WordPress preview functionality
		// Always save the meta data so previews work correctly
		if(!wp_is_post_revision($post_id)){
			// save last meta field value
			$value = get_post_meta( $post_id, '_phone_gridder_json', true );
			$value = wp_slash($value);
			update_post_meta( $post_id, '_phone_gridder_json_last', $value );
		}

		// Update the meta field in the database
		$phone_gridder_json_slash = wp_slash($phone_gridder_json);
		$update_result = update_post_meta( $post_id, '_phone_gridder_json', $phone_gridder_json_slash );
		// Keep custom preview in sync on manual updates so refreshing a preview tab shows latest state
		$preview_result = update_post_meta( $post_id, '_phone_gridder_json_preview', $phone_gridder_json_slash );

	}

	public function gridder_phone_json_metabox_callback( $post ){
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'gridder_phone_json_metabox', 'gridder_phone_json_metabox_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		// htmlspecialchars needed because textarea converts some chars
		$value = get_post_meta( $post->ID, '_phone_gridder_json', true );

		echo '<p><strong>Gridder JSON</strong></p>';
		echo '<textarea id="phone_gridder_json_field" name="phone_gridder_json_field" style="width:100%;height:200px;">';
		echo htmlspecialchars($value);
		echo '</textarea>';

	}

	public function gridder_metabox_callback(){
		require_once( Setup::$dir.'/gridder/markup.php' );
	}

	private function hex_to_rgba($hex, $opacity) {
		// Remove # if present
		$hex = ltrim($hex, '#');
		
		// Convert hex to RGB
		if (strlen($hex) == 3) {
			$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
			$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
			$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
		} else {
			$r = hexdec(substr($hex, 0, 2));
			$g = hexdec(substr($hex, 2, 2));
			$b = hexdec(substr($hex, 4, 2));
		}
		
		return "rgba($r, $g, $b, $opacity)";
	}

	public function gridder_styles() {
		$screen = get_current_screen();
		if ( LTUtility::is_admin_page_with_a_gridder($screen) ) {
			wp_enqueue_style( 'gridder-bootstrap', Setup::$uri.'/assets/bootstrap/css/bootstrap.css', array(), Setup::$ver );
			wp_enqueue_style( 'gridder-application', Setup::$uri.'/gridder/assets/css/gridder.style.css', array(), Setup::$ver );
            
            // Get gridder UI colors
            $frame_color = get_option('gridder_ui_frame_color', '#ffdcff');
            $frame_opacity = get_option('gridder_ui_frame_color_opacity', '100') / 100;
            $row_gutter_color = get_option('gridder_ui_row_gutter_color', '#f7f7f7');
            if ( strtolower( (string) $row_gutter_color ) === '#e5e5e5' ) {
                $row_gutter_color = '#f7f7f7';
            }
            $row_gutter_opacity = get_option('gridder_ui_row_gutter_color_opacity', '100') / 100;
            $selected_row_gutter_color = get_option('gridder_ui_selected_row_gutter_color', '#feb2fe');
            $selected_row_gutter_opacity = get_option('gridder_ui_selected_row_gutter_color_opacity', '100') / 100;
            $column_gutter_color = get_option('gridder_ui_column_gutter_color', '#cafcfc');
            $column_gutter_opacity = get_option('gridder_ui_column_gutter_color_opacity', '100') / 100;
            $center_column_gutter_color = get_option('gridder_ui_center_column_gutter_color', '#ccfcca');
            $center_column_gutter_opacity = get_option('gridder_ui_center_column_gutter_color_opacity', '100') / 100;
            $selected_outline_color = get_option('gridder_ui_selected_outline_color', '#00ffff');
            $selected_outline_opacity = get_option('gridder_ui_selected_outline_color_opacity', '100') / 100;
            $selected_outline_width = get_option('gridder_ui_selected_outline_width', '2');
            $row_dnd_yes_color = get_option('gridder_ui_row_dnd_yes_color', '#00ffff');
            $row_dnd_yes_opacity = get_option('gridder_ui_row_dnd_yes_color_opacity', '100') / 100;
            $row_dnd_no_color = get_option('gridder_ui_row_dnd_no_color', '#ff0000');
            $row_dnd_no_opacity = get_option('gridder_ui_row_dnd_no_color_opacity', '100') / 100;
            $show_row_numbers = get_option('gridder_ui_show_row_numbers', '0');
            $resize_handles_on_hover = get_option('gridder_ui_resize_handles_on_hover', '1');
            $show_stack_drag_handle = get_option('gridder_ui_show_stack_drag_handle', '1');
            $stack_outline_width = get_option('gridder_ui_stack_outline_width', '1');
            $element_outline_color = get_option('gridder_ui_element_outline_color', '#c1c1c1');
            $element_outline_opacity = get_option('gridder_ui_element_outline_color_opacity', '100') / 100;
            $show_image_outline = get_option('gridder_ui_show_image_outline', '0');
            
            // Convert hex to rgba
            $frame_rgba = $this->hex_to_rgba($frame_color, $frame_opacity);
            $row_gutter_rgba = $this->hex_to_rgba($row_gutter_color, $row_gutter_opacity);
            $selected_row_gutter_rgba = $this->hex_to_rgba($selected_row_gutter_color, $selected_row_gutter_opacity);
            $column_gutter_rgba = $this->hex_to_rgba($column_gutter_color, $column_gutter_opacity);
            $selected_outline_rgba = $this->hex_to_rgba($selected_outline_color, $selected_outline_opacity);
            $row_dnd_yes_rgba = $this->hex_to_rgba($row_dnd_yes_color, $row_dnd_yes_opacity);
            $row_dnd_no_rgba = $this->hex_to_rgba($row_dnd_no_color, $row_dnd_no_opacity);
            $element_outline_rgba = $this->hex_to_rgba($element_outline_color, $element_outline_opacity);
            $center_column_gutter_rgba = $this->hex_to_rgba($center_column_gutter_color, $center_column_gutter_opacity);
			// Build custom CSS
			$custom_css = '';

            // Element outline color
            $custom_css .= '
                #gridder-root .guides .element {
                    outline-color: ' . esc_attr($element_outline_rgba) . ' !important;
                }
                
                #gridder-root .guides .element.type-stack:not(.selected) {
                    outline-width: ' . esc_attr($stack_outline_width) . 'px !important;
                    outline-color: ' . esc_attr($element_outline_rgba) . ' !important;
                }
            ';
            
            // Hide outline on images if disabled
            if ($show_image_outline !== '1') {
                $custom_css .= '
                    #gridder-root .guides .element:not(.selected).type-img,
					#gridder-root .guides .element:not(.selected).type-postThumbnail {
                        outline-width: 0 !important;
                    }
                ';
            }

            // Translucent Gridder Header
            $translucent_header = get_option('gridder_ui_translucent_header', '0');
            if ($translucent_header === '1') {
                $custom_css .= '
                    #gridder-root .gridder-header {
                        background-color: rgba(255,255,255,0.8);
                        backdrop-filter: blur(10px);
						-webkit-backdrop-filter: blur(10px);
                    }
                ';
            }

            $custom_css .= '
                /* Gridder UI Custom Colors */
                #gridder-root .guides .top-frame,
                #gridder-root .guides .bottom-frame,
                #gridder-root .guides .left-frame,
                #gridder-root .guides .right-frame,
                #gridder-root .gridder-header .set-frame-btn,
                #gridder-root .gridder-header .set-frame-btn:hover,
                #gridder-root .gridder-header .set-frame-btn:focus {
                    background-color: ' . esc_attr($frame_rgba) . ' !important;
                }
                

                #gridder-root .input-group.group-column-gutter,
                #gridder-root .guides .column-gutter:not(.lay-middle-column),
				#gridder-root .group-column-gutter .choose-mu,
				#gridder-root .group-column-gutter .choose-mu-alt,
				#gridder-root .group-column-gutter .choose-mu-alt:hover,
				#gridder-root .group-column-gutter .choose-mu-alt:focus {
                    background-color: ' . esc_attr($column_gutter_rgba) . ' !important;
                }
                
                #gridder-root .guides .column-gutter.lay-middle-column {
                    background-color: ' . esc_attr($center_column_gutter_rgba) . ' !important;
                }
                
                #gridder-root .input-group.group-row-gutter,
                #gridder-root .guides .rowgutter:not(.rowgutter-has-bg),
				#gridder-root .group-row-gutter .choose-mu,
				#gridder-root .group-row-gutter .choose-mu-alt {
                    background-color: ' . esc_attr($row_gutter_rgba) . ' !important;
                }
                
                #gridder-root .rowgutter.selected-rowgutter:not(.rowgutter-has-bg) {
                    background-color: ' . esc_attr($selected_row_gutter_rgba) . ' !important;
                }
                
                #gridder-root .element.selected {
					outline-style: solid !important;
                    outline-width: ' . esc_attr($selected_outline_width) . 'px !important;
                    outline-color: ' . esc_attr($selected_outline_rgba) . ' !important;
                }
                
                #gridder-root .element.type-hr.selected,
                #gridder-root .element.type-vl.selected {
                    box-shadow: 0px 0px 0px ' . esc_attr($selected_outline_width) . 'px ' . esc_attr($selected_outline_rgba) . ' !important;
                }

				#gridder-root .selected:not(.type-stack) > .grab-top-left {
					border-top-color: ' . esc_attr($selected_outline_rgba) . ' !important;
					border-left-color: ' . esc_attr($selected_outline_rgba) . ' !important;
				}
				
				#gridder-root .selected:not(.type-stack) > .grab-bottom-left {
					border-bottom-color: ' . esc_attr($selected_outline_rgba) . ' !important;
					border-left-color: ' . esc_attr($selected_outline_rgba) . ' !important;
				}
				
				#gridder-root .selected:not(.type-stack) > .grab-top-right {
					border-top-color: ' . esc_attr($selected_outline_rgba) . ' !important;
					border-right-color: ' . esc_attr($selected_outline_rgba) . ' !important;
				}
				
				#gridder-root .selected:not(.type-stack) > .grab-bottom-right {
					border-bottom-color: ' . esc_attr($selected_outline_rgba) . ' !important;
					border-right-color: ' . esc_attr($selected_outline_rgba) . ' !important;
				}
                
                #gridder-root .guides .row-wrap.highlighted-row .row-highlight-container,
				#gridder-root .guides .highlighted-stack-row.lay-stack-element .lay-stack-element-inner {
                    box-shadow: inset 0 0 60px 1px ' . esc_attr($row_dnd_yes_rgba) . ' !important;
                }
                
                #gridder-root .guides .row-wrap.highlighted-row-cant-place-here .row-highlight-container,
				#gridder-root .guides .highlighted-stack-row-cant-place-here.lay-stack-element .lay-stack-element-inner {
                    box-shadow: inset 0 0 60px 1px ' . esc_attr($row_dnd_no_rgba) . ' !important;
                }
                
                /* Hide link toolbar */
                .mce-toolbar-grp.mce-inline-toolbar-grp.mce-container.mce-panel {
                    display: none !important;
                }
            ';
            
            // Hide row numbers if disabled
            if ($show_row_numbers !== '1') {
                $custom_css .= '
                    #gridder-root .lay-row-indicator-bubble-number {
                        display: none !important;
                    }
                ';
            }
            
            // Show resize handles only on hover if enabled
            if ($resize_handles_on_hover === '1') {
                $custom_css .= '
                    #gridder-root .grab {
                        opacity: 0;
                    }
                    
                    #gridder-root .element:hover > .grab {
                        opacity: 1;
                    }
                ';
            }
            
            // Hide stack drag handle if disabled
            if ($show_stack_drag_handle !== '1') {
                $custom_css .= '
                    #gridder-root .stack-drag-handle {
                        display: none !important;
                    }
                ';
            }
            
            wp_add_inline_style( 'gridder-application', $custom_css );

		}
	}

	public function register_lay_iris(){
		// using modified version of iris to prevent scrolling when user drags inside colorpicker
		wp_register_script( 'lay-iris', Setup::$uri.'/assets/js/vendor/iris.js', array( 'jquery-ui-widget', 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch', 'jquery-color' ), '1.0.7' );
	}

	public function gridder_scripts() {
		$screen = get_current_screen();

		if ( LTUtility::is_admin_page_with_a_gridder($screen) ) {

			// ace for html/css modals
			wp_enqueue_script( 'vendor-ace', Setup::$uri.'/assets/js/vendor/ace/ace.js', array(), Setup::$ver );
			wp_enqueue_script( 'vendor-ace-theme-tomorrow-one_dark', Setup::$uri.'/assets/js/vendor/ace/theme-one_dark.js', array('vendor-ace'), Setup::$ver );
			wp_enqueue_script( 'vendor-ace-mode-css', Setup::$uri.'/assets/js/vendor/ace/mode-css.js', array('vendor-ace'), Setup::$ver );
			wp_enqueue_script( 'vendor-highlight', Setup::$uri.'/gridder/assets/js/vendor/highlight.pack.js', array(), Setup::$ver);
			wp_enqueue_script( 'vendor-webfontloader', Setup::$uri.'/gridder/assets/js/vendor/webfontloader.js', array(), Setup::$ver);
			wp_enqueue_script( 'vendor-masonry', Setup::$uri.'/gridder/assets/js/vendor/masonry.min.js', array(), Setup::$ver );
			wp_enqueue_script( 'vendor-sortable', Setup::$uri.'/gridder/assets/js/vendor/sortable.min.js', array(), Setup::$ver );
			wp_enqueue_media();

            wp_enqueue_script( 'gridder-app', Setup::$uri."/gridder/assets/js/gridder.app.min.js", array(
				'jquery', 'vendor-highlight', 'vendor-masonry', 'lay-iris', 'vendor-sortable'
			), Setup::$ver, true );

			// use defaults from 'gridder defaults' options
			$rowgutter = get_option( 'gridder_defaults_row_gutter', LayConstants::gridder_defaults_row_gutter );
			$frame = get_option( 'gridder_defaults_frame', LayConstants::gridder_defaults_frame );
			$topframe = get_option( 'gridder_defaults_topframe', LayConstants::gridder_defaults_topframe );
			$bottomframe = get_option( 'gridder_defaults_bottomframe', LayConstants::gridder_defaults_bottomframe );
			$colgutter = get_option( 'gridder_defaults_column_gutter', LayConstants::gridder_defaults_column_gutter );
			$columncount = get_option( 'gridder_defaults_columncount', LayConstants::gridder_defaults_columncount );

			$phone_rowgutter = get_option( 'phone_gridder_defaults_row_gutter', LayConstants::phone_gridder_defaults_row_gutter );
			$phone_frame = get_option( 'phone_gridder_defaults_frame', LayConstants::phone_gridder_defaults_frame );
			$phone_topframe = get_option( 'phone_gridder_defaults_topframe', LayConstants::phone_gridder_defaults_topframe );
			$phone_bottomframe = get_option( 'phone_gridder_defaults_bottomframe', LayConstants::phone_gridder_defaults_bottomframe );
			$phone_colgutter = get_option( 'phone_gridder_defaults_column_gutter', LayConstants::phone_gridder_defaults_column_gutter );
			$phone_columncount = get_option( 'phone_gridder_defaults_columncount', LayConstants::phone_gridder_defaults_columncount );

			$phoneBreakpoint = MiscOptions::$phone_breakpoint;

			$gridder_json = "";
			$phone_gridder_json = "";
			$id = "";
			$page_custom_css = '';
			$page_custom_desktop_css = '';
			$page_custom_mobile_css = '';
			$lay_hide_site_title = '';
			$lay_hide_menu = '';
			$lay_hide_menu_2 = '';
			$lay_hide_menu_3 = '';
			$lay_hide_menu_4 = '';
			$lay_hide_footer = '';
			$lay_page_overlay_burger_color_enable = '';
			$lay_page_overlay_burger_color = '';
			$lay_menu_color_enable = '';
			$lay_menu_color = '';
			$lay_menu_bar_color_enable = '';
			$lay_menu_bar_color = '';
			$lay_site_title_color_enable = '';
			$lay_site_title_color = '';
			$lay_site_title_mouseover_color_enable = '';
			$lay_site_title_mouseover_color = '';
			$lay_menu_mouseover_color_enable = '';
			$lay_menu_mouseover_color = '';
			$lay_phone_site_title_color_enable = '';
			$lay_phone_site_title_color = '';
			$lay_phone_menu_color_enable = '';
			$lay_phone_menu_color = '';
			$lay_phone_menu_bar_color_enable = '';
			$lay_phone_menu_bar_color = '';
			$lay_phone_page_overlay_burger_color_enable = '';
			$lay_phone_page_overlay_burger_color = '';
			$lay_phone_mobile_menu_burger_color_enable = '';
			$lay_phone_mobile_menu_burger_color = '';

			$post_type = $screen->post_type;
			if( $post_type == 'post' ) {
					$post_type = 'project';
			}
			if( $screen->base == 'term' ) {
					$post_type = 'category';
			}
			// Keep lay_template as is (don't rename it)
			if( $post_type == 'lay_template' ) {
					$post_type = 'lay_template';
			}
            
			if( $screen->id == 'post' || $screen->id == "page" || $screen->id == "lay_template" ){
				global $post;
				$id = $post->ID;
				$gridder_json = get_post_meta( $id, '_gridder_json' );
				$phone_gridder_json = get_post_meta( $id, '_phone_gridder_json' );
				$page_custom_css = get_post_meta( $id, '_lay_page_custom_css', true );
				$page_custom_desktop_css = get_post_meta( $id, '_lay_page_custom_desktop_css', true );
				$page_custom_mobile_css = get_post_meta( $id, '_lay_page_custom_mobile_css', true );
				$lay_hide_site_title = get_post_meta( $id, '_lay_hide_site_title', true );
				$lay_hide_menu = get_post_meta( $id, '_lay_hide_menu', true );
				$lay_hide_menu_2 = get_post_meta( $id, '_lay_hide_menu_2', true );
				$lay_hide_menu_3 = get_post_meta( $id, '_lay_hide_menu_3', true );
				$lay_hide_menu_4 = get_post_meta( $id, '_lay_hide_menu_4', true );
				$lay_hide_footer = get_post_meta( $id, '_lay_hide_footer', true );
				$lay_page_overlay_burger_color_enable = get_post_meta( $id, '_lay_page_overlay_burger_color_enable', true );
				$lay_page_overlay_burger_color = get_post_meta( $id, '_lay_page_overlay_burger_color', true );
				$lay_menu_color_enable = get_post_meta( $id, '_lay_menu_color_enable', true );
				$lay_menu_color = get_post_meta( $id, '_lay_menu_color', true );
				$lay_menu_bar_color_enable = get_post_meta( $id, '_lay_menu_bar_color_enable', true );
				$lay_menu_bar_color = get_post_meta( $id, '_lay_menu_bar_color', true );
				$lay_site_title_color_enable = get_post_meta( $id, '_lay_site_title_color_enable', true );
				$lay_site_title_color = get_post_meta( $id, '_lay_site_title_color', true );
				$lay_site_title_mouseover_color_enable = get_post_meta( $id, '_lay_site_title_mouseover_color_enable', true );
				$lay_site_title_mouseover_color = get_post_meta( $id, '_lay_site_title_mouseover_color', true );
				$lay_menu_mouseover_color_enable = get_post_meta( $id, '_lay_menu_mouseover_color_enable', true );
				$lay_menu_mouseover_color = get_post_meta( $id, '_lay_menu_mouseover_color', true );
				$lay_phone_site_title_color_enable = get_post_meta( $id, '_lay_phone_site_title_color_enable', true );
				$lay_phone_site_title_color = get_post_meta( $id, '_lay_phone_site_title_color', true );
				$lay_phone_menu_color_enable = get_post_meta( $id, '_lay_phone_menu_color_enable', true );
				$lay_phone_menu_color = get_post_meta( $id, '_lay_phone_menu_color', true );
				$lay_phone_menu_bar_color_enable = get_post_meta( $id, '_lay_phone_menu_bar_color_enable', true );
				$lay_phone_menu_bar_color = get_post_meta( $id, '_lay_phone_menu_bar_color', true );
				$lay_phone_page_overlay_burger_color_enable = get_post_meta( $id, '_lay_phone_page_overlay_burger_color_enable', true );
				$lay_phone_page_overlay_burger_color = get_post_meta( $id, '_lay_phone_page_overlay_burger_color', true );
				$lay_phone_mobile_menu_burger_color_enable = get_post_meta( $id, '_lay_phone_mobile_menu_burger_color_enable', true );
				$lay_phone_mobile_menu_burger_color = get_post_meta( $id, '_lay_phone_mobile_menu_burger_color', true );
			}
			else if( $screen->id == 'edit-category' ){
				global $tag;
				if( is_object($tag) ){
					$id = $tag->term_id;
					$gridder_json = get_option( $tag->term_id.'_category_gridder_json', '' );
					$phone_gridder_json = get_option( $tag->term_id.'_phone_category_gridder_json', '' );
					$page_custom_css = get_term_meta( $id, '_lay_page_custom_css', true );
					$page_custom_desktop_css = get_term_meta( $id, '_lay_page_custom_desktop_css', true );
					$page_custom_mobile_css = get_term_meta( $id, '_lay_page_custom_mobile_css', true );
					$lay_hide_site_title = get_term_meta( $id, '_lay_hide_site_title', true );
					$lay_hide_menu = get_term_meta( $id, '_lay_hide_menu', true );
					$lay_hide_menu_2 = get_term_meta( $id, '_lay_hide_menu_2', true );
					$lay_hide_menu_3 = get_term_meta( $id, '_lay_hide_menu_3', true );
					$lay_hide_menu_4 = get_term_meta( $id, '_lay_hide_menu_4', true );
					$lay_hide_footer = get_term_meta( $id, '_lay_hide_footer', true );
					$lay_page_overlay_burger_color_enable = get_term_meta( $id, '_lay_page_overlay_burger_color_enable', true );
					$lay_page_overlay_burger_color = get_term_meta( $id, '_lay_page_overlay_burger_color', true );
					$lay_menu_color_enable = get_term_meta( $id, '_lay_menu_color_enable', true );
					$lay_menu_color = get_term_meta( $id, '_lay_menu_color', true );
					$lay_menu_bar_color_enable = get_term_meta( $id, '_lay_menu_bar_color_enable', true );
					$lay_menu_bar_color = get_term_meta( $id, '_lay_menu_bar_color', true );
					$lay_site_title_color_enable = get_term_meta( $id, '_lay_site_title_color_enable', true );
					$lay_site_title_color = get_term_meta( $id, '_lay_site_title_color', true );
					$lay_site_title_mouseover_color_enable = get_term_meta( $id, '_lay_site_title_mouseover_color_enable', true );
					$lay_site_title_mouseover_color = get_term_meta( $id, '_lay_site_title_mouseover_color', true );
					$lay_menu_mouseover_color_enable = get_term_meta( $id, '_lay_menu_mouseover_color_enable', true );
					$lay_menu_mouseover_color = get_term_meta( $id, '_lay_menu_mouseover_color', true );
					$lay_phone_site_title_color_enable = get_term_meta( $id, '_lay_phone_site_title_color_enable', true );
					$lay_phone_site_title_color = get_term_meta( $id, '_lay_phone_site_title_color', true );
					$lay_phone_menu_color_enable = get_term_meta( $id, '_lay_phone_menu_color_enable', true );
					$lay_phone_menu_color = get_term_meta( $id, '_lay_phone_menu_color', true );
					$lay_phone_menu_bar_color_enable = get_term_meta( $id, '_lay_phone_menu_bar_color_enable', true );
					$lay_phone_menu_bar_color = get_term_meta( $id, '_lay_phone_menu_bar_color', true );
					$lay_phone_page_overlay_burger_color_enable = get_term_meta( $id, '_lay_phone_page_overlay_burger_color_enable', true );
					$lay_phone_page_overlay_burger_color = get_term_meta( $id, '_lay_phone_page_overlay_burger_color', true );
					$lay_phone_mobile_menu_burger_color_enable = get_term_meta( $id, '_lay_phone_mobile_menu_burger_color_enable', true );
					$lay_phone_mobile_menu_burger_color = get_term_meta( $id, '_lay_phone_mobile_menu_burger_color', true );
				}
			}

			$pt_position = get_theme_mod("pt_position", "below-image");
			$pd_position = get_theme_mod("pd_position", "below-image");
			$pd_position_related_to_title = get_theme_mod("pd_position_related_to_title", "below-title");

			$cover_active_in_projects = get_option( 'cover_active_in_projects', "off" );
			$cover_active_in_pages = get_option( 'cover_active_in_pages', "off" );
			$cover_active_in_categories = get_option( 'cover_active_in_categories', "off" );

			// for an unknown reason in rare cases the arrays are saved as associative arrays, so flatten them here
			$cover_individual_project_ids = CoverOptions::get_individual_project_ids();
			$cover_individual_page_ids = CoverOptions::get_individual_page_ids();
			$cover_individual_category_ids = CoverOptions::get_individual_category_ids();

			$misc_options_cover = get_option('misc_options_cover', '');

			$pt_textformat = get_theme_mod("pt_textformat", "_Default");
			if($pt_textformat != "_Default"){
				$pt_textformat = '_'.$pt_textformat;
			}

			$ptags_textformat = get_theme_mod("ptags_textformat", "_Default");
			if($ptags_textformat != "_Default"){
				$ptags_textformat = '_'.$ptags_textformat;
			}
			
			$cover_disable_for_phone = get_option('cover_disable_for_phone', '');
			$activate_project_description = get_option('misc_options_activate_project_description', '');
			
			$product_categories_for_Select = '';
			if( class_exists('WooCommerce') ) {
				$product_categories = get_terms(array(
					'taxonomy' => 'product_cat',
					'hide_empty' => false
				));
				$product_categories_for_Select = array();
				foreach ($product_categories as $term) {
					$product_categories_for_Select[] = array(
						'label' => $term->name,
						'value' => $term->slug
					);
				}
				$product_categories_for_Select = json_encode($product_categories_for_Select);
			}

			$categories = get_categories(array('hide_empty' => false));
			$categories_for_Select = array();
			foreach ($categories as $term) {
				$categories_for_Select[] = array(
					'label' => $term->name,
					'value' => $term->slug
				);
			}
			$categories_for_Select = json_encode($categories_for_Select);


			$news_categories = get_terms(array(
                'taxonomy'   => 'lay_news_category',
                'hide_empty' => false
            ));

            $bg_color_palette = get_option( 'misc_options_colorpalette', '#000, #fff, #f00, #0f0, #00f, #ff0, #0ff, #f0f' );
            $bg_color_palette = Setup::turnTo6DigitHexString($bg_color_palette);

            //turn into array
            $bg_color_palette = explode( ',', $bg_color_palette );

			$simple_parallax = get_option('misc_options_simple_parallax', '') == 'on' && !MiscOptions::$locked;

			$imageHoverExists = is_plugin_active( 'laytheme-imagehover/laytheme-imagehover.php' ) || class_exists('LayThemeImagehover');
			$fullscreenSlider = is_plugin_active( 'laytheme-magneticslides/laytheme-magneticslides.php' ) || class_exists('LayThemeMagneticSlides');

			$plugins = array(
				'carousel' => is_plugin_active( 'laytheme-carousel/laytheme-carousel.php' ) || class_exists('LayThemeCarousel'),
				'imageHover' => $imageHoverExists,
                'fullscreenSlider' => $fullscreenSlider,
				'qTranslateX' => is_plugin_active( 'qtranslate-x/qtranslate.php' ) || is_plugin_active('qtranslate-xt-master/qtranslate.php') || class_exists('QTX_Translator'),
			);

			// TODO: move to plugins
			$lightboxActiveHere = false;
			$lightboxActiveForPhone = false;
			if( class_exists('LayThemeLightbox') ){
				$lightboxActiveHere = LayThemeLightbox::is_active_in_current_admin_page();
				$lightboxActiveForPhone = get_option( 'lightbox_activate_on_phone', '' ) == 'on' ? true : false;
			}

            $lightboxPluginActive = class_exists('LayThemeLightbox');

			$woocommerce_active = class_exists( 'WooCommerce' );
			$product_tags = $woocommerce_active ? json_encode(Lay_WooCommerce::$product_tags) : '';
			$product_categories = $woocommerce_active ? json_encode(Lay_WooCommerce::$product_categories) : '';

			// $categories_for_sort = array();
			// foreach( $categories as $cat ) {
			// 	$obj = new stdClass();
			// 	$obj->label = $cat->name;
			// 	$obj->value = $cat->slug;
			// 	$categories_for_sort []= $obj;
			// }

			$image_loading = get_option('misc_options_image_loading', 'on');
			if( $image_loading == 'on' ) {
				$image_loading = 'lazy_load';
			} else if ($image_loading == '') {
				$image_loading = 'instant_load';
			}

			$laycarousel_showCaptions = get_option( 'laycarousel_showCaptions', "on" ) == "on" ? true : false;

			$spaces_mu = get_option( 'gridder_spaces_mu', LayConstants::gridder_spaces_mu );
			$offsets_mu = get_option( 'gridder_offsets_mu', LayConstants::gridder_offsets_mu );

            if( MiscOptions::$locked ) {
                $simple_parallax = '';
                $misc_options_cover = '';
                $activate_project_description = '';
            }

            $default_category_id = get_option('default_category');
            $default_product_category_id = get_option('default_product_cat');
            
            // Get saved phone gridder active state
            $phone_gridder_active_option_name = 'phone_gridder_active_' . $post_type . '_' . $id;
            $phone_gridder_active = get_option($phone_gridder_active_option_name, 'false');
            $use_custom_mute_unmute_icons = get_option( 'misc_options_use_custom_mute_unmute_icons', '' ) == 'on' ? '1' : '';
            $custom_muted_icon = get_template_directory_uri().'/frontend/assets/img/volume-off.svg';
            $custom_unmuted_icon = get_template_directory_uri().'/frontend/assets/img/volume-on.svg';
            $custom_muted_icon_id = get_option( 'misc_options_muted_state_icon', '' );
            $custom_unmuted_icon_id = get_option( 'misc_options_unmuted_state_icon', '' );
            if( $custom_muted_icon_id != '' ){
                $icon = wp_get_attachment_image_src( $custom_muted_icon_id, 'full' );
                if( is_array($icon) ){
                    $custom_muted_icon = $icon[0];
                }
            }
            if( $custom_unmuted_icon_id != '' ){
                $icon = wp_get_attachment_image_src( $custom_unmuted_icon_id, 'full' );
                if( is_array($icon) ){
                    $custom_unmuted_icon = $icon[0];
                }
            }
            
            // https://wp-mix.com/wordpress-difference-between-home_url-site_url/
			// need to use get_site_url here instead of get_home_url
			wp_localize_script( 'gridder-app', 'passedData', array(
                'gridder_buttons' => get_option('misc_options_gridder_buttons', '{}'),
                'gridder_top_buttons' => get_option('misc_options_gridder_top_buttons', '{}'),
                'gridder_action_buttons' => get_option('misc_options_gridder_action_buttons', '{}'),
				'lay_element_layers_window_expanded' => get_option('lay_element_layers_window_expanded', 'true'),
				'lay_element_layers_window_position' => get_option('lay_element_layers_window_position', json_encode(array('x' => 200, 'y' => 300))),
				'spaces_mu' => $spaces_mu,
				'offsets_mu' => $offsets_mu,
				// Enabled by default when option not yet saved
				'enableLockPositionInElementRightClickMenu' => get_option('misc_options_enable_lock_position_in_element_right_click_menu', 'on'),
				'enableLockEditingInElementRightClickMenu' => get_option('misc_options_enable_lock_editing_in_element_right_click_menu', 'on'),
				'woocommerce_active' => $woocommerce_active,
				'table_config' => get_option('lay_table_config', array()),
				'accordion_config' => get_option('lay_accordion_config', array()),
				'lay_scroll_animation_presets' => get_option('lay_scroll_animation_presets', array()),
				'lay_scroll_animation_presets_nonce' => wp_create_nonce('lay_scroll_animation_presets'),
				'ptags_position' => get_theme_mod('ptags_position', 'below-title'),
				'table_column_widths' => get_option('lay_table_column_widths', array()),
				'product_tags' => $product_tags,
				'product_categories' => $product_categories,
				'default_category_id' => $default_category_id,
                'default_product_category_id' => $default_product_category_id,
				'parallax' => $simple_parallax,
				'pd_position' => $pd_position,
				'pd_position_related_to_title' => $pd_position_related_to_title,
				'pd_force_textformat' => get_theme_mod( 'pd_force_textformat', false ),
				'pd_force_textformat_select' => get_theme_mod( 'pd_force_textformat_select', 'Default' ),
				'pt_position' => $pt_position,
				'gridder_json' => $gridder_json, // TODO: necessary?
				'phone_gridder_json' => $phone_gridder_json, // TODO: necessary?
				'templateURI' => Setup::$uri,
				'siteUrl' => get_site_url(),
				'screenID' => $screen->id,
				'ajaxUrl' => admin_url( 'admin-ajax.php' ) ,
				'id' => $id,
				'screenId' => $screen->id,
				'screenBase' => $screen->base, // TODO: necessary?
				'phoneBreakpoint' => $phoneBreakpoint,
				'phoneGridderActive' => $phone_gridder_active,
				'phoneLayoutActive' => MiscOptions::$phoneLayoutActive,
				'templatesActive' => LayTemplates::$isActive,
				'postType' => $post_type,
				'cover_active_in_projects' => $cover_active_in_projects,
				'cover_active_in_pages' => $cover_active_in_pages,
				'cover_active_in_categories' => $cover_active_in_categories,
				'cover_individual_project_ids' => $cover_individual_project_ids,
				'cover_individual_page_ids' => $cover_individual_page_ids,
				'cover_individual_category_ids' => $cover_individual_category_ids,
				'cover_disable_for_phone' => $cover_disable_for_phone,
				'misc_options_cover' => $misc_options_cover,
				'pt_textformat' => $pt_textformat,
				'ptags_textformat' => $ptags_textformat,
				'sizes' => Setup::$sizes,
				'activateProjectDescription' => $activate_project_description,
				'categories' => $categories,
				'categories_for_Select' => $categories_for_Select,
				'product_categories_for_Select' => $product_categories_for_Select,
				'news_categories' => $news_categories,
				'phoneLayoutActive' => MiscOptions::$phoneLayoutActive,
				'bgColorPalette' => $bg_color_palette,
				'plugins' => $plugins,
				'image_loading' => $image_loading,
				'textformats' => FormatsManager::$customFormats,
				'laycarousel_showCaptions' => $laycarousel_showCaptions,
				'news_active' => Gridder::$active_for_news,
				'enablePlacefreelyForTexts' => get_option( 'misc_options_enable_placefreely_for_texts', '' ),
				'gridderDefaults' => array(
					'colCount' => $columncount,
					'colGutter' => $colgutter,
					'colGutterMu' => get_option( 'gridder_defaults_column_gutter_mu', LayConstants::gridder_defaults_column_gutter_mu ),
					'frameMargin' => $frame,
					'leftFrame' => get_option( 'gridder_defaults_frame_left', LayConstants::gridder_defaults_frame_left ),
					'rightFrame' => get_option( 'gridder_defaults_frame_right', LayConstants::gridder_defaults_frame_right ),
					'topFrameMargin' => $topframe,
					'bottomFrameMargin' => $bottomframe,
					'rowGutter' => $rowgutter,
					'rowGutterMu' => Gridder::$rowgutter_mu,
					'topFrameMu' => Gridder::$topframe_mu,
					'frameMu' => Gridder::$frame_mu,
					'bottomFrameMu' => Gridder::$bottomframe_mu,
					'phoneRowGutter' => $phone_rowgutter,
					'phoneFrame' => $phone_frame,
					'phoneLeftFrame' => get_option( 'phone_gridder_defaults_frame_left', LayConstants::phone_gridder_defaults_frame_left ),
					'phoneRightFrame' => get_option( 'phone_gridder_defaults_frame_right', LayConstants::phone_gridder_defaults_frame_right ),
					'phoneTopFrame' => $phone_topframe,
					'phoneBottomFrame' => $phone_bottomframe,
					'phoneColGutter' => $phone_colgutter,
					'phoneColGutterMu' => get_option( 'phone_gridder_defaults_column_gutter_mu', LayConstants::phone_gridder_defaults_column_gutter_mu ),
					'phoneColCount' => $phone_columncount,
					'phoneTopFrameMu' => get_option( 'phone_gridder_defaults_topframe_mu', LayConstants::phone_gridder_defaults_topframe_mu ),
					'phoneBottomFrameMu' => get_option( 'phone_gridder_defaults_bottomframe_mu', LayConstants::phone_gridder_defaults_bottomframe_mu ),
					'phoneRowGutterMu' => Gridder::$phone_rowgutter_mu,
					'phoneFrameMu' => get_option( 'phone_gridder_defaults_frame_mu', LayConstants::phone_gridder_defaults_frame_mu ),
					'horizontalGridSpaceIn' => get_option('gridder_horizontalgrid_space_in_default', 'px'),
					'horizontalGridSpace' => get_option('gridder_horizontalgrid_space_default', 20),
				),
				'lightboxActiveHere' => $lightboxActiveHere,
				'lightboxActiveForPhone' => $lightboxActiveForPhone,
				'lightboxPluginActive' => $lightboxPluginActive,
				'layersVisibility' => get_option('lay_layers_visibility', false),
				'sidebarMinimized' => get_option('lay_sidebar_minimized', 'false'),
				'guidesVisibility' => get_option('lay_guides_visibility', true),
				'lay_show_link_imagehover_setting' => get_option('lay_show_link_imagehover_setting', false),
				'connectTopBottomFrame' => get_option('lay_connect_top_bottom_frame', false),
				'connectLeftRightFrame' => get_option('lay_connect_left_right_frame', true),
				'locked' => MiscOptions::$locked,
				'stickytopDefault' => get_option( 'gridder_stickytop_default', 20 ),
				'fixedHeightDefault' => get_option( 'gridder_fixed_height_default', '100svh - 40px' ),
				'customAspectRatioWidthDefault' => (int) get_option( 'gridder_custom_aspect_ratio_width_default', 16 ),
				'customAspectRatioHeightDefault' => (int) get_option( 'gridder_custom_aspect_ratio_height_default', 9 ),
				'customAspectRatioPhoneWidthDefault' => (int) get_option( 'gridder_custom_aspect_ratio_phone_width_default', 16 ),
				'customAspectRatioPhoneHeightDefault' => (int) get_option( 'gridder_custom_aspect_ratio_phone_height_default', 9 ),
				'fullscreenslider_active_here' => class_exists('LayThemeMagneticSlides') && method_exists('LayThemeMagneticSlides', 'isActiveHere') ? LayThemeMagneticSlides::isActiveHere($id, $post_type) : false,
				'fullscreenslider_fit_browser' => get_option( 'ms_fit_browser', '' ),
				'fullscreenslider_fit_browser_maxdim' => get_option( 'ms_fit_browser_maxdim', 90 ),
				'imagehover_imageposition' => get_option( 'imagehover_imageposition', 'followmousex' ),
				'enableUndoRedo' => Gridder::$undoRedoEnabled ? 'on' : '',
				'gridder_ui_close_modal_on_background_click' => get_option( 'gridder_ui_close_modal_on_background_click', '0' ),
				'page_custom_css' => $page_custom_css,
				'page_custom_desktop_css' => $page_custom_desktop_css,
				'page_custom_mobile_css' => $page_custom_mobile_css,
				'lay_page_css_nonce' => wp_create_nonce( 'lay_page_css_nonce' ),
				'lay_hide_site_title' => $lay_hide_site_title,
				'lay_hide_menu' => $lay_hide_menu,
				'lay_hide_menu_2' => isset( $lay_hide_menu_2 ) ? $lay_hide_menu_2 : '',
				'lay_hide_menu_3' => isset( $lay_hide_menu_3 ) ? $lay_hide_menu_3 : '',
				'lay_hide_menu_4' => isset( $lay_hide_menu_4 ) ? $lay_hide_menu_4 : '',
				'lay_hide_footer' => $lay_hide_footer,
				'menu_amount' => intval( get_option( 'misc_options_menu_amount', 1 ) ) ?: 1,
				'lay_page_overlay_burger_color_enable' => isset( $lay_page_overlay_burger_color_enable ) ? $lay_page_overlay_burger_color_enable : '',
				'lay_page_overlay_burger_color' => isset( $lay_page_overlay_burger_color ) ? $lay_page_overlay_burger_color : '',
				'lay_menu_color_enable' => isset( $lay_menu_color_enable ) ? $lay_menu_color_enable : '',
				'lay_menu_color' => isset( $lay_menu_color ) ? $lay_menu_color : '',
				'lay_menu_bar_color_enable' => isset( $lay_menu_bar_color_enable ) ? $lay_menu_bar_color_enable : '',
				'lay_menu_bar_color' => isset( $lay_menu_bar_color ) ? $lay_menu_bar_color : '',
				'lay_site_title_color_enable' => isset( $lay_site_title_color_enable ) ? $lay_site_title_color_enable : '',
				'lay_site_title_color' => isset( $lay_site_title_color ) ? $lay_site_title_color : '',
				'lay_site_title_mouseover_color_enable' => isset( $lay_site_title_mouseover_color_enable ) ? $lay_site_title_mouseover_color_enable : '',
				'lay_site_title_mouseover_color' => isset( $lay_site_title_mouseover_color ) ? $lay_site_title_mouseover_color : '',
				'lay_menu_mouseover_color_enable' => isset( $lay_menu_mouseover_color_enable ) ? $lay_menu_mouseover_color_enable : '',
				'lay_menu_mouseover_color' => isset( $lay_menu_mouseover_color ) ? $lay_menu_mouseover_color : '',
				'lay_phone_site_title_color_enable' => isset( $lay_phone_site_title_color_enable ) ? $lay_phone_site_title_color_enable : '',
				'lay_phone_site_title_color' => isset( $lay_phone_site_title_color ) ? $lay_phone_site_title_color : '',
				'lay_phone_menu_color_enable' => isset( $lay_phone_menu_color_enable ) ? $lay_phone_menu_color_enable : '',
				'lay_phone_menu_color' => isset( $lay_phone_menu_color ) ? $lay_phone_menu_color : '',
				'lay_phone_menu_bar_color_enable' => isset( $lay_phone_menu_bar_color_enable ) ? $lay_phone_menu_bar_color_enable : '',
				'lay_phone_menu_bar_color' => isset( $lay_phone_menu_bar_color ) ? $lay_phone_menu_bar_color : '',
				'lay_phone_page_overlay_burger_color_enable' => isset( $lay_phone_page_overlay_burger_color_enable ) ? $lay_phone_page_overlay_burger_color_enable : '',
				'lay_phone_page_overlay_burger_color' => isset( $lay_phone_page_overlay_burger_color ) ? $lay_phone_page_overlay_burger_color : '',
				'lay_phone_mobile_menu_burger_color_enable' => isset( $lay_phone_mobile_menu_burger_color_enable ) ? $lay_phone_mobile_menu_burger_color_enable : '',
				'lay_phone_mobile_menu_burger_color' => isset( $lay_phone_mobile_menu_burger_color ) ? $lay_phone_mobile_menu_burger_color : '',
				'page_settings_default_mobile_menu_burger_color' => sanitize_hex_color( get_theme_mod( 'mobile_menu_burger_icon_color', '#000000' ) ) ?: '#000000',
				'page_overlay_feature_enabled' => get_option( 'misc_options_activate_overlay_feature', '' ) === 'on',
				'menu_bar_not_hidden' => get_theme_mod( 'mobile_hide_menubar', 0 ) != 1,
				'page_settings_default_menu_color' => sanitize_hex_color( get_theme_mod( 'nav_color', '#000000' ) ) ?: '#000000',
				'page_settings_default_menu_bar_color' => sanitize_hex_color( get_theme_mod( 'navbar_color', '#ffffff' ) ) ?: '#ffffff',
				'page_settings_default_site_title_color' => sanitize_hex_color( get_theme_mod( 'st_color', '#000000' ) ) ?: '#000000',
				'page_settings_default_site_title_mouseover_color' => sanitize_hex_color( get_theme_mod( 'stmouseover_color', '#000000' ) ) ?: '#000000',
				'page_settings_default_menu_mouseover_color' => sanitize_hex_color( get_theme_mod( 'navmouseover_color', '#000000' ) ) ?: '#000000',
				'lay_page_settings_nonce' => wp_create_nonce( 'lay_page_settings_nonce' ),
				'useCustomMuteUnmuteIcons' => $use_custom_mute_unmute_icons,
				'customMutedStateIcon' => $custom_muted_icon,
				'customUnmutedStateIcon' => $custom_unmuted_icon,
				'rowBgVideoUnmuteButtonPositionDefault' => get_option('gridder_row_bg_video_unmute_button_position_default', 'bottom-right')
			) );
		}
	}

}

$gridder = new Gridder();
