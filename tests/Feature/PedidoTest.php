<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historial de pedidos, detalle y descarga de la factura en PDF.
 */
class PedidoTest extends TestCase
{
    use RefreshDatabase;

    /** Crea un pedido con su detalle y su factura. */
    private function pedidoDe(User $usuario, array $atributos = []): Order
    {
        $pedido = Order::factory()->create(array_merge([
            'user_id'      => $usuario->id,
            'subtotal'     => 40000,
            'impuesto'     => 5200,
            'envio'        => 2900,
            'total'        => 48100,
            'fecha_compra' => now(),
        ], $atributos));

        $pedido->lineas()->create([
            'nombre_producto' => 'Laptop Lenovo IdeaPad',
            'sku'             => 'LAP-LEN-001',
            'precio_unitario' => 20000,
            'cantidad'        => 2,
            'subtotal'        => 40000,
        ]);

        Invoice::create([
            'order_id'       => $pedido->id,
            'user_id'        => $usuario->id,
            'numero_factura' => Invoice::siguienteNumero(),
            'fecha_emision'  => now(),
            'cliente_nombre' => $usuario->name,
            'cliente_correo' => $usuario->email,
            'cliente_cedula' => $usuario->cedula,
            'subtotal'       => $pedido->subtotal,
            'impuesto'       => $pedido->impuesto,
            'envio'          => $pedido->envio,
            'total'          => $pedido->total,
        ]);

        return $pedido->fresh(['lineas', 'factura']);
    }

    /* ==================================================================
     | Historial
     | ================================================================ */

    public function test_el_historial_exige_sesion_iniciada(): void
    {
        $this->get(route('pedidos.historial'))->assertRedirect(route('login'));
    }

    public function test_el_historial_muestra_los_pedidos_del_usuario(): void
    {
        $usuario = User::factory()->create();
        $pedido  = $this->pedidoDe($usuario);

        $this->actingAs($usuario)
            ->get(route('pedidos.historial'))
            ->assertOk()
            ->assertSee($pedido->numero_pedido)
            ->assertSee($pedido->numero_seguimiento)
            ->assertSee('₡48 100');
    }

    public function test_el_historial_no_muestra_los_pedidos_de_otras_personas(): void
    {
        $usuario = User::factory()->create();
        $otro    = User::factory()->create();
        $ajeno   = $this->pedidoDe($otro);

        $this->actingAs($usuario)
            ->get(route('pedidos.historial'))
            ->assertOk()
            ->assertDontSee($ajeno->numero_pedido);
    }

    public function test_el_historial_vacio_muestra_un_mensaje(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pedidos.historial'))
            ->assertOk()
            ->assertSee('Todavía no ha realizado compras');
    }

    /* ==================================================================
     | Detalle
     | ================================================================ */

    public function test_muestra_el_detalle_de_un_pedido_propio(): void
    {
        $usuario = User::factory()->create();
        $pedido  = $this->pedidoDe($usuario);

        $this->actingAs($usuario)
            ->get(route('pedidos.detalle', $pedido))
            ->assertOk()
            ->assertSee($pedido->numero_pedido)
            ->assertSee('Laptop Lenovo IdeaPad')
            ->assertSee('LAP-LEN-001')
            ->assertSee($pedido->envio_direccion);
    }

    public function test_no_permite_ver_el_pedido_de_otro_usuario(): void
    {
        $atacante = User::factory()->create();
        $victima  = User::factory()->create();
        $pedido   = $this->pedidoDe($victima);

        $this->actingAs($atacante)
            ->get(route('pedidos.detalle', $pedido))
            ->assertForbidden();
    }

    public function test_el_administrador_puede_ver_cualquier_pedido(): void
    {
        $admin   = User::factory()->administrador()->create();
        $cliente = User::factory()->create();
        $pedido  = $this->pedidoDe($cliente);

        $this->actingAs($admin)
            ->get(route('pedidos.detalle', $pedido))
            ->assertOk();
    }

    public function test_no_permite_ver_la_confirmacion_de_un_pedido_ajeno(): void
    {
        $atacante = User::factory()->create();
        $pedido   = $this->pedidoDe(User::factory()->create());

        $this->actingAs($atacante)
            ->get(route('pedidos.confirmacion', $pedido->numero_pedido))
            ->assertForbidden();
    }

    public function test_un_numero_de_pedido_inexistente_devuelve_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('pedidos.confirmacion', 'PED-9999-999999'))
            ->assertNotFound();
    }

    /* ==================================================================
     | Factura en PDF
     | ================================================================ */

    public function test_descarga_la_factura_en_pdf(): void
    {
        $usuario = User::factory()->create(['cedula' => '1-1234-5678']);
        $pedido  = $this->pedidoDe($usuario);

        $respuesta = $this->actingAs($usuario)->get(route('pedidos.factura', $pedido));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload($pedido->factura->numero_factura.'.pdf');

        // Comprobación de que realmente es un PDF válido.
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_no_permite_descargar_la_factura_de_otro_usuario(): void
    {
        $atacante = User::factory()->create();
        $pedido   = $this->pedidoDe(User::factory()->create());

        $this->actingAs($atacante)
            ->get(route('pedidos.factura', $pedido))
            ->assertForbidden();
    }

    public function test_un_pedido_sin_factura_no_tiene_pdf(): void
    {
        $usuario = User::factory()->create();
        $pedido  = Order::factory()->pendiente()->create(['user_id' => $usuario->id]);

        $this->actingAs($usuario)
            ->get(route('pedidos.factura', $pedido))
            ->assertNotFound();
    }
}
