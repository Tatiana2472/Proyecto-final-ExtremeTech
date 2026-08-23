<?php

namespace App\Models\Concerns;

/**
 * Numeración consecutiva anual del tipo PREFIJO-2026-000014.
 *
 * La comparten los pedidos (PED-) y las facturas (FAC-), que antes calculaban
 * su número con max('id') + 1. Ese cálculo tenía dos problemas:
 *
 *   1. Era global, así que la numeración nunca reiniciaba en enero pese a que
 *      el formato lleva el año.
 *   2. Dos compras simultáneas leían el mismo max('id') y armaban el mismo
 *      número. Como la columna tiene índice único, la segunda reventaba con un
 *      error 500 en pleno checkout.
 *
 * Acá el consecutivo se toma de los números ya emitidos en el año en curso y
 * se avanza hasta encontrar uno libre, igual que hace Order con el número de
 * seguimiento.
 */
trait ConsecutivoAnual
{
    /**
     * Siguiente número libre para la columna y el prefijo indicados.
     *
     * @param  string  $columna   Columna que guarda el número (numero_pedido…).
     * @param  string  $prefijo   Prefijo del documento (PED, FAC…).
     */
    protected static function siguienteConsecutivoAnual(string $columna, string $prefijo): string
    {
        $inicio = sprintf('%s-%s-', $prefijo, now()->year);

        // Último número emitido este año. El consecutivo va rellenado con ceros
        // a seis dígitos, así que el orden alfabético coincide con el numérico.
        $ultimo = static::query()
            ->where($columna, 'like', $inicio.'%')
            ->orderByDesc($columna)
            ->value($columna);

        $consecutivo = $ultimo !== null
            ? (int) substr((string) $ultimo, strlen($inicio))
            : 0;

        do {
            $consecutivo++;
            $numero = $inicio.sprintf('%06d', $consecutivo);
        } while (static::query()->where($columna, $numero)->exists());

        return $numero;
    }
}
