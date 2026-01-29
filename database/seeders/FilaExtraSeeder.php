<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\FilaExtra;
use App\Enums\StatusFila;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FilaExtraSeeder extends Seeder
{
    /**
     * Cria histórico de inscrições na fila de extras
     * Período: 01/01/2026 até 28/01/2026
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('📋 CRIANDO HISTÓRICO DE FILA DE EXTRAS');
        $this->command->info('======================================');

        // Data limite: hoje (28/01/2026)
        $dataLimite = Carbon::create(2026, 1, 28);

        // Buscar estudantes não-bolsistas ativos
        $estudantesNaoBolsistas = User::where('perfil', 'estudante')
            ->where('bolsista', false)
            ->where('desligado', false)
            ->get();

        $this->command->info("   📋 {$estudantesNaoBolsistas->count()} estudantes não-bolsistas encontrados");

        // Buscar refeições até a data limite, agrupadas por data
        $refeicoes = Refeicao::with('cardapio')
            ->whereHas('cardapio', function ($q) use ($dataLimite) {
                $q->where('data_do_cardapio', '<=', $dataLimite);
            })
            ->get()
            ->groupBy(fn($r) => $r->cardapio->data_do_cardapio->format('Y-m-d'));

        $inscricoesCriadas = 0;
        $aprovadas = 0;
        $rejeitadas = 0;
        $pendentes = 0;

        foreach ($refeicoes as $data => $refeicoesNoDia) {
            $dataCarbon = Carbon::parse($data);

            // Data de hoje (28/01/2026) - inscrições ficam pendentes
            $isHoje = $dataCarbon->isSameDay($dataLimite);

            foreach ($estudantesNaoBolsistas as $estudante) {
                // 50% de chance de se inscrever na fila extra neste dia
                if (rand(1, 100) > 50) {
                    continue;
                }

                // Escolher refeição baseada no turno de aula do estudante
                // Matutino -> almoço, Vespertino/Noturno -> jantar
                $turnoPreferido = match($estudante->turno_aula) {
                    'matutino' => 'almoco',
                    'vespertino', 'noturno' => 'jantar',
                    default => rand(0, 1) ? 'almoco' : 'jantar',
                };

                $refeicao = $refeicoesNoDia->firstWhere('turno', $turnoPreferido)
                          ?? $refeicoesNoDia->first();

                if (!$refeicao) {
                    continue;
                }

                // Verificar se já existe inscrição para esta refeição
                $jaInscrito = FilaExtra::where('user_id', $estudante->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->exists();

                if ($jaInscrito) {
                    continue;
                }

                // Determinar status baseado na data
                if ($isHoje) {
                    // Hoje: todas ficam pendentes (inscrito)
                    $status = StatusFila::INSCRITO;
                    $pendentes++;
                } else {
                    // Dias anteriores: distribuição realista
                    // 60% aprovado, 25% inscrito (aguardando), 15% rejeitado
                    $rand = rand(1, 100);
                    if ($rand <= 60) {
                        $status = StatusFila::APROVADO;
                        $aprovadas++;
                    } elseif ($rand <= 85) {
                        $status = StatusFila::INSCRITO;
                        $pendentes++;
                    } else {
                        $status = StatusFila::REJEITADO;
                        $rejeitadas++;
                    }
                }

                // Hora de inscrição: entre 7h e 10h do dia
                $horaInscricao = rand(7, 10);
                $minutoInscricao = rand(0, 59);
                $inscritoEm = $dataCarbon->copy()->setTime($horaInscricao, $minutoInscricao);

                FilaExtra::create([
                    'user_id' => $estudante->id,
                    'refeicao_id' => $refeicao->id,
                    'status_fila_extras' => $status,
                    'inscrito_em' => $inscritoEm,
                ]);

                $inscricoesCriadas++;
            }
        }

        $this->command->info('');
        $this->command->info('✅ Histórico de fila extra criado com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - Total de inscrições: {$inscricoesCriadas}");
        $this->command->info("   - ✅ Aprovadas: {$aprovadas}");
        $this->command->info("   - ⏳ Pendentes: {$pendentes}");
        $this->command->info("   - ❌ Rejeitadas: {$rejeitadas}");
        $this->command->info("   - Período: 01/01/2026 até 28/01/2026");
    }
}
