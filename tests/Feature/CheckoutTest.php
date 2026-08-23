<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Proceso de compra: pasarela de pago, factura y número de seguimiento.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tienda.impuesto.tasa'            => 0.13,
            'tienda.envio.costo'              => 2900,
            'tienda.envio.gratis_desde'       => 75000,
            'tienda.pagos.paypal.moneda'      => 'USD',
            'tienda.pagos.paypal.tipo_cambio' => 500,
        ]);

        $this->cliente = User::factory()->create([
            'name'      => 'María Rodríguez',
            'email'     => 'maria@example.com',
            'cedula'    => '1-1234-5678',
            'provincia' => 'San José',
        ]);
    }

    /** Datos base del formulario de compra. */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'envio_nombre'    => 'María Rodríguez',
            'envio_telefono'  => '8811-2233',
            'envio_direccion' => '200 metros norte de la iglesia',
            'envio_ciudad'    => 'Heredia',
            'envio_provincia' => 'Heredia',
            'metodo_pago'     => 'tarjeta',
            'nombre_tarjeta'  => 'MARIA RODRIGUEZ',
            'numero_tarjeta'  => '4111 1111 1111 1111',
            'mes'             => 12,
            'anio'            => now()->year + 2,
            'cvv'             => '123',
        ], $extra);
    }

    /** Deja un producto en el carrito del cliente. */
    private function conCarrito(int $precio = 20000, int $cantidad = 2, int $existencias = 10): Product
    {
        $producto = Product::factory()->conPrecio($precio)->create([
            'existencias' => $existencias,
            'nombre'      => 'Laptop de prueba',
        ]);

        $this->actingAs($this->cliente)
            ->post(route('carrito.agregar', $producto), ['cantidad' => $cantidad]);

        return $producto;
    }

    /* ==================================================================
     | Acceso al checkout
     | ================================================================ */

    public function test_el_checkout_exige_sesion_iniciada(): void
    {
        $this->get(route('checkout.mostrar'))->assertRedirect(route('login'));
    }

    public function test_con_el_carrito_vacio_redirige_al_catalogo(): void
    {
        $this->actingAs($this->cliente)
            ->get(route('checkout.mostrar'))
            ->assertRedirect(route('catalogo.listado'))
            ->assertSessionHas('error');
    }

    public function test_muestra_el_formulario_con_los_metodos_de_pago(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)
            ->get(route('checkout.mostrar'))
            ->assertOk()
            ->assertSee('Tarjeta de crédito / débito')
            ->assertSee('PayPal')
            ->assertSee('SINPE Móvil')
            ->assertSee('₡48 100');   // 40 000 + 5 200 + 2 900
    }

    /* ==================================================================
     | Compra exitosa con tarjeta
     | ================================================================ */

    public function test_completa_la_compra_con_tarjeta(): void
    {
        $producto = $this->conCarrito(precio: 20000, cantidad: 2);

        $respuesta = $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::first();

        $this->assertNotNull($pedido, 'Debió crearse el pedido.');
        $respuesta->assertRedirect(route('pedidos.confirmacion', $pedido->numero_pedido));

        // La tabla de compra incluye usuario, fecha y monto.
        $this->assertSame($this->cliente->id, $pedido->user_id);
        $this->assertNotNull($pedido->fecha_compra);
        $this->assertSame('40000.00', $pedido->subtotal);
        $this->assertSame('5200.00', $pedido->impuesto);
        $this->assertSame('2900.00', $pedido->envio);
        $this->assertSame('48100.00', $pedido->total);
        $this->assertSame('pagado', $pedido->estado);
        $this->assertSame('aprobado', $pedido->estado_pago);

        // Número de seguimiento generado.
        $this->assertNotEmpty($pedido->numero_seguimiento);
        $this->assertStringStartsWith('TS', $pedido->numero_seguimiento);

        // Detalle del pedido.
        $this->assertDatabaseHas('order_items', [
            'order_id'        => $pedido->id,
            'product_id'      => $producto->id,
            'nombre_producto' => 'Laptop de prueba',
            'cantidad'        => 2,
            'subtotal'        => 40000,
        ]);
    }

    public function test_emite_la_factura_al_pagar(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::firstOrFail();

        $this->assertNotNull($pedido->factura);
        $this->assertSame($this->cliente->id, $pedido->factura->user_id);
        $this->assertSame('María Rodríguez', $pedido->factura->cliente_nombre);
        $this->assertSame('1-1234-5678', $pedido->factura->cliente_cedula);
        $this->assertSame($pedido->total, $pedido->factura->total);
        $this->assertStringStartsWith('FAC-', $pedido->factura->numero_factura);
    }

    public function test_descuenta_las_existencias_del_producto(): void
    {
        $producto = $this->conCarrito(precio: 20000, cantidad: 3, existencias: 10);

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $this->assertSame(7, $producto->fresh()->existencias);
    }

    public function test_vacia_el_carrito_despues_de_comprar(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_registra_el_pago_sin_guardar_datos_sensibles(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pago = Order::firstOrFail()->pago;

        $this->assertSame('aprobado', $pago->estado);
        $this->assertSame('Visa', $pago->tarjeta_marca);
        $this->assertSame('1111', $pago->tarjeta_ultimos4);

        // El número completo y el CVV no están en la base de datos.
        $this->assertDatabaseMissing('payments', ['id_transaccion' => '4111111111111111']);
        $this->assertStringNotContainsString('4111111111111111', json_encode($pago->toArray()));
    }

    public function test_la_confirmacion_muestra_el_seguimiento_y_el_monto(): void
    {
        $this->conCarrito();
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::firstOrFail();

        $this->actingAs($this->cliente)
            ->get(route('pedidos.confirmacion', $pedido->numero_pedido))
            ->assertOk()
            ->assertSee('¡Gracias por su compra')
            ->assertSee($pedido->numero_pedido)
            ->assertSee($pedido->numero_seguimiento)
            ->assertSee('₡48 100');
    }

    /* ==================================================================
     | Otros métodos de pago
     | ================================================================ */

    public function test_completa_la_compra_con_paypal(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'metodo_pago'    => 'paypal',
            'correo_paypal'  => 'maria@example.com',
            'numero_tarjeta' => null,
            'cvv'            => null,
            'mes'            => null,
            'anio'           => null,
        ]));

        $pedido = Order::firstOrFail();

        $this->assertSame('paypal', $pedido->metodo_pago);
        $this->assertSame('aprobado', $pedido->estado_pago);
        // El pedido queda en colones y el cobro de PayPal en dólares.
        $this->assertSame('48100.00', $pedido->total);
        $this->assertSame('USD', $pedido->pago->moneda);
        $this->assertSame('96.20', $pedido->pago->monto);   // 48 100 / 500
        $this->assertSame('maria@example.com', $pedido->pago->correo_pagador);
    }

    public function test_completa_la_compra_con_sinpe_movil(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'metodo_pago'       => 'sinpe',
            'comprobante_sinpe' => '987654321',
            'numero_tarjeta'    => null,
            'cvv'               => null,
            'mes'               => null,
            'anio'              => null,
        ]));

        $pedido = Order::firstOrFail();

        $this->assertSame('sinpe', $pedido->metodo_pago);
        $this->assertSame('aprobado', $pedido->estado_pago);
        $this->assertStringContainsString('987654321', $pedido->pago->id_transaccion);
    }

    /* ==================================================================
     | Pago rechazado: la compra se revierte por completo
     | ================================================================ */

    public function test_si_la_tarjeta_es_rechazada_no_queda_ningun_pedido(): void
    {
        $producto = $this->conCarrito(precio: 20000, cantidad: 2, existencias: 10);

        $respuesta = $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'numero_tarjeta' => '4000 0000 0000 0002',   // tarjeta de prueba rechazada
        ]));

        $respuesta->assertRedirect()->assertSessionHas('error');

        // Todo se revirtió: ni pedido, ni detalle, ni factura, ni inventario.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(10, $producto->fresh()->existencias);

        // Y el carrito sigue disponible para reintentar.
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_el_rechazo_queda_registrado_en_el_log(): void
    {
        $this->conCarrito(precio: 20000, cantidad: 2);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $mensaje, array $contexto) {
                return $mensaje === 'Pago rechazado en el checkout'
                    && $contexto['metodo'] === 'tarjeta'
                    && $contexto['motivo'] === 'Tarjeta declinada por el banco emisor.'
                    // Del rechazo no se registra ningún dato de la tarjeta.
                    && ! array_intersect_key($contexto, array_flip(['numero', 'cvv', 'tarjeta_ultimos4']));
            });

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'numero_tarjeta' => '4000 0000 0000 0002',
        ]))->assertSessionHas('error');
    }

    public function test_si_paypal_rechaza_el_pago_no_queda_pedido(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'metodo_pago'    => 'paypal',
            'correo_paypal'  => 'rechazado@example.com',
            'numero_tarjeta' => null,
            'cvv'            => null,
            'mes'            => null,
            'anio'           => null,
        ]))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_no_devuelve_los_datos_de_la_tarjeta_al_formulario_tras_un_rechazo(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'numero_tarjeta' => '4000 0000 0000 0002',
        ]));

        // Requisito de seguridad: los datos sensibles no se guardan en la
        // sesión (old input) para repoblar el formulario.
        $this->assertNull(session('_old_input.numero_tarjeta'));
        $this->assertNull(session('_old_input.cvv'));
        // Los datos de envío sí se conservan, para no hacer escribir de nuevo.
        $this->assertSame('Heredia', session('_old_input.envio_ciudad'));
    }

    /* ==================================================================
     | Validaciones y control de inventario
     | ================================================================ */

    public function test_valida_los_datos_de_envio(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'envio_nombre'    => '',
            'envio_telefono'  => '123',
            'envio_direccion' => 'corta',
            'envio_provincia' => 'Provincia Inventada',
        ]))->assertSessionHasErrors([
            'envio_nombre', 'envio_telefono', 'envio_direccion', 'envio_provincia',
        ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_exige_los_datos_de_la_tarjeta_si_ese_es_el_metodo(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'nombre_tarjeta' => null,
            'numero_tarjeta' => null,
            'mes'            => null,
            'anio'           => null,
            'cvv'            => null,
        ]))->assertSessionHasErrors(['nombre_tarjeta', 'numero_tarjeta', 'mes', 'anio', 'cvv']);
    }

    public function test_exige_el_correo_solo_cuando_se_paga_con_paypal(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'metodo_pago'   => 'paypal',
            'correo_paypal' => null,
        ]))->assertSessionHasErrors('correo_paypal');
    }

    public function test_rechaza_un_metodo_de_pago_inexistente(): void
    {
        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'metodo_pago' => 'efectivo',
        ]))->assertSessionHasErrors('metodo_pago');
    }

    public function test_no_permite_comprar_mas_unidades_que_las_existentes(): void
    {
        $producto = $this->conCarrito(precio: 20000, cantidad: 5, existencias: 10);

        // El inventario baja después de que el producto ya estaba en el carrito.
        $producto->update(['existencias' => 2]);

        $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $producto->fresh()->existencias);
    }

    public function test_no_permite_comprar_un_producto_desactivado(): void
    {
        $producto = $this->conCarrito();
        $producto->update(['activo' => false]);

        $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_el_total_se_calcula_en_el_servidor_y_no_se_puede_alterar(): void
    {
        $this->conCarrito(precio: 20000, cantidad: 2);

        // El atacante intenta enviar su propio total.
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'total'    => 1,
            'subtotal' => 1,
            'impuesto' => 0,
            'envio'    => 0,
        ]));

        $pedido = Order::firstOrFail();

        $this->assertSame('48100.00', $pedido->total);
        $this->assertSame('40000.00', $pedido->subtotal);
    }

    public function test_el_precio_usado_es_el_del_catalogo_no_el_del_carrito(): void
    {
        $this->conCarrito(precio: 20000, cantidad: 1);

        // Alguien manipula el precio guardado en la línea del carrito.
        CartItem::query()->update(['precio_unitario' => 1]);

        // La compra se detiene: el carrito estaba mostrando un precio que no
        // corresponde al del catálogo.
        $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos())
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());

        // La línea quedó corregida con el precio real del catálogo, así que al
        // reintentar se cobra ese precio y nunca el manipulado.
        $this->assertSame('20000.00', CartItem::firstOrFail()->precio_unitario);

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $this->assertSame('20000.00', Order::firstOrFail()->subtotal);
    }

    /* ==================================================================
     | Cambios de precio con el carrito abierto
     | ================================================================ */

    public function test_no_cobra_un_total_distinto_del_que_muestra_el_carrito(): void
    {
        $producto = $this->conCarrito(precio: 100000, cantidad: 1);

        $totalMostrado = app(CarritoService::class)->totales()->total;

        // El administrador sube el precio mientras el carrito sigue abierto.
        $producto->update(['precio' => 200000]);

        $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos())
            ->assertSessionHas('error');

        // Antes de este arreglo se creaba el pedido cobrando el doble de lo
        // que el cliente vio en pantalla.
        $this->assertSame(0, Order::count());
        $this->assertSame(113000.0, $totalMostrado);
    }

    public function test_tras_el_aviso_la_compra_se_completa_con_el_precio_nuevo(): void
    {
        $producto = $this->conCarrito(precio: 100000, cantidad: 1);

        $producto->update(['precio' => 200000]);

        // Primer intento: se detiene y el carrito se pone al día.
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        // El carrito ya muestra el total nuevo, que es el que se cobrará.
        $this->assertSame(226000.0, app(CarritoService::class)->totales()->total);

        // Segundo intento: el cliente confirma el total que está viendo.
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::firstOrFail();

        $this->assertSame('200000.00', $pedido->subtotal);
        $this->assertSame('226000.00', $pedido->total);
    }

    public function test_la_pantalla_del_carrito_avisa_cuando_un_precio_cambio(): void
    {
        $producto = $this->conCarrito(precio: 100000, cantidad: 1);

        $producto->update(['precio' => 200000]);

        $this->actingAs($this->cliente)
            ->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('cambió desde que lo agregó', false);

        // El precio de la línea quedó al día tras visitar el carrito.
        $this->assertSame('200000.00', CartItem::firstOrFail()->precio_unitario);
    }
}
