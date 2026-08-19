<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Productos del catálogo. Cada producto pertenece a una categoría
 * (relación uno a muchos vista en la sesión 10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('nombre', 160);
            $table->string('slug', 180)->unique();
            $table->string('sku', 40)->unique();
            $table->string('marca', 80)->nullable();
            $table->string('resumen', 255)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2);
            $table->decimal('precio_anterior', 12, 2)->nullable();
            $table->unsignedInteger('existencias')->default(0);
            $table->string('imagen')->nullable();
            $table->boolean('destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índices para acelerar la búsqueda y el filtrado del catálogo.
            $table->index('precio');
            $table->index(['activo', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
