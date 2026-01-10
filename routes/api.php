<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\V1\Admin\CardapioController as AdminCardapioController;
use App\Http\Controllers\api\V1\Admin\PresencaController as AdminPresencaController;
use App\Http\Controllers\api\V1\Admin\BolsistaController as AdminBolsistaController;
use App\Http\Controllers\api\V1\Admin\RelatorioValidacaoController as AdminRelatorioController;
use App\Http\Controllers\api\V1\Admin\JustificativaController as AdminJustificativaController;
use App\Http\Controllers\api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\api\V1\Admin\RelatorioController as AdminRelatorioGeralController;
use App\Http\Controllers\api\v1\Admin\UserController as AdminUserController;
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
        
        // Justificativas do estudante
        Route::prefix('justificativas')->group(function () {
            Route::get('/', [\App\Http\Controllers\api\v1\Estudante\JustificativaController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\api\v1\Estudante\JustificativaController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\api\v1\Estudante\JustificativaController::class, 'show']);
        });
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
        // Bolsistas (RF09, RF10, RF13, RF15)
        // -----------------------------------------------------------------
        Route::prefix('bolsistas')->group(function () {
            Route::get('/', [AdminBolsistaController::class, 'todosBolsistas']);           // RF10 - Lista geral
            Route::get('/dia', [AdminBolsistaController::class, 'bolsistasDoDia']);        // RF09 - Lista do dia
            Route::get('/buscar', [AdminBolsistaController::class, 'buscarParaConfirmacao']); // RF13 - Busca manual
            Route::post('/import', [AdminBolsistaController::class, 'import']);            // RF15 - Importar Excel/CSV
            Route::post('/qrcode', [AdminBolsistaController::class, 'confirmarPorQrCode']); // RF13 - QR Code
            Route::post('/confirmar-lote', [AdminBolsistaController::class, 'confirmarLote']);
            Route::post('/{userId}/confirmar-presenca', [AdminBolsistaController::class, 'confirmarPresenca']);
            Route::post('/{userId}/marcar-falta', [AdminBolsistaController::class, 'marcarFalta']);
        });

        // -----------------------------------------------------------------
        // Lista de Bolsistas Aprovados (tabela bolsistas - RF15)
        // -----------------------------------------------------------------
        Route::prefix('bolsistas-aprovados')->group(function () {
            Route::get('/', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'destroy']);
            Route::post('/{id}/reativar', [\App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController::class, 'reativar']);
        });

        // -----------------------------------------------------------------
        // Estudantes (via admin)
        // -----------------------------------------------------------------
        Route::get('estudantes/turno', [AdminBolsistaController::class, 'estudantesPorTurno']);

        // -----------------------------------------------------------------
        // Relatórios de Validações
        // -----------------------------------------------------------------
        Route::prefix('relatorios/validacoes')->group(function () {
            Route::get('/', [AdminRelatorioController::class, 'index']);
            Route::get('/por-admin', [AdminRelatorioController::class, 'porAdmin']);
            Route::get('/timeline', [AdminRelatorioController::class, 'timeline']);
            Route::get('/refeicao/{id}', [AdminRelatorioController::class, 'porRefeicao']);
        });

        // -----------------------------------------------------------------
        // Justificativas (RF10)
        // -----------------------------------------------------------------
        Route::prefix('justificativas')->group(function () {
            Route::get('/', [AdminJustificativaController::class, 'index']);
            Route::get('/{id}', [AdminJustificativaController::class, 'show']);
            Route::post('/{id}/aprovar', [AdminJustificativaController::class, 'aprovar']);
            Route::post('/{id}/rejeitar', [AdminJustificativaController::class, 'rejeitar']);
            Route::get('/{id}/anexo', [AdminJustificativaController::class, 'downloadAnexo']);
        });

        // -----------------------------------------------------------------
        // Dashboard (RF11)
        // -----------------------------------------------------------------
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index']);
            Route::get('/resumo', [AdminDashboardController::class, 'resumo']);
            Route::get('/taxa-presenca', [AdminDashboardController::class, 'taxaPresenca']);
            Route::get('/faltas', [AdminDashboardController::class, 'faltas']);
            Route::get('/extras', [AdminDashboardController::class, 'extras']);
            Route::get('/evolucao', [AdminDashboardController::class, 'evolucao']);
            Route::get('/faltosos', [AdminDashboardController::class, 'faltosos']);
        });

        // -----------------------------------------------------------------
        // Relatórios Gerais (RF12)
        // -----------------------------------------------------------------
        Route::prefix('relatorios')->group(function () {
            Route::get('/presencas', [AdminRelatorioGeralController::class, 'presencas']);
            Route::get('/mensal', [AdminRelatorioGeralController::class, 'mensal']);
            Route::get('/semanal', [AdminRelatorioGeralController::class, 'semanal']);               // Formato planilha
            Route::get('/bolsista/{userId}', [AdminRelatorioGeralController::class, 'porBolsista']);
            Route::get('/exportar', [AdminRelatorioGeralController::class, 'exportar']);
            Route::get('/exportar-semanal', [AdminRelatorioGeralController::class, 'exportarSemanal']); // Excel formato planilha
            Route::get('/consolidado', [AdminRelatorioGeralController::class, 'consolidado']);
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
