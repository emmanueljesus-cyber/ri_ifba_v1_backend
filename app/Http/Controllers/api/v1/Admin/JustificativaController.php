<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\JustificativaService;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para gerenciamento de justificativas de faltas (RF10)
 * 
 * Responsabilidades:
 * - Orquestração HTTP (validação de requests, formatação de respostas)
 * - Delegação de lógica de negócio para JustificativaService
 */
class JustificativaController extends Controller
{
    public function __construct(
        private JustificativaService $service
    ) {}

    private function resolveAdminId(Request $request): int
    {
        $userId = $request->user()?->id;
        if ($userId) {
            return $userId;
        }

        $adminId = User::where('perfil', 'admin')->value('id');
        return $adminId ?? 1;
    }

    /**
     * RF10 - Listar justificativas de faltas
     * GET /api/v1/admin/justificativas
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|in:pendente,aprovada,rejeitada',
            'tipo' => 'nullable|in:atestado,documento,outros',
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $this->service->filtrarJustificativas(
            status: $request->input('status'),
            tipo: $request->input('tipo'),
            dataInicio: $request->input('data_inicio'),
            dataFim: $request->input('data_fim')
        );

        $perPage = $request->input('per_page', 15);
        $justificativas = $query->paginate($perPage);

        // Formata os dados
        $data = $justificativas->through(function ($justificativa) {
            return [
                'id' => $justificativa->id,
                'tipo_justificativa' => $justificativa->tipo_justificativa,
                'descricao' => $justificativa->descricao,
                'status_justificativa' => $justificativa->status_justificativa,
                'motivo_rejeicao' => $justificativa->motivo_rejeicao,
                'tem_anexo' => !empty($justificativa->anexo_path),
                'anexo_nome' => $justificativa->anexo_nome,
                'data_presenca' => $justificativa->presenca?->refeicao?->data_do_cardapio?->format('Y-m-d'),
                'turno' => $justificativa->presenca?->refeicao?->turno?->value,
                'user' => [
                    'id' => $justificativa->user?->id,
                    'nome' => $justificativa->user?->nome,
                    'matricula' => $justificativa->user?->matricula,
                    'foto' => $justificativa->user?->foto_url,
                ],
                'validada_por' => $justificativa->validada_por_user?->nome,
                'validada_em' => $justificativa->validada_em?->format('Y-m-d H:i:s'),
                'created_at' => $justificativa->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return ApiResponse::standardSuccess($data, [
            'total' => $justificativas->total(),
            'current_page' => $justificativas->currentPage(),
            'per_page' => $justificativas->perPage(),
            'last_page' => $justificativas->lastPage(),
        ]);
    }

    /**
     * RF10 - Detalhes de uma justificativa
     * GET /api/v1/admin/justificativas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $just = $this->service->buscarJustificativa($id);

            return ApiResponse::standardSuccess([
                'id' => $just->id,
                'usuario' => [
                    'id' => $just->usuario->id,
                    'nome' => $just->usuario->nome,
                    'matricula' => $just->usuario->matricula,
                    'email' => $just->usuario->email,
                    'curso' => $just->usuario->curso,
                ],
                'refeicao' => $just->refeicao ? [
                    'id' => $just->refeicao->id,
                    'data' => DateHelper::formatarDataBR($just->refeicao->data_do_cardapio),
                    'turno' => $just->refeicao->turno,
                    'cardapio' => $just->refeicao->cardapio ? [
                        'prato_principal' => $just->refeicao->cardapio->prato_principal_ptn01,
                    ] : null,
                ] : null,
                'tipo' => $just->tipo->value ?? $just->tipo,
                'motivo' => $just->motivo,
                'anexo_path' => $just->anexo,
                'tem_anexo' => !empty($just->anexo),
                'status_justificativa' => $just->status->value,
                'criado_em' => DateHelper::formatarDataHoraBR($just->created_at),
                'aprovador' => $just->aprovadoPor ? [
                    'id' => $just->aprovadoPor->id,
                    'nome' => $just->aprovadoPor->nome,
                ] : null,
                'aprovado_em' => $just->avaliado_em ? DateHelper::formatarDataHoraBR($just->avaliado_em) : null,
                'observacao_admin' => $just->motivo_rejeicao,
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('justificativa', 'Justificativa não encontrada.');
        }
    }

    /**
     * RF10 - Aprovar justificativa
     * POST /api/v1/admin/justificativas/{id}/aprovar
     */
    public function aprovar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'observacao' => 'nullable|string|max:500'
        ]);

        try {
            $justificativa = $this->service->aprovarJustificativa(
                id: $id,
                adminId: $this->resolveAdminId($request),
                observacao: $request->input('observacao')
            );

            return ApiResponse::standardSuccess(
                data: [
                    'id' => $justificativa->id,
                    'status_justificativa' => $justificativa->status->value,
                    'usuario' => $justificativa->usuario->nome,
                    'aprovado_em' => DateHelper::formatarDataHoraBR($justificativa->avaliado_em),
                ],
                meta: ['message' => '✅ Justificativa aprovada com sucesso.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('justificativa', 'Justificativa não encontrada.');
        } catch (\Exception $e) {
            return ApiResponse::standardError('justificativa', $e->getMessage(), 422);
        }
    }

    /**
     * RF10 - Rejeitar justificativa
     * POST /api/v1/admin/justificativas/{id}/rejeitar
     */
    public function rejeitar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'observacao' => 'required|string|max:500'
        ]);

        try {
            $justificativa = $this->service->rejeitarJustificativa(
                id: $id,
                adminId: $this->resolveAdminId($request),
                observacao: $request->input('observacao')
            );

            return ApiResponse::standardSuccess(
                data: [
                    'id' => $justificativa->id,
                    'status_justificativa' => $justificativa->status->value,
                    'usuario' => $justificativa->usuario->nome,
                    'observacao_admin' => $justificativa->motivo_rejeicao,
                    'aprovado_em' => DateHelper::formatarDataHoraBR($justificativa->avaliado_em),
                ],
                meta: ['message' => '❌ Justificativa rejeitada.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('justificativa', 'Justificativa não encontrada.');
        } catch (\Exception $e) {
            return ApiResponse::standardError('justificativa', $e->getMessage(), 422);
        }
    }

    /**
     * RF10 - Download do anexo
     * GET /api/v1/admin/justificativas/{id}/anexo
     */
    public function downloadAnexo(int $id)
    {
        try {
            $just = $this->service->buscarJustificativa($id);

            if (empty($just->anexo)) {
                return ApiResponse::standardNotFound('anexo', 'Esta justificativa não possui anexo.');
            }

            $path = 'justificativas/' . $just->anexo;

            if (!Storage::exists($path)) {
                return ApiResponse::standardNotFound('anexo', 'Arquivo não encontrado no servidor.');
            }

            return Storage::download($path, $just->anexo);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('justificativa', 'Justificativa não encontrada.');
        }
    }
}
