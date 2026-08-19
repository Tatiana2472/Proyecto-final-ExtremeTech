<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas que alimentan los reportes de ventas (pantalla y PDF).
 *
 * Solo se consideran los pedidos con el pago aprobado: un pedido rechazado o
 * cancelado no es una venta.
 *
 * NOTA: las agrupaciones por mes usan la función strftime() de SQLite, que es
 * el motor definido para este proyecto. Si en el futuro se migra a MySQL o
 * PostgreSQL hay que cambiar strftime por MONTH()/DATE_TRUNC en los tres
 * métodos que la utilizan (ventasPorMes y aniosConVentas).
 */
class ReporteService
{
    /**
     * Ventas agrupadas por mes para un año dado.
     *
     * @return Collection<int, object{mes:int, nombre_mes:string, pedidos:int, unidades:int, subtotal:float, impuesto:float, envio:float, total:float}>
     */
    public function ventasPorMes(int $anio): Collection
    {
        // toBase() devuelve filas planas (stdClass) en lugar de modelos Order.
        // Es importante: si fueran modelos, los casts «decimal:2» convertirían
        // las sumas en cadenas y los montos del reporte dejarían de ser números.
        $filas = Order::pagados()
            ->whereYear('fecha_compra', $anio)
            ->toBase()
            ->selectRaw('CAST(strftime("%m", fecha_compra) AS INTEGER) as mes')
            ->selectRaw('COUNT(*) as pedidos')
            ->selectRaw('SUM(subtotal) as subtotal')
            ->selectRaw('SUM(impuesto) as impuesto')
            ->selectRaw('SUM(envio) as envio')
            ->selectRaw('SUM(total) as total')
            ->groupBy('mes')
            ->get()
            ->keyBy('mes');

        // Unidades vendidas por mes (se consulta aparte para no duplicar los
        // montos del pedido al unir con el detalle).
        $unidades = OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.estado_pago', 'aprobado')
            ->whereYear('orders.fecha_compra', $anio)
            ->selectRaw('CAST(strftime("%m", orders.fecha_compra) AS INTEGER) as mes')
            ->selectRaw('SUM(order_items.cantidad) as unidades')
            ->groupBy('mes')
            ->pluck('unidades', 'mes');

        // Se devuelven los 12 meses, incluso los que no tuvieron ventas, para
        // que el reporte y el gráfico queden completos.
        return collect(range(1, 12))->map(function (int $mes) use ($filas, $unidades, $anio) {
            $fila = $filas->get($mes);

            return (object) [
                'mes'        => $mes,
                'nombre_mes' => Carbon::create($anio, $mes, 1)->locale('es')->monthName,
                'pedidos'    => (int) ($fila->pedidos ?? 0),
                'unidades'   => (int) ($unidades[$mes] ?? 0),
                'subtotal'   => (float) ($fila->subtotal ?? 0),
                'impuesto'   => (float) ($fila->impuesto ?? 0),
                'envio'      => (float) ($fila->envio ?? 0),
                'total'      => (float) ($fila->total ?? 0),
            ];
        });
    }

    /**
     * Ventas agrupadas por cliente dentro de un rango de fechas.
     *
     * @return Collection<int, object{user_id:int, cliente:string, correo:string, pedidos:int, total:float, promedio:float, ultima_compra:string|null}>
     */
    public function ventasPorCliente(?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        return Order::pagados()
            ->toBase()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->when($desde, fn ($q) => $q->where('orders.fecha_compra', '>=', $desde->startOfDay()))
            ->when($hasta, fn ($q) => $q->where('orders.fecha_compra', '<=', $hasta->endOfDay()))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc(DB::raw('SUM(orders.total)'))
            ->get([
                'users.id as user_id',
                'users.name as cliente',
                'users.email as correo',
                DB::raw('COUNT(orders.id) as pedidos'),
                DB::raw('SUM(orders.total) as total'),
                DB::raw('AVG(orders.total) as promedio'),
                DB::raw('MAX(orders.fecha_compra) as ultima_compra'),
            ])
            ->map(function ($fila) {
                $fila->pedidos  = (int) $fila->pedidos;
                $fila->total    = (float) $fila->total;
                $fila->promedio = (float) $fila->promedio;

                return $fila;
            });
    }

    /**
     * Detalle de los pedidos de un cliente (para el reporte individual).
     */
    public function pedidosDeCliente(User $cliente, ?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        return Order::pagados()
            ->where('user_id', $cliente->id)
            ->when($desde, fn ($q) => $q->where('fecha_compra', '>=', $desde->startOfDay()))
            ->when($hasta, fn ($q) => $q->where('fecha_compra', '<=', $hasta->endOfDay()))
            ->with('lineas')
            ->orderByDesc('fecha_compra')
            ->get();
    }

    /**
     * Productos más vendidos.
     */
    public function productosMasVendidos(int $limite = 10, ?Carbon $desde = null, ?Carbon $hasta = null): Collection
    {
        return OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.estado_pago', 'aprobado')
            ->when($desde, fn ($q) => $q->where('orders.fecha_compra', '>=', $desde->startOfDay()))
            ->when($hasta, fn ($q) => $q->where('orders.fecha_compra', '<=', $hasta->endOfDay()))
            ->groupBy('order_items.nombre_producto', 'order_items.sku')
            ->orderByDesc(DB::raw('SUM(order_items.cantidad)'))
            ->limit($limite)
            ->get([
                'order_items.nombre_producto as producto',
                'order_items.sku',
                DB::raw('SUM(order_items.cantidad) as unidades'),
                DB::raw('SUM(order_items.subtotal) as total'),
            ])
            ->map(function ($fila) {
                $fila->unidades = (int) $fila->unidades;
                $fila->total    = (float) $fila->total;

                return $fila;
            });
    }

    /**
     * Totales generales para el panel de administración.
     *
     * @return array{pedidos:int, ventas:float, impuestos:float, ticket_promedio:float, clientes:int}
     */
    public function resumen(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $consulta = Order::pagados()
            ->when($desde, fn ($q) => $q->where('fecha_compra', '>=', $desde->startOfDay()))
            ->when($hasta, fn ($q) => $q->where('fecha_compra', '<=', $hasta->endOfDay()));

        $pedidos = (clone $consulta)->count();
        $ventas  = (float) (clone $consulta)->sum('total');

        return [
            'pedidos'         => $pedidos,
            'ventas'          => $ventas,
            'impuestos'       => (float) (clone $consulta)->sum('impuesto'),
            'ticket_promedio' => $pedidos > 0 ? round($ventas / $pedidos, 2) : 0.0,
            'clientes'        => (int) (clone $consulta)->distinct('user_id')->count('user_id'),
        ];
    }

    /** Años en los que existen ventas registradas, para el selector del reporte. */
    public function aniosConVentas(): Collection
    {
        $anios = Order::pagados()
            ->selectRaw('DISTINCT CAST(strftime("%Y", fecha_compra) AS INTEGER) as anio')
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anio) => (int) $anio);

        return $anios->isEmpty() ? collect([now()->year]) : $anios;
    }
}
