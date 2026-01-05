<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CardapioResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                        => $this->id,
            'data_do_cardapio'         => optional($this->data_do_cardapio)->toDateString(),
            'prato_principal_ptn01'    => $this->prato_principal_ptn01,
            'prato_principal_ptn02'    => $this->prato_principal_ptn02,
            'guarnicao'                => $this->guarnicao,
            'acompanhamento_01'        => $this->acompanhamento_01,
            'acompanhamento_02'        => $this->acompanhamento_02,
            'salada'                   => $this->salada,
            'ovo_lacto_vegetariano'    => $this->ovo_lacto_vegetariano,
            'suco'                     => $this->suco,
            'sobremesa'                => $this->sobremesa,
            'criador' => $this->whenLoaded('criador', function () {
                return [
                    'id'        => $this->criador->id,
                    'nome'      => $this->criador->nome ?? $this->criador->name,
                    'matricula' => $this->criador->matricula ?? null,
                    'email'     => $this->criador->email,
                    'perfil'    => $this->criador->perfil ?? null,
                ];
            }),
            'refeicao' => $this->whenLoaded('refeicao', function () {
                return [
                    'id'               => $this->refeicao->id,
                    'data_do_cardapio' => optional($this->refeicao->data_do_cardapio)->toDateString(),
                    'turno'            => $this->refeicao->turno,
                    'capacidade'       => $this->refeicao->capacidade,
                ];
            }),
            'refeicoes' => $this->whenLoaded('refeicoes', function () {
                return $this->refeicoes->map(function ($refeicao) {
                    return [
                        'id'               => $refeicao->id,
                        'data_do_cardapio' => optional($refeicao->data_do_cardapio)->toDateString(),
                        'turno'            => $refeicao->turno,
                        'capacidade'       => $refeicao->capacidade,
                    ];
                });
            }),
            'criado_em'                => optional($this->criado_em)->toDateTimeString(),
            'atualizado_em'            => optional($this->atualizado_em)->toDateTimeString(),
        ];
    }
}
