<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('justificativas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('refeicao_id')->nullable();
            $table->foreign('refeicao_id')->references('id')->on('refeicoes')->nullOnDelete();

            $table->enum('tipo', ['antecipada', 'posterior']);

            $table->text('motivo');
            $table->string('anexo', 255)->nullable();
            $table->timestamp('enviado_em')->useCurrent();

            $table->timestamps();

            $table->index('user_id');
            $table->index('refeicao_id');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('justificativas');
    }
};
