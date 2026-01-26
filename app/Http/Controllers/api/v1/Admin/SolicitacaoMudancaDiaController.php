<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
use App\Models\SolicitacaoMudancaDia;
use App\Models\Notificacao;
use App\Enums\TipoNotificacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller para gerenciamento de solicitações de mudança de dias
 */
class SolicitacaoMudancaDiaController extends Controller
{
    /**
     * Lista solicitações de mudança de dias
     * GET /api/v1/admin/solicitacoes-mudanca-dias
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', 'pendente');

        $query = SolicitacaoMudancaDia::with(['user:id,nome,matricula,curso,turno_refeicao', 'avaliador:id,nome'])
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'todos') {
            $query->where('status', $status);
        }

        $solicitacoes = $query->get();

        $data = $solicitacoes->map(function ($s) {
            return [
                'id' => $s->id,
                'user' => [
                    'id' => $s->user->id,
                    'nome' => $s->user->nome,
                    'matricula' => $s->user->matricula,
                    'curso' => $s->user->curso,
                    'turno_refeicao' => $s->user->turno_refeicao,
                ],
                'dias_atuais' => $s->dias_atuais,
                'dias_atuais_texto' => $s->getDiasAtuaisTexto(),
                'dias_solicitados' => $s->dias_solicitados,
                'dias_solicitados_texto' => $s->getDiasSolicitadosTexto(),
                'motivo' => $s->motivo,
                'status' => $s->status,
                'motivo_rejeicao' => $s->motivo_rejeicao,
                'avaliado_por' => $s->avaliador?->nome,
                'avaliado_em' => $s->avaliado_em ? DateHelper::formatarDataHoraBR($s->avaliado_em) : null,
                'created_at' => DateHelper::formatarDataHoraBR($s->created_at),
            ];
        });

        return ApiResponse::standardSuccess(
            data: $data,
            meta: [
                'total' => $solicitacoes->count(),
                'pendentes' => SolicitacaoMudancaDia::pendentes()->count(),
            ]
        );
    }

    /**
     * Aprovar solicitação
     * PATCH /api/v1/admin/solicitacoes-mudanca-dias/{id}/aprovar
     */
    public function aprovar(Request $request, int $id): JsonResponse
    {
        $solicitacao = SolicitacaoMudancaDia::with('user')->findOrFail($id);

        if (!$solicitacao->isPendente()) {
            return ApiResponse::standardError('status', 'Esta solicitação já foi avaliada.', 400);
        }

        $solicitacao->aprovar($request->user()?->id ?? 1);

        // Criar notificação para o estudante
        Notificacao::create([
            'user_id' => $solicitacao->user_id,
            'titulo' => 'Solicitação Aprovada',
            'mensagem' => 'Sua solicitação de mudança de dias foi aprovada. Os novos dias já estão ativos.',
            'tipo' => TipoNotificacao::SOLICITACAO_APROVADA,
        ]);

        return ApiResponse::standardSuccess(
            data: ['id' => $solicitacao->id],
            meta: ['message' => 'Solicitação aprovada com sucesso.']
        );
    }

    /**
     * Rejeitar solicitação
     * PATCH /api/v1/admin/solicitacoes-mudanca-dias/{id}/rejeitar
     */
    public function rejeitar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo_rejeicao' => 'required|string|min:5|max:500',
        ], [
            'motivo_rejeicao.required' => 'O motivo da rejeição é obrigatório.',
            'motivo_rejeicao.min' => 'O motivo deve ter pelo menos 5 caracteres.',
        ]);

        $solicitacao = SolicitacaoMudancaDia::with('user')->findOrFail($id);

        if (!$solicitacao->isPendente()) {
            return ApiResponse::standardError('status', 'Esta solicitação já foi avaliada.', 400);
        }

        $solicitacao->rejeitar($request->user()?->id ?? 1, $request->input('motivo_rejeicao'));

        // Criar notificação para o estudante
        Notificacao::create([
            'user_id' => $solicitacao->user_id,
            'titulo' => 'Solicitação Rejeitada',
            'mensagem' => 'Sua solicitação de mudança de dias foi rejeitada. Motivo: ' . $request->input('motivo_rejeicao'),
            'tipo' => TipoNotificacao::SOLICITACAO_REJEITADA,
        ]);

        return ApiResponse::standardSuccess(
            data: ['id' => $solicitacao->id],
            meta: ['message' => 'Solicitação rejeitada.']
        );
    }
}
