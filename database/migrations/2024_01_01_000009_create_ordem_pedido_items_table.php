<?php
// database/migrations/2024_01_01_000009_create_ordem_pedido_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ordem_pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ordem_pedido')->constrained('ordens_pedido')->onDelete('cascade');
            $table->foreignId('id_produto')->nullable()->constrained('produtos');
            $table->foreignId('id_marca')->constrained('marcas');
            $table->foreignId('id_cotacao_item')->nullable()->constrained('cotacao_items');
            $table->string('descricao_produto');
            $table->decimal('quantidade', 10, 2);
            $table->decimal('valor_unitario', 15, 2);
            $table->decimal('valor_total_prod', 15, 2);
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ordem_pedido_items');
    }
};