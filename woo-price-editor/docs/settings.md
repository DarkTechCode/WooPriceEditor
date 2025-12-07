# Settings Documentation

## Overview

The Woo Price Editor provides configurable settings to customize the editor's default behavior and appearance. Settings are accessible from **WordPress Admin** → **Price Editor** → **Settings**.

## Settings Location

- **Menu Path**: Price Editor → Settings
- **Capability Required**: `manage_woocommerce`
- **Option Name**: `wpe_editor_settings` (stored in wp_options table)
- **Implementation**: WordPress Settings API

## Available Settings

### 1. Start Category

**Description**: Controls which product category is selected by default when the editor loads.

**Field Type**: Dropdown select

**Options**:
- **All Products** (value: `all`) - Shows all products regardless of category
- Individual product categories from your WooCommerce store

**Default Value**: `all`

**Use Case**: If your store has multiple categories and you primarily edit products in a specific category, you can set that category as the default to save time.

**Example**:
```php
$settings = get_option('wpe_editor_settings');
$start_category = $settings['start_category']; // 'all' or category slug
```

### 2. Default Columns

**Description**: Determines which columns are visible by default in the editor table.

**Field Type**: Multi-checkbox

**Available Columns**:
- **SKU** - Product SKU/part number
- **Status** - Product status (Published, Draft, Private, Pending)
- **Regular Price** - Base product price
- **Sale Price** - Discounted price (if applicable)
- **Tax Status** - Tax application setting (Taxable, Shipping only, None)
- **Tax Class** - Tax classification
- **Stock Status** - Inventory status (In Stock, Out of Stock, On Backorder)
- **Categories** - Product categories

**Default Values**:
```php
[
    'sku',
    'regular_price',
    'sale_price',
    'stock_status',
    'tax_status',
]
```

**Note**: The **Product** (title) and **Actions** columns are always visible and cannot be disabled.

**Use Case**: Customize the default view based on your workflow. For example, if you rarely modify tax settings, you can hide those columns by default.

**Programmatic Access**:
```php
$settings = get_option('wpe_editor_settings');
$default_columns = $settings['default_columns']; // Array of column keys
```

### 3. Instructions

**Description**: Customizable help text displayed at the top of the editor interface to guide users.

**Field Type**: Multi-line textarea

**Default Value**: 
```
Welcome to the Woo Price Editor! This tool allows you to quickly edit product information in bulk.

How to use:
• Click on any editable field to modify it directly
• Price fields: Enter new prices and they'll be saved automatically when you click away
• Title field: Click to edit, then press Enter to save or Escape to cancel
• Dropdown fields: Select new values from the dropdown menu
• Use the filters above the table to narrow down products
• Toggle column visibility using the column checkboxes
• All changes are saved automatically to your WooCommerce store

Tips:
• Use the search bar to find products by title, SKU, or ID
• Filter by category, status, tax status, or stock status
• Click the edit or view icons to open products in the standard WooCommerce interface
```

**Use Case**: Customize instructions for your team's specific workflow or add store-specific guidelines.

**Formatting**: Basic HTML is allowed via `wp_kses_post()` for simple formatting (paragraphs, line breaks, etc.)

## Configuration Methods

### Via WordPress Admin Interface

1. Navigate to **Price Editor** → **Settings**
2. Modify the desired settings
3. Click **Save Changes**
4. Settings are validated and sanitized automatically

### Programmatically via Code

#### Reading Settings

```php
// Get all settings
$settings = get_option('wpe_editor_settings', WPE_Plugin::get_default_options());

// Access individual settings
$start_category = $settings['start_category'] ?? 'all';
$default_columns = $settings['default_columns'] ?? [];
$instructions = $settings['instructions'] ?? '';
```

#### Updating Settings

```php
// Get current settings
$settings = get_option('wpe_editor_settings', []);

// Modify specific setting
$settings['start_category'] = 'electronics';

// Update in database
update_option('wpe_editor_settings', $settings);
```

#### Resetting to Defaults

```php
$defaults = WPE_Plugin::get_default_options();
update_option('wpe_editor_settings', $defaults);
```

## Settings Validation

The plugin automatically validates and sanitizes all settings:

### Start Category Validation

- Verifies the category exists using `get_term_by()`
- Falls back to `'all'` if invalid category is provided
- Displays admin notice if validation fails

```php
$category = get_term_by('slug', $input['start_category'], 'product_cat');
if ($category && !is_wp_error($category)) {
    $sanitized['start_category'] = $category->slug;
} else {
    // Fallback to default
    $sanitized['start_category'] = 'all';
}
```

### Default Columns Validation

- Checks columns against list of available columns
- Removes invalid column keys
- Ensures at least one column is selected
- Displays admin notice if no columns selected

```php
$valid_columns = ['sku', 'status', 'regular_price', 'sale_price', 'tax_status', 'tax_class', 'stock_status', 'categories'];
$sanitized['default_columns'] = array_intersect($input['default_columns'], $valid_columns);

if (empty($sanitized['default_columns'])) {
    // Fallback to defaults
    $sanitized['default_columns'] = $defaults['default_columns'];
}
```

### Instructions Validation

- Sanitizes HTML using `wp_kses_post()`
- Falls back to default instructions if empty
- Removes potentially harmful scripts and tags

```php
$sanitized['instructions'] = wp_kses_post($input['instructions']);
if (empty(trim($sanitized['instructions']))) {
    $sanitized['instructions'] = $this->get_default_instructions();
}
```

## Plugin Activation

During plugin activation, default settings are automatically created if they don't exist:

```php
public static function activate() {
    $defaults = self::get_default_options();
    $options  = get_option('wpe_editor_settings');

    if (false === $options) {
        add_option('wpe_editor_settings', $defaults);
    } else {
        // Merge with defaults for upgrades
        $updated_options = wp_parse_args((array) $options, $defaults);
        update_option('wpe_editor_settings', $updated_options);
    }
}
```

This ensures:
- Fresh installations get default settings
- Existing installations receive new settings when plugin is updated
- User customizations are preserved during updates

## Settings in JavaScript

Settings are localized to JavaScript for use in the editor interface:

```javascript
// Settings available in wpeData object
const startCategory = wpeData.startCategory;  // 'all' or category slug
const defaultColumns = wpeData.defaultColumns; // Array of column keys

// Example: Initialize filters with start category
if (startCategory !== 'all') {
    $('#filter-category').val(startCategory).trigger('change');
}

// Example: Show/hide columns based on defaults
defaultColumns.forEach(function(column) {
    $('input[value="' + column + '"]').prop('checked', true);
});
```

**Location**: `includes/class-wpe-plugin.php` → `enqueue_admin_assets()`

## Database Storage

Settings are stored as a serialized array in the WordPress options table:

**Table**: `wp_options`
**Option Name**: `wpe_editor_settings`
**Autoload**: yes (loaded on every page for performance)

**Example Database Row**:
```
option_name: wpe_editor_settings
option_value: a:3:{s:14:"start_category";s:3:"all";s:15:"default_columns";a:5:{i:0;s:3:"sku";i:1;s:13:"regular_price";i:2;s:10:"sale_price";i:3;s:12:"stock_status";i:4;s:10:"tax_status";}s:12:"instructions";s:500:"Welcome to the Woo Price Editor!...";}
autoload: yes
```

## Migration and Upgrades

The plugin handles settings migration during activation:

1. Checks if settings exist
2. If not, creates with defaults
3. If exist, merges with defaults to add new settings
4. Performs any necessary data transformations

**Example Migration** (from plugin update):
```php
// Handle migration for existing installations
if (!isset($options['instructions'])) {
    $updated_options['instructions'] = $defaults['instructions'];
}

// Remove deprecated values
if (isset($updated_options['default_columns']) && is_array($updated_options['default_columns'])) {
    $updated_options['default_columns'] = array_diff($updated_options['default_columns'], ['product']);
}

update_option('wpe_editor_settings', $updated_options);
```

## Hooks and Filters

### Available Filters

The plugin provides filters for extending settings functionality:

#### Modify Editor Context (including settings)

```php
add_filter('wpe_editor_context', function($context) {
    // Modify settings before passing to template
    $context['settings']['custom_option'] = 'custom_value';
    return $context;
});
```

## Troubleshooting

### Settings Not Saving

**Cause**: User lacks permission or nonce verification failed
**Solution**: Ensure user has `manage_woocommerce` capability and form nonce is valid

### Settings Reset to Defaults

**Cause**: Validation failure or corrupted data
**Solution**: Check admin notices for validation errors; may need to manually fix database entry

### Custom Category Not Showing

**Cause**: Category was deleted or slug changed
**Solution**: Plugin automatically falls back to "All Products"; update setting to valid category

## Best Practices

1. **Test settings changes** on a staging site before applying to production
2. **Document custom instructions** that are specific to your workflow
3. **Review default columns** based on your team's most common tasks
4. **Keep backups** of your settings if you've heavily customized instructions
5. **Use start category** to optimize initial load time for large catalogs
