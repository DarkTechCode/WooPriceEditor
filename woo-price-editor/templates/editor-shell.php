<?php
/**
 * Автономный административный шаблон для оболочки редактора цен Woo.
 *
 * @var array $context
 *
 * @package WooPriceEditor
 */

defined('ABSPATH') || exit;

$context = wp_parse_args(
    $context,
    [
        'nonce'    => '',
        'settings' => [],
        'user'     => wp_get_current_user(),
    ]
);

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Редактор цен Woo', 'woo-price-editor'); ?></title>
    <style>
        /* Загрузка стилей администратора WordPress */
        .dashicons {
            font-family: dashicons;
            display: inline-block;
            line-height: 1;
            font-weight: 400;
            font-style: normal;
            speak: never;
            text-decoration: inherit;
            text-transform: none;
            text-rendering: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            width: 1em;
            height: 1em;
            font-size: 20px;
            vertical-align: middle;
            text-align: center;
        }
        
        /* Совместимость со спиннером WordPress */
        .spinner {
            background: url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSIjMjI3MWIxIj48Y2lyY2xlIGN4PSIzIiBjeT0iMTAiIHI9IjIiLz48Y2lyY2xlIGN4PSIxMCIgY3k9IjMiIHI9IjIiLz48Y2lyY2xlIGN4PSIxNyIgY3k9IjEwIiByPSIyIi8+PGNpcmNsZSBjeD0iMTAiIGN5PSIxNyIgcj0iMiIvPjwvZz48L3N2Zz4=) no-repeat center;
            background-size: 20px 20px;
            display: inline-block;
            visibility: hidden;
            opacity: 0.7;
            width: 20px;
            height: 20px;
            margin: 0;
            vertical-align: middle;
        }

        .spinner.is-active {
            visibility: visible;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner.is-active {
            animation: spin 1s linear infinite;
        }
    </style>
    <?php do_action('admin_enqueue_scripts', 'toplevel_page_woo-price-editor'); ?>
    <?php wp_head(); ?>
</head>
<body class="wpe-shell" data-wpe-nonce="<?php echo esc_attr($context['nonce']); ?>">
<header class="wpe-header">
    <div class="wpe-branding">
        <div class="wpe-logo">W</div>
        <div>
            <div><?php esc_html_e('Редактор цен Woo', 'woo-price-editor'); ?></div>
            <small><?php esc_html_e('Полноэкранный редактор цен с встроенным редактированием', 'woo-price-editor'); ?></small>
        </div>
    </div>
    <div class="wpe-user">
        <?php echo esc_html($context['user'] instanceof WP_User ? $context['user']->display_name : ''); ?>
    </div>
</header>
<main class="wpe-main">
    <div class="wpe-editor-header">
        <h1 class="wpe-editor-title"><?php esc_html_e('Редактор цен товаров', 'woo-price-editor'); ?></h1>
        <div class="wpe-editor-instructions">
            <?php 
            $instructions = $context['settings']['instructions'] ?? '';
            if (!empty($instructions)) {
                echo '<div class="wpe-instructions-content">';
                echo wpautop(wp_kses_post($instructions));
                echo '</div>';
            } else {
                echo '<p class="wpe-editor-description">' . esc_html__('Редактируйте цены, названия и другие поля товаров напрямую. Изменения сохраняются автоматически.', 'woo-price-editor') . '</p>';
            }
            ?>
        </div>
    </div>
    <div class="wpe-editor-container">
        <!-- Раздел фильтров -->
        <div class="wpe-filters">
            <div class="wpe-filters-row">
                <!-- Поиск -->
                <div class="wpe-filter-group wpe-search-group">
                    <label for="wpe-search" class="screen-reader-text">
                        <?php esc_html_e('Поиск', 'woo-price-editor'); ?>
                    </label>
                    <input 
                        type="search" 
                        id="wpe-search" 
                        class="wpe-search-input" 
                        placeholder="<?php esc_attr_e('Поиск по названию, артикулу или ID...', 'woo-price-editor'); ?>"
                    >
                </div>

                <!-- Фильтр по статусу -->
                <div class="wpe-filter-group">
                    <label for="wpe-status-filter" class="screen-reader-text">
                        <?php esc_html_e('Статус', 'woo-price-editor'); ?>
                    </label>
                    <select id="wpe-status-filter" class="wpe-filter-select" data-filter="status">
                        <option value=""><?php esc_html_e('Все статусы', 'woo-price-editor'); ?></option>
                        <option value="publish"><?php esc_html_e('Опубликован', 'woo-price-editor'); ?></option>
                        <option value="draft"><?php esc_html_e('Черновик', 'woo-price-editor'); ?></option>
                        <option value="private"><?php esc_html_e('Приватный', 'woo-price-editor'); ?></option>
                        <option value="pending"><?php esc_html_e('Ожидает', 'woo-price-editor'); ?></option>
                    </select>
                </div>

                <!-- Фильтр по категории -->
                <div class="wpe-filter-group">
                    <label for="wpe-category-filter" class="screen-reader-text">
                        <?php esc_html_e('Категория', 'woo-price-editor'); ?>
                    </label>
                    <select id="wpe-category-filter" class="wpe-filter-select" data-filter="category">
                        <option value=""><?php esc_html_e('Все категории', 'woo-price-editor'); ?></option>
                        <!-- Заполняется через JavaScript -->
                    </select>
                </div>

                <!-- Фильтр налогового статуса -->
                <div class="wpe-filter-group">
                    <label for="wpe-tax-filter" class="screen-reader-text">
                        <?php esc_html_e('Налоговый статус', 'woo-price-editor'); ?>
                    </label>
                    <select id="wpe-tax-filter" class="wpe-filter-select" data-filter="tax_status">
                        <option value=""><?php esc_html_e('Все налоговые статусы', 'woo-price-editor'); ?></option>
                        <option value="taxable"><?php esc_html_e('Облагается налогом', 'woo-price-editor'); ?></option>
                        <option value="shipping"><?php esc_html_e('Только доставка', 'woo-price-editor'); ?></option>
                        <option value="none"><?php esc_html_e('Нет', 'woo-price-editor'); ?></option>
                    </select>
                </div>

                <!-- Фильтр статуса наличия -->
                <div class="wpe-filter-group">
                    <label for="wpe-stock-filter" class="screen-reader-text">
                        <?php esc_html_e('Статус наличия', 'woo-price-editor'); ?>
                    </label>
                    <select id="wpe-stock-filter" class="wpe-filter-select" data-filter="stock_status">
                        <option value=""><?php esc_html_e('Все статусы наличия', 'woo-price-editor'); ?></option>
                        <option value="instock"><?php esc_html_e('В наличии', 'woo-price-editor'); ?></option>
                        <option value="outofstock"><?php esc_html_e('Нет в наличии', 'woo-price-editor'); ?></option>
                        <option value="onbackorder"><?php esc_html_e('Под заказ', 'woo-price-editor'); ?></option>
                    </select>
                </div>

                <!-- Сброс фильтров -->
                <div class="wpe-filter-group">
                    <button type="button" id="wpe-reset-filters" class="button">
                        <?php esc_html_e('Сброс', 'woo-price-editor'); ?>
                    </button>
                </div>
            </div>

            <!-- Видимость колонок -->
            <div class="wpe-column-toggles">
                <span class="wpe-column-toggles-label"><?php esc_html_e('Колонки:', 'woo-price-editor'); ?></span>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="sku" checked>
                    <?php esc_html_e('Артикул', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="status" checked>
                    <?php esc_html_e('Статус', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="regular_price" checked>
                    <?php esc_html_e('Обычная цена', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="sale_price" checked>
                    <?php esc_html_e('Цена со скидкой', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="tax_status">
                    <?php esc_html_e('Налоговый статус', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="tax_class">
                    <?php esc_html_e('Налоговый класс', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="stock_status" checked>
                    <?php esc_html_e('Наличие', 'woo-price-editor'); ?>
                </label>
                <label class="wpe-column-toggle">
                    <input type="checkbox" data-column="categories">
                    <?php esc_html_e('Категории', 'woo-price-editor'); ?>
                </label>
            </div>
        </div>

        <!-- Область уведомлений -->
        <div id="wpe-notifications" class="wpe-notifications" aria-live="polite"></div>

        <!-- Область ошибок -->
        <div id="wpe-errors" class="wpe-errors" role="alert"></div>

        <!-- Таблица товаров -->
        <div class="wpe-table-container">
            <table id="wpe-products-table" class="wpe-products-table wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th class="wpe-col-id"><?php esc_html_e('ID', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-title"><?php esc_html_e('Название', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-sku"><?php esc_html_e('Артикул', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-status"><?php esc_html_e('Статус', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-regular-price"><?php esc_html_e('Обычная цена', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-sale-price"><?php esc_html_e('Цена со скидкой', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-tax-status"><?php esc_html_e('Налоговый статус', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-tax-class"><?php esc_html_e('Налоговый класс', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-stock-status"><?php esc_html_e('Наличие', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-categories"><?php esc_html_e('Категории', 'woo-price-editor'); ?></th>
                        <th class="wpe-col-actions"><?php esc_html_e('Действия', 'woo-price-editor'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Заполняется через JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Оверлей загрузки -->
        <div id="wpe-loading" class="wpe-loading" aria-hidden="true">
            <div class="wpe-loading-spinner">
                <div class="spinner"></div>
                <span class="wpe-loading-text"><?php esc_html_e('Загрузка...', 'woo-price-editor'); ?></span>
            </div>
        </div>

        <!-- Строка состояния -->
        <div class="wpe-status-bar">
            <div class="wpe-status-info">
                <span id="wpe-record-count">—</span>
            </div>
            <div class="wpe-status-links">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" target="_blank">
                    <?php esc_html_e('Все товары', 'woo-price-editor'); ?>
                </a>
                <span class="wpe-separator">|</span>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" target="_blank">
                    <?php esc_html_e('Добавить товар', 'woo-price-editor'); ?>
                </a>
                <span class="wpe-separator">|</span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=tax')); ?>" target="_blank">
                    <?php esc_html_e('Настройки налогов', 'woo-price-editor'); ?>
                </a>
            </div>
        </div>
    </div>
</main>
<?php wp_footer(); ?>
</body>
</html>
