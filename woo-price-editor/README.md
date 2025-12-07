# Woo Price Editor

A powerful WordPress plugin for bulk editing WooCommerce product prices and attributes with a streamlined, full-screen interface.

## Description

Woo Price Editor provides a dedicated full-screen interface for efficiently managing WooCommerce products. Features include inline editing, advanced filtering, bulk operations, and real-time updates.

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: Latest stable version (3.0+)
- **User Capability**: `manage_woocommerce` (typically Administrator or Shop Manager)

## Compatibility

- **WordPress**: Tested up to 6.4
- **WooCommerce**: Compatible with latest stable releases
- **PHP**: 7.4, 8.0, 8.1, 8.2
- **Browsers**: Modern browsers (Chrome, Firefox, Safari, Edge - latest 2 versions)

## Installation

See detailed installation instructions in [`docs/installation.md`](docs/installation.md).

**Quick Install**:

1. Upload the `woo-price-editor` directory to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Price Editor** in the admin menu
4. Configure settings under **Price Editor** → **Settings**

## Features

### Inline Editing
- Click any field to edit directly in the table
- Auto-save on blur (prices, dropdowns)
- Enter/Escape keyboard shortcuts (title field)
- Real-time validation and error messages

### Advanced Filtering
- Filter by product category
- Filter by status (Published, Draft, Private, Pending)
- Filter by tax status (Taxable, Shipping only, None)
- Filter by stock status (In Stock, Out of Stock, On Backorder)
- Full-text search (title, SKU, ID)

### Customizable Columns
- Show/hide columns as needed
- Configurable default columns
- Persistent column visibility preferences

### Full-Screen Interface
- Distraction-free editing environment
- Maximum screen real estate for product table
- Responsive design for various screen sizes

### Security
- Capability-based access control
- CSRF protection via nonces
- Input sanitization and validation
- Audit logging for security events

## Plugin Structure

```
woo-price-editor/
├── assets/
│   ├── css/
│   │   ├── editor.css          # Main editor styles
│   │   └── settings.css        # Settings page styles
│   └── js/
│       └── editor.js           # Frontend JavaScript
├── docs/
│   ├── installation.md         # Installation guide
│   ├── capabilities.md         # Capability requirements
│   ├── settings.md            # Settings documentation
│   ├── ajax-endpoints.md      # AJAX API documentation
│   └── technical-notes.md     # Technical implementation details
├── includes/
│   ├── class-wpe-plugin.php   # Main plugin class
│   ├── class-wpe-ajax.php     # AJAX handlers
│   ├── class-wpe-api.php      # REST API endpoints
│   ├── class-wpe-product.php  # Product data handlers
│   ├── class-wpe-security.php # Security utilities
│   └── class-wpe-settings.php # Settings page
├── templates/
│   └── editor-shell.php       # Full-screen editor template
├── tests/
│   ├── bootstrap.php          # Test bootstrap
│   ├── phpunit.xml.dist       # PHPUnit configuration
│   ├── README.md              # Testing documentation
│   ├── helpers/
│   │   └── class-wpe-test-case.php
│   ├── test-option-defaults.php
│   └── test-ajax-permissions.php
└── woo-price-editor.php       # Main plugin file
```

## Settings

Access settings via **WordPress Admin** → **Price Editor** → **Settings**

### Available Settings

#### 1. Start Category
- **Default**: All Products
- **Description**: Category automatically selected when editor loads
- **Options**: All Products or any WooCommerce product category

#### 2. Default Columns
- **Default**: SKU, Regular Price, Sale Price, Stock Status, Tax Status
- **Description**: Columns visible by default in the editor table
- **Options**: 
  - SKU
  - Status
  - Regular Price
  - Sale Price
  - Tax Status
  - Tax Class
  - Stock Status
  - Categories

**Note**: Product (title) and Actions columns are always visible.

#### 3. Instructions
- **Default**: Built-in help text
- **Description**: Customizable instructions displayed at the top of the editor
- **Format**: Plain text with basic HTML allowed

See detailed settings documentation in [`docs/settings.md`](docs/settings.md).

## Usage

### Accessing the Editor

1. Log in to WordPress admin with a user that has `manage_woocommerce` capability
2. Click **Price Editor** in the admin menu
3. The full-screen editor loads with your products

### Editing Products

**Price Fields (Regular Price, Sale Price)**:
1. Click the field value
2. Enter new price
3. Click outside the field or press Tab to save

**Title Field**:
1. Click the product title
2. Edit the text
3. Press Enter to save or Escape to cancel

**Dropdown Fields (Status, Tax Status, Stock Status)**:
1. Click the dropdown
2. Select new value
3. Change is saved automatically

### Filtering Products

Use the filter controls above the table:
- **Category**: Select a product category
- **Status**: Filter by publish status
- **Tax Status**: Filter by tax application
- **Stock Status**: Filter by inventory status
- **Search**: Enter title, SKU, or product ID

### Column Visibility

Toggle column visibility using the checkboxes above the table.

## AJAX Endpoints

The plugin uses AJAX for all client-server communication. All endpoints require:
- Authentication (logged-in user)
- Valid nonce (`wp_rest`)
- `manage_woocommerce` capability

### Available Endpoints

- **`wpe_get_categories`**: Retrieve product categories
- **`wpe_get_tax_classes`**: Retrieve tax classes
- **`wpe_get_products`**: Fetch products with filtering/pagination
- **`wpe_update_product`**: Update a product field

See detailed API documentation in [`docs/ajax-endpoints.md`](docs/ajax-endpoints.md).

## Technical Details

### WordPress-Bundled jQuery

The plugin uses WordPress's bundled jQuery to:
- Avoid version conflicts with core and other plugins
- Maintain compatibility with WordPress's noConflict mode
- Follow WordPress plugin development best practices

jQuery is enqueued using WordPress's standard system:

```php
wp_enqueue_script('jquery');
```

### Full-Screen Rendering

The editor uses a custom full-screen rendering approach that bypasses standard WordPress admin chrome for:
- Maximum workspace for the product table
- Distraction-free editing environment
- Improved performance (lighter DOM)

Implementation uses the `load-{$page_hook}` action to render a custom template and exit before WordPress admin interface loads.

See detailed technical notes in [`docs/technical-notes.md`](docs/technical-notes.md).

### Security Architecture

Multi-layer security approach:
1. **Menu Level**: Capability check when registering menu
2. **Page Level**: Access verification before rendering
3. **Request Level**: Nonce and capability check on every AJAX request
4. **Product Level**: Specific product edit permission check

See capability requirements in [`docs/capabilities.md`](docs/capabilities.md).

## Development

### Running Tests

The plugin includes a comprehensive PHPUnit test suite.

**Quick Start**:

```bash
# Set up WordPress test environment
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run all tests
cd woo-price-editor/tests
phpunit

# Run specific test file
phpunit test-option-defaults.php
```

See detailed testing documentation in [`tests/README.md`](tests/README.md).

### Test Coverage

- ✓ Option defaults and activation
- ✓ AJAX endpoint permissions
- ✓ Nonce verification
- ✓ Capability checks
- ✓ Input validation

### Extending the Plugin

The plugin provides hooks and filters for customization:

```php
// Modify editor context before rendering
add_filter('wpe_editor_context', function($context) {
    $context['custom_data'] = get_custom_data();
    return $context;
});
```

See [`docs/technical-notes.md`](docs/technical-notes.md) for extension examples.

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Installation Guide](docs/installation.md)**: Step-by-step installation and setup
- **[Capabilities](docs/capabilities.md)**: User permissions and access control
- **[Settings](docs/settings.md)**: Configuration options and usage
- **[AJAX Endpoints](docs/ajax-endpoints.md)**: API reference and examples
- **[Technical Notes](docs/technical-notes.md)**: Implementation details and architecture

## Troubleshooting

### Menu Not Visible
**Cause**: User lacks `manage_woocommerce` capability  
**Solution**: Ensure user is Administrator or Shop Manager

### Products Not Loading
**Cause**: WooCommerce not active or no products exist  
**Solution**: Activate WooCommerce and create test products

### Permission Errors
**Cause**: Invalid nonce or insufficient capability  
**Solution**: Refresh page to get new nonce; verify user has proper permissions

### Editor Page Blank
**Cause**: JavaScript error or plugin conflict  
**Solution**: Check browser console for errors; disable other plugins to identify conflicts

See [`docs/installation.md`](docs/installation.md) for more troubleshooting tips.

## Support

For issues, questions, or feature requests:

1. Check the documentation in the `docs/` directory
2. Review closed issues for similar problems
3. Open a new issue with detailed information

## License

This plugin is licensed under the GNU General Public License v2 or later.

## Credits

- **DataTables**: https://datatables.net/
- **WordPress**: https://wordpress.org/
- **WooCommerce**: https://woocommerce.com/

## Changelog

### Version 0.1.0
- Initial release
- Full-screen editor interface
- Inline editing for prices and attributes
- Advanced filtering and search
- AJAX-based product operations
- Configurable settings
- Comprehensive test suite
- Full documentation

## Author

**Dark Wizard**  
Website: https://darktech.ru

---

For developers: See technical implementation details in [`docs/technical-notes.md`](docs/technical-notes.md) and test documentation in [`tests/README.md`](tests/README.md).
