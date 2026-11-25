<?php

class LayAskAI {
	
	public function __construct() {
		// Only add for admin users
		if (is_admin()) {
			add_action('admin_footer', [$this, 'add_ask_ai_panel']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
			add_action('wp_ajax_save_ask_ai_position', [$this, 'save_ask_ai_position']);
			add_action('wp_ajax_disable_ask_ai', [$this, 'disable_ask_ai']);
		}
	}

	public function enqueue_scripts($hook) {
		// Only load on pages where we want the AI panel
		$floating_js_path = Setup::$dir . '/assets/js/admin-floating-pill.js';
		$floating_css_path = Setup::$dir . '/assets/css/admin-floating-pill.css';
		$js_path = Setup::$dir . '/ai/assets/ask_ai.js';
		$css_path = Setup::$dir . '/ai/assets/ask_ai.css';
		$floating_js_ver = file_exists($floating_js_path) ? (string) filemtime($floating_js_path) : Setup::$ver;
		$floating_css_ver = file_exists($floating_css_path) ? (string) filemtime($floating_css_path) : Setup::$ver;
		$js_ver = file_exists($js_path) ? (string) filemtime($js_path) : Setup::$ver;
		$css_ver = file_exists($css_path) ? (string) filemtime($css_path) : Setup::$ver;

		wp_enqueue_script(
			'lay-admin-floating-pill',
			Setup::$uri . '/assets/js/admin-floating-pill.js',
			[],
			$floating_js_ver,
			true
		);

		wp_enqueue_script(
			'lay-ask-ai',
			Setup::$uri . '/ai/assets/ask_ai.js',
			['jquery', 'lay-admin-floating-pill'],
			$js_ver,
			true
		);

		wp_localize_script(
			'lay-ask-ai',
			'layAskAI',
			[
				'nonce' => wp_create_nonce('lay_ask_ai'),
				'ajaxUrl' => admin_url('admin-ajax.php'),
			]
		);

		wp_enqueue_style(
			'lay-admin-floating-pill',
			Setup::$uri . '/assets/css/admin-floating-pill.css',
			[],
			$floating_css_ver
		);
		
		wp_enqueue_style(
			'lay-ask-ai-style',
			Setup::$uri . '/ai/assets/ask_ai.css',
			['lay-admin-floating-pill'],
			$css_ver
		);
	}

	public function save_ask_ai_position() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized', 403);
		}
		check_ajax_referer('lay_ask_ai', 'nonce');

		$position = [
			'bottom' => isset($_POST['bottom']) ? intval($_POST['bottom']) : 20,
			'right' => isset($_POST['right']) ? intval($_POST['right']) : 20
		];

		update_option('lay_ask_ai_position', $position);
		wp_send_json_success(['position' => $position]);
	}

	public function disable_ask_ai() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized', 403);
		}
		check_ajax_referer('lay_ask_ai', 'nonce');
		update_option('misc_options_activate_ask_ai', 'off');
		wp_send_json_success(['enabled' => false]);
	}

	public function add_ask_ai_panel() {
		// Get saved position or use defaults
		$position = get_option('lay_ask_ai_position', ['bottom' => 20, 'right' => 20]);
		// Get current screen to determine context
		$screen = get_current_screen();
		
		echo '<div id="lay-ask-ai-button" class="lay-admin-floating-pill" data-bottom="' . esc_attr($position['bottom']) . '" data-right="' . esc_attr($position['right']) . '" style="bottom: ' . esc_attr($position['bottom']) . 'px; right: ' . esc_attr($position['right']) . 'px;">
			<span class="lay-admin-floating-pill-drag-handle lay-ask-ai-drag-handle drag-icon"></span>
			<span class="lay-ask-ai-button-text">Ask AI</span>
			<button type="button" class="lay-admin-floating-pill-dismiss lay-ask-ai-disable-button" aria-label="Disable Ask AI">
				<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M18 6 6 18"></path>
					<path d="m6 6 12 12"></path>
				</svg>
			</button>
		</div>';
		
		echo '<div id="lay-ask-ai-panel" class="lay-ask-ai-panel">
			<div class="lay-ask-ai-content">
				<div class="lay-ask-ai-header">
					<a href="' . admin_url('admin.php?page=manage-layoptions#misc_options_activate_ask_ai') . '" class="lay-ask-ai-disable-link">disable here</a>
					<button type="button" class="lay-ask-ai-close">&times;</button>
				</div>
				<div class="lay-ask-ai-body">
					<iframe id="lay-ask-ai-iframe" data-src="https://lt-chatbot-production.up.railway.app/chatbot.html" frameborder="0"></iframe>
				</div>
			</div>
			<div class="lay-ask-ai-overlay"></div>
		</div>';
	}
}

new LayAskAI();
