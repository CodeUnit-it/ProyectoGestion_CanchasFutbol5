<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Primero eliminamos la clave foránea actual
            $table->dropForeign(['user_id']);
            // Luego la recreamos con onDelete('cascade')
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // En el down revertimos al comportamiento anterior
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
