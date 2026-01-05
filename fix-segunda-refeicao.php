#!/usr/bin/env php
<?php

/**
 * Script para Garantir Refeição de Segunda-feira
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cardapio;
use App\Models\Refeicao;

echo "\n";
echo "🔍 Verificando refeição para Segunda-feira (05/01/2026)...\n";
echo "\n";

$data = '2026-01-05';
$turno = 'almoco';

$refeicao = Refeicao::where('data_do_cardapio', $data)
    ->where('turno', $turno)
    ->first();

if ($refeicao) {
    echo "✅ Refeição já existe!\n";
    echo "   • ID: {$refeicao->id}\n";
    echo "   • Data: {$refeicao->data_do_cardapio}\n";
    echo "   • Turno: " . $refeicao->turno->value . "\n";
    echo "   • Vagas: {$refeicao->vagas_disponiveis}\n";
} else {
    echo "⚠️  Refeição não encontrada. Criando...\n\n";

    // Criar cardápio
    $cardapio = Cardapio::create([
        'data' => $data,
        'turno' => $turno,
        'prato_principal' => 'Arroz, Feijão e Frango',
        'guarnicao' => 'Salada de Alface e Tomate',
        'acompanhamento' => 'Farofa',
        'sobremesa' => 'Banana',
        'suco' => 'Suco de Laranja',
        'criado_por' => 1,
    ]);

    echo "✅ Cardápio criado! ID: {$cardapio->id}\n";

    // Criar refeição
    $refeicao = Refeicao::create([
        'cardapio_id' => $cardapio->id,
        'data_do_cardapio' => $data,
        'turno' => $turno,
        'vagas_disponiveis' => 100,
        'refeicoes_confirmadas' => 0,
    ]);

    echo "✅ Refeição criada! ID: {$refeicao->id}\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Pronto! Agora você pode testar:\n";
echo "\n";
echo "POST http://localhost:8000/api/v1/admin/presencas/confirmar\n";
echo "{\n";
echo "    \"matricula\": \"20241234\",\n";
echo "    \"turno\": \"almoco\",\n";
echo "    \"data\": \"2026-01-05\"\n";
echo "}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

