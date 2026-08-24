{{--
    Tarjeta de producto reutilizable.
    Parámetro esperado: $producto (App\Models\Product)
--}}
@php $agotado = ! $producto->hayExistencias(); @endphp

<div class="ts-card d-flex flex-column {{ $agotado ? 'ts-agotado' : '' }}">
    <a href="{{ route('catalogo.detalle', $producto) }}" class="position-relative">
        <img src="{{ $producto->urlImagen() }}" class="ts-card-img" alt="{{ $producto->nombre }}" loading="lazy">

        @if ($producto->tieneDescuento())
            <span class="badge ts-badge-descuento position-absolute top-0 start-0 m-2">
                -{{ $producto->porcentajeDescuento() }}%
            </span>
        @endif

        @if ($agotado)
            <span class="badge text-bg-secondary position-absolute top-0 end-0 m-2">Agotado</span>
        @elseif ($producto->existencias <= 5)
            <span class="badge text-bg-warning position-absolute top-0 end-0 m-2">
                Últimas {{ $producto->existencias }}
            </span>
        @endif
    </a>

    {{-- Lista de deseos: solo tiene sentido con la sesión iniciada, porque los
         favoritos se guardan contra el usuario en la tabla pivote --}}
    @auth
        @php $esFavorito = auth()->user()->tieneFavorito($producto); @endphp

        <form action="{{ route('favoritos.alternar', $producto) }}" method="POST"
              class="ts-favorito" data-favorito>
            @csrf
            <button type="submit"
                    class="btn btn-sm ts-btn-corazon {{ $esFavorito ? 'es-favorito' : '' }}"
                    aria-pressed="{{ $esFavorito ? 'true' : 'false' }}"
                    title="{{ $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                <i class="bi {{ $esFavorito ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            </button>
        </form>
    @endauth

    <div class="p-3 d-flex flex-column flex-grow-1">
        <span class="ts-categoria-pill">{{ $producto->categoria->nombre ?? 'General' }}</span>

        <h3 class="h6 mt-1 mb-1">
            <a href="{{ route('catalogo.detalle', $producto) }}" class="text-dark">{{ $producto->nombre }}</a>
        </h3>

        <p class="small text-muted mb-2">{{ \Illuminate\Support\Str::limit($producto->resumen, 70) }}</p>

        <div class="mt-auto">
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <span class="ts-precio">@precio($producto->precio)</span>
                @if ($producto->tieneDescuento())
                    <span class="ts-precio-anterior">@precio($producto->precio_anterior)</span>
                @endif
            </div>

            @if ($agotado)
                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                    <i class="bi bi-slash-circle me-1"></i>Sin existencias
                </button>
            @else
                {{-- El formulario incluye el token CSRF; el JS lo intercepta
                     para agregar al carrito sin recargar la página --}}
                <form action="{{ route('carrito.agregar', $producto) }}" method="POST" data-carrito-agregar>
                    @csrf
                    <input type="hidden" name="cantidad" value="1">
                    <button type="submit" class="btn btn-ts btn-sm w-100">
                        <i class="bi bi-cart-plus me-1"></i>Agregar al carrito
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
