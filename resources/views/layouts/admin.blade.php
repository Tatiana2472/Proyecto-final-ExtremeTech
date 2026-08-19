<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'Panel') · Administración {{ config('tienda.nombre') }}</title>

    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tienda.css') }}">
    <link rel="icon" href="{{ asset('img/favicon.svg') }}?v=3" type="image/svg+xml">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}?v=3" type="image/svg+xml">
</head>
<body>

<nav class="navbar navbar-ts py-3 mb-4 ts-no-imprimir">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('admin.panel') }}">
            <img src="{{ asset('img/logo-extreme-tech-real.svg') }}" alt="{{ config('tienda.nombre') }}" class="ts-logo">
            <span class="badge text-bg-dark align-middle" style="font-size: 0.65rem;">ADMIN</span>
        </a>

        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('inicio') }}" class="small text-decoration-none">
                <i class="bi bi-shop me-1"></i>Ver la tienda
            </a>

            <div class="dropdown">
                <a class="btn btn-sm btn-outline-secondary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('perfil.mostrar') }}">Mi perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">
    <div class="row g-4">

        {{-- ============================ Menú lateral ========================== --}}
        <div class="col-lg-2 ts-no-imprimir">
            <div class="ts-admin-sidebar p-3">
                <nav class="nav flex-column gap-1">
                    <a class="nav-link {{ request()->routeIs('admin.panel') ? 'active' : '' }}"
                       href="{{ route('admin.panel') }}">
                        <i class="bi bi-grid-1x2 me-2"></i>Panel
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.productos.*') ? 'active' : '' }}"
                       href="{{ route('admin.productos.index') }}">
                        <i class="bi bi-box-seam me-2"></i>Productos
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}"
                       href="{{ route('admin.categorias.index') }}">
                        <i class="bi bi-tags me-2"></i>Categorías
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.pedidos.*') ? 'active' : '' }}"
                       href="{{ route('admin.pedidos.index') }}">
                        <i class="bi bi-receipt me-2"></i>Pedidos
                    </a>

                    <hr class="border-secondary my-2">
                    <span class="text-uppercase small px-2 mb-1" style="color:#7f9cbb; font-size:0.7rem; letter-spacing:0.5px;">
                        Reportes de ventas
                    </span>

                    <a class="nav-link {{ request()->routeIs('admin.reportes.por-mes') ? 'active' : '' }}"
                       href="{{ route('admin.reportes.por-mes') }}">
                        <i class="bi bi-calendar3 me-2"></i>Por mes
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.reportes.por-cliente') ? 'active' : '' }}"
                       href="{{ route('admin.reportes.por-cliente') }}">
                        <i class="bi bi-people me-2"></i>Por cliente
                    </a>
                </nav>
            </div>
        </div>

        {{-- ============================= Contenido ============================ --}}
        <div class="col-lg-10">
            @include('partials.alertas')
            @yield('contenido')
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/tienda.js') }}"></script>
@stack('scripts')
</body>
</html>
