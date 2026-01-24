<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajustes na tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('turno', 'turno_refeicao');
            $table->string('turno_aula', 20)->nullable();
        });

        // Ajustes na tabela bolsistas
        Schema::table('bolsistas', function (Blueprint $table) {
            $table->renameColumn('turno', 'turno_refeicao');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('turno_refeicao', 'turno');
            $table->dropColumn('turno_aula');
        });

        Schema::table('bolsistas', function (Blueprint $table) {
            $table->renameColumn('turno_refeicao', 'turno');
        });
    }
};
