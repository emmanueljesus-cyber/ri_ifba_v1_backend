@extends('emails.layout')

@section('content')
<div class="greeting">
    Olá, <strong>{{ $user->nome }}!</strong> 🍽️
</div>

<div class="message">
    {{ $mensagem ?? 'Você tem uma nova notificação sobre suas refeições no RI-IFBA.' }}
</div>

@if(isset($cardapio))
<div style="background: #fff; border: 2px solid #28a745; border-radius: 10px; padding: 25px; margin: 25px 0;">
    <h3 style="color: #1e7e34; text-align: center; margin-bottom: 20px;">
        📅 Cardápio de {{ $cardapio->data->format('d/m/Y') }}
    </h3>
    
    @if($cardapio->almoço)
    <div style="margin-bottom: 20px;">
        <h4 style="color: #fd7e14; margin-bottom: 10px;">🌞 Almoço</h4>
        <p style="background: #fff3cd; padding: 10px; border-radius: 5px; margin: 0;">
            {{ $cardapio->almoço }}
        </p>
    </div>
    @endif
    
    @if($cardapio->jantar)
    <div>
        <h4 style="color: #6f42c1; margin-bottom: 10px;">🌙 Jantar</h4>
        <p style="background: #e2e3f5; padding: 10px; border-radius: 5px; margin: 0;">
            {{ $cardapio->jantar }}
        </p>
    </div>
    @endif
</div>
@endif

@if(isset($actionUrl))
<div class="action-button">
    <a href="{{ $actionUrl }}" class="btn">
        {{ $actionText ?? '🍽️ Ver no Sistema' }}
    </a>
</div>
@endif

<div class="info-box">
    <h4>🕐 Horários de Funcionamento:</h4>
    <ul style="list-style: none; padding: 0;">
        <li><strong>🌞 Almoço:</strong> 11:30 às 13:30</li>
        <li><strong>🌙 Jantar:</strong> 17:30 às 19:00</li>
        <li><strong>📅 Funcionamento:</strong> Segunda a Sexta-feira</li>
    </ul>
</div>

@if(isset($observacoes))
<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0;">
    <h4 style="color: #721c24; margin-bottom: 10px;">⚠️ Observações Importantes:</h4>
    <p style="color: #721c24; margin: 0;">{{ $observacoes }}</p>
</div>
@endif

<div style="margin-top: 30px; text-align: center; font-size: 14px; color: #999;">
    📧 <em>Email automático enviado em {{ now()->format('d/m/Y \à\s H:i') }}</em>
</div>
@endsection