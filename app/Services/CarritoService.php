<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lógica del carrito de compras.
 *
 * Toda la manipulación del carrito pasa por acá (y no por los controladores)
 * para que exista una sola fuente de verdad y para poder probarla con pruebas
 * unitarias sin necesidad de hacer peticiones HTTP.
 */
class CarritoService
{
    /** Cantidad máxima por línea, para evitar pedidos absurdos. */
    public const CANTIDAD_MAXIMA = 20;

    /**
     * Devuelve el carrito del usuario autenticado o el del visitante anónimo,
     * creándolo si aún no existe.
     */
    public function carritoActual(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        return Cart::firstOrCreate(['token_sesion' => $this->tokenSesion()]);
    }

    /**
     * Token estable guardado en la sesión para identificar el carrito de un
     * visitante que todavía no se ha registrado.
     */
    protected function tokenSesion(): string
    {
        if (! session()->has('carrito_token')) {
            session()->put('carrito_token', (string) Str::uuid());
        }

        return (string) session()->get('carrito_token');
    }

    /**
     * Agrega un producto al carrito. Si ya estaba, suma la cantidad.
     *
     * @throws RuntimeException si no hay existencias suficientes.
     */
    public function agregar(Product $producto, int $cantidad = 1): CartItem
    {
        $cantidad = $this->normalizarCantidad($cantidad);
        $carrito  = $this->carritoActual();

        return DB::transaction(function () use ($carrito, $producto, $cantidad) {
            $linea = $carrito->lineas()->where('product_id', $producto->id)->first();

            $cantidadFinal = $this->normalizarCantidad(($linea?->cantidad ?? 0) + $cantidad);

            if (! $producto->hayExistencias($cantidadFinal)) {
                throw new RuntimeException(
                    "Solo quedan {$producto->existencias} unidades de «{$producto->nombre}»."
                );
            }

            if ($linea) {
                $linea->update(['cantidad' => $cantidadFinal]);

                return $linea;
            }

            return $carrito->lineas()->create([
                'product_id'      => $producto->id,
                'cantidad'        => $cantidadFinal,
                'precio_unitario' => $producto->precio,
            ]);
        });
    }

    /**
     * Cambia la cantidad de una línea. Con cantidad 0 la elimina.
     */
    public function actualizar(int $lineaId, int $cantidad): ?CartItem
    {
        $linea = $this->buscarLinea($lineaId);

        if ($cantidad <= 0) {
            $linea->delete();

            return null;
        }

        $cantidad = $this->normalizarCantidad($cantidad);
        $producto = $linea->producto;

        if ($producto && ! $producto->hayExistencias($cantidad)) {
            throw new RuntimeException(
                "Solo quedan {$producto->existencias} unidades de «{$producto->nombre}»."
            );
        }

        $linea->update(['cantidad' => $cantidad]);

        return $linea;
    }

    /** Elimina una línea del carrito. */
    public function eliminar(int $lineaId): void
    {
        $this->buscarLinea($lineaId)->delete();
    }

    /** Vacía por completo el carrito actual. */
    public function vaciar(): void
    {
        $this->carritoActual()->lineas()->delete();
    }

    /**
     * Busca una línea asegurando que pertenezca al carrito de quien la pide.
     * Sin esta comprobación un usuario podría modificar el carrito de otro
     * cambiando el id en la URL.
     */
    protected function buscarLinea(int $lineaId): CartItem
    {
        $linea = $this->carritoActual()->lineas()->find($lineaId);

        if (! $linea) {
            throw new RuntimeException('El artículo indicado no está en su carrito.');
        }

        return $linea;
    }

    /** Líneas del carrito con su producto y categoría ya cargados. */
    public function lineas(): Collection
    {
        return $this->carritoActual()
            ->lineas()
            ->with('producto.categoria')
            ->orderBy('id')
            ->get();
    }

    /**
     * Cálculo automático del total: subtotal, impuestos y costo de envío.
     */
    public function totales(): TotalesCarrito
    {
        $lineas = $this->lineas();

        $subtotal = $lineas->sum(fn (CartItem $linea) => $linea->subtotal());
        $cantidad = (int) $lineas->sum('cantidad');

        return TotalesCarrito::calcular((float) $subtotal, $cantidad);
    }

    public function cantidadArticulos(): int
    {
        return (int) $this->carritoActual()->lineas()->sum('cantidad');
    }

    /**
     * Al iniciar sesión, traslada el carrito anónimo al del usuario.
     * Si ambos tienen el mismo producto se conserva la cantidad mayor.
     */
    public function adoptarCarrito(int $usuarioId): void
    {
        $token = session()->get('carrito_token');

        if (! $token) {
            return;
        }

        $carritoAnonimo = Cart::where('token_sesion', $token)->with('lineas')->first();

        if (! $carritoAnonimo || $carritoAnonimo->lineas->isEmpty()) {
            $carritoAnonimo?->delete();

            return;
        }

        DB::transaction(function () use ($carritoAnonimo, $usuarioId) {
            $carritoUsuario = Cart::firstOrCreate(['user_id' => $usuarioId]);

            foreach ($carritoAnonimo->lineas as $linea) {
                $existente = $carritoUsuario->lineas()
                    ->where('product_id', $linea->product_id)
                    ->first();

                if ($existente) {
                    $existente->update([
                        'cantidad' => $this->normalizarCantidad(
                            max($existente->cantidad, $linea->cantidad)
                        ),
                    ]);

                    continue;
                }

                $carritoUsuario->lineas()->create([
                    'product_id'      => $linea->product_id,
                    'cantidad'        => $linea->cantidad,
                    'precio_unitario' => $linea->precio_unitario,
                ]);
            }

            $carritoAnonimo->delete();
        });

        session()->forget('carrito_token');
    }

    protected function normalizarCantidad(int $cantidad): int
    {
        return max(1, min($cantidad, self::CANTIDAD_MAXIMA));
    }
}
