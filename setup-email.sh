# ========================================
# SCRIPT DE SETUP DE EMAIL
# ========================================

echo "📧 Configurando emails para RI-IFBA..."

# 1. Publique os templates de notificação
php artisan vendor:publish --tag=laravel-notifications

# 2. Crie template customizado de reset de senha
php artisan make:notification CustomResetPassword

echo "✅ Templates criados!"
echo ""
echo "📝 Para personalizar:"
echo "1. Edite: resources/views/notifications/reset-password.blade.php"
echo "2. Configure sua chave no .env"
echo "3. Teste: php artisan email:test seuemail@gmail.com"
