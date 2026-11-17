<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/mu-plugins/hotel-reglas.php';

final class NochesReservaTest extends TestCase
{
    public function test_cuenta_las_noches_entre_dos_fechas(): void
    {
        $this->assertSame(4, h5_noches_reserva('2026-12-01', '2026-12-04'));
    }

    public function test_misma_fecha_son_cero_noches(): void
    {
        $this->assertSame(0, h5_noches_reserva('2026-12-01', '2026-12-01'));
    }

    public function test_salida_anterior_a_la_entrada_son_cero_noches(): void
    {
        $this->assertSame(0, h5_noches_reserva('2026-12-05', '2026-12-01'));
    }

    public function test_cruza_fin_de_mes_y_de_anio(): void
    {
        $this->assertSame(3, h5_noches_reserva('2026-12-30', '2027-01-02'));
    }

    public function test_fecha_invalida_son_cero_noches(): void
    {
        $this->assertSame(0, h5_noches_reserva('esto-no-es-fecha', '2026-12-04'));
    }
}
