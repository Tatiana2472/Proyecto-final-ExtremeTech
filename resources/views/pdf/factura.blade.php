<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $factura->numero_factura }}</title>
    @include('pdf.estilos')
</head>
<body>

{{-- ============================== Encabezado ============================== --}}
<table class="encabezado">
    <tr>
        <td style="width: 55%;">
            <div>
                <img src="{{ public_path('img/logo-extreme-tech-real.svg') }}" alt="{{ config('tienda.nombre') }}" style="height:48px;">
            </div>
            <div class="pequeno gris" style="margin-top:6px;">
                {{ config('tienda.direccion') }}<br>
                {{ config('tienda.telefono') }} · {{ config('tienda.correo') }}
            </div>
        </td>
        <td class="derecha">
            <h1>FACTURA ELECTRÓNICA</h1>
            <div class="negrita" style="font-size: 12px;">{{ $factura->numero_factura }}</div>
            <div class="pequeno gris">
                Pedido: {{ $pedido->numero_pedido }}<br>
                Fecha de emisión: {{ $factura->fecha_emision->translatedFormat('d/m/Y h:i a') }}<br>
                Moneda: {{ $factura->moneda }}
            </div>
            <div style="margin-top: 5px;">
                <span class="sello {{ $pedido->estaPagado() ? 'sello-verde' : 'sello-gris' }}">
                    {{ $pedido->estaPagado() ? 'PAGADA' : strtoupper($pedido->estado_pago) }}
                </span>
            </div>
        </td>
    </tr>
</table>

{{-- ====================== Datos del cliente y del envío ==================== --}}
<table>
    <tr>
        <td style="width: 49%; vertical-align: top;">
            <h2>Datos del cliente</h2>
            <div class="caja">
                <table class="datos">
                    <tr><td class="gris" style="width: 38%;">Cliente</td><td class="negrita">{{ $factura->cliente_nombre }}</td></tr>
                    <tr><td class="gris">Identificación</td><td>{{ $factura->cliente_cedula ?: 'No indicada' }}</td></tr>
                    <tr><td class="gris">Correo</td><td>{{ $factura->cliente_correo }}</td></tr>
                    <tr><td class="gris">Código de usuario</td><td>#{{ $factura->user_id }}</td></tr>
                    <tr><td class="gris">Fecha de compra</td><td>{{ $pedido->fecha_compra?->translatedFormat('d/m/Y h:i a') }}</td></tr>
                </table>
            </div>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 49%; vertical-align: top;">
            <h2>Envío</h2>
            <div class="caja">
                <table class="datos">
                    <tr><td class="gris" style="width: 38%;">Recibe</td><td>{{ $pedido->envio_nombre }}</td></tr>
                    <tr><td class="gris">Teléfono</td><td>{{ $pedido->envio_telefono }}</td></tr>
                    <tr><td class="gris">Dirección</td><td>{{ $pedido->envio_direccion }}</td></tr>
                    <tr><td class="gris">Ciudad</td><td>{{ $pedido->envio_ciudad }}, {{ $pedido->envio_provincia }}</td></tr>
                    <tr><td class="gris">N.º seguimiento</td><td class="negrita">{{ $pedido->numero_seguimiento }}</td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- ========================== Detalle de la compra ======================== --}}
<h2>Detalle de la compra</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 8%;">Cant.</th>
            <th>Descripción</th>
            <th style="width: 16%;">SKU</th>
            <th style="width: 15%;" class="derecha">Precio unit.</th>
            <th style="width: 15%;" class="derecha">Importe</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pedido->lineas as $indice => $linea)
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td class="centro">{{ $linea->cantidad }}</td>
                <td>{{ $linea->nombre_producto }}</td>
                <td class="pequeno gris">{{ $linea->sku }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($linea->precio_unitario) }}</td>
                <td class="derecha negrita">{{ \App\Support\Moneda::formato($linea->subtotal) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ================================ Totales ============================== --}}
<table class="totales">
    <tr>
        <td class="gris">Subtotal</td>
        <td class="derecha">{{ \App\Support\Moneda::formato($factura->subtotal) }}</td>
    </tr>
    <tr>
        <td class="gris">
            {{ config('tienda.impuesto.nombre') }}
            ({{ rtrim(rtrim(number_format((float) $pedido->tasa_impuesto * 100, 2, '.', ''), '0'), '.') }}%)
        </td>
        <td class="derecha">{{ \App\Support\Moneda::formato($factura->impuesto) }}</td>
    </tr>
    <tr>
        <td class="gris">Envío</td>
        <td class="derecha">
            {{ (float) $factura->envio === 0.0 ? 'Gratis' : \App\Support\Moneda::formato($factura->envio) }}
        </td>
    </tr>
    <tr class="final">
        <td>TOTAL</td>
        <td class="derecha">{{ \App\Support\Moneda::formato($factura->total) }}</td>
    </tr>
</table>

<div style="clear: both;"></div>

{{-- ========================= Información del pago ========================= --}}
<h2>Forma de pago</h2>

<div class="caja">
    <table class="datos">
        <tr>
            <td class="gris" style="width: 20%;">Método</td>
            <td style="width: 30%;">{{ $pedido->etiquetaMetodoPago() }}</td>
            <td class="gris" style="width: 20%;">Estado</td>
            <td>{{ ucfirst($pedido->estado_pago) }}</td>
        </tr>
        @if ($pedido->pago)
            <tr>
                <td class="gris">N.º de transacción</td>
                <td>{{ $pedido->pago->id_transaccion }}</td>
                <td class="gris">
                    @if ($pedido->pago->tarjeta_ultimos4) Tarjeta
                    @elseif ($pedido->pago->correo_pagador) Cuenta
                    @endif
                </td>
                <td>
                    @if ($pedido->pago->tarjeta_ultimos4)
                        {{ $pedido->pago->tarjeta_marca }} terminada en {{ $pedido->pago->tarjeta_ultimos4 }}
                    @elseif ($pedido->pago->correo_pagador)
                        {{ $pedido->pago->correo_pagador }}
                    @endif
                </td>
            </tr>
        @endif
    </table>
</div>

@if ($pedido->notas)
    <h2>Notas del cliente</h2>
    <div class="caja">{{ $pedido->notas }}</div>
@endif

<div class="pie">
    {{ config('tienda.nombre') }} · Documento generado electrónicamente el
    {{ now()->translatedFormat('d/m/Y \a \l\a\s h:i a') }} ·
    Por seguridad, este comprobante no incluye el número completo de la tarjeta.
</div>

</body>
</html>
