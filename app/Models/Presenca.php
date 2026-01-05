<?php

namespace App\Models;

use App\Enums\StatusPresenca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presenca extends Model
{
    use HasFactory;
    protected $table = 'presencas';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'refeicao_id',
        'status_da_presenca',
        'validado_em',
        'validado_por',
        'registrado_em',
    ];

    protected $casts = [
        'confirmado_em' => 'datetime',
        'validado_em'   => 'datetime',
        'registrado_em' => 'datetime',
        'status_da_presenca' => StatusPresenca::class,
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

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    // ========== SCOPES ==========

    public function scopeConfirmados($query)
    {
        return $query->where('status_da_presenca', 'confirmado');
    }

    public function scopeValidados($query)
    {
        return $query->where('status_da_presenca', 'validado');
    }

    public function scopeFaltasJustificadas($query)
    {
        return $query->where('status_da_presenca', 'falta_justificada');
    }

    public function scopeFaltasInjustificadas($query)
    {
        return $query->where('status_da_presenca', 'falta_injustificada');
    }

    public function scopeDoMes($query, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereMonth('registrado_em', $mes)
            ->whereYear('registrado_em', $ano);
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function validar($validadorId)
    {
        $this->update([
            'status_da_presenca' => StatusPresenca::VALIDADO,
            'validado_em' => now(),
            'validado_por' => $validadorId,
        ]);
    }

    public function marcarFalta($justificada = false)
    {
        $this->update([
            'status_da_presenca' => $justificada ? StatusPresenca::FALTA_JUSTIFICADA : StatusPresenca::FALTA_INJUSTIFICADA,
        ]);
    }

    public function isConfirmado()
    {
        return $this->status_da_presenca === StatusPresenca::CONFIRMADO;
    }

    public function isValidado()
    {
        return $this->status_da_presenca === StatusPresenca::VALIDADO;
    }

    public function isFalta()
    {
        return in_array($this->status_da_presenca, [StatusPresenca::FALTA_JUSTIFICADA, StatusPresenca::FALTA_INJUSTIFICADA]);
    }
}
