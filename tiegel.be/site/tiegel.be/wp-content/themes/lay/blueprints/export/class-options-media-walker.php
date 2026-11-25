<?php
/**
 * Lay Blueprints — Options Media Walker.
 *
 * Walks every option value collected by OptionsCollector, finds strings
 * that look like attachment URLs from the source site (anything matching
 * the uploads dir host+path), looks up their attachment IDs via
 * attachment_url_to_postid, registers each with the MediaResolver, and
 * rewrites the string to the same `lay-bp://attachment/{id}` anchor scheme
 * the HtmlRewriter uses for HTML-embedded URLs.
 *
 * Recurses into nested arrays (handles serialized arrays, multi-value
 * options, JSON blobs that decoded to arrays, etc.).
 *
 * Strings that are JSON blobs (`fontmanager_json`, `formatsmanager_json`,
 * any *_gridder_json) get JSON-decoded, walked, and re-encoded — so font
 * URLs and embedded layout URLs in those blobs also get rewritten.
 *
 * Imported again by OptionsInstaller (`maybe_resolve_options_media_anchors`)
 * after the attachment IDMap is populated.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_OptionsMediaWalker {

	const ANCHOR_PREFIX = 'lay-bp://attachment/';

	/** @var LayBlueprint_MediaResolver */
	private $resolver;

	/** @var string Lowercased host (no scheme) of this site's uploads. */
	private $upload_host;

	/** @var string Path prefix of the uploads dir, with trailing slash. */
	private $upload_path_prefix;

	/** Stats. */
	public $rewritten_urls = 0;
	public $skipped_external = 0;

	/** @var array<string,int> */
	private $synthetic_ids_by_url = array();

	private $next_synthetic_id = 900000000;

	public function __construct( LayBlueprint_MediaResolver $resolver ) {
		$this->resolver = $resolver;
		$uploads = wp_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		$parsed  = $baseurl ? wp_parse_url( $baseurl ) : array();
		$this->upload_host        = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
		$this->upload_path_prefix = isset( $parsed['path'] ) ? rtrim( (string) $parsed['path'], '/' ) . '/' : '/wp-content/uploads/';
	}

	/**
	 * Recursively rewrite a value (any type). Returns the new value with
	 * URLs replaced by anchors. Side-effect: registers each resolved id
	 * with the MediaResolver.
	 */
	public function rewrite( $value, $hint = '' ) {
		if ( is_string( $value ) ) {
			return $this->rewrite_string( $value, $hint );
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = $this->rewrite( $v, $hint . '[' . $k . ']' );
			}
			return $out;
		}
		// Numbers, bools, nulls, objects — leave alone.
		return $value;
	}

	private function rewrite_string( $value, $hint ) {
		// Cheap pre-check: if the string doesn't contain "://", no URL inside.
		if ( strpos( $value, '://' ) === false && strpos( $value, '/wp-content/uploads/' ) === false ) {
			return $value;
		}

		// JSON-blob path: try to decode → walk → re-encode.
		if ( self::looks_like_json( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$rewritten_decoded = $this->rewrite( $decoded, $hint . '(json)' );
				$re                = wp_json_encode( $rewritten_decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( is_string( $re ) ) { return $re; }
			}
		}

		// Plain string: regex-replace every match.
		return preg_replace_callback(
			'#https?://[^\s<>"\']+#i',
			function ( $m ) use ( $hint ) {
				return $this->maybe_anchor( $m[0], $hint );
			},
			$value
		);
	}

	private function maybe_anchor( $url, $hint ) {
		// Only rewrite URLs that look like local uploads.
		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) ) { return $url; }
		$host = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
		$path = isset( $parsed['path'] ) ? $parsed['path'] : '';

		if ( $this->upload_host !== '' && $host !== $this->upload_host ) {
			$this->skipped_external++;
			return $url;
		}
		if ( strpos( $path, $this->upload_path_prefix ) === false ) {
			$this->skipped_external++;
			return $url;
		}

		$attachment_id = attachment_url_to_postid( $url );
		if ( ! $attachment_id ) {
			// Try unscaled form (foo-1024x768.jpg → foo.jpg)
			$base = preg_replace( '/-\d+x\d+(?=\.[a-zA-Z0-9]+(\?|$))/', '', $url );
			if ( $base !== $url ) {
				$attachment_id = attachment_url_to_postid( $base );
			}
		}
		if ( ! $attachment_id ) {
			// Couldn't resolve — leave the URL alone. The importer will see
			// a literal URL string and not try to rewrite it. If the file still
			// exists in uploads though, preserve it as a real file entry even
			// when its attachment row is gone/stale.
			$file_path = $this->local_upload_url_to_path( $url );
			if ( $file_path !== '' && is_readable( $file_path ) ) {
				$synthetic_id = $this->synthetic_id_for_url( $url );
				$this->resolver->register_local_file( $synthetic_id, $file_path, $url, $hint );
				$this->rewritten_urls++;
				return self::ANCHOR_PREFIX . $synthetic_id;
			}
			return $url;
		}

		// Functional assets in options (cursors, icons, custom CSS background
		// images) need to round-trip as real files, not stock placeholders.
		$this->resolver->register( (int) $attachment_id, 'image', $hint );
		$this->resolver->mark_preserve_real_file( (int) $attachment_id, $hint );
		$this->rewritten_urls++;
		return self::ANCHOR_PREFIX . (int) $attachment_id;
	}

	private function local_upload_url_to_path( $url ) {
		$uploads = wp_upload_dir();
		if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) { return ''; }
		$baseurl = rtrim( (string) $uploads['baseurl'], '/' );
		$basedir = rtrim( (string) $uploads['basedir'], '/' );
		$baseurl_no_scheme = preg_replace( '#^https?://#i', '', $baseurl );
		$url_no_scheme     = preg_replace( '#^https?://#i', '', (string) $url );
		if ( strpos( $url_no_scheme, $baseurl_no_scheme ) !== 0 ) { return ''; }
		$relative = substr( $url_no_scheme, strlen( $baseurl_no_scheme ) );
		$relative = strtok( $relative, '?' );
		return $basedir . $relative;
	}

	private function synthetic_id_for_url( $url ) {
		$key = (string) $url;
		if ( isset( $this->synthetic_ids_by_url[ $key ] ) ) {
			return $this->synthetic_ids_by_url[ $key ];
		}
		$this->synthetic_ids_by_url[ $key ] = $this->next_synthetic_id++;
		return $this->synthetic_ids_by_url[ $key ];
	}

	private static function looks_like_json( $s ) {
		if ( ! is_string( $s ) || strlen( $s ) < 2 ) { return false; }
		$first = $s[0];
		$last  = $s[ strlen( $s ) - 1 ];
		return ( $first === '{' && $last === '}' ) || ( $first === '[' && $last === ']' );
	}
}
