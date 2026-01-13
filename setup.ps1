# ============================================================
# 🚀 Script de Setup Automático - RI IFBA Backend (Windows)
# ============================================================
# Uso: .\setup.ps1
# ============================================================

$ErrorActionPreference = "Stop"

# Cores
function Write-Info { Write-Host "ℹ️  $args" -ForegroundColor Cyan }
function Write-Success { Write-Host "✅ $args" -ForegroundColor Green }
function Write-Warn { Write-Host "⚠️  $args" -ForegroundColor Yellow }
function Write-Err { Write-Host "❌ $args" -ForegroundColor Red }

# Banner
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║     🍽️  RI IFBA Backend - Setup Automático              ║" -ForegroundColor Green
Write-Host "║     Sistema de Gestão de Refeições                       ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

# ============================================================
# 1. Verificar se Docker Desktop está instalado
# ============================================================
Write-Info "Verificando Docker Desktop..."

$docker = Get-Command docker -ErrorAction SilentlyContinue
if (-not $docker) {
    Write-Err "Docker não encontrado!"
    Write-Host ""
    Write-Host "Instale o Docker Desktop:" -ForegroundColor Yellow
    Write-Host "  👉 https://www.docker.com/products/docker-desktop" -ForegroundColor White
    Write-Host ""
    exit 1
}
Write-Success "Docker encontrado"

# Verificar se Docker está rodando
try {
    docker info 2>&1 | Out-Null
    Write-Success "Docker está rodando"
} catch {
    Write-Err "Docker não está rodando!"
    Write-Host ""
    Write-Host "Inicie o Docker Desktop e tente novamente." -ForegroundColor Yellow
    Write-Host ""
    exit 1
}

# ============================================================
# 2. Verificar se WSL está disponível
# ============================================================
Write-Info "Verificando WSL..."

$wsl = Get-Command wsl -ErrorAction SilentlyContinue
if (-not $wsl) {
    Write-Err "WSL não encontrado!"
    Write-Host ""
    Write-Host "Instale o WSL:" -ForegroundColor Yellow
    Write-Host "  wsl --install" -ForegroundColor White
    Write-Host ""
    exit 1
}
Write-Success "WSL encontrado"

# ============================================================
# 3. Obter caminho WSL do projeto
# ============================================================
Write-Info "Configurando ambiente..."

# Converter caminho Windows para WSL
$currentPath = (Get-Location).Path

if ($currentPath -match "\\\\wsl") {
    # Já está em path WSL via \\wsl.localhost\...
    $wslPath = $currentPath -replace "\\\\wsl\.localhost\\[^\\]+", "" -replace "\\", "/"
} elseif ($currentPath -match "^([A-Z]):") {
    # Path Windows normal (C:\Users\...)
    $drive = $matches[1].ToLower()
    $wslPath = $currentPath -replace "^[A-Z]:", "/mnt/$drive" -replace "\\", "/"
} else {
    $wslPath = $currentPath -replace "\\", "/"
}

Write-Success "Caminho WSL: $wslPath"

# ============================================================
# 4. Executar setup.sh via WSL
# ============================================================
Write-Info "Executando setup no WSL..."
Write-Host ""

# Verificar se setup.sh existe
if (-not (Test-Path "setup.sh")) {
    Write-Err "Arquivo setup.sh não encontrado!"
    Write-Host "Certifique-se de estar na pasta raiz do projeto." -ForegroundColor Yellow
    exit 1
}

# Executar o script bash via WSL
wsl -e bash -c "cd '$wslPath' && chmod +x setup.sh && ./setup.sh"

$exitCode = $LASTEXITCODE

if ($exitCode -eq 0) {
    Write-Host ""
    Write-Success "Setup concluído com sucesso!"
} else {
    Write-Host ""
    Write-Err "Setup falhou com código de erro: $exitCode"
    exit $exitCode
}
