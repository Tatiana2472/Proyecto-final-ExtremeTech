<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->words(2, true);

        return [
            'nombre'      => Str::ucfirst($nombre),
            'slug'        => Str::slug($nombre).'-'.fake()->unique()->numberBetween(1, 99999),
            'descripcion' => fake()->sentence(),
            'icono'       => 'bi-tag',
            'activa'      => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn () => ['activa' => false]);
    }
}
