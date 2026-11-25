<?php
/**
 * Lay Blueprints — Bootstrap
 *
 * Registers the "Lay Blueprints" submenu under "Lay Options", enqueues the
 * React UI bundle on that page, and wires the REST API.
 *
 * The actual export/import logic lives in export/, import/, shared/, and
 * known-keys/ subdirectories. See readme.md for the architecture.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( defined( 'LAY_ENABLE_BLUEPRINTS' ) && ! LAY_ENABLE_BLUEPRINTS ) {
	return;
}

require_once Setup::$dir . '/blueprints/known-keys/class-known-keys-loader.php';
require_once Setup::$dir . '/blueprints/shared/interface-placeholder-provider.php';
require_once Setup::$dir . '/blueprints/shared/class-lay-blueprint-pics-provider.php';
require_once Setup::$dir . '/blueprints/shared/placeholder-provider.php';
require_once Setup::$dir . '/blueprints/shared/class-job-state.php';
require_once Setup::$dir . '/blueprints/shared/class-schema.php';
require_once Setup::$dir . '/blueprints/shared/class-remote-attachment-filters.php';

// Always-on: virtual attachments imported by Lay Blueprints serve their
// images from a remote service (lay-blueprint-pics). Register the URL
// filters on every request so frontend rendering Just Works.
LayBlueprint_RemoteAttachmentFilters::register();
require_once Setup::$dir . '/blueprints/export/class-options-collector.php';
require_once Setup::$dir . '/blueprints/export/class-thememods-collector.php';
require_once Setup::$dir . '/blueprints/export/class-menus-collector.php';
require_once Setup::$dir . '/blueprints/export/class-plugins-collector.php';
require_once Setup::$dir . '/blueprints/export/class-term-collector.php';
require_once Setup::$dir . '/blueprints/export/class-element-walker.php';
require_once Setup::$dir . '/blueprints/export/class-media-resolver.php';
require_once Setup::$dir . '/blueprints/export/class-html-rewriter.php';
require_once Setup::$dir . '/blueprints/export/class-content-collector.php';
require_once Setup::$dir . '/blueprints/export/class-fonts-collector.php';
require_once Setup::$dir . '/blueprints/export/class-options-media-walker.php';
require_once Setup::$dir . '/blueprints/export/class-zip-writer.php';
require_once Setup::$dir . '/blueprints/export/class-exporter.php';
require_once Setup::$dir . '/blueprints/shared/class-id-map.php';
require_once Setup::$dir . '/blueprints/import/class-zip-reader.php';
require_once Setup::$dir . '/blueprints/import/class-validator.php';
require_once Setup::$dir . '/blueprints/import/class-snapshot.php';
require_once Setup::$dir . '/blueprints/import/class-term-importer.php';
require_once Setup::$dir . '/blueprints/import/class-media-importer.php';
require_once Setup::$dir . '/blueprints/import/class-fonts-importer.php';
require_once Setup::$dir . '/blueprints/import/class-element-rewriter.php';
require_once Setup::$dir . '/blueprints/import/class-html-import-rewriter.php';
require_once Setup::$dir . '/blueprints/import/class-options-installer.php';
require_once Setup::$dir . '/blueprints/import/class-content-installer.php';
require_once Setup::$dir . '/blueprints/import/class-menus-installer.php';
require_once Setup::$dir . '/blueprints/import/class-importer.php';
require_once Setup::$dir . '/blueprints/rest/class-rest-controller.php';

class BlueprintsManager {

	const PAGE_SLUG = 'manage-blueprints';

	public static function init() {
		add_action( 'admin_menu',          array( __CLASS__, 'register_menu' ), 12 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'rest_api_init',       array( 'LayBlueprint_RestController', 'register_routes' ) );
	}

	public static function register_menu() {
		add_submenu_page(
			'manage-layoptions',
			lay_t( 'Blueprints', 'lay_blueprints' ),
			lay_t( 'Blueprints', 'lay_blueprints' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		require_once Setup::$dir . '/blueprints/admin/admin_page.php';
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style(
			'lay-blueprintsmanager-styles',
			Setup::$uri . '/blueprints/assets/css/blueprintsmanager.css',
			array(),
			Setup::$ver
		);

		wp_enqueue_script(
			'lay-blueprintsmanager-app',
			Setup::$uri . '/blueprints/assets/js/blueprintsmanager.umd.js',
			array(),
			Setup::$ver,
			true
		);

		wp_localize_script(
			'lay-blueprintsmanager-app',
			'layBlueprintsManagerData',
			self::get_passed_data()
		);
	}

	private static function get_passed_data() {
		return array(
			'restRoot'      => esc_url_raw( rest_url( 'lay-blueprints/v1' ) ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'i18n'          => self::get_i18n_strings(),
			'siteName'      => get_bloginfo( 'name' ),
			'layVersion'    => Setup::$ver,
			'blueprintProviderUrl' => esc_url_raw( apply_filters( 'lay_blueprint_provider_url', 'https://lay-blueprint-provider.fly.dev' ) ),
			'activePlugins' => LayBlueprint_PluginsCollector::active(),
			'imageSets'     => self::get_placeholder_image_sets(),
			'commercialImageSets' => self::get_placeholder_image_sets( true ),
			'solidColors'   => self::get_placeholder_solid_colors(),
			'lastRestorePoint' => LayBlueprint_Importer::client_restore_point(),
		);
	}

	private static function get_placeholder_image_sets( $commercial = false ) {
		$sets = self::fetch_remote_placeholder_image_sets( $commercial );
		if ( empty( $sets ) ) {
			$sets = self::fallback_placeholder_image_sets( $commercial );
		}
		$sets = apply_filters( 'lay_blueprint_placeholder_image_sets', $sets, $commercial );
		$out = self::sanitize_placeholder_image_sets( $sets );
		if ( $commercial ) {
			$out = array_values( array_filter( $out, function ( $set ) {
				return ! empty( $set['allowedForCommercial'] );
			} ) );
			if ( empty( $out ) ) {
				$out = self::sanitize_placeholder_image_sets( self::fallback_placeholder_image_sets( true ) );
			}
		}
		return $out;
	}

	private static function sanitize_placeholder_image_sets( $sets ) {
		$out = array();
		foreach ( (array) $sets as $set ) {
			if ( ! is_array( $set ) || empty( $set['slug'] ) ) { continue; }
			$slug = strtolower( (string) $set['slug'] );
			$slug = preg_replace( '/[^a-z0-9-]+/', '-', $slug );
			$slug = trim( $slug, '-' );
			if ( $slug === '' ) { continue; }
			$preview_url = '';
			if ( isset( $set['previewUrl'] ) && $set['previewUrl'] !== '' ) {
				$preview_url = (string) $set['previewUrl'];
			} elseif ( isset( $set['preview_url'] ) && $set['preview_url'] !== '' ) {
				$preview_url = (string) $set['preview_url'];
			}
			if ( strpos( $preview_url, '/' ) === 0 ) {
				$preview_url = self::placeholder_preview_base_url() . $preview_url;
			}
			$out[] = array(
				'slug' => substr( $slug, 0, 64 ),
				'name' => isset( $set['name'] ) && $set['name'] !== '' ? (string) $set['name'] : $slug,
				'previewUrl' => $preview_url !== '' ? esc_url_raw( $preview_url ) : self::build_placeholder_set_preview_url( $slug ),
				'allowedForCommercial' => ! empty( $set['allowedForCommercial'] ) || ! empty( $set['allowed_for_commercial'] ),
				'hasWatermark' => ! empty( $set['hasWatermark'] ) || ! empty( $set['has_watermark'] ),
				'selectionMode' => isset( $set['selectionMode'] ) && $set['selectionMode'] === 'mapped'
					? 'mapped'
					: ( isset( $set['selection_mode'] ) && $set['selection_mode'] === 'mapped' ? 'mapped' : 'pool' ),
				'imageCount' => isset( $set['imageCount'] )
					? max( 0, (int) $set['imageCount'] )
					: ( isset( $set['image_count'] ) ? max( 0, (int) $set['image_count'] ) : 0 ),
			);
		}
		return ! empty( $out ) ? $out : array( array( 'slug' => 'jose-porroche', 'name' => 'Jose Porroche', 'previewUrl' => self::build_placeholder_set_preview_url( 'jose-porroche' ) ) );
	}

	private static function fallback_placeholder_image_sets( $commercial = false ) {
		$sets = array(
			array(
				'slug' => 'jose-porroche',
				'name' => 'Jose Porroche',
				'preview_url' => self::build_placeholder_set_preview_url( 'jose-porroche' ),
				'allowed_for_commercial' => true,
				'has_watermark' => true,
				'selection_mode' => 'pool',
			),
			array(
				'slug' => 'adam-whyte',
				'name' => 'Adam Whyte',
				'preview_url' => self::build_placeholder_set_preview_url( 'adam-whyte' ),
				'allowed_for_commercial' => false,
				'has_watermark' => true,
				'selection_mode' => 'pool',
			),
		);
		if ( ! $commercial ) {
			return $sets;
		}
		return array_values( array_filter( $sets, function ( $set ) {
			return ! empty( $set['allowed_for_commercial'] );
		} ) );
	}

	private static function fetch_remote_placeholder_image_sets( $commercial = false ) {
		$cache_key = $commercial ? 'lay_blueprint_remote_commercial_image_sets_v1' : 'lay_blueprint_remote_image_sets_v1';
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$base = self::placeholder_preview_base_url();
		if ( $base === '' || ! function_exists( 'wp_remote_get' ) ) {
			return array();
		}
		$response = wp_remote_get(
			$base . '/api/sets?preview_base_path=1' . ( $commercial ? '&commercial=1' : '' ),
			array(
				'timeout' => 2,
				'redirection' => 2,
			)
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return array();
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['sets'] ) || ! is_array( $data['sets'] ) ) {
			return array();
		}
		set_transient( $cache_key, $data['sets'], 5 * MINUTE_IN_SECONDS );
		return $data['sets'];
	}

	private static function get_placeholder_solid_colors() {
		$colors = array(
			array(
				'slug' => 'warm-grey',
				'name' => 'Warm grey',
				'hex'  => '#f3f2ef',
			),
			array(
				'slug' => 'pale-green',
				'name' => 'Pale green',
				'hex'  => '#eef5ec',
			),
			array(
				'slug' => 'pale-blue',
				'name' => 'Pale blue',
				'hex'  => '#eef3f7',
			),
		);
		$colors = apply_filters( 'lay_blueprint_placeholder_solid_colors', $colors );
		$out = array();
		foreach ( (array) $colors as $color ) {
			if ( ! is_array( $color ) || empty( $color['slug'] ) || empty( $color['hex'] ) ) { continue; }
			$slug = strtolower( (string) $color['slug'] );
			$slug = preg_replace( '/[^a-z0-9-]+/', '-', $slug );
			$slug = trim( $slug, '-' );
			$hex = strtolower( trim( (string) $color['hex'] ) );
			if ( $slug === '' || ! preg_match( '/^#[0-9a-f]{6}$/', $hex ) ) { continue; }
			$out[] = array(
				'slug' => substr( $slug, 0, 64 ),
				'name' => isset( $color['name'] ) && $color['name'] !== '' ? (string) $color['name'] : $slug,
				'hex'  => $hex,
			);
		}
		return ! empty( $out ) ? $out : array( array( 'slug' => 'warm-grey', 'name' => 'Warm grey', 'hex' => '#f3f2ef' ) );
	}

	private static function build_placeholder_set_preview_url( $slug ) {
		$base = self::placeholder_preview_base_url();
		if ( $base === '' ) {
			return '';
		}
		return rtrim( $base, '/' ) . '/sets/' . rawurlencode( $slug );
	}

	private static function placeholder_preview_base_url() {
		$base = class_exists( 'LayBlueprint_LayBlueprintPicsProvider' )
			? LayBlueprint_LayBlueprintPicsProvider::default_preview_base_url()
			: apply_filters( 'lay_blueprint_placeholder_preview_base_url', 'https://lay-blueprint-media.fly.dev' );
		if ( ! is_string( $base ) || trim( $base ) === '' ) {
			return '';
		}
		return rtrim( $base, '/' );
	}

	private static function get_i18n_strings() {
		return array(
			'pageTitle'        => lay_t( 'Blueprints', 'lay_blueprints' ),
			'tabExport'        => lay_t( 'Export', 'export' ),
			'tabImport'        => lay_t( 'Import', 'import' ),
			'exportButton'     => lay_t( 'Export Blueprint', 'export_blueprint' ),
			'importDropzone'   => lay_t( 'Drop your Blueprint .zip file here, or click to browse', 'import_dropzone_hint' ),
			'requiredPlugins'  => lay_t( 'Required plugins', 'required_plugins' ),
		);
	}

}

BlueprintsManager::init();
