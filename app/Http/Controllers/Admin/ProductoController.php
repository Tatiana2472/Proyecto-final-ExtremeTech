<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mantenimiento del catálogo desde el panel de administración.
 */
class ProductoController extends Controller
{
    public function index(Request $peticion): View
    {
        $filtros = $peticion->validate([
            'q'         => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'exists:categories,id'],
        ]);

        return view('admin.productos.index', [
            'productos' => Product::with('categoria')
                ->buscar($filtros['q'] ?? null)
                ->deCategoria(isset($filtros['categoria']) ? (int) $filtros['categoria'] : null)
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
            'categorias' => Category::orderBy('nombre')->get(),
            'filtros'    => $filtros,
        ]);
    }

    public function crear(): View
    {
        return view('admin.productos.formulario', [
            'producto'   => new Product(['activo' => true, 'existencias' => 0]),
            'categorias' => Category::orderBy('nombre')->get(),
        ]);
    }

    public function guardar(ProductoRequest $peticion): RedirectResponse
    {
        $datos = $this->prepararDatos($peticion);

        $producto = Product::create($datos);

        return redirect()
            ->route('admin.productos.index')
            ->with('exito', "El producto «{$producto->nombre}» se creó correctamente.");
    }

    public function editar(Product $producto): View
    {
        return view('admin.productos.formulario', [
            'producto'   => $producto,
            'categorias' => Category::orderBy('nombre')->get(),
        ]);
    }

    public function actualizar(ProductoRequest $peticion, Product $producto): RedirectResponse
    {
        $producto->update($this->prepararDatos($peticion, $producto));

        return redirect()
            ->route('admin.productos.index')
            ->with('exito', "El producto «{$producto->nombre}» se actualizó correctamente.");
    }

    public function eliminar(Product $producto): RedirectResponse
    {
        $nombre = $producto->nombre;

        // No se borra físicamente si ya se vendió: se desactiva, para no
        // perder el historial de las facturas emitidas.
        if ($producto->lineasPedido()->exists()) {
            $producto->update(['activo' => false]);

            return redirect()
                ->route('admin.productos.index')
                ->with('exito', "«{$nombre}» tiene ventas registradas, por lo que se desactivó en lugar de eliminarse.");
        }

        $producto->delete();

        return redirect()
            ->route('admin.productos.index')
            ->with('exito', "El producto «{$nombre}» se eliminó.");
    }

    /**
     * Normaliza los datos del formulario y guarda la imagen si se subió una.
     */
    private function prepararDatos(ProductoRequest $peticion, ?Product $producto = null): array
    {
        $datos = $peticion->safe()->except('imagen');

        $datos['destacado'] = $peticion->boolean('destacado');
        $datos['activo']    = $peticion->boolean('activo');
        $datos['slug']      = Str::slug($datos['nombre']).'-'.Str::lower($datos['sku']);

        if ($peticion->hasFile('imagen')) {
            // Se guarda en storage/app/public/productos y se sirve por el
            // enlace simbólico creado con "php artisan storage:link".
            $ruta = $peticion->file('imagen')->store('productos', 'public');
            $datos['imagen'] = 'storage/'.$ruta;

            // Se borra la imagen anterior si estaba en storage.
            if ($producto && Str::startsWith((string) $producto->imagen, 'storage/')) {
                Storage::disk('public')->delete(Str::after($producto->imagen, 'storage/'));
            }
        }

        return $datos;
    }
}
