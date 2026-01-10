<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller para dashboard administrativo (RF11)
 * 
 * Responsabilidades:
 * - Fornecer estatísticas e métricas do sistema
 * - Delegar cálculos para DashboardService
 */
class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $service
    ) {}

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

        return ApiResponse::standardSuccess(
            data: [
                'resumo' => $this->service->resumoGeral($mes, $ano),
                'taxa_presenca' => $this->service->taxaPresenca($dataInicio, $dataFim),
                'faltas' => $this->service->faltasPorTipo($dataInicio, $dataFim),
                'extras' => $this->service->extrasAtendidos($dataInicio, $dataFim),
            ],
            meta: [
                'periodo' => "{$dataInicio} a {$dataFim}",
                'gerado_em' => DateHelper::formatarDataHoraBR(now()),
            ]
        );
    }

    /**
     * RF11 - Resumo geral
     * GET /api/v1/admin/dashboard/resumo
     */
    public function resumo(Request $request): JsonResponse
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        return ApiResponse::standardSuccess(
            $this->service->resumoGeral($mes, $ano)
        );
    }

    /**
     * RF11 - Taxa de presença
     * GET /api/v1/admin/dashboard/taxa-presenca
     */
    public function taxaPresenca(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return ApiResponse::standardSuccess(
            $this->service->taxaPresenca($dataInicio, $dataFim)
        );
    }

    /**
     * RF11 - Faltas por tipo
     * GET /api/v1/admin/dashboard/faltas
     */
    public function faltas(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return ApiResponse::standardSuccess(
            $this->service->faltasPorTipo($dataInicio, $dataFim)
        );
    }

    /**
     * RF11 - Extras atendidos
     * GET /api/v1/admin/dashboard/extras
     */
    public function extras(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        return ApiResponse::standardSuccess(
            $this->service->extrasAtendidos($dataInicio, $dataFim)
        );
    }

    /**
     * RF11 - Evolução mensal
     * GET /api/v1/admin/dashboard/evolucao
     */
    public function evolucao(Request $request): JsonResponse
    {
        $meses = $request->input('meses', 6);

        return ApiResponse::standardSuccess(
            data: $this->service->evolucaoMensal($meses),
            meta: ['meses_analisados' => $meses]
        );
    }

    /**
     * RF11 - Top faltosos do mês
     * GET /api/v1/admin/dashboard/faltosos
     */
    public function faltosos(Request $request): JsonResponse
    {
        $limite = $request->input('limite', 10);

        return ApiResponse::standardSuccess(
            data: $this->service->topFaltosos($limite),
            meta: ['limite' => $limite]
        );
    }
}
