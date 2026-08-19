@extends('layouts.admin')

@section('titulo', 'Reporte de ventas por mes')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Reporte de ventas por mes</h1>
            <p class="text-muted small mb-0">Solo se incluyen los pedidos con el pago aprobado.</p>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-end ts-no-imprimir">
            {{-- Selector de año --}}
            <form action="{{ route('admin.reportes.por-mes') }}" method="GET" class="d-flex gap-2 align-items-end">
                <div>
                    <label for="anio" class="form-label small fw-semibold mb-1">Año</label>
                    <select name="anio" id="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($anios as $opcion)
                            <option value="{{ $opcion }}" @selected($anio === $opcion)>{{ $opcion }}</option>
                        @endforeach
                        @unless ($anios->contains($anio))
                            <option value="{{ $anio }}" selected>{{ $anio }}</option>
                        @endunless
                    </select>
                </div>
                <noscript><button class="btn btn-sm btn-ts">Ver</button></noscript>
            </form>

            {{-- Descarga en PDF --}}
            <a href="{{ route('admin.reportes.por-mes.pdf', ['anio' => $anio]) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
            </a>
        </div>
    </div>

    {{-- ============================ Indicadores =========================== --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Ventas del año</div>
                <div class="valor">@precio($resumen['ventas'])</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Pedidos</div>
                <div class="valor">{{ $resumen['pedidos'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">{{ config('tienda.impuesto.nombre') }} recaudado</div>
                <div class="valor">@precio($resumen['impuestos'])</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Ticket promedio</div>
                <div class="valor">@precio($resumen['ticket_promedio'])</div>
            </div>
        </div>
    </div>

    {{-- ============================== Gráfico ============================= --}}
    <div class="ts-panel p-4 mb-4">
        <h2 class="h6 mb-3">Comportamiento mensual</h2>
        <canvas id="graficoMensual" height="110"></canvas>
    </div>

    {{-- =============================== Tabla ============================== --}}
    <div class="ts-panel p-0 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Mes</th>
                        <th scope="col" class="text-center">Pedidos</th>
                        <th scope="col" class="text-center">Unidades</th>
                        <th scope="col" class="text-end">Subtotal</th>
                        <th scope="col" class="text-end">{{ config('tienda.impuesto.nombre') }}</th>
                        <th scope="col" class="text-end">Envíos</th>
                        <th scope="col" class="text-end">Total vendido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $mes)
                        <tr class="{{ $mes->pedidos === 0 ? 'text-muted' : '' }}">
                            <td class="text-capitalize fw-semibold">{{ $mes->nombre_mes }}</td>
                            <td class="text-center">{{ $mes->pedidos }}</td>
                            <td class="text-center">{{ $mes->unidades }}</td>
                            <td class="text-end">@precio($mes->subtotal)</td>
                            <td class="text-end">@precio($mes->impuesto)</td>
                            <td class="text-end">@precio($mes->envio)</td>
                            <td class="text-end fw-bold">@precio($mes->total)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>Total {{ $anio }}</td>
                        <td class="text-center">{{ $ventas->sum('pedidos') }}</td>
                        <td class="text-center">{{ $ventas->sum('unidades') }}</td>
                        <td class="text-end">@precio($ventas->sum('subtotal'))</td>
                        <td class="text-end">@precio($ventas->sum('impuesto'))</td>
                        <td class="text-end">@precio($ventas->sum('envio'))</td>
                        <td class="text-end">@precio($ventas->sum('total'))</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ======================= Productos más vendidos ===================== --}}
    <div class="ts-panel p-0 overflow-hidden">
        <div class="p-4 pb-0">
            <h2 class="h6 mb-0">Productos más vendidos en {{ $anio }}</h2>
        </div>
        <div class="table-responsive mt-3">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Producto</th>
                        <th scope="col">SKU</th>
                        <th scope="col" class="text-center">Unidades</th>
                        <th scope="col" class="text-end">Total vendido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($masVendidos as $indice => $item)
                        <tr>
                            <td>{{ $indice + 1 }}</td>
                            <td>{{ $item->producto }}</td>
                            <td class="small text-muted">{{ $item->sku }}</td>
                            <td class="text-center">{{ $item->unidades }}</td>
                            <td class="text-end fw-semibold">@precio($item->total)</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin ventas en {{ $anio }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    @php
        // Los datos del gráfico se preparan acá y no dentro de la directiva de
        // serialización, porque Blade no admite arreglos multilínea en línea.
        $datosGrafico = $ventas->map(fn ($m) => [
            'mes'     => $m->nombre_mes,
            'total'   => $m->total,
            'pedidos' => $m->pedidos,
        ])->values();
    @endphp

    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        const datosMes = @json($datosGrafico);

        new Chart(document.getElementById('graficoMensual'), {
            data: {
                labels: datosMes.map((m) => m.mes),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Total vendido',
                        data: datosMes.map((m) => m.total),
                        backgroundColor: '#0ea5e9',
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Cantidad de pedidos',
                        data: datosMes.map((m) => m.pedidos),
                        borderColor: '#ff7a1a',
                        backgroundColor: '#ff7a1a',
                        tension: 0.3,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            callback: (v) => '{{ config('tienda.moneda.simbolo') }}' + v.toLocaleString('es-CR'),
                        },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { precision: 0 },
                    },
                },
            },
        });
    </script>
@endpush
