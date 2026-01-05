<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefeicaoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'data_do_cardapio' => $this->data_do_cardapio?->toDateString(),
            'turno'            => $this->turno,      // 'almoco' | 'jantar'
            'capacidade'       => $this->capacidade,
        ];
    }
}
