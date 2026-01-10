<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para formatação de dados de bolsistas
 * 
 * Elimina duplicação de formatação manual em arrays
 */
class BolsistaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'matricula' => $this->matricula,
            'nome' => $this->nome,
            'email' => $this->when($request->routeIs('*.todosBolsistas'), $this->email),
            'curso' => $this->curso,
            'turno_aluno' => $this->turno,
            'is_bolsista' => true,
            'ativo' => $this->when(isset($this->desligado), !$this->desligado),
            'dias_semana' => $this->when($this->relationLoaded('diasSemana'), 
                fn() => $this->diasSemana->pluck('dia_semana')->toArray()
            ),
            'dias_semana_texto' => $this->when($this->relationLoaded('diasSemana'), 
                fn() => $this->diasSemana
                    ->map(fn($d) => DateHelper::getDiaSemanaTexto($d->dia_semana))
                    ->implode(', ')
            ),
            // Dados de presença (quando aplicável)
            'presenca' => $this->when(isset($this->presenca_atual), function() {
                return $this->presenca_atual ? [
                    'id' => $this->presenca_atual->id,
                    'status' => $this->presenca_atual->status_da_presenca->value,
                    'confirmado_em' => DateHelper::formatarDataHoraBR($this->presenca_atual->validado_em),
                ] : null;
            }),
            'status_presenca' => $this->when(isset($this->presenca_atual), 
                fn() => $this->presenca_atual ? $this->presenca_atual->status_da_presenca->value : 'pendente'
            ),
            'presente' => $this->when(isset($this->presenca_atual), 
                fn() => $this->presenca_atual && $this->presenca_atual->status_da_presenca->value === 'presente'
            ),
            // Para busca de confirmação
            'presenca_status' => $this->when(isset($this->presenca_status_busca), $this->presenca_status_busca),
            'presenca_id' => $this->when(isset($this->presenca_id_busca), $this->presenca_id_busca),
            'ja_presente' => $this->when(isset($this->ja_presente_flag), $this->ja_presente_flag),
        ];
    }
}
