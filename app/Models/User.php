<?php

namespace App\Models;
use App\Enums\PerfilUsuario;
use Laravel\Sanctum\HasApiTokens;       // <- add

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'matricula',
        'nome',
        'email',
        'password',
        'perfil',
        'bolsista',
        'limite_faltas_mes',
        'desligado',
        'desligado_em',
        'desligado_motivo',
        'curso',
        'turno',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'bolsista' => 'boolean',
        'desligado' => 'boolean',
        'desligado_em' => 'datetime',
        'limite_faltas_mes' => 'integer',
        'perfil'            => PerfilUsuario::class,
    ];

    /**
     * IMPORTANTE: Login via matrícula
     */
    public function getAuthIdentifierName()
    {
        return 'matricula';
    }

    // ========== RELACIONAMENTOS ==========

    public function cardapiosCriados()
    {
        return $this->hasMany(Cardapio::class, 'criado_por');
    }

    public function presencas()
    {
        return $this->hasMany(\App\Models\Presenca::class, 'user_id'); // pelo seu dump é user_id
    }

    public function justificativas()
    {
        return $this->hasMany(Justificativa::class);
    }

    public function filasExtras()
    {
        return $this->hasMany(FilaExtra::class);
    }

    public function diasSemana()
    {
        return $this->hasMany(UsuarioDiaSemana::class);
    }

    public function validacoes()
    {
        return $this->hasMany(Presenca::class, 'validado_por');
    }

    // ========== SCOPES ==========

    public function scopeEstudantes($query)
    {
        return $query->where('perfil', 'estudante');
    }

    public function scopeAdmins($query)
    {
        return $query->where('perfil', 'admin');
    }

    public function scopeBolsistas($query)
    {
        return $query->where('bolsista', true);
    }

    public function scopeNaoBolsistas($query)
    {
        return $query->where('bolsista', false);
    }

    public function scopeAtivos($query)
    {
        return $query->where('desligado', false);
    }

    public function scopeDesligados($query)
    {
        return $query->where('desligado', true);
    }

    // ========== ACCESSORS ==========

    public function getIsEstudanteAttribute()
    {
        return $this->perfil === 'estudante';
    }

    public function getIsAdminAttribute()
    {
        return $this->perfil === 'admin';
    }

    public function getIsAtivoAttribute()
    {
        return !$this->desligado;
    }

    // ========== MÉTODOS AUXILIARES ==========

    public function desligar($motivo)
    {
        $this->update([
            'desligado' => true,
            'desligado_em' => now(),
            'desligado_motivo' => $motivo,
        ]);
    }

    public function reativar()
    {
        $this->update([
            'desligado' => false,
            'desligado_em' => null,
            'desligado_motivo' => null,
        ]);
    }

    public function podeUsarRefeitorio()
    {
        return !$this->desligado;
    }

    public function atingiuLimiteFaltas()
    {
        $faltasNoMes = $this->presencas()
            ->where('status_da_presenca', 'falta_injustificada')
            ->whereMonth('registrado_em', now()->month)
            ->whereYear('registrado_em', now()->year)
            ->count();

        return $faltasNoMes >= $this->limite_faltas_mes;
    }

    public function getFaltasNoMes()
    {
        return $this->presencas()
            ->where('status_da_presenca', 'falta_injustificada')
            ->whereMonth('registrado_em', now()->month)
            ->whereYear('registrado_em', now()->year)
            ->count();
    }

    /**
     * Verifica se o aluno tem direito à refeição no dia da semana especificado
     *
     * @param int $diaDaSemana Dia da semana (0=Domingo, 1=Segunda, ..., 6=Sábado)
     * @return bool
     */
    public function temDireitoRefeicaoNoDia($diaDaSemana)
    {
        return $this->diasSemana()
            ->where('dia_semana', $diaDaSemana)
            ->exists();
    }

    /**
     * Retorna os dias da semana cadastrados do aluno
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDiasCadastrados()
    {
        return $this->diasSemana()->pluck('dia_semana');
    }
}
