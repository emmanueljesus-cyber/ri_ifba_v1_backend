<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF15 - Tabela de bolsistas aprovados
     * 
     * Fluxo:
     * 1. Admin importa lista Excel → salva matrículas aqui
     * 2. Estudante se cadastra → sistema verifica se matrícula está aqui
     * 3. Se está na lista → automaticamente marca usuário como bolsista
     */
    public function up(): void
    {
        Schema::create('bolsistas', function (Blueprint $table) {
            $table->id();
            $table->string('matricula')->unique();
            $table->string('nome')->nullable();
            $table->string('curso')->nullable();
            $table->string('turno')->nullable(); // almoco, jantar
            $table->json('dias_semana')->nullable(); // [1,2,3,4,5]
            $table->boolean('ativo')->default(true); // Se a bolsa está ativa
            $table->unsignedBigInteger('user_id')->nullable(); // Preenchido após cadastro
            $table->timestamp('vinculado_em')->nullable(); // Data do vínculo
            $table->timestamps();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bolsistas');
    }
};
