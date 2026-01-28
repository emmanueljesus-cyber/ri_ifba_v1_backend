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

        // Buscar refeições agrupadas por data (para garantir 1 por dia por estudante)
        $refeicoesPorData = Refeicao::with('cardapio')
            ->get()
            ->groupBy(fn($r) => $r->cardapio->data_do_cardapio);

        $inscricoesCriadas = 0;

        foreach ($refeicoesPorData as $data => $refeicoesNoDia) {
            foreach ($estudantesNaoBolsistas as $estudante) {
                // 40% de chance de se inscrever na fila extra neste dia
                if (rand(1, 100) > 40) {
                    continue;
                }

                // Escolhe a refeição do turno do estudante (ou aleatória se não tiver turno)
                $turnoEstudante = $estudante->turno ?? 'almoco';
                $refeicao = $refeicoesNoDia->firstWhere('turno', $turnoEstudante)
                          ?? $refeicoesNoDia->first();

                if (!$refeicao) {
                    continue;
                }

                // Verificar se já existe inscrição para este dia
                $jaInscrito = FilaExtra::where('user_id', $estudante->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->exists();

                if ($jaInscrito) {
                    continue;
                }

                // Status com peso: 60% aprovado, 25% inscrito (aguardando), 15% rejeitado
                $rand = rand(1, 100);
                if ($rand <= 60) {
                    $status = 'aprovado';
                } elseif ($rand <= 85) {
                    $status = 'inscrito';
                } else {
                    $status = 'rejeitado';
                }

                // Data de inscrição baseada na data da refeição (manhã do mesmo dia ou dia anterior)
                $dataRefeicao = $refeicao->cardapio->data_do_cardapio;
                $horaInscricao = rand(7, 10); // Entre 7h e 10h
                $minutoInscricao = rand(0, 59);
                $inscritoEm = $dataRefeicao->copy()->setTime($horaInscricao, $minutoInscricao);

                FilaExtra::create([
                    'user_id' => $estudante->id,
                    'refeicao_id' => $refeicao->id,
                    'status_fila_extras' => $status,
                    'inscrito_em' => $inscritoEm,
                ]);

                $inscricoesCriadas++;
            }
        }

        $this->command->info("✅ {$inscricoesCriadas} inscrições na fila extra criadas");

        // Estatísticas
        $stats = FilaExtra::selectRaw('status_fila_extras, COUNT(*) as total')
            ->groupBy('status_fila_extras')
            ->get();

        $this->command->info("📊 Distribuição de status:");
        foreach ($stats as $stat) {
            $statusLabel = $stat->status_fila_extras instanceof \BackedEnum 
                ? $stat->status_fila_extras->value 
                : $stat->status_fila_extras;
            $this->command->info("   - {$statusLabel}: {$stat->total}");
        }
    }
}
