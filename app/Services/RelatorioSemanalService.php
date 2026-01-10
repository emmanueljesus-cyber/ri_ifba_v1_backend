<?php

namespace App\Services;

use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\FilaExtra;
use App\Enums\StatusPresenca;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioSemanalService
{
    /**
     * Gera relatório mensal com dados organizados por semana
     * Formato: Linhas = Categorias, Colunas = Semanas
     */
    public function gerarRelatorioMensal(int $mes, int $ano): array
    {
        $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth();

        // Identificar semanas do mês
        $semanas = $this->identificarSemanas($dataInicio, $dataFim);
        
        // Inicializar dados
        $dados = [
            'presente' => [],
            'extra' => [],
            'ausente' => [],
            'atestado' => [],
            'justificado' => [],
            'n_frequenta' => [],
        ];

        foreach ($semanas as $i => $semana) {
            $refeicaoIds = Refeicao::whereBetween('data_do_cardapio', [$semana['inicio'], $semana['fim']])
                ->pluck('id');

            // Férias ou sem dados
            if ($refeicaoIds->isEmpty()) {
                $dados['presente'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                $dados['extra'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                $dados['ausente'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                $dados['atestado'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                $dados['justificado'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                $dados['n_frequenta'][$i] = ['valor' => 'Férias/Recesso', 'tipo' => 'texto'];
                continue;
            }

            // Presentes
            $presentes = Presenca::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_da_presenca', StatusPresenca::PRESENTE)
                ->count();

            // Extras (aprovados da fila)
            $extras = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_fila_extras', 'aprovado')
                ->count();

            // Ausentes (falta injustificada)
            $ausentes = Presenca::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)
                ->count();

            // Atestado (justificativa com anexo médico - simulado como 0 por enquanto)
            $atestado = 0; // TODO: Implementar lógica específica para atestados médicos

            // Justificado (falta justificada)
            $justificados = Presenca::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)
                ->count();

            // Não Frequenta (cancelados)
            $naoFrequenta = Presenca::whereIn('refeicao_id', $refeicaoIds)
                ->where('status_da_presenca', StatusPresenca::CANCELADO)
                ->count();

            $dados['presente'][$i] = ['valor' => $presentes, 'tipo' => 'numero'];
            $dados['extra'][$i] = ['valor' => $extras, 'tipo' => 'numero'];
            $dados['ausente'][$i] = ['valor' => $ausentes, 'tipo' => 'numero'];
            $dados['atestado'][$i] = ['valor' => $atestado, 'tipo' => 'numero'];
            $dados['justificado'][$i] = ['valor' => $justificados, 'tipo' => 'numero'];
            $dados['n_frequenta'][$i] = ['valor' => $naoFrequenta, 'tipo' => 'numero'];
        }

        // Calcular totais
        $totais = [
            'presente' => $this->somarValores($dados['presente']),
            'extra' => $this->somarValores($dados['extra']),
            'ausente' => $this->somarValores($dados['ausente']),
            'atestado' => $this->somarValores($dados['atestado']),
            'justificado' => $this->somarValores($dados['justificado']),
            'n_frequenta' => $this->somarValores($dados['n_frequenta']),
        ];

        $totalMensal = $totais['presente'] + $totais['extra'];

        return [
            'mes' => $mes,
            'ano' => $ano,
            'mes_texto' => $dataInicio->locale('pt_BR')->monthName,
            'mes_ano' => strtoupper($dataInicio->locale('pt_BR')->translatedFormat('F \D\E Y')),
            'semanas' => $semanas,
            'dados' => $dados,
            'totais' => $totais,
            'total_mensal_refeicoes' => $totalMensal,
        ];
    }

    /**
     * Identifica as semanas de um mês
     */
    private function identificarSemanas(Carbon $inicio, Carbon $fim): array
    {
        $semanas = [];
        $current = $inicio->copy();
        $semanaNum = 1;

        while ($current->lte($fim)) {
            $inicioSemana = $current->copy();
            $fimSemana = $current->copy()->endOfWeek();
            
            if ($fimSemana->gt($fim)) {
                $fimSemana = $fim->copy();
            }

            $semanas[] = [
                'numero' => $semanaNum,
                'label' => "Semana {$semanaNum}",
                'inicio' => $inicioSemana->toDateString(),
                'fim' => $fimSemana->toDateString(),
                'periodo' => $inicioSemana->format('d/m') . ' - ' . $fimSemana->format('d/m'),
            ];

            $current = $fimSemana->copy()->addDay();
            $semanaNum++;
        }

        return $semanas;
    }

    /**
     * Soma valores numéricos, ignorando textos
     */
    private function somarValores(array $valores): int
    {
        $soma = 0;
        foreach ($valores as $v) {
            if ($v['tipo'] === 'numero') {
                $soma += $v['valor'];
            }
        }
        return $soma;
    }

    /**
     * Gera dados para exportação Excel no formato da planilha
     */
    public function dadosParaExcel(int $mes, int $ano): Collection
    {
        $relatorio = $this->gerarRelatorioMensal($mes, $ano);
        
        $linhas = collect();

        // Cabeçalho do mês
        $cabecalho = ['', $relatorio['mes_ano']];
        foreach ($relatorio['semanas'] as $semana) {
            $cabecalho[] = '';
        }
        $cabecalho[] = 'Total';
        $cabecalho[] = 'Mês';
        
        // Linha de semanas
        $linhaSemanas = [''];
        foreach ($relatorio['semanas'] as $semana) {
            $linhaSemanas[] = $semana['label'];
        }
        $linhaSemanas[] = '';
        $linhaSemanas[] = '';
        
        $linhas->push($linhaSemanas);

        // Dados por categoria
        $categorias = [
            'presente' => 'Presente',
            'extra' => 'Extra',
            'ausente' => 'Ausente',
            'atestado' => 'Atestado',
            'justificado' => 'Justificado',
            'n_frequenta' => 'Ñ Frequenta',
        ];

        foreach ($categorias as $key => $label) {
            $linha = [$label];
            foreach ($relatorio['dados'][$key] as $valor) {
                $linha[] = $valor['tipo'] === 'texto' ? $valor['valor'] : $valor['valor'];
            }
            $linha[] = $relatorio['totais'][$key];
            $linhas->push($linha);
        }

        // Total mensal
        $linhaTotal = ['Total Mensal de Refeições'];
        for ($i = 0; $i < count($relatorio['semanas']); $i++) {
            $linhaTotal[] = '-----';
        }
        $linhaTotal[] = $relatorio['total_mensal_refeicoes'];
        $linhas->push($linhaTotal);

        return $linhas;
    }
}
