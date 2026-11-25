<?php
class FontManager{
	private static $google_preconnects = array();
	private static $google_links = array();

	public static $customFonts = NULL;
	public static $originalFonts;

	/**
	 * Sanitize webfont link/script tags by replacing curly quotes with regular quotes
	 * Handles both double quotes (" and ") and single quotes (' and ')
	 */
	private static function sanitize_webfont_tag( $tag ) {
		if ( empty( $tag ) ) {
			return $tag;
		}
		
		// Replace curly double quotes (U+201C and U+201D) with regular double quotes
		$tag = str_replace( array( "\xE2\x80\x9C", "\xE2\x80\x9D" ), '"', $tag );
		
		// Replace curly single quotes (U+2018 and U+2019) with regular single quotes
		$tag = str_replace( array( "\xE2\x80\x98", "\xE2\x80\x99" ), "'", $tag );
		
		return $tag;
	}

	/**
	 * Output a safe, valid <link> tag for webfonts from user-provided HTML.
	 * Extracts href (and optional rel) and rebuilds the tag so malformed input
	 * (e.g. missing closing quote in href) cannot break the page or leak following content.
	 */
	private static function render_safe_link_tag( $tag ) {
		$tag = self::sanitize_webfont_tag( $tag );
		if ( trim( $tag ) === '' ) {
			return '';
		}
		// Extract href even from malformed tags (e.g. href="...Neuzeit rel="stylesheet">)
		if ( preg_match( '/href\s*=\s*["\']?([^"\'\s>]+)/i', $tag, $m ) ) {
			$href = trim( $m[1] );
			if ( $href !== '' ) {
				$href = esc_url( $href );
				if ( $href !== '' ) {
					return '<link rel="stylesheet" href="' . esc_attr( $href ) . '">' . "\n";
				}
			}
		}
		return '';
	}

	private static function extract_attribute( $tag, $attribute ) {
		$pattern = sprintf("/%s\\s*=\\s*(?:\"([^\"]*)\"|'([^']*)'|([^\\s>]+))/i", preg_quote( $attribute, '/' ) );
		if ( preg_match( $pattern, $tag, $matches ) ) {
			if ( ! empty( $matches[1] ) ) {
				return html_entity_decode( $matches[1] );
			}
			if ( ! empty( $matches[2] ) ) {
				return html_entity_decode( $matches[2] );
			}
			if ( ! empty( $matches[3] ) ) {
				return html_entity_decode( $matches[3] );
			}
		}
		return '';
	}

	private static function render_google_embed( $embed ) {
		$embed = trim( $embed );
		if ( $embed === '' ) {
			return '';
		}
		$matches = array();
		preg_match_all( '/<link\b[^>]*>/i', $embed, $matches );
		if ( empty( $matches[0] ) ) {
			return $embed;
		}
		$output = '';
		foreach ( $matches[0] as $tag ) {
			$rel = strtolower( self::extract_attribute( $tag, 'rel' ) );
			$href = self::extract_attribute( $tag, 'href' );
			if ( $rel === 'preconnect' ) {
				if ( $href === '' || isset( self::$google_preconnects[ $href ] ) ) {
					continue;
				}
				self::$google_preconnects[ $href ] = true;
				$output .= $tag . "\n";
				continue;
			}
			$key = $rel . '|' . $href;
			if ( $href !== '' && isset( self::$google_links[ $key ] ) ) {
				continue;
			}
			if ( $href !== '' ) {
				self::$google_links[ $key ] = true;
			}
			$output .= $tag . "\n";
		}
		return $output;
	}

	public function __construct(){

		$customFontsJSON = get_option('fontmanager_json', false);
		if($customFontsJSON){
			FontManager::$customFonts = json_decode($customFontsJSON, true);
		}

		FontManager::$originalFonts = array(
			'Andale Mono' => 'andale mono,times,serif',
			'Arial' => 'arial,helvetica,sans-serif',
			'Arial Black' => 'arial black,avant garde,sans-serif',
			'Book Antiqua' => 'book antiqua,palatino,serif',
			'Comic Sans MS' => 'comic sans ms,sans-serif',
			'Courier New' => 'courier new,courier,monospace',
			'Georgia' => 'georgia,palatino,serif',
			'Helvetica (only preinstalled on MacOS / iOS)' => 'helvetica,sans-serif',
			'Helvetica Neue (only preinstalled on MacOS / iOS)' => 'helvetica neue,sans-serif',
			'Impact' => 'impact,chicago,sans-serif',
			'Tahoma' => 'tahoma,arial,helvetica,sans-serif',
			'Terminal' => 'terminal,monaco,monospace',
			'Times New Roman' => 'times new roman,times,serif',
			'Trebuchet MS' => 'trebuchet ms,geneva,sans-serif',
			'Verdana' => 'verdana,geneva,sans-serif'
		);

		add_action( 'admin_menu', array($this, 'font_setup_menu'), 11 );
		add_action( 'admin_init', array($this, 'fontmanager_json_settings_api_init') );

		add_action( 'admin_enqueue_scripts', array( $this, 'fontmanager_styles' ) );
		
		add_action( 'admin_init', array( $this, 'register_scripts' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'fontmanager_scripts' ), 9 );
		add_action( 'wp_ajax_lay_save_fontmanager', array( $this, 'ajax_save_fontmanager' ) );
		
		add_filter( 'upload_mimes', array( $this, 'add_files_to_allowed_mimes' ), 10, 1 );
        // add_filter( 'wp_check_filetype_and_ext', array( $this, 'add_multiple_mime_types'), 99, 3 );

		add_filter( 'wp_check_filetype_and_ext', array( $this, 'update_mime_types' ), 10, 3 );


		add_action( 'admin_enqueue_scripts', array( $this, 'backend_add_attachment_webfonts' ), 9 );
		add_action( 'admin_head', array( $this, 'backend_add_external_webfonts' ), 9 );

		add_action( 'wp_head', array( $this, 'frontend_add_attachment_webfonts' ) );
		add_action( 'wp_head', array( $this, 'frontend_add_external_webfonts' ) );

		add_action( 'admin_footer', array( $this, 'print_fontmanager_JSON' ) );
		add_action( 'mce_external_plugins', array( $this, 'tinymce_add_fontloader') );
		add_filter( 'tiny_mce_before_init', array( $this, 'tinymce_add_fontmanager_fonts') );

	}

	// add link tags to gridder
	public function backend_add_external_webfonts(){
		$screen = get_current_screen();
		// `$screen->id` is built from the parent menu's translated title (e.g.
		// `lay-options_page_…` in EN, `lay-optionen_page_…` in DE), so a strict
		// equality check breaks under translation. Match on the slug substring.
		if ( $screen->id == 'toplevel_page_manage-textformats' || strpos($screen->id, 'manage-webfonts') !== false || strpos($screen->id, 'manage-buttons') !== false || LTUtility::is_admin_page_with_a_gridder($screen) ) {
			if (is_array(FontManager::$customFonts)) {
				for($i=0; $i<count(FontManager::$customFonts); $i++){
					if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "link" ){
						echo self::render_safe_link_tag( FontManager::$customFonts[$i]['link'] );
					}
					if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "script" ){
						echo self::sanitize_webfont_tag( FontManager::$customFonts[$i]['script'] );
					}
					if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "google" ){
						echo self::render_google_embed( FontManager::$customFonts[$i]['embed'] );
					}
				}
			}
		}
	}

	// add font face css to gridder
	public function backend_add_attachment_webfonts(){
		$screen = get_current_screen();
		$screen_id = $screen->id;
		if( strpos( $screen_id, 'manage-webfonts' ) !== false || strpos( $screen_id, 'manage-textformats' ) !== false || strpos( $screen_id, 'manage-buttons' ) !== false || LTUtility::is_admin_page_with_a_gridder($screen) ){
			$newfontsCSS = '';
			if (is_array(FontManager::$customFonts)) {
				for($i=0; $i<count(FontManager::$customFonts); $i++){
					if(!array_key_exists('type', FontManager::$customFonts[$i]) || FontManager::$customFonts[$i]['type'] == "attachment" ){
						$url = FontManager::$customFonts[$i]['url'];
						$url = LTUtility::filterURL($url);
						if( array_key_exists('id', FontManager::$customFonts[$i]) ){
							$temp_url = wp_get_attachment_url(FontManager::$customFonts[$i]['id']);
                            if( $temp_url != '' && $temp_url != false ) {
                                $url = $temp_url;
                                // somehow wp_get_attachment_url does not account for whether I'm on http or https
							    // maybe this is a wordpress bug
                                $url = LTUtility::filterURL($url);
                            }
						}

						$fileformat = 'woff';
						if(array_key_exists('filetype', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['filetype'] != ''){
							$fileformat = FontManager::$customFonts[$i]['filetype'];
						}

						$newfontsCSS .= '@font-face{ font-family: "'.FontManager::$customFonts[$i]['fontname'].'"; src: url("'.$url.'") format("'.$fileformat.'"); '.(get_option('misc_options_swap_webfonts', 'on') == 'on' ? 'font-display: swap;' : '').' } ';
					}
				}
			}
			wp_add_inline_style( 'wp-admin', $newfontsCSS );
		}
	}

	public function frontend_add_external_webfonts(){
		if (is_array(FontManager::$customFonts)) {
			for($i=0; $i<count(FontManager::$customFonts); $i++){
				if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "link" ){
					echo self::render_safe_link_tag( FontManager::$customFonts[$i]['link'] );
				}
				if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "script" ){
					echo self::sanitize_webfont_tag( FontManager::$customFonts[$i]['script'] );
				}
				if(array_key_exists('type', FontManager::$customFonts[$i]) && FontManager::$customFonts[$i]['type'] == "google" ){
					echo self::render_google_embed( FontManager::$customFonts[$i]['embed'] );
				}
			}
		}
	}

	public function frontend_add_attachment_webfonts(){
		$preload_markup = '';
		$seen_preload_urls = array();
		$newfontsCSS =
		'<!-- webfonts -->
		<style type="text/css">';
		if (is_array(FontManager::$customFonts)) {
			for($i=0; $i<count(FontManager::$customFonts); $i++){
				if(!array_key_exists('type', FontManager::$customFonts[$i]) || FontManager::$customFonts[$i]['type'] == "attachment" ){
					$url = FontManager::$customFonts[$i]['url'];
					$url = LTUtility::filterURL($url);
					if( array_key_exists('id', FontManager::$customFonts[$i]) ){
						$temp_url = wp_get_attachment_url(FontManager::$customFonts[$i]['id']);
                        if( $temp_url != '' && $temp_url != false ) {
                            $url = $temp_url;
						    // somehow wp_get_attachment_url does not account for whether I'm on http or https
						    // maybe this is a wordpress bug
						    $url = LTUtility::filterURL($url);
                        }

					}

					$fileformat = 'woff';
					if(array_key_exists('filetype', FontManager::$customFonts[$i]) && trim(FontManager::$customFonts[$i]['filetype']) != ''){
						$fileformat = FontManager::$customFonts[$i]['filetype'];
					}
					$fileformat = strtolower( trim( $fileformat ) );

					$newfontsCSS .= '@font-face{ font-family: "'.FontManager::$customFonts[$i]['fontname'].'"; src: url("'.$url.'") format("'.$fileformat.'"); '.(get_option('misc_options_swap_webfonts', 'on') == 'on' ? 'font-display: swap;' : '').' } ';

					// Preload local fonts for faster first render.
					$preload_type = '';
					if ( $fileformat === 'woff2' ) {
						$preload_type = 'font/woff2';
					} else if ( $fileformat === 'woff' ) {
						$preload_type = 'font/woff';
					} else if ( $fileformat === 'ttf' || $fileformat === 'truetype' ) {
						$preload_type = 'font/ttf';
					} else if ( $fileformat === 'otf' || $fileformat === 'opentype' ) {
						$preload_type = 'font/otf';
					}
					$font_preload_enabled = array_key_exists('preloadFont', FontManager::$customFonts[$i]) ? FontManager::$customFonts[$i]['preloadFont'] : false;
					
                    $font_preload_enabled = ($font_preload_enabled === true || $font_preload_enabled === 'true' || $font_preload_enabled == 1);
					if ( $font_preload_enabled && $preload_type !== '' && !in_array( $url, $seen_preload_urls, true ) ) {
						$seen_preload_urls[] = $url;
						$preload_markup .= '<link rel="preload" href="'.esc_url($url).'" as="font" type="'.$preload_type.'" crossorigin>' . "\n";
					}

				}
			}
		}
		$newfontsCSS .= '</style>';
		echo $preload_markup . $newfontsCSS;
	}

	public function tinymce_add_fontmanager_fonts( $initArray ) {
		// $original = 'Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats';
		$original = '';

		foreach (FontManager::$originalFonts as $key => $value) {
			$original .= $key.'='.$value.';';
		}

		$newfonts = '';

		if(is_array(FontManager::$customFonts)){
			for($i=0; $i<count(FontManager::$customFonts); $i++){
				if(!array_key_exists('type', FontManager::$customFonts[$i]) || FontManager::$customFonts[$i]['type'] == "attachment" ){
					$newfonts .= FontManager::$customFonts[$i]['fontname'].'='.FontManager::$customFonts[$i]['fontname'].';';
				}
			else if(FontManager::$customFonts[$i]['type'] == "link" || FontManager::$customFonts[$i]['type'] == "script" || FontManager::$customFonts[$i]['type'] == "google"){
				$css = FontManager::$customFonts[$i]['css'];

				// Sanitize curly quotes first (convert to regular quotes)
				$css = self::sanitize_webfont_tag($css);

				$css = str_replace('font-family: ', '', $css);
				$css = str_replace('font-family:', '', $css);
				$css = str_replace(';', '', $css);

				// Escape quotes in CSS value to prevent JavaScript syntax errors
				// WordPress outputs TinyMCE config directly in JavaScript, so quotes need to be escaped
				$css = str_replace('"', '\\"', $css);

				$newfonts .= FontManager::$customFonts[$i]['fontname'].'='.$css.';';
			}
			}
		}

		$initArray['font_formats'] = $newfonts.$original;

		return $initArray;
	}

	public function tinymce_add_fontloader( $plugins ) {
		$plugins['fontloader'] = Setup::$uri.'/fontmanager/assets/js/tinymce_plugin/tinymce_fontloader.js?ver='.Setup::$ver;
		return $plugins;
	}

	// needed for "repositionAfterWebfontsLoaded"
	public function print_fontmanager_JSON(){
		$screen = get_current_screen();
		if ( $screen->id == 'post' || $screen->id == 'page' || $screen->id == 'edit-category' || $screen->id == 'product' ||  LTUtility::is_admin_page_with_a_gridder($screen) ) {
			// since sometimes webfonts urls can be http or https, we will do this:
			$is_ssl = is_ssl();
			$fontmanager_json = get_option('fontmanager_json');
			if($is_ssl){
				$fontmanager_json = str_replace('http:', 'https:', $fontmanager_json);
			} else {
				$fontmanager_json = str_replace('https:', 'http:', $fontmanager_json);
			}

			echo '<textarea style="display: none;" id="fontmanager_json" name="fontmanager_json">'.$fontmanager_json.'</textarea>';
		}
	}

    // public static function add_multiple_mime_types( $check, $file, $filename ) {
    //     if ( empty( $check['ext'] ) && empty( $check['type'] ) ) {
    //         foreach ( FontManager::get_allowed_mime_types() as $mime ) {
    //             remove_filter( 'wp_check_filetype_and_ext', array( $this, 'add_multiple_mime_types' ), 99 );
    //             $mime_filter = function($mimes) use ($mime) {
    //                 return array_merge($mimes, $mime);
    //             };

    //             add_filter('upload_mimes', $mime_filter, 99);
    //             $check = wp_check_filetype_and_ext( $file, $filename, $mime );
    //             remove_filter('upload_mimes', $mime_filter, 99);
    //             add_filter( 'wp_check_filetype_and_ext', array( $this, 'add_multiple_mime_types' ), 99, 3 );
    //             if ( ! empty( $check['ext'] ) || ! empty( $check['type'] ) ) {
    //                 return $check;
    //             }
    //         }
    //     }

    //     return $check;
    // }


    // public static function get_allowed_mime_types(){
    //     return [
    //         [ 'woff2' => 'font/woff2' ],
    //         [ 'woff2' => 'application/font-woff2' ],
    //         [ 'woff2' => 'application/x-font-woff2' ],
    //         [ 'woff' => 'font/woff' ],
    //         [ 'woff' => 'application/font-woff' ],
    //         [ 'woff' => 'application/x-font-woff' ],
    //     ];
    // }

	public function add_files_to_allowed_mimes( $mimes ) {
		$mimes['woff']  = 'font/woff';
		$mimes['woff2'] = 'font/woff2';
		$mimes['ttf']   = 'font/ttf';
		$mimes['svg']   = 'image/svg+xml';
		$mimes['eot']   = 'application/vnd.ms-fontobject';
		$mimes['otf']   = 'font/otf';

		return $mimes;
	}

	public function update_mime_types( $defaults, $file, $filename ) {
		if ( 'ttf' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'font/ttf';
			$defaults['ext']  = 'ttf';
		}

        if ( 'woff' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'font/woff';
			$defaults['ext']  = 'woff';
		}

        if ( 'woff2' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'font/woff2';
			$defaults['ext']  = 'woff2';
		}

		if ( 'otf' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'font/otf';
			$defaults['ext']  = 'otf';
		}

		return $defaults;
	}

	public function register_scripts(){
		// New React-based FontManager - register dependencies
		wp_register_script( 'lay-opentype', 'https://cdn.jsdelivr.net/npm/opentype.js@1.3.4/dist/opentype.min.js', array(), '1.3.4', false );
	}

	public function fontmanager_styles($hook) {
        if( strpos($hook, 'manage-webfonts') !== false ){
			// New React-based FontManager
			wp_enqueue_style( 'fontmanager-styles', Setup::$uri.'/fontmanager/assets/css/fontmanager.css', array(), Setup::$ver );
		}
	}

	public function fontmanager_scripts($hook){
        if( strpos($hook, 'manage-webfonts') !== false ){
			wp_enqueue_media();
			
			// Load dependencies (React is bundled in fontmanager.umd.js)
			wp_enqueue_script( 'lay-opentype' );
			
			// Enqueue FontManager UMD bundle (React bundled inside)
			wp_enqueue_script( 'fontmanager-app', Setup::$uri."/fontmanager/assets/js/fontmanager.umd.js", array( 'lay-opentype' ), Setup::$ver, true );

			wp_localize_script( 'fontmanager-app', 'layFontManagerData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'lay-fontmanager-save' ),
				'i18n' => array(
					'addFont' => lay_t('Add Font', 'fontmanager_add_font'),
					'addWebfont' => lay_t('Add Webfont', 'fontmanager_add_webfont'),
					'fontName' => lay_t('Font Name', 'fontmanager_font_name'),
					'testSentence' => lay_t('Test Sentence', 'fontmanager_test_sentence'),
					'type' => lay_t('Type', 'fontmanager_type'),
					'emptyState' => lay_t('No fonts yet. Click "Add Font" to get started.', 'fontmanager_empty_state'),
					'previewText' => lay_t('The quick brown fox jumps over the lazy dog', 'fontmanager_preview_text'),
					'delete' => lay_t('Delete', 'fontmanager_delete'),
					'confirmDelete' => lay_t('Really delete?', 'fontmanager_confirm_delete'),
					'typeAttachment' => lay_t('attachment', 'fontmanager_type_attachment'),
					'typeLink' => lay_t('link', 'fontmanager_type_link'),
					'typeScript' => lay_t('script', 'fontmanager_type_script'),
					'typeGoogle' => lay_t('google', 'fontmanager_type_google'),
					'uploadFontFileWithTypes' => lay_t('Upload font file (.woff / .woff2 / .ttf)', 'fontmanager_upload_font_file_with_types'),
					'uploadFontFile' => lay_t('Upload font file', 'fontmanager_upload_font_file'),
					'addByLinkTagAndCss' => lay_t('Add by <link> tag and CSS', 'fontmanager_add_by_link_tag_and_css'),
					'addByScriptTagAndCss' => lay_t('Add by <script> tag and CSS', 'fontmanager_add_by_script_tag_and_css'),
					'importFromGoogleFonts' => lay_t('Import from Google Fonts', 'fontmanager_import_from_google_fonts'),
					'uploadHelp' => lay_t('Upload a font file (.woff, .woff2, .ttf).', 'fontmanager_upload_help'),
					'uploading' => lay_t('Uploading...', 'fontmanager_uploading'),
					'linkHelp' => lay_t('Please insert your "Font name", "Link tag" and "font-family" CSS below. Don\'t insert any other CSS styles like "font-weight" or "font-style"!', 'fontmanager_link_help'),
					'fontNameLabel' => lay_t('Font Name:', 'fontmanager_font_name_label'),
					'linkTagLabel' => lay_t('Link Tag:', 'fontmanager_link_tag_label'),
					'fontFamilyCssLabel' => lay_t('Font-family CSS:', 'fontmanager_font_family_css_label'),
					'scriptHelp' => lay_t('Please insert your "Font name", "JavaScript Embed Code" and your "font-family" CSS below. Don\'t insert any other CSS styles like "font-weight" or "font-style"!', 'fontmanager_script_help'),
					'scriptNote' => lay_t('Note: Fonts added via script tag may not display correctly until after you refresh the page.', 'fontmanager_script_note'),
					'javascriptCodeLabel' => lay_t('JavaScript Code:', 'fontmanager_javascript_code_label'),
					'googleHelpIntro' => lay_t('In', 'fontmanager_google_help_intro'),
					'googleHelpName' => lay_t('Google Fonts', 'fontmanager_google_help_name'),
					'googleHelpOutro' => lay_t('choose your fonts, then click the “Get embed code” button to copy the embed code and paste it below.', 'fontmanager_google_help_outro'),
					'googleEmbedLabel' => lay_t('Google Fonts Embed Code:', 'fontmanager_google_embed_label'),
					'googleFamiliesNote' => lay_t('Your link tag contains %s font families. We’ll create fonts for each of them:', 'fontmanager_google_families_note'),
					'cancel' => lay_t('Cancel', 'fontmanager_cancel'),
					'addFontButton' => lay_t('Add Font', 'fontmanager_add_font_button'),
					'invalidFileType' => lay_t('Please upload a valid font file (.woff, .woff2, .ttf)', 'fontmanager_invalid_file_type'),
					'filenameStartsNumber' => lay_t('Please make sure your font filename does not start with a number.', 'fontmanager_filename_starts_number'),
					'uploadFailed' => lay_t('Failed to upload font file', 'fontmanager_upload_failed'),
					'mediaLibraryUnavailable' => lay_t('WordPress media library not available', 'fontmanager_media_library_unavailable'),
					'mediaTitle' => lay_t('Upload Font File', 'fontmanager_media_title'),
					'mediaButton' => lay_t('Use this file', 'fontmanager_media_button'),
					'selectValidFile' => lay_t('Please select a .woff, .woff2, or .ttf font file', 'fontmanager_select_valid_file'),
					'filenameNumberError' => lay_t('Error: Please make sure your filename does not start with a number.', 'fontmanager_filename_number_error'),
					'fontNameNumberError' => lay_t('Error: Please make sure your font name does not start with a number.', 'fontmanager_font_name_number_error'),
					'preloadFont' => lay_t('Preload Font', 'fontmanager_preload_font'),
				),
			) );
		}
	}

	public function ajax_save_fontmanager() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'unauthorized' ), 403 );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'lay-fontmanager-save' ) ) {
			wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
		}

		$fonts_json = isset( $_POST['fonts_json'] ) ? wp_unslash( $_POST['fonts_json'] ) : '';
		if ( $fonts_json === '' ) {
			wp_send_json_error( array( 'message' => 'empty_payload' ), 400 );
		}

		$decoded = json_decode( $fonts_json, true );
		if ( $decoded === null && json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => 'invalid_json' ), 400 );
		}

		update_option( 'fontmanager_json', wp_json_encode( $decoded ) );
		FontManager::$customFonts = $decoded;

		wp_send_json_success( array( 'saved' => true ) );
	}

	public function fontmanager_json_settings_api_init() {
		// register_setting( 'admin-fonts-settings', 'fontmanager_json' );
		add_settings_section(
			'manage-webfonts-section',
			'',
			'',
			'manage-webfonts'
		);
	 	add_settings_field(
			'fontmanager_json',
			'Font Manager JSON',
			array($this, 'fontmanager_json_txtarea'),
			'manage-webfonts',
			'manage-webfonts-section'
		);
		register_setting( 'manage-webfonts', 'fontmanager_json' );
	}

	public function fontmanager_json_txtarea(){
		echo
		'<textarea name="fontmanager_json" id="fontmanager_json">'.get_option('fontmanager_json', '').'</textarea>';
	}

	public function font_setup_menu(){
		// add_menu_page( 'Webfonts', 'Webfonts', 'manage_options', 'manage-webfonts', array($this,'fonts_markup'), 'dashicons-editor-bold' );
		add_submenu_page( 'manage-layoptions', 'Webfonts', lay_t('Webfonts', 'webfonts'), 'manage_options', 'manage-webfonts', array($this, 'fonts_markup') );
	}

	public function fonts_markup(){
		require_once( Setup::$dir.'/fontmanager/markup.php' );
	}

}
$fontmanager = new FontManager();
