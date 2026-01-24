<?php

namespace App\Exceptions;

class UsuarioNaoEncontradoException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Usuário não encontrado.', 404);
    }
}
