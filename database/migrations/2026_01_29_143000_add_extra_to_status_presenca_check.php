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
        // Remove constraint antiga
        DB::statement("ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check");

        // Adiciona nova constraint com todos os status possíveis, incluindo 'extra'
        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('confirmado', 'validado', 'presente', 'falta_justificada', 'falta_injustificada', 'cancelado', 'ausente', 'extra'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check");

        // Restaura sem 'extra' e 'ausente' (conforme anterior, para rollback)
        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('confirmado', 'validado', 'falta_justificada', 'falta_injustificada', 'cancelado'))");
    }
};
