<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'telefono'          => '8'.fake()->numerify('###-####'),
            'cedula'            => fake()->numerify('#-####-####'),
            'direccion'         => fake()->streetAddress(),
            'ciudad'            => fake()->city(),
            'provincia'         => fake()->randomElement([
                'San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón',
            ]),
            'es_admin'          => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** Usuario con permisos de administración. */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_admin' => true,
        ]);
    }
}
