<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\TotalesCarrito;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Historial de ventas de demostración.
 *
 * Genera pedidos repartidos en los últimos 12 meses para que los reportes de
 * ventas por mes y por cliente, y el panel de administración, tengan datos
 * reales que mostrar desde la primera ejecución.
 */
class VentasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = User::where('es_admin', false)->get();
        $productos = Product::where('existencias', '>', 0)->get();

        if ($clientes->isEmpty() || $productos->isEmpty()) {
            return;
        }

        // Semilla fija para que el historial de demostración sea reproducible.
        mt_srand(2026);

        $metodos = ['tarjeta', 'paypal', 'sinpe'];
        $consecutivoPedido = 1;
        $consecutivoFactura = 1;

        for ($mesesAtras = 11; $mesesAtras >= 0; $mesesAtras--) {
            $mes = now()->subMonths($mesesAtras);
            $pedidosDelMes = mt_rand(3, 7);

            // En el mes en curso solo se sortean días que ya transcurrieron;
            // si no, al ejecutar el seeder a inicio de mes casi todas las
            // fechas caerían en el futuro y el panel mostraría ₡0 de ventas.
            $ultimoDia = $mesesAtras === 0
                ? now()->day
                : $mes->copy()->endOfMonth()->day;

            for ($i = 0; $i < $pedidosDelMes; $i++) {
                $fecha = Carbon::create(
                    $mes->year,
                    $mes->month,
                    mt_rand(1, $ultimoDia),
                    mt_rand(8, 20),
                    mt_rand(0, 59)
                );

                // Red de seguridad: nunca se registra una venta futura.
                if ($fecha->isFuture()) {
                    $fecha = now()->subMinutes(mt_rand(10, 300));
                }

                $cliente = $clientes->random();
                $seleccion = $productos->random(mt_rand(1, 3));

                // Se calcula el subtotal con los mismos precios del catálogo.
                $lineas = [];
                $subtotal = 0.0;

                foreach ($seleccion as $producto) {
                    $cantidad = mt_rand(1, 2);
                    $importe = round((float) $producto->precio * $cantidad, 2);
                    $subtotal += $importe;

                    $lineas[] = [
                        'product_id'      => $producto->id,
                        'nombre_producto' => $producto->nombre,
                        'sku'             => $producto->sku,
                        'precio_unitario' => $producto->precio,
                        'cantidad'        => $cantidad,
                        'subtotal'        => $importe,
                    ];
                }

                $totales = TotalesCarrito::calcular(
                    $subtotal,
                    (int) collect($lineas)->sum('cantidad')
                );

                $metodo = $metodos[array_rand($metodos)];

                $pedido = Order::create([
                    'user_id'            => $cliente->id,
                    'numero_pedido'      => sprintf('PED-%s-%06d', $fecha->year, $consecutivoPedido++),
                    'numero_seguimiento' => 'TS'.$fecha->format('ymd').strtoupper(Str::random(6)),
                    'estado'             => $mesesAtras === 0 ? 'pagado' : 'entregado',
                    'subtotal'           => $totales->subtotal,
                    'impuesto'           => $totales->impuesto,
                    'envio'              => $totales->envio,
                    'total'              => $totales->total,
                    'tasa_impuesto'      => $totales->tasaImpuesto,
                    'metodo_pago'        => $metodo,
                    'estado_pago'        => 'aprobado',
                    'envio_nombre'       => $cliente->name,
                    'envio_telefono'     => (string) $cliente->telefono,
                    'envio_direccion'    => (string) $cliente->direccion,
                    'envio_ciudad'       => (string) $cliente->ciudad,
                    'envio_provincia'    => (string) $cliente->provincia,
                    'fecha_compra'       => $fecha,
                    'created_at'         => $fecha,
                    'updated_at'         => $fecha,
                ]);

                $pedido->lineas()->createMany($lineas);

                $pedido->pagos()->create([
                    'metodo'           => $metodo,
                    'estado'           => 'aprobado',
                    'monto'            => $totales->total,
                    'moneda'           => (string) config('tienda.moneda.codigo', 'CRC'),
                    'id_transaccion'   => strtoupper($metodo === 'paypal' ? 'PAYID-' : 'TRX-').strtoupper(Str::random(12)),
                    'tarjeta_marca'    => $metodo === 'tarjeta' ? 'Visa' : null,
                    'tarjeta_ultimos4' => $metodo === 'tarjeta' ? (string) mt_rand(1000, 9999) : null,
                    'correo_pagador'   => $metodo === 'paypal' ? $cliente->email : null,
                    'mensaje'          => 'Pago aprobado (datos de demostración).',
                    'procesado_en'     => $fecha,
                ]);

                Invoice::create([
                    'order_id'       => $pedido->id,
                    'user_id'        => $cliente->id,
                    'numero_factura' => sprintf('FAC-%s-%06d', $fecha->year, $consecutivoFactura++),
                    'fecha_emision'  => $fecha,
                    'cliente_nombre' => $cliente->name,
                    'cliente_correo' => $cliente->email,
                    'cliente_cedula' => $cliente->cedula,
                    'subtotal'       => $totales->subtotal,
                    'impuesto'       => $totales->impuesto,
                    'envio'          => $totales->envio,
                    'total'          => $totales->total,
                    'moneda'         => (string) config('tienda.moneda.codigo', 'CRC'),
                    'created_at'     => $fecha,
                    'updated_at'     => $fecha,
                ]);
            }
        }
    }
}
