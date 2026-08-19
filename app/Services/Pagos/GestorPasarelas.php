<?php

namespace App\Services\Pagos;

use InvalidArgumentException;

/**
 * Selecciona la pasarela de pago según el método elegido en el checkout.
 *
 * Es una aplicación del patrón Factory: agregar una pasarela nueva (por
 * ejemplo Tilopay) solo requiere crear la clase, registrarla acá y habilitarla
 * en config/tienda.php.
 */
class GestorPasarelas
{
    /** @var array<string, PasarelaPago> */
    private array $pasarelas = [];

    public function __construct(
        PasarelaTarjeta $tarjeta,
        PasarelaPayPal $paypal,
        PasarelaSinpe $sinpe,
    ) {
        foreach ([$tarjeta, $paypal, $sinpe] as $pasarela) {
            $this->pasarelas[$pasarela->identificador()] = $pasarela;
        }
    }

    public function obtener(string $metodo): PasarelaPago
    {
        if (! isset($this->pasarelas[$metodo])) {
            throw new InvalidArgumentException("El método de pago «{$metodo}» no está disponible.");
        }

        if (! $this->estaHabilitado($metodo)) {
            throw new InvalidArgumentException("El método de pago «{$metodo}» está deshabilitado.");
        }

        return $this->pasarelas[$metodo];
    }

    public function estaHabilitado(string $metodo): bool
    {
        return (bool) config("tienda.pagos.metodos.{$metodo}.habilitado", false);
    }

    /** @return list<string> Métodos habilitados, para validar el formulario. */
    public function metodosDisponibles(): array
    {
        return array_values(array_filter(
            array_keys($this->pasarelas),
            fn (string $metodo) => $this->estaHabilitado($metodo)
        ));
    }
}
