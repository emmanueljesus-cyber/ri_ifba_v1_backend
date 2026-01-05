<?php
namespace App\Enums;
enum StatusFila: string
{
    case INSCRITO = 'inscrito';
    case APROVADO   = 'aprovado';
    case REJEITADO  = 'rejeitado';
}
