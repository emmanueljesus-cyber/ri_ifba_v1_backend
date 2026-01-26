<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoMudancaDias extends Model
{
    protected $table = 'solicitacoes_mudanca_dias';

    protected $fillable = [
        'user_id',
        'dias_semana',
        'status',
        'motivo_rejeicao'
    ];

    protected $casts = [
        'dias_semana' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
