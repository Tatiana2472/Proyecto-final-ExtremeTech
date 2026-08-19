@extends('layouts.admin')

@section('titulo', 'Categorías')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 mb-1">Categorías del catálogo</h1>
            <p class="text-muted small mb-0">
                Las categorías inactivas no aparecen en el menú ni en la tienda.
            </p>
        </div>
        <a href="{{ route('admin.categorias.crear') }}" class="btn btn-ts btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nueva categoría
        </a>
    </div>

    <div class="ts-panel p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Categoría</th>
                        <th scope="col">URL amigable</th>
                        <th scope="col" class="text-center">Productos</th>
                        <th scope="col" class="text-center">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categorias as $categoria)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi {{ $categoria->icono ?? 'bi-tag' }} fs-5 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold d-block">{{ $categoria->nombre }}</span>
                                        <span class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit($categoria->descripcion, 60) ?: 'Sin descripción' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td><code class="small">{{ $categoria->slug }}</code></td>
                            <td class="text-center">{{ $categoria->productos_count }}</td>
                            <td class="text-center">
                                @if ($categoria->activa)
                                    <span class="badge text-bg-success">Activa</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('catalogo.categoria', $categoria) }}"
                                       class="btn btn-outline-secondary" target="_blank" title="Ver en la tienda">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                    <a href="{{ route('admin.categorias.editar', $categoria) }}"
                                       class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.categorias.eliminar', $categoria) }}" method="POST"
                                          data-confirmar="¿Eliminar «{{ $categoria->nombre }}»? Si tiene productos asociados solo se desactivará.">
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
                        <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay categorías.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $categorias->links() }}
    </div>

@endsection
