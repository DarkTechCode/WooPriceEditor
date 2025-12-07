# AJAX Endpoints Documentation

## Overview

The Woo Price Editor uses WordPress AJAX endpoints for all client-server communication. All endpoints are registered via `wp_ajax_` hooks and require authentication and proper capabilities.

**Base URL**: `{site_url}/wp-admin/admin-ajax.php`

## Authentication & Security

### Required Headers/Parameters

All AJAX requests must include:

- **nonce**: Valid nonce created with `wp_create_nonce('wp_rest')`
- **action**: The AJAX action name (e.g., `wpe_get_products`)

### Capability Requirements

All endpoints require:
- User must be logged in
- User must have `manage_woocommerce` capability

### Security Checks

Each request is verified through:

1. **Authentication Check**: User must be logged in
2. **Nonce Verification**: Valid nonce for CSRF protection
3. **Capability Check**: User must have `manage_woocommerce` capability
4. **Product-Specific Check** (for updates): User must be able to edit the specific product

### Error Responses

**401 Unauthorized** - User not logged in:
```json
{
    "success": false,
    "message": "You must be logged in to access this endpoint.",
    "data": {
        "status": 401
    }
}
```

**403 Forbidden** - Invalid nonce:
```json
{
    "success": false,
    "message": "Security check failed.",
    "data": {
        "status": 403
    }
}
```

**403 Forbidden** - Insufficient capability:
```json
{
    "success": false,
    "message": "You do not have permission to manage products.",
    "data": {
        "status": 403
    }
}
```

## Endpoints

### 1. Get Categories

Retrieves all product categories from WooCommerce.

**Action**: `wpe_get_categories`

**Method**: `POST`

**Parameters**: None (only nonce required)

**Request Example**:
```javascript
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_get_categories',
    nonce: wpeData.nonce
}, function(response) {
    console.log(response.data);
});
```

**Success Response** (200):
```json
{
    "success": true,
    "data": [
        {
            "term_id": "15",
            "name": "Electronics",
            "slug": "electronics",
            "count": 42
        },
        {
            "term_id": "16",
            "name": "Clothing",
            "slug": "clothing",
            "count": 128
        }
    ]
}
```

**Implementation**: `WPE_AJAX::ajax_get_categories()`

**Location**: `includes/class-wpe-ajax.php`

---

### 2. Get Tax Classes

Retrieves all available tax classes from WooCommerce.

**Action**: `wpe_get_tax_classes`

**Method**: `POST`

**Parameters**: None (only nonce required)

**Request Example**:
```javascript
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_get_tax_classes',
    nonce: wpeData.nonce
}, function(response) {
    console.log(response.data);
});
```

**Success Response** (200):
```json
{
    "success": true,
    "data": [
        {
            "slug": "",
            "name": "Standard"
        },
        {
            "slug": "reduced-rate",
            "name": "Reduced Rate"
        },
        {
            "slug": "zero-rate",
            "name": "Zero Rate"
        }
    ]
}
```

**Implementation**: `WPE_AJAX::ajax_get_tax_classes()`

**Location**: `includes/class-wpe-ajax.php`

---

### 3. Get Products

Retrieves products with filtering, pagination, and search capabilities.

**Action**: `wpe_get_products`

**Method**: `POST`

**Parameters**:

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `nonce` | string | Yes | - | Security nonce |
| `page` | integer | No | 1 | Current page number |
| `per_page` | integer | No | 50 | Number of products per page |
| `status` | string | No | - | Filter by status: `publish`, `draft`, `private`, `pending` |
| `category` | string | No | - | Filter by category slug |
| `search` | string | No | - | Search in title, SKU, or ID |
| `tax_status` | string | No | - | Filter by tax status: `taxable`, `shipping`, `none` |
| `stock_status` | string | No | - | Filter by stock: `instock`, `outofstock`, `onbackorder` |
| `orderby` | string | No | `ID` | Order by field |
| `order` | string | No | `DESC` | Sort order: `ASC` or `DESC` |

**Request Example**:
```javascript
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_get_products',
    nonce: wpeData.nonce,
    page: 1,
    per_page: 50,
    status: 'publish',
    category: 'electronics',
    search: 'laptop',
    tax_status: 'taxable',
    stock_status: 'instock',
    orderby: 'title',
    order: 'ASC'
}, function(response) {
    console.log(response.data);
});
```

**Success Response** (200):
```json
{
    "success": true,
    "data": {
        "products": [
            {
                "id": "123",
                "title": "Gaming Laptop",
                "sku": "LAP-001",
                "status": "publish",
                "regular_price": "1299.99",
                "sale_price": "999.99",
                "tax_status": "taxable",
                "tax_class": "",
                "stock_status": "instock",
                "categories": [
                    {
                        "term_id": "15",
                        "name": "Electronics",
                        "slug": "electronics"
                    }
                ],
                "edit_url": "http://example.com/wp-admin/post.php?post=123&action=edit",
                "view_url": "http://example.com/product/gaming-laptop/"
            }
        ],
        "total": 150,
        "page": 1,
        "per_page": 50,
        "pages": 3
    }
}
```

**Product Object Fields**:
- `id`: Product ID
- `title`: Product name
- `sku`: Stock keeping unit
- `status`: Post status (`publish`, `draft`, `private`, `pending`)
- `regular_price`: Regular price (string, may be empty)
- `sale_price`: Sale price (string, may be empty)
- `tax_status`: Tax application (`taxable`, `shipping`, `none`)
- `tax_class`: Tax class slug
- `stock_status`: Stock availability (`instock`, `outofstock`, `onbackorder`)
- `categories`: Array of category objects
- `edit_url`: URL to edit product in WP admin
- `view_url`: URL to view product on frontend

**Implementation**: `WPE_AJAX::ajax_get_products()`

**Location**: `includes/class-wpe-ajax.php`

---

### 4. Update Product

Updates a specific field of a product.

**Action**: `wpe_update_product`

**Method**: `POST`

**Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `nonce` | string | Yes | Security nonce |
| `product_id` | integer | Yes | Product ID to update |
| `field` | string | Yes | Field to update (see supported fields below) |
| `value` | mixed | Yes | New value for the field |

**Supported Fields**:
- `title` - Product name (string)
- `regular_price` - Regular price (numeric string)
- `sale_price` - Sale price (numeric string)
- `tax_status` - Tax status (`taxable`, `shipping`, `none`)
- `tax_class` - Tax class slug
- `stock_status` - Stock status (`instock`, `outofstock`, `onbackorder`)

**Request Example**:
```javascript
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_update_product',
    nonce: wpeData.nonce,
    product_id: 123,
    field: 'regular_price',
    value: '1499.99'
}, function(response) {
    if (response.success) {
        console.log('Updated:', response.data.message);
    }
});
```

**Success Response** (200):
```json
{
    "success": true,
    "data": {
        "message": "Regular price updated successfully: $1,299.99 → $1,499.99",
        "old_value": "1299.99",
        "new_value": "1499.99",
        "product": {
            "id": "123",
            "title": "Gaming Laptop",
            "sku": "LAP-001",
            "status": "publish",
            "regular_price": "1499.99",
            "sale_price": "999.99",
            "tax_status": "taxable",
            "tax_class": "",
            "stock_status": "instock",
            "categories": [...],
            "edit_url": "...",
            "view_url": "..."
        }
    }
}
```

**Error Response** (400):
```json
{
    "success": false,
    "message": "Invalid price value",
    "data": {
        "status": 400
    }
}
```

**Error Response** (403) - Product-specific permission:
```json
{
    "success": false,
    "message": "You do not have permission to edit this product.",
    "data": {
        "status": 403
    }
}
```

**Field Validation**:

- **title**: Cannot be empty, sanitized with `sanitize_text_field()`
- **regular_price**: Must be numeric and >= 0
- **sale_price**: Must be numeric and >= 0
- **tax_status**: Must be one of: `taxable`, `shipping`, `none`
- **tax_class**: Must be valid tax class slug
- **stock_status**: Must be one of: `instock`, `outofstock`, `onbackorder`

**Implementation**: `WPE_AJAX::ajax_update_product()`

**Location**: `includes/class-wpe-ajax.php`

---

## Data Sanitization

All input data is sanitized before processing:

```php
$params = [
    'page'         => absint(wp_unslash($_REQUEST['page'])),
    'per_page'     => absint(wp_unslash($_REQUEST['per_page'])),
    'status'       => sanitize_text_field(wp_unslash($_REQUEST['status'])),
    'category'     => sanitize_text_field(wp_unslash($_REQUEST['category'])),
    'search'       => sanitize_text_field(wp_unslash($_REQUEST['search'])),
    'tax_status'   => sanitize_text_field(wp_unslash($_REQUEST['tax_status'])),
    'stock_status' => sanitize_text_field(wp_unslash($_REQUEST['stock_status'])),
];
```

Field-specific sanitization is handled by `WPE_Security::sanitize_field()`.

## Error Handling

All endpoints use try-catch blocks and return consistent error responses:

```php
try {
    $result = $this->product_handler->get_products($params);
    $this->send_response($result, true, 200);
} catch (Exception $e) {
    WPE_Security::log_event('ajax_error', [
        'action' => 'get_products',
        'error'  => $e->getMessage(),
    ]);
    $this->send_response(
        new WP_Error('error', $e->getMessage()),
        false,
        500
    );
}
```

**Error Logging**: All exceptions are logged via `WPE_Security::log_event()` for debugging and security monitoring.

## Usage in JavaScript

### Full Example with Error Handling

```javascript
function getProducts(filters) {
    return jQuery.ajax({
        url: wpeData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'wpe_get_products',
            nonce: wpeData.nonce,
            ...filters
        }
    }).then(function(response) {
        if (!response.success) {
            throw new Error(response.message || 'Unknown error');
        }
        return response.data;
    }).catch(function(error) {
        console.error('Failed to get products:', error);
        throw error;
    });
}

// Usage
getProducts({
    page: 1,
    per_page: 50,
    category: 'electronics'
}).then(function(data) {
    console.log('Products:', data.products);
    console.log('Total:', data.total);
}).catch(function(error) {
    alert('Error loading products: ' + error.message);
});
```

### Update Product Example

```javascript
function updateProduct(productId, field, value) {
    return jQuery.ajax({
        url: wpeData.ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'wpe_update_product',
            nonce: wpeData.nonce,
            product_id: productId,
            field: field,
            value: value
        }
    }).then(function(response) {
        if (!response.success) {
            throw new Error(response.message || 'Update failed');
        }
        return response.data;
    });
}

// Usage
updateProduct(123, 'regular_price', '1499.99')
    .then(function(data) {
        alert(data.message);
    })
    .catch(function(error) {
        alert('Error: ' + error.message);
    });
```

## Testing Endpoints

### Using cURL

```bash
# Get categories
curl -X POST 'http://example.com/wp-admin/admin-ajax.php' \
  -H 'Cookie: wordpress_logged_in_xxx=...' \
  -d 'action=wpe_get_categories' \
  -d 'nonce=abc123def456'

# Get products
curl -X POST 'http://example.com/wp-admin/admin-ajax.php' \
  -H 'Cookie: wordpress_logged_in_xxx=...' \
  -d 'action=wpe_get_products' \
  -d 'nonce=abc123def456' \
  -d 'page=1' \
  -d 'per_page=10' \
  -d 'category=electronics'

# Update product
curl -X POST 'http://example.com/wp-admin/admin-ajax.php' \
  -H 'Cookie: wordpress_logged_in_xxx=...' \
  -d 'action=wpe_update_product' \
  -d 'nonce=abc123def456' \
  -d 'product_id=123' \
  -d 'field=regular_price' \
  -d 'value=1499.99'
```

### Using Browser Console

```javascript
// Ensure wpeData is available (on editor page)
jQuery.post(wpeData.ajaxUrl, {
    action: 'wpe_get_products',
    nonce: wpeData.nonce,
    page: 1,
    per_page: 5
}).done(function(response) {
    console.table(response.data.products);
}).fail(function(xhr) {
    console.error('Error:', xhr.responseJSON);
});
```

## Rate Limiting

Currently, no rate limiting is implemented at the plugin level. Consider implementing server-level rate limiting for production environments to prevent abuse.

## Performance Considerations

1. **Pagination**: Always use `per_page` parameter to limit result sets
2. **Filtering**: Use specific filters to reduce database query complexity
3. **Caching**: Consider implementing transient caching for category and tax class lookups
4. **Indexing**: Ensure database has proper indexes on `post_type`, `post_status`, and meta keys

## Security Best Practices

1. **Always verify nonces** before making AJAX calls
2. **Never expose nonces** in URLs or public HTML
3. **Regenerate nonces** for long-running sessions
4. **Log failed attempts** for security monitoring
5. **Validate all input** on both client and server side
6. **Use HTTPS** in production to protect data in transit
