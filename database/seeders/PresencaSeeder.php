<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Models\UsuarioDiaSemana;
use App\Enums\StatusPresenca;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PresencaSeeder extends Seeder
{
    /**
     * Cria histórico de presenças e faltas para bolsistas
     * Período: 01/01/2026 até 27/01/2026 (dia anterior ao atual)
     * O dia 28/01/2026 (hoje) fica sem presenças para permitir testes
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('📊 CRIANDO HISTÓRICO DE PRESENÇAS E FALTAS');
        $this->command->info('==========================================');

        // Data limite: ontem (27/01/2026) - hoje fica pendente para testes
        $dataLimite = Carbon::create(2026, 1, 27);
        $dataHoje = Carbon::create(2026, 1, 28);

        // Buscar admin para validações
        $admin = User::where('perfil', 'admin')->first();

        // Buscar bolsistas ativos
        $bolsistas = User::where('perfil', 'estudante')
            ->where('bolsista', true)
            ->where('desligado', false)
            ->get();

        $this->command->info("   📋 Processando {$bolsistas->count()} bolsistas...");

        $presencasCriadas = 0;
        $faltasInjustificadas = 0;
        $faltasJustificadas = 0;

        foreach ($bolsistas as $bolsista) {
            // Buscar os dias da semana do bolsista
            $diasSemana = UsuarioDiaSemana::where('user_id', $bolsista->id)
                ->pluck('dia_semana')
                ->toArray();

            if (empty($diasSemana)) {
                continue;
            }

            // Buscar refeições do turno do bolsista até a data limite (EXCLUINDO HOJE)
            $turno = $bolsista->turno_refeicao ?? 'almoco';

            $refeicoes = Refeicao::with('cardapio')
                ->where('turno', $turno)
                ->whereHas('cardapio', function ($q) use ($dataLimite) {
                    $q->where('data_do_cardapio', '<=', $dataLimite);
                })
                ->get();

            foreach ($refeicoes as $refeicao) {
                $dataRefeicao = Carbon::parse($refeicao->cardapio->data_do_cardapio);
                $diaSemana = $dataRefeicao->dayOfWeekIso; // 1=segunda, 5=sexta

                // Verificar se o bolsista deve comparecer neste dia
                if (!in_array($diaSemana, $diasSemana)) {
                    continue;
                }

                // Verificar se já existe presença para esta refeição
                $existente = Presenca::where('user_id', $bolsista->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->exists();

                if ($existente) {
                    continue;
                }

                // Distribuição realista de status:
                // - 75% presente
                // - 10% falta justificada
                // - 10% falta injustificada
                // - 5% cancelado (não teve refeição)
                $rand = rand(1, 100);

                if ($rand <= 75) {
                    // Presente - validado pelo admin entre 11h-13h ou 18h-20h
                    $horaValidacao = $turno === 'almoco' ? rand(11, 13) : rand(18, 20);
                    $minutoValidacao = rand(0, 59);

                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicao->id,
                        'status_da_presenca' => StatusPresenca::PRESENTE,
                        'validado_em' => $dataRefeicao->copy()->setTime($horaValidacao, $minutoValidacao),
                        'validado_por' => $admin?->id,
                        'registrado_em' => $dataRefeicao->copy()->setTime($horaValidacao, $minutoValidacao),
                    ]);
                    $presencasCriadas++;
                } elseif ($rand <= 85) {
                    // Falta justificada
                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicao->id,
                        'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
                        'registrado_em' => $dataRefeicao->copy()->setTime(23, 59),
                    ]);
                    $faltasJustificadas++;
                } elseif ($rand <= 95) {
                    // Falta injustificada
                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicao->id,
                        'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
                        'registrado_em' => $dataRefeicao->copy()->setTime(23, 59),
                    ]);
                    $faltasInjustificadas++;
                } else {
                    // Cancelado (não houve refeição neste dia)
                    Presenca::create([
                        'user_id' => $bolsista->id,
                        'refeicao_id' => $refeicao->id,
                        'status_da_presenca' => StatusPresenca::CANCELADO,
                        'registrado_em' => $dataRefeicao->copy()->setTime(8, 0),
                    ]);
                }
            }
        }

        $total = $presencasCriadas + $faltasJustificadas + $faltasInjustificadas;

        $this->command->info('');
        $this->command->info('✅ Histórico de presenças criado com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - Total de registros: {$total}");
        $this->command->info("   - ✅ Presenças: {$presencasCriadas}");
        $this->command->info("   - 📋 Faltas justificadas: {$faltasJustificadas}");
        $this->command->info("   - ❌ Faltas injustificadas: {$faltasInjustificadas}");
        $this->command->info("   - Período: 01/01/2026 até 27/01/2026");
        $this->command->info("   - ⏳ Dia 28/01/2026 (hoje) ficou PENDENTE para testes");
    }
}
