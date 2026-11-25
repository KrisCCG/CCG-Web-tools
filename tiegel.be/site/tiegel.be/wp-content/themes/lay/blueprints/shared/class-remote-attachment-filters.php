<?php
/**
 * Lay Blueprints — Remote Attachment Filters.
 *
 * Imported attachments are virtual: a wp_posts row with no file on disk and
 * a `_lay_blueprint_remote_url` post-meta pointing at the lay-blueprint-media
 * service. These filters intercept every attachment URL/src lookup and
 * return the remote service URL — so Lay's existing attid-based render
 * code keeps working without any changes.
 *
 * For sized lookups (`thumbnail`, `medium`, `large`, `_265`, `_512`,
 * `_768`, `_1024`, …) we dynamically rewrite the {w}/{h} segments in the
 * service URL so the browser gets an appropriately-sized JPEG.
 *
 * Effects of this approach:
 *  - Import is instant (no HTTP, no sharp processing on import side)
 *  - Zero disk footprint for placeholder images
 *  - Replacing a placeholder with a real image later is just "Edit
 *    attachment → Replace media" — the meta gets dropped if the user
 *    deletes it manually, OR we add a hook on `wp_handle_upload` to clear
 *    it on replacement (left for later)
 *  - Service must be online for image rendering. Browser caches each URL
 *    once seen, but cold cache = service-dependent.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_RemoteAttachmentFilters {

	const META_KEY = '_lay_blueprint_remote_url';

	public static function register() {
		add_filter( 'wp_get_attachment_url',           array( __CLASS__, 'filter_url' ),           10, 2 );
		add_filter( 'wp_get_attachment_image_src',     array( __CLASS__, 'filter_image_src' ),     10, 4 );
		add_filter( 'wp_calculate_image_srcset',       array( __CLASS__, 'filter_image_srcset' ),  10, 5 );
		add_filter( 'get_attached_file',               array( __CLASS__, 'filter_attached_file' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js',    array( __CLASS__, 'filter_attachment_js' ), 10, 3 );
	}

	public static function filter_url( $url, $post_id ) {
		$remote = get_post_meta( $post_id, self::META_KEY, true );
		return $remote ? LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote ) : $url;
	}

	/**
	 * Some Lay code paths use get_attached_file() and then check filesize() —
	 * for virtual attachments there's no file. Returning the remote URL
	 * still satisfies the file-existence check by being truthy, while file
	 * operations on it fail safely (Lay's render code already handles
	 * filesize === false).
	 */
	public static function filter_attached_file( $file, $attachment_id ) {
		$remote = get_post_meta( $attachment_id, self::META_KEY, true );
		return $remote ? LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote ) : $file;
	}

	/**
	 * Return [url, width, height, is_intermediate] for any size on a
	 * virtual attachment. The lay-blueprint-media URL ends in /{w}/{h};
	 * we swap those segments based on the requested size.
	 */
	public static function filter_image_src( $image, $attachment_id, $size, $icon ) {
		$remote = get_post_meta( $attachment_id, self::META_KEY, true );
		if ( ! $remote ) { return $image; }
		$remote = LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote );

		$meta = wp_get_attachment_metadata( $attachment_id );
		$orig_w = is_array( $meta ) ? (int) ( $meta['width']  ?? 0 ) : 0;
		$orig_h = is_array( $meta ) ? (int) ( $meta['height'] ?? 0 ) : 0;
		if ( $orig_w <= 0 || $orig_h <= 0 ) {
			// Without metadata we can only return the URL as-is.
			return array( (string) $remote, $orig_w ?: 1200, $orig_h ?: 800, false );
		}

		list( $tw, $th ) = self::compute_target_dims( $size, $orig_w, $orig_h );
		$is_intermediate = ( $size !== 'full' && ( $tw !== $orig_w || $th !== $orig_h ) );

		return array(
			self::rewrite_dims( (string) $remote, $tw, $th ),
			$tw,
			$th,
			$is_intermediate,
		);
	}

	/**
	 * WordPress builds srcset URLs from `_wp_attachment_metadata['file']` and
	 * upload_dir. Our virtual attachments intentionally do not have local
	 * files, so without this filter WP can produce invalid URLs such as:
	 * /wp-content/uploads/https://lay-blueprint-media...
	 */
	public static function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$remote = get_post_meta( $attachment_id, self::META_KEY, true );
		if ( ! $remote ) {
			return $sources;
		}

		$remote = LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote );
		$orig_w = is_array( $image_meta ) ? (int) ( $image_meta['width']  ?? 0 ) : 0;
		$orig_h = is_array( $image_meta ) ? (int) ( $image_meta['height'] ?? 0 ) : 0;
		if ( $orig_w <= 0 || $orig_h <= 0 ) {
			return array(
				1200 => array(
					'url'        => $remote,
					'descriptor' => 'w',
					'value'      => 1200,
				),
			);
		}

		$remote_sources = array(
			$orig_w => array(
				'url'        => self::rewrite_dims( $remote, $orig_w, $orig_h ),
				'descriptor' => 'w',
				'value'      => $orig_w,
			),
		);

		$sizes = is_array( $image_meta['sizes'] ?? null ) ? $image_meta['sizes'] : array();
		foreach ( $sizes as $size ) {
			if ( ! is_array( $size ) ) {
				continue;
			}
			$w = (int) ( $size['width'] ?? 0 );
			$h = (int) ( $size['height'] ?? 0 );
			if ( $w <= 0 || $h <= 0 ) {
				continue;
			}
			$remote_sources[ $w ] = array(
				'url'        => self::rewrite_dims( $remote, $w, $h ),
				'descriptor' => 'w',
				'value'      => $w,
			);
		}

		ksort( $remote_sources, SORT_NUMERIC );
		return $remote_sources;
	}

	/**
	 * The WordPress Media Library uses wp_prepare_attachment_for_js(), not
	 * wp_get_attachment_image_src(), when it builds the grid view. Since our
	 * placeholder attachments do not have generated local thumbnails, WP would
	 * otherwise fall back to the full-size remote URL for every tile.
	 */
	public static function filter_attachment_js( $response, $attachment, $meta ) {
		if ( ! is_array( $response ) || ! isset( $attachment->ID ) ) {
			return $response;
		}

		$remote = get_post_meta( $attachment->ID, self::META_KEY, true );
		if ( ! $remote ) {
			return $response;
		}

		$remote = LayBlueprint_LayBlueprintPicsProvider::normalize_media_url( (string) $remote );
		$orig_w = is_array( $meta ) ? (int) ( $meta['width']  ?? 0 ) : 0;
		$orig_h = is_array( $meta ) ? (int) ( $meta['height'] ?? 0 ) : 0;
		if ( $orig_w <= 0 ) {
			$orig_w = (int) ( $response['width'] ?? 1200 );
		}
		if ( $orig_h <= 0 ) {
			$orig_h = (int) ( $response['height'] ?? 800 );
		}
		$orig_w = max( 1, $orig_w );
		$orig_h = max( 1, $orig_h );

		$response['url'] = $remote;
		$response['width'] = $orig_w;
		$response['height'] = $orig_h;
		$response['sizes'] = is_array( $response['sizes'] ?? null ) ? $response['sizes'] : array();

		foreach ( array( 'thumbnail', 'medium', 'medium_large', 'large', 'full' ) as $size ) {
			list( $tw, $th ) = self::compute_target_dims( $size, $orig_w, $orig_h );
			$response['sizes'][ $size ] = array(
				'height'      => $th,
				'width'       => $tw,
				'url'         => self::rewrite_dims( $remote, $tw, $th ),
				'orientation' => $tw >= $th ? 'landscape' : 'portrait',
			);
		}

		if ( isset( $response['sizes']['thumbnail']['url'] ) ) {
			$response['icon'] = $response['sizes']['thumbnail']['url'];
		}

		return $response;
	}

	/**
	 * Map a WordPress / Lay size identifier to (target_w, target_h) for the
	 * given source dimensions.
	 */
	public static function compute_target_dims( $size, $orig_w, $orig_h ) {
		if ( ! is_string( $size ) || $size === '' || $size === 'full' ) {
			return array( $orig_w, $orig_h );
		}

		// Lay's underscore-prefixed width keys: _265, _512, _768, _1024 …
		if ( preg_match( '/^_(\d+)$/', $size, $m ) ) {
			$tw = max( 1, (int) $m[1] );
			$th = max( 1, (int) round( $tw * ( $orig_h / max( $orig_w, 1 ) ) ) );
			return array( $tw, $th );
		}

		// Standard WP / theme-registered sizes — look up dims via global state.
		$registered = function_exists( 'wp_get_registered_image_subsizes' )
			? wp_get_registered_image_subsizes()
			: array();
		if ( isset( $registered[ $size ] ) ) {
			$tw = (int) $registered[ $size ]['width'];
			$th = (int) $registered[ $size ]['height'];
			$crop = ! empty( $registered[ $size ]['crop'] );
			if ( $crop ) {
				return array( max( 1, $tw ), max( 1, $th ) );
			}
			// Uncropped: scale to fit within tw × th, preserving AR.
			$scale = min( $tw / max( $orig_w, 1 ), $th / max( $orig_h, 1 ) );
			$scale = $scale > 0 ? min( 1, $scale ) : 1;
			return array( max( 1, (int) round( $orig_w * $scale ) ), max( 1, (int) round( $orig_h * $scale ) ) );
		}

		// Common WP fallback names.
		switch ( $size ) {
			case 'thumbnail': return array( 150, 150 );
			case 'medium':    return array( 300, max( 1, (int) round( 300 * $orig_h / max( $orig_w, 1 ) ) ) );
			case 'large':     return array( 1024, max( 1, (int) round( 1024 * $orig_h / max( $orig_w, 1 ) ) ) );
		}

		return array( $orig_w, $orig_h );
	}

	/**
	 * Replace the /{w}/{h} segment in a lay-blueprint-media URL.
	 *
	 *   http://host/sets/jose-porroche/pic/SEED/1920/1080  →  http://host/sets/jose-porroche/pic/SEED/265/149
	 *   http://host/solid/warm-grey/1920/1080              →  http://host/solid/warm-grey/265/149
	 *
	 * If the URL doesn't match the expected shape, leave it alone.
	 */
	public static function rewrite_dims( $url, $w, $h ) {
		$url = preg_replace( '~/pic/([^/?#]+)/\d+/\d+(?=$|[?#])~', '/pic/$1/' . $w . '/' . $h, $url );
		return preg_replace( '~/solid/([^/?#]+)/\d+/\d+(?=$|[?#])~', '/solid/$1/' . $w . '/' . $h, $url );
	}
}
