<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_mudanca_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('dias_atuais')->nullable();
            $table->json('dias_solicitados');
            $table->text('motivo');
            $table->enum('status', ['pendente', 'aprovada', 'rejeitada'])->default('pendente');
            $table->text('motivo_rejeicao')->nullable();
            $table->foreignId('avaliado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('avaliado_em')->nullable();
            $table->timestamps();

            // Índice para busca por status
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_mudanca_dias');
    }
};
