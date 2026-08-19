@extends('layouts.tienda')

@section('titulo', $producto->nombre)
@section('descripcion', $producto->resumen)

@section('contenido')

    <nav aria-label="Ruta de navegación">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('catalogo.listado') }}">Catálogo</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('catalogo.categoria', $producto->categoria) }}">{{ $producto->categoria->nombre }}</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($producto->nombre, 40) }}</li>
        </ol>
    </nav>

    <div class="ts-panel p-3 p-lg-4 mb-4">
        <div class="row g-4">

            {{-- ============================ Imagen ============================ --}}
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="{{ $producto->urlImagen() }}" alt="{{ $producto->nombre }}"
                         class="img-fluid rounded" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; background:#eef2f7;">
                    @if ($producto->tieneDescuento())
                        <span class="badge ts-badge-descuento position-absolute top-0 start-0 m-3 fs-6">
                            -{{ $producto->porcentajeDescuento() }}% de descuento
                        </span>
                    @endif
                </div>
            </div>

            {{-- =========================== Información ======================== --}}
            <div class="col-lg-6">
                <span class="ts-categoria-pill">{{ $producto->categoria->nombre }}</span>
                <h1 class="h3 mt-1">{{ $producto->nombre }}</h1>

                <div class="d-flex flex-wrap gap-3 text-muted small mb-3">
                    <span><i class="bi bi-upc-scan me-1"></i>SKU: {{ $producto->sku }}</span>
                    @if ($producto->marca)
                        <span><i class="bi bi-award me-1"></i>Marca: {{ $producto->marca }}</span>
                    @endif
                </div>

                <p class="lead fs-6">{{ $producto->resumen }}</p>

                <div class="d-flex align-items-baseline gap-3 my-3">
                    <span class="h2 mb-0 text-primary fw-bold">@precio($producto->precio)</span>
                    @if ($producto->tieneDescuento())
                        <span class="ts-precio-anterior fs-5">@precio($producto->precio_anterior)</span>
                        <span class="badge text-bg-success">
                            Ahorre @precio($producto->precio_anterior - $producto->precio)
                        </span>
                    @endif
                </div>

                {{-- Disponibilidad --}}
                <p class="mb-3">
                    @if ($producto->hayExistencias())
                        <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Disponible</span>
                        <span class="text-muted small ms-1">{{ $producto->existencias }} unidades en inventario</span>
                    @else
                        <span class="badge text-bg-secondary"><i class="bi bi-slash-circle me-1"></i>Agotado</span>
                    @endif
                </p>

                {{-- ====================== Agregar al carrito ==================== --}}
                @if ($producto->hayExistencias())
                    <form action="{{ route('carrito.agregar', $producto) }}" method="POST"
                          class="row g-2 align-items-end mb-3" data-carrito-agregar>
                        @csrf
                        <div class="col-auto">
                            <label for="cantidad" class="form-label small fw-semibold mb-1">Cantidad</label>
                            <div class="input-group" style="width: 148px;">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-cantidad-paso="-1" data-destino="cantidad" aria-label="Disminuir">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" id="cantidad" name="cantidad"
                                       value="1" min="1" max="{{ min($producto->existencias, 20) }}">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-cantidad-paso="1" data-destino="cantidad" aria-label="Aumentar">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-ts btn-lg w-100">
                                <i class="bi bi-cart-plus me-2"></i>Agregar al carrito
                            </button>
                        </div>
                    </form>

                    <a href="{{ route('carrito.mostrar') }}" class="btn btn-outline-primary w-100 mb-3">
                        <i class="bi bi-bag-check me-2"></i>Ir al carrito y finalizar compra
                    </a>
                @else
                    <div class="alert alert-secondary">
                        Este producto está agotado. Vuelva a consultarnos pronto.
                    </div>
                @endif

                {{-- Garantías --}}
                <div class="row g-2 small text-muted">
                    <div class="col-6"><i class="bi bi-truck me-2 text-primary"></i>Envío a todo el país</div>
                    <div class="col-6"><i class="bi bi-shield-check me-2 text-primary"></i>Garantía de 12 meses</div>
                    <div class="col-6"><i class="bi bi-credit-card me-2 text-primary"></i>Tarjeta, PayPal o SINPE</div>
                    <div class="col-6"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Cambios en 8 días</div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================== Descripción ============================ --}}
    <div class="ts-panel p-4 mb-4">
        <h2 class="h5 mb-3">Descripción del producto</h2>
        <p class="mb-0" style="white-space: pre-line;">{{ $producto->descripcion }}</p>
    </div>

    {{-- ==================== Productos vistos (cookies) ==================== --}}
    @include('partials.vistos-recientemente', ['vistos' => $vistos])

    {{-- =========================== Relacionados ========================== --}}
    @if ($relacionados->isNotEmpty())
        <section class="mt-5">
            <h2 class="h5 mb-3">También le puede interesar</h2>
            <div class="row g-3">
                @foreach ($relacionados as $relacionado)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.tarjeta-producto', ['producto' => $relacionado])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

@endsection
