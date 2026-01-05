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
}
