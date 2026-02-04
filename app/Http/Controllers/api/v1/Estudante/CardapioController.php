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

    public function hoje(\Illuminate\Http\Request $request)
    {
        $data = $request->query('data');
        $cardapio = $this->service->cardapioDeHoje($data);
        
        if (!$cardapio) {
            return response()->json([
                'data' => null, 
                'message' => 'Nenhum cardápio para hoje.'
            ], 200);
        }

        // Forçar carregamento das refeições para garantir que o Resource as veja
        $cardapio->loadMissing('refeicoes');

        return response()->json([
            'data' => new CardapioResource($cardapio)
        ]);
    }

    public function presencaHoje(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $agora = now();
        $hoje = $agora->toDateString();
        $diaSemana = $agora->dayOfWeek; // 0 (Dom) a 6 (Sáb)
        
        // Determinar o turno atual com base na hora
        $hora = $agora->hour;
        $turnoAtual = ($hora < 15) ? 'almoco' : 'jantar';

        // 1. Tentar buscar presença registrada para hoje (qualquer turno de hoje)
        $presenca = \App\Models\Presenca::where('user_id', $user->id)
            ->whereHas('refeicao', function ($query) use ($hoje) {
                $query->whereDate('data_do_cardapio', $hoje);
            })
            ->with('refeicao')
            ->orderBy('registrado_em', 'desc')
            ->first();

        if ($presenca) {
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

        // 2. Se for BOLSISTA e não tem presença ainda, verificar se tem direito à refeição no turno atual
        if ($user->bolsista) {
            // Busca a refeição do turno atual para hoje
            $refeicaoTurno = \App\Models\Refeicao::whereDate('data_do_cardapio', $hoje)
                ->where('turno', $turnoAtual)
                ->first();

            if ($refeicaoTurno && $user->temDireitoRefeicaoNoDia($diaSemana)) {
                return response()->json([
                    'data' => [
                        'id' => null,
                        'status' => 'disponivel', // Status virtual para bolsista pendente
                        'token' => hash('sha256', $user->id . $refeicaoTurno->id . config('app.key')),
                        'refeicao' => [
                            'turno' => $refeicaoTurno->turno,
                            'data' => $refeicaoTurno->data_do_cardapio->toDateString(),
                        ]
                    ]
                ]);
            }
        }

        // 3. Se NÃO FOR BOLSISTA, tenta buscar se tem inscrição aprovada na Fila Extra para o turno atual
        if (!$user->bolsista) {
            $inscricao = \App\Models\FilaExtra::where('user_id', $user->id)
                ->whereHas('refeicao', function ($query) use ($hoje, $turnoAtual) {
                    $query->where('data_do_cardapio', $hoje)
                          ->where('turno', $turnoAtual);
                })
                ->where('status_fila_extras', \App\Enums\StatusFila::APROVADO)
                ->with('refeicao')
                ->first();

            if ($inscricao) {
                return response()->json([
                    'data' => [
                        'id' => null,
                        'status' => 'aprovado_extra',
                        'token' => hash('sha256', $user->id . $inscricao->refeicao_id . config('app.key')),
                        'refeicao' => [
                            'turno' => $inscricao->refeicao->turno,
                            'data' => $inscricao->refeicao->data_do_cardapio->toDateString(),
                        ]
                    ]
                ]);
            }
        }

        // Se chegou aqui e não encontrou nada, retorna um objeto vazio com 200 para o frontend não quebrar
        // ou 404 se preferir manter o comportamento de "nada encontrado" sem erro de console
        return response()->json(['message' => 'Nenhuma presença ou direito à refeição encontrado para o turno atual.'], 404);
    }
}
