<?php

namespace App\Services\Pagos;

use Illuminate\Support\Str;

/**
 * Pasarela PayPal (modo sandbox simulado).
 *
 * El total de la tienda está en colones, así que se convierte a la moneda
 * configurada para PayPal (por omisión USD) usando el tipo de cambio del
 * archivo .env, tal como haría la integración real.
 *
 * En producción esta clase haría dos llamadas a la API REST de PayPal:
 * una para crear la orden y otra para capturarla, usando el Client ID y el
 * Secret obtenidos en developer.paypal.com. Las credenciales se leen de la
 * configuración y nunca se escriben dentro del código.
 */
class PasarelaPayPal implements PasarelaPago
{
    public function identificador(): string
    {
        return 'paypal';
    }

    public function nombre(): string
    {
        return 'PayPal';
    }

    public function procesar(SolicitudPago $solicitud): ResultadoPago
    {
        $correo = trim((string) $solicitud->dato('correo_paypal'));

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ResultadoPago::rechazado(
                $this->identificador(),
                $solicitud->monto,
                $solicitud->moneda,
                'La cuenta de PayPal indicada no es válida.'
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

        // Cuenta de prueba que siempre falla, para demostrar el rechazo.
        if (str_starts_with($correo, 'rechazado@')) {
            return ResultadoPago::rechazado(
                $this->identificador(),
                $solicitud->monto,
                $solicitud->moneda,
                'PayPal no pudo completar el pago con esa cuenta.'
            );
        }

        $monedaPayPal = (string) config('tienda.pagos.paypal.moneda', 'USD');
        $montoPayPal  = $this->convertir($solicitud->monto, $solicitud->moneda, $monedaPayPal);

        return ResultadoPago::aprobado(
            metodo: $this->identificador(),
            monto: $montoPayPal,
            moneda: $monedaPayPal,
            idTransaccion: 'PAYID-'.strtoupper(Str::random(14)),
            mensaje: sprintf('Pago completado en PayPal por %s %s.', number_format($montoPayPal, 2), $monedaPayPal),
            correoPagador: $correo,
        );
    }

    /**
     * Convierte el monto de la moneda de la tienda a la moneda de PayPal.
     */
    public function convertir(float $monto, string $desde, string $hacia): float
    {
        if (strtoupper($desde) === strtoupper($hacia)) {
            return round($monto, 2);
        }

        $tipoCambio = (float) config('tienda.pagos.paypal.tipo_cambio', 1);

        if ($tipoCambio <= 0) {
            $tipoCambio = 1;
        }

        return round($monto / $tipoCambio, 2);
    }
}
