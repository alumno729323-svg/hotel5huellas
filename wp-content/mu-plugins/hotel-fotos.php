<?php
/**
 * Plugin Name: Hotel 5 Huellas - Fotos de pájaros y conejos
 * Description: Las fotos de pájaros y conejos salen de Wikipedia (estable y sin clave).
 *              Si Wikipedia no responde, se usa la fuente original. Perros y gatos no cambian.
 */
if (!defined('ABSPATH')) exit;

function h5_wiki_temas() {
    return [
        'pajaro' => ['Budgerigar', 'Cockatiel', 'Parrot', 'Canary', 'Barn owl', 'Penguin', 'Flamingo', 'Toucan',
                     'Hummingbird', 'Peafowl', 'Bald eagle', 'Mute swan', 'Scarlet macaw', 'European robin',
                     'Eurasian blue tit', 'European goldfinch', 'Zebra finch', 'Atlantic puffin'],
        'conejo' => ['Rabbit', 'Holland Lop', 'Netherland Dwarf rabbit', 'Lionhead rabbit', 'Mini Lop',
                     'Angora rabbit', 'Dutch rabbit', 'Rex rabbit', 'Lop rabbit', 'European rabbit'],
    ];
}

/** Devuelve la URL (https) de una foto de uno de los artículos, o null si ninguno responde. */
function h5_foto_wiki(array $titulos) {
    shuffle($titulos);
    foreach (array_slice($titulos, 0, 4) as $t) {
        $resp = wp_remote_get(
            'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $t)),
            ['timeout' => 8, 'user-agent' => 'Hotel5Huellas/1.0 (proyecto de clase)', 'headers' => ['Accept' => 'application/json']]
        );
        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) continue;
        $j = json_decode(wp_remote_retrieve_body($resp), true);
        $url = is_array($j) ? ($j['thumbnail']['source'] ?? null) : null;
        if (!is_string($url)) continue;
        $url = preg_replace('#^http://#', 'https://', $url);
        // solo imágenes normales (nada de SVG) alojadas en Wikimedia
        if (!preg_match('#^https://upload\.wikimedia\.org/.+\.(jpe?g|png|webp)(\?.*)?$#i', $url)) continue;
        return $url;
    }
    return null;
}

add_filter('rest_pre_dispatch', function ($resultado, $server, $request) {
    if ($resultado !== null || $request->get_route() !== '/hotel/v1/animal') return $resultado;

    $tipo  = sanitize_key($request->get_param('tipo'));
    $temas = h5_wiki_temas();
    if (!isset($temas[$tipo])) return $resultado;          // perro y gato: lo gestiona hotel-api.php como siempre

    if (function_exists('h5_limit')) {
        $l = h5_limit('animal', 120);
        if (is_wp_error($l)) {
            return new WP_REST_Response(['code' => $l->get_error_code(), 'message' => $l->get_error_message(), 'data' => ['status' => 429]], 429);
        }
    }
    $url = h5_foto_wiki($temas[$tipo]);
    if (!$url) return $resultado;                          // Wikipedia no responde: se usa la fuente de siempre (hotel-api.php)
    return new WP_REST_Response(['tipo' => $tipo, 'url' => esc_url_raw($url)], 200);
}, 10, 3);
