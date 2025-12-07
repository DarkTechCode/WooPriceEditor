<?php
/**
 * Security helper class
 *
 * Provides security utilities for nonce verification, rate limiting,
 * capability checks, and input sanitization.
 *
 * @package WooPriceEditor
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPE_Security
 *
 * Handles all security-related functionality for the plugin
 */
class WPE_Security {

    /**
     * Nonce action name
     *
     * @var string
     */
    const NONCE_ACTION = 'wpe_price_editor_action';

    /**
     * Rate limit transient prefix
     *
     * @var string
     */
    const RATE_LIMIT_PREFIX = 'wpe_rate_';

    /**
     * Maximum requests per minute
     *
     * @var int
     */
    private static $rate_limit = 100;

    /**
     * Initialize rate limit from settings
     *
     * @return void
     */
    public static function init() {
        $settings = get_option('wpe_settings', []);
        if (isset($settings['rate_limit'])) {
            self::$rate_limit = absint($settings['rate_limit']);
        }
    }

    /**
     * Generate a nonce for the price editor
     *
     * @return string
     */
    public static function create_nonce() {
        return wp_create_nonce(self::NONCE_ACTION);
    }

    /**
     * Verify nonce from request
     *
     * @param string $nonce Nonce to verify
     * @return bool
     */
    public static function verify_nonce($nonce) {
        return wp_verify_nonce($nonce, self::NONCE_ACTION) !== false;
    }

    /**
     * Check if current user can manage WooCommerce
     *
     * @return bool
     */
    public static function can_manage_products() {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Check if current user can edit specific product
     *
     * @param int $product_id Product ID
     * @return bool
     */
    public static function can_edit_product($product_id) {
        if (!self::can_manage_products()) {
            return false;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        return current_user_can('edit_post', $product_id);
    }

    /**
     * Check rate limit for current user
     *
     * @return bool True if within limit, false if exceeded
     */
    public static function check_rate_limit() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }

        $key = self::RATE_LIMIT_PREFIX . $user_id;
        $count = get_transient($key);

        if ($count === false) {
            set_transient($key, 1, MINUTE_IN_SECONDS);
            return true;
        }

        if ($count >= self::$rate_limit) {
            return false;
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Sanitize product field value based on field type
     *
     * @param string $field Field name
     * @param mixed  $value Value to sanitize
     * @return mixed Sanitized value
     * @throws InvalidArgumentException If field is unknown
     */
    public static function sanitize_field($field, $value) {
        $field_config = self::get_field_config();

        if (!isset($field_config[$field])) {
            throw new InvalidArgumentException(
                sprintf(
                    /* translators: %s: Field name */
                    __('Неизвестное поле: %s', 'woo-price-editor'),
                    $field
                )
            );
        }

        $sanitize_callback = $field_config[$field]['sanitize'];

        if (is_callable($sanitize_callback)) {
            return call_user_func($sanitize_callback, $value);
        }

        return sanitize_text_field($value);
    }

    /**
     * Validate product field value
     *
     * @param string $field Field name
     * @param mixed  $value Value to validate
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validate_field($field, $value) {
        $result = ['valid' => true, 'error' => null];

        switch ($field) {
            case 'regular_price':
            case 'sale_price':
                $result = self::validate_price($value, $field === 'sale_price');
                break;

            case 'title':
                $result = self::validate_title($value);
                break;

            case 'tax_status':
                $valid_statuses = ['taxable', 'shipping', 'none'];
                if (!in_array($value, $valid_statuses, true)) {
                    $result = [
                        'valid' => false,
                        'error' => __('Недопустимое значение налогового статуса', 'woo-price-editor'),
                    ];
                }
                break;

            case 'stock_status':
                $valid_statuses = ['instock', 'outofstock', 'onbackorder'];
                if (!in_array($value, $valid_statuses, true)) {
                    $result = [
                        'valid' => false,
                        'error' => __('Недопустимое значение статуса наличия', 'woo-price-editor'),
                    ];
                }
                break;

            case 'tax_class':
                // Tax class can be empty (standard) or any valid class
                break;
        }

        return $result;
    }

    /**
     * Validate price value
     *
     * @param mixed $price     Price value
     * @param bool  $allow_empty Whether empty is allowed (for sale price)
     * @return array
     */
    private static function validate_price($price, $allow_empty = false) {
        // Trim whitespace and handle empty values
        $price = trim((string) $price);

        if ($price === '') {
            if ($allow_empty) {
                return ['valid' => true, 'error' => null];
            }
            return [
                'valid' => false,
                'error' => __('Цена не может быть пустой', 'woo-price-editor'),
            ];
        }

        // Заменить запятую на точку для дробной части
        $price = str_replace(',', '.', $price);

        // Удалить символы валюты и пробелы
        $price = preg_replace('/[^\d.-]/', '', $price);

        if (!is_numeric($price)) {
            return [
                'valid' => false,
                'error' => __('Цена должна быть числом', 'woo-price-editor'),
            ];
        }

        $num_price = (float) $price;

        if ($num_price < 0) {
            return [
                'valid' => false,
                'error' => __('Цена не может быть отрицательной', 'woo-price-editor'),
            ];
        }

        if ($num_price > 999999999.99) {
            return [
                'valid' => false,
                'error' => __('Слишком большое значение цены', 'woo-price-editor'),
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate title value
     *
     * @param string $title Title value
     * @return array
     */
    private static function validate_title($title) {
        $title = trim($title);

        if (empty($title)) {
            return [
                'valid' => false,
                'error' => __('Название не может быть пустым', 'woo-price-editor'),
            ];
        }

        if (mb_strlen($title) > 200) {
            return [
                'valid' => false,
                'error' => __('Название слишком длинное (макс. 200 символов)', 'woo-price-editor'),
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Get field configuration
     *
     * @return array
     */
    public static function get_field_config() {
        return [
            'title' => [
                'getter'   => 'get_name',
                'setter'   => 'set_name',
                'label'    => __('Название', 'woo-price-editor'),
                'sanitize' => 'sanitize_text_field',
            ],
            'regular_price' => [
                'getter'   => 'get_regular_price',
                'setter'   => 'set_regular_price',
                'label'    => __('Обычная цена', 'woo-price-editor'),
                'sanitize' => 'wc_format_decimal',
            ],
            'sale_price' => [
                'getter'   => 'get_sale_price',
                'setter'   => 'set_sale_price',
                'label'    => __('Цена со скидкой', 'woo-price-editor'),
                'sanitize' => [__CLASS__, 'sanitize_sale_price'],
            ],
            'tax_status' => [
                'getter'   => 'get_tax_status',
                'setter'   => 'set_tax_status',
                'label'    => __('Налоговый статус', 'woo-price-editor'),
                'sanitize' => 'sanitize_text_field',
            ],
            'tax_class' => [
                'getter'   => 'get_tax_class',
                'setter'   => 'set_tax_class',
                'label'    => __('Налоговый класс', 'woo-price-editor'),
                'sanitize' => 'sanitize_text_field',
            ],
            'stock_status' => [
                'getter'   => 'get_stock_status',
                'setter'   => 'set_stock_status',
                'label'    => __('Статус наличия', 'woo-price-editor'),
                'sanitize' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Sanitize sale price (can be empty)
     *
     * @param string $value Sale price value
     * @return string
     */
    public static function sanitize_sale_price($value) {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return wc_format_decimal($value);
    }

    /**
     * Log security event
     *
     * @param string $event   Event type
     * @param array  $context Additional context
     * @return void
     */
    public static function log_event($event, $context = []) {
        $settings = get_option('wpe_settings', []);
        if (empty($settings['enable_logging'])) {
            return;
        }

        if (!function_exists('wc_get_logger')) {
            return;
        }

        $logger = wc_get_logger();
        $context['source'] = 'woo-price-editor';
        $context['user_id'] = get_current_user_id();
        $context['ip'] = self::get_client_ip();

        $logger->info($event, $context);
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            // Take first IP if multiple
            $ip = explode(',', $ip)[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return trim($ip);
    }

    /**
     * Escape HTML for safe output
     *
     * @param string $text Text to escape
     * @return string
     */
    public static function escape_html($text) {
        return esc_html($text);
    }

    /**
     * Escape attribute for safe output
     *
     * @param string $text Text to escape
     * @return string
     */
    public static function escape_attr($text) {
        return esc_attr($text);
    }
}

// Initialize security settings
add_action('init', ['WPE_Security', 'init']);
