<?php

namespace App\Services\Catalogo;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Traduce un ProductoExterno al modelo Product de esta tienda.
 *
 * Separado del cliente HTTP a propósito: acá no hay red, solo reglas de
 * negocio, y por eso se puede probar con pruebas unitarias puras.
 */
class ImportadorCatalogo
{
    /** Caché en memoria de categorías, para no consultar la base por producto. */
    private array $categoriasPorSlug = [];

    public function __construct(
        private readonly string $prefijoSku = 'ET',
        private readonly bool $descargarImagenes = false,
        private readonly int $variantes = 1,
    ) {}

    /**
     * Crea o actualiza el producto local. Devuelve el modelo y si fue nuevo.
     *
     * @return array{producto: Product, creado: bool}
     */
    public function importar(ProductoExterno $externo, ?int $variante = null): array
    {
        $sku = $this->sku($externo, $variante);
        $existente = Product::where('sku', $sku)->first();

        $atributos = [
            'category_id' => $this->categoriaPara($externo)->id,
            'nombre' => $externo->nombre,
            'slug' => $existente?->slug ?? $this->slugUnico($externo, $sku),
            'marca' => $externo->marca,
            'resumen' => $externo->resumen ?? Str::limit($externo->descripcion ?? $externo->nombre, 250),
            'descripcion' => $externo->descripcion,
            'precio' => $externo->precio,
            'precio_anterior' => $externo->precioAnterior,
            'existencias' => $externo->existencias,
            'imagen' => $this->imagen($externo),
            'activo' => true,
        ];

        // updateOrCreate por SKU hace la operación idempotente: correr el
        // comando dos veces actualiza los precios en lugar de duplicar filas.
        $producto = Product::updateOrCreate(['sku' => $sku], $atributos);

        return ['producto' => $producto, 'creado' => $existente === null];
    }

    /**
     * Importa el producto base y, si se pidieron variantes, sus versiones en
     * otras capacidades o colores.
     *
     * Las tiendas reales publican el mismo modelo varias veces (128 GB,
     * 256 GB, 512 GB). Generarlas amplía el catálogo de forma realista, con
     * marcas repetidas, en lugar de inventar productos que no existen.
     *
     * @return array{creados: int, actualizados: int}
     */
    public function importarConVariantes(ProductoExterno $externo): array
    {
        $creados = $actualizados = 0;
        $categoria = $this->categoriaPara($externo);
        $etiquetas = $this->etiquetasDeVariante($categoria->slug);

        foreach (array_slice($etiquetas, 0, max(1, $this->variantes), true) as $posicion => $par) {
            [$etiqueta, $factor] = $par;

            $resultado = $this->importar(
                $posicion === 0 ? $externo : $this->conVariante($externo, $etiqueta, $factor),
                $posicion === 0 ? null : $posicion + 1,
            );

            $resultado['creado'] ? $creados++ : $actualizados++;
        }

        return ['creados' => $creados, 'actualizados' => $actualizados];
    }

    /** Copia el producto cambiando nombre y precio según la variante. */
    private function conVariante(ProductoExterno $base, string $etiqueta, float $factor): ProductoExterno
    {
        $precio = round($base->precio * $factor / 100) * 100;
        $anterior = $base->precioAnterior !== null
            ? round($base->precioAnterior * $factor / 100) * 100
            : null;

        return new ProductoExterno(
            idExterno: $base->idExterno,
            nombre: Str::limit($base->nombre.' '.$etiqueta, 155, ''),
            marca: $base->marca,
            precio: $precio,
            precioAnterior: ($anterior !== null && $anterior > $precio) ? $anterior : null,
            resumen: $base->resumen,
            descripcion: trim(($base->descripcion ?? '').' Presentación: '.$etiqueta.'.'),
            imagenUrl: $base->imagenUrl,
            existencias: max(1, (int) round($base->existencias / 2)),
            categoriasExternas: $base->categoriasExternas,
        );
    }

    /** @return list<array{0: string, 1: float}> */
    private function etiquetasDeVariante(string $slugCategoria): array
    {
        $mapa = (array) config('catalogo_externo.dummyjson.variantes', []);
        $tabla = $mapa[$slugCategoria] ?? $mapa['predeterminado'] ?? ['' => 1.0];

        $salida = [];

        foreach ($tabla as $etiqueta => $factor) {
            $salida[] = [(string) $etiqueta, (float) $factor];
        }

        return $salida;
    }

    /** Borra únicamente los productos importados (los del seeder no se tocan). */
    public function purgar(): int
    {
        return Product::where('sku', 'like', $this->prefijoSku.'-%')->delete();
    }

    /* ----------------------------------------------------------------------
     | Reglas de traducción
     | -------------------------------------------------------------------- */

    /** SKU determinista: ET-1234. Sirve de llave para no duplicar. */
    public function sku(ProductoExterno $externo, ?int $variante = null): string
    {
        $sufijo = $variante !== null ? '-V'.$variante : '';

        return Str::limit($this->prefijoSku.'-'.$externo->idExterno.$sufijo, 40, '');
    }

    /**
     * Elige la categoría local revisando primero las categorías que trae el
     * producto y, si no hay coincidencia, el nombre del producto.
     */
    public function categoriaPara(ProductoExterno $externo): Category
    {
        $mapeo = (array) config('catalogo_externo.mapeo_categorias', []);

        $textoCategorias = $this->normalizar(implode(' ', $externo->categoriasExternas));
        $textoNombre = $this->normalizar($externo->nombre);

        foreach ([$textoCategorias, $textoNombre] as $texto) {
            if ($texto === '') {
                continue;
            }

            foreach ($mapeo as $slugLocal => $palabras) {
                foreach ((array) $palabras as $palabra) {
                    if (str_contains($texto, $this->normalizar($palabra))) {
                        return $this->categoria($slugLocal);
                    }
                }
            }
        }

        return $this->categoria((string) config('catalogo_externo.categoria_por_defecto', 'accesorios'));
    }

    /**
     * Genera un slug que no choque con un producto ya existente. La columna
     * es única, así que un duplicado reventaría la importación completa.
     */
    private function slugUnico(ProductoExterno $externo, string $sku): string
    {
        $base = Str::limit(Str::slug($externo->nombre), 160, '') ?: 'producto';

        if (! Product::where('slug', $base)->exists()) {
            return $base;
        }

        // El SKU es único por definición, así que sirve de desempate.
        $sufijo = Str::slug($sku);

        return Str::limit($base, 160 - strlen($sufijo) - 1, '').'-'.$sufijo;
    }

    /**
     * Por defecto se guarda la URL de la imagen en el sitio de origen (no se
     * copia el archivo). Con --imagenes se descarga a public/img/productos/
     * externos/ para poder demostrar el proyecto sin conexión.
     */
    private function imagen(ProductoExterno $externo): ?string
    {
        if ($externo->imagenUrl === null) {
            return null;
        }

        if (! $this->descargarImagenes) {
            return $externo->imagenUrl;
        }

        $extension = pathinfo(parse_url($externo->imagenUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $extension = in_array(Str::lower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? Str::lower($extension)
            : 'jpg';

        $relativa = 'img/productos/externos/'.$this->sku($externo).'.'.$extension;  // SKU base: las variantes comparten foto
        $destino = public_path($relativa);

        if (file_exists($destino)) {
            return $relativa;
        }

        try {
            $respuesta = Http::timeout(20)
                ->withHeaders(['User-Agent' => (string) config('catalogo_externo.user_agent')])
                ->get($externo->imagenUrl);

            if (! $respuesta->successful()) {
                return $externo->imagenUrl;
            }

            if (! is_dir(dirname($destino))) {
                mkdir(dirname($destino), 0755, true);
            }

            file_put_contents($destino, $respuesta->body());

            return $relativa;
        } catch (\Throwable) {
            // Si la descarga falla se usa la URL remota: nunca se pierde la imagen.
            return $externo->imagenUrl;
        }
    }

    /** Busca la categoría local por slug; si no existe, la crea. */
    private function categoria(string $slug): Category
    {
        return $this->categoriasPorSlug[$slug] ??= Category::firstOrCreate(
            ['slug' => $slug],
            ['nombre' => Str::headline(str_replace('-', ' ', $slug)), 'activa' => true],
        );
    }

    /** Minúsculas y sin tildes, para comparar "Periféricos" con "perifericos". */
    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
