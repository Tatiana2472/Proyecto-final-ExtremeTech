{{--
    Plantilla compartida por las páginas de error (403, 404, 419, 500).
    Parámetros: $codigo, $titulo, $mensaje, $icono
--}}
@extends('layouts.tienda')

@section('titulo', $codigo.' · '.$titulo)

@section('contenido')

    <div class="row justify-content-center py-5">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="ts-panel p-5">
                <i class="bi {{ $icono }} text-primary" style="font-size: 3.5rem;"></i>

                <div class="display-4 fw-bold mt-2" style="color: var(--ts-azul);">{{ $codigo }}</div>
                <h1 class="h4 mt-1 mb-3">{{ $titulo }}</h1>
                <p class="text-muted">{{ $mensaje }}</p>

                <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                    <a href="{{ route('inicio') }}" class="btn btn-ts">
                        <i class="bi bi-house me-2"></i>Ir a la portada
                    </a>
                    <a href="{{ route('catalogo.listado') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-shop me-2"></i>Ver el catálogo
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
