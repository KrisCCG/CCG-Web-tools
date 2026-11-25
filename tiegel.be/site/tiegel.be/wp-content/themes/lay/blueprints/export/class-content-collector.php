<?php
/**
 * Lay Blueprints — Content Collector.
 *
 * Walks user-facing post types (post, page, lay_news, lay_template) and
 * exports each post's title/slug/status/content/excerpt + filtered post-meta
 * (using KnownKeys::should_export_post_meta) + the gridder layout JSONs.
 *
 * Layouts are passed through ElementWalker which collects attachment IDs
 * (registered with the MediaResolver) and queues HTML strings for the
 * HTMLRewriter. Layouts are mutated in-place (HTML strings get rewritten
 * to placeholder anchors).
 *
 * Excluded post types in V1: any WooCommerce types (`product`, `shop_*`)
 * plus `lay_custom_element` (out of scope).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ContentCollector {

	const EXCLUDED_POST_TYPES = array(
		'product',
		'shop_order',
		'shop_coupon',
		'shop_subscription',
		'lay_custom_element',
		'attachment',
		'revision',
		'nav_menu_item',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'oembed_cache',
		'user_request',
		'customize_changeset',
	);

	const POST_META_LAYOUT_KEYS = array( '_gridder_json', '_phone_gridder_json' );

	/** @var LayBlueprint_MediaResolver */
	private $resolver;

	/** @var LayBlueprint_HtmlRewriter */
	private $html_rewriter;

	/** @var string[] aggregated unknown-element warnings from the walker */
	public $unknown_warnings = array();

	public function __construct( LayBlueprint_MediaResolver $resolver, LayBlueprint_HtmlRewriter $html_rewriter ) {
		$this->resolver      = $resolver;
		$this->html_rewriter = $html_rewriter;
	}

	/**
	 * @return array{ values: array<string, array>, audit: array }
	 */
	public function collect() {
		$post_types = self::pickable_post_types();
		$by_type    = array();
		$audit      = array(
			'post_types'      => $post_types,
			'counts'          => array(),
			'layouts_walked'  => 0,
			'html_blocks'     => 0,
			'attachment_ids'  => 0,
		);

		foreach ( $post_types as $type ) {
			$posts = get_posts( array(
				'post_type'        => $type,
				'post_status'      => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'numberposts'      => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			) );

			$entries = array();
			$skipped = 0;
			foreach ( (array) $posts as $post ) {
				if ( self::is_unsaved_autodraft( $post ) ) {
					$skipped++;
					continue;
				}
				$entries[] = $this->export_post( $post, $audit );
			}
			$by_type[ $type ]                         = $entries;
			$audit['counts'][ $type ]                 = count( $entries );
			$audit['autodrafts_skipped'][ $type ]     = $skipped;
		}

		$audit['attachment_ids']   = $this->resolver->count_registered();

		return array(
			'values' => $by_type,
			'audit'  => $audit,
		);
	}

	private function export_post( WP_Post $post, array &$audit ) {
		$meta = $this->collect_post_meta( $post->ID );

		// Process layouts: collect attids + rewrite HTML inside text/html elements.
		$layouts = array();
		foreach ( self::POST_META_LAYOUT_KEYS as $layout_key ) {
			if ( ! isset( $meta[ $layout_key ] ) ) {
				continue;
			}
			$raw = $meta[ $layout_key ];
			$decoded = self::decode_layout_json( $raw );
			if ( $decoded === null ) {
				$layouts[ $layout_key ] = array( 'raw' => $raw, 'decoded' => null );
				continue;
			}
			$walker = new LayBlueprint_ElementWalker();
			$hint   = sprintf( '%s:%d:%s', $post->post_type, $post->ID, $layout_key );
			$walker->walk( $decoded, $hint );

			// Register all attachment IDs the walker found, including any
			// fallback dimensions captured from the element so orphan
			// attachments still resolve to a sensible placeholder.
			foreach ( $walker->get_attachment_records() as $id => $rec ) {
				$dims = isset( $rec['fallback_dims'] ) ? $rec['fallback_dims'] : null;
				$this->resolver->register( $id, $rec['kind'], $rec['hint'], $dims );
			}

			// Rewrite HTML strings (mutates the layout in-place via callback).
			foreach ( $walker->get_html_slots() as $slot ) {
				$new_html = $this->html_rewriter->rewrite( $slot['html'], $slot['hint'] );
				if ( $new_html !== $slot['html'] ) {
					call_user_func( $slot['callback'], $new_html );
				}
				$audit['html_blocks']++;
			}

			$audit['layouts_walked']++;

			if ( ! empty( $walker->unknown_warnings ) ) {
				$this->unknown_warnings = array_merge( $this->unknown_warnings, $walker->unknown_warnings );
			}

			// Store the (now possibly mutated) decoded layout.
			$layouts[ $layout_key ] = array( 'decoded' => $decoded );

			// Drop the raw string from meta so it doesn't double-export.
			unset( $meta[ $layout_key ] );
		}

		// Featured image / mouseover / video — these are stored in meta as
		// attachment IDs; the resolver needs them too so they get manifest
		// entries and the importer can remap.
		foreach ( array( '_thumbnail_id', '_lay_thumbnail_mouseover_image', '_lay_thumbnail_video' ) as $featured_key ) {
			if ( isset( $meta[ $featured_key ] ) && is_numeric( $meta[ $featured_key ] ) ) {
				$kind = $featured_key === '_lay_thumbnail_video' ? 'video' : 'image';
				$this->resolver->register( (int) $meta[ $featured_key ], $kind, sprintf( 'post:%d:%s', $post->ID, $featured_key ) );
			}
		}

		return array(
			'post_type'      => $post->post_type,
			'old_id'         => (int) $post->ID,
			'slug'           => $post->post_name,
			'title'          => $post->post_title,
			'status'         => $post->post_status,
			'menu_order'     => (int) $post->menu_order,
			'date_gmt'       => $post->post_date_gmt,
			'modified_gmt'   => $post->post_modified_gmt,
			'content'        => $post->post_content,
			'excerpt'        => $post->post_excerpt,
			'parent_old_id'  => (int) $post->post_parent,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'meta'           => $meta,
			'layouts'        => $layouts,
			'terms'          => self::collect_post_terms( $post ),
		);
	}

	private function collect_post_meta( $post_id ) {
		$all = get_post_meta( $post_id );
		if ( ! is_array( $all ) ) { return array(); }
		$out = array();
		foreach ( $all as $key => $values ) {
			if ( ! LayBlueprint_KnownKeys::should_export_post_meta( $key ) ) {
				continue;
			}
			// get_post_meta returns each value as an array of strings.
			// Most Lay meta is single-value; preserve scalar shape for those.
			$decoded = array_map( 'maybe_unserialize', (array) $values );
			$out[ $key ] = count( $decoded ) === 1 ? $decoded[0] : $decoded;
		}
		return $out;
	}

	private static function collect_post_terms( WP_Post $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type );
		$out = array();
		foreach ( (array) $taxonomies as $tax ) {
			$terms = wp_get_object_terms( $post->ID, $tax, array( 'fields' => 'all' ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
			$out[ $tax ] = array_values( array_map( function ( $t ) {
				return $t->slug;
			}, $terms ) );
		}
		return $out;
	}

	private static function decode_layout_json( $raw ) {
		if ( ! is_string( $raw ) || $raw === '' ) {
			return null;
		}
		// stripslashes for legacy WP-slashed values.
		$decoded = json_decode( $raw, true );
		if ( $decoded === null && json_last_error() !== JSON_ERROR_NONE ) {
			$decoded = json_decode( wp_unslash( $raw ), true );
		}
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * WP creates an "auto-draft" placeholder when a user clicks "Add new"
	 * but never saves. These have post_date_gmt = '0000-00-00 00:00:00'
	 * and usually empty slug + nonsense title. They will fail wp_insert_post
	 * on import (invalid date), so we skip them at export time.
	 */
	private static function is_unsaved_autodraft( WP_Post $post ) {
		return $post->post_date_gmt === '0000-00-00 00:00:00';
	}

	private static function pickable_post_types() {
		$all = get_post_types( array( 'public' => true ), 'names' );
		// Always include lay_template even if its `public` flag changes.
		if ( post_type_exists( 'lay_template' ) ) {
			$all['lay_template'] = 'lay_template';
		}
		$out = array();
		foreach ( (array) $all as $name ) {
			if ( in_array( $name, self::EXCLUDED_POST_TYPES, true ) ) { continue; }
			$out[] = $name;
		}
		return $out;
	}
}
