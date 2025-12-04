<?php
/**
 * AJAX handler class
 *
 * Provides AJAX endpoints for the price editor with proper security,
 * validation, and error handling.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPE_AJAX
 *
 * Handles AJAX requests for product operations
 */
class WPE_AJAX {

    /**
     * Product handler instance
     *
     * @var WPE_Product
     */
    private $product_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->product_handler = new WPE_Product();
    }

    /**
     * Register AJAX actions
     *
     * @return void
     */
    public function register_actions() {
        // Public actions (authenticated users)
        add_action('wp_ajax_wpe_get_categories', [$this, 'ajax_get_categories']);
        add_action('wp_ajax_wpe_get_tax_classes', [$this, 'ajax_get_tax_classes']);
        add_action('wp_ajax_wpe_get_products', [$this, 'ajax_get_products']);
        add_action('wp_ajax_wpe_update_product', [$this, 'ajax_update_product']);
    }

    /**
     * Check nonce and permissions
     *
     * @return bool|WP_Error True if valid, WP_Error if not
     */
    private function verify_request() {
        // Check authentication
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_authenticated',
                __('You must be logged in to access this endpoint.', 'woo-price-editor'),
                ['status' => 401]
            );
        }

        // Check nonce (used for both REST API and AJAX calls)
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed.', 'woo-price-editor'),
                ['status' => 403]
            );
        }

        // Check capability
        if (!current_user_can('manage_woocommerce')) {
            return new WP_Error(
                'forbidden',
                __('You do not have permission to manage products.', 'woo-price-editor'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Send JSON response
     *
     * @param mixed $data Response data
     * @param bool  $success Whether the request was successful
     * @param int   $status HTTP status code
     * @return void
     */
    private function send_response($data, $success = true, $status = 200) {
        if ($data instanceof WP_Error) {
            $response = [
                'success' => false,
                'message' => $data->get_error_message(),
                'data'    => $data->get_error_data(),
            ];
            $status = isset($data->get_error_data()['status']) ? $data->get_error_data()['status'] : 500;
        } else {
            $response = [
                'success' => $success,
            ];
            
            // If data has a message, include it at top level
            if (is_array($data) && isset($data['message'])) {
                $response['message'] = $data['message'];
            }
            
            $response['data'] = $data;
        }

        wp_send_json($response, $status);
    }

    /**
     * AJAX: Get categories
     *
     * @return void
     */
    public function ajax_get_categories() {
        $verify = $this->verify_request();
        if (is_wp_error($verify)) {
            $this->send_response($verify);
        }

        try {
            $categories = $this->product_handler->get_categories();
            $this->send_response($categories, true, 200);
        } catch (Exception $e) {
            WPE_Security::log_event('ajax_error', [
                'action' => 'get_categories',
                'error'  => $e->getMessage(),
            ]);
            $this->send_response(
                new WP_Error('error', $e->getMessage()),
                false,
                500
            );
        }
    }

    /**
     * AJAX: Get tax classes
     *
     * @return void
     */
    public function ajax_get_tax_classes() {
        $verify = $this->verify_request();
        if (is_wp_error($verify)) {
            $this->send_response($verify);
        }

        try {
            $tax_classes = $this->product_handler->get_tax_classes();
            $this->send_response($tax_classes, true, 200);
        } catch (Exception $e) {
            WPE_Security::log_event('ajax_error', [
                'action' => 'get_tax_classes',
                'error'  => $e->getMessage(),
            ]);
            $this->send_response(
                new WP_Error('error', $e->getMessage()),
                false,
                500
            );
        }
    }

    /**
     * AJAX: Get products
     *
     * @return void
     */
    public function ajax_get_products() {
        $verify = $this->verify_request();
        if (is_wp_error($verify)) {
            $this->send_response($verify);
        }

        try {
            // Sanitize parameters
            $params = [
                'page'         => isset($_REQUEST['page']) ? absint(wp_unslash($_REQUEST['page'])) : 1,
                'per_page'     => isset($_REQUEST['per_page']) ? absint(wp_unslash($_REQUEST['per_page'])) : 50,
                'status'       => isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : '',
                'category'     => isset($_REQUEST['category']) ? sanitize_text_field(wp_unslash($_REQUEST['category'])) : '',
                'search'       => isset($_REQUEST['search']) ? sanitize_text_field(wp_unslash($_REQUEST['search'])) : '',
                'tax_status'   => isset($_REQUEST['tax_status']) ? sanitize_text_field(wp_unslash($_REQUEST['tax_status'])) : '',
                'stock_status' => isset($_REQUEST['stock_status']) ? sanitize_text_field(wp_unslash($_REQUEST['stock_status'])) : '',
                'orderby'      => isset($_REQUEST['orderby']) ? sanitize_text_field(wp_unslash($_REQUEST['orderby'])) : 'ID',
                'order'        => isset($_REQUEST['order']) ? sanitize_text_field(wp_unslash($_REQUEST['order'])) : 'DESC',
            ];

            $result = $this->product_handler->get_products($params);
            $this->send_response($result, true, 200);
        } catch (Exception $e) {
            WPE_Security::log_event('ajax_error', [
                'action' => 'get_products',
                'error'  => $e->getMessage(),
            ]);
            $this->send_response(
                new WP_Error('error', $e->getMessage()),
                false,
                500
            );
        }
    }

    /**
     * AJAX: Update product
     *
     * @return void
     */
    public function ajax_update_product() {
        $verify = $this->verify_request();
        if (is_wp_error($verify)) {
            $this->send_response($verify);
        }

        try {
            // Get and validate product ID
            $product_id = isset($_REQUEST['product_id']) ? absint(wp_unslash($_REQUEST['product_id'])) : 0;
            if (!$product_id) {
                throw new InvalidArgumentException(__('Product ID is required.', 'woo-price-editor'));
            }

            // Check permission for this specific product
            if (!WPE_Security::can_edit_product($product_id)) {
                $this->send_response(
                    new WP_Error(
                        'forbidden',
                        __('You do not have permission to edit this product.', 'woo-price-editor'),
                        ['status' => 403]
                    )
                );
                return;
            }

            // Get and validate field
            $field = isset($_REQUEST['field']) ? sanitize_text_field(wp_unslash($_REQUEST['field'])) : '';
            if (!$field) {
                throw new InvalidArgumentException(__('Field is required.', 'woo-price-editor'));
            }

            // Get value
            $value = isset($_REQUEST['value']) ? wp_unslash($_REQUEST['value']) : '';

            // Sanitize the value based on field type
            try {
                $value = WPE_Security::sanitize_field($field, $value);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage());
            }

            // Update the product
            $result = $this->product_handler->update_field($product_id, $field, $value);

            if (!$result['success']) {
                $this->send_response(
                    new WP_Error('error', $result['message']),
                    false,
                    400
                );
                return;
            }

            $this->send_response($result, true, 200);
        } catch (InvalidArgumentException $e) {
            $this->send_response(
                new WP_Error('invalid_argument', $e->getMessage()),
                false,
                400
            );
        } catch (Exception $e) {
            WPE_Security::log_event('ajax_error', [
                'action'      => 'update_product',
                'product_id'  => isset($_REQUEST['product_id']) ? absint(wp_unslash($_REQUEST['product_id'])) : 0,
                'field'       => isset($_REQUEST['field']) ? sanitize_text_field(wp_unslash($_REQUEST['field'])) : '',
                'error'       => $e->getMessage(),
            ]);
            $this->send_response(
                new WP_Error('error', $e->getMessage()),
                false,
                500
            );
        }
    }
}
