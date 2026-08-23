<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
|
| Requieren que el servidor ejecute "php artisan schedule:run" cada minuto
| (con cron en Linux o el Programador de tareas en Windows). Si no se
| configura, el comando igual se puede correr a mano.
|
*/

// Los carritos de visitantes que nunca compraron se acumulan en la tabla
// «carts». Se limpian una vez al día.
Schedule::command('carritos:limpiar')->dailyAt('03:00');
