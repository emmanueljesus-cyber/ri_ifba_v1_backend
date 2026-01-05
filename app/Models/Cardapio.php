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
     * Boot do model - cria refeições automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        // Ao criar cardápio, cria as refeições automaticamente
        static::created(function ($cardapio) {
            $turnos = $cardapio->turnos ?? ['almoco', 'jantar']; // Padrão: ambos

            foreach ($turnos as $turno) {
                $cardapio->refeicoes()->create([
                    'turno' => $turno,
                    'data_do_cardapio' => $cardapio->data_do_cardapio,
                    'capacidade' => config('refeicoes.capacidade_padrao', 100),
                ]);
            }
        });

        // Ao atualizar turnos, sincroniza refeições
        static::updated(function ($cardapio) {
            if ($cardapio->wasChanged('turnos')) {
                $turnosAtuais = $cardapio->turnos ?? ['almoco', 'jantar'];

                // Remove refeições que não estão mais nos turnos
                $cardapio->refeicoes()
                    ->whereNotIn('turno', $turnosAtuais)
                    ->delete();

                // Adiciona refeições para novos turnos
                foreach ($turnosAtuais as $turno) {
                    $cardapio->refeicoes()->firstOrCreate(
                        ['turno' => $turno],
                        [
                            'data_do_cardapio' => $cardapio->data_do_cardapio,
                            'capacidade' => config('refeicoes.capacidade_padrao', 100),
                        ]
                    );
                }
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
        return $query->where('data_do_cardapio', '>=', now()->toDateString());
    }

    public function scopeDataPassada($query)
    {
        return $query->where('data_do_cardapio', '<', now()->toDateString());
    }

    public function scopeHoje($query)
    {
        return $query->where('data_do_cardapio', now()->toDateString());
    }
}
