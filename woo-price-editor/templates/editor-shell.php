<?php
/**
 * Standalone admin template for the Woo Price Editor shell.
 *
 * @var array $context
 *
 * @package WooPriceEditor
 */

defined('ABSPATH') || exit;

$context = wp_parse_args(
    $context,
    [
        'nonce'    => '',
        'settings' => [],
        'user'     => wp_get_current_user(),
    ]
);

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Woo Price Editor', 'woo-price-editor'); ?></title>
    <style>
        :root {
            --wpe-bg: #0f172a;
            --wpe-panel: #1e293b;
            --wpe-text: #e2e8f0;
            --wpe-accent: #22d3ee;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--wpe-bg);
            color: var(--wpe-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .wpe-shell {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .wpe-header {
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.6);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .wpe-branding {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .wpe-logo {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--wpe-accent), #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            color: #0f172a;
        }
        .wpe-user {
            font-weight: 500;
        }
        .wpe-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 40px;
            gap: 24px;
        }
        .wpe-card {
            background: var(--wpe-panel);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.5);
        }
        .wpe-card h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 1.25rem;
        }
        .wpe-settings {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            background: rgba(15, 23, 42, 0.8);
            padding: 16px;
            border-radius: 12px;
            overflow: auto;
            max-height: 240px;
        }
    </style>
</head>
<body class="wpe-shell" data-wpe-nonce="<?php echo esc_attr($context['nonce']); ?>">
<header class="wpe-header">
    <div class="wpe-branding">
        <div class="wpe-logo">W</div>
        <div>
            <div><?php esc_html_e('Woo Price Editor', 'woo-price-editor'); ?></div>
            <small><?php esc_html_e('Standalone editor shell', 'woo-price-editor'); ?></small>
        </div>
    </div>
    <div class="wpe-user">
        <?php echo esc_html($context['user'] instanceof WP_User ? $context['user']->display_name : ''); ?>
    </div>
</header>
<main class="wpe-main">
    <section class="wpe-card">
        <h2><?php esc_html_e('Editor status', 'woo-price-editor'); ?></h2>
        <p><?php esc_html_e('This full-screen container replaces the standard wp-admin chrome and is ready for the interactive editor UI.', 'woo-price-editor'); ?></p>
    </section>
    <section class="wpe-card">
        <h2><?php esc_html_e('Boot configuration', 'woo-price-editor'); ?></h2>
        <pre class="wpe-settings"><?php echo esc_html(wp_json_encode($context['settings'], JSON_PRETTY_PRINT)); ?></pre>
    </section>
</main>
</body>
</html>
