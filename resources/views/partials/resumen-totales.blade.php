{{--
    Desglose del cálculo automático del total de la compra.
    Parámetro esperado: $totales (App\Services\TotalesCarrito)
--}}
<ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between px-0">
        <span>Subtotal ({{ $totales->cantidadArticulos }} artículo{{ $totales->cantidadArticulos === 1 ? '' : 's' }})</span>
        <strong>@precio($totales->subtotal)</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between px-0">
        <span>{{ config('tienda.impuesto.nombre') }} ({{ $totales->porcentajeImpuesto() }}%)</span>
        <strong>@precio($totales->impuesto)</strong>
    </li>
    <li class="list-group-item d-flex justify-content-between px-0">
        <span>Costo de envío</span>
        @if ($totales->envioGratis)
            <strong class="text-success">Gratis</strong>
        @else
            <strong>@precio($totales->envio)</strong>
        @endif
    </li>
    <li class="list-group-item d-flex justify-content-between px-0 pt-3">
        <span class="h6 mb-0">Total a pagar</span>
        <span class="h5 mb-0 text-primary fw-bold">@precio($totales->total)</span>
    </li>
</ul>

@if (! $totales->envioGratis && $totales->faltaParaEnvioGratis > 0)
    <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
        <i class="bi bi-truck me-1"></i>
        Agregue @precio($totales->faltaParaEnvioGratis) más y el envío es <strong>gratis</strong>.
    </div>
@endif
