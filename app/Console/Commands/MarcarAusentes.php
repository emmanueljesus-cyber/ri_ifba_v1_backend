<?php

namespace App\Console\Commands;

use App\Enums\StatusPresenca;
use App\Enums\StatusJustificativa;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Models\Justificativa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para marcar automaticamente bolsistas ausentes
 *
 * Executa após o horário limite de cada refeição:
 * - Almoço: após 13:30
 * - Jantar: após 19:00
 *
 * Bolsistas que não tiveram presença confirmada e não têm justificativa
 * são marcados como FALTA_INJUSTIFICADA (ausente).
 */
class MarcarAusentes extends Command
{
    protected $signature = 'presencas:marcar-ausentes 
                            {--data= : Data específica (YYYY-MM-DD), padrão hoje}
                            {--turno= : Turno específico (almoco/jantar), padrão ambos}
                            {--force : Forçar execução mesmo fora do horário}';

    protected $description = 'Marca automaticamente como ausentes os bolsistas que não compareceram';

    public function handle(): int
    {
        $data = $this->option('data')
            ? Carbon::parse($this->option('data'))
            : Carbon::today();

        $turnoEspecifico = $this->option('turno');
        $force = $this->option('force');

        $this->info("📅 Verificando ausências para: {$data->format('d/m/Y')}");

        // Horários limite (do arquivo de configuração)
        $horarios = [
            'almoco' => config('restaurante.refeicoes.almoco.fim', '13:30'),
            'jantar' => config('restaurante.refeicoes.jantar.fim', '19:00'),
        ];

        $turnos = $turnoEspecifico ? [$turnoEspecifico] : ['almoco', 'jantar'];
        $totalMarcados = 0;

        foreach ($turnos as $turno) {
            $horaLimite = Carbon::parse($data->format('Y-m-d') . ' ' . $horarios[$turno]);

            // Só executa se já passou do horário limite (ou --force)
            if (!$force && Carbon::now()->lt($horaLimite)) {
                $this->warn("⏰ Turno {$turno}: ainda não passou do horário limite ({$horarios[$turno]})");
                continue;
            }

            $marcados = $this->marcarAusentesDoTurno($data, $turno);
            $totalMarcados += $marcados;

            $this->info("✅ Turno {$turno}: {$marcados} bolsista(s) marcado(s) como ausente(s)");
        }

        $this->newLine();
        $this->info("📊 Total de ausências registradas: {$totalMarcados}");

        Log::info('MarcarAusentes executado', [
            'data' => $data->format('Y-m-d'),
            'total_marcados' => $totalMarcados,
        ]);

        return self::SUCCESS;
    }

    private function marcarAusentesDoTurno(Carbon $data, string $turno): int
    {
        // Buscar a refeição do dia/turno
        $refeicao = Refeicao::where('data_do_cardapio', $data->format('Y-m-d'))
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            $this->warn("   ⚠️ Nenhuma refeição encontrada para {$turno}");
            return 0;
        }

        // Dia da semana (0=domingo, 1=segunda, ...)
        $diaSemana = $data->dayOfWeek;

        // Buscar bolsistas que deveriam estar presentes neste dia/turno
        $bolsistas = User::where('bolsista', true)
            ->where('desligado', false)
            ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaSemana))
            ->whereHas('aprovado', fn($q) => $q->where('turno_refeicao', $turno))
            ->get();

        $marcados = 0;

        foreach ($bolsistas as $bolsista) {
            // Verificar se já tem presença registrada
            $presencaExistente = Presenca::where('user_id', $bolsista->id)
                ->where('refeicao_id', $refeicao->id)
                ->first();

            // Se já tem presença (qualquer status), pula
            if ($presencaExistente) {
                continue;
            }

            // Verificar se tem justificativa antecipada APROVADA para esta refeição
            $temJustificativaAntecipada = Justificativa::where('user_id', $bolsista->id)
                ->where('refeicao_id', $refeicao->id)
                ->where('tipo', 'antecipada')
                ->where('status', StatusJustificativa::APROVADA)
                ->exists();

            // Se tem justificativa antecipada aprovada, pula (não marca como ausente)
            if ($temJustificativaAntecipada) {
                if ($this->getOutput()->isVerbose()) {
                    $this->line("   ✓ {$bolsista->nome} - justificativa antecipada");
                }
                continue;
            }

            // Criar presença como FALTA_INJUSTIFICADA (ausente)
            Presenca::create([
                'user_id' => $bolsista->id,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
                'registrado_em' => Carbon::now(),
                'validado_em' => Carbon::now(),
                'validado_por' => null, // Sistema automático
            ]);

            $marcados++;

            if ($this->getOutput()->isVerbose()) {
                $this->line("   ✗ {$bolsista->nome} ({$bolsista->matricula}) - AUSENTE");
            }
        }

        return $marcados;
    }
}
