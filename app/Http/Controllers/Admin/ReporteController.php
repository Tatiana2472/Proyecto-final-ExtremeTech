<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Reportes de ventas por mes y por cliente, en pantalla y en PDF.
 */
class ReporteController extends Controller
{
    public function __construct(protected ReporteService $reportes)
    {
    }

    /* ======================================================================
     | Reporte de ventas por mes
     | ==================================================================== */

    public function porMes(Request $peticion): View
    {
        $anio = $this->anioValidado($peticion);

        return view('admin.reportes.por-mes', [
            'anio'        => $anio,
            'anios'       => $this->reportes->aniosConVentas(),
            'ventas'      => $this->reportes->ventasPorMes($anio),
            'masVendidos' => $this->reportes->productosMasVendidos(
                10,
                Carbon::create($anio, 1, 1),
                Carbon::create($anio, 12, 31)
            ),
            'resumen' => $this->reportes->resumen(
                Carbon::create($anio, 1, 1),
                Carbon::create($anio, 12, 31)
            ),
        ]);
    }

    /** Genera el reporte de ventas por mes en PDF. */
    public function porMesPdf(Request $peticion): Response
    {
        $anio = $this->anioValidado($peticion);

        $pdf = Pdf::loadView('pdf.reporte-por-mes', [
            'anio'        => $anio,
            'ventas'      => $this->reportes->ventasPorMes($anio),
            'masVendidos' => $this->reportes->productosMasVendidos(
                10,
                Carbon::create($anio, 1, 1),
                Carbon::create($anio, 12, 31)
            ),
            'resumen' => $this->reportes->resumen(
                Carbon::create($anio, 1, 1),
                Carbon::create($anio, 12, 31)
            ),
            'generadoPor' => $peticion->user()->name,
        ])->setPaper('letter');

        return $pdf->download("reporte-ventas-por-mes-{$anio}.pdf");
    }

    /* ======================================================================
     | Reporte de ventas por cliente
     | ==================================================================== */

    public function porCliente(Request $peticion): View
    {
        [$desde, $hasta] = $this->rangoValidado($peticion);

        return view('admin.reportes.por-cliente', [
            'clientes' => $this->reportes->ventasPorCliente($desde, $hasta),
            'resumen'  => $this->reportes->resumen($desde, $hasta),
            'desde'    => $desde,
            'hasta'    => $hasta,
        ]);
    }

    /** Genera el reporte de ventas por cliente en PDF. */
    public function porClientePdf(Request $peticion): Response
    {
        [$desde, $hasta] = $this->rangoValidado($peticion);

        $pdf = Pdf::loadView('pdf.reporte-por-cliente', [
            'clientes'    => $this->reportes->ventasPorCliente($desde, $hasta),
            'resumen'     => $this->reportes->resumen($desde, $hasta),
            'desde'       => $desde,
            'hasta'       => $hasta,
            'generadoPor' => $peticion->user()->name,
        ])->setPaper('letter');

        return $pdf->download('reporte-ventas-por-cliente.pdf');
    }

    /** Reporte detallado de un cliente en particular, en PDF. */
    public function clienteDetallePdf(Request $peticion, User $cliente): Response
    {
        [$desde, $hasta] = $this->rangoValidado($peticion);

        $pedidos = $this->reportes->pedidosDeCliente($cliente, $desde, $hasta);

        $pdf = Pdf::loadView('pdf.reporte-cliente-detalle', [
            'cliente'     => $cliente,
            'pedidos'     => $pedidos,
            'desde'       => $desde,
            'hasta'       => $hasta,
            'generadoPor' => $peticion->user()->name,
        ])->setPaper('letter');

        return $pdf->download('reporte-cliente-'.$cliente->id.'.pdf');
    }

    /* ======================================================================
     | Validación de los parámetros de los reportes
     | ==================================================================== */

    private function anioValidado(Request $peticion): int
    {
        $datos = $peticion->validate([
            'anio' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        return (int) ($datos['anio'] ?? now()->year);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rangoValidado(Request $peticion): array
    {
        $datos = $peticion->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $desde = isset($datos['desde'])
            ? Carbon::parse($datos['desde'])
            : now()->startOfYear();

        $hasta = isset($datos['hasta'])
            ? Carbon::parse($datos['hasta'])
            : now();

        return [$desde, $hasta];
    }
}
