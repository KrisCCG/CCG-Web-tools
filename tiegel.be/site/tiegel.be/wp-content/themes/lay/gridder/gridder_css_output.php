<?php

class Gridder_CSS_Output{

	public function __construct(){
        add_action( 'admin_head', array($this, 'lay_customize_css_for_admin'));
    }

    public static function lay_customize_css_for_admin(){
        // projecttitle styles for backend edit category screen
        $screen = get_current_screen();
        if ( !LTUtility::is_admin_page_with_a_gridder($screen) ) {
            return;
        }
        $css_for_js = '';
        $strokewidth = get_theme_mod('link_underline_strokewidth', 1);
        if( intval($strokewidth) > 0 ) {
            $css_for_js = 'text-decoration-thickness:'.$strokewidth.'px!important;text-decoration-style: solid!important;text-decoration-line: underline!important';
        }
        // for tinymce style
        echo
        '<script type="text/javascript">
            var customizerPassedData = {};
            customizerPassedData.link_css = \'a{color: '.get_theme_mod('link_color', '#000000').';text-underline-offset: '.get_theme_mod('link_underline_offset', '3').get_theme_mod('link_underline_offset_mu', 'px').';'.$css_for_js.'}\';
        </script>';

        $css = '';

        // project tags
        $mod = get_theme_mod("ptags_visibility", 'always-show');
        if($mod == 'hide'){
            $css .= '#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags{display:none!important;}';
        }
        $css .= CSS_Output::generate_opacity_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'ptags_opacity', 100);
        $css .= CSS_Output::generate_opacity_css('.thumbnail-tags', 'ptags_opacity', 100);

        // if a textformat was set for project title, then we don't need to output css for it, cause we would use the textformat's css instead
        if(get_theme_mod("ptags_textformat", "_Default") == ""){
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'font-family', 'ptags_fontfamily', Customizer::$defaults['fontfamily']);
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'letter-spacing', 'ptags_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'line-height', 'ptags_lineheight', Customizer::$defaults['lineheight'], '', '');
            // textalign only applies to project title when its position is below image
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'font-weight', 'ptags_fontweight', Customizer::$defaults['fontweight']);

            $ptags_fontsize_mu = CSS_Output::get_mu('ptags_fontsize_mu', 'px');
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'font-size', 'ptags_fontsize', Customizer::$defaults['fontsize'], '', $ptags_fontsize_mu);
        }
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags', 'text-align', 'ptags_align', 'left');
        // project tags color always with !important so customizer value applies in gridder (including when textformat is set)
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, #gridder .lay-input-modal .thumbnail-tags, #gridder-root .thumbnail-tags', 'color', 'ptags_color', Customizer::$defaults['color'], '', '!important');

        // using important here so it works in modal!
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, .lay-input-modal .thumbnail-tags', 'margin-bottom', 'ptags_spacebottom', '0', '', 'px!important');
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .thumbnail-tags, .lay-input-modal .thumbnail-tags', 'margin-top', 'ptags_spacetop', '0', '', 'px!important');


        // project title 
        $mod = get_theme_mod("pt_visibility", 'always-show');
        if($mod == 'hide'){
            $css .= '#gridder #gridder-wrap .title, #gridder .lay-input-modal .title{display:none!important;}';
        }
        $css .= CSS_Output::generate_opacity_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'pt_opacity', 100);
        $css .= CSS_Output::generate_css('#gridder .container', 'background-color', 'bg_color', '#ffffff');
        $css .= CSS_Output::pt_generate_position_css('#gridder #gridder-wrap .titlewrap-on-image, #gridder .lay-input-modal .titlewrap-on-image');
        $css .= CSS_Output::generate_opacity_css('.title', 'pt_opacity', 100);

        // if a textformat was set for project title, then we don't need to output css for it, cause we would use the textformat's css instead
        if(get_theme_mod("pt_textformat", "_Default") == ""){
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'font-family', 'pt_fontfamily', Customizer::$defaults['fontfamily']);
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'letter-spacing', 'pt_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'line-height', 'pt_lineheight', Customizer::$defaults['lineheight'], '', '');
            // textalign only applies to project title when its position is below image
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'font-weight', 'pt_fontweight', Customizer::$defaults['fontweight']);

            $pt_fontsize_mu = CSS_Output::get_mu('pt_fontsize_mu', 'px');
            $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'font-size', 'pt_fontsize', Customizer::$defaults['fontsize'], '', $pt_fontsize_mu);
        }
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title', 'text-align', 'pt_align', 'left');
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .title, #gridder .lay-input-modal .title, #gridder-root .title', 'color', 'pt_color', Customizer::$defaults['color'], '', '!important');

        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .above-image .title, .lay-input-modal .above-image .title', 'margin-bottom', 'pt_spacetop', '5', '', 'px');
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .below-image .title, .lay-input-modal .below-image .title', 'margin-top', 'pt_spacetop', '5', '', 'px');

        $css .= CSS_Output::generate_css('#gridder .container', 'background-color', 'bg_color', '#ffffff');

        // project description
        
        // pd position
        $pd_position = get_theme_mod('pd_position', 'below-image');
        $pt_position = get_theme_mod('pt_position', 'below-image');
        // when pt position is below or above and pd pos is on-image, do center css
        // otherwise, the pt alignment defines where pt and pd are (can be on-image-top-left, or on-image-top-right, on-image-bottom-left etc)
        if( $pd_position == 'on-image' && strpos($pt_position, 'on-image') === false ){
            $css .= 
            '#gridder #gridder-wrap .titlewrap-on-image,
            #gridder .lay-input-modal .titlewrap-on-image{
                top: 50%;
                left: 50%;
                -webkit-transform: translate(-50%,-50%);
                -moz-transform: translate(-50%,-50%);
                -ms-transform: translate(-50%,-50%);
                -o-transform: translate(-50%,-50%);
                transform: translate(-50%,-50%);
            }';   
        }
        $css .= CSS_Output::generate_opacity_css('#gridder #gridder-wrap .descr, #gridder .lay-input-modal .descr, #gridder-root .descr', 'pd_opacity', 100);
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .descr, #gridder .lay-input-modal .descr, #gridder-root .descr', 'margin-top', 'pd_spacetop', '0', '', 'px');
        $css .= CSS_Output::generate_css('#gridder #gridder-wrap .descr, #gridder .lay-input-modal .descr, #gridder-root .descr', 'margin-bottom', 'pd_spacebottom', '0', '', 'px');

        // project description force textformat: .descr and .descr * so inner <p> etc. get the format and override .lay-textformat-parent > *
        $gridder_descr_selector = '#gridder #gridder-wrap .descr, #gridder .lay-input-modal .descr, #gridder-root .descr';
        if ( get_theme_mod( 'pd_force_textformat', false ) ) {
            $pd_force_format = get_theme_mod( 'pd_force_textformat_select', 'Default' );
            if ( $pd_force_format != '' ) {
                $css .= FormatsManager::get_textformat_css_for_selector( $pd_force_format, $gridder_descr_selector, false, true );
                $css .= FormatsManager::get_textformat_css_for_selector( $pd_force_format, $gridder_descr_selector . ' *', false, true );
            }
        }
        // project description force color (container and all descendants so <p> etc. get the color like frontend .thumb .descr *)
        if ( get_theme_mod( 'pd_force_color', false ) ) {
            $pd_force_color = get_theme_mod( 'pd_force_color_value', '#000000' );
            if ( $pd_force_color != '' ) {
                $css .= $gridder_descr_selector . ', ' . $gridder_descr_selector . ' * { color: ' . esc_attr( $pd_force_color ) . ' !important; }';
            }
        }

        // project thumbnail gradient overlay (multiple selectors for gridder canvas and modal)
        $pt_gradient_top = get_theme_mod( 'pt_gradient_top', false );
        $pt_gradient_bottom = get_theme_mod( 'pt_gradient_bottom', false );
        $pt_gradient_color = get_theme_mod( 'pt_gradient_color', '#000000' );
        $pt_gradient_opacity = (int) get_theme_mod( 'pt_gradient_opacity', 50 );
        $pt_gradient_opacity = max( 0, min( 100, $pt_gradient_opacity ) );
        $pt_gradient_rgba = CSS_Output::get_color_with_opacity( $pt_gradient_color, $pt_gradient_opacity );
        $gridder_sel_before = '#gridder #gridder-wrap .thumb-rel::before, #gridder-root .thumb-rel::before';
        $gridder_sel_after = '#gridder #gridder-wrap .thumb-rel::after, #gridder-root .thumb-rel::after';
        if ( $pt_gradient_top ) {
            $css .= $gridder_sel_before . '{content:"";position:absolute;top:0;left:0;right:0;height:30%;background:linear-gradient(to bottom,' . $pt_gradient_rgba . ',transparent);pointer-events:none;z-index:1;}';
        } else {
            $css .= $gridder_sel_before . '{display:none;}';
        }
        if ( $pt_gradient_bottom ) {
            $css .= $gridder_sel_after . '{content:"";position:absolute;bottom:0;left:0;right:0;height:30%;background:linear-gradient(to top,' . $pt_gradient_rgba . ',transparent);pointer-events:none;z-index:1;}';
        } else {
            $css .= $gridder_sel_after . '{display:none;}';
        }

        // thumbnailgrid filter
        // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$tgf_textformat = get_theme_mod('tgf_textformat', 'Default');
		if($tgf_textformat == ""){
			// if no textformat was selected for thumbnailgrid filter, generate css based on the individual customizer controls
			$tgf_fontsize_mu = CSS_Output::get_mu('tgf_fontsize_mu', 'px');
			$css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-size', 'tgf_fontsize', Customizer::$defaults['fontsize'],'', $tgf_fontsize_mu);
			$css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-weight', 'tgf_fontweight', Customizer::$defaults['fontweight']);
			$css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'letter-spacing', 'tgf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'color', 'tgf_color', Customizer::$defaults['color']);
			$css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'font-family', 'tgf_fontfamily', Customizer::$defaults['fontfamily']);
        }
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'text-align', 'tgf_align', 'left', '', '!important');
        $d = get_theme_mod('tgf_color', Customizer::$defaults['color']);
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter .lay-filter-active', 'color', 'tgf_active_color', $d);
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter', 'margin-bottom', 'tgf_spacebelow', '20', '', 'px');
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter-anchor', 'margin-right', 'tgf_spacebetween_2', '10', '', 'px');
        // $css .= CSS_Output::generate_css('.lay-thumbnailgrid-filter-anchor', 'margin-bottom', 'tgf_spacebetween_2', '10', '', 'px');

        $css .= CSS_Output::generate_opacity_css('.lay-thumbnailgrid-filter-anchor', 'tgf_opacity', 50);

        $css .= CSS_Output::generate_opacity_css('.lay-thumbnailgrid-filter-anchor.lay-filter-active', 'tgf_active_opacity', 100);

        $tgf_active_strokewidth = get_theme_mod('tgf_active_strokewidth', 0);
        if( $tgf_active_strokewidth > 0 ) {
            $css .= '
            .lay-thumbnailgrid-filter-anchor{
                text-decoration-thickness:'.$tgf_active_strokewidth.'px;
                text-decoration-color: transparent;
                text-decoration-style: solid;
                text-decoration-line: underline;
                text-underline-offset: '.get_theme_mod('link_underline_offset', 3).get_theme_mod('link_underline_offset_mu', 'px').';
            }
            .lay-thumbnailgrid-filter-anchor.lay-filter-active{
                text-decoration-color: inherit;
            }';
        }

        // tags filter for thumbnailgrid
        // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
		$tgtf_textformat = get_theme_mod('tgtf_textformat', 'Default');
		if($tgtf_textformat == ""){
			// if no textformat was selected for st, generate css based on the individual customizer controls
			$tgtf_fontsize_mu = CSS_Output::get_mu('tgtf_fontsize_mu', 'px');
			$css .= CSS_Output::generate_css('.tag-bubble', 'font-size', 'tgtf_fontsize', Customizer::$defaults['fontsize'],'', $tgtf_fontsize_mu);
			$css .= CSS_Output::generate_css('.tag-bubble', 'font-weight', 'tgtf_fontweight', Customizer::$defaults['fontweight']);
			$css .= CSS_Output::generate_css('.tag-bubble', 'letter-spacing', 'tgtf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
			$css .= CSS_Output::generate_css('.tag-bubble', 'color', 'tgtf_color', Customizer::$defaults['color']);
			$css .= CSS_Output::generate_css('.tag-bubble', 'font-family', 'tgtf_fontfamily', Customizer::$defaults['fontfamily']);
        }
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'text-align', 'tgtf_align', 'left', '', '!important');

        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'margin-bottom', 'tgtf_spacebelow', '20', '', 'px');
        $css .= CSS_Output::generate_css('.tag-bubble', 'margin', 'tgtf_spacebetween', '10', '', 'px');
        $css .= CSS_Output::generate_css('.lay-thumbnailgrid-tagfilter', 'margin-left', 'tgtf_spacebetween', '10', '-', 'px');

        $css .= CSS_Output::generate_css('.tag-bubble', 'background-color', 'tgtf_bubble_color', '#eeeeee');
        $css .= CSS_Output::generate_css('.tag-bubble', 'border-radius', 'tgtf_bubble_border_radius', '100', '', 'px');
        $css .= CSS_Output::generate_css('.tag-bubble:hover, .tag-bubble.lay-tag-active', 'background-color', 'tgtf_bubble_active_color', '#d0d0d0');
        

        $background_color = get_theme_mod('bg_color', '#ffffff');
        $css .= '#gridder-wrap{ background-color:'.$background_color.'; }';
        
        // woocommerce
        if( class_exists( 'WooCommerce' ) ) {
            // styles needed for productsgrid element

            // productsgrid filter
            // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
            $pgf_textformat = get_theme_mod('pgf_textformat', 'Default');
            if($pgf_textformat == ""){
                // if no textformat was selected for productsgrid filter, generate css based on the individual customizer controls
                $pgf_fontsize_mu = CSS_Output::get_mu('pgf_fontsize_mu', 'px');
                $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-size', 'pgf_fontsize', Customizer::$defaults['fontsize'],'', $pgf_fontsize_mu);
                $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-weight', 'pgf_fontweight', Customizer::$defaults['fontweight']);
                $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'letter-spacing', 'pgf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
                $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'color', 'pgf_color', Customizer::$defaults['color']);
                $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'font-family', 'pgf_fontfamily', Customizer::$defaults['fontfamily']);
            }
            $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'text-align', 'pgf_align', 'left', '', '!important');
            $d = get_theme_mod('pgf_color', Customizer::$defaults['color']);
            $css .= CSS_Output::generate_css('.lay-productsgrid-filter .lay-filter-active', 'color', 'pgf_active_color', $d);
            $css .= CSS_Output::generate_css('.lay-productsgrid-filter', 'margin-bottom', 'pgf_spacebelow', '20', '', 'px');
            $css .= CSS_Output::generate_css('.lay-productsgrid-filter-anchor', 'margin-right', 'pgf_spacebetween_2', '10', '', 'px');
            // $css .= CSS_Output::generate_css('.lay-productsgrid-filter-anchor', 'margin-bottom', 'pgf_spacebetween_2', '10', '', 'px');

            $css .= CSS_Output::generate_opacity_css('.lay-productsgrid-filter-anchor', 'pgf_opacity', 50);

            $css .= CSS_Output::generate_opacity_css('.lay-productsgrid-filter-anchor.lay-filter-active', 'pgf_active_opacity', 100);

            $pgf_active_strokewidth = get_theme_mod('pgf_active_strokewidth', 0);
            if( $pgf_active_strokewidth > 0 ) {
                $css .= '
                .lay-productsgrid-filter-anchor{
                    text-decoration-thickness:'.$pgf_active_strokewidth.'px;
                    text-decoration-color: transparent;
                    text-decoration-style: solid;
                    text-decoration-line: underline;
                    text-underline-offset: '.get_theme_mod('link_underline_offset', 3).get_theme_mod('link_underline_offset_mu', 'px').';
                }
                .lay-productsgrid-filter-anchor.lay-filter-active{
                    text-decoration-color: inherit;
                }';
            }

            // tags filter for productsgrid
            // if a textformat is selected, the html class is used by the dom element, so i don't need to create extra styles
            $pgtf_textformat = get_theme_mod('pgtf_textformat', 'Default');
            if($pgtf_textformat == ""){
                // if no textformat was selected for st, generate css based on the individual customizer controls
                $pgtf_fontsize_mu = CSS_Output::get_mu('pgtf_fontsize_mu', 'px');
                $css .= CSS_Output::generate_css('.ptag-bubble', 'font-size', 'pgtf_fontsize', Customizer::$defaults['fontsize'],'', $pgtf_fontsize_mu);
                $css .= CSS_Output::generate_css('.ptag-bubble', 'font-weight', 'pgtf_fontweight', Customizer::$defaults['fontweight']);
                $css .= CSS_Output::generate_css('.ptag-bubble', 'letter-spacing', 'pgtf_letterspacing', Customizer::$defaults['letterspacing'], '', 'em');
                $css .= CSS_Output::generate_css('.ptag-bubble', 'color', 'pgtf_color', Customizer::$defaults['color']);
                $css .= CSS_Output::generate_css('.ptag-bubble', 'font-family', 'pgtf_fontfamily', Customizer::$defaults['fontfamily']);
            }
            $css .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'text-align', 'pgtf_align', 'left', '', '!important');

            $css .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'margin-bottom', 'pgtf_spacebelow', '20', '', 'px');
            $css .= CSS_Output::generate_css('.ptag-bubble', 'margin', 'pgtf_spacebetween', '10', '', 'px');
            $css .= CSS_Output::generate_css('.lay-productsgrid-tagfilter', 'margin-left', 'pgtf_spacebetween', '10', '-', 'px');

            $css .= CSS_Output::generate_css('.ptag-bubble', 'background-color', 'pgtf_bubble_color', '#eeeeee');
            $css .= CSS_Output::generate_css('.ptag-bubble', 'border-radius', 'pgtf_bubble_border_radius', '100', '', 'px');
            $css .= CSS_Output::generate_css('.ptag-bubble:hover, .ptag-bubble.lay-tag-active', 'background-color', 'pgtf_bubble_active_color', '#d0d0d0');
            
            // button text format
            $button_text_format = get_theme_mod('lay_woocommerce_button_textformat', 'Shop_Big');
            $css .= FormatsManager::get_textformat_css_for_selector($button_text_format, '#gridder-root .lay-products .button', false);
            
            // project thumbnail custom add to cart button text format
            $show_add_to_cart_button = get_theme_mod('lay_woocommerce_pt_show_add_to_cart_button', 0);
            if( $show_add_to_cart_button ) {
                if( get_theme_mod('lay_woocommerce_pt_use_custom_textformat', 1) == true ) {
                    $format = get_theme_mod('lay_woocommerce_pt_add_to_cart_textformat', 'Shop_Small');
                    $css .= FormatsManager::get_textformat_css_for_selector($format, '#gridder-root .lay_woocommerce_product_thumbnail_text_wrap .button', false);
                }
                $add_to_cart_position = get_theme_mod('lay_woocommerce_pt_add_to_cart_position', 'under');
                if( $add_to_cart_position !== 'under' ){
                    $css .= '#gridder-root .lay_woocommerce_product_thumbnail_text_wrap.below-image{display:flex;justify-content:space-between;}';
                    if( $add_to_cart_position === 'left' ){
                        $css .= '#gridder-root .lay_woocommerce_product_thumbnail_text_wrap.below-image .add_to_cart_inline{order:0;}';
                        $css .= '#gridder-root .below-image.lay_woocommerce_product_thumbnail_text_wrap > a{order:1;}';
                    } else if( $add_to_cart_position === 'right' ){
                        $css .= '#gridder-root .lay_woocommerce_product_thumbnail_text_wrap.below-image .add_to_cart_inline{order:1;}';
                        $css .= '#gridder-root .below-image.lay_woocommerce_product_thumbnail_text_wrap > a{order:0;}';
                    }
                }
            }

            $button_style = get_theme_mod('lay_woocommerce_button_style', 'filled');
            $button_color = get_theme_mod('lay_woocommerce_button_color', '#000');
            $button_mouseover_color = get_theme_mod('lay_woocommerce_mouseover_button_color', '#000');
            // $lay_woocommerce_button_padding_top_bottom = get_theme_mod('lay_woocommerce_button_padding_top_bottom', '11');
            $lay_woocommerce_button_padding_left_right = get_theme_mod('lay_woocommerce_button_padding_left_right', '15');
            $lay_woocommerce_button_text_color = get_theme_mod('lay_woocommerce_button_text_color', '#fff');

            $css .= '#gridder-root .lay-products .button{ color:'.$lay_woocommerce_button_text_color.'!important; opacity: 1; }';
            // $css .= '#gridder-root .lay-products .button{ padding-top:'.$lay_woocommerce_button_padding_top_bottom.'px; padding-bottom:'.$lay_woocommerce_button_padding_top_bottom.'px; }';
            $css .= '#gridder-root .lay-products .button{ padding-left:'.$lay_woocommerce_button_padding_left_right.'px; padding-right:'.$lay_woocommerce_button_padding_left_right.'px; }';
            $css .= '#gridder-root .lay-products .button{ border-radius:'.get_theme_mod('lay_woocommerce_button_border_radius', 0).'px; }';
            
            $lay_woocommerce_add_to_cart_button_height = get_theme_mod('lay_woocommerce_add_to_cart_button_height', 52);
            $lay_woocommerce_add_to_cart_button_height = intval($lay_woocommerce_add_to_cart_button_height);

            $css .= CSS_Output::generate_css('#gridder-root .add_to_cart_button, #gridder-root .single_add_to_cart_button, #gridder-root .add_to_cart_inline .button, #gridder-root .add_to_cart_inline a', 'height', 'lay_woocommerce_add_to_cart_button_height', $lay_woocommerce_add_to_cart_button_height, '', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .add_to_cart_button, #gridder-root .single_add_to_cart_button, #gridder-root .add_to_cart_inline .button, #gridder-root .add_to_cart_inline a', 'line-height', 'lay_woocommerce_add_to_cart_button_height', $lay_woocommerce_add_to_cart_button_height-2, '', 'px!important');

            $button_border_width = get_theme_mod('lay_woocommerce_button_border_width', '1');

            switch($button_style){
                case 'filled':
                    $css .= '
                    #gridder-root .lay-products .button{
                        background-color: '.$button_color.';
                        border-bottom: none!important;
                        border: none!important;
                    }
                    #gridder-root .lay-products .button{
                        background-color: '.$button_mouseover_color.';
                    }
                    ';
                break;
                case 'outlines':
                    $css .= '
                    #gridder-root .lay-products .button{
                        border-color: '.$button_color.';
                        border-bottom: '.$button_border_width.'px solid '.$button_color.'!important;
                        border-width: '.$button_border_width.'px;
                        border-style: solid;
                        background-color: transparent;
                    }
                    #gridder-root .lay-products .button{
                        border-color: '.$button_mouseover_color.';
                        border-bottom-color: '.$button_mouseover_color.'!important;
                        background-color: transparent;
                    }
                    ';
                break;
            }

            $css .= CSS_Output::generate_css('#gridder-root .lay-woocommerce-product-thumbnail-title', 'margin-top', 'lay_woocommerce_pt_spacetop', '12','', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .lay-woocommerce-product-thumbnail-price', 'margin-top', 'lay_woocommerce_pt_price_spacetop', '0','', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .lay_woocommerce_product_thumbnail-out_of_stock', 'margin-top', 'lay_woocommerce_pt_outofstock_spacetop', '2','', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .lay-woocommerce-product-thumbnail-brand', 'margin-top', 'lay_woocommerce_pt_brands_spacetop', '10','', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .lay_woocommerce_product_thumbnail_description', 'margin-top', 'lay_woocommerce_pt_shortdescr_spacetop', '12','', 'px');
            $css .= CSS_Output::generate_css('#gridder-root .lay_woocommerce_product_thumbnail_text_wrap .add_to_cart_inline', 'margin-top', 'lay_woocommerce_pt_cart_button_spacetop', '15','', 'px!important');

            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-top-left', 'left', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-top-right', 'right', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            
            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-left', 'left', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-left', 'bottom', 'lay_woocommerce_pt_inner_space', '10','', 'px');

            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-right', 'right', 'lay_woocommerce_pt_inner_space', '10','', 'px');
            $css .= CSS_Output::generate_css('.lay_woocommerce_product_thumbnail_text_wrap.on-image-bottom-right', 'bottom', 'lay_woocommerce_pt_inner_space', '10','', 'px');
        }

        // carousel caption:
        $css .= '#gridder-wrap .element.type-carousel .caption{
            margin-top: '.get_option('laycarousel_spaceBetween', '0').'px;
        }';

        // buttons
        // #eeeeee same as standard tag bubble color
        $bgcolor_opacity = get_theme_mod('laybutton1_background_opacity', 100);
        $bgcolor = get_theme_mod('laybutton1_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton1_background_color', '#ffffff'), $bgcolor_opacity);
        $force_styles_1 = get_theme_mod('laybutton1_force_styles', false);
        $important_1 = $force_styles_1 ? '!important' : '';
        $laybutton1_textformat = get_theme_mod('laybutton1_textformat', 'Default');
        $css .= FormatsManager::get_textformat_css_for_selector($laybutton1_textformat, '#gridder-root a.laybutton1, #gridder-root div.laybutton1', false, $force_styles_1, true, false);
        $css .= 
        '#gridder-root a.laybutton1, #gridder-root div.laybutton1{
            color: '.get_theme_mod('laybutton1_text_color', '#000').$important_1.';
            border-radius: '.get_theme_mod('laybutton1_borderradius', 0).'px'.$important_1.';
            background-color: '.$bgcolor.$important_1.';
            border: '.get_theme_mod('laybutton1_borderwidth', 1).'px solid '.get_theme_mod('laybutton1_border_color', '#000000').$important_1.';
            padding-left: '.get_theme_mod('laybutton1_paddingleftright', 15).'px'.$important_1.';
            padding-right: '.get_theme_mod('laybutton1_paddingleftright', 15).'px'.$important_1.';
            padding-top: '.get_theme_mod('laybutton1_paddingtop', 5).'px'.$important_1.';
            padding-bottom: '.get_theme_mod('laybutton1_paddingbottom', 5).'px'.$important_1.';
            margin-left: '.get_theme_mod('laybutton1_marginleftright', 0).'px'.$important_1.';
            margin-right: '.get_theme_mod('laybutton1_marginleftright', 0).'px'.$important_1.';
            margin-top: '.get_theme_mod('laybutton1_margintop', 0).'px'.$important_1.';
            margin-bottom: '.get_theme_mod('laybutton1_marginbottom', 0).'px'.$important_1.';
            text-decoration: none!important;
        }';
        

        $bgcolor_opacity = get_theme_mod('laybutton2_background_opacity', 100);
        $bgcolor = get_theme_mod('laybutton2_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton2_background_color', '#ffffff'), $bgcolor_opacity);
        $force_styles_2 = get_theme_mod('laybutton2_force_styles', false);
        $important_2 = $force_styles_2 ? '!important' : '';
        $laybutton2_textformat = get_theme_mod('laybutton2_textformat', 'Default');
        $css .= FormatsManager::get_textformat_css_for_selector($laybutton2_textformat, '#gridder-root a.laybutton2, #gridder-root div.laybutton2', false, $force_styles_2, true, false);
        $css .= 
        '#gridder-root a.laybutton2, #gridder-root div.laybutton2{
            color: '.get_theme_mod('laybutton2_text_color', '#000').$important_2.';
            border-radius: '.get_theme_mod('laybutton2_borderradius', 100).'px'.$important_2.';
            background-color: '.$bgcolor.$important_2.';
            border: '.get_theme_mod('laybutton2_borderwidth', 1).'px solid '.get_theme_mod('laybutton2_border_color', '#000000').$important_2.';
            padding-left: '.get_theme_mod('laybutton2_paddingleftright', 20).'px'.$important_2.';
            padding-right: '.get_theme_mod('laybutton2_paddingleftright', 20).'px'.$important_2.';
            padding-top: '.get_theme_mod('laybutton2_paddingtop', 5).'px'.$important_2.';
            padding-bottom: '.get_theme_mod('laybutton2_paddingbottom', 5).'px'.$important_2.';
            margin-left: '.get_theme_mod('laybutton2_marginleftright', 0).'px'.$important_2.';
            margin-right: '.get_theme_mod('laybutton2_marginleftright', 0).'px'.$important_2.';
            margin-top: '.get_theme_mod('laybutton2_margintop', 0).'px'.$important_2.';
            margin-bottom: '.get_theme_mod('laybutton2_marginbottom', 0).'px'.$important_2.';
            text-decoration: none!important;
        }';
        
        $bgcolor_opacity = get_theme_mod('laybutton3_background_opacity', 100);
        $bgcolor = get_theme_mod('laybutton3_background_transparent', false) == true ? 'transparent' : CSS_Output::get_color_with_opacity(get_theme_mod('laybutton3_background_color', '#eeeeee'), $bgcolor_opacity);
        $force_styles_3 = get_theme_mod('laybutton3_force_styles', false);
        $important_3 = $force_styles_3 ? '!important' : '';
        $laybutton3_textformat = get_theme_mod('laybutton3_textformat', 'Default');
        $css .= FormatsManager::get_textformat_css_for_selector($laybutton3_textformat, '#gridder-root a.laybutton3, #gridder-root div.laybutton3', false, $force_styles_3, true, false);
        $css .= 
        '#gridder-root a.laybutton3, #gridder-root div.laybutton3{
            color: '.get_theme_mod('laybutton3_text_color', '#000').$important_3.';
            border-radius: '.get_theme_mod('laybutton3_borderradius', 100).'px'.$important_3.';
            background-color: '.$bgcolor.$important_3.';
            border: '.get_theme_mod('laybutton3_borderwidth', 0).'px solid '.get_theme_mod('laybutton3_border_color', '#000000').$important_3.';
            padding-left: '.get_theme_mod('laybutton3_paddingleftright', 20).'px'.$important_3.';
            padding-right: '.get_theme_mod('laybutton3_paddingleftright', 20).'px'.$important_3.';
            padding-top: '.get_theme_mod('laybutton3_paddingtop', 5).'px'.$important_3.';
            padding-bottom: '.get_theme_mod('laybutton3_paddingbottom', 5).'px'.$important_3.';
            margin-left: '.get_theme_mod('laybutton3_marginleftright', 0).'px'.$important_3.';
            margin-right: '.get_theme_mod('laybutton3_marginleftright', 0).'px'.$important_3.';
            margin-top: '.get_theme_mod('laybutton3_margintop', 0).'px'.$important_3.';
            margin-bottom: '.get_theme_mod('laybutton3_marginbottom', 0).'px'.$important_3.';
            text-decoration: none!important;
        }';

        // links in texts (including text-on-element overlays on img/video/carousel slides and project descriptions in gridder)
        $gridder_link_sel = '#gridder .text a:not(.laybutton), #gridder .lay-marquee a:not(.laybutton), #gridder .lay-table a:not(.laybutton), #gridder .lay-accordion a:not(.laybutton), #gridder .caption a:not(.laybutton), #gridder .text-to-add a:not(.laybutton), #gridder .link-in-text, #gridder .lay-link-parent a:not(.laybutton), #gridder .lay-text-on-element-overlay .lay-textformat-parent a:not(.laybutton), #gridder #gridder-wrap .descr a:not(.laybutton), #gridder-root .descr a:not(.laybutton)';
        $css .= $gridder_link_sel.'{
            text-underline-offset: '.get_theme_mod('link_underline_offset', 3).get_theme_mod('link_underline_offset_mu', 'px').';
        }';

        $css .= CSS_Output::generate_css($gridder_link_sel, 'color', 'link_color', '#000');
        $strokewidth = get_theme_mod('link_underline_strokewidth', 1);
        if( intval($strokewidth) > 0 ) {
            $css .= $gridder_link_sel.'{ 
                text-decoration-thickness:'.$strokewidth.'px;
                text-decoration-style: solid;
                text-decoration-line: underline;
            }';
        }

        // add carousel text width css and space between edge of slide and text slide
        $textslide_width = get_option( 'laycarousel_textslide_width', "400" );
        $textslide_width_phone = get_option( 'laycarousel_textslide_width_phone', $textslide_width );

        $textspace = get_option( 'laycarousel_textspace', "0" );
        $textspace_phone = get_option( 'laycarousel_textspace_phone', $textspace );

        $css .= '#gridder-root.show-desktop-version .element[data-type="carousel"] .placeholder.show-multiple-slides .text-slide-wrap{ width:'.$textslide_width.'px; }';
        $css .= '#gridder-root.show-desktop-version .element[data-type="carousel"] .placeholder.show-multiple-slides .text-slide-wrap > div{ left:'.$textspace.'px; right:'.$textspace.'px; }';

        $css .= '#gridder-root.show-phone-version .element[data-type="carousel"] .placeholder.show-multiple-slides .text-slide-wrap{ width:'.$textslide_width_phone.'px; }';
        $css .= '#gridder-root.show-phone-version .element[data-type="carousel"] .placeholder.show-multiple-slides .text-slide-wrap > div{ left:'.$textspace_phone.'px; right:'.$textspace_phone.'px; }';

        echo 
        '<!-- customizer css for gridder -->
        <style>'.$css.'</style>';

    }

}
new Gridder_CSS_Output();