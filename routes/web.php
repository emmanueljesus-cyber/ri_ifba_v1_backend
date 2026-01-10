<?php

use Illuminate\Support\Facades\Route;

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


// Rotas de teste removidas conforme refatoração para API-only
Route::get('/', function () {
    return view('welcome');
});
