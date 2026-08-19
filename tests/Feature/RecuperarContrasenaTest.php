<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerContrasena;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Recuperación de contraseña por correo.
 */
class RecuperarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_el_formulario_de_recuperacion(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('¿Olvidó su contraseña?');
    }

    public function test_el_enlace_aparece_en_la_pantalla_de_inicio_de_sesion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), escape: false);
    }

    public function test_envia_el_correo_con_el_enlace(): void
    {
        Notification::fake();

        $usuario = User::factory()->create(['email' => 'cliente@example.com']);

        $this->post(route('password.email'), ['email' => 'cliente@example.com'])
            ->assertSessionHas('exito');

        Notification::assertSentTo($usuario, RestablecerContrasena::class);
    }

    public function test_no_revela_si_el_correo_existe(): void
    {
        Notification::fake();

        // Con un correo que no está registrado la respuesta es la misma,
        // para que nadie pueda averiguar qué cuentas existen.
        $this->post(route('password.email'), ['email' => 'noexiste@example.com'])
            ->assertSessionHas('exito')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_valida_el_correo(): void
    {
        $this->post(route('password.email'), ['email' => 'no-es-un-correo'])
            ->assertSessionHasErrors('email');

        $this->post(route('password.email'), [])
            ->assertSessionHasErrors('email');
    }

    public function test_muestra_el_formulario_para_la_nueva_contrasena(): void
    {
        $this->get(route('password.reset', ['token' => 'un-token', 'email' => 'cliente@example.com']))
            ->assertOk()
            ->assertSee('Definir una nueva contraseña')
            ->assertSee('cliente@example.com');
    }

    public function test_restablece_la_contrasena_con_un_token_valido(): void
    {
        Event::fake();

        $usuario = User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'ClaveVieja1',
        ]);

        // Se genera un token real con el mismo mecanismo que usa el correo.
        $token = Password::createToken($usuario);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'cliente@example.com',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('exito');

        $this->assertTrue(Hash::check('ClaveNueva2', $usuario->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_el_token_no_se_puede_reutilizar(): void
    {
        $usuario = User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'ClaveVieja1',
        ]);

        $token = Password::createToken($usuario);

        $datos = [
            'token'                 => $token,
            'email'                 => 'cliente@example.com',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ];

        $this->post(route('password.update'), $datos)->assertRedirect(route('login'));

        // El mismo enlace ya no sirve una segunda vez.
        $this->post(route('password.update'), array_merge($datos, [
            'password'              => 'OtraClave3',
            'password_confirmation' => 'OtraClave3',
        ]))->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('ClaveNueva2', $usuario->fresh()->password));
    }

    public function test_rechaza_un_token_invalido(): void
    {
        $usuario = User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'ClaveVieja1',
        ]);

        $this->post(route('password.update'), [
            'token'                 => 'token-inventado',
            'email'                 => 'cliente@example.com',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ])->assertSessionHasErrors('email');

        // La contraseña no cambió.
        $this->assertTrue(Hash::check('ClaveVieja1', $usuario->fresh()->password));
    }

    public function test_exige_una_contrasena_segura_y_confirmada(): void
    {
        $usuario = User::factory()->create(['email' => 'cliente@example.com']);
        $token   = Password::createToken($usuario);

        // Demasiado débil
        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'cliente@example.com',
            'password'              => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');

        // La confirmación no coincide
        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'cliente@example.com',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'OtraDistinta3',
        ])->assertSessionHasErrors('password');
    }

    public function test_la_nueva_contrasena_se_guarda_cifrada(): void
    {
        $usuario = User::factory()->create(['email' => 'cliente@example.com']);

        $this->post(route('password.update'), [
            'token'                 => Password::createToken($usuario),
            'email'                 => 'cliente@example.com',
            'password'              => 'ClaveNueva2',
            'password_confirmation' => 'ClaveNueva2',
        ]);

        $guardada = $usuario->fresh()->password;

        $this->assertNotSame('ClaveNueva2', $guardada);
        $this->assertStringStartsWith('$2y$', $guardada);
    }

    public function test_un_usuario_autenticado_no_usa_esta_pantalla(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('password.request'))
            ->assertRedirect();
    }
}
