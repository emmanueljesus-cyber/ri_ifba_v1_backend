<?php

namespace App\Services;

use App\Models\User;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\Justificativa;
use App\Models\FilaExtra;
use App\Enums\StatusPresenca;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obtém resumo geral do sistema
     */
    public function resumoGeral(?string $mes = null, ?int $ano = null): array
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth();

        $totalBolsistas = User::where('bolsista', true)->count();
        $bolsistasAtivos = User::where('bolsista', true)->where('desligado', false)->count();

        $refeicoesDoMes = Refeicao::whereBetween('data_do_cardapio', [$dataInicio, $dataFim])->count();
        $refeicoesHoje = Refeicao::where('data_do_cardapio', now()->toDateString())->count();

        return [
            'total_bolsistas' => $totalBolsistas,
            'bolsistas_ativos' => $bolsistasAtivos,
            'bolsistas_inativos' => $totalBolsistas - $bolsistasAtivos,
            'refeicoes_mes' => $refeicoesDoMes,
            'refeicoes_hoje' => $refeicoesHoje,
            'periodo' => [
                'mes' => $mes,
                'ano' => $ano,
                'mes_texto' => Carbon::create($ano, $mes, 1)->locale('pt_BR')->monthName,
            ],
        ];
    }

    /**
     * Calcula taxa de presença no período
     */
    public function taxaPresenca(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : now()->startOfMonth();
        $fim = $dataFim ? Carbon::parse($dataFim) : now()->endOfMonth();

        // Total de presenças esperadas (bolsistas * dias de refeição)
        $refeicoes = Refeicao::whereBetween('data_do_cardapio', [$inicio, $fim])->get();
        
        $totalPresentes = 0;
        $totalEsperados = 0;
        
        foreach ($refeicoes as $refeicao) {
            $presencasRefeicao = Presenca::where('refeicao_id', $refeicao->id)->count();
            $presentesRefeicao = Presenca::where('refeicao_id', $refeicao->id)
                ->where('status_da_presenca', StatusPresenca::PRESENTE)
                ->count();
            
            $totalEsperados += $presencasRefeicao;
            $totalPresentes += $presentesRefeicao;
        }

        $taxa = $totalEsperados > 0 
            ? round(($totalPresentes / $totalEsperados) * 100, 2) 
            : 0;

        // Comparativo com mês anterior
        $inicioAnterior = $inicio->copy()->subMonth();
        $fimAnterior = $fim->copy()->subMonth();
        
        $refeicoesAnterior = Refeicao::whereBetween('data_do_cardapio', [$inicioAnterior, $fimAnterior])->get();
        $totalPresentesAnterior = 0;
        $totalEsperadosAnterior = 0;
        
        foreach ($refeicoesAnterior as $refeicao) {
            $presencasRefeicao = Presenca::where('refeicao_id', $refeicao->id)->count();
            $presentesRefeicao = Presenca::where('refeicao_id', $refeicao->id)
                ->where('status_da_presenca', StatusPresenca::PRESENTE)
                ->count();
            
            $totalEsperadosAnterior += $presencasRefeicao;
            $totalPresentesAnterior += $presentesRefeicao;
        }

        $taxaAnterior = $totalEsperadosAnterior > 0 
            ? round(($totalPresentesAnterior / $totalEsperadosAnterior) * 100, 2) 
            : 0;

        $variacao = $taxa - $taxaAnterior;

        return [
            'valor' => $taxa,
            'total_presentes' => $totalPresentes,
            'total_registros' => $totalEsperados,
            'comparativo_anterior' => ($variacao >= 0 ? '+' : '') . number_format($variacao, 1) . '%',
            'periodo' => [
                'inicio' => $inicio->format('d/m/Y'),
                'fim' => $fim->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Estatísticas de faltas por tipo
     */
    public function faltasPorTipo(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : now()->startOfMonth();
        $fim = $dataFim ? Carbon::parse($dataFim) : now()->endOfMonth();

        $refeicaoIds = Refeicao::whereBetween('data_do_cardapio', [$inicio, $fim])
            ->pluck('id');

        $faltasJustificadas = Presenca::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)
            ->count();

        $faltasInjustificadas = Presenca::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)
            ->count();

        $totalFaltas = $faltasJustificadas + $faltasInjustificadas;
        $percentualJustificadas = $totalFaltas > 0 
            ? round(($faltasJustificadas / $totalFaltas) * 100, 1) 
            : 0;

        return [
            'justificadas' => $faltasJustificadas,
            'injustificadas' => $faltasInjustificadas,
            'total' => $totalFaltas,
            'percentual_justificadas' => $percentualJustificadas . '%',
            'percentual_injustificadas' => (100 - $percentualJustificadas) . '%',
        ];
    }

    /**
     * Estatísticas de extras atendidos
     */
    public function extrasAtendidos(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $inicio = $dataInicio ? Carbon::parse($dataInicio) : now()->startOfMonth();
        $fim = $dataFim ? Carbon::parse($dataFim) : now()->endOfMonth();

        $refeicaoIds = Refeicao::whereBetween('data_do_cardapio', [$inicio, $fim])
            ->pluck('id');

        $inscritos = FilaExtra::whereIn('refeicao_id', $refeicaoIds)->count();
        $aprovados = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_fila_extras', 'aprovado')
            ->count();
        $rejeitados = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_fila_extras', 'rejeitado')
            ->count();

        $taxaAtendimento = $inscritos > 0 
            ? round(($aprovados / $inscritos) * 100, 1) 
            : 0;

        return [
            'inscritos' => $inscritos,
            'atendidos' => $aprovados,
            'rejeitados' => $rejeitados,
            'taxa_atendimento' => $taxaAtendimento . '%',
        ];
    }

    /**
     * Evolução mensal (últimos 6 meses)
     */
    public function evolucaoMensal(int $meses = 6): array
    {
        $evolucao = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $data = now()->subMonths($i);
            $inicio = $data->copy()->startOfMonth();
            $fim = $data->copy()->endOfMonth();

            $refeicaoIds = Refeicao::whereBetween('data_do_cardapio', [$inicio, $fim])
                ->pluck('id');

            $presentes = Presenca::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_da_presenca', StatusPresenca::PRESENTE)
                ->count();

            $totalRegistros = Presenca::whereIn('refeicao_id', $refeicaoIds)->count();

            $taxa = $totalRegistros > 0 
                ? round(($presentes / $totalRegistros) * 100, 1) 
                : 0;

            $evolucao[] = [
                'mes' => $data->locale('pt_BR')->shortMonthName,
                'ano' => $data->year,
                'presentes' => $presentes,
                'total' => $totalRegistros,
                'taxa_presenca' => $taxa,
            ];
        }

        return $evolucao;
    }

    /**
     * Top bolsistas com mais faltas
     */
    public function topFaltosos(int $limite = 10): array
    {
        $mesAtual = now()->month;
        $anoAtual = now()->year;

        $refeicaoIds = Refeicao::whereMonth('data_do_cardapio', $mesAtual)
            ->whereYear('data_do_cardapio', $anoAtual)
            ->pluck('id');

        $faltosos = Presenca::select('user_id', DB::raw('COUNT(*) as total_faltas'))
            ->whereIn('refeicao_id', $refeicaoIds)
            ->whereIn('status_da_presenca', [
                StatusPresenca::FALTA_JUSTIFICADA,
                StatusPresenca::FALTA_INJUSTIFICADA,
            ])
            ->groupBy('user_id')
            ->orderByDesc('total_faltas')
            ->limit($limite)
            ->get();

        return $faltosos->map(function ($item) {
            $user = User::find($item->user_id);
            return [
                'user_id' => $item->user_id,
                'nome' => $user?->nome ?? 'N/A',
                'matricula' => $user?->matricula ?? 'N/A',
                'total_faltas' => $item->total_faltas,
            ];
        })->toArray();
    }
}
