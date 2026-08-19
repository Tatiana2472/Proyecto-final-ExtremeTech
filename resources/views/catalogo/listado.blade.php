@extends('layouts.tienda')

@section('titulo', $categoria->nombre ?? 'Catálogo de productos')

@section('contenido')

    <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('catalogo.listado') }}">Catálogo</a></li>
            @if ($categoria)
                <li class="breadcrumb-item active" aria-current="page">{{ $categoria->nombre }}</li>
            @endif
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $categoria->nombre ?? 'Todos los productos' }}</h1>
            <p class="text-muted mb-0 small">
                {{ $productos->total() }} producto{{ $productos->total() === 1 ? '' : 's' }} encontrado{{ $productos->total() === 1 ? '' : 's' }}
                @if (! empty($filtros['q']))
                    para «{{ $filtros['q'] }}»
                @endif
            </p>
        </div>
    </div>

    <div class="row g-4">

        {{-- =========================== Filtros =========================== --}}
        <div class="col-lg-3">
            <form action="{{ route('catalogo.listado') }}" method="GET" class="ts-panel p-3">
                <h2 class="h6 text-uppercase text-muted mb-3">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </h2>

                {{-- Búsqueda por nombre --}}
                <div class="mb-3">
                    <label for="filtroQ" class="form-label small fw-semibold">Buscar por nombre o marca</label>
                    <input type="search" class="form-control form-control-sm" id="filtroQ" name="q"
                           maxlength="120" value="{{ $filtros['q'] ?? '' }}" placeholder="Ej. laptop, Samsung">
                </div>

                {{-- Filtro por categoría --}}
                <div class="mb-3">
                    <label for="filtroCategoria" class="form-label small fw-semibold">Categoría</label>
                    <select class="form-select form-select-sm" id="filtroCategoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $opcion)
                            <option value="{{ $opcion->slug }}" @selected(($filtros['categoria'] ?? null) === $opcion->slug)>
                                {{ $opcion->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro por rango de precio --}}
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Rango de precio</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" name="min" min="0" step="1000"
                                   value="{{ $filtros['min'] ?? '' }}" placeholder="Desde"
                                   aria-label="Precio mínimo">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control form-control-sm" name="max" min="0" step="1000"
                                   value="{{ $filtros['max'] ?? '' }}" placeholder="Hasta"
                                   aria-label="Precio máximo">
                        </div>
                    </div>
                    <div class="form-text" style="font-size: 0.75rem;">
                        Catálogo entre @precio($rangoPrecios['min']) y @precio($rangoPrecios['max'])
                    </div>
                </div>

                {{-- Ordenamiento --}}
                <div class="mb-3">
                    <label for="filtroOrden" class="form-label small fw-semibold">Ordenar por</label>
                    <select class="form-select form-select-sm" id="filtroOrden" name="orden">
                        @foreach ([
                            'recientes'   => 'Más recientes',
                            'precio_asc'  => 'Precio: de menor a mayor',
                            'precio_desc' => 'Precio: de mayor a menor',
                            'nombre'      => 'Nombre (A-Z)',
                            'antiguos'    => 'Más antiguos',
                        ] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['orden'] ?? 'recientes') === $valor)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-ts btn-sm">
                        <i class="bi bi-search me-1"></i>Aplicar filtros
                    </button>
                    <a href="{{ route('catalogo.listado') }}" class="btn btn-outline-secondary btn-sm">
                        Limpiar filtros
                    </a>
                </div>
            </form>
        </div>

        {{-- ========================== Resultados ========================= --}}
        <div class="col-lg-9">
            @if ($productos->isEmpty())
                <div class="ts-panel p-5 text-center">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <h2 class="h5 mt-3">No encontramos productos con esos criterios</h2>
                    <p class="text-muted">Pruebe con otras palabras o quite algunos filtros.</p>
                    <a href="{{ route('catalogo.listado') }}" class="btn btn-ts">Ver todo el catálogo</a>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($productos as $producto)
                        <div class="col-6 col-md-4">
                            @include('partials.tarjeta-producto', ['producto' => $producto])
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $productos->links() }}
                </div>
            @endif

            @include('partials.vistos-recientemente', ['vistos' => $vistos])
        </div>
    </div>

@endsection
