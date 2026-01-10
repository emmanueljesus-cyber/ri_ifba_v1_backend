<?php

namespace App\Exceptions;

/**
 * Exceções específicas de Presença
 */
class PresencaException extends BusinessException {}

class UsuarioNaoEncontradoException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Usuário não encontrado.', 404);
    }
}

class NaoEBolsistaException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Este usuário não é bolsista.', 403);
    }
}

class SemDireitoRefeicaoException extends BusinessException
{
    public function __construct(string $usuario, string $diaTentativa, string $diasCadastrados)
    {
        parent::__construct(
            'Este aluno não está cadastrado para se alimentar neste dia da semana.',
            403,
            [
                'usuario' => $usuario,
                'dia_tentativa' => $diaTentativa,
                'dias_cadastrados' => $diasCadastrados,
            ]
        );
    }
}

class RefeicaoNaoEncontradaException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Não há refeição cadastrada para este dia e turno.', 404);
    }
}

class PresencaJaConfirmadaException extends BusinessException
{
    public function __construct(int $presencaId, ?string $confirmadoEm)
    {
        parent::__construct(
            'Presença já foi confirmada anteriormente.',
            409,
            [
                'presenca_id' => $presencaId,
                'confirmado_em' => $confirmadoEm,
            ]
        );
    }
}

class TurnoObrigatorioException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('O turno é obrigatório.', 400);
    }
}
