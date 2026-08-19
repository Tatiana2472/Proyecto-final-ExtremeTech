<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Carrito de compras por HTTP: agregar, actualizar, eliminar y vaciar.
 */
class CarritoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tienda.impuesto.tasa'      => 0.13,
            'tienda.envio.costo'        => 2900,
            'tienda.envio.gratis_desde' => 75000,
        ]);
    }

    public function test_el_carrito_vacio_muestra_un_mensaje(): void
    {
        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Su carrito está vacío');
    }

    public function test_agrega_un_producto_al_carrito(): void
    {
        $producto = Product::factory()->create(['existencias' => 10, 'nombre' => 'Mouse gamer']);

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'cantidad'   => 2,
        ]);

        $this->get(route('carrito.mostrar'))->assertSee('Mouse gamer');
    }

    public function test_agrega_al_carrito_por_ajax_y_recibe_json(): void
    {
        $producto = Product::factory()->conPrecio(10000)->create(['existencias' => 10]);

        $respuesta = $this->postJson(route('carrito.agregar', $producto), ['cantidad' => 3]);

        $respuesta->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('totales.subtotal', 30000)
            ->assertJsonPath('totales.impuesto', 3900)
            ->assertJsonPath('totales.total', 36800)   // 30000 + 3900 + 2900
            ->assertJsonPath('totales.cantidad_articulos', 3);
    }

    public function test_el_contador_del_encabezado_responde_en_json(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 4]);

        $this->getJson(route('carrito.contador'))
            ->assertOk()
            ->assertJson(['cantidad' => 4]);
    }

    public function test_no_permite_agregar_mas_de_las_existencias(): void
    {
        $producto = Product::factory()->create(['existencias' => 2, 'nombre' => 'Silla gamer']);

        $this->postJson(route('carrito.agregar', $producto), ['cantidad' => 5])
            ->assertStatus(422)
            ->assertJsonPath('exito', false);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_valida_la_cantidad_enviada(): void
    {
        $producto = Product::factory()->create(['existencias' => 50]);

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 0])
            ->assertSessionHasErrors('cantidad');

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 999])
            ->assertSessionHasErrors('cantidad');

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 'muchos'])
            ->assertSessionHasErrors('cantidad');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_no_permite_agregar_un_producto_inactivo(): void
    {
        $producto = Product::factory()->inactivo()->create(['existencias' => 10]);

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1])
            ->assertNotFound();
    }

    public function test_actualiza_la_cantidad_de_una_linea(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $linea = Cart::first()->lineas()->first();

        $this->put(route('carrito.actualizar', $linea->id), ['cantidad' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['id' => $linea->id, 'cantidad' => 5]);
    }

    public function test_actualizar_a_cero_quita_el_producto(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 2]);

        $linea = Cart::first()->lineas()->first();

        $this->put(route('carrito.actualizar', $linea->id), ['cantidad' => 0]);

        $this->assertDatabaseMissing('cart_items', ['id' => $linea->id]);
    }

    public function test_elimina_una_linea_del_carrito(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $linea = Cart::first()->lineas()->first();

        $this->delete(route('carrito.eliminar', $linea->id))->assertRedirect();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_vacia_el_carrito(): void
    {
        Product::factory()->count(3)->create(['existencias' => 10])
            ->each(fn ($p) => $this->post(route('carrito.agregar', $p), ['cantidad' => 1]));

        $this->assertDatabaseCount('cart_items', 3);

        $this->delete(route('carrito.vaciar'))->assertRedirect();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_el_carrito_muestra_el_desglose_del_total(): void
    {
        $producto = Product::factory()->conPrecio(20000)->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 2]);

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('₡40 000')          // subtotal
            ->assertSee('₡5 200')           // IVA 13%
            ->assertSee('₡2 900')           // envío
            ->assertSee('₡48 100');         // total
    }

    public function test_avisa_cuando_el_envio_pasa_a_ser_gratis(): void
    {
        $producto = Product::factory()->conPrecio(80000)->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Gratis');
    }

    public function test_un_usuario_no_puede_modificar_el_carrito_de_otro(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);

        // Carrito de la víctima.
        $victima = User::factory()->create();
        $carritoVictima = Cart::create(['user_id' => $victima->id]);
        $lineaVictima = $carritoVictima->lineas()->create([
            'product_id'      => $producto->id,
            'cantidad'        => 1,
            'precio_unitario' => $producto->precio,
        ]);

        // El atacante intenta borrar y modificar esa línea usando su id.
        $atacante = User::factory()->create();

        $this->actingAs($atacante)
            ->delete(route('carrito.eliminar', $lineaVictima->id))
            ->assertRedirect();

        $this->actingAs($atacante)
            ->put(route('carrito.actualizar', $lineaVictima->id), ['cantidad' => 20])
            ->assertRedirect();

        // La línea de la víctima sigue intacta.
        $this->assertDatabaseHas('cart_items', [
            'id'       => $lineaVictima->id,
            'cantidad' => 1,
        ]);
    }

    public function test_el_carrito_conserva_el_precio_del_momento_en_que_se_agrego(): void
    {
        $producto = Product::factory()->conPrecio(10000)->create(['existencias' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        // El administrador cambia el precio después.
        $producto->update(['precio' => 25000]);

        $this->assertDatabaseHas('cart_items', [
            'product_id'      => $producto->id,
            'precio_unitario' => '10000.00',
        ]);
    }
}
