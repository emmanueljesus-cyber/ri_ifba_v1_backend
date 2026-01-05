<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_dias_semana', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->tinyInteger('dia_semana')->unsigned()->comment('0=Domingo, 1=Segunda, ..., 6=Sábado');

            $table->primary(['user_id', 'dia_semana']);

            $table->index('user_id');
            $table->index('dia_semana');
        });

        // Adicionar constraint check via raw SQL
        DB::statement('ALTER TABLE usuario_dias_semana ADD CONSTRAINT check_dia_semana CHECK (dia_semana >= 0 AND dia_semana <= 6)');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_dias_semana');
    }
};
