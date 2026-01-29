<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar em todos os proxies (necessário para Railway)
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'ensure.is.admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'ensure.is.bolsista' => \App\Http\Middleware\EnsureIsBolsista::class,
            'ensure.is.nao.bolsista' => \App\Http\Middleware\EnsureIsNaoBolsista::class,
            'check.status' => \App\Http\Middleware\CheckUserStatus::class,
        ]);

        $middleware->appendToGroup('api', [
            'check.status',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
