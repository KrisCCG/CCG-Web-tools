<?php

if (!class_exists('WP_Customize_Control')) {
	return null;
}

if (class_exists('Range_With_Number_Custom_Control')) {
	return;
}

/**
 * A class to create a range slider with a synchronized number input
 */
class Range_With_Number_Custom_Control extends WP_Customize_Control {
	public $type = 'range_with_number';

	/**
	 * Input attributes for the range slider (min, max, step)
	 */
	public $input_attrs = [];

	/**
	 * Render the content of the range with number control
	 *
	 * @return HTML
	 */
	public function render_content() {
		$min = isset($this->input_attrs['min']) ? $this->input_attrs['min'] : 0;
		$max = isset($this->input_attrs['max']) ? $this->input_attrs['max'] : 100;
		$step = isset($this->input_attrs['step']) ? $this->input_attrs['step'] : 1;
		$value = $this->value();
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

            <div class="lay-range-with-number-wrapper" style="display: flex; gap: 10px; align-items: center; margin-top: 8px;">
                <input 
                    type="range" 
                    class="lay-range-slider"
                    min="<?php echo esc_attr($min); ?>" 
                    max="<?php echo esc_attr($max); ?>"
                    step="<?php echo esc_attr($step); ?>"
                    <?php $this->link(); ?> 
                    value="<?php echo esc_attr($value); ?>"
                    style="flex: 1; min-width: 0;"
                />
                <input 
                    type="number" 
                    class="lay-range-number"
                    min="<?php echo esc_attr($min); ?>" 
                    max="<?php echo esc_attr($max); ?>"
                    step="<?php echo esc_attr($step); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    style="width: 65px; text-align: center;"
                />
            </div>
        </label>
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
	 * Get the inline script for synchronizing range and number inputs
	 */
	private function get_inline_script() {
		return "
		jQuery(document).ready(function($) {
			// Find all range-with-number controls
			$('.lay-range-with-number-wrapper').each(function() {
				var wrapper = $(this);
				var rangeInput = wrapper.find('.lay-range-slider');
				var numberInput = wrapper.find('.lay-range-number');
				
				// Sync number input when range changes
				rangeInput.on('input change', function() {
					var value = $(this).val();
					numberInput.val(value);
				});
				
				// Sync range input when number changes
				numberInput.on('input change', function() {
					var value = $(this).val();
					var min = parseFloat($(this).attr('min'));
					var max = parseFloat($(this).attr('max'));
					
					// Clamp value to min/max
					if (value < min) value = min;
					if (value > max) value = max;
					
					rangeInput.val(value).trigger('change');
					$(this).val(value); // Update number input with clamped value
				});
			});
		});
		";
	}

	/**
	 * Get the inline CSS for styling the range slider
	 */
	private function get_inline_style() {
		return "
		/* Range slider styling */
		.lay-range-slider {
			-webkit-appearance: none;
			appearance: none;
			height: 6px;
			border-radius: 3px;
			background: #ddd;
			outline: none;
		}
		
		.lay-range-slider::-webkit-slider-thumb {
			-webkit-appearance: none;
			appearance: none;
			width: 16px;
			height: 16px;
			border-radius: 50%;
			background: #fff;
			cursor: default;
			border: 1px solid #8c8f94;
		}
		
		.lay-range-slider::-moz-range-thumb {
			width: 16px;
			height: 16px;
			border-radius: 50%;
			background: #fff;
			cursor: default;
			border: 1px solid #8c8f94;
		}
		
		.lay-range-slider::-moz-range-track {
			background: #ddd;
			border-radius: 3px;
			height: 6px;
		}
		
		/* Number input styling */
		.lay-range-number {
			padding: 4px 8px;
			border: 1px solid #ddd;
			border-radius: 3px;
			font-size: 13px;
		}
		
		.lay-range-number:focus {
			border-color: #999;
			outline: none;
		}
		";
	}
}

