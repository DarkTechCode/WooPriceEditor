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
     * Placeholder for future asset loading.
     * Ensures hooks are wired for enqueueing editor scripts and styles.
     *
     * @param string $hook_suffix Current admin page suffix.
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== $this->page_hook) {
            return;
        }

        /**
         * Hook point for future asset registration. Third-party code or future
         * iterations can attach to this action to load scripts and styles
         * without needing to adjust the core scaffolding.
         */
        do_action('wpe_editor_enqueue_assets', $hook_suffix, $this->page_hook);
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
