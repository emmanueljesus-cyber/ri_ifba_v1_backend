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
        Schema::table('bolsistas', function (Blueprint $table) {
            $table->boolean('desligado')->default(false)->after('ativo');
            $table->timestamp('desligado_em')->nullable()->after('desligado');
            $table->text('desligado_motivo')->nullable()->after('desligado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bolsistas', function (Blueprint $table) {
            $table->dropColumn(['desligado', 'desligado_em', 'desligado_motivo']);
        });
    }
};
