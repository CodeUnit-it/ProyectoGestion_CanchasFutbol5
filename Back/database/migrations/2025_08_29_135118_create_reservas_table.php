<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('reservas', function (Blueprint $table) {
        $table->id();
        $table->string('cliente');
        $table->string('telefono')->nullable();
        $table->date('fecha');
        $table->time('hora_inicio');
        $table->time('hora_fin');
        $table->unsignedBigInteger('cancha_id');
        $table->timestamps();

        // Relación con la tabla canchas (si la tienes creada)
        $table->foreign('cancha_id')->references('id')->on('canchas')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};

