<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioDiaSemanaSeeder extends Seeder
{
    public function run(): void
    {
        // Busca apenas BOLSISTAS ativos
        $bolsistas = User::where('perfil', 'estudante')
            ->where('bolsista', true)
            ->where('desligado', false)
            ->get();

        $this->command->info("📋 Vinculando {$bolsistas->count()} bolsistas ativos aos dias da semana...");

        foreach ($bolsistas as $bolsista) {
            // Segunda a Sexta (1, 2, 3, 4, 5)
            for ($dia = 1; $dia <= 5; $dia++) {
                DB::table('usuario_dias_semana')->insertOrIgnore([
                    'user_id' => $bolsista->id,
                    'dia_semana' => $dia,
                ]);
            }
        }

        $total = DB::table('usuario_dias_semana')->count();
        $this->command->info("✅ Total de vínculos criados: {$total}");
    }
}
