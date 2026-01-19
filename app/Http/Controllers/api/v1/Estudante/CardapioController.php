<?php

namespace App\Http\Controllers\api\v1\Estudante;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardapioResource;
use App\Services\CardapioService;

class CardapioController extends Controller
{
    public function __construct(private CardapioService $service)
    {
    }

    public function hoje()
    {
        $cardapio = $this->service->cardapioDeHoje();
        if (!$cardapio) {
            return response()->json(['message' => 'Nenhum cardápio para hoje.'], 404);
        }
        return new CardapioResource($cardapio);
    }

    public function presencaHoje(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $hoje = now()->toDateString();
        
        // Busca presença para hoje (pode ser almoço ou jantar, dependendo da hora)
        // Por simplicidade, retornamos a primeira pendente ou a última confirmada de hoje
        $presenca = \App\Models\Presenca::where('user_id', $user->id)
            ->whereHas('refeicao', function ($query) use ($hoje) {
                $query->where('data_do_cardapio', $hoje);
            })
            ->with('refeicao')
            ->orderBy('registrado_em', 'desc')
            ->first();

        if (!$presenca) {
            return response()->json(['message' => 'Nenhuma presença registrada para hoje.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $presenca->id,
                'status' => $presenca->status_da_presenca,
                'token' => $presenca->gerarTokenQrCode(),
                'refeicao' => [
                    'turno' => $presenca->refeicao->turno,
                    'data' => $presenca->refeicao->data_do_cardapio->toDateString(),
                ]
            ]
        ]);
    }
}
