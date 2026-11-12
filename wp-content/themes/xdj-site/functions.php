<?php
if (!defined('ABSPATH')) exit;
add_action('after_setup_theme', function(){add_theme_support('title-tag');add_theme_support('post-thumbnails');register_nav_menus(['primary'=>'主导航']);});
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('onestep',get_stylesheet_uri(),[],filemtime(get_template_directory().'/style.css'));});
