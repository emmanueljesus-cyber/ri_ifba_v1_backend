# 🚀 Guia Rápido de Deploy - RI IFBA Backend

## 📋 Checklist Pré-Deploy

### 1. Preparação do Servidor

```bash
# Instalar Docker e Docker Compose
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Reiniciar sessão
exit
# Login novamente

# Verificar instalação
docker --version
docker-compose --version
```

### 2. Clonar Repositório

```bash
cd /var/www
sudo git clone https://github.com/seu-usuario/ri_ifba_v1_backend.git
cd ri_ifba_v1_backend
sudo chown -R $USER:$USER .
```

### 3. Configurar Ambiente de Produção

```bash
# Copiar exemplo de produção
cp .env.production.example .env.production

# Editar variáveis sensíveis
nano .env.production
```

**⚠️ IMPORTANTE - Alterar estas variáveis:**

```env
# Aplicação
APP_ENV=production
APP_DEBUG=false
APP_KEY=                                    # Será gerado automaticamente
APP_URL=https://seu-dominio.com.br

# Banco de Dados
DB_DATABASE=ri_ifba_production
DB_USERNAME=ri_ifba_user
DB_PASSWORD=SENHA_FORTE_AQUI_MIN_16_CHARS  # MUDAR!

# Redis
REDIS_PASSWORD=SENHA_REDIS_FORTE_AQUI      # MUDAR!

# Email (configurar SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app-gmail
MAIL_FROM_ADDRESS="noreply@ifba.edu.br"

# Sanctum (CORS)
SANCTUM_STATEFUL_DOMAINS=seu-dominio.com.br
SESSION_DOMAIN=.seu-dominio.com.br
```

### 4. Build e Deploy

```bash
# Copiar .env.production para .env
cp .env.production .env

# Build da imagem
docker-compose -f docker-compose.prod.yml build

# Subir containers
docker-compose -f docker-compose.prod.yml up -d

# Aguardar containers iniciarem (30s)
sleep 30

# Gerar APP_KEY
docker-compose -f docker-compose.prod.yml exec app php artisan key:generate --force

# Executar migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Popular banco (opcional - dados de teste)
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --class=UserSeeder

# Otimizar para produção
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### 5. Configurar SSL/HTTPS (Let's Encrypt)

```bash
# Instalar Certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx -y

# Obter certificado
sudo certbot certonly --standalone -d seu-dominio.com.br

# Certificados estarão em:
# /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem
# /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem

# Copiar para projeto
sudo mkdir -p docker/nginx/ssl
sudo cp /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem docker/nginx/ssl/cert.pem
sudo cp /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem docker/nginx/ssl/key.pem
sudo chown -R $USER:$USER docker/nginx/ssl

# Configurar renovação automática
sudo certbot renew --dry-run
```

### 6. Configurar Nginx para HTTPS

Criar `docker/nginx/conf.d-prod/laravel-ssl.conf`:

```nginx
server {
    listen 80;
    server_name seu-dominio.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com.br;
    root /var/www/html/public;
    index index.php;

    # SSL
    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # CORS (ajuste conforme seu frontend)
    add_header Access-Control-Allow-Origin "https://seu-frontend.com.br" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type" always;
    add_header Access-Control-Allow-Credentials "true" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Reiniciar Nginx:
```bash
docker-compose -f docker-compose.prod.yml restart nginx
```

### 7. Configurar Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp     # SSH
sudo ufw allow 80/tcp     # HTTP
sudo ufw allow 443/tcp    # HTTPS
sudo ufw enable
sudo ufw status
```

### 8. Configurar Backup Automático

Criar script `/usr/local/bin/backup-ri-ifba.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backups/ri-ifba"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup do banco de dados
docker exec ri-ifba-postgres-prod pg_dump -U ri_ifba_user ri_ifba_production | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup dos uploads (se houver)
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" /var/www/ri_ifba_v1_backend/storage/app

# Manter apenas últimos 7 dias
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup concluído: $DATE"
```

Tornar executável e agendar:
```bash
sudo chmod +x /usr/local/bin/backup-ri-ifba.sh

# Adicionar ao crontab (diário às 3h)
sudo crontab -e
# Adicionar linha:
0 3 * * * /usr/local/bin/backup-ri-ifba.sh >> /var/log/backup-ri-ifba.log 2>&1
```

---

## ✅ Verificação Pós-Deploy

### 1. Verificar Containers

```bash
docker-compose -f docker-compose.prod.yml ps
```

Todos devem estar `Up` e `healthy`.

### 2. Verificar Logs

```bash
# Logs da aplicação
docker-compose -f docker-compose.prod.yml logs -f app

# Logs do Nginx
docker-compose -f docker-compose.prod.yml logs -f nginx

# Logs do PostgreSQL
docker-compose -f docker-compose.prod.yml logs -f postgres
```

### 3. Testar API

```bash
# Health check
curl https://seu-dominio.com.br/health

# Cardápio público
curl https://seu-dominio.com.br/api/v1/cardapio/hoje

# Login (deve retornar 200 ou 401)
curl -X POST https://seu-dominio.com.br/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"matricula":"10000000001","password":"password"}'
```

### 4. Verificar Permissões

```bash
docker-compose -f docker-compose.prod.yml exec app ls -la storage
# Deve mostrar owner 'laravel'
```

---

## 🔄 Comandos de Manutenção

### Atualizar Aplicação

```bash
# Pull do código
git pull origin main

# Rebuild e restart
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d

# Executar migrations (se houver)
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Otimizar caches
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

# Reiniciar queue workers
docker-compose -f docker-compose.prod.yml restart queue-worker
```

### Ver Logs

```bash
# Últimas 100 linhas
docker-compose -f docker-compose.prod.yml logs --tail=100

# Seguir logs em tempo real
docker-compose -f docker-compose.prod.yml logs -f

# Logs específicos
docker-compose -f docker-compose.prod.yml logs -f app
docker-compose -f docker-compose.prod.yml logs -f nginx
```

### Backup Manual

```bash
# Banco de dados
docker exec ri-ifba-postgres-prod pg_dump -U ri_ifba_user ri_ifba_production > backup_$(date +%Y%m%d).sql

# Restore
cat backup_20260113.sql | docker exec -i ri-ifba-postgres-prod psql -U ri_ifba_user ri_ifba_production
```

### Reiniciar Serviços

```bash
# Todos
docker-compose -f docker-compose.prod.yml restart

# Específico
docker-compose -f docker-compose.prod.yml restart app
docker-compose -f docker-compose.prod.yml restart nginx
```

---

## 🔧 Troubleshooting Produção

### Problema: Container não inicia

```bash
# Ver logs
docker-compose -f docker-compose.prod.yml logs app

# Verificar .env
docker-compose -f docker-compose.prod.yml exec app cat .env | grep DB_

# Rebuild
docker-compose -f docker-compose.prod.yml build --no-cache app
docker-compose -f docker-compose.prod.yml up -d
```

### Problema: 500 Internal Server Error

```bash
# Ver logs Laravel
docker-compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log

# Limpar caches
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
docker-compose -f docker-compose.prod.yml exec app php artisan config:clear

# Verificar permissões
docker-compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache
```

### Problema: Banco de dados não conecta

```bash
# Verificar PostgreSQL
docker-compose -f docker-compose.prod.yml ps postgres

# Testar conexão
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📊 Monitoramento

### Recursos do Sistema

```bash
# Uso de CPU/Memória
docker stats

# Espaço em disco
df -h
docker system df
```

### Health Checks

```bash
# Via curl
watch -n 5 'curl -s https://seu-dominio.com.br/health'

# Via docker
docker-compose -f docker-compose.prod.yml ps
```

---

## 🔐 Segurança Adicional

### 1. Fail2Ban (Proteção contra ataques)

```bash
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

### 2. Limitar Tentativas de Login

Configurar rate limiting no Laravel (já incluído no projeto).

### 3. Monitorar Logs

```bash
# Instalar Logwatch
sudo apt install logwatch -y
```

### 4. Atualizar Sistema

```bash
# Agendar updates automáticos
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## 📈 Próximos Passos

1. ✅ Configurar monitoramento (Grafana/Prometheus)
2. ✅ Implementar CI/CD (GitHub Actions)
3. ✅ Configurar alertas (email/Slack)
4. ✅ Documentar procedimentos de rollback
5. ✅ Criar runbook de incidentes

---

## 📞 Suporte

Em caso de problemas:
1. Verificar logs: `docker-compose -f docker-compose.prod.yml logs`
2. Consultar `DOCKER_GUIDE.md`
3. Abrir issue no repositório

---

**Deploy preparado por:** Equipe RI IFBA  
**Última atualização:** 13/01/2026  
**Versão:** 1.0.0

