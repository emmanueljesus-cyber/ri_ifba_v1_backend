<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\BolsistaAprovadoService;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de matrículas aprovadas (RF15)
 * 
 * Responsabilidades:
 * - Orquestração HTTP
 * - Delegação para BolsistaAprovadoService
 */
class BolsistaAprovadoController extends Controller
{
    public function __construct(
        private BolsistaAprovadoService $service
    ) {}

    /**
     * RF15 - Lista bolsistas aprovados
     * GET /api/v1/admin/bolsistas-aprovados
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only(['turno', 'ativo', 'matricula', 'sort_by', 'sort_order']);
        $perPage = $request->integer('per_page', 20);
        
        $bolsistas = $this->service->listarBolsistas($filtros, $perPage);

        // Formatar dados
        $data = $bolsistas->map(function ($b) {
            return [
                'id' => $b->id,
                'matricula' => $b->matricula,
                'turno' => $b->turno,
                'ativo' => $b->ativo,
                'created_at' => DateHelper::formatarDataHoraBR($b->created_at),
            ];
        });

        // Estatísticas
        $stats = $this->service->estatisticas();

        return ApiResponse::standardResponse(
            data: $data,
            meta: [
                'pagination' => [
                    'total' => $bolsistas->total(),
                    'per_page' => $bolsistas->perPage(),
                    'current_page' => $bolsistas->currentPage(),
                    'last_page' => $bolsistas->lastPage(),
                ],
                'stats' => $stats,
            ]
        );
    }

    /**
     * RF15 - Adicionar bolsista à lista de aprovados
     * POST /api/v1/admin/bolsistas-aprovados
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'matricula' => 'required|string|max:20',
            'turno' => 'required|in:almoco,jantar',
        ]);

        try {
            $bolsista = $this->service->adicionarBolsista(
                matricula: $request->input('matricula'),
                turno: $request->input('turno')
            );

            return ApiResponse::standardCreated(
                data: [
                    'id' => $bolsista->id,
                    'matricula' => $bolsista->matricula,
                    'turno' => $bolsista->turno,
                ],
                meta: ['message' => '✅ Matrícula adicionada à lista de aprovados.']
            );
            
        } catch (\Exception $e) {
            return ApiResponse::standardError('bolsista', $e->getMessage(), 422);
        }
    }

    /**
     * RF15 - Detalhes de um bolsista aprovado
     * GET /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $bolsista = $this->service->buscarBolsista($id);

            return ApiResponse::standardSuccess([
                'id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'turno' => $bolsista->turno,
                'ativo' => $bolsista->ativo,
                'created_at' => DateHelper::formatarDataHoraBR($bolsista->created_at),
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
        }
    }

    /**
     * RF15 - Atualizar dados do bolsista aprovado
     * PUT /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'matricula' => 'sometimes|string|max:20',
            'turno' => 'sometimes|in:almoco,jantar',
        ]);

        try {
            $bolsista = $this->service->atualizarBolsista(
                id: $id,
                data: $request->only(['matricula', 'turno'])
            );

            return ApiResponse::standardSuccess(
                data: [
                    'id' => $bolsista->id,
                    'matricula' => $bolsista->matricula,
                ],
                meta: ['message' => '✅ Bolsista atualizado.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
        } catch (\Exception $e) {
            return ApiResponse::standardError('bolsista', $e->getMessage(), 422);
        }
    }

    /**
     * RF15 - Desativar bolsista (soft delete)
     * DELETE /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->desativarBolsista($id);

            return ApiResponse::standardSuccess(
                data: ['id' => $id],
                meta: ['message' => '✅ Bolsista desativado.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
        }
    }

    /**
     * RF15 - Reativar bolsista
     * POST /api/v1/admin/bolsistas-aprovados/{id}/reativar
     */
    public function reativar(int $id): JsonResponse
    {
        try {
            $bolsista = $this->service->reativarBolsista($id);

            return ApiResponse::standardSuccess(
                data: ['id' => $bolsista->id],
                meta: ['message' => '✅ Bolsista reativado.']
            );
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
        }
    }
}
