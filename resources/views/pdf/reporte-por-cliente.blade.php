<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas por cliente</title>
    @include('pdf.estilos')
</head>
<body>

<table class="encabezado">
    <tr>
        <td style="width: 60%;">
            <div class="marca">Extrem<span>Tech</span></div>
            <div class="lema-marca">LO MEJOR EN TECNOLOGÍA</div>
            <div class="pequeno gris">
                {{ config('tienda.direccion') }} · Cédula jurídica {{ config('tienda.cedula_juridica') }}
            </div>
        </td>
        <td class="derecha">
            <h1>REPORTE DE VENTAS POR CLIENTE</h1>
            <div class="negrita" style="font-size: 11px;">
                {{ $desde->translatedFormat('d/m/Y') }} al {{ $hasta->translatedFormat('d/m/Y') }}
            </div>
            <div class="pequeno gris">
                Generado el {{ now()->translatedFormat('d/m/Y h:i a') }}<br>
                Por: {{ $generadoPor }}
            </div>
        </td>
    </tr>
</table>

{{-- ============================== Indicadores ============================== --}}
<table class="indicadores">
    <tr>
        <td>
            <div class="valor">{{ $clientes->count() }}</div>
            <div class="etiqueta">Clientes con compras</div>
        </td>
        <td>
            <div class="valor">{{ $resumen['pedidos'] }}</div>
            <div class="etiqueta">Pedidos pagados</div>
        </td>
        <td>
            <div class="valor">{{ \App\Support\Moneda::formato($resumen['ventas']) }}</div>
            <div class="etiqueta">Total vendido</div>
        </td>
        <td>
            <div class="valor">{{ \App\Support\Moneda::formato($resumen['ticket_promedio']) }}</div>
            <div class="etiqueta">Ticket promedio</div>
        </td>
    </tr>
</table>

{{-- ============================ Detalle por cliente ======================= --}}
<h2>Compras por cliente (ordenado de mayor a menor)</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 25%;">Cliente</th>
            <th style="width: 24%;">Correo electrónico</th>
            <th style="width: 9%;" class="centro">Pedidos</th>
            <th style="width: 13%;" class="derecha">Promedio</th>
            <th style="width: 13%;" class="derecha">Total</th>
            <th style="width: 11%;" class="centro">Últ. compra</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($clientes as $indice => $cliente)
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td>{{ $indice + 1 }}</td>
                <td class="negrita">{{ $cliente->cliente }}</td>
                <td class="pequeno">{{ $cliente->correo }}</td>
                <td class="centro">{{ $cliente->pedidos }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($cliente->promedio) }}</td>
                <td class="derecha negrita">{{ \App\Support\Moneda::formato($cliente->total) }}</td>
                <td class="centro pequeno">
                    {{ \Illuminate\Support\Carbon::parse($cliente->ultima_compra)->format('d/m/Y') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="centro gris">No hay ventas registradas en el período seleccionado.</td>
            </tr>
        @endforelse
    </tbody>
    @if ($clientes->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">TOTALES</td>
                <td class="centro">{{ $clientes->sum('pedidos') }}</td>
                <td></td>
                <td class="derecha">{{ \App\Support\Moneda::formato($clientes->sum('total')) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

{{-- ====================== Participación de cada cliente =================== --}}
@if ($clientes->isNotEmpty())
    @php $mayor = max((float) $clientes->max('total'), 1); @endphp

    <h2>Participación en las ventas</h2>

    <table class="listado">
        <thead>
            <tr>
                <th style="width: 28%;">Cliente</th>
                <th>Participación</th>
                <th style="width: 16%;" class="derecha">Total</th>
                <th style="width: 10%;" class="derecha">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes->take(15) as $indice => $cliente)
                @php
                    $ancho = (int) round(($cliente->total / $mayor) * 100);
                    $porcentaje = $resumen['ventas'] > 0 ? ($cliente->total / $resumen['ventas']) * 100 : 0;
                @endphp
                <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                    <td>{{ $cliente->cliente }}</td>
                    <td>
                        <div style="background: #e6eaf0; height: 9px; width: 100%;">
                            <div style="background: #0277b5; height: 9px; width: {{ $ancho }}%;"></div>
                        </div>
                    </td>
                    <td class="derecha">{{ \App\Support\Moneda::formato($cliente->total) }}</td>
                    <td class="derecha">{{ number_format($porcentaje, 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="pie">
    {{ config('tienda.nombre') }} · Reporte de ventas por cliente · Solo incluye pedidos con pago aprobado.
</div>

</body>
</html>
