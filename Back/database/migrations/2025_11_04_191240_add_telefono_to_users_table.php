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
    Schema::table('users', function (Blueprint $table) {
        $table->string('telefono')->nullable()->after('email'); // nullable por si hay usuarios sin teléfono aún
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('telefono');
    });
}
};
