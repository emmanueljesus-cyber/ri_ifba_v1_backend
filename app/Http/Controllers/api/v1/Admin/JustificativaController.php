<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Justificativa;
use App\Models\User;
use App\Enums\StatusJustificativa;
use App\Enums\StatusPresenca;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JustificativaController extends Controller
{
    /**
     * RF10 - Listar justificativas de faltas
     * GET /api/v1/admin/justificativas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Justificativa::with(['user', 'refeicao.cardapio', 'avaliador']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->has('data_inicio') && $request->has('data_fim')) {
            $query->whereBetween('enviado_em', [
                Carbon::parse($request->input('data_inicio'))->startOfDay(),
                Carbon::parse($request->input('data_fim'))->endOfDay(),
            ]);
        }

        // Ordenação
        $orderBy = $request->input('order_by', 'enviado_em');
        $orderDir = $request->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginação
        $perPage = $request->input('per_page', 15);
        $justificativas = $query->paginate($perPage);

        $data = $justificativas->map(function ($just) {
            return [
                'id' => $just->id,
                'usuario' => [
                    'id' => $just->user->id,
                    'nome' => $just->user->nome,
                    'matricula' => $just->user->matricula,
                ],
                'refeicao' => $just->refeicao ? [
                    'id' => $just->refeicao->id,
                    'data' => $just->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $just->refeicao->turno->value,
                ] : null,
                'tipo' => $just->tipo->value ?? $just->tipo,
                'motivo' => $just->motivo,
                'tem_anexo' => !empty($just->anexo),
                'status' => $just->status ?? 'pendente',
                'enviado_em' => $just->enviado_em->format('d/m/Y H:i'),
                'avaliado_por' => $just->avaliador?->nome,
                'avaliado_em' => $just->avaliado_em?->format('d/m/Y H:i'),
                'motivo_rejeicao' => $just->motivo_rejeicao,
            ];
        });

        return response()->json([
            'data' => $data,
            'errors' => [],
            'meta' => [
                'total' => $justificativas->total(),
                'per_page' => $justificativas->perPage(),
                'current_page' => $justificativas->currentPage(),
                'last_page' => $justificativas->lastPage(),
                'stats' => [
                    'pendentes' => Justificativa::where('status', 'pendente')->count(),
                    'aprovadas' => Justificativa::where('status', 'aprovada')->count(),
                    'rejeitadas' => Justificativa::where('status', 'rejeitada')->count(),
                ],
            ],
        ]);
    }

    /**
     * RF10 - Detalhes de uma justificativa
     * GET /api/v1/admin/justificativas/{id}
     */
    public function show(int $id): JsonResponse
    {
        $just = Justificativa::with(['user', 'refeicao.cardapio', 'avaliador'])->find($id);

        if (!$just) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Justificativa não encontrada.']],
                'meta' => [],
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $just->id,
                'usuario' => [
                    'id' => $just->user->id,
                    'nome' => $just->user->nome,
                    'matricula' => $just->user->matricula,
                    'email' => $just->user->email,
                    'curso' => $just->user->curso,
                ],
                'refeicao' => $just->refeicao ? [
                    'id' => $just->refeicao->id,
                    'data' => $just->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $just->refeicao->turno->value,
                    'cardapio' => $just->refeicao->cardapio ? [
                        'prato_principal' => $just->refeicao->cardapio->prato_principal_ptn01,
                    ] : null,
                ] : null,
                'tipo' => $just->tipo->value ?? $just->tipo,
                'tipo_texto' => $just->getTipoTexto(),
                'motivo' => $just->motivo,
                'anexo' => $just->anexo,
                'tem_anexo' => !empty($just->anexo),
                'status' => $just->status ?? 'pendente',
                'enviado_em' => $just->enviado_em->format('d/m/Y H:i'),
                'avaliador' => $just->avaliador ? [
                    'id' => $just->avaliador->id,
                    'nome' => $just->avaliador->nome,
                ] : null,
                'avaliado_em' => $just->avaliado_em?->format('d/m/Y H:i'),
                'motivo_rejeicao' => $just->motivo_rejeicao,
            ],
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * RF10 - Aprovar justificativa
     * POST /api/v1/admin/justificativas/{id}/aprovar
     */
    public function aprovar(Request $request, int $id): JsonResponse
    {
        $just = Justificativa::with(['user', 'refeicao'])->find($id);

        if (!$just) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Justificativa não encontrada.']],
                'meta' => [],
            ], 404);
        }

        if (($just->status ?? 'pendente') !== 'pendente') {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Esta justificativa já foi avaliada.']],
                'meta' => ['status_atual' => $just->status],
            ], 422);
        }

        $adminId = $request->user()?->id ?? 1;

        $just->update([
            'status' => 'aprovada',
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
        ]);

        // Atualizar presença para falta justificada se existir
        if ($just->refeicao) {
            $presenca = \App\Models\Presenca::where('user_id', $just->user_id)
                ->where('refeicao_id', $just->refeicao_id)
                ->first();

            if ($presenca) {
                $presenca->update([
                    'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
                ]);
            }
        }

        return response()->json([
            'data' => [
                'id' => $just->id,
                'status' => 'aprovada',
                'usuario' => $just->user->nome,
                'avaliado_em' => now()->format('d/m/Y H:i'),
            ],
            'errors' => [],
            'meta' => ['message' => '✅ Justificativa aprovada com sucesso.'],
        ]);
    }

    /**
     * RF10 - Rejeitar justificativa
     * POST /api/v1/admin/justificativas/{id}/rejeitar
     */
    public function rejeitar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $just = Justificativa::with(['user', 'refeicao'])->find($id);

        if (!$just) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Justificativa não encontrada.']],
                'meta' => [],
            ], 404);
        }

        if (($just->status ?? 'pendente') !== 'pendente') {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Esta justificativa já foi avaliada.']],
                'meta' => ['status_atual' => $just->status],
            ], 422);
        }

        $adminId = $request->user()?->id ?? 1;

        $just->update([
            'status' => 'rejeitada',
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
            'motivo_rejeicao' => $request->input('motivo'),
        ]);

        // Atualizar presença para falta injustificada se existir
        if ($just->refeicao) {
            $presenca = \App\Models\Presenca::where('user_id', $just->user_id)
                ->where('refeicao_id', $just->refeicao_id)
                ->first();

            if ($presenca) {
                $presenca->update([
                    'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
                ]);
            }
        }

        return response()->json([
            'data' => [
                'id' => $just->id,
                'status' => 'rejeitada',
                'usuario' => $just->user->nome,
                'motivo_rejeicao' => $request->input('motivo'),
                'avaliado_em' => now()->format('d/m/Y H:i'),
            ],
            'errors' => [],
            'meta' => ['message' => '❌ Justificativa rejeitada.'],
        ]);
    }

    /**
     * RF10 - Download do anexo
     * GET /api/v1/admin/justificativas/{id}/anexo
     */
    public function downloadAnexo(int $id)
    {
        $just = Justificativa::find($id);

        if (!$just) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Justificativa não encontrada.']],
                'meta' => [],
            ], 404);
        }

        if (empty($just->anexo)) {
            return response()->json([
                'data' => null,
                'errors' => ['anexo' => ['Esta justificativa não possui anexo.']],
                'meta' => [],
            ], 404);
        }

        $path = 'justificativas/' . $just->anexo;

        if (!Storage::exists($path)) {
            return response()->json([
                'data' => null,
                'errors' => ['anexo' => ['Arquivo não encontrado no servidor.']],
                'meta' => [],
            ], 404);
        }

        return Storage::download($path, $just->anexo);
    }
}
