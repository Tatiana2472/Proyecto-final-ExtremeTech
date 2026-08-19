<?php

namespace App\Services\Catalogo;

use Generator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Origen alternativo: DummyJSON (https://dummyjson.com).
 *
 * Es una API pública, gratuita y sin llaves, creada expresamente para que
 * los desarrolladores prueben aplicaciones. No hay aquí ninguna zona gris:
 * el propio servicio existe para ser consumido.
 *
 * Solo se traen las categorías de tecnología, porque el catálogo completo
 * incluye ropa, muebles y abarrotes que no corresponden a esta tienda.
 */
class ClienteDummyJson implements FuenteDeProductos
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $userAgent,
        private readonly array $categorias,
        private readonly float $tipoCambio,
        private readonly int $porPagina = 30,
        private readonly int $timeout = 20,
    ) {}

    public static function desdeConfiguracion(): self
    {
        return new self(
            baseUrl: rtrim((string) config('catalogo_externo.dummyjson.url'), '/'),
            userAgent: (string) config('catalogo_externo.user_agent'),
            categorias: (array) config('catalogo_externo.dummyjson.categorias', []),
            tipoCambio: (float) config('catalogo_externo.dummyjson.tipo_cambio', 510),
            porPagina: (int) config('catalogo_externo.por_pagina', 30),
            timeout: (int) config('catalogo_externo.timeout_segundos', 20),
        );
    }

    public function descripcion(): string
    {
        return $this->baseUrl.'/products (categorías: '.implode(', ', $this->categorias).')';
    }

    /** Esta API está hecha para consumirse; no hay restricción que revisar. */
    public function rutaBloqueadaPorRobots(): bool
    {
        return false;
    }

    public function verificarDisponibilidad(): int
    {
        $total = 0;

        foreach ($this->categorias as $categoria) {
            $total += (int) ($this->peticion($categoria, 1, 0)['total'] ?? 0);
        }

        return $total;
    }

    /**
     * Recorre cada categoría de tecnología configurada.
     *
     * @return Generator<int, ProductoExterno>
     */
    public function productos(int $paginas = 1, ?string $categoria = null): Generator
    {
        // Si se pide una categoría concreta se respeta; si no, se usan todas
        // las de tecnología definidas en la configuración.
        $categorias = $categoria ? [$categoria] : $this->categorias;

        foreach ($categorias as $slug) {
            for ($pagina = 0; $pagina < $paginas; $pagina++) {
                $respuesta = $this->peticion($slug, $this->porPagina, $pagina * $this->porPagina);
                $lote = $respuesta['products'] ?? [];

                if (! is_array($lote) || $lote === []) {
                    break;  // Categoría agotada.
                }

                foreach ($lote as $crudo) {
                    if (is_array($crudo)) {
                        yield ProductoExterno::desdeDummyJson($crudo, $this->tipoCambio);
                    }
                }

                // Si vino menos de una página completa, ya no hay más.
                if (count($lote) < $this->porPagina) {
                    break;
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function peticion(string $categoria, int $limite, int $saltar): array
    {
        $url = $this->baseUrl.'/products/category/'.urlencode($categoria);

        $respuesta = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Accept' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry(2, 1000, throw: false)
            ->get($url, ['limit' => $limite, 'skip' => $saltar]);

        if (! $respuesta->successful()) {
            throw new RuntimeException(
                "DummyJSON respondió con el código HTTP {$respuesta->status()} en {$url}."
            );
        }

        return (array) $respuesta->json();
    }
}
