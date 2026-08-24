<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;

/**
 * Lista de deseos: relación MUCHOS A MUCHOS entre usuarios y productos a
 * través de la tabla pivote «favoritos».
 */
class FavoritosTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente  = User::factory()->create();
        $this->producto = Product::factory()->create(['nombre' => 'Monitor de prueba']);
    }

    /* ==================================================================
     | La relación en los dos sentidos
     | ================================================================ */

    public function test_un_usuario_puede_tener_muchos_productos_favoritos(): void
    {
        $productos = Product::factory()->count(3)->create();

        $this->cliente->favoritos()->attach($productos);

        $this->assertCount(3, $this->cliente->favoritos);
    }

    public function test_un_producto_puede_ser_favorito_de_muchos_usuarios(): void
    {
        $usuarios = User::factory()->count(4)->create();

        $this->producto->seguidores()->attach($usuarios);

        // El lado inverso de la misma relación muchos a muchos.
        $this->assertSame(4, $this->producto->seguidores()->count());
    }

    public function test_la_pivote_guarda_las_marcas_de_tiempo(): void
    {
        $this->cliente->favoritos()->attach($this->producto);

        $pivote = $this->cliente->favoritos()->first()->pivot;

        $this->assertNotNull($pivote->created_at);
        $this->assertNotNull($pivote->updated_at);
    }

    public function test_un_producto_no_se_puede_repetir_en_la_lista(): void
    {
        $this->cliente->favoritos()->attach($this->producto);

        // La restricción única de la tabla pivote lo impide a nivel de base.
        $this->expectException(UniqueConstraintViolationException::class);

        $this->cliente->favoritos()->attach($this->producto);
    }

    public function test_al_borrar_el_producto_se_borra_su_fila_en_la_pivote(): void
    {
        $this->cliente->favoritos()->attach($this->producto);

        $this->producto->delete();

        // La llave foránea está declarada con cascadeOnDelete.
        $this->assertDatabaseCount('favoritos', 0);
    }

    /* ==================================================================
     | Marcar y desmarcar desde la tienda
     | ================================================================ */

    public function test_marcar_un_producto_como_favorito(): void
    {
        $this->actingAs($this->cliente)
            ->post(route('favoritos.alternar', $this->producto))
            ->assertRedirect()
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('favoritos', [
            'user_id'    => $this->cliente->id,
            'product_id' => $this->producto->id,
        ]);
    }

    public function test_volver_a_tocar_el_corazon_lo_desmarca(): void
    {
        $this->actingAs($this->cliente)->post(route('favoritos.alternar', $this->producto));
        $this->actingAs($this->cliente)->post(route('favoritos.alternar', $this->producto));

        $this->assertDatabaseCount('favoritos', 0);
    }

    public function test_responde_en_json_cuando_la_peticion_es_asincrona(): void
    {
        $this->actingAs($this->cliente)
            ->postJson(route('favoritos.alternar', $this->producto))
            ->assertOk()
            ->assertJson(['exito' => true, 'marcado' => true, 'cantidad' => 1]);
    }

    public function test_se_puede_quitar_desde_la_pantalla_de_favoritos(): void
    {
        $this->cliente->favoritos()->attach($this->producto);

        $this->actingAs($this->cliente)
            ->delete(route('favoritos.eliminar', $this->producto))
            ->assertRedirect();

        $this->assertDatabaseCount('favoritos', 0);
        // El producto sigue existiendo: solo se borró la fila de la pivote.
        $this->assertDatabaseHas('products', ['id' => $this->producto->id]);
    }

    public function test_no_se_puede_marcar_un_producto_desactivado(): void
    {
        $oculto = Product::factory()->create(['activo' => false]);

        $this->actingAs($this->cliente)
            ->post(route('favoritos.alternar', $oculto))
            ->assertNotFound();
    }

    /* ==================================================================
     | Pantalla «Mis favoritos»
     | ================================================================ */

    public function test_la_pantalla_muestra_los_productos_guardados(): void
    {
        $this->cliente->favoritos()->attach($this->producto);

        $this->actingAs($this->cliente)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertSee('Monitor de prueba');
    }

    public function test_solo_se_ven_los_favoritos_propios(): void
    {
        $ajeno = User::factory()->create();
        $suyo  = Product::factory()->create(['nombre' => 'Teclado ajeno']);
        $ajeno->favoritos()->attach($suyo);

        $this->actingAs($this->cliente)
            ->get(route('favoritos.index'))
            ->assertOk()
            ->assertDontSee('Teclado ajeno');
    }

    public function test_la_lista_de_deseos_exige_sesion_iniciada(): void
    {
        $this->get(route('favoritos.index'))->assertRedirect(route('login'));
        $this->post(route('favoritos.alternar', $this->producto))->assertRedirect(route('login'));
    }
}
