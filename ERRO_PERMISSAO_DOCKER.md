# 🔧 Erro de Permissão do Docker - SOLUÇÃO RÁPIDA

## ❌ Erro Encontrado

```bash
permission denied while trying to connect to the Docker daemon socket at unix:///var/run/docker.sock
```

---

## ✅ SOLUÇÕES (Em ordem de preferência)

### Solução 1: Adicionar Usuário ao Grupo Docker (RECOMENDADO)

```bash
# 1. Adicionar seu usuário ao grupo docker
sudo usermod -aG docker $USER

# 2. Aplicar as mudanças (escolha UMA das opções):

# Opção A: Recarregar grupo (mais rápido)
newgrp docker

# Opção B: Relogar no WSL (mais garantido)
exit
# Depois abra WSL novamente

# 3. Verificar se funcionou
docker ps

# 4. Voltar ao projeto e executar
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend
make setup
```

---

### Solução 2: Usar sudo (Temporário - NÃO RECOMENDADO)

```bash
# Se a Solução 1 não funcionar, use sudo temporariamente
sudo make setup

# Ou executar comandos Docker diretamente
sudo docker compose build
sudo docker compose up -d
```

**⚠️ Problema:** Vai criar arquivos com permissão root. Melhor usar Solução 1.

---

### Solução 3: Iniciar o Serviço Docker (Se não estiver rodando)

```bash
# Verificar status do Docker
sudo service docker status

# Se não estiver rodando, iniciar
sudo service docker start

# Verificar novamente
sudo service docker status

# Testar
docker ps
```

---

### Solução 4: Docker Desktop (Se estiver usando)

Se você instalou Docker Desktop:

1. **Abrir Docker Desktop no Windows**
2. **Verificar se está rodando** (ícone na bandeja do sistema)
3. **Settings → Resources → WSL Integration**
4. **Ativar integração com Ubuntu**
5. **Apply & Restart**
6. **Fechar e reabrir WSL**

```bash
# Testar no WSL
docker --version
docker ps

# Executar setup
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend
make setup
```

---

## 🎯 SOLUÇÃO COMPLETA PASSO A PASSO

### Passo 1: Adicionar ao Grupo Docker

```bash
# Execute estes comandos no WSL:
sudo usermod -aG docker $USER
newgrp docker
```

### Passo 2: Verificar

```bash
# Deve funcionar SEM sudo:
docker ps

# Se funcionar, você verá uma lista vazia ou containers rodando
# Se ainda der erro de permissão, vá para Passo 3
```

### Passo 3: Verificar Docker Daemon

```bash
# Verificar se Docker está rodando
sudo service docker status

# Se não estiver, iniciar
sudo service docker start

# Testar novamente
docker ps
```

### Passo 4: Executar Setup

```bash
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend
make setup
```

---

## 🔍 Diagnóstico

### Verificar qual é o problema:

```bash
# 1. Docker está instalado?
docker --version

# 2. Docker daemon está rodando?
sudo service docker status

# 3. Você está no grupo docker?
groups | grep docker

# 4. Socket existe?
ls -la /var/run/docker.sock
```

### Resultados esperados:

```bash
# groups deve mostrar:
emmanueljesus adm cdrom sudo dip plugdev docker

# service docker status deve mostrar:
* Docker is running

# ls deve mostrar:
srw-rw---- 1 root docker 0 Jan 13 10:00 /var/run/docker.sock
```

---

## ⚠️ Problema Comum: Usuário não está no grupo docker

### Sintomas:
- Comando `docker` precisa de `sudo`
- Erro "permission denied"
- `groups` não mostra "docker"

### Solução Definitiva:

```bash
# 1. Adicionar ao grupo
sudo usermod -aG docker $USER

# 2. IMPORTANTE: Você precisa SAIR e ENTRAR novamente no WSL
exit

# 3. Abrir WSL novamente
wsl

# 4. Verificar se está no grupo
groups

# Deve aparecer "docker" na lista

# 5. Testar
docker ps

# 6. Executar setup
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend
make setup
```

---

## 🚀 COMANDOS RÁPIDOS (Copie e Cole)

### Opção A: Solução Completa (RECOMENDADA)

```bash
# Execute tudo de uma vez:
sudo usermod -aG docker $USER && \
newgrp docker && \
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend && \
docker ps && \
echo "✅ Docker funcionando! Executando setup..." && \
make setup
```

### Opção B: Com Sudo (Se Opção A não funcionar)

```bash
cd /mnt/c/Users/emmanuel.jesus/Documents/teste\ docker/ri_ifba_v1_backend && \
sudo docker compose build && \
sudo docker compose up -d && \
sleep 10 && \
sudo docker compose exec app composer install && \
sudo docker compose exec app php artisan migrate:fresh --seed
```

---

## 📋 Checklist de Troubleshooting

- [ ] Docker está instalado? (`docker --version`)
- [ ] Docker daemon está rodando? (`sudo service docker status`)
- [ ] Usuário está no grupo docker? (`groups | grep docker`)
- [ ] Socket tem permissão correta? (`ls -la /var/run/docker.sock`)
- [ ] Saiu e entrou no WSL depois de adicionar ao grupo?
- [ ] Docker Desktop está rodando? (se usando)

---

## 🎉 DEPOIS DE RESOLVER

Quando funcionar, você verá:

```bash
emmanueljesus@DESKTOP-TAEU5H0:~/projeto$ make setup
🚀 Configurando projeto...
docker compose build
[+] Building 125.3s (17/17) FINISHED
✅ Setup concluído!

📋 Acesse:
   - Backend: http://localhost:8000
   - Adminer: http://localhost:8080

🔑 Credenciais:
   - Admin: 10000000001 / password
   - Bolsista: 20231160001 / password
```

---

## 💡 Resumo

**Causa:** Usuário não tem permissão para acessar Docker daemon

**Solução:**
1. `sudo usermod -aG docker $USER`
2. `exit` e abrir WSL novamente
3. `make setup`

**Tempo:** 1-2 minutos

---

**Depois de resolver, volte e execute:** `make setup` ✅

