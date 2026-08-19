<?php

namespace App\Console\Commands;

use App\Services\Catalogo\ClienteDummyJson;
use App\Services\Catalogo\ClienteTiendaExterna;
use App\Services\Catalogo\FuenteDeProductos;
use App\Services\Catalogo\ImportadorCatalogo;
use Illuminate\Console\Command;
use Throwable;

/**
 * Importa productos desde la API pública de una tienda WooCommerce.
 *
 * Ejemplos:
 *   php artisan catalogo:importar --simular
 *   php artisan catalogo:importar --paginas=3 --limite=100
 *   php artisan catalogo:importar --imagenes
 *   php artisan catalogo:importar --purgar
 */
class ImportarCatalogo extends Command
{
    protected $signature = 'catalogo:importar
        {--fuente=woocommerce : Origen de los datos: woocommerce o dummyjson}
        {--url= : Dirección de la tienda (por defecto la de CATALOGO_EXTERNO_URL)}
        {--paginas=1 : Cantidad de páginas de la API por recorrer}
        {--limite=0 : Máximo de productos por importar (0 = sin límite)}
        {--categoria= : Identificador de categoría en la tienda de origen}
        {--simular : Muestra lo que haría sin escribir en la base de datos}
        {--imagenes : Descarga las imágenes en lugar de enlazarlas}
        {--variantes=1 : Versiones por producto (capacidad o color), de 1 a 3}
        {--purgar : Borra los productos importados previamente y termina}
        {--forzar : Continúa aunque robots.txt desaconseje la ruta}';

    protected $description = 'Importa productos al catálogo desde la API pública de una tienda WooCommerce';

    public function handle(): int
    {
        $prefijo = (string) config('catalogo_externo.prefijo_sku', 'ET');
        $simular = (bool) $this->option('simular');

        $importador = new ImportadorCatalogo(
            prefijoSku: $prefijo,
            descargarImagenes: (bool) $this->option('imagenes'),
            variantes: max(1, min(3, (int) $this->option('variantes'))),
        );

        // --purgar es una operación destructiva: siempre se confirma.
        if ($this->option('purgar')) {
            return $this->purgar($importador, $prefijo);
        }

        try {
            $cliente = $this->fuente();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('Origen: <info>'.$cliente->descripcion().'</info>');

        if (! $this->verificaciones($cliente)) {
            return self::FAILURE;
        }

        $limite = (int) $this->option('limite');
        $paginas = max(1, (int) $this->option('paginas'));
        $categoria = $this->option('categoria');

        $creados = $actualizados = $descartados = 0;
        $filas = [];

        try {
            foreach ($cliente->productos($paginas, $categoria) as $externo) {
                if ($limite > 0 && ($creados + $actualizados) >= $limite) {
                    break;
                }

                // Productos sin nombre o sin precio utilizable se saltan.
                if ($externo === null) {
                    $descartados++;

                    continue;
                }

                if ($simular) {
                    $creados++;
                    $filas[] = [
                        $importador->sku($externo),
                        \Illuminate\Support\Str::limit($externo->nombre, 45),
                        \App\Support\Moneda::formato($externo->precio),
                        $importador->categoriaPara($externo)->nombre,
                    ];

                    continue;
                }

                $resultado = $importador->importarConVariantes($externo);
                $creados += $resultado['creados'];
                $actualizados += $resultado['actualizados'];
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('La importación se detuvo: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($simular) {
            $this->newLine();
            $this->table(['SKU', 'Producto', 'Precio', 'Categoría'], $filas);
            $this->warn('Modo simulación: no se escribió nada en la base de datos.');
            $this->line("Productos legibles: {$creados}   Descartados: {$descartados}");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Listo. Creados: {$creados}   Actualizados: {$actualizados}   Descartados: {$descartados}");

        return self::SUCCESS;
    }

    /**
     * Comprobaciones previas: que la API exista y que robots.txt no
     * desaconseje la ruta. Es la parte que hace defendible el proceso.
     */
    /**
     * Elige el origen según --fuente. Ambas clases cumplen la misma
     * interfaz, así que el resto del comando no cambia.
     */
    private function fuente(): FuenteDeProductos
    {
        return match ((string) $this->option('fuente')) {
            'dummyjson' => ClienteDummyJson::desdeConfiguracion(),
            'woocommerce' => ClienteTiendaExterna::desdeConfiguracion($this->option('url')),
            default => throw new \InvalidArgumentException(
                'Origen no reconocido. Use --fuente=woocommerce o --fuente=dummyjson.'
            ),
        };
    }

    private function verificaciones(FuenteDeProductos $cliente): bool
    {
        if ($cliente->rutaBloqueadaPorRobots()) {
            $this->warn('El archivo robots.txt del sitio desaconseja esta ruta para robots.');

            if (! $this->option('forzar') && ! $this->confirm('¿Continuar de todos modos?', false)) {
                $this->line('Importación cancelada.');

                return false;
            }
        }

        try {
            $total = $cliente->verificarDisponibilidad();
            $this->line("Productos publicados en el origen: <info>{$total}</info>");
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return false;
        }

        return true;
    }

    private function purgar(ImportadorCatalogo $importador, string $prefijo): int
    {
        // La confirmación se puede omitir con --forzar para poder usar el
        // comando dentro de un script de despliegue.
        if (! $this->option('forzar') && ! $this->confirm("¿Borrar todos los productos con SKU {$prefijo}-*?", false)) {
            $this->line('Operación cancelada.');

            return self::SUCCESS;
        }

        $borrados = $importador->purgar();
        $this->info("Se eliminaron {$borrados} productos importados.");

        return self::SUCCESS;
    }
}
