<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Models\Bolsista;
use App\Enums\StatusPresenca;
use App\Enums\TurnoRefeicao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PresencaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Busca apenas BOLSISTAS ativos
        $bolsistas = User::where('perfil', 'estudante')
            ->where('bolsista', true)
            ->where('desligado', false)
            ->get();

        $this->command->info("📋 Gerando presenças para {$bolsistas->count()} bolsistas...");

        // Status possíveis com pesos realistas
        // Bolsistas geralmente comparecem (70%), mas podem faltar
        $statusPesos = [
            'presente' => 70,
            'falta_injustificada' => 15,
            'falta_justificada' => 10,
            'cancelado' => 5,
        ];

        $presencasCriadas = 0;
        $presencasIgnoradas = 0;

        foreach ($bolsistas as $user) {
            // Buscar dados do bolsista na tabela bolsistas
            $bolsistaData = Bolsista::where('matricula', $user->matricula)->first();

            // Determinar o turno do bolsista (almoco ou jantar)
            $turnoBolsista = $bolsistaData?->turno_refeicao ?? $user->turno_refeicao ?? 'almoco';

            // Buscar os dias da semana que o bolsista tem direito
            $diasSemana = DB::table('usuario_dias_semana')
                ->where('user_id', $user->id)
                ->pluck('dia_semana')
                ->toArray();

            if (empty($diasSemana)) {
                // Se não tem dias cadastrados, assume seg-sex
                $diasSemana = [1, 2, 3, 4, 5];
            }

            // Buscar APENAS refeições do turno do bolsista e nos dias que ele tem direito
            $refeicoes = Refeicao::with('cardapio')
                ->where('turno', $turnoBolsista)
                ->whereHas('cardapio', function ($query) use ($diasSemana) {
                    $query->whereRaw('EXTRACT(ISODOW FROM data_do_cardapio) IN (' . implode(',', $diasSemana) . ')');
                })
                ->get();

            foreach ($refeicoes as $refeicao) {
                // Verificar se já existe presença para este usuário nesta refeição
                $existe = Presenca::where('user_id', $user->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->exists();

                if ($existe) {
                    $presencasIgnoradas++;
                    continue;
                }

                // Verificar se já existe presença para este usuário neste DIA (garantia extra)
                $dataCardapio = $refeicao->cardapio->data_do_cardapio;
                $jaTemPresencaNoDia = Presenca::where('user_id', $user->id)
                    ->whereHas('refeicao.cardapio', function ($query) use ($dataCardapio) {
                        $query->where('data_do_cardapio', $dataCardapio);
                    })
                    ->exists();

                if ($jaTemPresencaNoDia) {
                    $presencasIgnoradas++;
                    continue;
                }

                // Sortear status com base nos pesos
                $statusSorteado = $this->sortearStatus($statusPesos);

                // Mapear para os valores do Enum suportados pelo banco
                $statusFinal = match($statusSorteado) {
                    'presente' => StatusPresenca::PRESENTE,
                    'falta_justificada' => StatusPresenca::FALTA_JUSTIFICADA,
                    'cancelado' => StatusPresenca::CANCELADO,
                    default => StatusPresenca::FALTA_INJUSTIFICADA,
                };

                $horaBase = $refeicao->turno === TurnoRefeicao::ALMOCO ? 11 : 18;
                $dataRefeicao = $refeicao->cardapio->data_do_cardapio;

                $validadoEm = null;
                if ($statusFinal === StatusPresenca::PRESENTE) {
                    $validadoEm = Carbon::instance($dataRefeicao)->copy()
                        ->setTime($horaBase, 0)
                        ->addMinutes(rand(5, 45));
                }

                Presenca::create([
                    'user_id' => $user->id,
                    'refeicao_id' => $refeicao->id,
                    'status_da_presenca' => $statusFinal,
                    'validado_em' => $validadoEm,
                    'registrado_em' => Carbon::instance($dataRefeicao)->copy()
                        ->setTime($horaBase - 1, rand(0, 59)),
                ]);

                $presencasCriadas++;
            }
        }

        $this->command->info("✅ {$presencasCriadas} presenças criadas");
        if ($presencasIgnoradas > 0) {
            $this->command->info("⏭️  {$presencasIgnoradas} presenças ignoradas (já existiam ou duplicadas)");
        }

        // Estatísticas
        $stats = Presenca::selectRaw('status_da_presenca, COUNT(*) as total')
            ->groupBy('status_da_presenca')
            ->get();

        $this->command->info("📊 Distribuição de status:");
        foreach ($stats as $stat) {
            $statusLabel = $stat->status_da_presenca instanceof \BackedEnum 
                ? $stat->status_da_presenca->value 
                : $stat->status_da_presenca;
            $this->command->info("   - {$statusLabel}: {$stat->total}");
        }
    }

    /**
     * Sorteia um status baseado nos pesos definidos
     */
    private function sortearStatus(array $pesos): string
    {
        $total = array_sum($pesos);
        $random = rand(1, $total);
        $acumulado = 0;

        foreach ($pesos as $status => $peso) {
            $acumulado += $peso;
            if ($random <= $acumulado) {
                return $status;
            }
        }

        return 'presente';
    }
}
