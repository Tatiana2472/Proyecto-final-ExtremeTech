<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mantenimiento de categorías desde el panel de administración.
 */
class CategoriaAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create();
    }

    /* ==================================================================
     | Control de acceso
     | ================================================================ */

    public function test_un_cliente_no_puede_administrar_categorias(): void
    {
        $cliente   = User::factory()->create();
        $categoria = Category::factory()->create();

        $this->actingAs($cliente)->get(route('admin.categorias.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.categorias.crear'))->assertForbidden();
        $this->actingAs($cliente)->post(route('admin.categorias.guardar'), [])->assertForbidden();
        $this->actingAs($cliente)->delete(route('admin.categorias.eliminar', $categoria))->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $categoria->id]);
    }

    public function test_un_visitante_es_enviado_a_iniciar_sesion(): void
    {
        $this->get(route('admin.categorias.index'))->assertRedirect(route('login'));
    }

    /* ==================================================================
     | Listado
     | ================================================================ */

    public function test_lista_las_categorias_con_su_cantidad_de_productos(): void
    {
        $categoria = Category::factory()->create(['nombre' => 'Laptops', 'slug' => 'laptops']);
        Product::factory()->count(3)->create(['category_id' => $categoria->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.categorias.index'))
            ->assertOk()
            ->assertSee('Laptops')
            ->assertSee('laptops');
    }

    /* ==================================================================
     | Crear
     | ================================================================ */

    public function test_crea_una_categoria(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categorias.guardar'), [
                'nombre'      => 'Impresoras',
                'descripcion' => 'Impresoras de tinta y láser.',
                'icono'       => 'bi-printer',
                'activa'      => '1',
            ])
            ->assertRedirect(route('admin.categorias.index'))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('categories', [
            'nombre' => 'Impresoras',
            'slug'   => 'impresoras',
            'icono'  => 'bi-printer',
            'activa' => true,
        ]);
    }

    public function test_genera_el_slug_a_partir_del_nombre(): void
    {
        $this->actingAs($this->admin)->post(route('admin.categorias.guardar'), [
            'nombre' => 'Cámaras y Vídeo',
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'camaras-y-video']);
    }

    public function test_valida_los_datos_de_la_categoria(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categorias.guardar'), [
                'nombre' => 'X',                       // muy corto
                'slug'   => 'Con Mayúsculas Y Espacios',
                'icono'  => 'no-es-un-icono',
            ])
            ->assertSessionHasErrors(['nombre', 'slug', 'icono']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_no_permite_repetir_la_url_amigable(): void
    {
        Category::factory()->create(['slug' => 'laptops']);

        $this->actingAs($this->admin)
            ->post(route('admin.categorias.guardar'), [
                'nombre' => 'Otra categoría',
                'slug'   => 'laptops',
            ])
            ->assertSessionHasErrors('slug');
    }

    /* ==================================================================
     | Editar
     | ================================================================ */

    public function test_actualiza_una_categoria(): void
    {
        $categoria = Category::factory()->create(['nombre' => 'Nombre viejo', 'slug' => 'nombre-viejo']);

        $this->actingAs($this->admin)
            ->put(route('admin.categorias.actualizar', $categoria), [
                'nombre'      => 'Nombre nuevo',
                'slug'        => 'nombre-nuevo',
                'descripcion' => 'Actualizada.',
                'icono'       => 'bi-laptop',
                'activa'      => '1',
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $categoria->refresh();

        $this->assertSame('Nombre nuevo', $categoria->nombre);
        $this->assertSame('nombre-nuevo', $categoria->slug);
    }

    public function test_puede_guardar_conservando_su_propia_url_amigable(): void
    {
        $categoria = Category::factory()->create(['slug' => 'audio']);

        $this->actingAs($this->admin)
            ->put(route('admin.categorias.actualizar', $categoria), [
                'nombre' => 'Audio y sonido',
                'slug'   => 'audio',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_desactivar_una_categoria_la_oculta_de_la_tienda(): void
    {
        $categoria = Category::factory()->create(['nombre' => 'Oculta', 'slug' => 'oculta']);
        Product::factory()->create(['category_id' => $categoria->id, 'nombre' => 'Producto escondido']);

        $this->actingAs($this->admin)->put(route('admin.categorias.actualizar', $categoria), [
            'nombre' => 'Oculta',
            'slug'   => 'oculta',
            // sin 'activa' => la categoría queda inactiva
        ]);

        $this->assertFalse($categoria->fresh()->activa);

        // Ya no se puede navegar por ella.
        $this->get(route('catalogo.categoria', 'oculta'))->assertNotFound();
    }

    /* ==================================================================
     | Eliminar
     | ================================================================ */

    public function test_elimina_una_categoria_sin_productos(): void
    {
        $categoria = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categorias.eliminar', $categoria))
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseMissing('categories', ['id' => $categoria->id]);
    }

    public function test_una_categoria_con_productos_se_desactiva_en_lugar_de_borrarse(): void
    {
        $categoria = Category::factory()->create();
        $producto  = Product::factory()->create(['category_id' => $categoria->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.categorias.eliminar', $categoria))
            ->assertRedirect(route('admin.categorias.index'));

        // Ni la categoría ni sus productos se pierden.
        $this->assertDatabaseHas('categories', ['id' => $categoria->id, 'activa' => false]);
        $this->assertDatabaseHas('products', ['id' => $producto->id]);
    }
}
