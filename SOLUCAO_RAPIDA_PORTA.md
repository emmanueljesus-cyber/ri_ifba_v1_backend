# ✅ SOLUÇÃO RÁPIDA: Erro Porta 5432

## 🎯 Problema Resolvido!

A porta **5432** estava ocupada pelo PostgreSQL local do Windows.  
Configuramos o Docker para usar a porta **54320** externamente.

---

## 🚀 Execute Estes Comandos (Copie e Cole no WSL):

### 1️⃣ Limpar containers antigos

```bash
cd "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend"
sudo docker compose down -v
```

### 2️⃣ Copiar arquivos atualizados

```bash
cd "/mnt/c/Users/emmanuel.jesus/Documents/IFBA/ri_ifba_v1_backend"
cp .env.docker docker-compose.yml "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend/"
cp ERRO_PORTA_5432.md "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend/"
```

### 3️⃣ Verificar alterações

```bash
cd "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend"
grep "DB_PORT_EXTERNAL" .env.docker
```

**Deve mostrar:**
```
DB_PORT_EXTERNAL=54320
```

### 4️⃣ Iniciar o projeto

```bash
sudo make setup
```

**Ou manualmente:**

```bash
sudo docker compose up -d
```

---

## ⏱️ Tempo Estimado

- **Setup completo:** 3-5 minutos (primeira vez)
- **Já tem imagens:** 30-60 segundos

---

## ✅ Como Saber Se Funcionou?

### Verificar containers rodando:

```bash
sudo docker compose ps
```

**Deve mostrar:**

```
NAME                STATUS          PORTS
ri-ifba-app         Up             
ri-ifba-nginx       Up              0.0.0.0:8000->80/tcp
ri-ifba-postgres    Up (healthy)    0.0.0.0:54320->5432/tcp  ⬅️ Porta 54320!
ri-ifba-redis       Up (healthy)    0.0.0.0:6379->6379/tcp
ri-ifba-queue       Up
```

### Testar API:

```bash
curl http://localhost:8000/api/v1/cardapio/hoje
```

**Deve retornar JSON do cardápio** (ou erro 404 se não tiver cardápio cadastrado - normal!)

---

## 🔌 Acessando o Banco de Dados

### Do Dentro do Docker (Laravel):

```
DB_HOST=postgres
DB_PORT=5432  ⬅️ Porta interna (não muda!)
```

### Do Windows (DBeaver/pgAdmin):

```
Host: localhost
Porta: 54320  ⬅️ Nova porta externa!
Banco: ri_ifba_v1
Usuário: postgres
Senha: 201099
```

### Via Adminer (Interface Web):

```bash
sudo make adminer-up
```

Acesse: **http://localhost:8080**

```
Sistema: PostgreSQL
Servidor: postgres
Usuário: postgres
Senha: 201099
Base: ri_ifba_v1
```

---

## 🐛 Se Ainda Não Funcionar

### Ver logs em tempo real:

```bash
sudo docker compose logs -f
```

### Ver logs do PostgreSQL:

```bash
sudo docker compose logs postgres
```

### Ver logs da aplicação:

```bash
sudo docker compose logs app
```

### Limpar TUDO e recomeçar:

```bash
cd "/mnt/c/Users/emmanuel.jesus/Documents/teste docker/ri_ifba_v1_backend"
sudo docker compose down -v
sudo docker image rm ri-ifba-backend:dev || true
sudo docker builder prune -af
sudo make setup
```

---

## 📋 Checklist

Marque conforme executar:

- [ ] Limpou containers antigos (`docker compose down -v`)
- [ ] Copiou arquivos atualizados (`.env.docker`, `docker-compose.yml`)
- [ ] Verificou `DB_PORT_EXTERNAL=54320` no `.env.docker`
- [ ] Executou `sudo make setup`
- [ ] Containers estão rodando (`docker compose ps`)
- [ ] PostgreSQL está na porta **54320** (não 5432)
- [ ] API responde (`curl http://localhost:8000/api/v1/cardapio/hoje`)

---

## 🎉 Depois que funcionar

### Usuários de teste criados:

**Admin:**
```
Matrícula: 20212160036
Senha: senha123
```

**Bolsista:**
```
Matrícula: 20221234567
Senha: senha123
```

**Não Bolsista:**
```
Matrícula: 20229876543
Senha: senha123
```

### Endpoints para testar:

```bash
# Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"matricula": "20212160036", "password": "senha123"}'

# Cardápio hoje
curl http://localhost:8000/api/v1/cardapio/hoje

# Cardápio semana
curl http://localhost:8000/api/v1/cardapio/semana
```

---

## 📚 Documentação Completa

- [ERRO_PORTA_5432.md](./ERRO_PORTA_5432.md) - Explicação detalhada do problema
- [DOCKER_E_BANCO_DE_DADOS.md](./DOCKER_E_BANCO_DE_DADOS.md) - Como funciona o Docker + Banco
- [COMO_RODAR.md](./COMO_RODAR.md) - Guia completo de instalação

---

**Última atualização:** 13/01/2026  
**Status:** ✅ Problema resolvido - Pronto para uso!

