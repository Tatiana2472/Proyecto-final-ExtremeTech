<?php

namespace App\Services\Catalogo;

use Generator;

/**
 * Contrato que debe cumplir cualquier origen de productos.
 *
 * Gracias a esta interfaz el comando de importación no sabe —ni le
 * importa— si los datos vienen de una tienda WooCommerce, de una API de
 * pruebas o de un archivo. Para agregar un origen nuevo se escribe una
 * clase más y no se toca nada de lo existente.
 */
interface FuenteDeProductos
{
    /** Texto corto que identifica el origen en pantalla. */
    public function descripcion(): string;

    /** Cantidad de productos disponibles. Lanza excepción si no responde. */
    public function verificarDisponibilidad(): int;

    /** true si el sitio desaconseja esta ruta en su robots.txt. */
    public function rutaBloqueadaPorRobots(): bool;

    /**
     * Devuelve los productos ya normalizados, de a uno.
     *
     * @return Generator<int, ProductoExterno>
     */
    public function productos(int $paginas = 1, ?string $categoria = null): Generator;
}
