<?php

namespace App\Services;

/**
 * Resultado del cálculo automático del total de la compra.
 *
 * Se modela como un objeto inmutable para que el subtotal, el impuesto, el
 * envío y el total viajen siempre juntos y coherentes entre el carrito, el
 * checkout y la factura.
 */
class TotalesCarrito
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $impuesto,
        public readonly float $envio,
        public readonly float $total,
        public readonly float $tasaImpuesto,
        public readonly int $cantidadArticulos,
        public readonly bool $envioGratis,
        public readonly float $faltaParaEnvioGratis,
    ) {
    }

    /**
     * Calcula los totales a partir del subtotal de las líneas del carrito.
     *
     * @param  float  $subtotal   Suma de (precio unitario x cantidad).
     * @param  int    $cantidad   Unidades totales en el carrito.
     */
    public static function calcular(float $subtotal, int $cantidad): self
    {
        $subtotal = round(max($subtotal, 0), 2);

        $tasa         = (float) config('tienda.impuesto.tasa', 0.13);
        $costoEnvio   = (float) config('tienda.envio.costo', 0);
        $gratisDesde  = (float) config('tienda.envio.gratis_desde', 0);

        $impuesto = round($subtotal * $tasa, 2);

        // El carrito vacío no cobra envío. Si el subtotal alcanza el monto
        // mínimo, el envío es gratuito.
        $envioGratis = $gratisDesde > 0 && $subtotal >= $gratisDesde;
        $envio = ($subtotal <= 0 || $envioGratis) ? 0.0 : round($costoEnvio, 2);

        $falta = ($gratisDesde > 0 && ! $envioGratis && $subtotal > 0)
            ? round($gratisDesde - $subtotal, 2)
            : 0.0;

        return new self(
            subtotal: $subtotal,
            impuesto: $impuesto,
            envio: $envio,
            total: round($subtotal + $impuesto + $envio, 2),
            tasaImpuesto: $tasa,
            cantidadArticulos: max($cantidad, 0),
            envioGratis: $envioGratis && $subtotal > 0,
            faltaParaEnvioGratis: $falta,
        );
    }

    public function estaVacio(): bool
    {
        return $this->cantidadArticulos === 0;
    }

    /** Porcentaje del impuesto listo para mostrar, por ejemplo "13". */
    public function porcentajeImpuesto(): string
    {
        return rtrim(rtrim(number_format($this->tasaImpuesto * 100, 2, '.', ''), '0'), '.');
    }

    public function aArreglo(): array
    {
        return [
            'subtotal'            => $this->subtotal,
            'impuesto'            => $this->impuesto,
            'envio'               => $this->envio,
            'total'               => $this->total,
            'tasa_impuesto'       => $this->tasaImpuesto,
            'cantidad_articulos'  => $this->cantidadArticulos,
            'envio_gratis'        => $this->envioGratis,
        ];
    }
}
