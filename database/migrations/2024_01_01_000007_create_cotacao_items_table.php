<?php
// database/migrations/2024_01_01_000007_create_cotacao_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cotacao_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotacao')->constrained('cotacoes')->onDelete('cascade');
            $table->foreignId('id_produto')->nullable()->constrained('produtos');
            $table->foreignId('id_marca')->constrained('marcas');
            $table->string('descricao_produto');
            $table->decimal('quantidade', 10, 2);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_total_prod', 15, 2)->default(0);
            $table->decimal('valor_unitario_resposta', 15, 2)->nullable();
            $table->decimal('valor_total_resposta', 15, 2)->nullable();
            $table->text('observacao')->nullable();
            $table->text('observacao_resposta')->nullable();
            $table->boolean('selecionado')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotacao_items');
    }
};