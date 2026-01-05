<?php

namespace App\Http\Controllers\api\v1\Publico;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardapioResource;
use App\Services\CardapioService;
use Illuminate\Http\Request;

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

    public function semanal(Request $request)
    {
        $turno = $request->query('turno');
        $cardapios = $this->service->cardapioSemanal($turno);
        return CardapioResource::collection($cardapios);
    }

    public function mensal(Request $request)
    {
        $turno = $request->query('turno');
        $perPage = $request->integer('per_page', 10);
        $cardapios = $this->service->cardapioMensal($turno, $perPage);
        return CardapioResource::collection($cardapios);
    }
}
