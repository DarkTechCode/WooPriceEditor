<?php
/**
 * Основной загрузчик плагина.
 *
 * @package WooPriceEditor
 */

defined('ABSPATH') || exit;

class WPE_Plugin {
    /**
     * Экземпляр синглтона.
     *
     * @var WPE_Plugin|null
     */
    private static $instance = null;

    /**
     * Суффикс хука для страницы администратора.
     *
     * @var string
     */
    private $page_hook = '';

    /**
     * Кешированный nonce для сессии редактора.
     *
     * @var string
     */
    private $editor_nonce = '';

    /**
     * Получить экземпляр синглтона.
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
     * Обработчик активации плагина.
     *
     * Устанавливает параметры редактора по умолчанию и обрабатывает миграцию.
     *
     * @return void
     */
    public static function activate() {
        $defaults = self::get_default_options();
        $options  = get_option('wpe_editor_settings');

        if (false === $options) {
            add_option('wpe_editor_settings', $defaults);
        } else {
            // Merge with defaults to ensure new fields are added
            $updated_options = wp_parse_args((array) $options, $defaults);
            
            // Handle migration for existing installations
            if (!isset($options['instructions'])) {
                $updated_options['instructions'] = $defaults['instructions'];
            }
            
            // Remove 'product' from default_columns if it exists (it's always visible)
            if (isset($updated_options['default_columns']) && is_array($updated_options['default_columns'])) {
                $updated_options['default_columns'] = array_diff($updated_options['default_columns'], ['product']);
                $updated_options['default_columns'] = array_values($updated_options['default_columns']);
            }
            
            update_option('wpe_editor_settings', $updated_options);
        }
    }

    /**
     * Значения настроек по умолчанию для оболочки редактора.
     *
     * @return array
     */
    public static function get_default_options() {
        return [
            'start_category'  => 'all',
            'default_columns' => [
                'sku',
                'regular_price',
                'sale_price',
                'stock_status',
                'tax_status',
            ],
            'instructions' => self::get_default_instructions(),
        ];
    }

    /**
     * Получить текст инструкций по умолчанию.
     *
     * @return string Инструкции по умолчанию.
     */
    private static function get_default_instructions() {
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

    /**
     * Инициализировать хуки после загрузки плагинов.
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
        
        // Initialize AJAX handlers
        $ajax = new WPE_AJAX();
        $ajax->register_actions();
        
        // Initialize settings
        $settings = new WPE_Settings();
        $settings->init();
    }

    /**
     * Зарегистрировать пункт меню администратора верхнего уровня для редактора.
     *
     * @return void
     */
    public function register_admin_menu() {
        $this->page_hook = add_menu_page(
            __('Редактор цен Woo', 'woo-price-editor'),
            __('Редактор цен', 'woo-price-editor'),
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
     * Подготовить контекст для каждого запроса, такой как nonce редактора.
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
     * Подключить административные ресурсы редактора.
     * Загружает DataTables, CSS/JS плагина и передаёт динамические данные.
     *
     * @param string $hook_suffix Текущий суффикс страницы администратора.
     * @return void
     */
    public function enqueue_admin_assets($hook_suffix) {
        // allow hook suffix to be the actual page hook or our registered hook
        if ($hook_suffix !== $this->page_hook && $hook_suffix !== 'toplevel_page_woo-price-editor') {
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
            ['jquery', 'datatables'],
            WPE_PLUGIN_VERSION,
            true
        );

        // Get current settings for localization
        $settings = get_option('wpe_editor_settings', self::get_default_options());

        // Передать локализованные данные в скрипт
        wp_localize_script('wpe-editor', 'wpeData', [
            'restUrl'        => rest_url('woo-price-editor/v1'),
            'nonce'          => wp_create_nonce('wp_rest'),
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'pageLength'     => 50, // Размер страницы по умолчанию
            'defaultColumns' => $settings['default_columns'] ?? [],
            'startCategory'  => $settings['start_category'] ?? 'all',
            'i18n'           => [
                'loading'          => __('Загрузка...', 'woo-price-editor'),
                'saved'            => __('Сохранено', 'woo-price-editor'),
                'cancel'           => __('Отмена', 'woo-price-editor'),
                'edit'             => __('Редактировать', 'woo-price-editor'),
                'view'             => __('Просмотр', 'woo-price-editor'),
                'show'             => __('Показать', 'woo-price-editor'),
                'entries'          => __('записей', 'woo-price-editor'),
                'showing'          => __('Показано', 'woo-price-editor'),
                'of'               => __('из', 'woo-price-editor'),
                'products'         => __('товаров', 'woo-price-editor'),
                'page'             => __('Страница', 'woo-price-editor'),
                'previous'         => __('Предыдущая', 'woo-price-editor'),
                'next'             => __('Следующая', 'woo-price-editor'),
                'first'            => __('Первая', 'woo-price-editor'),
                'last'             => __('Последняя', 'woo-price-editor'),
                'search'           => __('Поиск:', 'woo-price-editor'),
                'noData'           => __('Товары не найдены', 'woo-price-editor'),
                'filteredFrom'     => __('отфильтровано из', 'woo-price-editor'),
                'total'            => __('всего', 'woo-price-editor'),
                'notAuthenticated' => __('Пожалуйста, войдите снова', 'woo-price-editor'),
                'forbidden'        => __('У вас нет прав доступа', 'woo-price-editor'),
                'rateLimitExceeded' => __('Слишком много запросов. Пожалуйста, подождите.', 'woo-price-editor'),
                'timeout'          => __('Превышено время ожидания запроса', 'woo-price-editor'),
                'networkError'     => __('Ошибка сети. Проверьте подключение.', 'woo-price-editor'),
                'serverError'      => __('Ошибка сервера. Попробуйте позже.', 'woo-price-editor'),
                'invalidPrice'     => __('Неверное значение цены', 'woo-price-editor'),
                'negativePrice'    => __('Цена не может быть отрицательной', 'woo-price-editor'),
                'emptyTitle'       => __('Название не может быть пустым', 'woo-price-editor'),
                'instock'          => __('В наличии', 'woo-price-editor'),
                'outofstock'      => __('Нет в наличии', 'woo-price-editor'),
                'onbackorder'      => __('Под заказ', 'woo-price-editor'),
                'taxable'          => __('Облагается налогом', 'woo-price-editor'),
                'shipping'         => __('Только доставка', 'woo-price-editor'),
                'none'             => __('Нет', 'woo-price-editor'),
                'standard'         => __('Стандартный', 'woo-price-editor'),
                'publish'          => __('Опубликован', 'woo-price-editor'),
                'draft'            => __('Черновик', 'woo-price-editor'),
                'private'          => __('Приватный', 'woo-price-editor'),
                'pending'          => __('Ожидает', 'woo-price-editor'),
            ],
        ]);
    }

    /**
     * Резервный рендер, если хук загрузки был пропущен.
     *
     * @return void
     */
    public function render_placeholder_screen() {
        $this->render_fullscreen_shell();
    }

    /**
     * Проверить, может ли текущий пользователь открыть редактор.
     *
     * @return void
     */
    private function ensure_user_can_access() {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die(
                esc_html__('У вас нет прав для доступа к редактору цен Woo.', 'woo-price-editor'),
                esc_html__('Доступ запрещён', 'woo-price-editor'),
                ['response' => 403]
            );
        }
    }

    /**
     * Отрисовать полноэкранную оболочку редактора, минуя стандартный интерфейс wp-admin.
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
                esc_html__('Не удалось найти шаблон редактора.', 'woo-price-editor'),
                esc_html__('Шаблон отсутствует', 'woo-price-editor'),
                ['response' => 500]
            );
        }

        $context = apply_filters('wpe_editor_context', $context);

        include $template;
        exit;
    }
}
