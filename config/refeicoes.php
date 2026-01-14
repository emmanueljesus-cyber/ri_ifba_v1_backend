<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurações de Refeições
    |--------------------------------------------------------------------------
    |
    | Configurações padrão para o sistema de refeições do RI
    |
    */

    /**
     * Capacidade padrão de cada refeição
     */
    'capacidade_padrao' => env('REFEICAO_CAPACIDADE_PADRAO', 100),

    /**
     * Turnos disponíveis
     */
    'turnos_disponiveis' => [
        'almoco' => 'Almoço',
        'jantar' => 'Jantar',
    ],

    /**
     * Turnos padrão ao criar cardápio (se não informado)
     */
    'turnos_padrao' => ['almoco', 'jantar'],

    /**
     * Horários das refeições
     */
    'horarios' => [
        'almoco' => [
            'inicio' => env('HORARIO_ALMOCO_INICIO', '11:00'),
            'fim' => env('HORARIO_ALMOCO_FIM', '13:00'),
        ],
        'jantar' => [
            'inicio' => env('HORARIO_JANTAR_INICIO', '17:00'),
            'fim' => env('HORARIO_JANTAR_FIM', '19:00'),
        ],
    ],

];

