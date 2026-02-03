<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'RI-IFBA' }}</title>
    <style>
        /* Reset e base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }

        /* Container principal */
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Header - Verde IFBA */
        .header {
            background: linear-gradient(135deg, #1e7e34, #28a745);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Conteúdo principal */
        .content {
            padding: 40px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #444;
        }

        /* Botão de ação */
        .action-button {
            text-align: center;
            margin: 35px 0;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        /* Info box */
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .info-box h4 {
            color: #1e7e34;
            margin-bottom: 10px;
        }

        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-text {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .contact-info {
            font-size: 12px;
            color: #868e96;
        }

        /* Responsivo */
        @media (max-width: 600px) {
            .email-container { margin: 10px; }
            .header, .content, .footer { padding: 25px 20px; }
            .logo { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🍽️ RI-IFBA</div>
            <div class="subtitle">Restaurante Institucional - Instituto Federal da Bahia</div>
        </div>

        <!-- Conteúdo -->
        <div class="content">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                <strong>Instituto Federal da Bahia</strong><br>
                Campus Salvador - Restaurante Institucional
            </div>
            <div class="contact-info">
                📧 restaurante@ifba.edu.br | 📞 (71) 3891-9400<br>
                📍 Rua Emídio dos Santos, s/nº - Barbalho, Salvador - BA
            </div>
        </div>
    </div>
</body>
</html>