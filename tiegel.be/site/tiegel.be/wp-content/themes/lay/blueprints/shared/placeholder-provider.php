<?php
/**
 * Lay Blueprints — Placeholder provider factory.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once dirname( __FILE__ ) . '/interface-placeholder-provider.php';
require_once dirname( __FILE__ ) . '/class-lay-blueprint-pics-provider.php';

/**
 * Default factory. Swap via filter to replace the provider site-wide.
 *
 * The default provider points to lay-blueprint-media:
 * https://lay-blueprint-media.b-cdn.net
 */
function lay_blueprint_get_placeholder_provider() {
	static $instance = null;
	if ( $instance === null ) {
		$instance = new LayBlueprint_LayBlueprintPicsProvider( LayBlueprint_LayBlueprintPicsProvider::default_base_url() );
		$filtered = apply_filters( 'lay_blueprint_placeholder_provider', $instance );
		if ( $filtered instanceof LayBlueprint_PlaceholderProvider ) {
			$instance = $filtered;
		}
	}
	return $instance;
}
