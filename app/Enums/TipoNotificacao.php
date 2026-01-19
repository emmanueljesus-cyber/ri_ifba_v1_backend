<?php

namespace App\Enums;

/**
 * Tipos de notificação do sistema
 */
enum TipoNotificacao: string
{
    case JUSTIFICATIVA_APROVADA = 'justificativa_aprovada';
    case JUSTIFICATIVA_REJEITADA = 'justificativa_rejeitada';
    case CADASTRO_CONFIRMADO = 'cadastro_confirmado';
    case FILA_CONFIRMADA = 'fila_confirmada';
    case FILA_POSICAO_ALTERADA = 'fila_posicao_alterada';
    case FILA_APROVADA = 'fila_aprovada';
    case FILA_REJEITADA = 'fila_rejeitada';
    case GERAL = 'geral';
}
