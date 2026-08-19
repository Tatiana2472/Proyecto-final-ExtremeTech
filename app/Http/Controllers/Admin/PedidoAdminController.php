<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de pedidos desde el panel de administración.
 */
class PedidoAdminController extends Controller
{
    public function index(Request $peticion): View
    {
        $filtros = $peticion->validate([
            'estado' => ['nullable', Rule::in(['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'])],
            'q'      => ['nullable', 'string', 'max:60'],
        ]);

        $pedidos = Order::with('usuario')
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['q'] ?? null, function ($q, $termino) {
                $patron = '%'.$termino.'%';
                $q->where(function ($sub) use ($patron) {
                    $sub->where('numero_pedido', 'like', $patron)
                        ->orWhere('numero_seguimiento', 'like', $patron);
                });
            })
            ->latest('fecha_compra')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pedidos.index', [
            'pedidos' => $pedidos,
            'filtros' => $filtros,
        ]);
    }

    public function detalle(Order $pedido): View
    {
        return view('admin.pedidos.detalle', [
            'pedido' => $pedido->load('lineas', 'usuario', 'factura', 'pagos'),
        ]);
    }

    /** Cambia el estado del pedido (por ejemplo, de pagado a enviado). */
    public function cambiarEstado(Request $peticion, Order $pedido): RedirectResponse
    {
        $datos = $peticion->validate([
            'estado' => ['required', Rule::in(['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'])],
        ]);

        $pedido->update(['estado' => $datos['estado']]);

        return back()->with('exito', "El pedido {$pedido->numero_pedido} pasó a «{$pedido->etiquetaEstado()}».");
    }
}
