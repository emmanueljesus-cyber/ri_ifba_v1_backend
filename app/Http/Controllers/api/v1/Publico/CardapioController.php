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
            return response()->json(['data' => null, 'message' => 'Nenhum cardápio para hoje.'], 200);
        }
        $cardapio->loadMissing('refeicoes');
        return response()->json(['data' => new CardapioResource($cardapio)]);
    }

    public function semanal(Request $request)
    {
        $turno = $request->query('turno');
        $data = $request->query('data');
        $cardapios = $this->service->cardapioSemanal($turno, $data);

        // Expandir cardápios para incluir um registro por turno
        $cardapiosExpandidos = collect();

        foreach ($cardapios as $cardapio) {
            $refeicoes = $cardapio->refeicoes ?? collect();

            // Para cada refeição (turno), criar um registro de cardápio
            foreach ($refeicoes as $refeicao) {
                $turnoValue = $refeicao->turno instanceof \BackedEnum ? $refeicao->turno->value : $refeicao->turno;

                // Se houver filtro de turno, pula se não bater
                if ($turno && $turno !== $turnoValue) {
                    continue;
                }

                $cardapiosExpandidos->push([
                    'id' => $cardapio->id,
                    'data_do_cardapio' => $cardapio->data_do_cardapio?->toDateString(),
                    'turno' => $turnoValue,
                    'prato_principal_ptn01' => $cardapio->prato_principal_ptn01,
                    'prato_principal_ptn02' => $cardapio->prato_principal_ptn02,
                    'guarnicao' => $cardapio->guarnicao,
                    'acompanhamento_01' => $cardapio->acompanhamento_01,
                    'acompanhamento_02' => $cardapio->acompanhamento_02,
                    'salada' => $cardapio->salada,
                    'ovo_lacto_vegetariano' => $cardapio->ovo_lacto_vegetariano,
                    'suco' => $cardapio->suco,
                    'sobremesa' => $cardapio->sobremesa,
                    'created_at' => $cardapio->created_at?->toDateTimeString(),
                    'updated_at' => $cardapio->updated_at?->toDateTimeString(),
                    // Adiciona refeições para compatibilidade com o frontend se necessário
                    'refeicoes' => $refeicoes->map(fn($r) => [
                        'id' => $r->id,
                        'turno' => $r->turno instanceof \BackedEnum ? $r->turno->value : $r->turno,
                    ]),
                ]);
            }
        }

        return response()->json([
            'data' => $cardapiosExpandidos,
            'message' => 'Cardápios da semana'
        ]);
    }

    public function mensal(Request $request)
    {
        $turno = $request->query('turno');
        $perPage = $request->integer('per_page', 10);
        $cardapios = $this->service->cardapioMensal($turno, $perPage);

        // Expandir cardápios para incluir um registro por turno
        $cardapiosExpandidos = collect();

        foreach ($cardapios as $cardapio) {
            $refeicoes = $cardapio->refeicoes ?? collect();

            // Para cada refeição (turno), criar um registro de cardápio
            foreach ($refeicoes as $refeicao) {
                $turnoValue = $refeicao->turno instanceof \BackedEnum ? $refeicao->turno->value : $refeicao->turno;

                // Se houver filtro de turno, pula se não bater
                if ($turno && $turno !== $turnoValue) {
                    continue;
                }

                $cardapiosExpandidos->push([
                    'id' => $cardapio->id,
                    'data_do_cardapio' => $cardapio->data_do_cardapio?->toDateString(),
                    'turno' => $turnoValue,
                    'prato_principal_ptn01' => $cardapio->prato_principal_ptn01,
                    'prato_principal_ptn02' => $cardapio->prato_principal_ptn02,
                    'guarnicao' => $cardapio->guarnicao,
                    'acompanhamento_01' => $cardapio->acompanhamento_01,
                    'acompanhamento_02' => $cardapio->acompanhamento_02,
                    'salada' => $cardapio->salada,
                    'ovo_lacto_vegetariano' => $cardapio->ovo_lacto_vegetariano,
                    'suco' => $cardapio->suco,
                    'sobremesa' => $cardapio->sobremesa,
                    'created_at' => $cardapio->created_at?->toDateTimeString(),
                    'updated_at' => $cardapio->updated_at?->toDateTimeString(),
                    // Adiciona refeições para compatibilidade com o frontend
                    'refeicoes' => $refeicoes->map(fn($r) => [
                        'id' => $r->id,
                        'turno' => $r->turno instanceof \BackedEnum ? $r->turno->value : $r->turno,
                    ]),
                ]);
            }
        }

        return response()->json([
            'data' => $cardapiosExpandidos,
            'message' => 'Cardápios do mês'
        ]);
    }
}
