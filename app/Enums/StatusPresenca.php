<?php

namespace App\Enums;

enum StatusPresenca: string
{
    case PRESENTE            = 'presente';
    case FALTA_JUSTIFICADA   = 'falta_justificada';
    case FALTA_INJUSTIFICADA = 'falta_injustificada';
    case CANCELADO           = 'cancelado';
}
