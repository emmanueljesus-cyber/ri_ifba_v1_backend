<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao RI IFBA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #006633;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #006633;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .welcome-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
            background-color: #e7f3ff;
            color: #0056b3;
        }
        .credentials {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border: 1px dashed #dee2e6;
        }
        .credentials p {
            margin: 8px 0;
            font-size: 15px;
        }
        .credentials strong {
            color: #495057;
        }
        .password-box {
            background-color: #fff;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            font-family: monospace;
            font-size: 18px;
            color: #d63384;
            display: inline-block;
            margin-top: 5px;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #006633;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Restaurante Institucional</h1>
            <p>IFBA - Instituto Federal da Bahia</p>
        </div>

        <p>Olá, <strong>{{ $user->nome }}</strong>!</p>

        <p>Você foi cadastrado como <strong>Administrador</strong> no sistema do Restaurante Institucional do IFBA.</p>

        <div style="text-align: center;">
            <span class="welcome-badge">ACESSO HABILITADO</span>
        </div>

        <p>Para acessar o painel administrativo, utilize as credenciais abaixo:</p>

        <div class="credentials">
            <p><strong>📍 Matrícula (Login):</strong> {{ $user->matricula }}</p>
            <p><strong>📧 E-mail:</strong> {{ $user->email }}</p>
            <p><strong>🔑 Senha Temporária:</strong><br>
                <span class="password-box">{{ $senhaTemporaria }}</span>
            </p>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/login" class="cta-button">Acessar Sistema</a>
        </div>

        <div class="warning">
            <strong>⚠️ Atenção:</strong> Por segurança, recomendamos que você altere sua senha logo no primeiro acesso através do seu perfil no sistema.
        </div>

        <p>Como administrador, você poderá gerenciar cardápios, validar presenças de bolsistas, emitir relatórios e muito mais.</p>

        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>© {{ date('Y') }} - RI IFBA</p>
        </div>
    </div>
</body>
</html>
