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

    public function scopePresentes($query)
    {
        return $query->where('status_da_presenca', StatusPresenca::PRESENTE);
    }

    public function scopeFaltasJustificadas($query)
    {
        return $query->where('status_da_presenca', StatusPresenca::FALTA_JUSTIFICADA);
    }

    public function scopeFaltasInjustificadas($query)
    {
        return $query->where('status_da_presenca', StatusPresenca::FALTA_INJUSTIFICADA);
    }

    public function scopeCancelados($query)
    {
        return $query->where('status_da_presenca', StatusPresenca::CANCELADO);
    }

    public function scopeDoMes($query, $mes = null, $ano = null)
    {
        $mes = $mes ?? now()->month;
        $ano = $ano ?? now()->year;

        return $query->whereMonth('registrado_em', $mes)
            ->whereYear('registrado_em', $ano);
    }

    // ========== MÉTODOS AUXILIARES ==========

    /**
     * Marca o aluno como presente (admin valida presença)
     */
    public function marcarPresente($validadorId)
    {
        $this->update([
            'status_da_presenca' => StatusPresenca::PRESENTE,
            'validado_em' => now(),
            'validado_por' => $validadorId,
        ]);
    }

    /**
     * Marca falta (justificada ou injustificada)
     */
    public function marcarFalta($justificada = false)
    {
        $this->update([
            'status_da_presenca' => $justificada ? StatusPresenca::FALTA_JUSTIFICADA : StatusPresenca::FALTA_INJUSTIFICADA,
        ]);
    }

    public function isPresente()
    {
        return $this->status_da_presenca === StatusPresenca::PRESENTE;
    }

    public function isFalta()
    {
        return in_array($this->status_da_presenca, [StatusPresenca::FALTA_JUSTIFICADA, StatusPresenca::FALTA_INJUSTIFICADA]);
    }

    public function isCancelado()
    {
        return $this->status_da_presenca === StatusPresenca::CANCELADO;
    }

    /**
     * Gera token único para QR Code
     */
    public function gerarTokenQrCode()
    {
        return hash('sha256', $this->id . $this->user_id . $this->refeicao_id . config('app.key'));
    }

    /**
     * Gera URL do QR Code para validação
     */
    public function gerarUrlQrCode()
    {
        $token = $this->gerarTokenQrCode();
        return url("/api/v1/admin/presencas/validar-qrcode?token={$token}");
    }

    /**
     * Busca presença por token do QR Code (presenças não validadas ainda)
     */
    public static function buscarPorTokenQrCode($token)
    {
        return self::with(['user', 'refeicao'])
            ->whereNull('status_da_presenca')
            ->orWhere('status_da_presenca', '!=', StatusPresenca::PRESENTE)
            ->get()
            ->first(function ($presenca) use ($token) {
                return $presenca->gerarTokenQrCode() === $token;
            });
    }
}
