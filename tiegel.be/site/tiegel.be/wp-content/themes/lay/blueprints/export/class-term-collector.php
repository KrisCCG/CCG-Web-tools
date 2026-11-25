<?php
/**
 * Lay Blueprints — Term Collector.
 *
 * Walks every taxonomy that user-facing content uses (default category,
 * lay_news_category, post_tag) and exports the term hierarchy in a
 * portable form that doesn't depend on local term IDs.
 *
 * Output entries reference parents by slug, not by term_id. The importer
 * recreates terms in dependency order and builds a `old_term_id → new_term_id`
 * map that is then used to renumber the per-category layout option keys
 * (`{term_id}_category_gridder_json`).
 *
 * Excluded: WC product_cat / product_tag, plus any taxonomy that lay_custom_element
 * registers (that post type is out of V1 scope).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_TermCollector {

	const EXCLUDED_TAXONOMIES = array(
		'product_cat',
		'product_tag',
		'product_brand',
		'product_shipping_class',
		'pa_color',
		'pa_size',
		'lay_custom_element_category', // example placeholder; safe to ignore if not registered
		'nav_menu',                    // handled by MenusCollector
		'link_category',
	);

	/**
	 * @return array{ values: array, audit: array{ taxonomies: string[], term_count: int } }
	 */
	public static function collect() {
		$taxonomies = self::pickable_taxonomies();
		$result     = array();
		$total      = 0;

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$id_to_slug = array();
			foreach ( $terms as $t ) {
				$id_to_slug[ (int) $t->term_id ] = $t->slug;
			}

			$tax_entries = array();
			foreach ( $terms as $t ) {
				$parent_slug = ( $t->parent && isset( $id_to_slug[ (int) $t->parent ] ) )
					? $id_to_slug[ (int) $t->parent ]
					: null;

				$tax_entries[] = array(
					// We keep the original term_id alongside the slug so the
					// importer can map old `{term_id}_category_gridder_json`
					// keys to new ones via id → slug → new id.
					'old_term_id' => (int) $t->term_id,
					'slug'        => $t->slug,
					'name'        => $t->name,
					'description' => $t->description,
					'parent_slug' => $parent_slug,
					'meta'        => self::collect_term_meta( $t->term_id ),
				);
				$total++;
			}

			// Preserve hierarchy by sorting parents-first.
			usort( $tax_entries, function ( $a, $b ) {
				if ( $a['parent_slug'] === null && $b['parent_slug'] !== null ) { return -1; }
				if ( $a['parent_slug'] !== null && $b['parent_slug'] === null ) { return 1; }
				return strcmp( $a['slug'], $b['slug'] );
			} );

			$result[ $taxonomy ] = $tax_entries;
		}

		return array(
			'values' => $result,
			'audit'  => array(
				'taxonomies' => array_keys( $result ),
				'term_count' => $total,
			),
		);
	}

	private static function pickable_taxonomies() {
		$all = get_taxonomies( array( 'public' => true ), 'names' );
		$out = array();
		foreach ( (array) $all as $name ) {
			if ( in_array( $name, self::EXCLUDED_TAXONOMIES, true ) ) {
				continue;
			}
			$out[] = $name;
		}
		return $out;
	}

	private static function collect_term_meta( $term_id ) {
		$meta_all = get_term_meta( $term_id );
		if ( ! is_array( $meta_all ) ) {
			return array();
		}
		$out = array();
		foreach ( $meta_all as $key => $values ) {
			// Skip WP-internal scaffolding.
			if ( strpos( $key, '_edit_' ) === 0 ) { continue; }
			// Term meta is rarely Lay-owned; export only Lay-prefixed for now.
			if ( strpos( $key, 'lay_' ) !== 0 && strpos( $key, '_lay_' ) !== 0 ) { continue; }
			$out[ $key ] = array_map( 'maybe_unserialize', (array) $values );
		}
		return $out;
	}
}
