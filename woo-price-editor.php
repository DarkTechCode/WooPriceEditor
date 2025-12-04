<?php
/**
 * Plugin Name: WooCommerce Price Editor
 * Plugin URI: https://github.com/woo-price-editor
 * Description: Bulk edit WooCommerce product prices with advanced filtering, search, and inline editing
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: WooEditPrice Team
 * Author URI: https://github.com/woo-price-editor
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-price-editor
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package WooPriceEditor
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('WPE_VERSION', '1.0.0');
define('WPE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPE_MIN_PHP_VERSION', '7.4');
define('WPE_MIN_WP_VERSION', '5.8');
define('WPE_MIN_WC_VERSION', '5.0');

/**
 * Check PHP version
 *
 * @return bool
 */
function wpe_check_php_version() {
    return version_compare(PHP_VERSION, WPE_MIN_PHP_VERSION, '>=');
}

/**
 * Check WordPress version
 *
 * @return bool
 */
function wpe_check_wp_version() {
    return version_compare(get_bloginfo('version'), WPE_MIN_WP_VERSION, '>=');
}

/**
 * Check if WooCommerce is active
 *
 * @return bool
 */
function wpe_check_woocommerce() {
    return class_exists('WooCommerce');
}

/**
 * Display admin notice for missing requirements
 *
 * @param string $message Notice message
 * @return void
 */
function wpe_admin_notice_error($message) {
    printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        esc_html($message)
    );
}

/**
 * Check all requirements and display notices if needed
 *
 * @return bool
 */
function wpe_check_requirements() {
    $requirements_met = true;

    if (!wpe_check_php_version()) {
        add_action('admin_notices', function() {
            wpe_admin_notice_error(
                sprintf(
                    /* translators: %s: Required PHP version */
                    __('WooCommerce Price Editor requires PHP version %s or higher.', 'woo-price-editor'),
                    WPE_MIN_PHP_VERSION
                )
            );
        });
        $requirements_met = false;
    }

    if (!wpe_check_wp_version()) {
        add_action('admin_notices', function() {
            wpe_admin_notice_error(
                sprintf(
                    /* translators: %s: Required WordPress version */
                    __('WooCommerce Price Editor requires WordPress version %s or higher.', 'woo-price-editor'),
                    WPE_MIN_WP_VERSION
                )
            );
        });
        $requirements_met = false;
    }

    if (!wpe_check_woocommerce()) {
        add_action('admin_notices', function() {
            wpe_admin_notice_error(
                __('WooCommerce Price Editor requires WooCommerce to be installed and active.', 'woo-price-editor')
            );
        });
        $requirements_met = false;
    }

    return $requirements_met;
}

/**
 * Initialize the plugin
 *
 * @return void
 */
function wpe_init() {
    if (!wpe_check_requirements()) {
        return;
    }

    // Load text domain
    load_plugin_textdomain(
        'woo-price-editor',
        false,
        dirname(WPE_PLUGIN_BASENAME) . '/languages'
    );

    // Include required files
    require_once WPE_PLUGIN_DIR . 'includes/class-wpe-security.php';
    require_once WPE_PLUGIN_DIR . 'includes/class-wpe-product.php';
    require_once WPE_PLUGIN_DIR . 'includes/class-wpe-api.php';
    require_once WPE_PLUGIN_DIR . 'includes/class-wpe-admin.php';

    // Initialize admin
    if (is_admin()) {
        $admin = new WPE_Admin();
        $admin->init();
    }

    // Initialize REST API
    add_action('rest_api_init', function() {
        $api = new WPE_API();
        $api->register_routes();
    });
}
add_action('plugins_loaded', 'wpe_init');

/**
 * Plugin activation hook
 *
 * @return void
 */
function wpe_activate() {
    if (!wpe_check_requirements()) {
        deactivate_plugins(WPE_PLUGIN_BASENAME);
        wp_die(
            esc_html__('WooCommerce Price Editor cannot be activated. Please check the requirements.', 'woo-price-editor'),
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }

    // Set default options
    $default_options = [
        'page_length'     => 50,
        'enable_logging'  => false,
        'rate_limit'      => 100,
        'cache_duration'  => 3600,
    ];

    if (!get_option('wpe_settings')) {
        add_option('wpe_settings', $default_options);
    }

    // Clear any existing caches
    wp_cache_flush();

    // Log activation
    if (function_exists('wc_get_logger')) {
        $logger = wc_get_logger();
        $logger->info('WooCommerce Price Editor activated', ['source' => 'woo-price-editor']);
    }
}
register_activation_hook(__FILE__, 'wpe_activate');

/**
 * Plugin deactivation hook
 *
 * @return void
 */
function wpe_deactivate() {
    // Clear transients
    delete_transient('wpe_product_categories');
    delete_transient('wpe_tax_classes');

    // Log deactivation
    if (function_exists('wc_get_logger')) {
        $logger = wc_get_logger();
        $logger->info('WooCommerce Price Editor deactivated', ['source' => 'woo-price-editor']);
    }
}
register_deactivation_hook(__FILE__, 'wpe_deactivate');

/**
 * Plugin uninstall hook - defined in uninstall.php
 */
