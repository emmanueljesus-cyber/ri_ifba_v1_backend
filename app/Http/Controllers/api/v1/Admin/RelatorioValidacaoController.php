<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presenca;
use App\Models\Refeicao;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RelatorioValidacaoController extends Controller
{
    /**
     * Relatório de quem validou as presenças
     * GET /api/v1/admin/relatorios/validacoes
     */
    public function index(Request $request)
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
            'refeicao_id' => 'nullable|exists:refeicoes,id',
            'validado_por' => 'nullable|exists:users,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Presenca::with(['user', 'refeicao.cardapio', 'validador'])
            ->where('status_da_presenca', 'validado')
            ->whereNotNull('validado_por');

        // Filtro por período
        if ($request->data_inicio) {
            $query->whereDate('validado_em', '>=', $request->data_inicio);
        }
        if ($request->data_fim) {
            $query->whereDate('validado_em', '<=', $request->data_fim);
        }

        // Filtro por refeição
        if ($request->refeicao_id) {
            $query->where('refeicao_id', $request->refeicao_id);
        }

        // Filtro por admin validador
        if ($request->validado_por) {
            $query->where('validado_por', $request->validado_por);
        }

        $presencas = $query->orderBy('validado_em', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $presencas->map(function ($presenca) {
                return [
                    'id' => $presenca->id,
                    'estudante' => [
                        'id' => $presenca->user->id,
                        'nome' => $presenca->user->nome,
                        'matricula' => $presenca->user->matricula,
                    ],
                    'refeicao' => [
                        'id' => $presenca->refeicao->id,
                        'data' => $presenca->refeicao->data_do_cardapio->format('d/m/Y'),
                        'turno' => $presenca->refeicao->turno->value,
                    ],
                    'validacao' => [
                        'validado_em' => $presenca->validado_em->format('d/m/Y H:i:s'),
                        'validado_por' => [
                            'id' => $presenca->validador->id,
                            'nome' => $presenca->validador->nome,
                        ],
                    ],
                ];
            }),
            'meta' => [
                'current_page' => $presencas->currentPage(),
                'last_page' => $presencas->lastPage(),
                'per_page' => $presencas->perPage(),
                'total' => $presencas->total(),
            ],
        ]);
    }

    /**
     * Estatísticas por admin validador
     * GET /api/v1/admin/relatorios/validacoes/por-admin
     */
    public function porAdmin(Request $request)
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
        ]);

        $query = Presenca::with('validador')
            ->where('status_da_presenca', 'validado')
            ->whereNotNull('validado_por');

        if ($request->data_inicio) {
            $query->whereDate('validado_em', '>=', $request->data_inicio);
        }
        if ($request->data_fim) {
            $query->whereDate('validado_em', '<=', $request->data_fim);
        }

        $estatisticas = $query->get()
            ->groupBy('validado_por')
            ->map(function ($presencas) {
                $admin = $presencas->first()->validador;

                return [
                    'admin' => [
                        'id' => $admin->id,
                        'nome' => $admin->nome,
                    ],
                    'total_validacoes' => $presencas->count(),
                    'primeira_validacao' => $presencas->min('validado_em')->format('d/m/Y H:i:s'),
                    'ultima_validacao' => $presencas->max('validado_em')->format('d/m/Y H:i:s'),
                ];
            })
            ->sortByDesc('total_validacoes')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $estatisticas,
            'resumo' => [
                'total_admins' => $estatisticas->count(),
                'total_validacoes' => $estatisticas->sum('total_validacoes'),
            ],
        ]);
    }

    /**
     * Histórico de validações de uma refeição específica
     * GET /api/v1/admin/relatorios/validacoes/refeicao/{id}
     */
    public function porRefeicao($refeicaoId)
    {
        $refeicao = Refeicao::with('cardapio')->findOrFail($refeicaoId);

        $presencas = Presenca::with(['user', 'validador'])
            ->where('refeicao_id', $refeicaoId)
            ->where('status_da_presenca', 'validado')
            ->whereNotNull('validado_por')
            ->orderBy('validado_em')
            ->get();

        $estatisticas = [
            'total_validadas' => $presencas->count(),
            'admins_distintos' => $presencas->pluck('validado_por')->unique()->count(),
            'primeira_validacao' => $presencas->first()?->validado_em?->format('d/m/Y H:i:s'),
            'ultima_validacao' => $presencas->last()?->validado_em?->format('d/m/Y H:i:s'),
        ];

        return response()->json([
            'success' => true,
            'refeicao' => [
                'id' => $refeicao->id,
                'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                'turno' => $refeicao->turno->value,
            ],
            'estatisticas' => $estatisticas,
            'validacoes' => $presencas->map(function ($presenca) {
                return [
                    'estudante' => [
                        'nome' => $presenca->user->nome,
                        'matricula' => $presenca->user->matricula,
                    ],
                    'validado_em' => $presenca->validado_em->format('d/m/Y H:i:s'),
                    'validado_por' => $presenca->validador->nome,
                ];
            }),
        ]);
    }

    /**
     * Timeline de validações (para gráficos)
     * GET /api/v1/admin/relatorios/validacoes/timeline
     */
    public function timeline(Request $request)
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
        ]);

        $dataInicio = $request->data_inicio ?? now()->subDays(7)->format('Y-m-d');
        $dataFim = $request->data_fim ?? now()->format('Y-m-d');

        $validacoes = Presenca::whereNotNull('validado_por')
            ->whereDate('validado_em', '>=', $dataInicio)
            ->whereDate('validado_em', '<=', $dataFim)
            ->get()
            ->groupBy(function ($presenca) {
                return $presenca->validado_em->format('Y-m-d');
            })
            ->map(function ($presencas, $data) {
                return [
                    'data' => Carbon::parse($data)->format('d/m/Y'),
                    'total' => $presencas->count(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'periodo' => [
                'inicio' => Carbon::parse($dataInicio)->format('d/m/Y'),
                'fim' => Carbon::parse($dataFim)->format('d/m/Y'),
            ],
            'data' => $validacoes,
        ]);
    }
}
