<?php
// database/migrations/2024_01_01_000004_create_marcas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->string('nome');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marcas');
    }
};