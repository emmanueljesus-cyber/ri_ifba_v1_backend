<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Performance indexes for dashboard and reports
     */
    public function up(): void
    {
        Schema::table('presencas', function (Blueprint $table) {
            // Índices para queries de dashboard e relatórios
            // Nota: user_id e refeicao_id já têm índices simples na migration original
            // Adicionamos apenas índices compostos para queries específicas
            $table->index(['status_da_presenca'], 'idx_presenca_status');
            $table->index(['user_id', 'status_da_presenca'], 'idx_presenca_user_status');
        });

        Schema::table('users', function (Blueprint $table) {
            // Índices para filtros comuns de bolsistas
            $table->index(['bolsista', 'desligado'], 'idx_users_bolsista_desligado');
            $table->index(['perfil'], 'idx_users_perfil');
        });

        Schema::table('justificativas', function (Blueprint $table) {
            // Índices para queries de justificativas pendentes
            // Coluna correta: 'status' (adicionada em 2026_01_09_120000_add_status_to_justificativas_table)
            $table->index(['status'], 'idx_justificativa_status');
            $table->index(['user_id', 'created_at'], 'idx_justificativa_user_created');
        });

        // Não adicionamos índices em inscricoes_extras porque a tabela pode não existir
        // ou ter estrutura diferente. Verificar schema primeiro se necessário.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presencas', function (Blueprint $table) {
            $table->dropIndex('idx_presenca_status');
            $table->dropIndex('idx_presenca_user_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_bolsista_desligado');
            $table->dropIndex('idx_users_perfil');
        });

        Schema::table('justificativas', function (Blueprint $table) {
            $table->dropIndex('idx_justificativa_status');
            $table->dropIndex('idx_justificativa_user_created');
        });
    }
};
