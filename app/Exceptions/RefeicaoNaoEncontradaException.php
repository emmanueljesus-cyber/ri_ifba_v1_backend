<?php

namespace App\Exceptions;

class RefeicaoNaoEncontradaException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Não há refeição cadastrada para este dia e turno.', 404);
    }
}
