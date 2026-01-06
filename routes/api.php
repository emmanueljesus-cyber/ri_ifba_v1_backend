<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\V1\Admin\CardapioController as AdminCardapioController;
use App\Http\Controllers\api\V1\Admin\PresencaController as AdminPresencaController;
use App\Http\Controllers\api\V1\Admin\BolsistaController as AdminBolsistaController;
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

    $adminMiddleware = config('app.debug') ? [] : ['auth:sanctum', 'ensure.is.admin'];

    Route::prefix('admin')->middleware($adminMiddleware)->group(function () {
        // Rotas de Cardápios
        Route::get('cardapios',                      [AdminCardapioController::class, 'index']);
        Route::post('cardapios',                     [AdminCardapioController::class, 'store']);
        Route::post('cardapios/import',              [AdminCardapioController::class, 'import']);
        Route::post('cardapios/import/upload',       [AdminCardapioController::class, 'import']);
        Route::get('cardapios/{cardapio}',           [AdminCardapioController::class, 'show']);
        Route::put('cardapios/{cardapio}',           [AdminCardapioController::class, 'update']);
        Route::delete('cardapios/{cardapio}',        [AdminCardapioController::class, 'destroy']);
        Route::delete('cardapios',                   [AdminCardapioController::class, 'deleteAll']);
        Route::post('cardapios/multiple',            [AdminCardapioController::class, 'deleteMultiple']);
        Route::post('cardapios/date-range',          [AdminCardapioController::class, 'deleteByDateRange']);

        // Rotas de Confirmação de Presença
        Route::get('presencas',                      [AdminPresencaController::class, 'index']);
        Route::post('presencas/confirmar',           [AdminPresencaController::class, 'confirmarPresenca']);
        Route::post('presencas/{userId}/confirmar',  [AdminPresencaController::class, 'confirmarPorId']);
        Route::post('presencas/{userId}/remover',    [AdminPresencaController::class, 'removerConfirmacao']);
        Route::post('presencas/validar-lote',        [AdminPresencaController::class, 'validarLote']);
        Route::post('presencas/{id}/marcar-falta',   [AdminPresencaController::class, 'marcarFalta']);
        Route::post('presencas/{id}/cancelar',       [AdminPresencaController::class, 'cancelar']);

        // RF13: Validação por QR Code e Matrícula
        Route::post('presencas/validar-qrcode',      [AdminPresencaController::class, 'validarPorQrCode']);
        Route::get('presencas/{id}/qrcode',          [AdminPresencaController::class, 'gerarQrCode']);

        // Rotas de Relatório de Validações
        Route::get('relatorios/validacoes',                [App\Http\Controllers\api\v1\Admin\RelatorioValidacaoController::class, 'index']);
        Route::get('relatorios/validacoes/por-admin',      [App\Http\Controllers\api\v1\Admin\RelatorioValidacaoController::class, 'porAdmin']);
        Route::get('relatorios/validacoes/refeicao/{id}',  [App\Http\Controllers\api\v1\Admin\RelatorioValidacaoController::class, 'porRefeicao']);
        Route::get('relatorios/validacoes/timeline',       [App\Http\Controllers\api\v1\Admin\RelatorioValidacaoController::class, 'timeline']);

        // RF09 - Rotas de Bolsistas e Estudantes
        Route::get('bolsistas/dia',                        [AdminBolsistaController::class, 'bolsistasDoDia']);
        Route::get('bolsistas',                            [AdminBolsistaController::class, 'todosBolsistas']);
        Route::get('estudantes/turno',                     [AdminBolsistaController::class, 'estudantesPorTurno']);
        Route::post('bolsistas/{userId}/confirmar-presenca', [AdminBolsistaController::class, 'confirmarPresenca']);
        Route::post('bolsistas/{userId}/marcar-falta',     [AdminBolsistaController::class, 'marcarFalta']);
        Route::post('bolsistas/confirmar-lote',            [AdminBolsistaController::class, 'confirmarLote']);
    });

});
