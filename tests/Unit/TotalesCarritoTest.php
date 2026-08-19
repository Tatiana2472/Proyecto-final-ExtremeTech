<?php

namespace Tests\Unit;

use App\Services\TotalesCarrito;
use Tests\TestCase;

/**
 * Pruebas unitarias del cálculo automático del total de la compra.
 *
 * Verifican el requisito «Cálculo automático del total de la compra incluyendo
 * impuestos y costos de envío».
 */
class TotalesCarritoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Se fijan los parámetros de negocio para que las pruebas no dependan
        // de lo que tenga configurado el archivo .env de cada persona.
        config([
            'tienda.impuesto.tasa'      => 0.13,   // IVA 13%
            'tienda.envio.costo'        => 2900,
            'tienda.envio.gratis_desde' => 75000,
        ]);
    }

    public function test_calcula_el_impuesto_el_envio_y_el_total(): void
    {
        $totales = TotalesCarrito::calcular(subtotal: 50000, cantidad: 2);

        $this->assertSame(50000.0, $totales->subtotal);
        $this->assertSame(6500.0, $totales->impuesto);            // 50000 * 0.13
        $this->assertSame(2900.0, $totales->envio);
        $this->assertSame(59400.0, $totales->total);              // 50000 + 6500 + 2900
        $this->assertSame(2, $totales->cantidadArticulos);
        $this->assertFalse($totales->envioGratis);
    }

    public function test_el_envio_es_gratis_al_alcanzar_el_monto_minimo(): void
    {
        $totales = TotalesCarrito::calcular(subtotal: 75000, cantidad: 1);

        $this->assertTrue($totales->envioGratis);
        $this->assertSame(0.0, $totales->envio);
        $this->assertSame(84750.0, $totales->total);              // 75000 + 9750 + 0
        $this->assertSame(0.0, $totales->faltaParaEnvioGratis);
    }

    public function test_indica_cuanto_falta_para_el_envio_gratis(): void
    {
        $totales = TotalesCarrito::calcular(subtotal: 60000, cantidad: 1);

        $this->assertFalse($totales->envioGratis);
        $this->assertSame(15000.0, $totales->faltaParaEnvioGratis);
    }

    public function test_el_carrito_vacio_no_cobra_impuesto_ni_envio(): void
    {
        $totales = TotalesCarrito::calcular(subtotal: 0, cantidad: 0);

        $this->assertSame(0.0, $totales->subtotal);
        $this->assertSame(0.0, $totales->impuesto);
        $this->assertSame(0.0, $totales->envio);
        $this->assertSame(0.0, $totales->total);
        $this->assertTrue($totales->estaVacio());
    }

    public function test_un_subtotal_negativo_se_trata_como_cero(): void
    {
        $totales = TotalesCarrito::calcular(subtotal: -5000, cantidad: -3);

        $this->assertSame(0.0, $totales->total);
        $this->assertSame(0, $totales->cantidadArticulos);
    }

    public function test_redondea_los_montos_a_dos_decimales(): void
    {
        // 33 333,33 * 0,13 = 4 333,3329 -> 4 333,33
        $totales = TotalesCarrito::calcular(subtotal: 33333.33, cantidad: 1);

        $this->assertSame(4333.33, $totales->impuesto);
        $this->assertSame(40566.66, $totales->total);
    }

    public function test_muestra_el_porcentaje_del_impuesto_sin_ceros_sobrantes(): void
    {
        $this->assertSame('13', TotalesCarrito::calcular(1000, 1)->porcentajeImpuesto());

        config(['tienda.impuesto.tasa' => 0.045]);
        $this->assertSame('4.5', TotalesCarrito::calcular(1000, 1)->porcentajeImpuesto());
    }

    public function test_respeta_una_tasa_de_impuesto_distinta(): void
    {
        config(['tienda.impuesto.tasa' => 0.04]);   // tarifa reducida

        $totales = TotalesCarrito::calcular(subtotal: 100000, cantidad: 1);

        $this->assertSame(4000.0, $totales->impuesto);
        $this->assertSame(104000.0, $totales->total);   // envío gratis: 100000 > 75000
    }
}
