@extends('layouts.tienda')

@section('titulo', 'Mis pedidos')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-bag-check me-2"></i>Historial de pedidos</h1>
        <a href="{{ route('perfil.mostrar') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person me-1"></i>Volver al perfil
        </a>
    </div>

    @if ($pedidos->isEmpty())

        <div class="ts-panel p-5 text-center">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <h2 class="h5 mt-3">Todavía no ha realizado compras</h2>
            <p class="text-muted">Cuando complete su primera compra aparecerá acá.</p>
            <a href="{{ route('catalogo.listado') }}" class="btn btn-ts">Ir al catálogo</a>
        </div>

    @else

        <div class="ts-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table ts-tabla-limpia align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Pedido</th>
                            <th scope="col">Fecha de compra</th>
                            <th scope="col">Artículos</th>
                            <th scope="col">Estado</th>
                            <th scope="col">N.º de seguimiento</th>
                            <th scope="col" class="text-end">Monto</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedidos as $pedido)
                            <tr>
                                <td>
                                    <a href="{{ route('pedidos.detalle', $pedido) }}" class="fw-semibold">
                                        {{ $pedido->numero_pedido }}
                                    </a>
                                    <div class="small text-muted">{{ $pedido->etiquetaMetodoPago() }}</div>
                                </td>
                                <td class="small">
                                    {{ $pedido->fecha_compra?->translatedFormat('d M Y') }}
                                    <div class="text-muted">{{ $pedido->fecha_compra?->format('h:i a') }}</div>
                                </td>
                                <td>{{ $pedido->cantidadArticulos() }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $pedido->colorEstado() }}">{{ $pedido->etiquetaEstado() }}</span>
                                </td>
                                <td><code class="small">{{ $pedido->numero_seguimiento }}</code></td>
                                <td class="text-end fw-semibold">@precio($pedido->total)</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pedidos.detalle', $pedido) }}" class="btn btn-outline-primary"
                                           title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($pedido->factura)
                                            <a href="{{ route('pedidos.factura', $pedido) }}" class="btn btn-outline-danger"
                                               title="Descargar factura en PDF">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $pedidos->links() }}
        </div>

    @endif

@endsection
