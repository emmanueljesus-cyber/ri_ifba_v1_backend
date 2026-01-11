<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decisão sobre Justificativa - RI IFBA</title>
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
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
        }
        .status-aprovada {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejeitada {
            background-color: #f8d7da;
            color: #721c24;
        }
        .details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .details p {
            margin: 8px 0;
        }
        .details strong {
            color: #495057;
        }
        .observacao {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .observacao h3 {
            margin: 0 0 10px;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍽️ Restaurante Institucional</h1>
            <p>IFBA - Instituto Federal da Bahia</p>
        </div>

        <p>Olá, <strong>{{ $nomeEstudante }}</strong>!</p>

        <p>Sua justificativa de falta foi analisada e recebeu a seguinte decisão:</p>

        <div style="text-align: center;">
            @if($decisao === 'aprovada')
                <span class="status-badge status-aprovada">✅ APROVADA</span>
            @else
                <span class="status-badge status-rejeitada">❌ REJEITADA</span>
            @endif
        </div>

        <div class="details">
            <p><strong>📅 Data da refeição:</strong> 
                {{ $justificativa->presenca?->refeicao?->cardapio?->data_do_cardapio?->format('d/m/Y') ?? 'N/A' }}
            </p>
            <p><strong>🍴 Turno:</strong> 
                {{ ucfirst($justificativa->presenca?->refeicao?->turno ?? 'N/A') }}
            </p>
            <p><strong>📝 Seu motivo:</strong> 
                {{ $justificativa->motivo }}
            </p>
            <p><strong>📆 Data da análise:</strong> 
                {{ $justificativa->aprovado_em?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
            </p>
        </div>

        @if($observacaoAdmin)
        <div class="observacao">
            <h3>💬 Observação do Administrador:</h3>
            <p>{{ $observacaoAdmin }}</p>
        </div>
        @endif

        @if($decisao === 'aprovada')
        <p>Sua falta foi registrada como <strong>falta justificada</strong> e não contará negativamente em seu histórico.</p>
        @else
        <p>Sua falta permanece como <strong>falta injustificada</strong>. Caso discorde da decisão, procure a administração do RI para mais informações.</p>
        @endif

        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>© {{ date('Y') }} - RI IFBA</p>
        </div>
    </div>
</body>
</html>
