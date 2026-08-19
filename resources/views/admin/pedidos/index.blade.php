@extends('layouts.admin')

@section('titulo', 'Pedidos')

@section('contenido')

    <h1 class="h4 mb-4">Pedidos</h1>

    <form action="{{ route('admin.pedidos.index') }}" method="GET" class="ts-panel p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold mb-1">N.º de pedido o seguimiento</label>
                <input type="search" class="form-control form-control-sm" id="q" name="q" maxlength="60"
                       value="{{ $filtros['q'] ?? '' }}" placeholder="PED-2026-000001 o TS260415ABCDEF">
            </div>
            <div class="col-md-4">
                <label for="estado" class="form-label small fw-semibold mb-1">Estado</label>
                <select class="form-select form-select-sm" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach (['pendiente' => 'Pendiente de pago', 'pagado' => 'Pagado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? null) === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-ts btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
                <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="ts-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Fecha de compra</th>
                        <th scope="col">Método de pago</th>
                        <th scope="col">Seguimiento</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Monto</th>
                        <th scope="col" class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td>
                                <a href="{{ route('admin.pedidos.detalle', $pedido) }}" class="fw-semibold">
                                    {{ $pedido->numero_pedido }}
                                </a>
                            </td>
                            <td class="small">
                                {{ $pedido->usuario->name ?? '—' }}
                                <span class="d-block text-muted">{{ $pedido->usuario->email ?? '' }}</span>
                            </td>
                            <td class="small">{{ $pedido->fecha_compra?->translatedFormat('d M Y h:i a') }}</td>
                            <td class="small">{{ $pedido->etiquetaMetodoPago() }}</td>
                            <td><code class="small">{{ $pedido->numero_seguimiento }}</code></td>
                            <td><span class="badge text-bg-{{ $pedido->colorEstado() }}">{{ $pedido->etiquetaEstado() }}</span></td>
                            <td class="text-end fw-semibold">@precio($pedido->total)</td>
                            <td class="text-end">
                                <a href="{{ route('admin.pedidos.detalle', $pedido) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No hay pedidos que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $pedidos->links() }}
    </div>

@endsection
