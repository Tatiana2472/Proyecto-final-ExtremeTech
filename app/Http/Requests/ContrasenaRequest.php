<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Cambio de contraseña.
 *
 * Se exige la contraseña actual (regla "current_password") para que nadie
 * pueda cambiarla aprovechando una sesión abierta ajena.
 */
class ContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'current_password'],
            'password'        => ['required', 'confirmed', 'different:password_actual', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return [
            'password_actual' => 'contraseña actual',
            'password'        => 'nueva contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different'               => 'La nueva contraseña debe ser distinta de la actual.',
        ];
    }
}
