<?php
/**
 * Lay Blueprints — REST API controller.
 *
 * Phase 2: /export/start, /export/progress, /export/download are real.
 * Import endpoints + rollback remain stubs (Phase 4).
 *
 * All endpoints require manage_options. Cookie auth ⇒ wp_rest nonce check.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_RestController {

	const NAMESPACE = 'lay-blueprints/v1';

	public static function register_routes() {
		$auth = array( __CLASS__, 'check_admin' );

		register_rest_route( self::NAMESPACE, '/export/start',     array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'export_start' ),    'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/export/progress',  array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'export_progress' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/export/download',  array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'export_download' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/image-set/validate', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'image_set_validate' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/import/validate',  array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'import_validate' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/import/apply',     array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'import_apply' ),    'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/import/progress',  array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'import_progress' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/import/rollback',  array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'import_rollback' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/site-title', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_site_title' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/license/check', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_license_check' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/status', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_status' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/complete', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_complete' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/plugins/install', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_plugins_install' ), 'permission_callback' => $auth ) );
		register_rest_route( self::NAMESPACE, '/setup/plugins/upload', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'setup_plugin_upload' ), 'permission_callback' => $auth ) );
	}

	public static function check_admin() {
		return current_user_can( 'manage_options' );
	}

	// ── Export ──

	public static function export_start( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = array(); }
		$required = isset( $body['required_plugins'] ) && is_array( $body['required_plugins'] )
			? array_values( array_filter( $body['required_plugins'], 'is_string' ) )
			: array();
		$strategy = isset( $body['min_version_strategy'] ) ? (string) $body['min_version_strategy'] : 'exact';
		if ( $strategy !== 'minor' ) {
			$strategy = 'exact';
		}
		$source_type = isset( $body['placeholder_source_type'] ) ? (string) $body['placeholder_source_type'] : 'image_set';
		if ( $source_type !== 'solid_color' ) {
			$source_type = 'image_set';
		}
		$image_set = isset( $body['placeholder_image_set'] ) ? self::sanitize_slug_param( $body['placeholder_image_set'], 'jose-porroche' ) : 'jose-porroche';
		$solid_color = isset( $body['placeholder_color'] ) ? self::sanitize_slug_param( $body['placeholder_color'], 'warm-grey' ) : 'warm-grey';
		$selection_mode = isset( $body['placeholder_selection_mode'] ) && (string) $body['placeholder_selection_mode'] === 'mapped' ? 'mapped' : 'pool';
		$available_image_count = 0;
		$commercial_intent = ! empty( $body['commercial_intent'] );
		if ( $source_type === 'image_set' && ( $commercial_intent || $selection_mode === 'mapped' ) ) {
			$set_validation = self::validate_placeholder_image_set( $image_set, $commercial_intent );
			if ( is_wp_error( $set_validation ) ) {
				return $set_validation;
			}
			if ( empty( $set_validation['valid'] ) ) {
				$message = $commercial_intent
					? 'This photo set is not available for commercial Blueprint exports.'
					: 'This photo set is not available for Blueprint exports.';
				return new WP_Error( 'image_set_not_allowed', $message, array( 'status' => 400 ) );
			}
			$selection_mode = ! empty( $set_validation['selectionMode'] ) && $set_validation['selectionMode'] === 'mapped' ? 'mapped' : 'pool';
			$available_image_count = isset( $set_validation['imageCount'] ) ? (int) $set_validation['imageCount'] : 0;
		}

		$state = LayBlueprint_Exporter::start( $required, $strategy, $source_type, $image_set, $solid_color, $selection_mode, $available_image_count );
		return new WP_REST_Response( self::client_state( $state ), 200 );
	}

	public static function image_set_validate( WP_REST_Request $request ) {
		$slug = self::sanitize_slug_param( (string) $request->get_param( 'slug' ), '' );
		if ( $slug === '' ) {
			return new WP_REST_Response( array(
				'slug' => '',
				'valid' => false,
				'message' => 'Enter a photo set code.',
			), 200 );
		}
		$commercial = self::truthy_param( $request->get_param( 'commercial' ) );
		$result = self::validate_placeholder_image_set( $slug, $commercial );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( $result, 200 );
	}

	public static function export_progress( WP_REST_Request $request ) {
		$job_id = (string) $request->get_param( 'job_id' );
		$state  = LayBlueprint_JobState::get( $job_id );
		if ( ! $state ) {
			return new WP_Error( 'job_not_found', 'Unknown job id.', array( 'status' => 404 ) );
		}
		return new WP_REST_Response( self::client_state( $state ), 200 );
	}

	public static function export_download( WP_REST_Request $request ) {
		$job_id = (string) $request->get_param( 'job_id' );
		$state  = LayBlueprint_JobState::get( $job_id );
		if ( ! $state ) {
			return new WP_Error( 'job_not_found', 'Unknown job id.', array( 'status' => 404 ) );
		}
		if ( $state['status'] !== 'complete' ) {
			return new WP_Error( 'job_not_ready', 'Export is not complete yet.', array( 'status' => 409 ) );
		}
		$file = $state['file_path'];
		if ( ! is_string( $file ) || ! is_readable( $file ) ) {
			return new WP_Error( 'file_missing', 'Export file is no longer available; please re-run.', array( 'status' => 410 ) );
		}

		// Stream the binary directly. WP_REST_Response can't return raw bytes
		// with a download disposition cleanly, so we bypass the response stack.
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . self::sanitize_disposition_filename( $state['file_name'] ?: basename( $file ) ) . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file );
		exit;
	}

	// ── Import ──

	public static function import_validate( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file']['tmp_name'] ) ) {
			return new WP_Error( 'no_file', 'Upload a Blueprint .zip file in the "file" field.', array( 'status' => 400 ) );
		}
		if ( ! is_uploaded_file( $files['file']['tmp_name'] ) && ! is_readable( $files['file']['tmp_name'] ) ) {
			return new WP_Error( 'upload_unreadable', 'Uploaded file is not readable.', array( 'status' => 400 ) );
		}

		// Stage to a stable path under uploads/lay-blueprints-tmp/ so apply
		// can re-open it in a separate request.
		$state = LayBlueprint_JobState::create( 'import' );
		$staged = self::stage_uploaded_file( $files['file']['tmp_name'], $state['job_id'] );
		if ( is_wp_error( $staged ) ) {
			LayBlueprint_JobState::fail( $state['job_id'], $staged->get_error_message() );
			return $staged;
		}

		$reader = LayBlueprint_ZipReader::open( $staged );
		if ( is_wp_error( $reader ) ) {
			LayBlueprint_JobState::fail( $state['job_id'], $reader->get_error_message() );
			return $reader;
		}
		$manifest = $reader->manifest();
		$reader->close();
		if ( is_wp_error( $manifest ) ) {
			LayBlueprint_JobState::fail( $state['job_id'], $manifest->get_error_message() );
			return $manifest;
		}

		$report = LayBlueprint_Validator::validate( $manifest );

		LayBlueprint_JobState::update( $state['job_id'], array(
			'status'    => 'pending',
			'step'      => $report['passed'] ? 'Ready to apply' : 'Compatibility check failed',
			'progress'  => 0,
			'file_path' => $staged,
			'file_name' => isset( $files['file']['name'] ) ? sanitize_file_name( $files['file']['name'] ) : null,
			'meta'      => array(
				'manifest_summary' => array(
					'site'       => isset( $manifest['site'] ) ? $manifest['site'] : array(),
					'compat'     => isset( $manifest['compat'] ) ? $manifest['compat'] : array(),
					'counts'     => isset( $manifest['counts'] ) ? $manifest['counts'] : array(),
					'exported_at'=> isset( $manifest['exported_at'] ) ? $manifest['exported_at'] : null,
				),
				'validation_report' => $report,
			),
		) );

		return new WP_REST_Response( self::client_state( LayBlueprint_JobState::get( $state['job_id'] ) ), 200 );
	}

	public static function import_apply( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$job_id = isset( $body['job_id'] ) ? (string) $body['job_id'] : '';
		$state  = LayBlueprint_JobState::get( $job_id );
		if ( ! $state ) {
			return new WP_Error( 'job_not_found', 'Unknown import job. Re-upload the file.', array( 'status' => 404 ) );
		}
		if ( empty( $state['meta']['validation_report']['passed'] ) ) {
			return new WP_Error( 'not_passed', 'Validation has not passed. Resolve compat issues first.', array( 'status' => 409 ) );
		}
		$result = LayBlueprint_Importer::apply( $job_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( self::client_state( $result ), 200 );
	}

	public static function import_progress( WP_REST_Request $request ) {
		$job_id = (string) $request->get_param( 'job_id' );
		$state  = LayBlueprint_JobState::get( $job_id );
		if ( ! $state ) {
			return new WP_Error( 'job_not_found', 'Unknown job id.', array( 'status' => 404 ) );
		}
		return new WP_REST_Response( self::client_state( $state ), 200 );
	}

	public static function import_rollback( WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$job_id = isset( $body['job_id'] ) ? (string) $body['job_id'] : '';
		$result = LayBlueprint_Importer::rollback( $job_id );
		if ( is_wp_error( $result ) ) { return $result; }
		return new WP_REST_Response( $result, 200 );
	}

	// ── Setup helpers ──

	public static function setup_site_title( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = array(); }
		$site_title = isset( $body['site_title'] ) ? trim( sanitize_text_field( (string) $body['site_title'] ) ) : '';
		if ( $site_title === '' ) {
			return new WP_Error( 'missing_site_title', 'Enter a site title.', array( 'status' => 400 ) );
		}
		update_option( 'blogname', $site_title );
		return new WP_REST_Response( array(
			'siteName' => get_bloginfo( 'name' ),
		), 200 );
	}

	public static function setup_license_check( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = array(); }
		$license_key = isset( $body['license_key'] ) ? trim( sanitize_text_field( (string) $body['license_key'] ) ) : '';
		if ( $license_key === '' ) {
			$license_key = trim( (string) get_option( 'lay_license_key', '' ) );
		}
		if ( $license_key === '' ) {
			return new WP_Error( 'missing_license_key', 'Enter your Lay Theme license key.', array( 'status' => 400 ) );
		}
		$result = self::check_lay_license_key( $license_key, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( self::client_license_state( $result ), 200 );
	}

	public static function setup_complete() {
		if ( class_exists( 'LaySetupWizard' ) ) {
			LaySetupWizard::mark_completed();
		} else {
			update_option( 'lay_setup_wizard_status', 'completed', false );
			delete_option( 'lay_setup_wizard_completed' );
			delete_option( 'lay_setup_wizard_redirect_pending' );
			delete_option( 'lay_setup_wizard_continue_dismissed' );
		}
		return new WP_REST_Response( array( 'completed' => true ), 200 );
	}

	public static function setup_status( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = array(); }
		$status = isset( $body['status'] ) ? sanitize_key( (string) $body['status'] ) : '';
		$allowed = array( 'pending', 'skipped', 'completed' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return new WP_Error( 'invalid_setup_status', 'Invalid setup status.', array( 'status' => 400 ) );
		}
		if ( class_exists( 'LaySetupWizard' ) ) {
			$status = LaySetupWizard::set_status( $status );
		} else {
			update_option( 'lay_setup_wizard_status', $status, false );
			delete_option( 'lay_setup_wizard_completed' );
			if ( $status !== 'pending' ) {
				delete_option( 'lay_setup_wizard_redirect_pending' );
			}
			if ( $status === 'pending' || $status === 'skipped' || $status === 'completed' ) {
				delete_option( 'lay_setup_wizard_continue_dismissed' );
			}
		}
		return new WP_REST_Response( array( 'status' => $status ), 200 );
	}

	public static function setup_plugins_install( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = array(); }
		$license_key = isset( $body['license_key'] ) ? trim( sanitize_text_field( (string) $body['license_key'] ) ) : '';
		if ( $license_key === '' ) {
			$license_key = trim( (string) get_option( 'lay_license_key', '' ) );
		}

		$requested = isset( $body['plugins'] ) && is_array( $body['plugins'] ) ? $body['plugins'] : array();
		$slugs = array();
		$requested_by_slug = array();
		foreach ( $requested as $item ) {
			$slug = is_array( $item ) && isset( $item['slug'] ) ? (string) $item['slug'] : (string) $item;
			$slug = self::sanitize_slug_param( $slug, '' );
			if ( $slug !== '' ) {
				$slugs[] = $slug;
				$requested_by_slug[ $slug ] = is_array( $item ) ? $item : array( 'slug' => $slug );
			}
		}
		$slugs = array_values( array_unique( $slugs ) );
		if ( empty( $slugs ) ) {
			return new WP_REST_Response( array( 'installed' => array(), 'message' => 'No plugins need to be installed.' ), 200 );
		}

		$known = self::known_lay_plugins();
		$needs_license = false;
		foreach ( $slugs as $slug ) {
			if ( ! isset( $known[ $slug ] ) ) {
				return new WP_Error( 'plugin_not_supported', 'This Blueprint requires a plugin that cannot be installed automatically: ' . $slug, array( 'status' => 400 ) );
			}
			$status = isset( $requested_by_slug[ $slug ]['status'] ) ? (string) $requested_by_slug[ $slug ]['status'] : '';
			if ( $status !== 'inactive' && ( ! isset( $known[ $slug ]['license_required'] ) || $known[ $slug ]['license_required'] !== false ) ) {
				$needs_license = true;
			}
		}

		$license = array(
			'valid' => false,
			'products' => array(),
			'_product_keys' => array(),
		);
		if ( $needs_license ) {
			if ( $license_key === '' ) {
				return new WP_Error( 'missing_license_key', 'Enter your Lay Theme license key before installing required Lay plugins.', array( 'status' => 400 ) );
			}
			$license = self::check_lay_license_key( $license_key, true );
			if ( is_wp_error( $license ) ) {
				return $license;
			}
		}

		$allowed_products = isset( $license['products'] ) && is_array( $license['products'] ) ? $license['products'] : array();
		$product_keys = isset( $license['_product_keys'] ) && is_array( $license['_product_keys'] ) ? $license['_product_keys'] : array();
		$has_all = in_array( 'all', $allowed_products, true );
		$results = array();

		foreach ( $slugs as $slug ) {
			$license_required = ! isset( $known[ $slug ]['license_required'] ) || $known[ $slug ]['license_required'] !== false;
			if ( $license_required && ! $has_all && ! in_array( $slug, $allowed_products, true ) ) {
				return new WP_Error( 'plugin_not_in_license', 'Your license key does not include ' . $known[ $slug ]['name'] . '.', array( 'status' => 403 ) );
			}
			$force_update = isset( $requested_by_slug[ $slug ]['status'] ) && (string) $requested_by_slug[ $slug ]['status'] === 'outdated';
			$plugin_license_key = isset( $product_keys[ $slug ] ) ? (string) $product_keys[ $slug ] : $license_key;
			$result = self::install_or_activate_lay_plugin( $slug, $known[ $slug ], $plugin_license_key, $force_update );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$results[] = $result;
		}

		return new WP_REST_Response( array(
			'installed' => $results,
			'license'   => self::client_license_state( $license ),
		), 200 );
	}

	public static function setup_plugin_upload( WP_REST_Request $request ) {
		$slug = self::sanitize_slug_param( (string) $request->get_param( 'slug' ), '' );
		if ( $slug === '' ) {
			return new WP_Error( 'missing_plugin_slug', 'Choose the plugin this ZIP should install.', array( 'status' => 400 ) );
		}
		$known = self::known_lay_plugins();
		if ( ! isset( $known[ $slug ] ) ) {
			return new WP_Error( 'plugin_not_supported', 'This plugin cannot be installed through the setup wizard.', array( 'status' => 400 ) );
		}
		if ( isset( $known[ $slug ]['license_required'] ) && $known[ $slug ]['license_required'] === false ) {
			return new WP_Error( 'plugin_upload_not_needed', $known[ $slug ]['name'] . ' can be installed automatically.', array( 'status' => 400 ) );
		}

		$files = $request->get_file_params();
		if ( empty( $files['file']['tmp_name'] ) ) {
			return new WP_Error( 'no_file', 'Upload the plugin ZIP file in the "file" field.', array( 'status' => 400 ) );
		}
		$tmp = $files['file']['tmp_name'];
		if ( ! is_uploaded_file( $tmp ) && ! is_readable( $tmp ) ) {
			return new WP_Error( 'upload_unreadable', 'Uploaded plugin ZIP is not readable.', array( 'status' => 400 ) );
		}
		$name = isset( $files['file']['name'] ) ? sanitize_file_name( $files['file']['name'] ) : '';
		if ( $name !== '' && strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) !== 'zip' ) {
			return new WP_Error( 'plugin_zip_required', 'Upload the plugin as a ZIP file.', array( 'status' => 400 ) );
		}

		$contains_expected_plugin = self::uploaded_package_contains_expected_plugin( $tmp, $known[ $slug ] );
		if ( is_wp_error( $contains_expected_plugin ) ) {
			return $contains_expected_plugin;
		}

		$result = self::install_uploaded_lay_plugin( $slug, $known[ $slug ], $tmp );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( array(
			'installed' => $result,
		), 200 );
	}

	private static function stage_uploaded_file( $tmp, $job_id ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'uploads_error', $uploads['error'] );
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'lay-blueprints-tmp';
		if ( ! file_exists( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'stage_mkdir', 'Could not create staging dir.' );
		}
		$dest = trailingslashit( $dir ) . $job_id . '.' . LayBlueprint_Schema::FILE_EXTENSION;
		// Try move first (uploaded file) then copy fallback.
		if ( ! @move_uploaded_file( $tmp, $dest ) ) {
			if ( ! @copy( $tmp, $dest ) ) {
				return new WP_Error( 'stage_failed', 'Could not stage uploaded file.' );
			}
		}
		return $dest;
	}

	// ── Helpers ──

	/**
	 * Strip server-only fields before sending state to the browser.
	 * file_path is a server path, not useful (or safe) for the client.
	 */
	private static function client_state( array $state ) {
		unset( $state['file_path'] );
		// Provide a download URL the React UI can navigate to.
		if ( $state['status'] === 'complete' ) {
			$state['download_url'] = self::build_download_url( $state['job_id'] );
		}
		return $state;
	}

	private static function build_download_url( $job_id ) {
		$nonce = wp_create_nonce( 'wp_rest' );
		return add_query_arg(
			array( 'job_id' => $job_id, '_wpnonce' => $nonce ),
			rest_url( self::NAMESPACE . '/export/download' )
		);
	}

	private static function sanitize_disposition_filename( $name ) {
		$name = (string) $name;
		// Keep it ASCII-safe to avoid Content-Disposition encoding traps.
		$name = preg_replace( '/[^\w.\-]+/', '_', $name );
		if ( $name === '' ) {
			$name = 'blueprint.layblueprint.zip';
		}
		return $name;
	}

	private static function sanitize_slug_param( $value, $fallback ) {
		$slug = strtolower( (string) $value );
		$slug = preg_replace( '/[^a-z0-9-]+/', '-', $slug );
		$slug = trim( $slug, '-' );
		return $slug !== '' ? substr( $slug, 0, 64 ) : $fallback;
	}

	private static function truthy_param( $value ) {
		return $value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'yes';
	}

	private static function known_lay_plugins() {
		return apply_filters( 'lay_setup_wizard_known_plugins', array(
			'laytheme-carousel' => array(
				'name' => 'Lay Theme Carousel',
				'file' => 'laytheme-carousel/laytheme-carousel.php',
				'endpoint' => 'https://kernl.us/api/v1/updates/56b09a95ffb0be4f0769e299/',
				'product_refs' => array( 'carousel', 'single_with_carousel', 'single with carousel', 'laytheme carousel', 'lay theme carousel' ),
			),
			'laytheme-lightbox' => array(
				'name' => 'Lay Theme Lightbox',
				'file' => 'laytheme-lightbox/laytheme-lightbox.php',
				'endpoint' => 'https://kernl.us/api/v1/updates/56b3219b681396fc05d44cf8/',
				'product_refs' => array( 'lightbox', 'single_with_lightbox', 'single with lightbox', 'laytheme lightbox', 'lay theme lightbox' ),
			),
			'laytheme-imagehover' => array(
				'name' => 'Lay Theme Imagehover',
				'file' => 'laytheme-imagehover/laytheme-imagehover.php',
				'endpoint' => 'https://kernl.us/api/v1/updates/5ea4015ce8bd8b2fc0e51870/',
				'product_refs' => array( 'imagehover', 'image hover', 'single_with_imagehover', 'single_with_image_hover', 'single with imagehover', 'single with image hover', 'laytheme imagehover', 'lay theme imagehover' ),
			),
			'laytheme-magneticslides' => array(
				'name' => 'Lay Theme Fullscreen Slider',
				'file' => 'laytheme-magneticslides/laytheme-magneticslides.php',
				'endpoint' => 'https://kernl.us/api/v1/updates/56b32245681396fc05d44cf9/',
				'product_refs' => array( 'fullscreen slider', 'fullscreen-slider', 'fullscreen_slider', 'single_with_fullscreen_slider', 'single with fullscreen slider', 'magneticslides', 'magnetic slides', 'laytheme fullscreen slider', 'lay theme fullscreen slider', 'laytheme magneticslides', 'lay theme magnetic slides' ),
			),
			'laytheme-scrollslider' => array(
				'name' => 'Lay Theme Scroll Slider',
				'file' => 'laytheme-scrollslider/laytheme-scrollslider.php',
				'endpoint' => 'https://kernl.us/api/v1/updates/67e41116a9862dea66591090/',
				'product_refs' => array( 'scrollslider', 'scroll slider', 'scroll-slider', 'scroll_slider', 'single_with_scroll_slider', 'single with scroll slider', 'laytheme scrollslider', 'lay theme scroll slider' ),
			),
			LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG => array(
				'name' => 'qTranslate-XT',
				'file' => 'qtranslate-xt/qtranslate.php',
				'file_candidates' => array(
					'qtranslate-xt/qtranslate.php',
					'qtranslate-xt-master/qtranslate.php',
				),
				'package' => 'https://github.com/qtranslate/qtranslate-xt/archive/refs/heads/master.zip',
				'license_required' => false,
				'product_refs' => array(),
			),
		) );
	}

	private static function check_lay_license_key( $license_key, $activate ) {
		$license_key = trim( (string) $license_key );
		if ( $license_key === '' ) {
			return new WP_Error( 'missing_license_key', 'Enter your Lay Theme license key.', array( 'status' => 400 ) );
		}
		$server = defined( 'LAY_THEME_LICENSE_SERVER_URL' ) ? LAY_THEME_LICENSE_SERVER_URL : 'http://45.55.133.112/';
		$secret = defined( 'LAY_THEME_SECRET_KEY' ) ? LAY_THEME_SECRET_KEY : '578cf5d3f16170.28673753';
		$check = self::license_server_request( $server, array(
			'slm_action' => 'slm_check',
			'secret_key' => $secret,
			'license_key' => $license_key,
		) );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
		if ( ! isset( $check['result'] ) || $check['result'] !== 'success' ) {
			return new WP_Error( 'license_invalid', isset( $check['message'] ) ? (string) $check['message'] : 'This license key is not valid.', array( 'status' => 403 ) );
		}
		if ( isset( $check['status'] ) && (string) $check['status'] === 'blocked' ) {
			return new WP_Error( 'license_blocked', 'This license key is blocked.', array( 'status' => 403 ) );
		}

		$activation_message = '';
		if ( $activate ) {
			$activation = self::license_server_request( $server, array(
				'slm_action' => 'slm_activate',
				'secret_key' => $secret,
				'license_key' => $license_key,
				'registered_domain' => self::normalized_license_domain(),
				'item_reference' => urlencode( defined( 'LAY_THEME_ITEM_REFERENCE' ) ? LAY_THEME_ITEM_REFERENCE : 'Lay Theme' ),
			) );
			if ( is_wp_error( $activation ) ) {
				return $activation;
			}
			if ( ! isset( $activation['result'] ) || $activation['result'] !== 'success' ) {
				return new WP_Error( 'license_activation_failed', isset( $activation['message'] ) ? (string) $activation['message'] : 'This license key could not be activated.', array( 'status' => 403 ) );
			}
			$activation_message = isset( $activation['message'] ) ? (string) $activation['message'] : '';
			$fresh_check = self::license_server_request( $server, array(
				'slm_action' => 'slm_check',
				'secret_key' => $secret,
				'license_key' => $license_key,
			) );
			if ( ! is_wp_error( $fresh_check ) && isset( $fresh_check['result'] ) && $fresh_check['result'] === 'success' ) {
				$check = $fresh_check;
			}
			update_option( 'lay_license_key', $license_key );
			update_option( 'lay_license_domain_hash', hash( 'sha256', self::normalized_license_domain() ) );
			delete_option( 'lay_license_invalid' );
			delete_option( 'lay_license_deactivation_reason' );
			delete_option( 'lay_license_last_invalid_email' );
			delete_option( 'lay_license_last_invalid_key' );
			delete_option( 'lay_license_last_tested_domain' );
			delete_option( 'lay_license_deactivation_email_sent' );
		}

		$license_records = array( $check );
		if ( ! empty( $check['email'] ) ) {
			$email_check = self::license_server_request( $server, array(
				'slm_action' => 'slm_check_email',
				'secret_key' => $secret,
				'email' => sanitize_email( (string) $check['email'] ),
			) );
			if ( ! is_wp_error( $email_check ) && isset( $email_check['keys'] ) && is_array( $email_check['keys'] ) ) {
				foreach ( $email_check['keys'] as $key_record ) {
					if ( is_array( $key_record ) ) {
						$license_records[] = $key_record;
					}
				}
			}
		}

		$product_ref = isset( $check['product_ref'] ) ? (string) $check['product_ref'] : '';
		$product_refs = self::license_product_refs( $license_records );
		$products = self::products_from_license_records( $license_records );
		$product_keys = self::product_keys_from_license_records( $license_records );
		$registered_domains = isset( $check['registered_domains'] ) && is_array( $check['registered_domains'] ) ? $check['registered_domains'] : array();
		return array(
			'valid' => true,
			'products' => $products,
			'product_ref' => $product_ref,
			'product_refs' => $product_refs,
			'status' => isset( $check['status'] ) ? (string) $check['status'] : '',
			'email' => isset( $check['email'] ) ? sanitize_email( (string) $check['email'] ) : '',
			'activation_message' => $activation_message,
			'activation_domain' => self::normalized_license_domain(),
			'activation_is_free_domain' => self::is_free_license_domain( self::normalized_license_domain() ),
			'max_allowed_domains' => isset( $check['max_allowed_domains'] ) ? (int) $check['max_allowed_domains'] : null,
			'registered_domains_count' => count( $registered_domains ),
			'_product_keys' => $product_keys,
		);
	}

	private static function license_server_request( $server, array $args ) {
		$response = wp_remote_get( add_query_arg( $args, trailingslashit( $server ) ), array(
			'timeout' => 120,
			'redirection' => 2,
			'sslverify' => false,
		) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'license_server_unreachable', $response->get_error_message(), array( 'status' => 503 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			return new WP_Error( 'license_server_error', 'The license server could not be reached right now.', array( 'status' => 503 ) );
		}
		return $data;
	}

	private static function client_license_state( array $license ) {
		unset( $license['_product_keys'] );
		return $license;
	}

	private static function products_from_license_records( array $records ) {
		$products = array();
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) { continue; }
			$product_ref = isset( $record['product_ref'] ) ? (string) $record['product_ref'] : '';
			$products = array_merge( $products, self::products_from_license_ref( $product_ref, $record ) );
		}
		return array_values( array_unique( $products ) );
	}

	private static function product_keys_from_license_records( array $records ) {
		$product_keys = array();
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) || empty( $record['license_key'] ) ) { continue; }
			$product_ref = isset( $record['product_ref'] ) ? (string) $record['product_ref'] : '';
			$products = self::products_from_license_ref( $product_ref, $record );
			foreach ( $products as $slug ) {
				if ( $slug === 'all' || isset( $product_keys[ $slug ] ) ) { continue; }
				$product_keys[ $slug ] = (string) $record['license_key'];
			}
		}
		return $product_keys;
	}

	private static function license_product_refs( array $records ) {
		$refs = array();
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) || ! isset( $record['product_ref'] ) ) { continue; }
			$ref = trim( (string) $record['product_ref'] );
			if ( $ref !== '' ) {
				$refs[] = $ref;
			}
		}
		return array_values( array_unique( $refs ) );
	}

	private static function products_from_license_ref( $product_ref, array $license_data ) {
		$normalized = self::normalize_product_ref( $product_ref );
		$products = array();
		if ( preg_match( '/\b(pro|studio|all|bundle|bundles|addons|add ons|everything|complete|club)\b/i', (string) $product_ref ) ) {
			$products[] = 'all';
		}
		foreach ( self::known_lay_plugins() as $slug => $plugin ) {
			foreach ( $plugin['product_refs'] as $needle ) {
				if ( $needle !== '' && strpos( $normalized, self::normalize_product_ref( $needle ) ) !== false ) {
					$products[] = $slug;
					break;
				}
			}
		}
		return array_values( array_unique( apply_filters( 'lay_setup_wizard_license_products', $products, $license_data, $product_ref ) ) );
	}

	private static function normalize_product_ref( $value ) {
		$value = strtolower( (string) $value );
		$value = str_replace( array( '&', '+', '_' ), ' ', $value );
		$value = preg_replace( '/[^a-z0-9]+/', ' ', $value );
		return trim( preg_replace( '/\s+/', ' ', $value ) );
	}

	private static function normalized_license_domain() {
		$host = isset( $_SERVER['SERVER_NAME'] ) ? (string) wp_unslash( $_SERVER['SERVER_NAME'] ) : '';
		if ( $host === '' ) {
			$host = parse_url( home_url( '/' ), PHP_URL_HOST );
		}
		if ( ! is_string( $host ) || $host === '' ) {
			$host = (string) home_url( '/' );
		}
		$host = strtolower( trim( preg_replace( '#^https?://#i', '', $host ) ) );
		$host = rtrim( $host, '/' );
		$host = preg_replace( '#:\d+$#', '', $host );
		$host = preg_replace( '/^www\./', '', $host );
		return $host;
	}

	private static function is_free_license_domain( $domain ) {
		$domain = strtolower( trim( (string) $domain ) );
		$domain = preg_replace( '#^https?://#i', '', $domain );
		$domain = rtrim( $domain, '/' );
		$domain = preg_replace( '#:\d+$#', '', $domain );
		return $domain === 'localhost' || strpos( $domain, 'localhost' ) === 0 || substr( $domain, -6 ) === '.local';
	}

	private static function install_or_activate_lay_plugin( $slug, array $plugin, $license_key, $force_update = false ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		$file = self::resolve_installed_plugin_file( $slug, $plugin, $all_plugins );
		$installed = $file !== '';
		if ( $installed ) {
			if ( $force_update ) {
				$updated = self::upgrade_lay_plugin( $slug, $plugin, $license_key );
				if ( is_wp_error( $updated ) ) {
					return $updated;
				}
				if ( ! is_plugin_active( $file ) ) {
					$activated = activate_plugin( $file, '', false, true );
					if ( is_wp_error( $activated ) ) {
						return $activated;
					}
				}
				return array( 'slug' => $slug, 'name' => $plugin['name'], 'status' => 'updated_activated' );
			}
			if ( ! is_plugin_active( $file ) ) {
				$activated = activate_plugin( $file, '', false, true );
				if ( is_wp_error( $activated ) ) {
					return $activated;
				}
				return array( 'slug' => $slug, 'name' => $plugin['name'], 'status' => 'activated' );
			}
			return array( 'slug' => $slug, 'name' => $plugin['name'], 'status' => 'already_active' );
		}

		$package = self::fetch_plugin_package( $plugin, $license_key );
		if ( is_wp_error( $package ) ) {
			return $package;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		$skin = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result = $upgrader->install( $package );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! $result ) {
			return new WP_Error( 'plugin_install_failed', 'Could not install ' . $plugin['name'] . '.', array( 'status' => 500 ) );
		}
		$all_plugins = get_plugins();
		$file = self::resolve_installed_plugin_file( $slug, $plugin, $all_plugins );
		if ( $file === '' || ! file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
			return new WP_Error( 'plugin_file_missing', $plugin['name'] . ' was installed, but its plugin file could not be found.', array( 'status' => 500 ) );
		}
		$activated = activate_plugin( $file, '', false, true );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}
		return array( 'slug' => $slug, 'name' => $plugin['name'], 'status' => 'installed_activated' );
	}

	private static function upgrade_lay_plugin( $slug, array $plugin, $license_key ) {
		$package = self::fetch_plugin_package( $plugin, $license_key );
		if ( is_wp_error( $package ) ) {
			return $package;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		WP_Filesystem();
		$all_plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$file = self::resolve_installed_plugin_file( $slug, $plugin, $all_plugins );
		if ( $file === '' ) {
			return new WP_Error( 'plugin_file_missing', $plugin['name'] . ' could not be found for updating.', array( 'status' => 500 ) );
		}
		$transient = get_site_transient( 'update_plugins' );
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}
		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}
		$transient->response[ $file ] = (object) array(
			'id' => $slug,
			'slug' => $slug,
			'plugin' => $file,
			'new_version' => '999.0.0',
			'package' => $package,
			'url' => 'https://laytheme.com/addons/',
		);
		set_site_transient( 'update_plugins', $transient );
		$skin = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result = $upgrader->upgrade( $file );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! $result ) {
			return new WP_Error( 'plugin_update_failed', 'Could not update ' . $plugin['name'] . '.', array( 'status' => 500 ) );
		}
		return $result;
	}

	private static function install_uploaded_lay_plugin( $slug, array $plugin, $package ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		WP_Filesystem();

		$skin = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result = $upgrader->install( $package, array( 'overwrite_package' => true ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! $result ) {
			return new WP_Error( 'plugin_install_failed', 'Could not install ' . $plugin['name'] . '.', array( 'status' => 500 ) );
		}

		wp_clean_plugins_cache( true );
		$all_plugins = get_plugins();
		$file = self::resolve_installed_plugin_file( $slug, $plugin, $all_plugins );
		if ( $file === '' || ! file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
			return new WP_Error( 'plugin_file_missing', $plugin['name'] . ' was installed, but its plugin file could not be found.', array( 'status' => 500 ) );
		}
		$activated = activate_plugin( $file, '', false, true );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}
		return array( 'slug' => $slug, 'name' => $plugin['name'], 'status' => 'uploaded_activated' );
	}

	private static function uploaded_package_contains_expected_plugin( $package, array $plugin ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return true;
		}
		$zip = new ZipArchive();
		if ( $zip->open( $package ) !== true ) {
			return new WP_Error( 'plugin_zip_invalid', 'This plugin ZIP could not be opened.', array( 'status' => 400 ) );
		}
		$candidates = array();
		if ( ! empty( $plugin['file'] ) ) {
			$candidates[] = (string) $plugin['file'];
		}
		if ( ! empty( $plugin['file_candidates'] ) && is_array( $plugin['file_candidates'] ) ) {
			foreach ( $plugin['file_candidates'] as $candidate ) {
				$candidates[] = (string) $candidate;
			}
		}
		$candidates = array_values( array_unique( array_filter( array_map( 'strval', $candidates ) ) ) );
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );
			$name = ltrim( str_replace( '\\', '/', $name ), '/' );
			foreach ( $candidates as $candidate ) {
				$candidate = ltrim( str_replace( '\\', '/', $candidate ), '/' );
				if ( $name === $candidate || substr( $name, -1 * ( strlen( $candidate ) + 1 ) ) === '/' . $candidate ) {
					$zip->close();
					return true;
				}
				if ( basename( $name ) === basename( $candidate ) && strpos( dirname( $name ), dirname( $candidate ) ) !== false ) {
					$zip->close();
					return true;
				}
			}
		}
		$zip->close();
		return new WP_Error( 'plugin_zip_mismatch', 'This ZIP does not contain ' . $plugin['name'] . '.', array( 'status' => 400 ) );
	}

	private static function fetch_plugin_package( array $plugin, $license_key ) {
		if ( ! empty( $plugin['package'] ) && is_string( $plugin['package'] ) && preg_match( '#^https?://#i', $plugin['package'] ) ) {
			return (string) $plugin['package'];
		}
		$endpoint = isset( $plugin['endpoint'] ) ? (string) $plugin['endpoint'] : '';
		if ( $endpoint === '' ) {
			return new WP_Error( 'plugin_package_missing', 'No download source exists for ' . $plugin['name'] . '.', array( 'status' => 500 ) );
		}
		$response = wp_remote_get( add_query_arg( array(
			'installed_version' => '',
			'domain' => self::normalized_license_domain(),
			'license' => $license_key,
			'checking_for_updates' => '1',
		), $endpoint ), array(
			'timeout' => 20,
			'redirection' => 2,
		) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'plugin_package_unreachable', $response->get_error_message(), array( 'status' => 503 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			return new WP_Error( 'plugin_package_unavailable', 'Could not retrieve the installer for ' . $plugin['name'] . '.', array( 'status' => 503 ) );
		}
		foreach ( array( 'download_url', 'download_link', 'package', 'url' ) as $key ) {
			if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) && preg_match( '#^https?://#i', $data[ $key ] ) ) {
				return $data[ $key ];
			}
		}
		return new WP_Error( 'plugin_package_unavailable', 'Could not retrieve the installer for ' . $plugin['name'] . '.', array( 'status' => 503 ) );
	}

	private static function resolve_installed_plugin_file( $slug, array $plugin, array $all_plugins ) {
		$candidates = array();
		if ( ! empty( $plugin['file'] ) ) {
			$candidates[] = (string) $plugin['file'];
		}
		if ( ! empty( $plugin['file_candidates'] ) && is_array( $plugin['file_candidates'] ) ) {
			foreach ( $plugin['file_candidates'] as $candidate ) {
				$candidates[] = (string) $candidate;
			}
		}
		foreach ( array_unique( $candidates ) as $candidate ) {
			if ( isset( $all_plugins[ $candidate ] ) ) {
				return $candidate;
			}
		}
		if ( $slug === LayBlueprint_PluginsCollector::QTRANSLATE_XT_SLUG ) {
			foreach ( $all_plugins as $file => $data ) {
				$name = isset( $data['Name'] ) ? (string) $data['Name'] : '';
				if ( basename( $file ) === 'qtranslate.php' && ( stripos( $name, 'qTranslate-XT' ) !== false || stripos( $name, 'qTranslate XT' ) !== false ) ) {
					return $file;
				}
			}
		}
		return '';
	}

	private static function validate_placeholder_image_set( $slug, $commercial ) {
		$slug = self::sanitize_slug_param( $slug, '' );
		if ( $slug === '' ) {
			return array( 'slug' => '', 'valid' => false );
		}
		$base = class_exists( 'LayBlueprint_LayBlueprintPicsProvider' )
			? LayBlueprint_LayBlueprintPicsProvider::default_preview_base_url()
			: apply_filters( 'lay_blueprint_placeholder_preview_base_url', 'https://lay-blueprint-media.fly.dev' );
		if ( ! is_string( $base ) || trim( $base ) === '' || ! function_exists( 'wp_remote_get' ) ) {
			return new WP_Error( 'image_set_validation_unavailable', 'Could not check this photo set right now.', array( 'status' => 503 ) );
		}
		$url = rtrim( $base, '/' ) . '/api/sets/validate?' . http_build_query( array(
			'slug' => $slug,
			'commercial' => $commercial ? '1' : '0',
		), '', '&' );
		$response = wp_remote_get( $url, array(
			'timeout' => 3,
			'redirection' => 2,
		) );
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'image_set_validation_failed', 'Could not check this photo set right now.', array( 'status' => 503 ) );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'image_set_validation_failed', 'Could not check this photo set right now.', array( 'status' => 503 ) );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'image_set_validation_failed', 'Could not check this photo set right now.', array( 'status' => 503 ) );
		}
		return array(
			'slug' => isset( $data['slug'] ) ? self::sanitize_slug_param( $data['slug'], $slug ) : $slug,
			'valid' => ! empty( $data['valid'] ),
			'exists' => ! empty( $data['exists'] ),
			'commercial' => ! empty( $data['commercial'] ),
			'name' => isset( $data['name'] ) ? (string) $data['name'] : null,
			'hasWatermark' => ! empty( $data['has_watermark'] ),
			'allowedForCommercial' => ! empty( $data['allowed_for_commercial'] ),
			'selectionMode' => isset( $data['selection_mode'] ) && $data['selection_mode'] === 'mapped' ? 'mapped' : 'pool',
			'imageCount' => isset( $data['image_count'] ) ? max( 0, (int) $data['image_count'] ) : 0,
		);
	}

}
