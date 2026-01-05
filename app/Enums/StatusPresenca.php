<?php
namespace App\Enums;
enum StatusPresenca: string
{
    case CONFIRMADO          = 'confirmado';
    case VALIDADO            = 'validado';
    case FALTA_JUSTIFICADA   = 'falta_justificada';
    case FALTA_INJUSTIFICADA = 'falta_injustificada';
    case CANCELADO           = 'cancelado';
}
