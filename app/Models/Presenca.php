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
        'bolsista_id',
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

    public function bolsista()
    {
        return $this->belongsTo(Bolsista::class);
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
        // Se for uma instância virtual (não salva no banco ainda)
        if (!$this->exists) {
            $this->status_da_presenca = $this->status_da_presenca ?? StatusPresenca::PRESENTE;
            $this->registrado_em = $this->registrado_em ?? now();
            $this->validado_em = now();
            $this->validado_por = $validadorId;
            $this->save();
            return;
        }

        $this->update([
            'status_da_presenca' => $this->status_da_presenca === StatusPresenca::EXTRA ? StatusPresenca::EXTRA : StatusPresenca::PRESENTE,
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
        // 1. Tentar buscar em presenças reais
        $presenca = self::with(['user', 'refeicao'])
            ->where(function ($q) {
                $q->whereNull('status_da_presenca')
                    ->orWhere('status_da_presenca', '!=', StatusPresenca::PRESENTE);
            })
            ->get()
            ->first(function ($p) use ($token) {
                return $p->gerarTokenQrCode() === $token;
            });

        if ($presenca) {
            return $presenca;
        }

        // 2. Tentar buscar em FilaExtra (estudantes não bolsistas aprovados)
        $inscricao = \App\Models\FilaExtra::with(['user', 'refeicao'])
            ->where('status_fila_extras', \App\Enums\StatusFila::APROVADO)
            ->get()
            ->first(function ($i) use ($token) {
                $tokenGerado = hash('sha256', $i->user_id . $i->refeicao_id . config('app.key'));
                return $tokenGerado === $token;
            });

        if ($inscricao) {
            // Verificar se já tem presença (para não duplicar)
            $jaTemPresenca = self::where('user_id', $inscricao->user_id)
                ->where('refeicao_id', $inscricao->refeicao_id)
                ->exists();

            if ($jaTemPresenca) {
                return null;
            }

            // Criar uma instância de presença temporária para o validador reconhecer
            // mas sem salvar ainda (o controller de validação deve salvar ao confirmar)
            $presencaVirtual = new self([
                'user_id' => $inscricao->user_id,
                'refeicao_id' => $inscricao->refeicao_id,
                'status_da_presenca' => StatusPresenca::EXTRA,
            ]);
            $presencaVirtual->setRelation('user', $inscricao->user);
            $presencaVirtual->setRelation('refeicao', $inscricao->refeicao);

            return $presencaVirtual;
        }

        // 3. Tentar buscar para BOLSISTAS que ainda não têm registro de presença
        $hoje = now();
        $diaSemana = $hoje->dayOfWeek;
        
        // Buscar bolsistas ativos que têm direito hoje
        $bolsistaComDireito = \App\Models\User::bolsistas()
            ->ativos()
            ->whereHas('diasSemana', function ($q) use ($diaSemana) {
                $q->where('dia_semana', $diaSemana);
            })
            ->get()
            ->first(function ($user) use ($token) {
                // Para bolsistas virtuais, precisamos testar contra o token gerado para as refeições de HOJE
                $refeicoesHoje = \App\Models\Refeicao::where('data_do_cardapio', now()->toDateString())->get();
                foreach ($refeicoesHoje as $refeicao) {
                    $tokenGerado = hash('sha256', $user->id . $refeicao->id . config('app.key'));
                    if ($tokenGerado === $token) {
                        return true;
                    }
                }
                return false;
            });

        if ($bolsistaComDireito) {
            // Descobrir qual refeição gerou esse token
            $refeicaoId = null;
            $refeicoesHoje = \App\Models\Refeicao::where('data_do_cardapio', now()->toDateString())->get();
            foreach ($refeicoesHoje as $refeicao) {
                if (hash('sha256', $bolsistaComDireito->id . $refeicao->id . config('app.key')) === $token) {
                    $refeicaoId = $refeicao->id;
                    $refeicaoEncontrada = $refeicao;
                    break;
                }
            }

            if ($refeicaoId) {
                // Verificar se já tem presença (para não duplicar)
                $jaTemPresenca = self::where('user_id', $bolsistaComDireito->id)
                    ->where('refeicao_id', $refeicaoId)
                    ->exists();

                if ($jaTemPresenca) {
                    return null;
                }

                $presencaVirtual = new self([
                    'user_id' => $bolsistaComDireito->id,
                    'refeicao_id' => $refeicaoId,
                    'status_da_presenca' => StatusPresenca::PRESENTE,
                ]);
                $presencaVirtual->setRelation('user', $bolsistaComDireito);
                $presencaVirtual->setRelation('refeicao', $refeicaoEncontrada);

                return $presencaVirtual;
            }
        }

        return null;
    }
}
