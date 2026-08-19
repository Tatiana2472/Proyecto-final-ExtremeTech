<?php

namespace Tests\Unit;

use App\Services\Pagos\GestorPasarelas;
use App\Services\Pagos\PasarelaPayPal;
use App\Services\Pagos\PasarelaSinpe;
use App\Services\Pagos\PasarelaTarjeta;
use App\Services\Pagos\SolicitudPago;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Pruebas unitarias de PayPal, SINPE Móvil y del selector de pasarelas.
 */
class PasarelasAlternativasTest extends TestCase
{
    private function solicitud(array $datos, float $monto = 50000): SolicitudPago
    {
        return new SolicitudPago(
            monto: $monto,
            moneda: 'CRC',
            referencia: 'PED-2026-000002',
            descripcion: 'Compra de prueba',
            datos: $datos,
        );
    }

    /* ==================================================================
     | PayPal
     | ================================================================ */

    public function test_paypal_aprueba_un_pago_con_correo_valido(): void
    {
        config([
            'tienda.pagos.paypal.moneda'      => 'USD',
            'tienda.pagos.paypal.tipo_cambio' => 500,
        ]);

        $resultado = (new PasarelaPayPal())->procesar(
            $this->solicitud(['correo_paypal' => 'cliente@example.com'], 50000)
        );

        $this->assertTrue($resultado->aprobado);
        $this->assertSame('paypal', $resultado->metodo);
        $this->assertSame('USD', $resultado->moneda);
        $this->assertSame(100.0, $resultado->monto);          // 50 000 / 500
        $this->assertSame('cliente@example.com', $resultado->correoPagador);
        $this->assertStringStartsWith('PAYID-', (string) $resultado->idTransaccion);
    }

    public function test_paypal_rechaza_un_correo_invalido(): void
    {
        $resultado = (new PasarelaPayPal())->procesar(
            $this->solicitud(['correo_paypal' => 'no-es-un-correo'])
        );

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('no es válida', $resultado->mensaje);
    }

    public function test_paypal_rechaza_la_cuenta_de_prueba_de_fallo(): void
    {
        $resultado = (new PasarelaPayPal())->procesar(
            $this->solicitud(['correo_paypal' => 'rechazado@example.com'])
        );

        $this->assertFalse($resultado->aprobado);
    }

    public function test_paypal_no_convierte_si_la_moneda_es_la_misma(): void
    {
        config(['tienda.pagos.paypal.moneda' => 'CRC']);

        $resultado = (new PasarelaPayPal())->procesar(
            $this->solicitud(['correo_paypal' => 'cliente@example.com'], 50000)
        );

        $this->assertSame(50000.0, $resultado->monto);
        $this->assertSame('CRC', $resultado->moneda);
    }

    public function test_paypal_no_divide_entre_cero_si_falta_el_tipo_de_cambio(): void
    {
        config([
            'tienda.pagos.paypal.moneda'      => 'USD',
            'tienda.pagos.paypal.tipo_cambio' => 0,
        ]);

        $resultado = (new PasarelaPayPal())->procesar(
            $this->solicitud(['correo_paypal' => 'cliente@example.com'], 50000)
        );

        $this->assertTrue($resultado->aprobado);
        $this->assertSame(50000.0, $resultado->monto);
    }

    /* ==================================================================
     | SINPE Móvil
     | ================================================================ */

    public function test_sinpe_aprueba_con_un_comprobante_valido(): void
    {
        $resultado = (new PasarelaSinpe())->procesar(
            $this->solicitud(['comprobante_sinpe' => '123456789'])
        );

        $this->assertTrue($resultado->aprobado);
        $this->assertSame('sinpe', $resultado->metodo);
        $this->assertStringContainsString('123456789', (string) $resultado->idTransaccion);
    }

    public function test_sinpe_rechaza_un_comprobante_demasiado_corto(): void
    {
        $resultado = (new PasarelaSinpe())->procesar(
            $this->solicitud(['comprobante_sinpe' => '123'])
        );

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('6 dígitos', $resultado->mensaje);
    }

    /* ==================================================================
     | Selector de pasarelas (patrón Factory)
     | ================================================================ */

    public function test_el_gestor_devuelve_la_pasarela_correspondiente(): void
    {
        $gestor = $this->gestor();

        $this->assertInstanceOf(PasarelaTarjeta::class, $gestor->obtener('tarjeta'));
        $this->assertInstanceOf(PasarelaPayPal::class, $gestor->obtener('paypal'));
        $this->assertInstanceOf(PasarelaSinpe::class, $gestor->obtener('sinpe'));
    }

    public function test_el_gestor_falla_con_un_metodo_desconocido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->gestor()->obtener('bitcoin');
    }

    public function test_el_gestor_no_permite_usar_un_metodo_deshabilitado(): void
    {
        config(['tienda.pagos.metodos.paypal.habilitado' => false]);

        $gestor = $this->gestor();

        $this->assertNotContains('paypal', $gestor->metodosDisponibles());

        $this->expectException(InvalidArgumentException::class);
        $gestor->obtener('paypal');
    }

    public function test_lista_los_metodos_habilitados(): void
    {
        config([
            'tienda.pagos.metodos.tarjeta.habilitado' => true,
            'tienda.pagos.metodos.paypal.habilitado'  => true,
            'tienda.pagos.metodos.sinpe.habilitado'   => false,
        ]);

        $this->assertSame(['tarjeta', 'paypal'], $this->gestor()->metodosDisponibles());
    }

    private function gestor(): GestorPasarelas
    {
        return new GestorPasarelas(new PasarelaTarjeta(), new PasarelaPayPal(), new PasarelaSinpe());
    }
}
