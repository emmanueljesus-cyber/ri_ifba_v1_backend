<?php

namespace App\Models;

use App\Enums\TipoNotificacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model para notificações in-app
 * 
 * Armazena notificações para exibição dentro do sistema.
 * Complementa as notificações por e-mail.
 */
class Notificacao extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensagem',
        'dados',
        'lida_em',
    ];

    protected $casts = [
        'tipo' => TipoNotificacao::class,
        'dados' => 'array',
        'lida_em' => 'datetime',
    ];

    /**
     * Usuário que recebe a notificação
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Verifica se a notificação foi lida
     */
    public function foiLida(): bool
    {
        return $this->lida_em !== null;
    }

    /**
     * Marca a notificação como lida
     */
    public function marcarComoLida(): void
    {
        if (!$this->foiLida()) {
            $this->update(['lida_em' => now()]);
        }
    }

    /**
     * Scope: notificações não lidas
     */
    public function scopeNaoLidas($query)
    {
        return $query->whereNull('lida_em');
    }

    /**
     * Scope: notificações de um usuário
     */
    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
