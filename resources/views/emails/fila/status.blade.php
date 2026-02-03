@extends('emails.layout')

@section('content')
<div class="greeting">
    Oi, <strong>{{ $usuario->nome }}</strong>! 🔔
</div>

<div class="message">
    {{ $mensagem ?? 'Você tem uma atualização sobre sua posição na fila do restaurante.' }}
</div>

@if(isset($fila))
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; padding: 30px; margin: 25px 0; text-align: center;">
    <h2 style="margin: 0 0 20px 0; font-size: 24px;">
        🎯 Status da Fila
    </h2>
    
    <div style="display: inline-block; background: rgba(255,255,255,0.2); border-radius: 8px; padding: 20px; margin: 10px;">
        <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
            #{{ $posicao ?? $fila->posicao_atual ?? '?' }}
        </div>
        <div style="font-size: 14px; opacity: 0.9;">
            Sua Posição
        </div>
    </div>
    
    @if(isset($estimativa))
    <div style="display: inline-block; background: rgba(255,255,255,0.2); border-radius: 8px; padding: 20px; margin: 10px;">
        <div style="font-size: 24px; font-weight: bold; margin-bottom: 5px;">
            ~{{ $estimativa }}min
        </div>
        <div style="font-size: 14px; opacity: 0.9;">
            Tempo Estimado
        </div>
    </div>
    @endif
</div>
@endif

@if(isset($status))
<div class="info-box">
    <h4>📊 Informações da Fila:</h4>
    <ul style="list-style: none; padding: 0;">
        <li><strong>🎯 Status:</strong> {{ $status }}</li>
        @if(isset($turno))
        <li><strong>🍽️ Refeição:</strong> {{ $turno }}</li>
        @endif
        @if(isset($data))
        <li><strong>📅 Data:</strong> {{ $data->format('d/m/Y') }}</li>
        @endif
        @if(isset($pessoas_na_frente))
        <li><strong>👥 Pessoas na frente:</strong> {{ $pessoas_na_frente }}</li>
        @endif
    </ul>
</div>
@endif

@if($status === 'CHAMADO' || $status === 'SUA_VEZ')
<div style="background: #d4edda; border: 2px solid #c3e6cb; border-radius: 10px; padding: 20px; margin: 25px 0; text-align: center;">
    <h3 style="color: #155724; margin-bottom: 15px;">🎉 É SUA VEZ!</h3>
    <p style="color: #155724; font-size: 16px; font-weight: 500; margin: 0;">
        Dirija-se ao restaurante agora! Você tem <strong>10 minutos</strong> para se apresentar.
    </p>
</div>
@endif

@if(isset($actionUrl))
<div class="action-button">
    <a href="{{ $actionUrl }}" class="btn">
        {{ $actionText ?? '📱 Abrir App' }}
    </a>
</div>
@endif

<div style="background: #f8f9fa; border-radius: 5px; padding: 15px; margin: 25px 0;">
    <h4 style="color: #495057; margin-bottom: 10px;">💡 Dicas:</h4>
    <ul style="color: #6c757d; margin: 0;">
        <li>Mantenha seu celular por perto</li>
        <li>Chegue com antecedência quando for chamado</li>
        <li>Traga seu documento de identificação</li>
    </ul>
</div>

<div style="margin-top: 30px; text-align: center; font-size: 14px; color: #999;">
    🤖 <em>Notificação automática - {{ now()->format('d/m/Y \à\s H:i') }}</em>
</div>
@endsection