<?php
/**
 * Plugin Name: Hotel 5 Huellas - Reglas de reserva
 */
function h5_noches_reserva($entrada, $salida) {
    try {
        $utc = new DateTimeZone('UTC');
        $d = new DateTimeImmutable((string) $entrada, $utc);
        $h = new DateTimeImmutable((string) $salida, $utc);
    } catch (Exception $e) {
        return 0;
    }
    return $h > $d ? (int) $d->diff($h)->days : 0;
}

if (!defined('H5_MAX_NOCHES')) define('H5_MAX_NOCHES', 30);

/**
 * Valida una reserva. Devuelve la lista de errores (vacía = reserva correcta).
 * $d: nombre, mascota, desde, hasta. $hoy: AAAA-MM-DD.
 */
function h5_validar_reserva(array $d, $hoy) {
    $errores = [];
    $nombre  = trim((string) ($d['nombre'] ?? ''));
    $mascota = trim((string) ($d['mascota'] ?? ''));
    if (mb_strlen($nombre) < 2) $errores[] = 'El nombre debe tener al menos 2 caracteres.';
    if ($mascota === '')        $errores[] = 'Indica el nombre de la mascota.';

    $desde = (string) ($d['desde'] ?? '');
    $hasta = (string) ($d['hasta'] ?? '');
    if ($desde < $hoy) $errores[] = 'La fecha de entrada ya pasó.';

    $noches = h5_noches_reserva($desde, $hasta);
    if ($noches < 1) {
        $errores[] = 'La salida debe ser después de la entrada.';
    } elseif ($noches > H5_MAX_NOCHES) {
        $errores[] = 'Máximo ' . H5_MAX_NOCHES . ' noches por reserva.';
    }
    return $errores;
}
