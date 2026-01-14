#!/bin/bash

# ============================================================
# 🧹 Script de Limpeza e População do Banco de Dados
# ============================================================
# Uso: ./reset-database.sh
# ============================================================

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║     🧹 Limpando e Populando Banco de Dados              ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto"
    exit 1
fi

# Limpar e popular banco
echo "🔄 Limpando banco de dados..."
php artisan migrate:fresh

echo ""
echo "🌱 Populando banco de dados..."
php artisan db:seed

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║     ✅ Banco de dados limpo e populado com sucesso!     ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

