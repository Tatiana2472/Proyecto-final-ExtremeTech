<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

/**
 * Borra los carritos de visitantes anónimos que quedaron abandonados.
 *
 * Cada visitante sin sesión iniciada genera una fila en «carts» la primera vez
 * que ve el carrito. La mayoría nunca llega a comprar ni a registrarse, así que
 * esas filas se acumulan sin que nada las recoja: solo los carritos que el
 * usuario "adopta" al iniciar sesión se eliminan (CarritoService::adoptarCarrito).
 *
 * Los carritos con dueño (user_id) NO se tocan: son del cliente y debe poder
 * volver a encontrarlos.
 *
 * Ejemplos:
 *   php artisan carritos:limpiar --simular
 *   php artisan carritos:limpiar
 *   php artisan carritos:limpiar --dias=7
 */
class LimpiarCarritos extends Command
{
    protected $signature = 'carritos:limpiar
        {--dias=30 : Días sin actividad a partir de los cuales se considera abandonado}
        {--simular : Muestra cuántos se borrarían sin borrar nada}';

    protected $description = 'Elimina los carritos de visitantes anónimos abandonados';

    public function handle(): int
    {
        $dias  = max(1, (int) $this->option('dias'));
        $corte = now()->subDays($dias);

        // Solo carritos sin dueño y sin actividad desde la fecha de corte.
        $abandonados = Cart::whereNull('user_id')->where('updated_at', '<', $corte);

        $cantidad = (clone $abandonados)->count();

        if ($cantidad === 0) {
            $this->info("No hay carritos anónimos con más de {$dias} días sin actividad.");

            return self::SUCCESS;
        }

        if ($this->option('simular')) {
            $this->warn("Se borrarían {$cantidad} carritos anónimos (simulación: no se borró nada).");

            return self::SUCCESS;
        }

        // Las líneas se van con el carrito: la llave foránea de cart_items está
        // declarada con cascadeOnDelete.
        $abandonados->delete();

        $this->info("Carritos anónimos eliminados: {$cantidad} (sin actividad desde hace más de {$dias} días).");

        return self::SUCCESS;
    }
}
