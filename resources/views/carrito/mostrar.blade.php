@extends('layouts.tienda')

@section('titulo', 'Mi carrito de compras')

@section('contenido')

    <ul class="ts-pasos ts-no-imprimir">
        <li class="activo"><i class="bi bi-1-circle me-1"></i>Carrito</li>
        <li><i class="bi bi-2-circle me-1"></i>Datos y pago</li>
        <li><i class="bi bi-3-circle me-1"></i>Confirmación</li>
    </ul>

    <h1 class="h3 mb-4"><i class="bi bi-cart3 me-2"></i>Mi carrito de compras</h1>

    @if ($lineas->isEmpty())

        <div class="ts-panel p-5 text-center">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <h2 class="h5 mt-3">Su carrito está vacío</h2>
            <p class="text-muted">Explore el catálogo y agregue los productos que necesita.</p>
            <a href="{{ route('catalogo.listado') }}" class="btn btn-ts">
                <i class="bi bi-shop me-2"></i>Ir al catálogo
            </a>
        </div>

        @include('partials.vistos-recientemente', ['vistos' => $vistos])

    @else

        <div class="row g-4">

            {{-- ========================== Líneas =========================== --}}
            <div class="col-lg-8">
                <div class="ts-panel p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table ts-tabla-limpia align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col" class="text-center">Precio</th>
                                    <th scope="col" class="text-center" style="min-width: 170px;">Cantidad</th>
                                    <th scope="col" class="text-end">Importe</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lineas as $linea)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $linea->producto->urlImagen() }}"
                                                     alt="{{ $linea->producto->nombre }}"
                                                     class="rounded" width="64" height="64"
                                                     style="object-fit: cover; background:#eef2f7;">
                                                <div>
                                                    <a href="{{ route('catalogo.detalle', $linea->producto) }}"
                                                       class="fw-semibold text-dark d-block">{{ $linea->producto->nombre }}</a>
                                                    <span class="small text-muted">
                                                        {{ $linea->producto->categoria->nombre }} ·
                                                        SKU {{ $linea->producto->sku }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-center">@precio($linea->precio_unitario)</td>

                                        {{-- Actualizar cantidad --}}
                                        <td>
                                            <form action="{{ route('carrito.actualizar', $linea->id) }}" method="POST"
                                                  class="d-flex justify-content-center">
                                                @csrf
                                                @method('PUT')
                                                <select name="cantidad" class="form-select form-select-sm"
                                                        style="max-width: 90px;" data-carrito-cantidad
                                                        aria-label="Cantidad de {{ $linea->producto->nombre }}">
                                                    @for ($i = 1; $i <= min($linea->producto->existencias, 20); $i++)
                                                        <option value="{{ $i }}" @selected($linea->cantidad === $i)>{{ $i }}</option>
                                                    @endfor
                                                </select>
                                                <noscript>
                                                    <button class="btn btn-sm btn-outline-secondary ms-2">Actualizar</button>
                                                </noscript>
                                            </form>
                                        </td>

                                        <td class="text-end fw-semibold">@precio($linea->subtotal())</td>

                                        {{-- Eliminar línea --}}
                                        <td class="text-end">
                                            <form action="{{ route('carrito.eliminar', $linea->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        aria-label="Quitar {{ $linea->producto->nombre }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between gap-2 mt-3">
                    <a href="{{ route('catalogo.listado') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Seguir comprando
                    </a>

                    <form action="{{ route('carrito.vaciar') }}" method="POST"
                          data-confirmar="¿Seguro que desea vaciar todo el carrito?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash3 me-1"></i>Vaciar carrito
                        </button>
                    </form>
                </div>
            </div>

            {{-- ========================= Totales =========================== --}}
            <div class="col-lg-4">
                <div class="ts-panel p-4 sticky-lg-top" style="top: 90px;">
                    <h2 class="h5 mb-3">Resumen de la compra</h2>

                    @include('partials.resumen-totales', ['totales' => $totales])

                    <div class="d-grid mt-4">
                        @auth
                            <a href="{{ route('checkout.mostrar') }}" class="btn btn-ts btn-lg">
                                <i class="bi bi-lock-fill me-2"></i>Continuar con el pago
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-ts btn-lg">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar para comprar
                            </a>
                            <p class="text-muted small text-center mt-2 mb-0">
                                ¿No tiene cuenta? <a href="{{ route('registro') }}">Registrarse</a>
                            </p>
                        @endauth
                    </div>

                    <p class="text-muted small text-center mt-3 mb-0">
                        <i class="bi bi-shield-lock me-1"></i>Pago procesado por conexión segura
                    </p>
                </div>
            </div>
        </div>

        @include('partials.vistos-recientemente', ['vistos' => $vistos])

    @endif

@endsection
