=== WooCommerce Price Editor ===
Contributors: woopriceeditor
Tags: woocommerce, price, bulk edit, products, admin
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk edit WooCommerce product prices with advanced filtering, search, and inline editing.

== Description ==

WooCommerce Price Editor provides a powerful interface for managing product prices in bulk. Features include:

* **Inline Editing** - Edit product titles, prices, tax settings, and stock status directly in the table
* **Advanced Filtering** - Filter by status, category, tax status, and stock status
* **Search** - Search products by title, SKU, or ID
* **Column Management** - Show/hide columns to focus on what you need
* **Auto-save** - Changes are saved automatically as you edit
* **Pagination** - Handles large product catalogs efficiently

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

= Security =

* CSRF protection with WordPress nonces
* Capability checks on all operations
* Input validation and sanitization
* Rate limiting to prevent abuse
* Proper escaping of all output

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woo-price-editor`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to WooCommerce → Price Editor in the admin menu

== Frequently Asked Questions ==

= Who can use the Price Editor? =

Only users with the `manage_woocommerce` capability can access the Price Editor.

= Are changes saved automatically? =

Yes, changes to prices, tax settings, and stock status are saved automatically when you finish editing. Title changes require clicking the save button.

= Does it work with variable products? =

The current version displays simple and variable products. Variations are not shown individually.

== Changelog ==

= 1.0.0 =
* Initial release
* Inline editing for titles, prices, tax settings, and stock status
* Filtering by status, category, tax status, and stock status
* Search by title, SKU, or ID
* Column visibility management
* Server-side pagination
* Full security implementation (CSRF, capability checks, sanitization)

== Upgrade Notice ==

= 1.0.0 =
Initial release of WooCommerce Price Editor.
