<?php

namespace Tests\Unit;

use App\Services\Pagos\PasarelaTarjeta;
use App\Services\Pagos\SolicitudPago;
use Tests\TestCase;

/**
 * Pruebas unitarias de la pasarela de pago con tarjeta.
 */
class PasarelaTarjetaTest extends TestCase
{
    private PasarelaTarjeta $pasarela;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pasarela = new PasarelaTarjeta();
    }

    /** Construye una solicitud de pago de prueba. */
    private function solicitud(array $datos = [], float $monto = 50000): SolicitudPago
    {
        return new SolicitudPago(
            monto: $monto,
            moneda: 'CRC',
            referencia: 'PED-2026-000001',
            descripcion: 'Compra de prueba',
            datos: array_merge([
                'nombre' => 'María Rodríguez',
                'numero' => '4111111111111111',
                'mes'    => 12,
                'anio'   => now()->year + 2,
                'cvv'    => '123',
            ], $datos),
        );
    }

    /* ------------------------------------------------------------------
     | Algoritmo de Luhn
     | ---------------------------------------------------------------- */

    public function test_acepta_numeros_de_tarjeta_validos(): void
    {
        foreach (['4111111111111111', '5500005555555559', '378282246310005'] as $numero) {
            $this->assertTrue(
                $this->pasarela->numeroEsValido($numero),
                "El número {$numero} debería ser válido según Luhn."
            );
        }
    }

    public function test_rechaza_numeros_de_tarjeta_invalidos(): void
    {
        foreach (['4111111111111112', '1234567890123456', '411', 'abcd', ''] as $numero) {
            $this->assertFalse(
                $this->pasarela->numeroEsValido($numero),
                "El número «{$numero}» no debería ser válido."
            );
        }
    }

    public function test_detecta_la_marca_de_la_tarjeta(): void
    {
        $this->assertSame('Visa', $this->pasarela->detectarMarca('4111111111111111'));
        $this->assertSame('MasterCard', $this->pasarela->detectarMarca('5500005555555559'));
        $this->assertSame('American Express', $this->pasarela->detectarMarca('378282246310005'));
    }

    /* ------------------------------------------------------------------
     | Procesamiento del pago
     | ---------------------------------------------------------------- */

    public function test_aprueba_un_pago_con_datos_correctos(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud());

        $this->assertTrue($resultado->aprobado);
        $this->assertSame('tarjeta', $resultado->metodo);
        $this->assertSame(50000.0, $resultado->monto);
        $this->assertSame('CRC', $resultado->moneda);
        $this->assertStringStartsWith('TRX-', (string) $resultado->idTransaccion);
    }

    public function test_solo_guarda_los_ultimos_cuatro_digitos_de_la_tarjeta(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud());

        $this->assertSame('1111', $resultado->tarjetaUltimos4);
        $this->assertSame('Visa', $resultado->tarjetaMarca);

        // Requisito de seguridad: el número completo y el CVV no deben
        // aparecer en ninguna parte de lo que se persiste.
        $paraGuardar = json_encode($resultado->paraGuardar());
        $this->assertStringNotContainsString('4111111111111111', $paraGuardar);
        $this->assertStringNotContainsString('123', (string) $resultado->tarjetaUltimos4);
    }

    public function test_rechaza_una_tarjeta_con_numero_invalido(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud(['numero' => '4111111111111112']));

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('no es válido', $resultado->mensaje);
        $this->assertNull($resultado->idTransaccion);
    }

    public function test_rechaza_una_tarjeta_vencida(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud([
            'mes'  => 1,
            'anio' => now()->year - 1,
        ]));

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('vencida', $resultado->mensaje);
    }

    public function test_rechaza_un_mes_de_vencimiento_fuera_de_rango(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud(['mes' => 13]));

        $this->assertFalse($resultado->aprobado);
    }

    public function test_rechaza_un_cvv_invalido(): void
    {
        foreach (['12', '12345', 'abc', ''] as $cvv) {
            $resultado = $this->pasarela->procesar($this->solicitud(['cvv' => $cvv]));

            $this->assertFalse($resultado->aprobado, "El CVV «{$cvv}» no debería aceptarse.");
        }
    }

    public function test_la_tarjeta_de_prueba_de_rechazo_es_declinada(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud(['numero' => '4000000000000002']));

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('declinada', $resultado->mensaje);
    }

    public function test_no_permite_cobrar_un_monto_de_cero(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud(monto: 0));

        $this->assertFalse($resultado->aprobado);
        $this->assertStringContainsString('mayor que cero', $resultado->mensaje);
    }

    public function test_acepta_el_anio_de_vencimiento_con_dos_digitos(): void
    {
        $resultado = $this->pasarela->procesar($this->solicitud([
            'anio' => (int) now()->addYears(2)->format('y'),
        ]));

        $this->assertTrue($resultado->aprobado);
    }
}
