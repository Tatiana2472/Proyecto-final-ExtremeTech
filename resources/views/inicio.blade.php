@extends('layouts.tienda')

@section('titulo', config('tienda.nombre'))
@section('descripcion', 'Laptops, celulares, audio, gaming y accesorios con envío a todo Costa Rica.')

@section('contenido')

    {{-- ============================== Hero ============================== --}}
    <section class="ts-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col-lg-7">
                <span class="badge text-bg-light text-primary mb-3">Envío gratis desde @precio(config('tienda.envio.gratis_desde'))</span>
                <h1 class="fw-bold display-6">Tecnología que rinde, a precio que conviene</h1>
                <p class="lead mb-4 opacity-75">
                    Laptops, celulares, audio y equipo gamer con garantía local y entrega en todo el país.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('catalogo.listado') }}" class="btn btn-ts btn-lg px-4">
                        <i class="bi bi-shop me-2"></i>Ver catálogo
                    </a>
                    <a href="{{ route('catalogo.listado', ['orden' => 'precio_asc']) }}"
                       class="btn btn-outline-light btn-lg px-4">Ofertas del mes</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <i class="bi bi-laptop" style="font-size: 9rem; opacity: 0.35;"></i>
            </div>
        </div>
    </section>

    {{-- ========================== Ventajas ============================== --}}
    <div class="row g-3 mb-5">
        @foreach ([
            ['bi-truck', 'Envío a todo el país', 'Entrega de 1 a 3 días hábiles'],
            ['bi-shield-check', 'Compra protegida', 'Sitio con certificado SSL'],
            ['bi-credit-card-2-front', 'Múltiples formas de pago', 'Tarjeta, PayPal y SINPE Móvil'],
            ['bi-arrow-repeat', 'Garantía local', '12 meses en todos los equipos'],
        ] as [$icono, $titulo, $texto])
            <div class="col-6 col-lg-3">
                <div class="ts-panel p-3 h-100 d-flex align-items-start gap-3">
                    <i class="bi {{ $icono }} fs-4 text-primary"></i>
                    <div>
                        <div class="fw-semibold small">{{ $titulo }}</div>
                        <div class="text-muted small">{{ $texto }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ========================= Categorías ============================= --}}
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0">Compre por categoría</h2>
            <a href="{{ route('catalogo.listado') }}" class="small fw-semibold">Ver todo <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row g-3">
            @foreach ($categorias as $categoria)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('catalogo.categoria', $categoria) }}" class="ts-categoria-card">
                        <i class="bi {{ $categoria->icono ?? 'bi-tag' }}"></i>
                        <div class="fw-semibold small">{{ $categoria->nombre }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ $categoria->productos_count }} producto{{ $categoria->productos_count === 1 ? '' : 's' }}
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ==================== Productos vistos (cookies) =================== --}}
    @include('partials.vistos-recientemente', ['vistos' => $vistos])

    {{-- ========================== Destacados ============================ --}}
    <section class="my-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h4 mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Productos destacados</h2>
            <a href="{{ route('catalogo.listado') }}" class="small fw-semibold">Ver más <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="row g-3">
            @forelse ($destacados as $producto)
                <div class="col-6 col-md-4 col-lg-3">
                    @include('partials.tarjeta-producto', ['producto' => $producto])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">Todavía no hay productos destacados.</div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ============================ Ofertas ============================= --}}
    @if ($ofertas->isNotEmpty())
        <section class="mb-5">
            <h2 class="h4 mb-3"><i class="bi bi-tags-fill ts-texto-naranja me-2"></i>En oferta</h2>
            <div class="row g-3">
                @foreach ($ofertas as $producto)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.tarjeta-producto', ['producto' => $producto])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- =========================== Novedades =========================== --}}
    @if ($novedades->isNotEmpty())
        <section class="mb-4">
            <h2 class="h4 mb-3"><i class="bi bi-box-seam me-2 text-primary"></i>Recién llegados</h2>
            <div class="row g-3">
                @foreach ($novedades as $producto)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('partials.tarjeta-producto', ['producto' => $producto])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

@endsection
