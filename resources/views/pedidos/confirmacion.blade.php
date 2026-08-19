@extends('layouts.tienda')

@section('titulo', 'Compra confirmada')

@section('contenido')

    <ul class="ts-pasos ts-no-imprimir">
        <li class="listo"><i class="bi bi-check-circle me-1"></i>Carrito</li>
        <li class="listo"><i class="bi bi-check-circle me-1"></i>Datos y pago</li>
        <li class="activo"><i class="bi bi-3-circle me-1"></i>Confirmación</li>
    </ul>

    {{-- ====================== Encabezado de confirmación ===================== --}}
    <div class="ts-panel p-4 p-lg-5 text-center mb-4">
        <i class="bi bi-check-circle-fill text-success" style="font-size: 3.5rem;"></i>
        <h1 class="h3 mt-3 mb-2">¡Gracias por su compra, {{ Str::of($pedido->usuario->name ?? $pedido->envio_nombre)->before(' ') }}!</h1>
        <p class="text-muted mb-4">
            Su pedido fue registrado y el pago quedó {{ $pedido->estaPagado() ? 'aprobado' : 'pendiente de confirmación' }}.
            Le enviaremos las actualizaciones al correo registrado.
        </p>

        <div class="row g-3 justify-content-center text-start">
            <div class="col-md-4">
                <div class="ts-indicador text-center">
                    <div class="etiqueta">Número de pedido</div>
                    <div class="valor" style="font-size: 1.1rem;">{{ $pedido->numero_pedido }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ts-indicador text-center">
                    <div class="etiqueta">Número de seguimiento</div>
                    <div class="valor" style="font-size: 1.1rem;">{{ $pedido->numero_seguimiento }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ts-indicador text-center">
                    <div class="etiqueta">Monto pagado</div>
                    <div class="valor">@precio($pedido->total)</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4 ts-no-imprimir">
            @if ($pedido->factura)
                <a href="{{ route('pedidos.factura', $pedido) }}" class="btn btn-ts">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Descargar factura ({{ $pedido->factura->numero_factura }})
                </a>
            @endif
            <a href="{{ route('pedidos.historial') }}" class="btn btn-outline-primary">
                <i class="bi bi-bag-check me-2"></i>Ver mis pedidos
            </a>
            <a href="{{ route('catalogo.listado') }}" class="btn btn-outline-secondary">
                <i class="bi bi-shop me-2"></i>Seguir comprando
            </a>
        </div>
    </div>

    {{-- ========================= Detalle del pedido ========================= --}}
    <h2 class="h5 mb-3">Detalle de su compra</h2>

    @include('pedidos.partials.cuerpo-pedido', ['pedido' => $pedido])

@endsection
