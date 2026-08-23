<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarritoRequest;
use App\Models\Product;
use App\Services\CarritoService;
use App\Services\VistosRecientementeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Carrito de compras: agregar, actualizar, eliminar y vaciar.
 *
 * Los métodos responden en JSON cuando la petición viene por AJAX (fetch) y
 * con una redirección cuando viene de un formulario normal. Es el ejemplo de
 * comunicación asíncrona visto en la sesión 11: el usuario agrega productos
 * sin recargar la página.
 */
class CarritoController extends Controller
{
    public function __construct(
        protected CarritoService $carrito,
        protected VistosRecientementeService $vistos,
    ) {
    }

    /** Pantalla del carrito con el cálculo automático del total. */
    public function mostrar(): View
    {
        $this->avisarSiCambiaronLosPrecios();

        return view('carrito.mostrar', [
            'lineas'  => $this->carrito->lineas(),
            'totales' => $this->carrito->totales(),
            'vistos'  => $this->vistos->productos(),
        ]);
    }

    /** Agrega un producto al carrito. */
    public function agregar(CarritoRequest $peticion, Product $producto): RedirectResponse|JsonResponse
    {
        abort_unless($producto->activo, 404);

        try {
            $this->carrito->agregar($producto, (int) $peticion->validated('cantidad'));
        } catch (RuntimeException $e) {
            return $this->responder($peticion, false, $e->getMessage());
        }

        return $this->responder(
            $peticion,
            true,
            "«{$producto->nombre}» se agregó a su carrito."
        );
    }

    /** Cambia la cantidad de una línea del carrito. */
    public function actualizar(CarritoRequest $peticion, int $linea): RedirectResponse|JsonResponse
    {
        try {
            $resultado = $this->carrito->actualizar($linea, (int) $peticion->validated('cantidad'));
        } catch (RuntimeException $e) {
            return $this->responder($peticion, false, $e->getMessage());
        }

        return $this->responder(
            $peticion,
            true,
            $resultado === null ? 'El producto se quitó del carrito.' : 'Carrito actualizado.'
        );
    }

    /** Elimina una línea del carrito. */
    public function eliminar(Request $peticion, int $linea): RedirectResponse|JsonResponse
    {
        try {
            $this->carrito->eliminar($linea);
        } catch (RuntimeException $e) {
            return $this->responder($peticion, false, $e->getMessage());
        }

        return $this->responder($peticion, true, 'El producto se quitó del carrito.');
    }

    /** Vacía el carrito completo. */
    public function vaciar(Request $peticion): RedirectResponse|JsonResponse
    {
        $this->carrito->vaciar();

        return $this->responder($peticion, true, 'Su carrito quedó vacío.');
    }

    /** Cantidad de artículos, para actualizar el indicador del encabezado. */
    public function contador(): JsonResponse
    {
        return response()->json([
            'cantidad' => $this->carrito->cantidadArticulos(),
        ]);
    }

    /**
     * Pone al día los precios del carrito y, si alguno cambió, lo advierte.
     *
     * Se usa session()->now() y no flash() porque el aviso corresponde a esta
     * misma pantalla: con flash() volvería a aparecer en la página siguiente.
     */
    protected function avisarSiCambiaronLosPrecios(): void
    {
        $cambiadas = $this->carrito->sincronizarPrecios();

        if ($cambiadas->isEmpty()) {
            return;
        }

        session()->now('aviso', $cambiadas->count() === 1
            ? 'El precio de «'.$cambiadas->first()->producto->nombre.'» cambió desde que lo agregó. Su carrito ya muestra el precio actual.'
            : 'El precio de '.$cambiadas->count().' de sus productos cambió desde que los agregó. Su carrito ya muestra los precios actuales.'
        );
    }

    /**
     * Responde en JSON si la petición es asíncrona; si no, redirige con el
     * mensaje correspondiente.
     */
    protected function responder(Request $peticion, bool $exito, string $mensaje): RedirectResponse|JsonResponse
    {
        $totales = $this->carrito->totales();

        if ($peticion->expectsJson()) {
            return response()->json([
                'exito'   => $exito,
                'mensaje' => $mensaje,
                'totales' => $totales->aArreglo(),
            ], $exito ? 200 : 422);
        }

        return back()->with($exito ? 'exito' : 'error', $mensaje);
    }
}
