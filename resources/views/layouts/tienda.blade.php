<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Token CSRF disponible para las peticiones AJAX del carrito --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('tienda.nombre') }}</title>
    <meta name="description" content="@yield('descripcion', config('tienda.lema'))">

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    {{-- El parámetro ?v= obliga al navegador a descargar el ícono nuevo en
         lugar de reutilizar el que tenga guardado en caché --}}
    <link rel="icon" href="{{ asset('img/favicon.svg') }}?v=3" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=3" type="image/svg+xml">
</head>
<body class="d-flex flex-column min-vh-100">

{{-- ====================== Barra superior informativa ====================== --}}
<div class="ts-barra-superior py-2 d-none d-md-block">
    <div class="container d-flex justify-content-between">
        <span><i class="bi bi-truck me-1"></i> Envío gratis en compras superiores a @precio(config('tienda.envio.gratis_desde'))</span>
        <span>
            <i class="bi bi-telephone me-1"></i> {{ config('tienda.telefono') }}
            <i class="bi bi-envelope ms-3 me-1"></i> {{ config('tienda.correo') }}
        </span>
    </div>
</div>

{{-- ============================== Encabezado ============================== --}}
<nav class="navbar navbar-expand-lg navbar-ts sticky-top py-3">
    <div class="container">
        <a class="navbar-brand" href="{{ route('inicio') }}">
            <img src="{{ asset('img/logo-extreme-tech-real.svg') }}" alt="{{ config('tienda.nombre') }}" class="ts-logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}"
                       href="{{ route('inicio') }}">Inicio</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('catalogo.*') ? 'active' : '' }}"
                       href="#" role="button" data-bs-toggle="dropdown">Categorías</a>
                    <ul class="dropdown-menu">
                        @foreach ($categoriasMenu ?? [] as $categoriaMenu)
                            <li>
                                <a class="dropdown-item" href="{{ route('catalogo.categoria', $categoriaMenu) }}">
                                    <i class="bi {{ $categoriaMenu->icono ?? 'bi-tag' }} me-2"></i>{{ $categoriaMenu->nombre }}
                                </a>
                            </li>
                        @endforeach
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-semibold" href="{{ route('catalogo.listado') }}">Ver todo el catálogo</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('catalogo.listado') ? 'active' : '' }}"
                       href="{{ route('catalogo.listado') }}">Productos</a>
                </li>
            </ul>

            {{-- Buscador: envía por GET al listado del catálogo --}}
            <form class="d-flex me-lg-3 my-2 my-lg-0" role="search" action="{{ route('catalogo.listado') }}" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="q" maxlength="120"
                           value="{{ request('q') }}" placeholder="Buscar productos..." aria-label="Buscar">
                    <button class="btn btn-ts" type="submit" aria-label="Buscar">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <ul class="navbar-nav align-items-lg-center">
                {{-- Carrito con el contador de artículos --}}
                <li class="nav-item me-lg-2">
                    <a class="nav-link position-relative" href="{{ route('carrito.mostrar') }}">
                        <i class="bi bi-cart3 fs-5"></i>
                        <span class="d-lg-none ms-1">Carrito</span>
                        <span id="contadorCarrito"
                              class="badge rounded-pill text-bg-danger ts-carrito-badge {{ ($articulosCarrito ?? 0) > 0 ? '' : 'd-none' }}">
                            {{ $articulosCarrito ?? 0 }}
                        </span>
                    </a>
                </li>

                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>Ingresar</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-ts-azul btn-sm px-3" href="{{ route('registro') }}">Crear cuenta</a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('perfil.mostrar') }}"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
                            <li><a class="dropdown-item" href="{{ route('pedidos.historial') }}"><i class="bi bi-bag-check me-2"></i>Mis pedidos</a></li>
                            @if (auth()->user()->esAdministrador())
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('admin.panel') }}"><i class="bi bi-speedometer2 me-2"></i>Panel de administración</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                {{-- El cierre de sesión va por POST + CSRF para que no pueda
                                     forzarse desde un enlace externo --}}
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

{{-- ============================== Contenido ============================== --}}
<main class="flex-grow-1 py-4">
    <div class="container">
        @include('partials.alertas')
        @yield('contenido')
    </div>
</main>

{{-- ================================ Footer =============================== --}}
<footer class="ts-footer pt-5 pb-4 mt-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <img src="{{ asset('img/logo-extreme-tech-real-blanco.svg') }}"
                     alt="{{ config('tienda.nombre') }}" class="ts-logo-pie">
                <p class="small mb-2">Lo mejor en tecnología.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-white">Catálogo</h6>
                <ul class="list-unstyled small">
                    @foreach (($categoriasMenu ?? [])->take(5) as $categoriaPie)
                        <li class="mb-1"><a href="{{ route('catalogo.categoria', $categoriaPie) }}">{{ $categoriaPie->nombre }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="text-white">Mi cuenta</h6>
                <ul class="list-unstyled small">
                    @auth
                        <li class="mb-1"><a href="{{ route('perfil.mostrar') }}">Mis datos</a></li>
                        <li class="mb-1"><a href="{{ route('pedidos.historial') }}">Historial de pedidos</a></li>
                    @else
                        <li class="mb-1"><a href="{{ route('login') }}">Iniciar sesión</a></li>
                        <li class="mb-1"><a href="{{ route('registro') }}">Crear cuenta</a></li>
                    @endauth
                    <li class="mb-1"><a href="{{ route('carrito.mostrar') }}">Mi carrito</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6 class="text-white">Contacto</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><i class="bi bi-geo-alt me-2"></i>{{ config('tienda.direccion') }}</li>
                    <li class="mb-1"><i class="bi bi-telephone me-2"></i>{{ config('tienda.telefono') }}</li>
                    <li class="mb-1"><i class="bi bi-envelope me-2"></i>{{ config('tienda.correo') }}</li>
                </ul>
                <div class="mt-3 small">
                    <i class="bi bi-shield-lock me-1"></i>Sitio protegido con certificado SSL
                </div>
            </div>
        </div>
        <hr class="border-secondary my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between small">
            <span>&copy; {{ now()->year }} {{ config('tienda.nombre') }}. Todos los derechos reservados.</span>
            <span>Precios en colones costarricenses e incluyen {{ config('tienda.impuesto.nombre') }} donde corresponde.</span>
        </div>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/tienda.js') }}"></script>
@stack('scripts')
</body>
</html>
