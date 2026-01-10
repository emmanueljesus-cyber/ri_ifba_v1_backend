<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BolsistaImportRequest;
use App\Models\User;
use App\Models\Refeicao;
use App\Models\Presenca;
use App\Models\UsuarioDiaSemana;
use App\Enums\StatusPresenca;
use App\Services\BolsistaImportService;
use App\Services\PresencaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BolsistaController extends Controller
{
    public function __construct(
        protected PresencaService $presencaService
    ) {}
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
                    'confirmado_em' => $presenca->validado_em?->format('d/m/Y H:i'),
                ] : null,
                'status_presenca' => $presenca ? $presenca->status_da_presenca->value : 'pendente',
            ];
        });

        return response()->json([
            'data' => $lista->values(),
            'errors' => [],
            'meta' => [
                'data' => Carbon::parse($data)->format('d/m/Y'),
                'data_iso' => $data,
                'dia_semana' => $diaSemana,
                'dia_semana_texto' => Carbon::parse($data)->locale('pt_BR')->dayName,
                'turno_filtrado' => $turno,
                'total_bolsistas' => $bolsistas->count(),
                'refeicao_id' => $refeicao?->id,
                'stats' => [
                    'total' => $bolsistas->count(),
                    'presentes' => $lista->where('status_presenca', 'presente')->count(),
                    'pendentes' => $lista->where('status_presenca', 'pendente')->count(),
                    'faltas_justificadas' => $lista->where('status_presenca', 'falta_justificada')->count(),
                    'faltas_injustificadas' => $lista->where('status_presenca', 'falta_injustificada')->count(),
                    'cancelados' => $lista->where('status_presenca', 'cancelado')->count(),
                ],
            ],
        ]);
    }

    /**
     * RF10 - Lista todos os bolsistas
     * GET /api/v1/admin/bolsistas
     */
    public function todosBolsistas(Request $request): JsonResponse
    {
        $query = User::where('bolsista', true)->with('diasSemana');

        // Filtros opcionais
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
                'ativo' => !$bolsista->desligado,
                'dias_semana' => $bolsista->diasSemana->pluck('dia_semana')->toArray(),
                'dias_semana_texto' => $diasTexto,
            ];
        });

        return response()->json([
            'data' => $lista->values(),
            'errors' => [],
            'meta' => [
                'total' => $bolsistas->count(),
                'ativos' => $lista->where('ativo', true)->count(),
                'inativos' => $lista->where('ativo', false)->count(),
            ],
        ]);
    }

    /**
     * RF13 - Buscar bolsista para confirmação manual
     * GET /api/v1/admin/bolsistas/buscar
     *
     * Busca bolsistas por nome ou matrícula para confirmação manual de presença
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

        // Buscar bolsistas que correspondem à busca e têm direito ao dia
        $bolsistas = User::where('bolsista', true)
            ->where('desligado', false)
            ->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('matricula', 'like', "%{$search}%");
            })
            ->whereHas('diasSemana', function ($q) use ($diaSemana) {
                $q->where('dia_semana', $diaSemana);
            })
            ->limit(10)
            ->get();

        // Buscar refeição
        $refeicao = Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();

        // Mapear com status de presença
        $lista = $bolsistas->map(function ($bolsista) use ($refeicao, $data, $turno) {
            $presenca = null;
            if ($refeicao) {
                $presenca = Presenca::where('user_id', $bolsista->id)
                    ->where('refeicao_id', $refeicao->id)
                    ->first();
            }

            return [
                'user_id' => $bolsista->id,
                'matricula' => $bolsista->matricula,
                'nome' => $bolsista->nome,
                'curso' => $bolsista->curso,
                'turno_aluno' => $bolsista->turno,
                'presenca_status' => $presenca ? $presenca->status_da_presenca->value : 'sem_registro',
                'presenca_id' => $presenca?->id,
                'ja_presente' => $presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE,
            ];
        });

        return response()->json([
            'data' => $lista->values(),
            'errors' => [],
            'meta' => [
                'total' => $lista->count(),
                'data' => Carbon::parse($data)->format('d/m/Y'),
                'data_iso' => $data,
                'turno' => $turno,
                'refeicao_id' => $refeicao?->id,
                'tem_refeicao' => $refeicao !== null,
            ],
        ]);
    }

    /**
     * RF13 - Confirmar presença por QR Code (matrícula)
     * POST /api/v1/admin/bolsistas/qrcode
     *
     * Recebe a matrícula escaneada do QR Code e confirma presença
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

        // Buscar usuário pela matrícula
        $user = User::where('matricula', $matricula)->first();

        if (!$user) {
            return response()->json([
                'data' => null,
                'errors' => ['matricula' => ['Matrícula não encontrada.']],
                'meta' => [],
            ], 404);
        }

        if (!$user->bolsista) {
            return response()->json([
                'data' => null,
                'errors' => ['matricula' => ['Este usuário não é bolsista.']],
                'meta' => [],
            ], 422);
        }

        if ($user->desligado) {
            return response()->json([
                'data' => null,
                'errors' => ['matricula' => ['Este bolsista está desligado.']],
                'meta' => [],
            ], 422);
        }

        // Verificar se tem direito ao dia
        $temDireito = $user->diasSemana()->where('dia_semana', $diaSemana)->exists();

        if (!$temDireito) {
            $diasCadastrados = $user->diasSemana->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))->implode(', ');
            return response()->json([
                'data' => null,
                'errors' => ['permissao' => ['Bolsista não tem direito a refeição neste dia.']],
                'meta' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum',
                ],
            ], 422);
        }

        // Buscar refeição
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

        // Verificar se já está confirmado
        $presenca = Presenca::where('user_id', $user->id)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE) {
            return response()->json([
                'data' => [
                    'usuario' => $user->nome,
                    'matricula' => $user->matricula,
                    'curso' => $user->curso,
                    'confirmado_em' => $presenca->validado_em?->format('H:i:s'),
                ],
                'errors' => [],
                'meta' => [
                    'message' => '⚠️ Presença já estava confirmada.',
                    'ja_presente' => true,
                ],
            ], 200);
        }

        // Criar ou atualizar presença
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
                'presenca_id' => $presenca->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'curso' => $user->curso,
                'refeicao' => [
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
                'confirmado_em' => now()->format('H:i:s'),
            ],
            'errors' => [],
            'meta' => [
                'message' => "✅ Presença confirmada para {$user->nome}!",
            ],
        ], 201);
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

        $query = User::estudantes()
            ->whereHas('diasSemana', function ($q) use ($diaSemana) {
                $q->where('dia_semana', $diaSemana);
            });

        if ($apenasAtivos) {
            $query->where('desligado', false);
        }

        $estudantes = $query->orderBy('nome')->get();

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
            'data' => $lista->values(),
            'errors' => [],
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
     * Controller apenas orquestra. Lógica de negócio está no PresencaService.
     */
    public function confirmarPresenca(Request $request, int $userId): JsonResponse
    {
        $resultado = $this->presencaService->confirmarPresencaCompleta(
            $userId,
            Carbon::parse($request->input('data', now()))->format('Y-m-d'),
            $request->input('turno', ''),
            $request->user()?->id
        );

        if (!$resultado['sucesso']) {
            return response()->json([
                'data' => null,
                'errors' => ['erro' => [$resultado['erro']]],
                'meta' => $resultado['meta'],
            ], $resultado['status_code']);
        }

        return response()->json([
            'data' => $resultado['data'],
            'errors' => [],
            'meta' => $resultado['meta'],
        ], $resultado['status_code']);
    }

    /**
     * Marcar falta do bolsista
     * POST /api/v1/admin/bolsistas/{userId}/marcar-falta
     * 
     * Controller apenas orquestra. Lógica de negócio está no PresencaService.
     */
    public function marcarFalta(Request $request, int $userId): JsonResponse
    {
        $resultado = $this->presencaService->marcarFaltaCompleta(
            $userId,
            Carbon::parse($request->input('data', now()))->format('Y-m-d'),
            $request->input('turno', ''),
            $request->boolean('justificada', false),
            $request->user()?->id
        );

        if (!$resultado['sucesso']) {
            return response()->json([
                'data' => null,
                'errors' => ['erro' => [$resultado['erro']]],
                'meta' => $resultado['meta'],
            ], $resultado['status_code']);
        }

        return response()->json([
            'data' => $resultado['data'],
            'errors' => [],
            'meta' => $resultado['meta'],
        ], $resultado['status_code']);
    }

    /**
     * Confirmar presença em lote (múltiplos bolsistas)
     * POST /api/v1/admin/bolsistas/confirmar-lote
     */
    public function confirmarLote(Request $request): JsonResponse
    {
        $userIds = $request->input('user_ids', []);
        $turno = $request->input('turno');
        $data = Carbon::parse($request->input('data', now()))->format('Y-m-d');

        if (empty($userIds)) {
            return response()->json([
                'data' => null,
                'errors' => ['user_ids' => ['Nenhum usuário selecionado.']],
                'meta' => [],
            ], 400);
        }

        if (!$turno) {
            return response()->json([
                'data' => null,
                'errors' => ['turno' => ['O turno é obrigatório.']],
                'meta' => [],
            ], 400);
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

        return response()->json([
            'data' => [
                'total_solicitados' => count($userIds),
                'confirmados' => $confirmados,
                'ja_confirmados' => $jaConfirmados,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
            ],
            'errors' => $erros,
            'meta' => ['message' => "{$confirmados} presença(s) confirmada(s) com sucesso."],
        ]);
    }

    /**
     * RF15 - Importar lista de bolsistas via Excel/CSV
     * POST /api/v1/admin/bolsistas/import
     *
     * Permite anexar/inserir lista de bolsistas por turno de refeição
     */
    public function import(BolsistaImportRequest $request, BolsistaImportService $service): JsonResponse
    {
        $file = $request->file('file');
        $turnoPadrao = $request->input('turno_padrao');
        $atualizarExistentes = $request->boolean('atualizar_existentes', true);

        try {
            // Ler arquivo Excel/CSV
            $rows = Excel::toArray(new class {}, $file)[0] ?? [];

            if (empty($rows)) {
                return response()->json([
                    'data' => null,
                    'errors' => ['file' => ['Arquivo vazio ou formato inválido.']],
                    'meta' => [],
                ], 422);
            }

            // Processar importação
            $resultado = $service->import($rows, $turnoPadrao, $atualizarExistentes);

            return response()->json([
                'data' => [
                    'criados' => $resultado['created'],
                    'atualizados' => $resultado['updated'],
                ],
                'errors' => $resultado['errors'],
                'meta' => $resultado['meta'],
            ], count($resultado['errors']) > 0 ? 207 : 201);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'errors' => ['file' => ['Erro ao processar arquivo: ' . $e->getMessage()]],
                'meta' => [],
            ], 500);
        }
    }
}
