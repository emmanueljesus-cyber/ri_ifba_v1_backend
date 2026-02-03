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
        $isBolsistaModel = $this->resource instanceof \App\Models\Bolsista;
        
        if ($isBolsistaModel) {
            $bolsista = $this->resource;
            $user = $this->relationLoaded('user') ? $this->user : null;
            $isLinked = $this->user_id !== null;
            $id = $this->id;
            $matricula = $this->matricula;
            
            // Priorizar dados da tabela administrativa (bolsistas) conforme solicitado pelo admin
            $nome = $this->nome ?? $user?->nome;
            $curso = $this->curso ?? $user?->curso;
            $turno = $this->turno_refeicao ?? $user?->turno_refeicao;
            
            $ativo = (bool) $this->ativo;
            $desligadoMotivo = $this->desligado_motivo;
        } else {
            $user = $this->resource;
            $bolsista = $this->relationLoaded('aprovado') ? $this->aprovado : null;
            $isLinked = $bolsista !== null;
            $id = $user->id;
            $matricula = $user->matricula;
            $nome = $user->nome;
            $curso = $user->curso;
            $turno = $user->turno_refeicao;
            $ativo = !$user->desligado;
            $desligadoMotivo = $user->desligado_motivo;
        }

        // Dias da semana - Priorizar sempre a tabela administrativa (bolsistas)
        // O usuário não deve impactar na gestão do administrador
        $diasSemana = $isBolsistaModel ? ($bolsista->dias_semana ?? []) : [];
        
        if (empty($diasSemana) && $user && $user->relationLoaded('diasSemana')) {
            $diasSemana = $user->diasSemana->pluck('dia_semana')->toArray();
        }

        return [
            'id' => $id,
            'user_id' => $isBolsistaModel ? $this->user_id : $user->id,
            'matricula' => $matricula,
            'nome' => $nome,
            'email' => $user?->email,
            'foto_url' => $user?->foto_url ?? null,
            'curso' => $curso,
            'turno_refeicao' => $turno,
            'turno_aula' => $user?->turno_aula ?? null,
            'is_bolsista' => true,
            'ativo' => $ativo,
            'vinculado' => $isLinked,
            'desligado_motivo' => $desligadoMotivo,

            // Preferências e restrições alimentares
            'preferencia_alimentar' => $user?->preferencia_alimentar ?? null,
            'is_ovolactovegetariano' => ($user?->preferencia_alimentar ?? null) === 'ovolactovegetariano',
            'restricoes_alimentares' => $user?->restricoes_alimentares ?? [],
            'alergias' => $user?->alergias ?? null,

            'dias_semana' => $diasSemana,
            'dias_semana_texto' => collect($diasSemana)
                ->map(fn($d) => DateHelper::getDiaSemanaTexto($d))
                ->implode(', '),

            // Total de faltas
            'total_faltas' => $this->when($isLinked || !$isBolsistaModel, function() use ($isBolsistaModel, $user) {
                if ($isBolsistaModel && $this->user_id) {
                    return $this->contarFaltasNaoJustificadas();
                }
                if (!$isBolsistaModel) {
                    return \App\Models\Presenca::where('user_id', $user->id)
                        ->where('status_da_presenca', \App\Enums\StatusPresenca::FALTA_INJUSTIFICADA)
                        ->count();
                }
                return 0;
            }),

            // Dados de presença (quando aplicável)
            'presenca' => $this->when(isset($this->presenca_atual), function() {
                return $this->presenca_atual ? [
                    'id' => $this->presenca_atual->id,
                    'status' => $this->presenca_atual->status_da_presenca->value,
                    'confirmado_em' => DateHelper::formatarDataHoraBR($this->presenca_atual->validado_em),
                ] : null;
            }),
            'presenca_atual' => $this->when(isset($this->presenca_atual), function() {
                return $this->presenca_atual ? [
                    'id' => $this->presenca_atual->id,
                    'status_da_presenca' => $this->presenca_atual->status_da_presenca->value,
                    'confirmado_em' => DateHelper::formatarDataHoraBR($this->presenca_atual->validado_em),
                ] : null;
            }),
            'status_presenca' => $this->when(property_exists($this->resource, 'presenca_atual') || isset($this->presenca_atual),
                fn() => $this->presenca_atual ? $this->presenca_atual->status_da_presenca->value : 'pendente'
            ),
            'presente' => $this->when(property_exists($this->resource, 'presenca_atual') || isset($this->presenca_atual), 
                fn() => $this->presenca_atual && $this->presenca_atual->status_da_presenca->value === 'presente'
            ),

            // Dados de justificativa antecipada (quando aplicável)
            'tem_falta_antecipada' => $this->when(isset($this->tem_falta_antecipada), (bool) ($this->tem_falta_antecipada ?? false)),
            'justificativa_antecipada' => $this->when(isset($this->justificativa_antecipada), function() {
                return $this->justificativa_antecipada ? [
                    'id' => $this->justificativa_antecipada->id,
                    'motivo' => $this->justificativa_antecipada->motivo,
                    'status' => $this->justificativa_antecipada->status,
                    'criado_em' => DateHelper::formatarDataHoraBR($this->justificativa_antecipada->created_at),
                ] : null;
            }),
            
            // Para busca de confirmação
            'presenca_status' => $this->when(isset($this->presenca_status_busca), $this->presenca_status_busca),
            'presenca_id' => $this->when(isset($this->presenca_id_busca), $this->presenca_id_busca),
            'ja_presente' => $this->when(isset($this->ja_presente_flag), $this->ja_presente_flag),
        ];
    }
}
