<?php

namespace App\Http\Controllers;

use App\Exceptions\PagoRechazadoException;
use App\Http\Requests\CheckoutRequest;
use App\Mail\ConfirmacionPedido;
use App\Models\Order;
use App\Services\CarritoService;
use App\Services\Pagos\GestorPasarelas;
use App\Services\PedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Proceso de compra: formulario de pago y confirmación del pedido.
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected CarritoService $carrito,
        protected PedidoService $pedidos,
        protected GestorPasarelas $pasarelas,
    ) {
    }

    /** Formulario de datos de envío y de pago. */
    public function mostrar(Request $peticion): View|RedirectResponse
    {
        // Antes de pedir los datos de pago se ponen al día los precios, para
        // que el total que el cliente autoriza sea exactamente el que se le
        // va a cobrar.
        $this->carrito->sincronizarPrecios();

        $lineas = $this->carrito->lineas();

        if ($lineas->isEmpty()) {
            return redirect()
                ->route('catalogo.listado')
                ->with('error', 'Su carrito está vacío. Agregue productos antes de continuar.');
        }

        $usuario = $peticion->user();

        return view('checkout.mostrar', [
            'lineas'     => $lineas,
            'totales'    => $this->carrito->totales(),
            'usuario'    => $usuario,
            'metodos'    => $this->metodosHabilitados(),
            'provincias' => ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'],
        ]);
    }

    /**
     * Procesa la compra: crea el pedido, cobra con la pasarela y emite la
     * factura. Todo dentro de una transacción (ver PedidoService).
     */
    public function procesar(CheckoutRequest $peticion): RedirectResponse
    {
        try {
            $pedido = $this->pedidos->procesarCompra(
                usuario: $peticion->user(),
                datosEnvio: $peticion->datosEnvio(),
                metodoPago: $peticion->validated('metodo_pago'),
                datosPago: $peticion->datosPago(),
            );
        } catch (PagoRechazadoException $e) {
            // El pago falló: el pedido se revirtió por completo y con él la
            // fila de payments, así que del intento no queda ningún rastro en
            // la base. Se anota en el log para poder revisar después cuántos
            // pagos se rechazan y por qué. No se registra ningún dato de la
            // tarjeta: ResultadoPago::rechazado() no los lleva.
            Log::warning('Pago rechazado en el checkout', [
                'usuario' => $peticion->user()->id,
                'metodo'  => $e->resultado->metodo,
                'monto'   => $e->resultado->monto,
                'moneda'  => $e->resultado->moneda,
                'motivo'  => $e->resultado->mensaje,
            ]);

            return back()
                ->withInput($peticion->except($this->camposSensibles()))
                ->with('error', 'No se pudo procesar el pago: '.$e->getMessage());
        } catch (RuntimeException $e) {
            return back()
                ->withInput($peticion->except($this->camposSensibles()))
                ->with('error', $e->getMessage());
        }

        $this->enviarConfirmacion($pedido);

        return redirect()
            ->route('pedidos.confirmacion', $pedido->numero_pedido)
            ->with('exito', '¡Su compra se realizó con éxito!');
    }

    /**
     * Envía el correo de confirmación con la factura adjunta.
     *
     * Se hace FUERA de la transacción y dentro de un try/catch: el pago ya se
     * cobró, así que un problema con el servidor de correo no debe hacer
     * fallar la compra ni mostrarle un error al cliente. El fallo queda
     * registrado en el log para revisarlo después.
     */
    private function enviarConfirmacion(Order $pedido): void
    {
        try {
            Mail::to($pedido->usuario->email, $pedido->usuario->name)
                ->send(new ConfirmacionPedido($pedido));
        } catch (Throwable $e) {
            Log::error('No se pudo enviar el correo de confirmación del pedido '.$pedido->numero_pedido, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Datos de tarjeta que nunca se devuelven al formulario ni se guardan en
     * la sesión al ocurrir un error.
     *
     * @return list<string>
     */
    private function camposSensibles(): array
    {
        return ['numero_tarjeta', 'cvv', 'mes', 'anio'];
    }

    /** Métodos de pago habilitados con su etiqueta y descripción. */
    private function metodosHabilitados(): array
    {
        $metodos = [];

        foreach ($this->pasarelas->metodosDisponibles() as $identificador) {
            $metodos[$identificador] = config("tienda.pagos.metodos.{$identificador}");
        }

        return $metodos;
    }
}
