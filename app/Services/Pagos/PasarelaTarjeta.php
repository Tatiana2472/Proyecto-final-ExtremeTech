<?php

namespace App\Services\Pagos;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Pasarela de tarjeta de crédito / débito.
 *
 * Trabaja en modo sandbox: valida la tarjeta localmente (algoritmo de Luhn,
 * fecha de vencimiento, longitud del CVV) y devuelve un identificador de
 * transacción simulado. No se envía ningún dato a un banco real y, muy
 * importante, ni el número completo ni el CVV se guardan en la base de datos:
 * solo se conservan la marca y los últimos 4 dígitos.
 *
 * Para producción se sustituye el cuerpo de procesar() por la llamada HTTP a
 * la pasarela contratada (Tilopay, BAC Credomatic, Onvo Pay), manteniendo la
 * misma interfaz para no tocar el resto de la aplicación.
 */
class PasarelaTarjeta implements PasarelaPago
{
    /**
     * Tarjetas de prueba que la pasarela rechaza a propósito, para poder
     * demostrar el manejo de errores en el checkout.
     */
    private const TARJETAS_RECHAZADAS = [
        '4000000000000002' => 'Tarjeta declinada por el banco emisor.',
        '4000000000009995' => 'Fondos insuficientes.',
    ];

    public function identificador(): string
    {
        return 'tarjeta';
    }

    public function nombre(): string
    {
        return 'Tarjeta de crédito / débito';
    }

    public function procesar(SolicitudPago $solicitud): ResultadoPago
    {
        $numero = preg_replace('/\D/', '', (string) $solicitud->dato('numero'));
        $mes    = (int) $solicitud->dato('mes');
        $anio   = (int) $solicitud->dato('anio');
        $cvv    = (string) $solicitud->dato('cvv');

        $rechazar = fn (string $mensaje) => ResultadoPago::rechazado(
            $this->identificador(), $solicitud->monto, $solicitud->moneda, $mensaje
        );

        if (! $this->numeroEsValido($numero)) {
            return $rechazar('El número de tarjeta no es válido.');
        }

        if (! $this->fechaEsValida($mes, $anio)) {
            return $rechazar('La tarjeta está vencida o la fecha es incorrecta.');
        }

        if (! preg_match('/^\d{3,4}$/', $cvv)) {
            return $rechazar('El código de seguridad (CVV) no es válido.');
        }

        if (isset(self::TARJETAS_RECHAZADAS[$numero])) {
            return $rechazar(self::TARJETAS_RECHAZADAS[$numero]);
        }

        if ($solicitud->monto <= 0) {
            return $rechazar('El monto a cobrar debe ser mayor que cero.');
        }

        return ResultadoPago::aprobado(
            metodo: $this->identificador(),
            monto: $solicitud->monto,
            moneda: $solicitud->moneda,
            idTransaccion: 'TRX-'.strtoupper(Str::random(12)),
            mensaje: 'Pago autorizado.',
            tarjetaMarca: $this->detectarMarca($numero),
            tarjetaUltimos4: substr($numero, -4),
        );
    }

    /**
     * Algoritmo de Luhn: valida el dígito verificador de la tarjeta.
     * Es la misma comprobación que hacen las pasarelas antes de enviar la
     * transacción a la red bancaria, y evita cobros con números inventados.
     */
    public function numeroEsValido(string $numero): bool
    {
        if (! preg_match('/^\d{13,19}$/', $numero)) {
            return false;
        }

        $suma = 0;
        $duplicar = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];

            if ($duplicar) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $suma += $digito;
            $duplicar = ! $duplicar;
        }

        return $suma % 10 === 0;
    }

    private function fechaEsValida(int $mes, int $anio): bool
    {
        if ($mes < 1 || $mes > 12) {
            return false;
        }

        // Se aceptan años de 2 dígitos (26) o de 4 dígitos (2026).
        if ($anio < 100) {
            $anio += 2000;
        }

        $vencimiento = Carbon::create($anio, $mes)->endOfMonth();

        return $vencimiento->greaterThanOrEqualTo(now()->startOfDay());
    }

    public function detectarMarca(string $numero): string
    {
        return match (true) {
            str_starts_with($numero, '4')                                   => 'Visa',
            (bool) preg_match('/^5[1-5]/', $numero)                         => 'MasterCard',
            (bool) preg_match('/^2(2[2-9]|[3-6]|7[01]|720)/', $numero)      => 'MasterCard',
            (bool) preg_match('/^3[47]/', $numero)                          => 'American Express',
            (bool) preg_match('/^6(011|5)/', $numero)                       => 'Discover',
            default                                                          => 'Desconocida',
        };
    }
}
