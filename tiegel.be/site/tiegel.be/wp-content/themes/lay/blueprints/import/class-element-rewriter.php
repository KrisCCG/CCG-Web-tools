<?php
/**
 * Lay Blueprints — Element Rewriter (import side).
 *
 * Mirror of ElementWalker. Walks a decoded layout tree and rewrites every
 * attachment ID it finds via the IDMap. Strips stale `url` and `sizes`
 * fields — those came from the source site and are unusable on the target.
 * The frontend regenerates URLs from the new attachment ID at render time.
 *
 * Same element-type catalog as the export-side walker — kept in sync via
 * the same KNOWN_NO_DIRECT_MEDIA_TYPES list and recursion paths.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ElementRewriter {

	/** @var LayBlueprint_IdMap */
	private $attachments;

	/** @var LayBlueprint_IdMap|null Post IDMap — needed for postThumbnail's postid. */
	private $posts;

	public $stats = array(
		'attids_rewritten' => 0,
		'attids_missing'   => 0,
	);

	public function __construct( LayBlueprint_IdMap $attachments, $posts = null ) {
		$this->attachments = $attachments;
		$this->posts       = ( $posts instanceof LayBlueprint_IdMap ) ? $posts : null;
	}

	public function rewrite( array &$layout ) {
		if ( isset( $layout['bgImage'] ) && is_array( $layout['bgImage'] ) ) {
			$this->rewrite_bg_image( $layout['bgImage'] );
		}
		if ( isset( $layout['bgVideo'] ) && is_array( $layout['bgVideo'] ) ) {
			$this->rewrite_bg_video( $layout['bgVideo'] );
		}

		if ( isset( $layout['rowAttrs'] ) && is_array( $layout['rowAttrs'] ) ) {
			foreach ( $layout['rowAttrs'] as &$row ) {
				if ( ! is_array( $row ) ) { continue; }
				foreach ( array( 'bgimage', 'bgimage2', 'bgimage3' ) as $key ) {
					if ( isset( $row[ $key ] ) && is_array( $row[ $key ] ) ) {
						$this->rewrite_bg_image( $row[ $key ] );
					}
				}
				if ( isset( $row['bgvideo'] ) && is_array( $row['bgvideo'] ) ) {
					$this->rewrite_bg_video( $row['bgvideo'] );
				}
				// Deep walk for plugin-defined row features like
				// webglslideshow that nest element-shaped slides.
				$this->deep_rewrite( $row, 0 );
			}
			unset( $row );
		}

		if ( isset( $layout['cont'] ) && is_array( $layout['cont'] ) ) {
			foreach ( $layout['cont'] as &$el ) {
				if ( is_array( $el ) ) {
					$this->rewrite_element( $el );
				}
			}
			unset( $el );
		}
	}

	private function rewrite_element( array &$el ) {
		$type = isset( $el['type'] ) ? (string) $el['type'] : '';

		switch ( $type ) {
			case 'img':
				$this->rewrite_postid( $el );
				$this->rewrite_attid( $el, 'attid' );
				$this->refresh_image_urls( $el, 'attid' );
				$this->rewrite_mouseover_thumbnail( $el );
				return;

			case 'postThumbnail':
				// postThumbnail references a post by `postid`. The element's
				// `cont` and `sizes` are a snapshot of the source post's
				// featured image URL — stale on the target. Look up the new
				// post id, find its thumbnail attachment, and refresh.
				$this->rewrite_postid( $el );
				$this->refresh_post_thumbnail_urls( $el );
				$this->refresh_post_mouseover_thumbnail_urls( $el );
				return;

			case 'html5video':
				foreach ( array( 'mp4Attid', 'mp4attid' ) as $f ) {
					if ( isset( $el[ $f ] ) ) { $this->rewrite_attid( $el, $f ); }
				}
				foreach ( array( 'imageAttid', 'imgAttid', 'imgattid' ) as $f ) {
					if ( isset( $el[ $f ] ) ) { $this->rewrite_attid( $el, $f ); }
				}
				$this->refresh_video_urls( $el );
				return;

			case 'video':
				// External (Vimeo/YouTube) — leave untouched.
				return;

			case 'text':
			case 'html':
				// HTML rewriting handled by HtmlImportRewriter elsewhere.
				return;

			case 'carousel':
				if ( isset( $el['carousel'] ) && is_array( $el['carousel'] ) ) {
					foreach ( $el['carousel'] as &$slide ) {
						if ( is_array( $slide ) ) { $this->rewrite_element( $slide ); }
					}
					unset( $slide );
				}
				return;

			case 'elementgrid':
				if ( isset( $el['config']['elements'] ) && is_array( $el['config']['elements'] ) ) {
					foreach ( $el['config']['elements'] as &$nested ) {
						if ( is_array( $nested ) ) { $this->rewrite_element( $nested ); }
					}
					unset( $nested );
				}
				return;

			case 'stack':
				if ( isset( $el['cont'] ) && is_array( $el['cont'] ) ) {
					foreach ( $el['cont'] as &$nested ) {
						if ( is_array( $nested ) ) { $this->rewrite_element( $nested ); }
					}
					unset( $nested );
				}
				return;

			default:
				$this->rewrite_unknown( $el );
				return;
		}
	}

	private function rewrite_bg_image( array &$bg ) {
		$this->rewrite_attid( $bg, 'attid' );
		$this->refresh_image_urls( $bg, 'attid' );
	}

	private function rewrite_bg_video( array &$bg ) {
		$this->rewrite_attid( $bg, 'mp4Attid' );
		$this->rewrite_attid( $bg, 'imageAttid' );
		$this->refresh_video_urls( $bg );
	}

	private function rewrite_attid( array &$node, $field ) {
		if ( ! isset( $node[ $field ] ) ) { return; }
		$old = (int) $node[ $field ];
		if ( $old <= 0 ) { return; }
		$new = $this->attachments->lookup( $old );
		if ( $new === null ) {
			$this->stats['attids_missing']++;
			// Leave the old ID in place so a manual fix is still possible —
			// blanking it would silently break the layout instead.
			return;
		}
		$node[ $field ] = $new;
		$this->stats['attids_rewritten']++;
	}

	/**
	 * After remapping the attid, fetch the new attachment's metadata and
	 * rebuild the node's `url` + `sizes` so the frontend has valid data.
	 * Lay's render code reads `$node->sizes->full->url` directly — leaving
	 * sizes empty produces "Undefined array key 'full'" warnings + broken
	 * images.
	 */
	private function refresh_image_urls( array &$node, $attid_field ) {
		$new_id = isset( $node[ $attid_field ] ) ? (int) $node[ $attid_field ] : 0;
		if ( $new_id <= 0 ) {
			$node['url'] = '';
			$node['sizes'] = array();
			if ( isset( $node['cont'] ) && is_string( $node['cont'] ) ) {
				$node['cont'] = '';
			}
			return;
		}
		$src = wp_get_attachment_image_src( $new_id, 'full' );
		$resolved_url = '';
		if ( $src ) {
			$resolved_url  = (string) $src[0];
			$node['sizes'] = self::build_sizes_object( $new_id );
		} else {
			$resolved_url  = (string) ( wp_get_attachment_url( $new_id ) ?: '' );
			$node['sizes'] = $resolved_url ? self::build_sizes_object( $new_id ) : array();
		}
		$node['url'] = $resolved_url;
		// Some img elements carry a `cont` field that holds a URL-style
		// path used by the Gridder admin's placeholder preview (and
		// possibly other render paths). Older imports left this stale —
		// keep it in sync with `url` so the admin preview matches the
		// frontend.
		if ( isset( $node['cont'] ) && is_string( $node['cont'] ) ) {
			$node['cont'] = $resolved_url;
		}
	}

	/**
	 * Build the sizes map Lay's frontend expects.
	 *
	 * Format observed in real Lay layouts: each size is a plain URL string,
	 * NOT an object — `{ full: 'https://.../foo.jpg', _1024: '.../foo-1024x...jpg', ... }`.
	 * Lay's render code calls `substr($el['sizes']['full'], 0, 4)` and other
	 * string ops directly, so giving it a structured object crashes the
	 * frontend with "substr(): argument must be of type string".
	 *
	 * We populate the canonical sizes Lay expects (`full`, `_265`, `_512`,
	 * `_768`, `_1024`, `_1280`, `_1920`, `_2560`, `_3200`, `_3840`, `_4096`)
	 * by asking WP for each. Sizes that don't exist for this attachment fall
	 * back to the closest larger size — Lay's code tolerates duplicate URLs
	 * across sizes and uses width-based selection at render time.
	 */
	private static function build_sizes_object( $attachment_id ) {
		// Lay's expected key set (see frontend/assets/php/elements/el_functions.php:673).
		$keys     = array( 'full', '_265', '_512', '_768', '_1024', '_1280', '_1920', '_2560', '_3200', '_3840', '_4096' );
		$out      = array();
		$full_url = '';

		// Resolve full first — every other size falls back to this.
		$full = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( $full ) {
			$full_url = (string) $full[0];
		} else {
			$full_url = (string) ( wp_get_attachment_url( $attachment_id ) ?: '' );
		}
		$out['full'] = $full_url;

		// Lay's _265, _512, _768, _1024, _1280, _1920, _2560, _3200, _3840, _4096 are
		// keys named after a target *width*. Pass them WITH the leading
		// underscore so RemoteAttachmentFilters::filter_image_src can compute
		// dimensions from the numeric suffix and return a size-specific
		// service URL.
		foreach ( $keys as $key ) {
			if ( $key === 'full' ) { continue; }
			$src = wp_get_attachment_image_src( $attachment_id, $key );
			$out[ $key ] = $src ? (string) $src[0] : $full_url;
		}

		return $out;
	}

	private function refresh_video_urls( array &$node ) {
		// Video element / bgvideo: rebuild mp4 URL + poster image URL.
		// Lay's data uses several attid field-name variations; try them all.
		$mp4_id = 0;
		foreach ( array( 'mp4Attid', 'mp4attid' ) as $f ) {
			if ( isset( $node[ $f ] ) && (int) $node[ $f ] > 0 ) { $mp4_id = (int) $node[ $f ]; break; }
		}
		$image_id = 0;
		foreach ( array( 'imageAttid', 'imgAttid', 'imgattid' ) as $f ) {
			if ( isset( $node[ $f ] ) && (int) $node[ $f ] > 0 ) { $image_id = (int) $node[ $f ]; break; }
		}
		// Some html5video variants only carry `mp4attid` and use it for both
		// the JPG poster (in `sizes`) and the .mp4 — fall back to it.
		if ( $image_id === 0 && $mp4_id > 0 ) { $image_id = $mp4_id; }

		if ( $mp4_id > 0 && isset( $node['mp4'] ) ) {
			$mp4_url = wp_get_attachment_url( $mp4_id );
			$node['mp4'] = (string) ( $mp4_url ?: '' );
		} elseif ( isset( $node['mp4'] ) ) {
			$node['mp4'] = '';
		}

		if ( $image_id > 0 ) {
			// Some Lay versions use `image`, others use `sizes` — populate
			// whichever is present (and `sizes` always for safety).
			if ( isset( $node['image'] ) ) {
				$node['image'] = self::build_sizes_object( $image_id );
			}
			if ( isset( $node['sizes'] ) ) {
				$node['sizes'] = self::build_sizes_object( $image_id );
			}
			$src = wp_get_attachment_image_src( $image_id, 'full' );
			$resolved_url = $src ? (string) $src[0] : (string) ( wp_get_attachment_url( $image_id ) ?: '' );
			if ( array_key_exists( 'cont', $node ) && is_string( $node['cont'] ) ) {
				$node['cont'] = $resolved_url;
			}
			if ( isset( $node['url'] ) && is_string( $node['url'] ) ) {
				$node['url'] = $resolved_url;
			}
		} else {
			if ( isset( $node['image'] ) ) { $node['image'] = array(); }
			if ( isset( $node['sizes'] ) ) { $node['sizes'] = array(); }
			if ( array_key_exists( 'cont', $node ) && is_string( $node['cont'] ) ) {
				$node['cont'] = '';
			}
		}
	}

	private function rewrite_unknown( array &$el ) {
		foreach ( array( 'attid', 'imageAttid', 'mp4Attid' ) as $field ) {
			if ( isset( $el[ $field ] ) && is_numeric( $el[ $field ] ) ) {
				$this->rewrite_attid( $el, $field );
			}
		}
		// If this unknown element looks like an image (has attid + url-style
		// cont/sizes), refresh those too so the Gridder admin's placeholder
		// preview and any image-rendering paths that read `cont` work.
		if ( isset( $el['attid'] ) && is_numeric( $el['attid'] ) ) {
			$has_image_fields = isset( $el['cont'] ) || isset( $el['url'] ) || isset( $el['sizes'] );
			if ( $has_image_fields ) {
				$this->refresh_image_urls( $el, 'attid' );
			}
		}
		$this->rewrite_mouseover_thumbnail( $el );
		// Deep recursion: walk every array field, including ones nested
		// inside associative objects (e.g. socialmediaicons.config.content[]).
		$this->deep_rewrite( $el, 0 );
	}

	private function deep_rewrite( array &$node, $depth ) {
		if ( $depth > 8 ) { return; }
		foreach ( $node as $field => &$value ) {
			if ( ! is_array( $value ) ) { continue; }
			$looks_like_list = false;
			foreach ( $value as $item ) {
				if ( is_array( $item ) && ( isset( $item['type'] ) || isset( $item['attid'] ) ) ) {
					$looks_like_list = true;
					break;
				}
			}
			if ( $looks_like_list ) {
				foreach ( $value as &$nested ) {
					if ( is_array( $nested ) ) { $this->rewrite_element( $nested ); }
				}
				unset( $nested );
			} else {
				$this->deep_rewrite( $value, $depth + 1 );
			}
		}
		unset( $value );
	}

	/**
	 * Remap the element's `postid` (used by postThumbnail / news /
	 * thumbnailgrid filters) via the post IDMap if available.
	 */
	private function rewrite_postid( array &$el ) {
		if ( ! $this->posts ) { return; }
		if ( ! isset( $el['postid'] ) || ! is_numeric( $el['postid'] ) ) { return; }
		$old = (int) $el['postid'];
		$new = $this->posts->lookup( $old );
		if ( $new !== null ) {
			$el['postid'] = $new;
		}
	}

	/**
	 * For postThumbnail: look up the post's _thumbnail_id, then refresh
	 * cont/url/sizes from that attachment.
	 */
	private function refresh_post_thumbnail_urls( array &$el ) {
		if ( ! isset( $el['postid'] ) || ! is_numeric( $el['postid'] ) ) { return; }
		$post_id      = (int) $el['postid'];
		$thumb_id     = (int) get_post_meta( $post_id, '_thumbnail_id', true );
		if ( $thumb_id <= 0 ) {
			// Post has no featured image. Clear stale cont/sizes so the
			// admin doesn't show a broken background-image.
			if ( isset( $el['cont'] )  && is_string( $el['cont'] ) )  { $el['cont']  = ''; }
			if ( isset( $el['url'] )   && is_string( $el['url'] ) )   { $el['url']   = ''; }
			if ( isset( $el['sizes'] ) && is_array( $el['sizes'] ) )  { $el['sizes'] = array(); }
			return;
		}
		$src = wp_get_attachment_image_src( $thumb_id, 'full' );
		$resolved_url = $src ? (string) $src[0] : (string) ( wp_get_attachment_url( $thumb_id ) ?: '' );
		if ( isset( $el['cont'] )  && is_string( $el['cont'] ) )  { $el['cont']  = $resolved_url; }
		if ( isset( $el['url'] )   && is_string( $el['url'] ) )   { $el['url']   = $resolved_url; }
		$el['sizes'] = $resolved_url ? self::build_sizes_object( $thumb_id ) : array();
	}

	/**
	 * Project/product thumbnail elements can carry a separate mouseover
	 * attachment snapshot (`mo_attid`, `mo_sizes`, `mo_w`, `mo_h`). Keep that
	 * in sync as well; otherwise the frontend renders an empty mo_thumb
	 * srcset even though the normal featured image was rewritten correctly.
	 */
	private function rewrite_mouseover_thumbnail( array &$el ) {
		if ( ! isset( $el['mo_attid'] ) || ! is_numeric( $el['mo_attid'] ) ) {
			return;
		}
		$this->rewrite_attid( $el, 'mo_attid' );
		$this->refresh_mouseover_thumbnail_urls( $el, (int) $el['mo_attid'] );
	}

	private function refresh_post_mouseover_thumbnail_urls( array &$el ) {
		if ( ! isset( $el['postid'] ) || ! is_numeric( $el['postid'] ) ) { return; }
		$post_id = (int) $el['postid'];
		$mo_id   = (int) get_post_meta( $post_id, '_lay_thumbnail_mouseover_image', true );
		if ( $mo_id <= 0 ) {
			if ( isset( $el['mo_attid'] ) ) { unset( $el['mo_attid'] ); }
			if ( isset( $el['mo_sizes'] ) ) { $el['mo_sizes'] = array(); }
			if ( isset( $el['mo_w'] ) )     { $el['mo_w'] = ''; }
			if ( isset( $el['mo_h'] ) )     { $el['mo_h'] = ''; }
			return;
		}
		$el['mo_attid'] = $mo_id;
		$this->refresh_mouseover_thumbnail_urls( $el, $mo_id );
	}

	private function refresh_mouseover_thumbnail_urls( array &$el, $attachment_id ) {
		$attachment_id = (int) $attachment_id;
		if ( $attachment_id <= 0 ) {
			$el['mo_sizes'] = array();
			$el['mo_w']     = '';
			$el['mo_h']     = '';
			return;
		}

		$full = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! $full ) {
			$el['mo_sizes'] = array();
			$el['mo_w']     = '';
			$el['mo_h']     = '';
			return;
		}

		$el['mo_sizes'] = self::build_sizes_object( $attachment_id );
		$el['mo_w']     = isset( $full[1] ) ? (int) $full[1] : '';
		$el['mo_h']     = isset( $full[2] ) ? (int) $full[2] : '';
	}
}
