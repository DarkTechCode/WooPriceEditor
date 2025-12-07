# WooEditPrice
Плагин WordPress для редактирования цен WooCommerce

## Обзор

Этот проект предоставляет интерфейс для редактирования цен товаров WooCommerce с расширенными возможностями фильтрации, поиска и встроенного редактирования. Плагин имеет модульную структуру и расположен в директории `woo-price-editor/`.

## Новая структура плагина

Основной код плагина был рефакторен в стандартную структуру плагина WordPress:

**Расположение**: `/woo-price-editor/`

**Основные возможности**:
- ✓ Полноэкранный интерфейс редактора без отвлекающих элементов
- ✓ Операции с товарами через AJAX с надлежащей защитой
- ✓ Настраиваемые параметры (стартовая категория, колонки по умолчанию, инструкции)
- ✓ Использование встроенного в WordPress jQuery (без конфликтов версий)
- ✓ Полная документация в директории `docs/`
- ✓ Набор тестов PHPUnit с покрытием в директории `tests/`
- ✓ Совместимость с WordPress 6.0+ и последними версиями WooCommerce

**Документация**: См. `/woo-price-editor/README.md` для полной документации плагина

**Требования**:
- WordPress 6.0+
- PHP 7.4+
- WooCommerce 3.0+
- Право пользователя: `manage_woocommerce`

### Быстрые ссылки

- **Документация плагина**: [`woo-price-editor/README.md`](woo-price-editor/README.md)
- **Руководство по установке**: [`woo-price-editor/docs/installation.md`](woo-price-editor/docs/installation.md)
- **Документация по настройкам**: [`woo-price-editor/docs/settings.md`](woo-price-editor/docs/settings.md)
- **Справочник AJAX API**: [`woo-price-editor/docs/ajax-endpoints.md`](woo-price-editor/docs/ajax-endpoints.md)
- **Технические заметки**: [`woo-price-editor/docs/technical-notes.md`](woo-price-editor/docs/technical-notes.md)
- **Запуск тестов**: [`woo-price-editor/tests/README.md`](woo-price-editor/tests/README.md)

### Современная структура плагина

```
woo-price-editor/
├── assets/              # Файлы CSS и JavaScript
│   ├── css/
│   │   ├── editor.css
│   │   └── settings.css
│   └── js/
│       └── editor.js
├── docs/                # Полная документация
│   ├── installation.md
│   ├── capabilities.md
│   ├── settings.md
│   ├── ajax-endpoints.md
│   └── technical-notes.md
├── includes/            # PHP классы (паттерн MVC)
│   ├── class-wpe-plugin.php      # Основной контроллер плагина
│   ├── class-wpe-ajax.php        # Обработчики AJAX
│   ├── class-wpe-api.php         # Конечные точки REST API
│   ├── class-wpe-product.php     # Модель данных товара
│   ├── class-wpe-security.php    # Утилиты безопасности
│   └── class-wpe-settings.php    # Страница настроек
├── templates/           # Шаблоны представлений
│   └── editor-shell.php
├── tests/               # Набор тестов PHPUnit
│   ├── bootstrap.php
│   ├── phpunit.xml.dist
│   ├── README.md
│   ├── test-option-defaults.php
│   └── test-ajax-permissions.php
├── woo-price-editor.php # Основной файл плагина (загрузчик)
└── README.md            # Документация плагина
```

## Архитектура и структура

### Унаследованная файловая структура (Автономные страницы)

### Файловая структура

```
├── index.php              # Главная страница с навигацией
├── price_editor.php       # Основной интерфейс редактора цен
├── api/
│   └── standalone_api.php # API для обработки запросов
├── assets/
│   ├── css/
│   │   ├── base.css              # Базовые стили и сбросы
│   │   ├── header.css            # Стили заголовка страницы
│   │   ├── filters.css           # Стили секции фильтров
│   │   ├── column-manager.css    # Управление колонками
│   │   ├── table.css             # Стили таблицы и DataTables
│   │   ├── editing.css           # Редактируемые поля
│   │   ├── statuses.css          # Статусы товаров
│   │   ├── links-buttons.css     # Ссылки и кнопки
│   │   ├── system.css            # Системные элементы (модальные окна, уведомления)
│   │   ├── errors.css            # Область отображения ошибок
│   │   ├── indicators.css        # Индикаторы состояния
│   │   ├── datatables-custom.css # Кастомные стили DataTables
│   │   ├── responsive.css        # Адаптивные стили
│   │   └── jquery.dataTables.min.css # Стили DataTables
│   └── js/
│       ├── price_editor.js       # Точка входа и инициализация
│       ├── price_editor.core.js  # Основной класс PriceEditor
│       ├── price_editor.data.js  # Модуль работы с данными и API
│       ├── price_editor.editing.js # Модуль редактирования полей
│       ├── price_editor.ui.js    # Модуль UI компонентов
│       ├── price_editor.filters.js # Модуль фильтрации и поиска
│       ├── jquery-3.6.0.min.js   # jQuery
│       ├── jquery.dataTables.min.js # DataTables
│       └── datatables_ru.json    # Локализация DataTables
```

### Технологический стек

- **Backend**: PHP 7.4+, WordPress/WooCommerce API
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), jQuery 3.6.0
- **UI библиотеки**: DataTables для таблиц с серверной обработкой
- **База данных**: WordPress posts/meta + WooCommerce метаданные товаров

## Логика работы

### 1. Инициализация (price_editor.php)

```php
// Подключение WordPress
require_once __DIR__ . '/../wp-config.php';

// Проверка прав доступа
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('У вас нет прав для доступа к этой странице.'));
}
```

### 2. Инициализация Frontend (assets/js/price_editor.js)

Модульная архитектура JavaScript с разделением ответственности:

- **price_editor.js**: Точка входа, проверка зависимостей, инициализация
- **price_editor.core.js**: Основной класс `PriceEditor`, настройка DataTable
- **Модули**: data, editing, ui, filters для разделения логики

### 3. Поток данных

1. **Загрузка данных**: AJAX-запросы к `api/standalone_api.php`
2. **Отображение**: DataTable с кастомными рендерами для редактируемых полей
3. **Редактирование**: Встроенное редактирование с автосохранением
4. **Обновление**: Отправка изменений через API обратно в WooCommerce

## Конечные точки API (api/standalone_api.php)

### 1. Получение категорий товаров

```php
POST /api/standalone_api.php
Action: get_categories
Response: {
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Игрушки",
      "slug": "igrushki",
      "count": 45
    }
  ]
}
```

### 2. Получение налоговых классов

```php
POST /api/standalone_api.php
Action: get_tax_classes
Response: {
  "success": true,
  "data": [
    {"slug": "", "name": "Стандартный"},
    {"slug": "10", "name": "10 %"},
    {"slug": "Reduced rate", "name": "Reduced rate"}
  ]
}
```

### 3. Получение товаров с фильтрацией

```php
POST /api/standalone_api.php
Action: get_products
Parameters:
- status: фильтр по статусу (publish/draft/private)
- category: фильтр по категории (slug)
- search: поиск по названию, артикулу, ID
- tax_status: фильтр по налоговому статусу
Response: {
  "success": true,
  "data": {
    "products": [...],
    "total": 150,
    "page": 1,
    "per_page": 50
  }
}
```

### 4. Обновление товара

```php
POST /api/standalone_api.php
Action: update_product
Parameters:
- product_id: ID товара
- field: поле для обновления (title/regular_price/sale_price/tax_status/tax_class/stock_status)
- value: новое значение
Response: {
  "success": true,
  "data": {
    "message": "Установлена новая обычная цена товара #123: 1000 р. → 1200 р.",
    "old_value": "1000",
    "new_value": "1200"
  }
}
```

## Основные функции

### Frontend (JavaScript) - Модульная архитектура

#### price_editor.js (43 строки)

- Точка входа в приложение
- Проверка зависимостей (jQuery, DataTables)
- Инициализация главного класса PriceEditor
- Глобальные функции для обратной совместимости

#### price_editor.core.js (306 строк)

Основной класс и ядро системы:

- **Инициализация**: Создание экземпляров всех модулей
- **DataTable**: Настройка таблицы с кастомными рендерами
- **Методы делегирования**: Перенаправление вызовов к соответствующим модулям

#### price_editor.data.js (267 строк)

Модуль работы с данными:

- **Загрузка данных**: Категории и налоговые классы через AJAX
- **Взаимодействие с API**: Все запросы к backend
- **Автосохранение**: Автоматическое сохранение изменений при потере фокуса
- **Сохранение**: Функции сохранения для всех типов полей (название, цены, налоги, наличие)

#### price_editor.editing.js (346 строк)

Модуль редактирования полей:

- **UI элементы**: Создание полей input и select
- **Встроенное редактирование**: Обработка клика по тексту для редактирования
- **Привязка событий**: События для редактируемых полей
- **Функции отмены**: Восстановление исходных значений при отмене

#### price_editor.ui.js (95 строк)

Модуль пользовательского интерфейса:

- **Техническая информация**: Строка состояния внизу экрана
- **Уведомления**: Система уведомлений об операциях
- **Модальные окна**: Подтверждение действий
- **Обработка ошибок**: Отображение и управление ошибками

#### price_editor.filters.js (102 строки)

Модуль фильтрации и поиска:

- **Фильтры**: Обработка фильтров по статусу, категории, налоговому статусу
- **Поиск**: Реализация поиска с задержкой
- **Вспомогательные функции**: Работа со статусами и форматированием

#### Конфигурация DataTable

- Серверная обработка через AJAX
- Кастомные рендеры для редактируемых полей
- Отображение всех товаров без пагинации
- Сортировка по ID (по убыванию)
- Русская локализация

### Backend (PHP)

#### Интеграция с WooCommerce

```php
$product = wc_get_product($product_id);
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// Обновление различных полей
switch ($field) {
    case 'title':
        $product->set_name($value);
        break;
    case 'regular_price':
        $product->set_regular_price($value);
        break;
    case 'sale_price':
        $product->set_sale_price($value);
        break;
    case 'tax_status':
        $product->set_tax_status($value);
        break;
    case 'tax_class':
        $product->set_tax_class($value);
        break;
    case 'stock_status':
        $product->set_stock_status($value);
        break;
}
$success = $product->save();
```

#### SQL запросы для получения данных

```sql
-- Подсчет товаров
SELECT COUNT(DISTINCT p.ID)
FROM {$wpdb->posts} p
WHERE p.post_type = 'product' AND p.post_status IN ('publish', 'draft', 'private')

-- Получение товаров с метаданными
SELECT DISTINCT p.ID, p.post_title, p.post_status,
       pm_sku.meta_value as sku,
       pm_price.meta_value as regular_price,
       -- ... другие поля
FROM {$wpdb->posts} p
LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
-- ... JOIN с другими метаданными
```

## Безопасность

- **Проверка прав доступа**: Только пользователи с правом `manage_woocommerce`
- **Санитизация данных**: `sanitize_text_field()` для всех входящих данных
- **Подготовленные запросы**: `wpdb->prepare()` для SQL запросов
- **Защита от CSRF**: WordPress nonce (можно добавить)

## Возможности

### Фильтрация и поиск

- Фильтр колонок таблицы (какие отображать)
- Фильтр по статусу товара (опубликован/черновик/приватный)
- Фильтр по категории товара
- Поиск по названию, артикулу, ID товара
- Фильтр по налоговому статусу
- Фильтр по наличию

### Редактирование

- **Названия товаров**: Встроенное редактирование с кнопками сохранения/отмены
- **Цены**: Обычная цена и цена со скидкой с автосохранением
- **Налоговые настройки**: Статус налога и налоговый класс (аналогично наличию)
- **Наличие товара**: Статусы наличия с автосохранением
- **Автосохранение**: Мгновенное сохранение при изменении

### Особенности UI/UX

- Модульная архитектура для лучшей поддерживаемости
- Адаптивный дизайн для мобильных устройств
- Техническая строка состояния внизу экрана
- Система уведомлений об операциях
- Область отображения ошибок с автоскрытием
- Анимации и переходы для улучшения UX

### Навигация (index.php)

- Быстрые ссылки на различные админки
- Админка WordPress
- QR трекинг
- Настройки редиректов
- Настройка городов
- Админка бонусного сайта
- Базы заказов с различных сайтов

## Интеграция

### WordPress/WooCommerce

- Использует стандартные WordPress хуки и фильтры
- Интеграция с WooCommerce API для работы с товарами
- Поддержка мультикатегорий товаров
- Совместимость с произвольными полями WooCommerce

### Поддержка браузеров

- Современные браузеры с поддержкой ES6+
- jQuery 3.6.0+
- DataTables 1.10+

## Архитектурные преимущества

### Модульная структура JavaScript

- **Поддерживаемость**: Каждый модуль имеет четкую ответственность
- **Читаемость**: Код разбит на логические части (< 350 строк на модуль)
- **Расширяемость**: Легко добавлять новые функции и модули
- **Тестируемость**: Каждый модуль можно тестировать отдельно

### Порядок загрузки модулей

```html
<script src="assets/js/price_editor.ui.js"></script>
<script src="assets/js/price_editor.filters.js"></script>
<script src="assets/js/price_editor.data.js"></script>
<script src="assets/js/price_editor.editing.js"></script>
<script src="assets/js/price_editor.core.js"></script>
```
