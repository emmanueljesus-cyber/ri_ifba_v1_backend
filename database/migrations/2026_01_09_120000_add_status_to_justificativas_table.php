<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('justificativas', function (Blueprint $table) {
            $table->enum('status', ['pendente', 'aprovada', 'rejeitada'])->default('pendente')->after('anexo');
            
            $table->unsignedBigInteger('avaliado_por')->nullable()->after('status');
            $table->foreign('avaliado_por')->references('id')->on('users')->nullOnDelete();
            
            $table->timestamp('avaliado_em')->nullable()->after('avaliado_por');
            $table->text('motivo_rejeicao')->nullable()->after('avaliado_em');
        });
    }

    public function down(): void
    {
        Schema::table('justificativas', function (Blueprint $table) {
            $table->dropForeign(['avaliado_por']);
            $table->dropColumn(['status', 'avaliado_por', 'avaliado_em', 'motivo_rejeicao']);
        });
    }
};
