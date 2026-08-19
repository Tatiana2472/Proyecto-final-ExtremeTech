@extends('layouts.tienda')

@section('titulo', 'Nueva contraseña')

@section('contenido')

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="ts-panel p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock fs-1 text-primary"></i>
                    <h1 class="h4 mt-2 mb-1">Definir una nueva contraseña</h1>
                    <p class="text-muted small mb-0">Escriba la contraseña que usará de ahora en adelante.</p>
                </div>

                <form action="{{ route('password.update') }}" method="POST" novalidate>
                    @csrf

                    {{-- El token identifica la solicitud y vence a los 60 minutos --}}
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $email) }}" required
                                   maxlength="160" autocomplete="email">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required autocomplete="new-password" autofocus>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Mínimo 8 caracteres, con al menos una letra y un número.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Repetir la contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-ts w-100 btn-lg">
                        <i class="bi bi-check2-circle me-2"></i>Guardar contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>

@endsection
