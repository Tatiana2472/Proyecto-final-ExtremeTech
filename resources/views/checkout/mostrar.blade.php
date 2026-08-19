@extends('layouts.tienda')

@section('titulo', 'Finalizar compra')

@section('contenido')

    <ul class="ts-pasos">
        <li class="listo"><i class="bi bi-check-circle me-1"></i>Carrito</li>
        <li class="activo"><i class="bi bi-2-circle me-1"></i>Datos y pago</li>
        <li><i class="bi bi-3-circle me-1"></i>Confirmación</li>
    </ul>

    <h1 class="h3 mb-4"><i class="bi bi-lock-fill me-2"></i>Finalizar compra</h1>

    {{-- data-un-solo-envio evita que el usuario pague dos veces por doble clic --}}
    <form action="{{ route('checkout.procesar') }}" method="POST" data-un-solo-envio novalidate>
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ==================== Datos de envío ==================== --}}
                <div class="ts-panel p-4 mb-4">
                    <h2 class="h5 mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Datos de entrega</h2>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="envio_nombre" class="form-label">Nombre de quien recibe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('envio_nombre') is-invalid @enderror"
                                   id="envio_nombre" name="envio_nombre" maxlength="160" required
                                   value="{{ old('envio_nombre', $usuario->name) }}">
                            @error('envio_nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-5">
                            <label for="envio_telefono" class="form-label">Teléfono de contacto <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control @error('envio_telefono') is-invalid @enderror"
                                   id="envio_telefono" name="envio_telefono" maxlength="30" required
                                   value="{{ old('envio_telefono', $usuario->telefono) }}" placeholder="8888-8888">
                            @error('envio_telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="envio_direccion" class="form-label">Dirección exacta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('envio_direccion') is-invalid @enderror"
                                   id="envio_direccion" name="envio_direccion" maxlength="255" required
                                   value="{{ old('envio_direccion', $usuario->direccion) }}"
                                   placeholder="Barrio, señas y número de casa">
                            @error('envio_direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="envio_provincia" class="form-label">Provincia <span class="text-danger">*</span></label>
                            <select class="form-select @error('envio_provincia') is-invalid @enderror"
                                    id="envio_provincia" name="envio_provincia" required>
                                <option value="">Seleccione…</option>
                                @foreach ($provincias as $provincia)
                                    <option value="{{ $provincia }}"
                                        @selected(old('envio_provincia', $usuario->provincia) === $provincia)>
                                        {{ $provincia }}
                                    </option>
                                @endforeach
                            </select>
                            @error('envio_provincia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="envio_ciudad" class="form-label">Ciudad o cantón <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('envio_ciudad') is-invalid @enderror"
                                   id="envio_ciudad" name="envio_ciudad" maxlength="100" required
                                   value="{{ old('envio_ciudad', $usuario->ciudad) }}">
                            @error('envio_ciudad') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="notas" class="form-label">Notas para la entrega <span class="text-muted small">(opcional)</span></label>
                            <textarea class="form-control @error('notas') is-invalid @enderror" id="notas" name="notas"
                                      rows="2" maxlength="500">{{ old('notas') }}</textarea>
                            @error('notas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- ==================== Método de pago ==================== --}}
                <div class="ts-panel p-4">
                    <h2 class="h5 mb-1"><i class="bi bi-credit-card-2-front me-2 text-primary"></i>Método de pago</h2>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-shield-lock me-1"></i>
                        Sus datos viajan cifrados. No almacenamos el número completo de su tarjeta ni el CVV.
                    </p>

                    @error('metodo_pago') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                    <div class="row g-2 mb-4">
                        @foreach ($metodos as $identificador => $metodo)
                            <div class="col-md-4">
                                <label class="ts-metodo-pago d-flex align-items-start gap-2 h-100">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="metodo_pago"
                                           value="{{ $identificador }}"
                                           @checked(old('metodo_pago', 'tarjeta') === $identificador)>
                                    <span class="ts-metodo-texto small">
                                        <strong class="d-block">
                                            @switch($identificador)
                                                @case('tarjeta') <i class="bi bi-credit-card me-1"></i> @break
                                                @case('paypal')  <i class="bi bi-paypal me-1"></i> @break
                                                @case('sinpe')   <i class="bi bi-phone me-1"></i> @break
                                            @endswitch
                                            {{ $metodo['etiqueta'] }}
                                        </strong>
                                        <span class="text-muted">{{ $metodo['descripcion'] }}</span>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    {{-- ------------------- Campos: tarjeta ------------------- --}}
                    <div data-campos-pago="tarjeta" class="d-none">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nombre_tarjeta" class="form-label">Nombre tal como aparece en la tarjeta</label>
                                <input type="text" class="form-control @error('nombre_tarjeta') is-invalid @enderror"
                                       id="nombre_tarjeta" name="nombre_tarjeta" maxlength="120"
                                       autocomplete="cc-name" data-obligatorio="si"
                                       value="{{ old('nombre_tarjeta', $usuario->name) }}">
                                @error('nombre_tarjeta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="numero_tarjeta" class="form-label">Número de tarjeta</label>
                                <input type="text" class="form-control @error('numero_tarjeta') is-invalid @enderror"
                                       id="numero_tarjeta" name="numero_tarjeta" inputmode="numeric"
                                       autocomplete="cc-number" data-obligatorio="si"
                                       placeholder="4111 1111 1111 1111">
                                @error('numero_tarjeta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 col-6">
                                <label for="mes" class="form-label">Mes</label>
                                <select class="form-select @error('mes') is-invalid @enderror" id="mes" name="mes"
                                        autocomplete="cc-exp-month" data-obligatorio="si">
                                    <option value="">MM</option>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" @selected((int) old('mes') === $m)>
                                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endfor
                                </select>
                                @error('mes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 col-6">
                                <label for="anio" class="form-label">Año</label>
                                <select class="form-select @error('anio') is-invalid @enderror" id="anio" name="anio"
                                        autocomplete="cc-exp-year" data-obligatorio="si">
                                    <option value="">AAAA</option>
                                    @for ($a = now()->year; $a <= now()->year + 12; $a++)
                                        <option value="{{ $a }}" @selected((int) old('anio') === $a)>{{ $a }}</option>
                                    @endfor
                                </select>
                                @error('anio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 col-6">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="password" class="form-control @error('cvv') is-invalid @enderror"
                                       id="cvv" name="cvv" inputmode="numeric" maxlength="4"
                                       autocomplete="cc-csc" data-obligatorio="si" placeholder="123">
                                @error('cvv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="alert alert-secondary small mt-3 mb-0">
                            <strong>Modo de pruebas (sandbox).</strong>
                            Use <code>4111 1111 1111 1111</code> con cualquier fecha futura y CVV para una compra aprobada.
                            La tarjeta <code>4000 0000 0000 0002</code> simula un rechazo del banco.
                        </div>
                    </div>

                    {{-- -------------------- Campos: PayPal ------------------- --}}
                    <div data-campos-pago="paypal" class="d-none">
                        <label for="correo_paypal" class="form-label">Correo de su cuenta PayPal</label>
                        <input type="email" class="form-control @error('correo_paypal') is-invalid @enderror"
                               id="correo_paypal" name="correo_paypal" maxlength="160" data-obligatorio="si"
                               value="{{ old('correo_paypal', $usuario->email) }}">
                        @error('correo_paypal') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="alert alert-secondary small mt-3 mb-0">
                            El cobro se realiza en {{ config('tienda.pagos.paypal.moneda') }} usando el tipo de cambio
                            de @precio(config('tienda.pagos.paypal.tipo_cambio')) por dólar.
                            En modo de pruebas cualquier correo válido se aprueba; los que comienzan con
                            <code>rechazado@</code> simulan un pago fallido.
                        </div>
                    </div>

                    {{-- --------------------- Campos: SINPE ------------------- --}}
                    <div data-campos-pago="sinpe" class="d-none">
                        <div class="alert alert-info small">
                            Realice la transferencia por SINPE Móvil al número
                            <strong>{{ config('tienda.pagos.sinpe.numero') }}</strong>
                            e indique abajo el número de comprobante que le envía su banco.
                        </div>

                        <label for="comprobante_sinpe" class="form-label">Número de comprobante</label>
                        <input type="text" class="form-control @error('comprobante_sinpe') is-invalid @enderror"
                               id="comprobante_sinpe" name="comprobante_sinpe" maxlength="30" data-obligatorio="si"
                               value="{{ old('comprobante_sinpe') }}" placeholder="Ej. 123456789">
                        @error('comprobante_sinpe') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- ===================== Resumen del pedido ==================== --}}
            <div class="col-lg-4">
                <div class="ts-panel p-4 sticky-lg-top" style="top: 90px;">
                    <h2 class="h5 mb-3">Su pedido</h2>

                    <ul class="list-unstyled small mb-3">
                        @foreach ($lineas as $linea)
                            <li class="d-flex justify-content-between gap-2 mb-2">
                                <span class="text-truncate">
                                    <span class="badge text-bg-light border">{{ $linea->cantidad }}×</span>
                                    {{ $linea->producto->nombre }}
                                </span>
                                <strong class="text-nowrap">@precio($linea->subtotal())</strong>
                            </li>
                        @endforeach
                    </ul>

                    @include('partials.resumen-totales', ['totales' => $totales])

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-ts btn-lg">
                            <i class="bi bi-shield-check me-2"></i>Pagar @precio($totales->total)
                        </button>
                    </div>

                    <p class="text-muted small text-center mt-3 mb-0">
                        Al confirmar acepta nuestras políticas de compra y devolución.
                    </p>

                    <a href="{{ route('carrito.mostrar') }}" class="btn btn-link btn-sm w-100 mt-1">
                        <i class="bi bi-arrow-left me-1"></i>Volver al carrito
                    </a>
                </div>
            </div>
        </div>
    </form>

@endsection
