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
        Schema::table('users', function (Blueprint $table) {
            $table->text('alergias')->nullable()->after('restricoes_alimentares');
            $table->boolean('is_ovolactovegetariano')->default(false)->after('alergias');
        });

        Schema::create('solicitacoes_mudanca_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('dias_semana');
            $table->enum('status', ['pendente', 'aprovada', 'rejeitada'])->default('pendente');
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_mudanca_dias');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['alergias', 'is_ovolactovegetariano']);
        });
    }
};
