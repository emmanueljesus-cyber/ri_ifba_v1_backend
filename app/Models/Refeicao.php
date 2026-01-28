<?php

namespace App\Models;

use App\Enums\TurnoRefeicao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refeicao extends Model
{
    use HasFactory;

    protected $table = 'refeicoes';
    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';
    protected $fillable = [
        'cardapio_id',
        'data_do_cardapio',
        'turno',
        'capacidade',
    ];

    protected $casts = [
        'data_do_cardapio' => 'date',
        'criado_em'        => 'datetime',
        'atualizado_em'    => 'datetime',
        'turno'            => TurnoRefeicao::class,
    ];

     /**
     * Boot do model - garante sincronização da data
     */
    protected static function boot()
    {
        parent::boot();

        // Ao criar uma refeição, garante que a data seja a mesma do cardápio
        static::creating(function ($refeicao) {
            if ($refeicao->cardapio_id && !$refeicao->data_do_cardapio) {
                $cardapio = Cardapio::find($refeicao->cardapio_id);
                if ($cardapio) {
                    $refeicao->data_do_cardapio = $cardapio->data_do_cardapio;
                }
            }
        });

        // Ao atualizar, valida que a data seja consistente
        static::updating(function ($refeicao) {
            if ($refeicao->isDirty('cardapio_id')) {
                $cardapio = Cardapio::find($refeicao->cardapio_id);
                if ($cardapio) {
                    $refeicao->data_do_cardapio = $cardapio->data_do_cardapio;
                }
            }
        });
    }



    // ========== RELACIONAMENTOS ==========

    public function cardapio()
    {
        return $this->belongsTo(Cardapio::class);
    }

    public function presencas()
    {
        return $this->hasMany(Presenca::class);
    }

    public function justificativas()
    {
        return $this->hasMany(Justificativa::class);
    }

    public function filasExtras()
    {
        return $this->hasMany(FilaExtra::class);
    }

    // ========== SCOPES ==========

    public function scopeTurno($query, $turno)
    {
        return $query->where('turno', $turno);
    }

    public function scopeDataEntre($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_do_cardapio', [$dataInicio, $dataFim]);
    }

    public function scopeHoje($query)
    {
        return $query->where('data_do_cardapio', now()->toDateString());
    }

    public function scopeFuturas($query)
    {
        return $query->where('data_do_cardapio', '>=', now()->toDateString());
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function getPresentes()
    {
        return $this->presencas()
            ->where('status_da_presenca', 'presente')
            ->count();
    }

    /**
     * @deprecated Usar getPresentes() em vez disso
     */
    public function getConfirmados()
    {
        return $this->getPresentes();
    }

    public function getFaltas()
    {
        return $this->presencas()
            ->whereIn('status_da_presenca', ['falta_justificada', 'falta_injustificada'])
            ->count();
    }

    public function getVagasDisponiveis()
    {
        if (!$this->capacidade) {
            return null;
        }
        return $this->capacidade - $this->getConfirmados();
    }

    public function temVagasDisponiveis()
    {
        $vagas = $this->getVagasDisponiveis();
        return $vagas === null || $vagas > 0;
    }

    /**
     * Conta quantos bolsistas são esperados para esta refeição
     * Baseado em: bolsistas ativos + cadastrados para este dia da semana + turno
     */
    public function getBolsistasEsperados(): int
    {
        $data = $this->cardapio?->data_do_cardapio ?? $this->data_do_cardapio;
        if (!$data) return 0;

        $diaSemana = \Carbon\Carbon::parse($data)->dayOfWeek;
        $turno = $this->turno instanceof \BackedEnum ? $this->turno->value : $this->turno;

        return \App\Models\User::where('bolsista', true)
            ->where('desligado', false)
            ->whereHas('diasSemana', fn($q) => $q->where('dia_semana', $diaSemana))
            ->whereHas('aprovado', fn($q) => $q->where('turno_refeicao', $turno))
            ->count();
    }

    /**
     * Calcula vagas extras disponíveis considerando:
     * - Vagas extras fixas do config
     * - Bolsistas esperados que NÃO tiveram presença confirmada (status = presente)
     *
     * @return int Número de vagas extras disponíveis
     */
    public function getVagasExtrasDisponiveis(): int
    {
        $turno = $this->turno instanceof \BackedEnum ? $this->turno->value : $this->turno;

        // Vagas extras fixas do config
        $vagasExtrasFixas = $turno === 'almoco'
            ? config('restaurante.fila_extras.vagas_almoco', 20)
            : config('restaurante.fila_extras.vagas_jantar', 15);

        // Bolsistas esperados
        $bolsistasEsperados = $this->getBolsistasEsperados();

        // Bolsistas com presença confirmada (status = presente)
        $bolsistasPresentes = $this->getPresentes();

        // Bolsistas que faltaram = esperados - presentes
        $bolsistasFaltantes = max(0, $bolsistasEsperados - $bolsistasPresentes);

        // Vagas extras disponíveis = vagas fixas + bolsistas faltantes
        return $vagasExtrasFixas + $bolsistasFaltantes;
    }

    /**
     * Conta quantos extras já estão inscritos/aprovados
     */
    public function getExtrasInscritos(): int
    {
        return \App\Models\FilaExtra::where('refeicao_id', $this->id)
            ->whereIn('status_fila_extras', ['inscrito', 'aprovado'])
            ->count();
    }

    /**
     * Verifica se ainda há vagas extras disponíveis
     */
    public function temVagasExtras(): bool
    {
        return $this->getExtrasInscritos() < $this->getVagasExtrasDisponiveis();
    }
}
