<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carrito de compras.
 *
 * Se guarda en la base de datos y no solo en la sesión, para que el carrito
 * de un visitante no registrado sobreviva y se pueda "adoptar" cuando el
 * usuario inicia sesión (ver App\Services\CarritoService::adoptarCarrito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('token_sesion', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
