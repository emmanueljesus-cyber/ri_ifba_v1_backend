<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Listar todos os usuários
     * GET /api/v1/admin/usuarios
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filtro por perfil
        if ($request->has('perfil')) {
            $query->where('perfil', $request->perfil);
        }

        // Filtro por bolsista
        if ($request->has('bolsista')) {
            $query->where('bolsista', $request->boolean('bolsista'));
        }

        // Filtro por status (desligado)
        if ($request->has('desligado')) {
            $query->where('desligado', $request->boolean('desligado'));
        } else {
            // Por padrão, não mostrar desligados
            $query->where('desligado', false);
        }

        // Busca por nome ou matrícula
        if ($request->has('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'ILIKE', "%{$busca}%")
                  ->orWhere('matricula', 'ILIKE', "%{$busca}%")
                  ->orWhere('email', 'ILIKE', "%{$busca}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'nome');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $usuarios = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuários recuperados com sucesso',
            'data' => $usuarios->items(),
            'meta' => [
                'current_page' => $usuarios->currentPage(),
                'per_page' => $usuarios->perPage(),
                'total' => $usuarios->total(),
                'last_page' => $usuarios->lastPage(),
            ]
        ], 200);
    }

    /**
     * Criar novo usuário
     * POST /api/v1/admin/usuarios
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Hash da senha
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $usuario = User::create($data);

        return ApiResponse::created($usuario, 'Usuário criado com sucesso');
    }

    /**
     * Buscar usuário por ID
     * GET /api/v1/admin/usuarios/{id}
     */
    public function show(int $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado');
        }

        return ApiResponse::success(
            $usuario,
            'Usuário recuperado com sucesso'
        );
    }

    /**
     * Atualizar usuário
     * PUT/PATCH /api/v1/admin/usuarios/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado');
        }

        $data = $request->validated();

        // Hash da senha se fornecida
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $usuario->update($data);

        return ApiResponse::success(
            $usuario->fresh(),
            'Usuário atualizado com sucesso'
        );
    }

    /**
     * Desativar usuário (soft delete)
     * DELETE /api/v1/admin/usuarios/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado');
        }

        // Soft delete - marca como desligado
        $usuario->update(['desligado' => true]);

        return ApiResponse::success(
            null,
            'Usuário desativado com sucesso'
        );
    }

    /**
     * Reativar usuário
     * POST /api/v1/admin/usuarios/{id}/reativar
     */
    public function reativar(int $id): JsonResponse
    {
        $usuario = User::where('id', $id)->where('desligado', true)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado ou já está ativo');
        }

        $usuario->update(['desligado' => false]);

        return ApiResponse::success(
            $usuario->fresh(),
            'Usuário reativado com sucesso'
        );
    }

    /**
     * Buscar usuário por matrícula
     * GET /api/v1/admin/usuarios/matricula/{matricula}
     */
    public function buscarPorMatricula(string $matricula): JsonResponse
    {
        $usuario = User::where('matricula', $matricula)->first();

        if (!$usuario) {
            return ApiResponse::notFound('Usuário não encontrado');
        }

        return ApiResponse::success(
            $usuario,
            'Usuário recuperado com sucesso'
        );
    }

    /**
     * Listar apenas bolsistas ativos
     * GET /api/v1/admin/usuarios/bolsistas
     */
    public function listarBolsistas(Request $request): JsonResponse
    {
        $query = User::where('bolsista', true)
            ->where('desligado', false);

        // Filtro por turno
        if ($request->has('turno')) {
            $query->where('turno', $request->turno);
        }

        // Ordenação
        $query->orderBy('nome', 'asc');

        $bolsistas = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Bolsistas recuperados com sucesso',
            'data' => $bolsistas,
            'meta' => [
                'total' => $bolsistas->count()
            ]
        ], 200);
    }
}

