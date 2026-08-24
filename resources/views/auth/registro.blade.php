@extends('layouts.tienda')

@section('titulo', 'Crear cuenta')

@section('contenido')

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="ts-panel p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus fs-1 text-primary"></i>
                    <h1 class="h4 mt-2 mb-1">Crear una cuenta</h1>
                    <p class="text-muted small mb-0">Regístrese para comprar y seguir sus pedidos.</p>
                </div>

                <form action="{{ route('registro.guardar') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required
                                   maxlength="120" autocomplete="name" autofocus
                                   pattern="[A-Za-zÁÉÍÓÚáéíóúÜüÑñÀ-ÿ .'-]+"
                                   title="Use únicamente letras, espacios, apóstrofos, puntos o guiones.">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}" required
                                   maxlength="160" autocomplete="email"
                                   title="Ingrese un correo válido, por ejemplo: nombre@dominio.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono <span class="text-muted small">(opcional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control @error('telefono') is-invalid @enderror"
                                   id="telefono" name="telefono" value="{{ old('telefono') }}"
                                   maxlength="30" placeholder="8888-8888" autocomplete="tel">
                            @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text">Mínimo 8 caracteres, con al menos una letra y un número.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input @error('terminos') is-invalid @enderror" type="checkbox"
                               id="terminos" name="terminos" value="1" @checked(old('terminos'))>
                        <label class="form-check-label small" for="terminos">
                            Acepto los términos, condiciones y la política de privacidad de la tienda.
                        </label>
                        @error('terminos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-ts w-100 btn-lg">
                        <i class="bi bi-check2-circle me-2"></i>Crear mi cuenta
                    </button>
                </form>

                <hr class="my-4">

                <p class="text-center small mb-0">
                    ¿Ya tiene cuenta? <a href="{{ route('login') }}" class="fw-semibold">Inicie sesión</a>
                </p>
            </div>
        </div>
    </div>

@endsection
