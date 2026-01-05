<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: drop old CHECK constraint and recreate with new values
        DB::statement("ALTER TABLE refeicoes DROP CONSTRAINT IF EXISTS refeicoes_turno_check");

        // mapear valores antigos para os novos
        // 'manha'|'tarde' -> 'almoco', 'noite' -> 'jantar'
        DB::statement("UPDATE refeicoes SET turno = 'almoco' WHERE turno IN ('manha','tarde')");
        DB::statement("UPDATE refeicoes SET turno = 'jantar' WHERE turno = 'noite'");

        // criar novo CHECK constraint com valores permitidos
        DB::statement("ALTER TABLE refeicoes ADD CONSTRAINT refeicoes_turno_check CHECK (turno IN ('almoco','jantar'))");
    }

    public function down(): void
    {
        // reverte constraint para valores anteriores
        DB::statement("ALTER TABLE refeicoes DROP CONSTRAINT IF EXISTS refeicoes_turno_check");
        DB::statement("UPDATE refeicoes SET turno = 'manha' WHERE turno = 'almoco'");
        DB::statement("UPDATE refeicoes SET turno = 'noite' WHERE turno = 'jantar'");
        DB::statement("ALTER TABLE refeicoes ADD CONSTRAINT refeicoes_turno_check CHECK (turno IN ('manha','tarde','noite'))");
    }
};
