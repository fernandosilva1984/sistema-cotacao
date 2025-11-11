<?php
// database/migrations/2024_01_01_000002_add_empresa_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_empresa')->nullable()->constrained('empresas');
            $table->boolean('status')->default(true);
            $table->boolean('is_master')->default(false);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_empresa']);
            $table->dropColumn(['id_empresa', 'status', 'is_master']);
        });
    }
};