<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refeicoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cardapio_id');
            $table->foreign('cardapio_id')->references('id')->on('cardapios')->cascadeOnDelete();

            $table->date('data_do_cardapio');
            $table->enum('turno', ['almoco', 'jantar']);

            $table->integer('capacidade')->nullable();

            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

            $table->index('data_do_cardapio');
            $table->index('turno');
            $table->unique(['cardapio_id', 'turno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refeicoes');
    }
};
