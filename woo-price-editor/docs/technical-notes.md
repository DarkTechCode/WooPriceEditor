# Технические заметки

## Обзор

Этот документ предоставляет технические детали реализации Редактора цен WooCommerce, включая заметки о использовании jQuery, встроенного в WordPress, подходе полноэкранного рендеринга и другие архитектурные решения.

## jQuery встроенный в WordPress

### Почему jQuery WordPress?

Плагин использует jQuery, встроенный в WordPress, вместо загрузки отдельной версии, чтобы:

1. **Избежать конфликтов**: предотвращает конфликты версий jQuery с ядром WordPress и другими плагинами
2. **Сохранить совместимость**: обеспечивает совместимость с режимом noConflict WordPress
3. **Избежать избыточности**: предотвращает загрузку нескольких версий jQuery (пропускная способность/производительность)
4. **Следовать лучшим практикам**: соответствует стандартам разработки плагинов WordPress

### Реализация

jQuery подключается с использованием стандартной системы WordPress enqueue:

```php
// Подключить встроенный jQuery (не отдельную копию)
wp_enqueue_script('jquery');
```

**Расположение**: `includes/class-wpe-plugin.php` → `enqueue_admin_assets()`

### Версия jQuery

Версия jQuery зависит от версии WordPress:

- **WordPress 5.6+**: jQuery 3.5.1+ (перенесено с jQuery 1.x)
- **Современный WordPress**: jQuery 3.6.0+

### Режим noConflict

WordPress запускает jQuery в режиме noConflict, что означает, что `$` не доступен автоматически. Плагин обрабатывает это правильно:

**Правильное использование**:
```javascript
jQuery(document).ready(function($) {
    // $ теперь безопасно использовать в этой области
    $('#my-element').click(function() {
        // ...
    });
});

// Или обёрка в IIFE
(function($) {
    // $ безопасен здесь
})(jQuery);
```

**Неправильное использование** (не сработает):
```javascript
// Это не сработает в WordPress
$(document).ready(function() {
    // $ не определён
});
```

### Управление зависимостями

Плагин явно объявляет зависимости jQuery:

```php
wp_enqueue_script(
    'wpe-editor',
    WPE_PLUGIN_URL . 'assets/js/editor.js',
    ['jquery', 'datatables', 'wp-api-fetch'], // Зависимости
    WPE_PLUGIN_VERSION,
    true // Загрузить в footer
);
```

Это гарантирует:
- jQuery загружается перед скриптами плагина
- Порядок загрузки поддерживается
- Зависимости разрешаются автоматически

## Полноэкранный рендеринг

### Подход

Редактор использует пользовательский подход полноэкранного рендеринга, который обходит стандартный интерфейс администратора WordPress (админ-хром).

**Зачем полноэкранный?**

1. **Максимизированное рабочее пространство**: предоставляет максимальное пространство экрана для таблицы товаров
2. **Без отвлечений**: исключает боковую панель администратора и беспорядок в заголовке
3. **Производительность**: более лёгкий DOM, меньше скриптов для загрузки
4. **Лучший UX**: сосредоточенный интерфейс для задач массового редактирования

### Детали реализации

#### Последовательность загрузки

```php
public function render_fullscreen_shell() {
    // 1. Проверить доступ пользователя
    $this->ensure_user_can_access();

    // 2. Подготовить данные контекста
    $context = [
        'nonce'    => $this->editor_nonce,
        'settings' => get_option('wpe_editor_settings', self::get_default_options()),
        'user'     => wp_get_current_user(),
    ];

    // 3. Установить HTTP заголовки
    status_header(200);
    nocache_headers();

    // 4. Загрузить пользовательский шаблон
    $template = trailingslashit(WPE_PLUGIN_DIR) . 'templates/editor-shell.php';
    include $template;
    
    // 5. Выход для предотвращения продолжения WordPress
    exit;
}
```

**Расположение**: `includes/class-wpe-plugin.php`

#### Время Hook'а

Рендеринг запускается на хуке `load-{$page_hook}`:

```php
if (!empty($this->page_hook)) {
    add_action('load-' . $this->page_hook, [$this, 'render_fullscreen_shell']);
}
```

Этот хук срабатывает:
- После `admin_menu` 
- До `admin_enqueue_scripts`
- До рендеринга любого интерфейса администратора

#### Структура шаблона

Пользовательский шаблон (`templates/editor-shell.php`) включает:

1. **Полный HTML документ** — полные теги `<!DOCTYPE>` и `<html>`
2. **Действия head WordPress** — `wp_head()` для стилей/скриптов
3. **Пользовательский макет** — интерфейс полноэкранного редактора
4. **Действия footer WordPress** — `wp_footer()` для скриптов в footer'е

**Преимущества**:
- Полный контроль над структурой страницы
- Всё ещё можно использовать функции и хуки WordPress
- Подключённые ресурсы загружаются правильно
- Панель администратора WordPress может отображаться опционально

### Предотвращение загрузки хрома

Вызов `exit` после включения шаблона предотвращает загрузку админ-хрома WordPress:

```php
include $template;
exit; // Критически важно: предотвращает загрузку админ-хрома
```

Без этого выхода:
- WordPress продолжил бы рендеринг
- Боковая панель и заголовок администратора появились бы
- Страница не была бы полноэкранной

## Стратегия загрузки ресурсов

### Порядок загрузки CSS

```php
// 1. CSS DataTables (внешний CDN)
wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');

// 2. CSS плагина (зависит от CSS DataTables)
wp_enqueue_style('wpe-editor', WPE_PLUGIN_URL . 'assets/css/editor.css', ['datatables']);
```

### Порядок загрузки JavaScript

```php
// 1. jQuery (встроенный WordPress)
wp_enqueue_script('jquery');

// 2. DataTables (внешний CDN, зависит от jQuery)
wp_enqueue_script('datatables', 'https://cdn.datatables.net/.../jquery.dataTables.min.js', ['jquery']);

// 3. JS плагина (зависит от jQuery, DataTables и wp-api-fetch)
wp_enqueue_script('wpe-editor', WPE_PLUGIN_URL . 'assets/js/editor.js', ['jquery', 'datatables', 'wp-api-fetch']);
```

Все скрипты загружаются в footer (параметр `true`) для лучшей производительности загрузки страницы.

### Локализация скриптов

Конфигурация и переводы передаются в JavaScript через `wp_localize_script()`:

```php
wp_localize_script('wpe-editor', 'wpeData', [
    'ajaxUrl'        => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('wp_rest'),
    'pageLength'     => 50,
    'defaultColumns' => $settings['default_columns'],
    'startCategory'  => $settings['start_category'],
    'i18n'           => [ /* переводы */ ],
]);
```

Это создаёт глобальный объект JavaScript `wpeData`, доступный скриптам плагина.

## Запросы к базе данных

### Получение товаров

Плагин использует хранилище данных WooCommerce для получения товаров:

```php
$query_args = [
    'post_type'      => 'product',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => $per_page,
    'paged'          => $page,
    'orderby'        => $orderby,
    'order'          => $order,
];

// Добавить фильтры
if (!empty($category)) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $category,
        ],
    ];
}

$query = new WP_Query($query_args);
```

**Преимущества**:
- Использует кэширование запросов WordPress
- Совместим с пользовательскими установками WooCommerce
- Соблюдает фильтры и хуки WordPress

### Обновления товаров

Обновления используют методы объектов товаров WooCommerce:

```php
$product = wc_get_product($product_id);
$product->set_regular_price($value);
$product->save();
```

**Преимущества**:
- Запускает хуки WooCommerce
- Обеспечивает целостность данных
- Правильно обрабатывает вариации и сложные товары
- Валидирует данные через встроенную валидацию WooCommerce

## Архитектура безопасности

### Многоуровневая безопасность

1. **Уровень меню**: проверка прав при регистрации меню
2. **Уровень страницы**: проверка доступа перед рендерингом
3. **Уровень запроса**: проверка nonce и прав для каждого AJAX запроса
4. **Уровень товара**: проверка прав редактирования конкретного товара

### Стратегия Nonce

Плагин использует nonce REST API WordPress (`wp_rest`) для всех AJAX запросов:

```php
$nonce = wp_create_nonce('wp_rest');
```

**Почему `wp_rest` вместо пользовательского nonce?**
- Согласуется со стандартами WordPress
- Совместим с встроенными паттернами AJAX WordPress
- Автоматически обновляется WordPress
- Работает с heartbeat API WordPress

### Санитизация данных

Санитизация, специфичная для поля, через `WPE_Security::sanitize_field()`:

```php
public static function sanitize_field($field, $value) {
    switch ($field) {
        case 'title':
            $sanitized = sanitize_text_field($value);
            if (empty($sanitized)) {
                throw new InvalidArgumentException(__('Название не может быть пустым.'));
            }
            return $sanitized;

        case 'regular_price':
        case 'sale_price':
            if (!is_numeric($value)) {
                throw new InvalidArgumentException(__('Невалидное значение цены.'));
            }
            if ($value < 0) {
                throw new InvalidArgumentException(__('Цена не может быть отрицательной.'));
            }
            return wc_format_decimal($value);

        // ... больше случаев
    }
}
```

**Расположение**: `includes/class-wpe-security.php`

## Оптимизации производительности

### Серверная пагинация

Товары загружаются с серверной пагинацией:

```php
'posts_per_page' => $per_page, // По умолчанию: 50
'paged'          => $page,
```

Это предотвращает загрузку тысяч товаров одновременно.

### Выборочная загрузка колонок

Только запрошенные колонки обрабатываются:

```php
foreach ($products as $product_data) {
    $product_array = [
        'id'    => $product->get_id(),
        'title' => $product->get_name(),
        'sku'   => $product->get_sku(),
        // Загружаем только то, что нужно
    ];
}
```

### Кэширование через Transients (будущее улучшение)

Рассмотрите внедрение кэширования через transients для:
- Категорий товаров
- Налоговых классов
- Прав пользователей

Пример:
```php
$categories = get_transient('wpe_categories');
if (false === $categories) {
    $categories = $this->fetch_categories();
    set_transient('wpe_categories', $categories, HOUR_IN_SECONDS);
}
```

## Совместимость с браузерами

### Минимальные требования

- **Современные браузеры**: Chrome, Firefox, Safari, Edge (последние 2 версии)
- **JavaScript**: ES5+ (трансформация в настоящий момент не реализована)
- **CSS**: требуется поддержка CSS3

### Известные проблемы

- **IE11**: не поддерживается (отсутствуют возможности ES6, проблемы с Flexbox)
- **Мобильные устройства**: ограниченная поддержка (таблица не адаптивна по умолчанию)

### Polyfills

В настоящий момент polyfills не загружаются. Рассмотрите добавление для более широкой поддержки:
- `Promise` polyfill для старых браузеров
- `Fetch` polyfill если заменять AJAX вызовы
- `Object.assign` polyfill

## Совместимость WordPress

### Протестировано с

- **WordPress**: 6.0+
- **WooCommerce**: 7.0+
- **PHP**: 7.4, 8.0, 8.1, 8.2

### Требуемые возможности WordPress

- `wp_ajax_` хуки
- Settings API
- `wp_enqueue_script/style()`
- `wp_localize_script()`
- Admin menu API
- Система nonce

### Интеграция WooCommerce

**Требуемые функции**:
- `wc_get_product()` — получение объекта товара
- `wc_format_decimal()` — форматирование цен
- `WC_Tax::get_tax_classes()` — получение налоговых классов
- Setters/getters объекта товара

**Опциональные улучшения**:
- `wc_get_product_types()` — поддержка всех типов товаров
- `WC_Cache_Helper` — инвалидация кэша
- WooCommerce REST API — альтернатива AJAX

## Отладка

### Включите режим отладки WordPress

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Логи будут записаны в `wp-content/debug.log`.

### Логирование отладки плагина

Плагин логирует события через `WPE_Security::log_event()`:

```php
WPE_Security::log_event('ajax_error', [
    'action' => 'update_product',
    'error'  => $e->getMessage(),
]);
```

**Расположение**: проверьте `wp-content/debug.log` для записей.

### Консоль браузера

Редактор логирует активность в консоль браузера:

```javascript
console.log('Товар обновлён:', response.data);
console.error('Ошибка сохранения:', error);
```

### Отладка AJAX

Мониторьте AJAX запросы в браузере DevTools:
1. Откройте DevTools (F12)
2. Перейдите на вкладку Network
3. Фильтруйте по "XHR"
4. Смотрите запросы к `admin-ajax.php`

## Расширение плагина

### Добавление пользовательских полей

Чтобы добавить пользовательское поле:

1. **Добавить в обработчик товара** (`class-wpe-product.php`):
```php
case 'custom_field':
    $product_array['custom_field'] = get_post_meta($product->get_id(), '_custom_field', true);
    break;
```

2. **Добавить санитизацию** (`class-wpe-security.php`):
```php
case 'custom_field':
    return sanitize_text_field($value);
```

3. **Добавить в обработчик AJAX** (если нужен для обновлений)

4. **Обновить frontend** для отображения/редактирования поля

### Добавление хуков

Плагин предоставляет фильтры для расширения:

```php
// Изменить контекст редактора перед рендерингом
add_filter('wpe_editor_context', function($context) {
    $context['custom_data'] = get_custom_data();
    return $context;
});
```

### Добавление пользовательских конечных точек AJAX

```php
add_action('wp_ajax_wpe_custom_action', function() {
    $ajax = new WPE_AJAX();
    // Использовать существующую верификацию
    $verify = $ajax->verify_request();
    if (is_wp_error($verify)) {
        wp_send_json_error($verify->get_error_message());
    }
    
    // Ваша логика здесь
    wp_send_json_success(['data' => 'your data']);
});
```

## Лучшие практики разработки

### Кодирование

1. Следуйте стандартам кодирования WordPress
2. Используйте соответствующие функции санитизации WordPress
3. Проверяйте права доступа перед выполнением любых операций
4. Используйте текстовый домен `woo-price-editor` для всех транслируемых строк

### Производительность

1. Избегайте прямых SQL запросов — используйте WordPress функции
2. Используйте пагинацию для больших наборов данных
3. Кэшируйте часто используемые данные
4. Минимизируйте количество AJAX запросов

### Безопасность

1. Всегда проверяйте права перед выполнением действий
2. Санитизируйте все входящие данные
3. Используйте nonce для всех форм и AJAX запросов
4. Логируйте подозрительные события
