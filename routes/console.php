<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Agendamento de tarefas automáticas
 *
 * Para funcionar, adicione no crontab do servidor:
 * * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 */

// Marcar ausentes automaticamente após o horário de cada refeição
Schedule::command('presencas:marcar-ausentes')
    ->dailyAt('13:35')  // Após almoço
    ->description('Marca ausentes do almoço');

Schedule::command('presencas:marcar-ausentes')
    ->dailyAt('19:05')  // Após jantar
    ->description('Marca ausentes do jantar');


