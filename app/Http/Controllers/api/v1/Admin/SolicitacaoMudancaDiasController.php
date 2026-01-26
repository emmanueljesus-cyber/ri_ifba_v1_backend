<?php

namespace App\Http\Controllers\api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SolicitacaoMudancaDias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitacaoMudancaDiasController extends Controller
{
    /**
     * Listar solicitações pendentes
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pendente');
        
        $solicitacoes = SolicitacaoMudancaDias::with('user')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($solicitacoes);
    }

    /**
     * Aprovar solicitação
     */
    public function aprovar(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $solicitacao = SolicitacaoMudancaDias::findOrFail($id);

            if ($solicitacao->status !== 'pendente') {
                return ApiResponse::error('Esta solicitação já foi processada.', 422);
            }

            // Atualiza os dias do usuário
            $user = $solicitacao->user;
            $user->diasSemana()->delete();

            $diasParaInserir = collect($solicitacao->dias_semana)->unique()->map(function ($dia) use ($user) {
                return ['user_id' => $user->id, 'dia_semana' => $dia];
            })->toArray();

            $user->diasSemana()->insert($diasParaInserir);

            // Marca solicitação como aprovada
            $solicitacao->update(['status' => 'aprovada']);

            DB::commit();

            return ApiResponse::success(null, 'Solicitação aprovada e dias atualizados com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Erro ao aprovar solicitação: ' . $e->getMessage());
        }
    }

    /**
     * Rejeitar solicitação
     */
    public function rejeitar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo_rejeicao' => 'required|string|max:255'
        ]);

        try {
            $solicitacao = SolicitacaoMudancaDias::findOrFail($id);

            if ($solicitacao->status !== 'pendente') {
                return ApiResponse::error('Esta solicitação já foi processada.', 422);
            }

            $solicitacao->update([
                'status' => 'rejeitada',
                'motivo_rejeicao' => $request->motivo_rejeicao
            ]);

            return ApiResponse::success(null, 'Solicitação rejeitada com sucesso.');

        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao rejeitar solicitação: ' . $e->getMessage());
        }
    }
}
