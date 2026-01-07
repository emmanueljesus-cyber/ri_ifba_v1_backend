<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\V1\Admin\CardapioController as AdminCardapioController;
use App\Http\Controllers\api\V1\Admin\PresencaController as AdminPresencaController;
use App\Http\Controllers\api\V1\Admin\BolsistaController as AdminBolsistaController;
use App\Http\Controllers\api\V1\Admin\RelatorioValidacaoController as AdminRelatorioController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\api\V1\Estudante\CardapioController as EstudanteCardapioController;
use App\Http\Controllers\api\V1\Publico\CardapioController as PublicoCardapioController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefixo: /api/v1
| Respostas padronizadas: { data, errors, meta }
|
| Toggle de autenticação:
| - APP_DEBUG=true  → rotas admin/estudante SEM autenticação
| - APP_DEBUG=false → rotas admin/estudante COM auth:sanctum
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // ROTAS PÚBLICAS (sem autenticação)
    // =========================================================================
    Route::prefix('cardapio')->group(function () {
        Route::get('hoje', [PublicoCardapioController::class, 'hoje']);
        Route::get('semanal', [PublicoCardapioController::class, 'semanal']);
        Route::get('mensal', [PublicoCardapioController::class, 'mensal']);
    });

    // =========================================================================
    // ROTAS ESTUDANTE (auth condicional)
    // =========================================================================
    $estudanteMiddleware = config('app.debug') ? [] : ['auth:sanctum'];

    Route::prefix('estudante')->middleware($estudanteMiddleware)->group(function () {
        Route::get('cardapio/hoje', [EstudanteCardapioController::class, 'hoje']);
    });

    // =========================================================================
    // ROTAS ADMIN (auth condicional)
    // =========================================================================
    $adminMiddleware = config('app.debug') ? [] : ['auth:sanctum', 'ensure.is.admin'];

    Route::prefix('admin')->middleware($adminMiddleware)->group(function () {

        // -----------------------------------------------------------------
        // Cardápios
        // -----------------------------------------------------------------
        Route::prefix('cardapios')->group(function () {
            Route::get('/', [AdminCardapioController::class, 'index']);
            Route::post('/', [AdminCardapioController::class, 'store']);
            Route::post('/import', [AdminCardapioController::class, 'import']);
            Route::get('/{cardapio}', [AdminCardapioController::class, 'show']);
            Route::put('/{cardapio}', [AdminCardapioController::class, 'update']);
            Route::delete('/{cardapio}', [AdminCardapioController::class, 'destroy']);
            Route::delete('/', [AdminCardapioController::class, 'deleteAll']);
            Route::post('/delete-multiple', [AdminCardapioController::class, 'deleteMultiple']);
            Route::post('/delete-by-date', [AdminCardapioController::class, 'deleteByDateRange']);
        });

        // -----------------------------------------------------------------
        // Presenças
        // -----------------------------------------------------------------
        Route::prefix('presencas')->group(function () {
            Route::get('/', [AdminPresencaController::class, 'index']);
            Route::post('/confirmar', [AdminPresencaController::class, 'confirmarPresenca']);
            Route::post('/validar-lote', [AdminPresencaController::class, 'validarLote']);
            Route::post('/validar-qrcode', [AdminPresencaController::class, 'validarPorQrCode']);
            Route::get('/{id}/qrcode', [AdminPresencaController::class, 'gerarQrCode']);
            Route::post('/{userId}/confirmar', [AdminPresencaController::class, 'confirmarPorId']);
            Route::post('/{userId}/remover', [AdminPresencaController::class, 'removerConfirmacao']);
            Route::post('/{id}/marcar-falta', [AdminPresencaController::class, 'marcarFalta']);
            Route::post('/{id}/cancelar', [AdminPresencaController::class, 'cancelar']);
        });

        // -----------------------------------------------------------------
        // Bolsistas (RF09, RF10, RF13)
        // -----------------------------------------------------------------
        Route::prefix('bolsistas')->group(function () {
            Route::get('/', [AdminBolsistaController::class, 'todosBolsistas']);           // RF10 - Lista geral
            Route::get('/dia', [AdminBolsistaController::class, 'bolsistasDoDia']);        // RF09 - Lista do dia
            Route::get('/buscar', [AdminBolsistaController::class, 'buscarParaConfirmacao']); // RF13 - Busca manual
            Route::post('/qrcode', [AdminBolsistaController::class, 'confirmarPorQrCode']); // RF13 - QR Code
            Route::post('/confirmar-lote', [AdminBolsistaController::class, 'confirmarLote']);
            Route::post('/{userId}/confirmar-presenca', [AdminBolsistaController::class, 'confirmarPresenca']);
            Route::post('/{userId}/marcar-falta', [AdminBolsistaController::class, 'marcarFalta']);
        });

        // -----------------------------------------------------------------
        // Estudantes (via admin)
        // -----------------------------------------------------------------
        Route::get('estudantes/turno', [AdminBolsistaController::class, 'estudantesPorTurno']);

        // -----------------------------------------------------------------
        // Relatórios
        // -----------------------------------------------------------------
        Route::prefix('relatorios/validacoes')->group(function () {
            Route::get('/', [AdminRelatorioController::class, 'index']);
            Route::get('/por-admin', [AdminRelatorioController::class, 'porAdmin']);
            Route::get('/timeline', [AdminRelatorioController::class, 'timeline']);
            Route::get('/refeicao/{id}', [AdminRelatorioController::class, 'porRefeicao']);
        });

        // -----------------------------------------------------------------
        // Gerenciamento de Usuários (RF14)
        // -----------------------------------------------------------------
        Route::prefix('usuarios')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);                      // Listar todos
            Route::post('/', [AdminUserController::class, 'store']);                     // Criar novo
            Route::get('/bolsistas', [AdminUserController::class, 'listarBolsistas']);   // Apenas bolsistas
            Route::get('/matricula/{matricula}', [AdminUserController::class, 'buscarPorMatricula']); // Por matrícula
            Route::get('/{usuario}', [AdminUserController::class, 'show']);              // Buscar por ID
            Route::put('/{usuario}', [AdminUserController::class, 'update']);            // Atualizar
            Route::patch('/{usuario}', [AdminUserController::class, 'update']);          // Atualizar parcial
            Route::delete('/{usuario}', [AdminUserController::class, 'destroy']);        // Desativar
            Route::post('/{usuario}/reativar', [AdminUserController::class, 'reativar']); // Reativar
        });

    });

});
