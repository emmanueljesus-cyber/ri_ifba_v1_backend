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
        Schema::table('presencas', function (Blueprint $table) {
            // Tornar user_id opcional para permitir bolsistas não cadastrados
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Adicionar vínculo direto com a tabela de bolsistas (lista master)
            $table->unsignedBigInteger('bolsista_id')->after('user_id')->nullable();
            $table->foreign('bolsista_id')->references('id')->on('bolsistas')->nullOnDelete();
            
            // Remover unique antigo e criar novo que considere ambos os casos
            $table->dropUnique(['user_id', 'refeicao_id']);
            
            // Index para performance
            $table->index('bolsista_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presencas', function (Blueprint $table) {
            $table->dropForeign(['bolsista_id']);
            $table->dropColumn('bolsista_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'refeicao_id']);
        });
    }
};
