<?php
/**
 * Lay Blueprints — Term Importer.
 *
 * Walks blueprint.terms (taxonomy → entries[]) in dependency order
 * (parents-first, sorted upstream by the collector) and either creates
 * each term via wp_insert_term or reuses an existing same-slug term.
 * Populates a TermIDMap (old_term_id → new_term_id) used by:
 *   - OptionsInstaller for category-archive layouts
 *   - ContentInstaller for object_id resolution on menus
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_TermImporter {

	/** @var LayBlueprint_IdMap */
	private $id_map;

	public function __construct( LayBlueprint_IdMap $id_map ) {
		$this->id_map = $id_map;
	}

	/**
	 * @return array audit { taxonomy: string, created: int, reused: int, failed: int }[]
	 */
	public function import( array $terms_by_taxonomy ) {
		$audit = array();
		foreach ( $terms_by_taxonomy as $taxonomy => $entries ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$audit[] = array( 'taxonomy' => $taxonomy, 'created' => 0, 'reused' => 0, 'failed' => 0, 'note' => 'taxonomy_not_registered' );
				continue;
			}
			$created = 0;
			$reused  = 0;
			$failed  = 0;

			// Build slug → new_term_id as we go so children find their parents.
			$slug_to_new = array();

			foreach ( (array) $entries as $entry ) {
				if ( ! is_array( $entry ) || empty( $entry['slug'] ) ) {
					$failed++;
					continue;
				}
				$slug = (string) $entry['slug'];
				$name = isset( $entry['name'] ) ? (string) $entry['name'] : $slug;
				$desc = isset( $entry['description'] ) ? (string) $entry['description'] : '';

				$parent_new_id = 0;
				if ( ! empty( $entry['parent_slug'] ) && isset( $slug_to_new[ $entry['parent_slug'] ] ) ) {
					$parent_new_id = (int) $slug_to_new[ $entry['parent_slug'] ];
				}

				$existing = get_term_by( 'slug', $slug, $taxonomy );
				if ( $existing && ! is_wp_error( $existing ) ) {
					$new_id = (int) $existing->term_id;
					$reused++;
				} else {
					$result = wp_insert_term( $name, $taxonomy, array(
						'slug'        => $slug,
						'description' => $desc,
						'parent'      => $parent_new_id,
					) );
					if ( is_wp_error( $result ) ) {
						$failed++;
						continue;
					}
					$new_id = (int) $result['term_id'];
					$created++;
				}

				$slug_to_new[ $slug ] = $new_id;
				if ( ! empty( $entry['old_term_id'] ) ) {
					$this->id_map->set( (int) $entry['old_term_id'], $new_id );
				}

				// Apply Lay-prefixed term meta.
				if ( ! empty( $entry['meta'] ) && is_array( $entry['meta'] ) ) {
					foreach ( $entry['meta'] as $meta_key => $values ) {
						$values = is_array( $values ) ? $values : array( $values );
						delete_term_meta( $new_id, $meta_key );
						foreach ( $values as $v ) {
							add_term_meta( $new_id, $meta_key, $v );
						}
					}
				}
			}

			$audit[] = array(
				'taxonomy' => $taxonomy,
				'created'  => $created,
				'reused'   => $reused,
				'failed'   => $failed,
			);
		}
		return $audit;
	}
}
