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
     * Helper: Obter usuário autenticado ou fallback para dev
     */
    private function getUser(Request $request)
    {
        $user = $request->user();

        // Fallback para desenvolvimento sem autenticação
        if (!$user && config('app.debug')) {
            $user = \App\Models\User::where('perfil', 'estudante')
                ->where('bolsista', false)
                ->first();

            if (!$user) {
                throw new \Exception('Nenhum estudante não-bolsista encontrado no banco de dados.');
            }
        }

        return $user;
    }

    /**
     * RF06 - Inscrever-se na fila de extras
     * POST /api/v1/estudante/fila-extras
     */
    public function inscrever(Request $request): JsonResponse
    {
        $request->validate([
            'refeicao_id' => 'required|exists:refeicoes,id',
        ]);

        $user = $this->getUser($request);
        $refeicaoId = $request->input('refeicao_id');

        // Verificar se é bolsista (bolsistas não podem entrar na fila)
        if ($user->bolsista) {
            return ApiResponse::standardError(
                'inscricao',
                'Bolsistas não precisam se inscrever na fila de extras.',
                422
            );
        }

        // Buscar refeição
        $refeicao = Refeicao::with('cardapio')->find($refeicaoId);

        if (!$refeicao) {
            return ApiResponse::standardError(
                'refeicao',
                'Refeição não encontrada.',
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
            turno: $refeicao->turno->value
        );

        // Retornar dados completos da inscrição
        return ApiResponse::standardCreated([
            'id' => $inscricao->id,
            'user_id' => $inscricao->user_id,
            'refeicao_id' => $inscricao->refeicao_id,
            'data_inscricao' => $inscricao->inscrito_em->format('Y-m-d H:i:s'),
            'posicao' => $posicao,
            'confirmado' => false,
            'cancelado' => false,
            'refeicao' => [
                'id' => $refeicao->id,
                'turno' => $refeicao->turno->value,
                'data' => $refeicao->cardapio?->data_do_cardapio?->format('Y-m-d'),
                'cardapio' => $refeicao->cardapio ? [
                    'id' => $refeicao->cardapio->id,
                    'prato_principal' => $refeicao->cardapio->prato_principal_ptn01,
                ] : null,
            ],
        ]);
    }

    /**
     * RF06 - Cancelar inscrição na fila
     * DELETE /api/v1/estudante/fila-extras/{id}
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser($request);

        // Buscar inscrição do usuário (sem filtrar por status)
        $inscricao = FilaExtra::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$inscricao) {
            return ApiResponse::standardNotFound('inscricao', 'Inscrição não encontrada.');
        }

        // Verificar se já foi aprovada (confirmada pelo admin)
        if ($inscricao->status_fila_extras === StatusFila::APROVADO) {
            return ApiResponse::standardError(
                'inscricao',
                'Não é possível cancelar uma inscrição já confirmada.',
                422
            );
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
        $user = $this->getUser($request);
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
        $totalVagas = $refeicao->getVagasExtrasDisponiveis();
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
            'bolsistas_esperados' => $refeicao->getBolsistasEsperados(),
            'bolsistas_presentes' => $refeicao->getPresentes(),
        ]);
    }

    /**
     * RF06/RF07 - Lista inscrições ativas do estudante
     * GET /api/v1/estudante/fila-extras
     */
    public function minhasInscricoes(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $inscricoes = FilaExtra::where('user_id', $user->id)
            ->with(['refeicao.cardapio'])
            ->orderByDesc('inscrito_em')
            ->limit(10)
            ->get()
            ->map(function($i) {
                return [
                    'id' => $i->id,
                    'user_id' => $i->user_id,
                    'refeicao_id' => $i->refeicao_id,
                    'data_inscricao' => $i->inscrito_em?->format('Y-m-d H:i:s'),
                    'posicao' => $i->status_fila_extras === StatusFila::INSCRITO ? $i->getPosicaoFila() : 0,
                    'confirmado' => $i->status_fila_extras === StatusFila::APROVADO,
                    'cancelado' => $i->status_fila_extras === StatusFila::REJEITADO,
                    'refeicao' => $i->refeicao ? [
                        'id' => $i->refeicao->id,
                        'turno' => $i->refeicao->turno->value,
                        'data' => $i->refeicao->cardapio?->data_do_cardapio?->format('Y-m-d') ?? null,
                        'cardapio' => $i->refeicao->cardapio ? [
                            'id' => $i->refeicao->cardapio->id,
                            'prato_principal' => $i->refeicao->cardapio->prato_principal_ptn01,
                            'acompanhamento' => $i->refeicao->cardapio->acompanhamento_01,
                            'guarnicao' => $i->refeicao->cardapio->guarnicao,
                            'salada' => $i->refeicao->cardapio->salada,
                            'sobremesa' => $i->refeicao->cardapio->sobremesa,
                        ] : null,
                    ] : null,
                    'created_at' => $i->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $i->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

        return ApiResponse::standardSuccess($inscricoes);
    }

    /**
     * Lista refeições disponíveis hoje para não-bolsistas
     * GET /api/v1/estudante/fila-extras/disponiveis
     */
    public function refeicoesDisponiveis(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $hoje = now()->toDateString();
        $horaAtual = now();

        // Buscar todas as refeições de hoje
        $refeicoes = Refeicao::with(['cardapio', 'presencas'])
            ->whereHas('cardapio', fn($q) => $q->where('data_do_cardapio', $hoje))
            ->get();

        $refeicoesDisponiveis = $refeicoes->map(function($refeicao) use ($user, $horaAtual, $hoje) {
            $turno = $refeicao->turno->value;

            // Obter horários da configuração
            $horariosConfig = config('restaurante.refeicoes');
            $horarioFimStr = $horariosConfig[$turno]['fim'] ?? ($turno === 'almoco' ? '13:30' : '19:00');
            $horarioInicioStr = $horariosConfig[$turno]['inicio'] ?? ($turno === 'almoco' ? '11:00' : '17:30');

            $horarioFim = Carbon::parse($hoje . ' ' . $horarioFimStr);

            // Verificar se está no horário de exibição (até o fim do turno)
            $estaNoHorario = $horaAtual->lessThanOrEqualTo($horarioFim);

            // Usar os novos métodos do modelo Refeicao
            $bolsistasEsperados = $refeicao->getBolsistasEsperados();
            $bolsistasPresentes = $refeicao->getPresentes();
            $vagasExtrasDisponiveis = $refeicao->getVagasExtrasDisponiveis();
            $extrasInscritos = $refeicao->getExtrasInscritos();
            $vagasRestantes = max(0, $vagasExtrasDisponiveis - $extrasInscritos);

            // Verificar se o usuário já está inscrito
            $minhaInscricao = FilaExtra::where('user_id', $user->id)
                ->where('refeicao_id', $refeicao->id)
                ->first();

            return [
                'refeicao_id' => $refeicao->id,
                'turno' => $turno,
                'turno_label' => $turno === 'almoco' ? 'Almoço' : 'Jantar',
                'horario_inicio' => $horarioInicioStr,
                'horario_fim' => $horarioFimStr,
                'esta_no_horario' => $estaNoHorario,
                'pode_inscrever' => $estaNoHorario && !$minhaInscricao,
                'vagas_disponiveis' => $vagasRestantes,
                'vagas_extras_total' => $vagasExtrasDisponiveis,
                'extras_inscritos' => $extrasInscritos,
                'bolsistas_esperados' => $bolsistasEsperados,
                'bolsistas_presentes' => $bolsistasPresentes,
                'inscrito' => $minhaInscricao !== null,
                'inscricao_id' => $minhaInscricao?->id,
                'posicao_fila' => $minhaInscricao ? $minhaInscricao->getPosicaoFila() : null,
                'status_inscricao' => $minhaInscricao?->status_fila_extras?->value,
                'dentro_das_vagas' => $minhaInscricao ? $minhaInscricao->getPosicaoFila() <= $vagasExtrasDisponiveis : null,
                'cardapio' => [
                    'id' => $refeicao->cardapio->id,
                    'prato_principal_ptn01' => $refeicao->cardapio->prato_principal_ptn01,
                    'prato_principal_ptn02' => $refeicao->cardapio->prato_principal_ptn02,
                    'guarnicao' => $refeicao->cardapio->guarnicao,
                    'acompanhamento_01' => $refeicao->cardapio->acompanhamento_01,
                    'acompanhamento_02' => $refeicao->cardapio->acompanhamento_02,
                    'salada' => $refeicao->cardapio->salada,
                    'ovo_lacto_vegetariano' => $refeicao->cardapio->ovo_lacto_vegetariano,
                    'suco' => $refeicao->cardapio->suco,
                    'sobremesa' => $refeicao->cardapio->sobremesa,
                ],
            ];
        })->filter(fn($r) => $r['esta_no_horario']); // Retornar apenas as que estão no horário

        return ApiResponse::standardSuccess([
            'refeicoes' => $refeicoesDisponiveis->values(),
            'data' => $hoje,
            'hora_atual' => $horaAtual->format('H:i:s'),
        ]);
    }

}
