<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comando «carritos:limpiar»: recoge los carritos de visitantes anónimos que
 * quedaron abandonados, sin tocar los que pertenecen a un cliente.
 */
class LimpiarCarritosTest extends TestCase
{
    use RefreshDatabase;

    /** Crea un carrito y le fuerza la fecha de última actividad. */
    private function carrito(array $atributos, int $diasSinActividad = 0): Cart
    {
        $carrito = Cart::create($atributos);

        if ($diasSinActividad > 0) {
            $carrito->forceFill(['updated_at' => now()->subDays($diasSinActividad)])->save();
        }

        return $carrito;
    }

    public function test_borra_los_carritos_anonimos_abandonados(): void
    {
        $this->carrito(['token_sesion' => 'viejo'], diasSinActividad: 60);

        $this->artisan('carritos:limpiar')->assertSuccessful();

        $this->assertSame(0, Cart::count());
    }

    public function test_conserva_los_carritos_anonimos_recientes(): void
    {
        $this->carrito(['token_sesion' => 'reciente'], diasSinActividad: 3);

        $this->artisan('carritos:limpiar')->assertSuccessful();

        $this->assertSame(1, Cart::count());
    }

    public function test_nunca_borra_el_carrito_de_un_cliente(): void
    {
        $usuario = User::factory()->create();

        // Aunque lleve meses sin tocarlo: es del cliente y debe encontrarlo.
        $this->carrito(['user_id' => $usuario->id], diasSinActividad: 120);

        $this->artisan('carritos:limpiar')->assertSuccessful();

        $this->assertSame(1, Cart::whereNotNull('user_id')->count());
    }

    public function test_se_lleva_tambien_las_lineas_del_carrito(): void
    {
        $carrito  = $this->carrito(['token_sesion' => 'viejo'], diasSinActividad: 60);
        $producto = Product::factory()->create();

        $carrito->lineas()->create([
            'product_id'      => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 15000,
        ]);

        $this->artisan('carritos:limpiar')->assertSuccessful();

        // La llave foránea de cart_items está declarada con cascadeOnDelete.
        $this->assertSame(0, CartItem::count());
    }

    public function test_la_opcion_dias_cambia_el_corte(): void
    {
        $this->carrito(['token_sesion' => 'de-diez-dias'], diasSinActividad: 10);

        // Con el corte por omisión (30 días) todavía no se borra.
        $this->artisan('carritos:limpiar')->assertSuccessful();
        $this->assertSame(1, Cart::count());

        $this->artisan('carritos:limpiar', ['--dias' => 7])->assertSuccessful();
        $this->assertSame(0, Cart::count());
    }

    public function test_la_opcion_simular_no_borra_nada(): void
    {
        $this->carrito(['token_sesion' => 'viejo'], diasSinActividad: 60);

        $this->artisan('carritos:limpiar', ['--simular' => true])->assertSuccessful();

        $this->assertSame(1, Cart::count());
    }
}
