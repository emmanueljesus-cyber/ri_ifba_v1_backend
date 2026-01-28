<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificacaoService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para notificações do administrador
 */
class NotificacaoController extends Controller
{
    public function __construct(
        private NotificacaoService $service
    ) {}

    /**
     * Helper: Obter usuário autenticado ou fallback para dev
     */
    private function getUser(Request $request)
    {
        $user = $request->user();

        // Fallback para desenvolvimento sem autenticação
        if (!$user && config('app.debug')) {
            $user = \App\Models\User::where('perfil', 'admin')->first();

            if (!$user) {
                throw new \Exception('Nenhum administrador encontrado no banco de dados.');
            }
        }

        return $user;
    }

    /**
     * Lista notificações do admin autenticado
     * GET /api/v1/admin/notificacoes
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $this->getUser($request)->id;
        $perPage = $request->integer('per_page', 15);

        $notificacoes = $this->service->listarDoUsuario($userId, $perPage);

        return ApiResponse::standardSuccess(
            data: $notificacoes->items(),
            meta: [
                'total' => $notificacoes->total(),
                'per_page' => $notificacoes->perPage(),
                'current_page' => $notificacoes->currentPage(),
                'nao_lidas' => $this->service->contarNaoLidas($userId),
            ]
        );
    }

    /**
     * Lista apenas notificações não lidas
     * GET /api/v1/admin/notificacoes/nao-lidas
     */
    public function naoLidas(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $userId = $user->id;

        \Log::info('ADMIN NOTIFICACOES: Buscando não lidas', [
            'user_id' => $userId,
            'user_nome' => $user->nome ?? 'N/A',
            'user_perfil' => $user->perfil ?? 'N/A',
        ]);

        $notificacoes = $this->service->naoLidasDoUsuario($userId);

        \Log::info('ADMIN NOTIFICACOES: Encontradas', [
            'total' => $notificacoes->count(),
        ]);

        return ApiResponse::standardSuccess(
            data: $notificacoes,
            meta: ['total' => $notificacoes->count()]
        );
    }

    /**
     * Conta notificações não lidas (para badge)
     * GET /api/v1/admin/notificacoes/contador
     */
    public function contador(Request $request): JsonResponse
    {
        $userId = $this->getUser($request)->id;
        $count = $this->service->contarNaoLidas($userId);

        return ApiResponse::standardSuccess(
            data: ['nao_lidas' => $count]
        );
    }

    /**
     * Marca uma notificação como lida
     * PATCH /api/v1/admin/notificacoes/{id}/ler
     */
    public function marcarComoLida(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUser($request)->id;
        $notificacao = $this->service->marcarComoLida($id, $userId);

        if (!$notificacao) {
            return ApiResponse::standardNotFound('notificacao', 'Notificação não encontrada.');
        }

        return ApiResponse::standardSuccess(
            data: $notificacao,
            meta: ['message' => 'Notificação marcada como lida.']
        );
    }

    /**
     * Marca todas as notificações como lidas
     * PATCH /api/v1/admin/notificacoes/marcar-todas-lidas
     */
    public function marcarTodasComoLidas(Request $request): JsonResponse
    {
        $userId = $this->getUser($request)->id;
        $count = $this->service->marcarTodasComoLidas($userId);

        return ApiResponse::standardSuccess(
            data: ['marcadas' => $count],
            meta: ['message' => "{$count} notificações marcadas como lidas."]
        );
    }

    /**
     * Exclui uma notificação
     * DELETE /api/v1/admin/notificacoes/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $this->getUser($request)->id;
        $notificacao = \App\Models\Notificacao::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$notificacao) {
            return ApiResponse::standardNotFound('notificacao', 'Notificação não encontrada.');
        }

        $notificacao->delete();

        return ApiResponse::standardSuccess(
            meta: ['message' => 'Notificação excluída.']
        );
    }
}
