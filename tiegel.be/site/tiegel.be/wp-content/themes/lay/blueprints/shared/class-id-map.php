<?php
/**
 * Lay Blueprints — ID Map.
 *
 * Generic old → new ID mapping built up during import. Two instances per
 * import job: one for attachment IDs (built by MediaImporter + FontsImporter),
 * one for term IDs (built by TermImporter). Used by every later step that
 * needs to translate references in layouts, post-meta, and option keys.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_IdMap {

	/** @var array<int,int> old_id → new_id */
	private $map = array();

	/** @var array<int,string> old_id → reason it couldn't be mapped */
	private $missing = array();

	/** @var string Label used in audit messages, e.g. 'attachment' or 'term'. */
	private $label;

	public function __construct( $label = 'id' ) {
		$this->label = (string) $label;
	}

	public function set( $old_id, $new_id ) {
		$old = (int) $old_id;
		$new = (int) $new_id;
		if ( $old <= 0 || $new <= 0 ) { return; }
		$this->map[ $old ] = $new;
		unset( $this->missing[ $old ] );
	}

	public function mark_missing( $old_id, $reason = '' ) {
		$old = (int) $old_id;
		if ( $old <= 0 ) { return; }
		if ( ! isset( $this->map[ $old ] ) ) {
			$this->missing[ $old ] = (string) $reason;
		}
	}

	/**
	 * Look up new id. Returns null if not mapped (caller decides what to do).
	 */
	public function lookup( $old_id ) {
		$old = (int) $old_id;
		return isset( $this->map[ $old ] ) ? $this->map[ $old ] : null;
	}

	public function has( $old_id ) {
		return isset( $this->map[ (int) $old_id ] );
	}

	public function size() {
		return count( $this->map );
	}

	public function missing_count() {
		return count( $this->missing );
	}

	public function get_map() {
		return $this->map;
	}

	public function get_missing() {
		return $this->missing;
	}

	public function get_label() {
		return $this->label;
	}
}
