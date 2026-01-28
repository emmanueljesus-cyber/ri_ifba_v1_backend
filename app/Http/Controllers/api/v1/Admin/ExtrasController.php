<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\FilaExtra;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Enums\StatusFila;
use App\Enums\StatusPresenca;
use App\Enums\TipoNotificacao;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

/**
 * Controller para gerenciamento de fila de extras pelo admin
 *
 * Responsabilidades:
 * - Listar todas as inscrições de extras
 * - Aprovar/rejeitar inscrições
 * - Confirmar presença de extras
 * - Remover inscrições
 * - Estatísticas de extras
 */
class ExtrasController extends Controller
{
    public function __construct(
        private NotificacaoService $notificacaoService
    ) {}

    /**
     * Lista todas as inscrições na fila de extras
     * GET /api/v1/admin/extras
     *
     * Query params:
     * - data: Data específica (YYYY-MM-DD)
     * - turno: almoco/jantar
     * - status: inscrito/aprovado/rejeitado
     * - per_page: Itens por página (default: 20)
     */
    public function index(Request $request): JsonResponse
    {
        $query = FilaExtra::with(['user:id,nome,matricula,email,foto_perfil', 'refeicao.cardapio'])
            ->orderBy('inscrito_em', 'asc');

        // Filtro por data
        if ($request->has('data')) {
            $query->whereHas('refeicao.cardapio', function ($q) use ($request) {
                $q->whereDate('data_do_cardapio', $request->input('data'));
            });
        }

        // Filtro por data_inicio e data_fim
        if ($request->has('data_inicio')) {
            $query->whereHas('refeicao.cardapio', function ($q) use ($request) {
                $q->whereDate('data_do_cardapio', '>=', $request->input('data_inicio'));
            });
        }
        if ($request->has('data_fim')) {
            $query->whereHas('refeicao.cardapio', function ($q) use ($request) {
                $q->whereDate('data_do_cardapio', '<=', $request->input('data_fim'));
            });
        }

        // Filtro por turno
        if ($request->has('turno')) {
            $query->whereHas('refeicao', function ($q) use ($request) {
                $q->where('turno', $request->input('turno'));
            });
        }

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status_fila_extras', $request->input('status'));
        }

        $perPage = $request->integer('per_page', 50);
        $inscricoes = $query->paginate($perPage);

        // Formatar dados
        $dados = $inscricoes->map(function ($inscricao) {
            return [
                'id' => $inscricao->id,
                'user' => [
                    'id' => $inscricao->user->id,
                    'nome' => $inscricao->user->nome,
                    'matricula' => $inscricao->user->matricula,
                    'email' => $inscricao->user->email,
                    'foto' => $inscricao->user->foto_url,
                ],
                'refeicao' => [
                    'id' => $inscricao->refeicao->id,
                    'turno' => $inscricao->refeicao->turno->value ?? $inscricao->refeicao->turno,
                    'data' => $inscricao->refeicao->cardapio?->data_do_cardapio?->format('Y-m-d'),
                ],
                'status' => $inscricao->status_fila_extras->value ?? $inscricao->status_fila_extras,
                'inscrito_em' => $inscricao->inscrito_em?->format('Y-m-d H:i:s'),
                'posicao' => $inscricao->getPosicaoFila(),
            ];
        });

        return ApiResponse::standardSuccess(
            data: $dados,
            meta: [
                'total' => $inscricoes->total(),
                'per_page' => $inscricoes->perPage(),
                'current_page' => $inscricoes->currentPage(),
                'last_page' => $inscricoes->lastPage(),
            ]
        );
    }

    /**
     * Lista inscrições do dia atual
     * GET /api/v1/admin/extras/hoje
     */
    public function hoje(Request $request): JsonResponse
    {
        $turno = $request->input('turno');
        $hoje = now()->toDateString();

        $query = FilaExtra::with(['user:id,nome,matricula,email,foto_perfil', 'refeicao.cardapio'])
            ->whereHas('refeicao.cardapio', function ($q) use ($hoje) {
                $q->whereDate('data_do_cardapio', $hoje);
            })
            ->orderBy('inscrito_em', 'asc');

        if ($turno) {
            $query->whereHas('refeicao', fn($q) => $q->where('turno', $turno));
        }

        $inscricoes = $query->get();

        // Estatísticas do dia
        $estatisticas = [
            'total_inscritos' => $inscricoes->count(),
            'aprovados' => $inscricoes->where('status_fila_extras', StatusFila::APROVADO)->count(),
            'aguardando' => $inscricoes->where('status_fila_extras', StatusFila::INSCRITO)->count(),
            'rejeitados' => $inscricoes->where('status_fila_extras', StatusFila::REJEITADO)->count(),
        ];

        // Filtrar apenas inscritos (aguardando) e aprovados para a lista principal
        // Rejeitados não aparecem na fila ativa
        $inscricoesAtivas = $inscricoes->filter(function($inscricao) {
            $status = $inscricao->status_fila_extras instanceof StatusFila
                ? $inscricao->status_fila_extras
                : StatusFila::tryFrom($inscricao->status_fila_extras);
            return $status !== StatusFila::REJEITADO;
        });

        $dados = $inscricoesAtivas->values()->map(function ($inscricao, $index) {
            $status = $inscricao->status_fila_extras instanceof StatusFila
                ? $inscricao->status_fila_extras
                : StatusFila::tryFrom($inscricao->status_fila_extras);

            // Posição só faz sentido para aguardando (inscrito)
            $posicao = $status === StatusFila::INSCRITO ? $inscricao->getPosicaoFila() : null;

            return [
                'id' => $inscricao->id,
                'user' => [
                    'id' => $inscricao->user->id,
                    'nome' => $inscricao->user->nome,
                    'matricula' => $inscricao->user->matricula,
                    'foto' => $inscricao->user->foto_url,
                ],
                'turno' => $inscricao->refeicao->turno->value ?? $inscricao->refeicao->turno,
                'status' => $status?->value ?? $inscricao->status_fila_extras,
                'inscrito_em' => $inscricao->inscrito_em?->format('Y-m-d H:i:s'),
                'posicao' => $posicao,
            ];
        });

        return ApiResponse::standardSuccess(
            data: $dados,
            meta: [
                'data' => $hoje,
                'estatisticas' => $estatisticas,
                'vagas' => $this->calcularVagasExtras($hoje, $turno),
            ]
        );
    }

    /**
     * Calcula informações de vagas extras para uma data/turno
     */
    private function calcularVagasExtras(string $data, ?string $turno = null): array
    {
        $turnos = $turno ? [$turno] : ['almoco', 'jantar'];
        $resultado = [];

        foreach ($turnos as $t) {
            $refeicao = Refeicao::with('cardapio')
                ->whereHas('cardapio', fn($q) => $q->whereDate('data_do_cardapio', $data))
                ->where('turno', $t)
                ->first();

            if ($refeicao) {
                $resultado[$t] = [
                    'bolsistas_esperados' => $refeicao->getBolsistasEsperados(),
                    'bolsistas_presentes' => $refeicao->getPresentes(),
                    'vagas_extras_total' => $refeicao->getVagasExtrasDisponiveis(),
                    'extras_inscritos' => $refeicao->getExtrasInscritos(),
                    'vagas_restantes' => max(0, $refeicao->getVagasExtrasDisponiveis() - $refeicao->getExtrasInscritos()),
                ];
            }
        }

        return $resultado;
    }

    /**
     * Aprovar inscrição na fila
     * POST /api/v1/admin/extras/{id}/aprovar
     */
    public function aprovar(Request $request, int $id): JsonResponse
    {
        $inscricao = FilaExtra::with(['user', 'refeicao.cardapio'])->find($id);

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada.');
        }

        if ($inscricao->status_fila_extras !== StatusFila::INSCRITO) {
            return ApiResponse::standardError(
                'inscricao',
                'Esta inscrição já foi processada.',
                422
            );
        }

        $inscricao->update(['status_fila_extras' => StatusFila::APROVADO]);

        // Notificar estudante
        $this->notificacaoService->criar(
            userId: $inscricao->user_id,
            tipo: TipoNotificacao::FILA_APROVADA,
            titulo: 'Inscrição Aprovada',
            mensagem: "Sua inscrição para refeição extra foi aprovada! Turno: {$inscricao->refeicao->turno->value}"
        );

        return ApiResponse::standardSuccess([
            'id' => $inscricao->id,
            'status' => 'aprovado',
            'mensagem' => 'Inscrição aprovada com sucesso.',
        ]);
    }

    /**
     * Rejeitar inscrição na fila
     * POST /api/v1/admin/extras/{id}/rejeitar
     */
    public function rejeitar(Request $request, int $id): JsonResponse
    {
        $inscricao = FilaExtra::with(['user', 'refeicao.cardapio'])->find($id);

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada.');
        }

        if ($inscricao->status_fila_extras !== StatusFila::INSCRITO) {
            return ApiResponse::standardError(
                'inscricao',
                'Esta inscrição já foi processada.',
                422
            );
        }

        $motivo = $request->input('motivo', 'Não há vagas disponíveis.');

        $inscricao->update(['status_fila_extras' => StatusFila::REJEITADO]);

        // Notificar estudante
        $this->notificacaoService->criar(
            userId: $inscricao->user_id,
            tipo: TipoNotificacao::FILA_REJEITADA,
            titulo: 'Inscrição Não Aprovada',
            mensagem: "Sua inscrição para refeição extra não foi aprovada. Motivo: {$motivo}"
        );

        return ApiResponse::standardSuccess([
            'id' => $inscricao->id,
            'status' => 'rejeitado',
            'mensagem' => 'Inscrição rejeitada com sucesso.',
        ]);
    }

    /**
     * Confirmar presença de extra (estudante compareceu)
     * POST /api/v1/admin/extras/{id}/confirmar-presenca
     */
    public function confirmarPresenca(Request $request, int $id): JsonResponse
    {
        $inscricao = FilaExtra::with(['user', 'refeicao'])->find($id);

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada.');
        }

        if ($inscricao->status_fila_extras !== StatusFila::APROVADO) {
            return ApiResponse::standardError(
                'inscricao',
                'Apenas inscrições aprovadas podem ter presença confirmada.',
                422
            );
        }

        // Verificar se já existe presença
        $presencaExistente = Presenca::where('user_id', $inscricao->user_id)
            ->where('refeicao_id', $inscricao->refeicao_id)
            ->first();

        if ($presencaExistente) {
            return ApiResponse::standardError(
                'presenca',
                'Presença já registrada para este estudante.',
                422
            );
        }

        // Criar presença como EXTRA
        Presenca::create([
            'user_id' => $inscricao->user_id,
            'refeicao_id' => $inscricao->refeicao_id,
            'status_da_presenca' => StatusPresenca::EXTRA,
            'validado_em' => now(),
            'registrado_em' => now(),
        ]);

        return ApiResponse::standardSuccess([
            'mensagem' => 'Presença confirmada com sucesso.',
            'inscricao_id' => $inscricao->id,
            'estudante' => $inscricao->user->nome,
        ]);
    }

    /**
     * Remover inscrição da fila
     * DELETE /api/v1/admin/extras/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $inscricao = FilaExtra::find($id);

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada.');
        }

        $inscricao->delete();

        return ApiResponse::standardSuccess(
            data: null,
            meta: ['mensagem' => 'Inscrição removida com sucesso.']
        );
    }

    /**
     * Estatísticas gerais de extras
     * GET /api/v1/admin/extras/estatisticas
     */
    public function estatisticas(Request $request): JsonResponse
    {
        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->toDateString());
        $dataFim = $request->input('data_fim', now()->endOfMonth()->toDateString());

        $refeicaoIds = Refeicao::whereHas('cardapio', function ($q) use ($dataInicio, $dataFim) {
            $q->whereBetween('data_do_cardapio', [$dataInicio, $dataFim]);
        })->pluck('id');

        $inscritos = FilaExtra::whereIn('refeicao_id', $refeicaoIds)->count();
        $aprovados = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_fila_extras', StatusFila::APROVADO)
            ->count();
        $rejeitados = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_fila_extras', StatusFila::REJEITADO)
            ->count();
        $aguardando = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->where('status_fila_extras', StatusFila::INSCRITO)
            ->count();

        $taxaAprovacao = $inscritos > 0
            ? round(($aprovados / $inscritos) * 100, 1)
            : 0;

        // Top estudantes que mais pedem extra
        $topEstudantes = FilaExtra::whereIn('refeicao_id', $refeicaoIds)
            ->selectRaw('user_id, COUNT(*) as total_inscricoes')
            ->groupBy('user_id')
            ->orderByDesc('total_inscricoes')
            ->limit(10)
            ->with('user:id,nome,matricula')
            ->get()
            ->map(fn($item) => [
                'nome' => $item->user->nome ?? 'N/A',
                'matricula' => $item->user->matricula ?? 'N/A',
                'total_inscricoes' => $item->total_inscricoes,
            ]);

        return ApiResponse::standardSuccess([
            'resumo' => [
                'total_inscritos' => $inscritos,
                'aprovados' => $aprovados,
                'rejeitados' => $rejeitados,
                'aguardando' => $aguardando,
                'taxa_aprovacao' => $taxaAprovacao . '%',
            ],
            'top_estudantes' => $topEstudantes,
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
            ],
        ]);
    }

    /**
     * Aprovar múltiplas inscrições em lote
     * POST /api/v1/admin/extras/aprovar-lote
     */
    public function aprovarLote(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:filas_extras,id',
        ]);

        $ids = $request->input('ids');
        $aprovados = 0;
        $erros = [];

        foreach ($ids as $id) {
            $inscricao = FilaExtra::with(['user', 'refeicao'])->find($id);

            if (!$inscricao) {
                $erros[] = "ID {$id}: Inscrição não encontrada.";
                continue;
            }

            if ($inscricao->status_fila_extras !== StatusFila::INSCRITO) {
                $erros[] = "ID {$id}: Já processada.";
                continue;
            }

            $inscricao->update(['status_fila_extras' => StatusFila::APROVADO]);

            $this->notificacaoService->criar(
                userId: $inscricao->user_id,
                tipo: TipoNotificacao::FILA_APROVADA,
                titulo: 'Inscrição Aprovada',
                mensagem: "Sua inscrição para refeição extra foi aprovada!"
            );

            $aprovados++;
        }

        return ApiResponse::standardSuccess(
            data: [
                'aprovados' => $aprovados,
                'erros' => $erros,
            ],
            meta: ['mensagem' => "{$aprovados} inscrições aprovadas."]
        );
    }

    /**
     * Exportar relatório de fila de extras em Excel
     * GET /api/v1/admin/extras/exportar
     */
    public function exportar(Request $request)
    {
        $query = FilaExtra::with(['user:id,nome,matricula,email,curso', 'refeicao.cardapio'])
            ->orderBy('inscrito_em', 'asc');

        // Filtro por período
        if ($request->has('data_inicio')) {
            $dataInicio = Carbon::parse($request->input('data_inicio'))->startOfDay();
            $query->whereHas('refeicao.cardapio', function ($q) use ($dataInicio) {
                $q->where('data_do_cardapio', '>=', $dataInicio);
            });
        }

        if ($request->has('data_fim')) {
            $dataFim = Carbon::parse($request->input('data_fim'))->endOfDay();
            $query->whereHas('refeicao.cardapio', function ($q) use ($dataFim) {
                $q->where('data_do_cardapio', '<=', $dataFim);
            });
        }

        // Filtro por turno
        if ($request->has('turno')) {
            $turno = $request->input('turno');
            $query->whereHas('refeicao', fn($q) => $q->where('turno', $turno));
        }

        $inscricoes = $query->get();

        // Criar array para exportação
        $dados = $inscricoes->map(function ($inscricao) {
            $status = $inscricao->status_fila_extras instanceof StatusFila
                ? $inscricao->status_fila_extras->value
                : $inscricao->status_fila_extras;

            $turno = $inscricao->refeicao->turno instanceof \BackedEnum
                ? $inscricao->refeicao->turno->value
                : $inscricao->refeicao->turno;

            return [
                'Nome' => $inscricao->user->nome,
                'Matrícula' => $inscricao->user->matricula,
                'E-mail' => $inscricao->user->email,
                'Curso' => $inscricao->user->curso ?? '-',
                'Data' => $inscricao->refeicao->cardapio?->data_do_cardapio?->format('d/m/Y') ?? '-',
                'Turno' => $turno === 'almoco' ? 'Almoço' : 'Jantar',
                'Status' => match($status) {
                    'inscrito' => 'Aguardando',
                    'aprovado' => 'Confirmado',
                    'rejeitado' => 'Rejeitado',
                    default => $status
                },
                'Inscrito em' => $inscricao->inscrito_em?->format('d/m/Y H:i') ?? '-',
            ];
        })->toArray();

        // Usar Maatwebsite Excel para exportar
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FilaExtrasExport($dados),
            'relatorio_fila_extras_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
