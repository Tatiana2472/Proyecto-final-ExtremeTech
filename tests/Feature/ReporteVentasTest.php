<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Reportes de ventas por mes y por cliente, en pantalla y en PDF.
 */
class ReporteVentasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create([
            'name' => 'Administrador Take Tech CR',
        ]);
    }

    /** Crea un pedido pagado con una línea de detalle. */
    private function venta(User $cliente, string $fecha, float $total, int $unidades = 1): Order
    {
        $pedido = Order::factory()->create([
            'user_id'      => $cliente->id,
            'subtotal'     => $total,
            'impuesto'     => round($total * 0.13, 2),
            'envio'        => 0,
            'total'        => $total,
            'estado_pago'  => 'aprobado',
            'fecha_compra' => $fecha,
        ]);

        $pedido->lineas()->create([
            'nombre_producto' => 'Producto vendido',
            'sku'             => 'SKU-001',
            'precio_unitario' => $total / max($unidades, 1),
            'cantidad'        => $unidades,
            'subtotal'        => $total,
        ]);

        return $pedido;
    }

    /* ==================================================================
     | Control de acceso
     | ================================================================ */

    public function test_un_visitante_no_puede_ver_los_reportes(): void
    {
        $this->get(route('admin.reportes.por-mes'))->assertRedirect(route('login'));
    }

    public function test_un_cliente_no_puede_ver_los_reportes(): void
    {
        $cliente = User::factory()->create();

        foreach ([
            route('admin.panel'),
            route('admin.reportes.por-mes'),
            route('admin.reportes.por-cliente'),
            route('admin.reportes.por-mes.pdf'),
            route('admin.reportes.por-cliente.pdf'),
        ] as $ruta) {
            $this->actingAs($cliente)->get($ruta)->assertForbidden();
        }
    }

    /* ==================================================================
     | Reporte por mes
     | ================================================================ */

    public function test_el_reporte_por_mes_agrupa_las_ventas_correctamente(): void
    {
        $cliente = User::factory()->create();

        $this->venta($cliente, '2026-01-15 10:00:00', 100000);
        $this->venta($cliente, '2026-01-20 11:00:00', 50000);
        $this->venta($cliente, '2026-03-05 12:00:00', 75000);

        $ventas = app(ReporteService::class)->ventasPorMes(2026);

        // Se devuelven los 12 meses, aunque algunos estén en cero.
        $this->assertCount(12, $ventas);

        $enero = $ventas->firstWhere('mes', 1);
        $this->assertSame(2, $enero->pedidos);
        $this->assertSame(150000.0, $enero->total);

        $marzo = $ventas->firstWhere('mes', 3);
        $this->assertSame(1, $marzo->pedidos);
        $this->assertSame(75000.0, $marzo->total);

        $febrero = $ventas->firstWhere('mes', 2);
        $this->assertSame(0, $febrero->pedidos);
        $this->assertSame(0.0, $febrero->total);
    }

    public function test_el_reporte_por_mes_ignora_los_pedidos_no_pagados(): void
    {
        $cliente = User::factory()->create();

        $this->venta($cliente, '2026-05-10 10:00:00', 100000);

        // Un pedido con el pago rechazado no es una venta.
        Order::factory()->create([
            'user_id'      => $cliente->id,
            'total'        => 999999,
            'estado_pago'  => 'rechazado',
            'fecha_compra' => '2026-05-11 10:00:00',
        ]);

        $mayo = app(ReporteService::class)->ventasPorMes(2026)->firstWhere('mes', 5);

        $this->assertSame(1, $mayo->pedidos);
        $this->assertSame(100000.0, $mayo->total);
    }

    public function test_muestra_la_pantalla_del_reporte_por_mes(): void
    {
        $cliente = User::factory()->create();
        $this->venta($cliente, now()->startOfYear()->addDays(10)->toDateTimeString(), 120000);

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-mes', ['anio' => now()->year]))
            ->assertOk()
            ->assertSee('Reporte de ventas por mes')
            ->assertSee('₡120 000');
    }

    public function test_genera_el_reporte_por_mes_en_pdf(): void
    {
        $cliente = User::factory()->create();
        $this->venta($cliente, '2026-02-14 10:00:00', 88000);

        $respuesta = $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-mes.pdf', ['anio' => 2026]));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('reporte-ventas-por-mes-2026.pdf');

        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_valida_el_anio_del_reporte(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-mes', ['anio' => 'no-es-un-anio']))
            ->assertSessionHasErrors('anio');

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-mes', ['anio' => 1500]))
            ->assertSessionHasErrors('anio');
    }

    /* ==================================================================
     | Reporte por cliente
     | ================================================================ */

    public function test_el_reporte_por_cliente_agrupa_y_ordena_por_monto(): void
    {
        $maria  = User::factory()->create(['name' => 'María Rodríguez']);
        $carlos = User::factory()->create(['name' => 'Carlos Vargas']);

        $this->venta($maria, '2026-04-01 10:00:00', 30000);
        $this->venta($maria, '2026-04-15 10:00:00', 20000);
        $this->venta($carlos, '2026-04-20 10:00:00', 200000);

        $clientes = app(ReporteService::class)->ventasPorCliente(
            Carbon::parse('2026-04-01'),
            Carbon::parse('2026-04-30')
        );

        $this->assertCount(2, $clientes);

        // Ordenado de mayor a menor monto.
        $this->assertSame('Carlos Vargas', $clientes->first()->cliente);
        $this->assertSame(200000.0, $clientes->first()->total);

        $segundo = $clientes->last();
        $this->assertSame('María Rodríguez', $segundo->cliente);
        $this->assertSame(2, $segundo->pedidos);
        $this->assertSame(50000.0, $segundo->total);
        $this->assertSame(25000.0, $segundo->promedio);
    }

    public function test_el_reporte_por_cliente_respeta_el_rango_de_fechas(): void
    {
        $cliente = User::factory()->create();

        $this->venta($cliente, '2026-01-10 10:00:00', 10000);
        $this->venta($cliente, '2026-06-10 10:00:00', 90000);

        $clientes = app(ReporteService::class)->ventasPorCliente(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-30')
        );

        $this->assertCount(1, $clientes);
        $this->assertSame(90000.0, $clientes->first()->total);
    }

    public function test_muestra_la_pantalla_del_reporte_por_cliente(): void
    {
        $cliente = User::factory()->create(['name' => 'Luis Fernández Ruiz']);
        $this->venta($cliente, now()->subDays(3)->toDateTimeString(), 65000);

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-cliente'))
            ->assertOk()
            ->assertSee('Reporte de ventas por cliente')
            ->assertSee('Luis Fernández Ruiz')
            ->assertSee('₡65 000');
    }

    public function test_genera_el_reporte_por_cliente_en_pdf(): void
    {
        $cliente = User::factory()->create(['name' => 'Ana Solís Castro']);
        $this->venta($cliente, now()->subDay()->toDateTimeString(), 45000);

        $respuesta = $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-cliente.pdf'));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('reporte-ventas-por-cliente.pdf');

        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_genera_el_reporte_individual_de_un_cliente_en_pdf(): void
    {
        $cliente = User::factory()->create(['name' => 'Ana Solís Castro']);
        $this->venta($cliente, now()->subDay()->toDateTimeString(), 45000, unidades: 3);

        $respuesta = $this->actingAs($this->admin)
            ->get(route('admin.reportes.cliente.pdf', $cliente));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    public function test_valida_el_rango_de_fechas(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reportes.por-cliente', [
                'desde' => '2026-06-30',
                'hasta' => '2026-01-01',   // anterior a "desde"
            ]))
            ->assertSessionHasErrors('hasta');
    }

    /* ==================================================================
     | Productos más vendidos y resumen
     | ================================================================ */

    public function test_calcula_los_productos_mas_vendidos(): void
    {
        $cliente = User::factory()->create();
        $pedido  = $this->venta($cliente, '2026-07-01 10:00:00', 50000);

        $pedido->lineas()->create([
            'nombre_producto' => 'Memoria USB Kingston',
            'sku'             => 'ACC-KIN-002',
            'precio_unitario' => 9500,
            'cantidad'        => 8,
            'subtotal'        => 76000,
        ]);

        $masVendidos = app(ReporteService::class)->productosMasVendidos(5);

        $this->assertSame('Memoria USB Kingston', $masVendidos->first()->producto);
        $this->assertSame(8, $masVendidos->first()->unidades);
        $this->assertSame(76000.0, $masVendidos->first()->total);
    }

    public function test_calcula_el_resumen_general(): void
    {
        $cliente = User::factory()->create();
        $otro    = User::factory()->create();

        $this->venta($cliente, '2026-08-01 10:00:00', 100000);
        $this->venta($otro, '2026-08-02 10:00:00', 50000);

        $resumen = app(ReporteService::class)->resumen();

        $this->assertSame(2, $resumen['pedidos']);
        $this->assertSame(150000.0, $resumen['ventas']);
        $this->assertSame(75000.0, $resumen['ticket_promedio']);
        $this->assertSame(2, $resumen['clientes']);
    }

    public function test_el_resumen_no_divide_entre_cero_sin_ventas(): void
    {
        $resumen = app(ReporteService::class)->resumen();

        $this->assertSame(0, $resumen['pedidos']);
        $this->assertSame(0.0, $resumen['ticket_promedio']);
    }

    public function test_el_panel_de_administracion_muestra_los_indicadores(): void
    {
        $cliente = User::factory()->create();
        $this->venta($cliente, now()->toDateTimeString(), 90000);

        $this->actingAs($this->admin)
            ->get(route('admin.panel'))
            ->assertOk()
            ->assertSee('Panel de administración')
            ->assertSee('₡90 000');
    }
}
