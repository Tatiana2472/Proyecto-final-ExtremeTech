<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\InicioSesionRequest;
use App\Services\CarritoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesión.
 */
class SesionController extends Controller
{
    public function __construct(protected CarritoService $carrito)
    {
    }

    public function mostrar(): View
    {
        return view('auth.inicio-sesion');
    }

    public function iniciar(InicioSesionRequest $peticion): RedirectResponse
    {
        // La validación de credenciales y el límite de intentos viven en el
        // FormRequest; si fallan, se lanza una ValidationException.
        $peticion->autenticar();

        // Sesión segura: se genera un identificador de sesión nuevo después
        // de autenticar (protección contra session fixation).
        $peticion->session()->regenerate();

        $this->carrito->adoptarCarrito(Auth::id());

        $usuario = Auth::user();

        // El administrador va directo a su panel.
        $destino = $usuario->esAdministrador()
            ? route('admin.panel')
            : route('inicio');

        return redirect()->intended($destino)
            ->with('exito', "Sesión iniciada. ¡Hola de nuevo, {$usuario->name}!");
    }

    public function cerrar(Request $peticion): RedirectResponse
    {
        Auth::logout();

        // Se invalida la sesión y se regenera el token CSRF para que la
        // sesión anterior no pueda reutilizarse.
        $peticion->session()->invalidate();
        $peticion->session()->regenerateToken();

        return redirect()->route('inicio')->with('exito', 'Su sesión se cerró correctamente.');
    }
}
