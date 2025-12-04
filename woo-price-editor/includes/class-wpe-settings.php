<?php
/**
 * Settings page class for Woo Price Editor.
 *
 * @package WooPriceEditor
 */

defined('ABSPATH') || exit;

class WPE_Settings {
    /**
     * Settings page hook suffix.
     *
     * @var string
     */
    private $page_hook = '';

    /**
     * Available columns for the editor.
     *
     * @var array
     */
    private $available_columns = [];

    /**
     * Initialize the settings page.
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'register_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_styles']);
        $this->available_columns = $this->get_available_columns();
    }

    /**
     * Register the settings submenu page.
     *
     * @return void
     */
    public function register_settings_menu() {
        $this->page_hook = add_submenu_page(
            'woo-price-editor',
            __('Woo Price Editor Settings', 'woo-price-editor'),
            __('Settings', 'woo-price-editor'),
            'manage_woocommerce',
            'wpe-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings using WordPress Settings API.
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'wpe_settings',
            'wpe_editor_settings',
            [$this, 'sanitize_settings']
        );

        // Start Category Section
        add_settings_section(
            'wpe_start_category_section',
            __('Start Category', 'woo-price-editor'),
            [$this, 'render_start_category_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_start_category',
            __('Default Start Category', 'woo-price-editor'),
            [$this, 'render_start_category_field'],
            'wpe_settings',
            'wpe_start_category_section'
        );

        // Default Columns Section
        add_settings_section(
            'wpe_default_columns_section',
            __('Default Columns', 'woo-price-editor'),
            [$this, 'render_default_columns_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_default_columns',
            __('Visible Columns', 'woo-price-editor'),
            [$this, 'render_default_columns_field'],
            'wpe_settings',
            'wpe_default_columns_section'
        );

        // Instructions Section
        add_settings_section(
            'wpe_instructions_section',
            __('Instructions', 'woo-price-editor'),
            [$this, 'render_instructions_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_instructions',
            __('Editor Instructions', 'woo-price-editor'),
            [$this, 'render_instructions_field'],
            'wpe_settings',
            'wpe_instructions_section'
        );
    }

    /**
     * Enqueue settings page styles.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     * @return void
     */
    public function enqueue_settings_styles($hook_suffix) {
        if ($hook_suffix !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'wpe-settings',
            WPE_PLUGIN_URL . 'assets/css/settings.css',
            [],
            WPE_PLUGIN_VERSION
        );
    }

    /**
     * Render the settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to access this page.', 'woo-price-editor'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Woo Price Editor Settings', 'woo-price-editor'); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('wpe_settings');
                do_settings_sections('wpe_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render start category section description.
     *
     * @return void
     */
    public function render_start_category_section() {
        echo '<p>' . esc_html__('Choose the default category that will be selected when the editor loads. Select "All Products" to show all products by default.', 'woo-price-editor') . '</p>';
    }

    /**
     * Render start category field.
     *
     * @return void
     */
    public function render_start_category_field() {
        $settings = get_option('wpe_editor_settings', $this->get_default_options());
        $current_category = $settings['start_category'] ?? 'all';
        
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);

        echo '<select id="wpe_start_category" name="wpe_editor_settings[start_category]">';
        echo '<option value="all"' . selected($current_category, 'all', false) . '>' . esc_html__('All Products', 'woo-price-editor') . '</option>';
        
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($category->slug),
                    selected($current_category, $category->slug, false),
                    esc_html($category->name)
                );
            }
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('The category that will be automatically selected when the editor opens.', 'woo-price-editor') . '</p>';
    }

    /**
     * Render default columns section description.
     *
     * @return void
     */
    public function render_default_columns_section() {
        echo '<p>' . esc_html__('Choose which columns should be visible by default in the editor table. Users can toggle column visibility using the column controls.', 'woo-price-editor') . '</p>';
    }

    /**
     * Render default columns field.
     *
     * @return void
     */
    public function render_default_columns_field() {
        $settings = get_option('wpe_editor_settings', $this->get_default_options());
        $default_columns = $settings['default_columns'] ?? [];
        
        echo '<div class="wpe-columns-grid">';
        foreach ($this->available_columns as $column_key => $column_label) {
            $checked = in_array($column_key, $default_columns, true);
            printf(
                '<label class="wpe-column-checkbox">
                    <input type="checkbox" name="wpe_editor_settings[default_columns][]" value="%s"%s>
                    %s
                </label>',
                esc_attr($column_key),
                checked($checked, true, false),
                esc_html($column_label)
            );
        }
        echo '</div>';
        echo '<p class="description">' . esc_html__('Select the columns that should be visible by default. The "Product" and "Actions" columns are always visible.', 'woo-price-editor') . '</p>';
    }

    /**
     * Render instructions section description.
     *
     * @return void
     */
    public function render_instructions_section() {
        echo '<p>' . esc_html__('Provide instructions that will help users understand how to use the price editor effectively.', 'woo-price-editor') . '</p>';
    }

    /**
     * Render instructions field.
     *
     * @return void
     */
    public function render_instructions_field() {
        $settings = get_option('wpe_editor_settings', $this->get_default_options());
        $instructions = $settings['instructions'] ?? $this->get_default_instructions();
        
        echo '<textarea id="wpe_instructions" name="wpe_editor_settings[instructions]" rows="8" class="large-text">' . esc_textarea($instructions) . '</textarea>';
        echo '<p class="description">' . esc_html__('These instructions will be displayed at the top of the editor page to guide users.', 'woo-price-editor') . '</p>';
    }

    /**
     * Sanitize and validate settings.
     *
     * @param array $input Raw input data.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        $defaults = $this->get_default_options();

        // Sanitize start category
        if (isset($input['start_category'])) {
            if ($input['start_category'] === 'all') {
                $sanitized['start_category'] = 'all';
            } else {
                // Validate that the category exists
                $category = get_term_by('slug', $input['start_category'], 'product_cat');
                if ($category && !is_wp_error($category)) {
                    $sanitized['start_category'] = $category->slug;
                } else {
                    add_settings_error(
                        'wpe_editor_settings',
                        'invalid_category',
                        __('Invalid category selected. Using default value.', 'woo-price-editor')
                    );
                    $sanitized['start_category'] = $defaults['start_category'];
                }
            }
        }

        // Sanitize default columns
        if (isset($input['default_columns']) && is_array($input['default_columns'])) {
            $valid_columns = array_keys($this->available_columns);
            $sanitized['default_columns'] = array_intersect($input['default_columns'], $valid_columns);
            
            // Ensure at least some columns are selected
            if (empty($sanitized['default_columns'])) {
                add_settings_error(
                    'wpe_editor_settings',
                    'no_columns',
                    __('At least one column must be selected. Using default columns.', 'woo-price-editor')
                );
                $sanitized['default_columns'] = $defaults['default_columns'];
            }
        }

        // Sanitize instructions
        if (isset($input['instructions'])) {
            $sanitized['instructions'] = wp_kses_post($input['instructions']);
            if (empty(trim($sanitized['instructions']))) {
                $sanitized['instructions'] = $this->get_default_instructions();
            }
        }

        return $sanitized;
    }

    /**
     * Get available columns for the editor.
     *
     * @return array Available columns with labels.
     */
    private function get_available_columns() {
        return [
            'sku' => __('SKU', 'woo-price-editor'),
            'status' => __('Status', 'woo-price-editor'),
            'regular_price' => __('Regular Price', 'woo-price-editor'),
            'sale_price' => __('Sale Price', 'woo-price-editor'),
            'tax_status' => __('Tax Status', 'woo-price-editor'),
            'tax_class' => __('Tax Class', 'woo-price-editor'),
            'stock_status' => __('Stock Status', 'woo-price-editor'),
            'categories' => __('Categories', 'woo-price-editor'),
        ];
    }

    /**
     * Get default options for the settings.
     *
     * @return array Default options.
     */
    private function get_default_options() {
        return [
            'start_category'  => 'all',
            'default_columns' => [
                'sku',
                'regular_price',
                'sale_price',
                'stock_status',
                'tax_status',
            ],
            'instructions' => $this->get_default_instructions(),
        ];
    }

    /**
     * Get default instructions text.
     *
     * @return string Default instructions.
     */
    private function get_default_instructions() {
        return __(
            "Welcome to the Woo Price Editor! This tool allows you to quickly edit product information in bulk.\n\n" .
            "How to use:\n" .
            "• Click on any editable field to modify it directly\n" .
            "• Price fields: Enter new prices and they'll be saved automatically when you click away\n" .
            "• Title field: Click to edit, then press Enter to save or Escape to cancel\n" .
            "• Dropdown fields: Select new values from the dropdown menu\n" .
            "• Use the filters above the table to narrow down products\n" .
            "• Toggle column visibility using the column checkboxes\n" .
            "• All changes are saved automatically to your WooCommerce store\n\n" .
            "Tips:\n" .
            "• Use the search bar to find products by title, SKU, or ID\n" .
            "• Filter by category, status, tax status, or stock status\n" .
            "• Click the edit or view icons to open products in the standard WooCommerce interface",
            'woo-price-editor'
        );
    }
}