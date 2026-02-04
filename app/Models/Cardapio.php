<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cardapio extends Model
{
    use HasFactory;

    public const CREATED_AT = 'criado_em';
    public const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'data_do_cardapio',
        'turnos',
        'prato_principal_ptn01',
        'prato_principal_ptn02',
        'guarnicao',
        'acompanhamento_01',
        'acompanhamento_02',
        'salada',
        'ovo_lacto_vegetariano',
        'suco',
        'sobremesa',
        'criado_por',
    ];

    protected $casts = [
        'data_do_cardapio' => 'date',
        'turnos' => 'array',
    ];

    /**
     * Boot do model - cria/sincroniza refeições automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        // Ao salvar cardápio (create OU update), sincroniza as refeições
        static::saved(function ($cardapio) {
            $turnos = $cardapio->turnos ?? ['almoco', 'jantar']; // Padrão: ambos

            // Remove refeições de turnos que não estão mais na lista
            $cardapio->refeicoes()
                ->whereNotIn('turno', $turnos)
                ->delete();

            // Cria ou atualiza refeições para cada turno
            foreach ($turnos as $turno) {
                $cardapio->refeicoes()->updateOrCreate(
                    ['turno' => $turno],
                    [
                        'data_do_cardapio' => $cardapio->data_do_cardapio,
                        'capacidade' => config('refeicoes.capacidade_padrao', 100),
                    ]
                );
            }
        });
    }

    // ========== RELACIONAMENTOS ==========

    public function criador()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function refeicao()
    {
        return $this->hasOne(Refeicao::class);
    }

    public function refeicoes()
    {
        return $this->hasMany(Refeicao::class);
    }

    // ========== SCOPES ==========

    public function scopeDataEntre($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_do_cardapio', [$dataInicio, $dataFim]);
    }

    public function scopeDataFutura($query)
    {
        return $query->whereDate('data_do_cardapio', '>=', now()->toDateString());
    }

    public function scopeDataPassada($query)
    {
        return $query->whereDate('data_do_cardapio', '<', now()->toDateString());
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_do_cardapio', now()->toDateString());
    }
}
