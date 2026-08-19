<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de compras (pedidos).
 *
 * Incluye la identificación del usuario (user_id), la fecha de compra
 * (fecha_compra), el monto de la compra (total) y el desglose de impuestos y
 * envío, además del número de seguimiento del pedido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('numero_pedido', 30)->unique();
            $table->string('numero_seguimiento', 40)->unique();

            // pendiente | pagado | enviado | entregado | cancelado
            $table->string('estado', 20)->default('pendiente');

            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('envio', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('tasa_impuesto', 5, 4);

            // tarjeta | paypal | sinpe
            $table->string('metodo_pago', 20);
            // pendiente | aprobado | rechazado
            $table->string('estado_pago', 20)->default('pendiente');

            // Datos de envío tomados del formulario de checkout.
            $table->string('envio_nombre', 160);
            $table->string('envio_telefono', 30);
            $table->string('envio_direccion');
            $table->string('envio_ciudad', 100);
            $table->string('envio_provincia', 100);
            $table->text('notas')->nullable();

            $table->timestamp('fecha_compra')->nullable();
            $table->timestamps();

            // Índices usados por los reportes de ventas por mes y por cliente.
            $table->index('fecha_compra');
            $table->index(['user_id', 'estado_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
