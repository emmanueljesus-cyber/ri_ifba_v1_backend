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
        Schema::table('cardapios', function (Blueprint $table) {
            // JSON para armazenar ['almoco', 'jantar'] ou ['almoco'] ou ['jantar']
            // Padrão: ambos os turnos
            $table->json('turnos')->default('["almoco", "jantar"]')->after('data_do_cardapio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cardapios', function (Blueprint $table) {
            $table->dropColumn('turnos');
        });
    }
};

