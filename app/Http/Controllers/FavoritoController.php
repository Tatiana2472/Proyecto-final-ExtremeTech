<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lista de deseos del cliente.
 *
 * Trabaja sobre la relación muchos a muchos User::favoritos() y su tabla
 * pivote «favoritos». Igual que el carrito, responde en JSON cuando la petición
 * viene por AJAX y con una redirección cuando viene de un formulario normal.
 */
class FavoritoController extends Controller
{
    /** Pantalla «Mis favoritos». */
    public function index(): View
    {
        // with('categoria') evita el problema N+1: una consulta para los
        // productos y otra para sus categorías, en vez de una por producto.
        $favoritos = auth()->user()
            ->favoritos()
            ->with('categoria')
            ->paginate(12);

        return view('favoritos.index', ['favoritos' => $favoritos]);
    }

    /**
     * Marca o desmarca un producto como favorito.
     *
     * Se usa toggle() de la relación muchos a muchos: si la fila existe en la
     * pivote la borra, y si no existe la inserta. Es justo lo que necesita un
     * botón de corazón que alterna con un solo clic.
     */
    public function alternar(Request $peticion, Product $producto): RedirectResponse|JsonResponse
    {
        abort_unless($producto->activo, 404);

        $resultado = $peticion->user()->favoritos()->toggle($producto);

        // toggle() devuelve qué hizo: ['attached' => [...], 'detached' => [...]].
        $marcado = ! empty($resultado['attached']);

        $mensaje = $marcado
            ? "«{$producto->nombre}» se agregó a sus favoritos."
            : "«{$producto->nombre}» se quitó de sus favoritos.";

        if ($peticion->expectsJson()) {
            return response()->json([
                'exito'    => true,
                'marcado'  => $marcado,
                'mensaje'  => $mensaje,
                'cantidad' => $peticion->user()->favoritos()->count(),
            ]);
        }

        return back()->with('exito', $mensaje);
    }

    /** Quita un producto de la lista desde la propia pantalla de favoritos. */
    public function eliminar(Request $peticion, Product $producto): RedirectResponse
    {
        // detach() borra la fila de la pivote sin tocar el producto.
        $peticion->user()->favoritos()->detach($producto->id);

        return back()->with('exito', "«{$producto->nombre}» se quitó de sus favoritos.");
    }
}
