<?php
/**
 * Tests for AJAX endpoint permissions
 *
 * @package WooPriceEditor
 */

class Test_AJAX_Permissions extends WPE_Test_Case {
    
    /**
     * AJAX handler instance
     *
     * @var WPE_AJAX
     */
    private $ajax_handler;
    
    /**
     * Setup test
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->ajax_handler = new WPE_AJAX();
        $this->ajax_handler->register_actions();
    }
    
    /**
     * Teardown test
     */
    public function tearDown(): void {
        $this->cleanup_ajax_request();
        parent::tearDown();
    }
    
    /**
     * Test get_products requires authentication
     */
    public function test_get_products_requires_authentication() {
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        // Mock request
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_get_products', [
            'nonce' => $nonce,
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_nopriv_wpe_get_products');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail - no authentication
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('logged in', $response['message']);
    }
    
    /**
     * Test get_products requires valid nonce
     */
    public function test_get_products_requires_valid_nonce() {
        // Create user with capability
        $user_id = $this->create_test_user(true);
        wp_set_current_user($user_id);
        
        // Mock request with invalid nonce
        $this->mock_ajax_request('wpe_get_products', [
            'nonce' => 'invalid_nonce',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_get_products');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail - invalid nonce
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Security check failed', $response['message']);
    }
    
    /**
     * Test get_products requires manage_woocommerce capability
     */
    public function test_get_products_requires_capability() {
        // Create user WITHOUT capability
        $user_id = $this->create_test_user(false);
        wp_set_current_user($user_id);
        
        // Mock request with valid nonce
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_get_products', [
            'nonce' => $nonce,
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_get_products');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail - no capability
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('permission', strtolower($response['message']));
    }
    
    /**
     * Test get_categories requires authentication
     */
    public function test_get_categories_requires_authentication() {
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        // Mock request
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_get_categories', [
            'nonce' => $nonce,
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_nopriv_wpe_get_categories');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test get_categories requires capability
     */
    public function test_get_categories_requires_capability() {
        // Create user WITHOUT capability
        $user_id = $this->create_test_user(false);
        wp_set_current_user($user_id);
        
        // Mock request with valid nonce
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_get_categories', [
            'nonce' => $nonce,
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_get_categories');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test update_product requires authentication
     */
    public function test_update_product_requires_authentication() {
        // Create a test product
        $product_id = $this->create_test_product();
        
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        // Mock request
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_update_product', [
            'nonce'      => $nonce,
            'product_id' => $product_id,
            'field'      => 'regular_price',
            'value'      => '20.00',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_nopriv_wpe_update_product');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test update_product requires valid nonce
     */
    public function test_update_product_requires_valid_nonce() {
        // Create user with capability
        $user_id = $this->create_test_user(true);
        wp_set_current_user($user_id);
        
        // Create a test product
        $product_id = $this->create_test_product();
        
        // Mock request with invalid nonce
        $this->mock_ajax_request('wpe_update_product', [
            'nonce'      => 'invalid_nonce',
            'product_id' => $product_id,
            'field'      => 'regular_price',
            'value'      => '20.00',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_update_product');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test update_product requires capability
     */
    public function test_update_product_requires_capability() {
        // Create user WITHOUT capability
        $user_id = $this->create_test_user(false);
        wp_set_current_user($user_id);
        
        // Create a test product
        $product_id = $this->create_test_product();
        
        // Mock request with valid nonce
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_update_product', [
            'nonce'      => $nonce,
            'product_id' => $product_id,
            'field'      => 'regular_price',
            'value'      => '20.00',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_update_product');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test update_product validates product_id is required
     */
    public function test_update_product_requires_product_id() {
        // Create user with capability
        $user_id = $this->create_test_user(true);
        wp_set_current_user($user_id);
        
        // Mock request without product_id
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_update_product', [
            'nonce' => $nonce,
            'field' => 'regular_price',
            'value' => '20.00',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_update_product');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Product ID', $response['message']);
    }
    
    /**
     * Test update_product validates field is required
     */
    public function test_update_product_requires_field() {
        // Create user with capability
        $user_id = $this->create_test_user(true);
        wp_set_current_user($user_id);
        
        // Create a test product
        $product_id = $this->create_test_product();
        
        // Mock request without field
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_update_product', [
            'nonce'      => $nonce,
            'product_id' => $product_id,
            'value'      => '20.00',
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_wpe_update_product');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Field', $response['message']);
    }
    
    /**
     * Test get_tax_classes requires authentication
     */
    public function test_get_tax_classes_requires_authentication() {
        // Ensure no user is logged in
        wp_set_current_user(0);
        
        // Mock request
        $nonce = wp_create_nonce('wp_rest');
        $this->mock_ajax_request('wpe_get_tax_classes', [
            'nonce' => $nonce,
        ]);
        
        // Capture output
        ob_start();
        try {
            do_action('wp_ajax_nopriv_wpe_get_tax_classes');
        } catch (WPExit $e) {
            // Expected to exit
        }
        $response = $this->get_ajax_response();
        
        // Should fail
        $this->assertNotNull($response);
        $this->assertFalse($response['success']);
    }
    
    /**
     * Test all endpoints use consistent error responses
     */
    public function test_consistent_error_response_format() {
        // Test each endpoint returns consistent error format
        $endpoints = [
            'wpe_get_products',
            'wpe_get_categories',
            'wpe_get_tax_classes',
        ];
        
        foreach ($endpoints as $endpoint) {
            // No user logged in
            wp_set_current_user(0);
            
            // Mock request
            $nonce = wp_create_nonce('wp_rest');
            $this->mock_ajax_request($endpoint, ['nonce' => $nonce]);
            
            // Capture output
            ob_start();
            try {
                do_action("wp_ajax_nopriv_{$endpoint}");
            } catch (WPExit $e) {
                // Expected
            }
            $response = $this->get_ajax_response();
            
            // Check consistent format
            $this->assertIsArray($response, "Endpoint {$endpoint} should return array");
            $this->assertArrayHasKey('success', $response, "Endpoint {$endpoint} should have 'success' key");
            $this->assertFalse($response['success'], "Endpoint {$endpoint} should fail without auth");
            $this->assertArrayHasKey('message', $response, "Endpoint {$endpoint} should have 'message' key");
            
            // Cleanup for next iteration
            $this->cleanup_ajax_request();
        }
    }
}
