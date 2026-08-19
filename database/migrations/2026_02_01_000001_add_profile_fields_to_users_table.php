<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a la tabla users los datos personales que el cliente puede editar
 * desde su perfil, más la bandera que identifica a los administradores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefono', 30)->nullable()->after('email');
            $table->string('cedula', 30)->nullable()->after('telefono');
            $table->string('direccion')->nullable()->after('cedula');
            $table->string('ciudad', 100)->nullable()->after('direccion');
            $table->string('provincia', 100)->nullable()->after('ciudad');
            $table->boolean('es_admin')->default(false)->after('provincia');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telefono', 'cedula', 'direccion', 'ciudad', 'provincia', 'es_admin',
            ]);
        });
    }
};
