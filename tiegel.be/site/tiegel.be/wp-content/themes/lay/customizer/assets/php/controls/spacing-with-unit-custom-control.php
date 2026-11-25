<?php

if (!class_exists('WP_Customize_Control')) {
	return null;
}

if (class_exists('Spacing_With_Unit_Custom_Control')) {
	return;
}

/**
 * A class to create a combined spacing control with value input and unit selector
 * This control manages two settings: one for the numeric value and one for the unit (px or %)
 */
class Spacing_With_Unit_Custom_Control extends WP_Customize_Control {
	public $type = 'spacing_with_unit';

	/**
	 * The setting ID for the unit (px or %)
	 */
	public $unit_setting = '';

	/**
	 * Input step for the number field
	 */
	public $step = '0.1';

	/**
	 * Choices for the unit selector
	 * Default: array('%' => '%', 'px' => 'px')
	 */
	public $unit_choices = [];

	/**
	 * Render the content of the combined spacing control
	 *
	 * @return HTML
	 */
	public function render_content() {
		if (empty($this->unit_setting)) {
			return;
		}

		$unit_setting = $this->manager->get_setting($this->unit_setting);
		if (!$unit_setting) {
			return;
		}
		?>
        <label>
            <?php
            if (!empty($this->label)): ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
            <?php endif;
            if (!empty($this->description)): ?>
                <span class="description customize-control-description"><?php echo $this->description; ?></span>
            <?php endif;
            ?>

            <div style="display: flex; gap: 8px; align-items: center;">
                <input 
                    type="number" 
                    step="<?php echo esc_attr($this->step); ?>" 
                    <?php $this->link(); ?> 
                    value="<?php echo esc_attr($this->value()); ?>"
                    style="flex: 1; min-width: 0;"
                />
                <select 
                    class="spacing-unit-selector"
                    data-customize-setting-link="<?php echo esc_attr($this->unit_setting); ?>"
                    style="flex: 0 0 auto; width: 60px;"
                >
                    <?php
                    $unit_value = get_theme_mod($this->unit_setting, '%');

                    // Use custom choices if provided, otherwise default to % and px
                    $choices = !empty($this->unit_choices) ? $this->unit_choices : ['%' => '%', 'px' => 'px'];

                    foreach ($choices as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($unit_value, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
        </label>
        <?php
	}

	/**
	 * Enqueue control related scripts/styles.
	 */
	public function enqueue() {
		wp_add_inline_script('customize-controls', $this->get_inline_script());
	}

	/**
	 * Get the inline script for properly linking the unit selector
	 * 
	 * WHY THIS JAVASCRIPT IS NEEDED:
	 * This control manages TWO settings:
	 * 1. The number input (e.g., "15") - uses WordPress's $this->link() method (automatic binding)
	 * 2. The unit select (e.g., "px" or "%") - uses a DIFFERENT setting ID
	 * 
	 * WordPress's $this->link() only works for the control's PRIMARY setting.
	 * Since the unit selector controls a SECONDARY setting, we need to manually create
	 * two-way data binding between the select element and its WordPress setting.
	 * 
	 * Without this:
	 * - The select would display but wouldn't save changes
	 * - Changes wouldn't update the setting in the database
	 * - The preview wouldn't update when changing units
	 * - Settings wouldn't persist across page refreshes
	 */
	private function get_inline_script() {
		return "
        (function($) {
            wp.customize.bind('ready', function() {
                // Find our custom spacing unit selectors and properly bind them
                // Using specific class to avoid conflicts with WordPress's native radio buttons
                $('.spacing-unit-selector[data-customize-setting-link]').each(function() {
                    // Get the setting ID from the data attribute (e.g., 'st_spacetop_mu')
                    var settingId = $(this).attr('data-customize-setting-link');
                    var setting = wp.customize(settingId);
                    if (setting) {
                        var element = $(this);
                        
                        // Find the number input that's paired with this unit selector
                        var numberInput = element.closest('div').find('input[type=\"number\"]');
                        
                        // Function to update the step based on selected unit
                        var updateStep = function(unit) {
                            if (unit === 'px') {
                                numberInput.attr('step', '1');
                            } else {
                                // For %, em, vw, or any other unit, use 0.1
                                numberInput.attr('step', '0.1');
                            }
                        };
                        
                        // Initialize: Set the select box to match the saved value from database
                        element.val(setting.get());
                        
                        // Initialize step based on current unit
                        updateStep(setting.get());
                        
                        // Control → Setting: When user changes the select, update the WordPress setting
                        element.on('change', function() {
                            var newUnit = $(this).val();
                            setting.set(newUnit);
                            updateStep(newUnit);
                        });
                        
                        // Setting → Control: When setting changes (e.g., from API), update the select box
                        // This ensures the UI stays in sync with the underlying data
                        setting.bind(function(value) {
                            element.val(value);
                            updateStep(value);
                        });
                    }
                });
            });
        })(jQuery);
        ";
	}
}
