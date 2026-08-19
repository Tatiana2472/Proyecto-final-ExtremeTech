<?php

namespace App\Services\Pagos;

/**
 * Contrato que debe cumplir toda pasarela de pago.
 *
 * Gracias a esta interfaz el CheckoutController no sabe (ni le importa) si el
 * cobro se hace con tarjeta, PayPal o SINPE Móvil: solo llama a procesar().
 * Esto es el principio de inversión de dependencias aplicado a POO en PHP.
 */
interface PasarelaPago
{
    /** Identificador corto del método: tarjeta, paypal, sinpe. */
    public function identificador(): string;

    /** Nombre visible para el usuario. */
    public function nombre(): string;

    /** Ejecuta el cobro y devuelve el resultado. */
    public function procesar(SolicitudPago $solicitud): ResultadoPago;
}
