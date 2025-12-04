<?php
/**
 * Admin page display template
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap wpe-wrap">
    <h1 class="wpe-page-title">
        <span class="dashicons dashicons-money-alt"></span>
        <?php esc_html_e('WooCommerce Price Editor', 'woo-price-editor'); ?>
    </h1>

    <div class="wpe-header">
        <p class="wpe-description">
            <?php esc_html_e('Edit product prices, titles, and other fields directly from this page. Changes are saved automatically.', 'woo-price-editor'); ?>
        </p>
    </div>

    <!-- Filters Section -->
    <div class="wpe-filters">
        <div class="wpe-filters-row">
            <!-- Search -->
            <div class="wpe-filter-group wpe-search-group">
                <label for="wpe-search" class="screen-reader-text">
                    <?php esc_html_e('Search', 'woo-price-editor'); ?>
                </label>
                <input 
                    type="search" 
                    id="wpe-search" 
                    class="wpe-search-input" 
                    placeholder="<?php esc_attr_e('Search by title, SKU, or ID...', 'woo-price-editor'); ?>"
                >
            </div>

            <!-- Status Filter -->
            <div class="wpe-filter-group">
                <label for="wpe-status-filter" class="screen-reader-text">
                    <?php esc_html_e('Status', 'woo-price-editor'); ?>
                </label>
                <select id="wpe-status-filter" class="wpe-filter-select" data-filter="status">
                    <option value=""><?php esc_html_e('All statuses', 'woo-price-editor'); ?></option>
                    <option value="publish"><?php esc_html_e('Published', 'woo-price-editor'); ?></option>
                    <option value="draft"><?php esc_html_e('Draft', 'woo-price-editor'); ?></option>
                    <option value="private"><?php esc_html_e('Private', 'woo-price-editor'); ?></option>
                    <option value="pending"><?php esc_html_e('Pending', 'woo-price-editor'); ?></option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="wpe-filter-group">
                <label for="wpe-category-filter" class="screen-reader-text">
                    <?php esc_html_e('Category', 'woo-price-editor'); ?>
                </label>
                <select id="wpe-category-filter" class="wpe-filter-select" data-filter="category">
                    <option value=""><?php esc_html_e('All categories', 'woo-price-editor'); ?></option>
                    <!-- Populated via JavaScript -->
                </select>
            </div>

            <!-- Tax Status Filter -->
            <div class="wpe-filter-group">
                <label for="wpe-tax-filter" class="screen-reader-text">
                    <?php esc_html_e('Tax Status', 'woo-price-editor'); ?>
                </label>
                <select id="wpe-tax-filter" class="wpe-filter-select" data-filter="tax_status">
                    <option value=""><?php esc_html_e('All tax statuses', 'woo-price-editor'); ?></option>
                    <option value="taxable"><?php esc_html_e('Taxable', 'woo-price-editor'); ?></option>
                    <option value="shipping"><?php esc_html_e('Shipping only', 'woo-price-editor'); ?></option>
                    <option value="none"><?php esc_html_e('None', 'woo-price-editor'); ?></option>
                </select>
            </div>

            <!-- Stock Status Filter -->
            <div class="wpe-filter-group">
                <label for="wpe-stock-filter" class="screen-reader-text">
                    <?php esc_html_e('Stock Status', 'woo-price-editor'); ?>
                </label>
                <select id="wpe-stock-filter" class="wpe-filter-select" data-filter="stock_status">
                    <option value=""><?php esc_html_e('All stock statuses', 'woo-price-editor'); ?></option>
                    <option value="instock"><?php esc_html_e('In Stock', 'woo-price-editor'); ?></option>
                    <option value="outofstock"><?php esc_html_e('Out of Stock', 'woo-price-editor'); ?></option>
                    <option value="onbackorder"><?php esc_html_e('On Backorder', 'woo-price-editor'); ?></option>
                </select>
            </div>

            <!-- Reset Filters -->
            <div class="wpe-filter-group">
                <button type="button" id="wpe-reset-filters" class="button">
                    <?php esc_html_e('Reset', 'woo-price-editor'); ?>
                </button>
            </div>
        </div>

        <!-- Column Visibility -->
        <div class="wpe-column-toggles">
            <span class="wpe-column-toggles-label"><?php esc_html_e('Columns:', 'woo-price-editor'); ?></span>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="sku" checked>
                <?php esc_html_e('SKU', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="status" checked>
                <?php esc_html_e('Status', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="regular_price" checked>
                <?php esc_html_e('Regular Price', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="sale_price" checked>
                <?php esc_html_e('Sale Price', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="tax_status">
                <?php esc_html_e('Tax Status', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="tax_class">
                <?php esc_html_e('Tax Class', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="stock_status" checked>
                <?php esc_html_e('Stock', 'woo-price-editor'); ?>
            </label>
            <label class="wpe-column-toggle">
                <input type="checkbox" data-column="categories">
                <?php esc_html_e('Categories', 'woo-price-editor'); ?>
            </label>
        </div>
    </div>

    <!-- Notifications Area -->
    <div id="wpe-notifications" class="wpe-notifications" aria-live="polite"></div>

    <!-- Error Area -->
    <div id="wpe-errors" class="wpe-errors" role="alert"></div>

    <!-- Products Table -->
    <div class="wpe-table-container">
        <table id="wpe-products-table" class="wpe-products-table wp-list-table widefat striped">
            <thead>
                <tr>
                    <th class="wpe-col-id"><?php esc_html_e('ID', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-title"><?php esc_html_e('Title', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-sku"><?php esc_html_e('SKU', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-status"><?php esc_html_e('Status', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-regular-price"><?php esc_html_e('Regular Price', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-sale-price"><?php esc_html_e('Sale Price', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-tax-status"><?php esc_html_e('Tax Status', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-tax-class"><?php esc_html_e('Tax Class', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-stock-status"><?php esc_html_e('Stock', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-categories"><?php esc_html_e('Categories', 'woo-price-editor'); ?></th>
                    <th class="wpe-col-actions"><?php esc_html_e('Actions', 'woo-price-editor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Populated via JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Loading Overlay -->
    <div id="wpe-loading" class="wpe-loading" aria-hidden="true">
        <div class="wpe-loading-spinner">
            <span class="spinner is-active"></span>
            <span class="wpe-loading-text"><?php esc_html_e('Loading...', 'woo-price-editor'); ?></span>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="wpe-status-bar">
        <div class="wpe-status-info">
            <span id="wpe-record-count">—</span>
        </div>
        <div class="wpe-status-links">
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" target="_blank">
                <?php esc_html_e('All Products', 'woo-price-editor'); ?>
            </a>
            <span class="wpe-separator">|</span>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" target="_blank">
                <?php esc_html_e('Add New Product', 'woo-price-editor'); ?>
            </a>
            <span class="wpe-separator">|</span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=tax')); ?>" target="_blank">
                <?php esc_html_e('Tax Settings', 'woo-price-editor'); ?>
            </a>
        </div>
    </div>
</div>
