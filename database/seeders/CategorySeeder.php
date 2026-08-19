<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Categorías de la tienda de tecnología.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre'      => 'Laptops',
                'slug'        => 'laptops',
                'icono'       => 'bi-laptop',
                'descripcion' => 'Computadoras portátiles para estudio, oficina y diseño.',
            ],
            [
                'nombre'      => 'Celulares y tablets',
                'slug'        => 'celulares-y-tablets',
                'icono'       => 'bi-phone',
                'descripcion' => 'Teléfonos inteligentes y tabletas de las mejores marcas.',
            ],
            [
                'nombre'      => 'Audio',
                'slug'        => 'audio',
                'icono'       => 'bi-headphones',
                'descripcion' => 'Audífonos, parlantes y micrófonos para trabajo y entretenimiento.',
            ],
            [
                'nombre'      => 'Gaming',
                'slug'        => 'gaming',
                'icono'       => 'bi-controller',
                'descripcion' => 'Consolas, controles y equipo para jugadores.',
            ],
            [
                'nombre'      => 'Monitores',
                'slug'        => 'monitores',
                'icono'       => 'bi-display',
                'descripcion' => 'Pantallas para productividad, diseño y videojuegos.',
            ],
            [
                'nombre'      => 'Accesorios',
                'slug'        => 'accesorios',
                'icono'       => 'bi-keyboard',
                'descripcion' => 'Teclados, mouse, almacenamiento y todo lo que necesita su equipo.',
            ],
        ];

        foreach ($categorias as $categoria) {
            Category::updateOrCreate(['slug' => $categoria['slug']], $categoria);
        }
    }
}
