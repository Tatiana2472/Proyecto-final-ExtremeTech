@extends('layouts.admin')

@section('titulo', 'Pedido '.$pedido->numero_pedido)

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Pedido {{ $pedido->numero_pedido }}</h1>
            <p class="text-muted small mb-0">
                {{ $pedido->usuario->name ?? '—' }} ({{ $pedido->usuario->email ?? '' }}) ·
                {{ $pedido->fecha_compra?->translatedFormat('d \d\e F \d\e Y, h:i a') }}
            </p>
        </div>

        <div class="d-flex gap-2 ts-no-imprimir">
            {{-- Cambio de estado del pedido --}}
            <form action="{{ route('admin.pedidos.estado', $pedido) }}" method="POST" class="d-flex gap-2">
                @csrf
                @method('PUT')
                <select name="estado" class="form-select form-select-sm" style="width: auto;">
                    @foreach (['pendiente' => 'Pendiente de pago', 'pagado' => 'Pagado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($pedido->estado === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-ts btn-sm text-nowrap">
                    <i class="bi bi-check2 me-1"></i>Actualizar
                </button>
            </form>

            <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @include('pedidos.partials.cuerpo-pedido', ['pedido' => $pedido])

    {{-- ==================== Historial de intentos de pago ==================== --}}
    <div class="ts-panel p-0 overflow-hidden mt-4">
        <div class="p-4 pb-0">
            <h2 class="h6 mb-0">Transacciones registradas</h2>
        </div>
        <div class="table-responsive mt-3">
            <table class="table ts-tabla-limpia align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Método</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Transacción</th>
                        <th scope="col">Detalle</th>
                        <th scope="col" class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedido->pagos as $pago)
                        <tr>
                            <td class="small">{{ $pago->procesado_en?->translatedFormat('d M Y h:i a') }}</td>
                            <td class="small">{{ config("tienda.pagos.metodos.{$pago->metodo}.etiqueta", $pago->metodo) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $pago->fueAprobado() ? 'success' : 'danger' }}">
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>
                            <td><code class="small">{{ $pago->id_transaccion ?? '—' }}</code></td>
                            <td class="small text-muted">
                                @if ($pago->tarjeta_ultimos4)
                                    {{ $pago->tarjeta_marca }} ····{{ $pago->tarjeta_ultimos4 }}
                                @elseif ($pago->correo_pagador)
                                    {{ $pago->correo_pagador }}
                                @else
                                    {{ $pago->mensaje }}
                                @endif
                            </td>
                            <td class="text-end">{{ $pago->moneda }} {{ number_format((float) $pago->monto, 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin transacciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
