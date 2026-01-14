<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bolsista;
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

        $this->command->info("📋 Vinculando {$bolsistas->count()} bolsistas aos dias da semana...");

        $vinculos = 0;

        foreach ($bolsistas as $bolsista) {
            // Busca os dias_semana específicos na tabela bolsistas
            $bolsistaAprovado = Bolsista::where('matricula', $bolsista->matricula)->first();

            if ($bolsistaAprovado && $bolsistaAprovado->dias_semana) {
                // Usa os dias específicos definidos na importação
                $diasSemana = is_array($bolsistaAprovado->dias_semana)
                    ? $bolsistaAprovado->dias_semana
                    : json_decode($bolsistaAprovado->dias_semana, true);

                foreach ($diasSemana as $dia) {
                    DB::table('usuario_dias_semana')->insertOrIgnore([
                        'user_id' => $bolsista->id,
                        'dia_semana' => $dia,
                    ]);
                    $vinculos++;
                }
            } else {
                // Fallback: se não encontrar, vincula de segunda a sexta
                $this->command->warn("   ⚠️  Bolsista {$bolsista->matricula} sem dias específicos, usando seg-sex");
                for ($dia = 1; $dia <= 5; $dia++) {
                    DB::table('usuario_dias_semana')->insertOrIgnore([
                        'user_id' => $bolsista->id,
                        'dia_semana' => $dia,
                    ]);
                    $vinculos++;
                }
            }
        }

        $this->command->info("✅ Total de vínculos criados: {$vinculos}");
        $this->command->info("📊 Média de dias por bolsista: " . round($vinculos / max($bolsistas->count(), 1), 1));
    }
}
