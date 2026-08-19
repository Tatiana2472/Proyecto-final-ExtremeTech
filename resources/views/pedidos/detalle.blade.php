@extends('layouts.tienda')

@section('titulo', 'Pedido '.$pedido->numero_pedido)

@section('contenido')

    <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('perfil.mostrar') }}">Mi perfil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('pedidos.historial') }}">Mis pedidos</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $pedido->numero_pedido }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Pedido {{ $pedido->numero_pedido }}</h1>
            <p class="text-muted mb-0 small">
                Realizado el {{ $pedido->fecha_compra?->translatedFormat('d \d\e F \d\e Y \a \l\a\s h:i a') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge text-bg-{{ $pedido->colorEstado() }} align-self-center py-2 px-3">
                {{ $pedido->etiquetaEstado() }}
            </span>
            @if ($pedido->factura)
                <a href="{{ route('pedidos.factura', $pedido) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Factura PDF
                </a>
            @endif
        </div>
    </div>

    @include('pedidos.partials.cuerpo-pedido', ['pedido' => $pedido])

    <a href="{{ route('pedidos.historial') }}" class="btn btn-outline-secondary mt-4">
        <i class="bi bi-arrow-left me-1"></i>Volver a mis pedidos
    </a>

@endsection
