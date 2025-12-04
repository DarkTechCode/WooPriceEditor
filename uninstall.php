<?php
/**
 * Uninstall script
 *
 * Runs when the plugin is deleted (not just deactivated).
 * Cleans up all plugin data from the database.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Exit if uninstall is not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('wpe_settings');

// Delete all transients
delete_transient('wpe_product_categories');
delete_transient('wpe_tax_classes');

// Delete rate limit transients for all users
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_wpe_rate_%'
    )
);
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_timeout_wpe_rate_%'
    )
);

// Clear any cached data
wp_cache_flush();
