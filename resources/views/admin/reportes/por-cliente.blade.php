@extends('layouts.admin')

@section('titulo', 'Reporte de ventas por cliente')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Reporte de ventas por cliente</h1>
            <p class="text-muted small mb-0">
                Período del {{ $desde->translatedFormat('d M Y') }} al {{ $hasta->translatedFormat('d M Y') }}.
            </p>
        </div>

        <a href="{{ route('admin.reportes.por-cliente.pdf', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}"
           class="btn btn-danger btn-sm ts-no-imprimir">
            <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
        </a>
    </div>

    {{-- ============================ Filtro de fechas ======================= --}}
    <form action="{{ route('admin.reportes.por-cliente') }}" method="GET" class="ts-panel p-3 mb-4 ts-no-imprimir">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="desde" class="form-label small fw-semibold mb-1">Desde</label>
                <input type="date" class="form-control form-control-sm" id="desde" name="desde"
                       value="{{ $desde->toDateString() }}">
            </div>
            <div class="col-md-4">
                <label for="hasta" class="form-label small fw-semibold mb-1">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="hasta" name="hasta"
                       value="{{ $hasta->toDateString() }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-ts btn-sm">
                    <i class="bi bi-funnel me-1"></i>Aplicar
                </button>
                <a href="{{ route('admin.reportes.por-cliente') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    {{-- ============================== Indicadores ========================= --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Clientes que compraron</div>
                <div class="valor">{{ $clientes->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ts-indicador">
                <div class="etiqueta">Total vendido</div>
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
                <div class="etiqueta">Ticket promedio</div>
                <div class="valor">@precio($resumen['ticket_promedio'])</div>
            </div>
        </div>
    </div>

    {{-- ================================ Tabla ============================= --}}
    <div class="ts-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Correo electrónico</th>
                        <th scope="col" class="text-center">Pedidos</th>
                        <th scope="col" class="text-end">Promedio</th>
                        <th scope="col" class="text-end">Total comprado</th>
                        <th scope="col">Última compra</th>
                        <th scope="col" class="text-end ts-no-imprimir">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $indice => $cliente)
                        <tr>
                            <td>{{ $indice + 1 }}</td>
                            <td class="fw-semibold">{{ $cliente->cliente }}</td>
                            <td class="small text-muted">{{ $cliente->correo }}</td>
                            <td class="text-center">{{ $cliente->pedidos }}</td>
                            <td class="text-end">@precio($cliente->promedio)</td>
                            <td class="text-end fw-bold">@precio($cliente->total)</td>
                            <td class="small">
                                {{ \Illuminate\Support\Carbon::parse($cliente->ultima_compra)->translatedFormat('d M Y') }}
                            </td>
                            <td class="text-end ts-no-imprimir">
                                <a class="btn btn-sm btn-outline-danger"
                                   href="{{ route('admin.reportes.cliente.pdf', ['cliente' => $cliente->user_id, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}"
                                   title="Reporte individual en PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay ventas registradas en el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($clientes->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="3">Totales</td>
                            <td class="text-center">{{ $clientes->sum('pedidos') }}</td>
                            <td></td>
                            <td class="text-end">@precio($clientes->sum('total'))</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

@endsection
