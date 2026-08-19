@extends('layouts.admin')

@section('titulo', 'Productos')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h4 mb-0">Productos del catálogo</h1>
        <a href="{{ route('admin.productos.crear') }}" class="btn btn-ts btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo producto
        </a>
    </div>

    {{-- ================================ Filtros ============================ --}}
    <form action="{{ route('admin.productos.index') }}" method="GET" class="ts-panel p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold mb-1">Buscar</label>
                <input type="search" class="form-control form-control-sm" id="q" name="q" maxlength="120"
                       value="{{ $filtros['q'] ?? '' }}" placeholder="Nombre, marca o SKU">
            </div>
            <div class="col-md-4">
                <label for="categoria" class="form-label small fw-semibold mb-1">Categoría</label>
                <select class="form-select form-select-sm" id="categoria" name="categoria">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) ($filtros['categoria'] ?? 0) === $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-ts btn-sm"><i class="bi bi-search me-1"></i>Buscar</button>
                <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    {{-- ================================= Tabla ============================= --}}
    <div class="ts-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="text-end">Precio</th>
                        <th scope="col" class="text-center">Existencias</th>
                        <th scope="col" class="text-center">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $producto->urlImagen() }}" alt="" width="46" height="46"
                                         class="rounded" style="object-fit: cover; background:#eef2f7;">
                                    <div>
                                        <span class="fw-semibold d-block">{{ $producto->nombre }}</span>
                                        <span class="small text-muted">
                                            {{ $producto->sku }} @if ($producto->marca) · {{ $producto->marca }} @endif
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $producto->categoria->nombre ?? '—' }}</td>
                            <td class="text-end">
                                @precio($producto->precio)
                                @if ($producto->tieneDescuento())
                                    <span class="d-block ts-precio-anterior">@precio($producto->precio_anterior)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($producto->existencias === 0)
                                    <span class="badge text-bg-danger">Agotado</span>
                                @elseif ($producto->existencias <= 5)
                                    <span class="badge text-bg-warning">{{ $producto->existencias }}</span>
                                @else
                                    {{ $producto->existencias }}
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($producto->activo)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                                @if ($producto->destacado)
                                    <i class="bi bi-star-fill text-warning ms-1" title="Destacado"></i>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('catalogo.detalle', $producto) }}" class="btn btn-outline-secondary"
                                       target="_blank" title="Ver en la tienda">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    <a href="{{ route('admin.productos.editar', $producto) }}"
                                       class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.productos.eliminar', $producto) }}" method="POST"
                                          data-confirmar="¿Eliminar «{{ $producto->nombre }}»? Si tiene ventas registradas solo se desactivará.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay productos que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $productos->links() }}
    </div>

@endsection
