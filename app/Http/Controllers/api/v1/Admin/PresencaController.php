<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Enums\StatusPresenca;
use App\Services\PresencaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PresencaController extends Controller
{
    public function __construct(
        protected PresencaService $presencaService
    ) {}

    /**
     * Lista os bolsistas do dia (elegíveis para a refeição) e suas presenças
     * GET /api/v1/admin/presencas
     */
    public function index(Request $request)
    {
        if (config('app.debug')) {
            Log::info('=== PRESENCAS INDEX INICIADO ===');
        }

        $data = $request->input('data', now()->format('Y-m-d'));
        $turno = $request->input('turno');

        if (config('app.debug')) {
            Log::info('Parametros recebidos', ['data' => $data, 'turno' => $turno]);
        }

        // Buscar a refeição do dia/turno
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->when($turno, function ($q) use ($turno) {
                return $q->where('turno', $turno);
            })
            ->with('cardapio')
            ->first();

        if (config('app.debug')) {
            Log::info('Refeicao encontrada', ['refeicao_id' => $refeicao ? $refeicao->id : null]);
        }

        // Determinar o dia da semana (0=Domingo, 1=Segunda, ..., 6=Sábado)
        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar apenas bolsistas que têm direito à refeição NESTE dia da semana
        $bolsistasQuery = User::where('bolsista', true)
            ->whereHas('diasSemana', function ($q) use ($diaDaSemana) {
                $q->where('dia_semana', $diaDaSemana);
            });
        $bolsistas = $bolsistasQuery->orderBy('nome')->get();

        if (config('app.debug')) {
            Log::info('Bolsistas buscados', [
                'dia_da_semana' => $diaDaSemana,
                'bolsistas_count' => $bolsistas->count(),
            ]);
        }

        if (!$refeicao) {
            return response()->json([
                'data' => [],
                'errors' => ['refeicao' => ['Não há refeição cadastrada para este dia e turno.']],
                'meta' => [
                    'data' => $data,
                    'turno' => $turno,
                    'total_bolsistas' => $bolsistas->count(),
                    'sugestao' => 'Crie um cardápio para esta data primeiro',
                ],
            ], 404);
        }

        // Buscar presenças já registradas para esta refeição
        $presencas = Presenca::where('refeicao_id', $refeicao->id)
            ->with('validador')
            ->get()
            ->keyBy('user_id');

        // Montar lista com status de cada bolsista
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
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                ],
                'presenca' => $presenca ? [
                    'id' => $presenca->id,
                    'status' => $presenca->status_da_presenca->value,
                    'confirmado_em' => $presenca->registrado_em,
                    'confirmado_por' => $presenca->validador ? $presenca->validador->nome : null,
                ] : null,
                'presente' => $presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE,
            ];
        });

        return response()->json([
            'data' => $lista->values(),
            'errors' => [],
            'meta' => [
                'total_bolsistas' => $bolsistas->count(),
                'presentes' => $lista->where('presente', true)->count(),
                'ausentes' => $lista->where('presente', false)->count(),
                'refeicao' => [
                    'id' => $refeicao->id,
                    'turno' => $refeicao->turno->value,
                    'data' => $refeicao->data_do_cardapio->format('Y-m-d'),
                ],
            ],
        ]);
    }

    /**
     * Confirma uma presença específica por ID (via botão na lista)
     * POST /api/v1/admin/presencas/{user_id}/confirmar
     * 
     * Controller orquestra e formata HTTP. Service contém regras de negócio.
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

            return response()->json([
                'data' => [
                    'presenca_id' => $resultado['presenca']->id,
                    'usuario' => $resultado['user']->nome,
                    'confirmado_em' => $resultado['presenca']->validado_em->format('H:i:s'),
                ],
                'errors' => [],
                'meta' => ['message' => '✅ Presença confirmada!'],
            ], 201);

        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json([
                'data' => null,
                'errors' => ['erro' => [$e->getMessage()]],
                'meta' => $e->getMeta(),
            ], $e->getCode());
        }
    }

    /**
     * Remove confirmação de presença (desfazer)
     * POST /api/v1/admin/presencas/{user_id}/remover
     */
    public function removerConfirmacao(Request $request, $userId)
    {
        $turno = $request->input('turno');
        $data = $request->input('data', now()->format('Y-m-d'));

        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'data' => null,
                'errors' => ['refeicao' => ['Refeição não encontrada.']],
                'meta' => [],
            ], 404);
        }

        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if (!$presenca) {
            return response()->json([
                'data' => null,
                'errors' => ['presenca' => ['Presença não encontrada.']],
                'meta' => [],
            ], 404);
        }

        $presenca->delete();

        return response()->json([
            'data' => null,
            'errors' => [],
            'meta' => ['message' => 'Confirmação removida.'],
        ]);
    }

    /**
     * Confirma presença por matrícula ou QR Code
     * Admin busca aluno pela matrícula e confirma presença
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

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataInput)) {
            $data = Carbon::createFromFormat('d/m/Y', $dataInput)->format('Y-m-d');
        } else {
            $data = $dataInput;
        }

        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            return response()->json([
                'data' => null,
                'errors' => ['matricula' => ['Matrícula não encontrada.']],
                'meta' => [],
            ], 404);
        }

        if (!$user->bolsista || $user->desligado) {
            return response()->json([
                'data' => null,
                'errors' => ['user' => ['Usuário não é bolsista ativo.']],
                'meta' => [],
            ], 403);
        }

        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        if (!$user->temDireitoRefeicaoNoDia($diaDaSemana)) {
            $diasCadastrados = $user->diasSemana()->get()->map(fn($dia) => $dia->getDiaSemanaTexto())->implode(', ');

            return response()->json([
                'data' => null,
                'errors' => ['permissao' => ['Você não está cadastrado para se alimentar neste dia da semana.']],
                'meta' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum dia cadastrado',
                ],
            ], 403);
        }

        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'data' => null,
                'errors' => ['refeicao' => ['Não há refeição cadastrada para este dia e turno.']],
                'meta' => [],
            ], 404);
        }

        $presenca = Presenca::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE) {
            return response()->json([
                'data' => null,
                'errors' => ['presenca' => ['Presença já foi confirmada anteriormente.']],
                'meta' => [
                    'usuario' => $user->nome,
                    'confirmado_em' => $presenca->registrado_em?->format('H:i:s'),
                ],
            ], 400);
        }

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

        return response()->json([
            'data' => [
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
                'confirmado_em' => now()->format('H:i:s'),
            ],
            'errors' => [],
            'meta' => ['message' => '✅ Presença confirmada!'],
        ], 201);
    }

    /**
     * Marca falta em uma presença
     * POST /api/v1/admin/presencas/{id}/marcar-falta
     */
    public function marcarFalta(Request $request, $id)
    {
        $request->validate([
            'justificada' => 'required|boolean',
        ]);

        $presenca = Presenca::with(['user', 'refeicao'])->findOrFail($id);

        $presenca->marcarFalta($request->input('justificada'));

        return response()->json([
            'data' => $presenca,
            'errors' => [],
            'meta' => ['message' => 'Falta marcada com sucesso.'],
        ]);
    }

    /**
     * Cancela uma presença (admin cancela a refeição do dia)
     * POST /api/v1/admin/presencas/{id}/cancelar
     */
    public function cancelar($id)
    {
        $presenca = Presenca::findOrFail($id);

        $presenca->update([
            'status_da_presenca' => StatusPresenca::CANCELADO,
        ]);

        return response()->json([
            'data' => $presenca,
            'errors' => [],
            'meta' => ['message' => 'Presença cancelada com sucesso.'],
        ]);
    }

    /**
     * Estatísticas de presença por período
     * GET /api/v1/admin/presencas/estatisticas
     */
    public function estatisticas(Request $request)
    {
        $dataInicio = $request->input('data_inicio', now()->startOfMonth()->format('Y-m-d'));
        $dataFim = $request->input('data_fim', now()->endOfMonth()->format('Y-m-d'));

        $presencas = Presenca::whereHas('refeicao', function ($q) use ($dataInicio, $dataFim) {
            $q->whereBetween('data_do_cardapio', [$dataInicio, $dataFim]);
        })->get();

        return response()->json([
            'data' => [
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
            ],
            'errors' => [],
            'meta' => [
                'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
            ],
        ]);
    }

    /**
     * Confirmação em lote (múltiplos alunos de uma vez)
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
            ->where(function ($q) {
                $q->whereNull('status_da_presenca')
                  ->orWhere('status_da_presenca', '!=', StatusPresenca::PRESENTE);
            })
            ->get();

        $confirmadas = 0;
        foreach ($presencas as $presenca) {
            $presenca->marcarPresente($confirmadorId);
            $confirmadas++;
        }

        return response()->json([
            'data' => [
                'total_solicitado' => count($presencaIds),
                'confirmadas' => $confirmadas,
            ],
            'errors' => [],
            'meta' => ['message' => "{$confirmadas} presença(s) confirmada(s) com sucesso."],
        ]);
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
            return response()->json([
                'data' => null,
                'errors' => ['token' => ['QR Code inválido ou presença não encontrada.']],
                'meta' => [],
            ], 404);
        }

        $presenca->marcarPresente($request->user()?->id ?? 1);

        return response()->json([
            'data' => [
                'usuario' => $presenca->user->nome,
                'matricula' => $presenca->user->matricula,
                'refeicao' => [
                    'data' => $presenca->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $presenca->refeicao->turno->value,
                ],
                'confirmado_em' => now()->format('H:i:s'),
                'confirmado_por' => $request->user()?->nome ?? 'Admin Sistema',
            ],
            'errors' => [],
            'meta' => ['message' => "✅ Presença confirmada para {$presenca->user->nome}!"],
        ], 201);
    }

    /**
     * Gerar QR Code para uma presença
     * GET /api/v1/admin/presencas/{id}/qrcode
     */
    public function gerarQrCode($id)
    {
        $presenca = Presenca::with(['user', 'refeicao'])->findOrFail($id);

        return response()->json([
            'data' => [
                'presenca_id' => $presenca->id,
                'usuario' => $presenca->user->nome,
                'matricula' => $presenca->user->matricula,
                'refeicao' => [
                    'data' => $presenca->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $presenca->refeicao->turno->value,
                ],
                'url_qrcode' => $presenca->gerarUrlQrCode(),
                'token' => $presenca->gerarTokenQrCode(),
            ],
            'errors' => [],
            'meta' => [],
        ]);
    }
}
