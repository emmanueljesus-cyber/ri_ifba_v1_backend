<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filas_extras', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('refeicao_id');
            $table->foreign('refeicao_id')->references('id')->on('refeicoes')->cascadeOnDelete();

            $table->enum('status_fila_extras', ['inscrito', 'aprovado', 'rejeitado'])->default('inscrito');

            $table->timestamp('inscrito_em')->useCurrent();

            $table->timestamps();

            $table->unique(['user_id', 'refeicao_id']);
            $table->index('user_id');
            $table->index('refeicao_id');
            $table->index('status_fila_extras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filas_extras');
    }
};
