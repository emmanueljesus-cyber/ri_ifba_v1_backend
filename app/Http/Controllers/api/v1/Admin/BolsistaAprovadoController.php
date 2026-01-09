<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bolsista;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento manual de bolsistas aprovados
 * Permite adicionar/editar/remover matrículas da lista de bolsistas
 */
class BolsistaAprovadoController extends Controller
{
    /**
     * Lista todos os bolsistas aprovados
     * GET /api/v1/admin/bolsistas-aprovados
     */
    public function index(Request $request): JsonResponse
    {
        $query = Bolsista::with('user')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        if ($request->has('vinculado')) {
            if ($request->boolean('vinculado')) {
                $query->whereNotNull('user_id');
            } else {
                $query->whereNull('user_id');
            }
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('matricula', 'like', "%{$search}%")
                  ->orWhere('nome', 'like', "%{$search}%");
            });
        }

        $bolsistas = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $bolsistas->map(function ($b) {
                return [
                    'id' => $b->id,
                    'matricula' => $b->matricula,
                    'nome' => $b->nome,
                    'curso' => $b->curso,
                    'turno' => $b->turno,
                    'dias_semana' => $b->dias_semana,
                    'ativo' => $b->ativo,
                    'vinculado' => $b->user_id !== null,
                    'user_id' => $b->user_id,
                    'user_nome' => $b->user?->nome,
                    'vinculado_em' => $b->vinculado_em?->format('d/m/Y H:i'),
                    'created_at' => $b->created_at->format('d/m/Y H:i'),
                ];
            }),
            'errors' => [],
            'meta' => [
                'total' => $bolsistas->total(),
                'per_page' => $bolsistas->perPage(),
                'current_page' => $bolsistas->currentPage(),
                'last_page' => $bolsistas->lastPage(),
                'pendentes' => Bolsista::whereNull('user_id')->where('ativo', true)->count(),
                'vinculados' => Bolsista::whereNotNull('user_id')->count(),
                'inativos' => Bolsista::where('ativo', false)->count(),
            ],
        ]);
    }

    /**
     * Adicionar bolsista manualmente
     * POST /api/v1/admin/bolsistas-aprovados
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'matricula' => 'required|string|unique:bolsistas,matricula',
            'nome' => 'nullable|string|max:255',
            'curso' => 'nullable|string|max:255',
            'turno' => 'nullable|in:almoco,jantar',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
        ]);

        $bolsista = Bolsista::create([
            'matricula' => $request->input('matricula'),
            'nome' => $request->input('nome'),
            'curso' => $request->input('curso'),
            'turno' => $request->input('turno'),
            'dias_semana' => $request->input('dias_semana', [1, 2, 3, 4, 5]),
            'ativo' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'status' => 'Aguardando cadastro do estudante',
            ],
            'errors' => [],
            'meta' => ['message' => '✅ Bolsista adicionado à lista de aprovados.'],
        ], 201);
    }

    /**
     * Detalhes de um bolsista
     * GET /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function show(int $id): JsonResponse
    {
        $bolsista = Bolsista::with('user')->find($id);

        if (!$bolsista) {
            return response()->json([
                'data' => null,
                'errors' => ['bolsista' => ['Bolsista não encontrado.']],
                'meta' => [],
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'curso' => $bolsista->curso,
                'turno' => $bolsista->turno,
                'dias_semana' => $bolsista->dias_semana,
                'ativo' => $bolsista->ativo,
                'vinculado' => $bolsista->user_id !== null,
                'user' => $bolsista->user ? [
                    'id' => $bolsista->user->id,
                    'nome' => $bolsista->user->nome,
                    'email' => $bolsista->user->email,
                ] : null,
                'vinculado_em' => $bolsista->vinculado_em?->format('d/m/Y H:i'),
            ],
            'errors' => [],
            'meta' => [],
        ]);
    }

    /**
     * Atualizar bolsista
     * PUT /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $bolsista = Bolsista::find($id);

        if (!$bolsista) {
            return response()->json([
                'data' => null,
                'errors' => ['bolsista' => ['Bolsista não encontrado.']],
                'meta' => [],
            ], 404);
        }

        $request->validate([
            'matricula' => 'sometimes|string|unique:bolsistas,matricula,' . $id,
            'nome' => 'nullable|string|max:255',
            'curso' => 'nullable|string|max:255',
            'turno' => 'nullable|in:almoco,jantar',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'ativo' => 'sometimes|boolean',
        ]);

        $bolsista->update($request->only([
            'matricula', 'nome', 'curso', 'turno', 'dias_semana', 'ativo'
        ]));

        return response()->json([
            'data' => [
                'id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
            ],
            'errors' => [],
            'meta' => ['message' => '✅ Bolsista atualizado.'],
        ]);
    }

    /**
     * Desativar bolsista (não deleta, apenas marca como inativo)
     * DELETE /api/v1/admin/bolsistas-aprovados/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $bolsista = Bolsista::find($id);

        if (!$bolsista) {
            return response()->json([
                'data' => null,
                'errors' => ['bolsista' => ['Bolsista não encontrado.']],
                'meta' => [],
            ], 404);
        }

        // Desativa ao invés de deletar
        $bolsista->update(['ativo' => false]);

        // Se já estava vinculado a um usuário, desmarcar como bolsista
        if ($bolsista->user_id) {
            $bolsista->user->update(['bolsista' => false]);
        }

        return response()->json([
            'data' => ['id' => $id],
            'errors' => [],
            'meta' => ['message' => '✅ Bolsista desativado.'],
        ]);
    }

    /**
     * Reativar bolsista
     * POST /api/v1/admin/bolsistas-aprovados/{id}/reativar
     */
    public function reativar(int $id): JsonResponse
    {
        $bolsista = Bolsista::find($id);

        if (!$bolsista) {
            return response()->json([
                'data' => null,
                'errors' => ['bolsista' => ['Bolsista não encontrado.']],
                'meta' => [],
            ], 404);
        }

        $bolsista->update(['ativo' => true]);

        // Se estava vinculado a um usuário, remarcar como bolsista
        if ($bolsista->user_id) {
            $bolsista->user->update(['bolsista' => true]);
        }

        return response()->json([
            'data' => ['id' => $id],
            'errors' => [],
            'meta' => ['message' => '✅ Bolsista reativado.'],
        ]);
    }
}
