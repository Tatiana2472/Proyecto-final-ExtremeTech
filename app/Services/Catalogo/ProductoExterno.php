<?php

namespace App\Services\Catalogo;

use Illuminate\Support\Str;

/**
 * Producto traído de una tienda externa, ya normalizado.
 *
 * Esta clase es el «traductor»: aísla el resto del sistema del formato
 * exacto que devuelve la API. Si mañana se cambia de proveedor solo hay que
 * escribir otro método `desdeWooCommerce()`; el importador no se entera.
 *
 * Es readonly (PHP 8.2) para que una vez construido nadie pueda alterarlo
 * por accidente a mitad de la importación.
 */
readonly class ProductoExterno
{
    public function __construct(
        public string $idExterno,
        public string $nombre,
        public ?string $marca,
        public float $precio,
        public ?float $precioAnterior,
        public ?string $resumen,
        public ?string $descripcion,
        public ?string $imagenUrl,
        public int $existencias,
        public array $categoriasExternas = [],
    ) {}

    /**
     * Construye el DTO a partir de un elemento del arreglo que devuelve
     * GET /wp-json/wc/store/v1/products de WooCommerce.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function desdeWooCommerce(array $datos, int $existenciasPorDefecto): ?self
    {
        $nombre = trim((string) ($datos['name'] ?? ''));
        $id = (string) ($datos['id'] ?? '');

        // Sin nombre o sin identificador el registro no sirve para nada.
        if ($nombre === '' || $id === '') {
            return null;
        }

        $precios = is_array($datos['prices'] ?? null) ? $datos['prices'] : [];

        // WooCommerce publica los precios en la unidad mínima de la moneda.
        // Con currency_minor_unit = 2, el string "32900000" significa
        // 329 000,00 colones. Hay que dividir entre 10^minor_unit.
        $decimales = (int) ($precios['currency_minor_unit'] ?? 2);
        $precio = self::aMonto($precios['price'] ?? null, $decimales);
        $regular = self::aMonto($precios['regular_price'] ?? null, $decimales);

        // Un producto sin precio (variable, agotado o "consultar") no se
        // importa: rompería el cálculo del carrito.
        if ($precio === null || $precio <= 0) {
            return null;
        }

        // Solo se guarda el precio anterior cuando de verdad hay descuento.
        $precioAnterior = ($regular !== null && $regular > $precio) ? $regular : null;

        return new self(
            idExterno: $id,
            nombre: Str::limit($nombre, 155, ''),
            marca: self::marcaDesdeAtributos($datos) ?? self::primeraPalabra($nombre),
            precio: $precio,
            precioAnterior: $precioAnterior,
            resumen: self::textoPlano($datos['short_description'] ?? '', 250),
            descripcion: self::descripcionCompleta($datos),
            imagenUrl: self::primeraImagen($datos),
            existencias: self::existencias($datos, $existenciasPorDefecto),
            categoriasExternas: self::nombresDeCategorias($datos),
        );
    }

    /**
     * Construye el DTO a partir de un producto de DummyJSON.
     *
     * Dos diferencias importantes con WooCommerce:
     *  - los precios vienen en dólares y hay que pasarlos a colones,
     *  - `price` es el precio de lista y `discountPercentage` el descuento,
     *    así que el precio de venta se calcula, no viene dado.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function desdeDummyJson(array $datos, float $tipoCambio): ?self
    {
        $nombre = trim((string) ($datos['title'] ?? ''));
        $id = (string) ($datos['id'] ?? '');
        $precioLista = $datos['price'] ?? null;

        if ($nombre === '' || $id === '' || ! is_numeric($precioLista) || $precioLista <= 0) {
            return null;
        }

        $descuento = is_numeric($datos['discountPercentage'] ?? null)
            ? max(0.0, min(90.0, (float) $datos['discountPercentage']))
            : 0.0;

        // Se redondea a la centena de colones, que es como se muestran los
        // precios al público en Costa Rica.
        $aColones = fn (float $usd) => round($usd * $tipoCambio / 100) * 100;

        $precioAnterior = $aColones((float) $precioLista);
        $precio = $aColones((float) $precioLista * (1 - $descuento / 100));

        // Malla de seguridad: si una categoría trae algo ajeno a una tienda
        // de tecnología, el precio suele delatarlo (un reloj de lujo de
        // millones de colones no pertenece a este catálogo).
        $minimo = (float) config('catalogo_externo.dummyjson.precio_minimo', 0);
        $maximo = (float) config('catalogo_externo.dummyjson.precio_maximo', PHP_INT_MAX);

        if ($precio < $minimo || $precio > $maximo) {
            return null;
        }

        $imagen = ((array) ($datos['images'] ?? []))[0] ?? ($datos['thumbnail'] ?? null);
        $marca = self::textoPlano($datos['brand'] ?? null, 80) ?? self::primeraPalabra($nombre);

        return new self(
            idExterno: $id,
            nombre: Str::limit($nombre, 155, ''),
            marca: $marca,
            precio: $precio,
            precioAnterior: $precioAnterior > $precio ? $precioAnterior : null,
            resumen: self::resumenEnEspanol($datos, $marca),
            descripcion: self::descripcionEnEspanol($datos, $marca),
            imagenUrl: (is_string($imagen) && Str::startsWith($imagen, ['http://', 'https://']) && strlen($imagen) <= 255)
                ? $imagen
                : null,
            existencias: max(0, (int) ($datos['stock'] ?? 0)),
            categoriasExternas: array_filter([self::textoPlano($datos['category'] ?? '')]),
        );
    }

    /**
     * Arma la descripción EN ESPAÑOL.
     *
     * La API está en inglés, así que su campo `description` se descarta a
     * propósito: volcarlo dejaría el catálogo mezclado en dos idiomas. En su
     * lugar el texto se construye con los campos estructurados (tipo, marca,
     * garantía, envío, devolución), que son listas cerradas de valores y por
     * eso se traducen de forma confiable desde config/catalogo_externo.php.
     */
    private static function descripcionEnEspanol(array $datos, ?string $marca): ?string
    {
        $frases = [self::resumenEnEspanol($datos, $marca)];

        $condiciones = array_filter([
            self::traducir('garantias', $datos['warrantyInformation'] ?? null),
            self::traducir('devoluciones', $datos['returnPolicy'] ?? null),
        ]);

        if ($condiciones !== []) {
            $frases[] = 'Incluye '.implode(' y ', $condiciones).'.';
        }

        if ($envio = self::traducir('envios', $datos['shippingInformation'] ?? null)) {
            $frases[] = $envio.'.';
        }

        $frases = array_filter($frases);

        return $frases === [] ? null : implode(' ', $frases);
    }

    /** Primera frase: «Computadora portátil de la marca Apple.» */
    private static function resumenEnEspanol(array $datos, ?string $marca): ?string
    {
        $tipos = (array) config('catalogo_externo.dummyjson.tipos', []);
        $tipo = $tipos[(string) ($datos['category'] ?? '')] ?? 'Producto de tecnología';

        return $marca ? "{$tipo} de la marca {$marca}." : "{$tipo}.";
    }

    /** Busca un valor en los diccionarios de traducción de la configuración. */
    private static function traducir(string $diccionario, mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return ((array) config("catalogo_externo.dummyjson.{$diccionario}", []))[$valor] ?? null;
    }

    /* ----------------------------------------------------------------------
     | Ayudas privadas de conversión
     | -------------------------------------------------------------------- */

    /** "32900000" con 2 decimales -> 329000.00 */
    private static function aMonto(mixed $valor, int $decimales): ?float
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        return round(((float) $valor) / (10 ** max(0, $decimales)), 2);
    }

    /** Quita etiquetas HTML, decodifica entidades y recorta espacios. */
    private static function textoPlano(mixed $html, ?int $limite = null): ?string
    {
        $texto = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');

        if ($texto === '') {
            return null;
        }

        return $limite ? Str::limit($texto, $limite) : $texto;
    }

    /**
     * Descripción larga + las características técnicas que la tienda publica
     * como atributos, que es justo lo que pide el enunciado («lista de
     * productos con detalles como descripción, precio, imágenes»).
     */
    private static function descripcionCompleta(array $datos): ?string
    {
        $partes = array_filter([
            self::textoPlano($datos['description'] ?? ''),
            self::caracteristicas($datos),
        ]);

        return $partes === [] ? null : implode("\n\n", $partes);
    }

    /** "Características: Marca: HP. Color: Negro." */
    private static function caracteristicas(array $datos): ?string
    {
        $atributos = is_array($datos['attributes'] ?? null) ? $datos['attributes'] : [];
        $lineas = [];

        foreach ($atributos as $atributo) {
            $nombre = self::textoPlano($atributo['name'] ?? '');
            $terminos = is_array($atributo['terms'] ?? null) ? $atributo['terms'] : [];

            $valores = array_filter(array_map(
                fn ($t) => self::textoPlano($t['name'] ?? ''),
                $terminos
            ));

            if ($nombre && $valores !== []) {
                $lineas[] = $nombre.': '.implode(', ', $valores);
            }
        }

        return $lineas === [] ? null : 'Características: '.implode('. ', $lineas).'.';
    }

    /** Busca un atributo llamado "marca"/"brand" antes de adivinar. */
    private static function marcaDesdeAtributos(array $datos): ?string
    {
        foreach ((array) ($datos['attributes'] ?? []) as $atributo) {
            $nombre = Str::lower(self::textoPlano($atributo['name'] ?? '') ?? '');

            if (! in_array($nombre, ['marca', 'brand'], true)) {
                continue;
            }

            $primerTermino = ((array) ($atributo['terms'] ?? []))[0]['name'] ?? null;

            if ($valor = self::textoPlano($primerTermino, 80)) {
                return $valor;
            }
        }

        return null;
    }

    /** Respaldo: la primera palabra del nombre suele ser la marca. */
    private static function primeraPalabra(string $nombre): ?string
    {
        $palabra = Str::of($nombre)->trim()->explode(' ')->first();

        return $palabra ? Str::limit((string) $palabra, 80, '') : null;
    }

    private static function primeraImagen(array $datos): ?string
    {
        $src = ((array) ($datos['images'] ?? []))[0]['src'] ?? null;

        if (! is_string($src) || ! Str::startsWith($src, ['http://', 'https://'])) {
            return null;
        }

        // La columna `imagen` es VARCHAR(255): una URL más larga no cabría.
        return strlen($src) <= 255 ? $src : null;
    }

    private static function existencias(array $datos, int $porDefecto): int
    {
        $hayExistencias = (bool) ($datos['is_in_stock'] ?? true);

        if (! $hayExistencias) {
            return 0;
        }

        $restantes = $datos['low_stock_remaining'] ?? null;

        return is_numeric($restantes) ? max(0, (int) $restantes) : $porDefecto;
    }

    /** @return list<string> */
    private static function nombresDeCategorias(array $datos): array
    {
        return array_values(array_filter(array_map(
            fn ($c) => self::textoPlano($c['name'] ?? ''),
            (array) ($datos['categories'] ?? [])
        )));
    }
}
