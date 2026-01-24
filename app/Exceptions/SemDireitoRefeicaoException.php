<?php

namespace App\Exceptions;

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
