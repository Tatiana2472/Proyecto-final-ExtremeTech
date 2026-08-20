<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas por mes {{ $anio }}</title>
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
            <h1>REPORTE DE VENTAS POR MES</h1>
            <div class="negrita" style="font-size: 12px;">Año {{ $anio }}</div>
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
            <div class="valor">{{ \App\Support\Moneda::formato($resumen['ventas']) }}</div>
            <div class="etiqueta">Total vendido</div>
        </td>
        <td>
            <div class="valor">{{ $resumen['pedidos'] }}</div>
            <div class="etiqueta">Pedidos pagados</div>
        </td>
        <td>
            <div class="valor">{{ \App\Support\Moneda::formato($resumen['impuestos']) }}</div>
            <div class="etiqueta">{{ config('tienda.impuesto.nombre') }} recaudado</div>
        </td>
        <td>
            <div class="valor">{{ \App\Support\Moneda::formato($resumen['ticket_promedio']) }}</div>
            <div class="etiqueta">Ticket promedio</div>
        </td>
    </tr>
</table>

{{-- ========================== Detalle mensual ============================= --}}
<h2>Ventas mes a mes</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 16%;">Mes</th>
            <th style="width: 10%;" class="centro">Pedidos</th>
            <th style="width: 10%;" class="centro">Unidades</th>
            <th class="derecha">Subtotal</th>
            <th class="derecha">{{ config('tienda.impuesto.nombre') }}</th>
            <th class="derecha">Envíos</th>
            <th class="derecha">Total vendido</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ventas as $indice => $mes)
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td style="text-transform: capitalize;" class="negrita">{{ $mes->nombre_mes }}</td>
                <td class="centro">{{ $mes->pedidos }}</td>
                <td class="centro">{{ $mes->unidades }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($mes->subtotal) }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($mes->impuesto) }}</td>
                <td class="derecha">{{ \App\Support\Moneda::formato($mes->envio) }}</td>
                <td class="derecha negrita">{{ \App\Support\Moneda::formato($mes->total) }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL {{ $anio }}</td>
            <td class="centro">{{ $ventas->sum('pedidos') }}</td>
            <td class="centro">{{ $ventas->sum('unidades') }}</td>
            <td class="derecha">{{ \App\Support\Moneda::formato($ventas->sum('subtotal')) }}</td>
            <td class="derecha">{{ \App\Support\Moneda::formato($ventas->sum('impuesto')) }}</td>
            <td class="derecha">{{ \App\Support\Moneda::formato($ventas->sum('envio')) }}</td>
            <td class="derecha">{{ \App\Support\Moneda::formato($ventas->sum('total')) }}</td>
        </tr>
    </tfoot>
</table>

{{-- ===================== Gráfico de barras en texto ======================= --}}
@php
    $maximo = max((float) $ventas->max('total'), 1);
@endphp

<h2>Distribución de las ventas</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 16%;">Mes</th>
            <th>Participación</th>
            <th style="width: 18%;" class="derecha">Total</th>
            <th style="width: 12%;" class="derecha">%</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ventas as $indice => $mes)
            @php
                $ancho = (int) round(($mes->total / $maximo) * 100);
                $porcentaje = $resumen['ventas'] > 0 ? ($mes->total / $resumen['ventas']) * 100 : 0;
            @endphp
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td style="text-transform: capitalize;">{{ $mes->nombre_mes }}</td>
                <td>
                    {{-- Barra dibujada con un div, porque DomPDF no ejecuta JavaScript --}}
                    <div style="background: #e6eaf0; height: 9px; width: 100%;">
                        <div style="background: #0277b5; height: 9px; width: {{ $ancho }}%;"></div>
                    </div>
                </td>
                <td class="derecha">{{ \App\Support\Moneda::formato($mes->total) }}</td>
                <td class="derecha">{{ number_format($porcentaje, 1) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ======================= Productos más vendidos ======================== --}}
<h2>Productos más vendidos en {{ $anio }}</h2>

<table class="listado">
    <thead>
        <tr>
            <th style="width: 6%;">#</th>
            <th>Producto</th>
            <th style="width: 18%;">SKU</th>
            <th style="width: 14%;" class="centro">Unidades</th>
            <th style="width: 20%;" class="derecha">Total vendido</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($masVendidos as $indice => $item)
            <tr class="{{ $indice % 2 === 1 ? 'par' : '' }}">
                <td>{{ $indice + 1 }}</td>
                <td>{{ $item->producto }}</td>
                <td class="pequeno gris">{{ $item->sku }}</td>
                <td class="centro">{{ $item->unidades }}</td>
                <td class="derecha negrita">{{ \App\Support\Moneda::formato($item->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="centro gris">No hay ventas registradas en {{ $anio }}.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pie">
    {{ config('tienda.nombre') }} · Reporte de ventas por mes · Solo incluye pedidos con pago aprobado.
</div>

</body>
</html>
