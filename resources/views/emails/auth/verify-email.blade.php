@extends('emails.layout')

@section('content')
<div class="greeting">
    Bem-vindo(a), <strong>{{ $user->nome ?? 'Usuário' }}</strong>! 🎉
</div>

<div class="message">
    Sua conta foi criada com sucesso no sistema <strong>RI-IFBA</strong>! 
    Para começar a usar todos os recursos, você precisa confirmar seu endereço de email.
</div>

<div class="action-button">
    <a href="{{ $url }}" class="btn">
        ✅ Confirmar Email
    </a>
</div>

<div class="info-box">
    <h4>📋 Seus Dados de Cadastro:</h4>
    <ul style="list-style: none; padding: 0;">
        <li><strong>📛 Nome:</strong> {{ $user->nome ?? 'Não informado' }}</li>
        <li><strong>📧 Email:</strong> {{ $user->email ?? 'Não informado' }}</li>
        <li><strong>🎓 Matrícula:</strong> {{ $user->matricula ?? 'Não informado' }}</li>
        <li><strong>👤 Perfil:</strong> {{ $user->perfil_usuario->name ?? 'Não definido' }}</li>
    </ul>
</div>

<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; padding: 20px; margin: 25px 0;">
    <h4 style="color: #0c5460; margin-bottom: 10px;">🍽️ Próximos Passos:</h4>
    <ol style="margin-left: 20px; color: #0c5460;">
        <li>Confirme seu email clicando no botão acima</li>
        <li>Faça login no sistema</li>
        <li>Complete seu perfil se necessário</li>
        <li>Consulte o cardápio do dia</li>
        <li>Gerencie suas refeições</li>
    </ol>
</div>

<div style="margin-top: 25px; font-size: 14px; color: #666;">
    Se o botão não funcionar, copie e cole este link no seu navegador:<br>
    <code style="background: #f1f1f1; padding: 5px; border-radius: 3px; word-break: break-all;">{{ $url }}</code>
</div>

<div style="margin-top: 30px; text-align: center; font-size: 14px; color: #999;">
    ⏰ <em>Confirme dentro de 24 horas - Email enviado em {{ now()->format('d/m/Y \à\s H:i') }}</em>
</div>
@endsection