<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Subida de la imagen del producto desde el panel de administración.
 *
 * Los formatos aceptados son JPG, PNG y WEBP. El SVG queda fuera a propósito:
 * puede llevar <script> y se serviría desde el propio dominio de la tienda.
 *
 * Los archivos se simulan con create() indicando el tipo MIME y no con
 * fake()->image(), que necesita la extensión GD de PHP: GD no está entre los
 * requisitos del README y la suite debe correr sin ella.
 */
class ProductoImagenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin     = User::factory()->create(['es_admin' => true]);
        $this->categoria = Category::factory()->create();
    }

    /** Datos mínimos del formulario de producto. */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'category_id' => $this->categoria->id,
            'nombre'      => 'Monitor de prueba',
            'sku'         => 'MON-001',
            'precio'      => 150000,
            'existencias' => 4,
            'activo'      => 1,
        ], $extra);
    }

    public function test_acepta_una_imagen_jpg(): void
    {
        $respuesta = $this->actingAs($this->admin)->post(
            route('admin.productos.guardar'),
            $this->datos(['imagen' => UploadedFile::fake()->create('monitor.jpg', 120, 'image/jpeg')])
        );

        $respuesta->assertRedirect(route('admin.productos.index'));

        $producto = Product::where('sku', 'MON-001')->firstOrFail();

        $this->assertStringStartsWith('storage/productos/', $producto->imagen);
        Storage::disk('public')->assertExists(str_replace('storage/', '', $producto->imagen));
    }

    public function test_acepta_png_y_webp(): void
    {
        $casos = [
            ['PNG-001', 'foto.png', 'image/png'],
            ['WEB-001', 'foto.webp', 'image/webp'],
        ];

        foreach ($casos as [$sku, $archivo, $mime]) {
            $this->actingAs($this->admin)->post(
                route('admin.productos.guardar'),
                $this->datos([
                    'sku'    => $sku,
                    'imagen' => UploadedFile::fake()->create($archivo, 120, $mime),
                ])
            )->assertSessionHasNoErrors();

            $this->assertTrue(Product::where('sku', $sku)->exists());
        }
    }

    public function test_rechaza_un_svg(): void
    {
        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->actingAs($this->admin)
            ->post(route('admin.productos.guardar'), $this->datos(['imagen' => $svg]))
            ->assertSessionHasErrors('imagen');

        $this->assertFalse(Product::where('sku', 'MON-001')->exists());
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.productos.guardar'), $this->datos([
                'imagen' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('imagen');
    }

    public function test_rechaza_una_imagen_de_mas_de_dos_megas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.productos.guardar'), $this->datos([
                'imagen' => UploadedFile::fake()->create('enorme.jpg', 2100, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('imagen');
    }

    public function test_el_producto_se_puede_guardar_sin_imagen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.productos.guardar'), $this->datos())
            ->assertSessionHasNoErrors();

        $producto = Product::where('sku', 'MON-001')->firstOrFail();

        // Sin imagen propia se usa el respaldo del catálogo.
        $this->assertStringContainsString('sin-imagen.svg', $producto->urlImagen());
    }
}
