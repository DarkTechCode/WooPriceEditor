# Woo Price Editor Documentation

Welcome to the Woo Price Editor documentation. This directory contains comprehensive guides covering all aspects of the plugin.

## Documentation Index

### Getting Started

- **[Installation Guide](installation.md)** - Step-by-step installation instructions, requirements, post-installation setup, and troubleshooting

### Configuration

- **[Settings Documentation](settings.md)** - Complete guide to plugin settings including start category, default columns, and instructions customization

- **[Capability Requirements](capabilities.md)** - Detailed explanation of user permissions, capability checks, and access control

### Development

- **[AJAX Endpoints](ajax-endpoints.md)** - Complete API reference for all AJAX endpoints with request/response examples, authentication, and security

- **[Technical Notes](technical-notes.md)** - Implementation details including WordPress-bundled jQuery, full-screen rendering, security architecture, and extension guides

### Testing

- **[Running Tests](../tests/README.md)** - PHPUnit test suite documentation with setup instructions and examples

## Quick Reference

### System Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: 3.0 or higher
- **User Capability**: `manage_woocommerce`

### Installation

1. Upload plugin to `/wp-content/plugins/woo-price-editor/`
2. Activate via WordPress admin
3. Access via **Price Editor** menu
4. Configure settings as needed

See [Installation Guide](installation.md) for detailed instructions.

### Key Features

- **Full-Screen Editor**: Distraction-free interface for bulk editing
- **Inline Editing**: Click fields to edit directly in table
- **Advanced Filtering**: Category, status, tax, stock filters plus search
- **Customizable Columns**: Show/hide columns, configure defaults
- **Security**: Capability-based access, nonce verification, input sanitization
- **AJAX API**: Fast, secure product operations

### Architecture

```
Plugin Bootstrap (woo-price-editor.php)
    ↓
WPE_Plugin (Main Controller)
    ├── Admin Menu & Full-Screen Rendering
    ├── Asset Loading (CSS/JS)
    └── Settings Initialization
        ↓
AJAX Handlers (WPE_AJAX)
    ├── Security Verification
    ├── Get Categories/Tax Classes/Products
    └── Update Product Fields
        ↓
Product Handler (WPE_Product)
    ├── Data Retrieval
    └── Field Updates (via WooCommerce API)
        ↓
Security Layer (WPE_Security)
    ├── Field Sanitization
    ├── Permission Checks
    └── Event Logging
```

### Security Model

All operations require:
1. ✓ Authenticated user (logged in)
2. ✓ Valid nonce (`wp_rest`)
3. ✓ `manage_woocommerce` capability
4. ✓ Product-specific permissions (for updates)

See [Capability Requirements](capabilities.md) for details.

### AJAX Endpoints Summary

| Endpoint | Purpose | Auth Required |
|----------|---------|---------------|
| `wpe_get_categories` | Fetch product categories | ✓ |
| `wpe_get_tax_classes` | Fetch tax classes | ✓ |
| `wpe_get_products` | Get products with filters | ✓ |
| `wpe_update_product` | Update product field | ✓ |

See [AJAX Endpoints](ajax-endpoints.md) for complete API reference.

### Settings Overview

| Setting | Default | Description |
|---------|---------|-------------|
| Start Category | All Products | Category selected on editor load |
| Default Columns | sku, regular_price, sale_price, stock_status, tax_status | Columns visible by default |
| Instructions | Built-in help text | Customizable help displayed to users |

See [Settings Documentation](settings.md) for configuration details.

## Common Tasks

### Change Default Visible Columns

1. Navigate to **Price Editor** → **Settings**
2. Check/uncheck columns in "Visible Columns" section
3. Click **Save Changes**

### Set Default Category Filter

1. Navigate to **Price Editor** → **Settings**
2. Select category from "Default Start Category" dropdown
3. Click **Save Changes**

### Customize User Instructions

1. Navigate to **Price Editor** → **Settings**
2. Edit text in "Editor Instructions" field
3. Click **Save Changes**

### Grant Access to Custom Role

```php
$role = get_role('custom_role');
if ($role) {
    $role->add_cap('manage_woocommerce');
}
```

See [Capability Requirements](capabilities.md) for more on permissions.

### Test AJAX Endpoint

```javascript
// In browser console on editor page
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_get_products',
    nonce: wpeData.nonce,
    page: 1,
    per_page: 10
}, function(response) {
    console.log(response);
});
```

See [AJAX Endpoints](ajax-endpoints.md) for API details.

## Troubleshooting

### Can't See Price Editor Menu

**Problem**: Menu item not visible in WordPress admin

**Causes**:
- User lacks `manage_woocommerce` capability
- Plugin not activated

**Solutions**:
- Verify user role (Administrator or Shop Manager)
- Check Plugins page to confirm activation

### Products Not Loading

**Problem**: Editor loads but no products appear

**Causes**:
- WooCommerce not installed/activated
- No products in database
- JavaScript error

**Solutions**:
- Activate WooCommerce
- Create test products
- Check browser console for errors

### Permission Denied Errors

**Problem**: "You do not have permission" error messages

**Causes**:
- Invalid or expired nonce
- User lacks capability
- Product-specific permission issue

**Solutions**:
- Refresh page to get new nonce
- Verify user has `manage_woocommerce` capability
- Check if user can edit specific product in WP admin

### Editor Page is Blank

**Problem**: Clicking Price Editor shows blank page

**Causes**:
- PHP error
- Missing template file
- Theme/plugin conflict

**Solutions**:
- Enable WP_DEBUG and check error logs
- Verify `templates/editor-shell.php` exists
- Disable other plugins to identify conflict

See individual documentation files for more detailed troubleshooting.

## Developer Resources

### Extending the Plugin

```php
// Add custom data to editor context
add_filter('wpe_editor_context', function($context) {
    $context['my_custom_data'] = get_my_data();
    return $context;
});
```

### Adding Custom AJAX Endpoint

```php
add_action('wp_ajax_wpe_custom_action', function() {
    // Verify permissions using existing handler
    $ajax = new WPE_AJAX();
    // ... your logic
});
```

See [Technical Notes](technical-notes.md) for extension examples.

### Running Tests

```bash
# Setup WordPress test suite
export WP_TESTS_DIR=/tmp/wordpress-tests-lib

# Run all tests
cd woo-price-editor/tests
phpunit

# Run specific test
phpunit test-option-defaults.php
```

See [Testing Documentation](../tests/README.md) for setup and usage.

## Additional Resources

### WordPress Documentation
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [AJAX in Plugins](https://developer.wordpress.org/plugins/javascript/ajax/)
- [Settings API](https://developer.wordpress.org/plugins/settings/settings-api/)

### WooCommerce Documentation
- [WooCommerce Docs](https://woocommerce.com/documentation/)
- [Product CRUD](https://github.com/woocommerce/woocommerce/wiki/CRUD-Objects-in-3.0)

### Libraries Used
- [DataTables](https://datatables.net/)
- [jQuery](https://jquery.com/)

## Contributing

When contributing to the plugin:

1. Read all documentation to understand architecture
2. Follow WordPress coding standards
3. Write tests for new features
4. Update documentation for changes
5. Test on supported WordPress/PHP versions

## Support

For questions or issues:

1. Check documentation in this directory
2. Review [Technical Notes](technical-notes.md) for implementation details
3. Search existing issues
4. Open new issue with details

---

**Last Updated**: December 2024  
**Plugin Version**: 0.1.0  
**WordPress Compatibility**: 6.0+
