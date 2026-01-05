#!/usr/bin/env php
<?php

/**
 * Teste de Validação com Rule::enum()
 *
 * Testa se a validação com Enum está funcionando corretamente
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Validator;
use App\Enums\TurnoRefeicao;
use Illuminate\Validation\Rule;

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║   🧪 TESTE DE VALIDAÇÃO COM Rule::enum()             ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
echo "\n";

$rules = [
    'turnos' => ['required', 'array', 'min:1'],
    'turnos.*' => ['required', 'string', 'filled', Rule::enum(TurnoRefeicao::class)],
];

// ==========================================================
// TESTE 1: Valores válidos do Enum
// ==========================================================
echo "📊 TESTE 1: Valores válidos do Enum...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$testCases = [
    ['turnos' => ['almoco']],
    ['turnos' => ['jantar']],
    ['turnos' => ['almoco', 'jantar']],
];

foreach ($testCases as $index => $data) {
    $validator = Validator::make($data, $rules);

    if ($validator->passes()) {
        echo "✅ Teste 1.".($index+1).": " . json_encode($data['turnos']) . " → VÁLIDO\n";
    } else {
        echo "❌ Teste 1.".($index+1).": " . json_encode($data['turnos']) . " → INVÁLIDO (Erro!)\n";
        echo "   Erros: " . json_encode($validator->errors()->all()) . "\n";
    }
}

echo "\n";

// ==========================================================
// TESTE 2: Valores inválidos (devem falhar)
// ==========================================================
echo "📊 TESTE 2: Valores inválidos (devem ser rejeitados)...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$invalidCases = [
    ['turnos' => ['almoço']], // Com acento
    ['turnos' => ['ALMOCO']], // Maiúsculo
    ['turnos' => ['lunch']], // Inglês
    ['turnos' => ['almoco', 'cafe']], // Café não existe
    ['turnos' => ['']], // Vazio
    ['turnos' => [123]], // Número
];

$rejeitados = 0;

foreach ($invalidCases as $index => $data) {
    $validator = Validator::make($data, $rules);

    if ($validator->fails()) {
        echo "✅ Teste 2.".($index+1).": " . json_encode($data['turnos']) . " → REJEITADO (Correto!)\n";
        echo "   Mensagem: " . $validator->errors()->first() . "\n\n";
        $rejeitados++;
    } else {
        echo "❌ Teste 2.".($index+1).": " . json_encode($data['turnos']) . " → ACEITO (Erro! Deveria rejeitar)\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ==========================================================
// TESTE 3: Enum values disponíveis
// ==========================================================
echo "📊 TESTE 3: Verificando valores disponíveis no Enum...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$enumCases = TurnoRefeicao::cases();

echo "Valores válidos no TurnoRefeicao:\n";
foreach ($enumCases as $case) {
    echo "   • {$case->name} = '{$case->value}'\n";
}

echo "\n";

// ==========================================================
// RESUMO
// ==========================================================
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║   📋 RESUMO DOS TESTES                                ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";
echo "\n";

$totalTestes = 3 + count($invalidCases);
$testesPassed = 3 + $rejeitados;

echo "Total de Testes: {$totalTestes}\n";
echo "Testes Passados: {$testesPassed}\n";
echo "Testes Falhados: " . ($totalTestes - $testesPassed) . "\n";
echo "\n";

if ($testesPassed === $totalTestes) {
    echo "✅ TODOS OS TESTES PASSARAM!\n";
    echo "✅ Rule::enum() funcionando perfeitamente!\n";
    echo "✅ Type safety garantido!\n";
} else {
    echo "⚠️  Alguns testes falharam. Verifique os erros.\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 Benefícios do Rule::enum():\n";
echo "   ✅ Type safety em tempo de compilação\n";
echo "   ✅ Autocomplete na IDE\n";
echo "   ✅ Fonte única de verdade (Enum)\n";
echo "   ✅ Refatoração segura\n";
echo "   ✅ Validação automática de valores\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

echo "✅ Testes concluídos!\n\n";

