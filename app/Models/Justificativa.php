<?php

namespace App\Models;

use App\Enums\TipoJustificativa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Justificativa extends Model
{
    use HasFactory;

    protected $table = 'justificativas';
    public $timestamps = false;


    protected $fillable = [
        'user_id',
        'refeicao_id',
        'tipo',
        'motivo',
        'anexo',
        'enviado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'tipo'       => TipoJustificativa::class,

    ];

    // ========== RELACIONAMENTOS ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refeicao()
    {
        return $this->belongsTo(Refeicao::class);
    }

    // ========== SCOPES ==========

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeAntecipada($query)
    {
        return $query->where('tipo', TipoJustificativa::ANTECIPADA);
    }

    public function scopePosterior($query)
    {
        return $query->where('tipo', TipoJustificativa::POSTERIOR);
    }

    public function scopeComAnexo($query)
    {
        return $query->whereNotNull('anexo');
    }

    public function scopeSemAnexo($query)
    {
        return $query->whereNull('anexo');
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function temAnexo()
    {
        return !empty($this->anexo);
    }

    public function getCaminhoAnexo()
    {
        return $this->anexo ? storage_path('app/justificativas/' . $this->anexo) : null;
    }

    public function getTipoTexto()
    {
        $tipos = [
            'antecipada' => 'Antecipada',
            'posterior'  => 'Posterior',
        ];

        return $tipos[$this->tipo?->value ?? $this->tipo] ?? $this->tipo;
    }
}
