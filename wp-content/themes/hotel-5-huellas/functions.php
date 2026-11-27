<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'script', 'style']);
});

add_action('wp_enqueue_scripts', function () {
    $dir = get_template_directory();
    $v_css = file_exists($dir . '/style.css') ? filemtime($dir . '/style.css') : '5';
    $v_js  = file_exists($dir . '/app.js') ? filemtime($dir . '/app.js') : '5';

    wp_enqueue_style('h5-fonts', 'https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap', [], null);
    wp_enqueue_style('h5-style', get_stylesheet_uri(), ['h5-fonts'], $v_css);
    wp_enqueue_script('h5-app', get_template_directory_uri() . '/app.js', [], $v_js, true);
    
    // Guía de bienvenida paso a paso
    $v_tcss = file_exists($dir . '/tour.css') ? filemtime($dir . '/tour.css') : '1';
    $v_tjs  = file_exists($dir . '/tour.js') ? filemtime($dir . '/tour.js') : '1';
    wp_enqueue_style('h5-tour', get_template_directory_uri() . '/tour.css', ['h5-style'], $v_tcss);
    wp_enqueue_script('h5-tour', get_template_directory_uri() . '/tour.js', ['h5-app'], $v_tjs, true);

    $cfg = [
        'root'    => rest_url('hotel/v1/'),
        'nonce'   => wp_create_nonce('wp_rest'),
        'user'    => function_exists('h5_user_info') ? h5_user_info() : ['logged' => false, 'name' => '', 'rol' => '', 'staff' => false],
        'planes'  => function_exists('h5_planes') ? h5_planes() : new stdClass(),
        'extras'  => function_exists('h5_extras') ? h5_extras() : new stdClass(),
    ];
    wp_add_inline_script('h5-app', 'window.HotelCfg=' . wp_json_encode($cfg) . ';', 'before');
});