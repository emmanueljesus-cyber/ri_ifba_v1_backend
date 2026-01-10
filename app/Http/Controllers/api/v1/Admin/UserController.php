<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciamento de usuários (RF14)
 * 
 * Responsabilidades:
 * - Orquestração HTTP (validação, formatação)
 * - Delegação para UserService
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $service
    ) {}

    /**
     * RF14 - Listar todos os usuários
     * GET /api/v1/admin/usuarios
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->only([
            'perfil', 'bolsista', 'desligado', 'busca', 'sort_by', 'sort_order'
        ]);
        
        $perPage = $request->integer('per_page', 15);
        $usuarios = $this->service->listarUsuarios($filtros, $perPage);

        return ApiResponse::success(
            data: $usuarios->items(),
            message: 'Usuários recuperados com sucesso'
        );
    }

    /**
     * RF14 - Criar novo usuário
     * POST /api/v1/admin/usuarios
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $usuario = $this->service->criarUsuario($request->validated());
            return ApiResponse::created($usuario, 'Usuário criado com sucesso');
            
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    /**
     * RF14 - Buscar usuário por ID
     * GET /api/v1/admin/usuarios/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usuario = $this->service->buscarUsuario($id);
            return ApiResponse::success($usuario, 'Usuário recuperado com sucesso');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Usuário não encontrado');
        }
    }

    /**
     * RF14 - Atualizar usuário
     * PUT/PATCH /api/v1/admin/usuarios/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = $this->service->atualizarUsuario($id, $request->validated());
            return ApiResponse::success($usuario, 'Usuário atualizado com sucesso');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Usuário não encontrado');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    /**
     * RF14 - Desativar usuário (soft delete)
     * DELETE /api/v1/admin/usuarios/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->desativarUsuario($id);
            return ApiResponse::success(null, 'Usuário desativado com sucesso');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Usuário não encontrado');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }
    }

    /**
     * RF14 - Reativar usuário
     * POST /api/v1/admin/usuarios/{id}/reativar
     */
    public function reativar(int $id): JsonResponse
    {
        try {
            $usuario = $this->service->reativarUsuario($id);
            return ApiResponse::success($usuario, 'Usuário reativado com sucesso');
            
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 404);
        }
    }

    /**
     * RF14 - Buscar usuário por matrícula
     * GET /api/v1/admin/usuarios/matricula/{matricula}
     */
    public function buscarPorMatricula(string $matricula): JsonResponse
    {
        $usuario = $this->service->buscarPorMatricula($matricula);

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado');
        }

        return ApiResponse::success($usuario, 'Usuário recuperado com sucesso');
    }

    /**
     * RF14 - Listar apenas bolsistas ativos
     * GET /api/v1/admin/usuarios/bolsistas
     */
    public function listarBolsistas(Request $request): JsonResponse
    {
        $filtros = $request->only(['turno']);
        $perPage = $request->integer('per_page', 15);
        
        $bolsistas = $this->service->listarBolsistas($filtros, $perPage);

        return ApiResponse::success(
            data: $bolsistas->items(),
            message: 'Bolsistas recuperados com sucesso'
        );
    }
}
