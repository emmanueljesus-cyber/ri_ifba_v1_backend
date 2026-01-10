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
}
