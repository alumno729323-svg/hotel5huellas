<?php
/**
 * Plugin Name: Hotel 5 Huellas API
 * Description: API REST v6: habitaciones, extras, reservas, usuarios y roles, mis mascotas, perfil, reseñas, contacto, fotos de animales (proxy), estadísticas y panel del personal.
 * Version: 6.0.0
 */
if (!defined('ABSPATH')) exit;

/* ───────────── Datos del hotel ───────────── */

function h5_planes() {
    return [
        'cozy'    => ['id' => 'cozy',    'nombre' => 'Cozy',    'emoji' => '🛏️', 'desc' => 'Cama mullida, ventana al jardín y juguete de bienvenida.', 'precio' => 25, 'capacidad' => 4],
        'confort' => ['id' => 'confort', 'nombre' => 'Confort', 'emoji' => '🛋️', 'desc' => 'Más espacio, sofá propio y zona de juegos privada.',     'precio' => 38, 'capacidad' => 3],
        'suite'   => ['id' => 'suite',   'nombre' => 'Suite',   'emoji' => '👑', 'desc' => 'Suite con terraza, cámara 24 h y cama ortopédica.',       'precio' => 55, 'capacidad' => 2],
    ];
}

function h5_extras() {
    // tipo: 'dia' = se cobra por noche · 'unico' = se cobra una sola vez
    return [
        'paseo' => ['id' => 'paseo', 'nombre' => 'Paseo extra',            'emoji' => '🦮', 'precio' => 8,  'tipo' => 'dia'],
        'video' => ['id' => 'video', 'nombre' => 'Videollamada diaria',    'emoji' => '📱', 'precio' => 4,  'tipo' => 'dia'],
        'bano'  => ['id' => 'bano',  'nombre' => 'Baño y cepillado',       'emoji' => '🛁', 'precio' => 15, 'tipo' => 'unico'],
        'vet'   => ['id' => 'vet',   'nombre' => 'Chequeo veterinario',    'emoji' => '🩺', 'precio' => 20, 'tipo' => 'unico'],
        'spa'   => ['id' => 'spa',   'nombre' => 'Spa y masaje',           'emoji' => '💆', 'precio' => 22, 'tipo' => 'unico'],
        'taxi'  => ['id' => 'taxi',  'nombre' => 'Taxi mascota (ida y vuelta)', 'emoji' => '🚕', 'precio' => 12, 'tipo' => 'unico'],
    ];
}

function h5_especies() { return ['perro', 'gato', 'pajaro', 'conejo', 'hamster', 'otro']; }

/* ───────────── Roles y permisos ───────────── */

add_action('init', function () {
    if (get_option('h5_roles_v') === '5') return;
    remove_role('hotel_dueno');
    remove_role('hotel_cuidador');
    add_role('hotel_dueno', 'Dueño de mascota', [
        'read' => true, 'hotel_reservar' => true, 'hotel_resenar' => true,
    ]);
    add_role('hotel_cuidador', 'Cuidador', [
        'read' => true, 'hotel_reservar' => true, 'hotel_resenar' => true,
        'hotel_ver_reservas' => true, 'hotel_gestionar_reservas' => true, 'hotel_ver_mensajes' => true,
    ]);
    $admin = get_role('administrator');
    if ($admin) {
        foreach (['hotel_reservar', 'hotel_resenar', 'hotel_ver_reservas', 'hotel_gestionar_reservas', 'hotel_ver_mensajes'] as $c) {
            $admin->add_cap($c);
        }
    }
    update_option('h5_roles_v', '5');
});

// Dueños y cuidadores usan la web, no el escritorio de WordPress
add_action('admin_init', function () {
    if (wp_doing_ajax()) return;
    if (!current_user_can('edit_posts')) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
});
add_filter('show_admin_bar', function ($s) { return current_user_can('edit_posts') ? $s : false; });

function h5_rol_label($user) {
    if (!$user || !$user->exists()) return '';
    $roles = (array) $user->roles;
    if (in_array('administrator', $roles, true)) return 'Administrador';
    if (in_array('hotel_cuidador', $roles, true)) return 'Cuidador';
    if (in_array('hotel_dueno', $roles, true)) return 'Dueño';
    return 'Usuario';
}

function h5_user_info() {
    $u = wp_get_current_user();
    if (!$u->exists()) return ['logged' => false, 'name' => '', 'email' => '', 'rol' => '', 'staff' => false];
    return [
        'logged' => true,
        'name'   => $u->display_name,
        'email'  => $u->user_email,
        'rol'    => h5_rol_label($u),
        'staff'  => current_user_can('hotel_ver_reservas'),
    ];
}

/* ───────────── Tipos de contenido ───────────── */

add_action('init', function () {
    $base = ['public' => false, 'show_ui' => true, 'supports' => ['title'], 'has_archive' => false];
    register_post_type('hotel_reserva', $base + ['label' => 'Reservas',  'menu_icon' => 'dashicons-calendar-alt']);
    register_post_type('hotel_resena',  $base + ['label' => 'Reseñas',   'menu_icon' => 'dashicons-star-filled', 'supports' => ['title', 'editor']]);
    register_post_type('hotel_mensaje', $base + ['label' => 'Mensajes',  'menu_icon' => 'dashicons-email-alt',   'supports' => ['title', 'editor']]);
});

/* ───────────── Utilidades ───────────── */

function h5_err($code, $msg, $status = 400) {
    return new WP_Error($code, $msg, ['status' => $status]);
}

function h5_limit($clave, $max = 20, $ventana = 60) {
    $ip = 'x';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    elseif (!empty($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];
    $k = 'h5rl_' . md5($clave . '|' . $ip);
    $n = (int) get_transient($k);
    if ($n >= $max) return h5_err('h5_rate', 'Demasiadas peticiones, espera un minuto.', 429);
    set_transient($k, $n + 1, $ventana);
    return true;
}

function h5_texto($v, $min, $max, $campo) {
    $v = trim(wp_strip_all_tags((string) $v));
    $v = preg_replace('/\s+/u', ' ', $v);
    $len = function_exists('mb_strlen') ? mb_strlen($v) : strlen($v);
    if ($len < $min) return h5_err('h5_val', "El campo «{$campo}» es demasiado corto.");
    if ($len > $max) return h5_err('h5_val', "El campo «{$campo}» es demasiado largo (máx. $max).");
    return $v;
}

function h5_fecha($s) {
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return false;
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $s : false;
}

function h5_hoy() { return gmdate('Y-m-d'); }

function h5_noches($d, $h) { return (int) round((strtotime($h) - strtotime($d)) / DAY_IN_SECONDS); }

function h5_ocupacion($plan, $desde, $hasta) {
    $ids = get_posts([
        'post_type' => 'hotel_reserva', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => 'plan', 'value' => $plan],
            ['key' => 'estado', 'value' => 'cancelada', 'compare' => '!='],
            ['key' => 'desde', 'value' => $hasta, 'compare' => '<', 'type' => 'DATE'],
            ['key' => 'hasta', 'value' => $desde, 'compare' => '>', 'type' => 'DATE'],
        ],
    ]);
    $occ = [];
    foreach ($ids as $id) {
        $d = get_post_meta($id, 'desde', true);
        $h = get_post_meta($id, 'hasta', true);
        for ($t = strtotime($d); $t < strtotime($h); $t += DAY_IN_SECONDS) {
            $k = gmdate('Y-m-d', $t);
            $occ[$k] = ($occ[$k] ?? 0) + 1;
        }
    }
    return $occ;
}

function h5_calcular($plan, $noches, $extras_ids) {
    $p = h5_planes()[$plan];
    $lineas = [['concepto' => 'Habitación ' . $p['nombre'] . ' × ' . $noches . ($noches === 1 ? ' noche' : ' noches'), 'importe' => $p['precio'] * $noches]];
    $total = $p['precio'] * $noches;
    $ex = h5_extras();
    foreach ($extras_ids as $id) {
        if (!isset($ex[$id])) continue;
        $e = $ex[$id];
        $imp = $e['tipo'] === 'dia' ? $e['precio'] * $noches : $e['precio'];
        $lineas[] = ['concepto' => $e['nombre'] . ($e['tipo'] === 'dia' ? ' × ' . $noches : ''), 'importe' => $imp];
        $total += $imp;
    }
    return ['lineas' => $lineas, 'total' => $total];
}

function h5_primer_nombre($n) {
    $p = explode(' ', trim($n));
    return $p[0] !== '' ? $p[0] : 'Alguien';
}

/* ───────────── Rutas ───────────── */

add_action('rest_api_init', function () {
    $ns = 'hotel/v1';
    $pub = '__return_true';
    $logged = function () { return is_user_logged_in(); };
    $staff = function () { return current_user_can('hotel_ver_reservas') || current_user_can('hotel_ver_mensajes'); };
    $gest = function () { return current_user_can('hotel_gestionar_reservas'); };

    register_rest_route($ns, '/planes',    ['methods' => 'GET',  'callback' => function () { return array_values(h5_planes()); }, 'permission_callback' => $pub]);
    register_rest_route($ns, '/servicios', ['methods' => 'GET',  'callback' => function () { return array_values(h5_extras()); }, 'permission_callback' => $pub]);
    register_rest_route($ns, '/estadisticas', ['methods' => 'GET', 'callback' => 'h5_r_stats', 'permission_callback' => $pub]);
    register_rest_route($ns, '/disponibilidad', ['methods' => 'GET', 'callback' => 'h5_r_disponibilidad', 'permission_callback' => $pub]);
    register_rest_route($ns, '/reservas',  ['methods' => 'POST', 'callback' => 'h5_r_reservar',   'permission_callback' => $pub]);
    register_rest_route($ns, '/reservas',  ['methods' => 'GET',  'callback' => 'h5_r_ultimas',    'permission_callback' => $pub]);
    register_rest_route($ns, '/mis-reservas', ['methods' => 'GET', 'callback' => 'h5_r_mias',     'permission_callback' => $logged]);
    register_rest_route($ns, '/reservas/(?P<id>\d+)/estado', ['methods' => 'POST', 'callback' => 'h5_r_estado', 'permission_callback' => $gest]);
    register_rest_route($ns, '/panel',     ['methods' => 'GET',  'callback' => 'h5_r_panel',      'permission_callback' => $staff]);
    register_rest_route($ns, '/resenas',   ['methods' => 'GET',  'callback' => 'h5_r_resenas',    'permission_callback' => $pub]);
    register_rest_route($ns, '/resenas',   ['methods' => 'POST', 'callback' => 'h5_r_resenar',    'permission_callback' => function () { return current_user_can('hotel_resenar'); }]);
    register_rest_route($ns, '/contacto',  ['methods' => 'POST', 'callback' => 'h5_r_contacto',   'permission_callback' => $pub]);
    register_rest_route($ns, '/animal',    ['methods' => 'GET',  'callback' => 'h5_r_animal',     'permission_callback' => $pub]);
    register_rest_route($ns, '/mascotas',  ['methods' => 'GET',  'callback' => 'h5_r_mascotas',   'permission_callback' => $logged]);
    register_rest_route($ns, '/mascotas',  ['methods' => 'POST', 'callback' => 'h5_r_mascota_add', 'permission_callback' => $logged]);
    register_rest_route($ns, '/mascotas/(?P<id>[a-z0-9]+)', ['methods' => 'DELETE', 'callback' => 'h5_r_mascota_del', 'permission_callback' => $logged]);
    register_rest_route($ns, '/perfil',    ['methods' => 'POST', 'callback' => 'h5_r_perfil',     'permission_callback' => $logged]);
    register_rest_route($ns, '/auth/yo',       ['methods' => 'GET',  'callback' => function () { return h5_user_info(); }, 'permission_callback' => $pub]);
    register_rest_route($ns, '/auth/login',    ['methods' => 'POST', 'callback' => 'h5_r_login',    'permission_callback' => $pub]);
    register_rest_route($ns, '/auth/registro', ['methods' => 'POST', 'callback' => 'h5_r_registro', 'permission_callback' => $pub]);
    register_rest_route($ns, '/auth/logout',   ['methods' => 'POST', 'callback' => 'h5_r_logout',   'permission_callback' => $pub]);
});

/* ── Estadísticas para la portada ── */
function h5_r_stats() {
    $c = wp_count_posts('hotel_reserva');
    $rs = h5_r_resenas();
    return ['reservas' => isset($c->publish) ? (int) $c->publish : 0, 'resenas' => $rs['total'], 'media' => $rs['media']];
}

/* ── Disponibilidad ── */
function h5_r_disponibilidad(WP_REST_Request $r) {
    $planes = h5_planes();
    $plan = sanitize_key($r->get_param('plan'));
    if (!isset($planes[$plan])) return h5_err('h5_val', 'Habitación no válida.');
    $desde = h5_fecha($r->get_param('desde')) ?: h5_hoy();
    $dias = max(1, min(180, (int) ($r->get_param('dias') ?: 90)));
    $hasta = gmdate('Y-m-d', strtotime($desde) + $dias * DAY_IN_SECONDS);
    $occ = h5_ocupacion($plan, $desde, $hasta);
    $cap = $planes[$plan]['capacidad'];
    $llenos = [];
    foreach ($occ as $dia => $n) if ($n >= $cap) $llenos[] = $dia;
    sort($llenos);
    return ['plan' => $plan, 'capacidad' => $cap, 'llenos' => $llenos];
}

/* ── Crear reserva ── */
function h5_r_reservar(WP_REST_Request $r) {
    $l = h5_limit('reserva', 10);
    if (is_wp_error($l)) return $l;

    $planes = h5_planes();
    $nombre  = h5_texto($r->get_param('nombre'), 2, 60, 'nombre');   if (is_wp_error($nombre))  return $nombre;
    $mascota = h5_texto($r->get_param('mascota'), 1, 40, 'mascota'); if (is_wp_error($mascota)) return $mascota;
    $email = trim((string) $r->get_param('email'));
    if ($email !== '' && !is_email($email)) return h5_err('h5_val', 'El email no es válido.');
    $especie = sanitize_key($r->get_param('especie'));
    if (!in_array($especie, h5_especies(), true)) return h5_err('h5_val', 'Especie no válida.');
    $plan = sanitize_key($r->get_param('plan'));
    if (!isset($planes[$plan])) return h5_err('h5_val', 'Elige una habitación.');

    $desde = h5_fecha($r->get_param('desde'));
    $hasta = h5_fecha($r->get_param('hasta'));
    if (!$desde || !$hasta) return h5_err('h5_val', 'Elige las fechas en el calendario.');
    if ($desde < h5_hoy()) return h5_err('h5_val', 'La fecha de entrada ya pasó.');
    $noches = h5_noches($desde, $hasta);
    if ($noches < 1)  return h5_err('h5_val', 'La salida debe ser después de la entrada.');
    if ($noches > 30) return h5_err('h5_val', 'Máximo 30 noches por reserva.');

    $extras = $r->get_param('extras');
    $extras = is_array($extras) ? array_values(array_unique(array_map('sanitize_key', $extras))) : [];
    $extras = array_values(array_filter($extras, function ($e) { return isset(h5_extras()[$e]); }));

    $occ = h5_ocupacion($plan, $desde, $hasta);
    foreach ($occ as $dia => $n) {
        if ($dia >= $desde && $dia < $hasta && $n >= $planes[$plan]['capacidad']) {
            return h5_err('h5_lleno', 'Esa habitación está completa el ' . $dia . '. Prueba otras fechas u otra habitación.', 409);
        }
    }

    $calc = h5_calcular($plan, $noches, $extras);
    $id = wp_insert_post([
        'post_type' => 'hotel_reserva', 'post_status' => 'publish',
        'post_title' => $mascota . ' (' . $nombre . ') ' . $desde . ' → ' . $hasta,
    ], true);
    if (is_wp_error($id)) return h5_err('h5_db', 'No se pudo guardar la reserva.', 500);

    $meta = ['nombre' => $nombre, 'email' => $email, 'mascota' => $mascota, 'especie' => $especie, 'plan' => $plan,
             'desde' => $desde, 'hasta' => $hasta, 'noches' => $noches, 'extras' => implode(',', $extras),
             'total' => $calc['total'], 'estado' => 'pendiente', 'user_id' => get_current_user_id()];
    foreach ($meta as $k => $v) update_post_meta($id, $k, $v);

    return ['ok' => true, 'id' => $id, 'noches' => $noches, 'total' => $calc['total'], 'desglose' => $calc['lineas'],
            'mensaje' => '¡Reserva enviada! ' . $mascota . ' tiene plaza del ' . $desde . ' al ' . $hasta . '.'];
}

function h5_reserva_array($id, $completo = false) {
    $planes = h5_planes();
    $ex = h5_extras();
    $plan = get_post_meta($id, 'plan', true);
    $nombres = [];
    foreach (array_filter(explode(',', (string) get_post_meta($id, 'extras', true))) as $e) if (isset($ex[$e])) $nombres[] = $ex[$e]['nombre'];
    $a = [
        'id' => $id,
        'mascota' => get_post_meta($id, 'mascota', true),
        'especie' => get_post_meta($id, 'especie', true),
        'plan' => isset($planes[$plan]) ? $planes[$plan]['nombre'] : $plan,
        'desde' => get_post_meta($id, 'desde', true),
        'hasta' => get_post_meta($id, 'hasta', true),
        'estado' => get_post_meta($id, 'estado', true),
        'total' => (float) get_post_meta($id, 'total', true),
        'extras' => $nombres,
    ];
    if ($completo) {
        $a['dueno'] = get_post_meta($id, 'nombre', true);
        $a['email'] = get_post_meta($id, 'email', true);
        $a['creada'] = get_the_date('Y-m-d H:i', $id);
    } else {
        $a['dueno'] = h5_primer_nombre(get_post_meta($id, 'nombre', true));
    }
    return $a;
}

function h5_r_ultimas() {
    $ids = get_posts(['post_type' => 'hotel_reserva', 'post_status' => 'publish', 'numberposts' => 6, 'fields' => 'ids',
        'orderby' => 'date', 'order' => 'DESC', 'meta_query' => [['key' => 'estado', 'value' => 'cancelada', 'compare' => '!=']]]);
    return array_map('h5_reserva_array', $ids);
}

function h5_r_mias() {
    $ids = get_posts(['post_type' => 'hotel_reserva', 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids',
        'orderby' => 'date', 'order' => 'DESC', 'meta_key' => 'user_id', 'meta_value' => get_current_user_id()]);
    return array_map(function ($id) { return h5_reserva_array($id, true); }, $ids);
}

function h5_r_estado(WP_REST_Request $r) {
    $id = (int) $r['id'];
    $estado = sanitize_key($r->get_param('estado'));
    if (get_post_type($id) !== 'hotel_reserva') return h5_err('h5_nf', 'Reserva no encontrada.', 404);
    if (!in_array($estado, ['pendiente', 'confirmada', 'cancelada'], true)) return h5_err('h5_val', 'Estado no válido.');
    update_post_meta($id, 'estado', $estado);
    return ['ok' => true, 'id' => $id, 'estado' => $estado];
}

function h5_r_panel() {
    $out = ['reservas' => [], 'mensajes' => []];
    if (current_user_can('hotel_ver_reservas')) {
        $ids = get_posts(['post_type' => 'hotel_reserva', 'post_status' => 'publish', 'numberposts' => 60, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC']);
        $out['reservas'] = array_map(function ($id) { return h5_reserva_array($id, true); }, $ids);
    }
    if (current_user_can('hotel_ver_mensajes')) {
        $ids = get_posts(['post_type' => 'hotel_mensaje', 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC']);
        foreach ($ids as $id) {
            $out['mensajes'][] = [
                'id' => $id, 'nombre' => get_post_meta($id, 'nombre', true), 'email' => get_post_meta($id, 'email', true),
                'asunto' => get_the_title($id), 'texto' => get_post_field('post_content', $id), 'fecha' => get_the_date('Y-m-d H:i', $id),
            ];
        }
    }
    return $out;
}

/* ── Reseñas ── */
function h5_r_resenas() {
    $ids = get_posts(['post_type' => 'hotel_resena', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC']);
    $suma = 0; $items = []; $yo = get_current_user_id();
    foreach ($ids as $id) {
        $rating = (int) get_post_meta($id, 'rating', true);
        $suma += $rating;
        if (count($items) < 12) {
            $autor = get_userdata((int) get_post_field('post_author', $id));
            $items[] = [
                'id' => $id, 'autor' => $autor ? h5_primer_nombre($autor->display_name) : 'Cliente',
                'rating' => $rating, 'texto' => get_post_field('post_content', $id),
                'fecha' => get_the_date('Y-m-d', $id), 'mia' => $yo && (int) get_post_field('post_author', $id) === $yo,
            ];
        }
    }
    $total = count($ids);
    return ['total' => $total, 'media' => $total ? round($suma / $total, 1) : 0, 'items' => $items];
}

function h5_r_resenar(WP_REST_Request $r) {
    $l = h5_limit('resena', 6);
    if (is_wp_error($l)) return $l;
    $rating = (int) $r->get_param('rating');
    if ($rating < 1 || $rating > 5) return h5_err('h5_val', 'Elige de 1 a 5 estrellas.');
    $texto = h5_texto($r->get_param('texto'), 5, 400, 'reseña');
    if (is_wp_error($texto)) return $texto;
    $uid = get_current_user_id();
    $u = wp_get_current_user();

    // una reseña por usuario: si ya tenía una, se actualiza
    $previa = get_posts(['post_type' => 'hotel_resena', 'post_status' => 'publish', 'author' => $uid, 'numberposts' => 1, 'fields' => 'ids']);
    $datos = ['post_type' => 'hotel_resena', 'post_status' => 'publish', 'post_author' => $uid,
              'post_title' => 'Reseña de ' . $u->display_name, 'post_content' => $texto];
    if ($previa) { $datos['ID'] = $previa[0]; $id = wp_update_post($datos, true); }
    else { $id = wp_insert_post($datos, true); }
    if (is_wp_error($id)) return h5_err('h5_db', 'No se pudo guardar la reseña.', 500);
    update_post_meta($id, 'rating', $rating);
    return ['ok' => true, 'actualizada' => (bool) $previa];
}

/* ── Contacto ── */
function h5_r_contacto(WP_REST_Request $r) {
    $l = h5_limit('contacto', 3);
    if (is_wp_error($l)) return $l;
    if (trim((string) $r->get_param('web')) !== '') return ['ok' => true]; // campo trampa para bots
    $nombre = h5_texto($r->get_param('nombre'), 2, 60, 'nombre');    if (is_wp_error($nombre)) return $nombre;
    $asunto = h5_texto($r->get_param('asunto'), 3, 80, 'asunto');    if (is_wp_error($asunto)) return $asunto;
    $texto  = h5_texto($r->get_param('mensaje'), 10, 800, 'mensaje'); if (is_wp_error($texto))  return $texto;
    $email = trim((string) $r->get_param('email'));
    if (!is_email($email)) return h5_err('h5_val', 'Escribe un email válido para poder responderte.');
    $id = wp_insert_post(['post_type' => 'hotel_mensaje', 'post_status' => 'publish', 'post_title' => $asunto, 'post_content' => $texto], true);
    if (is_wp_error($id)) return h5_err('h5_db', 'No se pudo enviar el mensaje.', 500);
    update_post_meta($id, 'nombre', $nombre);
    update_post_meta($id, 'email', $email);
    return ['ok' => true, 'mensaje' => '¡Mensaje enviado! Te responderemos pronto.'];
}

/* ── Fotos de animales (proxy servidor → APIs públicas) ── */
function h5_r_animal(WP_REST_Request $r) {
    $l = h5_limit('animal', 120);
    if (is_wp_error($l)) return $l;
    $tipo = sanitize_key($r->get_param('tipo')) ?: 'perro';
    $fuentes = [
        'perro'  => ['https://dog.ceo/api/breeds/image/random', function ($j) { return $j['message'] ?? null; }],
        'gato'   => ['https://api.thecatapi.com/v1/images/search', function ($j) { return $j[0]['url'] ?? null; }],
        'pajaro' => ['https://shibe.online/api/birds?count=1', function ($j) { return $j[0] ?? null; }],
        'conejo' => ['https://api.bunnies.io/v2/loop/random/?media=gif,png', function ($j) { return $j['media']['poster'] ?? ($j['media']['gif'] ?? null); }],
    ];
    if (!isset($fuentes[$tipo])) return h5_err('h5_val', 'Animal no disponible.');
    $resp = wp_remote_get($fuentes[$tipo][0], ['timeout' => 8, 'headers' => ['Accept' => 'application/json']]);
    if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) return h5_err('h5_ext', 'La cámara de este animal no responde ahora mismo.', 502);
    $j = json_decode(wp_remote_retrieve_body($resp), true);
    $url = is_array($j) ? $fuentes[$tipo][1]($j) : null;
    if (is_string($url)) $url = preg_replace('#^http://#', 'https://', $url);
    if (!is_string($url) || !preg_match('#^https://#', $url)) return h5_err('h5_ext', 'Respuesta inesperada del servicio de fotos.', 502);
    return ['tipo' => $tipo, 'url' => esc_url_raw($url)];
}

/* ── Mis mascotas ── */
function h5_mascotas_de($uid) {
    $m = get_user_meta($uid, 'h5_mascotas', true);
    return is_array($m) ? array_values($m) : [];
}

function h5_r_mascotas() {
    return h5_mascotas_de(get_current_user_id());
}

function h5_r_mascota_add(WP_REST_Request $r) {
    $l = h5_limit('mascota', 20);
    if (is_wp_error($l)) return $l;
    $nombre = h5_texto($r->get_param('nombre'), 1, 40, 'nombre');
    if (is_wp_error($nombre)) return $nombre;
    $especie = sanitize_key($r->get_param('especie'));
    if (!in_array($especie, h5_especies(), true)) return h5_err('h5_val', 'Especie no válida.');
    $uid = get_current_user_id();
    $m = h5_mascotas_de($uid);
    if (count($m) >= 10) return h5_err('h5_val', 'Máximo 10 mascotas por cuenta.');
    $m[] = ['id' => substr(md5(uniqid('', true)), 0, 10), 'nombre' => $nombre, 'especie' => $especie];
    update_user_meta($uid, 'h5_mascotas', $m);
    return ['ok' => true, 'mascotas' => $m];
}

function h5_r_mascota_del(WP_REST_Request $r) {
    $id = sanitize_key($r['id']);
    $uid = get_current_user_id();
    $m = array_values(array_filter(h5_mascotas_de($uid), function ($x) use ($id) { return $x['id'] !== $id; }));
    update_user_meta($uid, 'h5_mascotas', $m);
    return ['ok' => true, 'mascotas' => $m];
}

/* ── Perfil ── */
function h5_r_perfil(WP_REST_Request $r) {
    $l = h5_limit('perfil', 8);
    if (is_wp_error($l)) return $l;
    $u = wp_get_current_user();
    $nombre = h5_texto($r->get_param('nombre'), 2, 40, 'nombre');
    if (is_wp_error($nombre)) return $nombre;
    $datos = ['ID' => $u->ID, 'display_name' => $nombre];
    $nueva = (string) $r->get_param('clave_nueva');
    if ($nueva !== '') {
        if (!wp_check_password((string) $r->get_param('clave_actual'), $u->user_pass, $u->ID)) {
            return h5_err('h5_auth', 'La contraseña actual no es correcta.', 403);
        }
        if (strlen($nueva) < 8) return h5_err('h5_val', 'La contraseña nueva debe tener al menos 8 caracteres.');
        $datos['user_pass'] = $nueva;
    }
    $res = wp_update_user($datos);
    if (is_wp_error($res)) return h5_err('h5_db', 'No se pudo guardar el perfil.', 500);
    return ['ok' => true, 'name' => $nombre, 'clave' => $nueva !== ''];
}

/* ── Sesión ── */
function h5_r_login(WP_REST_Request $r) {
    $l = h5_limit('login', 8);
    if (is_wp_error($l)) return $l;
    $usuario = trim((string) $r->get_param('usuario'));
    $clave = (string) $r->get_param('clave');
    if ($usuario === '' || $clave === '') return h5_err('h5_val', 'Escribe usuario y contraseña.');
    if (is_email($usuario)) {
        $u = get_user_by('email', $usuario);
        if ($u) $usuario = $u->user_login;
    }
    $recordar = filter_var($r->get_param('recordar'), FILTER_VALIDATE_BOOLEAN);
    $user = wp_signon(['user_login' => $usuario, 'user_password' => $clave, 'remember' => $recordar], '');
    if (is_wp_error($user)) return h5_err('h5_auth', 'Usuario o contraseña incorrectos.', 401);
    wp_set_current_user($user->ID);
    return ['ok' => true, 'name' => $user->display_name, 'rol' => h5_rol_label($user)];
}

function h5_r_registro(WP_REST_Request $r) {
    $l = h5_limit('registro', 5);
    if (is_wp_error($l)) return $l;
    $usuario = trim((string) $r->get_param('usuario'));
    $email = trim((string) $r->get_param('email'));
    $clave = (string) $r->get_param('clave');
    $nombre = trim(wp_strip_all_tags((string) $r->get_param('nombre')));
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $usuario)) return h5_err('h5_val', 'Usuario: 3-30 caracteres (letras, números, . _ -).');
    if (!is_email($email)) return h5_err('h5_val', 'El email no es válido.');
    if (strlen($clave) < 8) return h5_err('h5_val', 'La contraseña debe tener al menos 8 caracteres.');
    if (username_exists($usuario)) return h5_err('h5_dup', 'Ese usuario ya existe.', 409);
    if (email_exists($email)) return h5_err('h5_dup', 'Ya hay una cuenta con ese email.', 409);
    if ($nombre === '') $nombre = $usuario;
    $nombre = function_exists('mb_substr') ? mb_substr($nombre, 0, 40) : substr($nombre, 0, 40);

    // el rol SIEMPRE lo fija el servidor: quien se registra es "dueño"
    $id = wp_insert_user(['user_login' => $usuario, 'user_email' => $email, 'user_pass' => $clave,
                          'display_name' => $nombre, 'role' => 'hotel_dueno']);
    if (is_wp_error($id)) return h5_err('h5_db', 'No se pudo crear la cuenta.', 500);
    $user = wp_signon(['user_login' => $usuario, 'user_password' => $clave, 'remember' => true], '');
    return ['ok' => true, 'name' => $nombre, 'rol' => 'Dueño', 'login' => !is_wp_error($user)];
}

function h5_r_logout() {
    wp_logout();
    return ['ok' => true];
}