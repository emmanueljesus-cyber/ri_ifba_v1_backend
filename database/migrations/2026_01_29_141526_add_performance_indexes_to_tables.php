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
        // Índices para otimizar queries do dashboard
        Schema::table('presencas', function (Blueprint $table) {
            // Otimiza queries de presença por data e status
            $table->index(['data_presenca', 'status_da_presenca'], 'idx_data_status');
            // Otimiza queries de presença por usuário e data
            $table->index(['user_id', 'data_presenca'], 'idx_user_data');
        });

        Schema::table('users', function (Blueprint $table) {
            // Otimiza queries de bolsistas ativos
            $table->index(['bolsista', 'ativo'], 'idx_bolsista_ativo');
        });

        Schema::table('justificativas', function (Blueprint $table) {
            // Otimiza queries de justificativas pendentes
            $table->index(['status_justificativa', 'created_at'], 'idx_status_created');
        });

        Schema::table('inscricoes_extras', function (Blueprint $table) {
            // Otimiza queries de inscrições extras por data e status
            $table->index(['data_inscricao', 'status_inscricao'], 'idx_data_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presencas', function (Blueprint $table) {
            $table->dropIndex('idx_data_status');
            $table->dropIndex('idx_user_data');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_bolsista_ativo');
        });

        Schema::table('justificativas', function (Blueprint $table) {
            $table->dropIndex('idx_status_created');
        });

        Schema::table('inscricoes_extras', function (Blueprint $table) {
            $table->dropIndex('idx_data_status');
        });
    }
};
