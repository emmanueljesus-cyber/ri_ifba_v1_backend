# ❌ ERRO CRÍTICO: Caminhos com Espaços no WSL + Docker

## 🚨 Problema Identificado

Se você clonar o projeto em uma pasta cujo caminho contém **espaços**, o Docker Compose no WSL apresenta bugs graves:

### Sintomas:

1. ❌ Containers são criados mas não iniciam
2. ❌ Containers somem misteriosamente após serem criados
3. ❌ `docker compose ps` mostra apenas alguns containers (Redis, Postgres) mas outros sumiram (App, Nginx)
4. ❌ Comandos ficam lentos ou travam
5. ❌ Setup falha parcialmente

### Exemplo de Caminho Problemático:

```bash
# ❌ TEM ESPAÇO - NÃO FUNCIONA!
/mnt/c/Users/nome/Documents/teste docker/ri_ifba_v1_backend
                                   ↑
                            Espaço aqui!
```

### Por Que Isso Acontece?

O Docker Compose no WSL tem problemas para interpretar caminhos com espaços, mesmo usando aspas ou escapes. Isso causa:

- **Volumes não montam corretamente**
- **Containers são criados mas removidos imediatamente**
- **Comandos falham silenciosamente**

---

## ✅ SOLUÇÃO

### Use APENAS caminhos SEM espaços:

```bash
# ✅ SEM ESPAÇO - FUNCIONA PERFEITAMENTE!
/mnt/c/Users/nome/Documents/IFBA/ri_ifba_v1_backend
/mnt/c/Users/nome/projetos/ri_ifba_v1_backend
/home/nome/ri_ifba_v1_backend
```

---

## 🔧 Como Corrigir Se Você Já Clonou em Lugar Errado

### Opção 1: Mover o Projeto (Recomendado)

```bash
# No WSL
cd /mnt/c/Users/seu_usuario/Documents

# Criar pasta sem espaço
mkdir -p IFBA

# Mover projeto
mv "teste docker/ri_ifba_v1_backend" IFBA/

# Entrar na nova pasta
cd IFBA/ri_ifba_v1_backend

# Verificar se funcionou
pwd
# Deve mostrar: /mnt/c/Users/seu_usuario/Documents/IFBA/ri_ifba_v1_backend
```

### Opção 2: Clonar Novamente no Lugar Certo

```bash
# No WSL
cd ~
# ou
cd /mnt/c/Users/seu_usuario/Documents/IFBA

# Se a pasta já existe, remover
rm -rf ri_ifba_v1_backend

# Clonar novamente
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git

# Entrar e configurar
cd ri_ifba_v1_backend
make setup
```

### Opção 3: Trabalhar Apenas no WSL (Mais Rápido)

```bash
# Clonar direto no home do WSL (SEM /mnt/c)
cd ~
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
make setup
```

**💡 Vantagens:**
- Muito mais rápido (não usa sistema de arquivos do Windows)
- Zero problemas com espaços
- Melhor performance do Docker

---

## 📋 Verificar Se Seu Caminho Tem Espaços

```bash
# No WSL, dentro da pasta do projeto
pwd

# Verificar se tem espaços
pwd | grep ' '

# Se retornar algo, TEM ESPAÇO!
# Se não retornar nada, está OK!
```

### Exemplos:

```bash
# ❌ RUIM - Tem espaço
$ pwd
/mnt/c/Users/nome/Documents/teste docker/projeto

$ pwd | grep ' '
/mnt/c/Users/nome/Documents/teste docker/projeto
                           ^^^^^

# ✅ BOM - Sem espaço
$ pwd
/mnt/c/Users/nome/Documents/IFBA/projeto

$ pwd | grep ' '
(nenhum resultado)
```

---

## 🎯 Passo a Passo Correto Para Subir o Projeto

### 1️⃣ Garantir que está em caminho SEM espaços

```bash
# No WSL
cd /mnt/c/Users/seu_usuario/Documents/IFBA

# OU trabalhar direto no WSL (mais rápido)
cd ~
```

### 2️⃣ Clonar (se ainda não clonou)

```bash
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
```

### 3️⃣ Verificar se não tem espaços

```bash
pwd | grep ' '
# Se retornar algo, você está no lugar errado!
# Volte ao passo 1
```

### 4️⃣ Configurar permissões Docker (apenas 1ª vez)

```bash
# Adicionar usuário ao grupo docker (apenas 1ª vez)
sudo usermod -aG docker $USER

# Recarregar grupos
newgrp docker

# Testar
docker ps
# Deve funcionar sem pedir sudo!
```

### 5️⃣ Subir o projeto

```bash
# Comando único que faz TUDO
make setup

# Aguardar 5-10 minutos...
```

### 6️⃣ Verificar se funcionou

```bash
# Ver todos os containers
docker compose ps

# Deve mostrar 5 containers "Up":
# ri-ifba-app        Up
# ri-ifba-nginx      Up
# ri-ifba-postgres   Up (healthy)
# ri-ifba-redis      Up (healthy)
# ri-ifba-queue      Up
```

### 7️⃣ Testar API

```bash
# Corrigir o erro de digitação (porta 8000, não 800!)
curl http://localhost:8000/api/v1/cardapio/hoje

# Deve retornar JSON
```

---

## ❓ FAQ

### ❓ Por que meu projeto não funciona?

**Resposta:** Verifique se o caminho tem espaços:
```bash
pwd | grep ' '
```

Se retornar algo, **MOVA O PROJETO** para uma pasta sem espaços.

### ❓ Como saber se estou no lugar certo?

**Resposta:** Execute:
```bash
pwd
```

**✅ CERTO:**
- `/home/seu_usuario/ri_ifba_v1_backend`
- `/mnt/c/Users/seu_usuario/Documents/IFBA/ri_ifba_v1_backend`
- `/mnt/c/Users/seu_usuario/projetos/ri_ifba_v1_backend`

**❌ ERRADO:**
- `/mnt/c/Users/seu_usuario/Documents/teste docker/ri_ifba_v1_backend` ← tem espaço!
- `/mnt/c/Users/seu_usuario/My Documents/ri_ifba_v1_backend` ← tem espaço!

### ❓ Posso simplesmente renomear a pasta?

**Sim!** Mas é mais fácil clonar novamente:

```bash
# Opção 1: Renomear (remover espaço)
cd /mnt/c/Users/seu_usuario/Documents
mv "teste docker" teste_docker
cd teste_docker/ri_ifba_v1_backend

# Opção 2: Clonar de novo (recomendado)
cd /mnt/c/Users/seu_usuario/Documents/IFBA
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
make setup
```

### ❓ O erro é mesmo por causa do espaço?

**SIM!** Veja os sintomas que você teve:

1. ✅ `make setup` rodou
2. ✅ Composer instalou dependências
3. ✅ Migrations rodaram
4. ✅ Seeders popularam o banco
5. ❌ **MAS** `docker compose ps` mostrava apenas 2 containers (Redis e Queue reiniciando)
6. ❌ **E** `docker ps -a` mostrava só Redis
7. ❌ **E** App, Nginx sumiram misteriosamente

**Isso é o comportamento exato do bug de caminhos com espaços no WSL!**

### ❓ Qual a melhor localização para o projeto?

**Ordem de preferência:**

1. **🥇 Melhor:** Home do WSL
   ```bash
   ~/ri_ifba_v1_backend
   # ou
   /home/seu_usuario/ri_ifba_v1_backend
   ```
   - ✅ Mais rápido (não usa Windows)
   - ✅ Zero problemas com espaços
   - ✅ Melhor performance do Docker

2. **🥈 Bom:** Pasta sem espaços no Windows
   ```bash
   /mnt/c/Users/seu_usuario/Documents/IFBA/ri_ifba_v1_backend
   # ou
   /mnt/c/Users/seu_usuario/projetos/ri_ifba_v1_backend
   ```
   - ✅ Sem espaços
   - ✅ Acessível do Windows Explorer
   - ⚠️ Um pouco mais lento

3. **❌ Ruim:** Qualquer pasta com espaço
   ```bash
   /mnt/c/Users/seu_usuario/Documents/teste docker/...
   /mnt/c/Users/seu_usuario/My Documents/...
   ```
   - ❌ Docker Compose quebra
   - ❌ Containers somem
   - ❌ Setup falha parcialmente

---

## 🎯 Resumo Executivo

| Item | Status | Ação |
|------|--------|------|
| **Problema** | Caminho com espaço | Mover projeto para pasta sem espaços |
| **Erro no curl** | Digitou porta 800 | Usar porta **8000** |
| **Containers sumindo** | Bug do Docker/WSL | Usar caminho sem espaços |
| **Setup parcial** | Bug do Docker/WSL | Usar caminho sem espaços |

### Comando Para Resolver TUDO:

```bash
# 1. Ir para pasta SEM espaços
cd /mnt/c/Users/seu_usuario/Documents/IFBA

# 2. Clonar (ou mover)
git clone https://github.com/emmanueljesus-cyber/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend

# 3. Subir (SEM sudo após configurar)
make setup

# 4. Testar (PORTA 8000, não 800!)
curl http://localhost:8000/api/v1/cardapio/hoje
```

**🎉 Pronto! Deve funcionar perfeitamente!**

---

## 📚 Documentação Relacionada

- **DOCKER_BANCO_DADOS.md** - Guia completo sobre Docker
- **DOCKER_GUIDE.md** - Comandos avançados
- **README.md** - Visão geral do projeto

---

**Última atualização:** Janeiro 2026  
**Bug identificado em:** Docker Compose 2.x + WSL2 + Caminhos com espaços  
**Plataformas afetadas:** Windows 10/11 com WSL2

