<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioDiaSemana extends Model
{
    use HasFactory;

    protected $table = 'usuario_dias_semana';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = ['user_id', 'dia_semana'];

    protected $fillable = [
        'user_id',
        'dia_semana',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    // ========== RELACIONAMENTOS ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== SCOPES ==========

    public function scopeDia($query, $dia)
    {
        return $query->where('dia_semana', $dia);
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function getDiaSemanaTexto()
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return $dias[$this->dia_semana] ?? 'Desconhecido';
    }

    public static function getDiasSemanaDisponiveis()
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
    }
}
