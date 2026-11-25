<?php
/**
 * Lay Blueprints — Validator.
 *
 * Pre-flight check before applying an import:
 *
 *   1. schema_version compatible (we accept ≤ current schema; newer requires
 *      a Lay update — hard fail)
 *   2. Lay theme version ≥ compat.lay_theme.min_required
 *   3. For each compat.required_plugins entry: state of (slug)
 *      - 'ok'        installed + active + version ok
 *      - 'outdated'  installed + active but version < min_required
 *      - 'inactive'  installed but not active
 *      - 'missing'   not installed at all
 *
 * Returns a structured report. Only state 'ok' across the board allows
 * /import/apply to proceed.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_Validator {

	/**
	 * @return array {
	 *   passed        : bool
	 *   schema        : array{ source: int, current: int, status: 'ok'|'too_new'|'too_old' }
	 *   lay_theme     : array{ min_required: string|null, current: string|null, status: 'ok'|'outdated'|'unknown' }
	 *   plugins       : array<int, array{ slug, name, min_required, current, plugin_file, status }>
	 *   counts        : array  (from manifest, surfaced for the UI)
	 *   warnings      : string[]
	 * }
	 */
	public static function validate( array $manifest ) {
		$report = array(
			'passed'    => false,
			'schema'    => self::check_schema( $manifest ),
			'lay_theme' => self::check_lay_theme( $manifest ),
			'plugins'   => self::check_plugins( $manifest ),
			'counts'    => isset( $manifest['counts'] ) && is_array( $manifest['counts'] ) ? $manifest['counts'] : array(),
			'warnings'  => array(),
		);

		if ( self::is_qtranslate_xt_active() ) {
			$report['warnings'][] = 'qTranslate-XT is active.';
		}

		$report['passed'] =
			   $report['schema']['status']    === 'ok'
			&& $report['lay_theme']['status'] === 'ok'
			&& self::all_plugins_ok( $report['plugins'] );

		return $report;
	}

	private static function check_schema( array $manifest ) {
		$source  = isset( $manifest['schema_version'] ) ? (int) $manifest['schema_version'] : 0;
		$current = (int) LayBlueprint_Schema::SCHEMA_VERSION;
		if ( $source <= 0 ) {
			return array( 'source' => 0, 'current' => $current, 'status' => 'too_old' );
		}
		if ( $source > $current ) {
			return array( 'source' => $source, 'current' => $current, 'status' => 'too_new' );
		}
		return array( 'source' => $source, 'current' => $current, 'status' => 'ok' );
	}

	private static function check_lay_theme( array $manifest ) {
		$min_required = isset( $manifest['compat']['lay_theme']['min_required'] )
			? (string) $manifest['compat']['lay_theme']['min_required']
			: null;
		$current = LayBlueprint_Schema::lay_theme_version();
		if ( $current === null ) {
			return array( 'min_required' => $min_required, 'current' => null, 'status' => 'unknown' );
		}
		if ( $min_required && version_compare( $current, $min_required, '<' ) ) {
			return array( 'min_required' => $min_required, 'current' => $current, 'status' => 'outdated' );
		}
		return array( 'min_required' => $min_required, 'current' => $current, 'status' => 'ok' );
	}

	private static function check_plugins( array $manifest ) {
		$required = isset( $manifest['compat']['required_plugins'] ) && is_array( $manifest['compat']['required_plugins'] )
			? $manifest['compat']['required_plugins']
			: array();

		if ( empty( $required ) ) { return array(); }

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins    = get_plugins();
		$active_plugins = (array) get_option( 'active_plugins', array() );

		// Index by slug → (file, version)
		$by_slug = array();
		foreach ( $all_plugins as $file => $data ) {
			$slug = dirname( $file );
			if ( $slug === '.' || $slug === '' ) { continue; }
			$by_slug[ $slug ] = array(
				'file'    => $file,
				'name'    => isset( $data['Name'] )    ? $data['Name']    : $slug,
				'version' => isset( $data['Version'] ) ? $data['Version'] : '0.0.0',
			);
		}

		$out = array();
		foreach ( $required as $req ) {
			if ( ! is_array( $req ) || empty( $req['slug'] ) ) { continue; }
			$slug = (string) $req['slug'];
			$min  = isset( $req['min_required'] ) ? (string) $req['min_required'] : '';

			if ( $slug === LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG ) {
				$out[] = self::check_qtranslate_xt_plugin( $req, $min, $all_plugins, $active_plugins );
				continue;
			}

			if ( ! isset( $by_slug[ $slug ] ) ) {
				$out[] = array(
					'slug'         => $slug,
					'name'         => isset( $req['name'] ) ? $req['name'] : $slug,
					'min_required' => $min,
					'current'      => null,
					'plugin_file'  => null,
					'status'       => 'missing',
				);
				continue;
			}

			$info     = $by_slug[ $slug ];
			$is_active = in_array( $info['file'], $active_plugins, true );
			if ( ! $is_active ) {
				$out[] = array(
					'slug'         => $slug,
					'name'         => $info['name'],
					'min_required' => $min,
					'current'      => $info['version'],
					'plugin_file'  => $info['file'],
					'status'       => 'inactive',
				);
				continue;
			}

			if ( $min !== '' && version_compare( $info['version'], $min, '<' ) ) {
				$out[] = array(
					'slug'         => $slug,
					'name'         => $info['name'],
					'min_required' => $min,
					'current'      => $info['version'],
					'plugin_file'  => $info['file'],
					'status'       => 'outdated',
				);
				continue;
			}

			$out[] = array(
				'slug'         => $slug,
				'name'         => $info['name'],
				'min_required' => $min,
				'current'      => $info['version'],
				'plugin_file'  => $info['file'],
				'status'       => 'ok',
			);
		}
		return $out;
	}

	private static function check_qtranslate_xt_plugin( array $req, $min, array $all_plugins, array $active_plugins ) {
		$current = defined( 'QTX_VERSION' ) ? (string) QTX_VERSION : null;
		$name = isset( $req['name'] ) && $req['name'] !== '' ? (string) $req['name'] : 'qTranslate-XT';
		$installed = false;
		$installed_active = false;

		foreach ( $all_plugins as $file => $data ) {
			$plugin_name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
			if ( stripos( $plugin_name, 'qTranslate-XT' ) === false && stripos( $plugin_name, 'qTranslate XT' ) === false ) {
				continue;
			}
			$installed = true;
			if ( $plugin_name !== '' ) {
				$name = $plugin_name;
			}
			if ( $current === null && ! empty( $data['Version'] ) ) {
				$current = (string) $data['Version'];
			}
			if ( in_array( $file, $active_plugins, true ) ) {
				$installed_active = true;
			}
		}

		$is_active = LayBlueprint_PluginsCollector::is_qtranslate_xt_active();
		if ( ! $is_active ) {
			return array(
				'slug'         => LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG,
				'name'         => $name,
				'min_required' => $min,
				'current'      => $current,
				'plugin_file'  => null,
				'status'       => $installed || $installed_active ? 'inactive' : 'missing',
			);
		}

		if ( $current === null ) {
			$current = '0.0.0';
		}
		if ( $min !== '' && version_compare( $current, $min, '<' ) ) {
			return array(
				'slug'         => LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG,
				'name'         => $name,
				'min_required' => $min,
				'current'      => $current,
				'plugin_file'  => null,
				'status'       => 'outdated',
			);
		}

		return array(
			'slug'         => LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG,
			'name'         => $name,
			'min_required' => $min,
			'current'      => $current,
			'plugin_file'  => null,
			'status'       => 'ok',
		);
	}

	private static function all_plugins_ok( array $plugin_states ) {
		foreach ( $plugin_states as $p ) {
			if ( $p['status'] !== 'ok' ) { return false; }
		}
		return true;
	}

	private static function is_qtranslate_xt_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return LayBlueprint_PluginsCollector::is_qtranslate_xt_active()
			|| is_plugin_active( 'qtranslate-xt/qtranslate.php' );
	}
}
