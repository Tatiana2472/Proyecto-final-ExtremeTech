{{--
    Detalle común del pedido, reutilizado por la confirmación de compra y por
    la pantalla de detalle.
    Parámetro esperado: $pedido (App\Models\Order) con lineas, factura y pago.
--}}

<div class="row g-4">

    {{-- ========================= Detalle de líneas ========================= --}}
    <div class="col-lg-8">
        <div class="ts-panel p-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table ts-tabla-limpia align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Producto</th>
                            <th scope="col" class="text-center">Precio</th>
                            <th scope="col" class="text-center">Cant.</th>
                            <th scope="col" class="text-end">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedido->lineas as $linea)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $linea->nombre_producto }}</span>
                                    <span class="small text-muted">SKU {{ $linea->sku }}</span>
                                </td>
                                <td class="text-center">@precio($linea->precio_unitario)</td>
                                <td class="text-center">{{ $linea->cantidad }}</td>
                                <td class="text-end fw-semibold">@precio($linea->subtotal)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================== Datos de envío ======================== --}}
        <div class="ts-panel p-4">
            <h2 class="h6 mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i>Dirección de entrega</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4 text-muted fw-normal">Recibe</dt>
                <dd class="col-sm-8">{{ $pedido->envio_nombre }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Teléfono</dt>
                <dd class="col-sm-8">{{ $pedido->envio_telefono }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Dirección</dt>
                <dd class="col-sm-8">{{ $pedido->envio_direccion }}</dd>

                <dt class="col-sm-4 text-muted fw-normal">Ciudad / provincia</dt>
                <dd class="col-sm-8">{{ $pedido->envio_ciudad }}, {{ $pedido->envio_provincia }}</dd>

                @if ($pedido->notas)
                    <dt class="col-sm-4 text-muted fw-normal">Notas</dt>
                    <dd class="col-sm-8">{{ $pedido->notas }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ============================= Resumen ============================== --}}
    <div class="col-lg-4">
        <div class="ts-panel p-4 mb-4">
            <h2 class="h6 mb-3">Resumen de la compra</h2>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Subtotal</span><strong>@precio($pedido->subtotal)</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>{{ config('tienda.impuesto.nombre') }}
                        ({{ rtrim(rtrim(number_format((float) $pedido->tasa_impuesto * 100, 2, '.', ''), '0'), '.') }}%)</span>
                    <strong>@precio($pedido->impuesto)</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span>Envío</span>
                    <strong>{{ (float) $pedido->envio === 0.0 ? 'Gratis' : \App\Support\Moneda::formato($pedido->envio) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 pt-3">
                    <span class="h6 mb-0">Total</span>
                    <span class="h5 mb-0 text-primary fw-bold">@precio($pedido->total)</span>
                </li>
            </ul>
        </div>

        {{-- Seguimiento --}}
        <div class="ts-panel p-4 mb-4">
            <h2 class="h6 mb-3"><i class="bi bi-truck me-2 text-primary"></i>Seguimiento del envío</h2>
            <p class="small text-muted mb-1">Número de seguimiento</p>
            <p class="mb-0"><code class="fs-6">{{ $pedido->numero_seguimiento }}</code></p>
        </div>

        {{-- Pago --}}
        <div class="ts-panel p-4">
            <h2 class="h6 mb-3"><i class="bi bi-credit-card me-2 text-primary"></i>Pago</h2>
            <dl class="row mb-0 small">
                <dt class="col-6 text-muted fw-normal">Método</dt>
                <dd class="col-6 text-end">{{ $pedido->etiquetaMetodoPago() }}</dd>

                <dt class="col-6 text-muted fw-normal">Estado</dt>
                <dd class="col-6 text-end">
                    <span class="badge text-bg-{{ $pedido->estaPagado() ? 'success' : 'warning' }}">
                        {{ ucfirst($pedido->estado_pago) }}
                    </span>
                </dd>

                @if ($pedido->pago)
                    <dt class="col-6 text-muted fw-normal">Transacción</dt>
                    <dd class="col-6 text-end"><code class="small">{{ $pedido->pago->id_transaccion }}</code></dd>

                    @if ($pedido->pago->tarjeta_ultimos4)
                        <dt class="col-6 text-muted fw-normal">Tarjeta</dt>
                        <dd class="col-6 text-end">
                            {{ $pedido->pago->tarjeta_marca }} ····{{ $pedido->pago->tarjeta_ultimos4 }}
                        </dd>
                    @endif

                    @if ($pedido->pago->correo_pagador)
                        <dt class="col-6 text-muted fw-normal">Cuenta</dt>
                        <dd class="col-6 text-end small">{{ $pedido->pago->correo_pagador }}</dd>
                    @endif
                @endif

                @if ($pedido->factura)
                    <dt class="col-6 text-muted fw-normal">Factura</dt>
                    <dd class="col-6 text-end">{{ $pedido->factura->numero_factura }}</dd>
                @endif
            </dl>
        </div>
    </div>
</div>
