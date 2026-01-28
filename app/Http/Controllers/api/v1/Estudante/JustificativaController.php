<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Models\Justificativa;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Enums\TipoJustificativa;
use App\Enums\StatusJustificativa;
use App\Enums\StatusPresenca;
use App\Enums\TipoNotificacao;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JustificativaController extends Controller
{
    /**
     * Listar justificativas do estudante logado
     * GET /api/v1/estudante/justificativas
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'data' => null,
                'errors' => ['user' => ['Usuário não identificado.']],
                'meta' => [],
            ], 401);
        }

        $justificativas = Justificativa::with(['refeicao'])
            ->where('user_id', $userId)
            ->orderBy('enviado_em', 'desc')
            ->get()
            ->map(function ($just) {
                return [
                    'id' => $just->id,
                    'refeicao' => $just->refeicao ? [
                        'data' => $just->refeicao->data_do_cardapio->format('d/m/Y'),
                        'turno' => $just->refeicao->turno->value,
                    ] : null,
                    'tipo' => $just->tipo->value ?? $just->tipo,
                    'motivo' => $just->motivo,
                    'tem_anexo' => !empty($just->anexo),
                    'status' => $just->status?->value ?? 'pendente',
                    'enviado_em' => $just->enviado_em->format('d/m/Y H:i'),
                    'avaliado_em' => $just->avaliado_em?->format('d/m/Y H:i'),
                    'motivo_rejeicao' => $just->motivo_rejeicao,
                ];
            });

        return response()->json([
            'data' => $justificativas,
            'errors' => [],
            'meta' => ['total' => $justificativas->count()],
        ]);
    }

    /**
     * Enviar justificativa de falta
     * POST /api/v1/estudante/justificativas
     * 
     * REGRAS:
     * - Antecipada: Auto-aprovada, isenta o aluno automaticamente
     * - Posterior: Fica pendente para avaliação do admin
     */
    public function store(Request $request): JsonResponse
    {
        // Validação flexível: aceita refeicao_id OU (data + turno)
        $request->validate([
            'refeicao_id' => 'required_without_all:data,turno|exists:refeicoes,id',
            'data' => 'required_without:refeicao_id|date',
            'turno' => 'required_without:refeicao_id|in:almoco,jantar',
            'tipo' => 'required|in:antecipada,posterior',
            'motivo' => 'required|string|max:1000',
            'anexo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ], [
            'refeicao_id.exists' => 'Refeição não encontrada.',
            'data.required_without' => 'Informe a data da refeição.',
            'data.date' => 'Data inválida.',
            'turno.required_without' => 'Informe o turno da refeição.',
            'turno.in' => 'Turno deve ser almoço ou jantar.',
            'tipo.required' => 'Selecione o tipo de justificativa.',
            'tipo.in' => 'Tipo de justificativa inválido.',
            'motivo.required' => 'Informe o motivo da ausência.',
            'motivo.max' => 'O motivo não pode ter mais de 1000 caracteres.',
            'anexo.file' => 'O anexo deve ser um arquivo válido.',
            'anexo.mimes' => 'O anexo deve ser PDF, JPG, JPEG ou PNG.',
            'anexo.max' => 'O anexo não pode ter mais de 5MB.',
        ]);

        $userId = $request->user()?->id ?? $request->input('user_id');
        $tipo = $request->input('tipo');

        if (!$userId) {
            return response()->json([
                'data' => null,
                'errors' => ['user' => ['Usuário não identificado.']],
                'meta' => [],
            ], 401);
        }

        // Buscar refeição por ID ou por data+turno
        $refeicao = null;
        if ($request->has('refeicao_id')) {
            $refeicao = Refeicao::find($request->input('refeicao_id'));
        } else {
            // Buscar refeição pela data e turno
            $dataRefeicao = $request->input('data');
            $turnoRefeicao = $request->input('turno');

            $refeicao = Refeicao::where('data_do_cardapio', $dataRefeicao)
                ->where('turno', $turnoRefeicao)
                ->first();

            if (!$refeicao) {
                return response()->json([
                    'data' => null,
                    'errors' => ['data' => ["Não há cardápio cadastrado para esta data e turno."]],
                    'meta' => [],
                ], 422);
            }
        }

        $refeicaoId = $refeicao->id;
        $turno = $refeicao->turno->value;

        if (!$userId) {
            return response()->json([
                'data' => null,
                'errors' => ['user' => ['Usuário não identificado.']],
                'meta' => [],
            ], 401);
        }

        // Verificar se já existe justificativa para esta refeição
        $existente = Justificativa::where('user_id', $userId)
            ->where('refeicao_id', $refeicaoId)
            ->first();

        if ($existente) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Já existe uma justificativa para esta refeição.']],
                'meta' => ['justificativa_id' => $existente->id],
            ], 422);
        }

        // Verificar data da refeição
        $refeicao = Refeicao::find($refeicaoId);
        $turno = $refeicao->turno->value;

        // Obter datas como strings simples YYYY-MM-DD
        $hojeStr = date('Y-m-d'); // Data de hoje no servidor
        $dataRefeicaoStr = date('Y-m-d', strtotime($refeicao->data_do_cardapio));

        // DEBUG: Log para verificar os valores
        \Log::info('JUSTIFICATIVA DEBUG', [
            'hoje' => $hojeStr,
            'dataRefeicao' => $dataRefeicaoStr,
            'tipo' => $tipo,
            'comparacao' => [
                'igual' => ($dataRefeicaoStr === $hojeStr),
                'maior' => ($dataRefeicaoStr > $hojeStr),
                'menor' => ($dataRefeicaoStr < $hojeStr),
            ]
        ]);

        // REGRAS SIMPLIFICADAS:
        if ($tipo === 'antecipada') {
            // Antecipada: APENAS para HOJE ou FUTURO
            // Se a data da refeição for MENOR que hoje = passado = BLOQUEIA
            if ($dataRefeicaoStr < $hojeStr) {
                return response()->json([
                    'data' => null,
                    'errors' => ['tipo' => [
                        'Justificativa antecipada não pode ser enviada para datas passadas. Use justificativa posterior.',
                        'Debug: dataRefeicao=' . $dataRefeicaoStr . ', hoje=' . $hojeStr
                    ]],
                    'meta' => [],
                ], 422);
            }

            // Se for HOJE, verificar o prazo do turno
            if ($dataRefeicaoStr === $hojeStr) {
                $horaAtual = (int) date('Hi'); // Ex: 1430 para 14:30

                if ($turno === 'almoco' && $horaAtual > 1330) {
                    return response()->json([
                        'data' => null,
                        'errors' => ['tipo' => ['O prazo para justificativa antecipada de almoço encerrou às 13:30. Use justificativa posterior com atestado.']],
                        'meta' => [],
                    ], 422);
                }

                if ($turno === 'jantar' && $horaAtual > 1900) {
                    return response()->json([
                        'data' => null,
                        'errors' => ['tipo' => ['O prazo para justificativa antecipada de jantar encerrou às 19:00. Use justificativa posterior com atestado.']],
                        'meta' => [],
                    ], 422);
                }
            }
            // Se for FUTURA, está OK - permite
        }

        // Validação para justificativa POSTERIOR
        if ($tipo === 'posterior') {
            // Posterior: APENAS para PASSADO
            // Se a data da refeição for MAIOR OU IGUAL a hoje = BLOQUEIA
            if ($dataRefeicaoStr >= $hojeStr) {
                return response()->json([
                    'data' => null,
                    'errors' => ['tipo' => ['Justificativa posterior só pode ser enviada para datas passadas. Use justificativa antecipada.']],
                    'meta' => [],
                ], 422);
            }

            // REGRA: Justificativas posteriores devem ser enviadas até o PENÚLTIMO DIA LETIVO do mês
            $mesAusencia = (int) date('m', strtotime($dataRefeicaoStr));
            $anoAusencia = (int) date('Y', strtotime($dataRefeicaoStr));

            // Último dia do mês da ausência
            $ultimoDiaMes = date('Y-m-t', strtotime("$anoAusencia-$mesAusencia-01"));

            // Penúltimo dia letivo (simplificado: penúltimo dia útil)
            $penultimoDiaLetivo = $ultimoDiaMes;
            $diasUteis = 0;
            while ($diasUteis < 2) {
                $diaSemana = date('N', strtotime($penultimoDiaLetivo));
                if ($diaSemana < 6) { // 1-5 = Seg-Sex
                    $diasUteis++;
                }
                if ($diasUteis < 2) {
                    $penultimoDiaLetivo = date('Y-m-d', strtotime($penultimoDiaLetivo . ' -1 day'));
                }
            }

            // Verifica se ainda está no prazo
            if ($hojeStr > $penultimoDiaLetivo) {
                return response()->json([
                    'data' => null,
                    'errors' => ['tipo' => [
                        "O prazo para justificar ausências de " . date('m/Y', strtotime($dataRefeicaoStr)) .
                        " encerrou no dia " . date('d/m/Y', strtotime($penultimoDiaLetivo)) .
                        " (penúltimo dia letivo do mês)."
                    ]],
                    'meta' => [],
                ], 422);
            }
        }

        // Upload de anexo
        $anexoNome = null;
        if ($request->hasFile('anexo')) {
            $anexo = $request->file('anexo');
            $anexoNome = time() . '_' . $userId . '_' . $anexo->getClientOriginalName();
            $anexo->storeAs('justificativas', $anexoNome);
        }

        // Criar justificativa
        $justificativa = Justificativa::create([
            'user_id' => $userId,
            'refeicao_id' => $refeicaoId,
            'tipo' => $tipo,
            'motivo' => $request->input('motivo'),
            'anexo' => $anexoNome,
            'enviado_em' => now(),
            // REGRA: Antecipada = auto-aprovada, Posterior = pendente
            'status' => $tipo === 'antecipada' ? StatusJustificativa::APROVADA : StatusJustificativa::PENDENTE,
            'avaliado_por' => $tipo === 'antecipada' ? null : null, // Sistema auto-aprova antecipada
            'avaliado_em' => $tipo === 'antecipada' ? now() : null,
        ]);

        // Se antecipada, atualizar/criar presença como falta justificada
        if ($tipo === 'antecipada') {
            $presenca = Presenca::where('user_id', $userId)
                ->where('refeicao_id', $refeicaoId)
                ->first();

            if ($presenca) {
                // Atualizar existente
                $presenca->update([
                    'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
                ]);
            } else {
                // Criar nova presença como falta justificada
                Presenca::create([
                    'user_id' => $userId,
                    'refeicao_id' => $refeicaoId,
                    'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
                    'validado_em' => now(),
                ]);
            }
        }

        // Se posterior, notificar os administradores
        if ($tipo === 'posterior') {
            try {
                $notificacaoService = app(NotificacaoService::class);
                $user = User::find($userId);
                $dataFormatada = date('d/m/Y', strtotime($dataRefeicaoStr));

                // Buscar todos os admins para notificar
                $admins = User::where('perfil', 'admin')->get();

                \Log::info('NOTIFICACAO: Criando notificações para admins', [
                    'total_admins' => $admins->count(),
                    'aluno' => $user->nome ?? 'N/A',
                    'justificativa_id' => $justificativa->id,
                ]);

                foreach ($admins as $admin) {
                    $notificacao = $notificacaoService->criar(
                        userId: $admin->id,
                        tipo: TipoNotificacao::AVISO,
                        titulo: 'Nova Justificativa Pendente',
                        mensagem: "O aluno {$user->nome} enviou uma justificativa posterior para {$dataFormatada} ({$turno}). Aguardando sua avaliação.",
                        dados: [
                            'justificativa_id' => $justificativa->id,
                            'aluno_id' => $userId,
                            'aluno_nome' => $user->nome,
                        ]
                    );

                    \Log::info('NOTIFICACAO: Notificação criada', [
                        'notificacao_id' => $notificacao->id,
                        'admin_id' => $admin->id,
                        'admin_nome' => $admin->nome,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('NOTIFICACAO: Erro ao criar notificação', [
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $statusTexto = $tipo === 'antecipada'
            ? '✅ Justificativa antecipada aprovada automaticamente. Você está isento desta refeição. Não é necessário atestado.'
            : '⏳ Justificativa posterior enviada. Aguardando avaliação do administrador. Lembre-se de anexar atestado médico.';

        return response()->json([
            'data' => [
                'id' => $justificativa->id,
                'tipo' => $tipo,
                'status' => $justificativa->status->value,
                'auto_aprovada' => $tipo === 'antecipada',
            ],
            'errors' => [],
            'meta' => ['message' => $statusTexto],
        ], 201);
    }

    /**
     * Detalhes de uma justificativa
     * GET /api/v1/estudante/justificativas/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()?->id ?? $request->input('user_id');

        $justificativa = Justificativa::with(['refeicao'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$justificativa) {
            return response()->json([
                'data' => null,
                'errors' => ['justificativa' => ['Justificativa não encontrada.']],
                'meta' => [],
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $justificativa->id,
                'refeicao' => $justificativa->refeicao ? [
                    'data' => $justificativa->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $justificativa->refeicao->turno->value,
                ] : null,
                'tipo' => $justificativa->tipo->value ?? $justificativa->tipo,
                'motivo' => $justificativa->motivo,
                'tem_anexo' => !empty($justificativa->anexo),
                'status' => $justificativa->status?->value ?? 'pendente',
                'enviado_em' => $justificativa->enviado_em->format('d/m/Y H:i'),
                'avaliado_em' => $justificativa->avaliado_em?->format('d/m/Y H:i'),
                'motivo_rejeicao' => $justificativa->motivo_rejeicao,
            ],
            'errors' => [],
            'meta' => [],
        ]);
    }
}
