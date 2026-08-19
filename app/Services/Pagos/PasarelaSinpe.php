<?php

namespace App\Services\Pagos;

use Illuminate\Support\Str;

/**
 * SINPE Móvil (transferencia desde el teléfono).
 *
 * El cliente hace la transferencia al número de la tienda y digita el número
 * de comprobante que le envía el banco por SMS. La tienda registra el pago
 * como aprobado y luego lo concilia contra el estado de cuenta.
 */
class PasarelaSinpe implements PasarelaPago
{
    public function identificador(): string
    {
        return 'sinpe';
    }

    public function nombre(): string
    {
        return 'SINPE Móvil';
    }

    public function procesar(SolicitudPago $solicitud): ResultadoPago
    {
        $comprobante = preg_replace('/\D/', '', (string) $solicitud->dato('comprobante_sinpe'));

        if (strlen((string) $comprobante) < 6) {
            return ResultadoPago::rechazado(
                $this->identificador(),
                $solicitud->monto,
                $solicitud->moneda,
                'El número de comprobante de SINPE Móvil debe tener al menos 6 dígitos.'
            );
        }

        if ($solicitud->monto <= 0) {
            return ResultadoPago::rechazado(
                $this->identificador(),
                $solicitud->monto,
                $solicitud->moneda,
                'El monto a cobrar debe ser mayor que cero.'
            );
        }

        return ResultadoPago::aprobado(
            metodo: $this->identificador(),
            monto: $solicitud->monto,
            moneda: $solicitud->moneda,
            idTransaccion: 'SINPE-'.$comprobante.'-'.strtoupper(Str::random(4)),
            mensaje: 'Transferencia SINPE Móvil registrada.',
        );
    }
}
