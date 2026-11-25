<?php
/**
 * Lay Blueprints — HTML Import Rewriter.
 *
 * On export the HtmlRewriter replaced every local img/video URL inside
 * text/html element `cont` strings (and post_content) with a sentinel:
 *   src="lay-bp://attachment/{old_id}"
 *
 * On import this class scans HTML strings, looks each old_id up in the
 * attachment IDMap, and substitutes wp_get_attachment_url($new_id).
 *
 * Operates on plain strings (not DOMDocument) — the sentinel is unique
 * enough that a regex pass is reliable and avoids re-serialisation of
 * unrelated markup.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_HtmlImportRewriter {

	const SENTINEL_REGEX = '#lay-bp://attachment/(\d+)#';

	/** @var LayBlueprint_IdMap */
	private $attachments;

	public $stats = array(
		'urls_rewritten' => 0,
		'urls_missing'   => 0,
	);

	public function __construct( LayBlueprint_IdMap $attachments ) {
		$this->attachments = $attachments;
	}

	/**
	 * Rewrite a single HTML string. Returns the new string. Idempotent if
	 * called twice (the sentinel pattern is replaced with real URLs the
	 * second pass would just leave alone).
	 */
	public function rewrite( $html ) {
		if ( ! is_string( $html ) || $html === '' ) { return $html; }
		if ( strpos( $html, 'lay-bp://attachment/' ) === false ) { return $html; }

		return preg_replace_callback( self::SENTINEL_REGEX, function ( $m ) {
			$old_id = (int) $m[1];
			$new_id = $this->attachments->lookup( $old_id );
			if ( $new_id === null ) {
				$this->stats['urls_missing']++;
				// Leave a non-loading placeholder so the markup doesn't break
				// the layout; the user can search for "lay-bp-missing" in the
				// admin to find broken images post-import.
				return 'about:blank?lay-bp-missing=' . $old_id;
			}
			$url = wp_get_attachment_url( $new_id );
			if ( ! $url ) {
				$this->stats['urls_missing']++;
				return 'about:blank?lay-bp-no-url=' . $new_id;
			}
			$this->stats['urls_rewritten']++;
			return $url;
		}, $html );
	}
}
