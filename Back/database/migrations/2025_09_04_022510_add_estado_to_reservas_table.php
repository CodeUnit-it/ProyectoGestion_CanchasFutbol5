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
    Schema::table('reservas', function (Blueprint $table) {
        $table->string('estado')->default('activa')->after('cancha_id');
    });
}

public function down(): void
{
    Schema::table('reservas', function (Blueprint $table) {
        $table->dropColumn('estado');
    });
}
};