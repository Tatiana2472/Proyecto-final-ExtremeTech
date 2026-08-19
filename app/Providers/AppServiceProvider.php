<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CarritoService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Los meses y las fechas se muestran en español en toda la aplicación.
        Carbon::setLocale('es');

        // La paginación usa las clases de Bootstrap 5.
        Paginator::useBootstrapFive();

        /*
         | Directiva @precio($monto) para no repetir el formato de moneda en
         | cada vista. Blade escapa la salida con e(), así que el valor queda
         | protegido contra XSS.
         */
        Blade::directive('precio', function (string $expresion) {
            return "<?php echo e(\App\Support\Moneda::formato({$expresion})); ?>";
        });

        /*
         | Datos que necesita el encabezado en todas las páginas: las
         | categorías del menú y la cantidad de artículos del carrito.
         | Se usa un View Composer para no repetirlo en cada controlador.
         */
        View::composer('layouts.tienda', function ($vista) {
            $vista->with([
                'categoriasMenu'  => Category::activas()->orderBy('nombre')->get(),
                'articulosCarrito' => app(CarritoService::class)->cantidadArticulos(),
            ]);
        });
    }
}
