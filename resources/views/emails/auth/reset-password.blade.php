@extends('emails.layout')

@section('content')
<div class="greeting">
    Olá, <strong>{{ $user->nome ?? 'Usuário' }}</strong>!
</div>

<div class="message">
    Você solicitou a <strong>redefinição de senha</strong> para sua conta no sistema RI-IFBA. 
    Para criar uma nova senha, clique no botão abaixo:
</div>

<div class="action-button">
    <a href="{{ $url }}" class="btn">
        🔐 Redefinir Senha
    </a>
</div>

<div class="info-box">
    <h4>⚠️ Informações Importantes:</h4>
    <ul style="margin-left: 20px;">
        <li><strong>Este link expira em 60 minutos</strong></li>
        <li>Se você não solicitou esta alteração, ignore este email</li>
        <li>Sua senha atual permanecerá ativa até que seja alterada</li>
        <li>Use uma senha forte com pelo menos 8 caracteres</li>
    </ul>
</div>

<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
    <strong>💡 Dica de Segurança:</strong><br>
    Crie uma senha que contenha letras maiúsculas, minúsculas, números e símbolos. 
    Não compartilhe suas credenciais com outras pessoas.
</div>

<div style="margin-top: 25px; font-size: 14px; color: #666;">
    Se o botão não funcionar, copie e cole este link no seu navegador:<br>
    <code style="background: #f1f1f1; padding: 5px; border-radius: 3px; word-break: break-all;">{{ $url }}</code>
</div>

<div style="margin-top: 30px; text-align: center; font-size: 14px; color: #999;">
    ⏰ <em>Este email foi enviado em {{ now()->format('d/m/Y \à\s H:i') }}</em>
</div>
@endsection