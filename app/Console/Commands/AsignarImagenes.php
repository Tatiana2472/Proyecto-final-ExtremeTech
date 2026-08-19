<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalogo\GeneradorIlustraciones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Asigna fotografías reales a los productos del catálogo.
 *
 * Los productos escritos a mano en el seeder traen ilustraciones SVG,
 * mientras que los importados traen fotos. Este comando iguala las dos
 * cosas usando el CDN público de DummyJSON.
 *
 * Orden de prioridad para cada producto:
 *   1. Una foto propia en public/img/productos/propias/{slug}.{jpg|png|webp}
 *   2. Una foto de DummyJSON de la categoría equivalente
 *   3. La ilustración SVG que ya tenía (no se toca)
 *
 * Ejemplos:
 *   php artisan catalogo:imagenes --simular
 *   php artisan catalogo:imagenes
 *   php artisan catalogo:imagenes --todos
 */
class AsignarImagenes extends Command
{
    protected $signature = 'catalogo:imagenes
        {--ilustraciones : Genera ilustraciones propias en vez de buscar fotografías}
        {--simular : Muestra los cambios sin guardarlos}
        {--todos : Reasigna también los productos que ya tienen fotografía}
        {--restaurar : Devuelve los productos del seeder a su ilustración SVG}
        {--pendientes : Genera una lista de las fotos que hay que conseguir}';

    protected $description = 'Asigna fotografías reales a los productos del catálogo';

    public function handle(): int
    {
        if ($this->option('restaurar')) {
            return $this->restaurar();
        }

        if ($this->option('ilustraciones')) {
            return $this->ilustraciones();
        }

        if ($this->option('pendientes')) {
            return $this->pendientes();
        }

        $simular = (bool) $this->option('simular');
        $mapeo = (array) config('catalogo_externo.fotos', []);
        $asignadas = $propias = 0;
        $sinFuente = [];
        $filas = [];

        foreach (Category::all() as $categoria) {
            $productos = Product::where('category_id', $categoria->id)
                ->orderBy('id')
                ->get()
                ->filter(fn (Product $p) => $this->option('todos') || ! $this->tieneFoto($p));

            if ($productos->isEmpty()) {
                continue;
            }

            // Fotos disponibles para esta categoría local.
            try {
                $fotos = $this->fotosDe($mapeo[$categoria->slug] ?? []);
            } catch (Throwable $e) {
                $this->error('No se pudieron obtener fotos: '.$e->getMessage());

                return self::FAILURE;
            }

            $indice = 0;

            foreach ($productos as $producto) {
                // 1) Una foto propia siempre gana.
                if ($ruta = $this->fotoPropia($producto)) {
                    $filas[] = [Str::limit($producto->nombre, 38), $categoria->nombre, 'propia'];
                    $propias++;

                    if (! $simular) {
                        $producto->update(['imagen' => $ruta]);
                    }

                    continue;
                }

                // 2) Una foto del CDN, repartidas en orden y sin repetir
                //    hasta agotar la lista.
                if ($fotos === []) {
                    $sinFuente[$categoria->nombre] = ($sinFuente[$categoria->nombre] ?? 0) + 1;

                    continue;
                }

                $url = $fotos[$indice % count($fotos)];
                $indice++;
                $asignadas++;
                $filas[] = [Str::limit($producto->nombre, 38), $categoria->nombre, 'DummyJSON'];

                if (! $simular) {
                    $producto->update(['imagen' => $url]);
                }
            }
        }

        $this->newLine();

        if ($filas !== []) {
            $this->table(['Producto', 'Categoría', 'Origen de la foto'], $filas);
        }

        foreach ($sinFuente as $nombre => $cantidad) {
            $this->warn(
                "{$nombre}: {$cantidad} productos conservan su ilustración "
                .'(DummyJSON no publica fotos de esta categoría).'
            );
        }

        $carpeta = (string) config('catalogo_externo.fotos_propias');
        $this->line("Para usar fotos propias, colóquelas en public/{$carpeta}/{slug}.jpg");

        $simular
            ? $this->warn('Modo simulación: no se guardó nada.')
            : $this->info("Listo. Fotos del CDN: {$asignadas}   Fotos propias: {$propias}");

        return self::SUCCESS;
    }

    /**
     * Escribe una lista de trabajo con los productos que aún no tienen
     * fotografía real: el nombre exacto del archivo que hay que guardar y
     * un enlace de búsqueda por modelo.
     */
    private function pendientes(): int
    {
        $productos = Product::with('categoria')
            ->orderBy('category_id')
            ->get()
            ->filter(fn (Product $p) => ! $this->tieneFoto($p));

        $lineas = [
            '# Fotografías pendientes',
            '',
            'Guarde cada imagen en `public/img/productos/propias/` con el nombre',
            'de archivo indicado. Se aceptan `.jpg`, `.png` y `.webp`.',
            '',
            'Al terminar ejecute: `php artisan catalogo:imagenes`',
            '',
        ];

        foreach ($productos as $producto) {
            $busqueda = 'https://www.google.com/search?tbm=isch&q='
                .urlencode($producto->nombre.' producto');

            $lineas[] = "- [ ] **{$producto->nombre}** _({$producto->categoria?->nombre})_";
            $lineas[] = "      archivo: `{$producto->slug}.jpg`";
            $lineas[] = "      buscar: {$busqueda}";
            $lineas[] = '';
        }

        $ruta = base_path('tasks/fotos-pendientes.md');

        if (! is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0755, true);
        }

        file_put_contents($ruta, implode(PHP_EOL, $lineas));

        $this->info("Faltan {$productos->count()} fotografías.");
        $this->line('Lista escrita en: tasks/fotos-pendientes.md');

        return self::SUCCESS;
    }

    /**
     * Dibuja una ilustración para cada producto que no tenga una propia.
     *
     * Es la alternativa a las fotografías: todo el catálogo queda con el
     * mismo tratamiento visual y sin depender de servidores externos, lo
     * que además permite exponer el proyecto sin conexión.
     */
    private function ilustraciones(): int
    {
        $generador = new GeneradorIlustraciones;
        $simular = (bool) $this->option('simular');
        $generadas = $propias = 0;

        $productos = Product::with('categoria')->orderBy('category_id')->get();

        foreach ($productos as $producto) {
            // Una foto propia siempre gana sobre lo generado.
            if ($ruta = $this->fotoPropia($producto)) {
                $propias++;

                if (! $simular) {
                    $producto->update(['imagen' => $ruta]);
                }

                continue;
            }

            // Las ilustraciones dibujadas a mano del catálogo original se
            // respetan: no hay razón para reemplazarlas.
            if (! $this->option('todos') && $this->tieneIlustracionPropia($producto)) {
                continue;
            }

            $generadas++;

            if (! $simular) {
                $producto->update(['imagen' => $generador->generar($producto)]);
            }
        }

        $this->newLine();

        $simular
            ? $this->warn("Modo simulación: se generarían {$generadas} ilustraciones.")
            : $this->info("Listo. Ilustraciones generadas: {$generadas}   Fotos propias: {$propias}");

        $this->line('Todo el catálogo usa ahora el mismo estilo visual, sin depender de internet.');

        return self::SUCCESS;
    }

    /** ¿Tiene ya una ilustración dibujada a mano en img/productos/? */
    private function tieneIlustracionPropia(Product $producto): bool
    {
        return is_file(public_path('img/productos/'.$producto->slug.'.svg'));
    }

    /* ----------------------------------------------------------------------
     | Ayudas
     | -------------------------------------------------------------------- */

    /** Un producto «tiene foto» si su imagen es una URL o un archivo propio. */
    private function tieneFoto(Product $producto): bool
    {
        $imagen = (string) $producto->imagen;

        return Str::startsWith($imagen, ['http://', 'https://'])
            || Str::contains($imagen, (string) config('catalogo_externo.fotos_propias'));
    }

    /** Busca public/img/productos/propias/{slug}.{jpg|png|webp}. */
    private function fotoPropia(Product $producto): ?string
    {
        $carpeta = trim((string) config('catalogo_externo.fotos_propias'), '/');

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $relativa = "{$carpeta}/{$producto->slug}.{$extension}";

            if (is_file(public_path($relativa))) {
                return $relativa;
            }
        }

        return null;
    }

    /**
     * Reúne las URL de fotografía de una o varias categorías de DummyJSON.
     *
     * @param  list<string>  $categorias
     * @return list<string>
     */
    private function fotosDe(array $categorias): array
    {
        if ($categorias === []) {
            return [];
        }

        $base = rtrim((string) config('catalogo_externo.dummyjson.url'), '/');
        $urls = [];

        foreach ($categorias as $slug) {
            $respuesta = Http::withHeaders([
                'User-Agent' => (string) config('catalogo_externo.user_agent'),
                'Accept' => 'application/json',
            ])
                ->timeout((int) config('catalogo_externo.timeout_segundos', 20))
                ->retry(2, 1000, throw: false)
                ->get("{$base}/products/category/".urlencode($slug), ['limit' => 0]);

            if (! $respuesta->successful()) {
                continue;
            }

            foreach ((array) ($respuesta->json('products') ?? []) as $producto) {
                $url = ((array) ($producto['images'] ?? []))[0] ?? ($producto['thumbnail'] ?? null);

                // La columna `imagen` es VARCHAR(255).
                if (is_string($url) && Str::startsWith($url, 'https://') && strlen($url) <= 255) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /** Devuelve los productos del seeder a su ilustración SVG original. */
    private function restaurar(): int
    {
        $restaurados = 0;

        foreach (Product::where('sku', 'not like', 'ET-%')->get() as $producto) {
            $svg = 'img/productos/'.$producto->slug.'.svg';

            if (is_file(public_path($svg)) && $producto->imagen !== $svg) {
                $producto->update(['imagen' => $svg]);
                $restaurados++;
            }
        }

        $this->info("Se restauraron {$restaurados} ilustraciones del catálogo original.");

        return self::SUCCESS;
    }
}
