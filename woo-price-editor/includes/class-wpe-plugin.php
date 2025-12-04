<?php
/**
 * Core plugin bootstrapper.
 *
 * @package WooPriceEditor
 */

defined('ABSPATH') || exit;

class WPE_Plugin {
    /**
     * Singleton instance.
     *
     * @var WPE_Plugin|null
     */
    private static $instance = null;

    /**
     * Stored hook suffix for the admin page.
     *
     * @var string
     */
    private $page_hook = '';

    /**
     * Cached nonce for the editor session.
     *
     * @var string
     */
    private $editor_nonce = '';

    /**
     * Retrieve the singleton instance.
     *
     * @return WPE_Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Activation callback.
     *
     * Seeds default editor options.
     *
     * @return void
     */
    public static function activate() {
        $defaults = self::get_default_options();
        $options  = get_option('wpe_editor_settings');

        if (false === $options) {
            add_option('wpe_editor_settings', $defaults);
        } else {
            update_option('wpe_editor_settings', wp_parse_args((array) $options, $defaults));
        }
    }

    /**
     * Default option values for the editor shell.
     *
     * @return array
     */
    public static function get_default_options() {
        return [
            'start_category'  => 'all',
            'default_columns' => [
                'product',
                'sku',
                'regular_price',
                'sale_price',
                'stock_status',
                'tax_status',
            ],
        ];
    }

    /**
     * Initialize hooks when plugins are loaded.
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'prepare_editor_context']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Initialize REST API
        $api = new WPE_API();
        add_action('rest_api_init', [$api, 'register_routes']);
    }

    /**
     * Register the top-level admin menu entry for the editor.
     *
     * @return void
     */
    public function register_admin_menu() {
        $this->page_hook = add_menu_page(
            __('Woo Price Editor', 'woo-price-editor'),
            __('Price Editor', 'woo-price-editor'),
            'manage_woocommerce',
            'woo-price-editor',
            [$this, 'render_placeholder_screen'],
            'dashicons-money-alt',
            56
        );

        if (!empty($this->page_hook)) {
            add_action('load-' . $this->page_hook, [$this, 'render_fullscreen_shell']);
        }
    }

    /**
     * Prepare per-request context such as the editor nonce.
     *
     * @return void
     */
    public function prepare_editor_context() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $this->editor_nonce = wp_create_nonce('wpe_editor_shell');
    }

    /**
     * Enqueue admin assets for the editor.
     * Loads DataTables, plugin CSS/JS, and localizes dynamic data.
     *
     * @param string $hook_suffix Current admin page suffix.
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== $this->page_hook) {
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
            'wpe-editor',
            WPE_PLUGIN_URL . 'assets/css/editor.css',
            ['datatables'],
            WPE_PLUGIN_VERSION
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
            'wpe-editor',
            WPE_PLUGIN_URL . 'assets/js/editor.js',
            ['jquery', 'datatables', 'wp-api-fetch'],
            WPE_PLUGIN_VERSION,
            true
        );

        // Get current settings for localization
        $settings = get_option('wpe_editor_settings', self::get_default_options());

        // Localize script with dynamic data
        wp_localize_script('wpe-editor', 'wpeData', [
            'restUrl'        => rest_url('woo-price-editor/v1'),
            'nonce'          => wp_create_nonce('wp_rest'),
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'pageLength'     => 50, // Default page length
            'defaultColumns' => $settings['default_columns'] ?? [],
            'startCategory'  => $settings['start_category'] ?? 'all',
            'i18n'           => [
                'loading'          => __('Loading...', 'woo-price-editor'),
                'saved'            => __('Saved', 'woo-price-editor'),
                'cancel'           => __('Cancel', 'woo-price-editor'),
                'edit'             => __('Edit', 'woo-price-editor'),
                'view'             => __('View', 'woo-price-editor'),
                'show'             => __('Show', 'woo-price-editor'),
                'entries'          => __('entries', 'woo-price-editor'),
                'showing'          => __('Showing', 'woo-price-editor'),
                'of'               => __('of', 'woo-price-editor'),
                'products'         => __('products', 'woo-price-editor'),
                'page'             => __('Page', 'woo-price-editor'),
                'previous'         => __('Previous', 'woo-price-editor'),
                'next'             => __('Next', 'woo-price-editor'),
                'first'            => __('First', 'woo-price-editor'),
                'last'             => __('Last', 'woo-price-editor'),
                'search'           => __('Search:', 'woo-price-editor'),
                'noData'           => __('No products found', 'woo-price-editor'),
                'filteredFrom'     => __('filtered from', 'woo-price-editor'),
                'total'            => __('total', 'woo-price-editor'),
                'notAuthenticated' => __('Please log in again', 'woo-price-editor'),
                'forbidden'        => __('You do not have permission', 'woo-price-editor'),
                'rateLimitExceeded' => __('Too many requests. Please wait.', 'woo-price-editor'),
                'timeout'          => __('Request timed out', 'woo-price-editor'),
                'networkError'     => __('Network error. Please check your connection.', 'woo-price-editor'),
                'serverError'      => __('Server error. Please try again later.', 'woo-price-editor'),
                'invalidPrice'     => __('Invalid price value', 'woo-price-editor'),
                'negativePrice'    => __('Price cannot be negative', 'woo-price-editor'),
                'emptyTitle'       => __('Title cannot be empty', 'woo-price-editor'),
                'instock'          => __('In Stock', 'woo-price-editor'),
                'outofstock'      => __('Out of Stock', 'woo-price-editor'),
                'onbackorder'      => __('On Backorder', 'woo-price-editor'),
                'taxable'          => __('Taxable', 'woo-price-editor'),
                'shipping'         => __('Shipping only', 'woo-price-editor'),
                'none'             => __('None', 'woo-price-editor'),
                'standard'         => __('Standard', 'woo-price-editor'),
                'publish'          => __('Published', 'woo-price-editor'),
                'draft'            => __('Draft', 'woo-price-editor'),
                'private'          => __('Private', 'woo-price-editor'),
                'pending'          => __('Pending', 'woo-price-editor'),
            ],
        ]);
    }

    /**
     * Fallback renderer if the load hook is bypassed.
     *
     * @return void
     */
    public function render_placeholder_screen() {
        $this->render_fullscreen_shell();
    }

    /**
     * Ensure the current user can access the editor.
     *
     * @return void
     */
    private function ensure_user_can_access() {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(
                esc_html__('You do not have permission to access the Woo Price Editor.', 'woo-price-editor'),
                esc_html__('Access denied', 'woo-price-editor'),
                ['response' => 403]
            );
        }
    }

    /**
     * Render the full-screen editor shell and bypass standard wp-admin chrome.
     *
     * @return void
     */
    public function render_fullscreen_shell() {
        $this->ensure_user_can_access();

        $context = [
            'nonce'    => $this->editor_nonce ? $this->editor_nonce : wp_create_nonce('wpe_editor_shell'),
            'settings' => get_option('wpe_editor_settings', self::get_default_options()),
            'user'     => wp_get_current_user(),
        ];

        status_header(200);
        nocache_headers();

        $template = trailingslashit(WPE_PLUGIN_DIR) . 'templates/editor-shell.php';

        if (!file_exists($template)) {
            wp_die(
                esc_html__('The editor template could not be located.', 'woo-price-editor'),
                esc_html__('Template missing', 'woo-price-editor'),
                ['response' => 500]
            );
        }

        $context = apply_filters('wpe_editor_context', $context);

        include $template;
        exit;
    }
}
