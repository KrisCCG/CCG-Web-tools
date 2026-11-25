<?php
/**
 * Lay Blueprints — Plugins Collector.
 *
 * Lists currently active optional plugins with their versions read from
 * plugin headers. Lay plugins use their real plugin folder slug; qTranslate-XT
 * uses a canonical slug because its folder name is often customized.
 *
 * Used both for the Export-tab UI checklist and for building the manifest's
 * compat.required_plugins block based on which ones the user checked.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_PluginsCollector {

	const QTRANSLATE_XT_SLUG = 'qtranslate-xt';

	/**
	 * Active Lay plugins, sorted by name.
	 *
	 * @return array<int, array{slug:string, name:string, version:string, file:string}>
	 */
	public static function active() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active = (array) get_option( 'active_plugins', array() );
		$all    = get_plugins();
		$lay    = array();
		foreach ( $active as $plugin_file ) {
			$slug = dirname( $plugin_file );
			if ( $slug === '.' || $slug === '' ) {
				continue;
			}
			if ( strpos( $slug, 'laytheme-' ) !== 0 && $slug !== 'laygridder' ) {
				continue;
			}
			if ( ! isset( $all[ $plugin_file ] ) ) {
				continue;
			}
			$lay[] = array(
				'slug'    => $slug,
				'name'    => $all[ $plugin_file ]['Name'],
				'version' => $all[ $plugin_file ]['Version'],
				'file'    => $plugin_file,
			);
		}
		$qtranslate = self::qtranslate_xt_active_entry();
		if ( $qtranslate ) {
			$lay[] = $qtranslate;
		}
		usort( $lay, function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		} );
		return $lay;
	}

	/**
	 * Filter the active list down to slugs the user explicitly checked,
	 * shaped for the manifest's required_plugins block. min_required defaults
	 * to the version_at_export (= "exact" strategy).
	 *
	 * @param string[] $checked_slugs Plugin slugs the user marked as required.
	 * @return array<int, array{slug:string, name:string, version_at_export:string, min_required:string}>
	 */
	public static function build_required_block( array $checked_slugs ) {
		$active   = self::active();
		$by_slug  = array();
		foreach ( $active as $p ) {
			$by_slug[ $p['slug'] ] = $p;
		}
		$out = array();
		foreach ( $checked_slugs as $slug ) {
			if ( ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}
			$p = $by_slug[ $slug ];
			$out[] = array(
				'slug'              => $p['slug'],
				'name'              => $p['name'],
				'version_at_export' => $p['version'],
				'min_required'      => $p['version'],
			);
		}
		return $out;
	}

	public static function is_qtranslate_xt_active() {
		return class_exists( 'QTX_Translator' );
	}

	private static function qtranslate_xt_active_entry() {
		if ( ! self::is_qtranslate_xt_active() ) {
			return null;
		}
		$info = self::find_qtranslate_xt_plugin_info();
		return array(
			'slug'    => self::QTRANSLATE_XT_SLUG,
			'name'    => $info['name'],
			'version' => $info['version'],
			'file'    => $info['file'],
		);
	}

	private static function find_qtranslate_xt_plugin_info() {
		$version = defined( 'QTX_VERSION' ) ? (string) QTX_VERSION : '';
		$out = array(
			'name'    => 'qTranslate-XT',
			'version' => $version !== '' ? $version : '0.0.0',
			'file'    => '',
		);

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all = get_plugins();
		$active = (array) get_option( 'active_plugins', array() );
		foreach ( $all as $file => $data ) {
			$name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
			if ( stripos( $name, 'qTranslate-XT' ) === false && stripos( $name, 'qTranslate XT' ) === false ) {
				continue;
			}
			$out['name'] = $name !== '' ? $name : $out['name'];
			if ( ! empty( $data['Version'] ) ) {
				$out['version'] = (string) $data['Version'];
			}
			if ( in_array( $file, $active, true ) ) {
				$out['file'] = $file;
				break;
			}
			if ( $out['file'] === '' ) {
				$out['file'] = $file;
			}
		}
		return $out;
	}
}
