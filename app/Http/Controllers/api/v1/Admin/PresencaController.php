<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Enums\StatusPresenca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PresencaController extends Controller
{
    /**
     * Lista os bolsistas do dia (elegíveis para a refeição) e suas presenças
     * GET /api/v1/admin/presencas
     */
    public function index(Request $request)
    {
        // DEBUG EXTREMO
        \Log::info('=== PRESENCAS INDEX INICIADO ===');

        $data = $request->input('data', now()->format('Y-m-d'));
        $turno = $request->input('turno');

        \Log::info('Parametros recebidos', ['data' => $data, 'turno' => $turno]);

        // Buscar a refeição do dia/turno
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->when($turno, function ($q) use ($turno) {
                return $q->where('turno', $turno);
            })
            ->with('cardapio')
            ->first();

        \Log::info('Refeicao encontrada', ['refeicao_id' => $refeicao ? $refeicao->id : null]);

        // Determinar o dia da semana (0=Domingo, 1=Segunda, ..., 6=Sábado)
        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar apenas bolsistas que têm direito à refeição NESTE dia da semana
        $totalUsers = User::count();
        $bolsistasQuery = User::where('bolsista', true)
            ->whereHas('diasSemana', function($q) use ($diaDaSemana) {
                $q->where('dia_semana', $diaDaSemana);
            });
        $bolsistasCount = $bolsistasQuery->count();
        $bolsistas = $bolsistasQuery->orderBy('nome')->get();

        \Log::info('Bolsistas buscados', [
            'total_users_db' => $totalUsers,
            'dia_da_semana' => $diaDaSemana,
            'bolsistas_count_before_get' => $bolsistasCount,
            'bolsistas_count_after_get' => $bolsistas->count(),
            'first_bolsista' => $bolsistas->first() ? $bolsistas->first()->only(['id', 'matricula', 'nome', 'bolsista']) : null,
        ]);

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada para este dia e turno.',
                'error_code' => 'NO_MEAL',
                'data' => [],
                'info' => [
                    'data' => $data,
                    'turno' => $turno,
                    'total_bolsistas' => $bolsistas->count(),
                    'sugestao' => 'Crie um cardápio para esta data primeiro',
                ],
                'stats' => [
                    'total_bolsistas' => $bolsistas->count(),
                    'presentes' => 0,
                    'ausentes' => 0,
                    'refeicao' => null,
                ],
            ]);
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
                    'validado_em' => $presenca->validado_em,
                    'validado_por' => $presenca->validador ? $presenca->validador->nome : null,
                ] : null,
                'presente' => $presenca && $presenca->status_da_presenca === StatusPresenca::VALIDADO,
            ];
        });

        // Estatísticas
        $stats = [
            'total_bolsistas' => $bolsistas->count(),
            'presentes' => $lista->where('presente', true)->count(),
            'ausentes' => $lista->where('presente', false)->count(),
            'refeicao' => [
                'id' => $refeicao->id,
                'turno' => $refeicao->turno->value,
                'data' => $refeicao->data_do_cardapio,
                'cardapio' => $refeicao->cardapio ? $refeicao->cardapio->nome : null,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $lista->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * Confirma uma presença específica por ID (via botão na lista)
     * POST /api/v1/admin/presencas/{user_id}/confirmar
     */
    public function confirmarPorId(Request $request, $userId)
    {
        $turno = $request->input('turno');
        $dataInput = $request->input('data', now()->format('Y-m-d'));

        // Converter formato brasileiro (dd/mm/yyyy) para ISO (yyyy-mm-dd) se necessário
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataInput)) {
            $data = Carbon::createFromFormat('d/m/Y', $dataInput)->format('Y-m-d');
        } else {
            $data = $dataInput;
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'Turno é obrigatório.',
            ], 400);
        }

        // Busca o usuário
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Verifica se a data corresponde a um dia da semana que o aluno se cadastrou
        $diaDaSemana = Carbon::parse($data)->dayOfWeek;

        if (!$user->temDireitoRefeicaoNoDia($diaDaSemana)) {
            $diasCadastrados = $user->diasSemana()->get()->map(function ($dia) {
                return $dia->getDiaSemanaTexto();
            })->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'Este aluno não está cadastrado para se alimentar neste dia da semana.',
                'data' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum dia cadastrado',
                ],
            ], 403);
        }

        // Busca a refeição
        // Nota: data_do_cardapio está desnormalizada em refeicoes para performance
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada.',
            ], 404);
        }

        // Busca ou cria presença
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::VALIDADO) {
            return response()->json([
                'success' => false,
                'message' => 'Presença já confirmada.',
            ], 400);
        }

        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::VALIDADO,
                'registrado_em' => now(),
                'validado_em' => now(),
                'validado_por' => $request->user() ? $request->user()->id : 1,
            ]);
        } else {
            $presenca->validar($request->user() ? $request->user()->id : 1);
        }

        return response()->json([
            'success' => true,
            'message' => '✅ Presença confirmada!',
            'data' => [
                'usuario' => $user->nome,
                'validado_em' => $presenca->validado_em->format('H:i:s'),
            ],
        ]);
    }

    /**
     * Remove confirmação de presença (desfazer)
     * POST /api/v1/admin/presencas/{user_id}/remover-confirmacao
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
                'success' => false,
                'message' => 'Refeição não encontrada.',
            ], 404);
        }

        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if (!$presenca) {
            return response()->json([
                'success' => false,
                'message' => 'Presença não encontrada.',
            ], 404);
        }

        $presenca->delete();

        return response()->json([
            'success' => true,
            'message' => 'Confirmação removida.',
        ]);
    }

    /**
     * Confirma presença por QR Code (matrícula) ou botão
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

        // Converter formato brasileiro (dd/mm/yyyy) para ISO (yyyy-mm-dd) se necessário
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataInput)) {
            $data = Carbon::createFromFormat('d/m/Y', $dataInput)->format('Y-m-d');
        } else {
            $data = $dataInput;
        }

        // Busca o usuário
        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Matrícula não encontrada.',
            ], 404);
        }

        // Verifica se é bolsista ativo
        if (!$user->bolsista || $user->desligado) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não é bolsista ativo.',
            ], 403);
        }

        // Verifica se a data corresponde a um dia da semana que o aluno se cadastrou
        $diaDaSemana = Carbon::parse($data)->dayOfWeek; // 0=Domingo, 1=Segunda...

        if (!$user->temDireitoRefeicaoNoDia($diaDaSemana)) {
            $diasCadastrados = $user->diasSemana()->get()->map(function ($dia) {
                return $dia->getDiaSemanaTexto();
            })->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'Você não está cadastrado para se alimentar neste dia da semana.',
                'data' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum dia cadastrado',
                ],
            ], 403);
        }

        // Busca a refeição do dia e turno
        // Nota: data_do_cardapio está desnormalizada em refeicoes para performance
        // A data é sempre sincronizada com cardapios.data_do_cardapio via Model boot
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada para este dia e turno.',
            ], 404);
        }

        // Busca ou cria a presença
        $presenca = Presenca::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::VALIDADO) {
            return response()->json([
                'success' => false,
                'message' => 'Presença já foi confirmada anteriormente.',
                'data' => [
                    'usuario' => $user->nome,
                    'validado_em' => $presenca->validado_em->format('H:i:s'),
                ],
            ], 400);
        }

        if (!$presenca) {
            // Cria uma nova presença confirmada
            $presenca = Presenca::create([
                'user_id' => $user->id,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::VALIDADO,
                'registrado_em' => now(),
                'validado_em' => now(),
                'validado_por' => $request->user() ? $request->user()->id : 1, // 1 = Admin Sistema
            ]);
        } else {
            // Confirma presença existente
            $presenca->validar($request->user() ? $request->user()->id : 1);
        }

        return response()->json([
            'success' => true,
            'message' => '✅ Presença confirmada!',
            'data' => [
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
                'validado_em' => $presenca->validado_em->format('H:i:s'),
            ],
        ]);
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
            'success' => true,
            'message' => 'Falta marcada com sucesso.',
            'data' => $presenca,
        ]);
    }

    /**
     * Cancela uma presença
     * POST /api/v1/admin/presencas/{id}/cancelar
     */
    public function cancelar($id)
    {
        $presenca = Presenca::findOrFail($id);

        $presenca->update([
            'status_da_presenca' => StatusPresenca::CANCELADO,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Presença cancelada com sucesso.',
            'data' => $presenca,
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

        $stats = [
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
            ],
            'total' => $presencas->count(),
            'por_status' => [
                'confirmados' => $presencas->where('status_da_presenca', StatusPresenca::CONFIRMADO)->count(),
                'validados' => $presencas->where('status_da_presenca', StatusPresenca::VALIDADO)->count(),
                'faltas_justificadas' => $presencas->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA)->count(),
                'faltas_injustificadas' => $presencas->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA)->count(),
                'cancelados' => $presencas->where('status_da_presenca', StatusPresenca::CANCELADO)->count(),
            ],
            'taxa_presenca' => $presencas->count() > 0
                ? round(($presencas->whereIn('status_da_presenca', [StatusPresenca::CONFIRMADO, StatusPresenca::VALIDADO])->count() / $presencas->count()) * 100, 2)
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Validação em lote
     * POST /api/v1/admin/presencas/validar-lote
     */
    public function validarLote(Request $request)
    {
        $request->validate([
            'presenca_ids' => 'required|array',
            'presenca_ids.*' => 'required|exists:presencas,id',
        ]);

        $validadorId = $request->user() ? $request->user()->id : 1;
        $presencaIds = $request->input('presenca_ids');

        $presencas = Presenca::whereIn('id', $presencaIds)
            ->where('status_da_presenca', StatusPresenca::CONFIRMADO)
            ->get();

        $validadas = 0;
        foreach ($presencas as $presenca) {
            $presenca->validar($validadorId);
            $validadas++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$validadas} presença(s) validada(s) com sucesso.",
            'data' => [
                'total_solicitado' => count($presencaIds),
                'validadas' => $validadas,
            ],
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
                'success' => false,
                'message' => 'QR Code inválido ou presença já validada.',
            ], 404);
        }

        // Validar presença
        $presenca->validar($request->user() ? $request->user()->id : 1);

        return response()->json([
            'success' => true,
            'message' => "✅ Presença validada para {$presenca->user->nome}!",
            'data' => [
                'usuario' => $presenca->user->nome,
                'matricula' => $presenca->user->matricula,
                'refeicao' => [
                    'data' => $presenca->refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $presenca->refeicao->turno->value,
                ],
                'validado_em' => $presenca->validado_em->format('H:i:s'),
                'validado_por' => $request->user() ? $request->user()->nome : 'Admin Sistema',
            ],
        ]);
    }

    /**
     * Gerar QR Code para uma presença
     * GET /api/v1/admin/presencas/{id}/qrcode
     */
    public function gerarQrCode($id)
    {
        $presenca = Presenca::with(['user', 'refeicao'])->findOrFail($id);

        if ($presenca->status_da_presenca !== StatusPresenca::CONFIRMADO) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas presenças confirmadas podem gerar QR Code.',
            ], 400);
        }

        return response()->json([
            'success' => true,
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
        ]);
    }
}

