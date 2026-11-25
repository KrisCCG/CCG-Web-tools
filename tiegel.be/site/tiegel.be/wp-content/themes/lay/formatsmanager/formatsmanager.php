<?php
class FormatsManager {
	public static $customFormats = false;
	public static $fontWeights = array(
		'Thin (100)' => '100',
		'Extra Light (200)' => '200',
		'Light (300)' => '300',
		'Normal (400)' => '400',
		'Medium (500)' => '500',
		'Semi Bold (600)' => '600',
		'Bold (700)' => '700',
		'Extra Bold (800)' => '800',
		'Black (900)' => '900'
	);
	public static $defaultFormat = array(
		'formatname' => 'Default',
		'type' => 'Paragraph',
		'headlinetype' => 'h1',
		'fontfamily' => 'helvetica,sans-serif',
		'fontsize' => '25',
		'color' => '#000000',
		'letterspacing' => '0',
		'fontweight' => '400',
		'spacebottom' => '15',
		'spacetopmu' => 'px',
		'spacetop' => '0',
		'spacebottommu' => 'px',
		'paddingbottom' => '0',
		'paddingbottommu' => 'px',
		'textalign' => 'left',
		'lineheight' => '1.2',
		'textindent' => '0',
		'caps' => false,
		'italic' => false,
		'underline' => false,
		'borderbottom' => false,
		'tablet-spacetop' => "0",
		'tablet-spacetopmu' => 'px',
		'tablet-spacebottom' => '15',
		'tablet-spacebottommu' => 'px',
		'tablet-fontsizemu' => 'px',
		'tablet-fontsize' => '20',
		'tablet-paddingbottom' => '0',
		'tablet-paddingbottommu' => 'px',
		'phone-spacetop' => "0",
		'phone-spacetopmu' => 'px',
		'phone-spacebottom' => '15',
		'phone-spacebottommu' => 'px',
		'phone-fontsizemu' => 'px',
		'phone-fontsize' => '16',
		'phone-paddingbottom' => '0',
		'phone-paddingbottommu' => 'px',
		'not-deletable' => true,
		'textwrap' => 'wrap',
		'commonligatures' => false
	);
	public static $hasTabletSettings = false;
	public static $hasPaddingBottomSettings = false;

	public static function init(){
		$customFormatsJSON = FormatsManager::getDefaultFormatsJson();
		if ($customFormatsJSON) {
			FormatsManager::$customFormats = json_decode($customFormatsJSON, true);
		}

		$misc_options_textformats_for_tablet = get_option( 'misc_options_textformats_for_tablet', "on" );
		if ( $misc_options_textformats_for_tablet == "on" ) {
			FormatsManager::$hasTabletSettings = true;
		}

		$misc_options_textformats_padding_bottom = get_option( 'misc_options_textformats_padding_bottom', "" );
		if ( $misc_options_textformats_padding_bottom == "on" ) {
			FormatsManager::$hasPaddingBottomSettings = true;
		}


		add_action( 'admin_menu', 'FormatsManager::textformats_setup_menu', 10 );

		add_action( 'admin_init', 'FormatsManager::register_settings' );

		add_action( 'admin_enqueue_scripts', 'FormatsManager::formatsmanager_styles' );

		add_action( 'admin_init', 'FormatsManager::register_scripts', 10 );
		add_action( 'admin_enqueue_scripts', 'FormatsManager::formatsmanager_scripts', 9 );

		add_action( 'admin_head', 'FormatsManager::gridder_textformats_css', 10 );
		add_action( 'admin_head', 'FormatsManager::add_tinymce_js_vars', 10 );

		add_action( 'admin_footer', 'FormatsManager::print_JSON' );

		add_action( 'mce_external_plugins', 'FormatsManager::tinymce_add_textformatsloader' );
		add_filter( 'tiny_mce_before_init', 'FormatsManager::tinymce_formats' );

		add_action( 'wp_enqueue_scripts', 'FormatsManager::frontend_textformats_css', 13 );

		add_action( 'wp_ajax_lay_formatsmanager_save', array( __CLASS__, 'ajax_save_formats' ) );
	}

    public static function get_lineheight_row_markup($type = ""){
		// no lineheight settings needed for phone and tablet if advancedLineheightSettings is off
		if( ($type == "tablet" || $type == "phone") && !LayFrontend_Options::$textformats_lineheight_in_px ){
			return '';
		}

		// if type is "" the type is desktop
		$name = "";
		if($type != ""){
			$name = ucfirst($type);
			$name .= " ";
			$type = $type.'-';
		}
		$markup = '';
        if(LayFrontend_Options::$textformats_lineheight_in_px){
        	$markup .=  
        	'<input type="number" class="'.$type.'line-height" value="" step="0.1">
            <select class="'.$type.'line-height-mu">
        		<option value="">em</option>
        	    <option value="px">px</option>
        	</select>';
        } else {
    		$markup = '<input type="number" class="'.$type.'line-height" value="1.2" step="0.01">';
        }

        return 
        '<tr class="'.$type.'line-height-row">
            <td>'.$name.'Line Height</td>
            <td>'.$markup.'</td>
        </tr>';
	}

	// http://wordpress.stackexchange.com/questions/144705/unable-to-add-code-button-to-tinymce-toolbar
	// formats dropdown for tinymce
	public static function tinymce_formats($in) {
		// using 'attributes' => array('class' => '_'.$customFormats[$i]['formatname']),
		// instead of 'classes' => '_'.$customFormats[$i]['formatname']
		// this way the user can only have one format applied to a selected text

		$paragraph_formats = array();
		$headline_formats = array();
		$character_formats = array();

		$customFormats = FormatsManager::$customFormats;
		
		$style_formats = array();

		if ($customFormats && is_array($customFormats)) {
			for ($i=0; $i<count($customFormats); $i++) {
                // https://www.tiny.cloud/docs/configure/content-formatting/
				switch ($customFormats[$i]['type']) {
					case 'Paragraph':
						$paragraph_formats []= array(
							'title' => $customFormats[$i]['formatname'],
							'attributes' => array('class' => '_'.$customFormats[$i]['formatname']),
							'exact' => true,
                            // instead of using 'block' => 'p' i do this and it makes sure i can apply the formats to list elements too
                            'selector' => 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,table',
						);
					break;
					case 'Headline':
						$block = 'h1';
						if( array_key_exists('headlinetype', $customFormats[$i]) ) {
							$block = $customFormats[$i]['headlinetype'];
						}
						$headline_formats []= array(
							'title' => $customFormats[$i]['formatname'],
							'attributes' => array('class' => '_'.$customFormats[$i]['formatname']),
							'block' => $block
						);
					break;
					case 'Character':
						$character_formats []= array(
							'title' => $customFormats[$i]['formatname'],
							'attributes' => array('class' => '_'.$customFormats[$i]['formatname']),
							'inline' => 'span'
						);
					break;
				}
			}

			if ($paragraph_formats) {
				$style_formats []= array(
					'title' => 'Paragraph',
					'items' => $paragraph_formats
				);
			}
			if ($headline_formats) {
				$style_formats []= array(
					'title' => 'Headline',
					'items' => $headline_formats
				);
			}
			if ($character_formats) {
				$style_formats []= array(
					'title' => 'Character',
					'items' => $character_formats
				);
			}

			$in['style_formats'] = json_encode( $style_formats );
		} else {
			$style_formats []= array(
				'title' => 'Paragraph',
				'items' => array()
			);

			$style_formats []= array(
				'title' => 'Headline',
				'items' => array()
			);

			$style_formats []= array(
				'title' => 'Character',
				'items' => array()
			);

			$in['style_formats'] = json_encode( $style_formats );
		}

		return $in;
	}

	public static function add_tinymce_js_vars(){
		// need to add info about whether advanced lineheight is true as a js variable in admin head
		// because I cannot use wp-localize-script for "textformatsloader" tinymce external plugin
		$bool = "false";
		$misc_options_textformats_lineheight_in_px = get_option('misc_options_textformats_lineheight_in_px', '');
		
		if($misc_options_textformats_lineheight_in_px == "on"){
			$bool = "true";
		}

		echo 
		'<script>
			var lg_advanced_lineheights = '.$bool.';
		</script>';
	}

	public static function tinymce_add_textformatsloader( $plugins ) {
		$plugins['textformatsloader'] = Setup::$uri.'/formatsmanager/assets/js/tinymce_plugin/tinymce_textformatsloader.js?ver='.Setup::$ver;
		return $plugins;
	}


	public static function getDefaultFormatsJson() {
		$formats_array = array(
			FormatsManager::$defaultFormat
		);
		if( class_exists('WooCommerce') ) {
			$formats_array = array(
				FormatsManager::$defaultFormat,
				WooCommerceFormats::$Shop_Big,
				WooCommerceFormats::$Shop_Medium,
				WooCommerceFormats::$Shop_Small, 
				WooCommerceFormats::$Shop_Product_Thumbnails,
				WooCommerceFormats::$Shop_Cart,
				WooCommerceFormats::$Shop_Order_Received
			);
		}

		$formats = get_option( 'formatsmanager_json', json_encode($formats_array) );
        // If formatsmanager_json is invalid, recover from last valid backup first
        // instead of silently falling back to defaults.
        if(!is_array( json_decode($formats, true) )) {
            $backup_formats = get_option( 'formatsmanager_json_backup', '' );
            if ( is_string( $backup_formats ) && $backup_formats !== '' && is_array( json_decode( $backup_formats, true ) ) ) {
                $formats = $backup_formats;
            } else {
                // Last resort fallback.
                $formats = json_encode($formats_array);
            }
        }
       

		$formats = FormatsManager::maybeAddRequiredFormats($formats);
		$formats = json_encode($formats);
		return $formats;
	}

	public static function maybeAddRequiredFormats($formats) {
		$formatsJsonArr = json_decode($formats, true);
        if( is_array($formatsJsonArr) ) {
            if( class_exists('WooCommerce') ) {
                if( !FormatsManager::format_of_name_in_array('Shop_Cart', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Cart);
                }
                if( !FormatsManager::format_of_name_in_array('Shop_Product_Thumbnails', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Product_Thumbnails);
                }
                if( !FormatsManager::format_of_name_in_array('Shop_Small', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Small);
                }
                if( !FormatsManager::format_of_name_in_array('Shop_Medium', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Medium);
                }
                if( !FormatsManager::format_of_name_in_array('Shop_Big', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Big);
                }
                if( !FormatsManager::format_of_name_in_array('Shop_Order_Received', $formatsJsonArr) ){
                    array_unshift($formatsJsonArr, WooCommerceFormats::$Shop_Order_Received);
                }
            }
            if( !FormatsManager::format_of_name_in_array('Default', $formatsJsonArr) ){
                array_unshift($formatsJsonArr, FormatsManager::$defaultFormat);
            }
        }

		return $formatsJsonArr;
	}

	public static function format_of_name_in_array($formatname, $array) {
        if( is_array($array) ) {
            for ($i=0; $i < count($array); $i++) { 
                $format = $array[$i];
    
                if( $formatname == $format['formatname'] ) {
                    return true;
                }
            }
        }
		return false;
	}

	public static function print_JSON() {
		echo '<textarea style="display: none;" id="formatsmanager_json" name="formatsmanager_json">'.FormatsManager::getDefaultFormatsJson().'</textarea>';
	}

	private static function getPhoneSpecificCSS($array, $spaces = true, $useScaleForGridder = '', $important = false, $includePaddingBottom = true) {
		$phone_fontsize = array_key_exists('phone-fontsize', $array) ? $array['phone-fontsize'] : "16";
		$fontsizemu = array_key_exists('fontsizemu', $array) ? $array['fontsizemu'] : 'px';
		$phone_fontsizemu = array_key_exists('phone-fontsizemu', $array) ? $array['phone-fontsizemu'] : $fontsizemu;
        $important_css = $important ? '!important' : '';
		$isCharacter = isset($array['type']) && $array['type'] == 'Character';

		if($spaces && !$isCharacter){
			$phone_spacetop = array_key_exists('phone-spacetop', $array) ? $array['phone-spacetop'] : "0";
			$phone_spacetopmu = array_key_exists('phone-spacetopmu', $array) ? $array['phone-spacetopmu'] : "px";
	
			$phone_spacebottom = array_key_exists('phone-spacebottom', $array) ? $array['phone-spacebottom'] : "20";
			$phone_spacebottommu = array_key_exists('phone-spacebottommu', $array) ? $array['phone-spacebottommu'] : "px";
		}

        $return = '';

        if( $phone_fontsizemu == 'vw' && $useScaleForGridder == 'useScaleForGridder' ) {
            $return =
			'font-size:calc('.$phone_fontsize.$phone_fontsizemu.' / var(--gridder-vw-fontsize-scale));';
        } else {
            $return =
			'font-size:'.$phone_fontsize.$phone_fontsizemu.$important_css.';';
        }

		if($spaces && !$isCharacter){
			$return .= 'margin:'.$phone_spacetop.$phone_spacetopmu.' 0 '.$phone_spacebottom.$phone_spacebottommu.' 0;';
		}

        if( FormatsManager::$hasPaddingBottomSettings && $includePaddingBottom ){
            $phone_paddingbottom = array_key_exists('phone-paddingbottom', $array) ? $array['phone-paddingbottom'] : '0';
            $phone_paddingbottommu = array_key_exists('phone-paddingbottommu', $array) ? $array['phone-paddingbottommu'] : 'px';
            $return .= 'padding-bottom:'.$phone_paddingbottom.$phone_paddingbottommu.$important_css.';';
        }

        if(LayFrontend_Options::$textformats_lineheight_in_px){
            $lh_mu = array_key_exists('phone-lineheightmu', $array) ? $array['phone-lineheightmu'] : '';
            $lh = array_key_exists('phone-lineheight', $array) ? $array['phone-lineheight'] : false;
            if($lh !== false) {
                $return .= 'line-height:'.$lh.$lh_mu.';';
            } else {
                // if not phone lineheight was set, return desktop lineheight
                $lh_mu = array_key_exists('lineheightmu', $array) ? $array['lineheightmu'] : '';
                $lh = array_key_exists('lineheight', $array) ? $array['lineheight'] : false;
                $return .= 'line-height:'.$lh.$lh_mu.';';
            }

        }

		return $return;
	}

	private static function getDesktopSpecificCSS($array, $spaces = true, $useScaleForGridder = '', $important = false, $includePaddingBottom = true) {
		$fontsizemu = array_key_exists('fontsizemu', $array) ? $array['fontsizemu'] : 'px';
        $important_css = $important ? '!important' : '';
		$isCharacter = isset($array['type']) && $array['type'] == 'Character';

		if($spaces && !$isCharacter){
			$spacetop = array_key_exists('spacetop', $array) ? $array['spacetop'] : '0';
			$spacebottom = array_key_exists('spacebottom', $array) ? $array['spacebottom'] : '20';
			$spacetopmu = array_key_exists('spacetopmu', $array) ? $array['spacetopmu'] : "px";
			$spacebottommu = array_key_exists('spacebottommu', $array) ? $array['spacebottommu'] : "px";
		}

        if( $fontsizemu == 'vw' && $useScaleForGridder == 'useScaleForGridder' ) {
		    $return = 'font-size:calc('.$array['fontsize'].$fontsizemu.' / var(--gridder-vw-fontsize-scale));';
        } else {
		    $return = 'font-size:'.$array['fontsize'].$fontsizemu.$important_css.';';
        }
		if($spaces && !$isCharacter){
			$return .= 'margin:'.$spacetop.$spacetopmu.' 0 '.$spacebottom.$spacebottommu.' 0;';
		}

		if( FormatsManager::$hasPaddingBottomSettings && $includePaddingBottom ){
			$paddingbottom = array_key_exists('paddingbottom', $array) ? $array['paddingbottom'] : '0';
			$paddingbottommu = array_key_exists('paddingbottommu', $array) ? $array['paddingbottommu'] : 'px';
			$return .= 'padding-bottom:'.$paddingbottom.$paddingbottommu.$important_css.';';
		}

        if(LayFrontend_Options::$textformats_lineheight_in_px){
            $lh_mu = array_key_exists('lineheightmu', $array) ? $array['lineheightmu'] : '';
            $lh = array_key_exists('lineheight', $array) ? $array['lineheight'] : false;
            if($lh !== false) {
                $return .= 'line-height:'.$lh.$lh_mu.';';
            }
        }

		return $return;
	}

	private static function getTabletSpecificCSS($array, $spaces = true, $useScaleForGridder = '', $important = false, $includePaddingBottom = true) {
		// bail out early if tablet textformats is not active
		if (!FormatsManager::$hasTabletSettings) {
			return FormatsManager::getDesktopSpecificCSS($array, $spaces, $useScaleForGridder, $important);
		}
		$tablet_fontsize = array_key_exists('tablet-fontsize', $array) ? $array['tablet-fontsize'] : "16";
		$tablet_fontsizemu = array_key_exists('tablet-fontsizemu', $array) ? $array['tablet-fontsizemu'] : "px";
        $important_css = $important ? '!important' : '';
		$isCharacter = isset($array['type']) && $array['type'] == 'Character';

		if($spaces && !$isCharacter){
			$tablet_spacetop = array_key_exists('tablet-spacetop', $array) ? $array['tablet-spacetop'] : "0";
			$tablet_spacetopmu = array_key_exists('tablet-spacetopmu', $array) ? $array['tablet-spacetopmu'] : "px";
			$tablet_spacebottom = array_key_exists('tablet-spacebottom', $array) ? $array['tablet-spacebottom'] : "20";
			$tablet_spacebottommu = array_key_exists('tablet-spacebottommu', $array) ? $array['tablet-spacebottommu'] : "px";
		}

        if( $tablet_fontsizemu == 'vw' && $useScaleForGridder == 'useScaleForGridder' ) {
            $return = 'font-size:calc('.$tablet_fontsize.$tablet_fontsizemu.' / var(--gridder-vw-fontsize-scale));';
        }
        else {
            $return = 'font-size:'.$tablet_fontsize.$tablet_fontsizemu.$important_css.';';
        }

		if($spaces && !$isCharacter){
			$return .= 'margin:'.$tablet_spacetop.$tablet_spacetopmu.' 0 '.$tablet_spacebottom.$tablet_spacebottommu.' 0;';
		}

        if( FormatsManager::$hasPaddingBottomSettings && $includePaddingBottom ){
            $tablet_paddingbottom = array_key_exists('tablet-paddingbottom', $array) ? $array['tablet-paddingbottom'] : '0';
            $tablet_paddingbottommu = array_key_exists('tablet-paddingbottommu', $array) ? $array['tablet-paddingbottommu'] : 'px';
            $return .= 'padding-bottom:'.$tablet_paddingbottom.$tablet_paddingbottommu.$important_css.';';
        }

        if(LayFrontend_Options::$textformats_lineheight_in_px){
            $lh_mu = array_key_exists('tablet-lineheightmu', $array) ? $array['tablet-lineheightmu'] : '';
            $lh = array_key_exists('tablet-lineheight', $array) ? $array['tablet-lineheight'] : false;
            if($lh !== false) {
                $return .= 'line-height:'.$lh.$lh_mu.';';
            } else {
                // if not tablet lineheight was set, return desktop lineheight
                $lh_mu = array_key_exists('lineheightmu', $array) ? $array['lineheightmu'] : '';
                $lh = array_key_exists('lineheight', $array) ? $array['lineheight'] : false;
                $return .= 'line-height:'.$lh.$lh_mu.';';
            }
        }

		return $return;
	}

	/**
	 * Sanitize and format font-family value for valid CSS output
	 * Ensures comma-separated font lists are not wrapped in quotes
	 * and individual font names with spaces are properly quoted
	 */
	private static function sanitizeFontFamily($fontfamily) {
		if (empty($fontfamily)) {
			return 'sans-serif';
		}

		// Remove any wrapping quotes from the entire string
		$fontfamily = trim($fontfamily);
		$fontfamily = trim($fontfamily, '"\'');

		// Split by comma to get individual fonts
		$fonts = explode(',', $fontfamily);
		$sanitized_fonts = array();

		foreach ($fonts as $font) {
			$font = trim($font);
			// Remove any existing quotes
			$font = trim($font, '"\'');
			
			// Add quotes only if the font name contains spaces
			if (strpos($font, ' ') !== false) {
				$sanitized_fonts[] = "'" . $font . "'";
			} else {
				$sanitized_fonts[] = $font;
			}
		}

		return implode(', ', $sanitized_fonts);
	}

	private static function getVariableSettingsCSS($array, $settingsKey = 'variablesettings') {
		$variablesettings = '';
		if (array_key_exists($settingsKey, $array) && is_array($array[$settingsKey]) && count($array[$settingsKey]) > 0) {
			$values = array();
			foreach ($array[$settingsKey] as $obj) {
				if (!is_array($obj) || !array_key_exists('tag', $obj)) {
					continue;
				}
				$value = array_key_exists('value', $obj) ? $obj['value'] : (array_key_exists('defaultValue', $obj) ? $obj['defaultValue'] : 0);
				$values[] = '"'.$obj['tag'].'" '.$value;
			}
			if (count($values) > 0) {
				$variablesettings = 'font-variation-settings: '.join(', ', $values).';';
			}
		}
		return $variablesettings;
	}

	private static function isVariableStyleEnabled($format, $enabledKey) {
		if (!array_key_exists($enabledKey, $format)) {
			return false;
		}
		$value = $format[$enabledKey];
		return $value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on';
	}

	private static function getNestedStyleCSS($format, $enabledKey, $fontFamilyKey, $variableSettingsKey, $baseCss = '') {
		if (!FormatsManager::isVariableStyleEnabled($format, $enabledKey)) {
			return '';
		}

		$variableSettings = FormatsManager::getVariableSettingsCSS($format, $variableSettingsKey);
		if ($variableSettings !== '') {
			return $variableSettings;
		}

		$nestedCss = '';
		$fontFamily = '';
		if (array_key_exists($fontFamilyKey, $format) && is_string($format[$fontFamilyKey]) && trim($format[$fontFamilyKey]) !== '') {
			$fontFamily = $format[$fontFamilyKey];
		} elseif (array_key_exists('fontfamily', $format) && is_string($format['fontfamily']) && trim($format['fontfamily']) !== '') {
			$fontFamily = $format['fontfamily'];
		}

		if ($fontFamily !== '') {
			$nestedCss .= 'font-family:'.FormatsManager::sanitizeFontFamily($fontFamily).';';
		}
		$nestedCss .= $baseCss;

		return $nestedCss;
	}

	private static function getTextWrapAndLigatureCSS( $array, $important = false ) {
		$important_css = $important ? '!important' : '';
		$css = '';
		if ( array_key_exists( 'commonligatures', $array ) ) {
			$cl = $array['commonligatures'];
			if ( $cl === true || $cl === 1 || $cl === '1' || $cl === 'true' || $cl === 'on' ) {
				$css .= 'font-variant-ligatures: common-ligatures' . $important_css . ';';
			}
		}
		if ( isset( $array['type'] ) && $array['type'] === 'Paragraph' ) {
			$tw = array_key_exists( 'textwrap', $array ) ? (string) $array['textwrap'] : 'wrap';
			if ( $tw === 'balance' || $tw === 'pretty' ) {
				$css .= 'text-wrap: ' . $tw . $important_css . ';';
			} else {
				$css .= 'text-wrap: wrap' . $important_css . ';';
			}
		}
		return $css;
	}

	private static function appendSuffixToSelectorList($selectorList, $suffix) {
		$selectors = array_filter(array_map('trim', explode(',', (string) $selectorList)));
		if (empty($selectors)) {
			return trim((string) $selectorList).$suffix;
		}
		$withSuffix = array();
		foreach ($selectors as $selector) {
			$withSuffix[] = $selector.$suffix;
		}
		return implode(', ', $withSuffix);
	}

	private static function getFontCSS($array, $simple = false, $important = false, $includePaddingBottom = true) {
		// Check for both 'text-indent' (with hyphen) and 'textindent' (without hyphen)
		// Prefer 'text-indent' as that's what the React component uses
		$textindent = '0';
		if (array_key_exists('text-indent', $array)) {
			$textindent = (string)$array['text-indent'];
		} elseif (array_key_exists('textindent', $array)) {
			$textindent = (string)$array['textindent'];
		}
		$fontweight = array_key_exists('fontweight', $array) ? $array['fontweight'] : '400';
		$textalign = array_key_exists('textalign', $array) ? $array['textalign'] : 'left';

		$caps = "";
		if ( array_key_exists('caps', $array) && $array['caps'] == true ) {
			$caps = "text-transform:uppercase;";
		} else {
			$caps = "text-transform:none;";
		}

		$italic = "";
		if ( array_key_exists('italic', $array) && $array['italic'] == true ) {
			$italic = "font-style:italic;";
		} else {
			$italic = "font-style:normal;";
		}
		$underline = "";
		if ( array_key_exists('underline', $array) && $array['underline'] == true ) {
			$underline = "text-decoration: underline;";
		} else {
			$underline = "text-decoration: none;";
		}

		$borderbottom = "";
		if ( array_key_exists('borderbottom', $array) && $array['borderbottom'] == true ) {
			$borderbottom = "border-bottom: 1px solid;";
		} else {
			$borderbottom = "border-bottom: none;";
		}

		$variablesettings = FormatsManager::getVariableSettingsCSS($array, 'variablesettings');

        $lineheight = 'line-height:'.$array['lineheight'].';';
        if(LayFrontend_Options::$textformats_lineheight_in_px){
            // $lh_mu = array_key_exists('lineheightmu', $array) ? $array['lineheightmu'] : '';
            // $lh = array_key_exists('lineheight', $array) ? $array['lineheight'] : false;
            // if($lh !== false) {
            //     $return .= 'line-height:'.$lh.$lh_mu.';';
            // }
            // lineheight will be set in DesktopSpecificCSS
            $lineheight = '';

        }

		$important_css = $important ? '!important' : '';
		// Sanitize font-family to ensure valid CSS output
		$sanitized_fontfamily = FormatsManager::sanitizeFontFamily($array['fontfamily']);
		
		// todo: why do i need padding: 0? can i just remove it?
		$return =
		'font-family:'.$sanitized_fontfamily.$important_css.';'
		.'color:'.$array['color'].';'
		.'letter-spacing:'.$array['letterspacing'].'em;'
		.$lineheight
		.'font-weight:'.$fontweight.';'
		.$caps
		.$italic
		.$underline
		.$variablesettings;

		if( $simple == false ) {
			$return .=
			'padding: 0;'
			.'text-indent:'.$textindent.'em;'
			.$borderbottom
			.'text-align:'.$textalign.';';
		}

		// Add padding-bottom for Character formats when enabled
		if( FormatsManager::$hasPaddingBottomSettings && $includePaddingBottom ){
			$paddingbottom = array_key_exists('paddingbottom', $array) ? $array['paddingbottom'] : '0';
			$paddingbottommu = array_key_exists('paddingbottommu', $array) ? $array['paddingbottommu'] : 'px';
			$return .= 'padding-bottom:'.$paddingbottom.$paddingbottommu.$important_css.';';
		}

		$return .= FormatsManager::getTextWrapAndLigatureCSS( $array, $important );

		return $return;
	}

	public static function get_format_arr(){
		$formatsJsonString = FormatsManager::getDefaultFormatsJson();
		$formatsJsonArr = json_decode($formatsJsonString, true);
		return $formatsJsonArr;
	}

	/**
	 * Get a single format array by name, or null if not found.
	 */
	public static function get_format_by_name( $formatname ) {
		$formatsJsonArr = FormatsManager::get_format_arr();
		foreach ( $formatsJsonArr as $format ) {
			if ( isset( $format['formatname'] ) && $format['formatname'] === $formatname ) {
				return $format;
			}
		}
		return null;
	}

	/** Shorter name for phone-menu breakpoint format CSS. */
	public static function get_format_css_phone_menu( $formatname, $selector, $options = array() ) {
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$format = FormatsManager::get_format_by_name( $formatname );
		if ( $format === null ) {
			return '';
		}
		$opts = array_merge( array(
			'spaces' => true,
			'important' => false,
			'media_queries' => true,
			'include_padding_bottom' => true,
		), $options );
		return FormatsManager::get_css_for_selector_phone_menu( $format, $selector, $opts );
	}

	/** @deprecated Use get_format_css_phone_menu() instead. */
	public static function get_textformat_css_for_selector_for_phone_menu_breakpoint( $formatname, $selector, $options = array() ) {
		return FormatsManager::get_format_css_phone_menu( $formatname, $selector, $options );
	}

	public static function get_textformat_css_for_selector( $formatname, $selector, $options = array() ) {
		// Backward compat: old callers (e.g. gridder) pass (formatname, selector, $spaces, $important, $mediaQueries, $includePaddingBottom)
		if ( func_num_args() >= 4 && ! is_array( $options ) ) {
			$options = array(
				'spaces' => $options,
				'important' => func_get_arg( 3 ),
				'media_queries' => func_num_args() >= 5 ? func_get_arg( 4 ) : true,
				'include_padding_bottom' => func_num_args() >= 6 ? func_get_arg( 5 ) : true,
			);
		}
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$format = FormatsManager::get_format_by_name( $formatname );
		if ( $format === null ) {
			return '';
		}
		$opts = array_merge( array(
			'spaces' => true,
			'important' => false,
			'media_queries' => true,
			'include_padding_bottom' => true,
		), $options );
		return FormatsManager::get_css_for_selector( $format, $selector, $opts );
	}

	public static function frontend_textformats_css() {
		// Default format must be before all other formats, otherwise the default format's CSS will overwrite other textformats
		// thats why im using a extra variable $defaultFormatCSS here
		// Default format could be somewhere else because of re-ordering feature in textformats

		$formatsJsonArr = FormatsManager::get_format_arr();
		$defaultFormatCSS = '';
		$formatsCSS = '';

		for ($i = 0; $i<count($formatsJsonArr); $i++) {
			if( $formatsJsonArr[$i]["formatname"] == "Default" ) {
				$defaultFormatCSS .= FormatsManager::get_css_for_format( $formatsJsonArr[$i] );
			} else {
				$formatsCSS .= FormatsManager::get_css_for_format( $formatsJsonArr[$i] );
			}
		}

		$combinedFormats = $defaultFormatCSS.$formatsCSS;


		if ($combinedFormats != "") {
			wp_add_inline_style( 'frontend-style',
			$combinedFormats );
		}
	}

	public static function get_css_for_format($format){
		$formatsCSS = '';
		$strongVariableSettings = FormatsManager::getNestedStyleCSS($format, 'strong-variable-enabled', 'strong-fontfamily', 'strong-variablesettings', 'font-weight:400;');
		$italicVariableSettings = FormatsManager::getNestedStyleCSS($format, 'italic-variable-enabled', 'italic-fontfamily', 'italic-variablesettings', 'font-style:normal;');
		if ($format["formatname"] == "Default") {
			// "Default" textformat
			$formatsCSS .=
				'
					/* default text format "Default" */
					.lay-textformat-parent > *, ._Default, ._Default_no_spaces{
						'.FormatsManager::getFontCSS($format).'
					}';
			if ($strongVariableSettings !== '') {
				$formatsCSS .=
					'.lay-textformat-parent > * strong, ._Default strong, ._Default_no_spaces strong{
						'.$strongVariableSettings.'
					}';
			}
			if ($italicVariableSettings !== '') {
				$formatsCSS .=
					'.lay-textformat-parent > * em, .lay-textformat-parent > * i, ._Default em, ._Default i, ._Default_no_spaces em, ._Default_no_spaces i{
						'.$italicVariableSettings.'
					}';
			}

			if (FormatsManager::$hasTabletSettings) {
				$formatsCSS .=
					'@media (min-width: '.(MiscOptions::$tablet_breakpoint+1).'px){
						.lay-textformat-parent > *, ._Default{
							'.FormatsManager::getDesktopSpecificCSS($format, true).'
						}
						._Default_no_spaces{
							'.FormatsManager::getDesktopSpecificCSS($format, false).'
						}
						.lay-textformat-parent > *:last-child, ._Default:last-child{
							margin-bottom: 0;
						}
					}
					@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px) and (max-width: '.(MiscOptions::$tablet_breakpoint).'px){
						.lay-textformat-parent > *, ._Default{
							'.FormatsManager::getTabletSpecificCSS($format, true).'
						}
						._Default_no_spaces{
							'.FormatsManager::getTabletSpecificCSS($format, false).'
						}
						.lay-textformat-parent > *:last-child, ._Default:last-child{
							margin-bottom: 0;
						}
					}
					@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
						.lay-textformat-parent > *, ._Default{
							'.FormatsManager::getPhoneSpecificCSS($format, true).'
						}
						._Default_no_spaces{
							'.FormatsManager::getPhoneSpecificCSS($format, false).'
						}
						.lay-textformat-parent > *:last-child, ._Default:last-child{
							margin-bottom: 0;
						}
					}';
			} else {
				$formatsCSS .=
					'.lay-textformat-parent > *, ._Default, ._Default_no_spaces{
						'.FormatsManager::getFontCSS($format).'
					}
					.lay-textformat-parent > *:last-child, ._Default:last-child{
						margin-bottom: 0;
					}
					@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){
						.lay-textformat-parent > *, ._Default{
							'.FormatsManager::getDesktopSpecificCSS($format, true).'
						}
						._Default_no_spaces{
							'.FormatsManager::getDesktopSpecificCSS($format, false).'
						}
						.lay-textformat-parent > *:last-child, ._Default:last-child{
							margin-bottom: 0;
						}
					}
					@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
						.lay-textformat-parent > *, ._Default{
							'.FormatsManager::getPhoneSpecificCSS($format, true).'
						}
						._Default_no_spaces{
							'.FormatsManager::getPhoneSpecificCSS($format, false).'
						}
						.lay-textformat-parent > *:last-child, ._Default:last-child{
							margin-bottom: 0;
						}
					}';
			}
		} else {
			$formatsCSS .=
				'._'.$format['formatname'].', ._'.$format['formatname'].'_no_spaces{'
					.FormatsManager::getFontCSS($format)
				.'}';
			if ($strongVariableSettings !== '') {
				$formatsCSS .=
					'._'.$format['formatname'].' strong, ._'.$format['formatname'].'_no_spaces strong{'
						.$strongVariableSettings
					.'}';
			}
			if ($italicVariableSettings !== '') {
				$formatsCSS .=
					'._'.$format['formatname'].' em, ._'.$format['formatname'].' i, ._'.$format['formatname'].'_no_spaces em, ._'.$format['formatname'].'_no_spaces i{'
						.$italicVariableSettings
					.'}';
			}
			// custom textformats
			if (FormatsManager::$hasTabletSettings) {
				$formatsCSS .=
					'@media (min-width: '.(MiscOptions::$tablet_breakpoint+1).'px){
						._'.$format['formatname'].'{'
							.FormatsManager::getDesktopSpecificCSS($format, true).
						'}
						._'.$format['formatname'].':last-child{
							margin-bottom: 0;
						}
						._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getDesktopSpecificCSS($format, false).
						'}
					}
						@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px) and (max-width: '.((int)MiscOptions::$tablet_breakpoint).'px){
							._'.$format['formatname'].'{'
								.FormatsManager::getTabletSpecificCSS($format, true).
							'}
							._'.$format['formatname'].':last-child{
								margin-bottom: 0;
							}
							._'.$format['formatname'].'_no_spaces{'
								.FormatsManager::getTabletSpecificCSS($format, false).
							'}
						}
						@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
							._'.$format['formatname'].'{'
								.FormatsManager::getPhoneSpecificCSS($format, true).
							'}
							._'.$format['formatname'].':last-child{
								margin-bottom: 0;
							}
							._'.$format['formatname'].'_no_spaces{'
								.FormatsManager::getPhoneSpecificCSS($format, false).
							'}
						}';
			} else {
				$formatsCSS .=
					'@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){
						._'.$format['formatname'].'{'
							.FormatsManager::getDesktopSpecificCSS($format, true)
						.'}
						._'.$format['formatname'].':last-child{
							margin-bottom: 0;
						}
						._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getDesktopSpecificCSS($format, false)
						.'}
					}'
					.'@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
						._'.$format['formatname'].'{'
							.FormatsManager::getPhoneSpecificCSS($format, true)
						.'}
						._'.$format['formatname'].':last-child{
							margin-bottom: 0;
						}
						._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getPhoneSpecificCSS($format, false)
						.'}
					}';
			}
		}
		return $formatsCSS;
	}

	/** CSS for a format + selector using phone-menu breakpoint (not main phone breakpoint). */
	public static function get_css_for_selector_phone_menu( $format, $selector, $options = array() ) {
		$opts = array_merge( array(
			'spaces' => true,
			'important' => false,
			'media_queries' => true,
			'include_padding_bottom' => true,
		), $options );
		$spaces = $opts['spaces'];
		$important = $opts['important'];
		$mediaQueries = $opts['media_queries'];
		$includePaddingBottom = $opts['include_padding_bottom'];

		$formatsCSS = '';
		$strongVariableSettings = FormatsManager::getNestedStyleCSS($format, 'strong-variable-enabled', 'strong-fontfamily', 'strong-variablesettings', 'font-weight:400;');
		$italicVariableSettings = FormatsManager::getNestedStyleCSS($format, 'italic-variable-enabled', 'italic-fontfamily', 'italic-variablesettings', 'font-style:normal;');
		$selectorStrong = FormatsManager::appendSuffixToSelectorList($selector, ' strong');
		$selectorEm = FormatsManager::appendSuffixToSelectorList($selector, ' em');
		$selectorI = FormatsManager::appendSuffixToSelectorList($selector, ' i');
		$nestedVariableSelectorCss = ($strongVariableSettings !== '' ? $selectorStrong.'{'.$strongVariableSettings.'}' : '').($italicVariableSettings !== '' ? $selectorEm.', '.$selectorI.'{'.$italicVariableSettings.'}' : '');
        if( $mediaQueries == false ) {
            return $selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
                '.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss;
        }
		if (FormatsManager::$hasTabletSettings) {
			$formatsCSS .=
			$selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss.'
			@media (min-width: '.(MiscOptions::$tablet_breakpoint+1).'px){
				'.$selector.'{
					'.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (min-width: '.(MiscOptions::$phone_menu_breakpoint+1).'px) and (max-width: '.(MiscOptions::$tablet_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getTabletSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (max-width: '.(MiscOptions::$phone_menu_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getPhoneSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}';
		} else {
			$formatsCSS .=
			$selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss.'
			@media (min-width: '.(MiscOptions::$phone_menu_breakpoint+1).'px){
				'.$selector.'{
					'.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (max-width: '.(MiscOptions::$phone_menu_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getPhoneSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}';
		}
		return $formatsCSS;
	}

	/** @deprecated Use get_css_for_selector_phone_menu() instead. */
	public static function get_css_for_selector_for_phone_menu_breakpoint( $format, $selector, $options = array() ) {
		return FormatsManager::get_css_for_selector_phone_menu( $format, $selector, $options );
	}

	public static function get_css_for_selector( $format, $selector, $options = array() ) {
		$opts = array_merge( array(
			'spaces' => true,
			'important' => false,
			'media_queries' => true,
			'include_padding_bottom' => true,
		), $options );
		$spaces = $opts['spaces'];
		$important = $opts['important'];
		$mediaQueries = $opts['media_queries'];
		$includePaddingBottom = $opts['include_padding_bottom'];

		$formatsCSS = '';
		$strongVariableSettings = FormatsManager::getNestedStyleCSS($format, 'strong-variable-enabled', 'strong-fontfamily', 'strong-variablesettings', 'font-weight:400;');
		$italicVariableSettings = FormatsManager::getNestedStyleCSS($format, 'italic-variable-enabled', 'italic-fontfamily', 'italic-variablesettings', 'font-style:normal;');
		$selectorStrong = FormatsManager::appendSuffixToSelectorList($selector, ' strong');
		$selectorEm = FormatsManager::appendSuffixToSelectorList($selector, ' em');
		$selectorI = FormatsManager::appendSuffixToSelectorList($selector, ' i');
		$nestedVariableSelectorCss = ($strongVariableSettings !== '' ? $selectorStrong.'{'.$strongVariableSettings.'}' : '').($italicVariableSettings !== '' ? $selectorEm.', '.$selectorI.'{'.$italicVariableSettings.'}' : '');
        if( $mediaQueries == false ) {
            return $selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
                '.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss;
        }
		if (FormatsManager::$hasTabletSettings) {
			$formatsCSS .=
			$selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss.'
			@media (min-width: '.(MiscOptions::$tablet_breakpoint+1).'px){
				'.$selector.'{
					'.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px) and (max-width: '.(MiscOptions::$tablet_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getTabletSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getPhoneSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}';
		} else {
			$formatsCSS .=
			$selector.'{
				'.FormatsManager::getFontCSS($format, true, $important, $includePaddingBottom).'
			}'.$nestedVariableSelectorCss.'
			@media (min-width: '.(MiscOptions::$phone_breakpoint+1).'px){
				'.$selector.'{
					'.FormatsManager::getDesktopSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}
			@media (max-width: '.(MiscOptions::$phone_breakpoint).'px){
				'.$selector.'{
					'.FormatsManager::getPhoneSpecificCSS($format, $spaces, '', $important, $includePaddingBottom).'
				}
			}';
		}
		return $formatsCSS;
	}

	public static function gridder_textformats_css() {
		$screen = get_current_screen();
		if ( LTUtility::is_admin_page_with_a_gridder($screen) ) {
			// $formatsJsonString = FormatsManager::getDefaultFormatsJson();
			$formatsJsonString = FormatsManager::getDefaultFormatsJson();
			$formatsJsonArr = json_decode($formatsJsonString, true);

			$formatsCSS = '';

			for ($i = 0; $i<count($formatsJsonArr); $i++) {
				$format = $formatsJsonArr[$i];
				$strongVariableSettings = FormatsManager::getNestedStyleCSS($format, 'strong-variable-enabled', 'strong-fontfamily', 'strong-variablesettings', 'font-weight:400;');
				$italicVariableSettings = FormatsManager::getNestedStyleCSS($format, 'italic-variable-enabled', 'italic-fontfamily', 'italic-variablesettings', 'font-style:normal;');
				if ($format["formatname"] == "Default") {
					// "Default" textformat

					echo
						'<!-- default text format "Default" -->
						<style class="textformats-style">
							/* #poststuff selectors: WordPress adds #poststuff h2 { font-size: 14px } etc. on the post edit screen; without these, that would override our textformat font-size */
							#gridder .lay-textformat-parent > *,
							#gridder ._Default,
							#gridder ._Default_no_spaces,
							#poststuff .lay-textformat-parent > *,
							#poststuff ._Default,
							#poststuff ._Default_no_spaces{
								'.FormatsManager::getFontCSS($format).'
							}
							'.($strongVariableSettings !== '' ? '
							#gridder .lay-textformat-parent > * strong,
							#gridder ._Default strong,
							#gridder ._Default_no_spaces strong,
							#poststuff .lay-textformat-parent > * strong,
							#poststuff ._Default strong,
							#poststuff ._Default_no_spaces strong{
								'.$strongVariableSettings.'
							}' : '').'
							'.($italicVariableSettings !== '' ? '
							#gridder .lay-textformat-parent > * em,
							#gridder .lay-textformat-parent > * i,
							#gridder ._Default em,
							#gridder ._Default i,
							#gridder ._Default_no_spaces em,
							#gridder ._Default_no_spaces i,
							#poststuff .lay-textformat-parent > * em,
							#poststuff .lay-textformat-parent > * i,
							#poststuff ._Default em,
							#poststuff ._Default i,
							#poststuff ._Default_no_spaces em,
							#poststuff ._Default_no_spaces i{
								'.$italicVariableSettings.'
							}' : '').'
							#gridder .show-desktop-version .lay-textformat-parent > *,
							#gridder .show-desktop-version ._Default,
							#gridder .lay-input-modal .preview-desktop ._Default{
								'.FormatsManager::getDesktopSpecificCSS($format, true, 'useScaleForGridder').'
							}
							#gridder .show-desktop-version ._Default_no_spaces,
							#gridder .lay-input-modal .preview-desktop ._Default_no_spaces{
								'.FormatsManager::getDesktopSpecificCSS($format, false, 'useScaleForGridder').'
							}
							#gridder .lay-input-modal .preview-tablet ._Default{
								'.FormatsManager::getTabletSpecificCSS($format, true, 'useScaleForGridder').'
							}
							#gridder .lay-input-modal .preview-tablet ._Default_no_spaces{
								'.FormatsManager::getTabletSpecificCSS($format, false, 'useScaleForGridder').'
							}
							#gridder .show-phone-version .lay-textformat-parent > *,
							#gridder .show-phone-version ._Default,
							#gridder .lay-input-modal .preview-phone ._Default{
								'.FormatsManager::getPhoneSpecificCSS($format, true, 'useScaleForGridder').'
							}
							#gridder .show-phone-version ._Default_no_spaces,
							#gridder .lay-input-modal .preview-phone ._Default_no_spaces{
								'.FormatsManager::getPhoneSpecificCSS($format, false, 'useScaleForGridder').'
							}
						'.($strongVariableSettings !== '' ? '
						#gridder .lay-input-modal .preview-desktop ._Default strong,
						#gridder .lay-input-modal .preview-tablet ._Default strong,
						#gridder .lay-input-modal .preview-phone ._Default strong{
							'.$strongVariableSettings.'
						}' : '').'
						'.($italicVariableSettings !== '' ? '
						#gridder .lay-input-modal .preview-desktop ._Default em,
						#gridder .lay-input-modal .preview-desktop ._Default i,
						#gridder .lay-input-modal .preview-tablet ._Default em,
						#gridder .lay-input-modal .preview-tablet ._Default i,
						#gridder .lay-input-modal .preview-phone ._Default em,
						#gridder .lay-input-modal .preview-phone ._Default i{
							'.$italicVariableSettings.'
						}' : '').'
							/* for draggable items; #poststuff so textformat wins over WordPress #poststuff h2 styles on post edit screen */
							.lay-textformat-parent > *,
							._Default,
							._Default_no_spaces,
							#poststuff .lay-textformat-parent > *,
							#poststuff ._Default,
							#poststuff ._Default_no_spaces{
								'.FormatsManager::getFontCSS($format).'
								'.FormatsManager::getDesktopSpecificCSS($format, false).'
							}
						</style>';
				} else {
					// custom textformats (same #poststuff selectors as Default: beat WordPress #poststuff h2 { font-size: 14px } on post edit screen)
					$formatsCSS .=
						'#gridder .lay-textformat-parent ._'.$format['formatname'].',
						#poststuff .lay-textformat-parent ._'.$format['formatname'].'{'
							.FormatsManager::getFontCSS($format).
						'}
						#gridder ._'.$format['formatname'].'_no_spaces,
						#poststuff ._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getFontCSS($format).
						'}
						'.($strongVariableSettings !== '' ? '
						#gridder .lay-textformat-parent ._'.$format['formatname'].' strong,
						#gridder ._'.$format['formatname'].'_no_spaces strong,
						#poststuff .lay-textformat-parent ._'.$format['formatname'].' strong,
						#poststuff ._'.$format['formatname'].'_no_spaces strong{
							'.$strongVariableSettings.'
						}' : '').'
						'.($italicVariableSettings !== '' ? '
						#gridder .lay-textformat-parent ._'.$format['formatname'].' em,
						#gridder .lay-textformat-parent ._'.$format['formatname'].' i,
						#gridder ._'.$format['formatname'].'_no_spaces em,
						#gridder ._'.$format['formatname'].'_no_spaces i,
						#poststuff .lay-textformat-parent ._'.$format['formatname'].' em,
						#poststuff .lay-textformat-parent ._'.$format['formatname'].' i,
						#poststuff ._'.$format['formatname'].'_no_spaces em,
						#poststuff ._'.$format['formatname'].'_no_spaces i{
							'.$italicVariableSettings.'
						}' : '').'
						#gridder .show-desktop-version .lay-textformat-parent ._'.$format['formatname'].',
						#gridder .lay-input-modal .preview-desktop ._'.$format['formatname'].'{'
							.FormatsManager::getDesktopSpecificCSS($format, true, 'useScaleForGridder').
						'}
						#gridder .show-desktop-version ._'.$format['formatname'].'_no_spaces,
						#gridder .lay-input-modal .preview-desktop ._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getDesktopSpecificCSS($format, false, 'useScaleForGridder').
						'}
						#gridder .lay-input-modal .preview-tablet ._'.$format['formatname'].'{
							'.FormatsManager::getTabletSpecificCSS($format, true, 'useScaleForGridder').'
						}
						#gridder .lay-input-modal .preview-tablet ._'.$format['formatname'].'_no_spaces{
							'.FormatsManager::getTabletSpecificCSS($format, false, 'useScaleForGridder').'
						}
						#gridder .show-phone-version .lay-textformat-parent ._'.$format['formatname'].',
						#gridder .lay-input-modal .preview-phone ._'.$format['formatname'].'{'
							.FormatsManager::getPhoneSpecificCSS($format, true, 'useScaleForGridder').
						'}
						#gridder .show-phone-version ._'.$format['formatname'].'_no_spaces,
						#gridder .lay-input-modal .preview-phone ._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getPhoneSpecificCSS($format, false, 'useScaleForGridder').
						'}
						'.($strongVariableSettings !== '' ? '
						#gridder .lay-input-modal .preview-desktop ._'.$format['formatname'].' strong,
						#gridder .lay-input-modal .preview-tablet ._'.$format['formatname'].' strong,
						#gridder .lay-input-modal .preview-phone ._'.$format['formatname'].' strong{
							'.$strongVariableSettings.'
						}' : '').'
						'.($italicVariableSettings !== '' ? '
						#gridder .lay-input-modal .preview-desktop ._'.$format['formatname'].' em,
						#gridder .lay-input-modal .preview-desktop ._'.$format['formatname'].' i,
						#gridder .lay-input-modal .preview-tablet ._'.$format['formatname'].' em,
						#gridder .lay-input-modal .preview-tablet ._'.$format['formatname'].' i,
						#gridder .lay-input-modal .preview-phone ._'.$format['formatname'].' em,
						#gridder .lay-input-modal .preview-phone ._'.$format['formatname'].' i{
							'.$italicVariableSettings.'
						}' : '').'
						/* for draggable items */
						.lay-textformat-parent ._'.$format['formatname'].',
						#poststuff .lay-textformat-parent ._'.$format['formatname'].'{'
							.FormatsManager::getFontCSS($format)
							.FormatsManager::getDesktopSpecificCSS($format, true).
						'}
						._'.$format['formatname'].'_no_spaces,
						#poststuff ._'.$format['formatname'].'_no_spaces{'
							.FormatsManager::getFontCSS($format)
							.FormatsManager::getDesktopSpecificCSS($format, false).
						'}';
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

	// 
	public static function updateCustomizerStylesLinkedWithTextformats(){
		$textFormatsJsonString = FormatsManager::getDefaultFormatsJson();
		$textFormatsJsonArr = json_decode($textFormatsJsonString, true);

		$prefixes  = array('st_', 'pt_', 'nav_', 'pa_', 'tagline_', 'm_st_', 'intro_text_');

		foreach ($prefixes as $prefix) {
			$format_name = get_theme_mod($prefix.'textformat', 'Default');
			if($format_name){
				foreach ($textFormatsJsonArr as $value) {
					if ($format_name == $value['formatname']) {

						$fontweight = array_key_exists('fontweight', $value) ? $value['fontweight'] : '400';
						$fontsizemu = array_key_exists('fontsizemu', $value) ? $value['fontsizemu'] : 'px';

						set_theme_mod($prefix.'color', $value['color']);
						set_theme_mod($prefix.'fontfamily', $value['fontfamily']);
						set_theme_mod($prefix.'fontweight', $fontweight);
						set_theme_mod($prefix.'letterspacing', $value['letterspacing']);
						
						// for mobile site title i need to set phone fontsize and phone fontsizemu instead of desktop versions
						if($prefix == "m_st_"){
							$phonefontsize = array_key_exists('phone-fontsize', $value) ? $value['phone-fontsize'] : '16';
							set_theme_mod('mobile_menu_sitetitle_fontsize', $phonefontsize);

							$phonefontsizemu = array_key_exists('phone-fontsizemu', $value) ? $value['phone-fontsizemu'] : 'px';
							set_theme_mod('m_st_fontsize_mu', $phonefontsizemu);

						} else {
							set_theme_mod($prefix.'fontsize', $value['fontsize']);
							set_theme_mod($prefix.'fontsize_mu', $fontsizemu);
						}

						if ($prefix == "pt_") {
							set_theme_mod($prefix.'lineheight', $value['lineheight']);
							set_theme_mod($prefix.'align', $value['textalign']);
						}

						if ($prefix == "intro_text_") {
							set_theme_mod($prefix.'lineheight', $value['lineheight']);
							set_theme_mod($prefix.'align', $value['textalign']);
						}

					}
				}
			}
		}

	}

	public static function formatsmanager_styles($hook) {
        if( strpos($hook, 'manage-textformats') !== false ){
			// New React-based FormatsManager
			wp_enqueue_style( 'formatsmanager-styles', Setup::$uri.'/formatsmanager/assets/css/formatsmanager.css', array(), Setup::$ver );
		}
	}

	public static function register_scripts(){
		// New React-based FormatsManager - all bundled in the UMD file
		wp_register_script( 'lay-opentype', 'https://cdn.jsdelivr.net/npm/opentype.js@1.3.4/dist/opentype.min.js', array(), '1.3.4', false );
		wp_register_script( 'lay-variablefont', Setup::$uri."/formatsmanager/assets/js/vendor/variablefont.js", array(),  Setup::$ver, false );
	}

	public static function formatsmanager_scripts($hook){
        if( strpos($hook, 'manage-textformats') !== false ){
			// Load dependencies (React is bundled in formatsmanager.umd.js)
			wp_enqueue_script( 'lay-opentype' );
			wp_enqueue_script( 'lay-variablefont' );
			
			// Enqueue FormatsManager UMD bundle (React bundled inside)
			wp_enqueue_script( 'formatsmanager-app', Setup::$uri."/formatsmanager/assets/js/formatsmanager.umd.js", array( 'lay-opentype', 'lay-variablefont' ), Setup::$ver, true);
			
		$bg_color_palette = get_option( 'misc_options_colorpalette', '#000, #fff, #f00, #0f0, #00f, #ff0, #0ff, #f0f' );
		$bg_color_palette = Setup::turnTo6DigitHexString($bg_color_palette);

		//turn into array
		$bg_color_palette = explode( ',', $bg_color_palette );
		
		// Prepare system fonts for JS
		$systemFonts = array();
		foreach (FontManager::$originalFonts as $name => $value) {
			$systemFonts[] = array(
				'name' => $name,
				'value' => $value,
				'isVariable' => false
			);
		}
		
		wp_localize_script( 'formatsmanager-app', 'formatslgPassedData', array(
			'advancedLineHeights' => LayFrontend_Options::$textformats_lineheight_in_px,
			'showTabletSettings' => FormatsManager::$hasTabletSettings,
			'woocommerceActive' => class_exists('WooCommerce'),
			'bgColorPalette' => $bg_color_palette,
			'systemFonts' => $systemFonts,
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'lay_formatsmanager_nonce' ),
			'i18n' => array(
				'save' => lay_t('Save Changes', 'save_changes'),
				'saving' => lay_t('Saving…', 'saving'),
				'saved' => lay_t('Text Formats updated.', 'text_formats_updated'),
				'error' => lay_t('Saving text formats failed. Please try again.', 'saving_text_formats_failed_please_try_again'),
				'whatAreTitle' => lay_t('What are Text Formats?', 'formatsmanager_what_are_title'),
				'whatAreIntro' => lay_t('Predefine Text Formats (styles) below and use them on any texts. To have a consistent look, use them in Projects, Pages and the Customizer.', 'formatsmanager_what_are_intro'),
				'whatAreExample' => lay_t('Example: Create a Text Format. Apply it to your menu in the Customizer. Edit the format here → the menu text style changed too.', 'formatsmanager_what_are_example'),
				'whatAreDefault' => lay_t('By default, the "Default" format is in use everywhere. Give it a try and change it!', 'formatsmanager_what_are_default'),
				'whatAreListSpace' => lay_t('Define spaces above and below your paragraphs with "Space Top" and "Space Bottom"', 'formatsmanager_what_are_list_space'),
				'whatAreListCleanCode' => lay_t('The code of your site will be much cleaner', 'formatsmanager_what_are_list_clean_code'),
				'whatAreListPhoneStyles' => lay_t('Set extra styles for the phone view like "Phone Font Size", "Phone Space Top", "Phone Space Bottom"', 'formatsmanager_what_are_list_phone_styles'),
				'whatAreListWebfontsPrefix' => lay_t('Supports webfonts! Upload them', 'formatsmanager_what_are_list_webfonts_prefix'),
				'howToUseTitle' => lay_t('How to use', 'formatsmanager_how_to_use_title'),
				'step1Title' => lay_t('Step 1: Create a new Format', 'formatsmanager_step1_title'),
				'step1BodyIntro' => lay_t('Choose a Format Type below and type in a Format Name. Optionally choose an existing format to create a copy of it. Then click on', 'formatsmanager_step1_body_intro'),
				'step1AddFormatLabel' => lay_t('Add Format', 'formatsmanager_add_format_label'),
				'step1BodyAfterAdd' => lay_t('Note that once a format is created you can\'t change its name. Edit the format until you\'re happy, then click', 'formatsmanager_step1_body_after_add'),
				'step1SaveChangesLabel' => lay_t('Save Changes', 'save_changes'),
				'step1BodyAfterSave' => lay_t('.', 'formatsmanager_step1_body_after_save'),
				'step1Note' => lay_t('Paragraph and Headline formats can only be applied to blocks of text. Character formats can be used for words within a block of text.', 'formatsmanager_step1_note'),
				'step2Title' => lay_t('Step 2: Usage', 'formatsmanager_step2_title'),
				'step2BodyIntro' => lay_t('Your Text Formats will appear in the "Formats" dropdown menu when you edit texts in "Projects" and "Pages".', 'formatsmanager_step2_body_intro'),
				'step2ClearFormattingPrefix' => lay_t('If you apply your Text Format and it does not look right try this: Select your text, click', 'formatsmanager_step2_clear_prefix'),
				'step2ClearFormattingLabel' => lay_t('Clear formatting', 'formatsmanager_step2_clear_label'),
				'step2ClearFormattingSuffix' => lay_t(', then re-apply your Text Format.', 'formatsmanager_step2_clear_suffix'),
				'step3Body' => lay_t('In the Customizer your formats show up in the "Text Format" dropdown menus for Site Title, Menu and Project Title.', 'formatsmanager_step3_body'),
				'addWebfontsHerePrefix' => lay_t('Add Webfonts', 'formatsmanager_add_webfonts_prefix'),
				'linkHere' => lay_t('here', 'formatsmanager_here'),
				'settingsTitle' => lay_t('Settings', 'formatsmanager_settings_title'),
				'settingsAddTablet' => lay_t('Add "Tablet" settings to Textformats', 'formatsmanager_settings_add_tablet'),
				'settingsTabletBreakpointLabel' => lay_t('Tablet Breakpoint for Textformats', 'formatsmanager_settings_tablet_breakpoint_label'),
				'settingsTabletBreakpointHelp' => lay_t('Breakpoint between desktop and tablet version. Needs to be bigger than "Phone Breakpoint" (%s px).', 'formatsmanager_settings_tablet_breakpoint_help'),
				'settingsLineheightPxLabel' => lay_t('Line Height can be px', 'formatsmanager_settings_lineheight_px_label'),
				'settingsLineheightPxHelp' => lay_t('Be able to set desktop, tablet and phone line heights independently and in px or em.', 'formatsmanager_settings_lineheight_px_help'),
				'settingsPaddingBottomLabel' => lay_t('Add Padding Bottom Settings (Use this for example if the lower part of g is cut off for a headline)', 'formatsmanager_settings_padding_bottom_label'),
				'addFormatButton' => lay_t('Add Format', 'formatsmanager_add_format_button'),
				'addTextStyleButton' => lay_t('Add Text Style', 'formatsmanager_add_text_style_button'),
				'addNewTextFormatTitle' => lay_t('Add New Text Format', 'formatsmanager_add_new_text_format_title'),
				'addNewTextStyleTitle' => lay_t('Add New Text Style', 'formatsmanager_add_new_text_style_title'),
				'formatTypeLabel' => lay_t('Format Type', 'formatsmanager_format_type_label'),
				'formatTypeParagraph' => lay_t('Paragraph', 'formatsmanager_format_type_paragraph'),
				'formatTypeHeadline' => lay_t('Headline', 'formatsmanager_format_type_headline'),
				'formatTypeCharacter' => lay_t('Character', 'formatsmanager_format_type_character'),
				'formatNameLabel' => lay_t('Format Name', 'formatsmanager_format_name_label'),
				'formatNamePlaceholder' => lay_t('Format Name', 'formatsmanager_format_name_placeholder'),
				'copySettingsLabel' => lay_t('Copy Settings from (optional)', 'formatsmanager_copy_settings_label'),
				'selectPlaceholder' => lay_t('--SELECT--', 'formatsmanager_select_placeholder'),
				'cancel' => lay_t('Cancel', 'formatsmanager_cancel'),
				'addFormat' => lay_t('Add Format', 'formatsmanager_add_format'),
				'validationNameRequired' => lay_t('Please enter a format name.', 'formatsmanager_validation_name_required'),
				'validationNameInvalid' => lay_t('Use only letters, numbers, underscores and no special characters.', 'formatsmanager_validation_name_invalid'),
				'validationNameExists' => lay_t('This format name already exists.', 'formatsmanager_validation_name_exists'),
				'validationNameOk' => lay_t('Name looks good.', 'formatsmanager_validation_name_ok'),
				'copied' => lay_t('Copied!', 'formatsmanager_copied'),
				'delete' => lay_t('Delete', 'formatsmanager_delete'),
				'confirmDelete' => lay_t('Really delete?', 'formatsmanager_confirm_delete'),
				'headlineTypeLabel' => lay_t('Headline Type', 'formatsmanager_headline_type_label'),
				'fontFamilyLabel' => lay_t('Font Family', 'formatsmanager_font_family_label'),
				'fontSizeLabel' => lay_t('Font Size', 'formatsmanager_font_size_label'),
				'colorLabel' => lay_t('Color', 'formatsmanager_color_label'),
				'fontWeightLabel' => lay_t('Font Weight', 'formatsmanager_font_weight_label'),
				'fontStyleLabel' => lay_t('Font Style', 'formatsmanager_font_style_label'),
				'styleItalic' => lay_t('Italic', 'formatsmanager_style_italic'),
				'styleUnderline' => lay_t('Underline', 'formatsmanager_style_underline'),
				'styleAllCaps' => lay_t('All Caps', 'formatsmanager_style_all_caps'),
				'lineHeightLabel' => lay_t('Line Height', 'formatsmanager_line_height_label'),
				'textIndentLabel' => lay_t('Text Indent', 'formatsmanager_text_indent_label'),
				'letterSpacingLabel' => lay_t('Letter Spacing', 'formatsmanager_letter_spacing_label'),
				'textAlignLabel' => lay_t('Text Align', 'formatsmanager_text_align_label'),
				'textWrapLabel' => lay_t('Text wrap', 'formatsmanager_text_wrap_label'),
				'textWrapWrap' => lay_t('Wrap', 'formatsmanager_text_wrap_wrap'),
				'textWrapBalance' => lay_t('Balance', 'formatsmanager_text_wrap_balance'),
				'textWrapPretty' => lay_t('Pretty', 'formatsmanager_text_wrap_pretty'),
				'commonLigaturesLabel' => lay_t('Common ligatures', 'formatsmanager_common_ligatures_label'),
				'alignLeft' => lay_t('Align Left', 'formatsmanager_align_left'),
				'alignCenter' => lay_t('Align Center', 'formatsmanager_align_center'),
				'alignRight' => lay_t('Align Right', 'formatsmanager_align_right'),
				'alignJustify' => lay_t('Justify', 'formatsmanager_align_justify'),
				'spaceTopLabel' => lay_t('Space Top', 'formatsmanager_space_top_label'),
				'spaceBottomLabel' => lay_t('Space Bottom', 'formatsmanager_space_bottom_label'),
				'paddingBottomLabel' => lay_t('Padding Bottom', 'formatsmanager_padding_bottom_label'),
				'enableLabel' => lay_t('enable', 'formatsmanager_enable_label'),
				'variableFontSettings' => lay_t('Variable Font Settings', 'formatsmanager_variable_font_settings'),
				'boldVariableFontSettings' => lay_t('Bold Font Settings', 'formatsmanager_bold_variable_font_settings'),
				'italicVariableFontSettings' => lay_t('Italic Font Settings', 'formatsmanager_italic_variable_font_settings'),
				'previewDesktop' => lay_t('Desktop:', 'formatsmanager_preview_desktop'),
				'previewTablet' => lay_t('Tablet:', 'formatsmanager_preview_tablet'),
				'previewPhone' => lay_t('Phone:', 'formatsmanager_preview_phone'),
				'tabletLabel' => lay_t('Tablet', 'formatsmanager_tablet_label'),
				'phoneLabel' => lay_t('Phone', 'formatsmanager_phone_label'),
				'fontWeightThin' => lay_t('Thin (100)', 'formatsmanager_font_weight_thin'),
				'fontWeightExtraLight' => lay_t('Extra Light (200)', 'formatsmanager_font_weight_extra_light'),
				'fontWeightLight' => lay_t('Light (300)', 'formatsmanager_font_weight_light'),
				'fontWeightNormal' => lay_t('Normal (400)', 'formatsmanager_font_weight_normal'),
				'fontWeightMedium' => lay_t('Medium (500)', 'formatsmanager_font_weight_medium'),
				'fontWeightSemiBold' => lay_t('Semi Bold (600)', 'formatsmanager_font_weight_semi_bold'),
				'fontWeightBold' => lay_t('Bold (700)', 'formatsmanager_font_weight_bold'),
				'fontWeightExtraBold' => lay_t('Extra Bold (800)', 'formatsmanager_font_weight_extra_bold'),
				'fontWeightBlack' => lay_t('Black (900)', 'formatsmanager_font_weight_black'),
				'variableLabel' => lay_t('Variable', 'formatsmanager_variable_label'),
				'previewSamples' => array(
					lay_t('Hello from the typography playground!', 'formatsmanager_preview_sample_1'),
					lay_t('The quick brown fox brings snacks for friends.', 'formatsmanager_preview_sample_2'),
					lay_t('Sunsets love good kerning and tall leading.', 'formatsmanager_preview_sample_3'),
					lay_t('Type is what feelings look like in letters.', 'formatsmanager_preview_sample_4'),
					lay_t('Coffee, sketches, and carefully tuned line height.', 'formatsmanager_preview_sample_5'),
				),
				'variableLoading' => lay_t('Loading variable font data...', 'formatsmanager_variable_loading'),
				'variableNoAxes' => lay_t('No variable font axes detected. Make sure the selected font is a variable font.', 'formatsmanager_variable_no_axes'),
				'variableUnnamedAxis' => lay_t('Unnamed Axis', 'formatsmanager_variable_unnamed_axis'),
				'variableUnnamedPreset' => lay_t('Unnamed Preset', 'formatsmanager_variable_unnamed_preset'),
				'variablePresetsLabel' => lay_t('Variable Font Presets', 'formatsmanager_variable_presets_label'),
				'formatsJsonParseError' => lay_t('Text Formats JSON cannot be parsed! Please dont save the textformats and write info@laytheme.com', 'formatsmanager_formats_json_parse_error'),
			),
			'expandWhatAreTextFormatsStatus' => get_option( 'expand-what_are_text_formats', 'collapsed' ),
			'expandHowToUseTextFormatsStatus' => get_option( 'expand-how_to_use_text_formats', 'expanded' ),
			'expandTextformatsSettingsStatus' => get_option( 'expand-textformats_settings', 'expanded' ),
			'webfontsUrl' => admin_url( 'admin.php?page=manage-webfonts' ),
			'templateDirectoryUri' => get_template_directory_uri(),
			'textformatsForTablet' => get_option( 'misc_options_textformats_for_tablet', 'on' ),
			'textformatsTabletBreakpoint' => get_option( 'misc_options_textformats_tablet_breakpoint', 1024 ),
			'textformatsLineheightInPx' => get_option( 'misc_options_textformats_lineheight_in_px', '' ),
			'textformatsPaddingBottom' => get_option( 'misc_options_textformats_padding_bottom', '' ),
			'phoneBreakpoint' => (int)get_option( 'lay_breakpoint', 600 ),
		) 
	);

		// Enqueue collapse/expand script for explanation boxes
		wp_enqueue_script(
			'lay-collapse-and-expand-explanations',
			Setup::$uri . '/setup/assets/js/collapse_and_expand_explanations.js',
			array( 'jquery', 'underscore' ),
			Setup::$ver,
			true
		);
		wp_localize_script( 'lay-collapse-and-expand-explanations', 'setupPassedData', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	public static function register_settings() {
		register_setting( 'admin-textformats-settings', 'formatsmanager_json' );
	}

	public static function ajax_save_formats() {
		check_ajax_referer( 'lay_formatsmanager_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => lay_t('Not allowed.', 'not_allowed') ), 403 );
		}

		$payload = isset( $_POST['formats'] ) ? json_decode( wp_unslash( $_POST['formats'] ), true ) : array();

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => lay_t('Invalid payload.', 'invalid_payload') ), 400 );
		}

		// Ensure required formats are present
		$formatsJson = json_encode( $payload );
		$formatsJson = FormatsManager::maybeAddRequiredFormats( $formatsJson );
		
		// Save to database
        $formats_json_string = json_encode( $formatsJson );
		update_option( 'formatsmanager_json', $formats_json_string );
        // Keep a backup of the last valid JSON so we can recover from accidental corruption.
        update_option( 'formatsmanager_json_backup', $formats_json_string );

		// Save textformats settings if provided
		if ( isset( $_POST['textformats_for_tablet'] ) ) {
			update_option( 'misc_options_textformats_for_tablet', sanitize_text_field( $_POST['textformats_for_tablet'] ) );
		}
		if ( isset( $_POST['textformats_tablet_breakpoint'] ) ) {
			update_option( 'misc_options_textformats_tablet_breakpoint', intval( $_POST['textformats_tablet_breakpoint'] ) );
		}
		if ( isset( $_POST['textformats_lineheight_in_px'] ) ) {
			update_option( 'misc_options_textformats_lineheight_in_px', sanitize_text_field( $_POST['textformats_lineheight_in_px'] ) );
		}
		if ( isset( $_POST['textformats_padding_bottom'] ) ) {
			update_option( 'misc_options_textformats_padding_bottom', sanitize_text_field( $_POST['textformats_padding_bottom'] ) );
		}

		// Update customizer styles linked with textformats
		FormatsManager::updateCustomizerStylesLinkedWithTextformats();

		// Refresh the static formats
		FormatsManager::$customFormats = json_decode( json_encode( $formatsJson ), true );

		wp_send_json_success( array( 'message' => lay_t('Text Formats updated.', 'text_formats_updated') ) );
	}

	public static function textformats_setup_menu(){
		add_menu_page( 'Text Formats', 'Text Formats', 'manage_options', 'manage-textformats', 'FormatsManager::textformats_markup', 'dashicons-editor-textcolor', 998 );
	}

	public static function textformats_markup(){
		require_once( Setup::$dir.'/formatsmanager/markup.php' );
	}

}
FormatsManager::init();
