<?php
/**
 * Emergency Cache Clear Script
 * 
 * Upload this file to your theme directory (wp-content/themes/lay/)
 * Then visit it directly in your browser with the secret key:
 * 
 * https://yoursite.com/wp-content/themes/lay/clear_cache_emergency.php?key=Kx9mP2nQ7vB4
 * 
 * On localhost, it works without the key.
 * On production, you MUST add ?key=Kx9mP2nQ7vB4 to the URL
 * 
 * This will clear all layout cache if your site is broken or extremely slow.
 */

// Security check - require secret key in URL
$allowed = false;

// Set your secret key here (change this to something unique!)
$SECRET_KEY = 'Kx9mP2nQ7vB4';

// Allow on localhost
if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
    $allowed = true;
}

// Or visit with secret key: clear_cache_emergency.php?key=Kx9mP2nQ7vB4
if (isset($_GET['key']) && $_GET['key'] === $SECRET_KEY) {
    $allowed = true;
}

if (!$allowed) {
    die('Access denied. Add ?key=' . $SECRET_KEY . ' to the URL or run from localhost.');
}

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

echo '<h1>Emergency Cache Clear</h1>';
echo '<p>Clearing all Lay Layout Cache...</p>';

// Delete all transients that start with 'lay_layout_'
$result1 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lay_layout_%'");
$result2 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lay_layout_%'");

echo '<p>✓ Deleted ' . ($result1 + $result2) . ' cache entries</p>';

// Clear master cache
delete_transient('lay_layout_master_cache');
delete_transient('lay_layout_master_cache_metadata');

echo '<p>✓ Cleared master cache metadata</p>';

// Clear any chunked master cache entries
$result3 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lay_layout_master_cache_%'");
$result4 = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lay_layout_master_cache_%'");

echo '<p>✓ Deleted ' . ($result3 + $result4) . ' master cache chunks</p>';

echo '<h2>Cache Cleared Successfully!</h2>';
echo '<p>You can now access your website again.</p>';
echo '<p><a href="' . home_url() . '">Go to Homepage</a></p>';
echo '<hr>';
echo '<h3>Security Note</h3>';
echo '<p>For security, you can:</p>';
echo '<ul>';
echo '<li>Delete this file after use: <code>wp-content/themes/lay/clear_cache_emergency.php</code></li>';
echo '<li>Or change the secret key in the file (line 13)</li>';
echo '</ul>';
?>

