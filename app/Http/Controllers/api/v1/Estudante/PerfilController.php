<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para perfil do estudante (RF05)
 */
class PerfilController extends Controller
{
    /**
     * RF05 - Exibe dados do perfil do estudante
     * GET /api/v1/estudante/perfil
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::standardSuccess([
            'id' => $user->id,
            'matricula' => $user->matricula,
            'nome' => $user->nome,
            'email' => $user->email,
            'curso' => $user->curso,
            'turno' => $user->turno,
            'bolsista' => $user->bolsista,
            'preferencia_alimentar' => $user->preferencia_alimentar ?? 'comum',
            'perfil' => $user->perfil,
            'dias_cadastrados' => $user->getDiasCadastrados(),
        ]);
    }

    /**
     * RF05 - Atualiza preferência alimentar
     * PUT /api/v1/estudante/perfil/preferencia
     */
    public function atualizarPreferencia(Request $request): JsonResponse
    {
        $request->validate([
            'preferencia_alimentar' => 'required|in:comum,ovolactovegetariano',
        ]);

        $user = $request->user();
        $user->update([
            'preferencia_alimentar' => $request->input('preferencia_alimentar'),
        ]);

        return ApiResponse::standardSuccess(
            data: ['preferencia_alimentar' => $user->preferencia_alimentar],
            meta: ['mensagem' => 'Preferência atualizada. Entra em vigor no próximo dia útil.']
        );
    }

    /**
     * Atualiza dados básicos do perfil
     * PUT /api/v1/estudante/perfil
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'sometimes|email',
        ]);

        $user = $request->user();
        $user->update($request->only(['email']));

        return ApiResponse::standardSuccess(
            data: $user->only(['id', 'matricula', 'nome', 'email']),
            meta: ['atualizado' => true]
        );
    }
}
