<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $nombre = Str::ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id'     => Category::factory(),
            'nombre'          => $nombre,
            'slug'            => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku'             => strtoupper(Str::random(8)),
            'marca'           => fake()->randomElement(['Lenovo', 'HP', 'Samsung', 'Xiaomi', 'Logitech']),
            'resumen'         => fake()->sentence(8),
            'descripcion'     => fake()->paragraphs(2, true),
            'precio'          => fake()->numberBetween(5_000, 900_000),
            'precio_anterior' => null,
            'existencias'     => fake()->numberBetween(3, 40),
            'imagen'          => null,
            'destacado'       => false,
            'activo'          => true,
        ];
    }

    public function destacado(): static
    {
        return $this->state(fn () => ['destacado' => true]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    public function agotado(): static
    {
        return $this->state(fn () => ['existencias' => 0]);
    }

    public function conPrecio(float $precio): static
    {
        return $this->state(fn () => ['precio' => $precio]);
    }
}
