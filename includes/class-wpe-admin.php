<?php
/**
 * Admin class
 *
 * Handles admin menu, page rendering, and asset enqueuing.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPE_Admin
 *
 * Handles all admin-related functionality
 */
class WPE_Admin {

    /**
     * Menu slug
     *
     * @var string
     */
    const MENU_SLUG = 'woo-price-editor';

    /**
     * Settings option name
     *
     * @var string
     */
    const OPTION_NAME = 'wpe_settings';

    /**
     * Initialize admin hooks
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add admin menu page
     *
     * @return void
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __('Price Editor', 'woo-price-editor'),
            __('Price Editor', 'woo-price-editor'),
            'manage_woocommerce',
            self::MENU_SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_assets($hook) {
        // Only load on our page
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }

        // Enqueue WordPress jQuery (not a separate copy)
        wp_enqueue_script('jquery');

        // DataTables CSS
        wp_enqueue_style(
            'datatables',
            'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
            [],
            '1.13.6'
        );

        // Plugin CSS
        wp_enqueue_style(
            'wpe-admin',
            WPE_PLUGIN_URL . 'admin/css/wpe-admin.css',
            ['datatables'],
            WPE_VERSION
        );

        // DataTables JS
        wp_enqueue_script(
            'datatables',
            'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
            ['jquery'],
            '1.13.6',
            true
        );

        // Plugin JS
        wp_enqueue_script(
            'wpe-admin',
            WPE_PLUGIN_URL . 'admin/js/wpe-admin.js',
            ['jquery', 'datatables', 'wp-api-fetch'],
            WPE_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('wpe-admin', 'wpeData', [
            'restUrl'    => rest_url('woo-price-editor/v1'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'pageLength' => $this->get_setting('page_length', 50),
            'i18n'       => $this->get_i18n_strings(),
        ]);
    }

    /**
     * Get internationalization strings
     *
     * @return array
     */
    private function get_i18n_strings() {
        return [
            'loading'          => __('Loading...', 'woo-price-editor'),
            'noData'           => __('No products found', 'woo-price-editor'),
            'error'            => __('An error occurred', 'woo-price-editor'),
            'saving'           => __('Saving...', 'woo-price-editor'),
            'saved'            => __('Saved', 'woo-price-editor'),
            'saveFailed'       => __('Save failed', 'woo-price-editor'),
            'confirmDelete'    => __('Are you sure?', 'woo-price-editor'),
            'search'           => __('Search...', 'woo-price-editor'),
            'allStatuses'      => __('All statuses', 'woo-price-editor'),
            'allCategories'    => __('All categories', 'woo-price-editor'),
            'allTaxStatuses'   => __('All tax statuses', 'woo-price-editor'),
            'allStockStatuses' => __('All stock statuses', 'woo-price-editor'),
            'published'        => __('Published', 'woo-price-editor'),
            'draft'            => __('Draft', 'woo-price-editor'),
            'private'          => __('Private', 'woo-price-editor'),
            'pending'          => __('Pending', 'woo-price-editor'),
            'taxable'          => __('Taxable', 'woo-price-editor'),
            'shipping'         => __('Shipping only', 'woo-price-editor'),
            'none'             => __('None', 'woo-price-editor'),
            'instock'          => __('In Stock', 'woo-price-editor'),
            'outofstock'       => __('Out of Stock', 'woo-price-editor'),
            'onbackorder'      => __('On Backorder', 'woo-price-editor'),
            'invalidPrice'     => __('Invalid price value', 'woo-price-editor'),
            'negativePrice'    => __('Price cannot be negative', 'woo-price-editor'),
            'emptyTitle'       => __('Title cannot be empty', 'woo-price-editor'),
            'rateLimitExceeded' => __('Too many requests. Please wait.', 'woo-price-editor'),
            'networkError'     => __('Network error. Please check your connection.', 'woo-price-editor'),
            'serverError'      => __('Server error. Please try again later.', 'woo-price-editor'),
            'showing'          => __('Showing', 'woo-price-editor'),
            'of'               => __('of', 'woo-price-editor'),
            'products'         => __('products', 'woo-price-editor'),
            'page'             => __('Page', 'woo-price-editor'),
            'previous'         => __('Previous', 'woo-price-editor'),
            'next'             => __('Next', 'woo-price-editor'),
            'first'            => __('First', 'woo-price-editor'),
            'last'             => __('Last', 'woo-price-editor'),
        ];
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'wpe_settings_group',
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => [
                    'page_length'    => 50,
                    'enable_logging' => false,
                    'rate_limit'     => 100,
                    'cache_duration' => 3600,
                ],
            ]
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        $sanitized['page_length'] = isset($input['page_length'])
            ? min(100, max(10, absint($input['page_length'])))
            : 50;

        $sanitized['enable_logging'] = !empty($input['enable_logging']);

        $sanitized['rate_limit'] = isset($input['rate_limit'])
            ? min(1000, max(10, absint($input['rate_limit'])))
            : 100;

        $sanitized['cache_duration'] = isset($input['cache_duration'])
            ? min(86400, max(0, absint($input['cache_duration'])))
            : 3600;

        return $sanitized;
    }

    /**
     * Get a specific setting value
     *
     * @param string $key     Setting key
     * @param mixed  $default Default value
     * @return mixed
     */
    private function get_setting($key, $default = null) {
        $settings = get_option(self::OPTION_NAME, []);
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Render admin page
     *
     * @return void
     */
    public function render_page() {
        // Security check
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'woo-price-editor'));
        }

        include WPE_PLUGIN_DIR . 'admin/partials/wpe-admin-display.php';
    }
}
