#!/bin/bash

# 🧹 Script de Limpeza Completa do Docker
echo "🧹 Fazendo limpeza completa do Docker..."

# Para todos os containers relacionados
echo "🛑 Parando todos os containers..."
docker compose down -v
docker stop $(docker ps -aq) 2>/dev/null || true

# Remove containers órfãos e imagens
echo "🗑️ Removendo containers e imagens..."
docker container prune -f
docker image prune -f
docker network prune -f
docker volume prune -f

# Mata processos nas portas específicas
echo "🔫 Matando processos nas portas..."
sudo fuser -k 6378/tcp 2>/dev/null || true
sudo fuser -k 6380/tcp 2>/dev/null || true
sudo fuser -k 8000/tcp 2>/dev/null || true
sudo fuser -k 8001/tcp 2>/dev/null || true
sudo fuser -k 8889/tcp 2>/dev/null || true
sudo fuser -k 5433/tcp 2>/dev/null || true
sudo fuser -k 5432/tcp 2>/dev/null || true

# Remove diretório de dados antigo se existir
echo "🧯 Removendo dados antigos..."
sudo rm -rf ./.docker/postgres/data/* 2>/dev/null || true

# Lista portas ainda em uso
echo "📊 Verificando portas..."
sudo lsof -i :6378 2>/dev/null || echo "✅ Porta 6378 livre"
sudo lsof -i :6380 2>/dev/null || echo "✅ Porta 6380 livre"
sudo lsof -i :8000 2>/dev/null || echo "✅ Porta 8000 livre"
sudo lsof -i :8889 2>/dev/null || echo "✅ Porta 8889 livre"
sudo lsof -i :5433 2>/dev/null || echo "✅ Porta 5433 livre"

echo ""
echo "✅ Limpeza completa finalizada!"
echo "🚀 Agora execute: ./start-backend.sh"
