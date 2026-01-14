<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bolsista extends Model
{
    use HasFactory;

    protected $table = 'bolsistas';

    protected $fillable = [
        'matricula',
        'nome',
        'curso',
        'turno',
        'dias_semana',
        'ativo',
        'user_id',
        'vinculado_em',
        'desligado',
        'desligado_em',
        'desligado_motivo',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'ativo' => 'boolean',
        'desligado' => 'boolean',
        'vinculado_em' => 'datetime',
        'desligado_em' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário vinculado
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para bolsistas ativos
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para não vinculados (pendentes de cadastro)
     */
    public function scopePendente($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope para já vinculados
     */
    public function scopeVinculado($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Verifica se uma matrícula está aprovada para bolsa
     */
    public static function isAprovado(string $matricula): bool
    {
        return self::where('matricula', $matricula)
            ->where('ativo', true)
            ->exists();
    }

    /**
     * Busca dados do bolsista por matrícula
     */
    public static function getByMatricula(string $matricula): ?self
    {
        return self::where('matricula', $matricula)->first();
    }

    /**
     * Vincula o registro de bolsista a um usuário
     * Chamado automaticamente quando estudante se cadastra
     */
    public function vincularUsuario(User $user): void
    {
        // Atualizar usuário como bolsista
        $user->update([
            'bolsista' => true,
            'turno' => $this->turno ?? $user->turno,
            'curso' => $this->curso ?? $user->curso,
        ]);

        // Configurar dias da semana se especificado
        if (!empty($this->dias_semana)) {
            UsuarioDiaSemana::where('user_id', $user->id)->delete();
            
            foreach ($this->dias_semana as $dia) {
                UsuarioDiaSemana::create([
                    'user_id' => $user->id,
                    'dia_semana' => $dia,
                ]);
            }
        }

        // Marcar como vinculado
        $this->update([
            'user_id' => $user->id,
            'vinculado_em' => now(),
        ]);
    }

    /**
     * Verifica e vincula automaticamente um usuário se estiver na lista
     * Use no registro/login do estudante
     */
    public static function verificarEVincular(User $user): bool
    {
        $bolsista = self::where('matricula', $user->matricula)
            ->where('ativo', true)
            ->whereNull('user_id')
            ->first();

        if ($bolsista) {
            $bolsista->vincularUsuario($user);
            return true;
        }

        return false;
    }

    /**
     * Desliga o bolsista com um motivo
     */
    public function desligar(string $motivo): void
    {
        $this->update([
            'desligado' => true,
            'desligado_em' => now(),
            'desligado_motivo' => $motivo,
            'ativo' => false,
        ]);

        // Atualizar usuário vinculado se existir
        if ($this->user_id) {
            $this->user->update(['bolsista' => false]);
        }
    }

    /**
     * Reativa o bolsista
     */
    public function reativar(): void
    {
        $this->update([
            'desligado' => false,
            'desligado_em' => null,
            'desligado_motivo' =>null,
            'ativo' => true,
        ]);

        // Atualizar usuário vinculado se existir
        if ($this->user_id) {
            $this->user->update(['bolsista' => true]);
        }
    }

    /**
     * Conta faltas não justificadas do bolsista
     */
    public function contarFaltasNaoJustificadas(): int
    {
        if (!$this->user_id) {
            return 0;
        }

        return \App\Models\Presenca::where('user_id', $this->user_id)
            ->where('status', 'falta')
            ->whereDoesntHave('justificativa', function ($query) {
                $query->where('status', 'aprovada');
            })
            ->count();
    }

    /**
     * Verifica se bolsista deve ser notificado por excesso de faltas
     */
    public function deveSereNotificado(): bool
    {
        return !$this->desligado && $this->contarFaltasNaoJustificadas() >= 3;
    }
}
