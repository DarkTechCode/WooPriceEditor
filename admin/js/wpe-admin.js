/**
 * WooCommerce Price Editor - Admin JavaScript
 *
 * Combined and optimized JavaScript for the price editor admin page.
 * Implements modular architecture with proper security, error handling,
 * and performance optimizations.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Ensure wpeData is available
    if (typeof wpeData === 'undefined') {
        console.error('WPE: Configuration data not found');
        return;
    }

    /* ==========================================================================
       Configuration
       ========================================================================== */

    const Config = {
        restUrl: wpeData.restUrl,
        nonce: wpeData.nonce,
        pageLength: parseInt(wpeData.pageLength, 10) || 50,
        debounceDelay: 300,
        notificationDuration: 5000,
        i18n: wpeData.i18n || {}
    };

    /* ==========================================================================
       Utility Functions
       ========================================================================== */

    const Utils = {
        /**
         * Escape HTML to prevent XSS
         * @param {string} text - Text to escape
         * @returns {string}
         */
        escapeHtml(text) {
            if (text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        },

        /**
         * Escape attribute value
         * @param {string} text - Text to escape
         * @returns {string}
         */
        escapeAttr(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        },

        /**
         * Debounce function calls
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in ms
         * @returns {Function}
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Format price with currency
         * @param {string|number} price - Price value
         * @returns {string}
         */
        formatPrice(price) {
            if (price === '' || price === null || price === undefined) {
                return '—';
            }
            const num = parseFloat(price);
            if (isNaN(num)) return '—';
            return num.toFixed(2);
        },

        /**
         * Validate price value
         * @param {string} value - Price value
         * @param {boolean} allowEmpty - Whether empty is allowed
         * @returns {object}
         */
        validatePrice(value, allowEmpty = false) {
            const trimmed = String(value).trim();
            
            if (trimmed === '') {
                if (allowEmpty) {
                    return { valid: true, value: '' };
                }
                return { valid: false, error: Config.i18n.invalidPrice || 'Invalid price' };
            }

            // Replace comma with dot and remove non-numeric chars
            const normalized = trimmed.replace(',', '.').replace(/[^\d.-]/g, '');
            const num = parseFloat(normalized);

            if (isNaN(num)) {
                return { valid: false, error: Config.i18n.invalidPrice || 'Invalid price' };
            }

            if (num < 0) {
                return { valid: false, error: Config.i18n.negativePrice || 'Price cannot be negative' };
            }

            if (num > 999999999.99) {
                return { valid: false, error: Config.i18n.invalidPrice || 'Price too large' };
            }

            return { valid: true, value: num.toFixed(2) };
        },

        /**
         * Validate title value
         * @param {string} value - Title value
         * @returns {object}
         */
        validateTitle(value) {
            const trimmed = String(value).trim();
            
            if (trimmed === '') {
                return { valid: false, error: Config.i18n.emptyTitle || 'Title cannot be empty' };
            }

            if (trimmed.length > 200) {
                return { valid: false, error: 'Title too long (max 200 characters)' };
            }

            return { valid: true, value: trimmed };
        },

        /**
         * Get localized string
         * @param {string} key - Translation key
         * @param {string} fallback - Fallback value
         * @returns {string}
         */
        i18n(key, fallback = '') {
            return Config.i18n[key] || fallback || key;
        }
    };

    /* ==========================================================================
       API Client
       ========================================================================== */

    const ApiClient = {
        pendingRequests: {},

        /**
         * Make API request
         * @param {string} endpoint - API endpoint
         * @param {string} method - HTTP method
         * @param {object} data - Request data
         * @returns {Promise}
         */
        request(endpoint, method = 'GET', data = null) {
            const options = {
                url: `${Config.restUrl}${endpoint}`,
                method: method,
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', Config.nonce);
                },
                timeout: 30000
            };

            if (data) {
                if (method === 'GET') {
                    options.data = data;
                } else {
                    options.contentType = 'application/json';
                    options.data = JSON.stringify(data);
                }
            }

            return $.ajax(options).fail((xhr, status, error) => {
                this.handleError(xhr, status, error);
            });
        },

        /**
         * Handle API error
         * @param {object} xhr - XHR object
         * @param {string} status - Status text
         * @param {string} error - Error message
         */
        handleError(xhr, status, error) {
            let message;

            if (xhr.status === 401) {
                message = Utils.i18n('notAuthenticated', 'Please log in again');
            } else if (xhr.status === 403) {
                message = Utils.i18n('forbidden', 'You do not have permission');
            } else if (xhr.status === 429) {
                message = Utils.i18n('rateLimitExceeded', 'Too many requests');
            } else if (status === 'timeout') {
                message = Utils.i18n('timeout', 'Request timed out');
            } else if (xhr.status === 0) {
                message = Utils.i18n('networkError', 'Network error');
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || error;
                } catch (e) {
                    message = Utils.i18n('serverError', 'Server error');
                }
            }

            UI.showNotification(message, 'error');
        },

        /**
         * Cancel pending request
         * @param {string} key - Request key
         */
        cancelRequest(key) {
            if (this.pendingRequests[key]) {
                this.pendingRequests[key].abort();
                delete this.pendingRequests[key];
            }
        },

        /**
         * Get products with filters
         * @param {object} params - Query parameters
         * @returns {Promise}
         */
        getProducts(params) {
            return this.request('/products', 'GET', params);
        },

        /**
         * Update product field
         * @param {number} productId - Product ID
         * @param {string} field - Field name
         * @param {string} value - New value
         * @returns {Promise}
         */
        updateProduct(productId, field, value) {
            const key = `update_${productId}_${field}`;
            this.cancelRequest(key);

            const promise = this.request(`/products/${productId}`, 'PATCH', {
                field: field,
                value: value
            });

            this.pendingRequests[key] = promise;

            return promise.always(() => {
                delete this.pendingRequests[key];
            });
        },

        /**
         * Get categories
         * @returns {Promise}
         */
        getCategories() {
            return this.request('/categories', 'GET');
        },

        /**
         * Get tax classes
         * @returns {Promise}
         */
        getTaxClasses() {
            return this.request('/tax-classes', 'GET');
        }
    };

    /* ==========================================================================
       UI Module
       ========================================================================== */

    const UI = {
        $notifications: null,
        $errors: null,
        $loading: null,
        notificationTimeout: null,

        /**
         * Initialize UI elements
         */
        init() {
            this.$notifications = $('#wpe-notifications');
            this.$errors = $('#wpe-errors');
            this.$loading = $('#wpe-loading');
        },

        /**
         * Show notification
         * @param {string} message - Notification message
         * @param {string} type - Notification type (success, error, warning, info)
         * @param {number} duration - Duration in ms (0 for permanent)
         */
        showNotification(message, type = 'success', duration = Config.notificationDuration) {
            const id = 'notification-' + Date.now();
            const html = `
                <div id="${id}" class="wpe-notification wpe-notification-${Utils.escapeAttr(type)}">
                    <span class="wpe-notification-message">${Utils.escapeHtml(message)}</span>
                    <button type="button" class="wpe-notification-close" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `;

            this.$notifications.append(html);

            const $notification = $('#' + id);

            // Close button handler
            $notification.find('.wpe-notification-close').on('click', () => {
                this.removeNotification($notification);
            });

            // Auto-remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    this.removeNotification($notification);
                }, duration);
            }
        },

        /**
         * Remove notification
         * @param {jQuery} $notification - Notification element
         */
        removeNotification($notification) {
            $notification.fadeOut(200, function() {
                $(this).remove();
            });
        },

        /**
         * Show error in errors area
         * @param {string} message - Error message
         */
        showError(message) {
            const html = `
                <div class="wpe-error-message">
                    ${Utils.escapeHtml(message)}
                </div>
            `;
            this.$errors.html(html);

            // Auto-hide after 10 seconds
            setTimeout(() => {
                this.$errors.empty();
            }, 10000);
        },

        /**
         * Clear errors
         */
        clearErrors() {
            this.$errors.empty();
        },

        /**
         * Show loading overlay
         */
        showLoading() {
            this.$loading.addClass('active');
        },

        /**
         * Hide loading overlay
         */
        hideLoading() {
            this.$loading.removeClass('active');
        },

        /**
         * Update record count display
         * @param {number} showing - Number of visible records
         * @param {number} total - Total records
         */
        updateRecordCount(showing, total) {
            const text = `${Utils.i18n('showing', 'Showing')} ${showing} ${Utils.i18n('of', 'of')} ${total} ${Utils.i18n('products', 'products')}`;
            $('#wpe-record-count').text(text);
        }
    };

    /* ==========================================================================
       Filters Module
       ========================================================================== */

    const Filters = {
        currentFilters: {
            status: '',
            category: '',
            tax_status: '',
            stock_status: '',
            search: ''
        },
        searchDebounced: null,

        /**
         * Initialize filters
         */
        init() {
            this.bindEvents();
            this.loadCategories();
            this.searchDebounced = Utils.debounce(this.applySearch.bind(this), Config.debounceDelay);
        },

        /**
         * Bind filter events
         */
        bindEvents() {
            // Filter select changes
            $('.wpe-filter-select').on('change', (e) => {
                const $select = $(e.currentTarget);
                const filterName = $select.data('filter');
                const value = $select.val();

                this.currentFilters[filterName] = value;
                ProductTable.reload();
            });

            // Search input
            $('#wpe-search').on('input', (e) => {
                const value = $(e.currentTarget).val();
                this.currentFilters.search = value;
                this.searchDebounced();
            });

            // Reset filters
            $('#wpe-reset-filters').on('click', () => {
                this.reset();
            });
        },

        /**
         * Apply search filter
         */
        applySearch() {
            ProductTable.reload();
        },

        /**
         * Load categories for filter
         */
        loadCategories() {
            ApiClient.getCategories()
                .done((response) => {
                    if (response.success && response.data) {
                        const $select = $('#wpe-category-filter');
                        response.data.forEach((cat) => {
                            $select.append(
                                $('<option>')
                                    .val(cat.slug)
                                    .text(`${cat.name} (${cat.count})`)
                            );
                        });
                    }
                });
        },

        /**
         * Reset all filters
         */
        reset() {
            this.currentFilters = {
                status: '',
                category: '',
                tax_status: '',
                stock_status: '',
                search: ''
            };

            $('#wpe-search').val('');
            $('.wpe-filter-select').val('');

            ProductTable.reload();
        },

        /**
         * Get current filters
         * @returns {object}
         */
        getFilters() {
            return Object.assign({}, this.currentFilters);
        }
    };

    /* ==========================================================================
       Editing Module
       ========================================================================== */

    const Editing = {
        saveDebounced: {},
        taxClasses: [],

        /**
         * Initialize editing
         */
        init() {
            this.loadTaxClasses();
            this.bindEvents();
        },

        /**
         * Bind editing events
         */
        bindEvents() {
            const $table = $('#wpe-products-table');

            // Title editing - click to edit
            $table.on('click', '.wpe-editable-title .wpe-editable-value', (e) => {
                this.startTitleEdit($(e.currentTarget).closest('.wpe-editable-title'));
            });

            // Title save button
            $table.on('click', '.wpe-btn-save', (e) => {
                e.stopPropagation();
                this.saveTitleEdit($(e.currentTarget).closest('.wpe-editable-title'));
            });

            // Title cancel button
            $table.on('click', '.wpe-btn-cancel', (e) => {
                e.stopPropagation();
                this.cancelTitleEdit($(e.currentTarget).closest('.wpe-editable-title'));
            });

            // Title Enter key to save, Escape to cancel
            $table.on('keydown', '.wpe-title-input', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.saveTitleEdit($(e.currentTarget).closest('.wpe-editable-title'));
                } else if (e.key === 'Escape') {
                    this.cancelTitleEdit($(e.currentTarget).closest('.wpe-editable-title'));
                }
            });

            // Price fields - blur to save
            $table.on('blur', '.wpe-price-input', (e) => {
                this.savePriceField($(e.currentTarget));
            });

            // Price Enter key to save
            $table.on('keydown', '.wpe-price-input', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(e.currentTarget).blur();
                }
            });

            // Select fields - change to save
            $table.on('change', '.wpe-editable-select', (e) => {
                this.saveSelectField($(e.currentTarget));
            });
        },

        /**
         * Load tax classes for select fields
         */
        loadTaxClasses() {
            ApiClient.getTaxClasses()
                .done((response) => {
                    if (response.success && response.data) {
                        this.taxClasses = response.data;
                    }
                });
        },

        /**
         * Create editable field HTML
         * @param {object} config - Field configuration
         * @returns {string}
         */
        createEditableField(config) {
            const {
                productId,
                field,
                value,
                type = 'text',
                className = ''
            } = config;

            const escapedValue = Utils.escapeAttr(value);
            const displayValue = value || '—';

            if (type === 'select') {
                return this.createSelectField(config);
            }

            if (field === 'title') {
                return this.createTitleField(config);
            }

            if (field === 'regular_price' || field === 'sale_price') {
                return this.createPriceField(config);
            }

            return `
                <div class="wpe-editable" data-product-id="${productId}" data-field="${field}">
                    <span class="wpe-editable-value">${Utils.escapeHtml(displayValue)}</span>
                    <input type="${type}" class="wpe-editable-input ${className}" 
                           value="${escapedValue}" data-original="${escapedValue}">
                </div>
            `;
        },

        /**
         * Create title field HTML
         * @param {object} config - Field configuration
         * @returns {string}
         */
        createTitleField(config) {
            const { productId, value } = config;
            const escapedValue = Utils.escapeAttr(value);

            return `
                <div class="wpe-editable wpe-editable-title" data-product-id="${productId}" data-field="title">
                    <span class="wpe-editable-value">${Utils.escapeHtml(value)}</span>
                    <div class="wpe-title-input-wrapper" style="display: none;">
                        <input type="text" class="wpe-title-input" value="${escapedValue}" data-original="${escapedValue}">
                        <div class="wpe-title-buttons">
                            <button type="button" class="wpe-btn-save">${Utils.i18n('saved', '✓')}</button>
                            <button type="button" class="wpe-btn-cancel">${Utils.i18n('cancel', '✕')}</button>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Create price field HTML
         * @param {object} config - Field configuration
         * @returns {string}
         */
        createPriceField(config) {
            const { productId, field, value } = config;
            const displayValue = Utils.formatPrice(value);
            const escapedValue = Utils.escapeAttr(value);

            return `
                <div class="wpe-editable wpe-price-field" data-product-id="${productId}" data-field="${field}">
                    <input type="text" class="wpe-price-input" 
                           value="${escapedValue}" 
                           data-original="${escapedValue}"
                           placeholder="—">
                </div>
            `;
        },

        /**
         * Create select field HTML
         * @param {object} config - Field configuration
         * @returns {string}
         */
        createSelectField(config) {
            const { productId, field, value, options } = config;

            let optionsHtml = '';
            options.forEach(opt => {
                const selected = opt.value === value ? 'selected' : '';
                optionsHtml += `<option value="${Utils.escapeAttr(opt.value)}" ${selected}>${Utils.escapeHtml(opt.label)}</option>`;
            });

            return `
                <div class="wpe-editable" data-product-id="${productId}" data-field="${field}">
                    <select class="wpe-editable-select" data-original="${Utils.escapeAttr(value)}">
                        ${optionsHtml}
                    </select>
                </div>
            `;
        },

        /**
         * Start title editing
         * @param {jQuery} $wrapper - Title wrapper element
         */
        startTitleEdit($wrapper) {
            $wrapper.find('.wpe-editable-value').hide();
            $wrapper.find('.wpe-title-input-wrapper').show();
            $wrapper.find('.wpe-title-input').focus().select();
            $wrapper.addClass('wpe-editable-active');
        },

        /**
         * Save title edit
         * @param {jQuery} $wrapper - Title wrapper element
         */
        saveTitleEdit($wrapper) {
            const $input = $wrapper.find('.wpe-title-input');
            const newValue = $input.val();
            const originalValue = $input.data('original');
            const productId = $wrapper.data('product-id');

            // Validate
            const validation = Utils.validateTitle(newValue);
            if (!validation.valid) {
                UI.showNotification(validation.error, 'error');
                $input.focus();
                return;
            }

            // If unchanged, just cancel
            if (newValue === originalValue) {
                this.cancelTitleEdit($wrapper);
                return;
            }

            this.saveField(productId, 'title', validation.value, $wrapper);
        },

        /**
         * Cancel title edit
         * @param {jQuery} $wrapper - Title wrapper element
         */
        cancelTitleEdit($wrapper) {
            const $input = $wrapper.find('.wpe-title-input');
            const originalValue = $input.data('original');

            $input.val(originalValue);
            $wrapper.find('.wpe-editable-value').show();
            $wrapper.find('.wpe-title-input-wrapper').hide();
            $wrapper.removeClass('wpe-editable-active');
        },

        /**
         * Save price field
         * @param {jQuery} $input - Price input element
         */
        savePriceField($input) {
            const $wrapper = $input.closest('.wpe-editable');
            const productId = $wrapper.data('product-id');
            const field = $wrapper.data('field');
            const newValue = $input.val();
            const originalValue = $input.data('original');

            // If unchanged, do nothing
            if (newValue === originalValue) {
                return;
            }

            // Validate
            const allowEmpty = field === 'sale_price';
            const validation = Utils.validatePrice(newValue, allowEmpty);

            if (!validation.valid) {
                UI.showNotification(validation.error, 'error');
                $wrapper.addClass('wpe-save-error');
                setTimeout(() => $wrapper.removeClass('wpe-save-error'), 2000);
                $input.val(originalValue);
                return;
            }

            // Debounce save to prevent rapid multiple saves
            const key = `${productId}_${field}`;
            if (this.saveDebounced[key]) {
                clearTimeout(this.saveDebounced[key]);
            }

            this.saveDebounced[key] = setTimeout(() => {
                this.saveField(productId, field, validation.value, $wrapper);
                delete this.saveDebounced[key];
            }, 200);
        },

        /**
         * Save select field
         * @param {jQuery} $select - Select element
         */
        saveSelectField($select) {
            const $wrapper = $select.closest('.wpe-editable');
            const productId = $wrapper.data('product-id');
            const field = $wrapper.data('field');
            const newValue = $select.val();
            const originalValue = $select.data('original');

            // If unchanged, do nothing
            if (newValue === originalValue) {
                return;
            }

            this.saveField(productId, field, newValue, $wrapper);
        },

        /**
         * Save field to server
         * @param {number} productId - Product ID
         * @param {string} field - Field name
         * @param {string} value - New value
         * @param {jQuery} $wrapper - Field wrapper element
         */
        saveField(productId, field, value, $wrapper) {
            $wrapper.addClass('wpe-saving');

            ApiClient.updateProduct(productId, field, value)
                .done((response) => {
                    $wrapper.removeClass('wpe-saving');

                    if (response.success) {
                        // Update original value
                        const $input = $wrapper.find('input, select');
                        $input.data('original', value);

                        // Update display value for title
                        if (field === 'title') {
                            $wrapper.find('.wpe-editable-value').text(value);
                            this.cancelTitleEdit($wrapper);
                        }

                        // Show success state
                        $wrapper.addClass('wpe-saved');
                        setTimeout(() => $wrapper.removeClass('wpe-saved'), 1500);

                        UI.showNotification(response.message, 'success', 3000);
                    } else {
                        throw new Error(response.message || 'Save failed');
                    }
                })
                .fail((xhr) => {
                    $wrapper.removeClass('wpe-saving');
                    $wrapper.addClass('wpe-save-error');
                    setTimeout(() => $wrapper.removeClass('wpe-save-error'), 2000);

                    // Revert to original value
                    const $input = $wrapper.find('input, select');
                    $input.val($input.data('original'));
                });
        },

        /**
         * Get stock status options
         * @returns {array}
         */
        getStockStatusOptions() {
            return [
                { value: 'instock', label: Utils.i18n('instock', 'In Stock') },
                { value: 'outofstock', label: Utils.i18n('outofstock', 'Out of Stock') },
                { value: 'onbackorder', label: Utils.i18n('onbackorder', 'On Backorder') }
            ];
        },

        /**
         * Get tax status options
         * @returns {array}
         */
        getTaxStatusOptions() {
            return [
                { value: 'taxable', label: Utils.i18n('taxable', 'Taxable') },
                { value: 'shipping', label: Utils.i18n('shipping', 'Shipping only') },
                { value: 'none', label: Utils.i18n('none', 'None') }
            ];
        },

        /**
         * Get tax class options
         * @returns {array}
         */
        getTaxClassOptions() {
            const options = [{ value: '', label: Utils.i18n('standard', 'Standard') }];
            this.taxClasses.forEach(tc => {
                if (tc.slug !== '') {
                    options.push({ value: tc.slug, label: tc.name });
                }
            });
            return options;
        }
    };

    /* ==========================================================================
       Column Visibility Module
       ========================================================================== */

    const ColumnVisibility = {
        columnMap: {
            'sku': 2,
            'status': 3,
            'regular_price': 4,
            'sale_price': 5,
            'tax_status': 6,
            'tax_class': 7,
            'stock_status': 8,
            'categories': 9
        },

        /**
         * Initialize column visibility
         */
        init() {
            this.bindEvents();
            this.applyInitialVisibility();
        },

        /**
         * Bind column toggle events
         */
        bindEvents() {
            $('.wpe-column-toggle input').on('change', (e) => {
                const $checkbox = $(e.currentTarget);
                const column = $checkbox.data('column');
                const visible = $checkbox.is(':checked');

                this.toggleColumn(column, visible);
            });
        },

        /**
         * Apply initial visibility from checkboxes
         */
        applyInitialVisibility() {
            $('.wpe-column-toggle input').each((i, checkbox) => {
                const $checkbox = $(checkbox);
                const column = $checkbox.data('column');
                const visible = $checkbox.is(':checked');

                if (!visible) {
                    this.toggleColumn(column, false);
                }
            });
        },

        /**
         * Toggle column visibility
         * @param {string} column - Column name
         * @param {boolean} visible - Whether visible
         */
        toggleColumn(column, visible) {
            const columnIndex = this.columnMap[column];
            if (typeof columnIndex === 'undefined') return;

            const table = ProductTable.table;
            if (table) {
                table.column(columnIndex).visible(visible);
            }
        }
    };

    /* ==========================================================================
       Product Table Module
       ========================================================================== */

    const ProductTable = {
        table: null,
        $table: null,

        /**
         * Initialize DataTable
         */
        init() {
            this.$table = $('#wpe-products-table');

            this.table = this.$table.DataTable({
                serverSide: true,
                processing: true,
                pageLength: Config.pageLength,
                order: [[0, 'desc']],
                language: this.getLanguage(),
                ajax: {
                    url: `${Config.restUrl}/products`,
                    type: 'GET',
                    beforeSend: (xhr) => {
                        xhr.setRequestHeader('X-WP-Nonce', Config.nonce);
                    },
                    data: (d) => {
                        const filters = Filters.getFilters();
                        return {
                            page: Math.floor(d.start / d.length) + 1,
                            per_page: d.length,
                            search: filters.search,
                            status: filters.status,
                            category: filters.category,
                            tax_status: filters.tax_status,
                            stock_status: filters.stock_status,
                            orderby: this.getOrderColumn(d.order),
                            order: d.order[0] ? d.order[0].dir.toUpperCase() : 'DESC'
                        };
                    },
                    dataSrc: (json) => {
                        UI.updateRecordCount(json.products.length, json.total);
                        return json.products;
                    },
                    error: (xhr, error, thrown) => {
                        ApiClient.handleError(xhr, error, thrown);
                        return [];
                    }
                },
                columns: this.getColumns(),
                drawCallback: () => {
                    UI.hideLoading();
                },
                preDrawCallback: () => {
                    // Optional: show loading
                }
            });

            // Initialize column visibility after table is ready
            this.table.on('init', () => {
                ColumnVisibility.init();
            });
        },

        /**
         * Get column configuration
         * @returns {array}
         */
        getColumns() {
            return [
                {
                    data: 'id',
                    render: (data, type, row) => {
                        return `<strong>#${Utils.escapeHtml(data)}</strong>`;
                    }
                },
                {
                    data: 'title',
                    render: (data, type, row) => {
                        return Editing.createEditableField({
                            productId: row.id,
                            field: 'title',
                            value: data
                        });
                    }
                },
                {
                    data: 'sku',
                    render: (data) => {
                        return `<code>${Utils.escapeHtml(data || '—')}</code>`;
                    }
                },
                {
                    data: 'status',
                    render: (data) => {
                        const statusClass = `wpe-status-${data}`;
                        const label = Utils.i18n(data, data);
                        return `<span class="wpe-status-badge ${statusClass}">${Utils.escapeHtml(label)}</span>`;
                    }
                },
                {
                    data: 'regular_price',
                    render: (data, type, row) => {
                        return Editing.createEditableField({
                            productId: row.id,
                            field: 'regular_price',
                            value: data
                        });
                    }
                },
                {
                    data: 'sale_price',
                    render: (data, type, row) => {
                        return Editing.createEditableField({
                            productId: row.id,
                            field: 'sale_price',
                            value: data
                        });
                    }
                },
                {
                    data: 'tax_status',
                    render: (data, type, row) => {
                        return Editing.createSelectField({
                            productId: row.id,
                            field: 'tax_status',
                            value: data,
                            options: Editing.getTaxStatusOptions()
                        });
                    }
                },
                {
                    data: 'tax_class',
                    render: (data, type, row) => {
                        return Editing.createSelectField({
                            productId: row.id,
                            field: 'tax_class',
                            value: data,
                            options: Editing.getTaxClassOptions()
                        });
                    }
                },
                {
                    data: 'stock_status',
                    render: (data, type, row) => {
                        return Editing.createSelectField({
                            productId: row.id,
                            field: 'stock_status',
                            value: data,
                            options: Editing.getStockStatusOptions()
                        });
                    }
                },
                {
                    data: 'categories',
                    render: (data) => {
                        return `<span class="wpe-categories">${Utils.escapeHtml(data || '—')}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => {
                        return `
                            <div class="wpe-actions">
                                <a href="${Utils.escapeAttr(row.edit_link)}" 
                                   class="wpe-action-link" 
                                   target="_blank" 
                                   title="${Utils.i18n('edit', 'Edit')}">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="${Utils.escapeAttr(row.view_link)}" 
                                   class="wpe-action-link" 
                                   target="_blank" 
                                   title="${Utils.i18n('view', 'View')}">
                                    <span class="dashicons dashicons-visibility"></span>
                                </a>
                            </div>
                        `;
                    }
                }
            ];
        },

        /**
         * Get order column name
         * @param {array} order - DataTables order array
         * @returns {string}
         */
        getOrderColumn(order) {
            const columnMap = ['ID', 'title', 'sku', 'status', 'price', 'price', 'tax_status', 'tax_class', 'stock_status', 'categories'];
            const columnIndex = order[0] ? order[0].column : 0;
            return columnMap[columnIndex] || 'ID';
        },

        /**
         * Get DataTables language configuration
         * @returns {object}
         */
        getLanguage() {
            return {
                processing: Utils.i18n('loading', 'Loading...'),
                search: Utils.i18n('search', 'Search:'),
                lengthMenu: `${Utils.i18n('show', 'Show')} _MENU_ ${Utils.i18n('entries', 'entries')}`,
                info: `${Utils.i18n('showing', 'Showing')} _START_ - _END_ ${Utils.i18n('of', 'of')} _TOTAL_`,
                infoEmpty: Utils.i18n('noData', 'No products found'),
                infoFiltered: `(${Utils.i18n('filteredFrom', 'filtered from')} _MAX_ ${Utils.i18n('total', 'total')})`,
                loadingRecords: Utils.i18n('loading', 'Loading...'),
                zeroRecords: Utils.i18n('noData', 'No products found'),
                emptyTable: Utils.i18n('noData', 'No products found'),
                paginate: {
                    first: Utils.i18n('first', 'First'),
                    last: Utils.i18n('last', 'Last'),
                    next: Utils.i18n('next', 'Next'),
                    previous: Utils.i18n('previous', 'Previous')
                }
            };
        },

        /**
         * Reload table data
         */
        reload() {
            if (this.table) {
                this.table.ajax.reload(null, false);
            }
        }
    };

    /* ==========================================================================
       Main Application
       ========================================================================== */

    const App = {
        /**
         * Initialize application
         */
        init() {
            // Check for required elements
            if ($('#wpe-products-table').length === 0) {
                console.error('WPE: Products table not found');
                return;
            }

            // Initialize modules
            UI.init();
            Filters.init();
            Editing.init();
            ProductTable.init();

            // Log initialization
            console.log('WPE: Price Editor initialized');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        App.init();
    });

})(jQuery);
