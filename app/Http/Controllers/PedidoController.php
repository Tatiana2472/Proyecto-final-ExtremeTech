<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Pedidos del usuario: historial, detalle, confirmación y factura en PDF.
 */
class PedidoController extends Controller
{
    /** Historial de pedidos del usuario autenticado. */
    public function historial(Request $peticion): View
    {
        return view('pedidos.historial', [
            'pedidos' => $peticion->user()
                ->pedidos()
                ->with('lineas')
                ->paginate(10),
        ]);
    }

    /** Detalle de un pedido propio con sus variables. */
    public function detalle(Request $peticion, Order $pedido): View
    {
        $this->autorizarPedido($peticion, $pedido);

        return view('pedidos.detalle', [
            'pedido' => $pedido->load('lineas.producto', 'factura', 'pago'),
        ]);
    }

    /**
     * Confirmación de la compra, con el detalle y el número de seguimiento.
     * Se busca por número de pedido para no exponer los ids internos.
     */
    public function confirmacion(Request $peticion, string $numeroPedido): View
    {
        $pedido = Order::where('numero_pedido', $numeroPedido)->firstOrFail();

        $this->autorizarPedido($peticion, $pedido);

        return view('pedidos.confirmacion', [
            'pedido' => $pedido->load('lineas', 'factura', 'pago'),
        ]);
    }

    /** Descarga la factura del pedido en PDF. */
    public function facturaPdf(Request $peticion, Order $pedido): Response
    {
        $this->autorizarPedido($peticion, $pedido);

        $pedido->load('lineas', 'factura', 'pago', 'usuario');

        abort_if($pedido->factura === null, 404, 'Este pedido todavía no tiene factura emitida.');

        $pdf = Pdf::loadView('pdf.factura', [
            'pedido'  => $pedido,
            'factura' => $pedido->factura,
        ])->setPaper('letter');

        return $pdf->download("{$pedido->factura->numero_factura}.pdf");
    }

    /**
     * Un usuario solo puede ver sus propios pedidos.
     * El administrador puede ver cualquiera.
     */
    protected function autorizarPedido(Request $peticion, Order $pedido): void
    {
        $usuario = $peticion->user();

        abort_unless(
            $pedido->user_id === $usuario->id || $usuario->esAdministrador(),
            403,
            'No tiene permiso para ver este pedido.'
        );
    }
}
