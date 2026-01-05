<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cardapios', function (Blueprint $table) {
            $table->id();
            $table->date('data_do_cardapio')->unique();

            $table->string('prato_principal_ptn01', 255);
            $table->string('prato_principal_ptn02', 255);

            $table->string('guarnicao', 255)->nullable();

            $table->string('acompanhamento_01', 255);
            $table->string('acompanhamento_02', 255);

            $table->string('salada', 255)->nullable();

            $table->string('ovo_lacto_vegetariano', 255)->nullable();

            $table->string('suco', 100)->nullable();
            $table->string('sobremesa', 100)->nullable();

            $table->unsignedBigInteger('criado_por')->nullable();
            $table->foreign('criado_por')->references('id')->on('users')->nullOnDelete();

            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

            $table->index('data_do_cardapio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cardapios');
    }
};
