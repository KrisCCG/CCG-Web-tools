<?php

/**
 * Custom Preview System for LayTheme Gridder
 * 
 * Replaces WordPress's default preview button with a custom one that:
 * - Saves gridder data to temporary preview meta fields
 * - Opens preview in new tab with custom preview parameter
 * - Works consistently without WordPress's autosave quirks
 */

class Gridder_Custom_Preview {

	public function __construct() {
		// Hide WordPress's default preview button and add our custom one
		add_action('admin_head-post.php', array($this, 'custom_preview_admin_scripts'));
		add_action('admin_head-post-new.php', array($this, 'custom_preview_admin_scripts'));
		
		// Handle the AJAX save request from our preview button
		add_action('wp_ajax_gridder_save_preview', array($this, 'ajax_save_preview'));
		
		// Hook early to detect preview mode before WordPress loads the post
		add_action('init', array($this, 'handle_preview_display'), 1);
	}

	/**
	 * Add custom scripts to admin to replace preview button
	 */
	public function custom_preview_admin_scripts() {
		global $post;
		if (!$post) return;
		
		// Only add for post types that have gridder
		$allowed_types = array('post', 'page', 'product', 'lay_news');
		if ( get_option('misc_options_activate_deck_feature', '') === 'on' && post_type_exists('lay_deck') ) {
			$allowed_types[] = 'lay_deck';
		}
		if (!in_array($post->post_type, $allowed_types)) return;
		
		// Get the frontend URL for this post
		$post_url = get_permalink($post->ID);
		
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var previewAjaxUrl = (typeof ajaxurl !== 'undefined' && ajaxurl) ? ajaxurl : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
			var gzipAllowed = $('#gridder_json_gzip_supported').val() === '1';
			var hasPako = typeof window.pako !== 'undefined';
			function uint8ToBase64(bytes) {
				var chunkSize = 0x8000;
				var binary = '';
				for (var i = 0; i < bytes.length; i += chunkSize) {
					var chunk = bytes.subarray(i, i + chunkSize);
					binary += String.fromCharCode.apply(null, Array.from(chunk));
				}
				return window.btoa(binary);
			}

			function gzipToBase64(text) {
				if (!gzipAllowed || !hasPako) {
					return null;
				}
				try {
					var compressed = window.pako.gzip(text);
					return uint8ToBase64(compressed);
				} catch (e) {
					return null;
				}
			}

			// Hide WordPress's default preview button
			$('#preview-action').hide();
			
			// Add our custom preview button
			var customPreviewBtn = $('<div id="custom-preview-action">' +
				'<a class="preview button" href="#" id="custom-post-preview">' +
				'Preview Changes' +
				'</a>' +
				'</div>');
			
			// Insert after the save draft button area
			$('#minor-publishing-actions').prepend(customPreviewBtn);
			
			// Handle preview button click
			$('#custom-post-preview').on('click', function(e) {
				e.preventDefault();
				
				var $btn = $(this);
				var originalText = $btn.text();
				$btn.text('Saving...').addClass('disabled');
				
				// Get the gridder JSON data from the textareas
				var desktopJson = $('#gridder_json_field').val() || '';
				var phoneJson = $('#phone_gridder_json_field').val() || '';
				var postId = $('#post_ID').val();
				var desktopGzip = gzipToBase64(desktopJson);
				var phoneGzip = phoneJson ? gzipToBase64(phoneJson) : null;
				var sendRaw = !(gzipAllowed && hasPako && desktopGzip);
				if (!sendRaw) {
					desktopJson = '';
					phoneJson = '';
				}
				// Save via AJAX
				$.ajax({
					url: previewAjaxUrl,
					type: 'POST',
					data: {
						action: 'gridder_save_preview',
						post_id: postId,
						desktop_json: desktopJson,
						phone_json: phoneJson,
						desktop_json_gzip: desktopGzip || '',
						phone_json_gzip: phoneGzip || '',
						nonce: '<?php echo wp_create_nonce('gridder_preview_save'); ?>'
					},
					success: function(response) {
						if (response.success) {
							// Use the frontend post URL (passed from PHP)
							var postUrl = '<?php echo esc_js($post_url); ?>';
							
							// Build preview URL with all necessary parameters
							var previewUrl = postUrl + 
								(postUrl.indexOf('?') > -1 ? '&' : '?') +
								'lay_preview=true' +
								'&preview_post_id=' + postId + 
								'&preview_nonce=' + response.data.preview_nonce + 
								'&t=' + Date.now(); // Cache buster
							
							// Open or reload preview tab
							var previewWindow = window.open('', 'lay-preview-' + postId);
							previewWindow.location.href = previewUrl;
							previewWindow.focus();
							
							$btn.text(originalText).removeClass('disabled');
						} else {
							var message = (response && response.data) ? response.data : 'Unknown error';
							alert('Preview save failed: ' + message);
							$btn.text(originalText).removeClass('disabled');
						}
					},
					error: function(xhr) {
						var details = '';
						if (xhr && xhr.status) {
							details = ' (HTTP ' + xhr.status + ')';
						}
						alert('Preview save failed. Please try again.' + details);
						$btn.text(originalText).removeClass('disabled');
					}
				});
			});
		});
		</script>
		
		<style>
		#custom-preview-action .button.disabled {
			opacity: 0.5;
			pointer-events: none;
		}
		</style>
		<?php
	}

	/**
	 * AJAX handler to save preview data
	 */
	public function ajax_save_preview() {
		// Verify nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gridder_preview_save')) {
			wp_send_json_error('Invalid nonce');
			return;
		}
		
		// Get and validate post ID
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json_error('Invalid post ID');
			return;
		}
		
		// Check permissions
		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json_error('Permission denied');
			return;
		}
		
		// Get the JSON data
	$desktop_json_raw = isset($_POST['desktop_json']) ? $_POST['desktop_json'] : '';
	$phone_json_raw = isset($_POST['phone_json']) ? $_POST['phone_json'] : '';
	$desktop_json_gzip = isset($_POST['desktop_json_gzip']) ? $_POST['desktop_json_gzip'] : '';
	$phone_json_gzip = isset($_POST['phone_json_gzip']) ? $_POST['phone_json_gzip'] : '';

	$desktop_json = $this->normalize_preview_json($desktop_json_raw, $desktop_json_gzip);
	$phone_json = $this->normalize_preview_json($phone_json_raw, $phone_json_gzip);

	if (($desktop_json_raw !== '' || $desktop_json_gzip !== '') && $desktop_json === '') {
			wp_send_json_error('Invalid desktop JSON');
			return;
		}
	if (($phone_json_raw !== '' || $phone_json_gzip !== '') && $phone_json === '') {
			wp_send_json_error('Invalid phone JSON');
			return;
		}
		
		// Save to preview meta fields
		update_post_meta($post_id, '_gridder_json_preview', wp_slash($desktop_json));
		update_post_meta($post_id, '_phone_gridder_json_preview', wp_slash($phone_json));
		
		// Generate a preview nonce for security
		$preview_nonce = wp_create_nonce('lay_preview_' . $post_id);
		
		// Store preview timestamp
		update_post_meta($post_id, '_gridder_preview_timestamp', time());
		
		wp_send_json_success(array(
			'preview_nonce' => $preview_nonce,
			'post_id' => $post_id
		));
	}

	private function decode_preview_gzip($value) {
		if (!is_string($value) || $value === '') {
			return '';
		}
		if (!function_exists('gzdecode')) {
			return '';
		}
		$decoded = base64_decode($value, true);
		if ($decoded === false) {
			return '';
		}
		$inflated = gzdecode($decoded);
		if ($inflated === false) {
			return '';
		}
		return $inflated;
	}

	private function normalize_preview_json($json, $gzip) {
		$decoded_gzip = $this->decode_preview_gzip($gzip);
		if ($decoded_gzip !== '') {
			$json = $decoded_gzip;
		}
		if (!is_string($json) || $json === '') {
			return '';
		}
		$decoded = json_decode($json, true);
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			$unslashed = wp_unslash($json);
			$decoded = json_decode($unslashed, true);
		}
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			$slashed = wp_slash($json);
			$decoded = json_decode($slashed, true);
		}
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			return '';
		}
		return wp_json_encode($decoded, JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Handle preview display on frontend
	 */
	public function handle_preview_display() {
		// Check if this is a preview request
		if (!isset($_GET['lay_preview']) || $_GET['lay_preview'] !== 'true') {
			return;
		}
		
		// Get post ID from preview_post_id parameter (required)
		$post_id = isset($_GET['preview_post_id']) ? absint($_GET['preview_post_id']) : 0;
		
		if (!$post_id) {
			// Try to get from 'p' parameter
			$post_id = isset($_GET['p']) ? absint($_GET['p']) : 0;
		}
		
		// If we still don't have a post ID, we can't show preview
		// (We'll get it later from the URL path when WordPress parses the request)
		if (!$post_id) {
			// Store that we're in preview mode, but don't know the ID yet
			// We'll detect it in layout.php from the current post being loaded
			define('LAY_CUSTOM_PREVIEW_MODE', true);
			define('LAY_CUSTOM_PREVIEW_DETECT_ID', true);
			return;
		}
		
		// Verify preview nonce
		$nonce = isset($_GET['preview_nonce']) ? $_GET['preview_nonce'] : '';
		if (!wp_verify_nonce($nonce, 'lay_preview_' . $post_id)) {
			// Nonce might be for the post we'll detect later, so store it
			define('LAY_CUSTOM_PREVIEW_MODE', true);
			define('LAY_CUSTOM_PREVIEW_POST_ID', $post_id);
			define('LAY_CUSTOM_PREVIEW_NONCE', $nonce);
			return;
		}
		
		// Check if user has permission (logged in and can edit this post)
		if (!is_user_logged_in() || !current_user_can('edit_post', $post_id)) {
			wp_die('You do not have permission to view this preview.');
		}
		
		// Set a global flag that we're in preview mode
		define('LAY_CUSTOM_PREVIEW_MODE', true);
		define('LAY_CUSTOM_PREVIEW_POST_ID', $post_id);
		define('LAY_CUSTOM_PREVIEW_VERIFIED', true);
		
		// Continue with normal WordPress template loading
		// The layout.php will check for these constants
	}

	/**
	 * Check if we're currently in custom preview mode
	 */
	public static function is_custom_preview() {
		return defined('LAY_CUSTOM_PREVIEW_MODE') && LAY_CUSTOM_PREVIEW_MODE === true;
	}

	/**
	 * Get the preview post ID if in preview mode
	 */
	public static function get_preview_post_id() {
		// If we already have the post ID, return it
		if (defined('LAY_CUSTOM_PREVIEW_POST_ID')) {
			return LAY_CUSTOM_PREVIEW_POST_ID;
		}
		
		// If we're in detect mode, try to get from URL parameter
		if (defined('LAY_CUSTOM_PREVIEW_DETECT_ID') && isset($_GET['preview_post_id'])) {
			$post_id = absint($_GET['preview_post_id']);
			if ($post_id) {
				// Store it for future calls
				if (!defined('LAY_CUSTOM_PREVIEW_POST_ID')) {
					define('LAY_CUSTOM_PREVIEW_POST_ID', $post_id);
				}
				return $post_id;
			}
		}
		
		return false;
	}
}

// Initialize
new Gridder_Custom_Preview();

