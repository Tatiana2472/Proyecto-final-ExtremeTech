@extends('layouts.tienda')

@section('titulo', 'Iniciar sesión')

@section('contenido')

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="ts-panel p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="bi bi-box-arrow-in-right fs-1 text-primary"></i>
                    <h1 class="h4 mt-2 mb-1">Iniciar sesión</h1>
                    <p class="text-muted small mb-0">Ingrese sus datos para continuar comprando.</p>
                </div>

                <form action="{{ route('login.enviar') }}" method="POST" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required
                                   autocomplete="email" autofocus>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required autocomplete="current-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" value="1"
                                   @checked(old('recordarme'))>
                            <label class="form-check-label small" for="recordarme">Mantener mi sesión abierta</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="small">¿Olvidó su contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-ts w-100 btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center small mb-0">
                    ¿Es su primera vez? <a href="{{ route('registro') }}" class="fw-semibold">Cree una cuenta</a>
                </p>
            </div>

            {{-- Credenciales de demostración: útiles para la exposición del proyecto --}}
            @if (config('app.debug'))
                <div class="ts-panel p-3 mt-3 small">
                    <strong class="d-block mb-2"><i class="bi bi-info-circle me-1"></i>Cuentas de demostración</strong>
                    <div class="d-flex justify-content-between">
                        <span>Administrador</span>
                        <code>admin@extremtech.cr / Admin1234*</code>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cliente</span>
                        <code>maria@example.com / Cliente1234*</code>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection
