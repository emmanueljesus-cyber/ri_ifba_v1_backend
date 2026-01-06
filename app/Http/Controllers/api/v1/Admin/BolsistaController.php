<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Models\UsuarioDiaSemana;
use App\Enums\StatusPresenca;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class BolsistaController extends Controller
{
    /**
     * Nomes dos dias da semana em português
     */
    private const DIAS_SEMANA = [
        0 => 'Domingo',
        1 => 'Segunda',
        2 => 'Terça',
        3 => 'Quarta',
        4 => 'Quinta',
        5 => 'Sexta',
        6 => 'Sábado',
    ];

    /**
     * Converte número do dia para texto
     */
    private function getDiaSemanaTexto(int $dia): string
    {
        return self::DIAS_SEMANA[$dia] ?? 'Desconhecido';
    }

    /**
     * RF09 - Lista bolsistas do dia por turno
     * GET /api/v1/admin/bolsistas/dia
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bolsistasDoDia(Request $request): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');

        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar bolsistas cadastrados para este dia da semana
        $bolsistas = User::where('bolsista', true)
            ->with('diasSemana')
            ->whereHas('diasSemana', function ($q) use ($diaSemana) {
                $q->where('dia_semana', $diaSemana);
            })
            ->orderBy('nome')
            ->get();

        // Buscar refeição do dia/turno para verificar presenças
        $refeicaoQuery = Refeicao::where('data_do_cardapio', $data);
        if ($turno) {
            $refeicaoQuery->where('turno', $turno);
        }
        $refeicao = $refeicaoQuery->first();

        // Buscar presenças já registradas
        $presencas = [];
        if ($refeicao) {
            $presencas = Presenca::where('refeicao_id', $refeicao->id)
                ->get()
                ->keyBy('user_id');
        }

        // Montar lista com status de presença
        $lista = $bolsistas->map(function ($bolsista) use ($presencas, $refeicao) {
            $presenca = $presencas[$bolsista->id] ?? null;

            $diasTexto = $bolsista->diasSemana->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))->implode(', ');

            return [
                'user_id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'curso' => $bolsista->curso,
                'turno_aluno' => $bolsista->turno,
                'is_bolsista' => true,
                'dias_semana' => $bolsista->diasSemana->pluck('dia_semana')->toArray(),
                'dias_semana_texto' => $diasTexto,
                'presenca' => $presenca ? [
                    'id' => $presenca->id,
                    'status' => $presenca->status_da_presenca->value,
                    'validado_em' => $presenca->validado_em?->format('d/m/Y H:i'),
                ] : null,
                'status_presenca' => $presenca ? $presenca->status_da_presenca->value : 'pendente',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $lista->values(),
            'meta' => [
                'data' => Carbon::parse($data)->format('d/m/Y'),
                'data_iso' => $data,
                'dia_semana' => $diaSemana,
                'dia_semana_texto' => Carbon::parse($data)->locale('pt_BR')->dayName,
                'turno_filtrado' => $turno,
                'total_bolsistas' => $bolsistas->count(),
                'refeicao_id' => $refeicao?->id,
            ],
            'stats' => [
                'total' => $bolsistas->count(),
                'confirmados' => $lista->where('status_presenca', 'confirmado')->count(),
                'pendentes' => $lista->where('status_presenca', 'pendente')->count(),
                'faltas_justificadas' => $lista->where('status_presenca', 'falta_justificada')->count(),
                'faltas_injustificadas' => $lista->where('status_presenca', 'falta_injustificada')->count(),
                'cancelados' => $lista->where('status_presenca', 'cancelado')->count(),
            ],
        ]);
    }

    /**
     * Lista todos os bolsistas
     * GET /api/v1/admin/bolsistas
     *
     * @return JsonResponse
     */
    public function todosBolsistas(): JsonResponse
    {
        $bolsistas = User::where('bolsista', true)
            ->with('diasSemana')
            ->orderBy('nome')
            ->get();

        $lista = $bolsistas->map(function ($bolsista) {
            $diasTexto = $bolsista->diasSemana->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))->implode(', ');

            return [
                'user_id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'email' => $bolsista->email,
                'curso' => $bolsista->curso,
                'turno' => $bolsista->turno,
                'is_bolsista' => true,
                'desligado' => $bolsista->desligado,
                'dias_semana' => $bolsista->diasSemana->pluck('dia_semana')->toArray(),
                'dias_semana_texto' => $diasTexto,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $lista->values(),
            'total' => $bolsistas->count(),
        ]);
    }

    /**
     * Lista estudantes por turno
     * GET /api/v1/admin/estudantes/turno
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function estudantesPorTurno(Request $request): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');
        $apenasAtivos = $request->boolean('apenas_ativos', true);

        $diaSemana = Carbon::parse($data)->dayOfWeek;

        // Buscar estudantes cadastrados para este dia
        $query = User::estudantes()
            ->whereHas('diasSemana', function ($q) use ($diaSemana) {
                $q->where('dia_semana', $diaSemana);
            });

        if ($apenasAtivos) {
            $query->where('desligado', false);
        }

        $estudantes = $query->orderBy('nome')->get();

        // Buscar refeição e presenças
        $refeicaoQuery = Refeicao::where('data_do_cardapio', $data);
        if ($turno) {
            $refeicaoQuery->where('turno', $turno);
        }
        $refeicao = $refeicaoQuery->first();

        $presencas = [];
        if ($refeicao) {
            $presencas = Presenca::where('refeicao_id', $refeicao->id)
                ->get()
                ->keyBy('user_id');
        }

        $lista = $estudantes->map(function ($estudante) use ($presencas) {
            $presenca = $presencas[$estudante->id] ?? null;

            return [
                'user_id' => $estudante->id,
                'matricula' => $estudante->matricula,
                'nome' => $estudante->nome,
                'curso' => $estudante->curso,
                'turno_aluno' => $estudante->turno,
                'is_bolsista' => $estudante->bolsista,
                'status_presenca' => $presenca ? $presenca->status_da_presenca->value : 'pendente',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $lista->values(),
            'meta' => [
                'data' => Carbon::parse($data)->format('d/m/Y'),
                'dia_semana_texto' => Carbon::parse($data)->locale('pt_BR')->dayName,
                'turno' => $turno,
                'total' => $estudantes->count(),
                'total_bolsistas' => $lista->where('is_bolsista', true)->count(),
                'total_nao_bolsistas' => $lista->where('is_bolsista', false)->count(),
            ],
        ]);
    }

    /**
     * Confirmar presença do bolsista (marcar como presente)
     * POST /api/v1/admin/bolsistas/{userId}/confirmar-presenca
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function confirmarPresenca(Request $request, int $userId): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');

        // Validar turno
        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'O turno é obrigatório (almoco ou jantar).',
            ], 400);
        }

        // Buscar usuário
        $user = User::with('diasSemana')->find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Verificar se é bolsista
        if (!$user->bolsista) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário não é bolsista.',
            ], 403);
        }

        // Verificar dia da semana
        $diaSemana = Carbon::parse($data)->dayOfWeek;
        $temDireito = $user->diasSemana()->where('dia_semana', $diaSemana)->exists();

        if (!$temDireito) {
            $diasCadastrados = $user->diasSemana->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))->implode(', ');
            return response()->json([
                'success' => false,
                'message' => 'Este bolsista não está cadastrado para este dia.',
                'data' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum dia cadastrado',
                ],
            ], 403);
        }

        // Buscar refeição
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada para este dia e turno.',
            ], 404);
        }

        // Buscar ou criar presença
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::CONFIRMADO) {
            return response()->json([
                'success' => false,
                'message' => 'Presença já foi confirmada anteriormente.',
                'data' => [
                    'presenca_id' => $presenca->id,
                    'validado_em' => $presenca->validado_em?->format('d/m/Y H:i'),
                ],
            ], 409);
        }

        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::CONFIRMADO,
                'validado_em' => now(),
                'validado_por' => auth()->id(),
                'registrado_em' => now(),
            ]);
        } else {
            $presenca->update([
                'status_da_presenca' => StatusPresenca::CONFIRMADO,
                'validado_em' => now(),
                'validado_por' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Presença confirmada com sucesso.',
            'data' => [
                'presenca_id' => $presenca->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
                'validado_em' => $presenca->validado_em->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Marcar falta do bolsista
     * POST /api/v1/admin/bolsistas/{userId}/marcar-falta
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function marcarFalta(Request $request, int $userId): JsonResponse
    {
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');
        $turno = $request->input('turno');
        $justificada = $request->boolean('justificada', false);

        // Validar turno
        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'O turno é obrigatório (almoco ou jantar).',
            ], 400);
        }

        // Buscar usuário
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ], 404);
        }

        // Buscar refeição
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada para este dia e turno.',
            ], 404);
        }

        $status = $justificada
            ? StatusPresenca::FALTA_JUSTIFICADA
            : StatusPresenca::FALTA_INJUSTIFICADA;

        // Buscar ou criar presença
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => $status,
                'validado_em' => now(),
                'validado_por' => auth()->id(),
                'registrado_em' => now(),
            ]);
        } else {
            $presenca->update([
                'status_da_presenca' => $status,
                'validado_em' => now(),
                'validado_por' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $justificada
                ? 'Falta justificada registrada com sucesso.'
                : 'Falta injustificada registrada com sucesso.',
            'data' => [
                'presenca_id' => $presenca->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'status' => $status->value,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
            ],
        ]);
    }

    /**
     * Confirmar presença em lote (múltiplos bolsistas)
     * POST /api/v1/admin/bolsistas/confirmar-lote
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmarLote(Request $request): JsonResponse
    {
        $userIds = $request->input('user_ids', []);
        $turno = $request->input('turno');
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum usuário selecionado.',
            ], 400);
        }

        if (!$turno) {
            return response()->json([
                'success' => false,
                'message' => 'O turno é obrigatório.',
            ], 400);
        }

        // Buscar refeição
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        if (!$refeicao) {
            return response()->json([
                'success' => false,
                'message' => 'Não há refeição cadastrada para este dia e turno.',
            ], 404);
        }

        $confirmados = 0;
        $erros = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            if (!$user) {
                $erros[] = "Usuário ID {$userId} não encontrado.";
                continue;
            }

            // Verificar se já tem presença confirmada
            $presencaExistente = Presenca::where('user_id', $userId)
                ->where('refeicao_id', $refeicao->id)
                ->where('status_da_presenca', StatusPresenca::CONFIRMADO)
                ->exists();

            if ($presencaExistente) {
                continue; // Já confirmado, pula
            }

            // Criar ou atualizar presença
            Presenca::updateOrCreate(
                [
                    'user_id' => $userId,
                    'refeicao_id' => $refeicao->id,
                ],
                [
                    'status_da_presenca' => StatusPresenca::CONFIRMADO,
                    'validado_em' => now(),
                    'validado_por' => auth()->id(),
                    'registrado_em' => now(),
                ]
            );

            $confirmados++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$confirmados} presenças confirmadas com sucesso.",
            'data' => [
                'total_solicitados' => count($userIds),
                'confirmados' => $confirmados,
                'erros' => $erros,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
            ],
        ]);
    }
}
