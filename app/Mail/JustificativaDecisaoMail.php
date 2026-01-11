<?php

namespace App\Mail;

use App\Models\Justificativa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de notificação sobre decisão de justificativa (RF10)
 * 
 * Enviado ao estudante quando sua justificativa é aprovada ou rejeitada.
 */
class JustificativaDecisaoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Justificativa $justificativa;
    public string $decisao;
    public string $nomeEstudante;
    public ?string $observacaoAdmin;

    /**
     * Create a new message instance.
     */
    public function __construct(Justificativa $justificativa)
    {
        $this->justificativa = $justificativa;
        $this->decisao = $justificativa->status_justificativa->value;
        $this->nomeEstudante = $justificativa->usuario->name ?? 'Estudante';
        $this->observacaoAdmin = $justificativa->observacao_admin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $tipoDecisao = $this->decisao === 'aprovada' ? 'Aprovada' : 'Rejeitada';
        
        return new Envelope(
            subject: "Justificativa {$tipoDecisao} - RI IFBA",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.justificativa-decisao',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
