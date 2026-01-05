<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\FilaExtra;
use Illuminate\Database\Seeder;

class FilaExtraSeeder extends Seeder
{
    public function run(): void
    {
        $estudantesNaoBolsistas = User::where('perfil', 'estudante')
            ->where('bolsista', false)
            ->where('desligado', false)
            ->get();

        $refeicoes = Refeicao::all();

        foreach ($refeicoes as $refeicao) {
            // 50% dos não-bolsistas se inscrevem na fila extra
            foreach ($estudantesNaoBolsistas as $estudante) {
                if (rand(1, 100) <= 50) {
                    $status = ['inscrito', 'aprovado', 'rejeitado'][rand(0, 2)];

                    FilaExtra::create([
                        'user_id' => $estudante->id,
                        'refeicao_id' => $refeicao->id,
                        'status_fila_extras' => $status,
                        'inscrito_em' => now()->subHours(rand(24, 72)),
                    ]);
                }
            }
        }
    }
}
