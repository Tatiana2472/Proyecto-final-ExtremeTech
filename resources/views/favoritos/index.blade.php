@extends('layouts.tienda')

@section('titulo', 'Mis favoritos')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-heart me-2"></i>Mis favoritos</h1>
        <a href="{{ route('perfil.mostrar') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person me-1"></i>Volver al perfil
        </a>
    </div>

    @if ($favoritos->isEmpty())

        <div class="ts-panel p-5 text-center">
            <i class="bi bi-heart fs-1 text-muted"></i>
            <h2 class="h5 mt-3">Todavía no ha guardado productos</h2>
            <p class="text-muted">
                Toque el corazón de cualquier producto para guardarlo acá y encontrarlo después.
            </p>
            <a href="{{ route('catalogo.listado') }}" class="btn btn-ts">Ir al catálogo</a>
        </div>

    @else

        <p class="text-muted">
            {{ $favoritos->total() }}
            {{ \Illuminate\Support\Str::plural('producto', $favoritos->total()) }}
            en su lista de deseos.
        </p>

        <div class="row g-3">
            @foreach ($favoritos as $producto)
                <div class="col-6 col-md-4 col-lg-3">
                    @include('partials.tarjeta-producto', ['producto' => $producto])
                </div>
            @endforeach
        </div>

        @if ($favoritos->hasPages())
            <div class="mt-4">{{ $favoritos->links() }}</div>
        @endif

    @endif

@endsection
