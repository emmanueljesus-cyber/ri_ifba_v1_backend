<?php

namespace App\Services;

use App\Models\Justificativa;
use App\Models\Presenca;
use App\Models\User;
use App\Enums\StatusJustificativa;
use App\Enums\StatusPresenca;
use App\Mail\JustificativaDecisaoMail;
use App\Services\NotificacaoService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service para gerenciamento de justificativas de faltas
 * 
 * Responsável por toda a lógica de negócio relacionada a:
 * - Listagem e filtragem de justificativas
 * - Aprovação e rejeição de justificativas
 * - Atualização automática de status de presença
 */
class JustificativaService
{
    /**
     * Lista justificativas com filtros e paginação
     * 
     * @param array $filtros Filtros a aplicar (status, user_id, data_inicio, data_fim, etc)
     * @param int $perPage Resultados por página
     * @return LengthAwarePaginator
     */
    public function listarJustificativas(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Justificativa::with(['usuario', 'presenca.refeicao', 'aprovadoPor']);

        // Filtro por status
        if (isset($filtros['status'])) {
            $query->where('status_justificativa', $filtros['status']);
        }

        // Filtro por usuário
        if (isset($filtros['user_id'])) {
            $query->where('user_id', $filtros['user_id']);
        }

        // Filtro por período
        if (isset($filtros['data_inicio']) && isset($filtros['data_fim'])) {
            $query->whereBetween('created_at', [
                $filtros['data_inicio'],
                $filtros['data_fim']
            ]);
        }

        // Filtro por turno
        if (isset($filtros['turno'])) {
            $query->whereHas('presenca.refeicao', function ($q) use ($filtros) {
                $q->where('turno', $filtros['turno']);
            });
        }

        // Ordenação
        $sortBy = $filtros['sort_by'] ?? 'created_at';
        $sortOrder = $filtros['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Busca justificativa por ID
     * 
     * @param int $id
     * @return Justificativa
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function buscarJustificativa(int $id): Justificativa
    {
        return Justificativa::with([
            'usuario',
            'presenca.refeicao.cardapio',
            'aprovadoPor'
        ])->findOrFail($id);
    }

    /**
     * Aprova uma justificativa
     * 
     * Esta operação:
     * 1. Atualiza status da justificativa para APROVADA
     * 2. Atualiza status da presença para FALTA_JUSTIFICADA
     * 3. Registra quem aprovou e quando
     * 
     * @param int $id ID da justificativa
     * @param int $adminId ID do administrador que está aprovando
     * @param string|null $observacao Observação opcional do administrador
     * @return Justificativa
     * @throws \Exception Se justificativa não puder ser aprovada
     */
    public function aprovarJustificativa(int $id, int $adminId, ?string $observacao = null): Justificativa
    {
        $justificativa = DB::transaction(function () use ($id, $adminId, $observacao) {
            $justificativa = $this->buscarJustificativa($id);

            // Validar se pode ser aprovada
            if ($justificativa->status_justificativa !== StatusJustificativa::PENDENTE) {
                throw new \Exception(
                    'Apenas justificativas pendentes podem ser aprovadas. ' .
                    'Status atual: ' . $justificativa->status_justificativa->value
                );
            }

           // Atualizar justificativa
            $justificativa->update([
                'status_justificativa' => StatusJustificativa::APROVADA,
                'aprovado_por' => $adminId,
                'aprovado_em' => now(),
                'observacao_admin' => $observacao,
            ]);

            // Atualizar presença relacionada
            if ($justificativa->presenca) {
                $justificativa->presenca->update([
                    'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
                ]);
            }

            return $justificativa->fresh([
                'usuario',
                'presenca.refeicao.cardapio',
                'aprovadoPor'
            ]);
        });

        // RF10 - Notificar estudante por e-mail
        $this->notificarEstudante($justificativa);

        return $justificativa;
    }

    /**
     * Rejeita uma justificativa
     * 
     * Esta operação:
     * 1. Atualiza status da justificativa para REJEITADA
     * 2. Atualiza status da presença para FALTA_INJUSTIFICADA
     * 3. Registra quem rejeitou e quando
     * 4. Requer observação do administrador explicando a rejeição
     * 
     * @param int $id ID da justificativa
     * @param int $adminId ID do administrador que está rejeitando
     * @param string $observacao Motivo da rejeição (obrigatório)
     * @return Justificativa
     * @throws \Exception Se justificativa não puder ser rejeitada
     */
    public function rejeitarJustificativa(int $id, int $adminId, string $observacao): Justificativa
    {
        if (empty($observacao)) {
            throw new \Exception('Observação é obrigatória ao rejeitar uma justificativa.');
        }

        $justificativa = DB::transaction(function () use ($id, $adminId, $observacao) {
            $justificativa = $this->buscarJustificativa($id);

            // Validar se pode ser rejeitada
            if ($justificativa->status_justificativa !== StatusJustificativa::PENDENTE) {
                throw new \Exception(
                    'Apenas justificativas pendentes podem ser rejeitadas. ' .
                    'Status atual: ' . $justificativa->status_justificativa->value
                );
            }

            // Atualizar justificativa
            $justificativa->update([
                'status_justificativa' => StatusJustificativa::REJEITADA,
                'aprovado_por' => $adminId,
                'aprovado_em' => now(),
                'observacao_admin' => $observacao,
            ]);

            // Atualizar presença relacionada
            if ($justificativa->presenca) {
                $justificativa->presenca->update([
                    'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
                ]);
            }

            return $justificativa->fresh([
                'usuario',
                'presenca.refeicao.cardapio',
                'aprovadoPor'
            ]);
        });

        // RF10 - Notificar estudante por e-mail
        $this->notificarEstudante($justificativa);

        return $justificativa;
    }

    /**
     * Cancela uma justificativa (volta ao status pendente)
     * Útil para reverter uma aprovação/rejeição feita por engano
     * 
     * @param int $id
     * @param int $adminId
     * @return Justificativa
     */
    public function cancelarDecisao(int $id, int $adminId): Justificativa
    {
        return DB::transaction(function () use ($id, $adminId) {
            $justificativa = $this->buscarJustificativa($id);

            if ($justificativa->status_justificativa === StatusJustificativa::PENDENTE) {
                throw new \Exception('Justificativa já está pendente.');
            }

            $justificativa->update([
                'status_justificativa' => StatusJustificativa::PENDENTE,
                'aprovado_por' => null,
                'aprovado_em' => null,
                'observacao_admin' => 'Decisão cancelada por admin ID: ' . $adminId,
            ]);

            // Resetar presença para ausente (permitir nova decisão)
            if ($justificativa->presenca) {
                $justificativa->presenca->update([
                    'status_da_presenca' => StatusPresenca::AUSENTE,
                ]);
            }

            return $justificativa->fresh();
        });
    }

    /**
     * Estatísticas de justificativas
     * 
     * @param array $filtros Filtros opcionais (data_inicio, data_fim, etc)
     * @return array
     */
    public function estatisticas(array $filtros = []): array
    {
        $query = Justificativa::query();

        if (isset($filtros['data_inicio']) && isset($filtros['data_fim'])) {
            $query->whereBetween('created_at', [
                $filtros['data_inicio'],
                $filtros['data_fim']
            ]);
        }

        $total = $query->count();
        $pendentes = (clone $query)->where('status_justificativa', StatusJustificativa::PENDENTE)->count();
        $aprovadas = (clone $query)->where('status_justificativa', StatusJustificativa::APROVADA)->count();
        $rejeitadas = (clone $query)->where('status_justificativa', StatusJustificativa::REJEITADA)->count();

        $taxaAprovacao = $total > 0 ? round(($aprovadas / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'pendentes' => $pendentes,
            'aprovadas' => $aprovadas,
            'rejeitadas' => $rejeitadas,
            'taxa_aprovacao' => $taxaAprovacao,
        ];
    }

    /**
     * RF10 - Notifica o estudante sobre a decisão da justificativa
     * 
     * Cria:
     * 1. Notificação in-app (sempre)
     * 2. E-mail (se configurado para enviar)
     * 
     * @param Justificativa $justificativa
     * @return void
     */
    protected function notificarEstudante(Justificativa $justificativa): void
    {
        $usuario = $justificativa->usuario;
        
        if (!$usuario) {
            Log::warning('RF10: Não foi possível notificar - usuário não encontrado', [
                'justificativa_id' => $justificativa->id,
            ]);
            return;
        }

        // 1. Criar notificação in-app (SEMPRE funciona)
        try {
            $notificacaoService = app(NotificacaoService::class);
            $isAprovada = $justificativa->status_justificativa === \App\Enums\StatusJustificativa::APROVADA;

            if ($isAprovada) {
                $notificacaoService->notificarJustificativaAprovada(
                    userId: $usuario->id,
                    justificativaId: $justificativa->id,
                    observacao: $justificativa->observacao_admin
                );
            } else {
                $notificacaoService->notificarJustificativaRejeitada(
                    userId: $usuario->id,
                    justificativaId: $justificativa->id,
                    motivo: $justificativa->observacao_admin ?? 'Sem observação'
                );
            }

            Log::info('RF10: Notificação in-app criada', [
                'justificativa_id' => $justificativa->id,
                'user_id' => $usuario->id,
                'decisao' => $justificativa->status_justificativa->value,
            ]);

        } catch (\Exception $e) {
            Log::error('RF10: Falha ao criar notificação in-app', [
                'justificativa_id' => $justificativa->id,
                'erro' => $e->getMessage(),
            ]);
        }

        // 2. Enviar e-mail (se não for driver 'log')
        try {
            if (config('mail.default') !== 'log' && $usuario->email) {
                Mail::to($usuario->email)->queue(new JustificativaDecisaoMail($justificativa));

                Log::info('RF10: E-mail enviado', [
                    'justificativa_id' => $justificativa->id,
                    'estudante' => $usuario->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RF10: Falha ao enviar e-mail', [
                'justificativa_id' => $justificativa->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
