<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario de productos del panel de administración.
 */
class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->esAdministrador();
    }

    public function rules(): array
    {
        $productoId = $this->route('producto')?->id;

        return [
            'category_id'     => ['required', 'exists:categories,id'],
            'nombre'          => ['required', 'string', 'min:3', 'max:160'],
            'sku'             => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($productoId)],
            'marca'           => ['nullable', 'string', 'max:80'],
            'resumen'         => ['nullable', 'string', 'max:255'],
            'descripcion'     => ['nullable', 'string', 'max:5000'],
            'precio'          => ['required', 'numeric', 'min:1', 'max:99999999'],
            'precio_anterior' => ['nullable', 'numeric', 'gt:precio', 'max:99999999'],
            'existencias'     => ['required', 'integer', 'min:0', 'max:100000'],
            'destacado'       => ['nullable', 'boolean'],
            'activo'          => ['nullable', 'boolean'],
            'imagen'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id'     => 'categoría',
            'precio_anterior' => 'precio anterior',
        ];
    }

    public function messages(): array
    {
        return [
            'precio_anterior.gt' => 'El precio anterior debe ser mayor que el precio actual (es el precio sin descuento).',
            'imagen.max'         => 'La imagen no debe superar los 2 MB.',
        ];
    }
}
