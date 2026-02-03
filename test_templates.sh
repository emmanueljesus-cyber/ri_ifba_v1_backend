#!/bin/bash

# Script de teste para exportação de templates
# Uso: ./test_templates.sh

echo "🧪 Testando Exportação de Templates no Ambiente de Produção"
echo "============================================================"
echo ""

BASE_URL="https://ri-ifba-homol.up.railway.app/api/v1/admin"

echo "📋 1. Testando Template de Bolsistas (Excel/Fallback)"
echo "-----------------------------------------------------"
curl -I "${BASE_URL}/bolsistas/template" 2>&1 | head -10
echo ""

echo "📥 Tentando download..."
curl -o "template_bolsistas_test.xlsx" "${BASE_URL}/bolsistas/template" 2>&1
if [ -f "template_bolsistas_test.xlsx" ]; then
    SIZE=$(wc -c < "template_bolsistas_test.xlsx")
    echo "✅ Arquivo baixado! Tamanho: ${SIZE} bytes"
    file template_bolsistas_test.xlsx
    echo ""
else
    echo "❌ Falha ao baixar arquivo"
    echo ""
fi

echo ""
echo "📋 2. Testando Template de Bolsistas (CSV Direto)"
echo "------------------------------------------------"
curl -I "${BASE_URL}/bolsistas/template-csv" 2>&1 | head -10
echo ""

echo "📥 Tentando download..."
curl -o "template_bolsistas_test.csv" "${BASE_URL}/bolsistas/template-csv" 2>&1
if [ -f "template_bolsistas_test.csv" ]; then
    SIZE=$(wc -c < "template_bolsistas_test.csv")
    echo "✅ Arquivo baixado! Tamanho: ${SIZE} bytes"
    echo ""
    echo "📄 Primeiras 5 linhas:"
    head -5 template_bolsistas_test.csv
    echo ""
else
    echo "❌ Falha ao baixar arquivo"
    echo ""
fi

echo ""
echo "🍽️ 3. Testando Template de Cardápio (Excel/Fallback)"
echo "----------------------------------------------------"
curl -I "${BASE_URL}/cardapios/template" 2>&1 | head -10
echo ""

echo "📥 Tentando download..."
curl -o "template_cardapios_test.xlsx" "${BASE_URL}/cardapios/template" 2>&1
if [ -f "template_cardapios_test.xlsx" ]; then
    SIZE=$(wc -c < "template_cardapios_test.xlsx")
    echo "✅ Arquivo baixado! Tamanho: ${SIZE} bytes"
    file template_cardapios_test.xlsx
    echo ""
else
    echo "❌ Falha ao baixar arquivo"
    echo ""
fi

echo ""
echo "🍽️ 4. Testando Template de Cardápio (CSV Direto)"
echo "-----------------------------------------------"
curl -I "${BASE_URL}/cardapios/template-csv" 2>&1 | head -10
echo ""

echo "📥 Tentando download..."
curl -o "template_cardapios_test.csv" "${BASE_URL}/cardapios/template-csv" 2>&1
if [ -f "template_cardapios_test.csv" ]; then
    SIZE=$(wc -c < "template_cardapios_test.csv")
    echo "✅ Arquivo baixado! Tamanho: ${SIZE} bytes"
    echo ""
    echo "📄 Primeiras 5 linhas:"
    head -5 template_cardapios_test.csv
    echo ""
else
    echo "❌ Falha ao baixar arquivo"
    echo ""
fi

echo ""
echo "🎯 Resumo dos Testes"
echo "==================="
ls -lh template_*_test.* 2>/dev/null || echo "Nenhum arquivo baixado"
echo ""
echo "✅ Testes concluídos!"
echo ""
echo "📝 Notas:"
echo "- Se .xlsx foi baixado com sucesso: Laravel Excel está funcionando"
echo "- Se .csv foi baixado: Fallback está ativo"
echo "- Ambos os formatos são válidos para importação"
