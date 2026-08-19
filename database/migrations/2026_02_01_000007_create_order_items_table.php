<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle del pedido.
 *
 * Se copian el nombre y el precio del producto en el momento de la compra
 * (snapshot histórico): si mañana el producto cambia de precio o se elimina
 * del catálogo, la factura emitida no se altera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre_producto', 160);
            $table->string('sku', 40)->nullable();
            $table->decimal('precio_unitario', 12, 2);
            $table->unsignedInteger('cantidad');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
