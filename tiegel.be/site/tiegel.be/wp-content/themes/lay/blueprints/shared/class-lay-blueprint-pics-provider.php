<?php
/**
 * Lay Blueprints — LayBlueprintPics Placeholder Provider.
 *
 * Targets the lay-blueprint-pics service (Express + Sharp; see
 * /Users/arminunruh/Documents/git/lay-blueprint-media/).
 * Photos are curated and cropped server-side to exact dimensions.
 *
 * Activate via filter:
 *
 *     add_filter( 'lay_blueprint_placeholder_provider', function () {
 *         return new LayBlueprint_LayBlueprintPicsProvider( 'https://media.example.com', 'jose-porroche' );
 *     });
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once dirname( __FILE__ ) . '/interface-placeholder-provider.php';

class LayBlueprint_LayBlueprintPicsProvider implements LayBlueprint_PlaceholderProvider {

	/** @var string Base URL of the service, no trailing slash. */
	private $base;

	/** @var string Source image set slug used by the media service. */
	private $image_set;

	/** @var string Placeholder source type: image_set | solid_color. */
	private $source_type = 'image_set';

	/** @var string Solid color slug used by the media service. */
	private $solid_color;

	/** @var string Image selection mode: pool | mapped. */
	private $selection_mode = 'pool';

	/** @var int Counter used when mapped mode needs stable per-export order. */
	private $mapped_image_index = 0;

	const DEFAULT_BASE_URL = 'https://lay-blueprint-media.b-cdn.net';
	const DEFAULT_PREVIEW_BASE_URL = 'https://lay-blueprint-media.fly.dev';
	const MAX_DIM = 4000;
	const MIN_DIM = 1;
	const DEFAULT_IMAGE_SET = 'jose-porroche';
	const DEFAULT_SOLID_COLOR = 'warm-grey';
	const REMOTE_RENDER_VERSION = 'wm4';

	public function __construct( $base_url = self::DEFAULT_BASE_URL, $image_set = self::DEFAULT_IMAGE_SET, $source_type = 'image_set', $solid_color = self::DEFAULT_SOLID_COLOR, $selection_mode = 'pool' ) {
		$this->base = rtrim( (string) $base_url, '/' );
		$this->set_image_set( $image_set );
		$this->set_solid_color( $solid_color );
		$this->set_source_type( $source_type );
		$this->set_selection_mode( $selection_mode );
	}

	public static function default_base_url() {
		return rtrim( apply_filters( 'lay_blueprint_placeholder_media_base_url', self::DEFAULT_BASE_URL ), '/' );
	}

	public static function default_preview_base_url() {
		return rtrim( apply_filters( 'lay_blueprint_placeholder_preview_base_url', self::DEFAULT_PREVIEW_BASE_URL ), '/' );
	}

	public static function current_import_base_url() {
		$base = self::default_base_url();
		if ( function_exists( 'lay_blueprint_get_placeholder_provider' ) ) {
			$provider = lay_blueprint_get_placeholder_provider();
			if ( $provider instanceof self ) {
				$base = $provider->get_base_url();
			}
		}
		return rtrim( apply_filters( 'lay_blueprint_placeholder_import_base_url', $base ), '/' );
	}

	public static function normalize_media_url( $url, $base_url = null ) {
		$url = (string) $url;
		if ( $url === '' ) { return $url; }
		if ( apply_filters( 'lay_blueprint_normalize_placeholder_remote_urls', true, $url ) !== true ) {
			return $url;
		}
		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) || empty( $parsed['path'] ) ) {
			return $url;
		}
		$path = (string) $parsed['path'];
		if ( strpos( $path, '/solid/' ) !== 0 && strpos( $path, '/sets/' ) !== 0 && strpos( $path, '/pic/' ) !== 0 && strpos( $path, '/vid/' ) !== 0 ) {
			return $url;
		}
		$base = $base_url !== null ? rtrim( (string) $base_url, '/' ) : self::current_import_base_url();
		$normalized = $base . $path;
		if ( ! empty( $parsed['query'] ) ) {
			$normalized .= '?' . $parsed['query'];
		}
		if ( ! empty( $parsed['fragment'] ) ) {
			$normalized .= '#' . $parsed['fragment'];
		}
		return $normalized;
	}

	public function get_base_url() {
		return $this->base;
	}

	public function set_image_set( $image_set ) {
		$this->image_set = self::sanitize_set_slug( $image_set );
		return $this;
	}

	public function set_source_type( $source_type ) {
		$this->source_type = $source_type === 'solid_color' ? 'solid_color' : 'image_set';
		return $this;
	}

	public function set_solid_color( $solid_color ) {
		$this->solid_color = self::sanitize_color_slug( $solid_color );
		return $this;
	}

	public function set_selection_mode( $selection_mode ) {
		$this->selection_mode = $selection_mode === 'mapped' ? 'mapped' : 'pool';
		$this->mapped_image_index = 0;
		return $this;
	}

	public function get_selection_mode() {
		return $this->selection_mode;
	}

	public function next_image_seed( $seed ) {
		if ( $this->selection_mode === 'mapped' && $this->source_type === 'image_set' ) {
			return (string) $this->mapped_image_index++;
		}
		return (string) $seed;
	}

	public function set_placeholder_source( $source_type, $image_set, $solid_color, $selection_mode = 'pool' ) {
		$this->set_image_set( $image_set );
		$this->set_solid_color( $solid_color );
		$this->set_source_type( $source_type );
		$this->set_selection_mode( $selection_mode );
		return $this;
	}

	public function for_image( $width, $height, $ar, $seed ) {
		$w = (int) $width;
		$h = (int) $height;
		if ( $w < self::MIN_DIM || $h < self::MIN_DIM ) {
			$w = 1200;
			$h = (int) max( 1, round( 1200 / max( (float) $ar, 0.0001 ) ) );
		}
		$w = min( self::MAX_DIM, $w );
		$h = min( self::MAX_DIM, $h );

		if ( $this->source_type === 'solid_color' ) {
			return $this->base . '/solid/' . $this->solid_color . '/' . $w . '/' . $h;
		}

		return $this->base . '/sets/' . $this->image_set . '/pic/' . self::sanitize_seed( $seed ) . '/' . $w . '/' . $h . '?v=' . self::REMOTE_RENDER_VERSION;
	}

	public function for_video( $ar, $seed ) {
		// The service serves videos as passthrough — no AR negotiation.
		// The pool should contain enough variety that the picked video
		// roughly matches what the layout expects.
		return $this->base . '/sets/' . $this->image_set . '/vid/' . self::sanitize_seed( $seed );
	}

	private static function sanitize_seed( $seed ) {
		$s = preg_replace( '/[^A-Za-z0-9]/', '', (string) $seed );
		if ( $s === '' ) { $s = 'lay'; }
		return substr( $s, 0, 32 );
	}

	private static function sanitize_set_slug( $set ) {
		$s = strtolower( (string) $set );
		$s = preg_replace( '/[^a-z0-9-]+/', '-', $s );
		$s = trim( $s, '-' );
		if ( $s === '' ) { $s = self::DEFAULT_IMAGE_SET; }
		return substr( $s, 0, 64 );
	}

	private static function sanitize_color_slug( $color ) {
		$s = strtolower( (string) $color );
		$s = preg_replace( '/[^a-z0-9-]+/', '-', $s );
		$s = trim( $s, '-' );
		if ( $s === '' ) { $s = self::DEFAULT_SOLID_COLOR; }
		return substr( $s, 0, 64 );
	}
}
