<?php

if (!class_exists('WP_Customize_Control')) {
	return null;
}

if (class_exists('Order_Custom_Control')) {
	return;
}

/**
 * A class to create a drag-and-drop order control for WooCommerce single product elements
 * Manages multiple settings by reading their numeric order values and allowing visual reordering
 */
class Order_Custom_Control extends WP_Customize_Control {
	public $type = 'order_control';

	/**
	 * Array of setting IDs to manage
	 * Example: ['breadcrumbs' => 'setting_id_breadcrumbs', 'title' => 'setting_id_title']
	 */
	public $order_settings = [];

	/**
	 * Array of labels for each item
	 * Example: ['breadcrumbs' => 'Breadcrumbs', 'title' => 'Title']
	 */
	public $order_labels = [];

	/**
	 * Render the content of the order control
	 *
	 * @return HTML
	 */
	public function render_content() {
		if (empty($this->order_settings)) {
			return;
		}

		// Get all settings and their current order values
		$items = [];
		foreach ($this->order_settings as $key => $setting_id) {
			$setting = $this->manager->get_setting($setting_id);
			if ($setting) {
				$order_value = $setting->value();
				$label = isset($this->order_labels[$key]) ? $this->order_labels[$key] : ucfirst($key);
				$items[] = [
					'key' => $key,
					'setting_id' => $setting_id,
					'order' => intval($order_value),
					'label' => $label
				];
			}
		}

		// Sort items by their order value
		usort($items, function($a, $b) {
			return $a['order'] - $b['order'];
		});

		?>
        <label class="customize-control-title">
            <?php
            if (!empty($this->label)): ?>
                <span><?php echo esc_html($this->label); ?></span>
            <?php endif;
            if (!empty($this->description)): ?>
                <span class="description customize-control-description" style="margin-top: 8px; display: block;"><?php echo $this->description; ?></span>
            <?php endif;
            ?>
        </label>

        <div class="lay-order-control-wrapper" style="margin-top: 12px;">
            <ul class="lay-order-list" style="margin: 0; padding: 0; list-style: none;">
                <?php foreach ($items as $item): ?>
                    <li class="lay-order-item" 
                        data-key="<?php echo esc_attr($item['key']); ?>"
                        data-setting-id="<?php echo esc_attr($item['setting_id']); ?>"
                        style="padding: 10px 12px; margin-bottom: 5px; background: #fff; border: 1px solid #ddd; border-radius: 3px; cursor: move; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-menu" style="color: #999; flex-shrink: 0;"></span>
                        <span class="lay-order-label" style="flex: 1;"><?php echo esc_html($item['label']); ?></span>
                        <input type="hidden" 
                               class="lay-order-value" 
                               data-customize-setting-link="<?php echo esc_attr($item['setting_id']); ?>"
                               value="<?php echo esc_attr($item['order']); ?>" />
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="description" style="margin-top: 8px; font-style: italic; color: #666;">
                Drag items to reorder.
            </p>
        </div>
        <?php
	}

	/**
	 * Enqueue control related scripts/styles.
	 */
	public function enqueue() {
		wp_add_inline_script('customize-controls', $this->get_inline_script());
		wp_add_inline_style('customize-controls', $this->get_inline_style());
	}

	/**
	 * Get the inline script for drag-and-drop functionality
	 */
	private function get_inline_script() {
		return "
		jQuery(document).ready(function($) {
			// Initialize drag and drop for all order controls
			$('.lay-order-control-wrapper').each(function() {
				var wrapper = $(this);
				var list = wrapper.find('.lay-order-list');
				
				// Make list sortable using jQuery UI Sortable (already available in WordPress)
				if (typeof $.fn.sortable !== 'undefined') {
					list.sortable({
						cursor: 'move',
						opacity: 0.8,
						placeholder: 'lay-order-placeholder',
						update: function(event, ui) {
							updateOrderValues(list);
						}
					});
				}
				
				function updateOrderValues(list) {
					// Get all items in current order
					var items = list.find('.lay-order-item');
					
					// Update the order value for each item based on position
					items.each(function(index) {
						var item = $(this);
						var hiddenInput = item.find('.lay-order-value');
						var settingId = item.data('setting-id');
						
						// Set new order value (starting from 0)
						hiddenInput.val(index).trigger('change');
						
						// Also update the Customizer setting directly
						if (wp.customize && wp.customize(settingId)) {
							wp.customize(settingId).set(index);
						}
					});
				}
			});
		});
		";
	}

	/**
	 * Get the inline CSS for styling the order control
	 */
	private function get_inline_style() {
		return "
		/* Order control styling */
		.lay-order-item {
			transition: background-color 0.15s ease;
		}
		
		.lay-order-item:hover {
			background-color: #f7f7f7 !important;
		}
		
		.lay-order-item.ui-sortable-helper {
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
			border-color: #999 !important;
		}
		
		.lay-order-placeholder {
			background: #f0f0f1 !important;
			border: 2px dashed #c3c4c7 !important;
			height: 42px;
			margin-bottom: 5px;
			border-radius: 3px;
			visibility: visible !important;
		}
		
		.lay-order-list .dashicons-menu {
			cursor: grab;
		}
		
		.lay-order-item.ui-sortable-helper .dashicons-menu {
			cursor: grabbing;
		}
		";
	}
}

