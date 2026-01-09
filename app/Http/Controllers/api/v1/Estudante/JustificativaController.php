<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Models\Justificativa;
use App\Models\Presenca;
use App\Models\Refeicao;
use App\Enums\TipoJustificativa;
use App\Enums\StatusJustificativa;
use App\Enums\StatusPresenca;
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
        $request->validate([
            'refeicao_id' => 'required|exists:refeicoes,id',
            'tipo' => 'required|in:antecipada,posterior',
            'motivo' => 'required|string|max:1000',
            'anexo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        $userId = $request->user()?->id ?? $request->input('user_id');
        $refeicaoId = $request->input('refeicao_id');
        $tipo = $request->input('tipo');

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
        $hoje = now()->startOfDay();
        $dataRefeicao = $refeicao->data_do_cardapio;

        // Validar tipo vs data
        if ($tipo === 'antecipada' && $dataRefeicao->lt($hoje)) {
            return response()->json([
                'data' => null,
                'errors' => ['tipo' => ['Justificativa antecipada só pode ser enviada para datas futuras ou do dia atual.']],
                'meta' => [],
            ], 422);
        }

        if ($tipo === 'posterior' && $dataRefeicao->gte($hoje)) {
            return response()->json([
                'data' => null,
                'errors' => ['tipo' => ['Justificativa posterior só pode ser enviada para datas passadas.']],
                'meta' => [],
            ], 422);
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

        $statusTexto = $tipo === 'antecipada' 
            ? '✅ Justificativa aprovada automaticamente. Você está isento desta refeição.'
            : '⏳ Justificativa enviada. Aguardando avaliação do administrador.';

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
