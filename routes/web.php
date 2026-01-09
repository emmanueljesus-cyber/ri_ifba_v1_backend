<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TesteController;

Route::get('/', function () {
    return redirect('/teste');
});

/*
|--------------------------------------------------------------------------
| Rotas de Teste (Sem Autenticação)
|--------------------------------------------------------------------------
| Interface visual para testes das funcionalidades implementadas
| Acesse: http://localhost:8000/teste
*/

Route::prefix('teste')->name('teste.')->group(function () {
    Route::get('/', [TesteController::class, 'dashboard'])->name('dashboard');
    Route::get('/bolsistas', [TesteController::class, 'bolsistas'])->name('bolsistas');
    Route::get('/justificativas', [TesteController::class, 'justificativas'])->name('justificativas');
    Route::get('/relatorios', [TesteController::class, 'relatorios'])->name('relatorios');
});
