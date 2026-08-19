<?php

namespace App\Exceptions;

use App\Services\Pagos\ResultadoPago;
use RuntimeException;

/**
 * Se lanza cuando la pasarela de pago rechaza la transacción.
 *
 * Al lanzarse dentro de la transacción de base de datos provoca su reversión:
 * si el pago falla no queda ningún pedido a medias ni existencias descontadas.
 */
class PagoRechazadoException extends RuntimeException
{
    public function __construct(public readonly ResultadoPago $resultado)
    {
        parent::__construct($resultado->mensaje !== '' ? $resultado->mensaje : 'El pago fue rechazado.');
    }
}
