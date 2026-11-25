<?php
// https://make.wordpress.org/core/2014/07/08/customizer-improvements-in-4-0/
class Customizer{

	public static $fonts;
	public static $defaults = array();
	public static $textformatsSelect;
	public static $self;
	public static $projectDescr;

	public function __construct(){
		Customizer::$self = $this;
		Customizer::populateDefaults();
		Customizer::populateTextformatsSelect();

		$val = get_option('misc_options_activate_project_description', '');
		if($val == 'on' && !MiscOptions::$locked){
			Customizer::$projectDescr = true;
		}
		else{
			Customizer::$projectDescr = false;
		}
		
		// Load custom controls early so they're available for all customizer sections
		require_once Setup::$dir.'/customizer/assets/php/controls/category-dropdown-custom-control.php';
		require_once Setup::$dir.'/customizer/assets/php/controls/post-dropdown-custom-control.php';
		require_once Setup::$dir.'/customizer/assets/php/controls/spacing-with-unit-custom-control.php';
		require_once Setup::$dir.'/customizer/assets/php/controls/range-with-number-custom-control.php';
		require_once Setup::$dir.'/customizer/assets/php/controls/order-custom-control.php';
		
		add_action( 'customize_register', array($this, 'lay_customize_register'), 11 );

		add_action( 'customize_preview_init', array($this, 'customizer_preview_js') );
		// Bypass password protection in preview so content is visible and the password form submit isn't broken in the iframe
		add_action( 'customize_preview_init', array($this, 'bypass_password_in_preview') );
		// Layout can be loaded via AJAX from the preview iframe; that request doesn't have is_customize_preview(), so bypass when referer is customizer
		add_action( 'wp_ajax_get_laytheme_layout', array( $this, 'maybe_bypass_password_for_layout_ajax' ), 0 );
		add_action( 'wp_ajax_nopriv_get_laytheme_layout', array( $this, 'maybe_bypass_password_for_layout_ajax' ), 0 );
		add_action( 'customize_controls_enqueue_scripts', array($this, 'customizer_controls_js') );

		add_action( 'mce_external_plugins', array( $this, 'tinymce_add_setcustomizercss') );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customizer_controls_css') );
		add_action( 'wp_print_styles', array( $this, 'lay_customizer_preview_css' ), 10 );

		add_action( 'customize_controls_print_scripts', array( $this, 'add_woocommerce_script' ), 30 );


		$newFonts = array('' => '--SELECT--');
		$customFontsJSON = get_option('fontmanager_json');

		if($customFontsJSON){
			$customFonts = json_decode($customFontsJSON, true);
			if($customFonts){
				for($i=0; $i<count($customFonts); $i++){
					$key = $customFonts[$i]['fontname'];

					if( array_key_exists('type', $customFonts[$i]) &&
						($customFonts[$i]["type"] == "script" || $customFonts[$i]["type"] == "link") ){
						$css = $customFonts[$i]['css'];
						$css = str_replace('font-family: ', '', $css);
						$css = str_replace('font-family:', '', $css);
						$css = str_replace(';', '', $css);
						$newFonts[$css] = $key;
					}
					else{
						$newFonts[$key] = $customFonts[$i]['fontname'];
					}
				}
			}
		}

		$originalFonts = FontManager::$originalFonts;
		$originalFonts = array_flip($originalFonts);

		Customizer::$fonts = array_merge($newFonts, $originalFonts);
	}

	// taken from class-wc-shop-customizer.php
	function add_woocommerce_script(){
		$product_permalink = false;
		$product_id = false;

		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
		);
        
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
            $ix = 0;
			foreach ( $query->posts as $post ) {
                if($ix == 0) {
                    $product_permalink = get_permalink($post);
                    $product_id = $post->ID;
                }
                $ix++;
			}
		}
		$order_received_permalink = false;
		if( !class_exists('WooCommerce') ) {
			return;
		}
		if( class_exists('WooCommerce') ) {
			$order_received_permalink = wc_get_checkout_url().'/order-received';
		}

		?>
		<script type="text/javascript">
			jQuery( function( $ ) {
				wp.customize.section( 'lay_woocommerce_cart', function( section ) {
					section.expanded.bind( function( isExpanded ) {
						if ( isExpanded ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( wc_get_page_permalink( 'cart' ) ); ?>' );
						}
					} );
				} );
				<?php
				if( $product_permalink != false ) {
				?>
					wp.customize.section( 'lay_woocommerce_single_product', function( section ) {
						section.expanded.bind( function( isExpanded ) {
							if ( isExpanded ) {
								wp.customize.previewer.previewUrl.set( '<?php echo esc_js( $product_permalink ); ?>' );
							}
						} );
					} );
                    wp.customize.section( 'lay_woocommerce_single_product_order', function( section ) {
						section.expanded.bind( function( isExpanded ) {
							if ( isExpanded ) {
								wp.customize.previewer.previewUrl.set( '<?php echo esc_js( $product_permalink ); ?>' );
							}
						} );
					} );
				<?php
				}

				if( $order_received_permalink != false ) {
				?>
					wp.customize.section( 'lay_woocommerce_order_received', function( section ) {
						section.expanded.bind( function( isExpanded ) {
							if ( isExpanded ) {
								wp.customize.previewer.previewUrl.set( '<?php echo esc_js( $order_received_permalink ); ?>' );
							}
						} );
					} );
				<?php
				}
				?>

				// Handle front page setting changes - navigate preview to home URL
				wp.customize( 'frontpage_type', function( value ) {
					value.bind( function( newval ) {
						// When front page type changes, navigate to home
						wp.customize.previewer.previewUrl.set( '<?php echo esc_js( home_url( '/' ) ); ?>' );
					} );
				} );

				wp.customize( 'frontpage_select_category', function( value ) {
					value.bind( function( newval ) {
						// When category selection changes, navigate to home to show new front page
						if ( wp.customize('frontpage_type').get() === 'category' ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( home_url( '/' ) ); ?>' );
						}
					} );
				} );

				wp.customize( 'frontpage_select_page', function( value ) {
					value.bind( function( newval ) {
						// When page selection changes, navigate to home to show new front page
						if ( wp.customize('frontpage_type').get() === 'page' ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( home_url( '/' ) ); ?>' );
						}
					} );
				} );

				wp.customize( 'frontpage_select_project', function( value ) {
					value.bind( function( newval ) {
						// When project selection changes, navigate to home to show new front page
						if ( wp.customize('frontpage_type').get() === 'project' ) {
							wp.customize.previewer.previewUrl.set( '<?php echo esc_js( home_url( '/' ) ); ?>' );
						}
					} );
				} );
			});
		</script>
		<?php
	}

	function lay_customizer_preview_css(){
		wp_add_inline_style( 'customize-preview', 
		'.widget .customize-partial-edit-shortcut button, .customize-partial-edit-shortcut button{ background: cyan!important; }
		.customize-partial-edit-shortcut button svg{ fill: #000; }
		.widget .customize-partial-edit-shortcut button:hover, .customize-partial-edit-shortcut button:hover{ background: cyan!important; }
		html.in-customizer .lay-woocommerce-single-product-wrap.carousel_with_thumbnails,
		html.in-customizer .lay-woocommerce-single-product-wrap.carousel {
			opacity: 1 !important;
		}' 
		);
	}

	function customizer_controls_js(){
		// Override WordPress's slide-up/slide-down animation on control activate/
		// deactivate so visibility changes happen instantly. Eliminates the
		// flicker that happens when our PHP active_callback and JS handlers in
		// app.handlers.js momentarily disagree on a control's active state.
		// Depends on customize-controls so wp.customize.Control prototype exists.
		wp_enqueue_script('lay-disable-control-animations', Setup::$uri.'/customizer/assets/js/disable_control_animations.js', array('customize-controls'), Setup::$ver);

		wp_enqueue_script('lay-showhide_in_controls', Setup::$uri.'/customizer/assets/js/on_sections_panels_showhide_in_controls.js', array('jquery', 'customize-preview'), Setup::$ver);
		wp_localize_script( 'lay-showhide_in_controls', 'layShowhideInControlsPassedData',
			array(
				'wooCommerceActive' => class_exists( 'WooCommerce' ),
				'sideCartPluginActive' => LTUtility::is_sidecart_active()
			)
		);
	}

	/**
	 * In the customizer preview iframe, bypass password protection so the page content is visible.
	 * Submitting the password form in an iframe often fails (cookies/redirect), so we simply show the content.
	 */
	function bypass_password_in_preview() {
		add_filter( 'post_password_required', function( $required, $post ) {
			return false;
		}, 10, 2 );
	}

	/**
	 * When layout is requested via AJAX from the customizer preview iframe, bypass password so getLayout() returns content.
	 */
	function maybe_bypass_password_for_layout_ajax() {
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
		if ( strpos( $referer, 'wp_customize=' ) !== false || strpos( $referer, 'customize_changeset=' ) !== false ) {
			add_filter( 'post_password_required', function( $required, $post ) {
				return false;
			}, 10, 2 );
		}
	}

	function customizer_preview_js(){
		wp_enqueue_script('lay-showhide_in_preview', Setup::$uri.'/customizer/assets/js/on_sections_panels_showhide_in_preview.js', array('jquery', 'customize-preview'), Setup::$ver);

		wp_enqueue_script('lay-customizer', Setup::$uri.'/customizer/assets/js/app.handlers.min.js', array('jquery', 'customize-preview'), Setup::$ver, true);
        $textFormatsJsonString = FormatsManager::getDefaultFormatsJson();
		$menu_amount = intval(get_option('misc_options_menu_amount', 1));
        if( $menu_amount == 0 ) {
            $menu_amount = 1;
        }
		$breakpoint = get_option('lay_breakpoint', 600);
		$phone_menu_breakpoint = intval(get_option('lay_phone_menu_breakpoint', $breakpoint));
		$localizeArray = array(
			'textformats' => $textFormatsJsonString,
			'menu_amount' => $menu_amount,
			'wooCommerceActive' => class_exists( 'WooCommerce' ),
			'phone_menu_breakpoint' => $phone_menu_breakpoint
		);
		// if( class_exists('WooCommerce') && function_exists('wc_get_page_permalink') ) {
		// 	$localizeArray['shoplink'] = wc_get_page_permalink( 'shop' );
		// 	$localizeArray['checkoutlink'] = wc_get_page_permalink( 'checkout' );
		// 	$localizeArray['cartlink'] = wc_get_page_permalink( 'cart' );
		// }
		wp_localize_script('lay-customizer', 'layCustomizerPassedData', $localizeArray);
	}

	function customizer_controls_css() {
		//hiding customizer "menu" panel here
	    wp_add_inline_style( 'customize-controls', '.accordion-section-content.description, .customize-panel-description.description { display: block!important; } .control-panel-nav_menus{ display: none!important; }');
		// todo: not sure if the following even works?
		wp_enqueue_style( 'customizer-custom-css', Setup::$uri.'/customizer/assets/css/style.css' );	
	}

	public function tinymce_add_setcustomizercss( $plugins ) {
		$plugins['setcustomizercss'] = Setup::$uri.'/customizer/assets/js/tinymce_set_customizer_css.js?ver='.Setup::$ver;
		return $plugins;
	}

	public static function lay_customize_register($wp_customize){

		$wp_customize->remove_setting('blogname');
		$wp_customize->remove_control('blogname');
		$wp_customize->remove_section('title_tagline');
		$wp_customize->remove_section('nav');
		$wp_customize->remove_section('static_front_page');

		// $wp_customize->remove_section('custom_css');
		// this would cause errors for wordpress 4.4 but would be the preferred method of removing "menus" from the customizer instead of hiding it with css like i do now
		//$wp_customize->remove_panel('nav_menus');

		$wp_customize->add_panel( 'sitetitle_panel', array(
		    'priority'       => 10,
		    'capability'     => 'edit_theme_options',
		    'theme_supports' => '',
		    'title'          => lay_t('Site Title', 'customizer_panel_site_title'),
		) );

		$wp_customize->add_panel( 'projectlink_panel', array(
		    'priority'       => 20,
		    'capability'     => 'edit_theme_options',
		    'theme_supports' => '',
		    'title'          => lay_t('Project Thumbnails', 'customizer_panel_project_thumbnails'),
		    'description'	 => lay_t('A Project Thumbnail is inserted in the Gridder with "+Project".', 'customizer_project_thumbnails_panel_description')
		) );

		$breakpoint = get_option('lay_breakpoint', 600);
		$wp_customize->add_panel( 'mobile_panel', array(
			'priority'       => 25,
			'capability'     => 'edit_theme_options',
			'theme_supports' => '',
			'title'          => lay_t('Mobile (Smartphone)', 'customizer_panel_mobile'),
		) );

		$wp_customize->add_panel( 'linksintexts_panel', array(
			'priority'       => 30,
			'capability'     => 'edit_theme_options',
			'theme_supports' => '',
			'title'          => lay_t('Links in Texts', 'customizer_panel_links_in_texts'),
			'description'	 => lay_t('A Link in a text is when you added a Text in the Gridder with "+Text" and it contains a link.', 'customizer_links_in_texts_panel_description')
		) );

		$wp_customize->add_panel( 'lay_css', array(
			'priority'       => 999,
			'capability'     => 'edit_theme_options',
			'theme_supports' => '',
			'title'          => lay_t('CSS', 'customizer_panel_css'),
		) );

		if(LayIntro::$isActive){
			require_once Setup::$dir.'/customizer/assets/php/intro.php';
		}
		require_once Setup::$dir.'/customizer/assets/php/projecttitle.php';
		require_once Setup::$dir.'/customizer/assets/php/project_thumbnail_mouseover.php';
		require_once Setup::$dir.'/customizer/assets/php/project_thumbnail_gradient_overlay.php';
		if(Customizer::$projectDescr){
			require_once Setup::$dir.'/customizer/assets/php/project_descr.php';
		}
		require_once Setup::$dir.'/customizer/assets/php/project_tags.php';
		require_once Setup::$dir.'/customizer/assets/php/links_in_texts.php';
		require_once Setup::$dir.'/customizer/assets/php/links_in_texts_mouseover.php';
		require_once Setup::$dir.'/customizer/assets/php/favicon.php';
		require_once Setup::$dir.'/customizer/assets/php/sitetitle.php';
		require_once Setup::$dir.'/customizer/assets/php/sitetitle_mouseover.php';
		require_once Setup::$dir.'/customizer/assets/php/tagline.php';

		require_once Setup::$dir.'/customizer/assets/php/menu_bar.php';
		require_once Setup::$dir.'/customizer/assets/php/menu_active_section.php';
		require_once Setup::$dir.'/customizer/assets/php/menu_mouseover_section.php';
		require_once Setup::$dir.'/customizer/assets/php/menu_submenu.php';

		require_once Setup::$dir.'/customizer/assets/php/background.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_spaces.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_menu_icons.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_menu.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_menubar.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_sitetitle.php';
		require_once Setup::$dir.'/customizer/assets/php/mobile_background.php';
		require_once Setup::$dir.'/customizer/assets/php/project_arrows.php';
		require_once Setup::$dir.'/customizer/assets/php/frontpage.php';
		require_once Setup::$dir.'/customizer/assets/php/thumbnailgrid_category_filter.php';
		require_once Setup::$dir.'/customizer/assets/php/thumbnailgrid_tag_filter.php';

		require_once Setup::$dir.'/customizer/assets/php/css.php';
		require_once Setup::$dir.'/customizer/assets/php/search.php';
		require_once Setup::$dir.'/customizer/assets/php/cursor.php';
		if( class_exists( 'WooCommerce' ) ) {
			// im adding my own section for product thumbnails to avoid the standard redirect to the product archive
			$wp_customize->remove_section('woocommerce_product_images');

			require_once Setup::$dir.'/customizer/assets/php/woocommerce_product_thumbnails.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_spaces.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_mobile_spaces.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_buttons.php';
            require_once Setup::$dir.'/customizer/assets/php/woocommerce_cart_menupoint.php';
            require_once Setup::$dir.'/customizer/assets/php/woocommerce_cart_icon_mobile.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_store_notice.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_single_product.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_single_product_order.php';
            require_once Setup::$dir.'/customizer/assets/php/woocommerce_cart.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_checkout.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_order_received.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_productsgrid_category_filter.php';
			require_once Setup::$dir.'/customizer/assets/php/woocommerce_productsgrid_tag_filter.php';

			// not using this setting in lay theme
			$wp_customize->remove_control('woocommerce_single_image_width');
			// for woocommerce_thumbnail_image_width I'm inserting my own control in "woocommerce_product_thumbnails.php" with a better description of what it does
			$wp_customize->remove_control('woocommerce_thumbnail_image_width');
			// the shop page should not show product thumbnails, because people are supposed to create their own shop page using the gridder
			// so i just remove that customizer section altogether
			// idea: maybe let users decide if they want to show automatically generated product thumbnail grids. the way it was intended by WooCommerce
			// this way if someone had many categories, they would not need to add their product grid to every category
			$wp_customize->remove_section('woocommerce_product_catalog');

			$sideCartPluginActive = LTUtility::is_sidecart_active();
			if( $sideCartPluginActive ) {
				require_once Setup::$dir.'/customizer/assets/php/woocommerce_sidecart.php';
				require_once Setup::$dir.'/customizer/assets/php/woocommerce_sidecart_buttons.php';
			}
		}

	}

	public static function populateTextformatsSelect(){
		$textFormatsJsonString = FormatsManager::getDefaultFormatsJson();
		$textFormatsJsonArr = json_decode($textFormatsJsonString, true);
		$result = array('' => '--SELECT--');
		foreach ($textFormatsJsonArr as $value) {
			$result[$value['formatname']] = $value['formatname'];
		}
		Customizer::$textformatsSelect = $result;
	}

	public static function populateDefaults(){

		$formatsJsonString = FormatsManager::getDefaultFormatsJson();
		$formatsJsonArr = json_decode($formatsJsonString, true);

		$defaults = null;

		for($i = 0; $i<count($formatsJsonArr); $i++) {
		 	if($formatsJsonArr[$i]["formatname"] == "Default"){
		 		$defaults['color'] = $formatsJsonArr[$i]["color"];
		 		$defaults['fontfamily'] = $formatsJsonArr[$i]["fontfamily"];
		 		$defaults['fontsize'] = $formatsJsonArr[$i]["fontsize"];
		 		$defaults['letterspacing'] = $formatsJsonArr[$i]["letterspacing"];
		 		$defaults['fontweight'] = $formatsJsonArr[$i]["fontweight"];
		 		$defaults['lineheight'] = $formatsJsonArr[$i]["lineheight"];
		 	}
		 }

		if(!is_null($defaults)){
			Customizer::$defaults['color'] = $defaults['color'];
			Customizer::$defaults['fontfamily'] = $defaults['fontfamily'];
			Customizer::$defaults['fontsize'] = $defaults['fontsize'];
			Customizer::$defaults['letterspacing'] = $defaults['letterspacing'];
			Customizer::$defaults['fontweight'] = $defaults['fontweight'];
			Customizer::$defaults['lineheight'] = $defaults['lineheight'];
		}
		else{
			Customizer::$defaults['color'] = FormatsManager::$defaultFormat['color'];
			Customizer::$defaults['fontfamily'] = FormatsManager::$defaultFormat['fontfamily'];
			Customizer::$defaults['fontsize'] = FormatsManager::$defaultFormat['fontsize'];
			Customizer::$defaults['letterspacing'] = FormatsManager::$defaultFormat['letterspacing'];
			Customizer::$defaults['fontweight'] = FormatsManager::$defaultFormat['fontweight'];
			Customizer::$defaults['lineheight'] = FormatsManager::$defaultFormat['lineheight'];
		}


		Customizer::$defaults['underline_width'] = '0';
		Customizer::$defaults['st_spacetop'] = '16';
		Customizer::$defaults['st_spaceleft'] = '5';
		Customizer::$defaults['st_spaceright'] = '5';
		Customizer::$defaults['st_spacebottom'] = '16';
		Customizer::$defaults['st_img_width'] = '10';

		Customizer::$defaults['mobile_menu_txt_color'] = CSS_Output::get_mobile_menu_txt_color();
		Customizer::$defaults['mobile_menu_light_color'] = CSS_Output::get_mobile_menu_light_color();
		Customizer::$defaults['mobile_menu_dark_color'] = CSS_Output::get_mobile_menu_dark_color();

		Customizer::$defaults['mobile_menu_fontsize'] = '15';

		Customizer::$defaults['intro_text_spacetop'] = '5';
		Customizer::$defaults['intro_text_spaceleft'] = '5';
		Customizer::$defaults['intro_text_spaceright'] = '5';
		Customizer::$defaults['intro_text_spacebottom'] = '5';
	}

}
new Customizer();
