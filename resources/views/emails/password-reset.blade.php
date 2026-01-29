<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de Senha - RI IFBA</title>
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
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #22c55e;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #64748b;
            font-size: 14px;
            margin-top: 5px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin-bottom: 15px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #92400e;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 12px;
        }
        .link-fallback {
            word-break: break-all;
            font-size: 12px;
            color: #64748b;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ RI IFBA</h1>
            <p>Restaurante Institucional</p>
        </div>

        <div class="content">
            <p>Olá, <strong>{{ $user->nome }}</strong>!</p>

            <p>Recebemos uma solicitação para redefinir a senha da sua conta no sistema do Restaurante Institucional do IFBA.</p>

            <p>Clique no botão abaixo para criar uma nova senha:</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Redefinir Minha Senha</a>
            </div>

            <p class="link-fallback">
                Se o botão não funcionar, copie e cole este link no seu navegador:<br>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </p>

            <div class="warning">
                <strong>⚠️ Atenção:</strong> Este link é válido por apenas <strong>1 hora</strong>.
                Se você não solicitou esta redefinição, ignore este e-mail - sua senha permanecerá a mesma.
            </div>
        </div>

        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>© {{ date('Y') }} IFBA - Restaurante Institucional</p>
        </div>
    </div>
</body>
</html>
