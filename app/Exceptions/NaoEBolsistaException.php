<?php

namespace App\Exceptions;

class NaoEBolsistaException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Este usuário não é bolsista.', 403);
    }
}
