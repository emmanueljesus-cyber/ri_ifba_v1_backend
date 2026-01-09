<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\RelatorioService;
use App\Services\RelatorioSemanalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RelatorioPresencasExport;
use App\Exports\RelatorioMensalSemanalExport;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    protected RelatorioService $service;
    protected RelatorioSemanalService $semanalService;

    public function __construct(RelatorioService $service, RelatorioSemanalService $semanalService)
    {
        $this->service = $service;
        $this->semanalService = $semanalService;
    }

    /**
     * RF12 - Relatório de presenças por período
     * GET /api/v1/admin/relatorios/presencas
     */
    public function presencas(Request $request): JsonResponse
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'turno' => 'nullable|in:almoco,jantar',
        ]);

        $dados = $this->service->presencasPorPeriodo(
            $request->input('data_inicio'),
            $request->input('data_fim'),
            $request->input('turno')
        );

        return response()->json([
            'data' => $dados['dados'],
            'errors' => [],
            'meta' => [
                'totais' => $dados['totais'],
                'periodo' => $dados['periodo'],
            ],
        ]);
    }

    /**
     * RF12 - Resumo mensal
     * GET /api/v1/admin/relatorios/mensal
     */
    public function mensal(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $dados = $this->service->resumoMensal($mes, $ano);

        return response()->json([
            'data' => $dados,
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF12 - Relatório mensal por semanas (formato planilha)
     * GET /api/v1/admin/relatorios/semanal
     */
    public function semanal(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $dados = $this->semanalService->gerarRelatorioMensal($mes, $ano);

        return response()->json([
            'data' => $dados,
            'errors' => [],
            'meta' => [
                'formato' => 'Linhas=Categorias, Colunas=Semanas',
                'categorias' => ['Presente', 'Extra', 'Ausente', 'Atestado', 'Justificado', 'Ñ Frequenta'],
            ],
        ]);
    }

    /**
     * RF12 - Exportar relatório semanal para Excel
     * GET /api/v1/admin/relatorios/exportar-semanal
     */
    public function exportarSemanal(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $meses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        $nomeArquivo = 'relatorio_' . strtolower($meses[$mes]) . '_' . $ano . '.xlsx';

        return Excel::download(
            new RelatorioMensalSemanalExport($mes, $ano),
            $nomeArquivo
        );
    }

    /**
     * RF12 - Relatório por bolsista
     * GET /api/v1/admin/relatorios/bolsista/{userId}
     */
    public function porBolsista(Request $request, int $userId): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        $dados = $this->service->porBolsista($userId, $dataInicio, $dataFim);

        if (isset($dados['erro'])) {
            return response()->json([
                'data' => null,
                'errors' => ['bolsista' => [$dados['erro']]],
                'meta' => [],
            ], 404);
        }

        return response()->json([
            'data' => $dados,
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF12 - Exportar relatório para Excel
     * GET /api/v1/admin/relatorios/exportar
     */
    public function exportar(Request $request)
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'turno' => 'nullable|in:almoco,jantar',
            'formato' => 'nullable|in:xlsx,csv',
        ]);

        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $turno = $request->input('turno');
        $formato = $request->input('formato', 'xlsx');

        $dados = $this->service->dadosParaExportacao($dataInicio, $dataFim, $turno);

        if ($dados->isEmpty()) {
            return response()->json([
                'data' => null,
                'errors' => ['dados' => ['Nenhum dado encontrado para o período.']],
                'meta' => [],
            ], 404);
        }

        $nomeArquivo = 'relatorio_presencas_' . Carbon::parse($dataInicio)->format('Ymd') . '_' . Carbon::parse($dataFim)->format('Ymd');

        return Excel::download(
            new RelatorioPresencasExport($dados),
            $nomeArquivo . '.' . $formato
        );
    }

    /**
     * RF12 - Relatório consolidado
     * GET /api/v1/admin/relatorios/consolidado
     */
    public function consolidado(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        
        $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth()->toDateString();
        $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth()->toDateString();

        $resumoMensal = $this->service->resumoMensal($mes, $ano);
        $presencasPeriodo = $this->service->presencasPorPeriodo($dataInicio, $dataFim);
        $semanalData = $this->semanalService->gerarRelatorioMensal($mes, $ano);

        return response()->json([
            'data' => [
                'resumo' => $resumoMensal,
                'detalhado' => $presencasPeriodo['dados'],
                'semanal' => $semanalData,
            ],
            'errors' => [],
            'meta' => [
                'periodo' => $presencasPeriodo['periodo'],
                'totais' => $presencasPeriodo['totais'],
                'gerado_em' => now()->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}

