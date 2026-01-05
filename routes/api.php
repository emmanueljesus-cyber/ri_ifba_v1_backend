<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\V1\Admin\CardapioController as AdminCardapioController;
use App\Http\Controllers\api\V1\Admin\PresencaController as AdminPresencaController;
use App\Http\Controllers\api\V1\Estudante\CardapioController as EstudanteCardapioController;
use App\Http\Controllers\api\V1\Publico\CardapioController as PublicoCardapioController;

Route::prefix('v1')->group(function () {

    // Rotas públicas (sem autenticação)
    Route::get('cardapio/semanal', [PublicoCardapioController::class, 'semanal']);
    Route::get('cardapio/mensal', [PublicoCardapioController::class, 'mensal']);
    Route::get('cardapio/hoje', [PublicoCardapioController::class, 'hoje']);


    Route::prefix('estudante')->middleware(['auth:sanctum'])->group(function () {
        Route::get('cardapio/hoje', [EstudanteCardapioController::class, 'hoje']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum','ensure.is.admin'])->group(function () {
        // Rotas de Cardápios
        Route::get('cardapios',                      [AdminCardapioController::class, 'index'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('cardapios',                     [AdminCardapioController::class, 'store'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('cardapios/import',              [AdminCardapioController::class, 'import'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::get('cardapios/{cardapio}',           [AdminCardapioController::class, 'show'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::put('cardapios/{cardapio}',           [AdminCardapioController::class, 'update'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::delete('cardapios/{cardapio}',        [AdminCardapioController::class, 'destroy'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::delete('cardapios',                   [AdminCardapioController::class, 'deleteAll'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('cardapios/multiple',            [AdminCardapioController::class, 'deleteMultiple'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('cardapios/date-range',          [AdminCardapioController::class, 'deleteByDateRange'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);

        // Rotas de Confirmação de Presença
        Route::get('presencas',                      [AdminPresencaController::class, 'index'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/confirmar',           [AdminPresencaController::class, 'confirmarPresenca'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/{userId}/confirmar',  [AdminPresencaController::class, 'confirmarPorId'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/{userId}/remover',    [AdminPresencaController::class, 'removerConfirmacao'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/validar-lote',        [AdminPresencaController::class, 'validarLote'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/{id}/marcar-falta',   [AdminPresencaController::class, 'marcarFalta'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::post('presencas/{id}/cancelar',       [AdminPresencaController::class, 'cancelar'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
        Route::get('presencas/estatisticas',         [AdminPresencaController::class, 'estatisticas'])->withoutMiddleware(['auth:sanctum', 'ensure.is.admin']);
    });

});
