<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('matricula', 20)->unique();
            $table->string('nome', 100);
            $table->string('email', 120)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->enum('perfil', ['estudante', 'admin']);

            $table->boolean('bolsista')->default(false);
            $table->integer('limite_faltas_mes')->default(3);

            $table->boolean('desligado')->default(false);
            $table->timestamp('desligado_em')->nullable();
            $table->text('desligado_motivo')->nullable();

            $table->string('curso', 100)->nullable();
            $table->string('turno', 20)->nullable();

            $table->timestamps();

            $table->index('perfil');
            $table->index('bolsista');
            $table->index('desligado');
            $table->index('matricula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
