<?php
/**
 * Product handler class
 *
 * Provides product data retrieval and update functionality
 * with caching and optimized queries.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPE_Product
 *
 * Handles product operations with caching and security
 */
class WPE_Product {

    /**
     * Cache duration in seconds
     *
     * @var int
     */
    private $cache_duration = 3600;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('wpe_settings', []);
        if (isset($settings['cache_duration'])) {
            $this->cache_duration = absint($settings['cache_duration']);
        }
    }

    /**
     * Get product categories with caching
     *
     * @return array
     */
    public function get_categories() {
        $cache_key = 'wpe_product_categories';
        $categories = wp_cache_get($cache_key);

        if ($categories === false) {
            $terms = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (is_wp_error($terms)) {
                return [];
            }

            $categories = array_map(function($term) {
                return [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                ];
            }, $terms);

            wp_cache_set($cache_key, $categories, '', $this->cache_duration);
        }

        return $categories;
    }

    /**
     * Get tax classes with caching
     *
     * @return array
     */
    public function get_tax_classes() {
        $cache_key = 'wpe_tax_classes';
        $tax_classes = wp_cache_get($cache_key);

        if ($tax_classes === false) {
            $tax_classes = [];

            // Standard rate (empty slug)
            $tax_classes[] = [
                'slug' => '',
                'name' => __('Standard', 'woo-price-editor'),
            ];

            // Get WooCommerce tax classes
            $wc_tax_classes = WC_Tax::get_tax_classes();
            foreach ($wc_tax_classes as $class) {
                $tax_classes[] = [
                    'slug' => sanitize_title($class),
                    'name' => $class,
                ];
            }

            wp_cache_set($cache_key, $tax_classes, '', $this->cache_duration);
        }

        return $tax_classes;
    }

    /**
     * Get products with filtering and pagination
     *
     * @param array $params Query parameters
     * @return array
     */
    public function get_products($params = []) {
        global $wpdb;

        $defaults = [
            'page'       => 1,
            'per_page'   => 50,
            'status'     => '',
            'category'   => '',
            'search'     => '',
            'tax_status' => '',
            'stock_status' => '',
            'orderby'    => 'ID',
            'order'      => 'DESC',
        ];

        $params = wp_parse_args($params, $defaults);

        // Sanitize pagination
        $page = max(1, absint($params['page']));
        $per_page = min(100, max(10, absint($params['per_page'])));
        $offset = ($page - 1) * $per_page;

        // Build WC_Product_Query args
        $args = [
            'status'  => $this->get_valid_statuses($params['status']),
            'limit'   => $per_page,
            'page'    => $page,
            'orderby' => $this->sanitize_orderby($params['orderby']),
            'order'   => in_array(strtoupper($params['order']), ['ASC', 'DESC'], true)
                        ? strtoupper($params['order'])
                        : 'DESC',
            'return'  => 'ids',
        ];

        // Category filter
        if (!empty($params['category'])) {
            $args['category'] = [sanitize_text_field($params['category'])];
        }

        // Stock status filter
        if (!empty($params['stock_status'])) {
            $args['stock_status'] = sanitize_text_field($params['stock_status']);
        }

        // Search filter - use meta query for better performance
        if (!empty($params['search'])) {
            $search = sanitize_text_field($params['search']);

            // Check if search is numeric (ID or SKU)
            if (is_numeric($search)) {
                $args['include'] = [absint($search)];
            } else {
                $args['s'] = $search;
            }
        }

        // Get product IDs
        $query = new WC_Product_Query($args);
        $product_ids = $query->get_products();

        // Get total count (without pagination)
        $count_args = $args;
        unset($count_args['limit'], $count_args['page']);
        $count_args['return'] = 'ids';
        $count_query = new WC_Product_Query($count_args);
        $all_ids = $count_query->get_products();
        $total = count($all_ids);

        // Filter by tax status if needed (post-query filter)
        if (!empty($params['tax_status'])) {
            $tax_status_filter = sanitize_text_field($params['tax_status']);
            $product_ids = array_filter($product_ids, function($id) use ($tax_status_filter) {
                $product = wc_get_product($id);
                return $product && $product->get_tax_status() === $tax_status_filter;
            });
            // Recalculate total for tax status filter
            $all_ids = array_filter($all_ids, function($id) use ($tax_status_filter) {
                $product = wc_get_product($id);
                return $product && $product->get_tax_status() === $tax_status_filter;
            });
            $total = count($all_ids);
        }

        // Build product data array
        $products = [];
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            $products[] = $this->format_product_data($product);
        }

        return [
            'products'  => $products,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $per_page,
            'pages'     => ceil($total / $per_page),
        ];
    }

    /**
     * Format product data for response
     *
     * @param WC_Product $product Product object
     * @return array
     */
    private function format_product_data($product) {
        $categories = $product->get_category_ids();
        $category_names = [];

        foreach ($categories as $cat_id) {
            $term = get_term($cat_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $category_names[] = $term->name;
            }
        }

        return [
            'id'            => $product->get_id(),
            'title'         => $product->get_name(),
            'sku'           => $product->get_sku(),
            'status'        => $product->get_status(),
            'regular_price' => $product->get_regular_price(),
            'sale_price'    => $product->get_sale_price(),
            'price'         => $product->get_price(),
            'tax_status'    => $product->get_tax_status(),
            'tax_class'     => $product->get_tax_class(),
            'stock_status'  => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'categories'    => implode(', ', $category_names),
            'edit_link'     => get_edit_post_link($product->get_id(), 'raw'),
            'view_link'     => get_permalink($product->get_id()),
        ];
    }

    /**
     * Update product field
     *
     * @param int    $product_id Product ID
     * @param string $field      Field name
     * @param mixed  $value      New value
     * @return array Result with success status and message
     */
    public function update_field($product_id, $field, $value) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return [
                'success' => false,
                'message' => __('Product not found', 'woo-price-editor'),
            ];
        }

        // Get field configuration
        $field_config = WPE_Security::get_field_config();

        if (!isset($field_config[$field])) {
            return [
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: Field name */
                    __('Unknown field: %s', 'woo-price-editor'),
                    $field
                ),
            ];
        }

        // Validate field value
        $validation = WPE_Security::validate_field($field, $value);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['error'],
            ];
        }

        // Sanitize value
        try {
            $sanitized_value = WPE_Security::sanitize_field($field, $value);
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // Get old value for logging
        $config = $field_config[$field];
        $getter = $config['getter'];
        $setter = $config['setter'];
        $old_value = $product->$getter();

        // Handle empty sale price
        if ($field === 'sale_price' && $sanitized_value === '') {
            $product->set_sale_price('');
        } else {
            $product->$setter($sanitized_value);
        }

        // Save product
        $result = $product->save();

        if (!$result) {
            return [
                'success' => false,
                'message' => __('Failed to save product', 'woo-price-editor'),
            ];
        }

        // Clear product cache
        wc_delete_product_transients($product_id);

        // Log the change
        WPE_Security::log_event('product_updated', [
            'product_id' => $product_id,
            'field'      => $field,
            'old_value'  => $old_value,
            'new_value'  => $sanitized_value,
        ]);

        // Fire action for extensibility
        do_action('wpe_product_updated', $product_id, $field, $old_value, $sanitized_value);

        // Build success message
        $message = sprintf(
            /* translators: 1: Field label, 2: Product ID, 3: Old value, 4: New value */
            __('%1$s for product #%2$d changed: %3$s → %4$s', 'woo-price-editor'),
            $config['label'],
            $product_id,
            $old_value !== '' ? $old_value : '—',
            $sanitized_value !== '' ? $sanitized_value : '—'
        );

        return [
            'success'   => true,
            'message'   => $message,
            'old_value' => $old_value,
            'new_value' => $sanitized_value,
            'product'   => $this->format_product_data($product),
        ];
    }

    /**
     * Bulk update products
     *
     * @param array  $product_ids Array of product IDs
     * @param string $field       Field name
     * @param mixed  $value       New value
     * @return array Results for each product
     */
    public function bulk_update($product_ids, $field, $value) {
        $results = [
            'success' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        foreach ($product_ids as $product_id) {
            $result = $this->update_field($product_id, $field, $value);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][$product_id] = $result['message'];
            }
        }

        return $results;
    }

    /**
     * Get valid statuses from parameter
     *
     * @param string $status Status parameter
     * @return array
     */
    private function get_valid_statuses($status) {
        $all_statuses = ['publish', 'draft', 'private', 'pending'];

        if (empty($status)) {
            return $all_statuses;
        }

        $status = sanitize_text_field($status);

        if (in_array($status, $all_statuses, true)) {
            return [$status];
        }

        return $all_statuses;
    }

    /**
     * Sanitize orderby parameter
     *
     * @param string $orderby Orderby parameter
     * @return string
     */
    private function sanitize_orderby($orderby) {
        $allowed = ['ID', 'title', 'date', 'modified', 'menu_order', 'price'];
        $orderby = sanitize_text_field($orderby);

        if (in_array($orderby, $allowed, true)) {
            return $orderby;
        }

        return 'ID';
    }

    /**
     * Clear all product caches
     *
     * @return void
     */
    public function clear_caches() {
        wp_cache_delete('wpe_product_categories');
        wp_cache_delete('wpe_tax_classes');
    }
}
