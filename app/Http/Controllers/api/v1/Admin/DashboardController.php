<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    /**
     * RF11 - Dashboard principal com todas as estatísticas
     * GET /api/v1/admin/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        // Se não especificou datas, usa o mês/ano informado
        if (!$dataInicio || !$dataFim) {
            $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth()->toDateString();
            $dataFim = Carbon::create($ano, $mes, 1)->endOfMonth()->toDateString();
        }

        return response()->json([
            'data' => [
                'resumo' => $this->service->resumoGeral($mes, $ano),
                'taxa_presenca' => $this->service->taxaPresenca($dataInicio, $dataFim),
                'faltas' => $this->service->faltasPorTipo($dataInicio, $dataFim),
                'extras' => $this->service->extrasAtendidos($dataInicio, $dataFim),
            ],
            'errors' => [],
            'meta' => [
                'periodo' => "{$dataInicio} a {$dataFim}",
                'gerado_em' => now()->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    /**
     * RF11 - Resumo geral
     * GET /api/v1/admin/dashboard/resumo
     */
    public function resumo(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        return response()->json([
            'data' => $this->service->resumoGeral($mes, $ano),
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF11 - Taxa de presença
     * GET /api/v1/admin/dashboard/taxa-presenca
     */
    public function taxaPresenca(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return response()->json([
            'data' => $this->service->taxaPresenca($dataInicio, $dataFim),
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF11 - Faltas por tipo
     * GET /api/v1/admin/dashboard/faltas
     */
    public function faltas(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return response()->json([
            'data' => $this->service->faltasPorTipo($dataInicio, $dataFim),
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF11 - Extras atendidos
     * GET /api/v1/admin/dashboard/extras
     */
    public function extras(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return response()->json([
            'data' => $this->service->extrasAtendidos($dataInicio, $dataFim),
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF11 - Evolução mensal
     * GET /api/v1/admin/dashboard/evolucao
     */
    public function evolucao(Request $request): JsonResponse
    {
        $meses = $request->input('meses', 6);

        return response()->json([
            'data' => $this->service->evolucaoMensal($meses),
            'errors' => [],
            'meta' => ['meses_analisados' => $meses],
        ]);
    }

    /**
     * RF11 - Top faltosos do mês
     * GET /api/v1/admin/dashboard/faltosos
     */
    public function faltosos(Request $request): JsonResponse
    {
        $limite = $request->input('limite', 10);

        return response()->json([
            'data' => $this->service->topFaltosos($limite),
            'errors' => [],
            'meta' => ['limite' => $limite],
        ]);
    }
}
