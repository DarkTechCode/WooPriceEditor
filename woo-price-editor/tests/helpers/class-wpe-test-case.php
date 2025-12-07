<?php
/**
 * Base test case class for Woo Price Editor tests
 *
 * @package WooPriceEditor
 */

class WPE_Test_Case extends WP_UnitTestCase {
    /**
     * Setup test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Reset options before each test
        delete_option('wpe_editor_settings');
    }
    
    /**
     * Teardown test environment
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Clean up
        delete_option('wpe_editor_settings');
    }
    
    /**
     * Create a test user with specific capability
     *
     * @param bool $has_capability Whether user should have manage_woocommerce capability
     * @return int User ID
     */
    protected function create_test_user($has_capability = true) {
        $role = $has_capability ? 'shop_manager' : 'subscriber';
        $user_id = $this->factory->user->create(['role' => $role]);
        
        // Ensure shop_manager has manage_woocommerce capability
        if ($has_capability) {
            $user = new WP_User($user_id);
            $user->add_cap('manage_woocommerce');
        }
        
        return $user_id;
    }
    
    /**
     * Create a test product
     *
     * @param array $args Product arguments
     * @return int|WP_Error Product ID or error
     */
    protected function create_test_product($args = []) {
        $defaults = [
            'post_title'   => 'Test Product',
            'post_type'    => 'product',
            'post_status'  => 'publish',
            'post_content' => 'Test product description',
        ];
        
        $args = wp_parse_args($args, $defaults);
        $product_id = wp_insert_post($args);
        
        if (!is_wp_error($product_id)) {
            // Set default product meta
            update_post_meta($product_id, '_sku', 'TEST-SKU-' . $product_id);
            update_post_meta($product_id, '_regular_price', '10.00');
            update_post_meta($product_id, '_price', '10.00');
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, '_tax_status', 'taxable');
            update_post_meta($product_id, '_tax_class', '');
        }
        
        return $product_id;
    }
    
    /**
     * Mock an AJAX request
     *
     * @param string $action AJAX action name
     * @param array  $params Request parameters
     */
    protected function mock_ajax_request($action, $params = []) {
        $_POST['action'] = $action;
        foreach ($params as $key => $value) {
            $_POST[$key] = $_REQUEST[$key] = $value;
        }
    }
    
    /**
     * Clean up AJAX request
     */
    protected function cleanup_ajax_request() {
        $_POST = [];
        $_REQUEST = [];
        $_GET = [];
    }
    
    /**
     * Get last response from output buffer
     *
     * @return array|null Decoded JSON response or null
     */
    protected function get_ajax_response() {
        $output = ob_get_clean();
        return json_decode($output, true);
    }
}
