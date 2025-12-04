<?php
/**
 * REST API class
 *
 * Provides REST API endpoints for the price editor
 * with proper security, validation, and error handling.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPE_API
 *
 * Handles REST API endpoints for product operations
 */
class WPE_API {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'woo-price-editor/v1';

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
     * Register REST routes
     *
     * @return void
     */
    public function register_routes() {
        // Get products
        register_rest_route($this->namespace, '/products', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_products'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->get_products_args(),
        ]);

        // Update single product
        register_rest_route($this->namespace, '/products/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [$this, 'update_product'],
            'permission_callback' => [$this, 'check_edit_permission'],
            'args'                => $this->get_update_args(),
        ]);

        // Bulk update products
        register_rest_route($this->namespace, '/products/bulk', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [$this, 'bulk_update_products'],
            'permission_callback' => [$this, 'check_permission'],
            'args'                => $this->get_bulk_update_args(),
        ]);

        // Get categories
        register_rest_route($this->namespace, '/categories', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_categories'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Get tax classes
        register_rest_route($this->namespace, '/tax-classes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_tax_classes'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Check if user has permission to access API
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_permission($request) {
        // Check authentication
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to access this endpoint.', 'woo-price-editor'),
                ['status' => 401]
            );
        }

        // Check capability
        if (!WPE_Security::can_manage_products()) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to manage products.', 'woo-price-editor'),
                ['status' => 403]
            );
        }

        // Check rate limit
        if (!WPE_Security::check_rate_limit()) {
            return new WP_Error(
                'rest_rate_limit',
                __('Rate limit exceeded. Please try again later.', 'woo-price-editor'),
                ['status' => 429]
            );
        }

        return true;
    }

    /**
     * Check if user can edit specific product
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_edit_permission($request) {
        $base_check = $this->check_permission($request);

        if (is_wp_error($base_check)) {
            return $base_check;
        }

        $product_id = $request->get_param('id');

        if (!WPE_Security::can_edit_product($product_id)) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to edit this product.', 'woo-price-editor'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Get products endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_products($request) {
        try {
            $params = [
                'page'         => $request->get_param('page'),
                'per_page'     => $request->get_param('per_page'),
                'status'       => $request->get_param('status'),
                'category'     => $request->get_param('category'),
                'search'       => $request->get_param('search'),
                'tax_status'   => $request->get_param('tax_status'),
                'stock_status' => $request->get_param('stock_status'),
                'orderby'      => $request->get_param('orderby'),
                'order'        => $request->get_param('order'),
            ];

            $result = $this->product_handler->get_products($params);

            $response = new WP_REST_Response($result, 200);

            // Add pagination headers
            $response->header('X-WP-Total', $result['total']);
            $response->header('X-WP-TotalPages', $result['pages']);

            return $response;

        } catch (Exception $e) {
            WPE_Security::log_event('api_error', [
                'endpoint' => 'get_products',
                'error'    => $e->getMessage(),
            ]);

            return new WP_Error(
                'wpe_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Update product endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_product($request) {
        try {
            $product_id = $request->get_param('id');
            $field = $request->get_param('field');
            $value = $request->get_param('value');

            $result = $this->product_handler->update_field($product_id, $field, $value);

            if (!$result['success']) {
                return new WP_Error(
                    'wpe_update_failed',
                    $result['message'],
                    ['status' => 400]
                );
            }

            return new WP_REST_Response($result, 200);

        } catch (Exception $e) {
            WPE_Security::log_event('api_error', [
                'endpoint'   => 'update_product',
                'product_id' => $request->get_param('id'),
                'error'      => $e->getMessage(),
            ]);

            return new WP_Error(
                'wpe_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Bulk update products endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function bulk_update_products($request) {
        try {
            $product_ids = $request->get_param('product_ids');
            $field = $request->get_param('field');
            $value = $request->get_param('value');

            // Validate all product IDs
            foreach ($product_ids as $id) {
                if (!WPE_Security::can_edit_product($id)) {
                    return new WP_Error(
                        'rest_forbidden',
                        sprintf(
                            /* translators: %d: Product ID */
                            __('You do not have permission to edit product #%d.', 'woo-price-editor'),
                            $id
                        ),
                        ['status' => 403]
                    );
                }
            }

            $result = $this->product_handler->bulk_update($product_ids, $field, $value);

            return new WP_REST_Response([
                'success' => true,
                'updated' => $result['success'],
                'failed'  => $result['failed'],
                'errors'  => $result['errors'],
            ], 200);

        } catch (Exception $e) {
            WPE_Security::log_event('api_error', [
                'endpoint' => 'bulk_update_products',
                'error'    => $e->getMessage(),
            ]);

            return new WP_Error(
                'wpe_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Get categories endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_categories($request) {
        $categories = $this->product_handler->get_categories();

        return new WP_REST_Response([
            'success' => true,
            'data'    => $categories,
        ], 200);
    }

    /**
     * Get tax classes endpoint
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_tax_classes($request) {
        $tax_classes = $this->product_handler->get_tax_classes();

        return new WP_REST_Response([
            'success' => true,
            'data'    => $tax_classes,
        ], 200);
    }

    /**
     * Get arguments for get_products endpoint
     *
     * @return array
     */
    private function get_products_args() {
        return [
            'page' => [
                'description'       => __('Current page number', 'woo-price-editor'),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'per_page' => [
                'description'       => __('Items per page', 'woo-price-editor'),
                'type'              => 'integer',
                'default'           => 50,
                'minimum'           => 10,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ],
            'status' => [
                'description'       => __('Filter by product status', 'woo-price-editor'),
                'type'              => 'string',
                'enum'              => ['', 'publish', 'draft', 'private', 'pending'],
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'category' => [
                'description'       => __('Filter by category slug', 'woo-price-editor'),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'search' => [
                'description'       => __('Search by title, SKU, or ID', 'woo-price-editor'),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'tax_status' => [
                'description'       => __('Filter by tax status', 'woo-price-editor'),
                'type'              => 'string',
                'enum'              => ['', 'taxable', 'shipping', 'none'],
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'stock_status' => [
                'description'       => __('Filter by stock status', 'woo-price-editor'),
                'type'              => 'string',
                'enum'              => ['', 'instock', 'outofstock', 'onbackorder'],
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'orderby' => [
                'description'       => __('Order by field', 'woo-price-editor'),
                'type'              => 'string',
                'enum'              => ['ID', 'title', 'date', 'modified', 'price'],
                'default'           => 'ID',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order' => [
                'description'       => __('Order direction', 'woo-price-editor'),
                'type'              => 'string',
                'enum'              => ['ASC', 'DESC'],
                'default'           => 'DESC',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get arguments for update_product endpoint
     *
     * @return array
     */
    private function get_update_args() {
        return [
            'id' => [
                'description'       => __('Product ID', 'woo-price-editor'),
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
            'field' => [
                'description'       => __('Field to update', 'woo-price-editor'),
                'type'              => 'string',
                'required'          => true,
                'enum'              => ['title', 'regular_price', 'sale_price', 'tax_status', 'tax_class', 'stock_status'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'value' => [
                'description'       => __('New value', 'woo-price-editor'),
                'type'              => 'string',
                'required'          => true,
                // Sanitization is handled by WPE_Security::sanitize_field
            ],
        ];
    }

    /**
     * Get arguments for bulk_update_products endpoint
     *
     * @return array
     */
    private function get_bulk_update_args() {
        return [
            'product_ids' => [
                'description' => __('Array of product IDs', 'woo-price-editor'),
                'type'        => 'array',
                'required'    => true,
                'items'       => [
                    'type' => 'integer',
                ],
            ],
            'field' => [
                'description'       => __('Field to update', 'woo-price-editor'),
                'type'              => 'string',
                'required'          => true,
                'enum'              => ['title', 'regular_price', 'sale_price', 'tax_status', 'tax_class', 'stock_status'],
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'value' => [
                'description' => __('New value', 'woo-price-editor'),
                'type'        => 'string',
                'required'    => true,
            ],
        ];
    }
}
