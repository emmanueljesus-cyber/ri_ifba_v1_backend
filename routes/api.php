<?php

use App\Http\Controllers\api\v1\Admin\BolsistaAprovadoController;
use App\Http\Controllers\api\v1\Estudante\JustificativaController;
use App\Http\Controllers\api\v1\Estudante\NotificacaoController;
use App\Http\Controllers\api\v1\Estudante\PerfilController;
use App\Http\Controllers\api\v1\Estudante\HistoricoController;
use App\Http\Controllers\api\v1\Estudante\FilaExtraController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\v1\Admin\CardapioController as AdminCardapioController;
use App\Http\Controllers\api\v1\Admin\PresencaController as AdminPresencaController;
use App\Http\Controllers\api\v1\Admin\BolsistaController as AdminBolsistaController;
use App\Http\Controllers\api\v1\Admin\RelatorioValidacaoController as AdminRelatorioController;
use App\Http\Controllers\api\v1\Admin\JustificativaController as AdminJustificativaController;
use App\Http\Controllers\api\v1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\api\v1\Admin\RelatorioController as AdminRelatorioGeralController;
use App\Http\Controllers\api\v1\Admin\UserController as AdminUserController;
use App\Http\Controllers\api\v1\Admin\ExtrasController as AdminExtrasController;
use App\Http\Controllers\api\v1\Admin\SolicitacaoMudancaDiaController as AdminSolicitacaoMudancaDiaController;
use App\Http\Controllers\api\v1\Admin\NotificacaoController as AdminNotificacaoController;
use App\Http\Controllers\api\v1\Estudante\CardapioController as EstudanteCardapioController;
use App\Http\Controllers\api\v1\Publico\CardapioController as PublicoCardapioController;
use App\Http\Controllers\api\v1\AuthController;
use App\Http\Controllers\api\v1\PasswordResetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefixo: /api/v1
| Respostas padronizadas: { data, errors, meta }
|
| Autenticação:
| - Rotas estudante: sempre auth:sanctum
| - Rotas admin: em debug podem ser abertas; em produção usam auth:sanctum
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // ROTA DEBUG NOTIFICAÇÕES (REMOVER EM PRODUÇÃO)
    // =========================================================================
    Route::get('debug/notificacoes', function () {
        $admins = \App\Models\User::where('perfil', 'admin')->get(['id', 'nome', 'email', 'perfil']);
        $totalNotificacoes = \App\Models\Notificacao::count();
        $notificacoesRecentes = \App\Models\Notificacao::orderBy('created_at', 'desc')
            ->take(10)
            ->get(['id', 'user_id', 'tipo', 'titulo', 'created_at', 'lida_em']);

        return response()->json([
            'admins' => $admins,
            'total_notificacoes' => $totalNotificacoes,
            'notificacoes_recentes' => $notificacoesRecentes,
        ]);
    });
	// Em routes/api.php
	Route::get('debug/test-template', function() {
		try {
			return \Maatwebsite\Excel\Facades\Excel::download(
				new \App\Exports\CardapioTemplateExport(),
				'test.xlsx'
			);
		} catch (\Exception $e) {
			return response()->json([
				'error' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
			], 500);
		}
	});

    // =========================================================================
    // ROTAS PÚBLICAS (sem autenticação)
    // =========================================================================
    Route::prefix('cardapio')->group(function () {
        Route::get('hoje', [PublicoCardapioController::class, 'hoje']);
        Route::get('semanal', [PublicoCardapioController::class, 'semanal']);
        Route::get('mensal', [PublicoCardapioController::class, 'mensal']);
    });

    // =========================================================================
    // AUTENTICACAO
    // =========================================================================
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::get('verificar-matricula/{matricula}', [AuthController::class, 'verificarMatricula']);

        // Redefinição de senha (rotas públicas)
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('verify-reset-token', [PasswordResetController::class, 'verifyToken']);
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // =========================================================================
    // ROTAS DE DEBUG/TESTE (REMOVER EM PRODUÇÃO FINAL)
    // =========================================================================
    Route::get('debug/test-template', function() {
        try {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CardapioTemplateExport(),
                'test.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    });

    // Teste alternativo usando PhpSpreadsheet DIRETO (sem maatwebsite/excel)
    Route::get('debug/test-phpspreadsheet', function() {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Headers
            $sheet->setCellValue('A1', 'Data (DD/MM/AAAA)');
            $sheet->setCellValue('B1', 'Prato Principal 01');
            $sheet->setCellValue('C1', 'Prato Principal 02');
            
            // Dados de exemplo
            $sheet->setCellValue('A2', now()->format('d/m/Y'));
            $sheet->setCellValue('B2', 'Frango Grelhado');
            $sheet->setCellValue('C2', 'Omelete de Legumes');
            
            // Estilo do header
            $sheet->getStyle('A1:C1')->getFont()->setBold(true);
            
            // Gerar arquivo
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            
            // Criar resposta com stream
            $filename = 'test-phpspreadsheet-' . now()->format('Y-m-d') . '.xlsx';
            
            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    });
          // =========================================================================
        // ROTAS TEMPLATE V2 (PHPSPREADSHEET DIRETO)
        // =========================================================================
        Route::get('cardapios/template-v2', function(\App\Services\TemplateExportService $service) {
            return $service->downloadCardapioTemplate();
        });
        
        Route::get('bolsistas/template-v2', function(\App\Services\TemplateExportService $service) {
            return $service->downloadBolsistaTemplate();
        });


    // =========================================================================
    // ROTAS ESTUDANTE (sempre autenticadas via Sanctum)
    // =========================================================================
    $estudanteMiddleware = ['auth:sanctum'];
    $bolsistaMiddleware = ['auth:sanctum', 'ensure.is.bolsista'];
    $naoBolsistaMiddleware = ['auth:sanctum', 'ensure.is.nao.bolsista'];

    // -----------------------------------------------------------------
    // ROTAS COMUNS A TODOS OS ESTUDANTES (bolsistas e não bolsistas)
    // -----------------------------------------------------------------
    Route::prefix('estudante')->middleware($estudanteMiddleware)->group(function () {
        // RF03 - Cardápio (acessível a todos os estudantes)
        Route::get('cardapio/hoje', [EstudanteCardapioController::class, 'hoje']);
        Route::get('presenca/hoje', [EstudanteCardapioController::class, 'presencaHoje']);

        // RF05 - Perfil básico (TODOS OS ESTUDANTES)
        Route::get('perfil', [PerfilController::class, 'show']);
        Route::get('carteirinha', [PerfilController::class, 'carteirinha']);
        Route::put('perfil', [PerfilController::class, 'update']);
        Route::put('perfil/senha', [PerfilController::class, 'alterarSenha']);
        Route::post('perfil/foto', [PerfilController::class, 'atualizarFoto']);
        Route::delete('perfil/foto', [PerfilController::class, 'removerFoto']);

        // RF04 - Histórico (TODOS OS ESTUDANTES)
        Route::get('historico', [HistoricoController::class, 'index']);
        Route::get('historico/resumo', [HistoricoController::class, 'resumo']);

        // Notificações (TODOS OS ESTUDANTES)
        Route::prefix('notificacoes')->group(function () {
            Route::get('/', [NotificacaoController::class, 'index']);
            Route::get('/nao-lidas', [NotificacaoController::class, 'naoLidas']);
            Route::get('/contador', [NotificacaoController::class, 'contador']);
            Route::patch('/{id}/ler', [NotificacaoController::class, 'marcarComoLida']);
            Route::patch('/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasComoLidas']);
            Route::delete('/{id}', [NotificacaoController::class, 'destroy']);
        });
    });

    // -----------------------------------------------------------------
    // ROTAS EXCLUSIVAS PARA BOLSISTAS (RF02, RF04, RF05)
    // -----------------------------------------------------------------
    Route::prefix('estudante')->middleware($bolsistaMiddleware)->group(function () {

        // RF05 - Preferência alimentar e dias da semana (APENAS BOLSISTA)
        Route::put('perfil/preferencia', [PerfilController::class, 'atualizarPreferencia']);
        Route::put('perfil/restricoes-alimentares', [PerfilController::class, 'atualizarRestricoesAlimentares']);
        Route::put('perfil/dias-semana', [PerfilController::class, 'atualizarDiasSemana']);

        // RF02 - Justificativas do estudante (BOLSISTA)
        Route::prefix('justificativas')->group(function () {
            Route::get('/', [JustificativaController::class, 'index']);
            Route::post('/', [JustificativaController::class, 'store']);
            Route::get('/{id}', [JustificativaController::class, 'show']);
        });
    });

    // -----------------------------------------------------------------
    // ROTAS EXCLUSIVAS PARA NÃO BOLSISTAS (RF06, RF07)
    // -----------------------------------------------------------------
    Route::prefix('estudante')->middleware($naoBolsistaMiddleware)->group(function () {

        // RF06/RF07 - Fila de extras (NÃO BOLSISTA)
        Route::prefix('fila-extras')->group(function () {
            Route::get('/', [FilaExtraController::class, 'minhasInscricoes']);
            Route::get('/disponiveis', [FilaExtraController::class, 'refeicoesDisponiveis']);
            Route::post('/', [FilaExtraController::class, 'inscrever']);
            Route::get('/posicao', [FilaExtraController::class, 'posicao']);
            Route::delete('/{id}', [FilaExtraController::class, 'cancelar']);
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
            Route::get('/template', [AdminCardapioController::class, 'exportTemplate']);
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
            Route::get('/template', [AdminBolsistaController::class, 'exportTemplate']);   // Exportar template Excel
            Route::get('/alerta-faltas', [AdminBolsistaController::class, 'alertaFaltas']); // Bolsistas com risco de desligamento
            Route::post('/import', [AdminBolsistaController::class, 'import']);            // RF15 - Importar Excel/CSV
            Route::post('/qrcode', [AdminBolsistaController::class, 'confirmarPorQrCode']); // RF13 - QR Code
            Route::post('/confirmar-lote', [AdminBolsistaController::class, 'confirmarLote']);
            Route::post('/{userId}/confirmar-presenca', [AdminBolsistaController::class, 'confirmarPresenca']);
            Route::post('/{userId}/marcar-falta', [AdminBolsistaController::class, 'marcarFalta']);
            Route::post('/{id}/desligar', [AdminBolsistaController::class, 'desligar']);  // Desligar bolsista
            Route::post('/{id}/reativar', [AdminBolsistaController::class, 'reativar']);  // Reativar bolsista
        });

        // -----------------------------------------------------------------
        // Lista de Bolsistas Aprovados (tabela bolsistas - RF15)
        // -----------------------------------------------------------------
        Route::prefix('bolsistas-aprovados')->group(function () {
            Route::get('/', [BolsistaAprovadoController::class, 'index']);
            Route::post('/', [BolsistaAprovadoController::class, 'store']);
            Route::get('/{id}', [BolsistaAprovadoController::class, 'show']);
            Route::put('/{id}', [BolsistaAprovadoController::class, 'update']);
            Route::delete('/{id}', [BolsistaAprovadoController::class, 'destroy']);
            Route::post('/{id}/reativar', [BolsistaAprovadoController::class, 'reativar']);
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
        // Notificações (Admin)
        // -----------------------------------------------------------------
        Route::prefix('notificacoes')->group(function () {
            Route::get('/', [AdminNotificacaoController::class, 'index']);
            Route::get('/nao-lidas', [AdminNotificacaoController::class, 'naoLidas']);
            Route::get('/contador', [AdminNotificacaoController::class, 'contador']);
            Route::patch('/{id}/ler', [AdminNotificacaoController::class, 'marcarComoLida']);
            Route::patch('/marcar-todas-lidas', [AdminNotificacaoController::class, 'marcarTodasComoLidas']);
            Route::delete('/{id}', [AdminNotificacaoController::class, 'destroy']);
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
            Route::get('/presencas-detalhado', [AdminRelatorioGeralController::class, 'presencasDetalhadas']);
            Route::get('/mensal', [AdminRelatorioGeralController::class, 'mensal']);
            Route::get('/semanal', [AdminRelatorioGeralController::class, 'semanal']);               // Formato planilha
            Route::get('/bolsista/{userId}', [AdminRelatorioGeralController::class, 'porBolsista']);
            Route::get('/exportar', [AdminRelatorioGeralController::class, 'exportar']);
            Route::get('/exportar-semanal', [AdminRelatorioGeralController::class, 'exportarSemanal']); // Excel formato planilha
            Route::get('/consolidado', [AdminRelatorioGeralController::class, 'consolidado']);
        });

        // -----------------------------------------------------------------
        // Gerenciamento de Fila de Extras (RF06, RF07 - Admin)
        // -----------------------------------------------------------------
        Route::prefix('extras')->group(function () {
            Route::get('/', [AdminExtrasController::class, 'index']);                    // Listar todas inscrições
            Route::get('/hoje', [AdminExtrasController::class, 'hoje']);                 // Inscrições do dia
            Route::get('/estatisticas', [AdminExtrasController::class, 'estatisticas']); // Estatísticas
            Route::get('/exportar', [AdminExtrasController::class, 'exportar']);         // Exportar relatório Excel
            Route::post('/aprovar-lote', [AdminExtrasController::class, 'aprovarLote']); // Aprovar em lote
            Route::post('/{id}/aprovar', [AdminExtrasController::class, 'aprovar']);     // Aprovar inscrição
            Route::post('/{id}/rejeitar', [AdminExtrasController::class, 'rejeitar']);   // Rejeitar inscrição
            Route::post('/{id}/confirmar-presenca', [AdminExtrasController::class, 'confirmarPresenca']); // Confirmar presença
            Route::delete('/{id}', [AdminExtrasController::class, 'destroy']);           // Remover inscrição
        });

  
        // -----------------------------------------------------------------
        // Solicitações de Mudança de Dias
        // -----------------------------------------------------------------
        Route::prefix('solicitacoes-mudanca-dias')->group(function () {
            Route::get('/', [AdminSolicitacaoMudancaDiaController::class, 'index']);
            Route::patch('/{id}/aprovar', [AdminSolicitacaoMudancaDiaController::class, 'aprovar']);
            Route::patch('/{id}/rejeitar', [AdminSolicitacaoMudancaDiaController::class, 'rejeitar']);
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
