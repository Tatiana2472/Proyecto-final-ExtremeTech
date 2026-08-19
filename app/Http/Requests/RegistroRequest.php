<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validación del registro de usuarios nuevos.
 *
 * Las reglas se declaran en un FormRequest y no dentro del controlador: así la
 * validación es obligatoria (Laravel la ejecuta antes de entrar al método) y
 * los mensajes quedan centralizados.
 */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:120',
                // Solo letras (incluyendo tildes y ñ), espacios y apóstrofos.
                'regex:/^[\pL\s\'.-]+$/u',
            ],
            'email'    => ['required', 'string', 'email:rfc', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'terminos' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nombre completo',
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
            'telefono' => 'teléfono',
            'terminos' => 'términos y condiciones',
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex'         => 'El nombre solo puede contener letras y espacios.',
            'telefono.regex'     => 'El teléfono solo puede contener números, espacios y los signos + - ( ).',
            'terminos.accepted'  => 'Debe aceptar los términos y condiciones para crear la cuenta.',
            'email.unique'       => 'Ya existe una cuenta registrada con ese correo electrónico.',
        ];
    }

    /**
     * Limpia los datos antes de validarlos.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
