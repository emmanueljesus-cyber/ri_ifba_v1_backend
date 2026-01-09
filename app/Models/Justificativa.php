<?php

namespace App\Models;

use App\Enums\TipoJustificativa;
use App\Enums\StatusJustificativa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Justificativa extends Model
{
    use HasFactory;

    protected $table = 'justificativas';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'refeicao_id',
        'tipo',
        'motivo',
        'anexo',
        'enviado_em',
        'status',
        'avaliado_por',
        'avaliado_em',
        'motivo_rejeicao',
    ];

    protected $casts = [
        'enviado_em'  => 'datetime',
        'avaliado_em' => 'datetime',
        'tipo'        => TipoJustificativa::class,
        'status'      => StatusJustificativa::class,
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

    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por');
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

    public function scopePendentes($query)
    {
        return $query->where('status', StatusJustificativa::PENDENTE);
    }

    public function scopeAprovadas($query)
    {
        return $query->where('status', StatusJustificativa::APROVADA);
    }

    public function scopeRejeitadas($query)
    {
        return $query->where('status', StatusJustificativa::REJEITADA);
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

    public function isPendente()
    {
        return $this->status === StatusJustificativa::PENDENTE || $this->status === null;
    }

    public function isAprovada()
    {
        return $this->status === StatusJustificativa::APROVADA;
    }

    public function isRejeitada()
    {
        return $this->status === StatusJustificativa::REJEITADA;
    }

    public function aprovar(int $adminId)
    {
        $this->update([
            'status' => StatusJustificativa::APROVADA,
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
        ]);
    }

    public function rejeitar(int $adminId, ?string $motivo = null)
    {
        $this->update([
            'status' => StatusJustificativa::REJEITADA,
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
            'motivo_rejeicao' => $motivo,
        ]);
    }
}

