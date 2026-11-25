<?php

if( !function_exists('lay_render_dom_cursor_markup') ){
	function lay_render_dom_cursor_markup(){
		$dom_cursor_global = get_theme_mod( 'lay_cursor_use_dom_cursor', 0 ) == 1;
		$customizer_use_dom_cursor = $dom_cursor_global && get_theme_mod( 'lay_cursor', '' ) !== '';
		$carousel_use_dom_cursor = $dom_cursor_global;
		$lightbox_use_dom_cursor = $dom_cursor_global;
		$ms_use_dom_cursor = $dom_cursor_global;

		$resolve_cursor_attachment_url = function( $option_name ){
			$attachment_id = get_option( $option_name, '' );
			if( $attachment_id == '' ){
				return '';
			}
			$img = wp_get_attachment_image_src( $attachment_id, 'full' );
			if( !$img || !isset($img[0]) ){
				return '';
			}
			return $img[0];
		};

		$has_any_dom_cursor = $dom_cursor_global;

		if( !$has_any_dom_cursor ){
			return;
		}

		echo '<style>
		#lay-dom-cursor{
			position:fixed;
			left:0;
			top:0;
			transform:translate3d(-9999px,-9999px,0);
			z-index:2147483647;
			pointer-events:none;
			display:none;
		}
		#lay-dom-cursor img{
			display:block;
			width:auto;
			height:auto;
			max-width:none;
			max-height:none;
			pointer-events:none;
		}
		html.lay-dom-cursor-customizer,
		html.lay-dom-cursor-customizer body,
		html.lay-dom-cursor-customizer *,
		html.lay-dom-cursor-customizer *::before,
		html.lay-dom-cursor-customizer *::after{
			cursor:none!important;
		}';
		if ( get_theme_mod( 'lay_cursor_keep_normal_cursor_for_links', 0 ) == 1 ) {
			echo '
			html.lay-dom-cursor-customizer a,
			html.lay-dom-cursor-customizer a::before,
			html.lay-dom-cursor-customizer a::after,
			html.lay-dom-cursor-customizer a *,
			html.lay-dom-cursor-customizer a *::before,
			html.lay-dom-cursor-customizer a *::after,
			html.lay-dom-cursor-customizer .lay-marquee a,
			html.lay-dom-cursor-customizer .lay-marquee a::before,
			html.lay-dom-cursor-customizer .lay-marquee a::after,
			html.lay-dom-cursor-customizer .lay-marquee a *,
			html.lay-dom-cursor-customizer .lay-marquee a *::before,
			html.lay-dom-cursor-customizer .lay-marquee a *::after,
			html.lay-dom-cursor-customizer .lay-table a,
			html.lay-dom-cursor-customizer .lay-table a::before,
			html.lay-dom-cursor-customizer .lay-table a::after{
				cursor:pointer!important;
			}
			html.lay-dom-cursor-customizer .lay-table a *,
			html.lay-dom-cursor-customizer .lay-table a *::before,
			html.lay-dom-cursor-customizer .lay-table a *::after{
				cursor:pointer!important;
			}';
		}
		echo '
		</style>';

		$cursor_preload_urls = array();
		$add_plugin_cursor_urls = function( $base_url, $files ) use ( &$cursor_preload_urls ) {
			if( !is_string($base_url) || $base_url === '' ){
				return;
			}
			$base_url = trailingslashit($base_url);
			foreach( $files as $file ){
				$cursor_preload_urls[] = $base_url . $file;
			}
		};
		if( $customizer_use_dom_cursor ){
			$cursor_preload_urls[] = get_theme_mod( 'lay_cursor', '' );
		}
		if( $carousel_use_dom_cursor ){
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'laycarousel_arrowleft' );
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'laycarousel_arrowright' );
			if( class_exists('LayThemeCarousel') && isset(LayThemeCarousel::$url) ){
				$add_plugin_cursor_urls(
					LayThemeCarousel::$url . '/frontend/assets/img',
					array('cursor_prev.svg', 'cursor_next.svg')
				);
			}
		}
		if( $lightbox_use_dom_cursor ){
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'lightbox_arrowleft' );
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'lightbox_arrowright' );
			if( class_exists('LayThemeLightbox') && isset(LayThemeLightbox::$url) ){
				$add_plugin_cursor_urls(
					LayThemeLightbox::$url . '/frontend/assets/img',
					array('cursor_prev.svg', 'cursor_next.svg')
				);
			}
		}
		if( $ms_use_dom_cursor ){
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'ms_arrowup' );
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'ms_arrowdown' );
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'ms_arrowleft' );
			$cursor_preload_urls[] = $resolve_cursor_attachment_url( 'ms_arrowright' );
			if( class_exists('LayThemeMagneticSlides') && isset(LayThemeMagneticSlides::$url) ){
				$add_plugin_cursor_urls(
					LayThemeMagneticSlides::$url . '/frontend/assets/img',
					array('cursor_up.svg', 'cursor_down.svg', 'cursor_left.svg', 'cursor_right.svg')
				);
			}
		}
		$cursor_preload_urls = array_values(array_unique(array_filter($cursor_preload_urls)));

		echo '<div id="lay-dom-cursor" aria-hidden="true"><img alt="" /></div>';
		if( !empty($cursor_preload_urls) ){
			echo '<div id="lay-dom-cursor-preload" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;">';
			foreach($cursor_preload_urls as $cursor_preload_url){
				echo '<img alt="" src="'.esc_url($cursor_preload_url).'" loading="eager" decoding="async" fetchpriority="high" />';
			}
			echo '</div>';
		}
	}
}
