<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitacaoMudancaDia extends Model
{
    use HasFactory;

    protected $table = 'solicitacoes_mudanca_dias';

    protected $fillable = [
        'user_id',
        'dias_atuais',
        'dias_solicitados',
        'motivo',
        'status',
        'motivo_rejeicao',
        'avaliado_por',
        'avaliado_em',
    ];

    protected $casts = [
        'dias_atuais' => 'array',
        'dias_solicitados' => 'array',
        'avaliado_em' => 'datetime',
    ];

    // ========== RELACIONAMENTOS ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function avaliador()
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }

    // ========== SCOPES ==========

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeAprovadas($query)
    {
        return $query->where('status', 'aprovada');
    }

    public function scopeRejeitadas($query)
    {
        return $query->where('status', 'rejeitada');
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function isPendente(): bool
    {
        return $this->status === 'pendente';
    }

    public function isAprovada(): bool
    {
        return $this->status === 'aprovada';
    }

    public function isRejeitada(): bool
    {
        return $this->status === 'rejeitada';
    }

    public function aprovar(int $adminId): void
    {
        $this->update([
            'status' => 'aprovada',
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
        ]);

        // Atualizar dias do usuário
        $this->user->diasSemana()->delete();

        $diasParaInserir = collect($this->dias_solicitados)->map(function ($dia) {
            return ['user_id' => $this->user_id, 'dia_semana' => $dia];
        })->toArray();

        $this->user->diasSemana()->insert($diasParaInserir);
    }

    public function rejeitar(int $adminId, string $motivo): void
    {
        $this->update([
            'status' => 'rejeitada',
            'motivo_rejeicao' => $motivo,
            'avaliado_por' => $adminId,
            'avaliado_em' => now(),
        ]);
    }

    public function getDiasAtuaisTexto(): array
    {
        $nomes = $this->getNomesDias();
        return collect($this->dias_atuais ?? [])->map(fn($d) => $nomes[$d] ?? 'Desconhecido')->toArray();
    }

    public function getDiasSolicitadosTexto(): array
    {
        $nomes = $this->getNomesDias();
        return collect($this->dias_solicitados)->map(fn($d) => $nomes[$d] ?? 'Desconhecido')->toArray();
    }

    private function getNomesDias(): array
    {
        return [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
        ];
    }
}
