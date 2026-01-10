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
     * Contém TODA a lógica de negócio. Lança exceções para erros.
     * 
     * @throws TurnoObrigatorioException
     * @throws UsuarioNaoEncontradoException
     * @throws NaoEBolsistaException
     * @throws SemDireitoRefeicaoException
     * @throws RefeicaoNaoEncontradaException
     * @throws PresencaJaConfirmadaException
     * @return array ['presenca' => Presenca, 'user' => User, 'refeicao' => Refeicao]
     */
    public function confirmarPresencaCompleta(int $userId, string $data, string $turno, ?int $validadoPor = null): array
    {
        // 1. Validar turno
        if (empty($turno)) {
            throw new \App\Exceptions\TurnoObrigatorioException();
        }

        // 2. Buscar usuário
        $user = User::with('diasSemana')->find($userId);
        if (!$user) {
            throw new \App\Exceptions\UsuarioNaoEncontradoException();
        }

        // 3. Verificar se é bolsista
        if (!$user->bolsista) {
            throw new \App\Exceptions\NaoEBolsistaException();
        }

        // 4. Validar direito à refeição no dia
        $diaSemana = Carbon::parse($data)->dayOfWeek;
        if (!$user->temDireitoRefeicaoNoDia($diaSemana)) {
            $diasCadastrados = $user->diasSemana()
                ->get()
                ->map(fn($d) => $this->getDiaSemanaTexto($d->dia_semana))
                ->implode(', ');

            throw new \App\Exceptions\SemDireitoRefeicaoException(
                $user->nome,
                Carbon::parse($data)->locale('pt_BR')->dayName,
                $diasCadastrados ?: 'Nenhum dia cadastrado'
            );
        }

        // 5. Buscar refeição
        $refeicao = $this->buscarRefeicao($data, $turno);
        if (!$refeicao) {
            throw new \App\Exceptions\RefeicaoNaoEncontradaException();
        }

        // 6. Verificar se já confirmada
        $presenca = Presenca::where('user_id', $userId)
            ->where('refeicao_id', $refeicao->id)
            ->first();

        if ($presenca && $presenca->status_da_presenca === StatusPresenca::PRESENTE) {
            throw new \App\Exceptions\PresencaJaConfirmadaException(
                $presenca->id,
                $presenca->validado_em?->format('d/m/Y H:i')
            );
        }

        // 7. Confirmar presença
        if (!$presenca) {
            $presenca = Presenca::create([
                'user_id' => $userId,
                'refeicao_id' => $refeicao->id,
                'status_da_presenca' => StatusPresenca::PRESENTE,
                'validado_em' => now(),
                'validado_por' => $validadoPor ?? 1,
                'registrado_em' => now(),
            ]);
        } else {
            $presenca->marcarPresente($validadoPor ?? 1);
            $presenca = $presenca->fresh();
        }

        // 8. Retornar objetos de domínio
        return [
            'presenca' => $presenca,
            'user' => $user,
            'refeicao' => $refeicao,
        ];
    }

    /**
     * MÉTODO COMPLETO: Marca falta do bolsista
     * Contém TODA a lógica de negócio. Lança exceções para erros.
     * 
     * @throws TurnoObrigatorioException
     * @throws UsuarioNaoEncontradoException
     * @throws RefeicaoNaoEncontradaException
     * @return array ['presenca' => Presenca, 'user' => User, 'refeicao' => Refeicao]
     */
    public function marcarFaltaCompleta(int $userId, string $data, string $turno, bool $justificada = false, ?int $validadoPor = null): array
    {
        // 1. Validar turno
        if (empty($turno)) {
            throw new \App\Exceptions\TurnoObrigatorioException();
        }

        // 2. Buscar usuário
        $user = User::find($userId);
        if (!$user) {
            throw new \App\Exceptions\UsuarioNaoEncontradoException();
        }

        // 3. Buscar refeição
        $refeicao = $this->buscarRefeicao($data, $turno);
        if (!$refeicao) {
            throw new \App\Exceptions\RefeicaoNaoEncontradaException();
        }

        // 4. Marcar falta
        $presenca = $this->marcarFalta($userId, $refeicao->id, $justificada, $validadoPor);

        // 5. Retornar objetos de domínio
        return [
            'presenca' => $presenca,
            'user' => $user,
            'refeicao' => $refeicao,
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
