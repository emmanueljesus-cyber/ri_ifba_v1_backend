<?php

namespace App\Enums;

enum StatusJustificativa: string
{
    case PENDENTE = 'pendente';
    case APROVADA = 'aprovada';
    case REJEITADA = 'rejeitada';
}
