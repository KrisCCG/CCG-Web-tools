<?php
/**
 * Cache Invalidation Handler
 * Handles clearing of element markup transients and layout cache
 */

class Lay_Cache_Invalidation {
	
	/**
	 * Constructor - sets up all hooks
	 */
	public function __construct() {
		// Clear element markup caches when posts are updated (for performance optimization)
		add_action('save_post', [$this, 'clear_element_markup_caches_on_save'], 10, 1);
		add_action('delete_post', [$this, 'clear_element_markup_caches_on_delete'], 10, 1);
		
		// Clear element markup caches when customizer settings are saved
		add_action('customize_save_after', [$this, 'clear_element_markup_caches_on_customizer_save']);
		
		// Clear element markup caches when theme is updated
		add_action('upgrader_process_complete', [$this, 'clear_element_markup_caches_on_theme_update'], 10, 2);

		// Clear element markup transients when posts are reordered via drag-and-drop
		add_action('wp_ajax_update-menu-order', [$this, 'clear_transients_after_post_reorder'], 20);
		add_action('wp_ajax_update-menu-order-tags', [$this, 'clear_transients_after_tag_reorder'], 20);
	}

	/**
	 * Clear element markup caches when a post is saved
	 * This ensures fresh content after post updates
	 */
	public function clear_element_markup_caches_on_save($post_id) {
		// Only clear for published posts
		if (get_post_status($post_id) === 'publish') {
			$this->clear_all_element_markup_caches();
		}
	}

	/**
	 * Clear element markup caches when a post is deleted
	 */
	public function clear_element_markup_caches_on_delete($post_id) {
		$this->clear_all_element_markup_caches();
	}

	/**
	 * Clear element markup caches when customizer settings are saved
	 * Customizer settings can affect how elements are rendered (e.g. thumbnail grid styling)
	 */
	public function clear_element_markup_caches_on_customizer_save() {
		$this->clear_all_element_markup_caches();
	}

	/**
	 * Clear element markup caches when theme is updated
	 * Uses same approach as layout_cache.php's lay_on_upgrader_process_complete
	 */
	public function clear_element_markup_caches_on_theme_update($upgrader_object, $hook_extra) {
		$theme_name = 'lay';
		$theme_wasupdated = 'theme_not_updated';

		if (isset($hook_extra['themes']) && is_array($hook_extra['themes'])) {
			foreach ($hook_extra['themes'] as $index => $updated_theme) {
				if ($theme_name == $updated_theme) {
					$theme_wasupdated = 'theme_updated';
					break;
				}
			}
		}

		if ($theme_wasupdated == 'theme_updated') {
			$this->clear_all_element_markup_caches();
			error_log('Lay Element Markup Caches: Cleared due to theme update via upgrader_process_complete');
		}
	}

	/**
	 * Clear all element markup transient caches
	 * Clears caches for: thumbnailgrid, projectindex, productindex, productsgrid, elementgrid, newselement
	 */
	private function clear_all_element_markup_caches() {
		global $wpdb;
		// Delete all element markup transients
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_thumbgrid_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_thumbgrid_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_newselement_markup_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_projectindex_markup_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_productindex_markup_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_productsgrid_markup_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementgrid_markup_%'");
		$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_elementgrid_markup_%'");
	}

	/**
	 * Clear element markup transients after posts are reordered
	 * Hooked into wp_ajax_update-menu-order with priority 20 (runs after plugin updates order)
	 */
	public function clear_transients_after_post_reorder() {
		$this->clear_all_element_markup_caches();
		// Clear layout cache
		if (class_exists('Lay_Layout_Cache')) {
			Lay_Layout_Cache::clear_all();
		}
	}

	/**
	 * Clear element markup transients after taxonomies are reordered
	 * Hooked into wp_ajax_update-menu-order-tags with priority 20 (runs after plugin updates order)
	 */
	public function clear_transients_after_tag_reorder() {
		$this->clear_all_element_markup_caches();
		// Clear layout cache
		if (class_exists('Lay_Layout_Cache')) {
			Lay_Layout_Cache::clear_all();
		}
	}
}

// Initialize the cache invalidation handler
new Lay_Cache_Invalidation();

