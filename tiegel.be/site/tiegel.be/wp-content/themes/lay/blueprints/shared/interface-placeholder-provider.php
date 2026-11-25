<?php
/**
 * Lay Blueprints — Placeholder Provider Interface
 *
 * Resolves a placeholder media URL for an attachment that should NOT be
 * exported in raw form (would bloat the ZIP). Implementations can be:
 *
 *   - Static_Pool_Provider (default V1) — returns one of N pre-baked URLs
 *     hosted on a CDN, picked deterministically by seed.
 *   - Dynamic_Service_Provider (planned) — returns a URL pointing to a web
 *     service that generates a curated photo at the requested aspect ratio.
 *
 * Swap implementations via the `lay_blueprint_placeholder_provider` filter:
 *
 *     add_filter( 'lay_blueprint_placeholder_provider', function () {
 *         return new My_Custom_Provider();
 *     });
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

interface LayBlueprint_PlaceholderProvider {

	/**
	 * Return a URL for an image placeholder.
	 *
	 * @param int    $width  Pixel width of the original image.
	 * @param int    $height Pixel height of the original image.
	 * @param float  $ar     Width / height (so callers don't have to recompute).
	 * @param string $seed   Stable per-attachment hash so neighbouring images in
	 *                       a layout don't collapse to the same placeholder.
	 *                       Implementations should use this to vary output.
	 * @return string Absolute URL, served as image/jpeg or image/png.
	 */
	public function for_image( $width, $height, $ar, $seed );

	/**
	 * Return a URL for a video placeholder.
	 *
	 * @param float  $ar
	 * @param string $seed
	 * @return string Absolute URL, served as video/mp4.
	 */
	public function for_video( $ar, $seed );
}
