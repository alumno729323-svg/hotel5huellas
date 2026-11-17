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
