<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presencas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('refeicao_id');
            $table->foreign('refeicao_id')->references('id')->on('refeicoes')->cascadeOnDelete();

            $table->enum('status_da_presenca', [
                'confirmado',
                'validado',
                'falta_justificada',
                'falta_injustificada'
            ]);

            $table->timestamp('validado_em')->nullable();

            $table->unsignedBigInteger('validado_por')->nullable();
            $table->foreign('validado_por')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('registrado_em')->useCurrent();

            $table->timestamps();

            $table->unique(['user_id', 'refeicao_id']);
            $table->index('user_id');
            $table->index('refeicao_id');
            $table->index('validado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presencas');
    }
};
