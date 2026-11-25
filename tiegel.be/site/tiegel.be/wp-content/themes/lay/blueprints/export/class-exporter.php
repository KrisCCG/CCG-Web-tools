<?php
/**
 * Lay Blueprints — Exporter (orchestrator).
 *
 * Phase 2 scope: options, theme_mods, menus, plugin compat block. Content
 * (posts/layouts/media/fonts) lands in Phase 3.
 *
 * Runs synchronously inside the /export/start request. The JobState shape is
 * already prepared for an async future — when an export grows past PHP timeouts
 * we'll spawn a background runner without changing the API surface.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_Exporter {

	/**
	 * @param string[] $required_plugin_slugs Slugs the user checked in the UI.
	 * @param string   $min_version_strategy 'exact' (default) | 'minor' (loosen min_required to X.Y.0)
	 * @param string   $placeholder_source_type image_set | solid_color.
	 * @param string   $placeholder_image_set Source set used by the placeholder media service.
	 * @param string   $placeholder_color Solid color slug used by the placeholder media service.
	 * @param string   $placeholder_selection_mode pool | mapped.
	 * @param int      $placeholder_image_count Number of images available in mapped source set.
	 * @return array Final job state.
	 */
	public static function start( array $required_plugin_slugs, $min_version_strategy = 'exact', $placeholder_source_type = 'image_set', $placeholder_image_set = 'jose-porroche', $placeholder_color = 'warm-grey', $placeholder_selection_mode = 'pool', $placeholder_image_count = 0 ) {
		// Opportunistic cleanup of leftovers from previous exports.
		LayBlueprint_ZipWriter::purge_old_files( 24 );

		$state = LayBlueprint_JobState::create( 'export' );
		$job_id = $state['job_id'];

		try {
			LayBlueprint_JobState::update( $job_id, array( 'status' => 'running', 'step' => 'Collecting options', 'progress' => 5 ) );
			$options_result = LayBlueprint_OptionsCollector::collect();

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Collecting theme mods', 'progress' => 15 ) );
			$thememods_result = LayBlueprint_ThemeModsCollector::collect();

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Collecting menus', 'progress' => 25 ) );
			$menus_result = LayBlueprint_MenusCollector::collect();

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Collecting categories', 'progress' => 35 ) );
			$terms_result = LayBlueprint_TermCollector::collect();

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Walking content + layouts', 'progress' => 50 ) );
			$provider      = lay_blueprint_get_placeholder_provider();
			if ( method_exists( $provider, 'set_placeholder_source' ) ) {
				$provider->set_placeholder_source( $placeholder_source_type, $placeholder_image_set, $placeholder_color, $placeholder_selection_mode );
			} elseif ( method_exists( $provider, 'set_image_set' ) ) {
				$provider->set_image_set( $placeholder_image_set );
			}
			$resolver      = new LayBlueprint_MediaResolver( $provider );
			$html_rewriter = new LayBlueprint_HtmlRewriter( $resolver );
			$content_col   = new LayBlueprint_ContentCollector( $resolver, $html_rewriter );
			$content_result = $content_col->collect();

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Collecting fonts', 'progress' => 70 ) );
			$fonts_col    = new LayBlueprint_FontsCollector( $resolver );
			$fonts_result = $fonts_col->collect();

			// Walk option + theme-mod values to find attachment URLs hiding
			// inside scalar/array values (e.g. lay_cursor_image, custom CSS
			// with background URLs). Each becomes a `lay-bp://attachment/{id}`
			// anchor; the importer resolves anchors to new URLs after
			// sideloading.
			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Rewriting URLs in options', 'progress' => 75 ) );
			$opt_walker = new LayBlueprint_OptionsMediaWalker( $resolver );
			$options_result['values']    = $opt_walker->rewrite( $options_result['values'], 'options' );
			$options_result['audit']['url_anchors_planted'] = $opt_walker->rewritten_urls;
			$tm_walker = new LayBlueprint_OptionsMediaWalker( $resolver );
			$thememods_result['values']  = $tm_walker->rewrite( $thememods_result['values'], 'theme_mods' );
			$thememods_result['audit']['url_anchors_planted'] = $tm_walker->rewritten_urls;

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Resolving media placeholders', 'progress' => 80 ) );
			$media_manifest = $resolver->build_manifest();
			if ( $placeholder_selection_mode === 'mapped' ) {
				$needed_images = self::count_placeholder_images( $media_manifest );
				if ( $placeholder_image_count > 0 && $needed_images > $placeholder_image_count ) {
					throw new RuntimeException( 'This Blueprint needs ' . $needed_images . ' placeholder images, but the selected photo set only has ' . $placeholder_image_count . '.' );
				}
			}

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Building manifest', 'progress' => 85 ) );

			$blueprint = LayBlueprint_Schema::empty_blueprint();
			$blueprint['theme_mods']     = $thememods_result['values'];
			$blueprint['options']        = $options_result['values'];
			$blueprint['menus']          = $menus_result['values'];
			$blueprint['nav_menu_locations'] = LayBlueprint_MenusCollector::collect_locations();
			$blueprint['terms']          = $terms_result['values'];
			$blueprint['content']        = $content_result['values'];
			$blueprint['media_manifest'] = $media_manifest;
			$blueprint['fonts_manifest'] = $fonts_result['values'];

			$site_name      = get_bloginfo( 'name' );
			$theme_version  = LayBlueprint_Schema::lay_theme_version();
			$required_block = LayBlueprint_PluginsCollector::build_required_block( $required_plugin_slugs );
			if ( $min_version_strategy === 'minor' ) {
				foreach ( $required_block as &$entry ) {
					$entry['min_required'] = self::version_to_minor( $entry['version_at_export'] );
				}
				unset( $entry );
			}

			$content_total = 0;
			foreach ( $content_result['audit']['counts'] as $n ) { $content_total += (int) $n; }

			$manifest = array(
				'schema_version' => LayBlueprint_Schema::SCHEMA_VERSION,
				'generator'      => 'Blueprints / Lay Theme ' . (string) $theme_version,
				'site' => array(
					'name'    => $site_name,
					'url'     => home_url( '/' ),
					'locale'  => get_locale(),
				),
				'compat' => array(
					'lay_theme'        => array(
						'version_at_export' => $theme_version,
						'min_required'     => $min_version_strategy === 'minor'
							? self::version_to_minor( $theme_version )
							: $theme_version,
					),
					'required_plugins' => $required_block,
				),
				'exported_at' => gmdate( 'c' ),
				'placeholder_media' => array(
					'source_type' => $placeholder_source_type === 'solid_color' ? 'solid_color' : 'image_set',
					'image_set'    => $placeholder_image_set,
					'solid_color'  => $placeholder_color,
					'selection_mode' => $placeholder_selection_mode === 'mapped' ? 'mapped' : 'pool',
					'image_count'  => (int) $placeholder_image_count,
				),
				'counts' => array(
					'options'         => count( $options_result['values'] ),
					'theme_mods'      => count( $thememods_result['values'] ),
					'menus'           => count( $menus_result['values'] ),
					'terms'           => $terms_result['audit']['term_count'],
					'content'         => $content_total,
					'media_manifest'  => count( $media_manifest ),
					'fonts_manifest'  => $fonts_result['audit']['count'],
				),
				'audit' => array(
					'options'    => $options_result['audit'],
					'theme_mods' => $thememods_result['audit'],
					'menus'      => $menus_result['audit'],
					'terms'      => $terms_result['audit'],
					'content'    => $content_result['audit'],
					'fonts'      => $fonts_result['audit'],
					'unknown_layout_elements' => $content_col->unknown_warnings,
				),
			);

			$readme_text = self::build_readme( $manifest );

			LayBlueprint_JobState::update( $job_id, array( 'step' => 'Writing ZIP', 'progress' => 92 ) );

			$filename = LayBlueprint_Schema::build_filename( $site_name );
			// Pull real-file media entries (option assets) into the ZIP under
			// option_files/. Same mechanism as fonts.
			$option_files = array();
			foreach ( $media_manifest as $entry ) {
				if ( ! empty( $entry['preserve_real_file'] ) && ! empty( $entry['fs_path'] ) && ! empty( $entry['zip_path'] ) ) {
					$option_files[ $entry['zip_path'] ] = $entry['fs_path'];
				}
			}
			$extra_files = array_merge( $fonts_result['files'], $option_files );
			$path        = LayBlueprint_ZipWriter::write( $job_id, $filename, $manifest, $blueprint, $readme_text, $extra_files );
			if ( is_wp_error( $path ) ) {
				return LayBlueprint_JobState::fail( $job_id, $path->get_error_message() );
			}

			return LayBlueprint_JobState::complete( $job_id, $path, $filename );
		} catch ( \Throwable $e ) {
			return LayBlueprint_JobState::fail( $job_id, $e->getMessage() );
		}
	}

	private static function version_to_minor( $version ) {
		if ( ! is_string( $version ) ) {
			return $version;
		}
		$parts = explode( '.', $version );
		if ( count( $parts ) < 2 ) {
			return $version;
		}
		return $parts[0] . '.' . $parts[1] . '.0';
	}

	private static function count_placeholder_images( array $media_manifest ) {
		$count = 0;
		foreach ( $media_manifest as $entry ) {
			if (
				isset( $entry['kind'] ) &&
				$entry['kind'] === 'image' &&
				empty( $entry['preserve_real_file'] ) &&
				! empty( $entry['placeholder_url'] )
			) {
				$count++;
			}
		}
		return $count;
	}

	private static function build_readme( array $manifest ) {
		$lines = array();
		$lines[] = 'Blueprint';
		$lines[] = '';
		$lines[] = 'Source site:  ' . ( isset( $manifest['site']['name'] ) ? $manifest['site']['name'] : '?' );
		$lines[] = 'Source URL:   ' . ( isset( $manifest['site']['url'] ) ? $manifest['site']['url'] : '?' );
		$lines[] = 'Exported at:  ' . ( isset( $manifest['exported_at'] ) ? $manifest['exported_at'] : '?' );
		$lines[] = 'Schema:       v' . (int) $manifest['schema_version'];
		$lines[] = 'Generator:    ' . ( isset( $manifest['generator'] ) ? $manifest['generator'] : '?' );
		if ( ! empty( $manifest['placeholder_media']['source_type'] ) && $manifest['placeholder_media']['source_type'] === 'solid_color' ) {
			$lines[] = 'Placeholder:  Solid color ' . ( isset( $manifest['placeholder_media']['solid_color'] ) ? $manifest['placeholder_media']['solid_color'] : '?' );
		} elseif ( ! empty( $manifest['placeholder_media']['image_set'] ) ) {
			$lines[] = 'Image set:    ' . $manifest['placeholder_media']['image_set'];
		}
		$lines[] = '';
		$lines[] = 'Required versions:';
		$lines[] = '  Lay Theme >= ' . ( isset( $manifest['compat']['lay_theme']['min_required'] ) ? $manifest['compat']['lay_theme']['min_required'] : '?' );
		if ( ! empty( $manifest['compat']['required_plugins'] ) ) {
			foreach ( $manifest['compat']['required_plugins'] as $p ) {
				$lines[] = '  ' . $p['name'] . ' >= ' . $p['min_required'];
			}
		} else {
			$lines[] = '  (no optional plugins required)';
		}
		$lines[] = '';
		$lines[] = 'Counts:';
		$lines[] = '  Options:    ' . (int) $manifest['counts']['options'];
		$lines[] = '  Theme mods: ' . (int) $manifest['counts']['theme_mods'];
		$lines[] = '  Menus:      ' . (int) $manifest['counts']['menus'];
		$lines[] = '  Terms:      ' . (int) ( $manifest['counts']['terms'] ?? 0 );
		$lines[] = '  Posts:      ' . (int) ( $manifest['counts']['content'] ?? 0 );
		$lines[] = '  Media refs: ' . (int) ( $manifest['counts']['media_manifest'] ?? 0 );
		$lines[] = '  Fonts:      ' . (int) ( $manifest['counts']['fonts_manifest'] ?? 0 );
		$lines[] = '';
		$lines[] = 'Import this file via Lay Options → Blueprints → Import on the target site.';
		$lines[] = '';
		return implode( "\n", $lines );
	}
}
