@extends('layouts.admin')

@php $esNueva = ! $categoria->exists; @endphp

@section('titulo', $esNueva ? 'Nueva categoría' : 'Editar categoría')

@section('contenido')

    <h1 class="h4 mb-4">{{ $esNueva ? 'Nueva categoría' : 'Editar: '.$categoria->nombre }}</h1>

    <form action="{{ $esNueva ? route('admin.categorias.guardar') : route('admin.categorias.actualizar', $categoria) }}"
          method="POST" novalidate>
        @csrf
        @unless ($esNueva)
            @method('PUT')
        @endunless

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="ts-panel p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                   id="nombre" name="nombre" maxlength="120" required
                                   value="{{ old('nombre', $categoria->nombre) }}" autofocus>
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="slug" class="form-label">URL amigable</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                   id="slug" name="slug" maxlength="140"
                                   value="{{ old('slug', $categoria->slug) }}" placeholder="se genera sola">
                            @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Déjelo vacío y se genera a partir del nombre.</div>
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                      id="descripcion" name="descripcion" rows="3"
                                      maxlength="500">{{ old('descripcion', $categoria->descripcion) }}</textarea>
                            @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ts-panel p-4 mb-4">
                    <h2 class="h6 mb-3">Presentación</h2>

                    <div class="mb-3">
                        <label for="icono" class="form-label">Ícono</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi {{ old('icono', $categoria->icono) ?: 'bi-tag' }}"></i>
                            </span>
                            <input type="text" class="form-control @error('icono') is-invalid @enderror"
                                   id="icono" name="icono" maxlength="60"
                                   value="{{ old('icono', $categoria->icono) }}" placeholder="bi-laptop">
                            @error('icono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">
                            Nombre de <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener">Bootstrap Icons</a>,
                            por ejemplo <code>bi-laptop</code>, <code>bi-phone</code> o <code>bi-headphones</code>.
                        </div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="activa" name="activa" value="1"
                               @checked(old('activa', $categoria->activa ?? true))>
                        <label class="form-check-label" for="activa">Visible en la tienda</label>
                    </div>
                </div>

                @unless ($esNueva)
                    <div class="ts-panel p-4 mb-4 small">
                        <h2 class="h6 mb-2">Productos asociados</h2>
                        <p class="text-muted mb-2">
                            Esta categoría tiene <strong>{{ $categoria->productos()->count() }}</strong> producto(s).
                        </p>
                        <a href="{{ route('admin.productos.index', ['categoria' => $categoria->id]) }}"
                           class="btn btn-outline-primary btn-sm w-100">
                            Ver sus productos
                        </a>
                    </div>
                @endunless

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-ts">
                        <i class="bi bi-save me-2"></i>{{ $esNueva ? 'Crear categoría' : 'Guardar cambios' }}
                    </button>
                    <a href="{{ route('admin.categorias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>
    </form>

@endsection
