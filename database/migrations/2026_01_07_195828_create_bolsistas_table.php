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
        Schema::create('bolsistas', function (Blueprint $table) {
            $table->id();
            $table->string('matricula', 20)->unique();
            $table->string('nome', 100);
            $table->string('email', 120)->unique();
            $table->string('telefone', 20)->nullable();
            $table->enum('turno', ['matutino', 'vespertino', 'noturno']);
            $table->string('curso', 100)->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedBigInteger('importado_por')->nullable();
            $table->timestamp('importado_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('importado_por')->references('id')->on('users')->onDelete('set null');

            $table->index('matricula');
            $table->index('turno');
            $table->index('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bolsistas');
    }
};

