<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\View\View;

/**
 * Panel de administración con los indicadores principales.
 */
class PanelController extends Controller
{
    public function __construct(protected ReporteService $reportes)
    {
    }

    public function __invoke(): View
    {
        $inicioMes = now()->startOfMonth();

        return view('admin.panel', [
            'resumenMes'   => $this->reportes->resumen($inicioMes, now()),
            'resumenTotal' => $this->reportes->resumen(),
            'ventasPorMes' => $this->reportes->ventasPorMes(now()->year),
            'masVendidos'  => $this->reportes->productosMasVendidos(5),
            'ultimos'      => Order::with('usuario')->latest('fecha_compra')->take(8)->get(),
            'conteos'      => [
                'productos'   => Product::count(),
                'sinStock'    => Product::where('existencias', 0)->count(),
                'clientes'    => User::where('es_admin', false)->count(),
                'pendientes'  => Order::where('estado', 'pendiente')->count(),
            ],
        ]);
    }
}
