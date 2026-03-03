<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('canchas', function (Blueprint $table) {
            $table->integer('cant_jugadores')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('canchas', function (Blueprint $table) {
            $table->dropColumn('cant_jugadores');
        });
    }
};
