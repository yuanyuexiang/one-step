<?php
if (!defined('ABSPATH')) exit;

// Derivatives are prepared explicitly by WP-CLI; visitors never trigger image processing.
function xdj_image_attributes($url, $sizes = '(max-width: 600px) 100vw, 950px', $preferred = 1280) {
    static $manifest;
    if ($manifest === null) $manifest = get_option('xdj_image_variants', []);
    $base = content_url('/uploads/legacy/');
    if (!str_starts_with($url, $base)) return [];
    $variants = $manifest[basename(wp_parse_url($url, PHP_URL_PATH))] ?? [];
    if (!$variants) return [];
    ksort($variants, SORT_NUMERIC);
    $selected = end($variants);
    foreach ($variants as $width => $variant) {
        if ($width >= $preferred) { $selected = $variant; break; }
    }
    $srcset = [];
    foreach ($variants as $width => $variant) $srcset[] = $base.'optimized/'.$variant['file'].' '.$width.'w';
    return ['src' => $base.'optimized/'.$selected['file'], 'srcset' => implode(', ', $srcset),
        'sizes' => $sizes, 'width' => $selected['width'], 'height' => $selected['height'], 'decoding' => 'async'];
}
function xdj_image_html($file, $alt, $preferred = 768, $attributes = [], $sizes = '(max-width: 600px) 100vw, 600px') {
    $attributes = array_merge(['src' => xdj_media_url($file), 'alt' => $alt, 'loading' => 'lazy'],
        $attributes, xdj_image_attributes(xdj_media_url($file), $sizes, $preferred));
    $html = '<img';
    foreach ($attributes as $key => $value) if ($value !== null) $html .= ' '.esc_attr($key).'="'.esc_attr($value).'"';
    return $html.'>';
}
add_filter('wp_get_attachment_image_attributes', function ($attributes) {
    return array_merge($attributes, xdj_image_attributes($attributes['src'] ?? '', $attributes['sizes'] ?? '(max-width: 600px) 50vw, 400px', 768));
});
add_filter('the_content', function ($html) {
    if (!class_exists('WP_HTML_Tag_Processor')) return $html;
    $tags = new WP_HTML_Tag_Processor($html);
    while ($tags->next_tag('IMG')) {
        $url = $tags->get_attribute('src');
        $attributes = xdj_image_attributes(is_string($url) ? $url : '');
        if ($attributes) $tags->set_attribute('data-original-src', $url);
        foreach ($attributes as $key => $value) $tags->set_attribute($key, $value);
        if (!$tags->get_attribute('loading')) $tags->set_attribute('loading', 'lazy');
        $tags->set_attribute('decoding', 'async');
    }
    return $tags->get_updated_html();
}, 30);
