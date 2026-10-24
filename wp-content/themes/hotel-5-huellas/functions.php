<?php
add_action('wp_enqueue_scripts', function () {
  $dir = get_stylesheet_directory();
  $uri = get_stylesheet_directory_uri();

  wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], null);
  wp_enqueue_style('hotel-style', get_stylesheet_uri(), ['leaflet'], filemtime($dir . '/style.css'));

  wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
  wp_enqueue_script('hotel-app', $uri . '/app.js', ['leaflet'], filemtime($dir . '/app.js'), true);

  wp_localize_script('hotel-app', 'HotelCfg', [
    'hotelesUrl'  => rest_url('hotel/v1/hoteles'),
    'reservasUrl' => rest_url('hotel/v1/reservas'),
  ]);
});