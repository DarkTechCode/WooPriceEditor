# Technical Notes

## Overview

This document provides technical details about the Woo Price Editor implementation, including notes about WordPress-bundled jQuery usage, full-screen rendering approach, and other architectural decisions.

## WordPress-Bundled jQuery

### Why WordPress jQuery?

The plugin uses WordPress's bundled jQuery instead of loading a separate version to:

1. **Avoid Conflicts**: Prevents jQuery version conflicts with WordPress core and other plugins
2. **Maintain Compatibility**: Ensures compatibility with WordPress's noConflict mode
3. **Reduce Redundancy**: Avoids loading multiple jQuery versions (bandwidth/performance)
4. **Follow Best Practices**: Aligns with WordPress plugin development standards

### Implementation

jQuery is enqueued using WordPress's standard enqueue system:

```php
// Enqueue WordPress jQuery (not a separate copy)
wp_enqueue_script('jquery');
```

**Location**: `includes/class-wpe-plugin.php` → `enqueue_admin_assets()`

### jQuery Version

The jQuery version used depends on the WordPress version:

- **WordPress 5.6+**: jQuery 3.5.1+ (migrated from jQuery 1.x)
- **Modern WordPress**: jQuery 3.6.0+

### noConflict Mode

WordPress runs jQuery in noConflict mode, meaning `$` is not automatically available. The plugin handles this correctly:

**Correct Usage**:
```javascript
jQuery(document).ready(function($) {
    // $ is now safe to use within this scope
    $('#my-element').click(function() {
        // ...
    });
});

// Or wrap in IIFE
(function($) {
    // $ is safe here
})(jQuery);
```

**Incorrect Usage** (will fail):
```javascript
// This will not work in WordPress
$(document).ready(function() {
    // $ is undefined
});
```

### Dependency Management

The plugin declares jQuery dependencies explicitly:

```php
wp_enqueue_script(
    'wpe-editor',
    WPE_PLUGIN_URL . 'assets/js/editor.js',
    ['jquery', 'datatables', 'wp-api-fetch'], // Dependencies
    WPE_PLUGIN_VERSION,
    true // Load in footer
);
```

This ensures:
- jQuery loads before the plugin's scripts
- Proper load order is maintained
- Dependencies are resolved automatically

## Full-Screen Rendering

### Approach

The editor uses a custom full-screen rendering approach that bypasses the standard WordPress admin interface (admin chrome).

**Why Full-Screen?**

1. **Maximized Workspace**: Provides maximum screen real estate for the product table
2. **Distraction-Free**: Eliminates WordPress admin sidebar and header clutter
3. **Performance**: Lighter DOM, fewer scripts to load
4. **Better UX**: Focused interface for bulk editing tasks

### Implementation Details

#### Loading Sequence

```php
public function render_fullscreen_shell() {
    // 1. Verify user has access
    $this->ensure_user_can_access();

    // 2. Prepare context data
    $context = [
        'nonce'    => $this->editor_nonce,
        'settings' => get_option('wpe_editor_settings', self::get_default_options()),
        'user'     => wp_get_current_user(),
    ];

    // 3. Set HTTP headers
    status_header(200);
    nocache_headers();

    // 4. Load custom template
    $template = trailingslashit(WPE_PLUGIN_DIR) . 'templates/editor-shell.php';
    include $template;
    
    // 5. Exit to prevent WordPress from continuing
    exit;
}
```

**Location**: `includes/class-wpe-plugin.php`

#### Hook Timing

The render is triggered on the `load-{$page_hook}` action:

```php
if (!empty($this->page_hook)) {
    add_action('load-' . $this->page_hook, [$this, 'render_fullscreen_shell']);
}
```

This hook fires:
- After `admin_menu` 
- Before `admin_enqueue_scripts`
- Before any admin interface HTML is rendered

#### Template Structure

The custom template (`templates/editor-shell.php`) includes:

1. **Full HTML document** - Complete `<!DOCTYPE>` and `<html>` tags
2. **WordPress head actions** - `wp_head()` for enqueued styles/scripts
3. **Custom layout** - Full-screen editor interface
4. **WordPress footer actions** - `wp_footer()` for enqueued footer scripts

**Benefits**:
- Complete control over page structure
- Can still use WordPress functions and hooks
- Enqueued assets still load correctly
- WordPress admin bar can optionally be displayed

### Preventing Chrome Load

The `exit` call after including the template prevents WordPress from loading admin chrome:

```php
include $template;
exit; // Critical: prevents admin chrome from loading
```

Without this exit:
- WordPress would continue rendering
- Admin sidebar and header would appear
- Page would not be full-screen

## Asset Loading Strategy

### CSS Loading Order

```php
// 1. DataTables CSS (external CDN)
wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');

// 2. Plugin CSS (depends on DataTables CSS)
wp_enqueue_style('wpe-editor', WPE_PLUGIN_URL . 'assets/css/editor.css', ['datatables']);
```

### JavaScript Loading Order

```php
// 1. jQuery (WordPress bundled)
wp_enqueue_script('jquery');

// 2. DataTables (external CDN, depends on jQuery)
wp_enqueue_script('datatables', 'https://cdn.datatables.net/.../jquery.dataTables.min.js', ['jquery']);

// 3. Plugin JS (depends on jQuery, DataTables, and wp-api-fetch)
wp_enqueue_script('wpe-editor', WPE_PLUGIN_URL . 'assets/js/editor.js', ['jquery', 'datatables', 'wp-api-fetch']);
```

All scripts load in footer (`true` parameter) for better page load performance.

### Script Localization

Configuration and translations are passed to JavaScript via `wp_localize_script()`:

```php
wp_localize_script('wpe-editor', 'wpeData', [
    'ajaxUrl'        => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('wp_rest'),
    'pageLength'     => 50,
    'defaultColumns' => $settings['default_columns'],
    'startCategory'  => $settings['start_category'],
    'i18n'           => [ /* translations */ ],
]);
```

This creates a global `wpeData` JavaScript object available to the plugin's scripts.

## Database Queries

### Product Retrieval

The plugin uses WooCommerce's data store for product retrieval:

```php
$query_args = [
    'post_type'      => 'product',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => $per_page,
    'paged'          => $page,
    'orderby'        => $orderby,
    'order'          => $order,
];

// Add filters
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

**Benefits**:
- Leverages WordPress query caching
- Compatible with custom WooCommerce installations
- Respects WordPress filters and hooks

### Product Updates

Updates use WooCommerce's product object methods:

```php
$product = wc_get_product($product_id);
$product->set_regular_price($value);
$product->save();
```

**Benefits**:
- Triggers WooCommerce hooks
- Ensures data integrity
- Handles variation and complex products correctly
- Validates data through WooCommerce's built-in validation

## Security Architecture

### Multi-Layer Security

1. **Menu Level**: Capability check when registering menu
2. **Page Level**: Access verification before rendering
3. **Request Level**: Nonce and capability check on every AJAX request
4. **Product Level**: Specific product edit permission check

### Nonce Strategy

The plugin uses WordPress's REST API nonce (`wp_rest`) for all AJAX requests:

```php
$nonce = wp_create_nonce('wp_rest');
```

**Why `wp_rest` instead of custom nonce?**
- Consistent with WordPress standards
- Compatible with WordPress's built-in AJAX patterns
- Automatically refreshed by WordPress
- Works with WordPress's heartbeat API

### Data Sanitization

Field-specific sanitization via `WPE_Security::sanitize_field()`:

```php
public static function sanitize_field($field, $value) {
    switch ($field) {
        case 'title':
            $sanitized = sanitize_text_field($value);
            if (empty($sanitized)) {
                throw new InvalidArgumentException(__('Title cannot be empty.'));
            }
            return $sanitized;

        case 'regular_price':
        case 'sale_price':
            if (!is_numeric($value)) {
                throw new InvalidArgumentException(__('Invalid price value.'));
            }
            if ($value < 0) {
                throw new InvalidArgumentException(__('Price cannot be negative.'));
            }
            return wc_format_decimal($value);

        // ... more cases
    }
}
```

**Location**: `includes/class-wpe-security.php`

## Performance Optimizations

### Server-Side Pagination

Products are loaded with server-side pagination:

```php
'posts_per_page' => $per_page, // Default: 50
'paged'          => $page,
```

This prevents loading thousands of products at once.

### Selective Column Loading

Only requested columns are processed:

```php
foreach ($products as $product_data) {
    $product_array = [
        'id'    => $product->get_id(),
        'title' => $product->get_name(),
        'sku'   => $product->get_sku(),
        // Only load what's needed
    ];
}
```

### Transient Caching (Future Enhancement)

Consider implementing transient caching for:
- Product categories
- Tax classes
- User permissions

Example:
```php
$categories = get_transient('wpe_categories');
if (false === $categories) {
    $categories = $this->fetch_categories();
    set_transient('wpe_categories', $categories, HOUR_IN_SECONDS);
}
```

## Browser Compatibility

### Minimum Requirements

- **Modern browsers**: Chrome, Firefox, Safari, Edge (latest 2 versions)
- **JavaScript**: ES5+ (transpilation not currently implemented)
- **CSS**: CSS3 support required

### Known Issues

- **IE11**: Not supported (lacks ES6 features, Flexbox issues)
- **Mobile**: Limited support (table is not responsive by default)

### Polyfills

Currently no polyfills are loaded. Consider adding for broader support:
- `Promise` polyfill for older browsers
- `Fetch` polyfill if replacing AJAX calls
- `Object.assign` polyfill

## WordPress Compatibility

### Tested With

- **WordPress**: 6.0+
- **WooCommerce**: 7.0+
- **PHP**: 7.4, 8.0, 8.1, 8.2

### Required WordPress Features

- `wp_ajax_` hooks
- Settings API
- `wp_enqueue_script/style()`
- `wp_localize_script()`
- Admin menu API
- Nonce system

### WooCommerce Integration

**Required Functions**:
- `wc_get_product()` - Retrieve product object
- `wc_format_decimal()` - Format prices
- WC_Tax::get_tax_classes()` - Get tax classes
- Product object setters/getters

**Optional Enhancements**:
- `wc_get_product_types()` - Support all product types
- `WC_Cache_Helper` - Cache invalidation
- WooCommerce REST API - Alternative to AJAX

## Debugging

### Enable WordPress Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Logs will be written to `wp-content/debug.log`.

### Plugin Debug Logging

The plugin logs events via `WPE_Security::log_event()`:

```php
WPE_Security::log_event('ajax_error', [
    'action' => 'update_product',
    'error'  => $e->getMessage(),
]);
```

**Location**: Check `wp-content/debug.log` for entries.

### Browser Console

The editor logs activity to browser console:

```javascript
console.log('Product updated:', response.data);
console.error('Failed to save:', error);
```

### AJAX Debugging

Monitor AJAX requests in browser DevTools:
1. Open DevTools (F12)
2. Navigate to Network tab
3. Filter by "XHR"
4. Watch for requests to `admin-ajax.php`

## Extending the Plugin

### Adding Custom Fields

To add a custom field:

1. **Add to product handler** (`class-wpe-product.php`):
```php
case 'custom_field':
    $product_array['custom_field'] = get_post_meta($product->get_id(), '_custom_field', true);
    break;
```

2. **Add sanitization** (`class-wpe-security.php`):
```php
case 'custom_field':
    return sanitize_text_field($value);
```

3. **Add to AJAX handler** (if needed for updates)

4. **Update frontend** to display/edit the field

### Adding Hooks

The plugin provides filters for extension:

```php
// Modify editor context before rendering
add_filter('wpe_editor_context', function($context) {
    $context['custom_data'] = get_custom_data();
    return $context;
});
```

### Adding Custom AJAX Endpoints

```php
add_action('wp_ajax_wpe_custom_action', function() {
    $ajax = new WPE_AJAX();
    // Use existing verification
    $verify = $ajax->verify_request();
    if (is_wp_error($verify)) {
        wp_send_json_error($verify->get_error_message());
    }
    
    // Your custom logic
    $result = do_custom_thing();
    wp_send_json_success($result);
});
```

## Future Improvements

### Planned Enhancements

1. **Bulk Operations**: Select multiple products and update at once
2. **Export/Import**: CSV export and import functionality
3. **Revision History**: Track changes to products
4. **Advanced Filtering**: More filter options and saved filter sets
5. **Keyboard Shortcuts**: Navigate and edit with keyboard
6. **Mobile Responsive**: Better mobile/tablet support
7. **Real-time Updates**: WebSocket support for multi-user editing

### Performance Enhancements

1. **Lazy Loading**: Load products as user scrolls
2. **Query Optimization**: Custom database queries for complex filters
3. **AJAX Queue**: Prevent concurrent request conflicts
4. **Local Storage**: Cache categories and tax classes client-side

### Security Enhancements

1. **Rate Limiting**: Throttle requests per user
2. **Audit Log**: Detailed change history
3. **Two-Factor Auth**: Support for 2FA plugins
4. **IP Whitelisting**: Restrict access by IP

## Additional Resources

### WordPress Documentation

- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [AJAX in Plugins](https://developer.wordpress.org/plugins/javascript/ajax/)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)

### WooCommerce Documentation

- [WooCommerce Docs](https://woocommerce.com/documentation/)
- [Product CRUD](https://github.com/woocommerce/woocommerce/wiki/CRUD-Objects-in-3.0)
- [WC Data Stores](https://github.com/woocommerce/woocommerce/wiki/Data-Stores)

### Libraries Used

- **DataTables**: https://datatables.net/
- **jQuery**: https://jquery.com/
