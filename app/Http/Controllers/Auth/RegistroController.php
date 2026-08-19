<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use App\Services\CarritoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registro de usuarios nuevos.
 */
class RegistroController extends Controller
{
    public function __construct(protected CarritoService $carrito)
    {
    }

    public function mostrar(): View
    {
        return view('auth.registro');
    }

    public function registrar(RegistroRequest $peticion): RedirectResponse
    {
        // La contraseña se cifra con bcrypt automáticamente por el cast
        // 'hashed' declarado en el modelo User.
        $usuario = User::create([
            'name'     => $peticion->validated('name'),
            'email'    => $peticion->validated('email'),
            'password' => $peticion->validated('password'),
            'telefono' => $peticion->validated('telefono'),
        ]);

        Auth::login($usuario);

        // Se regenera el id de sesión para evitar la fijación de sesión.
        $peticion->session()->regenerate();

        // Si el visitante ya tenía productos en el carrito, se conservan.
        $this->carrito->adoptarCarrito($usuario->id);

        return redirect()
            ->route('inicio')
            ->with('exito', "¡Bienvenido/a, {$usuario->name}! Su cuenta fue creada correctamente.");
    }
}
