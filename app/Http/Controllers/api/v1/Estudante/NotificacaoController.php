<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Services\NotificacaoService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para notificações do estudante
 */
class NotificacaoController extends Controller
{
    public function __construct(
        private NotificacaoService $service
    ) {}

    /**
     * Lista notificações do usuário autenticado
     * GET /api/v1/estudante/notificacoes
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
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
     * GET /api/v1/estudante/notificacoes/nao-lidas
     */
    public function naoLidas(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notificacoes = $this->service->naoLidasDoUsuario($userId);

        return ApiResponse::standardSuccess(
            data: $notificacoes,
            meta: ['total' => $notificacoes->count()]
        );
    }

    /**
     * Conta notificações não lidas (para badge)
     * GET /api/v1/estudante/notificacoes/contador
     */
    public function contador(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->service->contarNaoLidas($userId);

        return ApiResponse::standardSuccess(['count' => $count]);
    }

    /**
     * Marca uma notificação como lida
     * POST /api/v1/estudante/notificacoes/{id}/ler
     */
    public function marcarComoLida(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $notificacao = $this->service->marcarComoLida($id, $userId);

        if (!$notificacao) {
            return ApiResponse::standardNotFound('notificação', 'Notificação não encontrada');
        }

        return ApiResponse::standardSuccess($notificacao);
    }

    /**
     * Marca todas as notificações como lidas
     * POST /api/v1/estudante/notificacoes/ler-todas
     */
    public function marcarTodasComoLidas(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->service->marcarTodasComoLidas($userId);

        return ApiResponse::standardSuccess(
            data: null,
            meta: ['marcadas' => $count]
        );
    }
}
