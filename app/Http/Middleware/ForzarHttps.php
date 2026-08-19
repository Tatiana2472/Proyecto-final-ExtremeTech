<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza HTTPS y agrega encabezados de seguridad.
 *
 * En entorno local se deja pasar HTTP para poder desarrollar sin certificado,
 * pero en producción (APP_ENV=production con el certificado SSL instalado)
 * toda petición HTTP se redirige a HTTPS y se envía la cabecera HSTS, que le
 * indica al navegador que no vuelva a usar HTTP con este dominio.
 */
class ForzarHttps
{
    public function handle(Request $request, Closure $siguiente): Response
    {
        $forzar = app()->environment('production') || config('tienda.forzar_https', false);

        if ($forzar) {
            // Los enlaces y formularios generados por Laravel usan https://
            URL::forceScheme('https');

            if (! $request->secure()) {
                return redirect()->secure($request->getRequestUri(), 301);
            }
        }

        $respuesta = $siguiente($request);

        // Encabezados de seguridad recomendados.
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($forzar) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}
