<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BolsistaImportRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\BolsistaResource;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;
use App\Models\User;
use App\Models\Presenca;
use App\Enums\StatusPresenca;
use App\Services\BolsistaImportService;
use App\Services\PresencaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exceptions\BusinessException;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller para gerenciamento de bolsistas (RF09, RF13, RF15)
 * 
 * Responsabilidades:
 * - Lista de bolsistas do dia/todos
 * - Busca e confirmação de presença (QR Code + manual)
 * - Importação de lista de bolsistas
 */
class BolsistaController extends Controller
{
    public function __construct(
        protected PresencaService $presencaService
    ) {}

    /**
     * RF09 - Lista bolsistas do dia por turno
     * GET /api/v1/admin/bolsistas/dia
     */
    public function bolsistasDoDia(Request $request): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');
        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar bolsistas com presenças
        [$bolsistas, $refeicao] = $this->buscarBolsistasComPresencas($data, $turno, $diaSemana);

        // Estatísticas
        $stats = $this->calcularEstatisticas($bolsistas);

        return ApiResponse::standardSuccess(
            data: BolsistaResource::collection($bolsistas),
            meta: [
                'data' => DateHelper::formatarDataBR($data),
                'data_iso' => $data,
                'dia_semana' => $diaSemana,
                'dia_semana_texto' => DateHelper::getDiaSemanaTexto($diaSemana),
                'turno_filtrado' => $turno,
                'total_bolsistas' => $bolsistas->count(),
                'refeicao_id' => $refeicao?->id,
                'stats' => $stats,
            ]
        );
    }

    /**
     * RF10 - Lista todos os bolsistas
     * GET /api/v1/admin/bolsistas
     */
    public function todosBolsistas(Request $request): JsonResponse
    {
        $query = \App\Models\Bolsista::with(['user.diasSemana']);

        // Filtros
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        if ($request->has('turno')) {
            $query->where('turno_refeicao', $request->input('turno'));
        }

        $perPage = $request->integer('per_page', 20);
        
        // Clonar query para estatísticas globais antes de paginar
        $statsQuery = clone $query;
        $total = $statsQuery->count();
        $ativos = (clone $statsQuery)->where('ativo', true)->count();
        $vinculados = (clone $statsQuery)->whereNotNull('user_id')->count();

        $bolsistas = $query->orderBy('nome')->paginate($perPage);

        return ApiResponse::standardSuccess(
            data: BolsistaResource::collection($bolsistas),
            meta: [
                'total' => $total,
                'ativos' => $ativos,
                'inativos' => $total - $ativos,
                'vinculados' => $vinculados,
                'pendentes' => $total - $vinculados,
                'pagination' => [
                    'current_page' => $bolsistas->currentPage(),
                    'last_page' => $bolsistas->lastPage(),
                    'per_page' => $bolsistas->perPage(),
                    'total' => $total,
                ]
            ]
        );
    }

    /**
     * RF13 - Buscar bolsista para confirmação manual
     * GET /api/v1/admin/bolsistas/buscar
     */
    public function buscarParaConfirmacao(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'turno' => 'required|in:almoco,jantar',
            'data' => 'nullable|date',
        ]);

        $search = $request->input('search');
        $turno = $request->input('turno');
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar na tabela MASTER de bolsistas (inclusivo para pendentes)
        $bolsistas = \App\Models\Bolsista::with(['user.diasSemana'])
            ->where('ativo', true)
            ->where('turno_refeicao', $turno)
            ->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%");
            })
            ->where(function($q) use ($diaSemana) {
                $q->whereHas('user.diasSemana', fn($d) => $d->where('dia_semana', $diaSemana))
                  ->orWhere(function($sub) use ($diaSemana) {
                      $sub->whereNull('user_id')
                          ->whereJsonContains('dias_semana', $diaSemana);
                  });
            })
            ->limit(10)
            ->get();

        // Buscar refeição
        $resultado = ValidationHelper::buscarRefeicao($data, $turno);
        $refeicao = $resultado['refeicao'];

        // Anexar status de presença
        if ($refeicao) {
            foreach ($bolsistas as $bolsista) {
                if ($bolsista->user_id) {
                    $presenca = Presenca::where('user_id', $bolsista->user_id)
                        ->where('refeicao_id', $refeicao->id)
                        ->first();

                    $bolsista->presenca_status_busca = $presenca ? $presenca->status_da_presenca->value : 'sem_registro';
                    $bolsista->presenca_id_busca = $presenca?->id;
                    $bolsista->ja_presente_flag = $presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE;
                } else {
                    $bolsista->presenca_status_busca = 'pendente_vinculo';
                    $bolsista->ja_presente_flag = false;
                }
            }
        }

        return ApiResponse::standardSuccess(
            data: BolsistaResource::collection($bolsistas),
            meta: [
                'total' => $bolsistas->count(),
                'data' => DateHelper::formatarDataBR($data),
                'data_iso' => $data,
                'turno' => $turno,
                'refeicao_id' => $refeicao?->id,
                'tem_refeicao' => $refeicao !== null,
            ]
        );
    }

    /**
     * RF13 - Confirmar presença por QR Code (matrícula)
     * POST /api/v1/admin/bolsistas/qrcode
     */
    public function confirmarPorQrCode(Request $request): JsonResponse
    {
        $request->validate([
            'matricula' => 'required|string',
            'turno' => 'required|in:almoco,jantar',
            'data' => 'nullable|date',
        ]);

        $matricula = $request->input('matricula');
        $turno = $request->input('turno');
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar usuário
        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            return ApiResponse::standardNotFound('matricula', 'Matrícula não encontrada.');
        }

        // Validar bolsista ativo
        $validacao = ValidationHelper::validarBolsistaAtivo($user, $diaSemana);
        if (!$validacao['valido']) {
            $erro = $validacao['erro'];
            return ApiResponse::standardError($erro['chave'], $erro['message'], $erro['code']);
        }

        // Validar se o bolsista tem direito ao turno específico
        if ($user->aprovado && $user->aprovado->turno_refeicao !== $turno) {
            return ApiResponse::standardError(
                'turno_incorreto',
                "Este bolsista não está listado para o turno de {$turno}. Turno do bolsista: {$user->aprovado->turno_refeicao}.",
                422
            );
        }

        // Buscar refeição
        $resultado = ValidationHelper::buscarRefeicao($data, $turno);
        if ($resultado['erro']) {
            return ApiResponse::standardNotFound('refeicao', $resultado['erro']['message']);
        }
        $refeicao = $resultado['refeicao'];

        // Verificar se já presente
        $presenca = Presenca::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE) {
            return ApiResponse::standardSuccess(
                data: [
                    'usuario' => $user->nome,
                    'matricula' => $user->matricula,
                    'curso' => $user->curso,
                    'confirmado_em' => $presenca->validado_em->format('H:i:s'),
                ],
                meta: [
                    'message' => '⚠️ Presença já estava confirmada.',
                    'ja_presente' => true,
                ]
            );
        }

        // Confirmar presença
        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $user->id,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::PRESENTE,
                'registrado_em' => now(),
                'validado_em' => now(),
                'validado_por' => $request->user()?->id ?? 1,
            ]);
        } else {
            $presenca->marcarPresente($request->user()?->id ?? 1);
        }

        return ApiResponse::standardCreated(
            data: [
                'presenca_id' => $presenca->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
                'refeicao' => [
                    'data' => DateHelper::formatarDataBR($refeicao->data_do_cardapio),
                    'turno' => $refeicao->turno->value,
                ],
                'confirmado_em' => now()->format('H:i:s'),
            ],
            meta: ['message' => "✅ Presença confirmada para {$user->nome}!"]
        );
    }

    /**
     * Lista estudantes por turno
     * GET /api/v1/admin/estudantes/turno
     */
    public function estudantesPorTurno(Request $request): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');
        $apenasAtivos = $request->boolean('apenas_ativos', true);
        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar estudantes do dia
        [$estudantes, $refeicao] = $this->buscarBolsistasComPresencas(
            $data, 
            $turno, 
            $diaSemana,
            apenasAtivos: $apenasAtivos,
            usarScopeEstudantes: true
        );

        return ApiResponse::standardSuccess(
            data: BolsistaResource::collection($estudantes),
            meta: [
                'data' => DateHelper::formatarDataBR($data),
                'dia_semana_texto' => DateHelper::getDiaSemanaTexto($diaSemana),
                'turno' => $turno,
                'total' => $estudantes->count(),
                'total_bolsistas' => $estudantes->where('bolsista', true)->count(),
                'total_nao_bolsistas' => $estudantes->where('bolsista', false)->count(),
            ]
        );
    }

    /**
     * Confirmar presença do bolsista
     * POST /api/v1/admin/bolsistas/{userId}/confirmar-presenca
     */
    public function confirmarPresenca(Request $request, int $userId): JsonResponse
    {
        try {
            $resultado = $this->presencaService->confirmarPresencaCompleta(
                $userId,
                Carbon::parse($request->input('data', now()))->format('Y-m-d'),
                $request->input('turno', ''),
                $request->user()?->id
            );

            return ApiResponse::standardCreated(
                data: [
                    'presenca_id' => $resultado['presenca']->id,
                    'usuario' => $resultado['user']->nome,
                    'matricula' => $resultado['user']->matricula,
                    'refeicao' => [
                        'id' => $resultado['refeicao']->id,
                        'data' => DateHelper::formatarDataBR($resultado['refeicao']->data_do_cardapio),
                        'turno' => $resultado['refeicao']->turno->value,
                    ],
                    'confirmado_em' => DateHelper::formatarDataHoraBR($resultado['presenca']->validado_em),
                ],
                meta: ['message' => '✅ Presença confirmada com sucesso.']
            );

        } catch (BusinessException $e) {
            return ApiResponse::standardError('erro', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Marcar falta do bolsista
     * POST /api/v1/admin/bolsistas/{userId}/marcar-falta
     */
    public function marcarFalta(Request $request, int $userId): JsonResponse
    {
        try {
            $justificada = $request->boolean('justificada', false);
            
            $resultado = $this->presencaService->marcarFaltaCompleta(
                $userId,
                Carbon::parse($request->input('data', now()))->format('Y-m-d'),
                $request->input('turno', ''),
                $justificada,
                $request->user()?->id
            );

            $mensagem = $justificada ? 'Falta justificada registrada.' : 'Falta injustificada registrada.';

            return ApiResponse::standardSuccess(
                data: [
                    'presenca_id' => $resultado['presenca']->id,
                    'usuario' => $resultado['user']->nome,
                    'matricula' => $resultado['user']->matricula,
                    'status' => $resultado['presenca']->status_da_presenca->value,
                    'refeicao' => [
                        'id' => $resultado['refeicao']->id,
                        'data' => DateHelper::formatarDataBR($resultado['refeicao']->data_do_cardapio),
                        'turno' => $resultado['refeicao']->turno->value,
                    ],
                ],
                meta: ['message' => $mensagem]
            );

        } catch (BusinessException $e) {
            return ApiResponse::standardError('erro', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Confirmar presença em lote
     * POST /api/v1/admin/bolsistas/confirmar-lote
     */
    public function confirmarLote(Request $request): JsonResponse
    {
        $userIds = $request->input('user_ids', []);
        $turno = $request->input('turno');
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');

        if (empty($userIds)) {
            return ApiResponse::standardError('user_ids', 'Nenhum usuário selecionado.', 400);
        }

        if (!$turno) {
            return ApiResponse::standardError('turno', 'O turno é obrigatório.', 400);
        }

        // Buscar refeição
        $resultado = ValidationHelper::buscarRefeicao($data, $turno);
        if ($resultado['erro']) {
            return ApiResponse::standardNotFound('refeicao', $resultado['erro']['message']);
        }
        $refeicao = $resultado['refeicao'];

        $confirmados = 0;
        $jaConfirmados = 0;
        $erros = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            if (!$user) {
                $erros[] = "Usuário ID {$userId} não encontrado.";
                continue;
            }

            $presencaExistente = Presenca::where('user_id', $userId)
                ->where('refeicao_id', $refeicao->id)
                ->where('status_da_presenca', StatusPresenca::PRESENTE)
                ->exists();

            if ($presencaExistente) {
                $jaConfirmados++;
                continue;
            }

            Presenca::updateOrCreate(
                [
                    'user_id' => $userId,
                    'refeicao_id' => $refeicao->id,
                ],
                [
                    'status_da_presenca' => StatusPresenca::PRESENTE,
                    'validado_em' => now(),
                    'validado_por' => $request->user()?->id ?? 1,
                    'registrado_em' => now(),
                ]
            );

            $confirmados++;
        }

        return ApiResponse::standardSuccess(
            data: [
                'total_solicitados' => count($userIds),
                'confirmados' => $confirmados,
                'ja_confirmados' => $jaConfirmados,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => DateHelper::formatarDataBR($refeicao->data_do_cardapio),
                    'turno' => $refeicao->turno->value,
                ],
            ],
            meta: [
                'message' => "{$confirmados} presença(s) confirmada(s) com sucesso.",
                'errors' => $erros,
            ]
        );
    }

    /**
     * RF15 - Importar lista de bolsistas via Excel/CSV
     * POST /api/v1/admin/bolsistas/import
     */
    public function import(BolsistaImportRequest $request, BolsistaImportService $service): JsonResponse
    {
        $file = $request->file('file');
        $turnoPadrao = $request->input('turno_padrao');
        $atualizarExistentes = $request->boolean('atualizar_existentes', true);

        try {
            $rows = Excel::toArray(new class {}, $file)[0] ?? [];

            if (empty($rows)) {
                return ApiResponse::standardError('file', 'Arquivo vazio ou formato inválido.', 422);
            }

            \Log::info('Importando bolsistas', [
                'total_linhas' => count($rows),
                'turno_padrao' => $turnoPadrao,
                'usuario_id' => $request->user()?->id,
            ]);

            $resultado = $service->import($rows, $turnoPadrao, $atualizarExistentes);

            \Log::info('Resultado da importação de bolsistas', [
                'total_criados' => count($resultado['created']),
                'total_atualizados' => count($resultado['updated']),
                'total_erros' => count($resultado['errors']),
            ]);

            return ApiResponse::standardCreated(
                data: [
                    'criados' => $resultado['created'],
                    'atualizados' => $resultado['updated'],
                ],
                meta: array_merge(
                    $resultado['meta'],
                    [
                        'errors' => $resultado['errors'],
                        'message' => $resultado['meta']['mensagem'] ?? 'Importação concluída!'
                    ]
                )
            );
            
        } catch (\Exception $e) {
            return ApiResponse::standardError('file', 'Erro ao processar arquivo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Exportar template Excel para importação de bolsistas
     * GET /api/v1/admin/bolsistas/template
     */
    public function exportTemplate()
    {
        try {
            $filename = 'template_bolsistas_' . now()->format('Y-m-d') . '.xlsx';
            
            return Excel::download(
                new \App\Exports\BolsistaTemplateExport(),
                $filename
            );
        } catch (\Exception $e) {
            \Log::error('Erro ao exportar template de bolsistas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Desligar bolsista
     * POST /api/v1/admin/bolsistas/{id}/desligar
     */
    public function desligar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'required|string|min:10',
        ]);

        // Tentar buscar primeiro na tabela de bolsistas (ID que o front agora envia)
        $bolsista = \App\Models\Bolsista::find($id);

        if (!$bolsista) {
            // Fallback: tentar buscar pelo User ID (compatibilidade com versões anteriores ou outros fluxos)
            $user = User::where('id', $id)->where('bolsista', true)->first();
            if (!$user) {
                return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
            }
            $bolsista = \App\Models\Bolsista::where('user_id', $user->id)->first();
            
            if (!$bolsista) {
                // Caso não tenha registro na tabela bolsistas, desliga apenas o user
                $user->update([
                    'desligado' => true,
                    'desligado_em' => now(),
                    'desligado_motivo' => $request->input('motivo'),
                    'bolsista' => false,
                ]);
                return ApiResponse::standardSuccess(
                    data: ['bolsista_id' => $user->id, 'desligado_em' => $user->desligado_em],
                    meta: ['message' => 'Bolsista desligado com sucesso.']
                );
            }
        }

        if ($bolsista->desligado) {
            return ApiResponse::standardError('bolsista', 'Bolsista já está desligado.', 400);
        }

        $bolsista->desligar($request->input('motivo'));

        return ApiResponse::standardSuccess(
            data: ['bolsista_id' => $bolsista->id, 'desligado_em' => $bolsista->desligado_em],
            meta: ['message' => 'Bolsista desligado com sucesso.']
        );
    }

    /**
     * Reativar bolsista
     * POST /api/v1/admin/bolsistas/{id}/reativar
     */
    public function reativar(int $id): JsonResponse
    {
        // Tentar buscar primeiro na tabela de bolsistas
        $bolsista = \App\Models\Bolsista::find($id);

        if (!$bolsista) {
            // Fallback: tentar buscar pelo User ID
            $user = User::where('id', $id)->where('bolsista', true)->first();
            if (!$user) {
                return ApiResponse::standardNotFound('bolsista', 'Bolsista não encontrado.');
            }
            $bolsista = \App\Models\Bolsista::where('user_id', $user->id)->first();
            
            if (!$bolsista) {
                // Caso não tenha registro na tabela bolsistas, reativa apenas o user
                $user->update([
                    'desligado' => false,
                    'desligado_em' => null,
                    'desligado_motivo' => null,
                    'bolsista' => true,
                ]);
                return ApiResponse::standardSuccess(
                    data: ['bolsista_id' => $user->id, 'reativado_em' => now()],
                    meta: ['message' => 'Bolsista reativado com sucesso.']
                );
            }
        }

        if (!$bolsista->desligado) {
            return ApiResponse::standardError('bolsista', 'Bolsista já está ativo.', 400);
        }

        $bolsista->reativar();

        return ApiResponse::standardSuccess(
            data: ['bolsista_id' => $bolsista->id, 'reativado_em' => now()],
            meta: ['message' => 'Bolsista reativado com sucesso.']
        );
    }

    /**
     * Listar bolsistas com risco de desligamento (3+ faltas)
     * GET /api/v1/admin/bolsistas/alerta-faltas
     */
    public function alertaFaltas(): JsonResponse
    {
        $bolsistas = \App\Models\Bolsista::where('desligado', false)
            ->whereNotNull('user_id')
            ->get()
            ->filter(fn($b) => $b->deveSereNotificado())
            ->map(function($b) {
                return [
                    'id' => $b->id,
                    'user_id' => $b->user_id,
                    'nome' => $b->user->nome ?? $b->nome,
                    'matricula' => $b->matricula,
                    'faltas_nao_justificadas' => $b->contarFaltasNaoJustificadas(),
                ];
            })->values();

        return ApiResponse::standardSuccess(
            data: $bolsistas,
            meta: [
                'total' => $bolsistas->count(),
                'message' => 'Bolsistas com 3 ou mais faltas não justificadas.',
            ]
        );
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Busca bolsistas com suas presenças anexadas
     * 
     * @return array [$bolsistas, $refeicao]
     */
    private function buscarBolsistasComPresencas(
        string $data, 
        ?string $turno, 
        int $diaSemana,
        bool $apenasAtivos = false,
        bool $usarScopeEstudantes = false
    ): array {
        if ($usarScopeEstudantes) {
            $query = User::estudantes();
            $query->with(['diasSemana', 'aprovado'])
                ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaSemana))
                ->where('desligado', false);

            if ($turno) {
                $query->whereHas('aprovado', function($q) use ($turno) {
                    $q->where('turno_refeicao', $turno);
                });
            }
            $lista = $query->orderBy('nome')->get();
        } else {
            // Buscar na tabela MASTER de bolsistas (inclusivo para pendentes)
            $query = \App\Models\Bolsista::with(['user.diasSemana'])
                ->where('ativo', true);

            if ($turno) {
                $query->where('turno_refeicao', $turno);
            }

            // Filtrar por dia da semana
            $query->where(function($q) use ($diaSemana) {
                // Se vinculado, olha dias_semana do user
                $q->whereHas('user.diasSemana', fn($d) => $d->where('dia_semana', $diaSemana))
                // Se pendente, olha dias_semana do bolsista (JSON)
                  ->orWhere(function($sub) use ($diaSemana) {
                      $sub->whereNull('user_id')
                          ->whereJsonContains('dias_semana', $diaSemana);
                  });
            });

            $lista = $query->orderBy('nome')->get();
        }

        // Buscar refeição
        $refeicaoQuery = \App\Models\Refeicao::where('data_do_cardapio', $data);
        if ($turno) {
            $refeicaoQuery->where('turno', $turno);
        }
        $refeicao = $refeicaoQuery->first();

        // Anexar presenças e justificativas
        if ($refeicao) {
            $presencas = Presenca::where('refeicao_id', $refeicao->id)
                ->get()
                ->keyBy('user_id');

            $justificativas = \App\Models\Justificativa::where('refeicao_id', $refeicao->id)
                ->where('tipo', 'antecipada')
                ->where('status', 'aprovada')
                ->get()
                ->keyBy('user_id');

            foreach ($lista as $item) {
                $userId = ($item instanceof User) ? $item->id : $item->user_id;
                
                if ($userId) {
                    $item->presenca_atual = $presencas[$userId] ?? null;
                    $item->justificativa_antecipada = $justificativas[$userId] ?? null;
                    $item->tem_falta_antecipada = isset($justificativas[$userId]);
                } else {
                    $item->presenca_atual = null;
                    $item->justificativa_antecipada = null;
                    $item->tem_falta_antecipada = false;
                }
            }
        }

        return [$lista, $refeicao];
    }

    /**
     * Calcula estatísticas de presença
     */
    private function calcularEstatisticas($bolsistas): array
    {
        $total = $bolsistas->count();
        $presentes = $bolsistas->filter(fn($b) => $b->presenca_atual?->status_da_presenca->value === 'presente')->count();
        $pendentes = $bolsistas->filter(fn($b) => !$b->presenca_atual)->count();
        $faltasJustificadas = $bolsistas->filter(fn($b) => $b->presenca_atual?->status_da_presenca->value === 'falta_justificada')->count();
        $faltasInjustificadas = $bolsistas->filter(fn($b) => $b->presenca_atual?->status_da_presenca->value === 'falta_injustificada')->count();
        $cancelados = $bolsistas->filter(fn($b) => $b->presenca_atual?->status_da_presenca->value === 'cancelado')->count();

        return compact('total', 'presentes', 'pendentes', 'faltasJustificadas', 'faltasInjustificadas', 'cancelados');
    }
}
