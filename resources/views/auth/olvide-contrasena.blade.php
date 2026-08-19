@extends('layouts.tienda')

@section('titulo', 'Recuperar contraseña')

@section('contenido')

    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="ts-panel p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="bi bi-key fs-1 text-primary"></i>
                    <h1 class="h4 mt-2 mb-1">¿Olvidó su contraseña?</h1>
                    <p class="text-muted small mb-0">
                        Escriba su correo y le enviaremos un enlace para crear una nueva.
                    </p>
                </div>

                <form action="{{ route('password.email') }}" method="POST" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required
                                   maxlength="160" autocomplete="email" autofocus>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-ts w-100 btn-lg">
                        <i class="bi bi-send me-2"></i>Enviar enlace
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center small mb-0">
                    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Volver a iniciar sesión</a>
                </p>
            </div>

            @if (config('app.debug') && config('mail.default') === 'log')
                <div class="ts-panel p-3 mt-3 small">
                    <i class="bi bi-info-circle me-1"></i>
                    En desarrollo el correo no se envía de verdad: queda escrito en
                    <code>storage/logs/laravel.log</code>. Copie de ahí el enlace para continuar.
                </div>
            @endif
        </div>
    </div>

@endsection
