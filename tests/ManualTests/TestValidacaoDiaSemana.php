<?php

/**
 * Teste de Validação de Dia da Semana - RF13
 *
 * Este arquivo pode ser executado via Tinker para testar a funcionalidade:
 * php artisan tinker
 * require 'tests/ManualTests/TestValidacaoDiaSemana.php';
 */

use App\Models\User;
use App\Models\UsuarioDiaSemana;
use Carbon\Carbon;

echo "=== TESTE DE VALIDAÇÃO DE DIA DA SEMANA ===\n\n";

// Busca um bolsista para teste
$bolsista = User::where('bolsista', true)->first();

if (!$bolsista) {
    echo "❌ Nenhum bolsista encontrado no banco de dados.\n";
    echo "   Crie um bolsista primeiro para testar.\n\n";
    exit;
}

echo "👤 Bolsista de teste: {$bolsista->nome} (Matrícula: {$bolsista->matricula})\n\n";

// Verifica dias cadastrados
$diasCadastrados = $bolsista->diasSemana()->pluck('dia_semana')->toArray();

if (empty($diasCadastrados)) {
    echo "ℹ️  Este bolsista não tem dias cadastrados.\n";
    echo "   Vamos cadastrar ele para Segunda (1), Quarta (3) e Sexta (5)...\n\n";

    // Cadastra dias de exemplo
    UsuarioDiaSemana::create(['user_id' => $bolsista->id, 'dia_semana' => 1]); // Segunda
    UsuarioDiaSemana::create(['user_id' => $bolsista->id, 'dia_semana' => 3]); // Quarta
    UsuarioDiaSemana::create(['user_id' => $bolsista->id, 'dia_semana' => 5]); // Sexta

    $diasCadastrados = [1, 3, 5];
    echo "✅ Dias cadastrados com sucesso!\n\n";
}

echo "📅 Dias da semana cadastrados:\n";
foreach ($diasCadastrados as $dia) {
    $nomes = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    echo "   - {$nomes[$dia]} ($dia)\n";
}
echo "\n";

// Testa cada dia da semana
echo "🧪 TESTANDO VALIDAÇÃO PARA CADA DIA:\n\n";

$dias = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
];

foreach ($dias as $numeroDia => $nomeDia) {
    $temDireito = $bolsista->temDireitoRefeicaoNoDia($numeroDia);

    if ($temDireito) {
        echo "✅ $nomeDia ($numeroDia): PERMITIDO\n";
    } else {
        echo "❌ $nomeDia ($numeroDia): BLOQUEADO\n";
    }
}

echo "\n";

// Exemplo prático com data real
echo "📆 EXEMPLO PRÁTICO COM DATA REAL:\n\n";

$dataSegunda = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
$dataTerca = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');

echo "Tentando validar presença em $dataSegunda (Segunda-feira):\n";
$diaSemanaSeg = Carbon::parse($dataSegunda)->dayOfWeek;
if ($bolsista->temDireitoRefeicaoNoDia($diaSemanaSeg)) {
    echo "   ✅ Presença PODE ser validada\n";
} else {
    echo "   ❌ Presença NÃO pode ser validada\n";
    echo "   💡 Dias permitidos: " . implode(', ', array_map(function($d) use ($dias) {
        return $dias[$d];
    }, $diasCadastrados)) . "\n";
}

echo "\nTentando validar presença em $dataTerca (Terça-feira):\n";
$diaSemanaTer = Carbon::parse($dataTerca)->dayOfWeek;
if ($bolsista->temDireitoRefeicaoNoDia($diaSemanaTer)) {
    echo "   ✅ Presença PODE ser validada\n";
} else {
    echo "   ❌ Presença NÃO pode ser validada\n";
    echo "   💡 Dias permitidos: " . implode(', ', array_map(function($d) use ($dias) {
        return $dias[$d];
    }, $diasCadastrados)) . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";

/**
 * PARA TESTAR VIA API:
 *
 * 1. Validar presença em dia permitido:
 *    POST /api/v1/admin/presencas/confirmar
 *    {
 *        "matricula": "{{matricula_do_bolsista}}",
 *        "turno": "almoco",
 *        "data": "2026-01-05"  // Segunda-feira
 *    }
 *
 * 2. Validar presença em dia NÃO permitido:
 *    POST /api/v1/admin/presencas/confirmar
 *    {
 *        "matricula": "{{matricula_do_bolsista}}",
 *        "turno": "almoco",
 *        "data": "2026-01-06"  // Terça-feira
 *    }
 *
 *    Resposta esperada:
 *    {
 *        "success": false,
 *        "message": "Você não está cadastrado para se alimentar neste dia da semana.",
 *        "data": {
 *            "usuario": "Nome do Aluno",
 *            "dia_tentativa": "Terça-feira",
 *            "dias_cadastrados": "Segunda, Quarta, Sexta"
 *        }
 *    }
 */

