<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Perfil de usuario: modificar datos personales y ver el historial de pedidos.
 */
class PerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_los_datos_personales_del_usuario(): void
    {
        $usuario = User::factory()->create([
            'name'      => 'Carlos Vargas Mora',
            'email'     => 'carlos@example.com',
            'telefono'  => '8744-9911',
            'provincia' => 'Cartago',
        ]);

        $this->actingAs($usuario)
            ->get(route('perfil.mostrar'))
            ->assertOk()
            ->assertSee('Carlos Vargas Mora')
            ->assertSee('carlos@example.com')
            ->assertSee('8744-9911');
    }

    public function test_muestra_el_resumen_de_compras_en_el_perfil(): void
    {
        $usuario = User::factory()->create();

        Order::factory()->count(2)->create([
            'user_id' => $usuario->id,
            'total'   => 50000,
        ]);

        $this->actingAs($usuario)
            ->get(route('perfil.mostrar'))
            ->assertOk()
            ->assertSee('₡100 000');   // total comprado
    }

    public function test_actualiza_los_datos_personales(): void
    {
        $usuario = User::factory()->create(['name' => 'Nombre Viejo']);

        $this->actingAs($usuario)
            ->put(route('perfil.actualizar'), [
                'name'      => 'Ana Solís Castro',
                'email'     => $usuario->email,
                'telefono'  => '6055-4477',
                'cedula'    => '3-0321-0654',
                'direccion' => 'Barrio El Carmen, apartamento 3B',
                'ciudad'    => 'Alajuela',
                'provincia' => 'Alajuela',
            ])
            ->assertRedirect(route('perfil.mostrar'))
            ->assertSessionHas('exito');

        $usuario->refresh();

        $this->assertSame('Ana Solís Castro', $usuario->name);
        $this->assertSame('6055-4477', $usuario->telefono);
        $this->assertSame('Alajuela', $usuario->provincia);
    }

    public function test_valida_los_datos_del_perfil(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->put(route('perfil.actualizar'), [
                'name'     => 'X',
                'email'    => 'no-es-correo',
                'telefono' => 'abc-def',
                'cedula'   => 'letras',
            ])
            ->assertSessionHasErrors(['name', 'email', 'telefono', 'cedula']);
    }

    public function test_no_permite_usar_el_correo_de_otro_usuario(): void
    {
        $otro    = User::factory()->create(['email' => 'ocupado@example.com']);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->put(route('perfil.actualizar'), [
                'name'  => 'Nombre Valido',
                'email' => 'ocupado@example.com',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_puede_guardar_manteniendo_su_propio_correo(): void
    {
        $usuario = User::factory()->create(['email' => 'mio@example.com']);

        $this->actingAs($usuario)
            ->put(route('perfil.actualizar'), [
                'name'  => 'Mi Nombre Nuevo',
                'email' => 'mio@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Mi Nombre Nuevo', $usuario->fresh()->name);
    }

    public function test_puede_cambiar_su_correo_electronico(): void
    {
        $usuario = User::factory()->create(['email' => 'viejo@example.com']);

        $this->actingAs($usuario)->put(route('perfil.actualizar'), [
            'name'  => 'Nombre Valido',
            'email' => 'correo.nuevo@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame('correo.nuevo@example.com', $usuario->fresh()->email);
    }

    public function test_el_formulario_del_perfil_no_puede_cambiar_campos_protegidos(): void
    {
        $usuario = User::factory()->create(['es_admin' => false]);

        // El controlador guarda únicamente $peticion->validated(), que solo
        // devuelve los campos declarados en las reglas de PerfilRequest.
        // Cualquier otro campo que llegue en el formulario se descarta.
        $this->actingAs($usuario)->put(route('perfil.actualizar'), [
            'name'              => 'Nombre Valido',
            'email'             => $usuario->email,
            'es_admin'          => 1,
            'email_verified_at' => null,
        ]);

        $this->assertFalse($usuario->fresh()->es_admin);
    }

    /* ==================================================================
     | Cambio de contraseña
     | ================================================================ */

    public function test_cambia_la_contrasena(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

        $this->actingAs($usuario)
            ->put(route('perfil.contrasena'), [
                'password_actual'       => 'ClaveVieja1',
                'password'              => 'ClaveNueva2',
                'password_confirmation' => 'ClaveNueva2',
            ])
            ->assertRedirect(route('perfil.mostrar'))
            ->assertSessionHas('exito');

        $this->assertTrue(Hash::check('ClaveNueva2', $usuario->fresh()->password));
    }

    public function test_exige_la_contrasena_actual_para_cambiarla(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

        $this->actingAs($usuario)
            ->put(route('perfil.contrasena'), [
                'password_actual'       => 'NoEsLaCorrecta1',
                'password'              => 'ClaveNueva2',
                'password_confirmation' => 'ClaveNueva2',
            ])
            ->assertSessionHasErrors('password_actual');

        $this->assertTrue(Hash::check('ClaveVieja1', $usuario->fresh()->password));
    }

    public function test_la_nueva_contrasena_debe_ser_distinta_y_segura(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

        // Igual a la actual
        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'password_actual'       => 'ClaveVieja1',
            'password'              => 'ClaveVieja1',
            'password_confirmation' => 'ClaveVieja1',
        ])->assertSessionHasErrors('password');

        // Demasiado débil
        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'password_actual'       => 'ClaveVieja1',
            'password'              => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');
    }

    public function test_la_contrasena_no_se_guarda_en_la_sesion_al_fallar(): void
    {
        $usuario = User::factory()->create(['password' => 'ClaveVieja1']);

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'password_actual'       => 'Incorrecta1',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ]);

        $this->assertNull(session('_old_input.password'));
        $this->assertNull(session('_old_input.password_actual'));
    }
}
