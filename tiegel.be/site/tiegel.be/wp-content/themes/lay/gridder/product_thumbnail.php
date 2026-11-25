<?php
/**
 * Picker AJAX for the carousel "+ Product Thumbnail" button.
 *
 * Returns the same JSON shape as ProjectThumbnail::get_post_thumbnail_json so
 * the shared PostThumbnailModal renders products without special-casing — but
 * for WC products instead of projects, with the project-only fields
 * (descr, cats, tags, thumbnail-video) omitted since products don't have
 * those metas.
 *
 * Loaded only when WooCommerce is active (see functions.php).
 */
class ProductThumbnail {

	public static function init() {
		add_action('wp_ajax_get_product_thumbnail_json', 'ProductThumbnail::get_product_thumbnail_json');
	}

	public static function get_product_thumbnail_json() {
		$products = array();

		$queryParam = array(
			'posts_per_page' => '-1',
			'post_type'      => 'product',
		);

		$query = new WP_Query( $queryParam );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				if ( $post->post_status != 'publish' ) {
					continue;
				}

				$title = get_the_title( $post->ID );
				$link  = get_permalink( $post->ID );

				$attid = get_post_thumbnail_id( $post->ID );
				$attachment = wp_get_attachment_image_src( $attid, 'full' );
				// No featured image → can't render a thumbnail in the picker.
				if ( $attachment === false ) {
					continue;
				}

				$sizes = array();
				$sizes['full'] = array(
					'url'         => $attachment[0],
					'width'       => $attachment[1],
					'height'      => $attachment[2],
					'orientation' => $attachment[1] > $attachment[2] ? 'landscape' : 'portrait',
				);
				foreach ( Setup::$sizes as $size ) {
					$attachment = wp_get_attachment_image_src( $attid, $size );
					if ( $attachment[0] == $sizes['full']['url'] ) {
						break;
					}
					$sizes[ $size ] = array(
						'url'         => $attachment[0],
						'width'       => $attachment[1],
						'height'      => $attachment[2],
						'orientation' => $attachment[1] > $attachment[2] ? 'landscape' : 'portrait',
					);
				}
				$ar = 1;
				if ( array_key_exists( 'full', $sizes ) && array_key_exists( 'width', $sizes['full'] ) && $sizes['full']['width'] != 0 ) {
					$ar = $sizes['full']['height'] / $sizes['full']['width'];
				}

				// Products use the same _lay_thumbnail_mouseover_image meta key
				// as projects (the metabox is registered for both post types).
				$moSizes = '';
				$moW     = '';
				$moH     = '';
				$mo_thumb_id = get_post_meta( $post->ID, '_lay_thumbnail_mouseover_image', true );
				if ( $mo_thumb_id && get_post( $mo_thumb_id ) ) {
					$attachment = wp_get_attachment_image_src( $mo_thumb_id, 'full' );
					$moSizes = array();
					$moSizes['full'] = array(
						'url'         => $attachment[0],
						'width'       => $attachment[1],
						'height'      => $attachment[2],
						'orientation' => $attachment[1] > $attachment[2] ? 'landscape' : 'portrait',
					);
					$moW = $moSizes['full']['width'];
					$moH = $moSizes['full']['height'];
					foreach ( Setup::$sizes as $size ) {
						$attachment = wp_get_attachment_image_src( $mo_thumb_id, $size );
						if ( $attachment[0] == $moSizes['full']['url'] ) {
							break;
						}
						$moSizes[ $size ] = array(
							'url'         => $attachment[0],
							'width'       => $attachment[1],
							'height'      => $attachment[2],
							'orientation' => $attachment[1] > $attachment[2] ? 'landscape' : 'portrait',
						);
					}
				}

				$products[] = array(
					'postid'    => $post->ID,
					'title'     => $title,
					'descr'     => '',
					'link'      => $link,
					'cats'      => array(),
					'attid'     => $attid,
					'sizes'     => $sizes,
					'ar'        => $ar,
					'video_url' => '',
					'video_w'   => '',
					'video_h'   => '',
					'mo_sizes'  => $moSizes,
					'mo_w'      => $moW,
					'mo_h'      => $moH,
					'tags'      => array(),
				);
			}
		}

		wp_send_json( $products );
		die();
	}
}

ProductThumbnail::init();
