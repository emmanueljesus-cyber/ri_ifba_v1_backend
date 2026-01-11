<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipo')->default('geral');
            $table->string('titulo');
            $table->text('mensagem');
            $table->json('dados')->nullable(); // Dados extras (ex: justificativa_id)
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['user_id', 'lida_em']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
