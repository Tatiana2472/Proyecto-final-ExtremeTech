<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Recuperación de contraseña en dos pasos:
 *
 *   1. El usuario pide el enlace y se le envía por correo un token temporal
 *      (se guarda cifrado en la tabla password_reset_tokens).
 *   2. Con ese token abre el formulario y define su nueva contraseña.
 *
 * Se apoya en el «password broker» de Laravel, que se encarga de generar,
 * guardar, validar y caducar los tokens.
 */
class ContrasenaOlvidadaController extends Controller
{
    /* ==================================================================
     | Paso 1: solicitar el enlace
     | ================================================================ */

    public function mostrarSolicitud(): View
    {
        return view('auth.olvide-contrasena');
    }

    public function enviarEnlace(Request $peticion): RedirectResponse
    {
        $peticion->merge([
            'email' => strtolower(trim((string) $peticion->input('email'))),
        ]);

        $peticion->validate(
            ['email' => ['required', 'email', 'max:160']],
            [],
            ['email' => 'correo electrónico']
        );

        $estado = Password::sendResetLink($peticion->only('email'));

        // Se responde igual exista o no la cuenta: así nadie puede usar este
        // formulario para averiguar qué correos están registrados.
        if ($estado === Password::RESET_THROTTLED) {
            return back()->with('error', 'Ya le enviamos un enlace hace poco. Espere unos minutos antes de volver a intentarlo.');
        }

        return back()->with(
            'exito',
            'Si el correo está registrado, le enviamos un enlace para restablecer su contraseña. Revise su bandeja de entrada.'
        );
    }

    /* ==================================================================
     | Paso 2: definir la nueva contraseña
     | ================================================================ */

    public function mostrarRestablecer(Request $peticion, string $token): View
    {
        return view('auth.restablecer-contrasena', [
            'token' => $token,
            'email' => $peticion->query('email'),
        ]);
    }

    public function restablecer(Request $peticion): RedirectResponse
    {
        $peticion->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaContrasena::min(8)->letters()->numbers()],
        ], [], [
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
        ]);

        $estado = Password::reset(
            $peticion->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario, string $contrasena) {
                $usuario->forceFill([
                    // El cast 'hashed' del modelo aplica bcrypt automáticamente.
                    'password' => $contrasena,
                    // Se cambia el token de «recordarme» para cerrar la sesión
                    // en cualquier otro dispositivo donde la cuenta siguiera
                    // abierta: si alguien robó la contraseña, pierde el acceso.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'El enlace no es válido o ya venció. Solicite uno nuevo.',
            ]);
        }

        return redirect()
            ->route('login')
            ->with('exito', 'Su contraseña se actualizó. Ya puede iniciar sesión.');
    }
}
