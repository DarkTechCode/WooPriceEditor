<?php
/**
 * Plugin Name: Woo Price Editor
 * Plugin URI: https://darktech.ru
 * Description: Full-screen WooCommerce price editor shell with capability gated admin entry point.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dark Wizard
 * Author URI: https://darktech.ru
 * Text Domain: woo-price-editor
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('WPE_PLUGIN_FILE', __FILE__);
define('WPE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPE_PLUGIN_VERSION', '0.1.0');

require_once WPE_PLUGIN_DIR . 'includes/class-wpe-plugin.php';

register_activation_hook(WPE_PLUGIN_FILE, ['WPE_Plugin', 'activate']);

add_action('plugins_loaded', static function() {
    WPE_Plugin::instance()->init();
});
