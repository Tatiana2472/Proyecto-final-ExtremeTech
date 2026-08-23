<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Líneas del carrito. Guarda el precio unitario con que se mostró el producto
 * al agregarlo; si el catálogo cambia de precio, CarritoService::sincronizarPrecios()
 * pone la línea al día y se le advierte al cliente antes de cobrarle, para que
 * el total en pantalla y el total cobrado nunca difieran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2);
            $table->timestamps();

            // Un producto solo puede aparecer una vez por carrito: si se
            // vuelve a agregar, se actualiza la cantidad.
            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
