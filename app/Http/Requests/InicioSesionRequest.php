<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validación del inicio de sesión, con límite de intentos.
 *
 * El limitador de intentos (RateLimiter) protege contra ataques de fuerza
 * bruta: después de 5 intentos fallidos desde el mismo correo e IP la cuenta
 * queda bloqueada temporalmente.
 */
class InicioSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'      => ['required', 'string', 'email', 'max:160'],
            'password'   => ['required', 'string'],
            'recordarme' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * Intenta autenticar al usuario.
     *
     * @throws ValidationException si las credenciales no son válidas.
     */
    public function autenticar(): void
    {
        $this->asegurarQueNoEstaBloqueado();

        $credenciales = $this->only('email', 'password');

        if (! Auth::attempt($credenciales, $this->boolean('recordarme'))) {
            RateLimiter::hit($this->claveLimite());

            // Mensaje genérico a propósito: no se revela si el correo existe.
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        RateLimiter::clear($this->claveLimite());
    }

    protected function asegurarQueNoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveLimite(), 5)) {
            return;
        }

        event(new Lockout($this));

        $segundos = RateLimiter::availableIn($this->claveLimite());

        throw ValidationException::withMessages([
            'email' => sprintf(
                'Demasiados intentos fallidos. Vuelva a intentarlo en %d segundos.',
                $segundos
            ),
        ]);
    }

    protected function claveLimite(): string
    {
        return 'inicio-sesion|'.Str::lower((string) $this->input('email')).'|'.$this->ip();
    }
}
