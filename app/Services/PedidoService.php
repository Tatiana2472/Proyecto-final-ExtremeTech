<?php

namespace App\Services;

use App\Exceptions\PagoRechazadoException;
use App\Models\CartItem;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Pagos\GestorPasarelas;
use App\Services\Pagos\SolicitudPago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Proceso de compra: convierte el carrito en un pedido, cobra con la pasarela
 * seleccionada y emite la factura.
 *
 * Todo ocurre dentro de una transacción de base de datos: si el pago se
 * rechaza o algo falla, no queda ningún registro inconsistente.
 */
class PedidoService
{
    public function __construct(
        protected CarritoService $carrito,
        protected GestorPasarelas $pasarelas,
    ) {
    }

    /**
     * @param  array<string, mixed>  $datosEnvio  nombre, telefono, direccion, ciudad, provincia, notas
     * @param  array<string, mixed>  $datosPago   Datos propios del método de pago
     *
     * @throws RuntimeException          si el carrito está vacío o falta inventario
     * @throws PagoRechazadoException    si la pasarela rechaza el cobro
     */
    public function procesarCompra(User $usuario, array $datosEnvio, string $metodoPago, array $datosPago): Order
    {
        $lineas = $this->carrito->lineas();

        if ($lineas->isEmpty()) {
            throw new RuntimeException('Su carrito está vacío.');
        }

        // Si el catálogo cambió de precio mientras el carrito estaba abierto,
        // se actualiza el carrito y se detiene la compra. El cobro se calcula
        // siempre con el precio actual (más abajo), así que continuar sin
        // avisar significaría cobrarle al cliente un total distinto del que
        // vio en pantalla.
        $reprecificadas = $this->carrito->sincronizarPrecios();

        if ($reprecificadas->isNotEmpty()) {
            throw new RuntimeException($this->mensajeCambioDePrecio($reprecificadas));
        }

        // La pasarela se resuelve antes de abrir la transacción para que un
        // método inválido falle de inmediato.
        $pasarela = $this->pasarelas->obtener($metodoPago);

        return DB::transaction(function () use ($usuario, $lineas, $datosEnvio, $metodoPago, $datosPago, $pasarela) {

            // 1. Se vuelven a leer los productos con bloqueo para validar el
            //    inventario y el precio contra la base de datos. Nunca se
            //    confía en los montos que venga del navegador.
            $productos = Product::whereIn('id', $lineas->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lineas as $linea) {
                $producto = $productos->get($linea->product_id);

                if (! $producto || ! $producto->activo) {
                    throw new RuntimeException('Uno de los productos de su carrito ya no está disponible.');
                }

                if (! $producto->hayExistencias($linea->cantidad)) {
                    throw new RuntimeException(
                        "No hay existencias suficientes de «{$producto->nombre}» (quedan {$producto->existencias})."
                    );
                }
            }

            // 2. Cálculo del total en el servidor.
            $subtotal = 0.0;
            foreach ($lineas as $linea) {
                $subtotal += round((float) $productos->get($linea->product_id)->precio * $linea->cantidad, 2);
            }

            $totales = TotalesCarrito::calcular($subtotal, (int) $lineas->sum('cantidad'));

            // 3. Se crea el pedido en estado pendiente.
            $pedido = Order::create([
                'user_id'            => $usuario->id,
                'numero_pedido'      => Order::siguienteNumeroPedido(),
                'numero_seguimiento' => Order::generarNumeroSeguimiento(),
                'estado'             => 'pendiente',
                'subtotal'           => $totales->subtotal,
                'impuesto'           => $totales->impuesto,
                'envio'              => $totales->envio,
                'total'              => $totales->total,
                'tasa_impuesto'      => $totales->tasaImpuesto,
                'metodo_pago'        => $metodoPago,
                'estado_pago'        => 'pendiente',
                'envio_nombre'       => $datosEnvio['nombre'],
                'envio_telefono'     => $datosEnvio['telefono'],
                'envio_direccion'    => $datosEnvio['direccion'],
                'envio_ciudad'       => $datosEnvio['ciudad'],
                'envio_provincia'    => $datosEnvio['provincia'],
                'notas'              => $datosEnvio['notas'] ?? null,
                'fecha_compra'       => now(),
            ]);

            // 4. Detalle del pedido y descuento de inventario.
            foreach ($lineas as $linea) {
                $producto = $productos->get($linea->product_id);

                $pedido->lineas()->create([
                    'product_id'      => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'sku'             => $producto->sku,
                    'precio_unitario' => $producto->precio,
                    'cantidad'        => $linea->cantidad,
                    'subtotal'        => round((float) $producto->precio * $linea->cantidad, 2),
                ]);

                $producto->decrement('existencias', $linea->cantidad);
            }

            // 5. Cobro con la pasarela seleccionada.
            //
            // OJO al integrar una pasarela REAL: este cobro ocurre dentro de la
            // transacción. Con el modo simulado no hay problema, pero contra un
            // banco de verdad la llamada sale por la red y, si el commit fallara
            // después de que el cobro se aprobó, el cliente quedaría pagado y sin
            // pedido. Al cambiar el cuerpo de procesar() por una llamada HTTP hay
            // que sacar el cobro de la transacción: crear el pedido, confirmar,
            // cobrar fuera, y actualizar el estado en una segunda transacción.
            $resultado = $pasarela->procesar(new SolicitudPago(
                monto: $totales->total,
                moneda: (string) config('tienda.moneda.codigo', 'CRC'),
                referencia: $pedido->numero_pedido,
                descripcion: sprintf('Compra en %s', config('tienda.nombre')),
                datos: $datosPago,
            ));

            $pedido->pagos()->create($resultado->paraGuardar());

            if (! $resultado->aprobado) {
                // Revierte el pedido, el detalle y el inventario.
                throw new PagoRechazadoException($resultado);
            }

            // 6. Pedido pagado: se emite la factura.
            $pedido->update([
                'estado'      => 'pagado',
                'estado_pago' => 'aprobado',
            ]);

            $this->emitirFactura($pedido, $usuario, $totales);

            // 7. Se vacía el carrito.
            $this->carrito->vaciar();

            return $pedido->fresh(['lineas', 'factura', 'pago']);
        });
    }

    /**
     * Mensaje que se le muestra al cliente cuando el precio de algo que tenía
     * en el carrito cambió antes de confirmar la compra.
     *
     * @param  Collection<int, CartItem>  $lineas
     */
    protected function mensajeCambioDePrecio(Collection $lineas): string
    {
        $productos = $lineas
            ->map(fn (CartItem $linea) => '«'.$linea->producto->nombre.'»')
            ->implode(', ');

        return sprintf(
            'El precio de %s cambió mientras usted compraba. Su carrito ya muestra el '
            .'precio actualizado: revise el nuevo total y confirme la compra de nuevo.',
            $productos
        );
    }

    /** Crea la factura (comprobante) asociada al pedido pagado. */
    protected function emitirFactura(Order $pedido, User $usuario, TotalesCarrito $totales): Invoice
    {
        return Invoice::create([
            'order_id'        => $pedido->id,
            'user_id'         => $usuario->id,
            'numero_factura'  => Invoice::siguienteNumero(),
            'fecha_emision'   => now(),
            'cliente_nombre'  => $usuario->name,
            'cliente_correo'  => $usuario->email,
            'cliente_cedula'  => $usuario->cedula,
            'subtotal'        => $totales->subtotal,
            'impuesto'        => $totales->impuesto,
            'envio'           => $totales->envio,
            'total'           => $totales->total,
            'moneda'          => (string) config('tienda.moneda.codigo', 'CRC'),
        ]);
    }
}
