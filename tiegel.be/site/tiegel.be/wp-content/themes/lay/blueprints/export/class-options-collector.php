<?php
/**
 * Lay Blueprints — Options Collector.
 *
 * Walks wp_options, asks LayBlueprint_KnownKeys whether each row should be
 * exported (static allowlist + pattern match + exclusion check), and returns
 * the resulting key→value dict plus an audit list.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_OptionsCollector {

	const SPECIALIZED_OPTIONS = array(
		'fontmanager_json',
	);

	/**
	 * @return array{ values: array<string,mixed>, audit: array{
	 *     matched_static: string[], matched_pattern: array, excluded_count: int, scanned_count: int } }
	 */
	public static function collect() {
		global $wpdb;

		$rows = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options}", ARRAY_A );
		$values          = array();
		$matched_static  = array();
		$matched_pattern = array();
		$excluded_count  = 0;
		$scanned_count   = is_array( $rows ) ? count( $rows ) : 0;

		if ( ! is_array( $rows ) ) {
			return self::empty_result();
		}

		$known       = LayBlueprint_KnownKeys::get_all();
		$static_set  = array_flip( $known['static_options'] );
		$patterns    = array_filter( $known['patterns'], function ( $p ) {
			return $p['kind'] !== 'theme_mod';
		} );

		foreach ( $rows as $row ) {
			$name = $row['option_name'];

			if ( in_array( $name, self::SPECIALIZED_OPTIONS, true ) ) {
				$excluded_count++;
				continue;
			}

			if ( LayBlueprint_KnownKeys::is_excluded( $name ) ) {
				$excluded_count++;
				continue;
			}

			$is_static = isset( $static_set[ $name ] );
			$matched_p = null;
			if ( ! $is_static ) {
				foreach ( $patterns as $p ) {
					if ( self::regex_match( $p['regex'], $name ) ) {
						$matched_p = $p['template'];
						break;
					}
				}
			}

			if ( ! $is_static && $matched_p === null ) {
				continue;
			}

			$values[ $name ] = maybe_unserialize( $row['option_value'] );

			if ( $is_static ) {
				$matched_static[] = $name;
			} else {
				$matched_pattern[] = array( 'name' => $name, 'template' => $matched_p );
			}
		}

		return array(
			'values' => $values,
			'audit'  => array(
				'matched_static'  => $matched_static,
				'matched_pattern' => $matched_pattern,
				'excluded_count'  => $excluded_count,
				'scanned_count'   => $scanned_count,
			),
		);
	}

	private static function empty_result() {
		return array(
			'values' => array(),
			'audit'  => array(
				'matched_static'  => array(),
				'matched_pattern' => array(),
				'excluded_count'  => 0,
				'scanned_count'   => 0,
			),
		);
	}

	private static function regex_match( $regex, $subject ) {
		$is_delimited = ( strlen( $regex ) >= 2 && $regex[0] === $regex[ strlen( $regex ) - 1 ] && ! ctype_alnum( $regex[0] ) );
		$pattern = $is_delimited ? $regex : '#' . $regex . '#';
		$result = @preg_match( $pattern, $subject );
		return $result === 1;
	}
}
