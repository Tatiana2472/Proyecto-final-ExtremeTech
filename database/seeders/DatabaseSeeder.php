<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Carga completa de datos iniciales.
 *
 * Ejecutar con:  php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            VentasDemoSeeder::class,
        ]);
    }
}
