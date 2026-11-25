<?php

/**
 * Scroll Animation presets — global, persistent across machines.
 *
 * Stored as a single WP option `lay_scroll_animation_presets` shaped:
 *   [ { "name": "Smooth fade-in", "config": { ...scrollAnimation fields... } }, ... ]
 *
 * The list is also embedded into `passedData.lay_scroll_animation_presets`
 * on initial page load (see gridder.php), so the sidebar starts populated
 * without a separate fetch.
 */
class GridderScrollAnimationPresets {

	const OPTION_KEY = 'lay_scroll_animation_presets';
	const NONCE_ACTION = 'lay_scroll_animation_presets';
	const ALLOWED_FIELDS = array(
		'enableScale', 'scaleFactor',
		'enableDirection', 'direction', 'distance',
		'enableSkew', 'skewFromX', 'skewFromY',
		'enableRotate', 'rotateFromX', 'rotateFromY', 'rotateFromZ',
		'enableFade', 'fadeFromOpacity',
		'enableBlur', 'blurFromPx',
		'enableGrayscale', 'grayscaleFromPct',
		'enableBrightness', 'brightnessFromPct',
		'enableReveal', 'revealDirection',
		'enableMarqueeScroll', 'marqueeScrollFactor', 'marqueeScrollOneDirectionOnly',
		'endAtTop', 'endAtFullExit', 'animateIndividualElements',
		'disableOnPhone',
		'enableScrub', 'top', 'duration', 'stagger',
		'easing',
	);

	public function __construct(){
		add_action('wp_ajax_lay_save_scroll_animation_preset', array($this, 'save_preset'));
		add_action('wp_ajax_lay_delete_scroll_animation_preset', array($this, 'delete_preset'));
	}

	private function ensure_can_edit(){
		if ( ! current_user_can('edit_posts') ) {
			wp_send_json_error(array('message' => 'forbidden'));
		}
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if ( ! wp_verify_nonce($nonce, self::NONCE_ACTION) ) {
			wp_send_json_error(array('message' => 'invalid_nonce'));
		}
	}

	/**
	 * Whitelist + coerce config fields. Anything not in ALLOWED_FIELDS is
	 * dropped silently — keeps the option payload small and prevents the
	 * client from stuffing arbitrary JSON into a stored option.
	 */
	private function sanitize_config($raw){
		if ( ! is_array($raw) ) {
			return array();
		}
		$out = array();
		foreach ( self::ALLOWED_FIELDS as $field ) {
			if ( ! array_key_exists($field, $raw) ) continue;
			$value = $raw[$field];
			if ( is_bool($value) ) {
				$out[$field] = $value;
			} elseif ( is_int($value) || is_float($value) ) {
				$out[$field] = $value + 0;
			} elseif ( is_string($value) ) {
				if ( $value === 'true' || $value === 'false' ) {
					$out[$field] = ($value === 'true');
				} elseif ( is_numeric($value) ) {
					$out[$field] = $value + 0;
				} else {
					$out[$field] = sanitize_text_field($value);
				}
			}
		}
		return $out;
	}

	private function get_presets(){
		$presets = get_option(self::OPTION_KEY, array());
		if ( ! is_array($presets) ) return array();
		$out = array();
		foreach ( $presets as $p ) {
			if ( is_array($p) && isset($p['name'], $p['config']) && is_array($p['config']) ) {
				$out[] = array(
					'name' => (string) $p['name'],
					'config' => $p['config'],
				);
			}
		}
		return $out;
	}

	public function save_preset(){
		$this->ensure_can_edit();

		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$name = trim($name);
		if ( $name === '' ) {
			wp_send_json_error(array('message' => 'missing_name'));
		}
		// Cap at 60 chars to keep the dropdown readable; client should mirror this.
		if ( function_exists('mb_substr') ) {
			$name = mb_substr($name, 0, 60);
		} else {
			$name = substr($name, 0, 60);
		}

		$config_raw_json = isset($_POST['config']) ? wp_unslash($_POST['config']) : '';
		$config_decoded = json_decode($config_raw_json, true);
		$config = $this->sanitize_config($config_decoded);
		if ( empty($config) ) {
			wp_send_json_error(array('message' => 'empty_config'));
		}

		$presets = $this->get_presets();
		$found = false;
		foreach ( $presets as $i => $p ) {
			if ( $p['name'] === $name ) {
				$presets[$i]['config'] = $config;
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			$presets[] = array('name' => $name, 'config' => $config);
		}

		// Hard cap so a malicious / runaway client can't bloat the options table.
		if ( count($presets) > 100 ) {
			$presets = array_slice($presets, -100);
		}

		update_option(self::OPTION_KEY, $presets);
		wp_send_json_success(array('presets' => $presets));
	}

	public function delete_preset(){
		$this->ensure_can_edit();

		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		if ( $name === '' ) {
			wp_send_json_error(array('message' => 'missing_name'));
		}

		$presets = $this->get_presets();
		$presets = array_values(array_filter($presets, function($p) use ($name) {
			return $p['name'] !== $name;
		}));
		update_option(self::OPTION_KEY, $presets);
		wp_send_json_success(array('presets' => $presets));
	}
}
new GridderScrollAnimationPresets();
