<?php
// database/migrations/2024_01_01_000010_create_cotacao_fornecedor_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cotacao_fornecedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotacao')->constrained('cotacoes')->onDelete('cascade');
            $table->foreignId('id_fornecedor')->constrained('fornecedores')->onDelete('cascade');
            $table->enum('status', ['pendente', 'enviada', 'respondida', 'recusada'])->default('pendente');
            $table->text('resposta_fornecedor')->nullable();
            $table->timestamp('data_envio')->nullable();
            $table->timestamp('data_resposta')->nullable();
            $table->timestamps();
            
            $table->unique(['id_cotacao', 'id_fornecedor']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotacao_fornecedor');
    }
};