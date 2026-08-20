<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de demostración.
 *
 * Las contraseñas se guardan cifradas con bcrypt: el cast 'hashed' del modelo
 * User aplica el hash automáticamente, aquí solo se escribe el texto plano.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@extremtech.cr'],
            [
                'name'              => 'Administrador ExtremTech',
                'password'          => 'Admin1234*',
                'telefono'          => '2222-3333',
                'cedula'            => '1-1111-1111',
                'direccion'         => 'Avenida Central, oficina 4',
                'ciudad'            => 'San José',
                'provincia'         => 'San José',
                'es_admin'          => true,
                'email_verified_at' => now(),
            ]
        );

        $clientes = [
            [
                'name'      => 'María Rodríguez Jiménez',
                'email'     => 'maria@example.com',
                'telefono'  => '8811-2233',
                'cedula'    => '1-1234-5678',
                'direccion' => '200 m norte de la iglesia, casa blanca',
                'ciudad'    => 'Heredia',
                'provincia' => 'Heredia',
            ],
            [
                'name'      => 'Carlos Vargas Mora',
                'email'     => 'carlos@example.com',
                'telefono'  => '8744-9911',
                'cedula'    => '2-0456-0789',
                'direccion' => 'Residencial Los Robles, casa 12',
                'ciudad'    => 'Cartago',
                'provincia' => 'Cartago',
            ],
            [
                'name'      => 'Ana Solís Castro',
                'email'     => 'ana@example.com',
                'telefono'  => '6055-4477',
                'cedula'    => '3-0321-0654',
                'direccion' => 'Barrio El Carmen, apartamento 3B',
                'ciudad'    => 'Alajuela',
                'provincia' => 'Alajuela',
            ],
            [
                'name'      => 'Luis Fernández Ruiz',
                'email'     => 'luis@example.com',
                'telefono'  => '7012-8899',
                'cedula'    => '1-0987-0123',
                'direccion' => 'Frente al parque central',
                'ciudad'    => 'Liberia',
                'provincia' => 'Guanacaste',
            ],
        ];

        foreach ($clientes as $cliente) {
            User::updateOrCreate(
                ['email' => $cliente['email']],
                $cliente + [
                    'password'          => 'Cliente1234*',
                    'es_admin'          => false,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
