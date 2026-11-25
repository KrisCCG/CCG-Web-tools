<?php
// todo: check html classes, do i need to set html class like isTouchDevice using this php here, maybe instead of JS
// require_once get_template_directory().'/assets/mobile_detect/Mobile_Detect.php';


include_once(get_template_directory().'/assets/plugins/tinywp-mobile-detect/mobile-detect.php');

class LTUtility{

	public static $isTouchDevice;
	public static $supportsPlaysInlineOnTouchDevice;
	public static $isPhone;

	public function __construct(){
		$detect = new TinyWP_Mobile_Detect;
		LTUtility::$isTouchDevice = $detect->isMobile();
		LTUtility::$isPhone = false;

		if( $detect->isMobile() && !$detect->isTablet() ){
			LTUtility::$isPhone = true;
		}
		// todo: see if this works:
		// html5 video playsinline is supported from iOS 10 WebKit and Chrome > 53
		LTUtility::$supportsPlaysInlineOnTouchDevice = false;
		if( LTUtility::$isTouchDevice ) {
			if( $detect->isiOS() && $detect->is('Safari') ) {
				if( $detect->version('Safari') >= 10 ){
					LTUtility::$supportsPlaysInlineOnTouchDevice = true;
				}
			}
			else if( $detect->isAndroidOS() && $detect->is('Chrome') ) {
				if( $detect->version('Chrome') >= 53 ){
					LTUtility::$supportsPlaysInlineOnTouchDevice = true;
				}
			}
			// be optimistic for other combinations
			LTUtility::$supportsPlaysInlineOnTouchDevice = true;
		}
	}

	public static function is_admin_page_with_a_gridder($screen){
		$return = false;
		if ( $screen->id == 'edit-category' || $screen->id == 'post' || $screen->id == 'page' || Gridder::$active_for_news == true && $screen->id == 'lay_news'  ) {
			$return = true;
		}
		if(LayFrontend_Options::$gridder_active_for_products == true && $screen->id == 'product'){
			$return = true;
		}
		$return = apply_filters('lay_is_admin_page_with_a_gridder', $return, $screen);
		return $return;
	}

	public static function is_sidecart_active(){
		return is_plugin_active('side-cart-woocommerce/xoo-wsc-main.php') || (function_exists('xoo_wsc_init') && class_exists('WooCommerce'));
	}

	public static function is_frontpage(){
		if( is_home() || is_front_page() ){
			return true;
		  }
		  return false;
	}

	public static function getRelativeURL($url) {
		$siteUrl = get_site_url();

		if ($url && substr($url, 0, strlen($siteUrl)) === $siteUrl) {
			return substr($url, strlen($siteUrl));
		}
		return $url;
	}

    public static function getAltTag($attid) {

        // $meta = wp_get_attachment_metadata($attid);
        // $image_meta_tags = [];
        // if(is_array($meta) && isset($meta['image_meta'])) {
        //     $image_meta_tags = $meta['image_meta'];
        // } else {
        //     $image_meta_tags = [];
        // }
        
        $_alt = '';
        switch (LayFrontend_Options::$alt_tag_to_use) {
            case 'title':
                $_alt = get_the_title($attid);
                break;
            case 'caption':
                $_alt = wp_get_attachment_caption($attid);
                break;
            case 'description':
                $_alt = get_post_field( 'post_content', $attid );
                break;
            case 'alt':
            default:
                $_alt = get_post_meta($attid, '_wp_attachment_image_alt', true);
                break;
        }

        $_alt = $_alt.' '.MiscOptions::$image_alt_tag;
        return $_alt;
    }


    public static function isCDNURL($url) {
        $cdns = [
            'cloudfront.net',
            'cloudinary.com',
            'optimole.com',
            'wp.com',
            'wordpress.com',
            'jetpack.com',
            'imagedelivery.net',
            'cloudflare.com',
            'cloudflare.net',
            'b-cdn.net',
            'stackpathcdn.com',
            'quic.cloud',
            'smushcdn.com',
            'smu.sh',
            'shortpixel.ai',
            'ewww.io'
        ];

        foreach ($cdns as $cdn) {
            if (strpos($url, $cdn) !== false) {
                return true;
            }
        }
        return false;
    }
    

	/**
	 * Return video MIME type for use in <source type="..."> based on URL extension.
	 * @param string $url Video URL (e.g. .mp4, .webm, .ogv, .m3u8)
	 * @return string e.g. 'video/mp4', 'video/webm', 'video/ogg', 'application/vnd.apple.mpegurl'
	 */
	public static function getVideoMimeTypeFromUrl($url) {
		$path = is_string($url) ? $url : '';
		$q = strpos($path, '?');
		if ($q !== false) {
			$path = substr($path, 0, $q);
		}
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		if ($ext === 'webm') {
			return 'video/webm';
		}
		if ($ext === 'ogv' || $ext === 'ogg') {
			return 'video/ogg';
		}
		if ($ext === 'm3u8') {
			return 'application/vnd.apple.mpegurl';
		}
		return 'video/mp4';
	}

	public static function getFullURL($url){
        // not a relative, url. and is a url that doesnt contain get_site_url → just return it. should be a url by a plugin like "wp-offload-media"
        // for example: http://laytheme.fra1.digitaloceanspaces.com/wp-content/uploads/2023/09/05071815/Screenshot-2023-09-04-at-15.52.53.jpg
        // using a plugin like "wp-offload-media" will result in an absolute url like the one above to be saved in json
        if( substr( $url, 0, 4 ) == 'http' && strpos($url, Setup::$siteurl_without_http) === false ) {
			return $url;
		}
        if(LTUtility::isCDNURL($url)){
            return $url;
        }
        // if it contains http, it must be an absolute url from an older version of lay theme, so this is for
        // backwards compatibility:
        if( substr( $url, 0, 4 ) == 'http' ) {
			return LTUtility::filterURL($url);
		}
        // if it contains a #, just return it like it is
        if( substr( $url, 0, 1 ) == '#' ) {
            return $url;
        }
        // mailto:, tel:, sms: — not relative paths
        if ( preg_match( '/^(mailto|tel|sms):/i', $url ) ) {
            return $url;
        }
        return get_site_url().$url;
	}

	public static function has_www(){
		if(strpos(get_site_url(), "://www.") !== false){
			return 1;
		}
		return 0;
	}

	// make sure url is https or http in case user switched from http to https or the other way around
	public static function filterURL($url){

        // Don't change CDN URLs - they handle their own protocols
        $cdn_domains = array(
            'cloudfront.net',
            'cloudinary.com',
            'optimole.com',
            // Jetpack CDN
            'i0.wp.com', 'i1.wp.com', 'i2.wp.com', 'i3.wp.com',
            'wp.com', 'wordpress.com', 'jetpack.com',
            // Cloudflare
            'imagedelivery.net', 'cloudflare.com', 'cloudflare.net',
            // Other popular CDNs
            'b-cdn.net', 'bunnycdn.com',
            'stackpathcdn.com',
            'quic.cloud',
            'smushcdn.com', 'smu.sh',
            'shortpixel.ai',
            'ewww.io',
            'imagekit.io'
        );
        
        foreach($cdn_domains as $cdn) {
            if(strpos($url, $cdn) !== false){
                return $url;
            }
        }

		$is_ssl = is_ssl();
		$has_www = self::has_www();

		if($url != ""){
			// Check protocol (http:// or https://)
			if(substr($url, 0, 7) == "http://" && $is_ssl == true){
				// Convert HTTP to HTTPS
				$url = "https://" . substr($url, 7);
			}
			else if(substr($url, 0, 8) == "https://" && ($is_ssl == false || $is_ssl == "")){
				// Convert HTTPS to HTTP
				$url = "http://" . substr($url, 8);
			}
				
			// add www in case we're on a www domain, but the url does not contain www
			if($has_www == 1){
				if($is_ssl == true){
					// https://www.
					if(substr($url,0,12) != 'https://www.'){
						// $part is everything after https://
						$part = substr($url,8);
						$url = "https://www.".$part;
					}
				}else{
					// http://www.
					if(substr($url,0,11) != 'http://www.'){
						// $part is everything after http://
						$part = substr($url,7);
						$url = "http://www.".$part;
					}			
				}
			}else{
				// no www, see if url needs removal of www
				if($is_ssl == true){
					if(substr($url,0,12) == 'https://www.'){
						// $part is everything after https://www.
						$part = substr($url,12);
						$url = "https://".$part;
					}
				}else{
					// http://www.
					if(substr($url,0,11) == 'http://www.'){
						// $part is everything after http://www.
						$part = substr($url,11);
						$url = "http://".$part;
					}
				}
			}
        }
		return $url;
	}

}
new LTUtility();
