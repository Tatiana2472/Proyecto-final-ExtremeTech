<?php

namespace App\Services\Catalogo;

use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cliente de la Store API pública de WooCommerce.
 *
 * Solo hace peticiones GET a un recurso publicado por el propio sitio, sin
 * autenticación y sin saltarse ninguna restricción. Además:
 *
 *  - se identifica con un User-Agent propio en lugar de imitar un navegador,
 *  - espera unos segundos entre página y página para no cargar el servidor,
 *  - respeta lo que el sitio declare en su archivo robots.txt.
 */
class ClienteTiendaExterna implements FuenteDeProductos
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $userAgent,
        private readonly int $porPagina = 50,
        private readonly float $pausaSegundos = 2.0,
        private readonly int $timeout = 20,
        private readonly int $reintentos = 2,
    ) {}

    /** Construye el cliente leyendo config/catalogo_externo.php. */
    public static function desdeConfiguracion(?string $baseUrl = null): self
    {
        $url = rtrim($baseUrl ?: (string) config('catalogo_externo.base_url'), '/');

        if ($url === '') {
            throw new RuntimeException(
                'No hay una tienda configurada. Defina CATALOGO_EXTERNO_URL en el archivo .env '
                .'(por ejemplo: CATALOGO_EXTERNO_URL=https://ejemplo.com).'
            );
        }

        return new self(
            baseUrl: $url,
            userAgent: (string) config('catalogo_externo.user_agent'),
            porPagina: (int) config('catalogo_externo.por_pagina', 50),
            pausaSegundos: (float) config('catalogo_externo.pausa_segundos', 2.0),
            timeout: (int) config('catalogo_externo.timeout_segundos', 20),
            reintentos: (int) config('catalogo_externo.reintentos', 2),
        );
    }

    public function descripcion(): string
    {
        return $this->urlProductos();
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function urlProductos(): string
    {
        return $this->baseUrl.config('catalogo_externo.ruta_productos');
    }

    /**
     * Comprueba que el sitio exponga la Store API antes de importar nada.
     * Devuelve la cantidad total de productos publicados.
     */
    public function verificarDisponibilidad(): int
    {
        $respuesta = $this->peticion(['page' => 1, 'per_page' => 1]);

        return (int) ($respuesta->header('X-WP-Total') ?: count($respuesta->json() ?? []));
    }

    /**
     * Lee robots.txt y devuelve true si la ruta de la API está bloqueada
     * para los robots genéricos (User-agent: *).
     *
     * Es una lectura deliberadamente conservadora: ante cualquier duda o
     * error de red devuelve false y quien ejecuta el comando decide.
     */
    public function rutaBloqueadaPorRobots(): bool
    {
        try {
            $respuesta = Http::withHeaders(['User-Agent' => $this->userAgent])
                ->timeout($this->timeout)
                ->get($this->baseUrl.'/robots.txt');
        } catch (\Throwable) {
            return false;
        }

        if (! $respuesta->successful()) {
            return false;
        }

        $ruta = (string) config('catalogo_externo.ruta_productos');
        $aplicaATodos = false;

        foreach (preg_split('/\R/', $respuesta->body()) ?: [] as $linea) {
            $linea = trim(Str::before($linea, '#'));

            if ($linea === '') {
                continue;
            }

            if (Str::startsWith(Str::lower($linea), 'user-agent:')) {
                $aplicaATodos = trim(Str::after($linea, ':')) === '*';

                continue;
            }

            if ($aplicaATodos && Str::startsWith(Str::lower($linea), 'disallow:')) {
                $prohibido = trim(Str::after($linea, ':'));

                if ($prohibido !== '' && Str::startsWith($ruta, $prohibido)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recorre el catálogo página por página.
     *
     * Se usa un generador para no cargar miles de productos en memoria de
     * una sola vez: cada producto se procesa y se descarta.
     *
     * @return Generator<int, ProductoExterno>
     */
    public function productos(int $paginas = 1, ?string $categoria = null): Generator
    {
        $existencias = (int) config('catalogo_externo.existencias_por_defecto', 10);

        for ($pagina = 1; $pagina <= $paginas; $pagina++) {
            $parametros = ['page' => $pagina, 'per_page' => $this->porPagina];

            if ($categoria !== null) {
                $parametros['category'] = $categoria;
            }

            $lote = $this->peticion($parametros)->json();

            if (! is_array($lote) || $lote === []) {
                break;  // Ya no hay más páginas.
            }

            foreach ($lote as $crudo) {
                if (! is_array($crudo)) {
                    continue;
                }

                // Los productos sin nombre o sin precio devuelven null y el
                // comando los cuenta como descartados.
                yield ProductoExterno::desdeWooCommerce($crudo, $existencias);
            }

            // Pausa cortés antes de pedir la siguiente página.
            if ($pagina < $paginas && $this->pausaSegundos > 0) {
                usleep((int) ($this->pausaSegundos * 1_000_000));
            }
        }
    }

    /** @param array<string, mixed> $parametros */
    private function peticion(array $parametros): \Illuminate\Http\Client\Response
    {
        $respuesta = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($this->reintentos, 1500, throw: false)
            ->get($this->urlProductos(), $parametros);

        if (! $respuesta->successful()) {
            throw new RuntimeException(
                "La tienda respondió con el código HTTP {$respuesta->status()} en {$this->urlProductos()}. "
                .'Verifique que el sitio use WooCommerce y tenga la Store API habilitada.'
            );
        }

        return $respuesta;
    }
}
