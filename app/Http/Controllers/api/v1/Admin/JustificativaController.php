<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\JustificativaService;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
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

    /**
     * RF10 - Listar justificativas de faltas
     * GET /api/v1/admin/justificativas
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'status', 'user_id', 'tipo', 'data_inicio', 'data_fim', 'turno',
            'sort_by', 'sort_order'
        ]);
        
        $perPage = $request->integer('per_page', 15);
        $justificativas = $this->service->listarJustificativas($filtros, $perPage);

        // Formatar dados para resposta
        $data = $justificativas->map(function ($just) {
            return [
                'id' => $just->id,
                'usuario' => [
                    'id' => $just->usuario->id,
                    'nome' => $just->usuario->nome,
                    'matricula' => $just->usuario->matricula,
                ],
                'refeicao' => $just->presenca ? [
                    'id' => $just->presenca->refeicao->id,
                    'data' => DateHelper::formatarDataBR($just->presenca->refeicao->data_do_cardapio),
                    'turno' => $just->presenca->refeicao->turno,
                ] : null,
                'motivo' => $just->motivo,
                'tem_anexo' => !empty($just->anexo_path),
                'status_justificativa' => $just->status_justificativa->value,
                'criado_em' => DateHelper::formatarDataHoraBR($just->created_at),
                'aprovado_por' => $just->aprovadoPor?->nome,
                'aprovado_em' => $just->aprovado_em ? DateHelper::formatarDataHoraBR($just->aprovado_em) : null,
                'observacao_admin' => $just->observacao_admin,
            ];
        });

        // Estatísticas
        $stats = $this->service->estatisticas($filtros);

        return ApiResponse::standardResponse(
            data: $data,
            meta: [
                'pagination' => [
                    'total' => $justificativas->total(),
                    'per_page' => $justificativas->perPage(),
                    'current_page' => $justificativas->currentPage(),
                    'last_page' => $justificativas->lastPage(),
                ],
                'stats' => $stats,
            ]
        );
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
                'refeicao' => $just->presenca ? [
                    'id' => $just->presenca->refeicao->id,
                    'data' => DateHelper::formatarDataBR($just->presenca->refeicao->data_do_cardapio),
                    'turno' => $just->presenca->refeicao->turno,
                    'cardapio' => $just->presenca->refeicao->cardapio ? [
                        'prato_principal' => $just->presenca->refeicao->cardapio->prato_principal_ptn01,
                    ] : null,
                ] : null,
                'motivo' => $just->motivo,
                'anexo_path' => $just->anexo_path,
                'tem_anexo' => !empty($just->anexo_path),
                'status_justificativa' => $just->status_justificativa->value,
                'criado_em' => DateHelper::formatarDataHoraBR($just->created_at),
                'aprovador' => $just->aprovadoPor ? [
                    'id' => $just->aprovadoPor->id,
                    'nome' => $just->aprovadoPor->nome,
                ] : null,
                'aprovado_em' => $just->aprovado_em ? DateHelper::formatarDataHoraBR($just->aprovado_em) : null,
                'observacao_admin' => $just->observacao_admin,
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
                adminId: $request->user()->id,
                observacao: $request->input('observacao')
            );

            return ApiResponse::standardSuccess(
                data: [
                    'id' => $justificativa->id,
                    'status_justificativa' => $justificativa->status_justificativa->value,
                    'usuario' => $justificativa->usuario->nome,
                    'aprovado_em' => DateHelper::formatarDataHoraBR($justificativa->aprovado_em),
                ],
                meta: ['message' => '✅ Justificativa aprovada com sucesso.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ e) {
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
                adminId: $request->user()->id,
                observacao: $request->input('observacao')
            );

            return ApiResponse::standardSuccess(
                data: [
                    'id' => $justificativa->id,
                    'status_justificativa' => $justificativa->status_justificativa->value,
                    'usuario' => $justificativa->usuario->nome,
                    'observacao_admin' => $justificativa->observacao_admin,
                    'aprovado_em' => DateHelper::formatarDataHoraBR($justificativa->aprovado_em),
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

            if (empty($just->anexo_path)) {
                return ApiResponse::standardNotFound('anexo', 'Esta justificativa não possui anexo.');
            }

            $path = 'justificativas/' . $just->anexo_path;

            if (!Storage::exists($path)) {
                return ApiResponse::standardNotFound('anexo', 'Arquivo não encontrado no servidor.');
            }

            return Storage::download($path, $just->anexo_path);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('justificativa', 'Justificativa não encontrada.');
        }
    }
}
