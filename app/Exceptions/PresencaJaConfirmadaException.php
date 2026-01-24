<?php

namespace App\Exceptions;

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
