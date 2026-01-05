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
        // Para PostgreSQL: Alterar o tipo enum para incluir 'cancelado'
        DB::statement("ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check");

        DB::statement("ALTER TABLE presencas ALTER COLUMN status_da_presenca TYPE VARCHAR(50)");

        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('confirmado', 'validado', 'falta_justificada', 'falta_injustificada', 'cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE presencas DROP CONSTRAINT IF EXISTS presencas_status_da_presenca_check");

        DB::statement("ALTER TABLE presencas ADD CONSTRAINT presencas_status_da_presenca_check CHECK (status_da_presenca IN ('confirmado', 'validado', 'falta_justificada', 'falta_injustificada'))");
    }
};
