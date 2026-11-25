<?php
/**
 * Lay Blueprints — Content Installer.
 *
 * For each blueprint.content[post_type][] entry:
 *   1. wp_insert_post  (creates a new post; we don't reuse old IDs)
 *   2. Apply post-meta — rewriting attachment IDs in `_thumbnail_id`,
 *      `_lay_thumbnail_mouseover_image`, `_lay_thumbnail_video` via the
 *      attachment IDMap.
 *   3. Apply layouts:
 *        - decode the JSON (if not already decoded by the collector)
 *        - run ElementRewriter to remap attachment IDs in structured fields
 *        - run HtmlImportRewriter on every text/html element's `cont` string
 *        - re-encode and write to _gridder_json / _phone_gridder_json.
 *   4. Apply terms (by slug → wp_set_object_terms).
 *
 * Builds a `post_old_id → new_id` map so MenusInstaller can resolve
 * linked_object refs to new IDs.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_ContentInstaller {

	/** @var LayBlueprint_IdMap */
	private $attachments;

	/** @var LayBlueprint_IdMap */
	private $posts;

	/** @var LayBlueprint_ElementRewriter */
	private $element_rewriter;

	/** @var LayBlueprint_HtmlImportRewriter */
	private $html_rewriter;

	const ATTACHMENT_ID_META_KEYS = array(
		'_thumbnail_id',
		'_lay_thumbnail_mouseover_image',
		'_lay_thumbnail_video',
	);

	public function __construct( LayBlueprint_IdMap $attachments, LayBlueprint_IdMap $posts ) {
		$this->attachments      = $attachments;
		$this->posts            = $posts;
		$this->element_rewriter = new LayBlueprint_ElementRewriter( $attachments, $posts );
		$this->html_rewriter    = new LayBlueprint_HtmlImportRewriter( $attachments );
	}

	/**
	 * @param array $content_by_type post_type → array of post entries (from blueprint.json).
	 * @return array audit
	 */
	public function install( array $content_by_type, $on_progress = null ) {
		$total       = 0;
		foreach ( $content_by_type as $list ) { $total += is_array( $list ) ? count( $list ) : 0; }

		$audit = array(
			'created'        => 0,
			'failed'         => 0,
			'layouts_applied'=> 0,
			'terms_set'      => 0,
		);
		$done = 0;

		// Pass 1: create posts (capture old → new mapping).
		foreach ( $content_by_type as $post_type => $entries ) {
			if ( ! is_array( $entries ) ) { continue; }
			foreach ( $entries as $entry ) {
				$done++;
				$new_id = $this->create_post( $post_type, $entry );
				if ( ! $new_id ) {
					$audit['failed']++;
					if ( $on_progress ) { call_user_func( $on_progress, $done, $total, 'create' ); }
					continue;
				}
				$audit['created']++;
				if ( ! empty( $entry['old_id'] ) ) {
					$this->posts->set( (int) $entry['old_id'], $new_id );
				}
				if ( $on_progress ) { call_user_func( $on_progress, $done, $total, 'create' ); }
			}
		}

		// Pass 2: parents, layouts, meta, terms — done as a second pass so
		// post_parent references resolve via the just-built posts IDMap.
		$done = 0;
		foreach ( $content_by_type as $post_type => $entries ) {
			if ( ! is_array( $entries ) ) { continue; }
			foreach ( $entries as $entry ) {
				$done++;
				if ( empty( $entry['old_id'] ) ) { continue; }
				$new_id = $this->posts->lookup( (int) $entry['old_id'] );
				if ( $new_id === null ) { continue; }

				$this->apply_post_parent( $new_id, $entry );
				$this->apply_meta( $new_id, isset( $entry['meta'] ) ? (array) $entry['meta'] : array() );
				$applied = $this->apply_layouts( $new_id, isset( $entry['layouts'] ) ? (array) $entry['layouts'] : array() );
				$audit['layouts_applied'] += $applied;
				$set = $this->apply_terms( $new_id, isset( $entry['terms'] ) ? (array) $entry['terms'] : array() );
				$audit['terms_set'] += $set;

				if ( $on_progress ) { call_user_func( $on_progress, $done, $total, 'finalize' ); }
			}
		}

		return $audit;
	}

	private function create_post( $post_type, array $entry ) {
		if ( ! post_type_exists( $post_type ) ) { return 0; }
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => isset( $entry['status'] )      ? (string) $entry['status']     : 'publish',
			'post_title'     => isset( $entry['title'] )       ? (string) $entry['title']      : '',
			'post_name'      => isset( $entry['slug'] )        ? (string) $entry['slug']       : '',
			'post_content'   => isset( $entry['content'] )     ? $this->html_rewriter->rewrite( (string) $entry['content'] ) : '',
			'post_excerpt'   => isset( $entry['excerpt'] )     ? (string) $entry['excerpt']    : '',
			'menu_order'     => isset( $entry['menu_order'] )  ? (int) $entry['menu_order']    : 0,
			'comment_status' => isset( $entry['comment_status'] ) ? (string) $entry['comment_status'] : 'closed',
			'ping_status'    => isset( $entry['ping_status'] )    ? (string) $entry['ping_status']    : 'closed',
		);
		if ( ! empty( $entry['date_gmt'] ) ) {
			$args['post_date_gmt'] = $entry['date_gmt'];
			$args['post_date']     = get_date_from_gmt( $entry['date_gmt'] );
		}
		$result = wp_insert_post( $args, true );
		if ( is_wp_error( $result ) ) { return 0; }
		return (int) $result;
	}

	private function apply_post_parent( $new_id, array $entry ) {
		if ( empty( $entry['parent_old_id'] ) ) { return; }
		$new_parent = $this->posts->lookup( (int) $entry['parent_old_id'] );
		if ( $new_parent === null ) { return; }
		wp_update_post( array( 'ID' => $new_id, 'post_parent' => $new_parent ) );
	}

	private function apply_meta( $new_id, array $meta ) {
		foreach ( $meta as $key => $value ) {
			if ( in_array( $key, self::ATTACHMENT_ID_META_KEYS, true ) ) {
				$old_attid = (int) ( is_array( $value ) ? reset( $value ) : $value );
				if ( $old_attid > 0 ) {
					$mapped = $this->attachments->lookup( $old_attid );
					if ( $mapped !== null ) {
						update_post_meta( $new_id, $key, $mapped );
						continue;
					}
				}
				continue; // skip unmapped attachment refs
			}
			update_post_meta( $new_id, $key, $value );
		}
	}

	private function apply_layouts( $new_id, array $layouts ) {
		$applied = 0;
		foreach ( array( '_gridder_json', '_phone_gridder_json' ) as $layout_key ) {
			if ( ! isset( $layouts[ $layout_key ] ) ) { continue; }
			$slot = $layouts[ $layout_key ];
			if ( ! is_array( $slot ) ) { continue; }

			$decoded = isset( $slot['decoded'] ) && is_array( $slot['decoded'] )
				? $slot['decoded']
				: ( isset( $slot['raw'] ) ? json_decode( wp_unslash( (string) $slot['raw'] ), true ) : null );

			if ( ! is_array( $decoded ) ) { continue; }

			// Element-level attachment ID rewrite.
			$this->element_rewriter->rewrite( $decoded );

			// HTML-string rewrite for text/html element cont fields.
			$this->rewrite_html_in_layout( $decoded );

			$json = wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			update_post_meta( $new_id, $layout_key, wp_slash( (string) $json ) );
			$applied++;
		}
		return $applied;
	}

	private function rewrite_html_in_layout( array &$layout ) {
		if ( ! isset( $layout['cont'] ) || ! is_array( $layout['cont'] ) ) { return; }
		foreach ( $layout['cont'] as &$el ) {
			if ( is_array( $el ) ) {
				$this->rewrite_html_in_element( $el );
			}
		}
		unset( $el );
	}

	private function rewrite_html_in_element( array &$el ) {
		$type = isset( $el['type'] ) ? (string) $el['type'] : '';
		if ( $type === 'text' || $type === 'html' ) {
			if ( isset( $el['cont'] ) && is_string( $el['cont'] ) ) {
				$el['cont'] = $this->html_rewriter->rewrite( $el['cont'] );
			} elseif ( isset( $el['cont']['cont'] ) && is_string( $el['cont']['cont'] ) ) {
				$el['cont']['cont'] = $this->html_rewriter->rewrite( $el['cont']['cont'] );
			}
		}
		// Recurse into containers.
		if ( $type === 'carousel' && isset( $el['carousel'] ) && is_array( $el['carousel'] ) ) {
			foreach ( $el['carousel'] as &$slide ) {
				if ( is_array( $slide ) ) { $this->rewrite_html_in_element( $slide ); }
			}
			unset( $slide );
		}
		if ( $type === 'elementgrid' && isset( $el['config']['elements'] ) && is_array( $el['config']['elements'] ) ) {
			foreach ( $el['config']['elements'] as &$nested ) {
				if ( is_array( $nested ) ) { $this->rewrite_html_in_element( $nested ); }
			}
			unset( $nested );
		}
		if ( $type === 'stack' && isset( $el['cont'] ) && is_array( $el['cont'] ) ) {
			foreach ( $el['cont'] as &$nested ) {
				if ( is_array( $nested ) ) { $this->rewrite_html_in_element( $nested ); }
			}
			unset( $nested );
		}
	}

	private function apply_terms( $new_id, array $terms ) {
		$count = 0;
		foreach ( $terms as $taxonomy => $slugs ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$slugs = array_filter( (array) $slugs, 'is_string' );
			if ( empty( $slugs ) ) { continue; }
			$result = wp_set_object_terms( $new_id, $slugs, $taxonomy, false );
			if ( ! is_wp_error( $result ) ) {
				$count += count( (array) $result );
			}
		}
		return $count;
	}
}
