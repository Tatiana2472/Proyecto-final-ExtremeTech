<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la edición de datos personales del perfil.
 */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\'.-]+$/u'],
            'email' => [
                'required', 'string', 'email:rfc', 'max:160',
                // Se ignora el propio registro para que el usuario pueda
                // guardar el formulario sin cambiar el correo.
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'telefono'  => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'cedula'    => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-\s]+$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad'    => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'  => 'nombre completo',
            'email' => 'correo electrónico',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex'     => 'El nombre solo puede contener letras y espacios.',
            'telefono.regex' => 'El teléfono solo puede contener números, espacios y los signos + - ( ).',
            'cedula.regex'   => 'La cédula solo puede contener números y guiones.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
