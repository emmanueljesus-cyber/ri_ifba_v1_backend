<?php

namespace App\Services;

use App\Models\User;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\Justificativa;
use App\Enums\StatusPresenca;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioService
{
    /**
     * Relatório de presenças por período
     */
    public function presencasPorPeriodo(string $dataInicio, string $dataFim, ?string $turno = null): array
    {
        $inicio = Carbon::parse($dataInicio);
        $fim = Carbon::parse($dataFim);

        $query = Refeicao::with(['cardapio', 'presencas.user'])
            ->whereBetween('data_do_cardapio', [$inicio, $fim])
            ->orderBy('data_do_cardapio');

        if ($turno) {
            $query->where('turno', $turno);
        }

        $refeicoes = $query->get();

        $dados = [];
        $totais = [
            'presentes' => 0,
            'falta_justificada' => 0,
            'falta_injustificada' => 0,
            'cancelados' => 0,
            'total_registros' => 0,
        ];

        foreach ($refeicoes as $refeicao) {
            $presentes = $refeicao->presencas->where('status_da_presenca', StatusPresenca::PRESENTE)->count();
            $faltaJust = $refeicao->presencas->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)->count();
            $faltaInjust = $refeicao->presencas->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)->count();
            $cancelados = $refeicao->presencas->where('status_da_presenca', StatusPresenca::CANCELADO)->count();

            $dados[] = [
                'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                'turno' => $refeicao->turno->value,
                'presentes' => $presentes,
                'falta_justificada' => $faltaJust,
                'falta_injustificada' => $faltaInjust,
                'cancelados' => $cancelados,
                'total' => $refeicao->presencas->count(),
            ];

            $totais['presentes'] += $presentes;
            $totais['falta_justificada'] += $faltaJust;
            $totais['falta_injustificada'] += $faltaInjust;
            $totais['cancelados'] += $cancelados;
            $totais['total_registros'] += $refeicao->presencas->count();
        }

        return [
            'dados' => $dados,
            'totais' => $totais,
            'periodo' => [
                'inicio' => $inicio->format('d/m/Y'),
                'fim' => $fim->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Resumo mensal consolidado
     */
    public function resumoMensal(int $mes, int $ano): array
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fim = Carbon::create($ano, $mes, 1)->endOfMonth();

        $refeicaoIds = Refeicao::whereBetween('data_do_cardapio', [$inicio, $fim])
            ->pluck('id');

        $presentes = Presenca::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_da_presenca', StatusPresenca::PRESENTE)
            ->count();

        $faltasJust = Presenca::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)
            ->count();

        $faltasInjust = Presenca::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)
            ->count();

        $totalRegistros = Presenca::whereIn('refeicao_id', $refeicaoIds)->count();
        $totalRefeicoes = $refeicaoIds->count();

        $taxaPresenca = $totalRegistros > 0 
            ? round(($presentes / $totalRegistros) * 100, 1) 
            : 0;

        return [
            'mes' => $mes,
            'ano' => $ano,
            'mes_texto' => Carbon::create($ano, $mes, 1)->locale('pt_BR')->monthName,
            'total_refeicoes' => $totalRefeicoes,
            'total_registros' => $totalRegistros,
            'presentes' => $presentes,
            'falta_justificada' => $faltasJust,
            'falta_injustificada' => $faltasInjust,
            'taxa_presenca' => $taxaPresenca . '%',
        ];
    }

    /**
     * Relatório por bolsista
     */
    public function porBolsista(int $userId, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['erro' => 'Usuário não encontrado'];
        }

        $inicio = $dataInicio ? Carbon::parse($dataInicio) : now()->startOfMonth();
        $fim = $dataFim ? Carbon::parse($dataFim) : now()->endOfMonth();

        $presencas = Presenca::with('refeicao')
            ->where('user_id', $userId)
            ->whereHas('refeicao', function ($q) use ($inicio, $fim) {
                $q->whereBetween('data_do_cardapio', [$inicio, $fim]);
            })
            ->get();

        $presentes = $presencas->where('status_da_presenca', StatusPresenca::PRESENTE)->count();
        $faltasJust = $presencas->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)->count();
        $faltasInjust = $presencas->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)->count();

        $historico = $presencas->map(function ($p) {
            return [
                'data' => $p->refeicao->data_do_cardapio->format('d/m/Y'),
                'turno' => $p->refeicao->turno->value,
                'status' => $p->status_da_presenca->value,
                'validado_em' => $p->validado_em?->format('d/m/Y H:i'),
            ];
        })->sortByDesc('data')->values();

        return [
            'bolsista' => [
                'id' => $user->id,
                'nome' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
            ],
            'periodo' => [
                'inicio' => $inicio->format('d/m/Y'),
                'fim' => $fim->format('d/m/Y'),
            ],
            'resumo' => [
                'total' => $presencas->count(),
                'presentes' => $presentes,
                'falta_justificada' => $faltasJust,
                'falta_injustificada' => $faltasInjust,
                'taxa_presenca' => $presencas->count() > 0 
                    ? round(($presentes / $presencas->count()) * 100, 1) . '%' 
                    : '0%',
            ],
            'historico' => $historico,
        ];
    }

    /**
     * Gera dados para exportação
     */
    public function dadosParaExportacao(string $dataInicio, string $dataFim, ?string $turno = null): Collection
    {
        $inicio = Carbon::parse($dataInicio);
        $fim = Carbon::parse($dataFim);

        $query = Presenca::with(['user', 'refeicao'])
            ->whereHas('refeicao', function ($q) use ($inicio, $fim, $turno) {
                $q->whereBetween('data_do_cardapio', [$inicio, $fim]);
                if ($turno) {
                    $q->where('turno', $turno);
                }
            });

        return $query->get()->map(function ($p) {
            return [
                'Data' => $p->refeicao->data_do_cardapio->format('d/m/Y'),
                'Turno' => $p->refeicao->turno->value,
                'Matrícula' => $p->user->matricula,
                'Nome' => $p->user->nome,
                'Curso' => $p->user->curso,
                'Status' => $this->traduzirStatus($p->status_da_presenca),
                'Validado em' => $p->validado_em?->format('d/m/Y H:i') ?? '-',
            ];
        });
    }

    private function traduzirStatus($status): string
    {
        $mapa = [
            'presente' => 'Presente',
            'falta_justificada' => 'Falta Justificada',
            'falta_injustificada' => 'Falta Injustificada',
            'cancelado' => 'Cancelado',
        ];

        $valor = is_object($status) ? $status->value : $status;
        return $mapa[$valor] ?? $valor;
    }
}
