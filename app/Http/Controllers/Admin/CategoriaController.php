<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mantenimiento de las categorías del catálogo.
 */
class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias.index', [
            'categorias' => Category::withCount('productos')
                ->orderBy('nombre')
                ->paginate(15),
        ]);
    }

    public function crear(): View
    {
        return view('admin.categorias.formulario', [
            'categoria' => new Category(['activa' => true, 'icono' => 'bi-tag']),
        ]);
    }

    public function guardar(CategoriaRequest $peticion): RedirectResponse
    {
        $categoria = Category::create($this->prepararDatos($peticion));

        return redirect()
            ->route('admin.categorias.index')
            ->with('exito', "La categoría «{$categoria->nombre}» se creó correctamente.");
    }

    public function editar(Category $categoria): View
    {
        return view('admin.categorias.formulario', ['categoria' => $categoria]);
    }

    public function actualizar(CategoriaRequest $peticion, Category $categoria): RedirectResponse
    {
        $categoria->update($this->prepararDatos($peticion));

        return redirect()
            ->route('admin.categorias.index')
            ->with('exito', "La categoría «{$categoria->nombre}» se actualizó correctamente.");
    }

    /**
     * Elimina la categoría.
     *
     * No se permite borrar una categoría que todavía tiene productos, porque
     * la llave foránea está definida con cascadeOnDelete: al borrarla se
     * borrarían también todos sus productos. En ese caso se desactiva.
     */
    public function eliminar(Category $categoria): RedirectResponse
    {
        $nombre = $categoria->nombre;

        if ($categoria->productos()->exists()) {
            $categoria->update(['activa' => false]);

            return redirect()
                ->route('admin.categorias.index')
                ->with('exito', "«{$nombre}» tiene productos asociados, por lo que se ocultó de la tienda en lugar de eliminarse. Mueva o elimine sus productos si desea borrarla.");
        }

        $categoria->delete();

        return redirect()
            ->route('admin.categorias.index')
            ->with('exito', "La categoría «{$nombre}» se eliminó.");
    }

    /**
     * Normaliza los datos: genera el slug si viene vacío y convierte los
     * interruptores a booleano.
     */
    private function prepararDatos(CategoriaRequest $peticion): array
    {
        $datos = $peticion->validated();

        // validated() solo devuelve las claves que venían en el formulario:
        // los campos opcionales que el usuario dejó vacíos no aparecen, por eso
        // se leen con ?? antes de usarlos.
        $datos['slug']        = Str::slug(($datos['slug'] ?? '') ?: $datos['nombre']);
        $datos['icono']       = ($datos['icono'] ?? '') ?: 'bi-tag';
        $datos['descripcion'] = $datos['descripcion'] ?? null;
        $datos['activa']      = $peticion->boolean('activa');

        return $datos;
    }
}
