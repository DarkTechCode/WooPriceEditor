<?php
/**
 * PHPUnit bootstrap file for Woo Price Editor tests
 *
 * @package WooPriceEditor
 */

// Define test environment
define('WPE_TESTS_DIR', __DIR__);
define('WPE_PLUGIN_DIR', dirname(__DIR__) . '/');

// Composer autoload if available
if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

// Load WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    echo "Please set the WP_TESTS_DIR environment variable to the WordPress tests directory.\n";
    echo "See tests/README.md for more information.\n";
    exit(1);
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    // Load WooCommerce first if available
    if (file_exists(dirname(__DIR__, 2) . '/woocommerce/woocommerce.php')) {
        require dirname(__DIR__, 2) . '/woocommerce/woocommerce.php';
    }
    
    // Load plugin
    require WPE_PLUGIN_DIR . 'woo-price-editor.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Include test helpers
require_once WPE_TESTS_DIR . '/helpers/class-wpe-test-case.php';
