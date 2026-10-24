<?php
/*
Plugin Name: Hotel API
Description: Hoteles y reservas del Hotel 5 Huellas (API REST)
Version: 2.0
*/
if (!defined('ABSPATH')) exit;

add_action('init', function () {
  register_post_type('hotel', [
    'label'     => 'Hoteles',
    'public'    => false,
    'show_ui'   => true,
    'menu_icon' => 'dashicons-building',
    'supports'  => ['title'],
  ]);
  register_post_type('reserva', [
    'label'     => 'Reservas',
    'public'    => false,
    'show_ui'   => true,
    'menu_icon' => 'dashicons-calendar-alt',
    'supports'  => ['title'],
  ]);

  if (!is_blog_installed() || get_option('h5_hoteles_cargados')) return;

  // nombre, ciudad, precio por noche, estrellas, admite mascotas, latitud, longitud
  $hoteles = [
    ['Hotel Plaza Huellas',       'Madrid',    95,  4, 1, 40.4200, -3.7050],
    ['Gran Vía Suites',           'Madrid',    140, 5, 0, 40.4203, -3.7058],
    ['Hostal Retiro Verde',       'Madrid',    55,  2, 1, 40.4153, -3.6844],
    ['Hotel Mar y Patas',         'Barcelona', 120, 4, 1, 41.3809,  2.1900],
    ['Ramblas Boutique',          'Barcelona', 160, 5, 0, 41.3818,  2.1735],
    ['Hostal Gótico',             'Barcelona', 60,  2, 1, 41.3833,  2.1767],
    ['Hotel Turia',               'Valencia',  85,  3, 1, 39.4750, -0.3700],
    ['Resort Malvarrosa',         'Valencia',  130, 4, 0, 39.4800, -0.3250],
    ['Hotel Giralda Pet Friendly','Sevilla',   90,  4, 1, 37.3860, -5.9926],
    ['Casa Triana',               'Sevilla',   70,  3, 0, 37.3835, -6.0035],
    ['Mirador del Tajo',          'Toledo',    110, 4, 1, 39.8570, -4.0300],
    ['Hostal Sefardí',            'Toledo',    50,  2, 1, 39.8567, -4.0242],
  ];
  foreach ($hoteles as $h) {
    $id = wp_insert_post([
      'post_type'   => 'hotel',
      'post_status' => 'publish',
      'post_title'  => $h[0],
    ]);
    if ($id && !is_wp_error($id)) {
      update_post_meta($id, 'ciudad',    $h[1]);
      update_post_meta($id, 'precio',    $h[2]);
      update_post_meta($id, 'estrellas', $h[3]);
      update_post_meta($id, 'mascotas',  $h[4]);
      update_post_meta($id, 'lat',       $h[5]);
      update_post_meta($id, 'lng',       $h[6]);
    }
  }
  update_option('h5_hoteles_cargados', 1);
});

add_action('rest_api_init', function () {
  register_rest_route('hotel/v1', '/hoteles', [
    'methods'             => 'GET',
    'callback'            => 'h5_listar_hoteles',
    'permission_callback' => '__return_true',
  ]);
  register_rest_route('hotel/v1', '/reservas', [
    [
      'methods'             => 'GET',
      'callback'            => 'h5_listar_reservas',
      'permission_callback' => '__return_true',
    ],
    [
      'methods'             => 'POST',
      'callback'            => 'h5_crear_reserva',
      'permission_callback' => '__return_true',
    ],
  ]);
});

function h5_listar_hoteles(WP_REST_Request $req) {
  $ciudad        = strtolower(remove_accents(sanitize_text_field((string) $req->get_param('ciudad'))));
  $precio_max    = (float) $req->get_param('precio_max');
  $estrellas_min = (int) $req->get_param('estrellas_min');
  $solo_mascotas = $req->get_param('mascotas') === '1';

  $posts = get_posts([
    'post_type'   => 'hotel',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
  ]);

  $salida = [];
  foreach ($posts as $p) {
    $h = [
      'id'        => $p->ID,
      'nombre'    => $p->post_title,
      'ciudad'    => get_post_meta($p->ID, 'ciudad', true),
      'precio'    => (float) get_post_meta($p->ID, 'precio', true),
      'estrellas' => (int) get_post_meta($p->ID, 'estrellas', true),
      'mascotas'  => get_post_meta($p->ID, 'mascotas', true) === '1',
      'lat'       => (float) get_post_meta($p->ID, 'lat', true),
      'lng'       => (float) get_post_meta($p->ID, 'lng', true),
    ];
    if ($ciudad !== '' && strpos(strtolower(remove_accents($h['ciudad'])), $ciudad) === false) continue;
    if ($precio_max > 0 && $h['precio'] > $precio_max) continue;
    if ($h['estrellas'] < $estrellas_min) continue;
    if ($solo_mascotas && !$h['mascotas']) continue;
    $salida[] = $h;
  }
  return $salida;
}

function h5_listar_reservas() {
  $posts = get_posts([
    'post_type'   => 'reserva',
    'post_status' => 'publish',
    'numberposts' => 50,
    'orderby'     => 'date',
    'order'       => 'DESC',
  ]);
  // No se devuelve el nombre del dueño (dato privado)
  return array_map(function ($p) {
    return [
      'hotel'   => get_post_meta($p->ID, 'hotel', true),
      'mascota' => get_post_meta($p->ID, 'mascota', true),
      'entrada' => get_post_meta($p->ID, 'entrada', true),
      'salida'  => get_post_meta($p->ID, 'salida', true),
    ];
  }, $posts);
}

function h5_crear_reserva(WP_REST_Request $req) {
  $dueno   = sanitize_text_field($req['dueno']);
  $hotel   = sanitize_text_field($req['hotel']);
  $mascota = sanitize_text_field($req['mascota']);
  $entrada = sanitize_text_field($req['entrada']);
  $salida  = sanitize_text_field($req['salida']);

  if (!$dueno || !$hotel || !$mascota || !$entrada || !$salida) {
    return new WP_Error('datos', 'Faltan datos', ['status' => 400]);
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entrada) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $salida)) {
    return new WP_Error('fechas', 'Fechas no válidas', ['status' => 400]);
  }
  if (strtotime($salida) <= strtotime($entrada)) {
    return new WP_Error('fechas', 'La salida debe ser posterior a la entrada', ['status' => 400]);
  }

  $id = wp_insert_post([
    'post_type'   => 'reserva',
    'post_status' => 'publish',
    'post_title'  => "$dueno - $hotel ($entrada → $salida)",
  ]);
  update_post_meta($id, 'dueno',   $dueno);
  update_post_meta($id, 'hotel',   $hotel);
  update_post_meta($id, 'mascota', $mascota);
  update_post_meta($id, 'entrada', $entrada);
  update_post_meta($id, 'salida',  $salida);

  return new WP_REST_Response(['id' => $id], 201);
}