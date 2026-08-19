<?php

namespace App\Services\Pagos;

/**
 * Respuesta de la pasarela de pago.
 */
class ResultadoPago
{
    public function __construct(
        public readonly bool $aprobado,
        public readonly string $metodo,
        public readonly float $monto,
        public readonly string $moneda,
        public readonly ?string $idTransaccion = null,
        public readonly string $mensaje = '',
        public readonly ?string $tarjetaMarca = null,
        public readonly ?string $tarjetaUltimos4 = null,
        public readonly ?string $correoPagador = null,
    ) {
    }

    public static function aprobado(
        string $metodo,
        float $monto,
        string $moneda,
        string $idTransaccion,
        string $mensaje = 'Transacción aprobada',
        ?string $tarjetaMarca = null,
        ?string $tarjetaUltimos4 = null,
        ?string $correoPagador = null,
    ): self {
        return new self(
            aprobado: true,
            metodo: $metodo,
            monto: $monto,
            moneda: $moneda,
            idTransaccion: $idTransaccion,
            mensaje: $mensaje,
            tarjetaMarca: $tarjetaMarca,
            tarjetaUltimos4: $tarjetaUltimos4,
            correoPagador: $correoPagador,
        );
    }

    public static function rechazado(string $metodo, float $monto, string $moneda, string $mensaje): self
    {
        return new self(
            aprobado: false,
            metodo: $metodo,
            monto: $monto,
            moneda: $moneda,
            mensaje: $mensaje,
        );
    }

    /** Atributos listos para guardar en la tabla payments. */
    public function paraGuardar(): array
    {
        return [
            'metodo'           => $this->metodo,
            'estado'           => $this->aprobado ? 'aprobado' : 'rechazado',
            'monto'            => $this->monto,
            'moneda'           => $this->moneda,
            'id_transaccion'   => $this->idTransaccion,
            'tarjeta_marca'    => $this->tarjetaMarca,
            'tarjeta_ultimos4' => $this->tarjetaUltimos4,
            'correo_pagador'   => $this->correoPagador,
            'mensaje'          => $this->mensaje,
            'procesado_en'     => now(),
        ];
    }
}
