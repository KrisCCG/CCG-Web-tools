<?php
/**
 * Lay Blueprints — Job State
 *
 * Transient-backed state container for export and import jobs. Survives
 * across REST requests so the UI can poll progress. Jobs auto-expire after
 * 24 hours.
 *
 * State shape:
 *   array(
 *     'job_id'     => string,
 *     'kind'       => 'export' | 'import',
 *     'status'     => 'pending' | 'running' | 'complete' | 'failed',
 *     'progress'   => int 0..100,
 *     'step'       => string  (current phase label, user-facing)
 *     'file_path'  => string|null  (absolute path to result ZIP/snapshot)
 *     'file_name'  => string|null  (suggested download filename)
 *     'error'      => string|null
 *     'meta'       => array  (free-form per-kind data)
 *     'created_at' => int    (unix ts)
 *     'updated_at' => int
 *   )
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_JobState {

	const TRANSIENT_PREFIX = 'lay_blueprint_job_';
	const TTL_SECONDS      = DAY_IN_SECONDS;

	public static function create( $kind ) {
		$job_id = self::new_job_id();
		$now    = time();
		$state  = array(
			'job_id'     => $job_id,
			'kind'       => $kind,
			'status'     => 'pending',
			'progress'   => 0,
			'step'       => 'Initialising',
			'file_path'  => null,
			'file_name'  => null,
			'error'      => null,
			'meta'       => array(),
			'created_at' => $now,
			'updated_at' => $now,
		);
		self::write( $job_id, $state );
		return $state;
	}

	public static function get( $job_id ) {
		if ( ! is_string( $job_id ) || $job_id === '' ) {
			return null;
		}
		$value = get_transient( self::TRANSIENT_PREFIX . $job_id );
		return is_array( $value ) ? $value : null;
	}

	public static function update( $job_id, array $patch ) {
		$current = self::get( $job_id );
		if ( $current === null ) {
			return null;
		}
		// Merge meta key-by-key so callers can append without overwriting.
		if ( isset( $patch['meta'] ) && is_array( $patch['meta'] ) ) {
			$patch['meta'] = array_merge( $current['meta'], $patch['meta'] );
		}
		$next               = array_merge( $current, $patch );
		$next['updated_at'] = time();
		self::write( $job_id, $next );
		return $next;
	}

	public static function fail( $job_id, $message ) {
		return self::update( $job_id, array(
			'status'   => 'failed',
			'progress' => 100,
			'error'    => (string) $message,
		) );
	}

	public static function complete( $job_id, $file_path, $file_name ) {
		return self::update( $job_id, array(
			'status'    => 'complete',
			'progress'  => 100,
			'step'      => 'Done',
			'file_path' => $file_path,
			'file_name' => $file_name,
		) );
	}

	public static function delete( $job_id ) {
		delete_transient( self::TRANSIENT_PREFIX . $job_id );
	}

	private static function write( $job_id, array $state ) {
		set_transient( self::TRANSIENT_PREFIX . $job_id, $state, self::TTL_SECONDS );
	}

	private static function new_job_id() {
		// 16 hex chars, sufficient for collision-free transient naming.
		return bin2hex( random_bytes( 8 ) );
	}
}
