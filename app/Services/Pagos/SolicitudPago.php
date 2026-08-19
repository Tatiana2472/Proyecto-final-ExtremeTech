<?php

namespace App\Services\Pagos;

/**
 * Datos que el checkout entrega a la pasarela de pago.
 *
 * Es un objeto de solo lectura: una vez creado no se puede modificar, de modo
 * que el monto que valida la pasarela es exactamente el que calculó el
 * servidor (nunca uno enviado por el navegador).
 */
class SolicitudPago
{
    /**
     * @param  array<string, mixed>  $datos  Datos propios del método de pago
     *                                       (número de tarjeta, correo PayPal,
     *                                       referencia SINPE, etc.).
     */
    public function __construct(
        public readonly float $monto,
        public readonly string $moneda,
        public readonly string $referencia,
        public readonly string $descripcion,
        public readonly array $datos = [],
    ) {
    }

    public function dato(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->datos[$clave] ?? $porDefecto;
    }
}
