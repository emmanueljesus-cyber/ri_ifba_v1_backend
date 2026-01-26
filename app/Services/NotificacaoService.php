<?php

namespace App\Services;

use App\Models\Notificacao;
use App\Models\User;
use App\Enums\TipoNotificacao;
use App\Enums\PerfilUsuario;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service para gerenciamento de notificações in-app
 */
class NotificacaoService
{
    /**
     * Cria uma nova notificação para o usuário
     */
    public function criar(
        int $userId,
        TipoNotificacao $tipo,
        string $titulo,
        string $mensagem,
        ?array $dados = null
    ): Notificacao {
        return Notificacao::create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'dados' => $dados,
        ]);
    }

    /**
     * Lista notificações do usuário
     */
    public function listarDoUsuario(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Notificacao::doUsuario($userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Lista apenas notificações não lidas
     */
    public function naoLidasDoUsuario(int $userId): Collection
    {
        return Notificacao::doUsuario($userId)
            ->naoLidas()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Conta notificações não lidas
     */
    public function contarNaoLidas(int $userId): int
    {
        return Notificacao::doUsuario($userId)->naoLidas()->count();
    }

    /**
     * Marca uma notificação como lida
     */
    public function marcarComoLida(int $notificacaoId, int $userId): ?Notificacao
    {
        $notificacao = Notificacao::where('id', $notificacaoId)
            ->where('user_id', $userId)
            ->first();

        if ($notificacao) {
            $notificacao->marcarComoLida();
        }

        return $notificacao;
    }

    /**
     * Marca todas as notificações do usuário como lidas
     */
    public function marcarTodasComoLidas(int $userId): int
    {
        return Notificacao::doUsuario($userId)
            ->naoLidas()
            ->update(['lida_em' => now()]);
    }

    // ===========================================
    // MÉTODOS DE CONVENIÊNCIA PARA NOTIFICAÇÕES
    // ===========================================

    /**
     * Notifica administradores sobre uma nova justificativa pendente
     */
    public function notificarNovaJustificativaPendente(int $justificativaId, string $nomeEstudante): void
    {
        $admins = User::where('perfil', PerfilUsuario::ADMIN)->where('desligado', false)->get();

        foreach ($admins as $admin) {
            $this->criar(
                userId: $admin->id,
                tipo: TipoNotificacao::JUSTIFICATIVA_PENDENTE,
                titulo: 'Nova Justificativa Pendente',
                mensagem: "O estudante {$nomeEstudante} enviou uma nova justificativa para análise.",
                dados: ['justificativa_id' => $justificativaId]
            );
        }
    }

    /**
     * Notifica sobre aprovação de justificativa
     */
    public function notificarJustificativaAprovada(
        int $userId,
        int $justificativaId,
        ?string $observacao = null
    ): Notificacao {
        $mensagem = 'Sua justificativa de falta foi aprovada.';
        if ($observacao) {
            $mensagem .= " Observação: {$observacao}";
        }

        return $this->criar(
            userId: $userId,
            tipo: TipoNotificacao::JUSTIFICATIVA_APROVADA,
            titulo: 'Justificativa Aprovada',
            mensagem: $mensagem,
            dados: ['justificativa_id' => $justificativaId]
        );
    }

    /**
     * Notifica sobre rejeição de justificativa
     */
    public function notificarJustificativaRejeitada(
        int $userId,
        int $justificativaId,
        string $motivo
    ): Notificacao {
        return $this->criar(
            userId: $userId,
            tipo: TipoNotificacao::JUSTIFICATIVA_REJEITADA,
            titulo: 'Justificativa Rejeitada',
            mensagem: "Sua justificativa foi rejeitada. Motivo: {$motivo}",
            dados: ['justificativa_id' => $justificativaId]
        );
    }

    /**
     * Notifica sobre cadastro confirmado
     */
    public function notificarCadastroConfirmado(int $userId): Notificacao
    {
        return $this->criar(
            userId: $userId,
            tipo: TipoNotificacao::CADASTRO_CONFIRMADO,
            titulo: 'Bem-vindo ao RI IFBA!',
            mensagem: 'Seu cadastro foi confirmado. Você já pode acessar todos os recursos do sistema.'
        );
    }

    /**
     * Notifica sobre confirmação na fila de extras
     */
    public function notificarFilaConfirmada(
        int $userId,
        int $posicao,
        string $turno
    ): Notificacao {
        $turnoFormatado = $turno === 'almoco' ? 'almoço' : 'jantar';
        
        return $this->criar(
            userId: $userId,
            tipo: TipoNotificacao::FILA_CONFIRMADA,
            titulo: 'Inscrição na Fila Confirmada',
            mensagem: "Você está na posição {$posicao} da fila de extras para o {$turnoFormatado}.",
            dados: ['posicao' => $posicao, 'turno' => $turno]
        );
    }

    /**
     * Notifica sobre mudança de posição na fila
     */
    public function notificarPosicaoFilaAlterada(
        int $userId,
        int $posicaoAnterior,
        int $posicaoNova,
        string $turno
    ): Notificacao {
        $turnoFormatado = $turno === 'almoco' ? 'almoço' : 'jantar';
        $direcao = $posicaoNova < $posicaoAnterior ? 'subiu' : 'desceu';
        
        return $this->criar(
            userId: $userId,
            tipo: TipoNotificacao::FILA_POSICAO_ALTERADA,
            titulo: 'Posição na Fila Atualizada',
            mensagem: "Sua posição na fila de extras ({$turnoFormatado}) {$direcao} para {$posicaoNova}.",
            dados: [
                'posicao_anterior' => $posicaoAnterior,
                'posicao_nova' => $posicaoNova,
                'turno' => $turno,
            ]
        );
    }

    /**
     * Notifica sobre nova solicitação de mudança de dias
     */
    public function notificarNovaSolicitacaoMudancaDias(int $solicitacaoId, string $nomeEstudante): void
    {
        $admins = User::where('perfil', PerfilUsuario::ADMIN)->get();
        foreach ($admins as $admin) {
            $this->criar(
                userId: $admin->id,
                tipo: TipoNotificacao::MUDANCA_DIAS_PENDENTE,
                titulo: 'Solicitação de Mudança de Dias',
                mensagem: "O estudante {$nomeEstudante} solicitou a alteração dos seus dias de frequência.",
                dados: [
                    'solicitacao_id' => $solicitacaoId,
                    'tipo' => 'mudanca_dias'
                ]
            );
        }
    }
}
