<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImagenToUsersAndProductosTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('imagen', 300)->default('noimagen.jpg');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen', 300)->default('noimagen.jpg');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
}
