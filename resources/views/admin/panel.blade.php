@extends('layouts.admin')

@section('titulo', 'Panel de administración')

@section('contenido')

    <h1 class="h4 mb-4">Panel de administración</h1>

    {{-- ======================== Indicadores del mes ======================== --}}
    <h2 class="h6 text-uppercase text-muted mb-3">{{ now()->translatedFormat('F \d\e Y') }}</h2>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Ventas del mes</div>
                <div class="valor">@precio($resumenMes['ventas'])</div>
                <div class="small text-muted">{{ $resumenMes['pedidos'] }} pedidos pagados</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Ticket promedio</div>
                <div class="valor">@precio($resumenMes['ticket_promedio'])</div>
                <div class="small text-muted">{{ $resumenMes['clientes'] }} clientes compraron</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">{{ config('tienda.impuesto.nombre') }} del mes</div>
                <div class="valor">@precio($resumenMes['impuestos'])</div>
                <div class="small text-muted">Por declarar</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Ventas históricas</div>
                <div class="valor">@precio($resumenTotal['ventas'])</div>
                <div class="small text-muted">{{ $resumenTotal['pedidos'] }} pedidos en total</div>
            </div>
        </div>
    </div>

    {{-- =========================== Estado interno ========================== --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ts-panel p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Productos en catálogo</span>
                <span class="fw-bold fs-5">{{ $conteos['productos'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-panel p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Sin existencias</span>
                <span class="fw-bold fs-5 {{ $conteos['sinStock'] > 0 ? 'text-danger' : '' }}">{{ $conteos['sinStock'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-panel p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Clientes registrados</span>
                <span class="fw-bold fs-5">{{ $conteos['clientes'] }}</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-panel p-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Pedidos pendientes</span>
                <span class="fw-bold fs-5 {{ $conteos['pendientes'] > 0 ? 'text-warning' : '' }}">{{ $conteos['pendientes'] }}</span>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ======================= Gráfico de ventas ======================== --}}
        <div class="col-lg-8">
            <div class="ts-panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 mb-0">Ventas mensuales de {{ now()->year }}</h2>
                    <a href="{{ route('admin.reportes.por-mes') }}" class="small">Ver reporte completo</a>
                </div>
                <canvas id="graficoVentas" height="130"></canvas>
            </div>
        </div>

        {{-- ====================== Productos más vendidos =================== --}}
        <div class="col-lg-4">
            <div class="ts-panel p-4 h-100">
                <h2 class="h6 mb-3">Más vendidos</h2>

                @forelse ($masVendidos as $item)
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <span class="small">
                            {{ $item->producto }}
                            <span class="d-block text-muted">{{ $item->unidades }} unidades</span>
                        </span>
                        <strong class="small text-nowrap">@precio($item->total)</strong>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aún no hay ventas registradas.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ========================= Últimos pedidos ========================== --}}
    <div class="ts-panel p-0 overflow-hidden mt-4">
        <div class="p-4 pb-0 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Últimos pedidos</h2>
            <a href="{{ route('admin.pedidos.index') }}" class="small">Ver todos</a>
        </div>

        <div class="table-responsive mt-3">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Pago</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ultimos as $pedido)
                        <tr>
                            <td>
                                <a href="{{ route('admin.pedidos.detalle', $pedido) }}" class="fw-semibold">
                                    {{ $pedido->numero_pedido }}
                                </a>
                            </td>
                            <td class="small">{{ $pedido->usuario->name ?? '—' }}</td>
                            <td class="small">{{ $pedido->fecha_compra?->translatedFormat('d M Y') }}</td>
                            <td class="small">{{ $pedido->etiquetaMetodoPago() }}</td>
                            <td><span class="badge text-bg-{{ $pedido->colorEstado() }}">{{ $pedido->etiquetaEstado() }}</span></td>
                            <td class="text-end fw-semibold">@precio($pedido->total)</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin pedidos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    @php
        // Se arma la estructura antes de serializarla, porque Blade no admite
        // arreglos multilínea escritos dentro de una directiva.
        $datosGrafico = $ventasPorMes
            ->map(fn ($m) => ['mes' => $m->nombre_mes, 'total' => $m->total])
            ->values();
    @endphp

    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        // La directiva de serialización de Blade convierte la colección a JSON
        // y escapa el contenido, de modo que no se puede inyectar código.
        const ventasMensuales = @json($datosGrafico);

        new Chart(document.getElementById('graficoVentas'), {
            type: 'bar',
            data: {
                labels: ventasMensuales.map((v) => v.mes),
                datasets: [{
                    label: 'Ventas ({{ config('tienda.moneda.codigo') }})',
                    data: ventasMensuales.map((v) => v.total),
                    backgroundColor: '#0ea5e9',
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (valor) => '{{ config('tienda.moneda.simbolo') }}' + valor.toLocaleString('es-CR'),
                        },
                    },
                },
            },
        });
    </script>
@endpush
