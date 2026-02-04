<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CardapioResource extends JsonResource
{
    public function toArray($request)
    {
        // Determinar quais turnos existem
        $refeicoes = $this->relationLoaded('refeicoes') ? $this->refeicoes : $this->refeicoes()->get();
        
        $refeicaoAlmoco = $refeicoes->first(function($r) {
            $val = $r->turno instanceof \BackedEnum ? $r->turno->value : (string)$r->turno;
            return strtolower(trim($val)) === 'almoco';
        });
        
        $refeicaoJantar = $refeicoes->first(function($r) {
            $val = $r->turno instanceof \BackedEnum ? $r->turno->value : (string)$r->turno;
            return strtolower(trim($val)) === 'jantar';
        });
        
        // Formatar refeição para o frontend
        $formatarRefeicao = function($refeicao) {
            if (!$refeicao) return null;
            return [
                'id' => $refeicao->id,
                'turno' => $refeicao->turno instanceof \BackedEnum ? $refeicao->turno->value : $refeicao->turno,
                'prato_principal' => $this->prato_principal_ptn01,
                'prato_principal_ptn02' => $this->prato_principal_ptn02,
                'acompanhamento' => trim(($this->acompanhamento_01 ?? '') . ($this->acompanhamento_02 ? ', ' . $this->acompanhamento_02 : '')),
                'guarnicao' => $this->guarnicao,
                'salada' => $this->salada,
                'sobremesa' => $this->sobremesa,
                'suco' => $this->suco,
                'ovo_lacto_vegetariano' => $this->ovo_lacto_vegetariano,
                'capacidade' => $refeicao->capacidade,
                'vagas_extras_disponiveis' => $refeicao->capacidade ?? 0,
            ];
        };

        return [
            'id'                        => $this->id,
            'data'                      => optional($this->data_do_cardapio)->toDateString(),
            'data_do_cardapio'          => optional($this->data_do_cardapio)->toDateString(),
            'data_formatada'            => optional($this->data_do_cardapio)->format('d/m/Y'),
            'dia_semana'                => optional($this->data_do_cardapio)?->locale('pt_BR')->dayName ?? '',
            'almoco'                    => $formatarRefeicao($refeicaoAlmoco),
            'jantar'                    => $formatarRefeicao($refeicaoJantar),
            'prato_principal_ptn01'     => $this->prato_principal_ptn01,
            'prato_principal_ptn02'     => $this->prato_principal_ptn02,
            'guarnicao'                 => $this->guarnicao,
            'acompanhamento_01'         => $this->acompanhamento_01,
            'acompanhamento_02'         => $this->acompanhamento_02,
            'salada'                    => $this->salada,
            'ovo_lacto_vegetariano'     => $this->ovo_lacto_vegetariano,
            'suco'                      => $this->suco,
            'sobremesa'                 => $this->sobremesa,
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
                    'turno'            => $this->refeicao->turno instanceof \BackedEnum ? $this->refeicao->turno->value : $this->refeicao->turno,
                    'capacidade'       => $this->refeicao->capacidade,
                ];
            }),
            'refeicoes' => $this->whenLoaded('refeicoes', function () {
                return $this->refeicoes->map(function ($refeicao) {
                    return [
                        'id'               => $refeicao->id,
                        'data_do_cardapio' => optional($refeicao->data_do_cardapio)->toDateString(),
                        'turno'            => $refeicao->turno instanceof \BackedEnum ? $refeicao->turno->value : $refeicao->turno,
                        'capacidade'       => $refeicao->capacidade,
                    ];
                });
            }),
            'criado_em'                 => optional($this->criado_em)->toDateTimeString(),
            'atualizado_em'             => optional($this->atualizado_em)->toDateTimeString(),
        ];
    }
}
