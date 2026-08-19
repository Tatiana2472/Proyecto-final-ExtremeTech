<?php

namespace App\Support;

/**
 * Formato de montos según la moneda configurada para la tienda.
 *
 * Se centraliza acá para que el símbolo, los separadores y la cantidad de
 * decimales sean idénticos en la web, en los PDF y en los correos.
 */
class Moneda
{
    /** Ejemplo: ₡329 000 */
    public static function formato(float|int|string|null $valor): string
    {
        $simbolo   = (string) config('tienda.moneda.simbolo', '₡');
        $decimales = (int) config('tienda.moneda.decimales', 0);

        return $simbolo.number_format((float) $valor, $decimales, ',', ' ');
    }

    /** Igual que formato(), pero sin el símbolo (para tablas de PDF). */
    public static function numero(float|int|string|null $valor): string
    {
        $decimales = (int) config('tienda.moneda.decimales', 0);

        return number_format((float) $valor, $decimales, ',', ' ');
    }

    public static function codigo(): string
    {
        return (string) config('tienda.moneda.codigo', 'CRC');
    }
}
