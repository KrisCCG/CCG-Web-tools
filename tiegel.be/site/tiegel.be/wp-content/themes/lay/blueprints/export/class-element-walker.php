<?php
/**
 * Lay Blueprints — Element Walker.
 *
 * Recursively walks a Gridder layout JSON tree (JsonFieldType) and registers
 * every attachment reference + every HTML body it finds. The walker is
 * structured around the field-name catalog from src/gridder/tsx/common/types.tsx;
 * see the inline comments per element type for the source of each field.
 *
 * Walker is stateful — instantiate once per export, feed each layout in via
 * walk(), then read $registered_attachments / $html_blocks out at the end
 * (the MediaResolver consumes both).
 *
 * Layout JSON is mutated in-place: HTML strings inside text/html element
 * `cont` fields get rewritten (Phase 3 leaves attachment IDs untouched —
 * the importer rewrites those via the media_manifest's old_id → new_id map).
 *
 * Unknown element types are passed through without crashing. A best-effort
 * scan looks for any obvious attid/url fields and warns in $unknown_warnings.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ElementWalker {

	/** @var array<int, array{kind:string, hint:string}> id → first observation */
	private $registered_attachments = array();

	/** @var array<int, array{html:string, hint:string, callback:callable}> mutation slots */
	private $html_slots = array();

	/** @var array<int, string> messages about unknown plugin element types */
	public $unknown_warnings = array();

	/**
	 * Lay element types that are real (defined somewhere under src/lay or a
	 * Lay plugin) but don't carry direct attachment IDs in the layout JSON.
	 * They typically reference content dynamically (by category, by current
	 * post, etc.) and resolve media at render time. Listing them here keeps
	 * the unknown-element warnings focused on truly novel types.
	 *
	 * Some of these CAN contain nested elements (accordion sections etc.) —
	 * we still recurse into any `cont`/`elements` arrays via the unknown
	 * fallback path; the listing here only suppresses the "Unknown" warning.
	 */
	const KNOWN_NO_DIRECT_MEDIA_TYPES = array(
		'accordion',
		'addtocartbutton',
		'embed',
		'hr',
		'marquee',
		'news',
		'postThumbnail',
		'productindex',
		'productsgrid',
		'projectindex',
		'shortcode',
		'socialmediaicons',
		'table',
		'textfooter',
		'thumbnailgrid',
		'vl',
	);

	public function get_attachment_ids() {
		return array_keys( $this->registered_attachments );
	}

	public function get_attachment_records() {
		return $this->registered_attachments;
	}

	public function get_html_slots() {
		return $this->html_slots;
	}

	/**
	 * Walk a layout. Mutations to HTML strings are not applied here — the
	 * caller (typically ContentCollector → HTMLRewriter) drains $html_slots
	 * after all layouts are walked, runs the rewriter, and writes back via
	 * the per-slot callback.
	 *
	 * @param array  &$layout Top-level JsonFieldType (passed by reference).
	 * @param string $hint    Trace hint (e.g. "post:42:_gridder_json").
	 */
	public function walk( array &$layout, $hint ) {
		// Top-level bg media.
		if ( isset( $layout['bgImage'] ) && is_array( $layout['bgImage'] ) ) {
			$this->visit_bg_image( $layout['bgImage'], $hint . '.bgImage' );
		}
		if ( isset( $layout['bgVideo'] ) && is_array( $layout['bgVideo'] ) ) {
			$this->visit_bg_video( $layout['bgVideo'], $hint . '.bgVideo' );
		}

		// Per-row attrs.
		if ( isset( $layout['rowAttrs'] ) && is_array( $layout['rowAttrs'] ) ) {
			foreach ( $layout['rowAttrs'] as $i => &$row ) {
				if ( ! is_array( $row ) ) { continue; }
				foreach ( array( 'bgimage', 'bgimage2', 'bgimage3' ) as $key ) {
					if ( isset( $row[ $key ] ) && is_array( $row[ $key ] ) ) {
						$this->visit_bg_image( $row[ $key ], $hint . '.rowAttrs[' . $i . '].' . $key );
					}
				}
				if ( isset( $row['bgvideo'] ) && is_array( $row['bgvideo'] ) ) {
					$this->visit_bg_video( $row['bgvideo'], $hint . '.rowAttrs[' . $i . '].bgvideo' );
				}
				// Deep walk for plugin-defined row features like
				// webglslideshow that nest element-shaped slides.
				$this->deep_walk_for_elements( $row, $hint . '.rowAttrs[' . $i . ']', 0 );
			}
			unset( $row );
		}

		// Element list.
		if ( isset( $layout['cont'] ) && is_array( $layout['cont'] ) ) {
			foreach ( $layout['cont'] as $i => &$el ) {
				if ( is_array( $el ) ) {
					$this->visit_element( $el, $hint . '.cont[' . $i . ']' );
				}
			}
			unset( $el );
		}
	}

	private function visit_element( array &$el, $hint ) {
		$type = isset( $el['type'] ) ? (string) $el['type'] : '';

		switch ( $type ) {
			case 'img':
				$this->register_attachment( $el, 'image', $hint );
				$this->register_mouseover_thumbnail( $el, $hint );
				return;

			case 'html5video':
				// HTML5 video has its own attid (mp4Attid) plus a poster image.
				$dims_for_video = self::dims_from_element( $el, array( 'mp4W', 'mp4H' ) );
				$dims_for_image = self::dims_from_element( $el, array( 'imageW', 'imageH', 'w', 'h' ) );
				if ( isset( $el['mp4Attid'] ) ) {
					$this->register_id( (int) $el['mp4Attid'], 'video', $hint . '.mp4Attid', $dims_for_video );
				}
				if ( isset( $el['imageAttid'] ) ) {
					$this->register_id( (int) $el['imageAttid'], 'image', $hint . '.imageAttid', $dims_for_image );
				}
				// `cont` on a video element holds the poster image URL (not HTML).
				return;

			case 'video':
				// External (Vimeo/YouTube) embeds — no rewriting, kept verbatim.
				return;

			case 'text':
			case 'html':
				$this->register_html_slot( $el, $hint );
				return;

			case 'carousel':
				if ( isset( $el['carousel'] ) && is_array( $el['carousel'] ) ) {
					foreach ( $el['carousel'] as $j => &$slide ) {
						if ( is_array( $slide ) ) {
							$this->visit_element( $slide, $hint . '.carousel[' . $j . ']' );
						}
					}
					unset( $slide );
				}
				return;

			case 'elementgrid':
				if ( isset( $el['config']['elements'] ) && is_array( $el['config']['elements'] ) ) {
					foreach ( $el['config']['elements'] as $j => &$nested ) {
						if ( is_array( $nested ) ) {
							$this->visit_element( $nested, $hint . '.config.elements[' . $j . ']' );
						}
					}
					unset( $nested );
				}
				return;

			case 'stack':
				if ( isset( $el['cont'] ) && is_array( $el['cont'] ) ) {
					foreach ( $el['cont'] as $j => &$nested ) {
						if ( is_array( $nested ) ) {
							$this->visit_element( $nested, $hint . '.stack[' . $j . ']' );
						}
					}
					unset( $nested );
				}
				return;

			default:
				$this->visit_unknown( $el, $hint, $type );
				return;
		}
	}

	private function visit_bg_image( array &$bg, $hint ) {
		if ( isset( $bg['attid'] ) ) {
			$dims = self::dims_from_sizes( $bg );
			$this->register_id( (int) $bg['attid'], 'image', $hint . '.attid', $dims );
		}
	}

	private function visit_bg_video( array &$bg, $hint ) {
		$mp4_dims   = self::dims_from_element( $bg, array( 'mp4W', 'mp4H' ) );
		$image_dims = self::dims_from_element( $bg, array( 'imageW', 'imageH' ) );
		if ( $mp4_dims === null && isset( $bg['mp4Ar'] ) && (float) $bg['mp4Ar'] > 0 ) {
			$mp4_dims = self::dims_from_ar( (float) $bg['mp4Ar'] );
		}
		if ( $image_dims === null && isset( $bg['imageAr'] ) && (float) $bg['imageAr'] > 0 ) {
			$image_dims = self::dims_from_ar( (float) $bg['imageAr'] );
		}
		if ( isset( $bg['mp4Attid'] ) ) {
			$this->register_id( (int) $bg['mp4Attid'], 'video', $hint . '.mp4Attid', $mp4_dims );
		}
		if ( isset( $bg['imageAttid'] ) ) {
			$this->register_id( (int) $bg['imageAttid'], 'image', $hint . '.imageAttid', $image_dims );
		}
	}

	private function register_attachment( array &$el, $kind, $hint ) {
		if ( isset( $el['attid'] ) ) {
			$dims = self::dims_from_element( $el, array( 'w', 'h' ) );
			if ( $dims === null ) {
				$dims = self::dims_from_sizes( $el );
			}
			if ( $dims === null && isset( $el['ar'] ) && (float) $el['ar'] > 0 ) {
				$dims = self::dims_from_ar( (float) $el['ar'] );
			}
			$this->register_id( (int) $el['attid'], $kind, $hint . '.attid', $dims );
		}
	}

	private function register_mouseover_thumbnail( array &$el, $hint ) {
		if ( ! isset( $el['mo_attid'] ) || ! is_numeric( $el['mo_attid'] ) ) {
			return;
		}
		$dims = self::dims_from_element( $el, array( 'mo_w', 'mo_h' ) );
		$this->register_id( (int) $el['mo_attid'], 'image', $hint . '.mo_attid', $dims );
	}

	private function register_id( $id, $kind, $hint, $fallback_dims = null ) {
		if ( $id <= 0 ) { return; }
		// Keep the first observation. MediaResolver later loads metadata
		// once per ID regardless of how many places reference it.
		if ( ! isset( $this->registered_attachments[ $id ] ) ) {
			$this->registered_attachments[ $id ] = array(
				'kind'          => $kind,
				'hint'          => $hint,
				'fallback_dims' => $fallback_dims,
			);
		} elseif ( empty( $this->registered_attachments[ $id ]['fallback_dims'] ) && $fallback_dims ) {
			// Backfill dimensions if the first observation didn't have them.
			$this->registered_attachments[ $id ]['fallback_dims'] = $fallback_dims;
		}
	}

	/**
	 * Pull (w, h) out of any of the named fields. Returns ['w' => int, 'h' => int]
	 * or null if either dim is missing or non-positive.
	 */
	private static function dims_from_element( array $node, array $field_pairs ) {
		// $field_pairs is a flat list — pairs are consecutive (w_field, h_field).
		for ( $i = 0; $i < count( $field_pairs ); $i += 2 ) {
			$wf = $field_pairs[ $i ];
			$hf = isset( $field_pairs[ $i + 1 ] ) ? $field_pairs[ $i + 1 ] : null;
			if ( ! $hf ) { break; }
			if ( isset( $node[ $wf ] ) && isset( $node[ $hf ] ) ) {
				$w = (int) $node[ $wf ];
				$h = (int) $node[ $hf ];
				if ( $w > 0 && $h > 0 ) { return array( 'w' => $w, 'h' => $h ); }
			}
		}
		return null;
	}

	private static function dims_from_sizes( array $node ) {
		// bgimage/etc. carry a `sizes` object with full.{width,height}.
		if ( isset( $node['sizes']['full']['width'] ) && isset( $node['sizes']['full']['height'] ) ) {
			$w = (int) $node['sizes']['full']['width'];
			$h = (int) $node['sizes']['full']['height'];
			if ( $w > 0 && $h > 0 ) { return array( 'w' => $w, 'h' => $h ); }
		}
		return null;
	}

	private static function dims_from_ar( $ar ) {
		// Only AR available — synthesize an arbitrary-but-decent canvas.
		if ( $ar <= 0 ) { return null; }
		$w = 1200;
		$h = (int) max( 1, round( $w / $ar ) );
		return array( 'w' => $w, 'h' => $h );
	}

	private function register_html_slot( array &$el, $hint ) {
		// `cont` for text/html elements is "any" in the source types, so it
		// can be an HTML string OR (for some legacy variants) a string
		// wrapped in { cont: '...' }. Handle both.
		if ( isset( $el['cont'] ) && is_string( $el['cont'] ) && $el['cont'] !== '' ) {
			$this->html_slots[] = array(
				'html'     => $el['cont'],
				'hint'     => $hint . '.cont',
				'callback' => function ( $new_html ) use ( &$el ) {
					$el['cont'] = $new_html;
				},
			);
		} elseif ( isset( $el['cont']['cont'] ) && is_string( $el['cont']['cont'] ) ) {
			$inner_ref = &$el['cont']['cont'];
			$this->html_slots[] = array(
				'html'     => $inner_ref,
				'hint'     => $hint . '.cont.cont',
				'callback' => function ( $new_html ) use ( &$inner_ref ) {
					$inner_ref = $new_html;
				},
			);
		}
	}

	/**
	 * Best-effort scan for unknown element types (plugin elements not in our
	 * known set). Looks for any field literally named `attid`/`imageAttid`/
	 * `mp4Attid` and registers it. Logs a warning so the user knows to
	 * verify after import.
	 */
	private function visit_unknown( array &$el, $hint, $type ) {
		$type_label = $type === '' ? '(empty)' : $type;
		$found_any  = false;
		foreach ( array( 'attid', 'imageAttid', 'mp4Attid' ) as $field ) {
			if ( isset( $el[ $field ] ) && is_numeric( $el[ $field ] ) ) {
				$kind = $field === 'mp4Attid' ? 'video' : 'image';
				$this->register_id( (int) $el[ $field ], $kind, $hint . '.' . $field );
				$found_any = true;
			}
		}
		if ( isset( $el['mo_attid'] ) && is_numeric( $el['mo_attid'] ) ) {
			$this->register_mouseover_thumbnail( $el, $hint );
			$found_any = true;
		}
		// Deep recursion: walk every array field, including ones nested inside
		// associative objects (like `config.content[]`). Catches plugin
		// elements with arbitrary container shapes —
		// e.g. socialmediaicons.config.content[], gallery.config.images[].
		$this->deep_walk_for_elements( $el, $hint, 0 );
		// Suppress warnings for known Lay element types that don't carry direct
		// attachment IDs (they resolve dynamically at render time).
		if ( in_array( $type, self::KNOWN_NO_DIRECT_MEDIA_TYPES, true ) ) {
			return;
		}
		$this->unknown_warnings[] = sprintf(
			'Unknown element type "%s" at %s — best-effort scan %s media refs. Please verify after import.',
			$type_label,
			$hint,
			$found_any ? 'collected' : 'found no'
		);
	}

	/**
	 * Walk nested associative objects looking for element-shaped arrays.
	 * Bounded to MAX_DEPTH to avoid runaway recursion on cycles.
	 */
	private function deep_walk_for_elements( array &$node, $hint, $depth ) {
		if ( $depth > 8 ) { return; }
		foreach ( $node as $field => &$value ) {
			if ( ! is_array( $value ) ) { continue; }
			if ( self::looks_like_element_list( $value ) ) {
				foreach ( $value as $j => &$nested ) {
					if ( is_array( $nested ) ) {
						$this->visit_element( $nested, $hint . '.' . $field . '[' . $j . ']' );
					}
				}
				unset( $nested );
			} else {
				$this->deep_walk_for_elements( $value, $hint . '.' . $field, $depth + 1 );
			}
		}
		unset( $value );
	}

	private static function looks_like_element_list( array $arr ) {
		// Heuristic: array of associative items, at least one with a `type`
		// or `attid` key (image-like). Loose enough to catch plugin-defined
		// container shapes like socialmediaicons.icons[].
		foreach ( $arr as $item ) {
			if ( is_array( $item ) && ( isset( $item['type'] ) || isset( $item['attid'] ) ) ) {
				return true;
			}
		}
		return false;
	}
}
