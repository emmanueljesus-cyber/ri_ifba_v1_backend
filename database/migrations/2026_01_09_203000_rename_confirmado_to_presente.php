<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Renomeia status 'confirmado' para 'presente'
     */
    public function up(): void
    {
        // 1. Primeiro remove o constraint antigo
        DB::statement('ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check');

        // 2. Atualiza os registros existentes
        DB::table('presencas')
            ->where('status_da_presenca', 'confirmado')
            ->update(['status_da_presenca' => 'presente']);
        
        // 3. Também atualiza 'validado' que pode existir em registros antigos
        DB::table('presencas')
            ->where('status_da_presenca', 'validado')
            ->update(['status_da_presenca' => 'presente']);

        // 4. Cria o novo constraint com os valores corretos
        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('presente', 'falta_justificada', 'falta_injustificada', 'cancelado'))");
    }

    public function down(): void
    {
        // Remove o constraint novo
        DB::statement('ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check');

        // Reverte os registros
        DB::table('presencas')
            ->where('status_da_presenca', 'presente')
            ->update(['status_da_presenca' => 'confirmado']);

        // Recria o constraint antigo
        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('confirmado', 'validado', 'falta_justificada', 'falta_injustificada', 'cancelado'))");
    }
};
