# Capability Requirements

## Overview

The Woo Price Editor plugin is designed with security in mind and requires specific WordPress capabilities to access its features. This document outlines the capability requirements for various plugin functions.

## Primary Capability: `manage_woocommerce`

All plugin functionality requires the `manage_woocommerce` capability, which is typically assigned to:

- **Administrator**: Full site control
- **Shop Manager**: WooCommerce management without full admin access

## Capability Checks by Feature

### Admin Menu Access

**Required Capability**: `manage_woocommerce`

The Price Editor menu item only appears for users with the `manage_woocommerce` capability. This is enforced in:

```php
add_menu_page(
    __('Woo Price Editor', 'woo-price-editor'),
    __('Price Editor', 'woo-price-editor'),
    'manage_woocommerce',  // Capability requirement
    'woo-price-editor',
    [$this, 'render_placeholder_screen'],
    'dashicons-money-alt',
    56
);
```

**Location**: `includes/class-wpe-plugin.php`

### Settings Page Access

**Required Capability**: `manage_woocommerce`

The Settings submenu and all settings operations require `manage_woocommerce`:

```php
add_submenu_page(
    'woo-price-editor',
    __('Woo Price Editor Settings', 'woo-price-editor'),
    __('Settings', 'woo-price-editor'),
    'manage_woocommerce',  // Capability requirement
    'wpe-settings',
    [$this, 'render_settings_page']
);
```

**Location**: `includes/class-wpe-settings.php`

### Full-Screen Editor Access

**Required Capability**: `manage_woocommerce`

Before rendering the editor shell, the plugin verifies the user is authenticated and has the required capability:

```php
private function ensure_user_can_access() {
    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_die(
            esc_html__('You do not have permission to access the Woo Price Editor.', 'woo-price-editor'),
            esc_html__('Access denied', 'woo-price-editor'),
            ['response' => 403]
        );
    }
}
```

**Location**: `includes/class-wpe-plugin.php`

### AJAX Endpoints

**Required Capability**: `manage_woocommerce`

All AJAX endpoints verify capability before processing requests:

#### Endpoints Requiring Capability:
- `wpe_get_categories` - Retrieve product categories
- `wpe_get_tax_classes` - Retrieve tax classes
- `wpe_get_products` - Fetch product listings
- `wpe_update_product` - Update product fields

**Verification Code**:
```php
private function verify_request() {
    // Check authentication
    if (!is_user_logged_in()) {
        return new WP_Error(
            'not_authenticated',
            __('You must be logged in to access this endpoint.', 'woo-price-editor'),
            ['status' => 401]
        );
    }

    // Check nonce
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error(
            'invalid_nonce',
            __('Security check failed.', 'woo-price-editor'),
            ['status' => 403]
        );
    }

    // Check capability
    if (!current_user_can('manage_woocommerce')) {
        return new WP_Error(
            'forbidden',
            __('You do not have permission to manage products.', 'woo-price-editor'),
            ['status' => 403]
        );
    }

    return true;
}
```

**Location**: `includes/class-wpe-ajax.php`

### Product-Specific Permissions

**Required Capability**: `manage_woocommerce` + product-specific check

When updating a product, additional checks verify the user can edit the specific product:

```php
if (!WPE_Security::can_edit_product($product_id)) {
    $this->send_response(
        new WP_Error(
            'forbidden',
            __('You do not have permission to edit this product.', 'woo-price-editor'),
            ['status' => 403]
        )
    );
    return;
}
```

**Location**: `includes/class-wpe-ajax.php`

The `WPE_Security::can_edit_product()` method ensures the user has both the general capability and permission to edit the specific product post.

## Granting Capabilities

### For Custom Roles

If you need to grant access to custom user roles:

```php
// Get the role
$role = get_role('custom_role_slug');

// Add the manage_woocommerce capability
if ($role) {
    $role->add_cap('manage_woocommerce');
}
```

### Using Plugins

Several plugins can help manage capabilities:
- **User Role Editor**: Visual interface for capability management
- **Members**: Advanced role and capability management
- **Capability Manager Enhanced**: Fine-grained permission control

## Security Considerations

### Nonce Verification

All AJAX requests must include a valid nonce created with `wp_create_nonce('wp_rest')`. This prevents CSRF attacks.

### Request Sanitization

All user input is sanitized using WordPress functions:
- `sanitize_text_field()` - Text inputs
- `absint()` - Integer values
- `wp_unslash()` - Remove slashes from data

### Logged Events

Security events are logged for monitoring:
- Failed permission checks
- Invalid product update attempts
- AJAX errors

**Location**: `includes/class-wpe-security.php`

## Error Responses

### HTTP Status Codes

The plugin returns appropriate HTTP status codes:

- **200 OK**: Successful request
- **400 Bad Request**: Invalid input data
- **401 Unauthorized**: User not logged in
- **403 Forbidden**: Insufficient permissions
- **500 Internal Server Error**: Server-side error

### Error Message Examples

**Not Authenticated** (401):
```json
{
    "success": false,
    "message": "You must be logged in to access this endpoint.",
    "data": {
        "status": 401
    }
}
```

**Invalid Nonce** (403):
```json
{
    "success": false,
    "message": "Security check failed.",
    "data": {
        "status": 403
    }
}
```

**Forbidden** (403):
```json
{
    "success": false,
    "message": "You do not have permission to manage products.",
    "data": {
        "status": 403
    }
}
```

## Best Practices

1. **Never bypass capability checks** - All custom code should respect the `manage_woocommerce` requirement
2. **Use built-in WordPress functions** - Leverage `current_user_can()` for all permission checks
3. **Log security events** - Monitor failed access attempts for security auditing
4. **Test with different roles** - Verify access control works correctly for all user roles
5. **Keep nonces fresh** - Nonces expire; implement proper refresh mechanisms in long-running sessions
