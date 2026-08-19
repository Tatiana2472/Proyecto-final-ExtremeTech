<?php

namespace Tests\Unit;

use App\Services\Catalogo\ProductoExterno;
use Tests\TestCase;

/**
 * Pruebas unitarias de la traducción de un producto externo al formato de
 * esta tienda. No tocan la red ni la base de datos: solo comprueban las
 * reglas de conversión, que es donde se concentran los errores sutiles
 * (sobre todo el manejo de precios en unidades mínimas).
 */
class ImportadorCatalogoTest extends TestCase
{
    /** @return array<string, mixed> */
    private function respuestaDeEjemplo(array $cambios = []): array
    {
        return array_replace_recursive([
            'id' => 4521,
            'name' => 'Monitor LG UltraGear 27" 165Hz',
            'short_description' => '<p>Panel IPS de 27&nbsp;pulgadas.</p>',
            'description' => '<p>Monitor <strong>gamer</strong> con 165 Hz.</p>',
            'is_in_stock' => true,
            'prices' => [
                'price' => '18900000',
                'regular_price' => '21900000',
                'currency_code' => 'CRC',
                'currency_minor_unit' => 2,
            ],
            'images' => [['src' => 'https://ejemplo.com/monitor.jpg']],
            'categories' => [['name' => 'Monitores']],
            'attributes' => [
                ['name' => 'Marca', 'terms' => [['name' => 'LG']]],
                ['name' => 'Tamaño', 'terms' => [['name' => '27"']]],
            ],
        ], $cambios);
    }

    public function test_convierte_el_precio_desde_la_unidad_minima_de_la_moneda(): void
    {
        $externo = ProductoExterno::desdeWooCommerce($this->respuestaDeEjemplo(), 10);

        // "18900000" con currency_minor_unit = 2 son 189 000,00 colones.
        $this->assertSame(189000.0, $externo->precio);
        $this->assertSame(219000.0, $externo->precioAnterior);
    }

    public function test_no_guarda_precio_anterior_cuando_no_hay_descuento(): void
    {
        $externo = ProductoExterno::desdeWooCommerce(
            $this->respuestaDeEjemplo(['prices' => ['regular_price' => '18900000']]),
            10
        );

        $this->assertNull($externo->precioAnterior);
    }

    public function test_descarta_productos_sin_precio_utilizable(): void
    {
        $sinPrecio = $this->respuestaDeEjemplo();
        $sinPrecio['prices']['price'] = '';

        $this->assertNull(ProductoExterno::desdeWooCommerce($sinPrecio, 10));
        $this->assertNull(ProductoExterno::desdeWooCommerce(['id' => 1, 'name' => ''], 10));
    }

    public function test_limpia_el_html_y_agrega_las_caracteristicas(): void
    {
        $externo = ProductoExterno::desdeWooCommerce($this->respuestaDeEjemplo(), 10);

        $this->assertSame('Panel IPS de 27 pulgadas.', $externo->resumen);
        $this->assertStringNotContainsString('<strong>', $externo->descripcion);
        $this->assertStringContainsString('Marca: LG', $externo->descripcion);
        $this->assertStringContainsString('Tamaño: 27"', $externo->descripcion);
    }

    public function test_toma_la_marca_del_atributo_correspondiente(): void
    {
        $externo = ProductoExterno::desdeWooCommerce($this->respuestaDeEjemplo(), 10);

        $this->assertSame('LG', $externo->marca);
    }

    public function test_un_producto_agotado_queda_en_cero_existencias(): void
    {
        $agotado = ProductoExterno::desdeWooCommerce(
            $this->respuestaDeEjemplo(['is_in_stock' => false]),
            10
        );

        $this->assertSame(0, $agotado->existencias);
    }

    public function test_usa_las_existencias_reales_cuando_la_tienda_las_publica(): void
    {
        $externo = ProductoExterno::desdeWooCommerce(
            $this->respuestaDeEjemplo(['low_stock_remaining' => 3]),
            10
        );

        $this->assertSame(3, $externo->existencias);
    }

    public function test_ignora_imagenes_que_no_sean_direcciones_validas(): void
    {
        $externo = ProductoExterno::desdeWooCommerce(
            $this->respuestaDeEjemplo(['images' => [['src' => '/local/imagen.jpg']]]),
            10
        );

        $this->assertNull($externo->imagenUrl);
    }

    /* ----------------------------------------------------------------------
     | Origen alternativo: DummyJSON
     | -------------------------------------------------------------------- */

    /** @return array<string, mixed> */
    private function productoDummyJson(array $cambios = []): array
    {
        return array_replace([
            'id' => 122,
            'title' => 'iPhone 6',
            'description' => 'Teléfono inteligente de Apple.',
            'category' => 'smartphones',
            'price' => 100.0,
            'discountPercentage' => 10.0,
            'stock' => 7,
            'brand' => 'Apple',
            'images' => ['https://cdn.dummyjson.com/iphone-6.png'],
            'warrantyInformation' => '1 year warranty',
            'shippingInformation' => 'Ships in 3-5 business days',
            'returnPolicy' => '30 days return policy',
        ], $cambios);
    }

    public function test_convierte_dolares_a_colones_y_aplica_el_descuento(): void
    {
        $externo = ProductoExterno::desdeDummyJson($this->productoDummyJson(), 510);

        // 100 USD * 510 = 51 000 colones de precio de lista.
        $this->assertSame(51000.0, $externo->precioAnterior);
        // Con 10% de descuento: 90 USD * 510 = 45 900 colones.
        $this->assertSame(45900.0, $externo->precio);
    }

    public function test_sin_descuento_no_guarda_precio_anterior(): void
    {
        $externo = ProductoExterno::desdeDummyJson(
            $this->productoDummyJson(['discountPercentage' => 0]),
            510
        );

        $this->assertNull($externo->precioAnterior);
    }

    public function test_toma_marca_existencias_y_categoria_de_dummyjson(): void
    {
        $externo = ProductoExterno::desdeDummyJson($this->productoDummyJson(), 510);

        $this->assertSame('Apple', $externo->marca);
        $this->assertSame(7, $externo->existencias);
        $this->assertSame(['smartphones'], $externo->categoriasExternas);
    }

    public function test_la_descripcion_se_genera_en_espanol_sin_copiar_el_ingles(): void
    {
        $externo = ProductoExterno::desdeDummyJson($this->productoDummyJson(), 510);

        // El texto se arma con los campos estructurados, no con el
        // `description` en inglés que trae la API.
        $this->assertSame('Teléfono inteligente de la marca Apple.', $externo->resumen);
        $this->assertStringContainsString('1 año de garantía', $externo->descripcion);
        $this->assertStringContainsString('devolución dentro de 30 días', $externo->descripcion);
        $this->assertStringContainsString('Envío en 3 a 5 días hábiles', $externo->descripcion);
        $this->assertStringNotContainsString('warranty', $externo->descripcion);
        $this->assertStringNotContainsString('Ships', $externo->descripcion);
    }

    public function test_descarta_productos_fuera_del_rango_de_precio(): void
    {
        // Un Rolex de 4 000 USD no pertenece a una tienda de cómputo.
        $this->assertNull(ProductoExterno::desdeDummyJson(
            $this->productoDummyJson(['price' => 3999.99, 'discountPercentage' => 0]),
            510
        ));
    }

    public function test_descarta_productos_de_dummyjson_sin_precio(): void
    {
        $this->assertNull(ProductoExterno::desdeDummyJson(
            $this->productoDummyJson(['price' => 0]),
            510
        ));
    }

    public function test_los_dos_origenes_producen_el_mismo_tipo_de_objeto(): void
    {
        // Esta es la razón de ser del DTO: el importador recibe siempre la
        // misma clase, sin importar de dónde vengan los datos.
        $desdeWoo = ProductoExterno::desdeWooCommerce($this->respuestaDeEjemplo(), 10);
        $desdeDummy = ProductoExterno::desdeDummyJson($this->productoDummyJson(), 510);

        $this->assertInstanceOf(ProductoExterno::class, $desdeWoo);
        $this->assertInstanceOf(ProductoExterno::class, $desdeDummy);
    }
}
