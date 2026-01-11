<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Enums\StatusPresenca;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller para histórico de refeições do estudante (RF04)
 */
class HistoricoController extends Controller
{
    /**
     * RF04 - Lista histórico de refeições e faltas
     * GET /api/v1/estudante/historico
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Filtros de período
        $periodo = $request->input('periodo', 'mes'); // semana, mes, todos
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        $query = $user->presencas()
            ->with(['refeicao.cardapio'])
            ->orderByDesc('registrado_em');

        // Aplicar filtro de período
        if ($dataInicio && $dataFim) {
            $query->whereBetween('registrado_em', [$dataInicio, $dataFim]);
        } elseif ($periodo === 'semana') {
            $query->where('registrado_em', '>=', now()->startOfWeek());
        } elseif ($periodo === 'mes') {
            $query->where('registrado_em', '>=', now()->startOfMonth());
        }

        $presencas = $query->paginate($request->integer('per_page', 15));

        // Calcular resumo
        $resumo = $this->calcularResumo($user, $periodo, $dataInicio, $dataFim);

        return ApiResponse::standardSuccess(
            data: $presencas->map(fn($p) => [
                'id' => $p->id,
                'data' => $p->refeicao?->cardapio?->data_do_cardapio?->format('Y-m-d'),
                'turno' => $p->refeicao?->turno,
                'status' => $p->status_da_presenca?->value ?? $p->status_da_presenca,
                'registrado_em' => $p->registrado_em?->format('Y-m-d H:i:s'),
            ]),
            meta: [
                'resumo' => $resumo,
                'periodo' => $periodo,
                'total' => $presencas->total(),
                'per_page' => $presencas->perPage(),
                'current_page' => $presencas->currentPage(),
            ]
        );
    }

    /**
     * RF04 - Resumo de presenças e faltas
     * GET /api/v1/estudante/historico/resumo
     */
    public function resumo(Request $request): JsonResponse
    {
        $user = $request->user();
        $periodo = $request->input('periodo', 'mes');

        $resumo = $this->calcularResumo($user, $periodo);

        return ApiResponse::standardSuccess($resumo);
    }

    /**
     * Calcula resumo de presenças e faltas
     */
    private function calcularResumo($user, string $periodo, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $query = $user->presencas();

        if ($dataInicio && $dataFim) {
            $query->whereBetween('registrado_em', [$dataInicio, $dataFim]);
        } elseif ($periodo === 'semana') {
            $query->where('registrado_em', '>=', now()->startOfWeek());
        } elseif ($periodo === 'mes') {
            $query->where('registrado_em', '>=', now()->startOfMonth());
        }

        $total = $query->count();
        $presentes = (clone $query)->where('status_da_presenca', StatusPresenca::PRESENTE)->count();
        $faltasJustificadas = (clone $query)->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)->count();
        $faltasInjustificadas = (clone $query)->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)->count();
        $ausentes = (clone $query)->where('status_da_presenca', StatusPresenca::AUSENTE)->count();

        $taxaPresenca = $total > 0 ? round(($presentes / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'presentes' => $presentes,
            'faltas_justificadas' => $faltasJustificadas,
            'faltas_injustificadas' => $faltasInjustificadas,
            'ausentes' => $ausentes,
            'taxa_presenca' => $taxaPresenca,
        ];
    }
}
