<?php
// database/migrations/2024_01_01_000014_create_import_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tabela principal de imports
        if (!Schema::hasTable('imports')) {
            Schema::create('imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('importer');
                $table->unsignedInteger('total_rows');
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('successful_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('errors')->nullable();
                $table->timestamps();
            });
        }

        // Tabela para batches de importação
        if (!Schema::hasTable('import_batches')) {
            Schema::create('import_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_id')->constrained()->cascadeOnDelete();
                $table->json('data');
                $table->json('errors')->nullable();
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('successful_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        // Tabela para linhas com falha na importação
        if (!Schema::hasTable('failed_import_rows')) {
            Schema::create('failed_import_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('import_id')->constrained()->cascadeOnDelete();
                $table->json('data');
                $table->text('validation_error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('failed_import_rows');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('imports');
    }
};