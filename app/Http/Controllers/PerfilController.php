<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContrasenaRequest;
use App\Http\Requests\PerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perfil del usuario: datos personales, contraseña e historial de pedidos.
 */
class PerfilController extends Controller
{
    /** Datos personales del usuario y resumen de su actividad. */
    public function mostrar(Request $peticion): View
    {
        $usuario = $peticion->user();

        return view('perfil.mostrar', [
            'usuario'    => $usuario,
            'provincias' => $this->provincias(),
            'resumen'    => [
                'pedidos'  => $usuario->pedidos()->count(),
                'comprado' => $usuario->totalComprado(),
                'ultima'   => $usuario->pedidos()->first()?->fecha_compra,
            ],
        ]);
    }

    /** Guarda los datos personales modificados. */
    public function actualizar(PerfilRequest $peticion): RedirectResponse
    {
        $peticion->user()->update($peticion->validated());

        return redirect()
            ->route('perfil.mostrar')
            ->with('exito', 'Sus datos personales se actualizaron correctamente.');
    }

    /** Cambia la contraseña del usuario. */
    public function cambiarContrasena(ContrasenaRequest $peticion): RedirectResponse
    {
        $peticion->user()->update([
            'password' => $peticion->validated('password'),
        ]);

        return redirect()
            ->route('perfil.mostrar')
            ->with('exito', 'Su contraseña se actualizó correctamente.');
    }

    /** @return list<string> */
    private function provincias(): array
    {
        return ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón'];
    }
}
