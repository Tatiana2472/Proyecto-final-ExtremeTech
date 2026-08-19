<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de facturas.
 *
 * Se separa del pedido porque un pedido es un documento comercial (puede
 * cancelarse) mientras que la factura es el comprobante fiscal que ya se
 * emitió. Contiene la identificación del usuario, la fecha de emisión y el
 * monto facturado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('numero_factura', 30)->unique();
            $table->timestamp('fecha_emision');

            // Datos fiscales del cliente al momento de emitir.
            $table->string('cliente_nombre', 160);
            $table->string('cliente_correo', 160);
            $table->string('cliente_cedula', 30)->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('envio', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('moneda', 3)->default('CRC');

            $table->timestamps();

            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
