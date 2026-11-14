<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/design.php';
add_action('after_setup_theme', function(){add_theme_support('title-tag');add_theme_support('post-thumbnails');register_nav_menus(['primary'=>'主导航']);});
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('onestep',get_stylesheet_uri(),[],filemtime(get_template_directory().'/style.css'));});

// A future icon selected in WordPress takes precedence over the theme's brand fallback.
add_filter('get_site_icon_url', function ($url, $size) {
    if (get_option('site_icon')) return $url;
    $available = [16,32,48,64,180,192,270,512];
    $chosen = 512;
    foreach ($available as $candidate) {
        if ($candidate >= $size) { $chosen = $candidate; break; }
    }
    $asset = 'assets/favicon-'.$chosen.'.png';
    return add_query_arg('v', filemtime(get_theme_file_path($asset)), get_theme_file_uri($asset));
}, 10, 2);

// Requests for /favicon.ico must return the brand ICO, including browsers without head discovery.
add_action('do_faviconico', function () {
    if (get_option('site_icon')) return;
    $path = get_theme_file_path('assets/favicon.ico');
    if (!is_readable($path)) return;
    status_header(200);
    header('Content-Type: image/x-icon');
    header('Content-Length: '.filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('onestep-design', get_theme_file_uri('assets/design.css'), ['onestep'], filemtime(get_theme_file_path('assets/design.css')));
});
