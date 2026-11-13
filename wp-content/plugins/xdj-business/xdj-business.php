<?php
/**
 * Plugin Name: One Step Business
 * Description: Structured content and market services for ai-onestep.com.
 * Version: 0.1.0
 * Text Domain: xdj-business
 */
if (!defined('ABSPATH')) { exit; }
add_action('init', function () {
    $types = [
        'product' => ['Products', 'products'],
        'solution' => ['Solutions', 'solutions'],
        'case_study' => ['Case Studies', 'case-studies'],
        'market' => ['Service Markets', 'service-region'],
    ];
    foreach ($types as $type => [$label, $slug]) {
        register_post_type($type, [
            'label' => $label,
            'public' => true,
            'show_in_rest' => true,
            // /markets/ belongs to the translated landing page.
            'has_archive' => !in_array($type, ['market','case_study'], true),
            'rewrite' => ['slug' => $slug],
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ]);
    }
    register_taxonomy('product_category', ['product'], [
        'label' => 'Product Categories', 'public' => true, 'hierarchical' => true,
        'show_in_rest' => true,
    ]);
});
register_activation_hook(__FILE__, function () {
    // Rewrite rules are refreshed on activation, never on every request.
    update_option('xdj_flush_rewrite', true);
});
add_action('init', function () {
    if (get_option('xdj_flush_rewrite')) {
        flush_rewrite_rules(); delete_option('xdj_flush_rewrite');
    }
}, 99);
require_once __DIR__ . '/includes/inquiries.php';
require_once __DIR__ . '/includes/languages.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/discovery.php';
