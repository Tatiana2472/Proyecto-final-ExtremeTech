<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de las transacciones devueltas por la pasarela de pago.
 *
 * IMPORTANTE (seguridad): nunca se almacena el número completo de la tarjeta
 * ni el CVV. Solo se guardan los últimos 4 dígitos y la marca, que es lo que
 * permite el estándar PCI-DSS para mostrar el comprobante al cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('metodo', 20);              // tarjeta | paypal | sinpe
            $table->string('estado', 20);              // aprobado | rechazado
            $table->decimal('monto', 12, 2);
            $table->string('moneda', 3)->default('CRC');

            $table->string('id_transaccion', 60)->nullable()->index();
            $table->string('tarjeta_marca', 20)->nullable();
            $table->string('tarjeta_ultimos4', 4)->nullable();
            $table->string('correo_pagador', 160)->nullable();

            $table->string('mensaje')->nullable();
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
