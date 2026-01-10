<?php

namespace App\Services;

use App\Models\Presenca;
use App\Models\Refeicao;
use App\Models\User;
use App\Enums\StatusPresenca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PresencaService
{
    /**
     * Busca a refeição do dia/turno
     */
    public function buscarRefeicao(string $data, string $turno): ?Refeicao
    {
        return Refeicao::where('data_do_cardapio', $data)
            ->where('turno', $turno)
            ->first();
    }

    /**
     * Valida se o usuário pode fazer refeição no dia
     * 
     * @return array ['valido' => bool, 'erro' => string|null, 'meta' => array]
     */
    public function validarDireitoRefeicao(User $user, string $data): array
    {
        $diaSemana = Carbon::parse($data)->dayOfWeek;

        if (!$user->temDireitoRefeicaoNoDia($diaSemana)) {
            $diasCadastrados = $user->diasSemana()
                ->get()
                ->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))
                ->implode(', ');

            return [
                'valido' => false,
                'erro' => 'Este aluno não está cadastrado para se alimentar neste dia da semana.',
                'meta' => [
                    'usuario' => $user->nome,
                    'dia_tentativa' => Carbon::parse($data)->locale('pt_BR')->dayName,
                    'dias_cadastrados' => $diasCadastrados ?: 'Nenhum dia cadastrado',
                ],
            ];
        }

        return ['valido' => true, 'erro' => null, 'meta' => []];
    }

    /**
     * Confirma presença de um usuário (baixo nível - requer refeicaoId)
     * 
     * @return array ['sucesso' => bool, 'presenca' => Presenca|null, 'erro' => string|null, 'meta' => array]
     */
    public function confirmarPresenca(int $userId, int $refeicaoId, ?int $validadoPor = null): array
    {
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicaoId)
            ->first();

        // Já confirmada?
        if ($presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE) {
            return [
                'sucesso' => false,
                'presenca' => $presenca,
                'erro' => 'Presença já foi confirmada anteriormente.',
                'meta' => [
                    'presenca_id' => $presenca->id,
                    'confirmado_em' => $presenca->validado_em?->format('d/m/Y H:i'),
                ],
                'status_code' => 409,
            ];
        }

        // Criar ou atualizar
        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicaoId,
                'status_da_presenca' => StatusPresenca::PRESENTE,
                'validado_em' => now(),
                'validado_por' => $validadoPor ?? 1,
                'registrado_em' => now(),
            ]);
        } else {
            $presenca->marcarPresente($validadoPor ?? 1);
        }

        return [
            'sucesso' => true,
            'presenca' => $presenca->fresh(),
            'erro' => null,
            'meta' => [],
            'status_code' => 201,
        ];
    }

    /**
     * MÉTODO COMPLETO: Confirma presença do bolsista
     * Contém TODA a lógica de negócio. Controller só chama e formata resposta.
     * 
     * @return array ['sucesso' => bool, 'data' => array, 'erro' => string|null, 'meta' => array, 'status_code' => int]
     */
    public function confirmarPresencaCompleta(int $userId, string $data, string $turno, ?int $validadoPor = null): array
    {
        // 1. Validar turno
        if (empty($turno)) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'O turno é obrigatório.', 'meta' => [], 'status_code' => 400];
        }

        // 2. Buscar usuário
        $user = User::with('diasSemana')->find($userId);
        if (!$user) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'Usuário não encontrado.', 'meta' => [], 'status_code' => 404];
        }

        // 3. Verificar se é bolsista
        if (!$user->bolsista) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'Este usuário não é bolsista.', 'meta' => [], 'status_code' => 403];
        }

        // 4. Validar direito à refeição no dia
        $validacao = $this->validarDireitoRefeicao($user, $data);
        if (!$validacao['valido']) {
            return ['sucesso' => false, 'data' => null, 'erro' => $validacao['erro'], 'meta' => $validacao['meta'], 'status_code' => 403];
        }

        // 5. Buscar refeição
        $refeicao = $this->buscarRefeicao($data, $turno);
        if (!$refeicao) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'Não há refeição cadastrada para este dia e turno.', 'meta' => [], 'status_code' => 404];
        }

        // 6. Confirmar presença
        $resultado = $this->confirmarPresenca($userId, $refeicao->id, $validadoPor);
        if (!$resultado['sucesso']) {
            return ['sucesso' => false, 'data' => null, 'erro' => $resultado['erro'], 'meta' => $resultado['meta'], 'status_code' => $resultado['status_code']];
        }

        // 7. Retornar sucesso
        return [
            'sucesso' => true,
            'data' => [
                'presenca_id' => $resultado['presenca']->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
                'confirmado_em' => $resultado['presenca']->validado_em->format('d/m/Y H:i'),
            ],
            'erro' => null,
            'meta' => ['message' => '✅ Presença confirmada com sucesso.'],
            'status_code' => 201,
        ];
    }

    /**
     * MÉTODO COMPLETO: Marca falta do bolsista
     * Contém TODA a lógica de negócio. Controller só chama e formata resposta.
     */
    public function marcarFaltaCompleta(int $userId, string $data, string $turno, bool $justificada = false, ?int $validadoPor = null): array
    {
        // 1. Validar turno
        if (empty($turno)) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'O turno é obrigatório.', 'meta' => [], 'status_code' => 400];
        }

        // 2. Buscar usuário
        $user = User::find($userId);
        if (!$user) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'Usuário não encontrado.', 'meta' => [], 'status_code' => 404];
        }

        // 3. Buscar refeição
        $refeicao = $this->buscarRefeicao($data, $turno);
        if (!$refeicao) {
            return ['sucesso' => false, 'data' => null, 'erro' => 'Não há refeição cadastrada para este dia e turno.', 'meta' => [], 'status_code' => 404];
        }

        // 4. Marcar falta
        $presenca = $this->marcarFalta($userId, $refeicao->id, $justificada, $validadoPor);

        $mensagem = $justificada ? 'Falta justificada registrada.' : 'Falta injustificada registrada.';

        return [
            'sucesso' => true,
            'data' => [
                'presenca_id' => $presenca->id,
                'usuario' => $user->nome,
                'matricula' => $user->matricula,
                'status' => $presenca->status_da_presenca->value,
                'refeicao' => [
                    'id' => $refeicao->id,
                    'data' => $refeicao->data_do_cardapio->format('d/m/Y'),
                    'turno' => $refeicao->turno->value,
                ],
            ],
            'erro' => null,
            'meta' => ['message' => $mensagem],
            'status_code' => 200,
        ];
    }

    /**
     * Marca falta (justificada ou injustificada)
     */
    public function marcarFalta(int $userId, int $refeicaoId, bool $justificada = false, ?int $validadoPor = null): Presenca
    {
        $status = $justificada
            ? StatusPresenca::FALTA_JUSTIFICADA
            : StatusPresenca::FALTA_INJUSTIFICADA;

        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicaoId)
            ->first();

        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicaoId,
                'status_da_presenca' => $status,
                'validado_em' => now(),
                'validado_por' => $validadoPor ?? 1,
                'registrado_em' => now(),
            ]);
        } else {
            $presenca->update([
                'status_da_presenca' => $status,
                'validado_em' => now(),
                'validado_por' => $validadoPor ?? 1,
            ]);
        }

        return $presenca->fresh();
    }

    /**
     * Remove confirmação de presença
     */
    public function removerConfirmacao(int $userId, int $refeicaoId): bool
    {
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicaoId)
            ->first();

        if ($presenca) {
            $presenca->delete();
            return true;
        }

        return false;
    }

    /**
     * Converte número do dia para texto
     */
    private function getDiaSemanaTexto(int $dia): string
    {
        $dias = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];

        return $dias[$dia] ?? 'Desconhecido';
    }
}
