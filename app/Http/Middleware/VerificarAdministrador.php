<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el paso solo a usuarios con la bandera es_admin.
 *
 * Se registra con el alias "admin" en bootstrap/app.php y se aplica a todo el
 * grupo de rutas /admin, incluidos los reportes de ventas.
 */
class VerificarAdministrador
{
    public function handle(Request $request, Closure $siguiente): Response
    {
        // Si no hay usuario autenticado o su bandera es_admin es falsa,
        // se corta la petición con un 403 antes de llegar al controlador.
        if (! $request->user()?->esAdministrador()) {
            abort(403, 'Esta sección es solo para administradores de la tienda.');
        }

        return $siguiente($request);
    }
}
