<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Services\CarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Pruebas del servicio del carrito: agregar, actualizar, eliminar y totales.
 */
class CarritoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarritoService $carrito;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tienda.impuesto.tasa'      => 0.13,
            'tienda.envio.costo'        => 2900,
            'tienda.envio.gratis_desde' => 75000,
        ]);

        $this->carrito = app(CarritoService::class);
    }

    public function test_agrega_un_producto_al_carrito(): void
    {
        $producto = Product::factory()->conPrecio(10000)->create(['existencias' => 10]);

        $linea = $this->carrito->agregar($producto, 2);

        $this->assertSame(2, $linea->cantidad);
        $this->assertSame('10000.00', $linea->precio_unitario);
        $this->assertSame(2, $this->carrito->cantidadArticulos());
    }

    public function test_sumar_el_mismo_producto_actualiza_la_cantidad_sin_duplicar_la_linea(): void
    {
        $producto = Product::factory()->conPrecio(5000)->create(['existencias' => 10]);

        $this->carrito->agregar($producto, 2);
        $this->carrito->agregar($producto, 3);

        $this->assertCount(1, $this->carrito->lineas());
        $this->assertSame(5, $this->carrito->cantidadArticulos());
    }

    public function test_no_permite_agregar_mas_unidades_que_las_existencias(): void
    {
        $producto = Product::factory()->create(['existencias' => 3, 'nombre' => 'Teclado X']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Solo quedan 3 unidades');

        $this->carrito->agregar($producto, 5);
    }

    public function test_limita_la_cantidad_maxima_por_linea(): void
    {
        $producto = Product::factory()->create(['existencias' => 500]);

        $linea = $this->carrito->agregar($producto, 999);

        $this->assertSame(CarritoService::CANTIDAD_MAXIMA, $linea->cantidad);
    }

    public function test_actualiza_la_cantidad_de_una_linea(): void
    {
        $producto = Product::factory()->conPrecio(8000)->create(['existencias' => 10]);
        $linea = $this->carrito->agregar($producto, 1);

        $this->carrito->actualizar($linea->id, 4);

        $this->assertSame(4, $this->carrito->cantidadArticulos());
    }

    public function test_actualizar_a_cero_elimina_la_linea(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $linea = $this->carrito->agregar($producto, 2);

        $resultado = $this->carrito->actualizar($linea->id, 0);

        $this->assertNull($resultado);
        $this->assertTrue($this->carrito->lineas()->isEmpty());
    }

    public function test_elimina_una_linea_del_carrito(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $linea = $this->carrito->agregar($producto, 1);

        $this->carrito->eliminar($linea->id);

        $this->assertSame(0, $this->carrito->cantidadArticulos());
    }

    public function test_vacia_el_carrito_completo(): void
    {
        Product::factory()->count(3)->create(['existencias' => 10])
            ->each(fn (Product $p) => $this->carrito->agregar($p, 2));

        $this->carrito->vaciar();

        $this->assertSame(0, $this->carrito->cantidadArticulos());
    }

    public function test_no_permite_tocar_una_linea_de_otro_carrito(): void
    {
        // Línea que pertenece a un carrito ajeno.
        $otroCarrito = Cart::create(['token_sesion' => 'token-de-otra-persona']);
        $producto = Product::factory()->create(['existencias' => 10]);
        $lineaAjena = $otroCarrito->lineas()->create([
            'product_id'      => $producto->id,
            'cantidad'        => 1,
            'precio_unitario' => $producto->precio,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no está en su carrito');

        $this->carrito->eliminar($lineaAjena->id);
    }

    public function test_calcula_los_totales_del_carrito(): void
    {
        $uno = Product::factory()->conPrecio(20000)->create(['existencias' => 10]);
        $dos = Product::factory()->conPrecio(5000)->create(['existencias' => 10]);

        $this->carrito->agregar($uno, 2);   // 40 000
        $this->carrito->agregar($dos, 1);   //  5 000

        $totales = $this->carrito->totales();

        $this->assertSame(45000.0, $totales->subtotal);
        $this->assertSame(5850.0, $totales->impuesto);
        $this->assertSame(2900.0, $totales->envio);
        $this->assertSame(53750.0, $totales->total);
        $this->assertSame(3, $totales->cantidadArticulos);
    }

    public function test_al_iniciar_sesion_el_carrito_anonimo_pasa_al_del_usuario(): void
    {
        $producto = Product::factory()->conPrecio(9000)->create(['existencias' => 10]);

        // Compra como visitante anónimo.
        $this->carrito->agregar($producto, 2);
        $this->assertSame(2, $this->carrito->cantidadArticulos());

        // Se guarda el token antes de adoptar, porque adoptarCarrito lo borra
        // de la sesión al terminar.
        $token = session()->get('carrito_token');
        $this->assertDatabaseHas('carts', ['token_sesion' => $token]);

        // Ahora inicia sesión.
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->carrito->adoptarCarrito($usuario->id);

        $this->assertSame(2, $this->carrito->cantidadArticulos());
        $this->assertDatabaseHas('carts', ['user_id' => $usuario->id]);
        // El carrito anónimo ya no existe: se trasladó al del usuario.
        $this->assertDatabaseMissing('carts', ['token_sesion' => $token]);
        $this->assertSame(1, Cart::count());
    }

    public function test_al_adoptar_el_carrito_se_conserva_la_cantidad_mayor(): void
    {
        $producto = Product::factory()->create(['existencias' => 20]);
        $usuario  = User::factory()->create();

        // El usuario ya tenía 1 unidad guardada de una sesión anterior.
        Cart::create(['user_id' => $usuario->id])->lineas()->create([
            'product_id'      => $producto->id,
            'cantidad'        => 1,
            'precio_unitario' => $producto->precio,
        ]);

        // Como visitante agregó 4 unidades del mismo producto.
        $this->carrito->agregar($producto, 4);

        $this->actingAs($usuario);
        $this->carrito->adoptarCarrito($usuario->id);

        $this->assertSame(4, $this->carrito->cantidadArticulos());
    }
}
