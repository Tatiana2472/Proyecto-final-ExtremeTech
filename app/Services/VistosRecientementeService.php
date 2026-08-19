<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

/**
 * Productos vistos recientemente, guardados en una COOKIE del navegador.
 *
 * Se eligió una cookie (y no la sesión ni la base de datos) porque el
 * requisito es que el usuario vea los últimos productos que visitó "mientras
 * navega por la tienda", incluso sin haber iniciado sesión y aunque cierre el
 * navegador. La cookie guarda únicamente los identificadores numéricos de los
 * productos: no contiene datos personales.
 */
class VistosRecientementeService
{
    /**
     * Registra un producto como visto.
     *
     * El id se coloca al inicio de la lista (el más reciente primero), se
     * eliminan duplicados y se recorta al máximo configurado.
     */
    public function registrar(Product $producto): void
    {
        $ids = $this->ids();

        // Se quita el producto si ya estaba, para volverlo a poner de primero.
        $ids = array_values(array_filter($ids, fn (int $id) => $id !== $producto->id));

        array_unshift($ids, $producto->id);

        $ids = array_slice($ids, 0, $this->maximo());

        // Cookie::queue adjunta la cookie a la respuesta que Laravel está por
        // enviar. Se firma y cifra automáticamente (EncryptCookies), por lo que
        // el usuario no puede manipular su contenido.
        Cookie::queue(
            $this->nombreCookie(),
            json_encode($ids),
            60 * 24 * $this->dias()   // minutos
        );
    }

    /**
     * Productos vistos, en el orden en que se visitaron (el último primero).
     *
     * @param  int|null  $excluirId  Producto que no se debe incluir (por
     *                               ejemplo, el que se está viendo ahora).
     */
    public function productos(?int $excluirId = null): Collection
    {
        $ids = $this->ids();

        if ($excluirId !== null) {
            $ids = array_values(array_filter($ids, fn (int $id) => $id !== $excluirId));
        }

        if ($ids === []) {
            return collect();
        }

        $productos = Product::activos()->whereIn('id', $ids)->get()->keyBy('id');

        // Se reordenan según la cookie, porque whereIn no respeta el orden.
        return collect($ids)
            ->map(fn (int $id) => $productos->get($id))
            ->filter()
            ->values();
    }

    /** Ids guardados en la cookie, saneados. */
    public function ids(): array
    {
        $crudo = Cookie::get($this->nombreCookie());

        if (blank($crudo)) {
            return [];
        }

        $decodificado = is_array($crudo) ? $crudo : json_decode((string) $crudo, true);

        if (! is_array($decodificado)) {
            return [];
        }

        // Solo se aceptan enteros positivos: la cookie nunca se usa
        // directamente en una consulta sin sanear.
        return array_values(array_unique(array_filter(
            array_map('intval', $decodificado),
            fn (int $id) => $id > 0
        )));
    }

    /** Borra el historial de productos vistos. */
    public function limpiar(): void
    {
        Cookie::queue(Cookie::forget($this->nombreCookie()));
    }

    public function nombreCookie(): string
    {
        return (string) config('tienda.vistos_recientemente.cookie', 'productos_vistos');
    }

    protected function maximo(): int
    {
        return max(1, (int) config('tienda.vistos_recientemente.maximo', 6));
    }

    protected function dias(): int
    {
        return max(1, (int) config('tienda.vistos_recientemente.dias', 30));
    }
}
