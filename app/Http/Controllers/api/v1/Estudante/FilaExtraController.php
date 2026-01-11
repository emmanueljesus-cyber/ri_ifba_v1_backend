<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\FilaExtra;
use App\Models\Refeicao;
use App\Enums\StatusFila;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller para fila de extras do estudante (RF06, RF07)
 */
class FilaExtraController extends Controller
{
    public function __construct(
        private NotificacaoService $notificacaoService
    ) {}

    /**
     * RF06 - Inscrever-se na fila de extras
     * POST /api/v1/estudante/fila-extras
     */
    public function inscrever(Request $request): JsonResponse
    {
        $request->validate([
            'turno' => 'required|in:almoco,jantar',
        ]);

        $user = $request->user();
        $turno = $request->input('turno');
        $hoje = now()->toDateString();

        // Verificar se é bolsista (bolsistas não podem entrar na fila)
        if ($user->bolsista) {
            return ApiResponse::standardError(
                'inscricao',
                'Bolsistas não precisam se inscrever na fila de extras.',
                422
            );
        }

        // Buscar refeição do dia
        $refeicao = Refeicao::whereHas('cardapio', fn($q) => $q->where('data_do_cardapio', $hoje))
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return ApiResponse::standardError(
                'refeicao',
                'Não há refeição cadastrada para este turno hoje.',
                404
            );
        }

        // Verificar se já está inscrito
        $inscricaoExistente = FilaExtra::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($inscricaoExistente) {
            return ApiResponse::standardError(
                'inscricao',
                'Você já está inscrito nesta fila.',
                422
            );
        }

        // Criar inscrição
        $inscricao = FilaExtra::create([
            'user_id' => $user->id,
            'refeicao_id' => $refeicao->id,
            'status_fila_extras' => StatusFila::INSCRITO,
            'inscrito_em' => now(),
        ]);

        $posicao = $inscricao->getPosicaoFila();

        // Notificar estudante
        $this->notificacaoService->notificarFilaConfirmada(
            userId: $user->id,
            posicao: $posicao,
            turno: $turno
        );

        return ApiResponse::standardCreated(
            data: [
                'inscricao_id' => $inscricao->id,
                'posicao' => $posicao,
                'turno' => $turno,
            ],
            meta: ['mensagem' => "Você está na posição {$posicao} da fila."]
        );
    }

    /**
     * RF06 - Cancelar inscrição na fila
     * DELETE /api/v1/estudante/fila-extras/{id}
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $inscricao = FilaExtra::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status_fila_extras', StatusFila::INSCRITO)
            ->first();

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada ou já processada.');
        }

        $inscricao->delete();

        return ApiResponse::standardSuccess(
            data: null,
            meta: ['mensagem' => 'Inscrição cancelada com sucesso.']
        );
    }

    /**
     * RF07 - Ver posição na fila
     * GET /api/v1/estudante/fila-extras/posicao
     */
    public function posicao(Request $request): JsonResponse
    {
        $user = $request->user();
        $turno = $request->input('turno', 'almoco');
        $hoje = now()->toDateString();

        // Buscar refeição do dia
        $refeicao = Refeicao::whereHas('cardapio', fn($q) => $q->where('data_do_cardapio', $hoje))
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return ApiResponse::standardSuccess([
                'inscrito' => false,
                'mensagem' => 'Não há refeição cadastrada para este turno hoje.',
            ]);
        }

        // Buscar inscrição do usuário
        $inscricao = FilaExtra::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if (!$inscricao) {
            return ApiResponse::standardSuccess([
                'inscrito' => false,
                'mensagem' => 'Você não está inscrito na fila.',
            ]);
        }

        // Calcular posição e vagas
        $posicao = $inscricao->getPosicaoFila();
        $totalVagas = config('ri.vagas_extras', 10); // Configurável
        $totalInscritos = FilaExtra::where('refeicao_id', $refeicao->id)
            ->where('status_fila_extras', StatusFila::INSCRITO)
            ->count();

        return ApiResponse::standardSuccess([
            'inscrito' => true,
            'inscricao_id' => $inscricao->id,
            'posicao' => $posicao,
            'total_vagas' => $totalVagas,
            'total_inscritos' => $totalInscritos,
            'dentro_das_vagas' => $posicao <= $totalVagas,
            'status' => $inscricao->status_fila_extras->value,
            'turno' => $turno,
        ]);
    }

    /**
     * RF06/RF07 - Lista inscrições ativas do estudante
     * GET /api/v1/estudante/fila-extras
     */
    public function minhasInscricoes(Request $request): JsonResponse
    {
        $user = $request->user();

        $inscricoes = FilaExtra::where('user_id', $user->id)
            ->with(['refeicao.cardapio'])
            ->orderByDesc('inscrito_em')
            ->limit(10)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'data' => $i->refeicao?->cardapio?->data_do_cardapio?->format('Y-m-d'),
                'turno' => $i->refeicao?->turno,
                'status' => $i->status_fila_extras->value,
                'posicao' => $i->status_fila_extras === StatusFila::INSCRITO ? $i->getPosicaoFila() : null,
                'inscrito_em' => $i->inscrito_em?->format('Y-m-d H:i:s'),
            ]);

        return ApiResponse::standardSuccess($inscricoes);
    }
}
