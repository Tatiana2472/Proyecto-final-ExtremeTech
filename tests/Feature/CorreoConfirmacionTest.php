<?php

namespace Tests\Feature;

use App\Mail\ConfirmacionPedido;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Correo de confirmación de compra con la factura adjunta.
 */
class CorreoConfirmacionTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tienda.impuesto.tasa'      => 0.13,
            'tienda.envio.costo'        => 2900,
            'tienda.envio.gratis_desde' => 75000,
        ]);

        $this->cliente = User::factory()->create([
            'name'  => 'María Rodríguez',
            'email' => 'maria@example.com',
        ]);
    }

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

    private function conCarrito(): Product
    {
        $producto = Product::factory()->conPrecio(20000)->create([
            'existencias' => 10,
            'nombre'      => 'Laptop de prueba',
        ]);

        $this->actingAs($this->cliente)
            ->post(route('carrito.agregar', $producto), ['cantidad' => 2]);

        return $producto;
    }

    public function test_envia_el_correo_de_confirmacion_al_completar_la_compra(): void
    {
        Mail::fake();

        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        Mail::assertSent(ConfirmacionPedido::class, function (ConfirmacionPedido $correo) {
            return $correo->hasTo('maria@example.com')
                && $correo->pedido->numero_pedido === Order::first()->numero_pedido;
        });
    }

    public function test_no_envia_correo_si_el_pago_fue_rechazado(): void
    {
        Mail::fake();

        $this->conCarrito();

        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos([
            'numero_tarjeta' => '4000 0000 0000 0002',
        ]));

        Mail::assertNothingSent();
    }

    public function test_el_correo_incluye_el_pedido_el_seguimiento_y_el_total(): void
    {
        $this->conCarrito();
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::with('lineas', 'factura')->firstOrFail();

        $html = (new ConfirmacionPedido($pedido))->render();

        $this->assertStringContainsString($pedido->numero_pedido, $html);
        $this->assertStringContainsString($pedido->numero_seguimiento, $html);
        $this->assertStringContainsString('Laptop de prueba', $html);
        $this->assertStringContainsString('₡48 100', $html);
        $this->assertStringContainsString('Gracias por su compra', $html);
    }

    public function test_el_correo_adjunta_la_factura_en_pdf(): void
    {
        $this->conCarrito();
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $pedido = Order::with('factura')->firstOrFail();

        $adjuntos = (new ConfirmacionPedido($pedido))->attachments();

        $this->assertCount(1, $adjuntos);
        $this->assertSame($pedido->factura->numero_factura.'.pdf', $adjuntos[0]->as);
        $this->assertSame('application/pdf', $adjuntos[0]->mime);
    }

    public function test_el_correo_no_expone_datos_de_la_tarjeta(): void
    {
        $this->conCarrito();
        $this->actingAs($this->cliente)->post(route('checkout.procesar'), $this->datos());

        $html = (new ConfirmacionPedido(Order::with('lineas')->firstOrFail()))->render();

        $this->assertStringNotContainsString('4111111111111111', $html);
        $this->assertStringNotContainsString('4111 1111 1111 1111', $html);
    }

    public function test_un_fallo_del_servidor_de_correo_no_rompe_la_compra(): void
    {
        // Se simula que el servidor de correo está caído.
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP caído'));

        $this->conCarrito();

        $respuesta = $this->actingAs($this->cliente)
            ->post(route('checkout.procesar'), $this->datos());

        // El cobro ya se hizo: la compra debe completarse igual.
        $pedido = Order::first();

        $this->assertNotNull($pedido, 'La compra debió completarse aunque el correo falle.');
        $respuesta->assertRedirect(route('pedidos.confirmacion', $pedido->numero_pedido));
        $this->assertSame('aprobado', $pedido->estado_pago);
    }
}
