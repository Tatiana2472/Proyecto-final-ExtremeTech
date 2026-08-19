@extends('layouts.tienda')

@section('titulo', 'Mi perfil')

@section('contenido')

    <h1 class="h3 mb-4"><i class="bi bi-person-circle me-2"></i>Mi perfil</h1>

    {{-- ========================= Resumen de actividad ====================== --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="ts-indicador">
                <div class="etiqueta">Pedidos realizados</div>
                <div class="valor">{{ $resumen['pedidos'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ts-indicador">
                <div class="etiqueta">Total comprado</div>
                <div class="valor">@precio($resumen['comprado'])</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ts-indicador">
                <div class="etiqueta">Última compra</div>
                <div class="valor" style="font-size: 1.1rem;">
                    {{ $resumen['ultima'] ? $resumen['ultima']->translatedFormat('d M Y') : 'Sin compras aún' }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ======================= Datos personales ======================== --}}
        <div class="col-lg-7">
            <div class="ts-panel p-4">
                <h2 class="h5 mb-3"><i class="bi bi-pencil-square me-2 text-primary"></i>Datos personales</h2>

                <form action="{{ route('perfil.actualizar') }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" maxlength="120" required
                                   value="{{ old('name', $usuario->name) }}">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-7">
                            <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" maxlength="160" required
                                   value="{{ old('email', $usuario->email) }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control @error('telefono') is-invalid @enderror"
                                   id="telefono" name="telefono" maxlength="30"
                                   value="{{ old('telefono', $usuario->telefono) }}">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="cedula" class="form-label">Cédula</label>
                            <input type="text" class="form-control @error('cedula') is-invalid @enderror"
                                   id="cedula" name="cedula" maxlength="30" placeholder="1-1234-5678"
                                   value="{{ old('cedula', $usuario->cedula) }}">
                            @error('cedula') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Se usa para la facturación.</div>
                        </div>

                        <div class="col-md-7">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control @error('direccion') is-invalid @enderror"
                                   id="direccion" name="direccion" maxlength="255"
                                   value="{{ old('direccion', $usuario->direccion) }}">
                            @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="provincia" class="form-label">Provincia</label>
                            <select class="form-select @error('provincia') is-invalid @enderror" id="provincia" name="provincia">
                                <option value="">Seleccione…</option>
                                @foreach ($provincias as $provincia)
                                    <option value="{{ $provincia }}" @selected(old('provincia', $usuario->provincia) === $provincia)>
                                        {{ $provincia }}
                                    </option>
                                @endforeach
                            </select>
                            @error('provincia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="ciudad" class="form-label">Ciudad o cantón</label>
                            <input type="text" class="form-control @error('ciudad') is-invalid @enderror"
                                   id="ciudad" name="ciudad" maxlength="100"
                                   value="{{ old('ciudad', $usuario->ciudad) }}">
                            @error('ciudad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-ts mt-4">
                        <i class="bi bi-save me-2"></i>Guardar cambios
                    </button>
                </form>
            </div>
        </div>

        {{-- ========================== Contraseña ========================== --}}
        <div class="col-lg-5">
            <div class="ts-panel p-4 mb-4">
                <h2 class="h5 mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Cambiar contraseña</h2>

                <form action="{{ route('perfil.contrasena') }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control @error('password_actual') is-invalid @enderror"
                               id="password_actual" name="password_actual" required autocomplete="current-password">
                        @error('password_actual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_nueva" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password_nueva" name="password" required autocomplete="new-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Repetir nueva contraseña</label>
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-ts-azul w-100">
                        <i class="bi bi-key me-2"></i>Actualizar contraseña
                    </button>
                </form>
            </div>

            <div class="ts-panel p-4">
                <h2 class="h6 mb-3"><i class="bi bi-bag-check me-2 text-primary"></i>Historial de pedidos</h2>
                <p class="small text-muted">
                    Consulte el estado, el número de seguimiento y la factura de cada una de sus compras.
                </p>
                <a href="{{ route('pedidos.historial') }}" class="btn btn-outline-primary w-100">
                    Ver mis pedidos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

@endsection
