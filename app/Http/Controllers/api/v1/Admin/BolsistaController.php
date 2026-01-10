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
        $query = User::where('bolsista', true)->with('diasSemana');

        // Filtros
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('ativo')) {
            $query->where('desligado', !$request->boolean('ativo'));
        }

        $bolsistas = $query->orderBy('nome')->get();

        return ApiResponse::standardSuccess(
            data: BolsistaResource::collection($bolsistas),
            meta: [
                'total' => $bolsistas->count(),
                'ativos' => $bolsistas->where('desligado', false)->count(),
                'inativos' => $bolsistas->where('desligado', true)->count(),
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

        // Buscar bolsistas
        $bolsistas = User::where('bolsista', true)
            ->where('desligado', false)
            ->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%");
            })
            ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaSemana))
            ->limit(10)
            ->get();

        // Buscar refeição
        $resultado = ValidationHelper::buscarRefeicao($data, $turno);
        $refeicao = $resultado['refeicao'];

        // Anexar status de presença
        if ($refeicao) {
            foreach ($bolsistas as $bolsista) {
                $presenca = Presenca::where('user_id', $bolsista->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->first();

                $bolsista->presenca_status_busca = $presenca ? $presenca->status_da_presenca->value : 'sem_registro';
                $bolsista->presenca_id_busca = $presenca?->id;
                $bolsista->ja_presente_flag = $presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE;
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

        } catch (\App\Exceptions\BusinessException $e) {
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

        } catch (\App\Exceptions\BusinessException $e) {
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

            $resultado = $service->import($rows, $turnoPadrao, $atualizarExistentes);

            return ApiResponse::standardCreated(
                data: [
                    'criados' => $resultado['created'],
                    'atualizados' => $resultado['updated'],
                ],
                meta: array_merge(
                    $resultado['meta'],
                    ['errors' => $resultado['errors']]
                )
            );
            
        } catch (\Exception $e) {
            return ApiResponse::standardError('file', 'Erro ao processar arquivo: ' . $e->getMessage(), 500);
        }
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
        $query = $usarScopeEstudantes ? User::estudantes() : User::where('bolsista', true);
        
        $query->with('diasSemana')
            ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaSemana))
            ->orderBy('nome');

        if ($apenasAtivos) {
            $query->where('desligado', false);
        }

        $bolsistas = $query->get();

        // Buscar refeição
        $refeicaoQuery = \App\Models\Refeicao::where('data_do_cardapio', $data);
        if ($turno) {
            $refeicaoQuery->where('turno', $turno);
        }
        $refeicao = $refeicaoQuery->first();

        // Anexar presenças
        if ($refeicao) {
            $presencas = Presenca::where('refeicao_id', $refeicao->id)
                ->get()
                ->keyBy('user_id');

            foreach ($bolsistas as $bolsista) {
                $bolsista->presenca_atual = $presencas[$bolsista->id] ?? null;
            }
        }

        return [$bolsistas, $refeicao];
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
