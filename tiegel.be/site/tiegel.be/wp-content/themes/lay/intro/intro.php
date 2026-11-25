<?php
// http://codex.wordpress.org/Settings_API#Examples
class LayIntro{

	public static $isActive;

	public function __construct(){
		LayIntro::$isActive = get_option('misc_options_intro', '') == 'on' && !MiscOptions::$locked ? true : false;
		if(LayIntro::$isActive){
			add_action('wp_head', array($this, 'intro_frontend_styles'));
		}
	}

	public function intro_frontend_styles(){
		$CSS = '';
        $breakpoint = get_option('lay_breakpoint', 600);
        $breakpoint = (int)$breakpoint;

		// if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$intro_text_textformat = get_theme_mod('intro_text_textformat', 'Default');
		if($intro_text_textformat == ""){
			// if no textformat was selected for intro_text, generate css based on the individual customizer controls
			$intro_text_fontsize_mu = CSS_Output::get_mu('intro_text_fontsize_mu', 'px');
			$CSS .= CSS_Output::generate_css('.intro_text', 'font-size', 'intro_text_fontsize', Customizer::$defaults['fontsize'],'', $intro_text_fontsize_mu);
			$CSS .= CSS_Output::generate_css('.intro_text', 'font-weight', 'intro_text_fontweight', Customizer::$defaults['fontweight']);
			$CSS .= CSS_Output::generate_css('.intro_text', 'letter-spacing', 'intro_text_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$CSS .= CSS_Output::generate_css('.intro_text', 'font-family', 'intro_text_fontfamily', Customizer::$defaults['fontfamily']);
			$CSS .= CSS_Output::generate_css('.intro_text', 'text-align', 'intro_text_align', 'left');
		}     
        $CSS .= CSS_Output::generate_css('.intro_text', 'color', 'intro_text_color', Customizer::$defaults['color']);
        $CSS .= CSS_Output::generate_css('.intro_text', 'text-align', 'intro_text_align', 'left');

		$intro_text_spacetop_mu = CSS_Output::get_mu('intro_text_spacetop_mu', 'px');
		$CSS .= CSS_Output::generate_css('.intro_text', 'top', 'intro_text_spacetop', Customizer::$defaults['intro_text_spacetop'], '', $intro_text_spacetop_mu);

		$intro_text_spaceleft_mu = CSS_Output::get_mu('intro_text_spaceleft_mu', '%');
		$CSS .= CSS_Output::generate_css('.intro_text', 'left', 'intro_text_spaceleft', Customizer::$defaults['intro_text_spaceleft'],'', $intro_text_spaceleft_mu);

		$intro_text_spaceright_mu = CSS_Output::get_mu('intro_text_spaceright_mu', '%');
		$CSS .= CSS_Output::generate_css('.intro_text', 'right', 'intro_text_spaceright', Customizer::$defaults['intro_text_spaceright'],'', $intro_text_spaceright_mu);

		$intro_text_spacebottom_mu = CSS_Output::get_mu('intro_text_spacebottom_mu', 'px');
		$CSS .= CSS_Output::generate_css('.intro_text', 'bottom', 'intro_text_spacebottom', Customizer::$defaults['intro_text_spacebottom'], '', $intro_text_spacebottom_mu);

		$CSS .= CSS_Output::generate_position_css('.intro_text', 'intro_text_position', 'center-left');
		 
		$CSS .= CSS_Output::generate_opacity_css_from_option('.intro', 'intro_opacity', 100);
		$CSS .= CSS_Output::generate_brightness_css('.intro .mediawrap', 'intro_media_brightness', 100);

        $intro_background_opacity = get_theme_mod('intro_background_opacity', 100);
        $CSS .= CSS_Output::generate_rgba('.intro', 'background-color', 'intro_background_color', '#ffffff', $intro_background_opacity);

        $honp_default = true;
        if( get_theme_mod('intro_hide_on_phone', 'not_set') == 'not_set' ) {
            $honp_default = false;
        }
        $hide_on_phone = get_theme_mod('intro_hide_on_phone', $honp_default);
        if( $hide_on_phone == true ) {
            $CSS .=
            '@media (max-width: '.($breakpoint).'px){
                .intro{
                    display: none;
                }
            }';
        }



		$transition = get_option( 'intro_transition', 'zoom' );
		switch($transition){
			case 'zoom':
				$CSS .=
				'.intro.animatehide{
					opacity: 0;
					-webkit-transform: scale(1.5);
					transform: scale(1.5);
				}';
			break;
			case 'fade':
				$CSS .= '.intro.animatehide{opacity: 0;}';
			break;
			case 'up':
				$CSS .=
				'.intro.animatehide{
					-webkit-transform: translateY(-100vh);
					transform: translateY(-100vh);
				}';
			break;
			case 'upfade':
				$CSS .=
				'.intro.animatehide{
					opacity: 0;
					-webkit-transform: translateY(-100vh);
					transform: translateY(-100vh);
				}';
			break;
		}

		$duration = get_option( 'intro_transition_duration', '500' );
		$CSS .= '.intro{
			transition: opacity '.$duration.'ms ease, transform '.$duration.'ms ease;
			-webkit-transition: opacity '.$duration.'ms ease, -webkit-transform '.$duration.'ms ease;
		}';

		$svg_overlay_width = get_option( 'intro_svg_overlay_width', '30' );
		$mu = get_option( 'intro_svg_overlay_width_in', '%' );

        $CSS .= '@media (min-width: '.($breakpoint + 1).'px){
            .intro-svg-overlay{width:'.$svg_overlay_width.$mu.';}
        }';

        $phone_intro_svg_overlay_width = get_option( 'phone_intro_svg_overlay_width', $svg_overlay_width );
		$phone_intro_svg_overlay_width_in = get_option( 'phone_intro_svg_overlay_width_in', $mu );

        $CSS .= '@media (max-width: '.($breakpoint).'px){
            .intro-svg-overlay{width:'.$phone_intro_svg_overlay_width.$phone_intro_svg_overlay_width_in.';}
        }';

		echo
		'<!-- intro style -->
		<style>'.$CSS.'</style>';
	}

	public static function get_svg_overlay_url(){
		$svg_val = get_option('intro_svg_overlay', '');
		// the value used to be an ID, now it will be a url of an attachment
		if( wp_get_attachment_url($svg_val) != false ){
			// svg_val is already an id
			return wp_get_attachment_url($svg_val);
		}
		// svg_val is a url
		return $svg_val;
	}

	public static function get_data($orientation){
        $option_name = ($orientation == 'landscape') ? 'intro_image' : 'phone_intro';

		$intro = [];
		$intro_url = get_option($option_name, '');
		$intro_attid = $intro_url;
		// the value used to be an ID, now it will be a url of an attachment
		if( wp_get_attachment_url($intro_url) == '' ){
			$intro_attid = attachment_url_to_postid( $intro_attid );
		}

		if($intro_attid != ''){
			$intro = wp_get_attachment_metadata($intro_attid);
			if($intro == false){
				return [];
			}
			$intro['url'] = wp_get_attachment_url($intro_attid);
			$intro['url'] = LTUtility::filterURL($intro['url']);
			$intro['type'] = false;
			$mime_type = get_post_mime_type($intro_attid);

			

			switch ($mime_type) {
				case 'image/jpeg':
				case 'image/png':
				case 'image/gif':
				case 'image/svg+xml':
					$intro['type'] = 'image';
					$intro['sizes'] = LayIntro::getSizes($intro_attid);
				break;
				case 'video/mpeg':
				case 'video/mp4': 
				case 'video/quicktime':
					$intro['type'] = 'video';
				break;
			}
		}
		
        return $intro;
	}
	public static function getSizes($attid){
		$sizes = array();
		global $_wp_additional_image_sizes;

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( !in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
				if ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$attachment = wp_get_attachment_image_src( $attid, $_size );
					$sizes[ $_size ] = LTUtility::filterURL($attachment[0]);
				}
			}
		}

		return $sizes;
	}

    public static function getSrcset($sizes){
        $size_index = ['_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096'];
        $imageScrsetArray = array();
        foreach($size_index as $size){
            $imageScrset = $sizes[$size].' '.substr($size, 1).'w';
            array_push($imageScrsetArray, $imageScrset);
        }
        return implode(', ', $imageScrsetArray);
    }

    public static function getMediaMarkup(){
        $mP = LayIntro::get_data('portrait');
        $mL = LayIntro::get_data('landscape');

        $mP_markup = '';
        $mL_markup = '';

        if(array_key_exists('type', $mP)){
            switch($mP['type']){
                case 'video':
                    $mP_markup = '<video data-w="'.$mP['width'].'" data-h="'.$mP['height'].'" class="intro-portrait-media media loaded" data-ar="'.$mP['width']/$mP['height'].'" playsinline muted autoplay loop src="'.$mP['url'].'"></video>';
                break;
                case 'image':
                    $mP_markup = '<img data-w="'.$mP['width'].'" data-h="'.$mP['height'].'" class="intro-portrait-media lazyload js-intro-image media setsizes-objectfit-cover" data-ar="'.$mP['width']/$mP['height'].'" sizes="" data-srcset="'.LayIntro::getSrcset($mP['sizes']).'" src="" alt="">';
                break;
            }
        }

        if(array_key_exists('type', $mL)){
            switch($mL['type']){
                case 'video':
                    $mL_markup = '<video data-w="'.$mL['width'].'" data-h="'.$mL['height'].'" class="intro-landscape-media media loaded" data-ar="'.$mL['width']/$mL['height'].'" playsinline muted autoplay loop src="'.$mL['url'].'"></video>';
                break;
                case 'image':
                    $mL_markup = '<img data-w="'.$mL['width'].'" data-h="'.$mL['height'].'" class="intro-landscape-media lazyload js-intro-image media setsizes-objectfit-cover" data-ar="'.$mL['width']/$mL['height'].'" sizes="" data-srcset="'.LayIntro::getSrcset($mL['sizes']).'" src="" alt="">';
                break;
            }
        }

        return $mP_markup.$mL_markup;
    }

    public static function getMarkup(){
        if(!LayIntro::$isActive) {
            return '';
        }
        if(!LTUtility::is_frontpage()){
            return '';
        }
        $medias = LayIntro::getMediaMarkup();

        $intro_use_svg_overlay = get_theme_mod('intro_use_svg_overlay', 0) == 1 ? true : false;
        $markup_svg_overlay = '';
        if($intro_use_svg_overlay) {
            $markup_svg_overlay = '<img class="intro-svg-overlay" src="'.LayIntro::get_svg_overlay_url().'" alt=""/>';
        }

        $intro_use_text_overlay = get_theme_mod('intro_use_text_overlay', 0) == 1 ? true : false;
        $intro_text_overlay = '';
        if($intro_use_text_overlay) {
            $intro_text_overlay = get_theme_mod('intro_text', '');
        }

        $intro_text_textformat = LayFrontend_Options::$intro_text_textformat;
		$intro_text_textformat = get_theme_mod('intro_text_textformat', 'Default');
		if($intro_text_textformat != ""){
			$intro_text_textformat = '_'.$intro_text_textformat;
		}

        return 
        '<div id="intro-region">
            <div class="intro">
                '.get_theme_mod('intro_custom_html', '').'
                <div class="intro-media-region">
                    <div>
                        <div class="mediawrap '.(get_theme_mod( 'intro_movement', 0 ) == 1 ? 'followmovement' : '').'">
                            '.$medias.'
                        </div>
                    </div>
                </div>
                <div class="intro-svg-wrap">
                    '.$markup_svg_overlay.'
                </div>
                <div class="intro_text '.$intro_text_textformat.'_no_spaces">'.$intro_text_overlay.'</div>
            </div>
        </div>';
    }

}
new LayIntro();