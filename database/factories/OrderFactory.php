<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Services\TotalesCarrito;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(15_000, 600_000);
        $totales  = TotalesCarrito::calcular($subtotal, fake()->numberBetween(1, 5));

        return [
            'user_id'            => User::factory(),
            'numero_pedido'      => 'PED-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'numero_seguimiento' => 'TS'.strtoupper(fake()->unique()->bothify('######??????')),
            'estado'             => 'pagado',
            'subtotal'           => $totales->subtotal,
            'impuesto'           => $totales->impuesto,
            'envio'              => $totales->envio,
            'total'              => $totales->total,
            'tasa_impuesto'      => $totales->tasaImpuesto,
            'metodo_pago'        => fake()->randomElement(['tarjeta', 'paypal', 'sinpe']),
            'estado_pago'        => 'aprobado',
            'envio_nombre'       => fake()->name(),
            'envio_telefono'     => '8'.fake()->numerify('###-####'),
            'envio_direccion'    => fake()->streetAddress(),
            'envio_ciudad'       => fake()->city(),
            'envio_provincia'    => 'San José',
            'notas'              => null,
            'fecha_compra'       => now(),
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn () => [
            'estado'      => 'pendiente',
            'estado_pago' => 'pendiente',
        ]);
    }

    public function enFecha(string $fecha): static
    {
        return $this->state(fn () => ['fecha_compra' => $fecha]);
    }
}
