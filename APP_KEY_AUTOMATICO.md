# 🔑 Geração Automática do APP_KEY no Docker

## ✅ Sim! O APP_KEY é gerado automaticamente

O `entrypoint.sh` do Docker **verifica e gera automaticamente** o `APP_KEY` quando o container sobe.

---

## 🔄 Como Funciona

### 1. Ao iniciar o container, o script verifica:

```bash
# 1. Se existe arquivo .env
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 2. Se APP_KEY está vazio ou inválido
if [ -z "$APP_KEY_VALUE" ] || [ "$APP_KEY_VALUE" = "" ]; then
    php artisan key:generate --force --ansi
    echo "✅ APP_KEY gerado com sucesso!"
fi
```

### 2. Quando acontece:

| Situação | Ação |
|----------|------|
| `.env` não existe | Copia `.env.example` + Gera `APP_KEY` |
| `APP_KEY` está vazio | Gera novo `APP_KEY` |
| `APP_KEY` inválido (sem `base64:`) | Gera novo `APP_KEY` |
| `APP_KEY` já existe e é válido | Usa o existente |

---

## 🚀 Desenvolvimento

### Primeira vez (Docker):
```bash
docker-compose up -d
```

**O que acontece automaticamente:**
1. ✅ Container sobe
2. ✅ `entrypoint.sh` executa
3. ✅ Verifica se `.env` existe
4. ✅ **Gera `APP_KEY` automaticamente**
5. ✅ Executa migrations
6. ✅ Aplicação pronta!

### Ver logs:
```bash
docker-compose logs app | grep APP_KEY
```

Você verá:
```
🔑 Gerando APP_KEY...
✅ APP_KEY gerado com sucesso!
```

---

## 🏭 Produção

### Opção 1: Geração Automática (Recomendado)

```bash
# 1. Configurar .env.production (deixar APP_KEY vazio)
nano .env.production

APP_NAME=ri_ifba_v1_backend
APP_ENV=production
APP_KEY=
APP_DEBUG=false

# 2. Copiar para .env
cp .env.production .env

# 3. Subir container
docker-compose -f docker-compose.prod.yml up -d
```

**O entrypoint.sh vai gerar automaticamente!** ✅

### Opção 2: Gerar Manualmente (Antes de subir)

```bash
# 1. Build da imagem
docker-compose -f docker-compose.prod.yml build

# 2. Subir containers
docker-compose -f docker-compose.prod.yml up -d

# 3. Gerar key manualmente
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate --force

# 4. Ver o valor gerado
docker-compose -f docker-compose.prod.yml exec app grep APP_KEY .env
```

### Opção 3: Gerar Localmente e Copiar

```bash
# 1. Gerar localmente
php artisan key:generate --show

# Resultado: base64:abcd1234...

# 2. Copiar para .env.production
nano .env.production

APP_KEY=base64:abcd1234...
```

---

## 🔍 Verificar se foi gerado

### Via Docker:
```bash
# Ver o .env dentro do container
docker-compose exec app cat .env | grep APP_KEY

# Ou ver logs
docker-compose logs app | grep -A 2 "APP_KEY"
```

### Resultado esperado:
```
APP_KEY=base64:random_long_string_here...
```

---

## 🎯 Exemplos Práticos

### Desenvolvimento:
```bash
# Setup inicial (gera APP_KEY automaticamente)
make setup

# Ou
docker-compose up -d

# Verificar
docker-compose exec app php artisan tinker
>>> config('app.key')
"base64:abcd..."  # ✅ Gerado!
```

### Produção:
```bash
# 1. Configurar .env.production (APP_KEY vazio)
cp .env.production.example .env.production
nano .env.production

# 2. Deploy (gera APP_KEY automaticamente)
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d

# 3. Verificar
docker-compose -f docker-compose.prod.yml exec app grep APP_KEY .env
```

---

## ⚠️ Importante: Backup do APP_KEY

### Em produção, **sempre faça backup do APP_KEY**!

```bash
# Salvar APP_KEY em local seguro
docker-compose exec app grep APP_KEY .env > APP_KEY_BACKUP.txt

# Ou adicionar ao .env.backup
echo "# APP_KEY de produção (backup)" > .env.backup
docker-compose exec app grep APP_KEY .env >> .env.backup
```

**Por quê?**
- 🔒 Usado para criptografar dados
- 🔐 Usado para sessões
- 🔑 Se mudar, dados criptografados ficam ilegíveis

---

## 🔄 Regenerar APP_KEY

### Se necessário regenerar:

```bash
# ⚠️ ATENÇÃO: Vai invalidar sessões e dados criptografados

# Desenvolvimento
docker-compose exec app php artisan key:generate --force

# Produção
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate --force
```

---

## 📋 Checklist

### Desenvolvimento:
- [x] `docker-compose up -d`
- [x] `APP_KEY` gerado automaticamente ✅
- [x] Ver logs: `docker-compose logs app | grep APP_KEY`

### Produção:
- [x] `.env.production` configurado (APP_KEY vazio)
- [x] `docker-compose -f docker-compose.prod.yml up -d`
- [x] `APP_KEY` gerado automaticamente ✅
- [x] **Fazer backup do APP_KEY** 🔒
- [x] Verificar: `docker-compose exec app grep APP_KEY .env`

---

## 🆘 Troubleshooting

### Problema: APP_KEY não foi gerado

```bash
# Verificar logs
docker-compose logs app | grep -i key

# Gerar manualmente
docker-compose exec app php artisan key:generate --force

# Verificar
docker-compose exec app cat .env | grep APP_KEY
```

### Problema: Erro "No application encryption key"

```bash
# Gerar key
docker-compose exec app php artisan key:generate --force

# Limpar cache
docker-compose exec app php artisan config:clear

# Reiniciar
docker-compose restart app
```

---

## ✅ Resumo

| Ambiente | APP_KEY | Como |
|----------|---------|------|
| **Desenvolvimento** | ✅ Automático | `entrypoint.sh` gera na primeira execução |
| **Produção** | ✅ Automático | `entrypoint.sh` gera se estiver vazio |
| **Manual** | ⚙️ Opcional | `php artisan key:generate --force` |

**Você NÃO precisa se preocupar!** O Docker cuida disso automaticamente. 🎉

---

**Última atualização:** 13/01/2026  
**Script:** `docker/entrypoint.sh` (linhas 15-30)

