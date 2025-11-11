<?php
// database/migrations/2024_01_01_000005_create_produtos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->foreignId('id_marca')->constrained('marcas');
            $table->string('descricao');
            $table->text('observacao')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->string('unidade_medida')->default('UN');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produtos');
    }
};