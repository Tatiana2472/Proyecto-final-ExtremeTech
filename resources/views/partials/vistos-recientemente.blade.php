{{--
    Productos vistos recientemente (leídos de la cookie "productos_vistos").
    Parámetro esperado: $vistos (colección de App\Models\Product)
--}}
@if (! empty($vistos) && count($vistos) > 0)
    <section class="ts-vistos p-3 p-md-4 mt-4" aria-labelledby="tituloVistos">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h6 mb-0 text-uppercase" id="tituloVistos">
                <i class="bi bi-clock-history me-2 text-primary"></i>Vistos recientemente
            </h2>
            <span class="badge text-bg-light border">Guardado en cookies</span>
        </div>

        <div class="row g-3">
            @foreach ($vistos as $visto)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                    <a href="{{ route('catalogo.detalle', $visto) }}"
                       class="d-flex align-items-center gap-2 text-dark">
                        <img src="{{ $visto->urlImagen() }}" alt="{{ $visto->nombre }}" class="ts-vistos-img" loading="lazy">
                        <span class="small">
                            <span class="d-block text-truncate" style="max-width: 150px;">{{ $visto->nombre }}</span>
                            <strong class="text-primary">@precio($visto->precio)</strong>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
@endif
