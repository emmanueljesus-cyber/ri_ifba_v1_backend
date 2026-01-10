<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Enums\StatusPresenca;
use App\Services\PresencaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Controller para gerenciamento de presenças (RF09, RF13)
 * 
 * Responsabilidades:
 * - Lista de bolsistas e presenças do dia
 * - Confirmação de presença (individual, lote, QR Code)
 * - Marcação de faltas e cancelamentos
 */
class PresencaController extends Controller
{
    public function __construct(
        protected PresencaService $presencaService
    ) {}

    /**
     * Lista bolsistas do dia e suas presenças
     * GET /api/v1/admin/presencas
     */
    public function index(Request $request)
    {
        if (config('app.debug')) {
            Log::info('=== PRESENCAS INDEX INICIADO ===');
        }

        $data = $request->input('data', now()->format('Y-m-d'));
        $turno = $request->input('turno');

        // Buscar refeição
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->when($turno, fn($q) => $q->where('turno', $turno))
            ->with('cardapio')
            ->first();

        // Dia da semana
        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar bolsistas do dia
        $bolsistas = User::where('bolsista', true)
            ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaDaSemana))
            ->orderBy('nome')
            ->get();

        if (!$refeicao) {
            return ApiResponse::standardNotFound(
                'refeicao',
                'Não há refeição cadastrada para este dia e turno.'
            );
        }

        // Presenças registradas
        $presencas = Presenca::where('refeicao_id', $refeicao->id)
            ->with('validador')
            ->get()
            ->keyBy('user_id');

        // Montar lista
        $lista = $bolsistas->map(function ($bolsista) use ($presencas, $refeicao) {
            $presenca = $presencas->get($bolsista->id);

            return [
                'user_id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'curso' => $bolsista->curso,
                'turno_aluno' => $bolsista->turno,
                'refeicao' => [
                    'turno' => $refeicao->turno->value,
                    'data' => DateHelper::formatarDataBR($refeicao->data_do_cardapio),
                ],
                'presenca' => $presenca ? [
                    'id' => $presenca->id,
                    'status' => $presenca->status_da_presenca->value,
                    'confirmado_em' => $presenca->registrado_em,
                    'confirmado_por' => $presenca->validador?->nome,
                ] : null,
                'presente' => $presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE,
            ];
        });

        return ApiResponse::standardSuccess(
            data: $lista->values(),
            meta: [
                'total_bolsistas' => $bolsistas->count(),
                'presentes' => $lista->where('presente', true)->count(),
                'ausentes' => $lista->where('presente', false)->count(),
                'refeicao' => [
                    'id' => $refeicao->id,
                    'turno' => $refeicao->turno->value,
                    'data' => $refeicao->data_do_cardapio->format('Y-m-d'),
                ],
            ]
        );
    }

    /**
     * Confirma presença por ID
     * POST /api/v1/admin/presencas/{user_id}/confirmar
     */
    public function confirmarPorId(Request $request, $userId)
    {
        try {
            $dataInput = $request->input('data', now()->format('Y-m-d'));
            $data = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataInput)
                ? Carbon::createFromFormat('d/m/Y', $dataInput)->format('Y-m-d')
                : $dataInput;

            $resultado = $this->presencaService->confirmarPresencaCompleta(
                (int) $userId,
                $data,
                $request->input('turno', ''),
                $request->user()?->id
            );

            return ApiResponse::standardCreated(
                data: [
                    'presenca_id' => $resultado['presenca']->id,
                    'usuario' => $resultado['user']->nome,
                    'confirmado_em' => $resultado['presenca']->validado_em->format('H:i:s'),
                ],
                meta: ['message' => '✅ Presença confirmada!']
            );

        } catch (\App\Exceptions\BusinessException $e) {
            return ApiResponse::standardError('erro', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Remove confirmação de presença
     * POST /api/v1/admin/presencas/{user_id}/remover
     */
    public function removerConfirmacao(Request $request, $userId)
    {
        $turno = $request->input('turno');
        $data = $request->input('data', now()->format('Y-m-d'));

        // Buscar refeição
        $resultado = ValidationHelper::buscarRefeicao($data, $turno);
        if ($resultado['erro']) {
            return ApiResponse::standardNotFound('refeicao', $resultado['erro']['message']);
        }
        $refeicao = $resultado['refeicao'];

        // Buscar presença
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if (!$presenca) {
            return ApiResponse::standardNotFound('presenca', 'Presença não encontrada.');
        }

        $presenca->delete();

        return ApiResponse::standardSuccess(
            null,
            ['message' => 'Confirmação removida.']
        );
    }

    /**
     * Confirma presença por matrícula ou QR Code
     * POST /api/v1/admin/presencas/confirmar
     */
    public function confirmarPresenca(Request $request)
    {
        $request->validate([
            'matricula' => 'required|string',
            'turno' => 'required|in:almoco,jantar',
            'data' => 'nullable|date',
        ]);

        $matricula = $request->input('matricula');
        $turno = $request->input('turno');
        $dataInput = $request->input('data', now()->format('Y-m-d'));

        $data = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataInput)
            ? Carbon::createFromFormat('d/m/Y', $dataInput)->format('Y-m-d')
            : $dataInput;

        // Buscar usuário
        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            return ApiResponse::standardNotFound('matricula', 'Matrícula não encontrada.');
        }

        if (!$user->bolsista || $user->desligado) {
            return ApiResponse::standardError('user', 'Usuário não é bolsista ativo.', 403);
        }

        // Verificar direito ao dia
        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        if (!$user->temDireitoRefeicaoNoDia($diaDaSemana)) {
            $diasCadastrados = $user->diasSemana()->get()
                ->map(fn($dia) => $dia->getDiaSemanaTexto())
                ->implode(', ');

            return ApiResponse::standardError(
                'permissao',
                'Você não está cadastrado para se alimentar neste dia da semana.',
                403
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
            return ApiResponse::standardError(
                'presenca',
                'Presença já foi confirmada anteriormente.',
                400
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
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
                'confirmado_em' => now()->format('H:i:s'),
            ],
            meta: ['message' => '✅ Presença confirmada!']
        );
    }

    /**
     * Marca falta
     * POST /api/v1/admin/presencas/{id}/marcar-falta
     */
    public function marcarFalta(Request $request, $id)
    {
        $request->validate([
            'justificada' => 'required|boolean',
        ]);

        $presenca = Presenca::with(['user', 'refeicao'])->findOrFail($id);

        $presenca->marcarFalta($request->input('justificada'));

        return ApiResponse::standardSuccess(
            $presenca,
            ['message' => 'Falta marcada com sucesso.']
        );
    }

    /**
     * Cancela presença
     * POST /api/v1/admin/presencas/{id}/cancelar
     */
    public function cancelar($id)
    {
        $presenca = Presenca::findOrFail($id);

        $presenca->update([
            'status_da_presenca' => StatusPresenca::CANCELADO,
        ]);

        return ApiResponse::standardSuccess(
            $presenca,
            ['message' => 'Presença cancelada com sucesso.']
        );
    }

    /**
     * Estatísticas de presença
     * GET /api/v1/admin/presencas/estatisticas
     */
    public function estatisticas(Request $request)
    {
        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->input('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $presencas = Presenca::whereHas('refeicao', fn($q) =>
            $q->whereBetween('data_do_cardapio', [$dataInicio, $dataFim])
        )->get();

        $stats = [
            'total' => $presencas->count(),
            'por_status' => [
                'presentes' => $presencas->where('status_da_presenca', StatusPresenca::PRESENTE)->count(),
                'faltas_justificadas' => $presencas->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)->count(),
                'faltas_injustificadas' => $presencas->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)->count(),
                'cancelados' => $presencas->where('status_da_presenca', StatusPresenca::CANCELADO)->count(),
            ],
            'taxa_presenca' => $presencas->count() > 0
                ? round(($presencas->where('status_da_presenca', StatusPresenca::PRESENTE)->count() / $presencas->count()) * 100, 2)
                : 0,
        ];

        return ApiResponse::standardSuccess(
            data: $stats,
            meta: ['periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim]]
        );
    }

    /**
     * Confirmação em lote
     * POST /api/v1/admin/presencas/validar-lote
     */
    public function validarLote(Request $request)
    {
        $request->validate([
            'presenca_ids' => 'required|array',
            'presenca_ids.*' => 'required|exists:presencas,id',
        ]);

        $confirmadorId = $request->user()?->id ?? 1;
        $presencaIds = $request->input('presenca_ids');

        $presencas = Presenca::whereIn('id', $presencaIds)
            ->where(fn($q) =>
                $q->whereNull('status_da_presenca')
                  ->orWhere('status_da_presenca', '!=', StatusPresenca::PRESENTE)
            )
            ->get();

        $confirmadas = 0;
        foreach ($presencas as $presenca) {
            $presenca->marcarPresente($confirmadorId);
            $confirmadas++;
        }

        return ApiResponse::standardSuccess(
            data: [
                'total_solicitado' => count($presencaIds),
                'confirmadas' => $confirmadas,
            ],
            meta: ['message' => "{$confirmadas} presença(s) confirmada(s) com sucesso."]
        );
    }

    /**
     * Validar presença por QR Code
     * POST /api/v1/admin/presencas/validar-qrcode
     */
    public function validarPorQrCode(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $presenca = Presenca::buscarPorTokenQrCode($request->token);

        if (!$presenca) {
            return ApiResponse::standardNotFound('token', 'QR Code inválido ou presença não encontrada.');
        }

        $presenca->marcarPresente($request->user()?->id ?? 1);

        return ApiResponse::standardCreated(
            data: [
                'usuario' => $presenca->user->nome,
                'matricula' => $presenca->user->matricula,
                'refeicao' => [
                    'data' => DateHelper::formatarDataBR($presenca->refeicao->data_do_cardapio),
                    'turno' => $presenca->refeicao->turno->value,
                ],
                'confirmado_em' => now()->format('H:i:s'),
                'confirmado_por' => $request->user()?->nome ?? 'Admin Sistema',
            ],
            meta: ['message' => "✅ Presença confirmada para {$presenca->user->nome}!"]
        );
    }

    /**
     * Gerar QR Code
     * GET /api/v1/admin/presencas/{id}/qrcode
     */
    public function gerarQrCode($id)
    {
        $presenca = Presenca::with(['user', 'refeicao'])->findOrFail($id);

        return ApiResponse::standardSuccess([
            'presenca_id' => $presenca->id,
            'usuario' => $presenca->user->nome,
            'matricula' => $presenca->user->matricula,
            'refeicao' => [
                'data' => DateHelper::formatarDataBR($presenca->refeicao->data_do_cardapio),
                'turno' => $presenca->refeicao->turno->value,
            ],
            'url_qrcode' => $presenca->gerarUrlQrCode(),
            'token' => $presenca->gerarTokenQrCode(),
        ]);
    }
}
