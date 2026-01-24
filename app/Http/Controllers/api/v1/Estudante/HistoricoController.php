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
     * Helper: Obter usuário autenticado ou fallback para dev
     */
    private function getUser(Request $request)
    {
        $user = $request->user();

        // Fallback para desenvolvimento sem autenticação
        if (!$user && config('app.debug')) {
            $user = \App\Models\User::where('perfil', 'estudante')
                ->where('bolsista', false)
                ->first();

            if (!$user) {
                throw new \Exception('Nenhum estudante não-bolsista encontrado no banco de dados.');
            }
        }

        return $user;
    }

    /**
     * RF04 - Lista histórico de refeições e faltas
     * GET /api/v1/estudante/historico
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        // Filtros de período
        $periodo = $request->input('periodo', 'mes'); // semana, mes, todos
        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');

        // Simplificado: carrega apenas refeicao (sem cardapio) para evitar timeout
        $query = $user->presencas()
            ->with(['refeicao'])
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
                'data' => $p->refeicao?->data_do_cardapio?->format('Y-m-d') ?? null,
                'turno' => $p->refeicao?->turno?->value ?? $p->refeicao?->turno ?? null,
                'prato_principal' => 'Refeição RI', // Placeholder - pode ser enriquecido depois
                'presente' => $p->status_da_presenca?->value === 'presente' || $p->status_da_presenca === 'presente',
                'confirmado_em' => $p->validado_em?->format('Y-m-d H:i:s'),
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
        $user = $this->getUser($request);
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

        // Estrutura para compatibilidade com frontend
        return [
            'total_refeicoes' => $presentes,
            'total_extras' => 0, // Pode ser calculado se houver lógica de extras
            'mes_atual' => [
                'total' => $total,
                'extras' => 0,
                'refeicoes' => $presentes,
            ],
            'ultima_refeicao' => null,
            // Dados adicionais para backwards compatibility
            'total' => $total,
            'presentes' => $presentes,
            'faltas_justificadas' => $faltasJustificadas,
            'faltas_injustificadas' => $faltasInjustificadas,
            'ausentes' => $ausentes,
            'taxa_presenca' => $taxaPresenca,
        ];
    }
}
