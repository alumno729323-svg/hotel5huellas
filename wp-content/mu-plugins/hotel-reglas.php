<?php
/**
 * Plugin Name: Hotel 5 Huellas - Reglas de reserva
 */
function h5_noches_reserva($entrada, $salida) {
    $d = strtotime($entrada . ' UTC');
    $h = strtotime($salida . ' UTC');
    if ($d === false || $h === false || $h <= $d) return 0;
    return (int) round(($h - $d) / 86400);
}
