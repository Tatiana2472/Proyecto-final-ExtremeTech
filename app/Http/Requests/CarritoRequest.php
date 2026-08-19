<?php

namespace App\Http\Requests;

use App\Services\CarritoService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de las cantidades del carrito.
 *
 * La cantidad se valida como entero dentro de un rango: nunca se confía en el
 * valor que llega del formulario o de una petición AJAX.
 */
class CarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Al actualizar se permite 0 (equivale a eliminar la línea);
        // al agregar, el mínimo es 1.
        $minimo = $this->routeIs('carrito.actualizar') ? 0 : 1;

        return [
            'cantidad' => ['required', 'integer', "min:{$minimo}", 'max:'.CarritoService::CANTIDAD_MAXIMA],
        ];
    }

    public function messages(): array
    {
        return [
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.max'     => 'La cantidad máxima por producto es '.CarritoService::CANTIDAD_MAXIMA.' unidades.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si no viene la cantidad se asume 1 (botón "Agregar al carrito").
        if (! $this->has('cantidad')) {
            $this->merge(['cantidad' => 1]);
        }
    }
}
