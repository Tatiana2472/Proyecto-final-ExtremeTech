<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario de categorías del panel de administración.
 */
class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->esAdministrador();
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria')?->id;

        return [
            'nombre'      => ['required', 'string', 'min:3', 'max:120'],
            'slug'        => [
                'nullable', 'string', 'max:140', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('categories', 'slug')->ignore($categoriaId),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'icono'       => ['nullable', 'string', 'max:60', 'regex:/^bi-[a-z0-9\-]+$/'],
            'activa'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'   => 'La URL amigable solo puede tener minúsculas, números y guiones.',
            'slug.unique'  => 'Ya existe otra categoría con esa URL amigable.',
            'icono.regex'  => 'El ícono debe ser un nombre de Bootstrap Icons, por ejemplo bi-laptop.',
        ];
    }

    public function attributes(): array
    {
        return [
            'slug'  => 'URL amigable',
            'icono' => 'ícono',
        ];
    }
}
