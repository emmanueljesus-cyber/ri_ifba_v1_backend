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

];

