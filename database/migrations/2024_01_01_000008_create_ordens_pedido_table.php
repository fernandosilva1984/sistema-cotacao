<?php
// database/migrations/2024_01_01_000008_create_ordens_pedido_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ordens_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->foreignId('id_usuario')->constrained('users');
            $table->foreignId('id_fornecedor')->constrained('fornecedores');
            $table->foreignId('id_cotacao')->nullable()->constrained('cotacoes');
            $table->date('data');
            $table->string('numero')->unique();
            $table->text('observacao')->nullable();
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->enum('status', ['pendente', 'aprovada', 'entregue', 'cancelada'])->default('pendente');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ordens_pedido');
    }
};