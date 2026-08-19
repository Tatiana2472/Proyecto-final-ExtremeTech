<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\VistosRecientementeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo: portada, listado con búsqueda y filtros, y detalle del producto.
 */
class CatalogoController extends Controller
{
    public function __construct(protected VistosRecientementeService $vistos)
    {
    }

    /** Portada de la tienda. */
    public function inicio(): View
    {
        return view('inicio', [
            'categorias'  => Category::activas()->withCount(['productos' => fn ($q) => $q->where('activo', true)])->get(),
            'destacados'  => Product::activos()->destacados()->with('categoria')->take(8)->get(),
            'novedades'   => Product::activos()->with('categoria')->latest()->take(4)->get(),
            'ofertas'     => Product::activos()->whereNotNull('precio_anterior')->with('categoria')->take(4)->get(),
            // Productos vistos recientemente, leídos de la cookie.
            'vistos'      => $this->vistos->productos(),
        ]);
    }

    /**
     * Listado del catálogo con búsqueda y filtrado por nombre, categoría y precio.
     *
     * Todos los filtros se aplican mediante los scopes del modelo Product, que
     * usan consultas preparadas: el texto que escribe el usuario nunca se
     * concatena dentro del SQL.
     */
    public function listado(Request $peticion): View
    {
        // Se validan los filtros que llegan por la URL antes de usarlos.
        $filtros = $peticion->validate([
            'q'        => ['nullable', 'string', 'max:120'],
            'categoria'=> ['nullable', 'exists:categories,slug'],
            'min'      => ['nullable', 'numeric', 'min:0'],
            'max'      => ['nullable', 'numeric', 'min:0'],
            'orden'    => ['nullable', 'in:recientes,antiguos,precio_asc,precio_desc,nombre'],
        ]);

        $categoria = isset($filtros['categoria'])
            ? Category::where('slug', $filtros['categoria'])->first()
            : null;

        $productos = Product::activos()
            ->with('categoria')
            ->buscar($filtros['q'] ?? null)
            ->deCategoria($categoria?->id)
            ->precioMinimo($filtros['min'] ?? null)
            ->precioMaximo($filtros['max'] ?? null)
            ->ordenar($filtros['orden'] ?? null)
            ->paginate(config('tienda.productos_por_pagina', 9))
            ->withQueryString();

        return view('catalogo.listado', [
            'productos'  => $productos,
            'categorias' => Category::activas()->orderBy('nombre')->get(),
            'categoria'  => $categoria,
            'filtros'    => $filtros,
            'rangoPrecios' => [
                'min' => (float) Product::activos()->min('precio'),
                'max' => (float) Product::activos()->max('precio'),
            ],
            'vistos' => $this->vistos->productos(),
        ]);
    }

    /** Productos de una categoría (URL amigable /categoria/{slug}). */
    public function porCategoria(Request $peticion, Category $categoria): View
    {
        abort_unless($categoria->activa, 404);

        $peticion->merge(['categoria' => $categoria->slug]);

        return $this->listado($peticion);
    }

    /**
     * Detalle del producto.
     *
     * Acá se registra el producto en la cookie de "vistos recientemente".
     */
    public function detalle(Product $producto): View
    {
        abort_unless($producto->activo, 404);

        // Se guarda ANTES de leer la lista, y luego se excluye el producto
        // actual para no mostrarlo dentro de sus propias recomendaciones.
        $this->vistos->registrar($producto);

        $relacionados = Product::activos()
            ->where('category_id', $producto->category_id)
            ->where('id', '!=', $producto->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('catalogo.detalle', [
            'producto'     => $producto->load('categoria'),
            'relacionados' => $relacionados,
            'vistos'       => $this->vistos->productos($producto->id),
        ]);
    }
}
