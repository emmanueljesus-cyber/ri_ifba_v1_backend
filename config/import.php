<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurações de Importação de Cardápio
    |--------------------------------------------------------------------------
    |
    | Configurações para a importação de cardápios via Excel/CSV.
    |
    */

    // Tamanho máximo do arquivo em KB (5MB = 5120KB)
    'max_file_size' => env('IMPORT_MAX_FILE_SIZE', 5120),

    // Tipos de arquivo permitidos
    'allowed_mimes' => ['xlsx', 'xls', 'csv'],

    // Número máximo de linhas por importação (0 = ilimitado)
    'max_rows' => env('IMPORT_MAX_ROWS', 0),

    // Habilitar logs de debug na importação
    'debug' => env('IMPORT_DEBUG', false),

    // Turno padrão quando não especificado
    'default_turno' => 'almoco',

];

