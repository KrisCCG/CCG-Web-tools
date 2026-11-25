<?php
/**
 * Lay Blueprints — HTML Rewriter.
 *
 * Inside text/html element `cont` fields the user can paste arbitrary HTML
 * including <img>, <video>, <source>, <picture>, <a>, <iframe>. URLs in
 * those tags can point to attachments in the source site's uploads folder.
 *
 * On export we need to:
 *   1) Map every <img src> / <source src> / <video src> URL back to an
 *      attachment ID via attachment_url_to_postid (best effort), register
 *      it with the MediaResolver, and rewrite the URL to a placeholder so
 *      the importer doesn't even need to do that mapping in reverse.
 *   2) Strip srcset (the placeholder is a single URL, no responsive variants).
 *   3) Leave external URLs (vimeo.com, youtube.com, etc.) untouched.
 *
 * Uses DOMDocument with explicit UTF-8 wrapping so multibyte content
 * survives the round-trip. Returns the rewritten HTML; caller writes it
 * back via the slot callback.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_HtmlRewriter {

	/** @var LayBlueprint_MediaResolver */
	private $resolver;

	/** @var string */
	private $home_url_root;

	/**
	 * URL → placeholder cache (this run only). Same image referenced in
	 * multiple HTML blocks resolves to the same placeholder and the same
	 * downstream attachment.
	 *
	 * @var array<string,string>
	 */
	private $url_placeholder_cache = array();

	public function __construct( LayBlueprint_MediaResolver $resolver ) {
		$this->resolver      = $resolver;
		$this->home_url_root = self::compute_home_root();
	}

	/**
	 * Rewrite the given HTML. Returns the new HTML and registers any media
	 * found with the MediaResolver.
	 *
	 * @param string $html
	 * @param string $hint  trace hint (e.g. "post:42 cont[3].cont")
	 */
	public function rewrite( $html, $hint = '' ) {
		if ( $html === '' || stripos( $html, '<' ) === false ) {
			return $html;
		}

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		// libxml emits warnings on user-provided HTML (often missing root,
		// unrecognized tags, etc.) which are not actionable.
		$prev = libxml_use_internal_errors( true );

		// Wrap so DOMDocument doesn't auto-add <html><body> elements that
		// then need stripping. The meta tag forces UTF-8 parsing.
		$wrapped = '<?xml encoding="UTF-8"?><div id="__lay_bp_root__">' . $html . '</div>';
		$loaded = @$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		if ( ! $loaded ) {
			// Parsing failed; leave HTML untouched so we don't corrupt content.
			return $html;
		}

		// <img src> + srcset
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			$this->rewrite_attr_url( $img, 'src',  $hint . ' img.src' );
			if ( $img->hasAttribute( 'srcset' ) ) {
				$img->removeAttribute( 'srcset' );
			}
			if ( $img->hasAttribute( 'sizes' ) ) {
				$img->removeAttribute( 'sizes' );
			}
		}
		// <source src> (used inside <video> and <picture>)
		foreach ( $dom->getElementsByTagName( 'source' ) as $source ) {
			$this->rewrite_attr_url( $source, 'src',    $hint . ' source.src' );
			$this->rewrite_attr_url( $source, 'srcset', $hint . ' source.srcset' );
		}
		// <video src> (when the URL is on the video element directly)
		foreach ( $dom->getElementsByTagName( 'video' ) as $video ) {
			$this->rewrite_attr_url( $video, 'src',    $hint . ' video.src' );
			$this->rewrite_attr_url( $video, 'poster', $hint . ' video.poster' );
		}
		// <audio src>
		foreach ( $dom->getElementsByTagName( 'audio' ) as $audio ) {
			$this->rewrite_attr_url( $audio, 'src', $hint . ' audio.src' );
		}

		// Serialize back to a fragment.
		$root = $dom->getElementById( '__lay_bp_root__' );
		if ( ! $root ) {
			return $html;
		}
		$out = '';
		foreach ( $root->childNodes as $child ) {
			$out .= $dom->saveHTML( $child );
		}

		return $out;
	}

	private function rewrite_attr_url( DOMElement $el, $attr, $hint ) {
		if ( ! $el->hasAttribute( $attr ) ) {
			return;
		}
		$value = $el->getAttribute( $attr );
		if ( $value === '' ) { return; }

		// srcset is comma-separated `url descriptor` pairs.
		if ( $attr === 'srcset' ) {
			$el->removeAttribute( 'srcset' );
			return;
		}

		$placeholder = $this->resolve_url( $value, $hint );
		if ( $placeholder !== null ) {
			$el->setAttribute( $attr, $placeholder );
		}
	}

	/**
	 * Resolve a URL: if it's a local attachment, look up its ID, register
	 * it, return the placeholder URL. If it's external, return null
	 * (no rewrite). If it can't be resolved to an attachment, also return
	 * null (leaving the original URL in place — better than blank).
	 */
	private function resolve_url( $url, $hint ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return null;
		}
		// Cache hit?
		if ( isset( $this->url_placeholder_cache[ $url ] ) ) {
			return $this->url_placeholder_cache[ $url ];
		}

		// External URL → don't touch.
		if ( ! $this->is_local_url( $url ) ) {
			return null;
		}

		$attachment_id = attachment_url_to_postid( $url );
		// attachment_url_to_postid doesn't handle scaled / sized URLs
		// (foo-1024x768.jpg). Try the original-size variant.
		if ( ! $attachment_id ) {
			$base = preg_replace( '/-\d+x\d+(?=\.[a-zA-Z0-9]+(\?|$))/', '', $url );
			if ( $base !== $url ) {
				$attachment_id = attachment_url_to_postid( $base );
			}
		}
		if ( ! $attachment_id ) {
			return null;
		}

		$this->resolver->register( $attachment_id, 'image', $hint );

		// We don't know the placeholder URL yet — MediaResolver picks it
		// during build_manifest. So return a deterministic *anchor* URL
		// that the importer will swap when applying the manifest.
		// Form: lay-bp://attachment/{id} — invalid scheme is intentional,
		// guarantees no browser fetches it accidentally pre-import.
		$anchor = 'lay-bp://attachment/' . $attachment_id;
		$this->url_placeholder_cache[ $url ] = $anchor;
		return $anchor;
	}

	private function is_local_url( $url ) {
		// Relative paths count as local (same origin).
		if ( strpos( $url, '/' ) === 0 ) {
			return true;
		}
		if ( $this->home_url_root === '' ) {
			return false;
		}
		// Compare host only — http vs https on the same host both count.
		$host_in_url = preg_replace( '#^https?://#i', '', $url );
		return strpos( $host_in_url, $this->home_url_root . '/' ) === 0
			|| strpos( $host_in_url, $this->home_url_root ) === 0;
	}

	private static function compute_home_root() {
		$home = home_url( '/' );
		if ( ! is_string( $home ) ) { return ''; }
		// Just the host (+ optional subdir), no protocol, no trailing slash.
		return rtrim( preg_replace( '#^https?://#i', '', $home ), '/' );
	}
}
