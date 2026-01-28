<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Restaurante Institucional
    |--------------------------------------------------------------------------
    |
    | Configurações globais para o sistema de restaurante institucional.
    |
    */

    // Horários das refeições
    'refeicoes' => [
        'almoco' => [
            'inicio' => '11:00',
            'fim' => '13:30',
            'nome' => 'Almoço',
        ],
        'jantar' => [
            'inicio' => '17:30',
            'fim' => '19:00',
            'nome' => 'Jantar',
        ],
    ],

    // Horário limite para marcar ausência automática (minutos após o fim)
    'ausencia_automatica_delay' => 5, // 5 minutos após o fim

    // Prazo máximo para justificativas
    'justificativas' => [
        // Antecipada: até o horário limite do turno
        'antecipada_almoco_limite' => '13:30',
        'antecipada_jantar_limite' => '19:00',

        // Posterior: até o penúltimo dia letivo do mês
        'posterior_prazo_dias_uteis' => 2, // Penúltimo dia útil
    ],

    // Quantidade máxima de faltas antes de alerta
    'alerta_faltas' => [
        'amarelo' => 3,  // Alerta amarelo
        'vermelho' => 5, // Alerta vermelho (risco de desligamento)
    ],

    // Configurações da fila de extras
    'fila_extras' => [
        'vagas_almoco' => 20,
        'vagas_jantar' => 15,
        'hora_limite_inscricao_almoco' => '10:00',
        'hora_limite_inscricao_jantar' => '16:00',
    ],
];
