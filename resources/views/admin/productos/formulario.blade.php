@extends('layouts.admin')

@php $esNuevo = ! $producto->exists; @endphp

@section('titulo', $esNuevo ? 'Nuevo producto' : 'Editar producto')

@section('contenido')

    <h1 class="h4 mb-4">{{ $esNuevo ? 'Nuevo producto' : 'Editar: '.$producto->nombre }}</h1>

    <form action="{{ $esNuevo ? route('admin.productos.guardar') : route('admin.productos.actualizar', $producto) }}"
          method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @unless ($esNuevo)
            @method('PUT')
        @endunless

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="ts-panel p-4">
                    <h2 class="h6 mb-3">Información general</h2>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   id="nombre" name="nombre" maxlength="160" required
                                   value="{{ old('nombre', $producto->nombre) }}">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                   id="sku" name="sku" maxlength="40" required
                                   value="{{ old('sku', $producto->sku) }}" placeholder="LAP-LEN-001">
                            @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id" required>
                                <option value="">Seleccione…</option>
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}"
                                        @selected((int) old('category_id', $producto->category_id) === $categoria->id)>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control @error('marca') is-invalid @enderror"
                                   id="marca" name="marca" maxlength="80"
                                   value="{{ old('marca', $producto->marca) }}">
                            @error('marca') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="resumen" class="form-label">Resumen corto</label>
                            <input type="text" class="form-control @error('resumen') is-invalid @enderror"
                                   id="resumen" name="resumen" maxlength="255"
                                   value="{{ old('resumen', $producto->resumen) }}"
                                   placeholder="Una línea con las características principales">
                            @error('resumen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción completa</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                      id="descripcion" name="descripcion" rows="6"
                                      maxlength="5000">{{ old('descripcion', $producto->descripcion) }}</textarea>
                            @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- ========================= Precio e inventario ================= --}}
                <div class="ts-panel p-4 mb-4">
                    <h2 class="h6 mb-3">Precio e inventario</h2>

                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio de venta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ config('tienda.moneda.simbolo') }}</span>
                            <input type="number" class="form-control @error('precio') is-invalid @enderror"
                                   id="precio" name="precio" min="1" step="1" required
                                   value="{{ old('precio', $producto->precio ? (int) $producto->precio : '') }}">
                            @error('precio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="precio_anterior" class="form-label">Precio anterior</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ config('tienda.moneda.simbolo') }}</span>
                            <input type="number" class="form-control @error('precio_anterior') is-invalid @enderror"
                                   id="precio_anterior" name="precio_anterior" min="1" step="1"
                                   value="{{ old('precio_anterior', $producto->precio_anterior ? (int) $producto->precio_anterior : '') }}">
                            @error('precio_anterior') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Déjelo vacío si el producto no está en oferta.</div>
                    </div>

                    <div class="mb-3">
                        <label for="existencias" class="form-label">Existencias <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('existencias') is-invalid @enderror"
                               id="existencias" name="existencias" min="0" step="1" required
                               value="{{ old('existencias', $producto->existencias ?? 0) }}">
                        @error('existencias') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1"
                               @checked(old('activo', $producto->activo ?? true))>
                        <label class="form-check-label" for="activo">Visible en la tienda</label>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="destacado" name="destacado" value="1"
                               @checked(old('destacado', $producto->destacado ?? false))>
                        <label class="form-check-label" for="destacado">Mostrar como destacado</label>
                    </div>
                </div>

                {{-- ============================== Imagen ======================== --}}
                <div class="ts-panel p-4 mb-4">
                    <h2 class="h6 mb-3">Imagen</h2>

                    @if ($producto->imagen)
                        <img src="{{ $producto->urlImagen() }}" alt="" class="img-fluid rounded mb-3"
                             style="aspect-ratio: 4/3; object-fit: cover; width: 100%;">
                    @endif

                    <input type="file" class="form-control @error('imagen') is-invalid @enderror"
                           id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                    @error('imagen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">JPG, PNG, WEBP o SVG. Máximo 2 MB.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-ts">
                        <i class="bi bi-save me-2"></i>{{ $esNuevo ? 'Crear producto' : 'Guardar cambios' }}
                    </button>
                    <a href="{{ route('admin.productos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>

@endsection
