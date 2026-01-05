<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se a coluna 'status_presenca' existe e renomeia para 'status_da_presenca'
        if (Schema::hasColumn('presencas', 'status_presenca')) {
            Schema::table('presencas', function (Blueprint $table) {
                $table->renameColumn('status_presenca', 'status_da_presenca');
            });
        }

        // Verifica se a coluna 'status' existe e renomeia para 'status_da_presenca'
        if (Schema::hasColumn('presencas', 'status')) {
            Schema::table('presencas', function (Blueprint $table) {
                $table->renameColumn('status', 'status_da_presenca');
            });
        }

        // Se a coluna não existir de nenhuma forma, cria ela
        if (!Schema::hasColumn('presencas', 'status_da_presenca')) {
            Schema::table('presencas', function (Blueprint $table) {
                $table->enum('status_da_presenca', [
                    'confirmado',
                    'validado',
                    'falta_justificada',
                    'falta_injustificada'
                ])->after('refeicao_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverte para manter consistência
    }
};

