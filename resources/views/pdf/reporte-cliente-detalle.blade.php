<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte del cliente {{ $cliente->name }}</title>
    @include('pdf.estilos')
</head>
<body>

<table class="encabezado">
    <tr>
        <td style="width: 58%;">
            <div>
                <img src="{{ public_path('img/logo-extreme-tech-real.svg') }}" alt="{{ config('tienda.nombre') }}" style="height:48px;">
            </div>
            <div class="pequeno gris" style="margin-top:6px;">
                {{ config('tienda.direccion') }}<br>
                {{ config('tienda.telefono') }} · {{ config('tienda.correo') }}
            </div>
        </td>
        <td class="derecha">
            <h1>REPORTE INDIVIDUAL DE CLIENTE</h1>
            <div class="pequeno gris">
                Período: {{ $desde->translatedFormat('d/m/Y') }} al {{ $hasta->translatedFormat('d/m/Y') }}<br>
                Generado el {{ now()->translatedFormat('d/m/Y h:i a') }} por {{ $generadoPor }}
            </div>
        </td>
    </tr>
</table>

{{-- ========================== Datos del cliente =========================== --}}
<h2>Datos del cliente</h2>

<div class="caja">
    <table class="datos">
        <tr>
            <td class="gris" style="width: 15%;">Nombre</td>
            <td class="negrita" style="width: 35%;">{{ $cliente->name }}</td>
            <td class="gris" style="width: 15%;">Código</td>
            <td>#{{ $cliente->id }}</td>
        </tr>
        <tr>
            <td class="gris">Correo</td>
            <td>{{ $cliente->email }}</td>
            <td class="gris">Cédula</td>
            <td>{{ $cliente->cedula ?: 'No indicada' }}</td>
        </tr>
        <tr>
            <td class="gris">Teléfono</td>
            <td>{{ $cliente->telefono ?: 'No indicado' }}</td>
            <td class="gris">Ubicación</td>
            <td>{{ trim(($cliente->ciudad ?: '—').', '.($cliente->provincia ?: '—'), ', ') }}</td>
        </tr>
    </table>
</div>

{{-- ============================= Indicadores ============================= --}}
@php
    $totalPeriodo = (float) $pedidos->sum('total');
    $unidades = $pedidos->sum(fn ($pedido) => $pedido->lineas->sum('cantidad'));
@endphp

<table class="indicadores">
    <tr>
        <td>
            <div class="valor">{{ $pedidos->count() }}</div>
            <div class="etiqueta">Pedidos en el período</div>
        </td>
        <td>
            <div class="valor">{{ $unidades }}</div>
            <div class="etiqueta">Unidades compradas</div>
        </td>
        <td>
            <div class="valor">{{ \App\Support\Moneda::formato($totalPeriodo) }}</div>
            <div class="etiqueta">Total comprado</div>
        </td>
        <td>
            <div class="valor">
                {{ \App\Support\Moneda::formato($pedidos->count() > 0 ? $totalPeriodo / $pedidos->count() : 0) }}
            </div>
            <div class="etiqueta">Ticket promedio</div>
        </td>
    </tr>
</table>

{{-- ============================ Lista de pedidos ========================== --}}
<h2>Pedidos del período</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 20%;">N.º de pedido</th>
            <th style="width: 15%;">Fecha</th>
            <th style="width: 18%;">Método de pago</th>
            <th style="width: 20%;">Seguimiento</th>
            <th style="width: 12%;" class="centro">Artículos</th>
            <th class="derecha">Monto</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pedidos as $indice => $pedido)
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td class="negrita">{{ $pedido->numero_pedido }}</td>
                <td class="pequeno">{{ $pedido->fecha_compra?->format('d/m/Y') }}</td>
                <td class="pequeno">{{ $pedido->etiquetaMetodoPago() }}</td>
                <td class="pequeno gris">{{ $pedido->numero_seguimiento }}</td>
                <td class="centro">{{ $pedido->lineas->sum('cantidad') }}</td>
                <td class="derecha negrita">{{ \App\Support\Moneda::formato($pedido->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="centro gris">El cliente no tiene compras en este período.</td></tr>
        @endforelse
    </tbody>
    @if ($pedidos->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">TOTAL</td>
                <td class="centro">{{ $unidades }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($totalPeriodo) }}</td>
            </tr>
        </tfoot>
    @endif
</table>

{{-- ========================= Detalle de cada pedido ======================= --}}
@if ($pedidos->isNotEmpty())
    <h2>Detalle de los productos comprados</h2>

    @foreach ($pedidos as $pedido)
        <div class="caja" style="margin-bottom: 4px;">
            <span class="negrita">{{ $pedido->numero_pedido }}</span>
            <span class="gris pequeno"> · {{ $pedido->fecha_compra?->format('d/m/Y') }}</span>
        </div>

        <table class="listado" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 8%;" class="centro">Cant.</th>
                    <th>Producto</th>
                    <th style="width: 18%;">SKU</th>
                    <th style="width: 16%;" class="derecha">Precio unit.</th>
                    <th style="width: 16%;" class="derecha">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pedido->lineas as $indice => $linea)
                    <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                        <td class="centro">{{ $linea->cantidad }}</td>
                        <td>{{ $linea->nombre_producto }}</td>
                        <td class="pequeno gris">{{ $linea->sku }}</td>
                        <td class="derecha">{{ \App\Support\Moneda::formato($linea->precio_unitario) }}</td>
                        <td class="derecha">{{ \App\Support\Moneda::formato($linea->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

<div class="pie">
    {{ config('tienda.nombre') }} · Reporte individual de cliente · Solo incluye pedidos con pago aprobado.
</div>

</body>
</html>
