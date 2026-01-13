# 🐳 Instalação do Docker no WSL/Ubuntu

## ❌ Erro Encontrado

```bash
make: docker-compose: No such file or directory
make: *** [Makefile:152: setup] Error 127
```

**Causa:** Docker não está instalado no WSL ou Docker Compose não está no PATH.

---

## ✅ Solução: Instalar Docker no WSL

### Opção 1: Docker Desktop (Recomendado para Windows)

#### 1. Instalar Docker Desktop no Windows

1. **Download:** https://www.docker.com/products/docker-desktop
2. **Instalar** o Docker Desktop
3. **Abrir** Docker Desktop
4. **Configurar WSL 2:**
   - Abrir Settings (ícone de engrenagem)
   - General → "Use WSL 2 based engine" (marcar)
   - Resources → WSL Integration
   - Ativar integração com Ubuntu

5. **Reiniciar** Docker Desktop

#### 2. Verificar no WSL

```bash
# Abrir WSL/Ubuntu
wsl

# Testar Docker
docker --version
docker compose version

# Deve mostrar:
# Docker version 24.x.x
# Docker Compose version v2.x.x
```

---

### Opção 2: Docker Engine Nativo no WSL

Se preferir instalar Docker diretamente no WSL (sem Docker Desktop):

#### 1. Atualizar pacotes

```bash
wsl

sudo apt update
sudo apt upgrade -y
```

#### 2. Instalar dependências

```bash
sudo apt install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    software-properties-common
```

#### 3. Adicionar repositório do Docker

```bash
# Adicionar chave GPG do Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Adicionar repositório
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

#### 4. Instalar Docker

```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
```

#### 5. Configurar permissões

```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Aplicar mudanças
newgrp docker
```

#### 6. Iniciar Docker

```bash
sudo service docker start
```

#### 7. Testar

```bash
docker --version
docker compose version
docker run hello-world
```

---

## 🔧 Depois de Instalar Docker

### 1. Retornar ao projeto

```bash
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend
```

### 2. Executar setup

```bash
make setup
```

**Ou se o make ainda não reconhecer:**

```bash
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```

---

## 🆘 Troubleshooting

### Problema: "Cannot connect to Docker daemon"

**Solução (Docker Desktop):**
```bash
# Certifique-se de que Docker Desktop está rodando no Windows
```

**Solução (Docker Engine nativo):**
```bash
# Iniciar o serviço
sudo service docker start

# Verificar status
sudo service docker status
```

### Problema: "permission denied"

```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Relogar ou executar
newgrp docker

# Testar
docker ps
```

### Problema: WSL não encontra docker

**Se usando Docker Desktop:**
1. Abrir Docker Desktop
2. Settings → Resources → WSL Integration
3. Ativar integração com sua distro Ubuntu
4. Restart Docker Desktop
5. Fechar e reabrir WSL

---

## 📋 Verificação Completa

Execute estes comandos para garantir que está tudo OK:

```bash
# 1. Verificar Docker
docker --version
# Esperado: Docker version 24.x.x ou superior

# 2. Verificar Docker Compose
docker compose version
# Esperado: Docker Compose version v2.x.x

# 3. Testar Docker
docker run hello-world
# Esperado: mensagem de sucesso

# 4. Verificar se Make está instalado
make --version
# Esperado: GNU Make 4.x

# 5. Ir para o projeto
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend

# 6. Executar setup
make setup
```

---

## ✅ Comandos Alternativos (sem Make)

Se preferir não usar Make ou tiver problemas:

### Setup Manual:

```bash
# 1. Copiar .env
cp .env.docker .env

# 2. Build
docker compose build

# 3. Subir containers
docker compose up -d

# 4. Aguardar 10 segundos
sleep 10

# 5. Instalar dependências
docker compose exec app composer install

# 6. Gerar APP_KEY (já é automático pelo entrypoint, mas pode forçar)
docker compose exec app php artisan key:generate

# 7. Migrations
docker compose exec app php artisan migrate:fresh --seed
```

### Comandos do Dia a Dia:

```bash
# Iniciar
docker compose up -d

# Parar
docker compose down

# Ver logs
docker compose logs -f

# Acessar shell
docker compose exec app bash

# Executar artisan
docker compose exec app php artisan migrate
```

---

## 🎯 Resumo

| Método | Recomendado | Dificuldade | Observação |
|--------|-------------|-------------|------------|
| **Docker Desktop** | ✅ | Fácil | Melhor para Windows |
| **Docker Engine (WSL)** | ⚠️ | Médio | Mais leve, sem GUI |

### Recomendação:

Para Windows + WSL, use **Docker Desktop** com integração WSL 2.

É mais fácil, tem GUI, e funciona melhor com o ecossistema Windows.

---

## 📚 Links Úteis

- **Docker Desktop:** https://www.docker.com/products/docker-desktop
- **Docker Docs:** https://docs.docker.com/
- **Docker no WSL:** https://docs.docker.com/desktop/wsl/

---

**Depois de instalar, volte e execute:** `make setup` 🚀

