<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\VistosRecientementeService;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Productos vistos recientemente mediante COOKIES.
 *
 * Verifica el requisito «Utilizar cookies en productos vistos recientemente.
 * Mostrarle al usuario los últimos productos que ha visitado mientras navega
 * por la tienda».
 */
class VistosRecientementeTest extends TestCase
{
    use RefreshDatabase;

    private function nombreCookie(): string
    {
        return app(VistosRecientementeService::class)->nombreCookie();
    }

    /**
     * Simula que el navegador ya tiene la cookie con esos productos.
     *
     * Se desactiva el middleware que cifra las cookies para poder escribirla
     * a mano; por eso las comprobaciones posteriores usan encriptada: false.
     */
    private function conCookieDeVistos(array $ids): static
    {
        return $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie($this->nombreCookie(), json_encode($ids));
    }

    /** Comprueba el valor de la cookie cuando el cifrado está desactivado. */
    private function esperarCookieSinCifrar($respuesta, array $ids): void
    {
        $respuesta->assertCookie($this->nombreCookie(), json_encode($ids), encrypted: false);
    }

    public function test_al_ver_un_producto_se_guarda_su_id_en_la_cookie(): void
    {
        $producto = Product::factory()->create();

        $this->get(route('catalogo.detalle', $producto))
            ->assertOk()
            ->assertCookie($this->nombreCookie(), json_encode([$producto->id]));
    }

    public function test_el_producto_mas_reciente_queda_de_primero(): void
    {
        $primero = Product::factory()->create();
        $segundo = Product::factory()->create();

        $this->get(route('catalogo.detalle', $primero));

        $this->esperarCookieSinCifrar(
            $this->conCookieDeVistos([$primero->id])->get(route('catalogo.detalle', $segundo)),
            [$segundo->id, $primero->id]
        );
    }

    public function test_no_se_repite_un_producto_visitado_dos_veces(): void
    {
        $uno = Product::factory()->create();
        $dos = Product::factory()->create();

        // El usuario ya vio [dos, uno] y vuelve a abrir "uno".
        $this->esperarCookieSinCifrar(
            $this->conCookieDeVistos([$dos->id, $uno->id])->get(route('catalogo.detalle', $uno)),
            [$uno->id, $dos->id]
        );
    }

    public function test_la_cookie_guarda_como_maximo_los_productos_configurados(): void
    {
        config(['tienda.vistos_recientemente.maximo' => 3]);

        $productos = Product::factory()->count(4)->create();
        $ids = $productos->pluck('id')->all();

        // Ya había 3 productos vistos y visita un cuarto: el más antiguo sale.
        $respuesta = $this->conCookieDeVistos([$ids[0], $ids[1], $ids[2]])
            ->get(route('catalogo.detalle', $productos[3]));

        $this->esperarCookieSinCifrar($respuesta, [$ids[3], $ids[0], $ids[1]]);
    }

    public function test_la_tienda_muestra_los_productos_vistos_mientras_navega(): void
    {
        $visto = Product::factory()->create(['nombre' => 'Audífonos vistos antes']);

        // En la portada
        $this->conCookieDeVistos([$visto->id])
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Vistos recientemente')
            ->assertSee('Audífonos vistos antes');

        // En el catálogo
        $this->conCookieDeVistos([$visto->id])
            ->get(route('catalogo.listado'))
            ->assertOk()
            ->assertSee('Vistos recientemente');

        // Y en el carrito
        $this->conCookieDeVistos([$visto->id])
            ->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Audífonos vistos antes');
    }

    public function test_no_muestra_la_seccion_si_no_hay_productos_vistos(): void
    {
        Product::factory()->create();

        $this->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('Vistos recientemente');
    }

    public function test_el_producto_actual_no_aparece_en_su_propia_lista_de_vistos(): void
    {
        $actual = Product::factory()->create(['nombre' => 'Producto que estoy viendo']);
        $otro   = Product::factory()->create(['nombre' => 'Producto anterior']);

        $this->conCookieDeVistos([$otro->id, $actual->id])
            ->get(route('catalogo.detalle', $actual))
            ->assertOk()
            ->assertSee('Producto anterior')
            // Solo debe aparecer una vez (como título de la página), no en la
            // franja de "vistos recientemente".
            ->assertSeeTextInOrder(['Producto que estoy viendo', 'Producto anterior']);
    }

    public function test_ignora_una_cookie_manipulada_con_contenido_invalido(): void
    {
        Product::factory()->create();

        // Valores no numéricos o negativos se descartan sin romper la página.
        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie($this->nombreCookie(), '["<script>", -5, "abc", null]')
            ->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('Vistos recientemente');
    }

    public function test_ignora_una_cookie_que_no_es_json(): void
    {
        Product::factory()->create();

        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie($this->nombreCookie(), 'esto-no-es-json')
            ->get(route('inicio'))
            ->assertOk();
    }

    public function test_no_muestra_productos_desactivados_aunque_esten_en_la_cookie(): void
    {
        $inactivo = Product::factory()->inactivo()->create(['nombre' => 'Descontinuado XYZ']);
        Product::factory()->create();

        $this->conCookieDeVistos([$inactivo->id])
            ->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('Descontinuado XYZ');
    }
}
