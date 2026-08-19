<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Consideraciones de seguridad exigidas por el proyecto:
 *
 *  - Validación de entradas para prevenir inyección SQL y XSS.
 *  - Sesiones seguras y cifrado de contraseñas.
 *  - Protección CSRF en los formularios.
 *  - Control de acceso al área administrativa.
 */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    /* ==================================================================
     | Inyección SQL
     | ================================================================ */

    public function test_el_buscador_no_permite_inyeccion_sql(): void
    {
        Product::factory()->create(['nombre' => 'Producto legítimo']);

        $ataques = [
            "' OR '1'='1",
            "'; DROP TABLE products; --",
            "1' UNION SELECT password FROM users --",
            "admin'--",
        ];

        foreach ($ataques as $ataque) {
            $this->get(route('catalogo.listado', ['q' => $ataque]))->assertOk();
        }

        // Las tablas siguen existiendo y el producto sigue ahí: Eloquent usa
        // consultas preparadas, el texto viaja como parámetro enlazado.
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertDatabaseCount('products', 1);
    }

    public function test_los_filtros_numericos_rechazan_texto_arbitrario(): void
    {
        Product::factory()->create();

        $this->get(route('catalogo.listado', ['min' => "0 OR 1=1"]))
            ->assertSessionHasErrors('min');

        $this->get(route('catalogo.listado', ['max' => 'DROP TABLE products']))
            ->assertSessionHasErrors('max');

        $this->assertTrue(Schema::hasTable('products'));
    }

    public function test_el_buscador_del_administrador_tampoco_es_vulnerable(): void
    {
        $admin = User::factory()->administrador()->create();
        Product::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.productos.index', ['q' => "'; DELETE FROM products; --"]))
            ->assertOk();

        $this->assertDatabaseCount('products', 1);
    }

    /* ==================================================================
     | Cross-Site Scripting (XSS)
     | ================================================================ */

    public function test_escapa_el_html_en_los_nombres_de_los_productos(): void
    {
        // Un producto cargado con un intento de XSS (por ejemplo, desde el
        // panel de administración o una importación).
        $producto = Product::factory()->create([
            'nombre'  => '<script>alert("xss")</script>Laptop',
            'resumen' => '<img src=x onerror=alert(1)>',
        ]);

        $contenido = $this->get(route('catalogo.detalle', $producto))
            ->assertOk()
            ->getContent();

        // Blade escapa la salida: el navegador no ejecuta nada.
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $contenido);
        $this->assertStringNotContainsString('<img src=x onerror=', $contenido);
        $this->assertStringContainsString('&lt;script&gt;', $contenido);
    }

    public function test_escapa_el_html_en_los_datos_del_pedido(): void
    {
        $usuario = User::factory()->create();

        // El nombre del usuario se valida al registrarse, pero además la
        // vista lo escapa: defensa en profundidad.
        $usuario->forceFill(['name' => '<b>Nombre</b><script>alert(1)</script>'])->save();

        $contenido = $this->actingAs($usuario)
            ->get(route('perfil.mostrar'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $contenido);
    }

    public function test_escapa_el_termino_de_busqueda_reflejado_en_la_pagina(): void
    {
        Product::factory()->create();

        $contenido = $this->get(route('catalogo.listado', ['q' => '"><script>alert(1)</script>']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $contenido);
    }

    public function test_escapa_el_nombre_de_la_categoria(): void
    {
        $categoria = Category::factory()->create([
            'nombre' => '<script>alert("cat")</script>',
            'slug'   => 'categoria-xss',
        ]);
        Product::factory()->create(['category_id' => $categoria->id]);

        $contenido = $this->get(route('catalogo.categoria', 'categoria-xss'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<script>alert("cat")</script>', $contenido);
    }

    /* ==================================================================
     | CSRF
     | ================================================================ */

    public function test_las_rutas_web_incluyen_la_verificacion_de_csrf(): void
    {
        // El middleware de CSRF se salta a sí mismo cuando detecta que se están
        // ejecutando pruebas, así que no se puede provocar un 419 desde acá.
        // Lo que sí se comprueba es que esté realmente registrado en el grupo
        // «web», que es el que protege todas las rutas de la tienda.
        $grupos = app(\Illuminate\Foundation\Http\Kernel::class)->getMiddlewareGroups();

        $conCsrf = array_filter(
            $grupos['web'] ?? [],
            fn ($middleware) => is_string($middleware) && str_contains($middleware, 'CsrfToken')
        );

        $this->assertNotEmpty($conCsrf, 'El grupo «web» debe incluir la verificación de CSRF.');
    }

    public function test_las_acciones_sensibles_no_responden_por_get(): void
    {
        $producto = Product::factory()->create(['existencias' => 5]);

        // Al aceptar solo POST/PUT/DELETE, estas acciones quedan cubiertas por
        // la verificación de token CSRF (que no se aplica a los GET).
        $this->get('/carrito/agregar/'.$producto->slug)->assertStatus(405);
        $this->get('/carrito/vaciar')->assertStatus(405);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_las_paginas_incluyen_el_token_csrf(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="_token"', escape: false);
    }

    /* ==================================================================
     | Sesión segura
     | ================================================================ */

    public function test_regenera_el_identificador_de_sesion_al_iniciar_sesion(): void
    {
        User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        $this->get(route('login'));
        $idAnterior = session()->getId();

        $this->post(route('login.enviar'), [
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        // Protección contra fijación de sesión (session fixation).
        $this->assertNotSame($idAnterior, session()->getId());
    }

    public function test_la_contrasena_nunca_se_expone_al_serializar_el_usuario(): void
    {
        $usuario = User::factory()->create(['password' => 'Clave1234']);

        $serializado = $usuario->toArray();

        $this->assertArrayNotHasKey('password', $serializado);
        $this->assertArrayNotHasKey('remember_token', $serializado);
    }

    /* ==================================================================
     | Encabezados de seguridad
     | ================================================================ */

    public function test_envia_los_encabezados_de_seguridad(): void
    {
        $this->get(route('inicio'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /* ==================================================================
     | Control de acceso al panel de administración
     | ================================================================ */

    public function test_un_cliente_no_puede_administrar_el_catalogo(): void
    {
        $cliente  = User::factory()->create();
        $producto = Product::factory()->create();

        $this->actingAs($cliente)->get(route('admin.productos.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.productos.crear'))->assertForbidden();
        $this->actingAs($cliente)->post(route('admin.productos.guardar'), [])->assertForbidden();
        $this->actingAs($cliente)->delete(route('admin.productos.eliminar', $producto))->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $producto->id]);
    }

    public function test_un_cliente_no_puede_cambiar_el_estado_de_un_pedido(): void
    {
        $cliente = User::factory()->create();

        $pedido = \App\Models\Order::factory()->create(['user_id' => $cliente->id]);

        $this->actingAs($cliente)
            ->put(route('admin.pedidos.estado', $pedido), ['estado' => 'entregado'])
            ->assertForbidden();

        $this->assertSame('pagado', $pedido->fresh()->estado);
    }

    public function test_el_administrador_si_puede_administrar_el_catalogo(): void
    {
        $admin     = User::factory()->administrador()->create();
        $categoria = Category::factory()->create();

        $this->actingAs($admin)->get(route('admin.productos.index'))->assertOk();

        $this->actingAs($admin)->post(route('admin.productos.guardar'), [
            'category_id' => $categoria->id,
            'nombre'      => 'Producto creado por el administrador',
            'sku'         => 'ADM-001',
            'precio'      => 25000,
            'existencias' => 10,
            'activo'      => '1',
        ])->assertRedirect(route('admin.productos.index'));

        $this->assertDatabaseHas('products', ['sku' => 'ADM-001']);
    }
}
