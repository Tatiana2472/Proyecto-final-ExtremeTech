<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catálogo de productos: categorización, listado, búsqueda y filtrado.
 */
class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_muestra_categorias_y_destacados(): void
    {
        $categoria = Category::factory()->create(['nombre' => 'Laptops', 'slug' => 'laptops']);
        $destacado = Product::factory()->destacado()->create([
            'category_id' => $categoria->id,
            'nombre'      => 'Laptop de prueba',
        ]);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Laptops')
            ->assertSee('Laptop de prueba');
    }

    public function test_el_listado_muestra_los_productos_activos(): void
    {
        $visible = Product::factory()->create(['nombre' => 'Producto visible']);
        $oculto  = Product::factory()->inactivo()->create(['nombre' => 'Producto oculto']);

        $this->get(route('catalogo.listado'))
            ->assertOk()
            ->assertSee('Producto visible')
            ->assertDontSee('Producto oculto');
    }

    public function test_el_detalle_muestra_descripcion_precio_e_imagen(): void
    {
        $producto = Product::factory()->create([
            'nombre'      => 'Monitor de prueba',
            'descripcion' => 'Una descripción bien detallada del monitor.',
            'precio'      => 118000,
            'imagen'      => 'img/productos/monitor-lg-ultragear-24-165hz.svg',
        ]);

        $this->get(route('catalogo.detalle', $producto))
            ->assertOk()
            ->assertSee('Monitor de prueba')
            ->assertSee('Una descripción bien detallada del monitor.')
            ->assertSee('₡118 000')
            ->assertSee('monitor-lg-ultragear-24-165hz.svg', escape: false)
            ->assertSee($producto->sku);
    }

    public function test_un_producto_inactivo_devuelve_404(): void
    {
        $producto = Product::factory()->inactivo()->create();

        $this->get(route('catalogo.detalle', $producto))->assertNotFound();
    }

    /* ==================================================================
     | Búsqueda
     | ================================================================ */

    public function test_busca_productos_por_nombre(): void
    {
        // La marca se fija a propósito: la factory la elige al azar y si al
        // segundo producto le tocara «Lenovo» también aparecería en la
        // búsqueda, porque el buscador mira nombre, marca, SKU y resumen.
        Product::factory()->create(['nombre' => 'Laptop Lenovo IdeaPad', 'marca' => 'Lenovo']);
        Product::factory()->create(['nombre' => 'Audífonos Sony', 'marca' => 'Sony']);

        $this->get(route('catalogo.listado', ['q' => 'lenovo']))
            ->assertOk()
            ->assertSee('Laptop Lenovo IdeaPad')
            ->assertDontSee('Audífonos Sony');
    }

    public function test_busca_productos_por_marca_y_por_sku(): void
    {
        Product::factory()->create(['nombre' => 'Equipo A', 'marca' => 'Xiaomi', 'sku' => 'CEL-XIA-001']);
        Product::factory()->create(['nombre' => 'Equipo B', 'marca' => 'Apple', 'sku' => 'CEL-APL-001']);

        $this->get(route('catalogo.listado', ['q' => 'Xiaomi']))
            ->assertSee('Equipo A')->assertDontSee('Equipo B');

        $this->get(route('catalogo.listado', ['q' => 'CEL-APL-001']))
            ->assertSee('Equipo B')->assertDontSee('Equipo A');
    }

    public function test_la_busqueda_sin_resultados_muestra_un_mensaje(): void
    {
        Product::factory()->create(['nombre' => 'Teclado mecánico']);

        $this->get(route('catalogo.listado', ['q' => 'zzzzznoexiste']))
            ->assertOk()
            ->assertSee('No encontramos productos');
    }

    /* ==================================================================
     | Filtros
     | ================================================================ */

    public function test_filtra_por_categoria(): void
    {
        $laptops = Category::factory()->create(['nombre' => 'Laptops', 'slug' => 'laptops']);
        $audio   = Category::factory()->create(['nombre' => 'Audio', 'slug' => 'audio']);

        Product::factory()->create(['category_id' => $laptops->id, 'nombre' => 'MacBook Air']);
        Product::factory()->create(['category_id' => $audio->id, 'nombre' => 'Parlante JBL']);

        $this->get(route('catalogo.categoria', 'laptops'))
            ->assertOk()
            ->assertSee('MacBook Air')
            ->assertDontSee('Parlante JBL');
    }

    public function test_filtra_por_rango_de_precio(): void
    {
        Product::factory()->conPrecio(10000)->create(['nombre' => 'Producto barato']);
        Product::factory()->conPrecio(500000)->create(['nombre' => 'Producto caro']);

        $this->get(route('catalogo.listado', ['min' => 100000]))
            ->assertSee('Producto caro')
            ->assertDontSee('Producto barato');

        $this->get(route('catalogo.listado', ['max' => 100000]))
            ->assertSee('Producto barato')
            ->assertDontSee('Producto caro');

        $this->get(route('catalogo.listado', ['min' => 5000, 'max' => 20000]))
            ->assertSee('Producto barato')
            ->assertDontSee('Producto caro');
    }

    public function test_ordena_los_productos_por_precio(): void
    {
        Product::factory()->conPrecio(300000)->create(['nombre' => 'Caro AAA']);
        Product::factory()->conPrecio(9000)->create(['nombre' => 'Barato BBB']);

        $contenido = $this->get(route('catalogo.listado', ['orden' => 'precio_asc']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($contenido, 'Caro AAA'),
            strpos($contenido, 'Barato BBB'),
            'Con orden ascendente el producto barato debe aparecer primero.'
        );
    }

    public function test_rechaza_una_categoria_inexistente(): void
    {
        $this->get(route('catalogo.listado', ['categoria' => 'no-existe']))
            ->assertSessionHasErrors('categoria');
    }

    public function test_rechaza_un_criterio_de_orden_no_permitido(): void
    {
        // Lista blanca de ordenamientos: evita inyección por el parámetro.
        $this->get(route('catalogo.listado', ['orden' => 'precio; DROP TABLE products']))
            ->assertSessionHasErrors('orden');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_combina_busqueda_categoria_y_precio(): void
    {
        $laptops = Category::factory()->create(['slug' => 'laptops', 'nombre' => 'Laptops']);

        Product::factory()->conPrecio(400000)->create([
            'category_id' => $laptops->id, 'nombre' => 'Laptop Asus TUF',
        ]);
        Product::factory()->conPrecio(900000)->create([
            'category_id' => $laptops->id, 'nombre' => 'Laptop Asus ROG',
        ]);

        $this->get(route('catalogo.listado', [
            'q' => 'Asus', 'categoria' => 'laptops', 'max' => 500000,
        ]))
            ->assertSee('Laptop Asus TUF')
            ->assertDontSee('Laptop Asus ROG');
    }

    public function test_pagina_los_resultados(): void
    {
        config(['tienda.productos_por_pagina' => 5]);
        Product::factory()->count(12)->create();

        $this->get(route('catalogo.listado'))
            ->assertOk()
            ->assertSee('page=2', escape: false);
    }

    public function test_un_producto_agotado_no_se_puede_agregar(): void
    {
        $producto = Product::factory()->agotado()->create(['nombre' => 'Base refrigerante']);

        $this->get(route('catalogo.detalle', $producto))
            ->assertOk()
            ->assertSee('Agotado')
            ->assertSee('está agotado');
    }
}
