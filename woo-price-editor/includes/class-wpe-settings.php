<?php
/**
 * Класс страницы настроек редактора цен Woo.
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
     * Зарегистрировать страницу подменю настроек.
     *
     * @return void
     */
    public function register_settings_menu() {
        $this->page_hook = add_submenu_page(
            'woo-price-editor',
            __('Настройки редактора цен Woo', 'woo-price-editor'),
            __('Настройки', 'woo-price-editor'),
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

        // Секция начальной категории
        add_settings_section(
            'wpe_start_category_section',
            __('Начальная категория', 'woo-price-editor'),
            [$this, 'render_start_category_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_start_category',
            __('Начальная категория по умолчанию', 'woo-price-editor'),
            [$this, 'render_start_category_field'],
            'wpe_settings',
            'wpe_start_category_section'
        );

        // Секция колонок по умолчанию
        add_settings_section(
            'wpe_default_columns_section',
            __('Колонки по умолчанию', 'woo-price-editor'),
            [$this, 'render_default_columns_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_default_columns',
            __('Видимые колонки', 'woo-price-editor'),
            [$this, 'render_default_columns_field'],
            'wpe_settings',
            'wpe_default_columns_section'
        );

        // Секция инструкций
        add_settings_section(
            'wpe_instructions_section',
            __('Инструкции', 'woo-price-editor'),
            [$this, 'render_instructions_section'],
            'wpe_settings'
        );

        add_settings_field(
            'wpe_instructions',
            __('Инструкции для редактора', 'woo-price-editor'),
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
            wp_die(__('У вас нет прав для доступа к этой странице.', 'woo-price-editor'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Настройки редактора цен Woo', 'woo-price-editor'); ?></h1>
            
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
     * Вывести описание секции начальной категории.
     *
     * @return void
     */
    public function render_start_category_section() {
        echo '<p>' . esc_html__('Выберите категорию по умолчанию, которая будет выбрана при загрузке редактора. Выберите "Все товары" для отображения всех товаров по умолчанию.', 'woo-price-editor') . '</p>';
    }

    /**
     * Вывести поле начальной категории.
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
        echo '<option value="all"' . selected($current_category, 'all', false) . '>' . esc_html__('Все товары', 'woo-price-editor') . '</option>';
        
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
        echo '<p class="description">' . esc_html__('Категория, которая будет автоматически выбрана при открытии редактора.', 'woo-price-editor') . '</p>';
    }

    /**
     * Вывести описание секции колонок по умолчанию.
     *
     * @return void
     */
    public function render_default_columns_section() {
        echo '<p>' . esc_html__('Выберите, какие колонки должны быть видимы по умолчанию в таблице редактора. Пользователи могут переключать видимость колонок с помощью элементов управления.', 'woo-price-editor') . '</p>';
    }

    /**
     * Вывести поле выбора колонок.
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
        echo '<p class="description">' . esc_html__('Выберите колонки, которые должны быть видимы по умолчанию. Колонки "Товар" и "Действия" отображаются всегда.', 'woo-price-editor') . '</p>';
    }

    /**
     * Вывести описание секции инструкций.
     *
     * @return void
     */
    public function render_instructions_section() {
        echo '<p>' . esc_html__('Добавьте инструкции, которые помогут пользователям эффективно работать с редактором цен.', 'woo-price-editor') . '</p>';
    }

    /**
     * Вывести поле инструкций.
     *
     * @return void
     */
    public function render_instructions_field() {
        $settings = get_option('wpe_editor_settings', $this->get_default_options());
        $instructions = $settings['instructions'] ?? $this->get_default_instructions();
        
        echo '<textarea id="wpe_instructions" name="wpe_editor_settings[instructions]" rows="8" class="large-text">' . esc_textarea($instructions) . '</textarea>';
        echo '<p class="description">' . esc_html__('Эти инструкции отображаются в верхней части страницы редактора и помогают пользователям.', 'woo-price-editor') . '</p>';
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
                        __('Выбрана недопустимая категория. Используется значение по умолчанию.', 'woo-price-editor')
                    );
                    $sanitized['start_category'] = $defaults['start_category'];
                }
            }
        }

        // Санитизация колонок по умолчанию
        if (isset($input['default_columns']) && is_array($input['default_columns'])) {
            $valid_columns = array_keys($this->available_columns);
            $sanitized['default_columns'] = array_intersect($input['default_columns'], $valid_columns);
            
            // Убедиться, что выбрана хотя бы одна колонка
            if (empty($sanitized['default_columns'])) {
                add_settings_error(
                    'wpe_editor_settings',
                    'no_columns',
                    __('Должна быть выбрана хотя бы одна колонка. Используются колонки по умолчанию.', 'woo-price-editor')
                );
                $sanitized['default_columns'] = $defaults['default_columns'];
            }
        }

        // Санитизация инструкций
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
            'sku' => __('Артикул', 'woo-price-editor'),
            'status' => __('Статус', 'woo-price-editor'),
            'regular_price' => __('Обычная цена', 'woo-price-editor'),
            'sale_price' => __('Цена со скидкой', 'woo-price-editor'),
            'tax_status' => __('Налоговый статус', 'woo-price-editor'),
            'tax_class' => __('Налоговый класс', 'woo-price-editor'),
            'stock_status' => __('Статус наличия', 'woo-price-editor'),
            'categories' => __('Категории', 'woo-price-editor'),
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
            "Добро пожаловать в редактор цен Woo! Этот инструмент позволяет быстро редактировать информацию о товарах в массовом режиме.\n\n" .
            "Как использовать:\n" .
            "• Кликните на любое редактируемое поле для его изменения\n" .
            "• Поля цен: Введите новые цены, они будут сохранены автоматически при клике вне поля\n" .
            "• Поле названия: Кликните для редактирования, затем нажмите Enter для сохранения или Escape для отмены\n" .
            "• Выпадающие списки: Выберите новые значения из выпадающего меню\n" .
            "• Используйте фильтры над таблицей для фильтрации товаров\n" .
            "• Переключайте видимость колонок с помощью чекбоксов\n" .
            "• Все изменения сохраняются автоматически в вашем магазине WooCommerce\n\n" .
            "Советы:\n" .
            "• Используйте строку поиска для поиска товаров по названию, артикулу или ID\n" .
            "• Фильтруйте по категории, статусу, налоговому статусу или статусу наличия\n" .
            "• Кликните на иконки редактирования или просмотра для открытия товара в стандартном интерфейсе WooCommerce",
            'woo-price-editor'
        );
    }
}