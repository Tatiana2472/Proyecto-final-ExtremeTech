<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Autenticación y gestión de usuarios: registro, inicio y cierre de sesión.
 */
class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    /* ==================================================================
     | Registro de usuarios nuevos
     | ================================================================ */

    public function test_muestra_el_formulario_de_registro(): void
    {
        $this->get(route('registro'))
            ->assertOk()
            ->assertSee('Crear una cuenta');
    }

    public function test_registra_un_usuario_nuevo_y_lo_autentica(): void
    {
        $respuesta = $this->post(route('registro.guardar'), [
            'name'                  => 'Ana Solís Castro',
            'email'                 => 'ana.nueva@example.com',
            'telefono'              => '8888-1122',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ]);

        $respuesta->assertRedirect(route('inicio'));

        $this->assertDatabaseHas('users', [
            'email'    => 'ana.nueva@example.com',
            'name'     => 'Ana Solís Castro',
            'es_admin' => false,
        ]);

        $this->assertAuthenticated();
    }

    public function test_la_contrasena_se_guarda_cifrada_con_bcrypt(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => 'Carlos Vargas',
            'email'                 => 'carlos.nuevo@example.com',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ]);

        $usuario = User::where('email', 'carlos.nuevo@example.com')->firstOrFail();

        // Nunca se almacena el texto plano.
        $this->assertNotSame('Clave1234', $usuario->password);
        $this->assertTrue(Hash::check('Clave1234', $usuario->password));
        $this->assertStringStartsWith('$2y$', $usuario->password);
    }

    public function test_el_correo_se_normaliza_a_minusculas(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => 'Luis Fernández',
            'email'                 => '  LUIS.MAYUSCULAS@Example.COM  ',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'luis.mayusculas@example.com']);
    }

    public function test_no_permite_registrar_un_correo_repetido(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->post(route('registro.guardar'), [
            'name'                  => 'Otra Persona',
            'email'                 => 'repetido@example.com',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'repetido@example.com')->count());
    }

    public function test_valida_los_datos_del_registro(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => 'A',                 // muy corto
            'email'                 => 'no-es-correo',
            'password'              => '123',               // débil
            'password_confirmation' => '456',               // no coincide
        ])->assertSessionHasErrors(['name', 'email', 'password', 'terminos']);

        $this->assertGuest();
    }

    public function test_no_permite_registrar_un_correo_con_formato_invalido(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => 'Persona Válida',
            'email'                 => 'persona@',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_exige_aceptar_los_terminos(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => 'María Rodríguez',
            'email'                 => 'maria.terminos@example.com',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
        ])->assertSessionHasErrors('terminos');
    }

    public function test_rechaza_un_nombre_con_caracteres_extranos(): void
    {
        $this->post(route('registro.guardar'), [
            'name'                  => '<script>alert(1)</script>',
            'email'                 => 'xss@example.com',
            'password'              => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'terminos'              => '1',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('users', ['email' => 'xss@example.com']);
    }

    /* ==================================================================
     | Inicio de sesión
     | ================================================================ */

    public function test_muestra_el_formulario_de_inicio_de_sesion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_un_cliente_inicia_sesion_con_credenciales_correctas(): void
    {
        $usuario = User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        $this->post(route('login.enviar'), [
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ])->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_administrador_es_enviado_a_su_panel(): void
    {
        User::factory()->administrador()->create([
            'email'    => 'admin@example.com',
            'password' => 'Clave1234',
        ]);

        $this->post(route('login.enviar'), [
            'email'    => 'admin@example.com',
            'password' => 'Clave1234',
        ])->assertRedirect(route('admin.panel'));
    }

    public function test_no_inicia_sesion_con_contrasena_incorrecta(): void
    {
        User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        $this->post(route('login.enviar'), [
            'email'    => 'cliente@example.com',
            'password' => 'ClaveEquivocada1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bloquea_temporalmente_despues_de_cinco_intentos_fallidos(): void
    {
        User::factory()->create([
            'email'    => 'victima@example.com',
            'password' => 'Clave1234',
        ]);

        // Cinco intentos fallidos consumen el límite.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.enviar'), [
                'email'    => 'victima@example.com',
                'password' => 'incorrecta'.$i,
            ]);
        }

        // El sexto intento se rechaza incluso con la contraseña correcta.
        $respuesta = $this->post(route('login.enviar'), [
            'email'    => 'victima@example.com',
            'password' => 'Clave1234',
        ]);

        $respuesta->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_el_carrito_del_visitante_se_conserva_al_iniciar_sesion(): void
    {
        $producto = Product::factory()->create(['existencias' => 10]);
        $usuario  = User::factory()->create([
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        // Agrega al carrito sin haber iniciado sesión.
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 2]);

        $this->post(route('login.enviar'), [
            'email'    => 'cliente@example.com',
            'password' => 'Clave1234',
        ]);

        $this->assertAuthenticatedAs($usuario);
        $this->assertDatabaseHas('carts', ['user_id' => $usuario->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $producto->id,
            'cantidad'   => 2,
        ]);
    }

    /* ==================================================================
     | Cierre de sesión
     | ================================================================ */

    public function test_cierra_la_sesion_correctamente(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('logout'))
            ->assertRedirect(route('inicio'));

        $this->assertGuest();
    }

    public function test_el_cierre_de_sesion_no_funciona_por_get(): void
    {
        $usuario = User::factory()->create();

        // Si /salir respondiera a GET, un enlace externo podría cerrar la
        // sesión del usuario sin su consentimiento (CSRF por GET).
        $this->actingAs($usuario)->get('/salir')->assertStatus(405);

        $this->assertAuthenticatedAs($usuario);
    }

    /* ==================================================================
     | Rutas protegidas
     | ================================================================ */

    public function test_un_visitante_no_entra_al_area_de_clientes(): void
    {
        foreach ([
            route('perfil.mostrar'),
            route('pedidos.historial'),
            route('checkout.mostrar'),
        ] as $ruta) {
            $this->get($ruta)->assertRedirect(route('login'));
        }
    }

    public function test_un_usuario_autenticado_no_ve_el_formulario_de_registro(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('registro'))
            ->assertRedirect();
    }
}
