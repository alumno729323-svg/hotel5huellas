<?php
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;
use PHPUnit\Framework\Assert;

require_once __DIR__ . '/../../wp-content/mu-plugins/hotel-reglas.php';

/** Los pasos llaman al código REAL de la aplicación (h5_validar_reserva). */
final class ReservaContext implements Context
{
    private string $hoy = '';
    private array $errores = [];
    private int $noches = 0;

    #[Given('que hoy es :fecha')]
    public function queHoyEs(string $fecha): void
    {
        $this->hoy = $fecha;
    }

    #[When('reservo para :nombre a :mascota del :desde al :hasta')]
    public function reservoPara(string $nombre, string $mascota, string $desde, string $hasta): void
    {
        $this->errores = h5_validar_reserva(
            ['nombre' => $nombre, 'mascota' => $mascota, 'desde' => $desde, 'hasta' => $hasta],
            $this->hoy
        );
        $this->noches = h5_noches_reserva($desde, $hasta);
    }

    #[Then('la reserva es aceptada')]
    public function laReservaEsAceptada(): void
    {
        Assert::assertSame([], $this->errores, 'Se esperaba una reserva válida: ' . implode(' | ', $this->errores));
    }

    #[Then('la reserva es rechazada con el mensaje :mensaje')]
    public function laReservaEsRechazada(string $mensaje): void
    {
        Assert::assertContains($mensaje, $this->errores);
    }

    #[Then('la estancia es de :n noches')]
    public function laEstanciaEsDe(int $n): void
    {
        Assert::assertSame($n, $this->noches);
    }
}
