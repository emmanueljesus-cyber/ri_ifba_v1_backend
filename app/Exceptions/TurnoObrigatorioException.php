<?php

namespace App\Exceptions;

class TurnoObrigatorioException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('O turno é obrigatório.', 400);
    }
}
