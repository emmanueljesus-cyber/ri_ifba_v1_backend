<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API online'], 200);
});

// RF15 - Página de importação de bolsistas
Route::get('/bolsistas/import', function () {
    return view('bolsistas.import');
})->name('bolsistas.import');

