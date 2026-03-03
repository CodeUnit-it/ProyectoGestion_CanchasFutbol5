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
        Schema::create('canchas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "Cancha 1", "Cancha techada"
            $table->string('tipo')->nullable(); // Ej: "Fútbol 5", "Fútbol 7"
            $table->decimal('precio_hora', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canchas');
    }
};
