<?php

namespace App\Models;

use App\Enums\StatusFila;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilaExtra extends Model
{
    use HasFactory;

    protected $table = 'filas_extras';

    protected $fillable = [
        'user_id',
        'refeicao_id',
        'status_fila_extras',
        'inscrito_em',
    ];

    protected $casts = [
        'inscrito_em'       => 'datetime',
        'status_fila_extras' => StatusFila::class,
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

    public function scopeAguardando($query)
    {
        return $query->where('status_fila_extras', StatusFila::INSCRITO);
    }

    public function scopeAprovados($query)
    {
        return $query->where('status_fila_extras', 'aprovado');
    }

    public function scopeRejeitados($query)
    {
        return $query->where('status_fila_extras', 'rejeitado');
    }

    public function scopeOrdenadoPorInscricao($query)
    {
        return $query->orderBy('inscrito_em', 'asc');
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function aprovar()
    {
        $this->update(['status_fila_extras' => StatusFila::APROVADO]);
    }

    public function isAprovado()
    {
        return $this->status_fila_extras === StatusFila::APROVADO;
    }

    public function isRejeitado()
    {
        return $this->status_fila_extras === StatusFila::REJEITADO;
    }

    public function getPosicaoFila()
    {
        return self::where('refeicao_id', $this->refeicao_id)
            ->where('status_fila_extras', StatusFila::INSCRITO)
            ->where('inscrito_em', '<=', $this->inscrito_em)
            ->count();
    }
}
