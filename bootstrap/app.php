<?php

use App\Http\Middleware\ForzarHttps;
use App\Http\Middleware\VerificarAdministrador;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Fuerza HTTPS en producción y agrega encabezados de seguridad.
        $middleware->append(ForzarHttps::class);

        // Alias usado por el grupo de rutas /admin.
        $middleware->alias([
            'admin' => VerificarAdministrador::class,
        ]);

        // A dónde se redirige a quien no ha iniciado sesión.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Datos que NUNCA se guardan en la sesión al reenviar un formulario
        // con errores. Incluye los campos de la tarjeta de crédito.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'password_actual',
            'numero_tarjeta',
            'cvv',
        ]);
    })->create();
