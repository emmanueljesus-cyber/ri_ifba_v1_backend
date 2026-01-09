<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TesteController extends Controller
{
    /**
     * Dashboard de testes
     */
    public function dashboard()
    {
        return view('teste.dashboard');
    }

    /**
     * Gerenciamento de bolsistas
     */
    public function bolsistas()
    {
        return view('teste.bolsistas');
    }

    /**
     * Gerenciamento de justificativas
     */
    public function justificativas()
    {
        return view('teste.justificativas');
    }

    /**
     * Relatórios
     */
    public function relatorios()
    {
        return view('teste.relatorios');
    }
}
