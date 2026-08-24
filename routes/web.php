<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Admin\PedidoAdminController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Auth\ContrasenaOlvidadaController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web de la tienda virtual
|--------------------------------------------------------------------------
|
| Todas las rutas POST/PUT/DELETE están protegidas automáticamente con el
| token CSRF por el middleware del grupo "web".
|
*/

/* ---------------------------------------------------------------------------
 | Catálogo (público)
 | ------------------------------------------------------------------------ */

Route::get('/', [CatalogoController::class, 'inicio'])->name('inicio');

Route::get('/productos', [CatalogoController::class, 'listado'])->name('catalogo.listado');
Route::get('/categoria/{categoria}', [CatalogoController::class, 'porCategoria'])->name('catalogo.categoria');
Route::get('/producto/{producto}', [CatalogoController::class, 'detalle'])->name('catalogo.detalle');

/* ---------------------------------------------------------------------------
 | Carrito (funciona con o sin sesión iniciada)
 | ------------------------------------------------------------------------ */

Route::controller(CarritoController::class)->prefix('carrito')->name('carrito.')->group(function () {
    Route::get('/', 'mostrar')->name('mostrar');
    Route::get('/contador', 'contador')->name('contador');
    Route::post('/agregar/{producto}', 'agregar')->name('agregar');
    Route::put('/linea/{linea}', 'actualizar')->name('actualizar');
    Route::delete('/linea/{linea}', 'eliminar')->name('eliminar');
    Route::delete('/vaciar', 'vaciar')->name('vaciar');
});

/* ---------------------------------------------------------------------------
 | Autenticación (solo visitantes)
 | ------------------------------------------------------------------------ */

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'mostrar'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'registrar'])->name('registro.guardar');

    Route::get('/ingresar', [SesionController::class, 'mostrar'])->name('login');
    Route::post('/ingresar', [SesionController::class, 'iniciar'])->name('login.enviar');

    // Recuperación de contraseña. Los nombres password.* son los que espera
    // el «password broker» de Laravel para armar el enlace del correo.
    Route::controller(ContrasenaOlvidadaController::class)->group(function () {
        Route::get('/olvide-mi-contrasena', 'mostrarSolicitud')->name('password.request');
        Route::post('/olvide-mi-contrasena', 'enviarEnlace')->name('password.email');
        Route::get('/restablecer-contrasena/{token}', 'mostrarRestablecer')->name('password.reset');
        Route::post('/restablecer-contrasena', 'restablecer')->name('password.update');
    });
});

Route::post('/salir', [SesionController::class, 'cerrar'])
    ->middleware('auth')
    ->name('logout');

/* ---------------------------------------------------------------------------
 | Área del cliente (requiere sesión)
 | ------------------------------------------------------------------------ */

Route::middleware('auth')->group(function () {

    // Perfil
    Route::get('/perfil', [PerfilController::class, 'mostrar'])->name('perfil.mostrar');
    Route::put('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');
    Route::put('/perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->name('perfil.contrasena');

    // Proceso de compra
    Route::get('/checkout', [CheckoutController::class, 'mostrar'])->name('checkout.mostrar');
    Route::post('/checkout', [CheckoutController::class, 'procesar'])->name('checkout.procesar');

    // Lista de deseos (relación muchos a muchos con la tabla pivote favoritos)
    Route::get('/mis-favoritos', [FavoritoController::class, 'index'])->name('favoritos.index');
    Route::post('/favoritos/{producto}', [FavoritoController::class, 'alternar'])->name('favoritos.alternar');
    Route::delete('/favoritos/{producto}', [FavoritoController::class, 'eliminar'])->name('favoritos.eliminar');

    // Pedidos e historial
    Route::get('/mis-pedidos', [PedidoController::class, 'historial'])->name('pedidos.historial');
    Route::get('/confirmacion/{numeroPedido}', [PedidoController::class, 'confirmacion'])->name('pedidos.confirmacion');
    Route::get('/mis-pedidos/{pedido}', [PedidoController::class, 'detalle'])->name('pedidos.detalle');
    Route::get('/mis-pedidos/{pedido}/factura', [PedidoController::class, 'facturaPdf'])->name('pedidos.factura');
});

/* ---------------------------------------------------------------------------
 | Panel de administración (requiere sesión + es_admin)
 | ------------------------------------------------------------------------ */

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', PanelController::class)->name('panel');

    // Catálogo
    Route::controller(ProductoController::class)->prefix('productos')->name('productos.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'crear')->name('crear');
        Route::post('/', 'guardar')->name('guardar');
        Route::get('/{producto}/editar', 'editar')->name('editar');
        Route::put('/{producto}', 'actualizar')->name('actualizar');
        Route::delete('/{producto}', 'eliminar')->name('eliminar');
    });

    // Categorías
    Route::controller(CategoriaController::class)->prefix('categorias')->name('categorias.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'crear')->name('crear');
        Route::post('/', 'guardar')->name('guardar');
        Route::get('/{categoria}/editar', 'editar')->name('editar');
        Route::put('/{categoria}', 'actualizar')->name('actualizar');
        Route::delete('/{categoria}', 'eliminar')->name('eliminar');
    });

    // Pedidos
    Route::controller(PedidoAdminController::class)->prefix('pedidos')->name('pedidos.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{pedido}', 'detalle')->name('detalle');
        Route::put('/{pedido}/estado', 'cambiarEstado')->name('estado');
    });

    // Reportes de ventas (pantalla y PDF)
    Route::controller(ReporteController::class)->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/por-mes', 'porMes')->name('por-mes');
        Route::get('/por-mes/pdf', 'porMesPdf')->name('por-mes.pdf');
        Route::get('/por-cliente', 'porCliente')->name('por-cliente');
        Route::get('/por-cliente/pdf', 'porClientePdf')->name('por-cliente.pdf');
        Route::get('/cliente/{cliente}/pdf', 'clienteDetallePdf')->name('cliente.pdf');
    });
});
